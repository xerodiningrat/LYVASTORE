<script setup lang="ts">
import PublicLayout from '@/layouts/PublicLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import {
    BadgeCheck,
    ChevronRight,
    Clock3,
    LoaderCircle,
    MessageCircleMore,
    PackageOpen,
    ShieldCheck,
    WalletCards,
    XCircle,
} from 'lucide-vue-next';
import { computed, type Component } from 'vue';

type HistoryParticle = {
    id: number;
    size: string;
    left: string;
    top: string;
    delay: string;
    duration: string;
    opacity: number;
};

type TransactionStatus = 'processing' | 'completed' | 'pending' | 'failed' | 'expired';

type TransactionRecord = {
    id: string;
    title: string;
    game: string;
    invoice: string;
    createdAt: string;
    amount: string;
    paymentMethod: string;
    status: TransactionStatus;
    detailUrl: string;
};

type SummaryCard = {
    label: string;
    value: string;
    tone: string;
};

const props = withDefaults(
    defineProps<{
        transactions?: TransactionRecord[];
    }>(),
    {
        transactions: () => [],
    },
);

const historyParticles: HistoryParticle[] = [
    { id: 1, size: '10px', left: '7%', top: '14%', delay: '0s', duration: '8.6s', opacity: 0.44 },
    { id: 2, size: '14px', left: '18%', top: '24%', delay: '1.3s', duration: '10.1s', opacity: 0.3 },
    { id: 3, size: '9px', left: '29%', top: '16%', delay: '0.6s', duration: '9.1s', opacity: 0.5 },
    { id: 4, size: '12px', left: '38%', top: '32%', delay: '2.2s', duration: '11.4s', opacity: 0.24 },
    { id: 5, size: '16px', left: '48%', top: '18%', delay: '1.7s', duration: '9.9s', opacity: 0.34 },
    { id: 6, size: '11px', left: '61%', top: '27%', delay: '0.5s', duration: '8.9s', opacity: 0.42 },
    { id: 7, size: '18px', left: '72%', top: '15%', delay: '2.1s', duration: '12.1s', opacity: 0.22 },
    { id: 8, size: '10px', left: '83%', top: '24%', delay: '1.2s', duration: '9.6s', opacity: 0.4 },
    { id: 9, size: '13px', left: '91%', top: '33%', delay: '2.8s', duration: '10.8s', opacity: 0.22 },
    { id: 10, size: '10px', left: '12%', top: '63%', delay: '0.9s', duration: '8.7s', opacity: 0.36 },
    { id: 11, size: '16px', left: '24%', top: '78%', delay: '2.4s', duration: '11.2s', opacity: 0.24 },
    { id: 12, size: '9px', left: '43%', top: '72%', delay: '0.2s', duration: '9.5s', opacity: 0.44 },
    { id: 13, size: '14px', left: '64%', top: '79%', delay: '1.9s', duration: '10.9s', opacity: 0.24 },
    { id: 14, size: '11px', left: '86%', top: '68%', delay: '2.6s', duration: '8.8s', opacity: 0.42 },
];

const hasTransactions = computed(() => props.transactions.length > 0);

const summaryCards = computed<SummaryCard[]>(() => [
    {
        label: 'Total Transaksi',
        value: String(props.transactions.length).padStart(2, '0'),
        tone: 'bg-indigo-50 text-indigo-700',
    },
    {
        label: 'Sedang Diproses',
        value: String(props.transactions.filter((item) => item.status === 'processing' || item.status === 'pending').length).padStart(2, '0'),
        tone: 'bg-amber-50 text-amber-700',
    },
    {
        label: 'Selesai',
        value: String(props.transactions.filter((item) => item.status === 'completed').length).padStart(2, '0'),
        tone: 'bg-emerald-50 text-emerald-700',
    },
]);

const statusMeta: Record<TransactionStatus, { label: string; tone: string; icon: Component }> = {
    processing: {
        label: 'Diproses',
        tone: 'border-amber-200 bg-amber-50 text-amber-700',
        icon: LoaderCircle,
    },
    completed: {
        label: 'Selesai',
        tone: 'border-emerald-200 bg-emerald-50 text-emerald-700',
        icon: BadgeCheck,
    },
    pending: {
        label: 'Menunggu',
        tone: 'border-sky-200 bg-sky-50 text-sky-700',
        icon: Clock3,
    },
    failed: {
        label: 'Gagal',
        tone: 'border-rose-200 bg-rose-50 text-rose-700',
        icon: XCircle,
    },
    expired: {
        label: 'Expired',
        tone: 'border-slate-200 bg-slate-100 text-slate-700',
        icon: Clock3,
    },
};
</script>

<template>
    <Head title="Riwayat Transaksi" />

    <PublicLayout active-nav="riwayat-transaksi">
        <main class="relative overflow-hidden px-4 py-8 sm:px-6 lg:px-8 lg:py-10">
            <div
                class="pointer-events-none absolute inset-x-0 top-0 h-80"
                style="background-image: radial-gradient(circle at top, rgba(67, 56, 202, 0.2), transparent 58%)"
            />

            <div class="pointer-events-none absolute inset-0">
                <div class="login-orb absolute left-[-5%] top-[8%] h-72 w-72 rounded-full bg-indigo-300/30 blur-3xl" />
                <div class="login-orb login-orb--alt absolute right-[-2%] top-[12%] h-80 w-80 rounded-full bg-sky-200/32 blur-3xl" />
                <div class="login-orb login-orb--soft absolute bottom-[6%] left-[10%] h-80 w-80 rounded-full bg-violet-200/20 blur-3xl" />

                <span
                    v-for="particle in historyParticles"
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

                <div class="relative z-10">
                    <div class="flex flex-col gap-5 border-b border-slate-200/80 pb-6 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="text-[0.72rem] font-semibold uppercase tracking-[0.26em] text-indigo-600">Riwayat Transaksi</p>
                            <h1 class="mt-3 text-4xl font-black tracking-tight text-slate-950 [font-family:'Space Grotesk',sans-serif] sm:text-5xl">
                                Daftar transaksi kamu.
                            </h1>
                        </div>

                        <span class="inline-flex w-fit items-center gap-2 rounded-full border px-4 py-2 text-xs font-bold uppercase tracking-[0.14em]"
                            :class="hasTransactions ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-slate-200 bg-white text-slate-600'">
                            <span class="h-2.5 w-2.5 rounded-full" :class="hasTransactions ? 'bg-emerald-500' : 'bg-slate-300'" />
                            {{ hasTransactions ? 'Data transaksi tersedia' : 'Belum ada transaksi' }}
                        </span>
                    </div>

                    <div v-if="hasTransactions" class="mt-8 space-y-6">
                        <div class="grid gap-4 sm:grid-cols-3">
                            <article v-for="card in summaryCards" :key="card.label" class="rounded-[28px] border border-white/80 bg-white/80 p-5 shadow-[0_18px_36px_rgba(15,23,42,0.05)]">
                                <p class="text-[0.68rem] font-semibold uppercase tracking-[0.2em] text-slate-500">{{ card.label }}</p>
                                <div class="mt-3 flex items-end justify-between gap-3">
                                    <p class="text-3xl font-black text-slate-950 [font-family:'Space Grotesk',sans-serif]">{{ card.value }}</p>
                                    <span class="rounded-full px-3 py-1 text-xs font-bold uppercase tracking-[0.14em]" :class="card.tone">Live</span>
                                </div>
                            </article>
                        </div>

                        <div class="rounded-[30px] border border-white/80 bg-white/78 p-3 shadow-[0_22px_48px_rgba(15,23,42,0.06)] sm:p-4 lg:overflow-hidden lg:p-0">
                            <div class="hidden grid-cols-[1.5fr,0.95fr,0.95fr,0.8fr,auto] gap-4 border-b border-slate-200/80 px-6 py-4 text-[0.68rem] font-semibold uppercase tracking-[0.2em] text-slate-500 lg:grid">
                                <span>Produk</span>
                                <span>Tanggal</span>
                                <span>Pembayaran</span>
                                <span>Status</span>
                                <span class="text-right">Aksi</span>
                            </div>

                            <article
                                v-for="transaction in props.transactions"
                                :key="transaction.id"
                                class="mb-3 rounded-[24px] border border-slate-200/80 bg-white px-4 py-4 shadow-[0_16px_36px_rgba(15,23,42,0.05)] last:mb-0 lg:mb-0 lg:rounded-none lg:border-0 lg:border-b lg:border-slate-200/70 lg:bg-transparent lg:px-6 lg:py-5 lg:shadow-none lg:last:border-b-0 lg:grid lg:grid-cols-[1.5fr,0.95fr,0.95fr,0.8fr,auto] lg:gap-4"
                            >
                                <div>
                                    <p class="text-[0.88rem] font-black uppercase tracking-[0.05em] text-slate-950 [font-family:'Space Grotesk',sans-serif] sm:text-sm">
                                        {{ transaction.title }}
                                    </p>
                                    <p class="mt-1.5 text-[0.82rem] text-slate-600 sm:mt-2 sm:text-sm">{{ transaction.game }}</p>
                                    <p class="mt-2 inline-flex rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-[0.64rem] font-semibold uppercase tracking-[0.14em] text-slate-400 sm:text-xs">
                                        {{ transaction.invoice }}
                                    </p>
                                </div>

                                <div class="mt-3 lg:mt-0">
                                    <p class="text-[0.68rem] font-semibold uppercase tracking-[0.18em] text-slate-500 lg:hidden">Tanggal</p>
                                    <p class="mt-1.5 text-[0.82rem] font-semibold text-slate-700 sm:text-sm lg:mt-0">{{ transaction.createdAt }}</p>
                                </div>

                                <div class="mt-3 lg:mt-0">
                                    <p class="text-[0.68rem] font-semibold uppercase tracking-[0.18em] text-slate-500 lg:hidden">Pembayaran</p>
                                    <p class="mt-1.5 text-[0.82rem] font-semibold text-slate-700 sm:text-sm lg:mt-0">{{ transaction.paymentMethod }}</p>
                                    <p class="mt-1 text-[0.92rem] font-bold text-slate-950 sm:text-sm">{{ transaction.amount }}</p>
                                </div>

                                <div class="mt-3 lg:mt-0">
                                    <p class="text-[0.68rem] font-semibold uppercase tracking-[0.18em] text-slate-500 lg:hidden">Status</p>
                                    <span
                                        class="mt-1.5 inline-flex items-center gap-2 rounded-full border px-3 py-2 text-[0.68rem] font-bold uppercase tracking-[0.14em] sm:text-xs lg:mt-0"
                                        :class="statusMeta[transaction.status].tone"
                                    >
                                        <component :is="statusMeta[transaction.status].icon" class="size-3.5" :class="transaction.status === 'processing' ? 'animate-spin' : ''" />
                                        {{ statusMeta[transaction.status].label }}
                                    </span>
                                </div>

                                <div class="mt-4 flex items-center lg:mt-0 lg:justify-end">
                                    <Link
                                        :href="transaction.detailUrl"
                                        class="inline-flex w-full items-center justify-center gap-2 rounded-[16px] border border-indigo-200 bg-indigo-50 px-3.5 py-2.5 text-[0.82rem] font-bold text-indigo-700 transition hover:border-indigo-300 hover:text-indigo-600 lg:w-auto lg:justify-start lg:rounded-none lg:border-0 lg:bg-transparent lg:px-0 lg:py-0 lg:text-sm"
                                    >
                                        Detail
                                        <ChevronRight class="size-4" />
                                    </Link>
                                </div>
                            </article>
                        </div>
                    </div>

                    <div v-else class="mt-10 overflow-hidden rounded-[34px] border border-white/80 bg-white/74 px-6 py-10 text-center shadow-[0_24px_60px_rgba(15,23,42,0.06)] sm:px-10 sm:py-14">
                        <div class="relative mx-auto h-56 w-full max-w-[360px]">
                            <div class="absolute inset-x-10 bottom-2 h-16 rounded-full bg-indigo-200/45 blur-2xl" />
                            <div class="absolute left-6 top-14 h-28 w-24 rotate-[-8deg] rounded-[30px] border border-white/60 bg-white/70 shadow-[0_16px_34px_rgba(15,23,42,0.08)]" />
                            <div class="absolute right-6 top-10 h-32 w-28 rotate-[9deg] rounded-[34px] border border-white/70 bg-white/85 shadow-[0_16px_34px_rgba(15,23,42,0.08)]" />
                            <div class="absolute inset-x-0 top-6 flex justify-center">
                                <div class="flex h-28 w-28 items-center justify-center rounded-[30px] bg-[linear-gradient(145deg,#4f46e5_0%,#7c3aed_55%,#60a5fa_100%)] shadow-[0_24px_48px_rgba(79,70,229,0.22)]">
                                    <PackageOpen class="size-12 text-white" />
                                </div>
                            </div>
                            <div class="absolute left-16 top-4 h-10 w-10 rounded-2xl bg-sky-100/80" />
                            <div class="absolute right-14 top-24 h-12 w-12 rounded-2xl bg-indigo-100/80" />
                        </div>

                        <h2 class="mt-3 text-3xl font-black tracking-tight text-slate-950 [font-family:'Space Grotesk',sans-serif]">Belum ada transaksi</h2>
                        <p class="mx-auto mt-4 max-w-2xl text-base leading-8 text-slate-600">
                            Riwayat transaksi kamu akan muncul di sini. Mulai top up game favorit kamu sekarang, lalu daftar pesanan akan otomatis tampil sebagai list transaksi.
                        </p>

                        <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                            <Link
                                :href="route('home')"
                                class="inline-flex h-[3.2rem] items-center justify-center rounded-[20px] bg-indigo-700 px-7 text-sm font-bold text-white shadow-[0_22px_40px_rgba(67,56,202,0.28)] transition hover:bg-indigo-800"
                            >
                                Lihat pilihan game
                            </Link>
                            <Link
                                :href="route('login')"
                                class="inline-flex h-[3.2rem] items-center justify-center rounded-[20px] border border-slate-200 bg-white px-7 text-sm font-bold text-slate-700 transition hover:border-indigo-300 hover:text-indigo-700"
                            >
                                Masuk akun
                            </Link>
                        </div>
                    </div>
                </div>
            </section>

            <section class="relative mx-auto mt-8 grid max-w-7xl gap-6 lg:grid-cols-3">
                <article class="rounded-[30px] border border-white/80 bg-white/76 p-6 shadow-[0_18px_42px_rgba(15,23,42,0.05)]">
                    <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-700">
                        <ShieldCheck class="size-4.5" />
                    </span>
                    <h3 class="mt-5 text-sm font-black uppercase tracking-[0.08em] text-slate-950 [font-family:'Space Grotesk',sans-serif]">Sinkron otomatis</h3>
                    <p class="mt-3 text-sm leading-7 text-slate-600">Begitu transaksi masuk dari backend, halaman ini langsung bisa menampilkan daftar pesanan dalam format list.</p>
                </article>

                <article class="rounded-[30px] border border-white/80 bg-white/76 p-6 shadow-[0_18px_42px_rgba(15,23,42,0.05)]">
                    <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700">
                        <WalletCards class="size-4.5" />
                    </span>
                    <h3 class="mt-5 text-sm font-black uppercase tracking-[0.08em] text-slate-950 [font-family:'Space Grotesk',sans-serif]">Status lebih jelas</h3>
                    <p class="mt-3 text-sm leading-7 text-slate-600">Transaksi selesai, diproses, menunggu, atau gagal akan tampil dengan badge yang mudah dibaca.</p>
                </article>

                <article class="rounded-[30px] border border-white/80 bg-white/76 p-6 shadow-[0_18px_42px_rgba(15,23,42,0.05)]">
                    <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-sky-50 text-sky-700">
                        <MessageCircleMore class="size-4.5" />
                    </span>
                    <h3 class="mt-5 text-sm font-black uppercase tracking-[0.08em] text-slate-950 [font-family:'Space Grotesk',sans-serif]">Butuh bantuan?</h3>
                    <p class="mt-3 text-sm leading-7 text-slate-600">Kalau ada pembayaran tertunda atau invoice tidak cocok, halaman bantuan dan support tetap bisa jadi jalur lanjutnya.</p>
                </article>
            </section>
        </main>
    </PublicLayout>
</template>
