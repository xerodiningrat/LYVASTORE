<?php

namespace App\Services;

use App\Models\CoinAdjustment;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class LyvaCoinService
{
    /**
     * @var array<int, int>
     */
    private array $rewardCache = [];

    public function __construct(
        private readonly SiteSettingService $settings,
    ) {}

    public function rewardForAmount(int|float|null $amount): int
    {
        $normalizedAmount = (int) round((float) $amount);

        if ($normalizedAmount <= 0) {
            return 0;
        }

        return $this->rewardCache[$normalizedAmount]
            ??= max($this->minimumRewardCoins(), (int) round($this->rewardBudgetForSellingAmount($normalizedAmount) / $this->coinValueRupiah()));
    }

    public function rewardRateLabel(): string
    {
        return 'Maks '.$this->formatPercent($this->maxRewardPercentOfSellingPrice()).' harga jual, disesuaikan margin';
    }

    /**
     * @return array{
     *     coinValueRupiah: int,
     *     maxRewardPercentOfSellingPrice: float,
     *     rewardShareOfEstimatedProfit: float,
     *     minimumRewardCoins: int,
     *     rewardRateLabel: string,
     *     tiers: array<int, array{max: int|null, percent: float, fixed: int, roundTo: int}>
     * }
     */
    public function frontendConfig(): array
    {
        return [
            'coinValueRupiah' => $this->coinValueRupiah(),
            'maxRewardPercentOfSellingPrice' => $this->maxRewardPercentOfSellingPrice(),
            'rewardShareOfEstimatedProfit' => $this->rewardShareOfEstimatedProfit(),
            'minimumRewardCoins' => $this->minimumRewardCoins(),
            'rewardRateLabel' => $this->rewardRateLabel(),
            'tiers' => collect($this->marginTiers())
                ->map(fn (array $tier) => [
                    'max' => $tier['max'],
                    'percent' => $tier['percent'],
                    'fixed' => $tier['fixed'],
                    'roundTo' => $tier['round_to'],
                ])
                ->values()
                ->all(),
        ];
    }

    public function balanceForUser(?User $user): int
    {
        if (! $user) {
            return 0;
        }

        $earnedCoins = (int) $this->rewardQuery($user)
            ->get(['id', 'total'])
            ->sum(fn (Transaction $transaction) => $this->rewardForAmount((int) $transaction->total));

        $spentCoins = (int) $this->spentCoinQuery($user)->sum('coin_spent_amount');
        $adjustmentCoins = (int) CoinAdjustment::query()
            ->where('user_id', $user->id)
            ->sum('amount');

        return max(0, $earnedCoins + $adjustmentCoins - $spentCoins);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function recentRewardsForUser(User $user, int $limit = 8): Collection
    {
        return $this->rewardQuery($user)
            ->latest('paid_at')
            ->latest('updated_at')
            ->take($limit)
            ->get([
                'public_id',
                'product_name',
                'package_label',
                'total',
                'paid_at',
                'updated_at',
            ])
            ->map(fn (Transaction $transaction) => [
                'publicId' => (string) $transaction->public_id,
                'productName' => (string) $transaction->product_name,
                'packageLabel' => (string) $transaction->package_label,
                'total' => (int) $transaction->total,
                'coins' => $this->rewardForAmount((int) $transaction->total),
                'completedAt' => ($transaction->paid_at ?? $transaction->updated_at)?->toIso8601String(),
                'checkoutUrl' => route('checkout.show', ['transaction' => $transaction->public_id]),
            ]);
    }

    public function rewardedTransactionCountForUser(?User $user): int
    {
        if (! $user) {
            return 0;
        }

        return (int) $this->rewardQuery($user)->count();
    }

    public function requiredCoinsForAmount(int|float|null $amount): int
    {
        $normalizedAmount = (int) round((float) $amount);

        if ($normalizedAmount <= 0) {
            return 0;
        }

        return max(1, (int) ceil($normalizedAmount / $this->coinValueRupiah()));
    }

    public function canUserPayAmount(?User $user, int|float|null $amount): bool
    {
        if (! $user) {
            return false;
        }

        return $this->balanceForUser($user) >= $this->requiredCoinsForAmount($amount);
    }

    public function estimatedProfitForAmount(int|float|null $amount): int
    {
        $normalizedAmount = (int) round((float) $amount);

        if ($normalizedAmount <= 0) {
            return 0;
        }

        return $this->estimatedProfitForSellingAmount($normalizedAmount);
    }

    private function rewardQuery(User $user): HasMany
    {
        return $user->transactions()
            ->where('payment_status', Transaction::PAYMENT_STATUS_PAID)
            ->where('status', Transaction::STATUS_COMPLETED)
            ->where(function (Builder $query) {
                $query
                    ->whereNull('payment_method_type')
                    ->orWhere('payment_method_type', '!=', 'lyva-coins');
            })
            ->where('total', '>', 0);
    }

    private function spentCoinQuery(User $user): HasMany
    {
        return $user->transactions()
            ->where('payment_status', Transaction::PAYMENT_STATUS_PAID)
            ->where('coin_spent_amount', '>', 0)
            ->whereNull('coin_refunded_at');
    }

    private function rewardBudgetForSellingAmount(int $sellingAmount): int
    {
        $saleCapBudget = (int) round($sellingAmount * $this->maxRewardPercentOfSellingPrice());
        $estimatedProfitBudget = (int) round($this->estimatedProfitForSellingAmount($sellingAmount) * $this->rewardShareOfEstimatedProfit());

        if ($estimatedProfitBudget <= 0) {
            return max(0, $saleCapBudget);
        }

        return max(0, min($saleCapBudget, $estimatedProfitBudget));
    }

    private function estimatedProfitForSellingAmount(int $sellingAmount): int
    {
        $estimatedBasePrice = $this->estimateBasePriceFromSellingAmount($sellingAmount);

        return max(0, $sellingAmount - $estimatedBasePrice);
    }

    private function estimateBasePriceFromSellingAmount(int $sellingAmount): int
    {
        $tiers = $this->marginTiers();
        $previousMax = 0;

        foreach ($tiers as $tier) {
            $lowerBound = max(1, $previousMax + 1);
            $upperBound = min(
                $sellingAmount,
                isset($tier['max']) && $tier['max'] !== null ? (int) $tier['max'] : $sellingAmount,
            );

            if ($upperBound < $lowerBound) {
                $previousMax = isset($tier['max']) && $tier['max'] !== null ? (int) $tier['max'] : $previousMax;
                continue;
            }

            $candidate = $this->searchBasePriceWithinTier($sellingAmount, $tier, $lowerBound, $upperBound);

            if ($candidate !== null) {
                return $candidate;
            }

            $previousMax = isset($tier['max']) && $tier['max'] !== null ? (int) $tier['max'] : $previousMax;
        }

        return max(0, (int) floor($sellingAmount * 0.94));
    }

    /**
     * @param  array{max: int|null, percent: float, fixed: int, round_to: int}  $tier
     */
    private function searchBasePriceWithinTier(int $sellingAmount, array $tier, int $lowerBound, int $upperBound): ?int
    {
        $percent = max(0, (float) ($tier['percent'] ?? 0));
        $fixed = max(0, (int) ($tier['fixed'] ?? 0));
        $roundTo = max(1, (int) ($tier['round_to'] ?? 100));
        $estimate = (int) floor(($sellingAmount - $fixed) / max(1, 1 + $percent));
        $estimate = max($lowerBound, min($upperBound, $estimate));
        $searchRadius = max(800, $roundTo * 4);
        $start = max($lowerBound, $estimate - $searchRadius);
        $end = min($upperBound, $estimate + $searchRadius);
        $bestBase = null;
        $bestDelta = null;

        for ($basePrice = $start; $basePrice <= $end; $basePrice++) {
            $calculatedSellingPrice = $this->applyTierSellingPrice($basePrice, $tier);
            $delta = abs($calculatedSellingPrice - $sellingAmount);

            if ($bestDelta === null || $delta < $bestDelta || ($delta === $bestDelta && ($bestBase === null || $basePrice > $bestBase))) {
                $bestDelta = $delta;
                $bestBase = $basePrice;
            }
        }

        if ($bestBase === null || $bestDelta === null || $bestDelta > $roundTo) {
            return null;
        }

        return $bestBase;
    }

    /**
     * @param  array{percent: float, fixed: int, round_to: int}  $tier
     */
    private function applyTierSellingPrice(int $basePrice, array $tier): int
    {
        $percent = max(0, (float) ($tier['percent'] ?? 0));
        $fixed = max(0, (int) ($tier['fixed'] ?? 0));
        $roundTo = max(1, (int) ($tier['round_to'] ?? 100));
        $priceWithMargin = $basePrice + (int) ceil($basePrice * $percent) + $fixed;

        return (int) (ceil($priceWithMargin / $roundTo) * $roundTo);
    }

    /**
     * @return array<int, array{max: int|null, percent: float, fixed: int, round_to: int}>
     */
    private function marginTiers(): array
    {
        return $this->settings->marginTiers();
    }

    private function coinValueRupiah(): int
    {
        return max(1, (int) config('lyva_coins.coin_value_rupiah', 1));
    }

    private function maxRewardPercentOfSellingPrice(): float
    {
        return max(0, (float) config('lyva_coins.max_reward_percent_of_selling_price', 0.01));
    }

    private function rewardShareOfEstimatedProfit(): float
    {
        return max(0, (float) config('lyva_coins.reward_share_of_estimated_profit', 0.15));
    }

    private function minimumRewardCoins(): int
    {
        return max(1, (int) config('lyva_coins.minimum_reward_coins', 1));
    }

    private function formatPercent(float $value): string
    {
        $percent = $value * 100;

        return fmod($percent, 1.0) === 0.0
            ? number_format($percent, 0, ',', '.').'%'
            : rtrim(rtrim(number_format($percent, 2, ',', '.'), '0'), ',').'%';
    }
}
