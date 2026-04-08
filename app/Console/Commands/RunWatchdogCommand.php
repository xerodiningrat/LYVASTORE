<?php

namespace App\Console\Commands;

use App\Services\WatchdogService;
use Illuminate\Console\Command;

class RunWatchdogCommand extends Command
{
    protected $signature = 'lyva:watchdog {--notify : Kirim notifikasi Telegram jika ditemukan masalah}';

    protected $description = 'Scan error log terbaru, coba atasi masalah aman secara otomatis, lalu kirim alert ke Telegram jika perlu';

    public function handle(WatchdogService $watchdog): int
    {
        if (! $watchdog->enabled()) {
            $this->warn('Watchdog dinonaktifkan lewat konfigurasi.');

            return self::SUCCESS;
        }

        $issues = $watchdog->collectRecentIssues();

        if ($issues === []) {
            $this->info('Tidak ada error baru yang perlu ditangani.');

            return self::SUCCESS;
        }

        $this->warn('Ditemukan '.count($issues).' issue terbaru.');

        foreach ($issues as $issue) {
            $this->line('['.strtoupper($issue['level']).'] '.$issue['source'].' - '.$issue['message']);
        }

        $actions = $watchdog->attemptAutoFixes($issues);

        foreach ($actions as $action) {
            $this->line($action['label'].': '.$action['status'].' - '.$action['notes']);
        }

        if ($this->option('notify')) {
            $notified = $watchdog->notifyIfNeeded($issues, $actions);
            $this->line($notified ? 'Alert Telegram dikirim.' : 'Alert Telegram tidak dikirim (belum perlu / duplikat / bot belum aktif).');
        }

        return self::SUCCESS;
    }
}
