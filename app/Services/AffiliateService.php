<?php

namespace App\Services;

use App\Models\AffiliateWithdrawal;
use App\Models\Transaction;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AffiliateService
{
    public function __construct(
        private readonly LyvaCoinService $coins,
    ) {}

    public function profileSummary(User $user): array
    {
        $freezeDays = max(1, (int) config('affiliate.freeze_days', 2));
        $freezeBoundary = CarbonImmutable::now(config('app.timezone'))->subDays($freezeDays);
        $commissionPercent = max(0, (float) config('affiliate.commission_percent', 5));
        $profitShareLimit = max(0, min(1, (float) config('affiliate.max_share_of_estimated_profit', 0.35)));
        $minimumWithdrawal = max(0, (int) config('affiliate.minimum_withdrawal', 10000));

        $eligibleTransactions = $this->eligibleTransactions($user);
        $grossByStatus = $eligibleTransactions
            ->map(fn (Transaction $transaction) => [
                'transaction' => $transaction,
                'commission' => $this->commissionForTransaction($transaction, $commissionPercent, $profitShareLimit),
                'is_frozen' => ($transaction->paid_at ?? $transaction->created_at)?->greaterThan($freezeBoundary) ?? true,
            ])
            ->filter(fn (array $item) => $item['commission'] > 0)
            ->values();

        $grossEarnings = (int) $grossByStatus->sum('commission');
        $frozenEarnings = (int) $grossByStatus->where('is_frozen', true)->sum('commission');
        $maturedEarnings = max(0, $grossEarnings - $frozenEarnings);

        $withdrawals = $user->affiliateWithdrawals()->latest('id')->get();
        $reservedStatuses = [
            AffiliateWithdrawal::STATUS_PENDING,
            AffiliateWithdrawal::STATUS_PROCESSING,
            AffiliateWithdrawal::STATUS_PAID,
        ];

        $reservedWithdrawals = (int) $withdrawals->whereIn('status', $reservedStatuses)->sum('amount');
        $paidWithdrawals = (int) $withdrawals->where('status', AffiliateWithdrawal::STATUS_PAID)->sum('amount');
        $processingWithdrawals = (int) $withdrawals
            ->whereIn('status', [AffiliateWithdrawal::STATUS_PENDING, AffiliateWithdrawal::STATUS_PROCESSING])
            ->sum('amount');

        $availableEarnings = max(0, $maturedEarnings - $reservedWithdrawals);

        return [
            'status' => (string) ($user->affiliate_status ?? 'none'),
            'statusLabel' => $this->statusLabel((string) ($user->affiliate_status ?? 'none')),
            'code' => $user->affiliate_code,
            'referralLink' => $user->affiliate_code ? route('register', ['ref' => $user->affiliate_code], absolute: true) : null,
            'commissionPercent' => $commissionPercent,
            'profitShareLimitPercent' => (int) round($profitShareLimit * 100),
            'freezeDays' => $freezeDays,
            'minimumWithdrawal' => $minimumWithdrawal,
            'appliedAt' => $user->affiliate_applied_at?->timezone('Asia/Jakarta')->toIso8601String(),
            'appliedAtLabel' => $user->affiliate_applied_at?->timezone('Asia/Jakarta')->locale('id')->translatedFormat('d M Y, H:i'),
            'approvedAt' => $user->affiliate_approved_at?->timezone('Asia/Jakarta')->toIso8601String(),
            'approvedAtLabel' => $user->affiliate_approved_at?->timezone('Asia/Jakarta')->locale('id')->translatedFormat('d M Y, H:i'),
            'totals' => [
                'referredUsers' => $user->referredUsers()->count(),
                'grossEarnings' => $grossEarnings,
                'frozenEarnings' => $frozenEarnings,
                'availableEarnings' => $availableEarnings,
                'processingWithdrawals' => $processingWithdrawals,
                'paidWithdrawals' => $paidWithdrawals,
            ],
            'recentCommissions' => $grossByStatus
                ->sortByDesc(fn (array $item) => optional($item['transaction']->paid_at ?? $item['transaction']->created_at)?->timestamp ?? 0)
                ->take(8)
                ->map(fn (array $item) => [
                    'id' => $item['transaction']->public_id,
                    'customerLabel' => $this->maskLabel($item['transaction']->customer_name ?: $item['transaction']->customer_email ?: $item['transaction']->customer_whatsapp),
                    'productLabel' => trim(($item['transaction']->product_name ?? '').' '.($item['transaction']->package_label ?? '')),
                    'commission' => $item['commission'],
                    'statusLabel' => $item['is_frozen'] ? 'Masih dibekukan' : 'Siap ditarik',
                    'timeLabel' => optional($item['transaction']->paid_at ?? $item['transaction']->created_at)?->timezone('Asia/Jakarta')->locale('id')->diffForHumans(),
                ])
                ->values()
                ->all(),
            'withdrawals' => $withdrawals
                ->take(8)
                ->map(fn (AffiliateWithdrawal $withdrawal) => [
                    'id' => $withdrawal->public_id,
                    'amount' => (int) $withdrawal->amount,
                    'status' => $withdrawal->status,
                    'statusLabel' => $this->withdrawalStatusLabel($withdrawal->status),
                    'requestedAtLabel' => optional($withdrawal->requested_at ?? $withdrawal->created_at)?->timezone('Asia/Jakarta')->locale('id')->translatedFormat('d M Y, H:i'),
                    'notes' => $withdrawal->notes,
                ])
                ->values()
                ->all(),
        ];
    }

    public function apply(User $user): User
    {
        if (($user->affiliate_status ?? 'none') === 'approved') {
            throw ValidationException::withMessages([
                'affiliate' => 'Akun ini sudah aktif sebagai affiliate.',
            ]);
        }

        if (($user->affiliate_status ?? 'none') === 'pending') {
            throw ValidationException::withMessages([
                'affiliate' => 'Pendaftaran affiliate kamu masih menunggu persetujuan admin.',
            ]);
        }

        $user->forceFill([
            'affiliate_status' => 'pending',
            'affiliate_code' => $user->affiliate_code ?: $this->generateAffiliateCode(),
            'affiliate_applied_at' => now(),
        ])->save();

        return $user->fresh();
    }

    public function requestWithdrawal(User $user, ?string $notes = null): AffiliateWithdrawal
    {
        $summary = $this->profileSummary($user);

        if (($user->affiliate_status ?? 'none') !== 'approved') {
            throw ValidationException::withMessages([
                'affiliate' => 'Akun kamu belum aktif sebagai affiliate.',
            ]);
        }

        if ((int) ($summary['totals']['availableEarnings'] ?? 0) < (int) ($summary['minimumWithdrawal'] ?? 0)) {
            throw ValidationException::withMessages([
                'affiliate' => 'Saldo affiliate yang bisa ditarik belum memenuhi minimum penarikan.',
            ]);
        }

        return DB::transaction(function () use ($user, $summary, $notes): AffiliateWithdrawal {
            return AffiliateWithdrawal::create([
                'user_id' => $user->id,
                'public_id' => 'AFW-'.Str::upper(Str::random(10)),
                'amount' => (int) ($summary['totals']['availableEarnings'] ?? 0),
                'status' => AffiliateWithdrawal::STATUS_PENDING,
                'whatsapp_number' => $user->whatsapp_number,
                'notes' => trim((string) $notes) ?: null,
                'requested_at' => now(),
            ]);
        });
    }

    public function approve(User $user): User
    {
        $user->forceFill([
            'affiliate_status' => 'approved',
            'affiliate_code' => $user->affiliate_code ?: $this->generateAffiliateCode(),
            'affiliate_approved_at' => now(),
        ])->save();

        return $user->fresh();
    }

    public function reject(User $user): User
    {
        $user->forceFill([
            'affiliate_status' => 'rejected',
        ])->save();

        return $user->fresh();
    }

    public function markWithdrawalProcessing(AffiliateWithdrawal $withdrawal): AffiliateWithdrawal
    {
        $withdrawal->forceFill([
            'status' => AffiliateWithdrawal::STATUS_PROCESSING,
            'processed_at' => now(),
        ])->save();

        return $withdrawal->fresh();
    }

    public function markWithdrawalPaid(AffiliateWithdrawal $withdrawal): AffiliateWithdrawal
    {
        $withdrawal->forceFill([
            'status' => AffiliateWithdrawal::STATUS_PAID,
            'paid_at' => now(),
        ])->save();

        return $withdrawal->fresh();
    }

    public function rejectWithdrawal(AffiliateWithdrawal $withdrawal): AffiliateWithdrawal
    {
        $withdrawal->forceFill([
            'status' => AffiliateWithdrawal::STATUS_REJECTED,
        ])->save();

        return $withdrawal->fresh();
    }

    public function resolveReferrerByCode(?string $code): ?User
    {
        $normalized = Str::upper(trim((string) $code));

        if ($normalized === '') {
            return null;
        }

        return User::query()
            ->where('affiliate_status', 'approved')
            ->where('affiliate_code', $normalized)
            ->first();
    }

    /**
     * @return Collection<int, Transaction>
     */
    private function eligibleTransactions(User $user): Collection
    {
        if (($user->affiliate_status ?? 'none') !== 'approved' || ! $user->affiliate_approved_at) {
            return collect();
        }

        return Transaction::query()
            ->select('transactions.*')
            ->join('users as referred_users', 'referred_users.id', '=', 'transactions.user_id')
            ->where('referred_users.referred_by_user_id', $user->id)
            ->where('transactions.payment_status', Transaction::PAYMENT_STATUS_PAID)
            ->where('transactions.status', Transaction::STATUS_COMPLETED)
            ->where(function ($query) use ($user) {
                $query
                    ->whereNotNull('transactions.paid_at')
                    ->where('transactions.paid_at', '>=', $user->affiliate_approved_at);
            })
            ->orderByDesc('transactions.id')
            ->get();
    }

    private function commissionForTransaction(Transaction $transaction, float $percent, float $profitShareLimit): int
    {
        $total = max(0, (int) ($transaction->total ?? 0));

        if ($total <= 0) {
            return 0;
        }

        $grossCommission = (int) floor($total * ($percent / 100));
        $estimatedProfit = $this->coins->estimatedProfitForAmount($total);
        $profitGuard = (int) floor($estimatedProfit * $profitShareLimit);

        if ($profitGuard <= 0) {
            return 0;
        }

        return max(0, min($grossCommission, $profitGuard));
    }

    private function generateAffiliateCode(): string
    {
        do {
            $code = 'LYVA'.Str::upper(Str::random(6));
        } while (User::query()->where('affiliate_code', $code)->exists());

        return $code;
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'pending' => 'Menunggu persetujuan',
            'approved' => 'Affiliate aktif',
            'rejected' => 'Pendaftaran ditolak',
            default => 'Belum daftar',
        };
    }

    private function withdrawalStatusLabel(string $status): string
    {
        return match ($status) {
            AffiliateWithdrawal::STATUS_PENDING => 'Menunggu diproses',
            AffiliateWithdrawal::STATUS_PROCESSING => 'Sedang diproses',
            AffiliateWithdrawal::STATUS_PAID => 'Sudah dibayar',
            AffiliateWithdrawal::STATUS_REJECTED => 'Ditolak',
            default => Str::headline($status),
        };
    }

    private function maskLabel(?string $value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return 'Member';
        }

        if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $value = explode('@', $value)[0] ?? 'member';
        }

        return Str::substr($value, 0, min(3, Str::length($value))).'***';
    }
}
