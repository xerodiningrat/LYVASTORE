<script setup lang="ts">
import { catalogProducts, isPublicCatalogProductVisible, vipaymentBackedProductIds } from '@/data/catalog';
import type { RecentPurchaseItem, SharedData } from '@/types';
import { Coins, Sparkles } from 'lucide-vue-next';
import { usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps<{
    items: RecentPurchaseItem[];
}>();
const page = usePage<SharedData>();
const unavailableProductIds = computed(() => new Set(page.props.unavailableProductIds ?? []));

const currentIndex = ref(0);
const visible = ref(false);
const syntheticItems = ref<RecentPurchaseItem[]>([]);
const nowTick = ref(Date.now());

const DISPLAY_MS = 4300;
const SWITCH_MS = 420;
const SYNTHETIC_ITEM_COUNT = 28;
const DAILY_REFRESH_MS = 60_000;
const RELATIVE_TICK_MS = 1_000;

const customerSeeds = [
    'Riz***',
    'Ald***',
    'Dik***',
    'Sya***',
    'Raf***',
    'Han***',
    'Put***',
    'Nad***',
    'Fik***',
    'Ayu***',
    'Bag***',
    'Ari***',
    'Dan***',
    'Zak***',
    'Lut***',
    'Gil***',
    'Rah***',
    'Fau***',
    'Lia***',
    'Nia***',
];

const statusSeeds = ['Live order', 'Pembayaran masuk', 'Pesanan selesai', 'Checkout berhasil', 'Sedang diproses'];

let cycleInterval: ReturnType<typeof window.setInterval> | null = null;
let switchTimeout: ReturnType<typeof window.setTimeout> | null = null;
let dailyRefreshInterval: ReturnType<typeof window.setInterval> | null = null;
let relativeTimeInterval: ReturnType<typeof window.setInterval> | null = null;

const displayItems = computed(() => {
    const uniqueItems = new Map<string, RecentPurchaseItem>();

    [...props.items, ...syntheticItems.value].forEach((item) => {
        uniqueItems.set(item.id, item);
    });

    return Array.from(uniqueItems.values());
});

const activeItem = computed(() => displayItems.value[currentIndex.value] ?? null);

const productInitials = (label: string) =>
    label
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((word) => word.charAt(0).toUpperCase())
        .join('')
        .slice(0, 2);

const seededUnit = (seed: number) => {
    const value = Math.sin(seed * 12.9898) * 43758.5453;

    return value - Math.floor(value);
};

const seededIndex = (length: number, seed: number) => {
    if (length <= 0) {
        return 0;
    }

    return Math.floor(seededUnit(seed) * length) % length;
};

const toRupiah = (amount: number) => `Rp${new Intl.NumberFormat('id-ID').format(amount)}`;

const roundAmount = (amount: number, step: number) => Math.max(step, Math.round(amount / step) * step);

const currentDayKey = () => {
    const now = new Date();
    const formatter = new Intl.DateTimeFormat('en-CA', {
        timeZone: 'Asia/Jakarta',
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
    });

    return formatter.format(now);
};

const syntheticSecondsAgo = (seed: number) => Math.max(9, Math.floor(seededUnit(seed + 29) * 14 * 60 * 60));
const popupSecondsAgoSeed = (item: RecentPurchaseItem) => {
    const dayKey = currentDayKey();
    const rawSeed = Array.from(`${item.id}-${item.productLabel}-${dayKey}`).reduce((total, char, index) => total + char.charCodeAt(0) * (index + 1), 0);

    return Math.max(5, Math.floor(seededUnit(rawSeed + 73) * 25 * 60));
};

const formatRelativeTime = (secondsAgo: number) => {
    const normalizedSecondsAgo = Math.max(1, Math.floor(secondsAgo));

    if (normalizedSecondsAgo < 60) {
        return `${normalizedSecondsAgo} detik yang lalu`;
    }

    if (normalizedSecondsAgo < 3600) {
        return `${Math.max(1, Math.floor(normalizedSecondsAgo / 60))} menit yang lalu`;
    }

    return `${Math.max(1, Math.floor(normalizedSecondsAgo / 3600))} jam yang lalu`;
};

const resolveTimeLabel = (item: RecentPurchaseItem) => {
    nowTick.value;

    const fallbackSecondsAgo = popupSecondsAgoSeed(item);

    if (item.occurredAt) {
        const occurredAtMs = new Date(item.occurredAt).getTime();

        if (Number.isFinite(occurredAtMs) && occurredAtMs > 0) {
            const secondsAgo = Math.max(1, Math.floor((nowTick.value - occurredAtMs) / 1000));

            if (secondsAgo > 30 * 60) {
                return formatRelativeTime(fallbackSecondsAgo);
            }

            return formatRelativeTime(secondsAgo);
        }
    }

    return formatRelativeTime(fallbackSecondsAgo);
};

const generateAmountForLabel = (label: string, seed: number) => {
    const lowerLabel = label.toLowerCase();
    const ratio = seededUnit(seed + 91);

    if (lowerLabel.includes('chatgpt') || lowerLabel.includes('capcut') || lowerLabel.includes('spotify') || lowerLabel.includes('netflix')) {
        return roundAmount(4500 + ratio * 16500, 100);
    }

    if (
        lowerLabel.includes('mobile legends') ||
        lowerLabel.includes('free fire') ||
        lowerLabel.includes('pubg') ||
        lowerLabel.includes('honor of kings') ||
        lowerLabel.includes('genshin')
    ) {
        return roundAmount(900 + ratio * 149000, 100);
    }

    if (lowerLabel.includes('wallet') || lowerLabel.includes('gift') || lowerLabel.includes('playstation') || lowerLabel.includes('google play')) {
        return roundAmount(10000 + ratio * 240000, 500);
    }

    return roundAmount(3000 + ratio * 87000, 100);
};

const buildSyntheticItems = () => {
    const vipaymentProductIds = new Set(vipaymentBackedProductIds);
    const realProducts = props.items.map((item) => ({
        label: item.productLabel,
        image: item.productImage ?? null,
    }));

    const catalogPool = catalogProducts
        .filter((product) => vipaymentProductIds.has(product.id) && isPublicCatalogProductVisible(product) && !unavailableProductIds.value.has(product.id))
        .map((product) => ({
            label: product.name,
            image: product.coverImage ?? product.iconImage ?? null,
        }));

    const productPool = [...realProducts, ...catalogPool].filter((product, index, self) => self.findIndex((entry) => entry.label === product.label) === index);

    if (productPool.length === 0) {
        return [];
    }

    const dayKey = currentDayKey();
    const seedBase = Number(dayKey.replaceAll('-', '')) || Date.now();

    return Array.from({ length: SYNTHETIC_ITEM_COUNT }, (_, index) => {
        const seed = seedBase + index * 17;
        const product = productPool[seededIndex(productPool.length, seed + 7)];
        const customerLabel = customerSeeds[seededIndex(customerSeeds.length, seed + 13)];
        const secondsAgo = syntheticSecondsAgo(seed);
        const timeLabel = formatRelativeTime(secondsAgo);
        const statusLabel = statusSeeds[seededIndex(statusSeeds.length, seed + 47)];
        const amountLabel = toRupiah(generateAmountForLabel(product.label, seed));

        return {
            id: `synthetic-purchase-${seedBase}-${index}`,
            customerLabel,
            productLabel: product.label,
            amountLabel,
            timeLabel,
            occurredAt: new Date(Date.now() - secondsAgo * 1000).toISOString(),
            statusLabel,
            productImage: product.image,
        } satisfies RecentPurchaseItem;
    });
};

const clearTimers = () => {
    if (cycleInterval) {
        window.clearInterval(cycleInterval);
        cycleInterval = null;
    }

    if (switchTimeout) {
        window.clearTimeout(switchTimeout);
        switchTimeout = null;
    }

    if (dailyRefreshInterval) {
        window.clearInterval(dailyRefreshInterval);
        dailyRefreshInterval = null;
    }

    if (relativeTimeInterval) {
        window.clearInterval(relativeTimeInterval);
        relativeTimeInterval = null;
    }
};

const startRotation = () => {
    clearTimers();

    if (typeof window === 'undefined' || displayItems.value.length === 0) {
        visible.value = false;
        return;
    }

    currentIndex.value = 0;
    visible.value = true;

    if (displayItems.value.length === 1) {
        return;
    }

    cycleInterval = window.setInterval(() => {
        visible.value = false;

        switchTimeout = window.setTimeout(() => {
            currentIndex.value = (currentIndex.value + 1) % displayItems.value.length;
            visible.value = true;
        }, SWITCH_MS);
    }, DISPLAY_MS + SWITCH_MS);
};

const refreshItems = () => {
    syntheticItems.value = buildSyntheticItems();
    startRotation();
};

watch(
    () => props.items,
    () => {
        if (typeof window === 'undefined') {
            return;
        }

        refreshItems();
    },
    { deep: true },
);

onMounted(() => {
    refreshItems();

    dailyRefreshInterval = window.setInterval(() => {
        refreshItems();
    }, DAILY_REFRESH_MS);

    relativeTimeInterval = window.setInterval(() => {
        nowTick.value = Date.now();
    }, RELATIVE_TICK_MS);
});

onBeforeUnmount(() => {
    clearTimers();
});
</script>

<template>
    <div class="pointer-events-none fixed bottom-[7.65rem] left-3 z-[60] hidden w-[calc(100vw-1.5rem)] max-w-[17rem] sm:block sm:bottom-8 sm:left-4 sm:max-w-[18rem]">
        <Transition name="recent-purchase-pop" mode="out-in">
            <div
                v-if="activeItem && visible"
                :key="`${activeItem.id}-${currentIndex}`"
                class="recent-purchase-popup pointer-events-auto relative overflow-hidden rounded-[20px] border border-white/80 bg-white/88 p-3 shadow-[0_14px_28px_rgba(15,23,42,0.1)] backdrop-blur-xl"
            >
                <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-indigo-300/70 to-transparent"></div>
                <div class="absolute -left-10 top-4 h-14 w-14 rounded-full bg-indigo-100/70 blur-2xl"></div>
                <div class="absolute -right-10 bottom-0 h-16 w-16 rounded-full bg-cyan-100/70 blur-2xl"></div>

                <div class="relative flex items-start gap-3">
                    <div class="relative shrink-0">
                        <img
                            v-if="activeItem.productImage"
                            :src="activeItem.productImage"
                            :alt="activeItem.productLabel"
                            class="h-11 w-11 rounded-[14px] object-cover shadow-[0_10px_18px_rgba(15,23,42,0.1)]"
                        />
                        <div
                            v-else
                            class="flex h-11 w-11 items-center justify-center rounded-[14px] bg-[linear-gradient(135deg,rgba(224,231,255,0.98),rgba(255,255,255,0.96))] text-[0.72rem] font-black uppercase tracking-[0.08em] text-indigo-700 shadow-[0_10px_18px_rgba(15,23,42,0.08)]"
                        >
                            {{ productInitials(activeItem.productLabel) }}
                        </div>
                        <span class="absolute -right-1 -top-1 inline-flex h-[1.125rem] w-[1.125rem] items-center justify-center rounded-full border border-white bg-emerald-500 text-white shadow-[0_8px_16px_rgba(16,185,129,0.24)]">
                            <Sparkles class="size-2.5" />
                        </span>
                    </div>

                    <div class="min-w-0 flex-1 space-y-1.5">
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1.5">
                            <span class="inline-flex items-center gap-1.5 rounded-full border border-indigo-200 bg-indigo-50 px-2.5 py-0.75 text-[0.54rem] font-black uppercase tracking-[0.22em] text-indigo-700">
                                <span class="h-1.5 w-1.5 rounded-full bg-indigo-500 recent-purchase-popup__live-dot"></span>
                                Live order
                            </span>
                            <span class="inline-flex items-center pt-px text-[0.58rem] font-bold uppercase tracking-[0.14em] text-slate-400">
                                {{ activeItem.statusLabel }}
                            </span>
                        </div>

                        <p class="text-[0.88rem] font-bold leading-[1.28rem] text-slate-950">
                            <span class="text-indigo-700">{{ activeItem.customerLabel }}</span>
                            baru membeli
                        </p>

                        <p class="line-clamp-2 text-[0.8rem] font-semibold leading-[1.35rem] text-slate-700">
                            {{ activeItem.productLabel }}
                        </p>

                        <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-[0.68rem] font-semibold text-slate-500">
                            <span class="inline-flex items-center gap-1">
                                <Coins class="size-2.5 text-amber-500" />
                                {{ activeItem.amountLabel }}
                            </span>
                            <span class="h-1 w-1 rounded-full bg-slate-300"></span>
                            <span>{{ resolveTimeLabel(activeItem) }}</span>
                        </div>
                    </div>
                </div>

                <div class="recent-purchase-popup__progress-wrap mt-2">
                    <div class="recent-purchase-popup__progress"></div>
                </div>
            </div>
        </Transition>
    </div>
</template>

<style scoped>
.recent-purchase-pop-enter-active,
.recent-purchase-pop-leave-active {
    transition:
        opacity 220ms ease,
        transform 220ms ease;
}

.recent-purchase-pop-enter-from,
.recent-purchase-pop-leave-to {
    opacity: 0;
    transform: translate3d(-10px, 14px, 0) scale(0.98);
}

.recent-purchase-popup__progress-wrap {
    height: 0.32rem;
    overflow: hidden;
    border-radius: 9999px;
    background: rgba(226, 232, 240, 0.72);
}

.recent-purchase-popup__progress {
    height: 100%;
    width: 100%;
    transform-origin: left center;
    border-radius: inherit;
    background: linear-gradient(90deg, rgba(99, 102, 241, 0.95), rgba(34, 211, 238, 0.92));
    animation: recent-purchase-progress 4.3s linear infinite;
}

.recent-purchase-popup__live-dot {
    animation: recent-purchase-live-dot 1.8s ease-in-out infinite;
}

@keyframes recent-purchase-progress {
    from {
        transform: scaleX(1);
    }

    to {
        transform: scaleX(0);
    }
}

@keyframes recent-purchase-live-dot {
    0%,
    100% {
        transform: scale(1);
        box-shadow: 0 0 0 0 rgba(99, 102, 241, 0.2);
    }

    50% {
        transform: scale(1.08);
        box-shadow: 0 0 0 6px rgba(99, 102, 241, 0);
    }
}

@media (prefers-reduced-motion: reduce) {
    .recent-purchase-pop-enter-active,
    .recent-purchase-pop-leave-active,
    .recent-purchase-popup__progress,
    .recent-purchase-popup__live-dot {
        animation: none !important;
        transition: none !important;
    }
}
</style>
