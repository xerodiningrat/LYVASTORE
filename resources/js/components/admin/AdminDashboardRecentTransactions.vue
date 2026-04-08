<script setup lang="ts">
import { ArrowUpRight } from 'lucide-vue-next';
import { Link } from '@inertiajs/vue3';

defineProps<{
    recentTransactions: Array<Record<string, string | number>>;
}>();

const number = new Intl.NumberFormat('id-ID');
const formatCurrency = (value: number) => `Rp ${number.format(value)}`;

const statusClass = (status: string) =>
    ({
        completed: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        processing: 'bg-sky-50 text-sky-700 ring-sky-200',
        pending: 'bg-amber-50 text-amber-700 ring-amber-200',
        failed: 'bg-rose-50 text-rose-700 ring-rose-200',
        expired: 'bg-slate-100 text-slate-600 ring-slate-200',
    })[status] ?? 'bg-slate-100 text-slate-700 ring-slate-200';
</script>

<template>
    <section class="rounded-[30px] border border-slate-200/80 bg-white p-5 shadow-[0_24px_70px_rgba(15,23,42,0.07)]">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <div class="text-[11px] uppercase tracking-[0.18em] text-slate-400">Aktivitas terbaru</div>
                <div class="mt-2 text-2xl font-semibold tracking-tight text-slate-950">Transaksi yang baru bergerak terakhir</div>
            </div>
            <Link href="/dashboard/transaksi" class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100">
                Buka monitor lengkap
                <ArrowUpRight class="size-4" />
            </Link>
        </div>

        <div class="mt-5 space-y-3">
            <article
                v-for="transaction in recentTransactions"
                :key="String(transaction.publicId)"
                class="flex flex-col gap-4 rounded-[26px] border border-slate-200/80 bg-slate-50/80 p-4 lg:flex-row lg:items-center lg:justify-between"
            >
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold ring-1" :class="statusClass(String(transaction.status ?? 'pending'))">
                            {{ transaction.statusLabel }}
                        </span>
                        <span class="rounded-full bg-white px-3 py-1 text-xs font-medium text-slate-500 ring-1 ring-slate-200">
                            {{ transaction.paymentStatusLabel }}
                        </span>
                        <span class="rounded-full bg-white px-3 py-1 text-xs font-medium text-slate-500 ring-1 ring-slate-200">
                            {{ transaction.sourceLabel }}
                        </span>
                        <span class="text-xs uppercase tracking-[0.18em] text-slate-400">#{{ transaction.publicId }}</span>
                    </div>
                    <div class="mt-3 text-base font-semibold text-slate-950">{{ transaction.productName }}</div>
                    <div class="text-sm text-slate-600">{{ transaction.packageLabel }}</div>
                    <div class="mt-2 text-xs text-slate-500">
                        {{ transaction.customerName }} • {{ transaction.updatedAtLabel }}
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <div class="text-right">
                        <div class="text-xl font-semibold tracking-tight text-slate-950">{{ formatCurrency(Number(transaction.total ?? 0)) }}</div>
                    </div>
                    <Link :href="String(transaction.checkoutUrl ?? '#')" class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100">
                        Detail
                        <ArrowUpRight class="size-4" />
                    </Link>
                </div>
            </article>
        </div>
    </section>
</template>
