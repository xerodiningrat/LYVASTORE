<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\User;
use App\Services\LeaderboardService;
use App\Services\LyvaCoinService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(
        Request $request,
        LyvaCoinService $lyvaCoinService,
        LeaderboardService $leaderboardService,
    ): Response {
        /** @var User $user */
        $user = $request->user();
        $now = CarbonImmutable::now();
        $completedBaseQuery = $user->transactions()
            ->where('payment_status', Transaction::PAYMENT_STATUS_PAID)
            ->where('status', Transaction::STATUS_COMPLETED);

        $totalSpent = (int) (clone $completedBaseQuery)->sum('total');
        $completedOrders = (int) (clone $completedBaseQuery)->count();
        $monthlySpend = (int) (clone $completedBaseQuery)
            ->whereRaw('COALESCE(paid_at, updated_at, created_at) >= ?', [$now->subDays(30)->toDateTimeString()])
            ->sum('total');
        $activeOrders = (int) $user->transactions()
            ->whereIn('status', [Transaction::STATUS_PENDING, Transaction::STATUS_PROCESSING])
            ->count();
        $failedOrders = (int) $user->transactions()
            ->whereIn('status', [Transaction::STATUS_FAILED, Transaction::STATUS_EXPIRED])
            ->count();
        $resolvedOrders = $completedOrders + $failedOrders;
        $successRate = $resolvedOrders > 0
            ? (int) round(($completedOrders / $resolvedOrders) * 100)
            : 100;
        $coinsBalance = $lyvaCoinService->balanceForUser($user);
        $rewardedTransactions = $lyvaCoinService->rewardedTransactionCountForUser($user);
        $leaderboardBoard = $leaderboardService->boardsFor($user)[LeaderboardService::PERIOD_MONTHLY] ?? [];
        $viewerEntry = data_get($leaderboardBoard, 'viewerEntry');
        $focusTransaction = $user->transactions()
            ->whereIn('status', [Transaction::STATUS_PENDING, Transaction::STATUS_PROCESSING])
            ->orderByRaw('COALESCE(paid_at, updated_at, created_at) DESC')
            ->first();

        return Inertia::render('Dashboard', [
            'overview' => [
                'totalSpent' => $totalSpent,
                'completedOrders' => $completedOrders,
                'coinsBalance' => $coinsBalance,
                'activeOrders' => $activeOrders,
                'monthlySpend' => $monthlySpend,
                'averageOrder' => $completedOrders > 0 ? (int) round($totalSpent / $completedOrders) : 0,
                'successRate' => $successRate,
                'rewardedTransactions' => $rewardedTransactions,
            ],
            'milestone' => $this->buildMilestone($totalSpent),
            'accountStatus' => $this->buildAccountStatus($user),
            'focusTransaction' => $focusTransaction ? $this->mapRecentTransaction($focusTransaction, $lyvaCoinService) : null,
            'leaderboard' => [
                'windowLabel' => (string) data_get($leaderboardBoard, 'windowLabel', '30 hari terakhir'),
                'participantsCount' => (int) data_get($leaderboardBoard, 'stats.participantsCount', 0),
                'rank' => $viewerEntry['rank'] ?? null,
                'badge' => $viewerEntry['badge'] ?? 'Belum masuk ranking',
                'totalSpent' => (int) ($viewerEntry['totalSpent'] ?? 0),
                'ordersCount' => (int) ($viewerEntry['ordersCount'] ?? 0),
                'topEntries' => collect(data_get($leaderboardBoard, 'entries', []))->take(3)->values()->all(),
            ],
            'recentTransactions' => $user->transactions()
                ->orderByRaw('COALESCE(paid_at, updated_at, created_at) DESC')
                ->take(6)
                ->get()
                ->map(fn (Transaction $transaction) => $this->mapRecentTransaction($transaction, $lyvaCoinService))
                ->values()
                ->all(),
            'recentRewards' => $lyvaCoinService->recentRewardsForUser($user, 4)->values()->all(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildMilestone(int $totalSpent): array
    {
        $tiers = [
            [
                'threshold' => 0,
                'label' => 'Baru mulai',
                'description' => 'Langkah awal untuk bangun histori akun yang aktif.',
            ],
            [
                'threshold' => 50_000,
                'label' => 'Mulai panas',
                'description' => 'Akun mulai konsisten top up dan kelihatan hidup.',
            ],
            [
                'threshold' => 150_000,
                'label' => 'Ritme bagus',
                'description' => 'Belanja sudah stabil dan cashback ikut terasa.',
            ],
            [
                'threshold' => 350_000,
                'label' => 'Top up serius',
                'description' => 'Sudah masuk kategori user aktif yang kuat.',
            ],
            [
                'threshold' => 750_000,
                'label' => 'Big spender',
                'description' => 'Momentum kuat untuk dorong ranking leaderboard.',
            ],
            [
                'threshold' => 1_500_000,
                'label' => 'Lyva elite',
                'description' => 'Akun dengan histori belanja yang paling matang.',
            ],
        ];

        $currentIndex = 0;

        foreach ($tiers as $index => $tier) {
            if ($totalSpent >= $tier['threshold']) {
                $currentIndex = $index;
            }
        }

        $currentTier = $tiers[$currentIndex];
        $nextTier = $tiers[$currentIndex + 1] ?? null;
        $startThreshold = $currentTier['threshold'];
        $endThreshold = $nextTier['threshold'] ?? $currentTier['threshold'];
        $progress = $nextTier
            ? (int) round((($totalSpent - $startThreshold) / max(1, $endThreshold - $startThreshold)) * 100)
            : 100;

        return [
            'currentLabel' => $currentTier['label'],
            'currentDescription' => $currentTier['description'],
            'progress' => max(0, min(100, $progress)),
            'nextLabel' => $nextTier['label'] ?? null,
            'remainingSpend' => $nextTier ? max(0, $nextTier['threshold'] - $totalSpent) : 0,
            'message' => $nextTier
                ? 'Belanja sedikit lagi untuk naik ke level berikutnya dan bikin histori akun makin kuat.'
                : 'Semua milestone utama sudah tercapai. Tinggal pertahankan ritmenya.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildAccountStatus(User $user): array
    {
        $checks = [
            'avatar' => filled($user->avatar),
            'email' => filled($user->email),
            'whatsapp' => filled($user->whatsapp_number),
            'whatsappVerified' => filled($user->whatsapp_verified_at),
        ];

        $completion = (int) round((collect($checks)->filter()->count() / count($checks)) * 100);

        return [
            'completion' => $completion,
            'joinedLabel' => $user->created_at
                ? CarbonImmutable::parse($user->created_at)->locale('id')->translatedFormat('d M Y')
                : 'Baru bergabung',
            'hasAvatar' => $checks['avatar'],
            'whatsappVerified' => $checks['whatsappVerified'],
            'email' => (string) $user->email,
            'whatsappNumber' => (string) ($user->whatsapp_number ?? '-'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapRecentTransaction(Transaction $transaction, LyvaCoinService $lyvaCoinService): array
    {
        $activityAt = $transaction->paid_at ?? $transaction->updated_at ?? $transaction->created_at;

        return [
            'publicId' => (string) $transaction->public_id,
            'productName' => (string) $transaction->product_name,
            'packageLabel' => (string) $transaction->package_label,
            'status' => (string) $transaction->status,
            'statusLabel' => $this->statusLabel($transaction),
            'paymentLabel' => (string) ($transaction->payment_method_label ?: 'Belum dipilih'),
            'total' => (int) $transaction->total,
            'coins' => $transaction->status === Transaction::STATUS_COMPLETED
                && $transaction->payment_status === Transaction::PAYMENT_STATUS_PAID
                ? $lyvaCoinService->rewardForAmount((int) $transaction->total)
                : 0,
            'activityLabel' => $activityAt
                ? CarbonImmutable::parse($activityAt)->locale('id')->translatedFormat('d M Y, H:i')
                : 'Baru dibuat',
            'checkoutUrl' => route('checkout.show', ['transaction' => $transaction->public_id]),
        ];
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
}
