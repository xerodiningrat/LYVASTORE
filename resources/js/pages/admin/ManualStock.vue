<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AdminLayout from '@/layouts/AdminLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { Boxes, CheckCircle2, Clock3, Copy, MailPlus, PackagePlus, Send } from 'lucide-vue-next';
import { ref, watch } from 'vue';

const props = defineProps<{
    status?: string;
    productOptions: Array<{
        id: string;
        name: string;
        packageSuggestions?: string[];
    }>;
    stats: {
        availableCount: number;
        reservedCount: number;
        usedCount: number;
        waitingOrders: number;
    };
    items: Array<{
        id: number;
        productId: string;
        productName: string;
        packageLabel: string;
        stockLabel: string | null;
        stockValue: string;
        notes: string | null;
        status: string;
        reservedForPublicId: string | null;
        createdAtLabel: string;
        reservedAtLabel: string | null;
        usedAtLabel: string | null;
    }>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard Admin', href: '/dashboard' },
    { title: 'Stok Manual', href: '/dashboard/stok-manual' },
];

const copiedId = ref<number | null>(null);
const selectedProductOption = ref<(typeof props.productOptions)[number] | null>(props.productOptions[0] ?? null);

const form = useForm({
    product_id: props.productOptions[0]?.id ?? 'vip-game-chatgpt',
    product_name: props.productOptions[0]?.name ?? 'ChatGPT',
    package_label: '',
    stock_label: 'Akun / kode',
    stock_values: '',
    notes: '',
});

watch(
    () => form.product_id,
    (value) => {
        const selected = props.productOptions.find((option) => option.id === value);

        if (selected) {
            selectedProductOption.value = selected;
            form.product_name = selected.name;

            if (!form.package_label.trim() && selected.packageSuggestions?.length) {
                form.package_label = selected.packageSuggestions[0];
            }
        } else {
            selectedProductOption.value = null;
        }
    },
    { immediate: true },
);

const applyPackageSuggestion = (label: string) => {
    form.package_label = label;
};

const submit = () => {
    form.post('/dashboard/stok-manual', {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('package_label', 'stock_values', 'notes');
        },
    });
};

const copyStock = async (itemId: number, value: string) => {
    try {
        await navigator.clipboard.writeText(value);
        copiedId.value = itemId;

        window.setTimeout(() => {
            if (copiedId.value === itemId) {
                copiedId.value = null;
            }
        }, 1800);
    } catch (error) {
        console.error(error);
    }
};

const stockStatusClass = (status: string) =>
    ({
        available: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        reserved: 'bg-sky-50 text-sky-700 ring-sky-200',
        used: 'bg-slate-100 text-slate-600 ring-slate-200',
    })[status] ?? 'bg-slate-100 text-slate-700 ring-slate-200';
</script>

<template>
    <Head title="Stok Manual" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4">
            <section
                class="overflow-hidden rounded-[34px] border border-slate-200/80 bg-[radial-gradient(circle_at_top_left,rgba(96,165,250,0.14),transparent_30%),linear-gradient(180deg,rgba(255,255,255,0.98),rgba(248,250,252,0.96))] p-6 shadow-[0_28px_80px_rgba(15,23,42,0.08)] lg:p-8"
            >
                <div class="grid gap-6 xl:grid-cols-[minmax(0,1.05fr)_minmax(320px,0.95fr)]">
                    <div>
                        <div class="text-[11px] font-semibold uppercase tracking-[0.28em] text-sky-500">Manual Stock Desk</div>
                        <h1 class="mt-3 max-w-3xl text-3xl font-semibold tracking-[-0.04em] text-slate-950 lg:text-[2.65rem]">
                            Kelola stok manual untuk ChatGPT, CapCut, dan produk akun digital lainnya.
                        </h1>
                        <p class="mt-4 max-w-2xl text-sm leading-7 text-slate-600">
                            Tambahkan stok sendiri ke database Lyva, lalu sistem akan otomatis menyiapkan stok untuk pesanan berbayar dan memberi tahu admin lewat
                            Telegram serta email.
                        </p>

                        <div class="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                            <div class="rounded-[24px] border border-emerald-100 bg-emerald-50/80 p-4 shadow-[0_14px_34px_rgba(16,185,129,0.08)]">
                                <div class="text-[11px] font-semibold uppercase tracking-[0.2em] text-emerald-500">Stok Tersedia</div>
                                <div class="mt-2 text-3xl font-semibold text-emerald-700">{{ stats.availableCount }}</div>
                            </div>
                            <div class="rounded-[24px] border border-sky-100 bg-sky-50/80 p-4 shadow-[0_14px_34px_rgba(59,130,246,0.08)]">
                                <div class="text-[11px] font-semibold uppercase tracking-[0.2em] text-sky-500">Siap Dikirim</div>
                                <div class="mt-2 text-3xl font-semibold text-sky-700">{{ stats.reservedCount }}</div>
                            </div>
                            <div class="rounded-[24px] border border-slate-200 bg-slate-50/90 p-4 shadow-[0_14px_34px_rgba(148,163,184,0.08)]">
                                <div class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-400">Sudah Dipakai</div>
                                <div class="mt-2 text-3xl font-semibold text-slate-950">{{ stats.usedCount }}</div>
                            </div>
                            <div class="rounded-[24px] border border-amber-100 bg-amber-50/80 p-4 shadow-[0_14px_34px_rgba(245,158,11,0.08)]">
                                <div class="text-[11px] font-semibold uppercase tracking-[0.2em] text-amber-500">Order Menunggu</div>
                                <div class="mt-2 text-3xl font-semibold text-amber-700">{{ stats.waitingOrders }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-[30px] border border-slate-200/80 bg-white/80 p-5 shadow-[0_18px_44px_rgba(148,163,184,0.12)] backdrop-blur-sm">
                        <div class="flex items-start gap-3">
                            <div class="flex size-11 items-center justify-center rounded-[20px] bg-slate-100 text-slate-700">
                                <Boxes class="size-5" />
                            </div>
                            <div>
                                <div class="text-base font-semibold text-slate-950">Flow admin jadi lebih cepat</div>
                                <p class="mt-1 text-sm leading-6 text-slate-600">
                                    Begitu order manual dibayar, sistem langsung menaruhnya ke antrean admin. Kalau stok cocok sudah ada, order otomatis siap dikirim.
                                </p>
                            </div>
                        </div>

                        <div class="mt-5 space-y-3">
                            <div class="flex items-start gap-3 rounded-[22px] border border-slate-200 bg-slate-50/80 p-4">
                                <div class="mt-0.5 flex size-9 items-center justify-center rounded-full bg-white text-slate-700 shadow-sm">
                                    <MailPlus class="size-4" />
                                </div>
                                <div>
                                    <div class="text-sm font-semibold text-slate-950">Notifikasi admin</div>
                                    <p class="mt-1 text-sm leading-6 text-slate-500">Order baru dikirim ke Telegram bot dan email admin supaya kamu tidak kelewatan pesanan.</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3 rounded-[22px] border border-slate-200 bg-slate-50/80 p-4">
                                <div class="mt-0.5 flex size-9 items-center justify-center rounded-full bg-white text-slate-700 shadow-sm">
                                    <Send class="size-4" />
                                </div>
                                <div>
                                    <div class="text-sm font-semibold text-slate-950">Klik selesai setelah kirim</div>
                                    <p class="mt-1 text-sm leading-6 text-slate-500">
                                        Setelah akun atau kode kamu kirim ke customer, tinggal klik tombol selesai di monitor transaksi.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="grid gap-6 xl:grid-cols-[minmax(340px,0.92fr)_minmax(0,1.08fr)]">
                <section class="rounded-[30px] border border-slate-200/80 bg-white p-6 shadow-[0_24px_70px_rgba(15,23,42,0.07)]">
                    <div class="text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-400">Tambah Stok Baru</div>
                    <h2 class="mt-2 text-2xl font-semibold tracking-tight text-slate-950">Input stok manual ke database Lyva</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">
                        Satu baris mewakili satu stok. Cocok untuk akun, voucher, invite code, atau credentials digital lain.
                    </p>

                    <div v-if="status" class="mt-5 rounded-[22px] border border-emerald-200 bg-emerald-50/90 px-4 py-3 text-sm font-medium text-emerald-700">
                        {{ status }}
                    </div>

                    <div class="mt-5 space-y-4">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Produk</label>
                            <Input v-model="form.product_id" list="manual-stock-products" class="h-12 rounded-2xl border-slate-200 text-[15px]" placeholder="Pilih atau ketik product ID" />
                            <datalist id="manual-stock-products">
                                <option v-for="option in productOptions" :key="option.id" :value="option.id">{{ option.name }}</option>
                            </datalist>
                            <InputError class="mt-2" :message="form.errors.product_id" />
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Nama produk</label>
                            <Input v-model="form.product_name" class="h-12 rounded-2xl border-slate-200 text-[15px]" placeholder="ChatGPT / CapCut Pro / lainnya" />
                            <InputError class="mt-2" :message="form.errors.product_name" />
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Label paket</label>
                            <Input v-model="form.package_label" class="h-12 rounded-2xl border-slate-200 text-[15px]" placeholder="Contoh: ChatGPT Plus 1 Bulan" />
                            <div v-if="selectedProductOption?.packageSuggestions?.length" class="mt-3 flex flex-wrap gap-2">
                                <button
                                    v-for="suggestion in selectedProductOption.packageSuggestions"
                                    :key="`${selectedProductOption.id}-${suggestion}`"
                                    type="button"
                                    class="inline-flex items-center rounded-full border border-sky-200 bg-sky-50 px-3.5 py-1.5 text-xs font-semibold text-sky-700 transition hover:border-sky-300 hover:bg-sky-100"
                                    @click="applyPackageSuggestion(suggestion)"
                                >
                                    {{ suggestion }}
                                </button>
                            </div>
                            <p v-if="selectedProductOption?.packageSuggestions?.length" class="mt-2 text-xs leading-6 text-slate-500">
                                Klik label paket di atas supaya nama paket sama persis dengan yang tampil di halaman produk.
                            </p>
                            <InputError class="mt-2" :message="form.errors.package_label" />
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Label stok</label>
                            <Input v-model="form.stock_label" class="h-12 rounded-2xl border-slate-200 text-[15px]" placeholder="Akun, kode voucher, invite code, dll" />
                            <InputError class="mt-2" :message="form.errors.stock_label" />
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Isi stok</label>
                            <textarea
                                v-model="form.stock_values"
                                rows="8"
                                class="w-full rounded-[24px] border border-slate-200 bg-slate-50/50 px-4 py-4 text-[15px] text-slate-950 outline-none transition focus:border-sky-300 focus:bg-white focus:ring-2 focus:ring-sky-100"
                                placeholder="Satu baris = satu stok&#10;contoh@email.com|password123&#10;contoh2@email.com|password456"
                            />
                            <p class="mt-2 text-xs leading-6 text-slate-500">Pisahkan setiap stok dengan baris baru agar bisa masuk sekaligus dalam satu submit.</p>
                            <InputError class="mt-2" :message="form.errors.stock_values" />
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Catatan admin</label>
                            <textarea
                                v-model="form.notes"
                                rows="4"
                                class="w-full rounded-[24px] border border-slate-200 bg-slate-50/50 px-4 py-4 text-[15px] text-slate-950 outline-none transition focus:border-sky-300 focus:bg-white focus:ring-2 focus:ring-sky-100"
                                placeholder="Catatan internal untuk stok ini"
                            />
                            <InputError class="mt-2" :message="form.errors.notes" />
                        </div>
                    </div>

                    <div class="mt-6 flex flex-wrap gap-3">
                        <Button :disabled="form.processing" class="rounded-full px-5" @click="submit">
                            <PackagePlus class="mr-2 size-4" />
                            Simpan stok manual
                        </Button>
                    </div>
                </section>

                <section class="rounded-[30px] border border-slate-200/80 bg-white p-6 shadow-[0_24px_70px_rgba(15,23,42,0.07)]">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <div class="text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-400">Daftar Stok</div>
                            <h2 class="mt-2 text-2xl font-semibold tracking-tight text-slate-950">Stok terbaru yang tersimpan</h2>
                            <p class="mt-2 text-sm leading-6 text-slate-500">Kamu bisa copy data stok dari sini saat akan mengirim order manual ke customer.</p>
                        </div>

                        <div class="rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-sm font-semibold text-slate-600">
                            {{ items.length }} item
                        </div>
                    </div>

                    <div class="mt-5 space-y-3">
                        <article
                            v-for="item in items"
                            :key="item.id"
                            class="rounded-[24px] border border-slate-200/80 bg-slate-50/70 p-4 shadow-[0_12px_28px_rgba(148,163,184,0.08)]"
                        >
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold ring-1" :class="stockStatusClass(item.status)">
                                            {{ item.status }}
                                        </span>
                                        <span class="rounded-full bg-white px-3 py-1 text-xs font-medium text-slate-500 ring-1 ring-slate-200">
                                            {{ item.productName }}
                                        </span>
                                    </div>
                                    <div class="mt-3 text-base font-semibold text-slate-950">{{ item.packageLabel }}</div>
                                    <div class="mt-1 text-xs uppercase tracking-[0.18em] text-slate-400">{{ item.productId }}</div>
                                </div>

                                <Button type="button" variant="outline" class="rounded-full px-4" @click="copyStock(item.id, item.stockValue)">
                                    <Copy class="mr-2 size-4" />
                                    {{ copiedId === item.id ? 'Tersalin' : 'Copy stok' }}
                                </Button>
                            </div>

                            <div class="mt-4 rounded-[20px] border border-slate-200 bg-white p-4">
                                <div class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">
                                    {{ item.stockLabel || 'Isi stok' }}
                                </div>
                                <pre class="mt-3 whitespace-pre-wrap break-all font-mono text-sm leading-6 text-slate-900">{{ item.stockValue }}</pre>
                            </div>

                            <div class="mt-4 grid gap-3 md:grid-cols-2">
                                <div class="rounded-[18px] border border-slate-200 bg-white px-4 py-3 text-sm text-slate-600">
                                    <div class="flex items-center gap-2 font-semibold text-slate-900">
                                        <Clock3 class="size-4 text-slate-400" />
                                        Dibuat
                                    </div>
                                    <div class="mt-2">{{ item.createdAtLabel }}</div>
                                </div>
                                <div class="rounded-[18px] border border-slate-200 bg-white px-4 py-3 text-sm text-slate-600">
                                    <div class="flex items-center gap-2 font-semibold text-slate-900">
                                        <CheckCircle2 class="size-4 text-slate-400" />
                                        Reservasi
                                    </div>
                                    <div class="mt-2">
                                        <template v-if="item.reservedForPublicId">
                                            #{{ item.reservedForPublicId }}<span v-if="item.reservedAtLabel"> • {{ item.reservedAtLabel }}</span>
                                        </template>
                                        <template v-else>
                                            Belum dipakai transaksi
                                        </template>
                                    </div>
                                </div>
                            </div>

                            <div v-if="item.notes" class="mt-4 rounded-[18px] border border-amber-100 bg-amber-50/80 px-4 py-3 text-sm leading-6 text-amber-700">
                                {{ item.notes }}
                            </div>
                        </article>
                    </div>
                </section>
            </section>
        </div>
    </AdminLayout>
</template>
