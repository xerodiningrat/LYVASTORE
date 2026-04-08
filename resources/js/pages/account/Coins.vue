<script setup lang="ts">
import PublicLayout from '@/layouts/PublicLayout.vue';
import type { SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowRight, Coins, Gift, History, ShieldCheck, Sparkles, WalletCards } from 'lucide-vue-next';
import { computed } from 'vue';

type RewardEntry = {
    publicId: string;
    productName: string;
    packageLabel: string;
    total: number;
    coins: number;
    completedAt?: string | null;
    checkoutUrl: string;
};

const props = defineProps<{
    balance: number;
    rewardRate: string;
    transactionCount: number;
    recentRewards: RewardEntry[];
}>();

const page = usePage<SharedData>();
const currentUser = computed(() => page.props.auth?.user ?? null);
const isLoggedIn = computed(() => Boolean(currentUser.value));
const coinProgram = computed(() => page.props.coinProgram ?? null);

const formatCurrency = (value: number) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);

const formatCoinCount = (value: number) =>
    `${new Intl.NumberFormat('id-ID', {
        maximumFractionDigits: 0,
    }).format(Math.max(0, Math.round(value)))} Coins`;

const formatDateTime = (value?: string | null) => {
    if (!value) {
        return 'Baru saja';
    }

    const parsed = new Date(value);

    if (Number.isNaN(parsed.getTime())) {
        return 'Baru saja';
    }

    return new Intl.DateTimeFormat('id-ID', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(parsed);
};

const totalEarnedLabel = computed(() => formatCoinCount(props.balance));
const coinValueLabel = computed(() => {
    const coinValue = Math.max(1, Number(coinProgram.value?.coinValueRupiah ?? 1));

    return `1 Coin = ${formatCurrency(coinValue)} nilai reward`;
});
const stats = computed(() => [
    {
        label: 'Cashback terkumpul',
        value: totalEarnedLabel.value,
        description: 'Total reward cashback yang sudah kamu kumpulkan.',
        icon: WalletCards,
        tone: 'bg-emerald-50 text-emerald-600',
    },
    {
        label: 'Formula cashback',
        value: props.rewardRate,
        description: 'Dibatasi otomatis supaya reward tetap menarik dan margin tetap aman.',
        icon: Sparkles,
        tone: 'bg-indigo-50 text-indigo-600',
    },
    {
        label: 'Transaksi berhadiah',
        value: `${props.transactionCount}`,
        description: 'Riwayat top up yang sudah kasih coin.',
        icon: History,
        tone: 'bg-amber-50 text-amber-600',
    },
]);
</script>

<template>
    <PublicLayout>
        <Head title="Lyva Coins" />

        <main class="relative overflow-hidden">
            <div class="pointer-events-none absolute inset-0">
                <div class="absolute left-[-8%] top-8 h-72 w-72 rounded-full bg-amber-200/30 blur-3xl" />
                <div class="absolute right-[-12%] top-20 h-96 w-96 rounded-full bg-sky-200/25 blur-3xl" />
            </div>

            <div class="relative mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <section class="overflow-hidden rounded-[34px] border border-white/80 bg-white/85 shadow-[0_28px_90px_rgba(15,23,42,0.08)] backdrop-blur">
                    <div class="grid gap-8 px-6 py-7 lg:grid-cols-[1.15fr,0.85fr] lg:px-8 lg:py-8">
                        <div class="space-y-6">
                            <div class="space-y-3">
                                <p class="text-[0.72rem] font-bold uppercase tracking-[0.22em] text-amber-500">Reward Wallet</p>
                                <h1 class="text-4xl font-black tracking-tight text-slate-950 [font-family:'Space Grotesk',sans-serif]">
                                    Lyva Coins
                                </h1>
                                <p class="max-w-2xl text-sm leading-7 text-slate-600">
                                    Semua cashback top up kamu terkumpul di sini sebagai reward akun. Coins menunjukkan total cashback yang sudah kamu kumpulkan dari transaksi selesai dan nanti bisa dipakai sebagai nilai tukar reward.
                                </p>
                            </div>

                            <div class="flex flex-wrap gap-3">
                                <span class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-4 py-2 text-xs font-bold uppercase tracking-[0.12em] text-emerald-700">
                                    <Coins class="size-3.5" />
                                    Cashback reward akun
                                </span>
                                <span class="inline-flex items-center gap-2 rounded-full border border-sky-200 bg-sky-50 px-4 py-2 text-xs font-bold uppercase tracking-[0.12em] text-sky-700">
                                    <ShieldCheck class="size-3.5" />
                                    Profit tetap aman
                                </span>
                            </div>

                            <div class="grid gap-4 md:grid-cols-3">
                                <article
                                    v-for="stat in stats"
                                    :key="stat.label"
                                    class="rounded-[26px] border border-slate-200/80 bg-white px-5 py-5 shadow-[0_18px_36px_rgba(15,23,42,0.05)]"
                                >
                                    <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl" :class="stat.tone">
                                        <component :is="stat.icon" class="size-5" />
                                    </span>
                                    <p class="mt-4 text-sm font-bold text-slate-500">{{ stat.label }}</p>
                                    <p class="mt-2 text-[1.65rem] font-black tracking-tight text-slate-950 [font-family:'Space Grotesk',sans-serif]">
                                        {{ stat.value }}
                                    </p>
                                    <p class="mt-2 text-sm leading-6 text-slate-600">{{ stat.description }}</p>
                                </article>
                            </div>
                        </div>

                        <div
                            class="rounded-[32px] border border-amber-100 bg-[linear-gradient(145deg,rgba(255,251,235,0.98),rgba(255,255,255,0.98),rgba(219,234,254,0.88))] p-6 shadow-[0_24px_60px_rgba(245,158,11,0.12)]"
                        >
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <p class="text-[0.72rem] font-bold uppercase tracking-[0.22em] text-amber-500">Total Cashback</p>
                                    <p class="mt-3 text-[2.8rem] font-black tracking-tight text-slate-950 [font-family:'Space Grotesk',sans-serif]">
                                        {{ totalEarnedLabel }}
                                    </p>
                                </div>
                                <span class="inline-flex h-16 w-16 items-center justify-center rounded-[24px] bg-white/90 text-amber-500 shadow-[0_16px_36px_rgba(245,158,11,0.18)]">
                                    <Coins class="size-8" />
                                </span>
                            </div>

                            <p class="mt-4 text-sm leading-7 text-slate-600">
                                Cashback coins kamu akan terus bertambah dari pesanan yang sudah berhasil. Reward dibatasi maksimal sesuai margin aman, jadi tetap enak buat customer tapi bisnis kamu tidak jebol saat coins nanti ditukar.
                            </p>

                            <div class="mt-6 space-y-3">
                                <div class="rounded-[22px] border border-amber-100 bg-white/80 px-4 py-4">
                                    <p class="text-[0.68rem] font-bold uppercase tracking-[0.14em] text-amber-500">Info reward</p>
                                    <p class="mt-2 text-sm leading-6 text-slate-600">
                                        {{ coinValueLabel }}. Besar cashback coin menyesuaikan nominal belanja, tapi tidak akan melebihi batas aman margin Lyva.
                                    </p>
                                </div>

                                <Link
                                    v-if="isLoggedIn"
                                    :href="route('transactions.history')"
                                    class="inline-flex h-12 w-full items-center justify-center rounded-full border border-slate-200 bg-white px-6 text-sm font-bold text-slate-700 transition hover:border-slate-300 hover:text-slate-950"
                                >
                                    Lihat riwayat transaksi
                                </Link>

                                <div v-else class="grid gap-3 sm:grid-cols-2">
                                    <Link
                                        :href="route('login')"
                                        class="inline-flex h-12 items-center justify-center rounded-full border border-slate-200 bg-white px-6 text-sm font-bold text-slate-700 transition hover:border-slate-300 hover:text-slate-950"
                                    >
                                        Masuk dulu
                                    </Link>
                                    <Link
                                        :href="route('register')"
                                        class="inline-flex h-12 items-center justify-center rounded-full border border-indigo-200 bg-indigo-50 px-6 text-sm font-bold text-indigo-700 transition hover:bg-indigo-100"
                                    >
                                        Buat akun
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="mt-8 rounded-[32px] border border-white/80 bg-white/85 p-6 shadow-[0_20px_64px_rgba(15,23,42,0.07)] backdrop-blur lg:p-7">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="text-[0.72rem] font-bold uppercase tracking-[0.22em] text-indigo-600">Riwayat Cashback</p>
                            <h2 class="mt-2 text-2xl font-black text-slate-950 [font-family:'Space Grotesk',sans-serif]">Transaksi penghasil coin</h2>
                            <p class="mt-2 text-sm leading-7 text-slate-600">
                                Setiap pesanan yang selesai akan nambah cashback coin ke akun kamu secara otomatis.
                            </p>
                        </div>

                        <p class="text-sm font-semibold text-slate-500">Total riwayat: {{ props.transactionCount }} transaksi</p>
                    </div>

                    <div v-if="!isLoggedIn" class="mt-6 rounded-[28px] border border-slate-200 bg-slate-50/80 px-5 py-6 text-center">
                        <span class="mx-auto inline-flex h-12 w-12 items-center justify-center rounded-full bg-white text-indigo-600 shadow-[0_12px_30px_rgba(15,23,42,0.08)]">
                            <Gift class="size-5" />
                        </span>
                        <h3 class="mt-4 text-xl font-black text-slate-950 [font-family:'Space Grotesk',sans-serif]">Masuk untuk lihat cashback coin kamu</h3>
                        <p class="mx-auto mt-2 max-w-2xl text-sm leading-7 text-slate-600">
                            Setelah login, halaman ini akan menampilkan total Lyva Coins dan riwayat cashback dari semua transaksi yang sudah selesai.
                        </p>
                    </div>

                    <div v-else-if="props.recentRewards.length === 0" class="mt-6 rounded-[28px] border border-dashed border-slate-300 bg-slate-50/80 px-5 py-8 text-center">
                        <span class="mx-auto inline-flex h-12 w-12 items-center justify-center rounded-full bg-white text-amber-500 shadow-[0_12px_30px_rgba(15,23,42,0.08)]">
                            <Coins class="size-5" />
                        </span>
                        <h3 class="mt-4 text-xl font-black text-slate-950 [font-family:'Space Grotesk',sans-serif]">Belum ada coin yang masuk</h3>
                        <p class="mx-auto mt-2 max-w-2xl text-sm leading-7 text-slate-600">
                            Selesaikan top up pertama kamu, nanti cashback coin otomatis muncul di sini begitu transaksi berhasil diproses.
                        </p>
                    </div>

                    <div v-else class="mt-6 grid gap-4 lg:grid-cols-2">
                        <article
                            v-for="reward in props.recentRewards"
                            :key="reward.publicId"
                            class="rounded-[28px] border border-slate-200 bg-white px-5 py-5 shadow-[0_18px_40px_rgba(15,23,42,0.05)]"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <p class="text-[0.72rem] font-bold uppercase tracking-[0.18em] text-slate-400">#{{ reward.publicId }}</p>
                                    <h3 class="mt-2 text-lg font-black text-slate-950 [font-family:'Space Grotesk',sans-serif]">
                                        {{ reward.productName }}
                                    </h3>
                                    <p class="mt-1 text-sm text-slate-600">{{ reward.packageLabel }}</p>
                                </div>
                                <span class="inline-flex shrink-0 items-center gap-2 rounded-full bg-emerald-50 px-3 py-2 text-sm font-bold text-emerald-700">
                                    <Coins class="size-4" />
                                    +{{ formatCoinCount(reward.coins) }}
                                </span>
                            </div>

                            <div class="mt-5 flex flex-wrap gap-3 text-sm text-slate-500">
                                <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-2 font-semibold">
                                    Belanja {{ formatCurrency(reward.total) }}
                                </span>
                                <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-2 font-semibold">
                                    {{ formatDateTime(reward.completedAt) }}
                                </span>
                            </div>

                            <Link
                                :href="reward.checkoutUrl"
                                class="mt-5 inline-flex items-center gap-2 text-sm font-bold text-indigo-700 transition hover:text-indigo-900"
                            >
                                Lihat detail transaksi
                                <ArrowRight class="size-4" />
                            </Link>
                        </article>
                    </div>
                </section>
            </div>
        </main>
    </PublicLayout>
</template>
