<?php

namespace App\Console\Commands;

use App\Services\LyvaflowService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SendPrivateInstallmentReminderCommand extends Command
{
    protected $signature = 'lyva:private-installment:remind {--force : Kirim reminder walau minggu ini sudah pernah dikirim}';

    protected $description = 'Kirim reminder WhatsApp mingguan untuk halaman private pembayaran';

    public function handle(LyvaflowService $lyvaflow): int
    {
        if (! config('private_installment.enabled', true)) {
            $this->line('Halaman private pembayaran sedang nonaktif.');

            return self::SUCCESS;
        }

        if (! config('private_installment.reminder.enabled', true)) {
            $this->line('Reminder mingguan private pembayaran sedang nonaktif.');

            return self::SUCCESS;
        }

        if (! $lyvaflow->configured()) {
            $this->warn('LYVAFLOW belum dikonfigurasi, reminder tidak dikirim.');

            return self::SUCCESS;
        }

        $target = $lyvaflow->normalizeWhatsappNumber((string) config('private_installment.reminder.target_whatsapp', ''));

        if ($target === '') {
            $this->warn('Nomor WhatsApp target reminder belum valid.');

            return self::SUCCESS;
        }

        $now = now()->timezone('Asia/Jakarta');
        $weekKey = $now->format('o-\WW');
        $cacheKey = 'private-installment:weekly-reminder:'.$weekKey;

        if (! $this->option('force') && Cache::has($cacheKey)) {
            $this->line('Reminder private pembayaran minggu ini sudah pernah dikirim.');

            return self::SUCCESS;
        }

        $message = $lyvaflow->composeStructuredMessage(
            'Reminder Private Pembayaran',
            [
                'Pengingat mingguan untuk halaman pembayaran private SeaBank.',
                'Link private: '.$this->privatePageUrl(),
            ],
            [[
                'title' => 'Ringkasan',
                'lines' => [
                    'Produk: '.(string) config('private_installment.product_name'),
                    'Total utang: '.$this->formatRupiah($this->targetAmount()),
                    'Sudah dibayar: '.$this->formatRupiah($this->paidAmount()),
                    'Sisa utang: '.$this->formatRupiah($this->remainingAmount()),
                    'Nominal bebas, minimal: '.$this->formatRupiah($this->minimumAmount()),
                    'Bank tujuan: '.(string) config('private_installment.bank_name', 'SeaBank'),
                    'Nomor rekening: '.(string) config('private_installment.account_number', ''),
                    'Atas nama: '.(string) config('private_installment.account_holder', ''),
                ],
            ]],
            [
                'Cek pembayaran dari notifikasi uang masuk via email, lalu tandai sudah dibayar dari panel admin.',
            ],
        );

        try {
            $lyvaflow->sendWhatsappMessage($target, $message);
            Cache::put($cacheKey, $now->toIso8601String(), now()->addDays(8));
            $this->info('Reminder private pembayaran berhasil dikirim ke '.$target.'.');

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            report($exception);
            $this->error($exception->getMessage() ?: 'Reminder private pembayaran gagal dikirim.');

            return self::FAILURE;
        }
    }

    private function minimumAmount(): int
    {
        $remainingAmount = $this->remainingAmount();
        $configuredMinimum = max(50000, (int) config('private_installment.minimum_amount', 50000));

        if ($remainingAmount > 0 && $remainingAmount < $configuredMinimum) {
            return $remainingAmount;
        }

        return $configuredMinimum;
    }

    private function targetAmount(): int
    {
        return max(0, (int) config('private_installment.target_amount', 5030000));
    }

    private function paidAmount(): int
    {
        return (int) \App\Models\Transaction::query()
            ->where('product_id', (string) config('private_installment.product_id'))
            ->where('payment_status', \App\Models\Transaction::PAYMENT_STATUS_PAID)
            ->sum('total');
    }

    private function remainingAmount(): int
    {
        return max(0, $this->targetAmount() - $this->paidAmount());
    }

    private function formatRupiah(int $amount): string
    {
        return 'Rp'.number_format(max(0, $amount), 0, ',', '.');
    }

    private function privatePageUrl(): string
    {
        $url = route('private-installment.show');
        $accessKey = trim((string) config('private_installment.access_key', ''));

        if ($accessKey === '') {
            return $url;
        }

        return $url.(str_contains($url, '?') ? '&' : '?').'key='.urlencode($accessKey);
    }
}
