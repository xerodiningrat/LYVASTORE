<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PromoCodeService
{
    private const STORAGE_KEY = 'checkout.promo_codes';

    public function __construct(
        private readonly SiteSettingService $settings,
    ) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        $promos = $this->settings->get(self::STORAGE_KEY, []);

        if (! is_array($promos)) {
            return [];
        }

        return collect($promos)
            ->filter(fn (mixed $promo) => is_array($promo))
            ->map(fn (array $promo) => $this->normalizePromo($promo))
            ->filter(fn (array $promo) => $promo['id'] !== '' && $promo['code'] !== '')
            ->sortBy([
                fn (array $promo) => $promo['isActive'] ? 0 : 1,
                fn (array $promo) => $promo['code'],
            ])
            ->values()
            ->all();
    }

    public function save(?string $promoId, array $payload): string
    {
        $resolvedPromoId = filled($promoId) ? trim((string) $promoId) : (string) Str::uuid();
        $promos = collect($this->all())
            ->keyBy(fn (array $promo) => (string) $promo['id']);

        $promos->put($resolvedPromoId, $this->normalizePromo([
            ...$payload,
            'id' => $resolvedPromoId,
        ]));

        $this->settings->put(self::STORAGE_KEY, $promos->values()->all());

        return $resolvedPromoId;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function publicPromos(): array
    {
        $now = now('Asia/Jakarta');

        return collect($this->all())
            ->filter(function (array $promo) use ($now) {
                if (! ($promo['isActive'] ?? false)) {
                    return false;
                }

                $startsAt = $this->parseDate($promo['startsAt'] ?? null);
                $expiresAt = $this->parseDate($promo['expiresAt'] ?? null);

                if ($startsAt && $startsAt->isFuture()) {
                    return false;
                }

                if ($expiresAt && $expiresAt->isPast()) {
                    return false;
                }

                return true;
            })
            ->map(function (array $promo) {
                $typeLabel = $promo['type'] === 'percent'
                    ? rtrim(rtrim(number_format((float) $promo['value'], 2, '.', ''), '0'), '.').'%'
                    : 'Rp'.number_format((int) $promo['value'], 0, ',', '.');

                return [
                    'id' => $promo['id'],
                    'code' => $promo['code'],
                    'label' => $promo['label'],
                    'description' => $promo['description'],
                    'type' => $promo['type'],
                    'value' => $promo['value'],
                    'typeLabel' => $typeLabel,
                    'minimumSubtotal' => (int) ($promo['minimumSubtotal'] ?? 0),
                    'maxDiscount' => $promo['maxDiscount'],
                    'productIds' => $promo['productIds'] ?? [],
                    'startsAt' => $promo['startsAt'],
                    'expiresAt' => $promo['expiresAt'],
                ];
            })
            ->values()
            ->all();
    }

    public function delete(string $promoId): void
    {
        $promos = collect($this->all())
            ->reject(fn (array $promo) => (string) $promo['id'] === trim($promoId))
            ->values()
            ->all();

        $this->settings->put(self::STORAGE_KEY, $promos);
    }

    /**
     * @return array<string, mixed>
     */
    public function preview(?string $rawCode, string $productId, int $subtotal): array
    {
        $code = $this->normalizeCode($rawCode);
        $normalizedProductId = Str::lower(trim($productId));
        $resolvedSubtotal = max(0, $subtotal);

        if ($code === '') {
            return [
                'status' => 'idle',
                'applied' => false,
                'message' => '',
                'code' => '',
                'label' => null,
                'discount' => 0,
                'subtotal' => $resolvedSubtotal,
                'finalTotal' => $resolvedSubtotal,
                'snapshot' => null,
            ];
        }

        $promo = collect($this->all())->first(
            fn (array $item) => (string) $item['code'] === $code
        );

        if (! is_array($promo)) {
            return $this->invalidPreview($code, $resolvedSubtotal, 'Kode promo tidak ditemukan atau sudah tidak aktif.');
        }

        if (! $promo['isActive']) {
            return $this->invalidPreview($code, $resolvedSubtotal, 'Kode promo ini sedang dinonaktifkan.');
        }

        $now = now('Asia/Jakarta');
        $startsAt = $this->parseDate($promo['startsAt'] ?? null);
        $expiresAt = $this->parseDate($promo['expiresAt'] ?? null);

        if ($startsAt && $startsAt->isFuture()) {
            return $this->invalidPreview($code, $resolvedSubtotal, 'Kode promo ini belum masuk periode aktif.');
        }

        if ($expiresAt && $expiresAt->isPast()) {
            return $this->invalidPreview($code, $resolvedSubtotal, 'Kode promo ini sudah melewati masa berlaku.');
        }

        $productIds = collect($promo['productIds'] ?? [])
            ->map(fn (mixed $item) => Str::lower(trim((string) $item)))
            ->filter()
            ->values();

        if ($productIds->isNotEmpty() && ! $productIds->contains($normalizedProductId)) {
            return $this->invalidPreview($code, $resolvedSubtotal, 'Kode promo ini tidak berlaku untuk produk yang sedang kamu pilih.');
        }

        $minimumSubtotal = max(0, (int) ($promo['minimumSubtotal'] ?? 0));

        if ($minimumSubtotal > 0 && $resolvedSubtotal < $minimumSubtotal) {
            return $this->invalidPreview(
                $code,
                $resolvedSubtotal,
                'Kode promo ini baru aktif untuk belanja minimal Rp'.number_format($minimumSubtotal, 0, ',', '.').'.',
            );
        }

        $discount = $this->resolveDiscount($promo, $resolvedSubtotal);

        if ($discount <= 0) {
            return $this->invalidPreview($code, $resolvedSubtotal, 'Kode promo ini belum bisa dipakai untuk nominal tersebut.');
        }

        return [
            'status' => 'applied',
            'applied' => true,
            'message' => 'Promo aktif. Diskon langsung dipotong dari total belanja.',
            'code' => $promo['code'],
            'label' => $promo['label'],
            'description' => $promo['description'],
            'discount' => $discount,
            'subtotal' => $resolvedSubtotal,
            'finalTotal' => max(0, $resolvedSubtotal - $discount),
            'snapshot' => [
                'id' => $promo['id'],
                'code' => $promo['code'],
                'label' => $promo['label'],
                'description' => $promo['description'],
                'type' => $promo['type'],
                'value' => $promo['value'],
                'minimumSubtotal' => $minimumSubtotal,
                'maxDiscount' => $promo['maxDiscount'],
                'productIds' => $productIds->all(),
                'startsAt' => $startsAt?->timezone('Asia/Jakarta')->toIso8601String(),
                'expiresAt' => $expiresAt?->timezone('Asia/Jakarta')->toIso8601String(),
                'validatedAt' => $now->toIso8601String(),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $promo
     */
    private function resolveDiscount(array $promo, int $subtotal): int
    {
        $type = (string) ($promo['type'] ?? 'fixed');
        $value = max(0, (float) ($promo['value'] ?? 0));
        $discount = $type === 'percent'
            ? (int) floor($subtotal * ($value / 100))
            : (int) round($value);

        $maxDiscount = $promo['maxDiscount'] ?? null;

        if ($maxDiscount !== null) {
            $discount = min($discount, max(0, (int) $maxDiscount));
        }

        return max(0, min($subtotal, $discount));
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function normalizePromo(array $payload): array
    {
        $productIds = collect($payload['product_ids'] ?? $payload['productIds'] ?? [])
            ->when(
                is_string($payload['product_ids'] ?? null),
                fn (Collection $items) => collect(preg_split('/[\s,]+/', (string) $payload['product_ids']) ?: [])
            )
            ->map(fn (mixed $item) => Str::lower(trim((string) $item)))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $type = in_array(($payload['type'] ?? null), ['fixed', 'percent'], true)
            ? (string) $payload['type']
            : 'fixed';
        $rawValue = max(0, (float) ($payload['value'] ?? 0));
        $value = $type === 'percent'
            ? min(100, round($rawValue, 2))
            : (int) round($rawValue);

        return [
            'id' => trim((string) ($payload['id'] ?? '')),
            'code' => $this->normalizeCode($payload['code'] ?? ''),
            'label' => filled($payload['label'] ?? null)
                ? trim((string) $payload['label'])
                : $this->normalizeCode($payload['code'] ?? ''),
            'description' => filled($payload['description'] ?? null)
                ? trim((string) $payload['description'])
                : null,
            'type' => $type,
            'value' => $value,
            'minimumSubtotal' => max(0, (int) ($payload['minimum_subtotal'] ?? $payload['minimumSubtotal'] ?? 0)),
            'maxDiscount' => filled($payload['max_discount'] ?? $payload['maxDiscount'] ?? null)
                ? max(0, (int) ($payload['max_discount'] ?? $payload['maxDiscount']))
                : null,
            'productIds' => $productIds,
            'startsAt' => $this->normalizeDate($payload['starts_at'] ?? $payload['startsAt'] ?? null),
            'expiresAt' => $this->normalizeDate($payload['expires_at'] ?? $payload['expiresAt'] ?? null),
            'isActive' => (bool) ($payload['is_active'] ?? $payload['isActive'] ?? true),
        ];
    }

    private function normalizeCode(mixed $value): string
    {
        return Str::upper(preg_replace('/\s+/', '', trim((string) $value)) ?? '');
    }

    private function normalizeDate(mixed $value): ?string
    {
        if (! filled($value)) {
            return null;
        }

        try {
            return Carbon::parse((string) $value, 'Asia/Jakarta')
                ->timezone('Asia/Jakarta')
                ->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            return null;
        }
    }

    private function parseDate(mixed $value): ?Carbon
    {
        if (! filled($value)) {
            return null;
        }

        try {
            return Carbon::parse((string) $value, 'Asia/Jakarta')->timezone('Asia/Jakarta');
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function invalidPreview(string $code, int $subtotal, string $message): array
    {
        return [
            'status' => 'invalid',
            'applied' => false,
            'message' => $message,
            'code' => $code,
            'label' => null,
            'discount' => 0,
            'subtotal' => $subtotal,
            'finalTotal' => $subtotal,
            'snapshot' => null,
        ];
    }
}
