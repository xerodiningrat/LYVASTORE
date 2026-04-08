import type { SharedData } from '@/types';

type CoinProgram = NonNullable<SharedData['coinProgram']>;
type CoinTier = CoinProgram['tiers'][number];

const clamp = (value: number, min: number, max: number) => Math.min(max, Math.max(min, value));

const applyTierSellingPrice = (basePrice: number, tier: CoinTier) => {
    const percent = Math.max(0, Number(tier.percent ?? 0));
    const fixed = Math.max(0, Number(tier.fixed ?? 0));
    const roundTo = Math.max(1, Number(tier.roundTo ?? 100));
    const priceWithMargin = basePrice + Math.ceil(basePrice * percent) + fixed;

    return Math.ceil(priceWithMargin / roundTo) * roundTo;
};

const searchBasePriceWithinTier = (sellingAmount: number, tier: CoinTier, lowerBound: number, upperBound: number) => {
    const percent = Math.max(0, Number(tier.percent ?? 0));
    const fixed = Math.max(0, Number(tier.fixed ?? 0));
    const roundTo = Math.max(1, Number(tier.roundTo ?? 100));
    const estimate = clamp(Math.floor((sellingAmount - fixed) / Math.max(1, 1 + percent)), lowerBound, upperBound);
    const searchRadius = Math.max(800, roundTo * 4);
    const start = Math.max(lowerBound, estimate - searchRadius);
    const end = Math.min(upperBound, estimate + searchRadius);
    let bestBase: number | null = null;
    let bestDelta: number | null = null;

    for (let basePrice = start; basePrice <= end; basePrice += 1) {
        const calculatedSellingPrice = applyTierSellingPrice(basePrice, tier);
        const delta = Math.abs(calculatedSellingPrice - sellingAmount);

        if (bestDelta === null || delta < bestDelta || (delta === bestDelta && (bestBase === null || basePrice > bestBase))) {
            bestDelta = delta;
            bestBase = basePrice;
        }
    }

    if (bestBase === null || bestDelta === null || bestDelta > roundTo) {
        return null;
    }

    return bestBase;
};

const estimateBasePriceFromSellingAmount = (sellingAmount: number, program?: CoinProgram | null) => {
    if (!program?.tiers?.length) {
        return Math.max(0, Math.floor(sellingAmount * 0.94));
    }

    let previousMax = 0;

    for (const tier of program.tiers) {
        const lowerBound = Math.max(1, previousMax + 1);
        const rawUpperBound = tier.max == null ? sellingAmount : Number(tier.max);
        const upperBound = Math.min(sellingAmount, rawUpperBound);

        if (upperBound >= lowerBound) {
            const candidate = searchBasePriceWithinTier(sellingAmount, tier, lowerBound, upperBound);

            if (candidate !== null) {
                return candidate;
            }
        }

        if (tier.max != null) {
            previousMax = Number(tier.max);
        }
    }

    return Math.max(0, Math.floor(sellingAmount * 0.94));
};

export const lyvaCoinsForAmount = (amount: number, program?: CoinProgram | null) => {
    const normalizedAmount = Math.max(0, Math.round(Number(amount) || 0));

    if (normalizedAmount <= 0) {
        return 0;
    }

    const coinValueRupiah = Math.max(1, Number(program?.coinValueRupiah ?? 1));
    const minimumRewardCoins = Math.max(1, Number(program?.minimumRewardCoins ?? 1));
    const maxRewardPercentOfSellingPrice = Math.max(0, Number(program?.maxRewardPercentOfSellingPrice ?? 0.01));
    const rewardShareOfEstimatedProfit = Math.max(0, Number(program?.rewardShareOfEstimatedProfit ?? 0.15));
    const estimatedBasePrice = estimateBasePriceFromSellingAmount(normalizedAmount, program);
    const estimatedProfit = Math.max(0, normalizedAmount - estimatedBasePrice);
    const saleCapBudget = Math.round(normalizedAmount * maxRewardPercentOfSellingPrice);
    const profitBudget = Math.round(estimatedProfit * rewardShareOfEstimatedProfit);
    const rewardBudget = profitBudget > 0 ? Math.min(saleCapBudget, profitBudget) : saleCapBudget;

    return Math.max(minimumRewardCoins, Math.round(Math.max(0, rewardBudget) / coinValueRupiah));
};
