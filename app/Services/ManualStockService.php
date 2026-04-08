<?php

namespace App\Services;

use App\Models\ManualStockItem;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class ManualStockService
{
    public function __construct(
        private readonly VipaymentService $vipayment,
    ) {}

    public function supportsProduct(string $productId): bool
    {
        $normalizedProductId = Str::lower(trim($productId));

        if ($normalizedProductId === '') {
            return false;
        }

        return collect(config('manual_stock.managed_keywords', []))
            ->contains(fn (string $keyword) => Str::contains($normalizedProductId, Str::lower(trim($keyword))));
    }

    /**
     * @return array<int, array{id: string, name: string, packageSuggestions: array<int, string>}>
     */
    public function managedProductOptions(): array
    {
        $fallback = collect([
            ['id' => 'vip-game-chatgpt', 'name' => 'ChatGPT'],
            ['id' => 'vip-game-capcut-pro', 'name' => 'CapCut Pro'],
        ]);

        if (! $this->vipayment->configured()) {
            return $fallback
                ->map(fn (array $product) => [
                    ...$product,
                    'packageSuggestions' => $this->packageSuggestionsForProduct((string) $product['id']),
                ])
                ->all();
        }

        try {
            $catalog = collect($this->vipayment->getCatalogProducts())
                ->filter(fn (array $product) => $this->supportsProduct((string) ($product['id'] ?? '')))
                ->map(fn (array $product) => [
                    'id' => (string) $product['id'],
                    'name' => (string) $product['name'],
                    'packageSuggestions' => $this->packageSuggestionsForProduct((string) ($product['id'] ?? '')),
                ])
                ->values();

            return $catalog->isNotEmpty()
                ? $catalog->all()
                : $fallback
                    ->map(fn (array $product) => [
                        ...$product,
                        'packageSuggestions' => $this->packageSuggestionsForProduct((string) $product['id']),
                    ])
                    ->all();
        } catch (\Throwable $exception) {
            report($exception);

            return $fallback
                ->map(fn (array $product) => [
                    ...$product,
                    'packageSuggestions' => $this->packageSuggestionsForProduct((string) $product['id']),
                ])
                ->all();
        }
    }

    /**
     * @return array<int, string>
     */
    private function packageSuggestionsForProduct(string $productId): array
    {
        return collect(config("manual_stock.catalog_offers.{$productId}", []))
            ->map(fn (mixed $offer) => is_array($offer) ? trim((string) data_get($offer, 'option.label', '')) : '')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array{product_id:string,product_name?:string|null,package_label:string,stock_label?:string|null,stock_values:string,notes?:string|null}  $payload
     * @return Collection<int, ManualStockItem>
     */
    public function storeFromTextarea(array $payload, ?User $admin = null): Collection
    {
        $lines = collect(preg_split('/\r\n|\r|\n/', (string) ($payload['stock_values'] ?? '')) ?: [])
            ->map(fn (string $line) => trim($line))
            ->filter()
            ->values();

        if ($lines->isEmpty()) {
            throw new RuntimeException('Isi stok masih kosong.');
        }

        return DB::transaction(function () use ($payload, $admin, $lines) {
            return $lines->map(function (string $line) use ($payload, $admin) {
                $item = ManualStockItem::create([
                    'product_id' => trim((string) $payload['product_id']),
                    'product_name' => trim((string) ($payload['product_name'] ?? '')) ?: null,
                    'package_label' => trim((string) $payload['package_label']),
                    'stock_label' => trim((string) ($payload['stock_label'] ?? '')) ?: null,
                    'stock_value' => $line,
                    'notes' => trim((string) ($payload['notes'] ?? '')) ?: null,
                    'status' => ManualStockItem::STATUS_AVAILABLE,
                    'created_by_user_id' => $admin?->id,
                ]);

                $this->assignWaitingTransactionForItem($item);

                return $item->fresh();
            });
        });
    }

    public function preparePaidTransaction(Transaction $transaction): Transaction
    {
        if ($transaction->product_source !== Transaction::PRODUCT_SOURCE_MANUAL_STOCK) {
            return $transaction;
        }

        return DB::transaction(function () use ($transaction) {
            $freshTransaction = Transaction::query()
                ->with('manualStockItem')
                ->lockForUpdate()
                ->findOrFail($transaction->id);

            $stockItem = $freshTransaction->manualStockItem;

            if (! $stockItem) {
                $stockItem = $this->reserveMatchingStock($freshTransaction);
            }

            $freshTransaction->forceFill([
                'status' => Transaction::STATUS_PROCESSING,
                'vipayment_status' => 'manual-stock',
                'manual_fulfillment_status' => $stockItem
                    ? Transaction::MANUAL_FULFILLMENT_READY_TO_SEND
                    : Transaction::MANUAL_FULFILLMENT_WAITING_STOCK,
                'error_message' => null,
            ])->save();

            return $freshTransaction->fresh();
        });
    }

    public function completeFulfillment(
        Transaction $transaction,
        ?User $admin = null,
        ?string $fulfillmentNote = null,
        bool $allowWithoutStock = false,
    ): Transaction
    {
        if ($transaction->product_source !== Transaction::PRODUCT_SOURCE_MANUAL_STOCK) {
            throw new RuntimeException('Transaksi ini bukan transaksi stok manual.');
        }

        if ($transaction->payment_status !== Transaction::PAYMENT_STATUS_PAID) {
            throw new RuntimeException('Transaksi belum dibayar.');
        }

        return DB::transaction(function () use ($transaction, $admin, $fulfillmentNote, $allowWithoutStock) {
            $freshTransaction = Transaction::query()
                ->with('manualStockItem')
                ->lockForUpdate()
                ->findOrFail($transaction->id);

            $stockItem = $freshTransaction->manualStockItem;

            if (! $stockItem) {
                $stockItem = $this->reserveMatchingStock($freshTransaction);
            }

            if (! $stockItem && ! $allowWithoutStock) {
                throw new RuntimeException('Belum ada stok yang cocok untuk transaksi ini.');
            }

            if ($stockItem) {
                $stockItem->forceFill([
                    'status' => ManualStockItem::STATUS_USED,
                    'used_at' => now(),
                ])->save();
            }

            $freshTransaction->forceFill([
                'status' => Transaction::STATUS_COMPLETED,
                'vipayment_status' => null,
                'manual_fulfillment_status' => Transaction::MANUAL_FULFILLMENT_SENT,
                'fulfilled_at' => now(),
                'fulfilled_by_user_id' => $admin?->id,
                'error_message' => null,
                'fulfillment_note' => $fulfillmentNote ?: $freshTransaction->fulfillment_note,
            ])->save();

            return $freshTransaction->fresh();
        });
    }

    /**
     * @return array<int, array{id:int,productId:string,productName:string,packageLabel:string,stockLabel:string|null,stockValue:string,notes:string|null,status:string,reservedForPublicId:string|null,createdAtLabel:string,reservedAtLabel:string|null,usedAtLabel:string|null}>
     */
    public function adminStockItemsPayload(int $limit = 120): array
    {
        return ManualStockItem::query()
            ->with('reservedTransaction')
            ->latest('updated_at')
            ->latest('created_at')
            ->take($limit)
            ->get()
            ->map(fn (ManualStockItem $item) => [
                'id' => (int) $item->id,
                'productId' => (string) $item->product_id,
                'productName' => (string) ($item->product_name ?: Str::headline(str_replace('-', ' ', $item->product_id))),
                'packageLabel' => (string) $item->package_label,
                'stockLabel' => $item->stock_label,
                'stockValue' => (string) $item->stock_value,
                'notes' => $item->notes,
                'status' => (string) $item->status,
                'reservedForPublicId' => $item->reservedTransaction?->public_id,
                'createdAtLabel' => $item->created_at?->locale('id')->translatedFormat('d M Y, H:i') ?? '-',
                'reservedAtLabel' => $item->reserved_at?->locale('id')->translatedFormat('d M Y, H:i'),
                'usedAtLabel' => $item->used_at?->locale('id')->translatedFormat('d M Y, H:i'),
            ])
            ->values()
            ->all();
    }

    private function assignWaitingTransactionForItem(ManualStockItem $item): ?Transaction
    {
        $transaction = Transaction::query()
            ->where('product_source', Transaction::PRODUCT_SOURCE_MANUAL_STOCK)
            ->where('payment_status', Transaction::PAYMENT_STATUS_PAID)
            ->where('status', Transaction::STATUS_PROCESSING)
            ->where('manual_fulfillment_status', Transaction::MANUAL_FULFILLMENT_WAITING_STOCK)
            ->where('product_id', $item->product_id)
            ->where('package_label', $item->package_label)
            ->orderByRaw('paid_at is null')
            ->orderBy('paid_at')
            ->orderBy('created_at')
            ->lockForUpdate()
            ->first();

        if (! $transaction) {
            return null;
        }

        $item->forceFill([
            'status' => ManualStockItem::STATUS_RESERVED,
            'reserved_for_transaction_id' => $transaction->id,
            'reserved_at' => now(),
        ])->save();

        $transaction->forceFill([
            'manual_fulfillment_status' => Transaction::MANUAL_FULFILLMENT_READY_TO_SEND,
            'error_message' => null,
        ])->save();

        return $transaction->fresh();
    }

    private function reserveMatchingStock(Transaction $transaction): ?ManualStockItem
    {
        $item = ManualStockItem::query()
            ->where('status', ManualStockItem::STATUS_AVAILABLE)
            ->where('product_id', $transaction->product_id)
            ->where('package_label', $transaction->package_label)
            ->orderBy('created_at')
            ->lockForUpdate()
            ->first();

        if (! $item) {
            return null;
        }

        $item->forceFill([
            'status' => ManualStockItem::STATUS_RESERVED,
            'reserved_for_transaction_id' => $transaction->id,
            'reserved_at' => now(),
        ])->save();

        return $item->fresh();
    }
}
