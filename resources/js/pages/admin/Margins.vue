<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AdminLayout from '@/layouts/AdminLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { BadgeDollarSign, BarChart3, Calculator, Percent, Plus, RotateCcw, Sparkles, Trash2, Wallet } from 'lucide-vue-next';
import { computed, ref } from 'vue';

type TierDraft = {
    max: number | null;
    percent: number;
    fixed: number;
    round_to: number;
};

const props = defineProps<{
    tiers: TierDraft[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard Admin', href: '/dashboard' },
    { title: 'Setting Margin', href: '/dashboard/margin' },
];

const page = usePage<{ flash?: { status?: string } }>();
const flashStatus = computed(() => page.props.flash?.status ?? '');

const number = new Intl.NumberFormat('id-ID');
const formatCurrency = (value: number) => `Rp ${number.format(Math.max(0, Math.round(value)))}`;
const formatPercent = (value: number) => `${(value * 100).toFixed(value * 100 >= 10 ? 1 : 2).replace(/\.0$/, '')}%`;

const defaultTier = (): TierDraft => ({
    max: null,
    percent: 0.04,
    fixed: 500,
    round_to: 100,
});

const form = useForm({
    tiers: props.tiers.map((tier) => ({
        max: tier.max,
        percent: tier.percent,
        fixed: tier.fixed,
        round_to: tier.round_to,
    })),
});

const quickPreviewAmounts = [1000, 5000, 12000, 25000, 50000, 100000];
const previewBaseAmount = ref(18000);

const parseNumber = (value: unknown, fallback = 0) => {
    const parsed = Number(value);

    return Number.isFinite(parsed) ? parsed : fallback;
};

const normalizedTiers = computed(() =>
    form.tiers.map((tier, index) => {
        const previousTier = form.tiers[index - 1];
        const previousMax = previousTier ? Math.max(0, Math.round(parseNumber(previousTier.max, 0))) : 0;

        return {
            index,
            max: tier.max === null ? null : Math.max(0, Math.round(parseNumber(tier.max, 0))),
            percent: Math.max(0, parseNumber(tier.percent, 0)),
            fixed: Math.max(0, Math.round(parseNumber(tier.fixed, 0))),
            roundTo: Math.max(1, Math.round(parseNumber(tier.round_to, 100))),
            rangeStart: index === 0 ? 0 : previousMax + 1,
        };
    }),
);

const tierFieldError = (index: number, field: 'max' | 'percent' | 'fixed' | 'round_to') =>
    (form.errors as Record<string, string | undefined>)[`tiers.${index}.${field}`];

const tierRangeLabel = (index: number) => {
    const tier = normalizedTiers.value[index];

    if (!tier) {
        return '-';
    }

    if (tier.max === null) {
        return `Di atas ${formatCurrency(Math.max(1, tier.rangeStart))}`;
    }

    if (index === 0) {
        return `Sampai ${formatCurrency(tier.max)}`;
    }

    return `${formatCurrency(tier.rangeStart)} - ${formatCurrency(tier.max)}`;
};

const applySellingPrice = (basePrice: number, tier: { percent: number; fixed: number; roundTo: number }) => {
    const priceWithMargin = basePrice + Math.ceil(basePrice * tier.percent) + tier.fixed;

    return Math.ceil(priceWithMargin / tier.roundTo) * tier.roundTo;
};

const resolveTierForAmount = (amount: number) =>
    normalizedTiers.value.find((tier) => tier.max === null || amount <= tier.max) ?? normalizedTiers.value.at(-1) ?? null;

const tierSimulation = (index: number) => {
    const tier = normalizedTiers.value[index];

    if (!tier) {
        return {
            exampleBase: 0,
            rawMargin: 0,
            sellingPrice: 0,
        };
    }

    const exampleBase = tier.max === null
        ? Math.max(tier.rangeStart + 10000, 150000)
        : Math.max(1000, Math.round((tier.rangeStart + tier.max) / 2));

    const rawMargin = Math.ceil(exampleBase * tier.percent) + tier.fixed;

    return {
        exampleBase,
        rawMargin,
        sellingPrice: applySellingPrice(exampleBase, tier),
    };
};

const previewSummary = computed(() => {
    const baseAmount = Math.max(0, Math.round(parseNumber(previewBaseAmount.value, 0)));
    const activeTier = resolveTierForAmount(baseAmount);

    if (!activeTier) {
        return {
            baseAmount,
            tierIndex: null,
            marginNominal: 0,
            sellingPrice: baseAmount,
            roundTo: 0,
            percent: 0,
            fixed: 0,
        };
    }

    const rawMargin = Math.ceil(baseAmount * activeTier.percent) + activeTier.fixed;
    const sellingPrice = applySellingPrice(baseAmount, activeTier);

    return {
        baseAmount,
        tierIndex: activeTier.index,
        marginNominal: Math.max(0, sellingPrice - baseAmount),
        sellingPrice,
        roundTo: activeTier.roundTo,
        percent: activeTier.percent,
        fixed: activeTier.fixed,
        rawMargin,
    };
});

const totalTiers = computed(() => normalizedTiers.value.length);
const averagePercent = computed(() =>
    normalizedTiers.value.length
        ? normalizedTiers.value.reduce((sum, tier) => sum + tier.percent, 0) / normalizedTiers.value.length
        : 0,
);
const averageFixed = computed(() =>
    normalizedTiers.value.length
        ? Math.round(normalizedTiers.value.reduce((sum, tier) => sum + tier.fixed, 0) / normalizedTiers.value.length)
        : 0,
);

const addTier = () => {
    form.tiers.push(defaultTier());
};

const removeTier = (index: number) => {
    if (form.tiers.length <= 1) {
        return;
    }

    form.tiers.splice(index, 1);
};

const resetPreview = () => {
    previewBaseAmount.value = 18000;
};

const submit = () => {
    form.put('/dashboard/margin', {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Setting Margin" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4">
            <section class="overflow-hidden rounded-[32px] border border-slate-200/80 bg-[radial-gradient(circle_at_top_left,_rgba(59,130,246,0.16),_transparent_32%),linear-gradient(180deg,_rgba(255,255,255,0.98),_rgba(248,250,252,0.98))] p-6 shadow-[0_24px_70px_rgba(15,23,42,0.07)] sm:p-7">
                <div class="flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
                    <div class="max-w-3xl">
                        <div class="inline-flex items-center rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.24em] text-sky-700">
                            Pricing Workspace
                        </div>
                        <h1 class="mt-4 text-3xl font-semibold tracking-tight text-slate-950 sm:text-[2.55rem]">
                            Atur margin VIPayment dengan tampilan yang lebih fokus
                        </h1>
                        <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-600 sm:text-[15px]">
                            Semua tier harga bisa diatur dari satu halaman yang lebih rapih, lengkap dengan preview harga jual dan simulasi margin langsung.
                        </p>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-3 xl:min-w-[29rem]">
                        <article class="rounded-[24px] border border-white/80 bg-white/90 p-4 shadow-[0_14px_30px_rgba(15,23,42,0.05)]">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="text-[11px] uppercase tracking-[0.16em] text-slate-400">Tier aktif</div>
                                    <div class="mt-2 text-2xl font-semibold tracking-tight text-slate-950">{{ number.format(totalTiers) }}</div>
                                </div>
                                <div class="rounded-2xl bg-sky-50 p-3 text-sky-600">
                                    <BarChart3 class="size-5" />
                                </div>
                            </div>
                        </article>

                        <article class="rounded-[24px] border border-white/80 bg-white/90 p-4 shadow-[0_14px_30px_rgba(15,23,42,0.05)]">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="text-[11px] uppercase tracking-[0.16em] text-slate-400">Margin rata-rata</div>
                                    <div class="mt-2 text-2xl font-semibold tracking-tight text-slate-950">{{ formatPercent(averagePercent) }}</div>
                                </div>
                                <div class="rounded-2xl bg-violet-50 p-3 text-violet-600">
                                    <Percent class="size-5" />
                                </div>
                            </div>
                        </article>

                        <article class="rounded-[24px] border border-white/80 bg-white/90 p-4 shadow-[0_14px_30px_rgba(15,23,42,0.05)]">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="text-[11px] uppercase tracking-[0.16em] text-slate-400">Fixed fee rata-rata</div>
                                    <div class="mt-2 text-2xl font-semibold tracking-tight text-slate-950">{{ formatCurrency(averageFixed) }}</div>
                                </div>
                                <div class="rounded-2xl bg-amber-50 p-3 text-amber-600">
                                    <BadgeDollarSign class="size-5" />
                                </div>
                            </div>
                        </article>
                    </div>
                </div>

                <div v-if="flashStatus" class="mt-6 rounded-[24px] border border-emerald-200/80 bg-emerald-50 px-4 py-4 text-sm font-medium text-emerald-700">
                    {{ flashStatus }}
                </div>
            </section>

            <section class="grid gap-6 xl:grid-cols-[minmax(0,1.32fr)_380px]">
                <div class="rounded-[32px] border border-slate-200/80 bg-white p-6 shadow-[0_24px_70px_rgba(15,23,42,0.07)] sm:p-7">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <div class="text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-400">Tier Editor</div>
                            <h2 class="mt-3 text-2xl font-semibold tracking-tight text-slate-950">Kelola urutan margin per rentang harga</h2>
                            <p class="mt-2 max-w-2xl text-sm leading-7 text-slate-600">
                                Tier dibaca dari atas ke bawah. Tier pertama yang cocok dengan harga modal akan langsung dipakai untuk hitung harga jual.
                            </p>
                        </div>

                        <Button type="button" variant="outline" class="rounded-full" @click="addTier">
                            <Plus class="mr-2 size-4" />
                            Tambah tier
                        </Button>
                    </div>

                    <div class="mt-8 space-y-4">
                        <article
                            v-for="(tier, index) in form.tiers"
                            :key="index"
                            class="overflow-hidden rounded-[28px] border border-slate-200/80 bg-[linear-gradient(180deg,rgba(248,250,252,0.98),rgba(255,255,255,1))]"
                        >
                            <div class="flex flex-col gap-4 border-b border-slate-200/80 px-5 py-5 lg:flex-row lg:items-center lg:justify-between">
                                <div class="flex flex-wrap items-center gap-3">
                                    <span class="inline-flex rounded-full bg-slate-900 px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-white">
                                        Tier {{ index + 1 }}
                                    </span>
                                    <span class="rounded-full bg-sky-50 px-3 py-1 text-xs font-medium text-sky-700">
                                        {{ tierRangeLabel(index) }}
                                    </span>
                                    <span class="rounded-full bg-white px-3 py-1 text-xs font-medium text-slate-500 ring-1 ring-slate-200">
                                        Modal contoh {{ formatCurrency(tierSimulation(index).exampleBase) }}
                                    </span>
                                </div>

                                <Button
                                    type="button"
                                    variant="outline"
                                    class="rounded-full border-rose-200 text-rose-600 hover:bg-rose-50 hover:text-rose-700"
                                    :disabled="form.tiers.length <= 1"
                                    @click="removeTier(index)"
                                >
                                    <Trash2 class="mr-2 size-4" />
                                    Hapus
                                </Button>
                            </div>

                            <div class="grid gap-5 px-5 py-5 xl:grid-cols-[minmax(0,1.12fr)_300px]">
                                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                                    <div class="grid gap-2">
                                        <Label class="text-sm font-semibold text-slate-700">Batas maksimal</Label>
                                        <Input
                                            :model-value="tier.max ?? ''"
                                            type="number"
                                            min="0"
                                            class="h-12 rounded-2xl border-slate-200 bg-white"
                                            placeholder="Kosongkan untuk tier terakhir"
                                            @update:model-value="
                                                (value) => {
                                                    const nextValue = String(value ?? '').trim();
                                                    tier.max = nextValue === '' ? null : Math.max(0, Math.round(parseNumber(nextValue, 0)));
                                                }
                                            "
                                        />
                                        <p class="text-xs text-slate-500">Kosong berarti tier ini dipakai untuk nominal paling atas.</p>
                                        <InputError :message="tierFieldError(index, 'max')" />
                                    </div>

                                    <div class="grid gap-2">
                                        <Label class="text-sm font-semibold text-slate-700">Percent</Label>
                                        <Input
                                            v-model="tier.percent"
                                            type="number"
                                            min="0"
                                            step="0.0001"
                                            class="h-12 rounded-2xl border-slate-200 bg-white"
                                        />
                                        <p class="text-xs text-slate-500">Contoh `0.07` untuk 7% markup.</p>
                                        <InputError :message="tierFieldError(index, 'percent')" />
                                    </div>

                                    <div class="grid gap-2">
                                        <Label class="text-sm font-semibold text-slate-700">Fixed fee</Label>
                                        <Input
                                            v-model="tier.fixed"
                                            type="number"
                                            min="0"
                                            class="h-12 rounded-2xl border-slate-200 bg-white"
                                        />
                                        <p class="text-xs text-slate-500">Tambahan nominal tetap untuk tiap transaksi.</p>
                                        <InputError :message="tierFieldError(index, 'fixed')" />
                                    </div>

                                    <div class="grid gap-2">
                                        <Label class="text-sm font-semibold text-slate-700">Pembulatan</Label>
                                        <Input
                                            v-model="tier.round_to"
                                            type="number"
                                            min="1"
                                            class="h-12 rounded-2xl border-slate-200 bg-white"
                                        />
                                        <p class="text-xs text-slate-500">Harga jual dibulatkan ke kelipatan ini.</p>
                                        <InputError :message="tierFieldError(index, 'round_to')" />
                                    </div>
                                </div>

                                <aside class="rounded-[24px] border border-slate-200/80 bg-white p-4 shadow-[0_14px_26px_rgba(15,23,42,0.05)]">
                                    <div class="text-[11px] uppercase tracking-[0.18em] text-slate-400">Preview tier {{ index + 1 }}</div>
                                    <div class="mt-4 grid gap-3">
                                        <div class="rounded-2xl bg-slate-50 px-4 py-3">
                                            <div class="text-[11px] uppercase tracking-[0.16em] text-slate-400">Markup persentase</div>
                                            <div class="mt-1 text-base font-semibold text-slate-950">{{ formatPercent(normalizedTiers[index]?.percent ?? 0) }}</div>
                                        </div>
                                        <div class="rounded-2xl bg-slate-50 px-4 py-3">
                                            <div class="text-[11px] uppercase tracking-[0.16em] text-slate-400">Margin mentah</div>
                                            <div class="mt-1 text-base font-semibold text-slate-950">{{ formatCurrency(tierSimulation(index).rawMargin) }}</div>
                                        </div>
                                        <div class="rounded-2xl bg-slate-950 px-4 py-3 text-white">
                                            <div class="text-[11px] uppercase tracking-[0.16em] text-slate-400">Harga jual contoh</div>
                                            <div class="mt-1 text-lg font-semibold">{{ formatCurrency(tierSimulation(index).sellingPrice) }}</div>
                                        </div>
                                    </div>
                                </aside>
                            </div>
                        </article>
                    </div>

                    <div class="mt-6 space-y-3">
                        <InputError :message="form.errors.tiers" />
                        <div class="flex flex-wrap gap-3">
                            <Button type="button" variant="outline" class="rounded-full" @click="addTier">
                                <Plus class="mr-2 size-4" />
                                Tambah tier baru
                            </Button>
                            <Button :disabled="form.processing" class="rounded-full" @click="submit">
                                Simpan setting margin
                            </Button>
                        </div>
                    </div>
                </div>

                <aside class="space-y-6">
                    <section class="rounded-[32px] border border-slate-200/80 bg-white p-6 shadow-[0_24px_70px_rgba(15,23,42,0.07)]">
                        <div class="flex items-center gap-3">
                            <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-slate-900 text-white">
                                <Calculator class="size-5" />
                            </span>
                            <div>
                                <div class="text-[11px] uppercase tracking-[0.18em] text-slate-400">Live simulator</div>
                                <div class="text-lg font-semibold tracking-tight text-slate-950">Preview harga jual sekarang</div>
                            </div>
                        </div>

                        <div class="mt-5 grid gap-2">
                            <Label class="text-sm font-semibold text-slate-700">Modal VIPayment</Label>
                            <Input v-model="previewBaseAmount" type="number" min="0" class="h-12 rounded-2xl border-slate-200 bg-slate-50 text-base font-semibold" />
                        </div>

                        <div class="mt-4 flex flex-wrap gap-2">
                            <button
                                v-for="amount in quickPreviewAmounts"
                                :key="amount"
                                type="button"
                                class="rounded-full border px-3 py-1.5 text-xs font-semibold transition"
                                :class="
                                    previewBaseAmount === amount
                                        ? 'border-sky-500 bg-sky-50 text-sky-700'
                                        : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300 hover:bg-slate-50'
                                "
                                @click="previewBaseAmount = amount"
                            >
                                {{ formatCurrency(amount) }}
                            </button>
                        </div>

                        <div class="mt-5 rounded-[28px] bg-[linear-gradient(180deg,#0f172a_0%,#111827_100%)] p-5 text-white shadow-[0_18px_40px_rgba(15,23,42,0.2)]">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <div class="text-[11px] uppercase tracking-[0.18em] text-slate-400">Harga jual hasil simulasi</div>
                                    <div class="mt-2 text-3xl font-semibold tracking-tight">{{ formatCurrency(previewSummary.sellingPrice) }}</div>
                                    <div class="mt-1 text-sm text-slate-300">
                                        {{ previewSummary.tierIndex === null ? 'Belum ada tier yang aktif.' : `Memakai Tier ${previewSummary.tierIndex + 1}` }}
                                    </div>
                                </div>
                                <div class="rounded-2xl bg-white/10 p-3 text-emerald-300">
                                    <Sparkles class="size-5" />
                                </div>
                            </div>

                            <div class="mt-5 grid gap-3">
                                <div class="flex items-center justify-between rounded-2xl bg-white/5 px-4 py-3">
                                    <span class="text-sm text-slate-300">Harga modal</span>
                                    <span class="font-semibold">{{ formatCurrency(previewSummary.baseAmount) }}</span>
                                </div>
                                <div class="flex items-center justify-between rounded-2xl bg-white/5 px-4 py-3">
                                    <span class="text-sm text-slate-300">Margin kotor</span>
                                    <span class="font-semibold">{{ formatCurrency(previewSummary.marginNominal) }}</span>
                                </div>
                                <div class="flex items-center justify-between rounded-2xl bg-white/5 px-4 py-3">
                                    <span class="text-sm text-slate-300">Percent aktif</span>
                                    <span class="font-semibold">{{ formatPercent(previewSummary.percent) }}</span>
                                </div>
                                <div class="flex items-center justify-between rounded-2xl bg-white/5 px-4 py-3">
                                    <span class="text-sm text-slate-300">Fixed fee aktif</span>
                                    <span class="font-semibold">{{ formatCurrency(previewSummary.fixed) }}</span>
                                </div>
                                <div class="flex items-center justify-between rounded-2xl bg-white/5 px-4 py-3">
                                    <span class="text-sm text-slate-300">Pembulatan</span>
                                    <span class="font-semibold">Kelipatan {{ formatCurrency(previewSummary.roundTo) }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 flex gap-3">
                            <Button type="button" variant="outline" class="flex-1 rounded-full" @click="resetPreview">
                                <RotateCcw class="mr-2 size-4" />
                                Reset preview
                            </Button>
                            <Button class="flex-1 rounded-full" :disabled="form.processing" @click="submit">
                                Simpan
                            </Button>
                        </div>
                    </section>

                    <section class="rounded-[32px] border border-slate-200/80 bg-white p-6 shadow-[0_24px_70px_rgba(15,23,42,0.07)]">
                        <div class="text-[11px] uppercase tracking-[0.18em] text-slate-400">Catatan pemakaian</div>
                        <div class="mt-3 text-lg font-semibold tracking-tight text-slate-950">Cara baca tier dengan aman</div>

                        <div class="mt-5 space-y-3">
                            <div class="rounded-[22px] border border-slate-200/80 bg-slate-50 px-4 py-4">
                                <div class="text-sm font-semibold text-slate-950">Urutan dari atas ke bawah</div>
                                <p class="mt-1 text-sm leading-6 text-slate-600">Tier pertama yang cocok dengan harga modal akan langsung dipakai sistem.</p>
                            </div>
                            <div class="rounded-[22px] border border-slate-200/80 bg-slate-50 px-4 py-4">
                                <div class="text-sm font-semibold text-slate-950">Tier terakhir boleh tanpa batas</div>
                                <p class="mt-1 text-sm leading-6 text-slate-600">Kosongkan `batas maksimal` pada tier paling bawah untuk menangani nominal paling besar.</p>
                            </div>
                            <div class="rounded-[22px] border border-slate-200/80 bg-slate-50 px-4 py-4">
                                <div class="text-sm font-semibold text-slate-950">Pembulatan bantu harga rapi</div>
                                <p class="mt-1 text-sm leading-6 text-slate-600">Pakai pembulatan yang masuk akal supaya harga tetap enak dilihat dan gampang bersaing.</p>
                            </div>
                        </div>
                    </section>
                </aside>
            </section>
        </div>
    </AdminLayout>
</template>
