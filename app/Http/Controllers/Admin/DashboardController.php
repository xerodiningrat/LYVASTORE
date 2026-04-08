<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ManualStockItem;
use App\Models\Transaction;
use App\Services\LyvaCoinService;
use App\Services\SecurityLogService;
use App\Services\SiteSettingService;
use App\Services\VipaymentService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(
        Request $request,
        SiteSettingService $settings,
        VipaymentService $vipayment,
        SecurityLogService $securityLogs,
        LyvaCoinService $coins,
    ): Response
    {
        $now = CarbonImmutable::now();
        $activityExpression = 'COALESCE(paid_at, updated_at, created_at)';
        $completedQuery = Transaction::query()
            ->where('payment_status', Transaction::PAYMENT_STATUS_PAID)
            ->where('status', Transaction::STATUS_COMPLETED);
        $paidQuery = Transaction::query()
            ->where('payment_status', Transaction::PAYMENT_STATUS_PAID);
        $financialStats = $this->buildFinancialStats($paidQuery, $coins, $now);
        $recentTransactions = Transaction::query()
            ->latest('updated_at')
            ->latest('created_at')
            ->take(8)
            ->get();
        $stockAlerts = $this->buildStockAlerts();

        $completedTransactions = (int) (clone $completedQuery)->count();
        $failedTransactions = (int) Transaction::query()
            ->whereIn('status', [Transaction::STATUS_FAILED, Transaction::STATUS_EXPIRED])
            ->count();
        $resolvedTransactions = $completedTransactions + $failedTransactions;
        $securitySnapshot = $securityLogs->dashboardPayload(60);

        return Inertia::render('Dashboard', [
            'stats' => [
                'totalTransactions' => (int) Transaction::query()->count(),
                'completedTransactions' => $completedTransactions,
                'pendingTransactions' => (int) Transaction::query()
                    ->whereIn('status', [Transaction::STATUS_PENDING, Transaction::STATUS_PROCESSING])
                    ->count(),
                'failedTransactions' => $failedTransactions,
                'grossRevenue' => $financialStats['grossRevenue'],
                'paidToday' => $financialStats['paidToday'],
                'paidThisWeek' => $financialStats['paidThisWeek'],
                'paidThisMonth' => $financialStats['paidThisMonth'],
                'completedToday' => (int) (clone $completedQuery)
                    ->whereRaw($activityExpression.' >= ?', [$now->startOfDay()->toDateTimeString()])
                    ->count(),
                'paidOrderCount' => $financialStats['paidOrderCount'],
                'estimatedCostBasis' => $financialStats['estimatedCostBasis'],
                'estimatedGrossProfit' => $financialStats['estimatedGrossProfit'],
                'estimatedProfitToday' => $financialStats['estimatedProfitToday'],
                'estimatedProfitThisWeek' => $financialStats['estimatedProfitThisWeek'],
                'estimatedProfitThisMonth' => $financialStats['estimatedProfitThisMonth'],
                'estimatedMarginPercent' => $financialStats['estimatedMarginPercent'],
                'productOverrideCount' => count($settings->productArtworkOverrides()),
                'marginTierCount' => count($settings->marginTiers()),
                'waitingManualOrders' => (int) Transaction::query()
                    ->where('product_source', Transaction::PRODUCT_SOURCE_MANUAL_STOCK)
                    ->where('payment_status', Transaction::PAYMENT_STATUS_PAID)
                    ->whereIn('status', [Transaction::STATUS_PENDING, Transaction::STATUS_PROCESSING])
                    ->where('manual_fulfillment_status', Transaction::MANUAL_FULFILLMENT_WAITING_STOCK)
                    ->count(),
                'readyManualOrders' => (int) Transaction::query()
                    ->where('product_source', Transaction::PRODUCT_SOURCE_MANUAL_STOCK)
                    ->where('payment_status', Transaction::PAYMENT_STATUS_PAID)
                    ->whereIn('status', [Transaction::STATUS_PENDING, Transaction::STATUS_PROCESSING])
                    ->where('manual_fulfillment_status', Transaction::MANUAL_FULFILLMENT_READY_TO_SEND)
                    ->count(),
                'lowStockPackages' => count($stockAlerts),
                'averageOrderValue' => $completedTransactions > 0
                    ? (int) round(((clone $completedQuery)->sum('total')) / $completedTransactions)
                    : 0,
                'successRate' => $resolvedTransactions > 0
                    ? (int) round(($completedTransactions / $resolvedTransactions) * 100)
                    : 100,
                'unavailableProviderProducts' => count($vipayment->unavailablePublicProductIds()),
                'securityWarnings' => (int) ($securitySnapshot['warningEntries'] ?? 0),
                'securityCritical' => (int) ($securitySnapshot['criticalEntries'] ?? 0),
                'securityUniqueIps' => (int) ($securitySnapshot['uniqueIps'] ?? 0),
            ],
            'performanceSeries' => $this->buildPerformanceSeries($now),
            'actionQueues' => $this->buildActionQueues(),
            'topProducts' => $this->buildTopProducts(),
            'stockAlerts' => $stockAlerts,
            'recentTransactions' => $recentTransactions
                ->map(fn (Transaction $transaction) => $this->mapRecentTransaction($transaction))
                ->values()
                ->all(),
            'branding' => $settings->branding(),
            'securitySnapshot' => [
                'warningEntries' => (int) ($securitySnapshot['warningEntries'] ?? 0),
                'criticalEntries' => (int) ($securitySnapshot['criticalEntries'] ?? 0),
                'uniqueIps' => (int) ($securitySnapshot['uniqueIps'] ?? 0),
                'latestTimestampLabel' => $securitySnapshot['latestTimestampLabel'] ?? null,
                'topEvent' => $securitySnapshot['topEvents'][0]['event'] ?? null,
            ],
        ]);
    }

    /**
     * @return array{
     *     grossRevenue: int,
     *     paidToday: int,
     *     paidThisWeek: int,
     *     paidThisMonth: int,
     *     paidOrderCount: int,
     *     estimatedCostBasis: int,
     *     estimatedGrossProfit: int,
     *     estimatedProfitToday: int,
     *     estimatedProfitThisWeek: int,
     *     estimatedProfitThisMonth: int,
     *     estimatedMarginPercent: float
     * }
     */
    private function buildFinancialStats(Builder $paidQuery, LyvaCoinService $coins, CarbonImmutable $now): array
    {
        $startOfDay = $now->startOfDay();
        $startOfWeek = $now->startOfWeek();
        $startOfMonth = $now->startOfMonth();
        $stats = [
            'grossRevenue' => 0,
            'paidToday' => 0,
            'paidThisWeek' => 0,
            'paidThisMonth' => 0,
            'paidOrderCount' => 0,
            'estimatedCostBasis' => 0,
            'estimatedGrossProfit' => 0,
            'estimatedProfitToday' => 0,
            'estimatedProfitThisWeek' => 0,
            'estimatedProfitThisMonth' => 0,
            'estimatedMarginPercent' => 0.0,
        ];

        foreach ((clone $paidQuery)->select(['id', 'total', 'paid_at', 'updated_at', 'created_at'])->cursor() as $transaction) {
            $total = (int) ($transaction->total ?? 0);
            $estimatedProfit = $coins->estimatedProfitForAmount($total);
            $estimatedCost = max(0, $total - $estimatedProfit);
            $activityAt = CarbonImmutable::parse($transaction->paid_at ?? $transaction->updated_at ?? $transaction->created_at);

            $stats['paidOrderCount']++;
            $stats['grossRevenue'] += $total;
            $stats['estimatedGrossProfit'] += $estimatedProfit;
            $stats['estimatedCostBasis'] += $estimatedCost;

            if ($activityAt->greaterThanOrEqualTo($startOfDay)) {
                $stats['paidToday'] += $total;
                $stats['estimatedProfitToday'] += $estimatedProfit;
            }

            if ($activityAt->greaterThanOrEqualTo($startOfWeek)) {
                $stats['paidThisWeek'] += $total;
                $stats['estimatedProfitThisWeek'] += $estimatedProfit;
            }

            if ($activityAt->greaterThanOrEqualTo($startOfMonth)) {
                $stats['paidThisMonth'] += $total;
                $stats['estimatedProfitThisMonth'] += $estimatedProfit;
            }
        }

        $stats['estimatedMarginPercent'] = $stats['grossRevenue'] > 0
            ? round(($stats['estimatedGrossProfit'] / $stats['grossRevenue']) * 100, 1)
            : 0.0;

        return $stats;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildPerformanceSeries(CarbonImmutable $now): array
    {
        $start = $now->startOfDay()->subDays(6);
        $series = collect(range(0, 6))
            ->mapWithKeys(function (int $offset) use ($start) {
                $day = $start->addDays($offset);

                return [
                    $day->toDateString() => [
                        'date' => $day->toDateString(),
                        'label' => $day->locale('id')->translatedFormat('D'),
                        'fullLabel' => $day->locale('id')->translatedFormat('d M'),
                        'paidRevenue' => 0,
                        'paidOrders' => 0,
                        'completedOrders' => 0,
                    ],
                ];
            });

        $transactions = Transaction::query()
            ->where(function ($query) use ($start) {
                $query
                    ->where('created_at', '>=', $start->toDateTimeString())
                    ->orWhere('updated_at', '>=', $start->toDateTimeString())
                    ->orWhere('paid_at', '>=', $start->toDateTimeString());
            })
            ->get(['payment_status', 'status', 'total', 'created_at', 'updated_at', 'paid_at']);

        foreach ($transactions as $transaction) {
            $activityAt = CarbonImmutable::parse($transaction->paid_at ?? $transaction->updated_at ?? $transaction->created_at);
            $key = $activityAt->toDateString();

            if (! $series->has($key)) {
                continue;
            }

            $entry = $series->get($key);

            if ($transaction->payment_status === Transaction::PAYMENT_STATUS_PAID) {
                $entry['paidRevenue'] += (int) $transaction->total;
                $entry['paidOrders']++;
            }

            if ($transaction->status === Transaction::STATUS_COMPLETED) {
                $entry['completedOrders']++;
            }

            $series->put($key, $entry);
        }

        return $series->values()->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildActionQueues(): array
    {
        return [
            [
                'title' => 'Menunggu stok manual',
                'count' => (int) Transaction::query()
                    ->where('product_source', Transaction::PRODUCT_SOURCE_MANUAL_STOCK)
                    ->where('payment_status', Transaction::PAYMENT_STATUS_PAID)
                    ->whereIn('status', [Transaction::STATUS_PENDING, Transaction::STATUS_PROCESSING])
                    ->where('manual_fulfillment_status', Transaction::MANUAL_FULFILLMENT_WAITING_STOCK)
                    ->count(),
                'description' => 'Order sudah dibayar, tapi belum ada stok akun yang cocok.',
                'tone' => 'danger',
                'href' => route('admin.manual-stock.index'),
                'ctaLabel' => 'Isi stok sekarang',
                'items' => Transaction::query()
                    ->where('product_source', Transaction::PRODUCT_SOURCE_MANUAL_STOCK)
                    ->where('payment_status', Transaction::PAYMENT_STATUS_PAID)
                    ->whereIn('status', [Transaction::STATUS_PENDING, Transaction::STATUS_PROCESSING])
                    ->where('manual_fulfillment_status', Transaction::MANUAL_FULFILLMENT_WAITING_STOCK)
                    ->latest('updated_at')
                    ->take(4)
                    ->get()
                    ->map(fn (Transaction $transaction) => $this->mapQueueTransaction($transaction))
                    ->values()
                    ->all(),
            ],
            [
                'title' => 'Siap dikirim admin',
                'count' => (int) Transaction::query()
                    ->where('product_source', Transaction::PRODUCT_SOURCE_MANUAL_STOCK)
                    ->where('payment_status', Transaction::PAYMENT_STATUS_PAID)
                    ->whereIn('status', [Transaction::STATUS_PENDING, Transaction::STATUS_PROCESSING])
                    ->where('manual_fulfillment_status', Transaction::MANUAL_FULFILLMENT_READY_TO_SEND)
                    ->count(),
                'description' => 'Stok sudah ada, tinggal kirim data akun lalu selesaikan.',
                'tone' => 'info',
                'href' => route('admin.transactions.index'),
                'ctaLabel' => 'Buka monitor transaksi',
                'items' => Transaction::query()
                    ->where('product_source', Transaction::PRODUCT_SOURCE_MANUAL_STOCK)
                    ->where('payment_status', Transaction::PAYMENT_STATUS_PAID)
                    ->whereIn('status', [Transaction::STATUS_PENDING, Transaction::STATUS_PROCESSING])
                    ->where('manual_fulfillment_status', Transaction::MANUAL_FULFILLMENT_READY_TO_SEND)
                    ->latest('updated_at')
                    ->take(4)
                    ->get()
                    ->map(fn (Transaction $transaction) => $this->mapQueueTransaction($transaction))
                    ->values()
                    ->all(),
            ],
            [
                'title' => 'Checkout belum dibayar',
                'count' => (int) Transaction::query()
                    ->where('status', Transaction::STATUS_PENDING)
                    ->where('payment_status', Transaction::PAYMENT_STATUS_UNPAID)
                    ->count(),
                'description' => 'Invoice aktif yang masih menunggu pembayaran customer.',
                'tone' => 'warning',
                'href' => route('admin.transactions.index'),
                'ctaLabel' => 'Pantau pembayaran',
                'items' => Transaction::query()
                    ->where('status', Transaction::STATUS_PENDING)
                    ->where('payment_status', Transaction::PAYMENT_STATUS_UNPAID)
                    ->latest('updated_at')
                    ->take(4)
                    ->get()
                    ->map(fn (Transaction $transaction) => $this->mapQueueTransaction($transaction))
                    ->values()
                    ->all(),
            ],
            [
                'title' => 'Perlu pengecekan',
                'count' => (int) Transaction::query()
                    ->whereIn('status', [Transaction::STATUS_FAILED, Transaction::STATUS_EXPIRED])
                    ->count(),
                'description' => 'Order gagal atau kedaluwarsa yang mungkin butuh tindak lanjut.',
                'tone' => 'neutral',
                'href' => route('admin.transactions.index'),
                'ctaLabel' => 'Lihat riwayat error',
                'items' => Transaction::query()
                    ->whereIn('status', [Transaction::STATUS_FAILED, Transaction::STATUS_EXPIRED])
                    ->latest('updated_at')
                    ->take(4)
                    ->get()
                    ->map(fn (Transaction $transaction) => $this->mapQueueTransaction($transaction))
                    ->values()
                    ->all(),
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildTopProducts(): array
    {
        return Transaction::query()
            ->selectRaw('product_name, COUNT(*) as orders_count, SUM(total) as revenue, MAX(updated_at) as last_order_at')
            ->where('payment_status', Transaction::PAYMENT_STATUS_PAID)
            ->where('status', Transaction::STATUS_COMPLETED)
            ->groupBy('product_name')
            ->orderByDesc('revenue')
            ->take(5)
            ->get()
            ->map(function (object $row, int $index) {
                $lastOrderAt = filled($row->last_order_at)
                    ? CarbonImmutable::parse((string) $row->last_order_at)->locale('id')->translatedFormat('d M Y, H:i')
                    : '-';

                return [
                    'rank' => $index + 1,
                    'productName' => (string) ($row->product_name ?: 'Produk tanpa nama'),
                    'ordersCount' => (int) $row->orders_count,
                    'revenue' => (int) $row->revenue,
                    'lastOrderLabel' => $lastOrderAt,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildStockAlerts(): array
    {
        $stockGroups = ManualStockItem::query()
            ->get(['product_id', 'product_name', 'package_label', 'status'])
            ->groupBy(fn (ManualStockItem $item) => $this->stockGroupingKey(
                (string) $item->product_id,
                (string) $item->package_label,
            ))
            ->map(function (Collection $items) {
                $first = $items->first();

                return [
                    'productId' => (string) ($first?->product_id ?? ''),
                    'productName' => (string) ($first?->product_name ?: 'Produk manual'),
                    'packageLabel' => (string) ($first?->package_label ?? ''),
                    'availableCount' => $items->where('status', ManualStockItem::STATUS_AVAILABLE)->count(),
                    'reservedCount' => $items->where('status', ManualStockItem::STATUS_RESERVED)->count(),
                    'usedCount' => $items->where('status', ManualStockItem::STATUS_USED)->count(),
                ];
            });

        $waitingGroups = Transaction::query()
            ->where('product_source', Transaction::PRODUCT_SOURCE_MANUAL_STOCK)
            ->where('payment_status', Transaction::PAYMENT_STATUS_PAID)
            ->whereIn('status', [Transaction::STATUS_PENDING, Transaction::STATUS_PROCESSING])
            ->where('manual_fulfillment_status', Transaction::MANUAL_FULFILLMENT_WAITING_STOCK)
            ->get(['product_id', 'product_name', 'package_label'])
            ->groupBy(fn (Transaction $transaction) => $this->stockGroupingKey(
                (string) $transaction->product_id,
                (string) $transaction->package_label,
            ))
            ->map(function (Collection $transactions) {
                $first = $transactions->first();

                return [
                    'productId' => (string) ($first?->product_id ?? ''),
                    'productName' => (string) ($first?->product_name ?: 'Produk manual'),
                    'packageLabel' => (string) ($first?->package_label ?? ''),
                    'waitingCount' => $transactions->count(),
                ];
            });

        return collect($stockGroups->keys())
            ->merge($waitingGroups->keys())
            ->unique()
            ->map(function (string $key) use ($stockGroups, $waitingGroups) {
                $stock = $stockGroups->get($key, [
                    'productId' => '',
                    'productName' => 'Produk manual',
                    'packageLabel' => '',
                    'availableCount' => 0,
                    'reservedCount' => 0,
                    'usedCount' => 0,
                ]);
                $waiting = $waitingGroups->get($key, [
                    'productId' => $stock['productId'],
                    'productName' => $stock['productName'],
                    'packageLabel' => $stock['packageLabel'],
                    'waitingCount' => 0,
                ]);

                return [
                    'productId' => (string) ($stock['productId'] ?: $waiting['productId']),
                    'productName' => (string) ($stock['productName'] ?: $waiting['productName']),
                    'packageLabel' => (string) ($stock['packageLabel'] ?: $waiting['packageLabel']),
                    'availableCount' => (int) $stock['availableCount'],
                    'reservedCount' => (int) $stock['reservedCount'],
                    'usedCount' => (int) $stock['usedCount'],
                    'waitingCount' => (int) $waiting['waitingCount'],
                    'href' => route('admin.manual-stock.index'),
                ];
            })
            ->filter(fn (array $alert) => $alert['waitingCount'] > 0 || $alert['availableCount'] <= 2)
            ->sortBy([
                ['waitingCount', 'desc'],
                ['availableCount', 'asc'],
                ['reservedCount', 'desc'],
            ])
            ->take(6)
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function mapRecentTransaction(Transaction $transaction): array
    {
        return [
            'publicId' => (string) $transaction->public_id,
            'customerName' => (string) ($transaction->customer_name ?: 'Guest Customer'),
            'productName' => (string) $transaction->product_name,
            'packageLabel' => (string) $transaction->package_label,
            'status' => (string) $transaction->status,
            'statusLabel' => $this->statusLabel($transaction),
            'paymentStatus' => (string) $transaction->payment_status,
            'paymentStatusLabel' => $this->paymentStatusLabel($transaction),
            'total' => (int) $transaction->total,
            'sourceLabel' => $this->sourceLabel($transaction),
            'updatedAtLabel' => $this->activityLabel($transaction),
            'checkoutUrl' => route('checkout.show', ['transaction' => $transaction->public_id]),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapQueueTransaction(Transaction $transaction): array
    {
        return [
            'publicId' => (string) $transaction->public_id,
            'productName' => (string) $transaction->product_name,
            'customerName' => (string) ($transaction->customer_name ?: 'Guest Customer'),
            'total' => (int) $transaction->total,
            'timeLabel' => $this->activityLabel($transaction),
            'statusLabel' => $this->statusLabel($transaction),
            'checkoutUrl' => route('checkout.show', ['transaction' => $transaction->public_id]),
        ];
    }

    private function activityLabel(Transaction $transaction): string
    {
        $activityAt = $transaction->paid_at ?? $transaction->updated_at ?? $transaction->created_at;

        return $activityAt
            ? CarbonImmutable::parse($activityAt)->locale('id')->translatedFormat('d M Y, H:i')
            : '-';
    }

    private function statusLabel(Transaction $transaction): string
    {
        return match ($transaction->status) {
            Transaction::STATUS_COMPLETED => 'Selesai',
            Transaction::STATUS_PROCESSING => 'Diproses',
            Transaction::STATUS_FAILED => 'Butuh pengecekan',
            Transaction::STATUS_EXPIRED => 'Kedaluwarsa',
            default => 'Menunggu bayar',
        };
    }

    private function paymentStatusLabel(Transaction $transaction): string
    {
        return match ($transaction->payment_status) {
            Transaction::PAYMENT_STATUS_PAID => 'Sudah dibayar',
            Transaction::PAYMENT_STATUS_FAILED => 'Pembayaran gagal',
            Transaction::PAYMENT_STATUS_EXPIRED => 'Pembayaran kedaluwarsa',
            default => 'Belum dibayar',
        };
    }

    private function sourceLabel(Transaction $transaction): string
    {
        return match ($transaction->product_source) {
            Transaction::PRODUCT_SOURCE_MANUAL_STOCK => 'Manual stock',
            Transaction::PRODUCT_SOURCE_VIPAYMENT => 'VIPayment',
            default => 'Manual',
        };
    }

    private function stockGroupingKey(string $productId, string $packageLabel): string
    {
        return trim($productId).'|'.trim($packageLabel);
    }
}
