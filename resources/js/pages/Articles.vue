<script setup lang="ts">
import PublicLayout from '@/layouts/PublicLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight, BookOpenText, Clock3, Flame, LayoutGrid, ShieldCheck, Sparkles, Trophy, Zap } from 'lucide-vue-next';

type ArticleChip = {
    label: string;
};

type ArticleCard = {
    category: string;
    title: string;
    excerpt: string;
    meta: string;
    accent: string;
};

type CategoryBlock = {
    title: string;
    description: string;
    count: string;
    accent: string;
};

const heroChips: ArticleChip[] = [
    { label: 'Meta Update' },
    { label: 'Event' },
    { label: 'Tips Rank' },
    { label: 'Voucher' },
];

const quickReads: ArticleCard[] = [
    {
        category: 'Patch',
        title: 'Hero paling stabil buat push rank minggu ini',
        excerpt: 'Ringkasan cepat hero yang lagi naik daun setelah perubahan patch terbaru.',
        meta: '5 menit baca',
        accent: 'linear-gradient(145deg,#60a5fa_0%,#2563eb_44%,#1d4ed8_100%)',
    },
    {
        category: 'Voucher',
        title: 'Kapan waktu paling pas beli voucher game',
        excerpt: 'Pola promo mingguan dan momentum event yang paling sering kasih value terbaik.',
        meta: '4 menit baca',
        accent: 'linear-gradient(145deg,#f59e0b_0%,#f97316_42%,#7c3aed_100%)',
    },
    {
        category: 'Esports',
        title: 'Tim yang lagi panas dan wajib dipantau di leaderboard',
        excerpt: 'Nama-nama yang lagi naik cepat dan potensial geser papan atas season ini.',
        meta: '6 menit baca',
        accent: 'linear-gradient(145deg,#22c55e_0%,#14b8a6_44%,#0ea5e9_100%)',
    },
];

const latestArticles: ArticleCard[] = [
    {
        category: 'Guide',
        title: 'Cara susun budget top up biar tetap aman tiap season',
        excerpt: 'Strategi sederhana untuk atur belanja diamond, voucher, dan battle pass tanpa boros.',
        meta: '7 menit baca · 26 Mar 2026',
        accent: 'linear-gradient(145deg,#a78bfa_0%,#7c3aed_46%,#ec4899_100%)',
    },
    {
        category: 'Event',
        title: 'Event mingguan yang paling worth buat dikejar sekarang',
        excerpt: 'Daftar event aktif yang punya reward bagus dan progres paling cepat diselesaikan.',
        meta: '5 menit baca · 25 Mar 2026',
        accent: 'linear-gradient(145deg,#f97316_0%,#ef4444_46%,#7c2d12_100%)',
    },
    {
        category: 'Meta',
        title: 'Perubahan patch yang bikin komposisi tim ikut bergeser',
        excerpt: 'Patch kecil tapi efeknya besar ke prioritas pick, counter, dan rotasi awal match.',
        meta: '8 menit baca · 24 Mar 2026',
        accent: 'linear-gradient(145deg,#0f172a_0%,#1e3a8a_46%,#38bdf8_100%)',
    },
    {
        category: 'Tips',
        title: 'Checklist sebelum top up biar transaksi lebih aman',
        excerpt: 'Hal-hal sederhana yang sering dilewatkan saat checkout tapi penting buat mencegah salah input.',
        meta: '4 menit baca · 23 Mar 2026',
        accent: 'linear-gradient(145deg,#38bdf8_0%,#14b8a6_48%,#22c55e_100%)',
    },
    {
        category: 'Esports',
        title: 'Kenapa momentum scrim bisa ngaruh ke performa minggu ini',
        excerpt: 'Pola latihan dan kedalaman roster sering jadi pembeda tipis di papan atas.',
        meta: '6 menit baca · 22 Mar 2026',
        accent: 'linear-gradient(145deg,#facc15_0%,#fb7185_50%,#4f46e5_100%)',
    },
    {
        category: 'Editorial',
        title: 'Ekosistem top up makin cepat, tapi kenapa trust tetap nomor satu',
        excerpt: 'Opini singkat soal kenapa kecepatan transaksi tetap harus dibarengi validasi dan support yang jelas.',
        meta: '5 menit baca · 21 Mar 2026',
        accent: 'linear-gradient(145deg,#f472b6_0%,#c084fc_46%,#60a5fa_100%)',
    },
];

const categoryBlocks: CategoryBlock[] = [
    {
        title: 'Patch & Meta',
        description: 'Analisis perubahan patch, hero, item, dan komposisi yang lagi efektif.',
        count: '18 artikel',
        accent: 'from-sky-400/25 via-indigo-400/10 to-transparent',
    },
    {
        title: 'Promo & Voucher',
        description: 'Update promo aktif, timing voucher, dan value terbaik buat belanja game.',
        count: '12 artikel',
        accent: 'from-amber-400/25 via-orange-400/10 to-transparent',
    },
    {
        title: 'Esports & Ladder',
        description: 'Sorotan tim, pergerakan ranking, dan race menuju papan atas season ini.',
        count: '9 artikel',
        accent: 'from-emerald-400/25 via-teal-400/10 to-transparent',
    },
];
</script>

<template>
    <Head title="Artikel" />

    <PublicLayout active-nav="artikel">
        <main class="relative overflow-hidden px-4 py-8 sm:px-6 lg:px-8 lg:py-10">
            <div
                class="pointer-events-none absolute inset-x-0 top-0 h-80"
                style="background-image: radial-gradient(circle at top, rgba(67, 56, 202, 0.2), transparent 58%)"
            />

            <div class="pointer-events-none absolute inset-0">
                <div class="login-orb absolute left-[-5%] top-[8%] h-72 w-72 rounded-full bg-indigo-300/30 blur-3xl" />
                <div class="login-orb login-orb--alt absolute right-[-2%] top-[12%] h-80 w-80 rounded-full bg-sky-200/32 blur-3xl" />
                <div class="login-orb login-orb--soft absolute bottom-[8%] left-[10%] h-80 w-80 rounded-full bg-violet-200/20 blur-3xl" />
            </div>

            <section class="relative mx-auto max-w-7xl overflow-hidden rounded-[40px] border border-white/85 bg-[linear-gradient(180deg,rgba(255,255,255,0.95)_0%,rgba(244,247,255,0.96)_100%)] p-6 shadow-[0_36px_120px_rgba(99,102,241,0.12)] backdrop-blur-xl sm:p-8 lg:p-10">
                <div class="transaction-grid pointer-events-none absolute inset-0 opacity-70" />
                <div class="pointer-events-none absolute inset-x-0 top-0 h-40 bg-[radial-gradient(circle_at_top,rgba(67,56,202,0.16),transparent_62%)]" />

                <div class="relative z-10 grid gap-8 lg:grid-cols-[1.15fr,0.85fr] lg:gap-12">
                    <div>
                        <p class="text-[0.72rem] font-semibold uppercase tracking-[0.26em] text-indigo-600">Artikel</p>
                        <h1 class="mt-4 max-w-2xl text-4xl font-black tracking-tight text-slate-950 [font-family:'Space Grotesk',sans-serif] sm:text-5xl">
                            Update game, promo, dan insight yang enak dibaca cepat.
                        </h1>
                        <p class="mt-5 max-w-2xl text-sm leading-7 text-slate-600 sm:text-base">
                            Halaman editorial buat kamu yang mau pantau patch, event, voucher, sampai pergerakan kompetitif tanpa harus lompat ke banyak sumber.
                        </p>

                        <div class="mt-7 flex flex-wrap gap-2">
                            <span
                                v-for="chip in heroChips"
                                :key="chip.label"
                                class="rounded-full border border-slate-200 bg-white px-4 py-2 text-xs font-bold uppercase tracking-[0.14em] text-slate-700"
                            >
                                {{ chip.label }}
                            </span>
                        </div>

                        <div class="mt-8 overflow-hidden rounded-[34px] bg-slate-950 p-6 text-white shadow-[0_28px_70px_rgba(15,23,42,0.24)] sm:p-7">
                            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top,rgba(96,165,250,0.16),transparent_44%)]" />
                            <div class="leaderboard-sheen absolute inset-0 bg-[linear-gradient(135deg,transparent_0%,transparent_45%,rgba(255,255,255,0.12)_52%,transparent_60%,transparent_100%)]" />

                            <div class="relative z-10">
                                <div class="flex flex-wrap items-center gap-3">
                                    <span class="rounded-full border border-white/10 bg-white/10 px-3 py-1.5 text-[0.68rem] font-semibold uppercase tracking-[0.16em] text-white/80">
                                        Featured Story
                                    </span>
                                    <span class="rounded-full border border-amber-300/20 bg-amber-300/12 px-3 py-1.5 text-[0.68rem] font-semibold uppercase tracking-[0.16em] text-amber-100">
                                        8 menit baca
                                    </span>
                                </div>

                                <h2 class="mt-6 max-w-2xl text-3xl font-black tracking-tight [font-family:'Space Grotesk',sans-serif] sm:text-4xl">
                                    Patch baru bikin pola push rank berubah total. Ini yang wajib kamu perhatiin.
                                </h2>
                                <p class="mt-4 max-w-2xl text-sm leading-7 text-white/72 sm:text-base">
                                    Dari prioritas pick, tempo rotasi, sampai cara ngatur top up buat kebutuhan event, update kecil minggu ini ternyata punya efek yang besar ke permainan.
                                </p>

                                <div class="mt-7 flex flex-wrap gap-3">
                                    <a
                                        href="#"
                                        class="inline-flex h-[3.1rem] items-center justify-center rounded-[20px] bg-white px-6 text-sm font-bold text-slate-950 transition hover:bg-slate-100"
                                    >
                                        Baca artikel utama
                                    </a>
                                    <Link
                                        :href="route('leaderboard')"
                                        class="inline-flex h-[3.1rem] items-center justify-center rounded-[20px] border border-white/15 px-6 text-sm font-bold text-white/90 transition hover:bg-white/10"
                                    >
                                        Lihat leaderboard
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <p class="text-[0.68rem] font-semibold uppercase tracking-[0.22em] text-slate-500">Quick Read</p>
                                <h2 class="mt-2 text-2xl font-black tracking-tight text-slate-950 [font-family:'Space Grotesk',sans-serif]">Update cepat</h2>
                            </div>
                            <span class="rounded-full border border-slate-200 bg-white px-4 py-2 text-xs font-bold uppercase tracking-[0.14em] text-slate-700">
                                Editor Picks
                            </span>
                        </div>

                        <article
                            v-for="article in quickReads"
                            :key="article.title"
                            class="group relative overflow-hidden rounded-[28px] border border-white/80 bg-white/80 p-5 shadow-[0_18px_36px_rgba(15,23,42,0.05)] transition hover:-translate-y-1 hover:shadow-[0_24px_48px_rgba(15,23,42,0.08)]"
                        >
                            <div class="absolute inset-x-0 top-0 h-24 opacity-90" :style="{ backgroundImage: article.accent }" />
                            <div class="absolute inset-x-0 top-0 h-24 bg-[linear-gradient(180deg,rgba(255,255,255,0.15),transparent)]" />

                            <div class="relative z-10">
                                <div class="flex items-center justify-between gap-3">
                                    <span class="rounded-full border border-white/70 bg-white/90 px-3 py-1.5 text-[0.68rem] font-semibold uppercase tracking-[0.14em] text-slate-700">
                                        {{ article.category }}
                                    </span>
                                    <span class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-400">{{ article.meta }}</span>
                                </div>

                                <h3 class="mt-10 text-xl font-black tracking-tight text-slate-950 [font-family:'Space Grotesk',sans-serif]">
                                    {{ article.title }}
                                </h3>
                                <p class="mt-3 text-sm leading-6 text-slate-600">
                                    {{ article.excerpt }}
                                </p>

                                <a href="#" class="mt-5 inline-flex items-center gap-2 text-sm font-bold text-indigo-700 transition group-hover:text-indigo-600">
                                    Baca cepat
                                    <ArrowRight class="size-4" />
                                </a>
                            </div>
                        </article>
                    </div>
                </div>
            </section>

            <section class="relative mx-auto mt-8 grid max-w-7xl gap-6 lg:grid-cols-[1.08fr,0.92fr]">
                <section class="overflow-hidden rounded-[34px] border border-white/85 bg-white/78 shadow-[0_28px_70px_rgba(99,102,241,0.08)] backdrop-blur">
                    <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-200/80 px-6 py-5">
                        <div>
                            <p class="text-[0.68rem] font-semibold uppercase tracking-[0.24em] text-slate-500">Latest Stories</p>
                            <h2 class="mt-2 text-2xl font-black tracking-tight text-slate-950 [font-family:'Space Grotesk',sans-serif]">Artikel terbaru</h2>
                        </div>

                        <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-xs font-bold uppercase tracking-[0.14em] text-slate-700">
                            <LayoutGrid class="size-3.5" />
                            Fresh Update
                        </span>
                    </div>

                    <div class="grid gap-0 md:grid-cols-2">
                        <article
                            v-for="article in latestArticles"
                            :key="article.title"
                            class="group border-b border-r border-slate-200/70 p-6 transition last:border-r-0 md:nth-[2n]:border-r-0 hover:bg-slate-50/70"
                        >
                            <div class="h-1.5 w-20 rounded-full" :style="{ backgroundImage: article.accent }" />
                            <div class="mt-4 flex flex-wrap items-center gap-3 text-[0.68rem] font-semibold uppercase tracking-[0.16em] text-slate-500">
                                <span>{{ article.category }}</span>
                                <span>{{ article.meta }}</span>
                            </div>

                            <h3 class="mt-4 text-2xl font-black tracking-tight text-slate-950 [font-family:'Space Grotesk',sans-serif]">
                                {{ article.title }}
                            </h3>
                            <p class="mt-3 text-sm leading-7 text-slate-600">
                                {{ article.excerpt }}
                            </p>

                            <a href="#" class="mt-6 inline-flex items-center gap-2 text-sm font-bold text-indigo-700 transition group-hover:text-indigo-600">
                                Baca selengkapnya
                                <ArrowRight class="size-4" />
                            </a>
                        </article>
                    </div>
                </section>

                <section class="space-y-6">
                    <article class="overflow-hidden rounded-[34px] border border-white/85 bg-white/78 p-6 shadow-[0_28px_70px_rgba(99,102,241,0.08)] backdrop-blur">
                        <p class="text-[0.68rem] font-semibold uppercase tracking-[0.24em] text-slate-500">Kategori</p>
                        <h2 class="mt-2 text-2xl font-black tracking-tight text-slate-950 [font-family:'Space Grotesk',sans-serif]">Jalur baca yang paling sering dibuka</h2>

                        <div class="mt-6 space-y-4">
                            <article
                                v-for="category in categoryBlocks"
                                :key="category.title"
                                class="relative overflow-hidden rounded-[26px] border border-slate-200/80 bg-slate-50/80 p-5"
                            >
                                <div class="absolute inset-y-0 left-0 w-full bg-gradient-to-r opacity-70" :class="category.accent" />
                                <div class="relative z-10">
                                    <div class="flex items-center justify-between gap-4">
                                        <p class="text-sm font-black uppercase tracking-[0.08em] text-slate-950 [font-family:'Space Grotesk',sans-serif]">
                                            {{ category.title }}
                                        </p>
                                        <span class="rounded-full border border-white/70 bg-white/90 px-3 py-1.5 text-[0.68rem] font-semibold uppercase tracking-[0.14em] text-slate-700">
                                            {{ category.count }}
                                        </span>
                                    </div>
                                    <p class="mt-3 text-sm leading-6 text-slate-600">
                                        {{ category.description }}
                                    </p>
                                </div>
                            </article>
                        </div>
                    </article>

                    <article class="overflow-hidden rounded-[34px] border border-white/85 bg-slate-950 p-6 text-white shadow-[0_28px_70px_rgba(15,23,42,0.24)]">
                        <p class="text-[0.68rem] font-semibold uppercase tracking-[0.24em] text-white/45">Editorial Focus</p>
                        <h2 class="mt-2 text-2xl font-black tracking-tight [font-family:'Space Grotesk',sans-serif]">Baca cepat, ambil insight, lalu gas main lagi.</h2>
                        <p class="mt-4 text-sm leading-7 text-white/70">
                            Halaman artikel ini dirancang buat bantu kamu ambil keputusan lebih cepat: kapan top up, event mana yang worth, dan meta apa yang lagi efektif.
                        </p>

                        <div class="mt-6 grid gap-3 sm:grid-cols-2">
                            <div class="rounded-[22px] border border-white/10 bg-white/6 p-4">
                                <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-white/10 text-white">
                                    <BookOpenText class="size-4.5" />
                                </div>
                                <p class="mt-4 text-sm font-black uppercase tracking-[0.08em]">Ringkas</p>
                                <p class="mt-2 text-sm leading-6 text-white/70">Copy pendek dan gampang discan buat yang nggak mau baca terlalu panjang.</p>
                            </div>

                            <div class="rounded-[22px] border border-white/10 bg-white/6 p-4">
                                <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-white/10 text-white">
                                    <Clock3 class="size-4.5" />
                                </div>
                                <p class="mt-4 text-sm font-black uppercase tracking-[0.08em]">Aktual</p>
                                <p class="mt-2 text-sm leading-6 text-white/70">Cocok buat sorotan patch, promo aktif, atau gerakan leaderboard mingguan.</p>
                            </div>
                        </div>

                        <div class="mt-6 flex flex-wrap gap-3">
                            <Link
                                :href="route('home')"
                                class="inline-flex h-[3.1rem] items-center justify-center rounded-[20px] bg-white px-6 text-sm font-bold text-slate-950 transition hover:bg-slate-100"
                            >
                                Lanjut ke top up
                            </Link>
                            <Link
                                :href="route('leaderboard')"
                                class="inline-flex h-[3.1rem] items-center justify-center rounded-[20px] border border-white/15 px-6 text-sm font-bold text-white/90 transition hover:bg-white/10"
                            >
                                Buka leaderboard
                            </Link>
                        </div>
                    </article>
                </section>
            </section>

            <section class="relative mx-auto mt-8 grid max-w-7xl gap-6 lg:grid-cols-3">
                <article class="rounded-[30px] border border-white/80 bg-white/76 p-6 shadow-[0_18px_42px_rgba(15,23,42,0.05)]">
                    <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-700">
                        <Sparkles class="size-4.5" />
                    </span>
                    <h3 class="mt-5 text-sm font-black uppercase tracking-[0.08em] text-slate-950 [font-family:'Space Grotesk',sans-serif]">Editorial pendek</h3>
                    <p class="mt-3 text-sm leading-7 text-slate-600">Fokus ke insight yang cepat dipakai, bukan artikel yang muter-muter terlalu panjang.</p>
                </article>

                <article class="rounded-[30px] border border-white/80 bg-white/76 p-6 shadow-[0_18px_42px_rgba(15,23,42,0.05)]">
                    <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700">
                        <Trophy class="size-4.5" />
                    </span>
                    <h3 class="mt-5 text-sm font-black uppercase tracking-[0.08em] text-slate-950 [font-family:'Space Grotesk',sans-serif]">Terkait kompetitif</h3>
                    <p class="mt-3 text-sm leading-7 text-slate-600">Artikel dibuat nyambung dengan halaman leaderboard, event, dan dinamika musim yang sedang aktif.</p>
                </article>

                <article class="rounded-[30px] border border-white/80 bg-white/76 p-6 shadow-[0_18px_42px_rgba(15,23,42,0.05)]">
                    <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-sky-50 text-sky-700">
                        <Zap class="size-4.5" />
                    </span>
                    <h3 class="mt-5 text-sm font-black uppercase tracking-[0.08em] text-slate-950 [font-family:'Space Grotesk',sans-serif]">Nyambung ke transaksi</h3>
                    <p class="mt-3 text-sm leading-7 text-slate-600">Promo, voucher, dan tips top up tetap jadi bagian penting buat pemain yang ingin cari info cepat sebelum checkout.</p>
                </article>
            </section>
        </main>
    </PublicLayout>
</template>
