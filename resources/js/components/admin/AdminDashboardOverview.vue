<script setup lang="ts">
import {
    ArrowUpRight,
    BadgeDollarSign,
    Clock3,
    ImagePlus,
    ReceiptText,
    TrendingUp,
    Wallet,
} from 'lucide-vue-next';
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps<{
    stats: Record<string, number>;
    performanceSeries: Array<Record<string, string | number>>;
    actionQueues: Array<Record<string, unknown>>;
    branding: {
        title: string;
        tagline: string;
        logoUrl: string | null;
    };
}>();

const number = new Intl.NumberFormat('id-ID');
const compactNumber = new Intl.NumberFormat('id-ID', { notation: 'compact', maximumFractionDigits: 1 });
const formatCurrency = (value: number) => `Rp ${number.format(value)}`;
const formatCompactCurrency = (value: number) => `Rp ${compactNumber.format(value)}`;
const formatOverviewCurrency = (value: number) => {
    const normalized = Math.max(0, Math.round(value));

    if (normalized >= 1_000_000) {
        return formatCompactCurrency(normalized);
    }

    return formatCurrency(normalized);
};

const series = computed(() =>
    props.performanceSeries.map((item) => ({
        date: String(item.date ?? ''),
        label: String(item.label ?? ''),
        fullLabel: String(item.fullLabel ?? ''),
        paidRevenue: Number(item.paidRevenue ?? 0),
        paidOrders: Number(item.paidOrders ?? 0),
        completedOrders: Number(item.completedOrders ?? 0),
    })),
);

const queues = computed(() =>
    props.actionQueues.map((queue) => ({
        title: String(queue.title ?? ''),
        count: Number(queue.count ?? 0),
        description: String(queue.description ?? ''),
        tone: String(queue.tone ?? 'neutral'),
        href: String(queue.href ?? '/dashboard/transaksi'),
        ctaLabel: String(queue.ctaLabel ?? 'Lihat detail'),
        items: Array.isArray(queue.items)
            ? queue.items.map((item) => ({
                  publicId: String((item as Record<string, unknown>).publicId ?? ''),
                  productName: String((item as Record<string, unknown>).productName ?? ''),
                  customerName: String((item as Record<string, unknown>).customerName ?? ''),
                  total: Number((item as Record<string, unknown>).total ?? 0),
                  timeLabel: String((item as Record<string, unknown>).timeLabel ?? ''),
                  statusLabel: String((item as Record<string, unknown>).statusLabel ?? ''),
                  checkoutUrl: String((item as Record<string, unknown>).checkoutUrl ?? '#'),
              }))
            : [],
    })),
);

const performanceMaxRevenue = computed(() => Math.max(...series.value.map((item) => item.paidRevenue), 1));

const revenueBarHeight = (value: number) => {
    if (value <= 0) {
        return '14px';
    }

    return `${Math.max(16, Math.round((value / performanceMaxRevenue.value) * 100))}%`;
};

const queueToneClass = (tone: string) =>
    ({
        danger: 'border-rose-200/80 bg-rose-50/70',
        info: 'border-sky-200/80 bg-sky-50/70',
        warning: 'border-amber-200/80 bg-amber-50/70',
        neutral: 'border-slate-200/80 bg-slate-50/80',
    })[tone] ?? 'border-slate-200/80 bg-slate-50/80';

const queueCountClass = (tone: string) =>
    ({
        danger: 'bg-rose-600 text-white',
        info: 'bg-sky-600 text-white',
        warning: 'bg-amber-500 text-slate-950',
        neutral: 'bg-slate-900 text-white',
    })[tone] ?? 'bg-slate-900 text-white';

const overviewCards = computed(() => [
    {
        label: 'Omzet hari ini',
        value: formatOverviewCurrency(Number(props.stats.paidToday ?? 0)),
        icon: Wallet,
        iconWrapClass: 'bg-sky-50 text-sky-600 ring-1 ring-sky-100',
    },
    {
        label: 'Minggu ini',
        value: formatOverviewCurrency(Number(props.stats.paidThisWeek ?? 0)),
        icon: ReceiptText,
        iconWrapClass: 'bg-violet-50 text-violet-600 ring-1 ring-violet-100',
    },
    {
        label: 'Bulan ini',
        value: formatOverviewCurrency(Number(props.stats.paidThisMonth ?? 0)),
        icon: BadgeDollarSign,
        iconWrapClass: 'bg-emerald-50 text-emerald-600 ring-1 ring-emerald-100',
    },
    {
        label: 'Order aktif',
        value: number.format(Number(props.stats.pendingTransactions ?? 0)),
        icon: Clock3,
        iconWrapClass: 'bg-amber-50 text-amber-600 ring-1 ring-amber-100',
    },
]);
</script>

<template>
    <section class="grid gap-5 xl:grid-cols-[minmax(0,1.65fr)_minmax(340px,0.95fr)]">
        <div class="overflow-hidden rounded-[32px] border border-slate-200/80 bg-[radial-gradient(circle_at_top_left,_rgba(59,130,246,0.12),_transparent_36%),linear-gradient(180deg,_rgba(255,255,255,0.98),_rgba(248,250,252,0.98))] p-6 shadow-[0_24px_70px_rgba(15,23,42,0.07)] sm:p-7">
            <div class="flex flex-col gap-5">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="max-w-2xl">
                        <div class="inline-flex items-center rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.24em] text-sky-700">
                            Operasional admin
                        </div>
                        <h1 class="mt-4 text-3xl font-semibold tracking-tight text-slate-950 sm:text-[2.6rem]">
                            Ringkasan performa dan antrean penting hari ini
                        </h1>
                        <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-600 sm:text-[15px]">
                            Lihat omzet yang masuk, order yang butuh tindakan cepat, dan stok manual yang mulai menipis dalam satu layar.
                        </p>
                    </div>

                    <div class="min-w-[220px] rounded-[26px] border border-slate-200/80 bg-white/90 p-4 backdrop-blur">
                        <div class="text-[11px] uppercase tracking-[0.18em] text-slate-400">Kualitas performa</div>
                        <div class="mt-3 flex items-end justify-between gap-4">
                            <div>
                                <div class="text-3xl font-semibold tracking-tight text-slate-950">{{ Number(stats.successRate ?? 0) }}%</div>
                                <div class="mt-1 text-sm text-slate-500">rasio order selesai terhadap order yang sudah resolved</div>
                            </div>
                            <div class="rounded-2xl bg-emerald-50 p-3 text-emerald-600">
                                <TrendingUp class="size-5" />
                            </div>
                        </div>
                        <div class="mt-4 grid grid-cols-2 gap-3 text-sm text-slate-600">
                            <div class="rounded-2xl bg-slate-50 px-3 py-2">
                                <div class="text-[11px] uppercase tracking-[0.16em] text-slate-400">Selesai hari ini</div>
                                <div class="mt-1 font-semibold text-slate-950">{{ number.format(Number(stats.completedToday ?? 0)) }}</div>
                            </div>
                            <div class="rounded-2xl bg-slate-50 px-3 py-2">
                                <div class="text-[11px] uppercase tracking-[0.16em] text-slate-400">Avg order</div>
                                <div class="mt-1 font-semibold text-slate-950">{{ formatCurrency(Number(stats.averageOrderValue ?? 0)) }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <article
                        v-for="card in overviewCards"
                        :key="card.label"
                        class="rounded-[26px] border border-slate-200/80 bg-white/90 p-4"
                    >
                        <div class="flex min-h-[116px] items-start gap-4">
                            <div class="min-w-0 flex-1 pt-1">
                                <div class="text-[11px] uppercase tracking-[0.18em] text-slate-400">{{ card.label }}</div>
                                <div class="mt-4 text-[clamp(1.45rem,1.7vw,2rem)] font-semibold leading-[1.02] tracking-[-0.06em] text-slate-950 tabular-nums whitespace-nowrap">
                                    {{ card.value }}
                                </div>
                            </div>
                            <div
                                :class="[
                                    'inline-flex size-12 min-h-12 min-w-12 shrink-0 items-center justify-center self-start rounded-2xl shadow-[inset_0_1px_0_rgba(255,255,255,0.65)]',
                                    card.iconWrapClass,
                                ]"
                            >
                                <component :is="card.icon" class="size-5 shrink-0" />
                            </div>
                        </div>
                    </article>
                </div>

                <div class="rounded-[30px] border border-slate-200/80 bg-white/92 p-5">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <div class="text-[11px] uppercase tracking-[0.18em] text-slate-400">Grafik 7 hari terakhir</div>
                            <div class="mt-2 text-xl font-semibold tracking-tight text-slate-950">Arus omzet yang sudah dibayar</div>
                        </div>
                        <div class="flex flex-wrap gap-2 text-xs text-slate-500">
                            <span class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1">
                                <span class="size-2 rounded-full bg-sky-500"></span>
                                omzet dibayar
                            </span>
                            <span class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1">
                                <span class="size-2 rounded-full bg-emerald-500"></span>
                                order selesai
                            </span>
                        </div>
                    </div>

                    <div class="mt-6 grid grid-cols-7 gap-3">
                        <article
                            v-for="day in series"
                            :key="day.date"
                            class="rounded-[24px] border border-slate-200/80 bg-slate-50/80 px-3 py-4"
                        >
                            <div class="text-[11px] uppercase tracking-[0.16em] text-slate-400">{{ day.fullLabel }}</div>
                            <div class="mt-2 text-sm font-semibold text-slate-950">{{ formatCompactCurrency(day.paidRevenue) }}</div>
                            <div class="mt-1 text-[11px] text-slate-500">{{ day.paidOrders }} bayar • {{ day.completedOrders }} selesai</div>

                            <div class="mt-4 flex h-28 items-end">
                                <div class="relative flex h-full w-full items-end">
                                    <div
                                        class="w-full rounded-[18px] bg-[linear-gradient(180deg,rgba(59,130,246,0.92),rgba(37,99,235,0.74))] shadow-[0_16px_34px_rgba(59,130,246,0.18)] transition-all duration-500"
                                        :style="{ height: revenueBarHeight(day.paidRevenue) }"
                                    ></div>
                                    <div
                                        class="absolute left-1/2 -translate-x-1/2 rounded-full bg-emerald-500 shadow-[0_10px_20px_rgba(16,185,129,0.28)]"
                                        :style="{
                                            bottom: revenueBarHeight(day.paidRevenue),
                                            width: '10px',
                                            height: '10px',
                                            marginBottom: '6px',
                                            opacity: day.completedOrders > 0 ? 1 : 0.28,
                                        }"
                                    ></div>
                                </div>
                            </div>

                            <div class="mt-3 text-sm font-medium text-slate-700">{{ day.label }}</div>
                        </article>
                    </div>
                </div>
            </div>
        </div>

        <div class="space-y-5">
            <section class="rounded-[30px] border border-slate-200/80 bg-white p-5 shadow-[0_24px_70px_rgba(15,23,42,0.07)]">
                <div class="flex items-center gap-4">
                    <div class="flex size-16 items-center justify-center overflow-hidden rounded-[24px] bg-slate-100 ring-1 ring-slate-200">
                        <img v-if="branding.logoUrl" :src="branding.logoUrl" :alt="branding.title" class="size-full object-cover" />
                        <img v-else src="/brand/lyva-mascot-mark.png" alt="Lyva Admin" class="size-10 object-contain" />
                    </div>
                    <div>
                        <div class="text-[11px] uppercase tracking-[0.18em] text-slate-400">Brand sidebar</div>
                        <div class="mt-1 text-lg font-semibold tracking-tight text-slate-950">{{ branding.title }}</div>
                        <div class="text-sm text-slate-500">{{ branding.tagline }}</div>
                    </div>
                </div>

                <div class="mt-5 grid grid-cols-2 gap-3">
                    <div class="rounded-[22px] border border-slate-200/80 bg-slate-50 px-4 py-3">
                        <div class="text-[11px] uppercase tracking-[0.16em] text-slate-400">Override produk</div>
                        <div class="mt-2 text-xl font-semibold text-slate-950">{{ number.format(Number(stats.productOverrideCount ?? 0)) }}</div>
                    </div>
                    <div class="rounded-[22px] border border-slate-200/80 bg-slate-50 px-4 py-3">
                        <div class="text-[11px] uppercase tracking-[0.16em] text-slate-400">Tier margin</div>
                        <div class="mt-2 text-xl font-semibold text-slate-950">{{ number.format(Number(stats.marginTierCount ?? 0)) }}</div>
                    </div>
                </div>

                <Link
                    href="/dashboard/branding"
                    class="mt-5 inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100"
                >
                    Atur branding sidebar
                    <ImagePlus class="size-4" />
                </Link>
            </section>

            <section class="rounded-[30px] border border-slate-200/80 bg-white p-5 shadow-[0_24px_70px_rgba(15,23,42,0.07)]">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <div class="text-[11px] uppercase tracking-[0.18em] text-slate-400">Antrian prioritas</div>
                        <div class="mt-2 text-xl font-semibold tracking-tight text-slate-950">Yang perlu disentuh lebih dulu</div>
                    </div>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600">
                        {{ number.format(queues.reduce((sum, queue) => sum + queue.count, 0)) }} item
                    </span>
                </div>

                <div class="mt-5 space-y-3">
                    <article
                        v-for="queue in queues"
                        :key="queue.title"
                        class="rounded-[26px] border p-4 transition"
                        :class="queueToneClass(queue.tone)"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex min-w-8 items-center justify-center rounded-full px-2.5 py-1 text-xs font-semibold" :class="queueCountClass(queue.tone)">
                                        {{ number.format(queue.count) }}
                                    </span>
                                    <div class="text-sm font-semibold text-slate-950">{{ queue.title }}</div>
                                </div>
                                <p class="mt-2 text-sm leading-6 text-slate-600">{{ queue.description }}</p>
                            </div>

                            <Link :href="queue.href" class="inline-flex items-center gap-2 rounded-full border border-white/80 bg-white/90 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-white">
                                {{ queue.ctaLabel }}
                                <ArrowUpRight class="size-3.5" />
                            </Link>
                        </div>

                        <div v-if="queue.items.length" class="mt-4 space-y-2">
                            <Link
                                v-for="item in queue.items"
                                :key="item.publicId"
                                :href="item.checkoutUrl"
                                class="flex items-center justify-between gap-3 rounded-[20px] border border-white/90 bg-white/80 px-3 py-3 transition hover:bg-white"
                            >
                                <div class="min-w-0">
                                    <div class="text-sm font-semibold text-slate-950">#{{ item.publicId }} • {{ item.productName }}</div>
                                    <div class="mt-1 text-xs text-slate-500">{{ item.customerName }} • {{ item.timeLabel }}</div>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm font-semibold text-slate-950">{{ formatCurrency(item.total) }}</div>
                                    <div class="text-[11px] uppercase tracking-[0.16em] text-slate-400">{{ item.statusLabel }}</div>
                                </div>
                            </Link>
                        </div>

                        <div v-else class="mt-4 rounded-[20px] border border-dashed border-slate-200 bg-white/70 px-4 py-3 text-sm text-slate-500">
                            Belum ada item di antrean ini sekarang.
                        </div>
                    </article>
                </div>
            </section>
        </div>
    </section>
</template>
