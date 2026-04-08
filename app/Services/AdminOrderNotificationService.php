<?php

namespace App\Services;

use App\Mail\AdminManualOrderAlertMail;
use App\Models\ManualStockItem;
use App\Models\Transaction;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class AdminOrderNotificationService
{
    public function __construct(
        private readonly TelegramBotService $telegram,
    ) {}

    public function notifyManualOrder(Transaction $transaction, ?ManualStockItem $stockItem = null): Transaction
    {
        if ($transaction->admin_manual_order_notified_at) {
            return $transaction;
        }

        try {
            if ($this->telegram->configured()) {
                $this->telegram->sendMessage(
                    $this->buildTelegramMessage($transaction, $stockItem),
                    $this->buildTelegramActions($transaction, $stockItem),
                );
            }
        } catch (\Throwable $exception) {
            report($exception);
        }

        try {
            $emails = $this->notificationEmails();

            if ($emails !== []) {
                Mail::to($emails)->send(new AdminManualOrderAlertMail($transaction, $this->stockStatusLabel($stockItem)));
            }
        } catch (\Throwable $exception) {
            report($exception);
        }

        $transaction->forceFill([
            'admin_manual_order_notified_at' => now(),
        ])->save();

        return $transaction->fresh();
    }

    /**
     * @return array<int, string>
     */
    private function notificationEmails(): array
    {
        $configuredEmails = collect(config('manual_stock.notification_emails', []))
            ->map(fn (string $email) => Str::lower(trim($email)))
            ->filter()
            ->values();

        if ($configuredEmails->isNotEmpty()) {
            return $configuredEmails->all();
        }

        return collect([
            ...config('admin.owner_emails', []),
            ...config('admin.admin_emails', []),
        ])
            ->map(fn (string $email) => Str::lower(trim($email)))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function buildTelegramMessage(Transaction $transaction, ?ManualStockItem $stockItem): string
    {
        $canConfirmWithoutStock = $this->allowsConfirmationWithoutStock($transaction);

        $lines = [
            '<b>Pesanan manual baru masuk</b>',
            '',
            '<b>ID transaksi:</b> #'.$transaction->public_id,
            '<b>Produk:</b> '.e($transaction->product_name),
            '<b>Paket:</b> '.e($transaction->package_label),
            '<b>Total:</b> Rp'.number_format((int) $transaction->total, 0, ',', '.'),
            '<b>Customer:</b> '.e($transaction->customer_name ?: 'Guest Customer'),
            '<b>WhatsApp:</b> '.e($transaction->customer_whatsapp ?: '-'),
            '<b>Email:</b> '.e($transaction->customer_email ?: '-'),
            '<b>Status stok:</b> '.e($this->stockStatusLabel($stockItem)),
            ...(! $stockItem ? [
                '<b>Aksi admin:</b> '.($canConfirmWithoutStock
                    ? 'Kalau data akun atau invite sudah kamu kirim manual, tekan tombol konfirmasi di bawah ini untuk langsung menyelesaikan order.'
                    : 'Tambahkan stok manual dulu, lalu tekan tombol konfirmasi saat data akun sudah terkirim.'),
            ] : [
                '<b>Aksi admin:</b> Data akun sudah siap, tinggal kirim ke customer lalu tekan tombol konfirmasi di bawah ini.',
            ]),
            '',
            '<b>Panel admin:</b> '.route('admin.transactions.index'),
        ];

        return implode("\n", $lines);
    }

    private function stockStatusLabel(?ManualStockItem $stockItem): string
    {
        if (! $stockItem) {
            return 'Belum ada stok yang cocok';
        }

        return 'Stok sudah disiapkan (#'.$stockItem->id.')';
    }

    /**
     * @return array<string, mixed>
     */
    private function buildTelegramActions(Transaction $transaction, ?ManualStockItem $stockItem): array
    {
        $canConfirmWithoutStock = $this->allowsConfirmationWithoutStock($transaction);
        $buttons = [];

        if ($stockItem || $canConfirmWithoutStock) {
            $buttons[] = [[
                'text' => 'Konfirmasi pesanan',
                'url' => URL::temporarySignedRoute(
                    'admin.transactions.manual.confirm.telegram',
                    now()->addDays(7),
                    ['transaction' => $transaction->id],
                ),
            ]];
        }

        return [
            'reply_markup' => [
                'inline_keyboard' => [
                    ...$buttons,
                    [
                        [
                            'text' => $stockItem || $canConfirmWithoutStock ? 'Buka panel admin' : 'Siapkan stok dulu',
                            'url' => route('admin.transactions.index'),
                        ],
                    ],
                ],
            ],
        ];
    }

    private function allowsConfirmationWithoutStock(Transaction $transaction): bool
    {
        return $this->isInviteOnlyPackage($transaction) || $this->isPrivateAccountPackage($transaction);
    }

    private function isInviteOnlyPackage(Transaction $transaction): bool
    {
        $value = $this->packageSearchText($transaction);

        return $value !== '' && Str::contains($value, ['invite', 'invitation', 'undangan']);
    }

    private function isPrivateAccountPackage(Transaction $transaction): bool
    {
        $value = $this->packageSearchText($transaction);

        return $value !== ''
            && Str::contains($value, ['private account', 'private akun', 'private acc'])
            && ! Str::contains($value, ['sharing']);
    }

    private function packageSearchText(Transaction $transaction): string
    {
        return Str::lower(trim(implode(' ', array_filter([
            (string) ($transaction->product_id ?? ''),
            (string) ($transaction->product_name ?? ''),
            (string) ($transaction->package_label ?? ''),
            (string) ($transaction->package_code ?? ''),
        ]))));
    }
}
