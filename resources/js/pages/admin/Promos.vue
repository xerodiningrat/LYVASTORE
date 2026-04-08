<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AdminLayout from '@/layouts/AdminLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { CalendarDays, CheckCircle2, Percent, Plus, Tag, Ticket, Trash2, WalletCards } from 'lucide-vue-next';
import { computed, reactive } from 'vue';

type PromoRecord = {
    id: string;
    code: string;
    label: string;
    description?: string | null;
    type: 'fixed' | 'percent';
    value: number;
    minimumSubtotal: number;
    maxDiscount?: number | null;
    productIds: string[];
    startsAt?: string | null;
    expiresAt?: string | null;
    isActive: boolean;
};

type PromoDraft = {
    id: string;
    code: string;
    label: string;
    description: string;
    type: 'fixed' | 'percent';
    value: number;
    minimum_subtotal: number;
    max_discount: number | null;
    product_ids: string;
    starts_at: string;
    expires_at: string;
    is_active: boolean;
};

const props = defineProps<{
    promos: PromoRecord[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard Admin', href: '/dashboard' },
    { title: 'Kode Promo', href: '/dashboard/promo' },
];

const page = usePage<{ flash?: { status?: string } }>();
const flashStatus = computed(() => page.props.flash?.status ?? '');

const formatCurrency = (value: number) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(Math.max(0, Math.round(value)));

const toLocalDateTimeInput = (value?: string | null) => {
    if (!value) {
        return '';
    }

    const normalized = value.replace(' ', 'T');

    return normalized.slice(0, 16);
};

const toDraft = (promo: PromoRecord): PromoDraft => ({
    id: promo.id,
    code: promo.code,
    label: promo.label ?? '',
    description: promo.description ?? '',
    type: promo.type,
    value: promo.value,
    minimum_subtotal: promo.minimumSubtotal ?? 0,
    max_discount: promo.maxDiscount ?? null,
    product_ids: promo.productIds.join(', '),
    starts_at: toLocalDateTimeInput(promo.startsAt),
    expires_at: toLocalDateTimeInput(promo.expiresAt),
    is_active: promo.isActive,
});

const promoDrafts = reactive<Record<string, PromoDraft>>(
    Object.fromEntries(props.promos.map((promo) => [promo.id, toDraft(promo)])),
);

const createForm = useForm({
    code: '',
    label: '',
    description: '',
    type: 'fixed' as 'fixed' | 'percent',
    value: 1000,
    minimum_subtotal: 0,
    max_discount: null as number | null,
    product_ids: '',
    starts_at: '',
    expires_at: '',
    is_active: true,
});

const activePromos = computed(() => props.promos.filter((promo) => promo.isActive).length);
const percentPromos = computed(() => props.promos.filter((promo) => promo.type === 'percent').length);
const fixedPromos = computed(() => props.promos.filter((promo) => promo.type === 'fixed').length);
const promoPath = (promoId?: string) => {
    const basePath = '/dashboard/promo';

    if (!promoId) {
        return basePath;
    }

    return `${basePath}/${encodeURIComponent(promoId)}`;
};

const submitCreate = () => {
    createForm.post(promoPath(), {
        preserveScroll: true,
        onSuccess: () => createForm.reset(),
    });
};

const savePromo = (promoId: string) => {
    const draft = promoDrafts[promoId];

    if (!draft) {
        return;
    }

    router.patch(
        promoPath(promoId),
        {
            code: draft.code,
            label: draft.label,
            description: draft.description,
            type: draft.type,
            value: draft.value,
            minimum_subtotal: draft.minimum_subtotal,
            max_discount: draft.max_discount,
            product_ids: draft.product_ids,
            starts_at: draft.starts_at || null,
            expires_at: draft.expires_at || null,
            is_active: draft.is_active,
        },
        { preserveScroll: true },
    );
};

const deletePromo = (promoId: string) => {
    router.delete(promoPath(promoId), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Kode Promo" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4">
            <section class="overflow-hidden rounded-[32px] border border-slate-200/80 bg-[radial-gradient(circle_at_top_left,_rgba(34,197,94,0.14),_transparent_34%),linear-gradient(180deg,_rgba(255,255,255,0.98),_rgba(248,250,252,0.98))] p-6 shadow-[0_24px_70px_rgba(15,23,42,0.07)] sm:p-7">
                <div class="flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
                    <div class="max-w-3xl">
                        <div class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.24em] text-emerald-700">
                            Promo Workspace
                        </div>
                        <h1 class="mt-4 text-3xl font-semibold tracking-tight text-slate-950 sm:text-[2.55rem]">
                            Bikin kode promo yang langsung bisa dipakai user saat checkout
                        </h1>
                        <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-600 sm:text-[15px]">
                            Atur potongan nominal atau persen, batas minimum belanja, jadwal aktif, sampai produk tertentu kalau diperlukan.
                        </p>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-3 xl:min-w-[30rem]">
                        <article class="rounded-[24px] border border-white/80 bg-white/90 p-4 shadow-[0_14px_30px_rgba(15,23,42,0.05)]">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="text-[11px] uppercase tracking-[0.16em] text-slate-400">Promo aktif</div>
                                    <div class="mt-2 text-2xl font-semibold tracking-tight text-slate-950">{{ activePromos }}</div>
                                </div>
                                <div class="rounded-2xl bg-emerald-50 p-3 text-emerald-600">
                                    <CheckCircle2 class="size-5" />
                                </div>
                            </div>
                        </article>

                        <article class="rounded-[24px] border border-white/80 bg-white/90 p-4 shadow-[0_14px_30px_rgba(15,23,42,0.05)]">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="text-[11px] uppercase tracking-[0.16em] text-slate-400">Promo persen</div>
                                    <div class="mt-2 text-2xl font-semibold tracking-tight text-slate-950">{{ percentPromos }}</div>
                                </div>
                                <div class="rounded-2xl bg-sky-50 p-3 text-sky-600">
                                    <Percent class="size-5" />
                                </div>
                            </div>
                        </article>

                        <article class="rounded-[24px] border border-white/80 bg-white/90 p-4 shadow-[0_14px_30px_rgba(15,23,42,0.05)]">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="text-[11px] uppercase tracking-[0.16em] text-slate-400">Promo nominal</div>
                                    <div class="mt-2 text-2xl font-semibold tracking-tight text-slate-950">{{ fixedPromos }}</div>
                                </div>
                                <div class="rounded-2xl bg-amber-50 p-3 text-amber-600">
                                    <WalletCards class="size-5" />
                                </div>
                            </div>
                        </article>
                    </div>
                </div>

                <div v-if="flashStatus" class="mt-6 rounded-[24px] border border-emerald-200/80 bg-emerald-50 px-4 py-4 text-sm font-medium text-emerald-700">
                    {{ flashStatus }}
                </div>
            </section>

            <section class="grid gap-6 xl:grid-cols-[420px_minmax(0,1fr)]">
                <div class="rounded-[32px] border border-slate-200/80 bg-white p-6 shadow-[0_24px_70px_rgba(15,23,42,0.07)] sm:p-7">
                    <div class="flex items-center gap-3">
                        <div class="rounded-[20px] bg-indigo-50 p-3 text-indigo-600">
                            <Plus class="size-5" />
                        </div>
                        <div>
                            <div class="text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-400">Promo Baru</div>
                            <h2 class="mt-1 text-xl font-semibold tracking-tight text-slate-950">Tambah kode promo</h2>
                        </div>
                    </div>

                    <div class="mt-6 space-y-4">
                        <div class="space-y-2">
                            <Label for="promo-code">Kode promo</Label>
                            <Input id="promo-code" v-model="createForm.code" placeholder="RAMADAN10" class="h-12 rounded-2xl" />
                            <InputError :message="createForm.errors.code" />
                        </div>

                        <div class="space-y-2">
                            <Label for="promo-label">Label promo</Label>
                            <Input id="promo-label" v-model="createForm.label" placeholder="Promo Ramadan" class="h-12 rounded-2xl" />
                            <InputError :message="createForm.errors.label" />
                        </div>

                        <div class="space-y-2">
                            <Label for="promo-description">Deskripsi</Label>
                            <Input id="promo-description" v-model="createForm.description" placeholder="Diskon spesial checkout malam ini" class="h-12 rounded-2xl" />
                            <InputError :message="createForm.errors.description" />
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="promo-type">Tipe diskon</Label>
                                <select id="promo-type" v-model="createForm.type" class="h-12 w-full rounded-2xl border border-slate-200 bg-white px-4 text-sm text-slate-900">
                                    <option value="fixed">Nominal</option>
                                    <option value="percent">Persen</option>
                                </select>
                            </div>

                            <div class="space-y-2">
                                <Label for="promo-value">Nilai</Label>
                                <Input id="promo-value" v-model.number="createForm.value" type="number" min="1" class="h-12 rounded-2xl" />
                                <InputError :message="createForm.errors.value" />
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="promo-minimum">Minimal belanja</Label>
                                <Input id="promo-minimum" v-model.number="createForm.minimum_subtotal" type="number" min="0" class="h-12 rounded-2xl" />
                            </div>

                            <div class="space-y-2">
                                <Label for="promo-max-discount">Maks diskon</Label>
                                <Input
                                    id="promo-max-discount"
                                    :model-value="createForm.max_discount ?? ''"
                                    type="number"
                                    min="0"
                                    class="h-12 rounded-2xl"
                                    @update:model-value="
                                        (value) => {
                                            const nextValue = String(value ?? '').trim();
                                            createForm.max_discount = nextValue === '' ? null : Number(nextValue);
                                        }
                                    "
                                />
                            </div>
                        </div>

                        <div class="space-y-2">
                            <Label for="promo-products">Produk tertentu</Label>
                            <Input id="promo-products" v-model="createForm.product_ids" placeholder="Kosongkan untuk semua produk, atau isi: mobile-legends-a, vip-game-chatgpt" class="h-12 rounded-2xl" />
                            <InputError :message="createForm.errors.product_ids" />
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="promo-start">Mulai aktif</Label>
                                <Input id="promo-start" v-model="createForm.starts_at" type="datetime-local" class="h-12 rounded-2xl" />
                            </div>

                            <div class="space-y-2">
                                <Label for="promo-expire">Berakhir</Label>
                                <Input id="promo-expire" v-model="createForm.expires_at" type="datetime-local" class="h-12 rounded-2xl" />
                            </div>
                        </div>

                        <label class="flex items-center gap-3 rounded-[22px] border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700">
                            <input v-model="createForm.is_active" type="checkbox" class="size-4 rounded border-slate-300 text-slate-950" />
                            Promo langsung aktif setelah disimpan
                        </label>

                        <Button class="h-12 w-full rounded-2xl text-sm font-semibold" :disabled="createForm.processing" @click="submitCreate">
                            <Ticket class="mr-2 size-4.5" />
                            Simpan kode promo
                        </Button>
                    </div>
                </div>

                <div class="space-y-6">
                    <section class="rounded-[32px] border border-slate-200/80 bg-white p-6 shadow-[0_24px_70px_rgba(15,23,42,0.07)] sm:p-7">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <div class="text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-400">Daftar Promo</div>
                                <h2 class="mt-1 text-xl font-semibold tracking-tight text-slate-950">Kode promo aktif dan draft</h2>
                            </div>
                            <div class="rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-sm font-semibold text-slate-700">
                                {{ props.promos.length }} promo
                            </div>
                        </div>

                        <div v-if="!props.promos.length" class="mt-6 rounded-[24px] border border-dashed border-slate-200 bg-slate-50/70 px-5 py-8 text-center text-sm text-slate-500">
                            Belum ada kode promo. Buat satu dari panel kiri, nanti langsung bisa dipakai user saat checkout.
                        </div>

                        <div v-else class="mt-6 space-y-4">
                            <article
                                v-for="promo in props.promos"
                                :key="promo.id"
                                class="rounded-[28px] border border-slate-200/80 bg-slate-50/55 p-5 shadow-[0_14px_30px_rgba(15,23,42,0.04)]"
                            >
                                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                    <div class="flex items-start gap-4">
                                        <div class="rounded-[20px] bg-white p-3 text-slate-900 shadow-[0_10px_24px_rgba(15,23,42,0.06)]">
                                            <Tag class="size-5" />
                                        </div>
                                        <div>
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="rounded-full border px-3 py-1 text-[11px] font-black uppercase tracking-[0.16em]" :class="promo.isActive ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-slate-200 bg-white text-slate-500'">
                                                    {{ promo.isActive ? 'aktif' : 'nonaktif' }}
                                                </span>
                                                <span class="rounded-full border border-slate-200 bg-white px-3 py-1 text-[11px] font-black uppercase tracking-[0.16em] text-slate-600">
                                                    {{ promo.type === 'percent' ? `${promo.value}%` : formatCurrency(promo.value) }}
                                                </span>
                                                <span v-if="promo.minimumSubtotal > 0" class="rounded-full border border-slate-200 bg-white px-3 py-1 text-[11px] font-black uppercase tracking-[0.16em] text-slate-600">
                                                    Min. {{ formatCurrency(promo.minimumSubtotal) }}
                                                </span>
                                            </div>
                                            <h3 class="mt-3 text-xl font-semibold tracking-tight text-slate-950">{{ promo.label || promo.code }}</h3>
                                            <p class="mt-1 text-sm font-semibold tracking-[0.2em] text-slate-400">{{ promo.code }}</p>
                                            <p v-if="promo.description" class="mt-3 max-w-2xl text-sm leading-7 text-slate-600">{{ promo.description }}</p>
                                        </div>
                                    </div>

                                    <button
                                        type="button"
                                        class="inline-flex items-center justify-center gap-2 rounded-full border border-red-200 bg-red-50 px-4 py-2 text-sm font-semibold text-red-600 transition hover:bg-red-100"
                                        @click="deletePromo(promo.id)"
                                    >
                                        <Trash2 class="size-4" />
                                        Hapus
                                    </button>
                                </div>

                                <div class="mt-5 grid gap-4 xl:grid-cols-2">
                                    <div class="space-y-2">
                                        <Label :for="`code-${promo.id}`">Kode promo</Label>
                                        <Input :id="`code-${promo.id}`" v-model="promoDrafts[promo.id].code" class="h-12 rounded-2xl bg-white" />
                                    </div>

                                    <div class="space-y-2">
                                        <Label :for="`label-${promo.id}`">Label promo</Label>
                                        <Input :id="`label-${promo.id}`" v-model="promoDrafts[promo.id].label" class="h-12 rounded-2xl bg-white" />
                                    </div>

                                    <div class="space-y-2 xl:col-span-2">
                                        <Label :for="`description-${promo.id}`">Deskripsi</Label>
                                        <Input :id="`description-${promo.id}`" v-model="promoDrafts[promo.id].description" class="h-12 rounded-2xl bg-white" />
                                    </div>

                                    <div class="space-y-2">
                                        <Label :for="`type-${promo.id}`">Tipe</Label>
                                        <select :id="`type-${promo.id}`" v-model="promoDrafts[promo.id].type" class="h-12 w-full rounded-2xl border border-slate-200 bg-white px-4 text-sm text-slate-900">
                                            <option value="fixed">Nominal</option>
                                            <option value="percent">Persen</option>
                                        </select>
                                    </div>

                                    <div class="space-y-2">
                                        <Label :for="`value-${promo.id}`">Nilai</Label>
                                        <Input :id="`value-${promo.id}`" v-model.number="promoDrafts[promo.id].value" type="number" min="1" class="h-12 rounded-2xl bg-white" />
                                    </div>

                                    <div class="space-y-2">
                                        <Label :for="`minimum-${promo.id}`">Minimal belanja</Label>
                                        <Input :id="`minimum-${promo.id}`" v-model.number="promoDrafts[promo.id].minimum_subtotal" type="number" min="0" class="h-12 rounded-2xl bg-white" />
                                    </div>

                                    <div class="space-y-2">
                                        <Label :for="`max-discount-${promo.id}`">Maks diskon</Label>
                                        <Input
                                            :id="`max-discount-${promo.id}`"
                                            :model-value="promoDrafts[promo.id].max_discount ?? ''"
                                            type="number"
                                            min="0"
                                            class="h-12 rounded-2xl bg-white"
                                            @update:model-value="
                                                (value) => {
                                                    const nextValue = String(value ?? '').trim();
                                                    promoDrafts[promo.id].max_discount = nextValue === '' ? null : Number(nextValue);
                                                }
                                            "
                                        />
                                    </div>

                                    <div class="space-y-2 xl:col-span-2">
                                        <Label :for="`products-${promo.id}`">Produk tertentu</Label>
                                        <Input :id="`products-${promo.id}`" v-model="promoDrafts[promo.id].product_ids" class="h-12 rounded-2xl bg-white" />
                                        <p class="text-xs text-slate-500">Pisahkan dengan koma. Kosongkan jika promo berlaku untuk semua produk.</p>
                                    </div>

                                    <div class="space-y-2">
                                        <Label :for="`start-${promo.id}`">
                                            <span class="inline-flex items-center gap-2">
                                                <CalendarDays class="size-4 text-slate-400" />
                                                Mulai aktif
                                            </span>
                                        </Label>
                                        <Input :id="`start-${promo.id}`" v-model="promoDrafts[promo.id].starts_at" type="datetime-local" class="h-12 rounded-2xl bg-white" />
                                    </div>

                                    <div class="space-y-2">
                                        <Label :for="`expire-${promo.id}`">
                                            <span class="inline-flex items-center gap-2">
                                                <Ticket class="size-4 text-slate-400" />
                                                Berakhir
                                            </span>
                                        </Label>
                                        <Input :id="`expire-${promo.id}`" v-model="promoDrafts[promo.id].expires_at" type="datetime-local" class="h-12 rounded-2xl bg-white" />
                                    </div>
                                </div>

                                <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <label class="inline-flex items-center gap-3 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700">
                                        <input v-model="promoDrafts[promo.id].is_active" type="checkbox" class="size-4 rounded border-slate-300 text-slate-950" />
                                        Promo aktif
                                    </label>

                                    <Button class="h-11 rounded-2xl px-5 text-sm font-semibold" @click="savePromo(promo.id)">
                                        Simpan perubahan
                                    </Button>
                                </div>
                            </article>
                        </div>
                    </section>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
