<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AdminLayout from '@/layouts/AdminLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { BadgeDollarSign, CalendarRange, Package2, ReceiptText, Trash2, TrendingUp, Wallet } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{
    status?: string;
    filters: {
        preset: string;
        startDate: string | null;
        endDate: string | null;
    };
    summary: {
        paidOrderCount: number;
        completedOrderCount: number;
        processingOrderCount: number;
        issueOrderCount: number;
        grossRevenue: number;
        estimatedCostBasis: number;
        estimatedGrossProfit: number;
        manualExpensesTotal: number;
        estimatedNetProfit: number;
        estimatedMarginPercent: number;
        averageOrderValue: number;
        expenseCount: number;
    };
    dailySeries: Array<{
        date: string;
        label: string;
        revenue: number;
        estimatedProfit: number;
        orders: number;
    }>;
    topProducts: Array<{
        productName: string;
        ordersCount: number;
        revenue: number;
        estimatedProfit: number;
        lastOrderLabel: string;
    }>;
    expenses: Array<{
        id: number;
        title: string;
        category: string;
        amount: number;
        expenseDate: string | null;
        expenseDateLabel: string;
        notes: string | null;
        createdBy: string | null;
    }>;
    expenseCategories: Array<{
        category: string;
        amount: number;
        count: number;
    }>;
    monthlySeries: Array<{
        month: string;
        monthLabel: string;
        revenue: number;
        estimatedGrossProfit: number;
        manualExpenses: number;
        estimatedNetProfit: number;
        orders: number;
    }>;
    monthComparison: {
        currentMonth: {
            label: string;
            grossRevenue: number;
            estimatedGrossProfit: number;
            manualExpensesTotal: number;
            estimatedNetProfit: number;
            paidOrderCount: number;
        };
        previousMonth: {
            label: string;
            grossRevenue: number;
            estimatedGrossProfit: number;
            manualExpensesTotal: number;
            estimatedNetProfit: number;
            paidOrderCount: number;
        };
        deltas: {
            grossRevenue: number | null;
            estimatedNetProfit: number | null;
            manualExpensesTotal: number | null;
            paidOrderCount: number | null;
        };
    };
    insights: Array<{
        tone: string;
        title: string;
        body: string;
    }>;
    priorityAlerts: Array<{
        level: string;
        title: string;
        body: string;
    }>;
    alertHistory: Array<{
        id: number;
        level: string;
        title: string;
        body: string;
        alertDateLabel: string;
        firstDetectedLabel: string;
        lastDetectedLabel: string;
        lastNotifiedLabel: string | null;
        seenCount: number;
    }>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard Admin', href: '/dashboard' },
    { title: 'Keuangan', href: '/dashboard/keuangan' },
];

const number = new Intl.NumberFormat('id-ID');
const currency = new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
});

const formatCurrency = (value: number) => currency.format(Math.max(0, Math.round(value)));
const formatSignedCurrency = (value: number) => {
    const roundedValue = Math.round(value);

    return roundedValue < 0 ? `- ${currency.format(Math.abs(roundedValue))}` : currency.format(roundedValue);
};
const formatPercent = (value: number) => `${Number.isInteger(value) ? value : value.toFixed(1)}%`;
const formatDelta = (value: number | null) => {
    if (value === null) {
        return 'Baru';
    }

    if (value === 0) {
        return '0%';
    }

    return `${value > 0 ? '+' : ''}${formatPercent(value)}`;
};
const deltaTone = (value: number | null, invert = false) => {
    if (value === null || value === 0) {
        return 'text-slate-500';
    }

    const isPositive = value > 0;
    const favorable = invert ? !isPositive : isPositive;

    return favorable ? 'text-emerald-600' : 'text-rose-600';
};

const preset = ref(props.filters.preset || 'this-month');
const startDate = ref(props.filters.startDate ?? '');
const endDate = ref(props.filters.endDate ?? '');
const financeBasePath = '/dashboard/keuangan';

const financeQuery = () => {
    const params = new URLSearchParams();

    if (preset.value !== 'custom' && preset.value) {
        params.set('preset', preset.value);
    }

    if (startDate.value) {
        params.set('start_date', startDate.value);
    }

    if (endDate.value) {
        params.set('end_date', endDate.value);
    }

    return params.toString();
};

const expensePath = (expenseId: number) => `${financeBasePath}/biaya/${encodeURIComponent(String(expenseId))}`;

const applyFilters = () => {
    router.get(
        financeBasePath,
        {
            preset: preset.value === 'custom' ? undefined : preset.value,
            start_date: startDate.value || undefined,
            end_date: endDate.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

const exportUrl = computed(() => {
    const query = financeQuery();

    return `${financeBasePath}/export/csv${query ? `?${query}` : ''}`;
});

const usePreset = (value: string) => {
    preset.value = value;

    if (value !== 'custom') {
        startDate.value = '';
        endDate.value = '';
    }

    applyFilters();
};

const expenseForm = useForm({
    title: '',
    category: 'Iklan',
    amount: '',
    expense_date: props.filters.endDate ?? new Date().toISOString().slice(0, 10),
    notes: '',
});

const submitExpense = () => {
    expenseForm.transform((data) => ({
        ...data,
        amount: Number(data.amount || 0),
    })).post(`${financeBasePath}/biaya`, {
        preserveScroll: true,
        onSuccess: () => {
            expenseForm.reset('title', 'amount', 'notes');
            expenseForm.category = 'Iklan';
            expenseForm.expense_date = props.filters.endDate ?? new Date().toISOString().slice(0, 10);
        },
    });
};

const deleteExpense = (expenseId: number) => {
    router.delete(expensePath(expenseId), {
        preserveScroll: true,
    });
};

const summaryCards = computed(() => [
    {
        label: 'Omzet dibayar',
        value: formatCurrency(props.summary.grossRevenue),
        note: `${number.format(props.summary.paidOrderCount)} order dibayar di periode ini.`,
        icon: Wallet,
        tone: 'from-sky-500/18 via-sky-500/6 to-transparent',
    },
    {
        label: 'Estimasi modal',
        value: formatCurrency(props.summary.estimatedCostBasis),
        note: 'Perkiraan total modal berdasarkan margin tier aktif.',
        icon: CalendarRange,
        tone: 'from-slate-500/14 via-slate-500/5 to-transparent',
    },
    {
        label: 'Estimasi profit kotor',
        value: formatCurrency(props.summary.estimatedGrossProfit),
        note: 'Estimasi keuntungan otomatis dari transaksi dibayar.',
        icon: BadgeDollarSign,
        tone: 'from-emerald-500/18 via-emerald-500/6 to-transparent',
    },
    {
        label: 'Biaya manual',
        value: formatCurrency(props.summary.manualExpensesTotal),
        note: `${number.format(props.summary.expenseCount)} pengeluaran tercatat pada periode ini.`,
        icon: ReceiptText,
        tone: 'from-rose-500/18 via-rose-500/6 to-transparent',
    },
    {
        label: 'Profit bersih estimasi',
        value: formatSignedCurrency(props.summary.estimatedNetProfit),
        note: 'Profit kotor dikurangi biaya manual pada periode terpilih.',
        icon: TrendingUp,
        tone: 'from-indigo-500/18 via-indigo-500/6 to-transparent',
    },
]);

const statusCards = computed(() => [
    {
        label: 'Order selesai',
        value: number.format(props.summary.completedOrderCount),
    },
    {
        label: 'Masih diproses',
        value: number.format(props.summary.processingOrderCount),
    },
    {
        label: 'Perlu perhatian',
        value: number.format(props.summary.issueOrderCount),
    },
    {
        label: 'Rata-rata order',
        value: formatCurrency(props.summary.averageOrderValue),
    },
]);

const maxRevenue = computed(() => Math.max(1, ...props.dailySeries.map((item) => item.revenue)));
const maxMonthlyNetProfit = computed(() => Math.max(1, ...props.monthlySeries.map((item) => Math.abs(item.estimatedNetProfit))));
const monthComparisonCards = computed(() => [
    {
        label: 'Omzet bulan ini',
        current: formatCurrency(props.monthComparison.currentMonth.grossRevenue),
        previous: formatCurrency(props.monthComparison.previousMonth.grossRevenue),
        delta: props.monthComparison.deltas.grossRevenue,
        invert: false,
    },
    {
        label: 'Profit bersih bulan ini',
        current: formatSignedCurrency(props.monthComparison.currentMonth.estimatedNetProfit),
        previous: formatSignedCurrency(props.monthComparison.previousMonth.estimatedNetProfit),
        delta: props.monthComparison.deltas.estimatedNetProfit,
        invert: false,
    },
    {
        label: 'Biaya manual bulan ini',
        current: formatCurrency(props.monthComparison.currentMonth.manualExpensesTotal),
        previous: formatCurrency(props.monthComparison.previousMonth.manualExpensesTotal),
        delta: props.monthComparison.deltas.manualExpensesTotal,
        invert: true,
    },
    {
        label: 'Order dibayar bulan ini',
        current: number.format(props.monthComparison.currentMonth.paidOrderCount),
        previous: number.format(props.monthComparison.previousMonth.paidOrderCount),
        delta: props.monthComparison.deltas.paidOrderCount,
        invert: false,
    },
]);
const insightToneClass = (tone: string) =>
    ({
        positive: 'border-emerald-200 bg-emerald-50/80 text-emerald-700',
        warning: 'border-amber-200 bg-amber-50/80 text-amber-700',
        info: 'border-sky-200 bg-sky-50/80 text-sky-700',
        neutral: 'border-slate-200 bg-slate-50/80 text-slate-700',
    })[tone] ?? 'border-slate-200 bg-slate-50/80 text-slate-700';
const priorityAlertClass = (level: string) =>
    ({
        critical: 'border-rose-300 bg-rose-50 text-rose-700',
        warning: 'border-amber-300 bg-amber-50 text-amber-700',
        healthy: 'border-emerald-300 bg-emerald-50 text-emerald-700',
    })[level] ?? 'border-slate-300 bg-slate-50 text-slate-700';
const historyToneClass = (level: string) =>
    ({
        critical: 'border-rose-200 bg-rose-50/70',
        warning: 'border-amber-200 bg-amber-50/70',
        healthy: 'border-emerald-200 bg-emerald-50/70',
    })[level] ?? 'border-slate-200 bg-slate-50/70';
</script>

<template>
    <Head title="Keuangan Admin" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4 sm:p-5">
            <section class="overflow-hidden rounded-[32px] border border-slate-200/80 bg-[radial-gradient(circle_at_top_left,_rgba(16,185,129,0.18),_transparent_32%),linear-gradient(180deg,_rgba(255,255,255,0.98),_rgba(248,250,252,0.98))] p-6 shadow-[0_24px_70px_rgba(15,23,42,0.07)] sm:p-7">
                <div class="flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
                    <div class="max-w-3xl">
                        <div class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.24em] text-emerald-700">
                            Finance Workspace
                        </div>
                        <h1 class="mt-4 text-3xl font-semibold tracking-tight text-slate-950 sm:text-[2.55rem]">
                            Laporan keuangan otomatis dari transaksi yang sudah dibayar
                        </h1>
                        <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-600 sm:text-[15px]">
                            Pantau omzet, estimasi modal, estimasi profit, dan produk paling kuat performanya dari satu halaman. Nilai profit dan modal dihitung otomatis mengikuti margin tier aktif.
                        </p>
                        <div class="mt-5">
                            <a :href="exportUrl" class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:text-slate-950">
                                Export CSV
                            </a>
                        </div>
                    </div>

                    <form class="grid gap-3 rounded-[28px] border border-slate-200/80 bg-white/92 p-4 shadow-[0_18px_38px_rgba(15,23,42,0.05)] sm:grid-cols-2 xl:min-w-[32rem]" @submit.prevent="applyFilters">
                        <label class="space-y-2 text-sm font-semibold text-slate-700">
                            <span>Preset periode</span>
                            <select
                                v-model="preset"
                                class="h-12 w-full rounded-2xl border border-slate-200 bg-white px-4 text-sm text-slate-800 outline-none"
                                @change="preset !== 'custom' ? applyFilters() : undefined"
                            >
                                <option value="today">Hari ini</option>
                                <option value="7d">7 hari</option>
                                <option value="30d">30 hari</option>
                                <option value="this-month">Bulan ini</option>
                                <option value="all">Semua data</option>
                                <option value="custom">Custom tanggal</option>
                            </select>
                        </label>

                        <div class="grid gap-3 sm:grid-cols-2 sm:col-span-2">
                            <label class="space-y-2 text-sm font-semibold text-slate-700">
                                <span>Tanggal mulai</span>
                                <input v-model="startDate" type="date" class="h-12 w-full rounded-2xl border border-slate-200 bg-white px-4 text-sm text-slate-800 outline-none" @change="preset = 'custom'" />
                            </label>
                            <label class="space-y-2 text-sm font-semibold text-slate-700">
                                <span>Tanggal akhir</span>
                                <input v-model="endDate" type="date" class="h-12 w-full rounded-2xl border border-slate-200 bg-white px-4 text-sm text-slate-800 outline-none" @change="preset = 'custom'" />
                            </label>
                        </div>

                        <div class="flex flex-wrap gap-2 sm:col-span-2">
                            <button type="button" class="rounded-full border border-slate-200 bg-slate-50 px-3.5 py-2 text-xs font-bold text-slate-700 transition hover:bg-white" @click="usePreset('today')">Hari ini</button>
                            <button type="button" class="rounded-full border border-slate-200 bg-slate-50 px-3.5 py-2 text-xs font-bold text-slate-700 transition hover:bg-white" @click="usePreset('7d')">7 hari</button>
                            <button type="button" class="rounded-full border border-slate-200 bg-slate-50 px-3.5 py-2 text-xs font-bold text-slate-700 transition hover:bg-white" @click="usePreset('30d')">30 hari</button>
                            <button type="submit" class="rounded-full bg-slate-950 px-4 py-2 text-xs font-bold text-white transition hover:bg-slate-800">Terapkan filter</button>
                        </div>
                    </form>
                </div>
            </section>

            <section class="grid gap-4 lg:grid-cols-4">
                <article
                    v-for="card in summaryCards"
                    :key="card.label"
                    class="relative overflow-hidden rounded-[28px] border border-white/80 bg-white/90 p-5 shadow-[0_18px_36px_rgba(15,23,42,0.05)]"
                >
                    <div class="absolute inset-0 bg-gradient-to-br" :class="card.tone"></div>
                    <div class="relative flex min-h-[180px] flex-col">
                        <div class="flex items-start justify-between gap-3">
                            <p class="text-[0.65rem] font-black uppercase tracking-[0.18em] text-slate-500">{{ card.label }}</p>
                            <span class="inline-flex size-11 items-center justify-center rounded-[18px] bg-slate-950 text-white">
                                <component :is="card.icon" class="size-5" />
                            </span>
                        </div>
                        <p class="mt-6 text-[clamp(1.45rem,2vw,2.15rem)] font-black leading-[0.96] tracking-[-0.07em] text-slate-950 break-words">
                            {{ card.value }}
                        </p>
                        <p class="mt-4 text-sm leading-6 text-slate-600">
                            {{ card.note }}
                        </p>
                    </div>
                </article>
            </section>

            <section class="rounded-[30px] border border-slate-200/80 bg-white p-5 shadow-[0_24px_70px_rgba(15,23,42,0.07)]">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <div class="text-[11px] uppercase tracking-[0.18em] text-slate-400">Alert prioritas</div>
                        <div class="mt-2 text-xl font-semibold tracking-tight text-slate-950">Sinyal yang perlu dilihat duluan</div>
                    </div>
                    <div class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700">
                        {{ number.format(priorityAlerts.length) }} alert
                    </div>
                </div>

                <div class="mt-5 grid gap-4 lg:grid-cols-2">
                    <article v-for="alert in priorityAlerts" :key="`${alert.level}-${alert.title}`" class="rounded-[24px] border px-4 py-4" :class="priorityAlertClass(alert.level)">
                        <div class="text-[11px] font-black uppercase tracking-[0.16em]">{{ alert.level }}</div>
                        <div class="mt-2 text-base font-semibold">{{ alert.title }}</div>
                        <p class="mt-2 text-sm leading-6 opacity-90">{{ alert.body }}</p>
                    </article>
                </div>
            </section>

            <section class="rounded-[30px] border border-slate-200/80 bg-white p-5 shadow-[0_24px_70px_rgba(15,23,42,0.07)]">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <div class="text-[11px] uppercase tracking-[0.18em] text-slate-400">Riwayat alert</div>
                        <div class="mt-2 text-xl font-semibold tracking-tight text-slate-950">Jejak alert keuangan terbaru</div>
                    </div>
                    <div class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700">
                        {{ number.format(alertHistory.length) }} log
                    </div>
                </div>

                <div class="mt-5 space-y-3">
                    <article v-for="item in alertHistory" :key="item.id" class="rounded-[24px] border px-4 py-4" :class="historyToneClass(item.level)">
                        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                            <div class="min-w-0">
                                <div class="text-[11px] font-black uppercase tracking-[0.16em] text-slate-500">{{ item.level }} • {{ item.alertDateLabel }}</div>
                                <div class="mt-2 text-base font-semibold text-slate-950">{{ item.title }}</div>
                                <p class="mt-2 text-sm leading-6 text-slate-600">{{ item.body }}</p>
                            </div>

                            <div class="grid gap-2 text-sm text-slate-600 md:min-w-[18rem]">
                                <div class="flex items-center justify-between gap-3">
                                    <span>Pertama terlihat</span>
                                    <span class="font-medium text-slate-950">{{ item.firstDetectedLabel }}</span>
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    <span>Terakhir terlihat</span>
                                    <span class="font-medium text-slate-950">{{ item.lastDetectedLabel }}</span>
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    <span>Jumlah muncul</span>
                                    <span class="font-medium text-slate-950">{{ number.format(item.seenCount) }}x</span>
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    <span>Telegram</span>
                                    <span class="font-medium text-slate-950">{{ item.lastNotifiedLabel ?? 'Belum dikirim' }}</span>
                                </div>
                            </div>
                        </div>
                    </article>

                    <div v-if="!alertHistory.length" class="rounded-[24px] border border-dashed border-slate-200 bg-slate-50 px-4 py-5 text-sm text-slate-500">
                        Belum ada histori alert keuangan yang tercatat.
                    </div>
                </div>
            </section>

            <section class="grid gap-5 xl:grid-cols-[minmax(0,1.25fr)_minmax(340px,0.75fr)]">
                <section class="rounded-[30px] border border-slate-200/80 bg-white p-5 shadow-[0_24px_70px_rgba(15,23,42,0.07)]">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <div class="text-[11px] uppercase tracking-[0.18em] text-slate-400">Tren 14 hari</div>
                            <div class="mt-2 text-xl font-semibold tracking-tight text-slate-950">Arus omzet dan estimasi profit</div>
                        </div>
                        <div class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-700">
                            {{ number.format(dailySeries.length) }} hari
                        </div>
                    </div>

                    <div class="mt-5 grid grid-cols-2 gap-3 md:grid-cols-4">
                        <article v-for="item in statusCards" :key="item.label" class="rounded-[22px] border border-slate-200/80 bg-slate-50/80 p-4">
                            <div class="text-[11px] uppercase tracking-[0.16em] text-slate-400">{{ item.label }}</div>
                            <div class="mt-3 text-xl font-semibold tracking-tight text-slate-950">{{ item.value }}</div>
                        </article>
                    </div>

                    <div class="mt-6 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-7">
                        <article
                            v-for="day in dailySeries"
                            :key="day.date"
                            class="rounded-[24px] border border-slate-200/80 bg-slate-50/80 px-3 py-4"
                        >
                            <div class="text-[11px] uppercase tracking-[0.16em] text-slate-400">{{ day.label }}</div>
                            <div class="mt-2 text-sm font-semibold text-slate-950">{{ formatCurrency(day.revenue) }}</div>
                            <div class="mt-1 text-[11px] text-emerald-600">profit {{ formatCurrency(day.estimatedProfit) }}</div>
                            <div class="mt-1 text-[11px] text-slate-500">{{ number.format(day.orders) }} order</div>

                            <div class="mt-4 flex h-24 items-end">
                                <div
                                    class="w-full rounded-[18px] bg-[linear-gradient(180deg,rgba(16,185,129,0.95),rgba(5,150,105,0.72))] shadow-[0_14px_30px_rgba(16,185,129,0.16)]"
                                    :style="{ height: `${Math.max(16, Math.round((day.revenue / maxRevenue) * 100))}%` }"
                                />
                            </div>
                        </article>
                    </div>
                </section>

                <section class="rounded-[30px] border border-slate-200/80 bg-slate-950 p-5 text-white shadow-[0_24px_70px_rgba(15,23,42,0.12)]">
                    <div class="text-[11px] uppercase tracking-[0.18em] text-white/42">Snapshot keuangan</div>
                    <div class="mt-2 text-xl font-semibold tracking-tight">Kondisi cepat periode terpilih</div>

                    <div class="mt-5 space-y-3">
                        <div class="rounded-[22px] border border-white/12 bg-white/8 p-4">
                            <p class="text-[0.62rem] font-black uppercase tracking-[0.18em] text-white/42">Omzet dibayar</p>
                            <p class="mt-2 text-lg font-black tracking-[-0.04em]">{{ formatCurrency(summary.grossRevenue) }}</p>
                            <p class="mt-1 text-sm text-white/62">Total transaksi dibayar dalam rentang yang dipilih.</p>
                        </div>
                        <div class="rounded-[22px] border border-white/12 bg-white/8 p-4">
                            <p class="text-[0.62rem] font-black uppercase tracking-[0.18em] text-white/42">Profit estimasi</p>
                            <p class="mt-2 text-lg font-black tracking-[-0.04em]">{{ formatCurrency(summary.estimatedGrossProfit) }}</p>
                            <p class="mt-1 text-sm text-white/62">Keuntungan kotor estimasi dari margin tier aktif.</p>
                        </div>
                        <div class="rounded-[22px] border border-white/12 bg-white/8 p-4">
                            <p class="text-[0.62rem] font-black uppercase tracking-[0.18em] text-white/42">Biaya manual</p>
                            <p class="mt-2 text-lg font-black tracking-[-0.04em]">{{ formatCurrency(summary.manualExpensesTotal) }}</p>
                            <p class="mt-1 text-sm text-white/62">Pengeluaran tambahan seperti iklan, tools, admin, atau operasional.</p>
                        </div>
                        <div class="rounded-[22px] border border-white/12 bg-white/8 p-4">
                            <p class="text-[0.62rem] font-black uppercase tracking-[0.18em] text-white/42">Margin estimasi</p>
                            <p class="mt-2 text-lg font-black tracking-[-0.04em]">{{ formatPercent(summary.estimatedMarginPercent) }}</p>
                            <p class="mt-1 text-sm text-white/62">Persentase profit estimasi dibanding omzet dibayar.</p>
                        </div>
                        <div class="rounded-[22px] border border-emerald-400/20 bg-emerald-500/10 p-4">
                            <p class="text-[0.62rem] font-black uppercase tracking-[0.18em] text-emerald-200/70">Profit bersih estimasi</p>
                            <p class="mt-2 text-lg font-black tracking-[-0.04em]">{{ formatSignedCurrency(summary.estimatedNetProfit) }}</p>
                            <p class="mt-1 text-sm text-emerald-100/70">Estimasi akhir setelah biaya manual pada periode ini dikurangkan.</p>
                        </div>
                    </div>
                </section>
            </section>

            <section class="rounded-[30px] border border-slate-200/80 bg-white p-5 shadow-[0_24px_70px_rgba(15,23,42,0.07)]">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <div class="text-[11px] uppercase tracking-[0.18em] text-slate-400">Tren bulanan</div>
                        <div class="mt-2 text-xl font-semibold tracking-tight text-slate-950">Omzet, biaya, dan profit bersih per bulan</div>
                    </div>
                    <div class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700">
                        {{ number.format(monthlySeries.length) }} bulan
                    </div>
                </div>

                <div class="mt-5 grid gap-3 lg:grid-cols-6">
                    <article v-for="item in monthlySeries" :key="item.month" class="rounded-[24px] border border-slate-200/80 bg-slate-50/80 px-4 py-4">
                        <div class="text-[11px] uppercase tracking-[0.16em] text-slate-400">{{ item.monthLabel }}</div>
                        <div class="mt-3 text-sm font-semibold text-slate-950">{{ formatSignedCurrency(item.estimatedNetProfit) }}</div>
                        <div class="mt-1 text-[11px] text-slate-500">{{ number.format(item.orders) }} order</div>

                        <div class="mt-4 flex h-28 items-end">
                            <div
                                class="w-full rounded-[18px] shadow-[0_14px_30px_rgba(99,102,241,0.16)]"
                                :class="item.estimatedNetProfit >= 0 ? 'bg-[linear-gradient(180deg,rgba(79,70,229,0.94),rgba(99,102,241,0.72))]' : 'bg-[linear-gradient(180deg,rgba(239,68,68,0.94),rgba(248,113,113,0.74))]'"
                                :style="{ height: `${Math.max(16, Math.round((Math.abs(item.estimatedNetProfit) / maxMonthlyNetProfit) * 100))}%` }"
                            />
                        </div>

                        <div class="mt-4 space-y-1.5 text-[11px] leading-5">
                            <div class="flex items-center justify-between gap-3 text-slate-500">
                                <span>Omzet</span>
                                <span class="font-semibold text-slate-700">{{ formatCurrency(item.revenue) }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-3 text-slate-500">
                                <span>Biaya</span>
                                <span class="font-semibold text-rose-600">{{ formatCurrency(item.manualExpenses) }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-3 text-slate-500">
                                <span>Profit kotor</span>
                                <span class="font-semibold text-emerald-600">{{ formatCurrency(item.estimatedGrossProfit) }}</span>
                            </div>
                        </div>
                    </article>

                    <div v-if="!monthlySeries.length" class="rounded-[24px] border border-dashed border-slate-200 bg-slate-50 px-4 py-5 text-sm text-slate-500 lg:col-span-6">
                        Belum ada data bulanan pada rentang ini.
                    </div>
                </div>
            </section>

            <section class="rounded-[30px] border border-slate-200/80 bg-white p-5 shadow-[0_24px_70px_rgba(15,23,42,0.07)]">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <div class="text-[11px] uppercase tracking-[0.18em] text-slate-400">Perbandingan bulan</div>
                        <div class="mt-2 text-xl font-semibold tracking-tight text-slate-950">Bulan ini vs bulan lalu</div>
                        <p class="mt-2 text-sm leading-6 text-slate-500">
                            Snapshot cepat buat lihat apakah omzet, profit bersih, biaya, dan jumlah order sedang naik atau turun secara bulanan.
                        </p>
                    </div>
                    <div class="rounded-[22px] border border-slate-200/80 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                        <span class="font-semibold text-slate-950">{{ monthComparison.currentMonth.label }}</span>
                        dibanding
                        <span class="font-semibold text-slate-950">{{ monthComparison.previousMonth.label }}</span>
                    </div>
                </div>

                <div class="mt-5 grid gap-4 lg:grid-cols-4">
                    <article v-for="card in monthComparisonCards" :key="card.label" class="rounded-[24px] border border-slate-200/80 bg-slate-50/80 p-4">
                        <div class="text-[11px] uppercase tracking-[0.16em] text-slate-400">{{ card.label }}</div>
                        <div class="mt-3 text-xl font-semibold tracking-tight text-slate-950">{{ card.current }}</div>
                        <div class="mt-1 text-xs text-slate-500">Bulan lalu {{ card.previous }}</div>
                        <div class="mt-3 text-sm font-semibold" :class="deltaTone(card.delta, card.invert)">
                            {{ formatDelta(card.delta) }}
                        </div>
                    </article>
                </div>
            </section>

            <section class="rounded-[30px] border border-slate-200/80 bg-white p-5 shadow-[0_24px_70px_rgba(15,23,42,0.07)]">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <div class="text-[11px] uppercase tracking-[0.18em] text-slate-400">Insight otomatis</div>
                        <div class="mt-2 text-xl font-semibold tracking-tight text-slate-950">Ringkasan cepat dari data keuangan</div>
                    </div>
                    <div class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700">
                        {{ number.format(insights.length) }} insight
                    </div>
                </div>

                <div class="mt-5 grid gap-4 lg:grid-cols-2">
                    <article v-for="insight in insights" :key="`${insight.tone}-${insight.title}`" class="rounded-[24px] border px-4 py-4" :class="insightToneClass(insight.tone)">
                        <div class="text-[11px] font-black uppercase tracking-[0.16em]">{{ insight.tone }}</div>
                        <div class="mt-2 text-base font-semibold">{{ insight.title }}</div>
                        <p class="mt-2 text-sm leading-6 opacity-90">{{ insight.body }}</p>
                    </article>
                </div>
            </section>

            <section class="grid gap-5 xl:grid-cols-[minmax(350px,0.9fr)_minmax(0,1.1fr)]">
                <section class="rounded-[30px] border border-slate-200/80 bg-white p-5 shadow-[0_24px_70px_rgba(15,23,42,0.07)]">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="text-[11px] uppercase tracking-[0.18em] text-slate-400">Biaya manual</div>
                            <div class="mt-2 text-xl font-semibold tracking-tight text-slate-950">Catat pengeluaran operasional</div>
                            <p class="mt-2 text-sm leading-6 text-slate-500">Masukkan biaya iklan, tools, server, fee admin, atau pengeluaran lain supaya profit bersih makin akurat.</p>
                        </div>
                        <span class="inline-flex size-11 items-center justify-center rounded-[18px] bg-rose-50 text-rose-600">
                            <ReceiptText class="size-5" />
                        </span>
                    </div>

                    <div v-if="status" class="mt-5 rounded-[22px] border border-emerald-200 bg-emerald-50/90 px-4 py-3 text-sm font-medium text-emerald-700">
                        {{ status }}
                    </div>

                    <form class="mt-5 space-y-4" @submit.prevent="submitExpense">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Nama biaya</label>
                            <Input v-model="expenseForm.title" class="h-12 rounded-2xl border-slate-200 text-[15px]" placeholder="Contoh: Iklan Meta minggu ini" />
                            <InputError class="mt-2" :message="expenseForm.errors.title" />
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">Kategori</label>
                                <Input v-model="expenseForm.category" list="expense-categories" class="h-12 rounded-2xl border-slate-200 text-[15px]" placeholder="Iklan / Server / Admin" />
                                <datalist id="expense-categories">
                                    <option value="Iklan" />
                                    <option value="Server" />
                                    <option value="Admin" />
                                    <option value="Tools" />
                                    <option value="Operasional" />
                                </datalist>
                                <InputError class="mt-2" :message="expenseForm.errors.category" />
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">Nominal</label>
                                <Input v-model="expenseForm.amount" type="number" min="1" class="h-12 rounded-2xl border-slate-200 text-[15px]" placeholder="50000" />
                                <InputError class="mt-2" :message="expenseForm.errors.amount" />
                            </div>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Tanggal biaya</label>
                            <Input v-model="expenseForm.expense_date" type="date" class="h-12 rounded-2xl border-slate-200 text-[15px]" />
                            <InputError class="mt-2" :message="expenseForm.errors.expense_date" />
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Catatan</label>
                            <textarea v-model="expenseForm.notes" rows="4" class="w-full rounded-[24px] border border-slate-200 px-4 py-3 text-sm text-slate-700 outline-none" placeholder="Opsional: detail biaya, link invoice, atau catatan internal." />
                            <InputError class="mt-2" :message="expenseForm.errors.notes" />
                        </div>

                        <Button type="submit" :disabled="expenseForm.processing" class="h-12 rounded-2xl px-5 text-sm font-semibold">
                            Simpan biaya
                        </Button>
                    </form>
                </section>

                <section class="space-y-5">
                    <section class="rounded-[30px] border border-slate-200/80 bg-white p-5 shadow-[0_24px_70px_rgba(15,23,42,0.07)]">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <div class="text-[11px] uppercase tracking-[0.18em] text-slate-400">Komposisi biaya</div>
                                <div class="mt-2 text-xl font-semibold tracking-tight text-slate-950">Kategori pengeluaran terbesar</div>
                            </div>
                            <div class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700">
                                {{ number.format(expenseCategories.length) }} kategori
                            </div>
                        </div>

                        <div class="mt-5 space-y-3">
                            <article v-for="item in expenseCategories" :key="item.category" class="rounded-[22px] border border-slate-200/80 bg-slate-50/80 px-4 py-4">
                                <div class="flex items-center justify-between gap-4">
                                    <div>
                                        <div class="text-sm font-semibold text-slate-950">{{ item.category }}</div>
                                        <div class="mt-1 text-xs text-slate-500">{{ number.format(item.count) }} pengeluaran tercatat</div>
                                    </div>
                                    <div class="text-right text-sm font-semibold text-rose-600">{{ formatCurrency(item.amount) }}</div>
                                </div>
                            </article>

                            <div v-if="!expenseCategories.length" class="rounded-[22px] border border-dashed border-slate-200 bg-slate-50 px-4 py-5 text-sm text-slate-500">
                                Belum ada biaya manual pada periode ini.
                            </div>
                        </div>
                    </section>

                    <section class="rounded-[30px] border border-slate-200/80 bg-white p-5 shadow-[0_24px_70px_rgba(15,23,42,0.07)]">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <div class="text-[11px] uppercase tracking-[0.18em] text-slate-400">Riwayat biaya</div>
                                <div class="mt-2 text-xl font-semibold tracking-tight text-slate-950">Biaya terbaru di periode terpilih</div>
                            </div>
                            <div class="rounded-full bg-rose-50 px-3 py-1 text-xs font-medium text-rose-700">
                                {{ number.format(expenses.length) }} biaya
                            </div>
                        </div>

                        <div class="mt-5 space-y-3">
                            <article v-for="expense in expenses" :key="expense.id" class="rounded-[24px] border border-slate-200/80 bg-slate-50/80 px-4 py-4">
                                <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                                    <div class="min-w-0">
                                        <div class="text-sm font-semibold text-slate-950">{{ expense.title }}</div>
                                        <div class="mt-1 text-xs text-slate-500">{{ expense.category }} • {{ expense.expenseDateLabel }}<span v-if="expense.createdBy"> • dicatat {{ expense.createdBy }}</span></div>
                                        <p v-if="expense.notes" class="mt-3 text-sm leading-6 text-slate-600">{{ expense.notes }}</p>
                                    </div>

                                    <div class="flex items-center gap-3 md:flex-col md:items-end">
                                        <div class="text-sm font-semibold text-rose-600">{{ formatCurrency(expense.amount) }}</div>
                                        <button type="button" class="inline-flex items-center gap-1 rounded-full border border-rose-200 bg-white px-3 py-1.5 text-xs font-semibold text-rose-600 transition hover:bg-rose-50" @click="deleteExpense(expense.id)">
                                            <Trash2 class="size-3.5" />
                                            Hapus
                                        </button>
                                    </div>
                                </div>
                            </article>

                            <div v-if="!expenses.length" class="rounded-[24px] border border-dashed border-slate-200 bg-slate-50 px-4 py-5 text-sm text-slate-500">
                                Belum ada biaya manual yang tercatat pada rentang ini.
                            </div>
                        </div>
                    </section>
                </section>
            </section>

            <section class="rounded-[30px] border border-slate-200/80 bg-white p-5 shadow-[0_24px_70px_rgba(15,23,42,0.07)]">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <div class="text-[11px] uppercase tracking-[0.18em] text-slate-400">Produk paling cuan</div>
                        <div class="mt-2 text-xl font-semibold tracking-tight text-slate-950">Top produk berdasarkan estimasi profit</div>
                    </div>
                    <div class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700">
                        {{ number.format(topProducts.length) }} produk
                    </div>
                </div>

                <div class="mt-5 space-y-3">
                    <article
                        v-for="product in topProducts"
                        :key="product.productName"
                        class="flex flex-col gap-3 rounded-[24px] border border-slate-200/80 bg-slate-50/80 px-4 py-4 md:flex-row md:items-center md:justify-between"
                    >
                        <div class="min-w-0 flex items-start gap-4">
                            <div class="inline-flex size-12 items-center justify-center rounded-[18px] bg-slate-950 text-white">
                                <Package2 class="size-5" />
                            </div>
                            <div class="min-w-0">
                                <div class="text-sm font-semibold text-slate-950">{{ product.productName }}</div>
                                <div class="mt-1 text-xs text-slate-500">
                                    {{ number.format(product.ordersCount) }} order • update terakhir {{ product.lastOrderLabel }}
                                </div>
                            </div>
                        </div>

                        <div class="grid gap-3 text-right sm:grid-cols-2">
                            <div class="rounded-[18px] bg-white px-4 py-3">
                                <div class="text-[11px] uppercase tracking-[0.16em] text-slate-400">Omzet</div>
                                <div class="mt-1 text-sm font-semibold text-slate-950">{{ formatCurrency(product.revenue) }}</div>
                            </div>
                            <div class="rounded-[18px] bg-emerald-50 px-4 py-3">
                                <div class="text-[11px] uppercase tracking-[0.16em] text-emerald-600">Estimasi profit</div>
                                <div class="mt-1 text-sm font-semibold text-emerald-700">{{ formatCurrency(product.estimatedProfit) }}</div>
                            </div>
                        </div>
                    </article>

                    <div v-if="!topProducts.length" class="rounded-[24px] border border-dashed border-slate-200 bg-slate-50 px-4 py-5 text-sm text-slate-500">
                        Belum ada transaksi dibayar pada rentang tanggal ini.
                    </div>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
