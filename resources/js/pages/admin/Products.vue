<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { catalogProducts, categoryChips } from '@/data/catalog';
import { applyProductDisplayOverride } from '@/data/product-display-overrides';
import { compareProductsByOrdering } from '@/data/product-ordering';
import { withFullProductArtwork } from '@/data/product-artwork';
import AdminLayout from '@/layouts/AdminLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { CheckCircle2, ImagePlus, Layers3, Search, Sparkles, Trash2 } from 'lucide-vue-next';
import { computed, onBeforeUnmount, ref, watch } from 'vue';

type VipCatalogProduct = {
    id: string;
    name: string;
    categoryId: string;
    categoryTitle: string;
    badge?: string | null;
};

const props = defineProps<{
    status?: string;
    vipCatalogProducts?: VipCatalogProduct[];
    overrides: Record<string, { coverImage: string; iconImage: string }>;
    displayOverrides: Record<string, { name?: string | null; categoryTitle?: string | null; badge?: string | null }>;
    hiddenProductIds: string[];
    orderingOverrides: Record<string, { pinned?: boolean; sortOrder?: number | null }>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard Admin', href: '/dashboard' },
    { title: 'Setting Produk', href: '/dashboard/produk' },
];

const search = ref('');
const imageInput = ref<HTMLInputElement | null>(null);
const previewUrl = ref<string | null>(null);
const badgePresets = ['Promo', 'Terlaris', 'Cashback', 'Baru', 'Populer', 'Value', 'Global', 'Gift', 'Login', 'Hot'];
const categoryPresets = categoryChips
    .filter((chip) => !['popular', 'release'].includes(chip.id))
    .map((chip) => chip.label);

const baseProducts = computed(() => {
    const mergedProducts = new Map<string, (typeof catalogProducts)[number] | VipCatalogProduct>();

    catalogProducts.forEach((product) => {
        mergedProducts.set(product.id, product);
    });

    (props.vipCatalogProducts ?? []).forEach((product) => {
        if (!mergedProducts.has(product.id)) {
            mergedProducts.set(product.id, product);
        }
    });

    return [...mergedProducts.values()];
});

const allProducts = computed(() =>
    baseProducts.value
        .map((product) => {
            const displayOverride = props.displayOverrides[product.id] ?? null;
            const artworkOverride = props.overrides[product.id] ?? null;
            const orderingOverride = props.orderingOverrides[product.id] ?? null;
            const baseProduct = withFullProductArtwork(product);
            const effectiveProduct = applyProductDisplayOverride(baseProduct, displayOverride);

            return {
                ...effectiveProduct,
                baseName: baseProduct.name,
                baseCategoryTitle: baseProduct.categoryTitle,
                baseBadge: baseProduct.badge ?? '',
                displayOverride,
                artworkOverride,
                orderingOverride,
                hidden: props.hiddenProductIds.includes(product.id),
                pinned: Boolean(orderingOverride?.pinned),
                sortOrder: orderingOverride?.sortOrder ?? null,
                source: catalogProducts.some((catalogProduct) => catalogProduct.id === product.id) ? 'local' : 'vipayment',
                hasOverride: Boolean(displayOverride || artworkOverride),
            };
        })
        .sort((left, right) => compareProductsByOrdering(left, right, props.orderingOverrides)),
);

const filteredProducts = computed(() =>
    allProducts.value.filter(
        (product) =>
            product.name.toLowerCase().includes(search.value.toLowerCase()) ||
            product.id.includes(search.value.toLowerCase()) ||
            product.baseName.toLowerCase().includes(search.value.toLowerCase()),
    ),
);

const selectedProductId = ref(allProducts.value[0]?.id ?? '');
const selectedProduct = computed(() => allProducts.value.find((product) => product.id === selectedProductId.value) ?? null);

const overrideCount = computed(() => allProducts.value.filter((product) => product.hasOverride).length);
const defaultCount = computed(() => allProducts.value.length - overrideCount.value);
const hiddenCount = computed(() => allProducts.value.filter((product) => product.hidden).length);

const form = useForm({
    image: null as File | null,
    name: '',
    category_title: '',
    badge: '',
    pinned: false as boolean,
    sort_order: '',
});

const removeForm = useForm({});
const visibilityForm = useForm({
    hidden: false as boolean,
});

const currentCoverImage = computed(() => previewUrl.value || selectedProduct.value?.artworkOverride?.coverImage || selectedProduct.value?.coverImage || null);
const currentIconImage = computed(() => previewUrl.value || selectedProduct.value?.artworkOverride?.iconImage || selectedProduct.value?.iconImage || null);
const selectedFileName = computed(() => form.image?.name ?? null);
const selectedStateLabel = computed(() => (selectedProduct.value?.hasOverride ? 'Custom override aktif' : 'Masih pakai data default'));
const currentNamePreview = computed(() => form.name.trim() || selectedProduct.value?.baseName || '');
const currentCategoryPreview = computed(() => form.category_title.trim() || selectedProduct.value?.baseCategoryTitle || '');
const currentBadgePreview = computed(() => {
    if (!selectedProduct.value) {
        return undefined;
    }

    return form.badge.trim() || undefined;
});
const selectedVisibilityLabel = computed(() => (selectedProduct.value?.hidden ? 'Disembunyikan dari katalog publik' : 'Tampil di katalog publik'));
const selectedPinLabel = computed(() => (form.pinned ? 'Diprioritaskan di atas' : 'Urutan normal'));

const resetPendingImage = () => {
    if (previewUrl.value?.startsWith('blob:')) {
        URL.revokeObjectURL(previewUrl.value);
    }

    previewUrl.value = null;
    form.image = null;

    if (imageInput.value) {
        imageInput.value.value = '';
    }
};

const openPicker = () => imageInput.value?.click();

const onFileChange = (event: Event) => {
    const input = event.target as HTMLInputElement | null;
    const file = input?.files?.[0] ?? null;

    resetPendingImage();

    form.image = file;
    previewUrl.value = file ? URL.createObjectURL(file) : null;
};

const submit = () => {
    if (!selectedProduct.value) {
        return;
    }

    const productPath = `/dashboard/produk/${encodeURIComponent(selectedProduct.value.id)}`;

    const normalizedName = form.name.trim();
    const normalizedCategoryTitle = form.category_title.trim();
    const normalizedBadge = form.badge.trim();
    const normalizedSortOrder = form.sort_order.trim();
    const parsedSortOrder = normalizedSortOrder !== '' ? Number.parseInt(normalizedSortOrder, 10) : null;
    const hasDisplayChange =
        normalizedName !== selectedProduct.value.baseName ||
        normalizedCategoryTitle !== selectedProduct.value.baseCategoryTitle ||
        normalizedBadge !== (selectedProduct.value.baseBadge ?? '') ||
        form.pinned !== selectedProduct.value.pinned ||
        parsedSortOrder !== selectedProduct.value.sortOrder;

    if (!form.image && !hasDisplayChange) {
        return;
    }

    form.transform((data) => {
        const payload: Record<string, File | string | null> = {};

        if (data.image) {
            payload.image = data.image;
        }

        if (normalizedName !== selectedProduct.value?.baseName) {
            payload.name = normalizedName;
        }

        if (normalizedCategoryTitle !== selectedProduct.value?.baseCategoryTitle) {
            payload.category_title = normalizedCategoryTitle;
        }

        if (normalizedBadge !== (selectedProduct.value?.baseBadge ?? '')) {
            payload.badge = normalizedBadge;
        }

        if (form.pinned !== selectedProduct.value?.pinned) {
            payload.pinned = form.pinned ? '1' : '0';
        }

        if (parsedSortOrder !== selectedProduct.value?.sortOrder) {
            payload.sort_order = normalizedSortOrder;
        }

        return payload;
    }).post(productPath, {
        forceFormData: true,
        preserveScroll: true,
        onFinish: () => {
            form.transform((data) => data);
        },
        onSuccess: () => {
            resetPendingImage();
        },
    });
};

const removeOverride = () => {
    if (!selectedProduct.value) {
        return;
    }

    removeForm.delete(`/dashboard/produk/${encodeURIComponent(selectedProduct.value.id)}`, {
        preserveScroll: true,
        onSuccess: () => {
            resetPendingImage();
        },
    });
};

const toggleVisibility = () => {
    if (!selectedProduct.value) {
        return;
    }

    visibilityForm.hidden = !selectedProduct.value.hidden;

    visibilityForm.patch(`/dashboard/produk/${encodeURIComponent(selectedProduct.value.id)}/visibility`, {
        preserveScroll: true,
    });
};

watch(selectedProductId, () => {
    resetPendingImage();
});

watch(
    selectedProduct,
    (product) => {
        form.name = product?.name ?? '';
        form.category_title = product?.categoryTitle ?? '';
        form.badge = product?.badge ?? '';
        form.pinned = product?.pinned ?? false;
        form.sort_order = product?.sortOrder !== null && product?.sortOrder !== undefined ? String(product.sortOrder) : '';
        form.clearErrors();
    },
    { immediate: true },
);

onBeforeUnmount(() => {
    resetPendingImage();
});
</script>

<template>
    <Head title="Setting Produk" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4">
            <section
                class="overflow-hidden rounded-[34px] border border-slate-200/80 bg-[radial-gradient(circle_at_top_left,rgba(96,165,250,0.14),transparent_32%),linear-gradient(180deg,rgba(255,255,255,0.98),rgba(248,250,252,0.96))] p-6 shadow-[0_28px_80px_rgba(15,23,42,0.08)] lg:p-8"
            >
                <div class="grid gap-6 xl:grid-cols-[minmax(0,1.15fr)_minmax(320px,0.85fr)]">
                    <div>
                        <div class="text-[11px] font-semibold uppercase tracking-[0.28em] text-sky-500">Product Control</div>
                        <h1 class="mt-3 max-w-3xl text-3xl font-semibold tracking-[-0.04em] text-slate-950 lg:text-[2.65rem]">
                            Setting produk yang lebih rapi untuk ganti artwork tanpa sentuh file manual.
                        </h1>
                        <p class="mt-4 max-w-2xl text-sm leading-7 text-slate-600">
                            Semua produk lokal dan VIPayment muncul di sini. Override gambar, nama, kategori, dan badge yang kamu simpan akan otomatis dipakai di home,
                            pencarian, dan halaman detail produk.
                        </p>

                        <div class="mt-6 grid gap-3 sm:grid-cols-3">
                            <div class="rounded-[24px] border border-white/80 bg-white/80 p-4 shadow-[0_14px_34px_rgba(148,163,184,0.12)]">
                                <div class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-400">Total Produk</div>
                                <div class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">{{ allProducts.length }}</div>
                                <p class="mt-2 text-sm text-slate-500">Semua produk yang bisa kamu atur gambarnya dari panel ini.</p>
                            </div>
                            <div class="rounded-[24px] border border-emerald-100 bg-emerald-50/80 p-4 shadow-[0_14px_34px_rgba(16,185,129,0.08)]">
                                <div class="text-[11px] font-semibold uppercase tracking-[0.2em] text-emerald-500">Override Aktif</div>
                                <div class="mt-2 text-3xl font-semibold tracking-tight text-emerald-700">{{ overrideCount }}</div>
                                <p class="mt-2 text-sm text-emerald-700/80">Produk yang sudah pakai override tampilan dari panel admin.</p>
                            </div>
                            <div class="rounded-[24px] border border-slate-200 bg-slate-50/90 p-4 shadow-[0_14px_34px_rgba(148,163,184,0.08)]">
                                <div class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-400">Masih Default</div>
                                <div class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">{{ defaultCount }}</div>
                                <p class="mt-2 text-sm text-slate-500">Produk yang masih mengikuti gambar bawaan katalog.</p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-[30px] border border-slate-200/80 bg-white/80 p-5 shadow-[0_18px_44px_rgba(148,163,184,0.12)] backdrop-blur-sm">
                        <div class="flex items-start gap-3">
                            <div class="flex size-11 items-center justify-center rounded-[20px] bg-sky-100 text-sky-600">
                                <Sparkles class="size-5" />
                            </div>
                            <div class="min-w-0">
                                <div class="text-sm font-semibold text-slate-950">Alur kerjanya cepat</div>
                                <p class="mt-1 text-sm leading-6 text-slate-600">
                                    Pilih produk, cek preview cover dan icon, lalu upload artwork baru. Setelah disimpan, tampilan publik ikut berubah otomatis.
                                </p>
                            </div>
                        </div>

                        <div class="mt-5 space-y-3">
                            <div class="flex items-start gap-3 rounded-[22px] border border-slate-200 bg-slate-50/80 p-4">
                                <div class="mt-0.5 flex size-9 items-center justify-center rounded-full bg-white text-slate-700 shadow-sm">
                                    <Layers3 class="size-4" />
                                </div>
                                <div>
                                    <div class="text-sm font-semibold text-slate-950">Preview lengkap</div>
                                    <p class="mt-1 text-sm leading-6 text-slate-500">Kamu bisa lihat cover, icon, dan simulasi card mini sebelum upload disimpan.</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-3 rounded-[22px] border border-slate-200 bg-slate-50/80 p-4">
                                <div class="mt-0.5 flex size-9 items-center justify-center rounded-full bg-white text-slate-700 shadow-sm">
                                    <CheckCircle2 class="size-4" />
                                </div>
                                <div>
                                    <div class="text-sm font-semibold text-slate-950">Propagasi otomatis</div>
                                    <p class="mt-1 text-sm leading-6 text-slate-500">Artwork yang aktif langsung dipakai di home, detail produk, dan identitas brand per produk.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="grid gap-6 xl:grid-cols-[minmax(320px,0.92fr)_minmax(0,1.08fr)]">
                <div class="rounded-[30px] border border-slate-200/80 bg-white p-5 shadow-[0_24px_70px_rgba(15,23,42,0.07)]">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <div class="text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-400">Library Produk</div>
                            <h2 class="mt-2 text-2xl font-semibold tracking-tight text-slate-950">Pilih produk yang mau diatur</h2>
                            <p class="mt-2 text-sm leading-6 text-slate-500">Cari produk berdasarkan nama atau ID, lalu edit identitas tampil dan artwork-nya dari panel kanan.</p>
                        </div>

                        <div class="rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-sm font-semibold text-slate-600">
                            {{ filteredProducts.length }} dari {{ allProducts.length }} produk
                        </div>
                    </div>

                    <div class="relative mt-5">
                        <Search class="pointer-events-none absolute left-4 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                        <Input v-model="search" class="h-12 rounded-2xl border-slate-200 pl-11 text-[15px]" placeholder="Cari nama produk atau ID..." />
                    </div>

                        <div class="mt-4 flex flex-wrap gap-2">
                            <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold text-slate-600">
                                {{ overrideCount }} custom aktif
                            </span>
                            <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold text-slate-600">
                                {{ defaultCount }} masih default
                            </span>
                            <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold text-slate-600">
                                {{ hiddenCount }} disembunyikan
                            </span>
                        </div>

                    <div v-if="filteredProducts.length" class="mt-5 grid max-h-[760px] gap-3 overflow-y-auto pr-1">
                        <button
                            v-for="product in filteredProducts"
                            :key="product.id"
                            type="button"
                            class="group flex items-center gap-3 rounded-[24px] border px-4 py-3 text-left transition-all duration-200"
                            :class="
                                selectedProductId === product.id
                                    ? 'border-sky-300 bg-sky-50 shadow-[0_14px_34px_rgba(59,130,246,0.14)]'
                                    : 'border-slate-200 bg-slate-50/70 hover:border-slate-300 hover:bg-white hover:shadow-[0_12px_28px_rgba(148,163,184,0.12)]'
                            "
                            @click="selectedProductId = product.id"
                        >
                            <div class="flex size-16 items-center justify-center overflow-hidden rounded-[20px] bg-white ring-1 ring-slate-200 shadow-sm">
                                <img :src="product.artworkOverride?.iconImage || product.iconImage" :alt="product.name" class="size-full object-cover" />
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="truncate text-[15px] font-semibold tracking-[-0.02em] text-slate-950">{{ product.name }}</div>
                                <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                                    <span class="rounded-full bg-white px-2.5 py-1 ring-1 ring-slate-200">{{ product.categoryTitle }}</span>
                                    <span class="rounded-full bg-white px-2.5 py-1 ring-1 ring-slate-200">{{ product.source === 'vipayment' ? 'VIPayment' : 'Lokal' }}</span>
                                    <span v-if="product.pinned" class="rounded-full bg-amber-50 px-2.5 py-1 text-amber-700 ring-1 ring-amber-200">Pinned</span>
                                    <span v-if="product.sortOrder !== null" class="rounded-full bg-indigo-50 px-2.5 py-1 text-indigo-700 ring-1 ring-indigo-200">
                                        Urutan {{ product.sortOrder }}
                                    </span>
                                    <span v-if="product.hidden" class="rounded-full bg-rose-50 px-2.5 py-1 text-rose-700 ring-1 ring-rose-200">Hidden</span>
                                    <span class="truncate">{{ product.id }}</span>
                                </div>
                            </div>

                            <span
                                class="rounded-full px-3 py-1 text-[11px] font-semibold ring-1"
                                :class="product.hasOverride ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-white text-slate-500 ring-slate-200'"
                            >
                                {{ product.hasOverride ? 'Override aktif' : 'Default' }}
                            </span>
                        </button>
                    </div>

                    <div v-else class="mt-5 rounded-[24px] border border-dashed border-slate-200 bg-slate-50/70 px-6 py-12 text-center">
                        <div class="text-lg font-semibold text-slate-950">Produk tidak ditemukan</div>
                        <p class="mt-2 text-sm leading-6 text-slate-500">Coba ubah kata kunci pencarian supaya daftar produk muncul lagi.</p>
                    </div>
                </div>

                <div v-if="selectedProduct" class="space-y-6">
                    <div
                        v-if="status"
                        class="rounded-[24px] border border-emerald-200 bg-emerald-50/90 px-5 py-4 text-sm font-medium text-emerald-700 shadow-[0_14px_30px_rgba(16,185,129,0.08)]"
                    >
                        {{ status }}
                    </div>

                    <section class="rounded-[30px] border border-slate-200/80 bg-white p-6 shadow-[0_24px_70px_rgba(15,23,42,0.07)]">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <div class="text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-400">Preview Workspace</div>
                                <h2 class="mt-2 text-[2rem] font-semibold tracking-[-0.04em] text-slate-950">{{ currentNamePreview }}</h2>
                                <div class="mt-2 flex flex-wrap items-center gap-2 text-sm text-slate-500">
                                    <span class="rounded-full bg-slate-100 px-3 py-1 font-medium text-slate-700">{{ currentCategoryPreview }}</span>
                                    <span class="rounded-full bg-slate-100 px-3 py-1 font-medium text-slate-700">
                                        {{ selectedProduct.source === 'vipayment' ? 'Produk VIPayment' : 'Produk lokal' }}
                                    </span>
                                    <span
                                        class="rounded-full px-3 py-1 font-medium ring-1"
                                        :class="selectedProduct.hidden ? 'bg-rose-50 text-rose-700 ring-rose-200' : 'bg-emerald-50 text-emerald-700 ring-emerald-200'"
                                    >
                                        {{ selectedVisibilityLabel }}
                                    </span>
                                    <span
                                        class="rounded-full px-3 py-1 font-medium ring-1"
                                        :class="form.pinned ? 'bg-amber-50 text-amber-700 ring-amber-200' : 'bg-slate-100 text-slate-700 ring-slate-200'"
                                    >
                                        {{ selectedPinLabel }}
                                    </span>
                                    <span v-if="form.sort_order.trim()" class="rounded-full bg-indigo-50 px-3 py-1 font-medium text-indigo-700 ring-1 ring-indigo-200">
                                        Urutan {{ form.sort_order.trim() }}
                                    </span>
                                    <span v-if="currentBadgePreview" class="rounded-full bg-amber-50 px-3 py-1 font-medium text-amber-700 ring-1 ring-amber-200">
                                        {{ currentBadgePreview }}
                                    </span>
                                    <span>{{ selectedProduct.id }}</span>
                                </div>
                            </div>

                            <span
                                class="rounded-full px-4 py-2 text-xs font-semibold ring-1"
                                :class="selectedProduct.hasOverride ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-slate-100 text-slate-600 ring-slate-200'"
                            >
                                {{ selectedStateLabel }}
                            </span>
                        </div>

                        <div class="mt-6 grid gap-4 xl:grid-cols-[minmax(0,1.08fr)_minmax(260px,0.92fr)]">
                            <div class="rounded-[28px] border border-slate-200 bg-slate-50/80 p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <div class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-400">Cover Produk</div>
                                        <div class="mt-1 text-sm text-slate-500">Dipakai untuk hero visual dan tampilan utama card.</div>
                                    </div>
                                    <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-600 ring-1 ring-slate-200">Preview besar</span>
                                </div>

                                <div class="mt-4 overflow-hidden rounded-[24px] border border-slate-200 bg-white shadow-inner">
                                    <img v-if="currentCoverImage" :src="currentCoverImage" :alt="selectedProduct.name" class="aspect-[4/5] w-full object-cover" />
                                </div>
                            </div>

                            <div class="grid gap-4">
                                <div class="rounded-[28px] border border-slate-200 bg-slate-50/80 p-4">
                                    <div class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-400">Icon Produk</div>
                                    <div class="mt-1 text-sm text-slate-500">Digunakan di list produk dan card mini.</div>

                                    <div class="mt-4 flex items-center justify-center rounded-[24px] border border-slate-200 bg-white p-6">
                                        <div class="flex size-28 items-center justify-center overflow-hidden rounded-[28px] bg-slate-100 ring-1 ring-slate-200 shadow-[0_18px_40px_rgba(148,163,184,0.14)]">
                                            <img v-if="currentIconImage" :src="currentIconImage" :alt="selectedProduct.name" class="size-full object-cover" />
                                        </div>
                                    </div>
                                </div>

                                <div class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-[0_16px_36px_rgba(148,163,184,0.12)]">
                                    <div class="px-4 pt-4 text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-400">Simulasi Card Mini</div>

                                    <div class="relative m-4 overflow-hidden rounded-[26px] border border-slate-200 px-4 py-5 text-white shadow-[0_22px_46px_rgba(59,130,246,0.14)]">
                                        <div class="absolute inset-0" :style="{ background: selectedProduct.background }"></div>
                                        <img
                                            v-if="currentCoverImage"
                                            :src="currentCoverImage"
                                            :alt="selectedProduct.name"
                                            class="absolute inset-0 h-full w-full object-cover opacity-20"
                                        />
                                        <div class="absolute inset-0 bg-[linear-gradient(145deg,rgba(15,23,42,0.2),rgba(15,23,42,0.72))]"></div>

                                        <div class="relative flex items-center gap-3">
                                            <div class="flex size-14 items-center justify-center overflow-hidden rounded-[18px] bg-white/20 ring-1 ring-white/20 backdrop-blur-sm">
                                                <img v-if="currentIconImage" :src="currentIconImage" :alt="selectedProduct.name" class="size-full object-cover" />
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <div class="text-[11px] font-semibold uppercase tracking-[0.18em] text-white/70">{{ currentCategoryPreview }}</div>
                                                <div class="truncate pt-1 text-[1.05rem] font-semibold tracking-[-0.02em] text-white">
                                                    {{ currentNamePreview }}
                                                </div>
                                                <div class="pt-1 text-xs text-white/70">{{ selectedProduct.hasOverride ? 'Override aktif' : 'Data default' }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-[30px] border border-slate-200/80 bg-white p-6 shadow-[0_24px_70px_rgba(15,23,42,0.07)]">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <div class="text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-400">Edit Produk</div>
                                <h3 class="mt-2 text-2xl font-semibold tracking-tight text-slate-950">Atur gambar dan identitas untuk {{ currentNamePreview }}</h3>
                                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                                    Kamu bisa upload gambar baru sekaligus ubah nama tampil, kategori, dan badge produk tanpa sentuh file manual.
                                </p>
                            </div>

                            <span class="rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">
                                JPG, PNG, WEBP up to 3 MB
                            </span>
                        </div>

                        <div class="mt-5 grid gap-4 lg:grid-cols-3">
                            <div class="rounded-[24px] border border-slate-200 bg-slate-50/70 p-4">
                                <label class="text-sm font-semibold text-slate-950">Nama tampil</label>
                                <Input v-model="form.name" class="mt-3 h-11 rounded-2xl border-slate-200 bg-white" placeholder="Nama produk di katalog" />
                                <p class="mt-2 text-xs leading-5 text-slate-500">Dipakai di card produk, pencarian, dan halaman detail.</p>
                            </div>
                            <div class="rounded-[24px] border border-slate-200 bg-slate-50/70 p-4">
                                <label class="text-sm font-semibold text-slate-950">Kategori tampil</label>
                                <Input v-model="form.category_title" class="mt-3 h-11 rounded-2xl border-slate-200 bg-white" placeholder="Kategori yang mau ditampilkan" />
                                <p class="mt-2 text-xs leading-5 text-slate-500">Cocok untuk rapikan kategori produk VIPayment yang kurang pas.</p>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <button
                                        v-for="preset in categoryPresets"
                                        :key="preset"
                                        type="button"
                                        class="rounded-full border px-3 py-1 text-xs font-semibold transition"
                                        :class="
                                            form.category_title === preset
                                                ? 'border-indigo-300 bg-indigo-50 text-indigo-700'
                                                : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300 hover:text-slate-900'
                                        "
                                        @click="form.category_title = preset"
                                    >
                                        {{ preset }}
                                    </button>
                                </div>
                            </div>
                            <div class="rounded-[24px] border border-slate-200 bg-slate-50/70 p-4">
                                <label class="text-sm font-semibold text-slate-950">Badge produk</label>
                                <Input v-model="form.badge" class="mt-3 h-11 rounded-2xl border-slate-200 bg-white" placeholder="Contoh: Terlaris, Baru, Promo" />
                                <p class="mt-2 text-xs leading-5 text-slate-500">Kosongkan kalau tidak ingin badge tampil.</p>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <button
                                        v-for="preset in badgePresets"
                                        :key="preset"
                                        type="button"
                                        class="rounded-full border px-3 py-1 text-xs font-semibold transition"
                                        :class="
                                            form.badge === preset
                                                ? 'border-amber-300 bg-amber-50 text-amber-700'
                                                : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300 hover:text-slate-900'
                                        "
                                        @click="form.badge = preset"
                                    >
                                        {{ preset }}
                                    </button>
                                    <button
                                        type="button"
                                        class="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-500 transition hover:border-slate-300 hover:text-slate-900"
                                        @click="form.badge = ''"
                                    >
                                        Hapus badge
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 grid gap-4 lg:grid-cols-2">
                            <div class="rounded-[24px] border border-slate-200 bg-slate-50/70 p-4">
                                <label class="text-sm font-semibold text-slate-950">Urutan tampil</label>
                                <Input
                                    v-model="form.sort_order"
                                    class="mt-3 h-11 rounded-2xl border-slate-200 bg-white"
                                    placeholder="Contoh: 1 untuk paling atas"
                                    inputmode="numeric"
                                />
                                <p class="mt-2 text-xs leading-5 text-slate-500">Angka lebih kecil tampil lebih dulu. Kosongkan kalau mau ikut urutan normal.</p>
                            </div>

                            <div class="rounded-[24px] border border-slate-200 bg-slate-50/70 p-4">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <div>
                                        <div class="text-sm font-semibold text-slate-950">Pin ke atas</div>
                                        <p class="mt-2 text-xs leading-5 text-slate-500">Produk pinned akan diprioritaskan tampil paling atas di kategori dan hasil pencarian.</p>
                                    </div>
                                    <Button type="button" variant="outline" class="rounded-full px-5" @click="form.pinned = !form.pinned">
                                        {{ form.pinned ? 'Lepas pin' : 'Pin produk' }}
                                    </Button>
                                </div>
                            </div>
                        </div>

                        <input ref="imageInput" type="file" accept=".jpg,.jpeg,.png,.webp" class="hidden" @change="onFileChange" />

                        <div class="mt-5 rounded-[24px] border border-slate-200 bg-slate-50/70 p-4">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <div class="text-sm font-semibold text-slate-950">Visibilitas katalog publik</div>
                                    <p class="mt-1 text-sm leading-6 text-slate-500">
                                        Kontrol apakah produk ini tampil di home dan pencarian publik atau hanya tetap tersedia di panel admin.
                                    </p>
                                </div>
                                <Button
                                    type="button"
                                    variant="outline"
                                    :disabled="visibilityForm.processing"
                                    class="rounded-full px-5"
                                    @click="toggleVisibility"
                                >
                                    {{ selectedProduct.hidden ? 'Tampilkan lagi' : 'Sembunyikan dari home' }}
                                </Button>
                            </div>
                        </div>

                        <button
                            type="button"
                            class="mt-5 flex w-full items-center justify-between gap-4 rounded-[28px] border border-dashed border-slate-300 bg-slate-50/70 px-5 py-5 text-left transition-all duration-200 hover:border-sky-300 hover:bg-sky-50/60"
                            @click="openPicker"
                        >
                            <div class="min-w-0">
                                <div class="text-base font-semibold tracking-[-0.02em] text-slate-950">
                                    {{ selectedFileName ? 'File siap diupload' : 'Pilih artwork baru dari perangkat kamu' }}
                                </div>
                                <div class="mt-1 truncate text-sm text-slate-500">
                                    {{ selectedFileName ?? 'Klik area ini untuk memilih file gambar yang akan dipakai.' }}
                                </div>
                            </div>

                            <div class="flex size-12 shrink-0 items-center justify-center rounded-[18px] bg-white text-slate-700 ring-1 ring-slate-200 shadow-sm">
                                <ImagePlus class="size-5" />
                            </div>
                        </button>

                        <div class="mt-4 grid gap-3 md:grid-cols-3">
                            <div class="rounded-[22px] border border-slate-200 bg-slate-50/70 p-4">
                                <div class="text-sm font-semibold text-slate-950">Auto sinkron</div>
                                <p class="mt-2 text-sm leading-6 text-slate-500">Cover dan icon preview akan ikut berubah dari file yang kamu pilih.</p>
                            </div>
                            <div class="rounded-[22px] border border-slate-200 bg-slate-50/70 p-4">
                                <div class="text-sm font-semibold text-slate-950">Lebih aman</div>
                                <p class="mt-2 text-sm leading-6 text-slate-500">Saat kamu pindah produk, file sementara akan otomatis direset biar tidak tertukar.</p>
                            </div>
                            <div class="rounded-[22px] border border-slate-200 bg-slate-50/70 p-4">
                                <div class="text-sm font-semibold text-slate-950">Publik langsung ikut</div>
                                <p class="mt-2 text-sm leading-6 text-slate-500">Nama, kategori, badge, dan artwork yang disimpan langsung dipakai di home, pencarian, dan halaman produk.</p>
                            </div>
                        </div>

                        <InputError class="mt-4" :message="form.errors.image" />
                        <InputError class="mt-2" :message="form.errors.name" />
                        <InputError class="mt-2" :message="form.errors.category_title" />
                        <InputError class="mt-2" :message="form.errors.badge" />

                        <div class="mt-6 flex flex-wrap gap-3">
                            <Button type="button" class="rounded-full px-5" variant="outline" @click="openPicker">
                                <ImagePlus class="mr-2 size-4" />
                                {{ selectedFileName ? 'Ganti file' : 'Pilih gambar' }}
                            </Button>

                            <Button :disabled="form.processing" class="rounded-full px-5" @click="submit">
                                Simpan override produk
                            </Button>

                            <Button
                                v-if="selectedProduct.hasOverride"
                                type="button"
                                variant="outline"
                                :disabled="removeForm.processing"
                                class="rounded-full border-rose-200 px-5 text-rose-600 hover:bg-rose-50 hover:text-rose-700"
                                @click="removeOverride"
                            >
                                <Trash2 class="mr-2 size-4" />
                                Reset ke default
                            </Button>
                        </div>
                    </section>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
