<script setup lang="ts">
import { Button } from '@/components/ui/button';
import AdminLayout from '@/layouts/AdminLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowUpRight, CheckCircle2, Clock3, Copy, Mail, MessageCircle, PackageCheck } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{
    initialFilter?: 'all' | 'invite-queue' | 'manual-action' | 'completed';
    status?: string;
    stats: {
        paidToday: number;
        completedToday: number;
        pendingCount: number;
        failedCount: number;
    };
    transactions: Array<{
        publicId: string;
        customerName: string;
        customerWhatsapp: string;
        customerEmail: string;
        productName: string;
        packageLabel: string;
        status: string;
        paymentStatus: string;
        paymentLabel: string;
        productSource: string;
        manualCategory: string | null;
        manualTargetEmail: string | null;
        manualFulfillmentStatus: string | null;
        total: number;
        updatedAtLabel: string;
        checkoutUrl: string;
        completeManualUrl: string;
        markManualPaidUrl: string;
        canCompleteManual: boolean;
        canMarkManualPaid: boolean;
        manualActionLabel: string;
        manualReplyChatUrl: string | null;
        manualPreviewMessage: string | null;
        manualStock: null | {
            id: number;
            label: string;
            value: string;
            notes: string | null;
            status: string;
            reservedAtLabel: string | null;
        };
    }>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard Admin', href: '/dashboard' },
    { title: 'Monitor Transaksi', href: '/dashboard/transaksi' },
];

const number = new Intl.NumberFormat('id-ID');
const formatCurrency = (value: number) => `Rp ${number.format(value)}`;
const copiedId = ref<string | null>(null);
const completingId = ref<string | null>(null);
const markingPaidId = ref<string | null>(null);
const activeFilter = ref<'all' | 'invite-queue' | 'manual-action' | 'completed'>(props.initialFilter ?? 'all');

const statusClass = (status: string) =>
    ({
        completed: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        processing: 'bg-sky-50 text-sky-700 ring-sky-200',
        pending: 'bg-amber-50 text-amber-700 ring-amber-200',
        failed: 'bg-rose-50 text-rose-700 ring-rose-200',
        expired: 'bg-slate-100 text-slate-600 ring-slate-200',
    })[status] ?? 'bg-slate-100 text-slate-700 ring-slate-200';

const productSourceClass = (source: string) =>
    ({
        vipayment: 'bg-violet-50 text-violet-700 ring-violet-200',
        manual: 'bg-slate-100 text-slate-700 ring-slate-200',
        'manual-stock': 'bg-cyan-50 text-cyan-700 ring-cyan-200',
    })[source] ?? 'bg-slate-100 text-slate-700 ring-slate-200';

const manualStatusLabel = (status: string | null) =>
    ({
        'waiting-stock': 'Menunggu stok',
        'ready-to-send': 'Siap dikirim',
        sent: 'Sudah dikirim',
    })[status ?? ''] ?? 'Bukan order manual';

const manualCategoryLabel = (category: string | null) =>
    ({
        invite: 'Invite workspace',
        'private-account': 'Private account',
        'manual-stock': 'Stok manual',
    })[category ?? ''] ?? 'Order manual';

const filterOptions = computed(() => [
    {
        id: 'all' as const,
        label: 'Semua transaksi',
        count: props.transactions.length,
    },
    {
        id: 'invite-queue' as const,
        label: 'Queue invite workspace',
        count: props.transactions.filter(
            (transaction) =>
                transaction.productSource === 'manual-stock' && transaction.manualCategory === 'invite' && transaction.status !== 'completed',
        ).length,
    },
    {
        id: 'manual-action' as const,
        label: 'Perlu aksi admin',
        count: props.transactions.filter((transaction) => transaction.productSource === 'manual-stock' && transaction.canCompleteManual).length,
    },
    {
        id: 'completed' as const,
        label: 'Sudah selesai',
        count: props.transactions.filter((transaction) => transaction.status === 'completed').length,
    },
]);

const transactionPriority = (transaction: (typeof props.transactions)[number]) => {
    if (transaction.productSource === 'manual-stock' && transaction.manualCategory === 'invite' && transaction.status !== 'completed') {
        return 0;
    }

    if (transaction.productSource === 'manual-stock' && transaction.canCompleteManual && transaction.status !== 'completed') {
        return 1;
    }

    if (transaction.productSource === 'manual-stock' && transaction.status !== 'completed') {
        return 2;
    }

    if (transaction.status === 'processing') {
        return 3;
    }

    if (transaction.status === 'pending') {
        return 4;
    }

    if (transaction.status === 'completed') {
        return 6;
    }

    return 5;
};

const filteredTransactions = computed(() => {
    let items = props.transactions;

    if (activeFilter.value === 'invite-queue') {
        items = props.transactions.filter(
            (transaction) =>
                transaction.productSource === 'manual-stock' && transaction.manualCategory === 'invite' && transaction.status !== 'completed',
        );
    } else if (activeFilter.value === 'manual-action') {
        items = props.transactions.filter((transaction) => transaction.productSource === 'manual-stock' && transaction.canCompleteManual);
    } else if (activeFilter.value === 'completed') {
        items = props.transactions.filter((transaction) => transaction.status === 'completed');
    }

    return [...items].sort((left, right) => {
        const priorityDiff = transactionPriority(left) - transactionPriority(right);

        if (priorityDiff !== 0) {
            return priorityDiff;
        }

        return props.transactions.indexOf(left) - props.transactions.indexOf(right);
    });
});

const completeManualOrder = (transactionId: string, url: string) => {
    completingId.value = transactionId;

    router.post(
        url,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                if (completingId.value === transactionId) {
                    completingId.value = null;
                }
            },
        },
    );
};

const markManualPaid = (transactionId: string, url: string) => {
    markingPaidId.value = transactionId;

    router.post(
        url,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                if (markingPaidId.value === transactionId) {
                    markingPaidId.value = null;
                }
            },
        },
    );
};

const copyManualPreviewMessage = async (transactionId: string, value: string | null) => {
    if (!value) {
        return;
    }

    try {
        await navigator.clipboard.writeText(value);
        copiedId.value = `preview-${transactionId}`;

        window.setTimeout(() => {
            if (copiedId.value === `preview-${transactionId}`) {
                copiedId.value = null;
            }
        }, 1800);
    } catch (error) {
        console.error(error);
    }
};

const copyStock = async (itemId: number, value: string) => {
    try {
        await navigator.clipboard.writeText(value);
        copiedId.value = `stock-${itemId}`;

        window.setTimeout(() => {
            if (copiedId.value === `stock-${itemId}`) {
                copiedId.value = null;
            }
        }, 1800);
    } catch (error) {
        console.error(error);
    }
};

const copyTargetEmail = async (transactionId: string, value: string | null) => {
    if (!value) {
        return;
    }

    try {
        await navigator.clipboard.writeText(value);
        copiedId.value = `email-${transactionId}`;

        window.setTimeout(() => {
            if (copiedId.value === `email-${transactionId}`) {
                copiedId.value = null;
            }
        }, 1800);
    } catch (error) {
        console.error(error);
    }
};

const copyInviteBrief = async (transactionId: string, email: string | null, packageLabel: string) => {
    if (!email) {
        return;
    }

    try {
        await navigator.clipboard.writeText(`Email invite: ${email}\nPaket: ${packageLabel}`);
        copiedId.value = `brief-${transactionId}`;

        window.setTimeout(() => {
            if (copiedId.value === `brief-${transactionId}`) {
                copiedId.value = null;
            }
        }, 1800);
    } catch (error) {
        console.error(error);
    }
};
</script>

<template>
    <Head title="Monitor Transaksi" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4">
            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <article class="rounded-[26px] border border-slate-200/80 bg-white p-5 shadow-[0_20px_65px_rgba(15,23,42,0.06)]">
                    <div class="text-[11px] uppercase tracking-[0.18em] text-slate-400">Pembayaran hari ini</div>
                    <div class="mt-3 text-2xl font-semibold text-slate-950">{{ formatCurrency(stats.paidToday) }}</div>
                </article>
                <article class="rounded-[26px] border border-slate-200/80 bg-white p-5 shadow-[0_20px_65px_rgba(15,23,42,0.06)]">
                    <div class="text-[11px] uppercase tracking-[0.18em] text-slate-400">Selesai hari ini</div>
                    <div class="mt-3 text-2xl font-semibold text-slate-950">{{ number.format(stats.completedToday) }}</div>
                </article>
                <article class="rounded-[26px] border border-slate-200/80 bg-white p-5 shadow-[0_20px_65px_rgba(15,23,42,0.06)]">
                    <div class="text-[11px] uppercase tracking-[0.18em] text-slate-400">Pending / proses</div>
                    <div class="mt-3 text-2xl font-semibold text-slate-950">{{ number.format(stats.pendingCount) }}</div>
                </article>
                <article class="rounded-[26px] border border-slate-200/80 bg-white p-5 shadow-[0_20px_65px_rgba(15,23,42,0.06)]">
                    <div class="text-[11px] uppercase tracking-[0.18em] text-slate-400">Gagal / expired</div>
                    <div class="mt-3 text-2xl font-semibold text-slate-950">{{ number.format(stats.failedCount) }}</div>
                </article>
            </section>

            <section class="rounded-[30px] border border-slate-200/80 bg-white p-5 shadow-[0_24px_70px_rgba(15,23,42,0.07)]">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <div class="text-[11px] uppercase tracking-[0.18em] text-slate-400">Monitor transaksi</div>
                        <div class="mt-2 text-2xl font-semibold text-slate-950">Transaksi terbaru dari seluruh sistem</div>
                        <p class="mt-2 text-sm leading-6 text-slate-500">
                            Order manual seperti ChatGPT atau CapCut akan menampilkan stok yang sudah disiapkan. Kalau datanya sudah siap, admin bisa
                            langsung kirim info akun ke customer atau buka reply chat lalu menandai pesanan selesai dari sini.
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <Link
                            href="/dashboard/transaksi/invite-workspace"
                            class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-700 hover:bg-emerald-100"
                        >
                            Buka queue invite
                            <CheckCircle2 class="size-4" />
                        </Link>
                        <Link
                            href="/dashboard/stok-manual"
                            class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100"
                        >
                            Kelola stok manual
                            <PackageCheck class="size-4" />
                        </Link>
                    </div>
                </div>

                <div
                    v-if="status"
                    class="mt-5 rounded-[24px] border border-emerald-200 bg-emerald-50/90 px-5 py-4 text-sm font-medium text-emerald-700"
                >
                    {{ status }}
                </div>

                <div class="mt-5 flex flex-wrap gap-3">
                    <button
                        v-for="filter in filterOptions"
                        :key="filter.id"
                        type="button"
                        class="inline-flex items-center gap-2 rounded-full border px-4 py-2 text-sm font-semibold transition"
                        :class="
                            activeFilter === filter.id
                                ? 'border-slate-900 bg-slate-900 text-white'
                                : 'border-slate-200 bg-slate-50 text-slate-600 hover:bg-slate-100'
                        "
                        @click="activeFilter = filter.id"
                    >
                        {{ filter.label }}
                        <span
                            class="inline-flex min-w-7 items-center justify-center rounded-full px-2 py-0.5 text-xs"
                            :class="activeFilter === filter.id ? 'bg-white/15 text-white' : 'bg-white text-slate-500 ring-1 ring-slate-200'"
                        >
                            {{ number.format(filter.count) }}
                        </span>
                    </button>
                </div>

                <div class="mt-5 space-y-4">
                    <div
                        v-if="filteredTransactions.length === 0"
                        class="rounded-[24px] border border-dashed border-slate-200 bg-slate-50/80 px-5 py-8 text-center text-sm text-slate-500"
                    >
                        Tidak ada transaksi yang cocok dengan filter ini.
                    </div>
                    <article
                        v-for="transaction in filteredTransactions"
                        :key="transaction.publicId"
                        class="rounded-[26px] border border-slate-200/80 bg-slate-50/75 p-4 shadow-[0_16px_34px_rgba(148,163,184,0.08)]"
                    >
                        <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span
                                        class="inline-flex rounded-full px-3 py-1 text-xs font-semibold ring-1"
                                        :class="statusClass(transaction.status)"
                                    >
                                        {{ transaction.status }}
                                    </span>
                                    <span class="rounded-full bg-white px-3 py-1 text-xs font-medium text-slate-500 ring-1 ring-slate-200">
                                        {{ transaction.paymentStatus }}
                                    </span>
                                    <span
                                        class="inline-flex rounded-full px-3 py-1 text-xs font-semibold ring-1"
                                        :class="productSourceClass(transaction.productSource)"
                                    >
                                        {{ transaction.productSource }}
                                    </span>
                                    <span
                                        v-if="transaction.productSource === 'manual-stock'"
                                        class="rounded-full bg-white px-3 py-1 text-xs font-medium text-slate-500 ring-1 ring-slate-200"
                                    >
                                        {{ manualStatusLabel(transaction.manualFulfillmentStatus) }}
                                    </span>
                                    <span
                                        v-if="transaction.productSource === 'manual-stock'"
                                        class="rounded-full bg-white px-3 py-1 text-xs font-medium text-slate-500 ring-1 ring-slate-200"
                                    >
                                        {{ manualCategoryLabel(transaction.manualCategory) }}
                                    </span>
                                    <span class="text-xs uppercase tracking-[0.18em] text-slate-400">#{{ transaction.publicId }}</span>
                                </div>

                                <div class="mt-4 text-lg font-semibold tracking-[-0.02em] text-slate-950">{{ transaction.productName }}</div>
                                <div class="mt-1 text-sm text-slate-600">{{ transaction.packageLabel }}</div>

                                <div class="mt-4 flex flex-wrap gap-3 text-sm text-slate-600">
                                    <div class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-2">
                                        <MessageCircle class="size-4 text-slate-400" />
                                        {{ transaction.customerWhatsapp }}
                                    </div>
                                    <div class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-2">
                                        <Mail class="size-4 text-slate-400" />
                                        {{ transaction.customerEmail }}
                                    </div>
                                    <div class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-2">
                                        <Clock3 class="size-4 text-slate-400" />
                                        {{ transaction.updatedAtLabel }}
                                    </div>
                                </div>

                                <div
                                    v-if="transaction.productSource === 'manual-stock'"
                                    class="mt-5 rounded-[24px] border p-4"
                                    :class="transaction.manualStock ? 'border-sky-200 bg-sky-50/70' : 'border-amber-200 bg-amber-50/70'"
                                >
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <div>
                                            <div class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">
                                                {{ transaction.manualStock ? 'Stok yang disiapkan' : 'Menunggu stok' }}
                                            </div>
                                            <div class="mt-2 text-sm leading-6 text-slate-700">
                                                <template v-if="transaction.manualStock">
                                                    {{ transaction.manualStock.label }}
                                                    <span v-if="transaction.manualStock.reservedAtLabel">
                                                        • {{ transaction.manualStock.reservedAtLabel }}</span
                                                    >
                                                </template>
                                                <template v-else>
                                                    {{
                                                        transaction.canCompleteManual
                                                            ? 'Paket ini bisa dikirim manual tanpa stok akun. Setelah invite dikirim, tinggal klik tombol selesai.'
                                                            : 'Belum ada stok yang cocok. Tambahkan stok dari menu `Stok Manual`, nanti sistem otomatis siapkan untuk order ini.'
                                                    }}
                                                </template>
                                            </div>
                                            <div
                                                v-if="transaction.manualTargetEmail"
                                                class="mt-3 inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700"
                                            >
                                                <Mail class="size-4" />
                                                Email target invite: {{ transaction.manualTargetEmail }}
                                            </div>
                                            <button
                                                v-if="transaction.manualTargetEmail"
                                                type="button"
                                                class="mt-3 inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-100"
                                                @click="copyTargetEmail(transaction.publicId, transaction.manualTargetEmail)"
                                            >
                                                <Copy class="size-4" />
                                                {{ copiedId === `email-${transaction.publicId}` ? 'Email tersalin' : 'Copy email invite' }}
                                            </button>
                                        </div>

                                        <Button
                                            v-if="transaction.manualStock"
                                            type="button"
                                            variant="outline"
                                            class="rounded-full px-4"
                                            @click="copyStock(transaction.manualStock.id, transaction.manualStock.value)"
                                        >
                                            <Copy class="mr-2 size-4" />
                                            {{ copiedId === `stock-${transaction.manualStock.id}` ? 'Tersalin' : 'Copy stok' }}
                                        </Button>
                                    </div>

                                    <div v-if="transaction.manualStock" class="mt-4 rounded-[18px] border border-white/80 bg-white/85 p-4">
                                        <pre class="whitespace-pre-wrap break-all font-mono text-sm leading-6 text-slate-900">{{
                                            transaction.manualStock.value
                                        }}</pre>
                                        <div
                                            v-if="transaction.manualStock.notes"
                                            class="mt-3 rounded-[16px] border border-amber-100 bg-amber-50/80 px-3 py-2 text-sm text-amber-700"
                                        >
                                            {{ transaction.manualStock.notes }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex w-full shrink-0 flex-col gap-3 xl:w-[240px]">
                                <div class="rounded-[22px] border border-slate-200 bg-white px-4 py-4 text-right shadow-sm">
                                    <div class="text-xs uppercase tracking-[0.18em] text-slate-400">Total</div>
                                    <div class="mt-2 text-2xl font-semibold text-slate-950">{{ formatCurrency(transaction.total) }}</div>
                                    <div class="mt-1 text-sm text-slate-500">{{ transaction.customerName }} • {{ transaction.paymentLabel }}</div>
                                </div>

                                <div class="grid gap-3">
                                    <Link
                                        :href="transaction.checkoutUrl"
                                        class="inline-flex items-center justify-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-100"
                                    >
                                        Detail checkout
                                        <ArrowUpRight class="size-4" />
                                    </Link>

                                    <a
                                        v-if="transaction.manualReplyChatUrl"
                                        :href="transaction.manualReplyChatUrl"
                                        target="_blank"
                                        rel="noreferrer"
                                        class="inline-flex items-center justify-center gap-2 rounded-full border border-sky-200 bg-sky-50 px-4 py-3 text-sm font-semibold text-sky-700 hover:bg-sky-100"
                                    >
                                        Reply chat customer
                                        <MessageCircle class="size-4" />
                                    </a>

                                    <Button
                                        v-if="transaction.manualPreviewMessage"
                                        type="button"
                                        variant="outline"
                                        class="rounded-full px-4"
                                        @click="copyManualPreviewMessage(transaction.publicId, transaction.manualPreviewMessage)"
                                    >
                                        <Copy class="mr-2 size-4" />
                                        {{ copiedId === `preview-${transaction.publicId}` ? 'Pesan tersalin' : 'Copy template pesan' }}
                                    </Button>

                                    <Button
                                        v-if="transaction.manualTargetEmail"
                                        type="button"
                                        variant="outline"
                                        class="rounded-full px-4"
                                        @click="copyInviteBrief(transaction.publicId, transaction.manualTargetEmail, transaction.packageLabel)"
                                    >
                                        <Copy class="mr-2 size-4" />
                                        {{ copiedId === `brief-${transaction.publicId}` ? 'Ringkasan tersalin' : 'Copy email + paket' }}
                                    </Button>

                                    <Button
                                        v-if="transaction.canMarkManualPaid"
                                        type="button"
                                        variant="outline"
                                        class="rounded-full border-emerald-200 bg-emerald-50 px-4 text-emerald-700 hover:bg-emerald-100"
                                        :disabled="markingPaidId === transaction.publicId"
                                        @click="markManualPaid(transaction.publicId, transaction.markManualPaidUrl)"
                                    >
                                        <CheckCircle2 class="mr-2 size-4" />
                                        {{ markingPaidId === transaction.publicId ? 'Memproses...' : 'Tandai sudah dibayar' }}
                                    </Button>

                                    <Button
                                        v-if="transaction.productSource === 'manual-stock'"
                                        type="button"
                                        class="rounded-full px-4"
                                        :disabled="!transaction.canCompleteManual || completingId === transaction.publicId"
                                        @click="completeManualOrder(transaction.publicId, transaction.completeManualUrl)"
                                    >
                                        {{
                                            completingId === transaction.publicId
                                                ? 'Memproses...'
                                                : transaction.canCompleteManual
                                                  ? transaction.manualActionLabel
                                                  : 'Tunggu stok siap'
                                        }}
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </article>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
