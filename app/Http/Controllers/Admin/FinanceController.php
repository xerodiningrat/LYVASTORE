<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ManualExpense;
use App\Models\Transaction;
use App\Services\FinanceAlertService;
use App\Services\LyvaCoinService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FinanceController extends Controller
{
    public function __invoke(Request $request, LyvaCoinService $coins, FinanceAlertService $financeAlerts): Response
    {
        $validated = $this->validateFilters($request);
        $range = $this->resolveRange($validated);
        $payload = $this->buildFinancePayload($range, $coins);

        return Inertia::render('admin/Finance', [
            'status' => session('status'),
            'filters' => [
                'preset' => $range['preset'],
                'startDate' => $range['start']?->toDateString(),
                'endDate' => $range['end']?->toDateString(),
            ],
            'priorityAlerts' => $financeAlerts->alerts(),
            'alertHistory' => $financeAlerts->recentHistory(),
            ...$payload,
        ]);
    }

    public function exportCsv(Request $request, LyvaCoinService $coins)
    {
        $validated = $this->validateFilters($request);
        $range = $this->resolveRange($validated);
        $payload = $this->buildFinancePayload($range, $coins);
        $filename = 'lyva-finance-'.($range['start']?->format('Ymd') ?? 'all').'-'.($range['end']?->format('Ymd') ?? 'now').'.csv';

        return response()->streamDownload(function () use ($payload, $range) {
            $handle = fopen('php://output', 'wb');

            if (! is_resource($handle)) {
                return;
            }

            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, ['Jenis', 'Label', 'Nilai']);
            fputcsv($handle, ['periode', 'preset', $range['preset']]);
            fputcsv($handle, ['periode', 'tanggal_mulai', $range['start']?->toDateString() ?? 'semua']);
            fputcsv($handle, ['periode', 'tanggal_akhir', $range['end']?->toDateString() ?? 'semua']);
            fputcsv($handle, ['ringkasan', 'omzet_dibayar', $payload['summary']['grossRevenue']]);
            fputcsv($handle, ['ringkasan', 'estimasi_modal', $payload['summary']['estimatedCostBasis']]);
            fputcsv($handle, ['ringkasan', 'estimasi_profit_kotor', $payload['summary']['estimatedGrossProfit']]);
            fputcsv($handle, ['ringkasan', 'biaya_manual', $payload['summary']['manualExpensesTotal']]);
            fputcsv($handle, ['ringkasan', 'estimasi_profit_bersih', $payload['summary']['estimatedNetProfit']]);
            fputcsv($handle, ['ringkasan', 'order_dibayar', $payload['summary']['paidOrderCount']]);
            fputcsv($handle, []);

            fputcsv($handle, ['Perbandingan', 'Periode', 'Omzet', 'Profit Kotor', 'Biaya Manual', 'Profit Bersih', 'Order']);
            foreach (['currentMonth', 'previousMonth'] as $key) {
                $item = $payload['monthComparison'][$key];
                fputcsv($handle, [
                    'perbandingan',
                    $item['label'],
                    $item['grossRevenue'],
                    $item['estimatedGrossProfit'],
                    $item['manualExpensesTotal'],
                    $item['estimatedNetProfit'],
                    $item['paidOrderCount'],
                ]);
            }

            fputcsv($handle, []);
            fputcsv($handle, ['Bulanan', 'Periode', 'Omzet', 'Profit Kotor', 'Biaya Manual', 'Profit Bersih', 'Order']);
            foreach ($payload['monthlySeries'] as $item) {
                fputcsv($handle, [
                    'bulanan',
                    $item['monthLabel'],
                    $item['revenue'],
                    $item['estimatedGrossProfit'],
                    $item['manualExpenses'],
                    $item['estimatedNetProfit'],
                    $item['orders'],
                ]);
            }

            fputcsv($handle, []);
            fputcsv($handle, ['Biaya', 'Tanggal', 'Kategori', 'Judul', 'Nominal', 'Catatan']);
            foreach ($payload['expenses'] as $expense) {
                fputcsv($handle, [
                    'biaya',
                    $expense['expenseDate'],
                    $expense['category'],
                    $expense['title'],
                    $expense['amount'],
                    $expense['notes'],
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function storeExpense(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'amount' => ['required', 'integer', 'min:1'],
            'expense_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        ManualExpense::query()->create([
            'title' => $validated['title'],
            'category' => $validated['category'] ?? null,
            'amount' => (int) $validated['amount'],
            'expense_date' => $validated['expense_date'],
            'notes' => $validated['notes'] ?? null,
            'created_by_user_id' => $request->user()?->id,
        ]);

        return back()->with('status', 'Biaya manual berhasil ditambahkan.');
    }

    public function destroyExpense(ManualExpense $manualExpense): RedirectResponse
    {
        $manualExpense->delete();

        return back()->with('status', 'Biaya manual berhasil dihapus.');
    }

    /**
     * @param  array{preset: string, start: CarbonImmutable|null, end: CarbonImmutable|null}  $range
     * @return array{
     *   summary: array<string, int|float>,
     *   dailySeries: array<int, array<string, int|string>>,
     *   topProducts: array<int, array<string, int|string>>,
     *   expenses: array<int, array<string, int|string|null>>,
     *   expenseCategories: array<int, array<string, int|string>>,
     *   monthlySeries: array<int, array<string, int|string>>,
     *   monthComparison: array<string, mixed>,
     *   insights: array<int, array<string, string>>
     * }
     */
    private function buildFinancePayload(array $range, LyvaCoinService $coins): array
    {
        $query = Transaction::query()
            ->where('payment_status', Transaction::PAYMENT_STATUS_PAID);

        if ($range['start'] && $range['end']) {
            $query->where(function ($builder) use ($range) {
                $builder->whereRaw(
                    'COALESCE(paid_at, updated_at, created_at) between ? and ?',
                    [
                        $range['start']->startOfDay()->toDateTimeString(),
                        $range['end']->endOfDay()->toDateTimeString(),
                    ],
                );
            });
        }

        $transactions = $query
            ->select(['id', 'public_id', 'status', 'total', 'product_name', 'package_label', 'paid_at', 'updated_at', 'created_at'])
            ->latest('id')
            ->get();

        $expensesQuery = ManualExpense::query()
            ->with('createdBy:id,name')
            ->latest('expense_date')
            ->latest('id');

        if ($range['start'] && $range['end']) {
            $expensesQuery->whereBetween('expense_date', [
                $range['start']->toDateString(),
                $range['end']->toDateString(),
            ]);
        }

        $expenses = $expensesQuery->get();
        $summary = [
            'paidOrderCount' => 0,
            'completedOrderCount' => 0,
            'processingOrderCount' => 0,
            'issueOrderCount' => 0,
            'grossRevenue' => 0,
            'estimatedCostBasis' => 0,
            'estimatedGrossProfit' => 0,
            'manualExpensesTotal' => 0,
            'estimatedNetProfit' => 0,
            'estimatedMarginPercent' => 0.0,
            'averageOrderValue' => 0,
            'expenseCount' => 0,
        ];
        $topProducts = [];
        $dailyMap = [];
        $categoryTotals = [];
        $monthlyMap = [];

        foreach ($transactions as $transaction) {
            $total = (int) ($transaction->total ?? 0);
            $estimatedProfit = $coins->estimatedProfitForAmount($total);
            $estimatedCost = max(0, $total - $estimatedProfit);
            $activityAt = CarbonImmutable::parse($transaction->paid_at ?? $transaction->updated_at ?? $transaction->created_at)->timezone('Asia/Jakarta');
            $dateKey = $activityAt->toDateString();
            $monthKey = $activityAt->format('Y-m');
            $productKey = trim((string) ($transaction->product_name ?: 'Produk tanpa nama'));

            $summary['paidOrderCount']++;
            $summary['grossRevenue'] += $total;
            $summary['estimatedCostBasis'] += $estimatedCost;
            $summary['estimatedGrossProfit'] += $estimatedProfit;

            if ($transaction->status === Transaction::STATUS_COMPLETED) {
                $summary['completedOrderCount']++;
            } elseif (in_array($transaction->status, [Transaction::STATUS_PENDING, Transaction::STATUS_PROCESSING], true)) {
                $summary['processingOrderCount']++;
            } else {
                $summary['issueOrderCount']++;
            }

            if (! isset($dailyMap[$dateKey])) {
                $dailyMap[$dateKey] = [
                    'date' => $dateKey,
                    'label' => $activityAt->locale('id')->translatedFormat('d M'),
                    'revenue' => 0,
                    'estimatedProfit' => 0,
                    'orders' => 0,
                ];
            }

            $dailyMap[$dateKey]['revenue'] += $total;
            $dailyMap[$dateKey]['estimatedProfit'] += $estimatedProfit;
            $dailyMap[$dateKey]['orders']++;

            if (! isset($monthlyMap[$monthKey])) {
                $monthlyMap[$monthKey] = [
                    'month' => $monthKey,
                    'monthLabel' => $activityAt->locale('id')->translatedFormat('M Y'),
                    'revenue' => 0,
                    'estimatedGrossProfit' => 0,
                    'manualExpenses' => 0,
                    'estimatedNetProfit' => 0,
                    'orders' => 0,
                ];
            }

            $monthlyMap[$monthKey]['revenue'] += $total;
            $monthlyMap[$monthKey]['estimatedGrossProfit'] += $estimatedProfit;
            $monthlyMap[$monthKey]['orders']++;

            if (! isset($topProducts[$productKey])) {
                $topProducts[$productKey] = [
                    'productName' => $productKey,
                    'ordersCount' => 0,
                    'revenue' => 0,
                    'estimatedProfit' => 0,
                    'lastOrderAt' => $activityAt->toDateTimeString(),
                ];
            }

            $topProducts[$productKey]['ordersCount']++;
            $topProducts[$productKey]['revenue'] += $total;
            $topProducts[$productKey]['estimatedProfit'] += $estimatedProfit;
            $topProducts[$productKey]['lastOrderAt'] = max($topProducts[$productKey]['lastOrderAt'], $activityAt->toDateTimeString());
        }

        $summary['averageOrderValue'] = $summary['paidOrderCount'] > 0
            ? (int) round($summary['grossRevenue'] / $summary['paidOrderCount'])
            : 0;

        foreach ($expenses as $expense) {
            $amount = (int) $expense->amount;
            $categoryKey = trim((string) ($expense->category ?: 'Lainnya'));
            $expenseAt = CarbonImmutable::parse($expense->expense_date, 'Asia/Jakarta');
            $monthKey = $expenseAt->format('Y-m');

            $summary['manualExpensesTotal'] += $amount;
            $summary['expenseCount']++;

            if (! isset($categoryTotals[$categoryKey])) {
                $categoryTotals[$categoryKey] = [
                    'category' => $categoryKey,
                    'amount' => 0,
                    'count' => 0,
                ];
            }

            $categoryTotals[$categoryKey]['amount'] += $amount;
            $categoryTotals[$categoryKey]['count']++;

            if (! isset($monthlyMap[$monthKey])) {
                $monthlyMap[$monthKey] = [
                    'month' => $monthKey,
                    'monthLabel' => $expenseAt->locale('id')->translatedFormat('M Y'),
                    'revenue' => 0,
                    'estimatedGrossProfit' => 0,
                    'manualExpenses' => 0,
                    'estimatedNetProfit' => 0,
                    'orders' => 0,
                ];
            }

            $monthlyMap[$monthKey]['manualExpenses'] += $amount;
        }

        $summary['estimatedNetProfit'] = $summary['estimatedGrossProfit'] - $summary['manualExpensesTotal'];
        $summary['estimatedMarginPercent'] = $summary['grossRevenue'] > 0
            ? round(($summary['estimatedGrossProfit'] / $summary['grossRevenue']) * 100, 1)
            : 0.0;

        $topProductsPayload = collect($topProducts)
            ->sortByDesc('estimatedProfit')
            ->take(8)
            ->values()
            ->map(function (array $item) {
                return [
                    'productName' => $item['productName'],
                    'ordersCount' => (int) $item['ordersCount'],
                    'revenue' => (int) $item['revenue'],
                    'estimatedProfit' => (int) $item['estimatedProfit'],
                    'lastOrderLabel' => CarbonImmutable::parse($item['lastOrderAt'])->locale('id')->translatedFormat('d M Y, H:i'),
                ];
            })
            ->all();

        $dailySeries = collect($dailyMap)
            ->sortBy('date')
            ->take(-14)
            ->values()
            ->all();

        $expensesPayload = $expenses
            ->take(12)
            ->map(function (ManualExpense $expense) {
                return [
                    'id' => $expense->id,
                    'title' => $expense->title,
                    'category' => $expense->category ?: 'Lainnya',
                    'amount' => (int) $expense->amount,
                    'expenseDate' => $expense->expense_date?->toDateString(),
                    'expenseDateLabel' => $expense->expense_date?->locale('id')->translatedFormat('d M Y') ?? '-',
                    'notes' => $expense->notes,
                    'createdBy' => $expense->createdBy?->name,
                ];
            })
            ->values()
            ->all();

        $expenseCategories = collect($categoryTotals)
            ->sortByDesc('amount')
            ->take(6)
            ->values()
            ->all();

        $monthlySeries = collect($monthlyMap)
            ->sortBy('month')
            ->values()
            ->map(function (array $item) {
                $item['estimatedNetProfit'] = $item['estimatedGrossProfit'] - $item['manualExpenses'];

                return $item;
            })
            ->take(-6)
            ->all();
        $monthComparison = $this->buildMonthComparison($coins);
        $insights = $this->buildInsights(
            $summary,
            $monthComparison,
            $topProductsPayload,
            $expenseCategories,
        );

        return [
            'summary' => $summary,
            'dailySeries' => $dailySeries,
            'topProducts' => $topProductsPayload,
            'expenses' => $expensesPayload,
            'expenseCategories' => $expenseCategories,
            'monthlySeries' => $monthlySeries,
            'monthComparison' => $monthComparison,
            'insights' => $insights,
        ];
    }

    /**
     * @return array{
     *   currentMonth: array<string, int|float|string>,
     *   previousMonth: array<string, int|float|string>,
     *   deltas: array<string, float|null>
     * }
     */
    private function buildMonthComparison(LyvaCoinService $coins): array
    {
        $now = CarbonImmutable::now('Asia/Jakarta');
        $currentStart = $now->startOfMonth();
        $currentEnd = $now->endOfDay();
        $previousStart = $currentStart->subMonth()->startOfMonth();
        $previousEnd = $currentStart->subMonth()->endOfMonth();

        $currentMonth = $this->buildPeriodSummary($currentStart, $currentEnd, $coins);
        $previousMonth = $this->buildPeriodSummary($previousStart, $previousEnd, $coins);

        return [
            'currentMonth' => [
                'label' => $currentStart->locale('id')->translatedFormat('F Y'),
                ...$currentMonth,
            ],
            'previousMonth' => [
                'label' => $previousStart->locale('id')->translatedFormat('F Y'),
                ...$previousMonth,
            ],
            'deltas' => [
                'grossRevenue' => $this->calculateDeltaPercent($currentMonth['grossRevenue'], $previousMonth['grossRevenue']),
                'estimatedNetProfit' => $this->calculateDeltaPercent($currentMonth['estimatedNetProfit'], $previousMonth['estimatedNetProfit']),
                'manualExpensesTotal' => $this->calculateDeltaPercent($currentMonth['manualExpensesTotal'], $previousMonth['manualExpensesTotal']),
                'paidOrderCount' => $this->calculateDeltaPercent($currentMonth['paidOrderCount'], $previousMonth['paidOrderCount']),
            ],
        ];
    }

    /**
     * @return array{
     *   grossRevenue: int,
     *   estimatedGrossProfit: int,
     *   manualExpensesTotal: int,
     *   estimatedNetProfit: int,
     *   paidOrderCount: int
     * }
     */
    private function buildPeriodSummary(CarbonImmutable $start, CarbonImmutable $end, LyvaCoinService $coins): array
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
            ->select(['total'])
            ->get();

        $grossRevenue = 0;
        $estimatedGrossProfit = 0;
        $paidOrderCount = 0;

        foreach ($transactions as $transaction) {
            $total = (int) ($transaction->total ?? 0);
            $grossRevenue += $total;
            $estimatedGrossProfit += $coins->estimatedProfitForAmount($total);
            $paidOrderCount++;
        }

        $manualExpensesTotal = (int) ManualExpense::query()
            ->whereBetween('expense_date', [$start->toDateString(), $end->toDateString()])
            ->sum('amount');

        return [
            'grossRevenue' => $grossRevenue,
            'estimatedGrossProfit' => $estimatedGrossProfit,
            'manualExpensesTotal' => $manualExpensesTotal,
            'estimatedNetProfit' => $estimatedGrossProfit - $manualExpensesTotal,
            'paidOrderCount' => $paidOrderCount,
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
     * @param  array<string, int|float>  $summary
     * @param  array<string, mixed>  $monthComparison
     * @param  array<int, array<string, int|string>>  $topProducts
     * @param  array<int, array<string, int|string>>  $expenseCategories
     * @return array<int, array<string, string>>
     */
    private function buildInsights(
        array $summary,
        array $monthComparison,
        array $topProducts,
        array $expenseCategories,
    ): array {
        $insights = [];
        $profitDelta = $monthComparison['deltas']['estimatedNetProfit'] ?? null;
        $currentMonthLabel = (string) ($monthComparison['currentMonth']['label'] ?? 'bulan ini');
        $previousMonthLabel = (string) ($monthComparison['previousMonth']['label'] ?? 'bulan lalu');

        if ($profitDelta === null) {
            $insights[] = [
                'tone' => 'positive',
                'title' => 'Profit bersih mulai terbentuk',
                'body' => 'Profit bersih '.$currentMonthLabel.' sudah muncul, sementara '.$previousMonthLabel.' belum punya basis pembanding.',
            ];
        } elseif ($profitDelta > 0) {
            $insights[] = [
                'tone' => 'positive',
                'title' => 'Profit bersih sedang naik',
                'body' => 'Estimasi profit bersih '.$currentMonthLabel.' naik '.$profitDelta.'% dibanding '.$previousMonthLabel.'.',
            ];
        } elseif ($profitDelta < 0) {
            $insights[] = [
                'tone' => 'warning',
                'title' => 'Profit bersih sedang turun',
                'body' => 'Estimasi profit bersih '.$currentMonthLabel.' turun '.abs($profitDelta).'% dibanding '.$previousMonthLabel.', jadi biaya dan produk pendorong perlu dicek.',
            ];
        } else {
            $insights[] = [
                'tone' => 'neutral',
                'title' => 'Profit bersih relatif stabil',
                'body' => 'Estimasi profit bersih '.$currentMonthLabel.' masih setara dengan '.$previousMonthLabel.'.',
            ];
        }

        if ($expenseCategories !== []) {
            $topExpenseCategory = $expenseCategories[0];
            $expenseTotal = (int) ($summary['manualExpensesTotal'] ?? 0);
            $topExpenseAmount = (int) ($topExpenseCategory['amount'] ?? 0);
            $share = $expenseTotal > 0 ? round(($topExpenseAmount / $expenseTotal) * 100) : 0;

            $insights[] = [
                'tone' => $share >= 45 ? 'warning' : 'info',
                'title' => 'Biaya terbesar ada di '.$topExpenseCategory['category'],
                'body' => 'Kategori ini menyumbang sekitar '.$share.'% dari total biaya manual pada periode terpilih.',
            ];
        }

        if ($topProducts !== []) {
            $bestProduct = $topProducts[0];

            $insights[] = [
                'tone' => 'positive',
                'title' => 'Produk paling mendorong profit',
                'body' => $bestProduct['productName'].' memberi estimasi profit terbesar dengan '.number_format((int) $bestProduct['ordersCount'], 0, ',', '.').' order.',
            ];
        }

        if (((int) ($summary['issueOrderCount'] ?? 0)) > 0) {
            $insights[] = [
                'tone' => 'warning',
                'title' => 'Masih ada order perlu perhatian',
                'body' => number_format((int) $summary['issueOrderCount'], 0, ',', '.').' order di periode ini masih masuk kategori bermasalah atau butuh follow-up.',
            ];
        }

        if ($insights === []) {
            $insights[] = [
                'tone' => 'neutral',
                'title' => 'Belum cukup data insight',
                'body' => 'Tambahkan transaksi atau biaya manual dulu supaya halaman keuangan bisa membaca pola bisnis dengan lebih jelas.',
            ];
        }

        return array_slice($insights, 0, 4);
    }

    /**
     * @return array{preset?: string|null, start_date?: string|null, end_date?: string|null}
     */
    private function validateFilters(Request $request): array
    {
        return $request->validate([
            'preset' => ['nullable', 'string', 'in:today,7d,30d,this-month,all'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);
    }

    /**
     * @param  array{preset?: string|null, start_date?: string|null, end_date?: string|null}  $validated
     * @return array{preset: string, start: CarbonImmutable|null, end: CarbonImmutable|null}
     */
    private function resolveRange(array $validated): array
    {
        $now = CarbonImmutable::now('Asia/Jakarta');
        $preset = (string) ($validated['preset'] ?? '');
        $startDate = $validated['start_date'] ?? null;
        $endDate = $validated['end_date'] ?? null;

        if ($startDate || $endDate) {
            return [
                'preset' => 'custom',
                'start' => $startDate ? CarbonImmutable::parse($startDate, 'Asia/Jakarta') : null,
                'end' => $endDate ? CarbonImmutable::parse($endDate, 'Asia/Jakarta') : null,
            ];
        }

        return match ($preset) {
            'today' => [
                'preset' => 'today',
                'start' => $now->startOfDay(),
                'end' => $now->endOfDay(),
            ],
            '7d' => [
                'preset' => '7d',
                'start' => $now->subDays(6)->startOfDay(),
                'end' => $now->endOfDay(),
            ],
            'all' => [
                'preset' => 'all',
                'start' => null,
                'end' => null,
            ],
            '30d' => [
                'preset' => '30d',
                'start' => $now->subDays(29)->startOfDay(),
                'end' => $now->endOfDay(),
            ],
            default => [
                'preset' => 'this-month',
                'start' => $now->startOfMonth(),
                'end' => $now->endOfDay(),
            ],
        };
    }
}
