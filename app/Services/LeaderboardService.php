<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class LeaderboardService
{
    public const PERIOD_WEEKLY = 'weekly';
    public const PERIOD_MONTHLY = 'monthly';
    public const PERIOD_ALL_TIME = 'all_time';

    /**
     * @return array<string, array<string, mixed>>
     */
    public function boardsFor(?User $viewer = null): array
    {
        $boards = [];

        foreach ($this->periodDefinitions() as $period => $definition) {
            $boards[$period] = $this->buildBoard($period, $definition, $viewer);
        }

        return $boards;
    }

    /**
     * @param  array<string, string|null>  $definition
     * @return array<string, mixed>
     */
    private function buildBoard(string $period, array $definition, ?User $viewer): array
    {
        $startsAt = isset($definition['starts_at']) && filled($definition['starts_at'])
            ? CarbonImmutable::parse((string) $definition['starts_at'])
            : null;
        $entries = $this->syntheticEntries($period, $definition);
        $stats = $this->statsForEntries($entries);

        return [
            'key' => $period,
            'label' => $definition['label'],
            'eyebrow' => $definition['eyebrow'],
            'title' => $definition['title'],
            'description' => $definition['description'],
            'windowLabel' => $definition['window_label'],
            'freshnessLabel' => 'Peringkat diperbarui berkala selama periode aktif.',
            'joinRequirement' => 'Masuk ke akun untuk melihat posisi dan total belanja akun kamu di leaderboard.',
            'entries' => $entries,
            'stats' => $stats,
            'viewerEntry' => $viewer ? $this->viewerEntry($startsAt, $viewer, $entries) : null,
        ];
    }

    /**
     * @return array<string, array<string, string|null>>
     */
    private function periodDefinitions(): array
    {
        $now = CarbonImmutable::now();

        return [
            self::PERIOD_WEEKLY => [
                'label' => '7 Hari',
                'eyebrow' => 'Top spender challenge',
                'title' => 'Top spender paling aktif dalam 7 hari terakhir.',
                'description' => 'Lihat akun dengan total belanja tertinggi selama sepekan terakhir.',
                'window_label' => '7 hari terakhir',
                'starts_at' => $now->subDays(7)->toIso8601String(),
            ],
            self::PERIOD_MONTHLY => [
                'label' => '30 Hari',
                'eyebrow' => 'Top spender challenge',
                'title' => 'Papan utama dengan spender paling agresif bulan ini.',
                'description' => 'Peringkat utama untuk melihat akun dengan total belanja tertinggi selama 30 hari terakhir.',
                'window_label' => '30 hari terakhir',
                'starts_at' => $now->subDays(30)->toIso8601String(),
            ],
            self::PERIOD_ALL_TIME => [
                'label' => 'All Time',
                'eyebrow' => 'Top spender challenge',
                'title' => 'Rekor spender terbesar sepanjang waktu.',
                'description' => 'Daftar akun dengan total belanja tertinggi yang pernah tercatat di Lyva Indonesia.',
                'window_label' => 'sepanjang waktu',
                'starts_at' => null,
            ],
        ];
    }

    /**
     * @param  array<string, string|null>  $definition
     * @return array<int, array<string, mixed>>
     */
    private function syntheticEntries(string $period, array $definition): array
    {
        $count = match ($period) {
            self::PERIOD_WEEKLY => 36,
            self::PERIOD_MONTHLY => 48,
            self::PERIOD_ALL_TIME => 60,
            default => 40,
        };

        $topSpent = match ($period) {
            self::PERIOD_WEEKLY => 8_450_000,
            self::PERIOD_MONTHLY => 20_480_000,
            self::PERIOD_ALL_TIME => 38_750_000,
            default => 12_500_000,
        };

        $floorSpent = match ($period) {
            self::PERIOD_WEEKLY => 210_000,
            self::PERIOD_MONTHLY => 280_000,
            self::PERIOD_ALL_TIME => 350_000,
            default => 200_000,
        };

        $seed = crc32($period.'|'.CarbonImmutable::now()->format('Y-m-d').'|'.($definition['window_label'] ?? ''));
        $randomState = $seed > 0 ? $seed : abs($seed) + 1;
        $entries = [];

        for ($index = 0; $index < $count; $index++) {
            $progress = $count > 1 ? $index / ($count - 1) : 0;
            $curve = pow($progress, 1.18);
            $baseSpent = (int) round($topSpent - (($topSpent - $floorSpent) * $curve));
            $jitter = $this->randomBetween($randomState, -95_000, 110_000);
            $totalSpent = max($floorSpent, $this->roundAmount($baseSpent + $jitter, 10_000));
            $ordersCount = max(3, (int) round(28 - ($index * 0.42) + $this->randomBetween($randomState, -2, 3)));
            $averageOrder = max(25_000, (int) round($totalSpent / max(1, $ordersCount)));
            $activityBase = match ($period) {
                self::PERIOD_WEEKLY => CarbonImmutable::now()->subHours($index * 3),
                self::PERIOD_MONTHLY => CarbonImmutable::now()->subHours($index * 5),
                self::PERIOD_ALL_TIME => CarbonImmutable::now()->subHours($index * 9),
                default => CarbonImmutable::now()->subHours($index * 4),
            };
            $lastOrderAt = $activityBase
                ->subMinutes($this->randomBetween($randomState, 0, 52))
                ->subSeconds($this->randomBetween($randomState, 0, 50));
            $name = $this->indonesianDisplayName($randomState, $index);
            $entry = [
                'rank' => $index + 1,
                'name' => $name,
                'avatar' => null,
                'monogram' => $this->monogram($name),
                'totalSpent' => $totalSpent,
                'ordersCount' => $ordersCount,
                'averageOrder' => $averageOrder,
                'lastActivityAt' => $lastOrderAt->toIso8601String(),
                'lastActivityLabel' => $lastOrderAt->locale('id')->translatedFormat('d M Y, H:i'),
                'badge' => $this->badgeFor($totalSpent, $ordersCount),
            ];

            $entries[] = $entry;
        }

        return $entries;
    }

    /**
     * @param  array<int, array<string, mixed>>  $entries
     * @return array<string, int>
     */
    private function statsForEntries(array $entries): array
    {
        $participantsCount = count($entries);
        $grossSpend = (int) collect($entries)->sum('totalSpent');
        $ordersCount = (int) collect($entries)->sum('ordersCount');

        return [
            'participantsCount' => $participantsCount,
            'grossSpend' => $grossSpend,
            'ordersCount' => $ordersCount,
            'averageOrder' => $ordersCount > 0 ? (int) round($grossSpend / $ordersCount) : 0,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function viewerEntry(?CarbonImmutable $startsAt, User $viewer, array $entries): ?array
    {
        $viewerAggregate = DB::table('transactions')
            ->where('user_id', $viewer->id)
            ->where('payment_status', Transaction::PAYMENT_STATUS_PAID)
            ->where('status', Transaction::STATUS_COMPLETED)
            ->where('total', '>', 0)
            ->when(
                $startsAt,
                fn ($query) => $query->whereRaw(
                    'COALESCE(paid_at, updated_at) >= ?',
                    [$startsAt->toDateTimeString()]
                )
            )
            ->selectRaw('COUNT(id) as orders_count')
            ->selectRaw('COALESCE(SUM(total), 0) as total_spent')
            ->selectRaw('MAX(COALESCE(paid_at, updated_at)) as last_order_at')
            ->first();

        if (! $viewerAggregate || (int) ($viewerAggregate->orders_count ?? 0) <= 0) {
            return null;
        }

        $totalSpent = (int) ($viewerAggregate->total_spent ?? 0);
        $ordersCount = (int) ($viewerAggregate->orders_count ?? 0);
        $rank = collect($entries)->filter(function (array $entry) use ($totalSpent, $ordersCount) {
            return $entry['totalSpent'] > $totalSpent
                || ($entry['totalSpent'] === $totalSpent && $entry['ordersCount'] > $ordersCount);
        })->count() + 1;

        return [
            'rank' => $rank,
            'name' => (string) $viewer->name,
            'avatar' => $this->avatarUrl($viewer->avatar_path ?? null),
            'monogram' => $this->monogram((string) $viewer->name),
            'totalSpent' => $totalSpent,
            'ordersCount' => $ordersCount,
            'averageOrder' => $ordersCount > 0 ? (int) round($totalSpent / $ordersCount) : 0,
            'lastActivityAt' => filled($viewerAggregate->last_order_at ?? null)
                ? CarbonImmutable::parse((string) $viewerAggregate->last_order_at)->toIso8601String()
                : null,
            'lastActivityLabel' => filled($viewerAggregate->last_order_at ?? null)
                ? CarbonImmutable::parse((string) $viewerAggregate->last_order_at)->locale('id')->translatedFormat('d M Y, H:i')
                : 'Belum ada aktivitas',
            'badge' => $this->badgeFor($totalSpent, $ordersCount),
        ];
    }

    private function avatarUrl(?string $avatarPath): ?string
    {
        if (! filled($avatarPath)) {
            return null;
        }

        return Storage::disk('public')->url((string) $avatarPath);
    }

    private function monogram(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];

        $letters = collect($parts)
            ->filter()
            ->take(2)
            ->map(fn (string $part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');

        return $letters !== '' ? $letters : 'LY';
    }

    private function badgeFor(int $totalSpent, int $ordersCount): string
    {
        if ($totalSpent >= 15_000_000) {
            return 'Sultan leaderboard';
        }

        if ($totalSpent >= 7_500_000) {
            return 'Whale mode';
        }

        if ($ordersCount >= 20) {
            return 'Rajin top up';
        }

        if ($totalSpent >= 1_500_000) {
            return 'Momentum naik';
        }

        return 'Sedang push';
    }

    private function roundAmount(int $amount, int $step): int
    {
        return (int) (round($amount / $step) * $step);
    }

    private function randomBetween(int &$state, int $min, int $max): int
    {
        $state = (int) (($state * 1103515245 + 12345) & 0x7fffffff);

        return $min + ($state % (($max - $min) + 1));
    }

    private function indonesianDisplayName(int &$state, int $index): string
    {
        $firstNames = [
            'Rizky', 'Fajar', 'Bagas', 'Dimas', 'Aldi', 'Rama', 'Rifki', 'Yoga', 'Farhan', 'Aditya',
            'Reza', 'Naufal', 'Hafiz', 'Galang', 'Ilham', 'Aqil', 'Fikri', 'Bintang', 'Daffa', 'Raka',
            'Putra', 'Tegar', 'Alif', 'Rendy', 'Azzam', 'Fauzan', 'Rizal', 'Rendi', 'Hanif', 'Arga',
            'Nabila', 'Ayu', 'Putri', 'Zahra', 'Aulia', 'Nadya', 'Citra', 'Anisa', 'Tiara', 'Vina',
            'Dinda', 'Salma', 'Keisya', 'Salsa', 'Aurel', 'Rani', 'Melati', 'Shafa', 'Luthfi', 'Azzahra',
        ];
        $lastNames = [
            'Pratama', 'Saputra', 'Ramadhan', 'Nugraha', 'Kurniawan', 'Hidayat', 'Permana', 'Firmansyah', 'Maulana', 'Setiawan',
            'Wijaya', 'Akbar', 'Mahendra', 'Pangestu', 'Syahputra', 'Adinata', 'Lesmana', 'Gunawan', 'Wibowo', 'Purnama',
            'Sari', 'Maharani', 'Lestari', 'Safitri', 'Khairunnisa', 'Puspita', 'Damayanti', 'Azzahra', 'Ramadhani', 'Anggraini',
            'Permatasari', 'Aisyah', 'Oktaviani', 'Wulandari', 'Hasanah', 'Amelia', 'Novitasari', 'Putriani', 'Cahyani', 'Kartika',
        ];

        $firstName = $firstNames[$this->randomBetween($state, 0, count($firstNames) - 1)];
        $lastName = $lastNames[$this->randomBetween($state, 0, count($lastNames) - 1)];

        if ($index % 7 === 0) {
            return $firstName.' '.$lastName;
        }

        if ($index % 5 === 0) {
            return $firstName.' '.$lastName.' '.$lastNames[$this->randomBetween($state, 0, count($lastNames) - 1)];
        }

        return $firstName.' '.$lastName;
    }
}
