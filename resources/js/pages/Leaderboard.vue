<script setup lang="ts">
import PublicLayout from '@/layouts/PublicLayout.vue';
import type { SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    ArrowRight,
    BadgeCheck,
    Crown,
    Flame,
    LogIn,
    Medal,
    ShieldCheck,
    Sparkles,
    Trophy,
    UserRound,
    WalletCards,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

type BoardKey = 'weekly' | 'monthly' | 'all_time';

type LeaderboardEntry = {
    rank: number;
    name: string;
    avatar: string | null;
    monogram: string;
    totalSpent: number;
    ordersCount: number;
    averageOrder: number;
    lastActivityAt?: string | null;
    lastActivityLabel: string;
    badge: string;
};

type BoardSnapshot = {
    key: BoardKey;
    label: string;
    eyebrow: string;
    title: string;
    description: string;
    windowLabel: string;
    freshnessLabel: string;
    joinRequirement: string;
    entries: LeaderboardEntry[];
    stats: {
        participantsCount: number;
        grossSpend: number;
        ordersCount: number;
        averageOrder: number;
    };
    viewerEntry: LeaderboardEntry | null;
};

const props = defineProps<{
    boards: Record<BoardKey, BoardSnapshot>;
    defaultBoard: BoardKey;
}>();

const page = usePage<SharedData>();
const activeBoard = ref<BoardKey>(props.defaultBoard);
const currentUser = computed(() => page.props.auth?.user ?? null);
const isLoggedIn = computed(() => Boolean(currentUser.value));
const boardTabs = computed(() => Object.values(props.boards) as BoardSnapshot[]);
const activeSnapshot = computed(() => props.boards[activeBoard.value]);
const activeEntries = computed(() => activeSnapshot.value.entries ?? []);
const podiumEntries = computed(() => activeEntries.value.slice(0, 3));
const featuredPodiumEntry = computed(() => podiumEntries.value.find((entry) => entry.rank === 1) ?? podiumEntries.value[0] ?? null);
const supportPodiumEntries = computed(() => podiumEntries.value.filter((entry) => entry.rank !== 1));

const formatCurrency = (value: number) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);

const formatCompactCurrency = (value: number) => {
    if (value >= 1_000_000_000) {
        return `Rp${new Intl.NumberFormat('id-ID', {
            minimumFractionDigits: value >= 10_000_000_000 ? 1 : 2,
            maximumFractionDigits: 2,
        }).format(value / 1_000_000_000)} M`;
    }

    if (value >= 1_000_000) {
        return `Rp${new Intl.NumberFormat('id-ID', {
            minimumFractionDigits: value >= 10_000_000 ? 1 : 2,
            maximumFractionDigits: 2,
        }).format(value / 1_000_000)} jt`;
    }

    if (value >= 1_000) {
        return `Rp${new Intl.NumberFormat('id-ID', {
            minimumFractionDigits: value >= 10_000 ? 0 : 1,
            maximumFractionDigits: 1,
        }).format(value / 1_000)} rb`;
    }

    return formatCurrency(value);
};

const statCards = computed(() => [
    {
        label: 'Peserta aktif',
        value: `${activeSnapshot.value.stats.participantsCount}`,
        detail: 'Rank valid',
        description: 'Akun login yang berhasil masuk ranking pada periode ini.',
        icon: UserRound,
        tone: 'bg-indigo-50 text-indigo-700',
    },
    {
        label: 'Total belanja',
        value: formatCompactCurrency(activeSnapshot.value.stats.grossSpend),
        detail: formatCurrency(activeSnapshot.value.stats.grossSpend),
        description: 'Akumulasi completed order yang dihitung ke papan peringkat.',
        icon: WalletCards,
        tone: 'bg-amber-50 text-amber-600',
    },
    {
        label: 'Rata-rata order',
        value: formatCompactCurrency(activeSnapshot.value.stats.averageOrder),
        detail: formatCurrency(activeSnapshot.value.stats.averageOrder),
        description: 'Nilai rata-rata per transaksi dari semua peserta leaderboard.',
        icon: Sparkles,
        tone: 'bg-emerald-50 text-emerald-600',
    },
]);

const statValueClass = (value: string) => {
    if (value.length >= 14) {
        return 'text-[1.12rem] sm:text-[1.22rem]';
    }

    if (value.length >= 10) {
        return 'text-[1.22rem] sm:text-[1.34rem]';
    }

    return 'text-[1.35rem] sm:text-[1.5rem]';
};

const viewerEntry = computed(() => activeSnapshot.value.viewerEntry);
const viewerHeadline = computed(() => {
    if (!isLoggedIn.value) {
        return 'Login dulu untuk ikut leaderboard';
    }

    if (!viewerEntry.value) {
        return 'Kamu belum masuk papan peringkat';
    }

    if (viewerEntry.value.rank <= 3) {
        return 'Kamu lagi duduk di area podium';
    }

    if (viewerEntry.value.rank <= 10) {
        return 'Posisi kamu sudah dekat papan atas';
    }

    return 'Masih ada ruang buat naik lebih tinggi';
});

const viewerSupport = computed(() => {
    if (!isLoggedIn.value) {
        return 'Setelah login, semua top up yang completed akan otomatis dihitung ke leaderboard ini.';
    }

    if (!viewerEntry.value) {
        return 'Selesaikan top up sambil login, nanti akun kamu langsung mulai dihitung di periode aktif.';
    }

    return `Total belanja kamu saat ini ${formatCurrency(viewerEntry.value.totalSpent)} dari ${viewerEntry.value.ordersCount} transaksi.`;
});

const insightItems = computed(() => [
    {
        title: 'Akun login saja yang dihitung',
        description: 'Transaksi guest tetap bisa jalan, tapi leaderboard hanya membaca order yang terikat ke akun user.',
        icon: ShieldCheck,
        tone: 'bg-sky-50 text-sky-700',
    },
    {
        title: 'Status harus paid dan completed',
        description: 'Order pending, unpaid, gagal, atau expired tidak akan menambah angka di papan ranking.',
        icon: BadgeCheck,
        tone: 'bg-emerald-50 text-emerald-700',
    },
    {
        title: 'Urutan murni dari total belanja',
        description: 'Semakin besar total top up kamu di periode aktif, semakin tinggi juga posisi akun di leaderboard.',
        icon: Flame,
        tone: 'bg-amber-50 text-amber-700',
    },
]);

const podiumShellClass = (entry: LeaderboardEntry) => {
    if (entry.rank === 1) {
        return 'border-amber-300/30 bg-white/[0.14]';
    }

    if (entry.rank === 2) {
        return 'border-sky-300/20 bg-white/10';
    }

    return 'border-fuchsia-300/20 bg-white/8';
};

const podiumAccentClass = (entry: LeaderboardEntry) => {
    if (entry.rank === 1) {
        return 'bg-[linear-gradient(145deg,rgba(251,191,36,0.92),rgba(249,115,22,0.9),rgba(79,70,229,0.9))]';
    }

    if (entry.rank === 2) {
        return 'bg-[linear-gradient(145deg,rgba(125,211,252,0.92),rgba(59,130,246,0.88),rgba(15,23,42,0.9))]';
    }

    return 'bg-[linear-gradient(145deg,rgba(244,114,182,0.9),rgba(168,85,247,0.88),rgba(79,70,229,0.88))]';
};

const rankIcon = (rank: number) => {
    if (rank === 1) {
        return Crown;
    }

    if (rank === 2) {
        return Trophy;
    }

    return Medal;
};
</script>

<template>
    <Head title="Leaderboard Belanja" />

    <PublicLayout active-nav="leaderboard">
        <main class="relative overflow-hidden px-4 py-8 sm:px-6 lg:px-8 lg:py-10">
            <div class="pointer-events-none absolute inset-0">
                <div class="absolute left-[-8%] top-10 h-80 w-80 rounded-full bg-indigo-200/30 blur-3xl" />
                <div class="absolute right-[-10%] top-24 h-96 w-96 rounded-full bg-sky-200/24 blur-3xl" />
                <div class="absolute bottom-[-4%] left-[16%] h-72 w-72 rounded-full bg-fuchsia-200/18 blur-3xl" />
            </div>

            <section class="relative mx-auto max-w-7xl">
                <div class="grid gap-6 lg:grid-cols-[1.05fr,0.95fr]">
                    <section
                        class="relative overflow-hidden rounded-[38px] border border-white/85 bg-[linear-gradient(180deg,rgba(255,255,255,0.95)_0%,rgba(243,247,255,0.96)_100%)] p-6 shadow-[0_34px_110px_rgba(99,102,241,0.12)] backdrop-blur-xl sm:p-8"
                    >
                        <div class="pointer-events-none absolute inset-x-0 top-0 h-40 bg-[radial-gradient(circle_at_top,rgba(67,56,202,0.18),transparent_62%)]" />

                        <div class="relative z-10">
                            <p class="text-[0.72rem] font-bold uppercase tracking-[0.26em] text-indigo-600">{{ activeSnapshot.eyebrow }}</p>
                            <h1 class="mt-4 max-w-3xl text-4xl font-black tracking-tight text-slate-950 [font-family:'Space_Grotesk',sans-serif] sm:text-5xl">
                                {{ activeSnapshot.title }}
                            </h1>
                            <p class="mt-5 max-w-2xl text-sm leading-7 text-slate-600 sm:text-base">
                                {{ activeSnapshot.description }}
                            </p>

                            <div class="mt-7 flex flex-wrap gap-3">
                                <span
                                    class="inline-flex items-center gap-2 rounded-full border border-indigo-200 bg-indigo-50 px-4 py-2 text-[0.72rem] font-bold uppercase tracking-[0.14em] text-indigo-700"
                                >
                                    <ShieldCheck class="size-3.5" />
                                    Login untuk ikut rank
                                </span>
                                <span
                                    class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-[0.72rem] font-bold uppercase tracking-[0.14em] text-slate-700"
                                >
                                    <Sparkles class="size-3.5" />
                                    {{ activeSnapshot.windowLabel }}
                                </span>
                            </div>

                            <div class="mt-8 inline-flex rounded-full border border-slate-200 bg-white/90 p-1 shadow-[0_14px_34px_rgba(15,23,42,0.06)]">
                                <button
                                    v-for="tab in boardTabs"
                                    :key="tab.key"
                                    type="button"
                                    class="rounded-full px-4 py-2 text-sm font-black transition sm:px-5"
                                    :class="
                                        activeBoard === tab.key
                                            ? 'bg-slate-950 text-white shadow-[0_12px_26px_rgba(15,23,42,0.22)]'
                                            : 'text-slate-500 hover:text-slate-900'
                                    "
                                    @click="activeBoard = tab.key"
                                >
                                    {{ tab.label }}
                                </button>
                            </div>

                            <div class="mt-8 grid gap-4 md:grid-cols-3">
                                <article
                                    v-for="stat in statCards"
                                    :key="stat.label"
                                    class="group relative min-h-[218px] overflow-hidden rounded-[28px] border border-slate-200/80 bg-white/92 px-5 py-5 shadow-[0_16px_36px_rgba(15,23,42,0.04)] transition duration-300 hover:-translate-y-1 hover:shadow-[0_24px_50px_rgba(15,23,42,0.08)]"
                                >
                                    <div class="absolute right-0 top-0 h-24 w-24 rounded-full bg-slate-100/80 blur-2xl transition duration-300 group-hover:scale-110" />
                                    <span class="relative inline-flex h-11 w-11 items-center justify-center rounded-2xl" :class="stat.tone">
                                        <component :is="stat.icon" class="size-5" />
                                    </span>
                                    <p class="relative mt-4 text-[0.72rem] font-bold uppercase tracking-[0.16em] text-slate-500">{{ stat.label }}</p>
                                    <p
                                        class="relative mt-2 max-w-full break-words leading-tight font-black tracking-tight text-slate-950 [font-family:'Space_Grotesk',sans-serif]"
                                        :class="statValueClass(stat.value)"
                                    >
                                        {{ stat.value }}
                                    </p>
                                    <p class="relative mt-2 text-[0.72rem] font-semibold uppercase tracking-[0.12em] text-slate-400">
                                        {{ stat.detail }}
                                    </p>
                                    <p class="relative mt-2 text-sm leading-6 text-slate-600">{{ stat.description }}</p>
                                </article>
                            </div>

                            <div class="mt-7 flex flex-col gap-3 sm:flex-row">
                                <Link
                                    :href="route('home')"
                                    class="inline-flex h-12 items-center justify-center rounded-full bg-indigo-700 px-7 text-sm font-bold text-white shadow-[0_20px_40px_rgba(67,56,202,0.28)] transition hover:bg-indigo-800"
                                >
                                    Top up buat naik rank
                                </Link>
                                <Link
                                    v-if="!isLoggedIn"
                                    :href="route('login')"
                                    class="inline-flex h-12 items-center justify-center rounded-full border border-slate-200 bg-white px-7 text-sm font-bold text-slate-700 transition hover:border-slate-300 hover:text-slate-950"
                                >
                                    Login dulu
                                </Link>
                                <Link
                                    v-else
                                    :href="route('transactions.history')"
                                    class="inline-flex h-12 items-center justify-center rounded-full border border-slate-200 bg-white px-7 text-sm font-bold text-slate-700 transition hover:border-slate-300 hover:text-slate-950"
                                >
                                    Cek riwayat transaksi
                                </Link>
                            </div>
                        </div>
                    </section>

                    <section class="relative overflow-hidden rounded-[38px] bg-slate-950 p-6 text-white shadow-[0_34px_90px_rgba(15,23,42,0.24)] sm:p-7">
                        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top,rgba(96,165,250,0.18),transparent_48%)]" />
                        <div class="leaderboard-sheen absolute inset-0 bg-[linear-gradient(135deg,transparent_0%,transparent_46%,rgba(255,255,255,0.12)_52%,transparent_58%,transparent_100%)]" />

                        <div class="relative z-10">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-[0.72rem] font-bold uppercase tracking-[0.22em] text-white/45">Top 3 spender</p>
                                    <h2 class="mt-2 text-3xl font-black tracking-tight [font-family:'Space_Grotesk',sans-serif]">Podium {{ activeSnapshot.windowLabel }}</h2>
                                </div>

                                <span class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/10 px-3 py-1.5 text-[0.68rem] font-bold uppercase tracking-[0.14em] text-white/80">
                                    <BadgeCheck class="size-3.5" />
                                    Live dari data transaksi
                                </span>
                            </div>

                            <div v-if="podiumEntries.length" class="mt-8 space-y-4">
                                <article
                                    v-if="featuredPodiumEntry"
                                    :key="`${activeSnapshot.key}-${featuredPodiumEntry.rank}-${featuredPodiumEntry.name}`"
                                    class="leaderboard-podium leaderboard-podium--champion group relative overflow-hidden rounded-[32px] border p-5 sm:p-6"
                                    :class="podiumShellClass(featuredPodiumEntry)"
                                >
                                    <div class="absolute inset-x-0 top-0 h-44 opacity-95 sm:h-48" :class="podiumAccentClass(featuredPodiumEntry)" />
                                    <div class="absolute inset-x-0 top-0 h-44 bg-[linear-gradient(180deg,rgba(255,255,255,0.22),transparent)] sm:h-48" />
                                    <div class="leaderboard-podium__orb absolute -right-8 top-8 h-28 w-28 rounded-full bg-white/10 blur-2xl" />

                                    <div class="relative z-10 flex h-full flex-col gap-5">
                                        <div class="flex flex-wrap items-start justify-between gap-4">
                                            <div class="flex items-center gap-3">
                                                <span
                                                    class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-white/20 bg-slate-950/35 text-sm font-black text-white shadow-[0_10px_26px_rgba(15,23,42,0.24)]"
                                                >
                                                    #{{ featuredPodiumEntry.rank }}
                                                </span>
                                                <div>
                                                    <p class="text-[0.68rem] font-bold uppercase tracking-[0.18em] text-white/55">Top spender utama</p>
                                                    <p class="mt-1 text-sm font-semibold text-white/82">{{ featuredPodiumEntry.badge }}</p>
                                                </div>
                                            </div>

                                            <span class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/10 px-3 py-1.5 text-[0.68rem] font-semibold uppercase tracking-[0.14em] text-white/80">
                                                <component :is="rankIcon(featuredPodiumEntry.rank)" class="size-3.5" />
                                                Champion lane
                                            </span>
                                        </div>

                                        <div class="grid gap-4 lg:grid-cols-[auto,1fr,auto] lg:items-start">
                                            <div
                                                class="flex h-20 w-20 items-center justify-center overflow-hidden rounded-[26px] border border-white/15 bg-white/10 text-2xl font-black text-white backdrop-blur shadow-[0_18px_36px_rgba(15,23,42,0.26)]"
                                            >
                                                <img v-if="featuredPodiumEntry.avatar" :src="featuredPodiumEntry.avatar" :alt="featuredPodiumEntry.name" class="h-full w-full object-cover" />
                                                <template v-else>
                                                    {{ featuredPodiumEntry.monogram }}
                                                </template>
                                            </div>

                                            <div class="min-w-0 pt-0.5">
                                                <p class="max-w-[10ch] text-[1.85rem] leading-[0.93] font-black tracking-tight text-white [font-family:'Space_Grotesk',sans-serif] sm:text-[2.12rem]">
                                                    {{ featuredPodiumEntry.name }}
                                                </p>
                                                <p class="mt-2 max-w-lg text-sm leading-6 text-white/72">
                                                    Peringkat pertama dengan total belanja tertinggi pada periode ini.
                                                </p>
                                            </div>

                                            <div class="rounded-[26px] border border-white/10 bg-slate-950/28 px-4 py-4 backdrop-blur sm:px-5 lg:self-end">
                                                <p class="text-[0.68rem] font-bold uppercase tracking-[0.18em] text-white/45">Total belanja</p>
                                                <p class="mt-3 text-[2.15rem] leading-none font-black tracking-tight text-white [font-family:'Space_Grotesk',sans-serif] sm:text-[2.55rem]">
                                                    {{ formatCompactCurrency(featuredPodiumEntry.totalSpent) }}
                                                </p>
                                                <p class="mt-2 text-sm font-medium text-white/62">
                                                    {{ formatCurrency(featuredPodiumEntry.totalSpent) }}
                                                </p>
                                            </div>
                                        </div>

                                        <div class="flex flex-wrap gap-2">
                                            <span class="rounded-full border border-white/10 bg-white/10 px-3 py-1.5 text-[0.68rem] font-semibold uppercase tracking-[0.14em] text-white/80">
                                                {{ featuredPodiumEntry.ordersCount }} transaksi
                                            </span>
                                            <span class="rounded-full border border-amber-300/20 bg-amber-300/12 px-3 py-1.5 text-[0.68rem] font-semibold uppercase tracking-[0.14em] text-amber-100">
                                                Avg {{ formatCurrency(featuredPodiumEntry.averageOrder) }}
                                            </span>
                                            <span class="rounded-full border border-white/10 bg-white/8 px-3 py-1.5 text-[0.68rem] font-semibold uppercase tracking-[0.14em] text-white/60">
                                                {{ featuredPodiumEntry.lastActivityLabel }}
                                            </span>
                                        </div>
                                    </div>
                                </article>

                                <div class="grid gap-4 sm:grid-cols-2">
                                    <article
                                        v-for="entry in supportPodiumEntries"
                                        :key="`${activeSnapshot.key}-${entry.rank}-${entry.name}`"
                                        class="leaderboard-podium group relative overflow-hidden rounded-[30px] border p-5"
                                        :class="podiumShellClass(entry)"
                                    >
                                        <div class="absolute inset-x-0 top-0 h-28 opacity-95" :class="podiumAccentClass(entry)" />
                                        <div class="absolute inset-x-0 top-0 h-28 bg-[linear-gradient(180deg,rgba(255,255,255,0.18),transparent)]" />
                                        <div class="leaderboard-podium__orb absolute -right-8 top-8 h-24 w-24 rounded-full bg-white/10 blur-2xl" />

                                        <div class="relative z-10 flex h-full flex-col gap-5">
                                            <div class="flex items-center justify-between gap-3">
                                                <span
                                                    class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-white/20 bg-slate-950/35 text-sm font-black text-white"
                                                >
                                                    #{{ entry.rank }}
                                                </span>
                                                <component :is="rankIcon(entry.rank)" class="size-5 text-white/90" />
                                            </div>

                                            <div
                                                class="flex h-16 w-16 items-center justify-center overflow-hidden rounded-[22px] border border-white/15 bg-white/10 text-lg font-black text-white backdrop-blur shadow-[0_16px_30px_rgba(15,23,42,0.18)]"
                                            >
                                                <img v-if="entry.avatar" :src="entry.avatar" :alt="entry.name" class="h-full w-full object-cover" />
                                                <template v-else>
                                                    {{ entry.monogram }}
                                                </template>
                                            </div>

                                            <div class="min-w-0">
                                                <p class="text-[1.45rem] leading-tight font-black tracking-tight text-white [font-family:'Space_Grotesk',sans-serif]">
                                                    {{ entry.name }}
                                                </p>
                                                <p class="mt-2 text-sm text-white/68">{{ entry.badge }}</p>
                                            </div>

                                            <div class="rounded-[22px] border border-white/10 bg-slate-950/24 px-4 py-3">
                                                <p class="text-[0.68rem] font-bold uppercase tracking-[0.18em] text-white/45">Total belanja</p>
                                                <p class="mt-2 text-[1.7rem] leading-none font-black tracking-tight text-white [font-family:'Space_Grotesk',sans-serif]">
                                                    {{ formatCompactCurrency(entry.totalSpent) }}
                                                </p>
                                                <p class="mt-2 text-xs font-medium text-white/58">
                                                    {{ formatCurrency(entry.totalSpent) }}
                                                </p>
                                            </div>

                                            <div class="flex flex-wrap gap-2">
                                                <span class="rounded-full border border-white/10 bg-white/10 px-3 py-1.5 text-[0.68rem] font-semibold uppercase tracking-[0.14em] text-white/80">
                                                    {{ entry.ordersCount }} transaksi
                                                </span>
                                                <span class="rounded-full border border-amber-300/20 bg-amber-300/12 px-3 py-1.5 text-[0.68rem] font-semibold uppercase tracking-[0.14em] text-amber-100">
                                                    Avg {{ formatCompactCurrency(entry.averageOrder) }}
                                                </span>
                                            </div>
                                        </div>
                                    </article>
                                </div>
                            </div>

                            <div v-else class="mt-8 rounded-[28px] border border-dashed border-white/15 bg-white/5 px-5 py-10 text-center">
                                <span class="mx-auto inline-flex h-12 w-12 items-center justify-center rounded-full bg-white/10 text-white/80">
                                    <Trophy class="size-5" />
                                </span>
                                <h3 class="mt-4 text-xl font-black [font-family:'Space_Grotesk',sans-serif]">Leaderboard masih kosong</h3>
                                <p class="mx-auto mt-2 max-w-md text-sm leading-7 text-white/70">
                                    Begitu ada top up completed dari akun login, papan podium akan langsung terisi otomatis.
                                </p>
                            </div>
                        </div>
                    </section>
                </div>

                <div class="mt-6 grid gap-6 lg:grid-cols-[1.08fr,0.92fr]">
                    <section class="overflow-hidden rounded-[34px] border border-white/85 bg-white/88 shadow-[0_24px_80px_rgba(15,23,42,0.08)] backdrop-blur">
                        <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-200/80 px-6 py-5">
                            <div>
                                <p class="text-[0.68rem] font-bold uppercase tracking-[0.24em] text-slate-500">Full ranking</p>
                                <h2 class="mt-2 text-2xl font-black tracking-tight text-slate-950 [font-family:'Space_Grotesk',sans-serif]">Top user paling banyak belanja</h2>
                            </div>

                            <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-xs font-bold uppercase tracking-[0.14em] text-slate-700">
                                <Sparkles class="size-3.5" />
                                {{ activeSnapshot.stats.ordersCount }} transaksi tercatat
                            </span>
                        </div>

                        <div v-if="activeEntries.length" class="divide-y divide-slate-200/80">
                            <article
                                v-for="entry in activeEntries"
                                :key="`${activeSnapshot.key}-${entry.rank}-${entry.name}`"
                                class="grid gap-4 px-6 py-5 transition hover:bg-slate-50/85 md:grid-cols-[auto,1.25fr,0.95fr,0.95fr]"
                            >
                                <div class="flex items-center">
                                    <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-slate-950 text-sm font-black text-white">
                                        #{{ entry.rank }}
                                    </span>
                                </div>

                                <div class="min-w-0">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-[18px] bg-slate-950 text-sm font-black text-white"
                                        >
                                            <img v-if="entry.avatar" :src="entry.avatar" :alt="entry.name" class="h-full w-full object-cover" />
                                            <template v-else>
                                                {{ entry.monogram }}
                                            </template>
                                        </div>

                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-black uppercase tracking-[0.06em] text-slate-950 [font-family:'Space_Grotesk',sans-serif]">
                                                {{ entry.name }}
                                            </p>
                                            <p class="mt-1 text-sm text-slate-500">{{ entry.badge }}</p>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <p class="text-[0.68rem] font-bold uppercase tracking-[0.18em] text-slate-500 md:hidden">Total belanja</p>
                                    <p class="mt-2 text-lg font-black text-slate-950 [font-family:'Space_Grotesk',sans-serif] md:mt-0">
                                        {{ formatCurrency(entry.totalSpent) }}
                                    </p>
                                    <p class="mt-1 text-sm text-slate-600">{{ entry.ordersCount }} transaksi</p>
                                </div>

                                <div>
                                    <p class="text-[0.68rem] font-bold uppercase tracking-[0.18em] text-slate-500 md:hidden">Aktivitas</p>
                                    <p class="mt-2 text-sm font-bold text-slate-900 md:mt-0">Avg {{ formatCurrency(entry.averageOrder) }}</p>
                                    <p class="mt-1 text-sm text-slate-600">{{ entry.lastActivityLabel }}</p>
                                </div>
                            </article>
                        </div>

                        <div v-else class="px-6 py-12 text-center">
                            <span class="mx-auto inline-flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-500">
                                <Trophy class="size-5" />
                            </span>
                            <h3 class="mt-4 text-xl font-black text-slate-950 [font-family:'Space_Grotesk',sans-serif]">Belum ada ranking yang tampil</h3>
                            <p class="mx-auto mt-2 max-w-2xl text-sm leading-7 text-slate-600">
                                Periode ini belum punya transaksi completed dari akun login. Begitu ada, daftar ranking akan langsung terisi di sini.
                            </p>
                        </div>
                    </section>

                    <div class="space-y-6">
                        <section class="overflow-hidden rounded-[34px] border border-white/85 bg-white/88 p-6 shadow-[0_24px_80px_rgba(15,23,42,0.08)] backdrop-blur">
                            <p class="text-[0.68rem] font-bold uppercase tracking-[0.24em] text-slate-500">Posisi kamu</p>
                            <h2 class="mt-2 text-2xl font-black tracking-tight text-slate-950 [font-family:'Space_Grotesk',sans-serif]">{{ viewerHeadline }}</h2>
                            <p class="mt-3 text-sm leading-7 text-slate-600">{{ viewerSupport }}</p>

                            <div
                                v-if="viewerEntry"
                                class="mt-6 rounded-[30px] border border-slate-200 bg-[linear-gradient(145deg,rgba(15,23,42,0.98),rgba(30,41,59,0.98))] p-5 text-white shadow-[0_24px_50px_rgba(15,23,42,0.2)]"
                            >
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="text-[0.68rem] font-bold uppercase tracking-[0.18em] text-white/45">Rank sekarang</p>
                                        <p class="mt-2 text-5xl font-black tracking-tight [font-family:'Space_Grotesk',sans-serif]">#{{ viewerEntry.rank }}</p>
                                    </div>
                                    <span class="rounded-full border border-white/10 bg-white/10 px-3 py-1.5 text-[0.68rem] font-semibold uppercase tracking-[0.14em] text-white/75">
                                        {{ activeSnapshot.windowLabel }}
                                    </span>
                                </div>

                                <div class="mt-6 grid gap-3 sm:grid-cols-3">
                                    <div class="rounded-[20px] border border-white/10 bg-white/8 px-4 py-4">
                                        <p class="text-[0.68rem] font-bold uppercase tracking-[0.14em] text-white/45">Total belanja</p>
                                        <p class="mt-2 text-lg font-black text-white [font-family:'Space_Grotesk',sans-serif]">
                                            {{ formatCurrency(viewerEntry.totalSpent) }}
                                        </p>
                                    </div>
                                    <div class="rounded-[20px] border border-white/10 bg-white/8 px-4 py-4">
                                        <p class="text-[0.68rem] font-bold uppercase tracking-[0.14em] text-white/45">Transaksi</p>
                                        <p class="mt-2 text-lg font-black text-white [font-family:'Space_Grotesk',sans-serif]">
                                            {{ viewerEntry.ordersCount }}
                                        </p>
                                    </div>
                                    <div class="rounded-[20px] border border-white/10 bg-white/8 px-4 py-4">
                                        <p class="text-[0.68rem] font-bold uppercase tracking-[0.14em] text-white/45">Rata-rata</p>
                                        <p class="mt-2 text-lg font-black text-white [font-family:'Space_Grotesk',sans-serif]">
                                            {{ formatCurrency(viewerEntry.averageOrder) }}
                                        </p>
                                    </div>
                                </div>

                                <p class="mt-4 text-sm text-white/70">Aktivitas terakhir: {{ viewerEntry.lastActivityLabel }}</p>
                            </div>

                            <div v-else-if="isLoggedIn" class="mt-6 rounded-[28px] border border-dashed border-slate-300 bg-slate-50 px-5 py-8 text-center">
                                <span class="mx-auto inline-flex h-12 w-12 items-center justify-center rounded-full bg-white text-indigo-600 shadow-[0_12px_30px_rgba(15,23,42,0.08)]">
                                    <Flame class="size-5" />
                                </span>
                                <h3 class="mt-4 text-xl font-black text-slate-950 [font-family:'Space_Grotesk',sans-serif]">Belum ada top up yang masuk</h3>
                                <p class="mx-auto mt-2 max-w-md text-sm leading-7 text-slate-600">
                                    Begitu kamu top up sambil login dan status order selesai, akun kamu langsung ikut race di leaderboard ini.
                                </p>
                                <Link
                                    :href="route('home')"
                                    class="mt-5 inline-flex h-11 items-center justify-center rounded-full bg-slate-950 px-6 text-sm font-bold text-white transition hover:bg-slate-800"
                                >
                                    Mulai top up
                                </Link>
                            </div>

                            <div v-else class="mt-6 rounded-[28px] border border-dashed border-slate-300 bg-slate-50 px-5 py-8 text-center">
                                <span class="mx-auto inline-flex h-12 w-12 items-center justify-center rounded-full bg-white text-indigo-600 shadow-[0_12px_30px_rgba(15,23,42,0.08)]">
                                    <LogIn class="size-5" />
                                </span>
                                <h3 class="mt-4 text-xl font-black text-slate-950 [font-family:'Space_Grotesk',sans-serif]">Masuk untuk ikut ranking</h3>
                                <p class="mx-auto mt-2 max-w-md text-sm leading-7 text-slate-600">
                                    Leaderboard ini cuma menghitung transaksi dari akun yang login. Setelah masuk, semua order completed kamu otomatis ikut dihitung.
                                </p>

                                <div class="mt-5 grid gap-3 sm:grid-cols-2">
                                    <Link
                                        :href="route('login')"
                                        class="inline-flex h-11 items-center justify-center rounded-full bg-slate-950 px-6 text-sm font-bold text-white transition hover:bg-slate-800"
                                    >
                                        Login
                                    </Link>
                                    <Link
                                        :href="route('register')"
                                        class="inline-flex h-11 items-center justify-center rounded-full border border-slate-200 bg-white px-6 text-sm font-bold text-slate-700 transition hover:border-slate-300 hover:text-slate-950"
                                    >
                                        Buat akun
                                    </Link>
                                </div>
                            </div>
                        </section>

                        <section class="overflow-hidden rounded-[34px] border border-white/85 bg-white/88 p-6 shadow-[0_24px_80px_rgba(15,23,42,0.08)] backdrop-blur">
                            <p class="text-[0.68rem] font-bold uppercase tracking-[0.24em] text-slate-500">Cara leaderboard dihitung</p>
                            <h2 class="mt-2 text-2xl font-black tracking-tight text-slate-950 [font-family:'Space_Grotesk',sans-serif]">Aturannya simpel dan transparan</h2>

                            <div class="mt-6 space-y-4">
                                <article
                                    v-for="item in insightItems"
                                    :key="item.title"
                                    class="flex items-start gap-4 rounded-[26px] bg-slate-50/90 p-4"
                                >
                                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl" :class="item.tone">
                                        <component :is="item.icon" class="size-4.5" />
                                    </span>

                                    <div>
                                        <p class="text-sm font-black uppercase tracking-[0.08em] text-slate-950 [font-family:'Space_Grotesk',sans-serif]">
                                            {{ item.title }}
                                        </p>
                                        <p class="mt-2 text-sm leading-6 text-slate-600">{{ item.description }}</p>
                                    </div>
                                </article>
                            </div>

                            <div class="mt-6 rounded-[26px] border border-indigo-100 bg-indigo-50/80 px-5 py-4">
                                <p class="text-sm font-bold text-indigo-950">{{ activeSnapshot.freshnessLabel }}</p>
                                <p class="mt-2 text-sm leading-6 text-indigo-700">{{ activeSnapshot.joinRequirement }}</p>
                            </div>

                            <Link
                                :href="route('home')"
                                class="mt-6 inline-flex items-center gap-2 text-sm font-bold text-indigo-700 transition hover:text-indigo-600"
                            >
                                Buka produk dan mulai push rank
                                <ArrowRight class="size-4" />
                            </Link>
                        </section>
                    </div>
                </div>
            </section>
        </main>
    </PublicLayout>
</template>

<style scoped>
.leaderboard-podium {
    transition:
        transform 220ms ease,
        box-shadow 220ms ease,
        border-color 220ms ease;
}

.leaderboard-podium:hover {
    transform: translateY(-6px);
    box-shadow: 0 28px 64px rgba(15, 23, 42, 0.24);
}

.leaderboard-podium__orb {
    transition:
        transform 260ms ease,
        opacity 260ms ease;
}

.leaderboard-podium:hover .leaderboard-podium__orb {
    opacity: 0.95;
    transform: scale(1.12);
}

.leaderboard-podium--champion:hover {
    box-shadow: 0 32px 76px rgba(245, 158, 11, 0.16);
}

@media (prefers-reduced-motion: reduce) {
    .leaderboard-podium,
    .leaderboard-podium__orb {
        transition: none !important;
    }

    .leaderboard-podium:hover {
        transform: none;
    }

    .leaderboard-podium:hover .leaderboard-podium__orb {
        transform: none;
    }
}
</style>
