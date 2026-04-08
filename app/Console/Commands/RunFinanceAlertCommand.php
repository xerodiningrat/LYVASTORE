<?php

namespace App\Console\Commands;

use App\Services\FinanceAlertService;
use Illuminate\Console\Command;

class RunFinanceAlertCommand extends Command
{
    protected $signature = 'lyva:finance-alert {--notify : Kirim notifikasi Telegram jika ditemukan alert penting}';

    protected $description = 'Scan sinyal keuangan utama seperti profit turun, biaya naik, dan order bermasalah';

    public function handle(FinanceAlertService $financeAlerts): int
    {
        $alerts = $financeAlerts->alerts();
        $financeAlerts->recordAlerts($alerts);

        foreach ($alerts as $alert) {
            $this->line('['.strtoupper($alert['level']).'] '.$alert['title'].' - '.$alert['body']);
        }

        if ($this->option('notify')) {
            $notified = $financeAlerts->notifyIfNeeded($alerts);
            $this->line($notified ? 'Alert Telegram keuangan dikirim.' : 'Alert Telegram keuangan tidak dikirim (aman / duplikat / bot belum aktif).');
        }

        return self::SUCCESS;
    }
}
