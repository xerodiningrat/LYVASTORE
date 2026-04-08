<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { AlertTriangle, RotateCcw, Search, ShieldAlert, ShieldCheck, Siren, WifiOff } from 'lucide-vue-next';
import { reactive } from 'vue';

const props = defineProps<{
    security: {
        totalEntries: number;
        warningEntries: number;
        criticalEntries: number;
        uniqueIps: number;
        latestTimestampLabel: string | null;
        filters: {
            level: string | null;
            event: string | null;
            ip: string | null;
            search: string | null;
        };
        availableLevels: string[];
        availableEvents: string[];
        topEvents: Array<{
            event: string;
            count: number;
        }>;
        topIps: Array<{
            ip: string;
            count: number;
        }>;
        recentEntries: Array<{
            timestamp: string;
            timestampLabel: string;
            level: string;
            event: string;
            eventLabel: string;
            context: Record<string, string | number | null>;
        }>;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard Admin', href: '/dashboard' },
    { title: 'Security Monitor', href: '/dashboard/security' },
];

const filters = reactive({
    level: props.security.filters.level ?? '',
    event: props.security.filters.event ?? '',
    ip: props.security.filters.ip ?? '',
    search: props.security.filters.search ?? '',
});

const submitFilters = () => {
    router.get(
        '/dashboard/security',
        {
            level: filters.level || undefined,
            event: filters.event || undefined,
            ip: filters.ip || undefined,
            search: filters.search || undefined,
        },
        {
            preserveScroll: true,
            replace: true,
        },
    );
};

const resetFilters = () => {
    filters.level = '';
    filters.event = '';
    filters.ip = '';
    filters.search = '';
    submitFilters();
};

const levelClass = (level: string) => {
    if (level === 'warning') {
        return 'border-amber-200 bg-amber-50 text-amber-700';
    }

    if (level === 'error' || level === 'critical') {
        return 'border-rose-200 bg-rose-50 text-rose-700';
    }

    return 'border-emerald-200 bg-emerald-50 text-emerald-700';
};
</script>

<template>
    <Head title="Security Monitor" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4">
            <section class="overflow-hidden rounded-[32px] border border-slate-200/80 bg-[radial-gradient(circle_at_top_left,_rgba(248,113,113,0.12),_transparent_34%),linear-gradient(180deg,_rgba(255,255,255,0.98),_rgba(248,250,252,0.98))] p-6 shadow-[0_24px_70px_rgba(15,23,42,0.07)] sm:p-7">
                <div class="flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
                    <div class="max-w-3xl">
                        <div class="inline-flex items-center rounded-full border border-rose-200 bg-rose-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.24em] text-rose-700">
                            Security Watch
                        </div>
                        <h1 class="mt-4 text-3xl font-semibold tracking-tight text-slate-950 sm:text-[2.55rem]">
                            Pantau spam, replay, manipulasi checkout, dan callback mencurigakan
                        </h1>
                        <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-600 sm:text-[15px]">
                            Halaman ini membaca event dari <span class="font-semibold text-slate-800">security.log</span> supaya tim admin bisa cepat melihat pola serangan atau request aneh tanpa buka file server manual.
                        </p>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-3 xl:min-w-[32rem]">
                        <article class="rounded-[24px] border border-white/80 bg-white/90 p-4 shadow-[0_14px_30px_rgba(15,23,42,0.05)]">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="text-[11px] uppercase tracking-[0.16em] text-slate-400">Total event</div>
                                    <div class="mt-2 text-2xl font-semibold tracking-tight text-slate-950">{{ security.totalEntries }}</div>
                                </div>
                                <div class="rounded-2xl bg-sky-50 p-3 text-sky-600">
                                    <ShieldCheck class="size-5" />
                                </div>
                            </div>
                        </article>

                        <article class="rounded-[24px] border border-white/80 bg-white/90 p-4 shadow-[0_14px_30px_rgba(15,23,42,0.05)]">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="text-[11px] uppercase tracking-[0.16em] text-slate-400">Warning</div>
                                    <div class="mt-2 text-2xl font-semibold tracking-tight text-slate-950">{{ security.warningEntries }}</div>
                                </div>
                                <div class="rounded-2xl bg-amber-50 p-3 text-amber-600">
                                    <AlertTriangle class="size-5" />
                                </div>
                            </div>
                        </article>

                        <article class="rounded-[24px] border border-white/80 bg-white/90 p-4 shadow-[0_14px_30px_rgba(15,23,42,0.05)]">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="text-[11px] uppercase tracking-[0.16em] text-slate-400">Critical / IP unik</div>
                                    <div class="mt-2 text-2xl font-semibold tracking-tight text-slate-950">{{ security.criticalEntries }} / {{ security.uniqueIps }}</div>
                                </div>
                                <div class="rounded-2xl bg-rose-50 p-3 text-rose-600">
                                    <WifiOff class="size-5" />
                                </div>
                            </div>
                        </article>
                    </div>
                </div>
            </section>

            <section class="rounded-[30px] border border-slate-200/80 bg-white p-5 shadow-[0_24px_70px_rgba(15,23,42,0.07)]">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <div class="text-[11px] uppercase tracking-[0.18em] text-slate-400">Filter & export</div>
                        <div class="mt-2 text-2xl font-semibold text-slate-950">Saring event penting tanpa buka server</div>
                        <p class="mt-2 text-sm leading-6 text-slate-500">
                            Log terbaru:
                            <span class="font-semibold text-slate-700">{{ security.latestTimestampLabel ?? 'belum ada data' }}</span>
                        </p>
                    </div>

                    <a
                        href="/dashboard/security/download"
                        class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800"
                    >
                        <ShieldAlert class="size-4" />
                        Unduh log terbaru
                    </a>
                </div>

                <form class="mt-6 grid gap-3 lg:grid-cols-[1.1fr,1.1fr,0.8fr,1.2fr,auto,auto]" @submit.prevent="submitFilters">
                    <label class="space-y-2">
                        <span class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Level</span>
                        <select v-model="filters.level" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-slate-400 focus:bg-white">
                            <option value="">Semua level</option>
                            <option v-for="level in security.availableLevels" :key="level" :value="level">
                                {{ level }}
                            </option>
                        </select>
                    </label>

                    <label class="space-y-2">
                        <span class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Event</span>
                        <select v-model="filters.event" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-slate-400 focus:bg-white">
                            <option value="">Semua event</option>
                            <option v-for="event in security.availableEvents" :key="event" :value="event">
                                {{ event }}
                            </option>
                        </select>
                    </label>

                    <label class="space-y-2">
                        <span class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">IP</span>
                        <input v-model="filters.ip" type="text" placeholder="103.12..." class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-slate-400 focus:bg-white" />
                    </label>

                    <label class="space-y-2">
                        <span class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Cari konteks</span>
                        <div class="flex items-center gap-2 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 transition focus-within:border-slate-400 focus-within:bg-white">
                            <Search class="size-4 text-slate-400" />
                            <input v-model="filters.search" type="text" placeholder="public_id, path, pesan..." class="w-full bg-transparent text-sm text-slate-800 outline-none" />
                        </div>
                    </label>

                    <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-slate-950 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
                        <Search class="size-4" />
                        Terapkan
                    </button>

                    <button type="button" class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:text-slate-950" @click="resetFilters">
                        <RotateCcw class="size-4" />
                        Reset
                    </button>
                </form>
            </section>

            <div class="grid gap-6 xl:grid-cols-[0.95fr,1.05fr]">
                <section class="rounded-[30px] border border-slate-200/80 bg-white p-5 shadow-[0_24px_70px_rgba(15,23,42,0.07)]">
                    <div class="text-[11px] uppercase tracking-[0.18em] text-slate-400">Top event</div>
                    <div class="mt-2 text-2xl font-semibold text-slate-950">Jenis kejadian paling sering</div>

                    <div v-if="!security.topEvents.length" class="mt-6 rounded-[24px] border border-emerald-200 bg-emerald-50/90 px-5 py-5 text-sm font-medium text-emerald-700">
                        Belum ada event keamanan yang tercatat.
                    </div>

                    <div v-else class="mt-6 space-y-3">
                        <article
                            v-for="item in security.topEvents"
                            :key="item.event"
                            class="flex items-center justify-between rounded-[22px] border border-slate-200/80 bg-slate-50/80 px-4 py-4"
                        >
                            <div class="min-w-0">
                                <div class="text-sm font-semibold text-slate-950">{{ item.event.replaceAll('_', ' ') }}</div>
                                <div class="mt-1 text-xs uppercase tracking-[0.16em] text-slate-400">{{ item.event }}</div>
                            </div>
                            <span class="inline-flex rounded-full bg-slate-900 px-3 py-1 text-xs font-bold text-white">{{ item.count }}</span>
                        </article>
                    </div>
                </section>

                <section class="rounded-[30px] border border-slate-200/80 bg-white p-5 shadow-[0_24px_70px_rgba(15,23,42,0.07)]">
                    <div class="text-[11px] uppercase tracking-[0.18em] text-slate-400">Top IP</div>
                    <div class="mt-2 text-2xl font-semibold text-slate-950">Alamat IP yang paling sering muncul</div>

                    <div v-if="!security.topIps.length" class="mt-6 rounded-[24px] border border-emerald-200 bg-emerald-50/90 px-5 py-5 text-sm font-medium text-emerald-700">
                        Belum ada alamat IP yang tercatat di security log.
                    </div>

                    <div v-else class="mt-6 space-y-3">
                        <article
                            v-for="item in security.topIps"
                            :key="item.ip"
                            class="flex items-center justify-between rounded-[22px] border border-slate-200/80 bg-slate-50/80 px-4 py-4"
                        >
                            <div class="min-w-0">
                                <div class="text-sm font-semibold text-slate-950">{{ item.ip }}</div>
                                <div class="mt-1 text-xs uppercase tracking-[0.16em] text-slate-400">request mencurigakan</div>
                            </div>
                            <span class="inline-flex rounded-full bg-rose-600 px-3 py-1 text-xs font-bold text-white">{{ item.count }}</span>
                        </article>
                    </div>
                </section>
            </div>

            <section class="rounded-[30px] border border-slate-200/80 bg-white p-5 shadow-[0_24px_70px_rgba(15,23,42,0.07)]">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <div class="text-[11px] uppercase tracking-[0.18em] text-slate-400">Recent log</div>
                        <div class="mt-2 text-2xl font-semibold text-slate-950">Event keamanan terbaru</div>
                        <p class="mt-2 text-sm leading-6 text-slate-500">
                            Diambil langsung dari file log keamanan harian terbaru di server.
                        </p>
                    </div>
                    <div class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-sm font-semibold text-slate-700">
                        <Siren class="size-4" />
                        {{ security.recentEntries.length }} event terbaru
                    </div>
                </div>

                <div v-if="!security.recentEntries.length" class="mt-6 rounded-[24px] border border-emerald-200 bg-emerald-50/90 px-5 py-5 text-sm font-medium text-emerald-700">
                    Security log masih kosong. Belum ada event yang perlu diaudit.
                </div>

                <div v-else class="mt-6 space-y-4">
                    <article
                        v-for="entry in security.recentEntries"
                        :key="`${entry.timestamp}-${entry.event}-${entry.context.ip ?? 'na'}`"
                        class="rounded-[24px] border border-slate-200/80 bg-slate-50/75 px-5 py-4 shadow-[0_16px_34px_rgba(148,163,184,0.08)]"
                    >
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <div class="text-lg font-semibold tracking-[-0.02em] text-slate-950">{{ entry.eventLabel }}</div>
                                    <span class="inline-flex rounded-full border px-3 py-1 text-xs font-semibold" :class="levelClass(entry.level)">
                                        {{ entry.level }}
                                    </span>
                                </div>
                                <div class="mt-1 text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">{{ entry.event }}</div>
                                <p class="mt-3 text-sm leading-6 text-slate-600">
                                    {{ entry.timestampLabel }}
                                    <span v-if="entry.context.ip"> • IP {{ entry.context.ip }}</span>
                                    <span v-if="entry.context.path"> • {{ entry.context.path }}</span>
                                </p>
                            </div>
                            <div class="min-w-0 text-sm text-slate-500 lg:max-w-[28rem]">
                                <div v-if="entry.context.public_id"><span class="font-semibold text-slate-700">Order:</span> {{ entry.context.public_id }}</div>
                                <div v-if="entry.context.product_id"><span class="font-semibold text-slate-700">Produk:</span> {{ entry.context.product_id }}</div>
                                <div v-if="entry.context.message"><span class="font-semibold text-slate-700">Pesan:</span> {{ entry.context.message }}</div>
                                <div v-if="entry.context.expected_total || entry.context.submitted_total">
                                    <span class="font-semibold text-slate-700">Nominal:</span>
                                    {{ entry.context.expected_total ?? '-' }} / {{ entry.context.submitted_total ?? entry.context.reported_amount ?? '-' }}
                                </div>
                            </div>
                        </div>
                    </article>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
