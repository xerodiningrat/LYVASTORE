<script setup lang="ts">
import AdminDashboardInsights from '@/components/admin/AdminDashboardInsights.vue';
import AdminDashboardOverview from '@/components/admin/AdminDashboardOverview.vue';
import AdminDashboardRecentTransactions from '@/components/admin/AdminDashboardRecentTransactions.vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Activity, AlertTriangle, ArrowUpRight, BadgeDollarSign, Boxes, CircleAlert, ShieldAlert, Sparkles, TrendingUp, Wallet } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    stats: Record<string, number>;
    performanceSeries: Array<Record<string, string | number>>;
    actionQueues: Array<Record<string, unknown>>;
    topProducts: Array<Record<string, string | number>>;
    stockAlerts: Array<Record<string, string | number>>;
    recentTransactions: Array<Record<string, string | number>>;
    branding: {
        title: string;
        tagline: string;
        logoUrl: string | null;
    };
    securitySnapshot: {
        warningEntries: number;
        criticalEntries: number;
        uniqueIps: number;
        latestTimestampLabel: string | null;
        topEvent: string | null;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard Admin', href: '/dashboard' }];

const number = new Intl.NumberFormat('id-ID');
const currency = new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
});
const compact = new Intl.NumberFormat('id-ID', {
    notation: 'compact',
    maximumFractionDigits: 1,
});

const formatCurrency = (value: number) => currency.format(Math.max(0, Math.round(value)));
const formatCompact = (value: number) => compact.format(Math.max(0, Math.round(value)));
const formatPercent = (value: number) => `${Number.isInteger(value) ? value : value.toFixed(1)}%`;

const pulseCards = computed(() => [
    {
        label: 'Omzet kotor',
        value: formatCurrency(Number(props.stats.grossRevenue ?? 0)),
        note: 'Akumulasi semua pembayaran yang sudah masuk.',
        icon: Wallet,
        tone: 'from-sky-500/18 via-sky-500/6 to-transparent',
        valueClass: 'text-[clamp(1.25rem,2vw,2.15rem)] leading-[0.96] tracking-[-0.07em]',
    },
    {
        label: 'Order aktif',
        value: number.format(Number(props.stats.pendingTransactions ?? 0)),
        note: 'Pending dan processing yang masih hidup.',
        icon: Activity,
        tone: 'from-amber-500/16 via-amber-500/6 to-transparent',
        valueClass: 'text-[clamp(2rem,2.7vw,2.35rem)] leading-none tracking-[-0.06em]',
    },
    {
        label: 'Menunggu stok',
        value: number.format(Number(props.stats.waitingManualOrders ?? 0)),
        note: 'Pesanan manual yang perlu isi data akun.',
        icon: Boxes,
        tone: 'from-rose-500/16 via-rose-500/6 to-transparent',
        valueClass: 'text-[clamp(2rem,2.7vw,2.35rem)] leading-none tracking-[-0.06em]',
    },
    {
        label: 'Alert stok',
        value: number.format(Number(props.stats.lowStockPackages ?? 0)),
        note: 'Paket yang mulai tipis atau kosong.',
        icon: CircleAlert,
        tone: 'from-violet-500/16 via-violet-500/6 to-transparent',
        valueClass: 'text-[clamp(2rem,2.7vw,2.35rem)] leading-none tracking-[-0.06em]',
    },
    {
        label: 'Provider kosong',
        value: number.format(Number(props.stats.unavailableProviderProducts ?? 0)),
        note: 'Produk VIP-backed yang sedang kosong dari provider.',
        icon: AlertTriangle,
        tone: 'from-amber-500/16 via-amber-500/6 to-transparent',
        valueClass: 'text-[clamp(2rem,2.7vw,2.35rem)] leading-none tracking-[-0.06em]',
    },
]);

const financeCards = computed(() => [
    {
        label: 'Estimasi modal',
        value: formatCurrency(Number(props.stats.estimatedCostBasis ?? 0)),
        note: 'Perkiraan total modal dari seluruh order yang sudah dibayar.',
        icon: Wallet,
        tone: 'from-slate-500/12 via-slate-500/5 to-transparent',
    },
    {
        label: 'Estimasi profit kotor',
        value: formatCurrency(Number(props.stats.estimatedGrossProfit ?? 0)),
        note: 'Estimasi keuntungan otomatis berdasarkan margin tier aktif.',
        icon: BadgeDollarSign,
        tone: 'from-emerald-500/18 via-emerald-500/6 to-transparent',
    },
    {
        label: 'Margin estimasi',
        value: formatPercent(Number(props.stats.estimatedMarginPercent ?? 0)),
        note: 'Persentase estimasi profit dibanding omzet dibayar.',
        icon: TrendingUp,
        tone: 'from-indigo-500/18 via-indigo-500/6 to-transparent',
    },
    {
        label: 'Profit bulan ini',
        value: formatCurrency(Number(props.stats.estimatedProfitThisMonth ?? 0)),
        note: 'Estimasi profit kotor dari order dibayar selama bulan berjalan.',
        icon: Activity,
        tone: 'from-amber-500/18 via-amber-500/6 to-transparent',
    },
]);

const queueMarquee = computed(() =>
    props.actionQueues.flatMap((queue) => {
        const record = queue as Record<string, unknown>;

        return [`${String(record.title ?? 'Antrian')} • ${number.format(Number(record.count ?? 0))} item`];
    }),
);

const performancePoints = computed(() =>
    props.performanceSeries.map((entry) => ({
        label: String(entry.label ?? ''),
        fullLabel: String(entry.fullLabel ?? ''),
        paidRevenue: Number(entry.paidRevenue ?? 0),
        completedOrders: Number(entry.completedOrders ?? 0),
    })),
);

const maxRevenue = computed(() => Math.max(1, ...performancePoints.value.map((point) => point.paidRevenue)));

const sparklinePoints = computed(() => {
    if (performancePoints.value.length === 0) {
        return [];
    }

    const step = performancePoints.value.length === 1 ? 0 : 92 / (performancePoints.value.length - 1);

    return performancePoints.value.map((point, index) => ({
        ...point,
        x: 4 + step * index,
        y: 46 - (point.paidRevenue / maxRevenue.value) * 30,
    }));
});

const sparklinePath = computed(() =>
    sparklinePoints.value.map((point, index) => `${index === 0 ? 'M' : 'L'} ${point.x.toFixed(2)} ${point.y.toFixed(2)}`).join(' '),
);

const bestRevenueDay = computed(() =>
    performancePoints.value.reduce(
        (best, point) => (point.paidRevenue > best.paidRevenue ? point : best),
        performancePoints.value[0] ?? { label: '-', fullLabel: '-', paidRevenue: 0, completedOrders: 0 },
    ),
);

const bestCompletionDay = computed(() =>
    performancePoints.value.reduce(
        (best, point) => (point.completedOrders > best.completedOrders ? point : best),
        performancePoints.value[0] ?? { label: '-', fullLabel: '-', paidRevenue: 0, completedOrders: 0 },
    ),
);
</script>

<template>
    <Head title="Dashboard Admin" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-5 p-4 sm:p-5">
            <div class="grid gap-4 xl:grid-cols-[minmax(0,1.08fr)_minmax(360px,0.92fr)]">
                <div class="space-y-4">
                    <section class="admin-dashboard-hero relative h-fit overflow-hidden rounded-[34px] border border-white/80 bg-white/86 p-5 shadow-[0_30px_80px_rgba(15,23,42,0.08)] backdrop-blur-xl sm:p-6">
                        <div class="admin-dashboard-hero__mesh admin-dashboard-hero__mesh--one"></div>
                        <div class="admin-dashboard-hero__mesh admin-dashboard-hero__mesh--two"></div>
                        <span class="admin-dashboard-hero__particle" style="left: 10%; top: 18%"></span>
                        <span class="admin-dashboard-hero__particle" style="left: 29%; top: 76%; animation-delay: 1.2s"></span>
                        <span class="admin-dashboard-hero__particle" style="left: 54%; top: 14%; animation-delay: 2.1s"></span>
                        <span class="admin-dashboard-hero__particle" style="left: 84%; top: 22%; animation-delay: 1.5s"></span>

                        <div class="relative space-y-5">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex items-center gap-2 rounded-full border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-[0.68rem] font-black uppercase tracking-[0.24em] text-indigo-700">
                                    <Sparkles class="size-3.5 text-fuchsia-500" />
                                    Beranda dashboard
                                </span>
                                <span class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-[0.68rem] font-bold text-emerald-700">
                                    <span class="admin-dashboard-hero__live-dot"></span>
                                    Live control panel
                                </span>
                            </div>

                            <div class="max-w-3xl">
                                <h1 class="text-[2rem] font-black leading-[0.95] tracking-[-0.05em] text-slate-950 sm:text-[2.55rem]">
                                    Ringkasan admin
                                    <span class="bg-gradient-to-r from-indigo-700 via-fuchsia-500 to-sky-500 bg-clip-text text-transparent">
                                        cepat dipantau.
                                    </span>
                                </h1>
                                <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-600 sm:text-[0.96rem]">
                                    Pantau omzet, antrean prioritas, performa 7 hari, dan akses cepat ke panel operasional dari satu dashboard.
                                </p>
                            </div>

                            <div class="grid gap-3 sm:grid-cols-3">
                                <div class="flex min-h-[96px] flex-col justify-between rounded-[22px] border border-slate-200/80 bg-white/84 px-4 py-3.5 shadow-[0_14px_28px_rgba(15,23,42,0.04)]">
                                    <p class="text-[0.62rem] font-black uppercase tracking-[0.18em] text-slate-500">Total transaksi</p>
                                    <p class="mt-3 text-[1.35rem] font-black leading-none tracking-[-0.05em] text-slate-950 tabular-nums">{{ number.format(Number(stats.totalTransactions ?? 0)) }}</p>
                                </div>
                                <div class="flex min-h-[96px] flex-col justify-between rounded-[22px] border border-slate-200/80 bg-white/84 px-4 py-3.5 shadow-[0_14px_28px_rgba(15,23,42,0.04)]">
                                    <p class="text-[0.62rem] font-black uppercase tracking-[0.18em] text-slate-500">Produk override</p>
                                    <p class="mt-3 text-[1.35rem] font-black leading-none tracking-[-0.05em] text-slate-950 tabular-nums">{{ number.format(Number(stats.productOverrideCount ?? 0)) }}</p>
                                </div>
                                <div class="flex min-h-[96px] flex-col justify-between rounded-[22px] border border-slate-200/80 bg-white/84 px-4 py-3.5 shadow-[0_14px_28px_rgba(15,23,42,0.04)]">
                                    <p class="text-[0.62rem] font-black uppercase tracking-[0.18em] text-slate-500">Tier margin</p>
                                    <p class="mt-3 text-[1.35rem] font-black leading-none tracking-[-0.05em] text-slate-950 tabular-nums">{{ number.format(Number(stats.marginTierCount ?? 0)) }}</p>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="grid gap-4 sm:grid-cols-2">
                        <article
                            v-for="card in pulseCards"
                            :key="card.label"
                            class="relative overflow-hidden rounded-[26px] border border-white/80 bg-white/82 p-5 shadow-[0_18px_36px_rgba(15,23,42,0.05)]"
                        >
                            <div class="absolute inset-0 bg-gradient-to-br" :class="card.tone"></div>
                            <div class="relative flex min-h-[196px] flex-col">
                                <div class="flex items-start justify-between gap-4">
                                    <p class="pr-2 text-[0.65rem] font-black uppercase tracking-[0.22em] text-slate-500">{{ card.label }}</p>
                                    <span class="inline-flex size-11 min-h-11 min-w-11 shrink-0 items-center justify-center rounded-[18px] bg-slate-950 text-white shadow-[0_12px_24px_rgba(15,23,42,0.12)]">
                                        <component :is="card.icon" class="size-5 shrink-0" />
                                    </span>
                                </div>

                                <div class="mt-5 min-w-0 flex-1">
                                    <p
                                        :class="[
                                            'max-w-full font-black text-slate-950 tabular-nums break-words',
                                            card.valueClass,
                                        ]"
                                    >
                                        {{ card.value }}
                                    </p>
                                    <p class="mt-4 max-w-[30ch] text-[0.98rem] leading-8 text-slate-600">
                                        {{ card.note }}
                                    </p>
                                </div>
                            </div>
                        </article>
                    </section>

                    <section class="rounded-[28px] border border-white/80 bg-white/84 p-5 shadow-[0_20px_42px_rgba(15,23,42,0.05)]">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="text-[0.65rem] font-black uppercase tracking-[0.22em] text-emerald-600">Keuangan otomatis</p>
                                <h2 class="mt-2 text-2xl font-black tracking-[-0.04em] text-slate-950">Omzet, modal, dan profit langsung kebaca</h2>
                                <p class="mt-2 max-w-2xl text-sm leading-7 text-slate-600">
                                    Angka di bawah ini dihitung otomatis dari order yang sudah dibayar. Nilai modal dan profit ditampilkan sebagai estimasi berdasarkan margin tier aktif.
                                </p>
                            </div>
                            <span class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-700">
                                {{ number.format(Number(stats.paidOrderCount ?? 0)) }} order dibayar
                            </span>
                        </div>

                        <div class="mt-5 grid gap-3 lg:grid-cols-4">
                            <article
                                v-for="card in financeCards"
                                :key="card.label"
                                class="relative overflow-hidden rounded-[24px] border border-slate-200/80 bg-white px-4 py-4 shadow-[0_14px_30px_rgba(15,23,42,0.04)]"
                            >
                                <div class="absolute inset-0 bg-gradient-to-br" :class="card.tone"></div>
                                <div class="relative flex min-h-[152px] flex-col">
                                    <div class="flex items-start justify-between gap-3">
                                        <p class="text-[0.62rem] font-black uppercase tracking-[0.18em] text-slate-500">{{ card.label }}</p>
                                        <span class="inline-flex size-10 items-center justify-center rounded-[16px] bg-slate-950 text-white">
                                            <component :is="card.icon" class="size-4.5" />
                                        </span>
                                    </div>
                                    <p class="mt-5 text-[clamp(1.35rem,1.9vw,2rem)] font-black leading-[1] tracking-[-0.06em] text-slate-950 tabular-nums break-words">
                                        {{ card.value }}
                                    </p>
                                    <p class="mt-4 text-sm leading-6 text-slate-600">
                                        {{ card.note }}
                                    </p>
                                </div>
                            </article>
                        </div>
                    </section>
                </div>

                <div class="space-y-4">
                    <section class="rounded-[30px] border border-slate-200/70 bg-slate-950 p-5 text-white shadow-[0_28px_60px_rgba(15,23,42,0.15)]">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <p class="text-[0.65rem] font-black uppercase tracking-[0.22em] text-white/46">Brand panel</p>
                                <p class="mt-2 text-2xl font-black tracking-[-0.04em]">{{ branding.title }}</p>
                            </div>
                            <div class="flex size-14 items-center justify-center overflow-hidden rounded-[20px] border border-white/12 bg-white/10">
                                <img v-if="branding.logoUrl" :src="branding.logoUrl" :alt="branding.title" class="size-full object-cover" />
                                <img v-else src="/brand/lyva-mascot-mark.png" alt="Lyva" class="size-9 object-contain" />
                            </div>
                        </div>

                        <p class="mt-3 text-sm leading-6 text-white/62">{{ branding.tagline }}</p>

                        <div class="mt-5 rounded-[24px] border border-white/12 bg-white/8 p-4 backdrop-blur-sm">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-[0.62rem] font-black uppercase tracking-[0.18em] text-white/42">Peak omzet</p>
                                    <p class="mt-2 text-lg font-black tracking-[-0.04em] text-white">{{ bestRevenueDay.fullLabel }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-[0.62rem] font-black uppercase tracking-[0.18em] text-white/42">Nilai</p>
                                    <p class="mt-2 text-lg font-black tracking-[-0.04em] text-white">{{ formatCurrency(bestRevenueDay.paidRevenue) }}</p>
                                </div>
                            </div>

                            <div class="mt-4 rounded-[18px] border border-white/10 bg-white/6 p-3">
                                <svg viewBox="0 0 100 52" class="h-28 w-full">
                                    <defs>
                                        <linearGradient id="dashboardHeroSparkline" x1="0%" x2="100%" y1="0%" y2="0%">
                                            <stop offset="0%" stop-color="#60a5fa" />
                                            <stop offset="50%" stop-color="#818cf8" />
                                            <stop offset="100%" stop-color="#f472b6" />
                                        </linearGradient>
                                    </defs>
                                    <g stroke="rgba(255,255,255,0.08)" stroke-width="0.45">
                                        <line x1="2" x2="98" y1="12" y2="12" />
                                        <line x1="2" x2="98" y1="24" y2="24" />
                                        <line x1="2" x2="98" y1="36" y2="36" />
                                        <line x1="2" x2="98" y1="48" y2="48" />
                                    </g>
                                    <path v-if="sparklinePath" :d="sparklinePath" fill="none" stroke="url(#dashboardHeroSparkline)" stroke-linecap="round" stroke-width="2.2" />
                                    <g v-for="point in sparklinePoints" :key="point.fullLabel">
                                        <circle :cx="point.x" :cy="point.y" r="1.8" fill="#fff" stroke="#818cf8" stroke-width="1" />
                                    </g>
                                </svg>
                            </div>
                        </div>

                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                            <article class="rounded-[22px] border border-white/12 bg-white/8 p-4">
                                <p class="text-[0.62rem] font-black uppercase tracking-[0.18em] text-white/42">Hari selesai terbaik</p>
                                <p class="mt-2 text-lg font-black tracking-[-0.04em] text-white">{{ bestCompletionDay.fullLabel }}</p>
                                <p class="mt-1 text-sm text-white/62">{{ number.format(bestCompletionDay.completedOrders) }} order selesai tercatat.</p>
                            </article>
                            <article class="rounded-[22px] border border-white/12 bg-white/8 p-4">
                                <p class="text-[0.62rem] font-black uppercase tracking-[0.18em] text-white/42">Success rate</p>
                                <p class="mt-2 text-lg font-black tracking-[-0.04em] text-white">{{ number.format(Number(stats.successRate ?? 0)) }}%</p>
                                <p class="mt-1 text-sm text-white/62">Rasio order selesai terhadap seluruh order resolved.</p>
                            </article>
                        </div>
                    </section>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <article class="rounded-[24px] border border-white/80 bg-white/84 p-4 shadow-[0_16px_34px_rgba(15,23,42,0.05)]">
                            <p class="text-[0.65rem] font-black uppercase tracking-[0.22em] text-slate-500">Minggu ini</p>
                            <p class="mt-2 text-xl font-black tracking-[-0.04em] text-slate-950">{{ formatCompact(Number(stats.paidThisWeek ?? 0)) }}</p>
                            <p class="mt-2 text-sm text-slate-600">Pergerakan omzet dibayar selama 7 hari terakhir.</p>
                        </article>
                        <article class="rounded-[24px] border border-white/80 bg-white/84 p-4 shadow-[0_16px_34px_rgba(15,23,42,0.05)]">
                            <p class="text-[0.65rem] font-black uppercase tracking-[0.22em] text-slate-500">Rata-rata order</p>
                            <p class="mt-2 text-xl font-black tracking-[-0.04em] text-slate-950">{{ formatCurrency(Number(stats.averageOrderValue ?? 0)) }}</p>
                            <p class="mt-2 text-sm text-slate-600">Nilai rata-rata order selesai yang tercatat saat ini.</p>
                        </article>
                        <article class="rounded-[24px] border border-white/80 bg-white/84 p-4 shadow-[0_16px_34px_rgba(15,23,42,0.05)]">
                            <p class="text-[0.65rem] font-black uppercase tracking-[0.22em] text-slate-500">Profit minggu ini</p>
                            <p class="mt-2 text-xl font-black tracking-[-0.04em] text-slate-950">{{ formatCompact(Number(stats.estimatedProfitThisWeek ?? 0)) }}</p>
                            <p class="mt-2 text-sm text-slate-600">Estimasi profit kotor dari order dibayar selama 7 hari terakhir.</p>
                        </article>
                        <Link href="/dashboard/security" class="group rounded-[24px] border border-rose-200/80 bg-[linear-gradient(180deg,rgba(255,255,255,0.94),rgba(255,241,242,0.94))] p-4 shadow-[0_16px_34px_rgba(15,23,42,0.05)] transition hover:border-rose-300 hover:bg-white">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-[0.65rem] font-black uppercase tracking-[0.22em] text-rose-600">Security</p>
                                    <p class="mt-2 text-xl font-black tracking-[-0.04em] text-slate-950">{{ number.format(Number(securitySnapshot.warningEntries ?? 0)) }}</p>
                                </div>
                                <span class="inline-flex size-11 items-center justify-center rounded-[18px] bg-rose-600 text-white shadow-[0_12px_24px_rgba(244,63,94,0.22)]">
                                    <ShieldAlert class="size-5" />
                                </span>
                            </div>
                            <p class="mt-3 text-sm leading-6 text-slate-600">
                                {{ number.format(Number(securitySnapshot.criticalEntries ?? 0)) }} critical/error, {{ number.format(Number(securitySnapshot.uniqueIps ?? 0)) }} IP unik.
                            </p>
                            <p class="mt-2 text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">
                                {{ securitySnapshot.latestTimestampLabel ?? 'Belum ada log terbaru' }}
                            </p>
                        </Link>
                    </div>
                </div>
            </div>

            <section class="overflow-hidden rounded-[24px] border border-white/75 bg-white/76 shadow-[0_18px_40px_rgba(15,23,42,0.05)] backdrop-blur-xl">
                <div class="admin-dashboard-marquee__track">
                    <div v-for="(item, index) in [...queueMarquee, ...queueMarquee]" :key="`queue-marquee-${index}`" class="admin-dashboard-marquee__item">
                        <span class="admin-dashboard-marquee__dot"></span>
                        <span>{{ item }}</span>
                    </div>
                </div>
            </section>

            <div class="space-y-5">
                <AdminDashboardOverview :stats="stats" :performance-series="performanceSeries" :action-queues="actionQueues" :branding="branding" />
                <AdminDashboardInsights :stats="stats" :top-products="topProducts" :stock-alerts="stockAlerts" />
                <AdminDashboardRecentTransactions :recent-transactions="recentTransactions" />
            </div>

            <section class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_minmax(280px,0.7fr)]">
                <div class="rounded-[28px] border border-white/80 bg-white/82 p-5 shadow-[0_22px_46px_rgba(15,23,42,0.05)] backdrop-blur-xl">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="text-[0.65rem] font-black uppercase tracking-[0.22em] text-indigo-600">Aksi cepat</p>
                            <h2 class="mt-2 text-2xl font-black tracking-[-0.04em] text-slate-950">Shortcut panel yang paling sering dipakai</h2>
                        </div>
                        <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-bold text-slate-600">
                            owner & admin only
                        </span>
                    </div>

                    <div class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                        <Link href="/dashboard/transaksi" class="group rounded-[22px] border border-slate-200/80 bg-slate-50/82 p-4 transition hover:border-slate-300 hover:bg-white">
                            <p class="text-sm font-black text-slate-950">Monitor transaksi</p>
                            <p class="mt-2 text-sm leading-6 text-slate-600">Cek pembayaran, proses manual, dan status order yang lagi gerak.</p>
                            <span class="mt-4 inline-flex items-center gap-2 text-sm font-bold text-indigo-700">
                                Buka panel
                                <ArrowUpRight class="size-4 transition group-hover:translate-x-0.5" />
                            </span>
                        </Link>

                        <Link href="/dashboard/stok-manual" class="group rounded-[22px] border border-slate-200/80 bg-slate-50/82 p-4 transition hover:border-slate-300 hover:bg-white">
                            <p class="text-sm font-black text-slate-950">Stok manual</p>
                            <p class="mt-2 text-sm leading-6 text-slate-600">Isi akun ChatGPT, CapCut, sharing, owner, dan private lebih cepat.</p>
                            <span class="mt-4 inline-flex items-center gap-2 text-sm font-bold text-indigo-700">
                                Kelola stok
                                <ArrowUpRight class="size-4 transition group-hover:translate-x-0.5" />
                            </span>
                        </Link>

                        <Link href="/dashboard/produk" class="group rounded-[22px] border border-slate-200/80 bg-slate-50/82 p-4 transition hover:border-slate-300 hover:bg-white">
                            <p class="text-sm font-black text-slate-950">Setting produk</p>
                            <p class="mt-2 text-sm leading-6 text-slate-600">Atur visual produk, override artwork, dan tampilan katalog utama.</p>
                            <span class="mt-4 inline-flex items-center gap-2 text-sm font-bold text-indigo-700">
                                Ubah produk
                                <ArrowUpRight class="size-4 transition group-hover:translate-x-0.5" />
                            </span>
                        </Link>

                        <Link href="/dashboard/margin" class="group rounded-[22px] border border-slate-200/80 bg-slate-50/82 p-4 transition hover:border-slate-300 hover:bg-white">
                            <p class="text-sm font-black text-slate-950">Setting margin</p>
                            <p class="mt-2 text-sm leading-6 text-slate-600">Jaga harga tetap kompetitif sambil aman untuk server dan profit.</p>
                            <span class="mt-4 inline-flex items-center gap-2 text-sm font-bold text-indigo-700">
                                Atur margin
                                <ArrowUpRight class="size-4 transition group-hover:translate-x-0.5" />
                            </span>
                        </Link>

                        <Link href="/dashboard/keuangan" class="group rounded-[22px] border border-emerald-200/80 bg-emerald-50/70 p-4 transition hover:border-emerald-300 hover:bg-white">
                            <p class="text-sm font-black text-slate-950">Laporan keuangan</p>
                            <p class="mt-2 text-sm leading-6 text-slate-600">Pantau omzet, estimasi modal, profit, dan performa produk per periode.</p>
                            <span class="mt-4 inline-flex items-center gap-2 text-sm font-bold text-emerald-700">
                                Buka keuangan
                                <ArrowUpRight class="size-4 transition group-hover:translate-x-0.5" />
                            </span>
                        </Link>

                        <Link href="/dashboard/security" class="group rounded-[22px] border border-rose-200/80 bg-rose-50/70 p-4 transition hover:border-rose-300 hover:bg-white">
                            <p class="text-sm font-black text-slate-950">Security monitor</p>
                            <p class="mt-2 text-sm leading-6 text-slate-600">Filter event mencurigakan, cek IP aktif, dan unduh log audit dari panel admin.</p>
                            <span class="mt-4 inline-flex items-center gap-2 text-sm font-bold text-rose-700">
                                Buka monitor
                                <ArrowUpRight class="size-4 transition group-hover:translate-x-0.5" />
                            </span>
                        </Link>
                    </div>
                </div>

                <div class="rounded-[28px] border border-slate-200/80 bg-slate-950 p-5 text-white shadow-[0_22px_50px_rgba(15,23,42,0.12)]">
                    <p class="text-[0.65rem] font-black uppercase tracking-[0.22em] text-white/42">Snapshot</p>
                    <h2 class="mt-2 text-2xl font-black tracking-[-0.04em]">Pulse operasional cepat</h2>
                    <div class="mt-5 space-y-3">
                        <div class="rounded-[22px] border border-white/12 bg-white/8 p-4">
                            <p class="text-[0.62rem] font-black uppercase tracking-[0.18em] text-white/42">Hari ini</p>
                            <p class="mt-2 text-lg font-black tracking-[-0.04em]">{{ formatCurrency(Number(stats.paidToday ?? 0)) }}</p>
                            <p class="mt-1 text-sm text-white/62">{{ number.format(Number(stats.completedToday ?? 0)) }} order selesai di hari berjalan.</p>
                        </div>
                        <div class="rounded-[22px] border border-white/12 bg-white/8 p-4">
                            <p class="text-[0.62rem] font-black uppercase tracking-[0.18em] text-white/42">Minggu ini</p>
                            <p class="mt-2 text-lg font-black tracking-[-0.04em]">{{ formatCompact(Number(stats.paidThisWeek ?? 0)) }}</p>
                            <p class="mt-1 text-sm text-white/62">Pergerakan omzet mingguan yang sudah dibayar.</p>
                        </div>
                        <div class="rounded-[22px] border border-white/12 bg-white/8 p-4">
                            <p class="text-[0.62rem] font-black uppercase tracking-[0.18em] text-white/42">Profit kotor</p>
                            <p class="mt-2 text-lg font-black tracking-[-0.04em]">{{ formatCompact(Number(stats.estimatedGrossProfit ?? 0)) }}</p>
                            <p class="mt-1 text-sm text-white/62">Estimasi profit otomatis dari seluruh order dibayar.</p>
                        </div>
                        <div class="rounded-[22px] border border-white/12 bg-white/8 p-4">
                            <p class="text-[0.62rem] font-black uppercase tracking-[0.18em] text-white/42">Bulan ini</p>
                            <p class="mt-2 text-lg font-black tracking-[-0.04em]">{{ formatCompact(Number(stats.paidThisMonth ?? 0)) }}</p>
                            <p class="mt-1 text-sm text-white/62">Cocok buat lihat nafas pertumbuhan bisnis bulan berjalan.</p>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>

<style scoped>
.admin-dashboard-hero__mesh {
    position: absolute;
    border-radius: 999px;
    filter: blur(52px);
    opacity: 0.8;
}

.admin-dashboard-hero__mesh--one {
    left: -4rem;
    top: -3rem;
    width: 16rem;
    height: 16rem;
    background: radial-gradient(circle, rgba(99, 102, 241, 0.22), transparent 70%);
}

.admin-dashboard-hero__mesh--two {
    right: -3rem;
    bottom: -4rem;
    width: 18rem;
    height: 18rem;
    background: radial-gradient(circle, rgba(56, 189, 248, 0.18), transparent 72%);
}

.admin-dashboard-hero__particle {
    position: absolute;
    width: 10px;
    height: 10px;
    border-radius: 999px;
    background:
        radial-gradient(circle at 30% 30%, rgba(255, 255, 255, 1), rgba(255, 255, 255, 0.12) 62%, transparent 75%),
        rgba(79, 70, 229, 0.4);
    box-shadow:
        0 0 0 3px rgba(255, 255, 255, 0.08),
        0 18px 30px rgba(79, 70, 229, 0.16);
    animation: admin-dashboard-float 9s ease-in-out infinite;
}

.admin-dashboard-hero__live-dot {
    width: 8px;
    height: 8px;
    border-radius: 999px;
    background: currentColor;
    box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.38);
    animation: admin-dashboard-pulse 2.6s ease-in-out infinite;
}

.admin-dashboard-marquee__track {
    display: flex;
    width: max-content;
    min-width: 100%;
    gap: 0.85rem;
    padding: 0.85rem 1rem;
    animation: admin-dashboard-marquee 30s linear infinite;
}

.admin-dashboard-marquee__item {
    display: inline-flex;
    align-items: center;
    gap: 0.65rem;
    white-space: nowrap;
    border-radius: 999px;
    border: 1px solid rgba(226, 232, 240, 0.96);
    background: rgba(255, 255, 255, 0.84);
    padding: 0.58rem 0.95rem;
    color: rgb(51 65 85);
    font-size: 0.78rem;
    font-weight: 700;
    box-shadow: 0 14px 26px rgba(15, 23, 42, 0.04);
}

.admin-dashboard-marquee__dot {
    width: 8px;
    height: 8px;
    border-radius: 999px;
    background: linear-gradient(180deg, #fb7185 0%, #8b5cf6 100%);
    box-shadow: 0 0 0 4px rgba(244, 114, 182, 0.08);
}

@keyframes admin-dashboard-float {
    0%,
    100% {
        transform: translate3d(0, 0, 0) scale(1);
    }
    50% {
        transform: translate3d(0, -9px, 0) scale(1.08);
    }
}

@keyframes admin-dashboard-pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.34);
        transform: scale(0.95);
    }
    70% {
        box-shadow: 0 0 0 12px rgba(16, 185, 129, 0);
        transform: scale(1.06);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(16, 185, 129, 0);
        transform: scale(0.96);
    }
}

@keyframes admin-dashboard-marquee {
    0% {
        transform: translateX(0);
    }
    100% {
        transform: translateX(calc(-50% - 0.42rem));
    }
}

@media (prefers-reduced-motion: reduce) {
    .admin-dashboard-hero__particle,
    .admin-dashboard-hero__live-dot,
    .admin-dashboard-marquee__track {
        animation: none !important;
    }
}
</style>
