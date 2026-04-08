<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use App\Services\GoogleSheetsSyncService;
use Illuminate\Console\Command;

class GoogleSheetsBackfillTransactionsCommand extends Command
{
    protected $signature = 'lyva:sheets-backfill {--limit=100 : Number of latest transactions to sync}';

    protected $description = 'Backfill latest transactions to Google Sheets webhook';

    public function handle(GoogleSheetsSyncService $sheets): int
    {
        if (! $sheets->configured()) {
            $this->error('Google Sheets webhook belum dikonfigurasi.');

            return self::FAILURE;
        }

        $limit = max(1, (int) $this->option('limit'));
        $transactions = Transaction::query()
            ->latest('id')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();

        $success = 0;

        foreach ($transactions as $transaction) {
            try {
                $sheets->syncTransaction($transaction, 'backfill');
                $success++;
                $this->line('Synced #'.$transaction->public_id);
            } catch (\Throwable $exception) {
                report($exception);
                $this->warn('Gagal sync #'.$transaction->public_id.': '.$exception->getMessage());
            }
        }

        $this->info('Selesai. Berhasil sync '.$success.' transaksi.');

        return self::SUCCESS;
    }
}
