<?php

namespace App\Services;

use App\Models\FinanceAlertLog;
use App\Models\ManualExpense;
use App\Models\Transaction;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;

class FinanceAlertService
{
    public function __construct(
        private readonly LyvaCoinService $coins,
        private readonly TelegramBotService $telegram,
    ) {}

    /**
     * @return array<int, array{level: string, title: string, body: string}>
     */
    public function alerts(): array
    {
        $now = CarbonImmutable::now('Asia/Jakarta');
        $currentStart = $now->startOfMonth();
        $currentEnd = $now->endOfDay();
        $previousStart = $currentStart->subMonth()->startOfMonth();
        $previousEnd = $currentStart->subMonth()->endOfMonth();

        $current = $this->buildPeriodSummary($currentStart, $currentEnd);
        $previous = $this->buildPeriodSummary($previousStart, $previousEnd);
        $alerts = [];

        if ($current['estimatedNetProfit'] < 0) {
            $alerts[] = [
                'level' => 'critical',
                'title' => 'Profit bersih bulan ini negatif',
                'body' => 'Estimasi profit bersih '.strtolower($current['label']).' sudah minus Rp'.number_format(abs($current['estimatedNetProfit']), 0, ',', '.').'.',
            ];
        }

        $profitDelta = $this->calculateDeltaPercent($current['estimatedNetProfit'], $previous['estimatedNetProfit']);
        if ($profitDelta !== null && $profitDelta <= -15) {
            $alerts[] = [
                'level' => 'warning',
                'title' => 'Profit bersih turun tajam',
                'body' => 'Estimasi profit bersih '.strtolower($current['label']).' turun '.abs($profitDelta).'% dibanding '.strtolower($previous['label']).'.',
            ];
        }

        $expenseDelta = $this->calculateDeltaPercent($current['manualExpensesTotal'], $previous['manualExpensesTotal']);
        if ($expenseDelta !== null && $expenseDelta >= 25 && $current['manualExpensesTotal'] >= 100000) {
            $alerts[] = [
                'level' => 'warning',
                'title' => 'Biaya manual naik cukup tinggi',
                'body' => 'Biaya manual '.strtolower($current['label']).' naik '.$expenseDelta.'% dan sekarang ada di Rp'.number_format($current['manualExpensesTotal'], 0, ',', '.').'.',
            ];
        }

        if ($current['issueOrderCount'] >= 3) {
            $alerts[] = [
                'level' => 'warning',
                'title' => 'Order bermasalah mulai menumpuk',
                'body' => 'Ada '.number_format($current['issueOrderCount'], 0, ',', '.').' order paid yang butuh perhatian di '.strtolower($current['label']).'.',
            ];
        }

        if ($alerts === []) {
            $alerts[] = [
                'level' => 'healthy',
                'title' => 'Kondisi keuangan masih sehat',
                'body' => 'Belum ada sinyal risiko utama dari profit, biaya manual, atau order bermasalah bulan ini.',
            ];
        }

        return array_slice($alerts, 0, 4);
    }

    /**
     * @param  array<int, array{level: string, title: string, body: string}>  $alerts
     */
    public function recordAlerts(array $alerts): void
    {
        $today = now('Asia/Jakarta')->toDateString();
        $detectedAt = now();

        foreach ($alerts as $alert) {
            $fingerprint = $this->fingerprintForAlert($alert);
            $log = FinanceAlertLog::query()->firstOrNew([
                'alert_date' => $today,
                'fingerprint' => $fingerprint,
            ]);

            if (! $log->exists) {
                $log->fill([
                    'level' => $alert['level'],
                    'title' => $alert['title'],
                    'body' => $alert['body'],
                    'first_detected_at' => $detectedAt,
                    'last_detected_at' => $detectedAt,
                    'seen_count' => 1,
                    'meta' => [
                        'source' => 'finance-alert',
                    ],
                ]);
            } else {
                $log->fill([
                    'level' => $alert['level'],
                    'title' => $alert['title'],
                    'body' => $alert['body'],
                    'last_detected_at' => $detectedAt,
                    'seen_count' => (int) $log->seen_count + 1,
                ]);
            }

            $log->save();
        }
    }

    /**
     * @return array<int, array<string, int|string|null>>
     */
    public function recentHistory(int $limit = 12): array
    {
        return FinanceAlertLog::query()
            ->latest('alert_date')
            ->latest('last_detected_at')
            ->limit($limit)
            ->get()
            ->map(function (FinanceAlertLog $log) {
                return [
                    'id' => $log->id,
                    'level' => $log->level,
                    'title' => $log->title,
                    'body' => $log->body,
                    'alertDateLabel' => $log->alert_date?->locale('id')->translatedFormat('d M Y') ?? '-',
                    'firstDetectedLabel' => $log->first_detected_at?->locale('id')->translatedFormat('d M Y, H:i') ?? '-',
                    'lastDetectedLabel' => $log->last_detected_at?->locale('id')->translatedFormat('d M Y, H:i') ?? '-',
                    'lastNotifiedLabel' => $log->last_notified_at?->locale('id')->translatedFormat('d M Y, H:i'),
                    'seenCount' => (int) $log->seen_count,
                ];
            })
            ->all();
    }

    /**
     * @param  array<int, array{level: string, title: string, body: string}>  $alerts
     */
    public function notifyIfNeeded(array $alerts): bool
    {
        $this->recordAlerts($alerts);

        $meaningfulAlerts = collect($alerts)
            ->filter(fn (array $alert) => $alert['level'] !== 'healthy')
            ->values()
            ->all();

        if ($meaningfulAlerts === [] || ! $this->telegram->configured()) {
            return false;
        }

        $fingerprint = sha1(json_encode($meaningfulAlerts));
        $cacheKey = 'finance-alert:'.$fingerprint;

        if (Cache::has($cacheKey)) {
            return false;
        }

        $this->telegram->sendMessage($this->buildTelegramMessage($meaningfulAlerts));
        Cache::put($cacheKey, true, now()->addHours(6));
        $this->markAlertsAsNotified($meaningfulAlerts);

        return true;
    }

    /**
     * @return array{
     *   label: string,
     *   grossRevenue: int,
     *   estimatedGrossProfit: int,
     *   manualExpensesTotal: int,
     *   estimatedNetProfit: int,
     *   paidOrderCount: int,
     *   issueOrderCount: int
     * }
     */
    private function buildPeriodSummary(CarbonImmutable $start, CarbonImmutable $end): array
    {
        $transactions = Transaction::query()
            ->where('payment_status', Transaction::PAYMENT_STATUS_PAID)
            ->where(function ($builder) use ($start, $end) {
                $builder->whereRaw(
                    'COALESCE(paid_at, updated_at, created_at) between ? and ?',
                    [
                        $start->startOfDay()->toDateTimeString(),
                        $end->endOfDay()->toDateTimeString(),
                    ],
                );
            })
            ->select(['status', 'total'])
            ->get();

        $grossRevenue = 0;
        $estimatedGrossProfit = 0;
        $paidOrderCount = 0;
        $issueOrderCount = 0;

        foreach ($transactions as $transaction) {
            $total = (int) ($transaction->total ?? 0);
            $grossRevenue += $total;
            $estimatedGrossProfit += $this->coins->estimatedProfitForAmount($total);
            $paidOrderCount++;

            if (! in_array($transaction->status, [Transaction::STATUS_COMPLETED, Transaction::STATUS_PENDING, Transaction::STATUS_PROCESSING], true)) {
                $issueOrderCount++;
            }
        }

        $manualExpensesTotal = (int) ManualExpense::query()
            ->whereBetween('expense_date', [$start->toDateString(), $end->toDateString()])
            ->sum('amount');

        return [
            'label' => $start->locale('id')->translatedFormat('F Y'),
            'grossRevenue' => $grossRevenue,
            'estimatedGrossProfit' => $estimatedGrossProfit,
            'manualExpensesTotal' => $manualExpensesTotal,
            'estimatedNetProfit' => $estimatedGrossProfit - $manualExpensesTotal,
            'paidOrderCount' => $paidOrderCount,
            'issueOrderCount' => $issueOrderCount,
        ];
    }

    private function calculateDeltaPercent(int $current, int $previous): ?float
    {
        if ($previous === 0) {
            return $current === 0 ? 0.0 : null;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    /**
     * @param  array{level: string, title: string, body: string}  $alert
     */
    private function fingerprintForAlert(array $alert): string
    {
        return sha1(implode('|', [
            $alert['level'],
            $alert['title'],
            $alert['body'],
        ]));
    }

    /**
     * @param  array<int, array{level: string, title: string, body: string}>  $alerts
     */
    private function markAlertsAsNotified(array $alerts): void
    {
        $today = now('Asia/Jakarta')->toDateString();
        $fingerprints = collect($alerts)
            ->map(fn (array $alert) => $this->fingerprintForAlert($alert))
            ->values()
            ->all();

        if ($fingerprints === []) {
            return;
        }

        FinanceAlertLog::query()
            ->where('alert_date', $today)
            ->whereIn('fingerprint', $fingerprints)
            ->update([
                'last_notified_at' => now(),
            ]);
    }

    /**
     * @param  array<int, array{level: string, title: string, body: string}>  $alerts
     */
    private function buildTelegramMessage(array $alerts): string
    {
        $lines = [
            '<b>Alert keuangan LYVA</b>',
            '',
        ];

        foreach ($alerts as $alert) {
            $lines[] = '<b>['.strtoupper($alert['level']).']</b> '.e($alert['title']);
            $lines[] = e($alert['body']);
            $lines[] = '';
        }

        $lines[] = '<b>Panel admin:</b> '.route('admin.finance.index');

        return implode("\n", $lines);
    }
}
