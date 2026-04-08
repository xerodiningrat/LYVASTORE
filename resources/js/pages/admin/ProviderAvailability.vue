<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { AlertTriangle, PackageSearch, ShieldAlert } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    unavailableProducts: Array<{
        id: string;
        name: string;
        reason: string;
    }>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard Admin', href: '/dashboard' },
    { title: 'Provider Kosong', href: '/dashboard/provider-kosong' },
];

const unavailableCount = computed(() => props.unavailableProducts.length);
</script>

<template>
    <Head title="Provider Kosong" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4">
            <section class="overflow-hidden rounded-[32px] border border-slate-200/80 bg-[radial-gradient(circle_at_top_left,_rgba(251,191,36,0.14),_transparent_34%),linear-gradient(180deg,_rgba(255,255,255,0.98),_rgba(248,250,252,0.98))] p-6 shadow-[0_24px_70px_rgba(15,23,42,0.07)] sm:p-7">
                <div class="flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
                    <div class="max-w-3xl">
                        <div class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.24em] text-amber-700">
                            Provider Watch
                        </div>
                        <h1 class="mt-4 text-3xl font-semibold tracking-tight text-slate-950 sm:text-[2.55rem]">
                            Pantau produk yang layanan VIPPayment-nya sedang kosong
                        </h1>
                        <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-600 sm:text-[15px]">
                            Produk di daftar ini sekarang sudah disembunyikan dari area publik utama supaya user tidak masuk ke halaman kosong atau melihat status belum tersedia.
                        </p>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2 xl:min-w-[24rem]">
                        <article class="rounded-[24px] border border-white/80 bg-white/90 p-4 shadow-[0_14px_30px_rgba(15,23,42,0.05)]">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="text-[11px] uppercase tracking-[0.16em] text-slate-400">Produk kosong</div>
                                    <div class="mt-2 text-2xl font-semibold tracking-tight text-slate-950">{{ unavailableCount }}</div>
                                </div>
                                <div class="rounded-2xl bg-amber-50 p-3 text-amber-600">
                                    <AlertTriangle class="size-5" />
                                </div>
                            </div>
                        </article>

                        <article class="rounded-[24px] border border-white/80 bg-white/90 p-4 shadow-[0_14px_30px_rgba(15,23,42,0.05)]">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="text-[11px] uppercase tracking-[0.16em] text-slate-400">Status publik</div>
                                    <div class="mt-2 text-lg font-semibold tracking-tight text-slate-950">Sudah disembunyikan</div>
                                </div>
                                <div class="rounded-2xl bg-rose-50 p-3 text-rose-600">
                                    <ShieldAlert class="size-5" />
                                </div>
                            </div>
                        </article>
                    </div>
                </div>
            </section>

            <section class="rounded-[30px] border border-slate-200/80 bg-white p-5 shadow-[0_24px_70px_rgba(15,23,42,0.07)]">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <div class="text-[11px] uppercase tracking-[0.18em] text-slate-400">Daftar produk</div>
                        <div class="mt-2 text-2xl font-semibold text-slate-950">Produk VIP-backed yang sedang kosong</div>
                        <p class="mt-2 text-sm leading-6 text-slate-500">
                            Kalau provider mengembalikan layanan lagi nanti, produk akan bisa dimunculkan kembali setelah cache refresh.
                        </p>
                    </div>
                    <Link href="/dashboard/produk" class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                        Buka setting produk
                        <PackageSearch class="size-4" />
                    </Link>
                </div>

                <div v-if="!unavailableProducts.length" class="mt-6 rounded-[24px] border border-emerald-200 bg-emerald-50/90 px-5 py-5 text-sm font-medium text-emerald-700">
                    Tidak ada produk provider kosong saat ini. Semua produk VIP-backed yang dipantau sedang tersedia.
                </div>

                <div v-else class="mt-6 space-y-4">
                    <article
                        v-for="product in unavailableProducts"
                        :key="product.id"
                        class="rounded-[24px] border border-slate-200/80 bg-slate-50/75 px-5 py-4 shadow-[0_16px_34px_rgba(148,163,184,0.08)]"
                    >
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                            <div class="min-w-0">
                                <div class="text-lg font-semibold tracking-[-0.02em] text-slate-950">{{ product.name }}</div>
                                <div class="mt-1 text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">{{ product.id }}</div>
                                <p class="mt-3 text-sm leading-6 text-slate-600">{{ product.reason }}</p>
                            </div>
                            <span class="inline-flex rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700 ring-1 ring-amber-200">
                                Disembunyikan dari publik
                            </span>
                        </div>
                    </article>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
