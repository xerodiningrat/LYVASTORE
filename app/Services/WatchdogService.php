<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class WatchdogService
{
    public function __construct(
        private readonly TelegramBotService $telegram,
    ) {}

    public function enabled(): bool
    {
        return (bool) config('watchdog.enabled', true);
    }

    /**
     * @return array<int, array{
     *     source: string,
     *     level: string,
     *     timestamp: string|null,
     *     message: string,
     *     fingerprint: string
     * }>
     */
    public function collectRecentIssues(): array
    {
        $lookbackMinutes = max(1, (int) config('watchdog.lookback_minutes', 10));
        $limit = max(1, (int) config('watchdog.max_entries_per_scan', 8));
        $cutoff = now()->subMinutes($lookbackMinutes);

        return collect((array) config('watchdog.log_files', []))
            ->filter(fn ($path) => is_string($path) && $path !== '' && File::exists($path))
            ->flatMap(fn (string $path) => $this->parseLogFile($path, $cutoff))
            ->sortByDesc(fn (array $entry) => $entry['timestamp'] ?? '')
            ->take($limit)
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<string, string|null>>  $issues
     * @return array<int, array{label: string, status: string, notes: string}>
     */
    public function attemptAutoFixes(array $issues): array
    {
        $actions = [];
        $messages = collect($issues)->pluck('message')->implode("\n");

        if ($messages !== '' && Str::contains($messages, [
            'route [',
            'target class',
            'view [',
            'config cache',
            'bootstrap/cache',
            'cache store',
            'permission denied',
        ])) {
            $actions[] = $this->runArtisanAction('optimize:clear', 'Bersihkan cache aplikasi');
        }

        if ($messages !== '' && Str::contains($messages, [
            'sitemap',
            'robots',
            'google',
            'schema',
        ])) {
            $actions[] = $this->runArtisanAction('lyva:generate-sitemap', 'Generate sitemap ulang');
        }

        return $actions;
    }

    /**
     * @param  array<int, array<string, string|null>>  $issues
     * @param  array<int, array{label: string, status: string, notes: string}>  $actions
     */
    public function notifyIfNeeded(array $issues, array $actions = []): bool
    {
        if ($issues === [] || ! $this->telegram->configured()) {
            return false;
        }

        $fingerprint = sha1(json_encode([
            'issues' => collect($issues)->pluck('fingerprint')->all(),
            'actions' => $actions,
        ]));

        $cacheKey = 'watchdog-alert:'.$fingerprint;
        $dedupeMinutes = max(1, (int) config('watchdog.dedupe_minutes', 30));

        if (Cache::has($cacheKey)) {
            return false;
        }

        $this->telegram->sendMessage($this->buildTelegramMessage($issues, $actions));
        Cache::put($cacheKey, true, now()->addMinutes($dedupeMinutes));

        return true;
    }

    /**
     * @param  array<int, array{
     *     source: string,
     *     level: string,
     *     timestamp: string|null,
     *     message: string,
     *     fingerprint: string
     * }>  $issues
     */
    private function buildTelegramMessage(array $issues, array $actions): string
    {
        $lines = [
            '<b>Watchdog LYVA mendeteksi masalah</b>',
            '',
        ];

        foreach ($issues as $issue) {
            $lines[] = '<b>['.strtoupper($issue['level']).']</b> '.e($issue['source']);
            $lines[] = e(Str::limit($issue['message'], 260));

            if (filled($issue['timestamp'])) {
                $lines[] = '<i>'.$issue['timestamp'].'</i>';
            }

            $lines[] = '';
        }

        if ($actions !== []) {
            $lines[] = '<b>Aksi otomatis</b>';

            foreach ($actions as $action) {
                $icon = $action['status'] === 'resolved' ? 'OK' : ($action['status'] === 'failed' ? 'FAIL' : 'INFO');
                $lines[] = "{$icon} - ".e($action['label']).' - '.e($action['notes']);
            }

            $lines[] = '';
        }

        $lines[] = '<b>Panel admin:</b> '.route('admin.security.index');

        return implode("\n", $lines);
    }

    /**
     * @return array<int, array{
     *     source: string,
     *     level: string,
     *     timestamp: string|null,
     *     message: string,
     *     fingerprint: string
     * }>
     */
    private function parseLogFile(string $path, \Illuminate\Support\Carbon $cutoff): array
    {
        $content = (string) File::get($path);

        if ($content === '') {
            return [];
        }

        $blocks = preg_split('/(?=^\[\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2}\])/m', $content) ?: [];

        return collect($blocks)
            ->map(fn (string $block) => trim($block))
            ->filter()
            ->map(function (string $block) use ($path) {
                if (! preg_match('/^\[(?<timestamp>[^\]]+)\]\s+[^\.\n]+\.(?<level>[A-Z]+):\s*(?<message>.*)$/s', $block, $matches)) {
                    return null;
                }

                $timestamp = trim((string) ($matches['timestamp'] ?? ''));
                $message = trim((string) ($matches['message'] ?? ''));
                $level = Str::lower(trim((string) ($matches['level'] ?? 'info')));

                return [
                    'source' => basename($path),
                    'level' => $level,
                    'timestamp' => $timestamp !== '' ? $timestamp : null,
                    'message' => preg_replace('/\s+/', ' ', $message) ?: '',
                    'fingerprint' => sha1(basename($path).'|'.$level.'|'.$message),
                ];
            })
            ->filter()
            ->filter(function (array $entry) use ($cutoff) {
                if (! in_array($entry['level'], ['error', 'critical', 'alert', 'emergency'], true)) {
                    return false;
                }

                if (! $entry['timestamp']) {
                    return true;
                }

                try {
                    return now()->parse($entry['timestamp'])->greaterThanOrEqualTo($cutoff);
                } catch (\Throwable) {
                    return true;
                }
            })
            ->values()
            ->all();
    }

    /**
     * @return array{label: string, status: string, notes: string}
     */
    private function runArtisanAction(string $command, string $label): array
    {
        try {
            Artisan::call($command);

            return [
                'label' => $label,
                'status' => 'resolved',
                'notes' => trim(Artisan::output()) ?: 'Perintah berhasil dijalankan.',
            ];
        } catch (\Throwable $exception) {
            report($exception);

            return [
                'label' => $label,
                'status' => 'failed',
                'notes' => $exception->getMessage(),
            ];
        }
    }
}
