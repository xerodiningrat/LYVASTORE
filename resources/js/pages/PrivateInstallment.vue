<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { BadgeCheck, LockKeyhole, MessageSquareText, ShieldCheck, WalletCards } from 'lucide-vue-next';
import { computed, onMounted } from 'vue';

const props = defineProps<{
    page: {
        title: string;
        description: string;
        productName: string;
        packageLabel: string;
        image: string;
        targetAmount: number;
        paidAmount: number;
        remainingAmount: number;
        progressPercent: number;
        paymentCount: number;
        minimumAmount: number;
        defaultAmount: number;
        checkoutNotice: string;
        guaranteeText: string;
        notes: string[];
        requiresAccessKey: boolean;
        bankName: string;
        accountNumber: string;
        accountHolder: string;
    };
    prefill: {
        accessKey?: string;
    };
}>();

const formatCurrency = (value: number) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);

const form = useForm({
    amount: props.page.defaultAmount,
    accessKey: props.prefill.accessKey ?? '',
    website: '',
    formStartedAt: Date.now(),
});

const presetAmounts = [50000, 100000, 250000, 500000, 1000000];
const selectedAmount = computed(() => {
    const normalizedAmount = Math.max(props.page.minimumAmount, Number(form.amount) || 0);

    if (props.page.remainingAmount <= 0) {
        return 0;
    }

    return Math.min(props.page.remainingAmount, normalizedAmount);
});
const canSubmit = computed(() => !form.processing && props.page.remainingAmount > 0);

onMounted(() => {
    form.formStartedAt = Date.now();
});

const submit = () => {
    form.amount = selectedAmount.value;
    form.post(route('private-installment.store'));
};
</script>

<template>
    <PublicLayout active-nav="topup">
        <Head :title="page.title" />

        <main class="bg-[radial-gradient(circle_at_top,#eff6ff_0%,#f8fafc_42%,#e2e8f0_100%)]">
            <section class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8 lg:py-14">
                <div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div class="max-w-3xl">
                        <div
                            class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white/80 px-4 py-1.5 text-xs font-black uppercase tracking-[0.2em] text-slate-600"
                        >
                            <LockKeyhole class="size-3.5 text-emerald-500" />
                            Halaman Private
                        </div>
                        <h1 class="mt-4 text-3xl font-black tracking-tight text-slate-950 [font-family:'Space_Grotesk',sans-serif] sm:text-4xl">
                            {{ page.title }}
                        </h1>
                        <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-600 sm:text-base">
                            {{ page.description }}
                        </p>
                    </div>

                    <div
                        class="rounded-3xl border border-emerald-200 bg-emerald-50/80 px-5 py-4 text-sm text-emerald-900 shadow-[0_18px_50px_rgba(16,185,129,0.12)]"
                    >
                        <div class="text-[0.72rem] font-black uppercase tracking-[0.18em] text-emerald-600">Sisa Utang</div>
                        <div class="mt-1 text-2xl font-black">{{ formatCurrency(page.remainingAmount) }}</div>
                        <div class="mt-1 text-xs text-emerald-700">
                            Target {{ formatCurrency(page.targetAmount) }} • {{ page.progressPercent }}% selesai
                        </div>
                    </div>
                </div>

                <div class="grid gap-6 lg:grid-cols-[1.05fr_0.95fr]">
                    <div class="space-y-6">
                        <div class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-[0_24px_60px_rgba(15,23,42,0.08)]">
                            <div class="grid gap-0 md:grid-cols-[0.9fr_1.1fr]">
                                <div class="bg-[linear-gradient(145deg,#0f172a_0%,#1d4ed8_52%,#38bdf8_100%)] p-6 text-white">
                                    <div
                                        class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-3 py-1 text-[0.68rem] font-black uppercase tracking-[0.18em]"
                                    >
                                        <BadgeCheck class="size-3.5" />
                                        Private Payment
                                    </div>
                                    <h2 class="mt-4 text-2xl font-black">{{ page.productName }}</h2>
                                    <p class="mt-2 text-sm leading-6 text-slate-100/90">{{ page.packageLabel }}</p>

                                    <div class="mt-6 overflow-hidden rounded-[24px] border border-white/15 bg-white/10 p-4 backdrop-blur">
                                        <img :src="page.image" :alt="page.productName" class="h-40 w-full rounded-[18px] object-cover" />
                                    </div>
                                </div>

                                <div class="p-6">
                                    <div class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                        <ShieldCheck class="mt-0.5 size-5 text-emerald-500" />
                                        <div>
                                            <div class="text-sm font-bold text-slate-900">Transfer manual private</div>
                                            <p class="mt-1 text-sm leading-6 text-slate-600">{{ page.checkoutNotice }}</p>
                                        </div>
                                    </div>

                                    <div class="mt-5 space-y-3">
                                        <div class="flex items-center justify-between rounded-2xl border border-slate-200 px-4 py-3">
                                            <div>
                                                <div class="text-xs font-black uppercase tracking-[0.16em] text-slate-500">Total Utang</div>
                                                <div class="mt-1 text-sm font-semibold text-slate-900">Target pembayaran keseluruhan</div>
                                            </div>
                                            <div class="text-right text-base font-black text-slate-950">{{ formatCurrency(page.targetAmount) }}</div>
                                        </div>
                                        <div class="flex items-center justify-between rounded-2xl border border-slate-200 px-4 py-3">
                                            <div>
                                                <div class="text-xs font-black uppercase tracking-[0.16em] text-slate-500">Sudah Dibayar</div>
                                                <div class="mt-1 text-sm font-semibold text-slate-900">Masuk dari transaksi yang sudah lunas</div>
                                            </div>
                                            <div class="text-right text-base font-black text-slate-950">{{ formatCurrency(page.paidAmount) }}</div>
                                        </div>
                                        <div class="rounded-2xl bg-slate-950 px-4 py-4 text-white">
                                            <div class="flex items-center justify-between gap-3">
                                                <div>
                                                    <div class="text-xs font-black uppercase tracking-[0.16em] text-slate-300">
                                                        Progress Pembayaran
                                                    </div>
                                                    <div class="mt-1 text-sm font-semibold text-white/90">
                                                        {{ page.paymentCount }} pembayaran tercatat • sisa {{ formatCurrency(page.remainingAmount) }}
                                                    </div>
                                                </div>
                                                <div class="text-right text-lg font-black">{{ page.progressPercent }}%</div>
                                            </div>
                                            <div class="mt-4 h-3 overflow-hidden rounded-full bg-white/15">
                                                <div
                                                    class="h-full rounded-full bg-[linear-gradient(90deg,#34d399_0%,#22c55e_100%)] transition-all duration-500"
                                                    :style="{ width: `${page.progressPercent}%` }"
                                                />
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-5 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm leading-6 text-amber-900">
                                        {{ page.guaranteeText }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-[0_20px_50px_rgba(15,23,42,0.06)]">
                            <div class="flex items-center gap-2 text-sm font-black uppercase tracking-[0.16em] text-slate-500">
                                <MessageSquareText class="size-4 text-sky-500" />
                                Catatan Halaman
                            </div>
                            <div class="mt-4 grid gap-3">
                                <div
                                    v-for="note in page.notes"
                                    :key="note"
                                    class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm leading-6 text-slate-700"
                                >
                                    {{ note }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-[0_24px_60px_rgba(15,23,42,0.08)]">
                        <div class="flex items-center gap-2 text-sm font-black uppercase tracking-[0.16em] text-slate-500">
                            <WalletCards class="size-4 text-indigo-500" />
                            Rekening Tujuan
                        </div>
                        <h2 class="mt-3 text-2xl font-black text-slate-950">Transfer manual ke {{ page.bankName }}</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            Gunakan rekening di bawah ini untuk mencicil utang. Nominal bebas, minimal {{ formatCurrency(page.minimumAmount) }}, lalu
                            buka checkout private untuk menyimpan instruksi transfernya.
                        </p>

                        <form class="mt-6 space-y-5" @submit.prevent="submit">
                            <input v-model.number="form.amount" type="hidden" name="amount" />
                            <input v-model="form.accessKey" type="hidden" name="accessKey" />
                            <input v-model="form.website" type="text" name="website" class="hidden" tabindex="-1" autocomplete="off" />
                            <input v-model="form.formStartedAt" type="hidden" name="formStartedAt" />

                            <div class="grid gap-4">
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                                    <label for="private-payment-amount" class="text-[0.72rem] font-black uppercase tracking-[0.18em] text-slate-500">
                                        Nominal Pembayaran
                                    </label>
                                    <input
                                        id="private-payment-amount"
                                        v-model.number="form.amount"
                                        type="number"
                                        inputmode="numeric"
                                        :min="page.minimumAmount"
                                        step="1000"
                                        class="mt-3 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-lg font-black text-slate-950 outline-none transition focus:border-emerald-400"
                                        placeholder="Masukkan nominal pembayaran"
                                    />
                                    <div class="mt-2 text-xs leading-5 text-slate-500">
                                        Bebas isi nominal berapa saja. Minimal {{ formatCurrency(page.minimumAmount) }} dan sisa utang saat ini
                                        {{ formatCurrency(page.remainingAmount) }}.
                                    </div>
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        <button
                                            v-for="preset in presetAmounts"
                                            :key="preset"
                                            type="button"
                                            class="rounded-full border px-3 py-1.5 text-xs font-black transition"
                                            :class="
                                                selectedAmount === preset
                                                    ? 'border-emerald-300 bg-emerald-50 text-emerald-700'
                                                    : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300'
                                            "
                                            @click="form.amount = preset"
                                        >
                                            {{ formatCurrency(preset) }}
                                        </button>
                                    </div>
                                    <InputError :message="form.errors.amount" class="mt-2" />
                                </div>
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                                    <div class="text-[0.72rem] font-black uppercase tracking-[0.18em] text-slate-500">Bank</div>
                                    <div class="mt-2 text-lg font-black text-slate-950">{{ page.bankName }}</div>
                                </div>
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                                    <div class="text-[0.72rem] font-black uppercase tracking-[0.18em] text-slate-500">Nomor Rekening</div>
                                    <div class="mt-2 break-all text-xl font-black tracking-[0.08em] text-slate-950">{{ page.accountNumber }}</div>
                                </div>
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                                    <div class="text-[0.72rem] font-black uppercase tracking-[0.18em] text-slate-500">Atas Nama</div>
                                    <div class="mt-2 text-lg font-black text-slate-950">{{ page.accountHolder }}</div>
                                </div>
                            </div>

                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm leading-6 text-slate-600">
                                Setelah transfer, cek notifikasi uang masuk lewat email. Dari panel admin, transaksi ini nanti bisa ditandai manual
                                sebagai sudah dibayar.
                            </div>

                            <button
                                type="submit"
                                class="inline-flex w-full items-center justify-center rounded-2xl bg-slate-950 px-5 py-3.5 text-sm font-black text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"
                                :disabled="!canSubmit"
                            >
                                {{
                                    page.remainingAmount <= 0
                                        ? 'Utang sudah lunas'
                                        : form.processing
                                          ? 'Membuat halaman checkout...'
                                          : 'Buka checkout private transfer'
                                }}
                            </button>
                            <InputError :message="form.errors.formStartedAt || form.errors.website || form.errors.accessKey" class="mt-2" />
                        </form>
                    </div>
                </div>
            </section>
        </main>
    </PublicLayout>
</template>
