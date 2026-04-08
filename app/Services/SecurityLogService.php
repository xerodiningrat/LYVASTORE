<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class SecurityLogService
{
    /**
     * @return array{
     *     totalEntries: int,
     *     warningEntries: int,
     *     criticalEntries: int,
     *     uniqueIps: int,
     *     latestTimestampLabel: string|null,
     *     filters: array<string, string|null>,
     *     availableLevels: array<int, string>,
     *     availableEvents: array<int, string>,
     *     topEvents: array<int, array{event: string, count: int}>,
     *     topIps: array<int, array{ip: string, count: int}>,
     *     recentEntries: array<int, array<string, mixed>>,
     * }
     */
    public function dashboardPayload(int $limit = 120, array $filters = []): array
    {
        $normalizedFilters = $this->normalizeFilters($filters);
        $entries = $this->recentEntries($limit, $normalizedFilters);
        $latestTimestamp = $entries->first()['timestampLabel'] ?? null;

        return [
            'totalEntries' => $entries->count(),
            'warningEntries' => $entries->where('level', 'warning')->count(),
            'criticalEntries' => $entries->whereIn('level', ['error', 'critical'])->count(),
            'uniqueIps' => $entries
                ->pluck('context.ip')
                ->filter(fn (mixed $ip) => is_string($ip) && $ip !== '')
                ->unique()
                ->count(),
            'latestTimestampLabel' => is_string($latestTimestamp) ? $latestTimestamp : null,
            'filters' => $normalizedFilters,
            'availableLevels' => ['warning', 'error', 'critical', 'info'],
            'availableEvents' => $this->availableEvents(),
            'topEvents' => $entries
                ->groupBy('event')
                ->map(fn (Collection $group, string $event) => [
                    'event' => $event,
                    'count' => $group->count(),
                ])
                ->sortByDesc('count')
                ->take(6)
                ->values()
                ->all(),
            'topIps' => $entries
                ->filter(fn (array $entry) => filled($entry['context']['ip'] ?? null))
                ->groupBy(fn (array $entry) => (string) $entry['context']['ip'])
                ->map(fn (Collection $group, string $ip) => [
                    'ip' => $ip,
                    'count' => $group->count(),
                ])
                ->sortByDesc('count')
                ->take(6)
                ->values()
                ->all(),
            'recentEntries' => $entries->all(),
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function recentEntries(int $limit = 120, array $filters = []): Collection
    {
        $lines = collect();

        foreach ($this->securityLogFiles() as $file) {
            $fileLines = collect(@file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [])
                ->reverse();

            $lines = $lines->concat($fileLines);

            if ($lines->count() >= $limit * 2) {
                break;
            }
        }

        return $lines
            ->map(fn (string $line) => $this->parseLine($line))
            ->filter(fn (?array $entry) => is_array($entry))
            ->filter(fn (array $entry) => $this->matchesFilters($entry, $filters))
            ->take($limit)
            ->values();
    }

    public function latestLogFile(): ?string
    {
        return $this->securityLogFiles()[0] ?? null;
    }

    /**
     * @return array<int, string>
     */
    private function securityLogFiles(): array
    {
        $files = File::glob(storage_path('logs/security-*.log')) ?: [];
        $legacy = storage_path('logs/security.log');

        if (File::exists($legacy)) {
            $files[] = $legacy;
        }

        rsort($files);

        return array_values(array_unique($files));
    }

    /**
     * @return array<string, mixed>|null
     */
    private function parseLine(string $line): ?array
    {
        if (! preg_match('/^\[(?<timestamp>[^\]]+)\]\s+\w+\.(?<level>[A-Z]+):\s+(?<message>[^\{]*?)(?:\s+(?<context>\{.*\}))?$/', $line, $matches)) {
            return null;
        }

        $context = [];
        $rawContext = trim((string) ($matches['context'] ?? ''));

        if ($rawContext !== '') {
            $decoded = json_decode($rawContext, true);

            if (is_array($decoded)) {
                $context = $decoded;
            }
        }

        $timestamp = trim((string) ($matches['timestamp'] ?? ''));
        $parsedAt = filled($timestamp) ? Carbon::parse($timestamp) : null;
        $event = trim((string) ($matches['message'] ?? ''));

        return [
            'timestamp' => $timestamp,
            'timestampLabel' => $parsedAt?->timezone('Asia/Jakarta')->format('d M Y H:i:s') ?? $timestamp,
            'level' => strtolower(trim((string) ($matches['level'] ?? 'info'))),
            'event' => $event !== '' ? $event : 'security_event',
            'eventLabel' => str($event !== '' ? $event : 'security_event')->replace('_', ' ')->headline()->toString(),
            'context' => $context,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{level: string|null, event: string|null, ip: string|null, search: string|null}
     */
    private function normalizeFilters(array $filters): array
    {
        return [
            'level' => $this->normalizeFilterValue($filters['level'] ?? null),
            'event' => $this->normalizeFilterValue($filters['event'] ?? null),
            'ip' => $this->normalizeFilterValue($filters['ip'] ?? null),
            'search' => $this->normalizeFilterValue($filters['search'] ?? null),
        ];
    }

    private function normalizeFilterValue(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value !== '' ? $value : null;
    }

    /**
     * @param  array<string, mixed>  $entry
     * @param  array<string, string|null>  $filters
     */
    private function matchesFilters(array $entry, array $filters): bool
    {
        $level = Str::lower((string) ($filters['level'] ?? ''));
        if ($level !== '' && Str::lower((string) ($entry['level'] ?? '')) !== $level) {
            return false;
        }

        $event = Str::lower((string) ($filters['event'] ?? ''));
        if ($event !== '' && Str::lower((string) ($entry['event'] ?? '')) !== $event) {
            return false;
        }

        $ip = Str::lower((string) ($filters['ip'] ?? ''));
        if ($ip !== '' && ! Str::contains(Str::lower((string) data_get($entry, 'context.ip', '')), $ip)) {
            return false;
        }

        $search = Str::lower((string) ($filters['search'] ?? ''));
        if ($search === '') {
            return true;
        }

        $haystack = Str::lower(json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '');

        return Str::contains($haystack, $search);
    }

    /**
     * @return array<int, string>
     */
    private function availableEvents(int $limit = 12): array
    {
        return $this->recentEntries(400)
            ->pluck('event')
            ->filter(fn (mixed $event) => is_string($event) && $event !== '')
            ->unique()
            ->values()
            ->take($limit)
            ->all();
    }
}
