<script setup lang="ts">
import Input from '@/components/ui/input/Input.vue';
import Label from '@/components/ui/label/Label.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { Head } from '@inertiajs/vue3';
import {
    ArrowRight,
    Calculator,
    Coins,
    ShieldCheck,
    Sparkles,
    Star,
    Target,
    TrendingUp,
    WalletCards,
    type LucideIcon,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

type CalculatorShortcut = {
    id: string;
    label: string;
    description: string;
    icon: LucideIcon;
    tone: string;
};

type CalculatorParticle = {
    id: number;
    size: string;
    left: string;
    top: string;
    delay: string;
    duration: string;
    opacity: number;
};

type BudgetSummary = {
    valid: boolean;
    totalAttempts: number;
    promoAttempts: number;
    normalAttempts: number;
    totalDiamonds: number;
    averageCost: number;
};

const calculatorShortcuts: CalculatorShortcut[] = [
    {
        id: 'winrate-mobile-legends',
        label: 'Hitung Win Rate ML',
        description: 'Cari jumlah win tambahan untuk kejar target win rate kamu.',
        icon: TrendingUp,
        tone: 'from-indigo-500/22 via-violet-500/10 to-transparent',
    },
    {
        id: 'magic-wheel-mobile-legends',
        label: 'Magic Wheel ML',
        description: 'Simulasi kebutuhan diamond untuk spin promo dan spin normal.',
        icon: Sparkles,
        tone: 'from-amber-400/24 via-orange-500/12 to-transparent',
    },
    {
        id: 'zodiac-mobile-legends',
        label: 'Zodiac ML',
        description: 'Perkiraan budget draw zodiac dengan harga event yang kamu pakai.',
        icon: Star,
        tone: 'from-sky-400/24 via-cyan-500/12 to-transparent',
    },
];

const calculatorParticles: CalculatorParticle[] = [
    { id: 1, size: '10px', left: '7%', top: '12%', delay: '0s', duration: '8.8s', opacity: 0.42 },
    { id: 2, size: '14px', left: '17%', top: '22%', delay: '1.3s', duration: '10.1s', opacity: 0.28 },
    { id: 3, size: '9px', left: '28%', top: '15%', delay: '0.6s', duration: '9.2s', opacity: 0.48 },
    { id: 4, size: '12px', left: '38%', top: '30%', delay: '2.2s', duration: '11.4s', opacity: 0.24 },
    { id: 5, size: '16px', left: '49%', top: '18%', delay: '1.7s', duration: '9.8s', opacity: 0.34 },
    { id: 6, size: '11px', left: '62%', top: '26%', delay: '0.5s', duration: '8.9s', opacity: 0.4 },
    { id: 7, size: '18px', left: '73%', top: '15%', delay: '2.1s', duration: '12.2s', opacity: 0.2 },
    { id: 8, size: '10px', left: '84%', top: '24%', delay: '1.2s', duration: '9.6s', opacity: 0.38 },
    { id: 9, size: '13px', left: '92%', top: '32%', delay: '2.8s', duration: '10.8s', opacity: 0.22 },
    { id: 10, size: '10px', left: '11%', top: '67%', delay: '0.9s', duration: '8.7s', opacity: 0.34 },
    { id: 11, size: '16px', left: '25%', top: '79%', delay: '2.4s', duration: '11.2s', opacity: 0.24 },
    { id: 12, size: '9px', left: '44%', top: '72%', delay: '0.2s', duration: '9.5s', opacity: 0.42 },
    { id: 13, size: '14px', left: '65%', top: '80%', delay: '1.9s', duration: '10.9s', opacity: 0.24 },
    { id: 14, size: '11px', left: '86%', top: '69%', delay: '2.6s', duration: '8.8s', opacity: 0.4 },
];

const currentMatches = ref('185');
const currentWinRate = ref('57.8');
const targetWinRate = ref('65');

const magicWheelAttempts = ref('200');
const magicWheelPromoAttempts = ref('5');
const magicWheelPromoCost = ref('30');
const magicWheelNormalCost = ref('60');

const zodiacAttempts = ref('100');
const zodiacPromoAttempts = ref('10');
const zodiacPromoCost = ref('25');
const zodiacNormalCost = ref('60');

const parsePositiveNumber = (value: string) => {
    const parsed = Number(value);

    return Number.isFinite(parsed) ? parsed : NaN;
};

const formatNumber = (value: number, maximumFractionDigits = 0) =>
    new Intl.NumberFormat('id-ID', {
        maximumFractionDigits,
        minimumFractionDigits: value % 1 === 0 ? 0 : Math.min(maximumFractionDigits, 1),
    }).format(value);

const buildBudgetSummary = (attemptsValue: string, promoAttemptsValue: string, promoCostValue: string, normalCostValue: string): BudgetSummary => {
    const totalAttempts = parsePositiveNumber(attemptsValue);
    const promoAttempts = parsePositiveNumber(promoAttemptsValue);
    const promoCost = parsePositiveNumber(promoCostValue);
    const normalCost = parsePositiveNumber(normalCostValue);

    if (
        ![totalAttempts, promoAttempts, promoCost, normalCost].every(Number.isFinite) ||
        totalAttempts <= 0 ||
        promoAttempts < 0 ||
        promoCost < 0 ||
        normalCost < 0
    ) {
        return {
            valid: false,
            totalAttempts: 0,
            promoAttempts: 0,
            normalAttempts: 0,
            totalDiamonds: 0,
            averageCost: 0,
        };
    }

    const safeTotalAttempts = Math.floor(totalAttempts);
    const safePromoAttempts = Math.min(safeTotalAttempts, Math.floor(promoAttempts));
    const normalAttempts = Math.max(safeTotalAttempts - safePromoAttempts, 0);
    const totalDiamonds = safePromoAttempts * promoCost + normalAttempts * normalCost;

    return {
        valid: true,
        totalAttempts: safeTotalAttempts,
        promoAttempts: safePromoAttempts,
        normalAttempts,
        totalDiamonds,
        averageCost: totalDiamonds / safeTotalAttempts,
    };
};

const winRateSummary = computed(() => {
    const matches = parsePositiveNumber(currentMatches.value);
    const currentRate = parsePositiveNumber(currentWinRate.value);
    const targetRate = parsePositiveNumber(targetWinRate.value);

    if (![matches, currentRate, targetRate].every(Number.isFinite) || matches <= 0) {
        return {
            valid: false,
            impossible: false,
            title: 'Isi total match dan win rate dulu.',
            description: 'Masukkan data match yang sudah dimainkan supaya hasilnya bisa dihitung.',
            winsNeeded: 0,
            projectedMatches: 0,
            estimatedCurrentWins: 0,
        };
    }

    if (currentRate < 0 || currentRate > 100 || targetRate < 0 || targetRate > 100) {
        return {
            valid: false,
            impossible: false,
            title: 'Persentase harus di antara 0 sampai 100.',
            description: 'Cek lagi nilai win rate sekarang dan target yang ingin kamu capai.',
            winsNeeded: 0,
            projectedMatches: 0,
            estimatedCurrentWins: 0,
        };
    }

    const estimatedCurrentWins = (matches * currentRate) / 100;

    if (targetRate <= currentRate) {
        return {
            valid: true,
            impossible: false,
            title: 'Target kamu sudah tercapai.',
            description: 'Kalau target tidak lebih tinggi dari win rate saat ini, kamu belum butuh win tambahan.',
            winsNeeded: 0,
            projectedMatches: Math.round(matches),
            estimatedCurrentWins,
        };
    }

    if (targetRate === 100) {
        return {
            valid: false,
            impossible: true,
            title: 'Target 100% tidak bisa dikejar lagi.',
            description: 'Kalau akun sudah pernah kalah, win rate 100% tidak mungkin kembali penuh.',
            winsNeeded: 0,
            projectedMatches: Math.round(matches),
            estimatedCurrentWins,
        };
    }

    const winsNeeded = Math.max(
        0,
        Math.ceil((((targetRate / 100) * matches) - estimatedCurrentWins) / (1 - targetRate / 100)),
    );

    return {
        valid: true,
        impossible: false,
        title: `Perlu sekitar ${formatNumber(winsNeeded)} win tambahan.`,
        description: `Kalau semua match berikutnya menang, kamu bisa naik ke target ${formatNumber(targetRate, 1)}%.`,
        winsNeeded,
        projectedMatches: Math.round(matches + winsNeeded),
        estimatedCurrentWins,
    };
});

const magicWheelSummary = computed(() =>
    buildBudgetSummary(
        magicWheelAttempts.value,
        magicWheelPromoAttempts.value,
        magicWheelPromoCost.value,
        magicWheelNormalCost.value,
    ),
);

const zodiacSummary = computed(() =>
    buildBudgetSummary(
        zodiacAttempts.value,
        zodiacPromoAttempts.value,
        zodiacPromoCost.value,
        zodiacNormalCost.value,
    ),
);
</script>

<template>
    <Head title="Kalkulator" />

    <PublicLayout active-nav="kalkulator">
        <main class="relative overflow-hidden px-4 py-8 sm:px-6 lg:px-8 lg:py-10">
            <div
                class="pointer-events-none absolute inset-x-0 top-0 h-80"
                style="background-image: radial-gradient(circle at top, rgba(67, 56, 202, 0.22), transparent 58%)"
            />

            <div class="pointer-events-none absolute inset-0">
                <div class="login-orb absolute left-[-5%] top-[8%] h-72 w-72 rounded-full bg-indigo-300/30 blur-3xl" />
                <div class="login-orb login-orb--alt absolute right-[-2%] top-[12%] h-80 w-80 rounded-full bg-sky-200/32 blur-3xl" />
                <div class="login-orb login-orb--soft absolute bottom-[6%] left-[10%] h-80 w-80 rounded-full bg-violet-200/20 blur-3xl" />

                <span
                    v-for="particle in calculatorParticles"
                    :key="particle.id"
                    class="login-particle"
                    :style="{
                        width: particle.size,
                        height: particle.size,
                        left: particle.left,
                        top: particle.top,
                        animationDelay: particle.delay,
                        animationDuration: particle.duration,
                        opacity: particle.opacity,
                    }"
                />
            </div>

            <section class="relative mx-auto max-w-7xl overflow-hidden rounded-[40px] border border-white/85 bg-[linear-gradient(180deg,rgba(255,255,255,0.95)_0%,rgba(244,247,255,0.96)_100%)] p-6 shadow-[0_36px_120px_rgba(99,102,241,0.12)] backdrop-blur-xl sm:p-8 lg:p-10">
                <div class="transaction-grid pointer-events-none absolute inset-0 opacity-70" />
                <div class="pointer-events-none absolute inset-x-0 top-0 h-40 bg-[radial-gradient(circle_at_top,rgba(67,56,202,0.16),transparent_62%)]" />

                <div class="relative z-10 grid gap-8 lg:grid-cols-[1.02fr,0.98fr] lg:gap-12">
                    <div>
                        <p class="text-[0.72rem] font-semibold uppercase tracking-[0.26em] text-indigo-600">Kalkulator</p>
                        <h1 class="mt-4 max-w-2xl text-4xl font-black tracking-tight text-slate-950 [font-family:'Space Grotesk',sans-serif] sm:text-5xl">
                            Tools cepat buat hitung kebutuhan Mobile Legends.
                        </h1>
                        <p class="mt-5 max-w-2xl text-sm leading-7 text-slate-600 sm:text-base">
                            Pilih kalkulator yang kamu butuhkan, lalu ubah angka sesuai skenario kamu sendiri. Dropdown di navbar juga langsung lompat ke section yang sama.
                        </p>

                        <div class="mt-8 grid gap-4 sm:grid-cols-3">
                            <a
                                v-for="shortcut in calculatorShortcuts"
                                :key="shortcut.id"
                                :href="`#${shortcut.id}`"
                                class="group relative overflow-hidden rounded-[28px] border border-white/85 bg-white/85 p-5 shadow-[0_18px_42px_rgba(15,23,42,0.05)] transition hover:-translate-y-1 hover:shadow-[0_28px_60px_rgba(79,70,229,0.14)]"
                            >
                                <div class="pointer-events-none absolute inset-0 bg-gradient-to-br" :class="shortcut.tone" />
                                <span class="relative flex h-11 w-11 items-center justify-center rounded-2xl bg-white/80 text-indigo-700 shadow-[0_12px_24px_rgba(99,102,241,0.12)]">
                                    <component :is="shortcut.icon" class="size-4.5" />
                                </span>
                                <h2 class="relative mt-5 text-sm font-black uppercase tracking-[0.08em] text-slate-950 [font-family:'Space Grotesk',sans-serif]">
                                    {{ shortcut.label }}
                                </h2>
                                <p class="relative mt-3 text-sm leading-6 text-slate-600">{{ shortcut.description }}</p>
                                <span class="relative mt-5 inline-flex items-center gap-2 text-xs font-bold uppercase tracking-[0.14em] text-indigo-700">
                                    Buka kalkulator
                                    <ArrowRight class="size-3.5 transition group-hover:translate-x-1" />
                                </span>
                            </a>
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-3 lg:grid-cols-1">
                        <article class="rounded-[30px] border border-white/85 bg-white/84 p-5 shadow-[0_20px_48px_rgba(15,23,42,0.05)]">
                            <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-700">
                                <Calculator class="size-4.5" />
                            </span>
                            <p class="mt-5 text-[0.68rem] font-semibold uppercase tracking-[0.2em] text-slate-500">Live tools</p>
                            <p class="mt-2 text-3xl font-black text-slate-950 [font-family:'Space Grotesk',sans-serif]">3</p>
                            <p class="mt-3 text-sm leading-6 text-slate-600">Win rate, Magic Wheel, dan Zodiac langsung siap dipakai.</p>
                        </article>

                        <article class="rounded-[30px] border border-white/85 bg-white/84 p-5 shadow-[0_20px_48px_rgba(15,23,42,0.05)]">
                            <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700">
                                <WalletCards class="size-4.5" />
                            </span>
                            <p class="mt-5 text-[0.68rem] font-semibold uppercase tracking-[0.2em] text-slate-500">Budget flex</p>
                            <p class="mt-2 text-3xl font-black text-slate-950 [font-family:'Space Grotesk',sans-serif]">Custom</p>
                            <p class="mt-3 text-sm leading-6 text-slate-600">Harga promo dan harga normal bisa kamu ubah sesuai event yang sedang berjalan.</p>
                        </article>

                        <article class="rounded-[30px] border border-white/85 bg-white/84 p-5 shadow-[0_20px_48px_rgba(15,23,42,0.05)]">
                            <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-sky-50 text-sky-700">
                                <ShieldCheck class="size-4.5" />
                            </span>
                            <p class="mt-5 text-[0.68rem] font-semibold uppercase tracking-[0.2em] text-slate-500">Catatan</p>
                            <p class="mt-2 text-3xl font-black text-slate-950 [font-family:'Space Grotesk',sans-serif]">Estimasi</p>
                            <p class="mt-3 text-sm leading-6 text-slate-600">Simulasi ini bantu ambil keputusan lebih cepat, bukan angka resmi dari event.</p>
                        </article>
                    </div>
                </div>
            </section>

            <div class="mx-auto mt-8 max-w-7xl space-y-8">
                <section
                    id="winrate-mobile-legends"
                    class="relative overflow-hidden rounded-[36px] border border-white/85 bg-white/84 p-6 shadow-[0_26px_72px_rgba(79,70,229,0.08)] sm:p-8 lg:p-10"
                >
                    <div class="pointer-events-none absolute inset-x-0 top-0 h-32 bg-[radial-gradient(circle_at_top_left,rgba(99,102,241,0.18),transparent_60%)]" />

                    <div class="relative z-10 grid gap-8 lg:grid-cols-[0.95fr,1.05fr] lg:gap-10">
                        <div>
                            <div class="inline-flex items-center gap-3 rounded-full border border-indigo-100 bg-indigo-50/80 px-4 py-2 text-xs font-bold uppercase tracking-[0.16em] text-indigo-700">
                                <TrendingUp class="size-3.5" />
                                Hitung Win Rate ML
                            </div>

                            <h2 class="mt-5 text-3xl font-black tracking-tight text-slate-950 [font-family:'Space Grotesk',sans-serif] sm:text-[2.2rem]">
                                Cari berapa win tambahan untuk kejar target.
                            </h2>
                            <p class="mt-4 max-w-2xl text-sm leading-7 text-slate-600 sm:text-base">
                                Masukkan total match yang sudah kamu mainkan, win rate saat ini, lalu target yang ingin dicapai. Kalkulator akan menghitung berapa win beruntun yang dibutuhkan.
                            </p>

                            <div class="mt-7 grid gap-4 sm:grid-cols-2">
                                <article class="rounded-[24px] border border-slate-200/80 bg-slate-50/90 p-4">
                                    <p class="text-[0.68rem] font-semibold uppercase tracking-[0.2em] text-slate-500">Perkiraan win saat ini</p>
                                    <p class="mt-3 text-2xl font-black text-slate-950 [font-family:'Space Grotesk',sans-serif]">
                                        {{ formatNumber(winRateSummary.estimatedCurrentWins, 1) }}
                                    </p>
                                    <p class="mt-2 text-sm text-slate-600">Hasil ini bersifat estimasi jika win rate kamu dibulatkan.</p>
                                </article>

                                <article class="rounded-[24px] border border-slate-200/80 bg-slate-50/90 p-4">
                                    <p class="text-[0.68rem] font-semibold uppercase tracking-[0.2em] text-slate-500">Proyeksi total match</p>
                                    <p class="mt-3 text-2xl font-black text-slate-950 [font-family:'Space Grotesk',sans-serif]">
                                        {{ formatNumber(winRateSummary.projectedMatches) }}
                                    </p>
                                    <p class="mt-2 text-sm text-slate-600">Total match setelah semua kemenangan tambahan dihitung.</p>
                                </article>
                            </div>
                        </div>

                        <div class="rounded-[30px] border border-white/80 bg-[linear-gradient(180deg,rgba(255,255,255,0.96)_0%,rgba(244,247,255,0.98)_100%)] p-5 shadow-[0_22px_48px_rgba(15,23,42,0.06)] sm:p-6">
                            <div class="grid gap-5 sm:grid-cols-2">
                                <div class="space-y-2">
                                    <Label for="current-matches" class="text-sm font-semibold text-slate-700">Total match sekarang</Label>
                                    <Input
                                        id="current-matches"
                                        v-model="currentMatches"
                                        type="number"
                                        min="1"
                                        step="1"
                                        class="h-12 rounded-2xl border-slate-200 bg-white px-4 text-sm"
                                        placeholder="Contoh: 185"
                                    />
                                </div>

                                <div class="space-y-2">
                                    <Label for="current-win-rate" class="text-sm font-semibold text-slate-700">Win rate sekarang (%)</Label>
                                    <Input
                                        id="current-win-rate"
                                        v-model="currentWinRate"
                                        type="number"
                                        min="0"
                                        max="100"
                                        step="0.1"
                                        class="h-12 rounded-2xl border-slate-200 bg-white px-4 text-sm"
                                        placeholder="Contoh: 57.8"
                                    />
                                </div>

                                <div class="space-y-2 sm:col-span-2">
                                    <Label for="target-win-rate" class="text-sm font-semibold text-slate-700">Target win rate (%)</Label>
                                    <Input
                                        id="target-win-rate"
                                        v-model="targetWinRate"
                                        type="number"
                                        min="0"
                                        max="100"
                                        step="0.1"
                                        class="h-12 rounded-2xl border-slate-200 bg-white px-4 text-sm"
                                        placeholder="Contoh: 65"
                                    />
                                </div>
                            </div>

                            <div class="mt-6 rounded-[26px] border border-indigo-100 bg-[linear-gradient(135deg,rgba(79,70,229,0.08)_0%,rgba(255,255,255,0.96)_100%)] p-5">
                                <div class="flex items-start gap-3">
                                    <span class="mt-0.5 flex h-11 w-11 items-center justify-center rounded-2xl bg-indigo-700 text-white shadow-[0_16px_36px_rgba(79,70,229,0.2)]">
                                        <Target class="size-4.5" />
                                    </span>
                                    <div>
                                        <p class="text-[0.68rem] font-semibold uppercase tracking-[0.2em] text-indigo-700">Hasil simulasi</p>
                                        <h3 class="mt-2 text-2xl font-black tracking-tight text-slate-950 [font-family:'Space Grotesk',sans-serif]">
                                            {{ winRateSummary.title }}
                                        </h3>
                                        <p class="mt-3 text-sm leading-7 text-slate-600">
                                            {{ winRateSummary.description }}
                                        </p>
                                    </div>
                                </div>

                                <div v-if="winRateSummary.valid && !winRateSummary.impossible" class="mt-5 grid gap-4 sm:grid-cols-2">
                                    <article class="rounded-[22px] border border-white/80 bg-white/88 p-4 shadow-[0_16px_28px_rgba(15,23,42,0.04)]">
                                        <p class="text-[0.68rem] font-semibold uppercase tracking-[0.2em] text-slate-500">Win tambahan</p>
                                        <p class="mt-3 text-3xl font-black text-slate-950 [font-family:'Space Grotesk',sans-serif]">
                                            {{ formatNumber(winRateSummary.winsNeeded) }}
                                        </p>
                                    </article>

                                    <article class="rounded-[22px] border border-white/80 bg-white/88 p-4 shadow-[0_16px_28px_rgba(15,23,42,0.04)]">
                                        <p class="text-[0.68rem] font-semibold uppercase tracking-[0.2em] text-slate-500">Target yang dikejar</p>
                                        <p class="mt-3 text-3xl font-black text-slate-950 [font-family:'Space Grotesk',sans-serif]">
                                            {{ formatNumber(parsePositiveNumber(targetWinRate) || 0, 1) }}%
                                        </p>
                                    </article>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section
                    id="magic-wheel-mobile-legends"
                    class="relative overflow-hidden rounded-[36px] border border-white/85 bg-white/84 p-6 shadow-[0_26px_72px_rgba(79,70,229,0.08)] sm:p-8 lg:p-10"
                >
                    <div class="pointer-events-none absolute inset-x-0 top-0 h-32 bg-[radial-gradient(circle_at_top_left,rgba(249,115,22,0.16),transparent_60%)]" />

                    <div class="relative z-10 grid gap-8 lg:grid-cols-[0.95fr,1.05fr] lg:gap-10">
                        <div>
                            <div class="inline-flex items-center gap-3 rounded-full border border-amber-100 bg-amber-50/80 px-4 py-2 text-xs font-bold uppercase tracking-[0.16em] text-amber-700">
                                <Sparkles class="size-3.5" />
                                Magic Wheel ML
                            </div>

                            <h2 class="mt-5 text-3xl font-black tracking-tight text-slate-950 [font-family:'Space Grotesk',sans-serif] sm:text-[2.2rem]">
                                Simulasi budget spin untuk event Magic Wheel.
                            </h2>
                            <p class="mt-4 max-w-2xl text-sm leading-7 text-slate-600 sm:text-base">
                                Isi jumlah spin yang ingin kamu lakukan, lalu tentukan berapa spin promo dan harga per spin. Cocok buat hitung budget sebelum kamu top up diamond.
                            </p>

                            <div class="mt-7 grid gap-4 sm:grid-cols-2">
                                <article class="rounded-[24px] border border-slate-200/80 bg-slate-50/90 p-4">
                                    <p class="text-[0.68rem] font-semibold uppercase tracking-[0.2em] text-slate-500">Spin promo terpakai</p>
                                    <p class="mt-3 text-2xl font-black text-slate-950 [font-family:'Space Grotesk',sans-serif]">
                                        {{ formatNumber(magicWheelSummary.promoAttempts) }}
                                    </p>
                                    <p class="mt-2 text-sm text-slate-600">Spin diskon yang benar-benar kepakai dari target kamu.</p>
                                </article>

                                <article class="rounded-[24px] border border-slate-200/80 bg-slate-50/90 p-4">
                                    <p class="text-[0.68rem] font-semibold uppercase tracking-[0.2em] text-slate-500">Spin normal</p>
                                    <p class="mt-3 text-2xl font-black text-slate-950 [font-family:'Space Grotesk',sans-serif]">
                                        {{ formatNumber(magicWheelSummary.normalAttempts) }}
                                    </p>
                                    <p class="mt-2 text-sm text-slate-600">Sisa spin setelah kuota diskon habis dipakai.</p>
                                </article>
                            </div>
                        </div>

                        <div class="rounded-[30px] border border-white/80 bg-[linear-gradient(180deg,rgba(255,255,255,0.96)_0%,rgba(255,248,240,0.98)_100%)] p-5 shadow-[0_22px_48px_rgba(15,23,42,0.06)] sm:p-6">
                            <div class="grid gap-5 sm:grid-cols-2">
                                <div class="space-y-2">
                                    <Label for="magic-wheel-attempts" class="text-sm font-semibold text-slate-700">Target total spin</Label>
                                    <Input
                                        id="magic-wheel-attempts"
                                        v-model="magicWheelAttempts"
                                        type="number"
                                        min="1"
                                        step="1"
                                        class="h-12 rounded-2xl border-slate-200 bg-white px-4 text-sm"
                                        placeholder="Contoh: 200"
                                    />
                                </div>

                                <div class="space-y-2">
                                    <Label for="magic-wheel-promo-attempts" class="text-sm font-semibold text-slate-700">Jumlah spin promo</Label>
                                    <Input
                                        id="magic-wheel-promo-attempts"
                                        v-model="magicWheelPromoAttempts"
                                        type="number"
                                        min="0"
                                        step="1"
                                        class="h-12 rounded-2xl border-slate-200 bg-white px-4 text-sm"
                                        placeholder="Contoh: 5"
                                    />
                                </div>

                                <div class="space-y-2">
                                    <Label for="magic-wheel-promo-cost" class="text-sm font-semibold text-slate-700">Harga spin promo</Label>
                                    <Input
                                        id="magic-wheel-promo-cost"
                                        v-model="magicWheelPromoCost"
                                        type="number"
                                        min="0"
                                        step="1"
                                        class="h-12 rounded-2xl border-slate-200 bg-white px-4 text-sm"
                                        placeholder="Contoh: 30"
                                    />
                                </div>

                                <div class="space-y-2">
                                    <Label for="magic-wheel-normal-cost" class="text-sm font-semibold text-slate-700">Harga spin normal</Label>
                                    <Input
                                        id="magic-wheel-normal-cost"
                                        v-model="magicWheelNormalCost"
                                        type="number"
                                        min="0"
                                        step="1"
                                        class="h-12 rounded-2xl border-slate-200 bg-white px-4 text-sm"
                                        placeholder="Contoh: 60"
                                    />
                                </div>
                            </div>

                            <div class="mt-6 rounded-[26px] border border-amber-100 bg-[linear-gradient(135deg,rgba(251,191,36,0.12)_0%,rgba(255,255,255,0.96)_100%)] p-5">
                                <div class="flex items-start gap-3">
                                    <span class="mt-0.5 flex h-11 w-11 items-center justify-center rounded-2xl bg-[linear-gradient(145deg,#f59e0b_0%,#f97316_100%)] text-white shadow-[0_16px_36px_rgba(249,115,22,0.18)]">
                                        <Coins class="size-4.5" />
                                    </span>
                                    <div>
                                        <p class="text-[0.68rem] font-semibold uppercase tracking-[0.2em] text-amber-700">Hasil simulasi</p>
                                        <h3 class="mt-2 text-2xl font-black tracking-tight text-slate-950 [font-family:'Space Grotesk',sans-serif]">
                                            {{
                                                magicWheelSummary.valid
                                                    ? `${formatNumber(magicWheelSummary.totalDiamonds)} diamond`
                                                    : 'Isi semua angka dengan benar'
                                            }}
                                        </h3>
                                        <p class="mt-3 text-sm leading-7 text-slate-600">
                                            {{
                                                magicWheelSummary.valid
                                                    ? `Rata-rata biaya spin kamu sekitar ${formatNumber(magicWheelSummary.averageCost, 1)} diamond per spin.`
                                                    : 'Gunakan angka positif supaya estimasi kebutuhan spin bisa dihitung.'
                                            }}
                                        </p>
                                    </div>
                                </div>

                                <div v-if="magicWheelSummary.valid" class="mt-5 grid gap-4 sm:grid-cols-3">
                                    <article class="rounded-[22px] border border-white/80 bg-white/88 p-4 shadow-[0_16px_28px_rgba(15,23,42,0.04)]">
                                        <p class="text-[0.68rem] font-semibold uppercase tracking-[0.2em] text-slate-500">Total spin</p>
                                        <p class="mt-3 text-2xl font-black text-slate-950 [font-family:'Space Grotesk',sans-serif]">
                                            {{ formatNumber(magicWheelSummary.totalAttempts) }}
                                        </p>
                                    </article>

                                    <article class="rounded-[22px] border border-white/80 bg-white/88 p-4 shadow-[0_16px_28px_rgba(15,23,42,0.04)]">
                                        <p class="text-[0.68rem] font-semibold uppercase tracking-[0.2em] text-slate-500">Spin promo</p>
                                        <p class="mt-3 text-2xl font-black text-slate-950 [font-family:'Space Grotesk',sans-serif]">
                                            {{ formatNumber(magicWheelSummary.promoAttempts) }}
                                        </p>
                                    </article>

                                    <article class="rounded-[22px] border border-white/80 bg-white/88 p-4 shadow-[0_16px_28px_rgba(15,23,42,0.04)]">
                                        <p class="text-[0.68rem] font-semibold uppercase tracking-[0.2em] text-slate-500">Spin normal</p>
                                        <p class="mt-3 text-2xl font-black text-slate-950 [font-family:'Space Grotesk',sans-serif]">
                                            {{ formatNumber(magicWheelSummary.normalAttempts) }}
                                        </p>
                                    </article>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section
                    id="zodiac-mobile-legends"
                    class="relative overflow-hidden rounded-[36px] border border-white/85 bg-white/84 p-6 shadow-[0_26px_72px_rgba(79,70,229,0.08)] sm:p-8 lg:p-10"
                >
                    <div class="pointer-events-none absolute inset-x-0 top-0 h-32 bg-[radial-gradient(circle_at_top_left,rgba(14,165,233,0.16),transparent_60%)]" />

                    <div class="relative z-10 grid gap-8 lg:grid-cols-[0.95fr,1.05fr] lg:gap-10">
                        <div>
                            <div class="inline-flex items-center gap-3 rounded-full border border-sky-100 bg-sky-50/80 px-4 py-2 text-xs font-bold uppercase tracking-[0.16em] text-sky-700">
                                <Star class="size-3.5" />
                                Zodiac ML
                            </div>

                            <h2 class="mt-5 text-3xl font-black tracking-tight text-slate-950 [font-family:'Space Grotesk',sans-serif] sm:text-[2.2rem]">
                                Estimasi budget draw untuk Zodiac summon.
                            </h2>
                            <p class="mt-4 max-w-2xl text-sm leading-7 text-slate-600 sm:text-base">
                                Ubah jumlah draw, harga promo, dan harga normal sesuai periode event yang sedang kamu incar. Hasilnya membantu kamu lihat kebutuhan diamond total dengan cepat.
                            </p>

                            <div class="mt-7 grid gap-4 sm:grid-cols-2">
                                <article class="rounded-[24px] border border-slate-200/80 bg-slate-50/90 p-4">
                                    <p class="text-[0.68rem] font-semibold uppercase tracking-[0.2em] text-slate-500">Draw promo terpakai</p>
                                    <p class="mt-3 text-2xl font-black text-slate-950 [font-family:'Space Grotesk',sans-serif]">
                                        {{ formatNumber(zodiacSummary.promoAttempts) }}
                                    </p>
                                    <p class="mt-2 text-sm text-slate-600">Kuota draw diskon yang masuk ke simulasi kamu.</p>
                                </article>

                                <article class="rounded-[24px] border border-slate-200/80 bg-slate-50/90 p-4">
                                    <p class="text-[0.68rem] font-semibold uppercase tracking-[0.2em] text-slate-500">Draw normal</p>
                                    <p class="mt-3 text-2xl font-black text-slate-950 [font-family:'Space Grotesk',sans-serif]">
                                        {{ formatNumber(zodiacSummary.normalAttempts) }}
                                    </p>
                                    <p class="mt-2 text-sm text-slate-600">Draw reguler setelah semua kuota promo dipakai.</p>
                                </article>
                            </div>
                        </div>

                        <div class="rounded-[30px] border border-white/80 bg-[linear-gradient(180deg,rgba(255,255,255,0.96)_0%,rgba(240,249,255,0.98)_100%)] p-5 shadow-[0_22px_48px_rgba(15,23,42,0.06)] sm:p-6">
                            <div class="grid gap-5 sm:grid-cols-2">
                                <div class="space-y-2">
                                    <Label for="zodiac-attempts" class="text-sm font-semibold text-slate-700">Target total draw</Label>
                                    <Input
                                        id="zodiac-attempts"
                                        v-model="zodiacAttempts"
                                        type="number"
                                        min="1"
                                        step="1"
                                        class="h-12 rounded-2xl border-slate-200 bg-white px-4 text-sm"
                                        placeholder="Contoh: 100"
                                    />
                                </div>

                                <div class="space-y-2">
                                    <Label for="zodiac-promo-attempts" class="text-sm font-semibold text-slate-700">Jumlah draw promo</Label>
                                    <Input
                                        id="zodiac-promo-attempts"
                                        v-model="zodiacPromoAttempts"
                                        type="number"
                                        min="0"
                                        step="1"
                                        class="h-12 rounded-2xl border-slate-200 bg-white px-4 text-sm"
                                        placeholder="Contoh: 10"
                                    />
                                </div>

                                <div class="space-y-2">
                                    <Label for="zodiac-promo-cost" class="text-sm font-semibold text-slate-700">Harga draw promo</Label>
                                    <Input
                                        id="zodiac-promo-cost"
                                        v-model="zodiacPromoCost"
                                        type="number"
                                        min="0"
                                        step="1"
                                        class="h-12 rounded-2xl border-slate-200 bg-white px-4 text-sm"
                                        placeholder="Contoh: 25"
                                    />
                                </div>

                                <div class="space-y-2">
                                    <Label for="zodiac-normal-cost" class="text-sm font-semibold text-slate-700">Harga draw normal</Label>
                                    <Input
                                        id="zodiac-normal-cost"
                                        v-model="zodiacNormalCost"
                                        type="number"
                                        min="0"
                                        step="1"
                                        class="h-12 rounded-2xl border-slate-200 bg-white px-4 text-sm"
                                        placeholder="Contoh: 60"
                                    />
                                </div>
                            </div>

                            <div class="mt-6 rounded-[26px] border border-sky-100 bg-[linear-gradient(135deg,rgba(14,165,233,0.12)_0%,rgba(255,255,255,0.96)_100%)] p-5">
                                <div class="flex items-start gap-3">
                                    <span class="mt-0.5 flex h-11 w-11 items-center justify-center rounded-2xl bg-[linear-gradient(145deg,#0ea5e9_0%,#2563eb_100%)] text-white shadow-[0_16px_36px_rgba(37,99,235,0.18)]">
                                        <Star class="size-4.5" />
                                    </span>
                                    <div>
                                        <p class="text-[0.68rem] font-semibold uppercase tracking-[0.2em] text-sky-700">Hasil simulasi</p>
                                        <h3 class="mt-2 text-2xl font-black tracking-tight text-slate-950 [font-family:'Space Grotesk',sans-serif]">
                                            {{
                                                zodiacSummary.valid
                                                    ? `${formatNumber(zodiacSummary.totalDiamonds)} diamond`
                                                    : 'Isi semua angka dengan benar'
                                            }}
                                        </h3>
                                        <p class="mt-3 text-sm leading-7 text-slate-600">
                                            {{
                                                zodiacSummary.valid
                                                    ? `Rata-rata biaya draw kamu sekitar ${formatNumber(zodiacSummary.averageCost, 1)} diamond per draw.`
                                                    : 'Gunakan angka positif supaya estimasi kebutuhan draw bisa dihitung.'
                                            }}
                                        </p>
                                    </div>
                                </div>

                                <div v-if="zodiacSummary.valid" class="mt-5 grid gap-4 sm:grid-cols-3">
                                    <article class="rounded-[22px] border border-white/80 bg-white/88 p-4 shadow-[0_16px_28px_rgba(15,23,42,0.04)]">
                                        <p class="text-[0.68rem] font-semibold uppercase tracking-[0.2em] text-slate-500">Total draw</p>
                                        <p class="mt-3 text-2xl font-black text-slate-950 [font-family:'Space Grotesk',sans-serif]">
                                            {{ formatNumber(zodiacSummary.totalAttempts) }}
                                        </p>
                                    </article>

                                    <article class="rounded-[22px] border border-white/80 bg-white/88 p-4 shadow-[0_16px_28px_rgba(15,23,42,0.04)]">
                                        <p class="text-[0.68rem] font-semibold uppercase tracking-[0.2em] text-slate-500">Draw promo</p>
                                        <p class="mt-3 text-2xl font-black text-slate-950 [font-family:'Space Grotesk',sans-serif]">
                                            {{ formatNumber(zodiacSummary.promoAttempts) }}
                                        </p>
                                    </article>

                                    <article class="rounded-[22px] border border-white/80 bg-white/88 p-4 shadow-[0_16px_28px_rgba(15,23,42,0.04)]">
                                        <p class="text-[0.68rem] font-semibold uppercase tracking-[0.2em] text-slate-500">Draw normal</p>
                                        <p class="mt-3 text-2xl font-black text-slate-950 [font-family:'Space Grotesk',sans-serif]">
                                            {{ formatNumber(zodiacSummary.normalAttempts) }}
                                        </p>
                                    </article>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="rounded-[34px] border border-white/85 bg-[linear-gradient(145deg,rgba(15,23,42,0.96)_0%,rgba(30,41,59,0.94)_55%,rgba(67,56,202,0.92)_100%)] p-6 text-white shadow-[0_28px_90px_rgba(15,23,42,0.22)] sm:p-8">
                    <div class="grid gap-6 lg:grid-cols-[1.05fr,0.95fr] lg:items-center">
                        <div>
                            <p class="text-[0.72rem] font-semibold uppercase tracking-[0.26em] text-white/70">Catatan cepat</p>
                            <h2 class="mt-4 text-3xl font-black tracking-tight [font-family:'Space Grotesk',sans-serif] sm:text-[2.2rem]">
                                Ubah angka sesuai event yang sedang aktif.
                            </h2>
                            <p class="mt-4 max-w-2xl text-sm leading-7 text-white/78 sm:text-base">
                                Harga spin promo, harga normal, dan jumlah draw tiap event bisa berubah. Kalkulator ini sengaja fleksibel supaya kamu bisa pakai untuk banyak skenario tanpa harus nunggu update manual.
                            </p>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-3">
                            <article class="rounded-[26px] border border-white/12 bg-white/8 p-4 backdrop-blur-sm">
                                <Target class="size-5 text-emerald-300" />
                                <p class="mt-4 text-sm font-black uppercase tracking-[0.08em] [font-family:'Space Grotesk',sans-serif]">Win Rate</p>
                                <p class="mt-2 text-sm leading-6 text-white/72">Bantu tentukan grind match yang realistis.</p>
                            </article>

                            <article class="rounded-[26px] border border-white/12 bg-white/8 p-4 backdrop-blur-sm">
                                <Sparkles class="size-5 text-amber-300" />
                                <p class="mt-4 text-sm font-black uppercase tracking-[0.08em] [font-family:'Space Grotesk',sans-serif]">Magic Wheel</p>
                                <p class="mt-2 text-sm leading-6 text-white/72">Cek budget diamond sebelum mulai spin.</p>
                            </article>

                            <article class="rounded-[26px] border border-white/12 bg-white/8 p-4 backdrop-blur-sm">
                                <Star class="size-5 text-sky-300" />
                                <p class="mt-4 text-sm font-black uppercase tracking-[0.08em] [font-family:'Space Grotesk',sans-serif]">Zodiac</p>
                                <p class="mt-2 text-sm leading-6 text-white/72">Bandingkan draw promo dan draw normal dengan cepat.</p>
                            </article>
                        </div>
                    </div>
                </section>
            </div>
        </main>
    </PublicLayout>
</template>
