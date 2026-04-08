<script setup lang="ts">
import { Dialog, DialogContent, DialogTitle } from '@/components/ui/dialog';
import {
    categoryChips,
    getProductFamilyRule,
    getProductFamilyVariantLabel,
    isPublicCatalogProductVisible,
    orderedProductSections,
    sortProductFamilyProducts,
    type ProductCard,
    type ProductSection,
} from '@/data/catalog';
import { applyProductArtworkOverride, withFullProductArtwork } from '@/data/product-artwork';
import { applyProductDisplayOverride } from '@/data/product-display-overrides';
import { compareProductsByOrdering } from '@/data/product-ordering';
import PublicLayout from '@/layouts/PublicLayout.vue';
import type { SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight, LayoutGrid } from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

type VipCatalogProduct = {
    id: string;
    name: string;
    categoryId: string;
    categoryTitle: string;
    badge?: string | null;
};

const props = defineProps<{
    vipCatalogProducts?: VipCatalogProduct[];
}>();
const page = usePage<SharedData>();
const isPromoPopupOpen = ref(false);
const siteUrl = 'https://lyvaindonesia.com';
const homeTitle = 'Lyva | Top Up Game & Voucher Digital | Lyva Indonesia';
const homeDescription =
    'Lyva adalah Lyva Indonesia, tempat top up game, voucher digital, e-wallet, pulsa, dan langganan premium dengan proses cepat, aman, dan praktis untuk Mobile Legends, Free Fire, PUBG Mobile, ChatGPT, dan banyak layanan lainnya.';
const homeKeywords =
    'lyva, lyva indonesia, lyva top up, top up lyva, top up, top up game, top up game terpercaya, top up mobile legends, top up free fire, top up pubg mobile, voucher digital, top up chatgpt, chatgpt premium, chatgpt premium murah, top up chatgpt premium, top up capcut, top up canva';
const homeIntroTitle = 'Lyva Indonesia';
const homeIntroDescription =
    'Lyva Indonesia adalah brand Lyva untuk top up game, voucher digital, e-wallet, pulsa, dan langganan premium yang cepat, aman, dan praktis.';
const homeSearchTags = [
    'Top Up',
    'Top Up Game',
    'Top Up Mobile Legends',
    'Top Up Free Fire',
    'Top Up PUBG Mobile',
    'Top Up ChatGPT',
    'ChatGPT Premium',
    'ChatGPT Premium Murah',
    'Voucher Digital',
    'Pulsa & E-Wallet',
];
const productArtworkOverrides = computed(() => page.props.adminPanel?.productArtworkOverrides ?? {});
const productDisplayOverrides = computed(() => page.props.adminPanel?.productDisplayOverrides ?? {});
const hiddenProductIds = computed(() => new Set(page.props.adminPanel?.hiddenProductIds ?? []));
const productOrderingOverrides = computed(() => page.props.adminPanel?.productOrderingOverrides ?? {});
const unavailableProductIds = computed(() => new Set(page.props.unavailableProductIds ?? []));
const cspNonce = computed(() => page.props.security?.cspNonce);
const homeStructuredData = JSON.stringify({
    '@context': 'https://schema.org',
    '@type': 'WebSite',
    name: 'Lyva Indonesia',
    alternateName: ['Lyva', 'LYVA INDONESIA'],
    url: siteUrl,
    description: homeDescription,
    inLanguage: 'id-ID',
    publisher: {
        '@type': 'Organization',
        name: 'Lyva Indonesia',
        url: siteUrl,
        logo: `${siteUrl}/brand/lyva-mascot-hd.png`,
    },
    keywords: homeKeywords,
    potentialAction: {
        '@type': 'SearchAction',
        target: `${siteUrl}/?q={search_term_string}`,
        'query-input': 'required name=search_term_string',
    },
});

type PromoSlide = {
    id: number;
    image: string;
};

type FamilyVariant = {
    label: string;
    product: ProductCard;
};

type ProductSectionEntry =
    | {
          type: 'product';
          key: string;
          product: ProductCard;
      }
    | {
          type: 'family';
          key: string;
          product: ProductCard;
          familyId: string;
          title: string;
          subtitle: string;
          accent: string;
          background: string;
          badge: string;
          coverImage?: string;
          monogram: string;
          variants: FamilyVariant[];
      };

type DisplaySection = Omit<ProductSection, 'items'> & {
    entries: ProductSectionEntry[];
};

const slides: PromoSlide[] = [
    {
        id: 1,
        image: '/slide/Gemini_Generated_Image_pt786lpt786lpt78.png',
    },
    {
        id: 2,
        image: '/slide/Gemini_Generated_Image_dba2pgdba2pgdba2.png',
    },
    {
        id: 3,
        image: '/slide/Gemini_Generated_Image_cpgugwcpgugwcpgu.png',
    },
    {
        id: 4,
        image: '/slide/Gemini_Generated_Image_91u5sk91u5sk91u5.png',
    },
];

const sectionIconMap = new Map<string, (typeof categoryChips)[number]['icon']>([
    ...categoryChips.map((chip) => [chip.id, chip.icon] as const),
    ...orderedProductSections.map((section) => [section.id, section.icon] as const),
]);

const categoryIdByLabel = new Map<string, string>([
    ...categoryChips.map((chip) => [chip.label.toLowerCase(), chip.id] as const),
    ...orderedProductSections.map((section) => [section.title.toLowerCase(), section.id] as const),
]);

const buildHomeDedupKey = (product: Pick<ProductCard, 'id' | 'name'>) => {
    const familyRule = getProductFamilyRule(product);

    if (familyRule) {
        return `family:${familyRule.id}`;
    }

    return `product:${product.name.trim().toLowerCase()}`;
};

const localProductKeys = new Set(orderedProductSections.flatMap((section) => section.items.map((item) => buildHomeDedupKey(item))));

const extraVipSections = computed<ProductSection[]>(() => {
    const groupedSections = new Map<string, ProductSection>();

    for (const product of (props.vipCatalogProducts ?? []).filter(
        (entry) => isPublicCatalogProductVisible(entry) && !hiddenProductIds.value.has(entry.id),
    )) {
        const displayOverride = productDisplayOverrides.value[product.id];
        const categoryTitle = displayOverride?.categoryTitle || product.categoryTitle || 'Produk Lainnya';
        const categoryId = categoryIdByLabel.get(categoryTitle.toLowerCase()) || product.categoryId || 'instant';
        const productKey = buildHomeDedupKey(product);

        if (localProductKeys.has(productKey)) {
            continue;
        }

        if (!groupedSections.has(categoryId)) {
            groupedSections.set(categoryId, {
                id: categoryId,
                title: categoryTitle,
                icon: sectionIconMap.get(categoryId) ?? LayoutGrid,
                items: [],
            });
        }

        groupedSections
            .get(categoryId)
            ?.items.push(
                applyProductArtworkOverride(
                    withFullProductArtwork(applyProductDisplayOverride({ ...product, badge: undefined }, displayOverride)),
                    productArtworkOverrides.value[product.id],
                ),
            );
    }

    return [...groupedSections.values()].map((section) => ({
        ...section,
        items: [...section.items].sort((left, right) => compareProductsByOrdering(left, right, productOrderingOverrides.value)),
    }));
});

const homeSections = computed(() => {
    const sectionMap = new Map<string, ProductSection>();

    orderedProductSections.forEach((section) => {
        sectionMap.set(section.id, {
            ...section,
            items: section.items
                .map((item) =>
                    applyProductArtworkOverride(
                        applyProductDisplayOverride(item, productDisplayOverrides.value[item.id]),
                        productArtworkOverrides.value[item.id],
                    ),
                )
                .filter((item) => !hiddenProductIds.value.has(item.id) && !unavailableProductIds.value.has(item.id))
                .sort((left, right) => compareProductsByOrdering(left, right, productOrderingOverrides.value)),
        });
    });

    extraVipSections.value.forEach((section) => {
        const currentSection = sectionMap.get(section.id);

        if (currentSection) {
            currentSection.items = [...currentSection.items, ...section.items];
            return;
        }

        sectionMap.set(section.id, section);
    });

    const orderedIds = categoryChips.map((chip) => chip.id);
    const orderedSections = orderedIds.map((id) => sectionMap.get(id)).filter((section): section is ProductSection => Boolean(section));

    const extraSections = [...sectionMap.values()].filter((section) => !orderedIds.includes(section.id));

    return [...orderedSections, ...extraSections];
});

const buildSectionEntries = (
    section: ProductSection,
    allProducts: ProductCard[],
    consumedProducts: Set<string>,
    consumedFamilies: Set<string>,
    consumedEntryKeys: Set<string>,
): ProductSectionEntry[] => {
    const entries: ProductSectionEntry[] = [];

    for (const product of section.items) {
        if (consumedProducts.has(product.id)) {
            continue;
        }

        const familyRule = getProductFamilyRule(product);

        if (familyRule) {
            if (consumedFamilies.has(familyRule.id)) {
                consumedProducts.add(product.id);
                continue;
            }

            const familyProducts = allProducts.filter((candidate) => !consumedProducts.has(candidate.id) && familyRule.match(candidate));

            if (familyProducts.length > 1) {
                familyProducts.forEach((candidate) => consumedProducts.add(candidate.id));
                consumedFamilies.add(familyRule.id);
                consumedEntryKeys.add(buildHomeDedupKey(product));

                const sortedFamilyProducts = sortProductFamilyProducts(familyProducts, familyRule).sort((left, right) =>
                    compareProductsByOrdering(left, right, productOrderingOverrides.value),
                );
                const leadProduct = sortedFamilyProducts[0];
                const variants: FamilyVariant[] = sortedFamilyProducts.map((candidate) => ({
                    label: getProductFamilyVariantLabel(candidate, familyRule.title),
                    product: candidate,
                }));

                entries.push({
                    type: 'family',
                    key: `${section.id}:family:${familyRule.id}`,
                    product: leadProduct,
                    familyId: familyRule.id,
                    title: familyRule.title,
                    subtitle: `${variants.length} varian tersedia dalam satu keluarga produk.`,
                    accent: leadProduct.accent,
                    background: leadProduct.background,
                    badge: `${variants.length} varian`,
                    coverImage: leadProduct.coverImage,
                    monogram: leadProduct.monogram,
                    variants,
                });

                continue;
            }
        }

        const entryKey = buildHomeDedupKey(product);

        if (consumedEntryKeys.has(entryKey)) {
            consumedProducts.add(product.id);
            continue;
        }

        consumedEntryKeys.add(entryKey);
        consumedProducts.add(product.id);
        entries.push({
            type: 'product',
            key: `${section.id}:product:${product.id}`,
            product,
        });
    }

    return entries;
};

const displaySections = computed<DisplaySection[]>(() => {
    const allProducts = homeSections.value.flatMap((section) => section.items);
    const consumedProducts = new Set<string>();
    const consumedFamilies = new Set<string>();
    const consumedEntryKeys = new Set<string>();

    return homeSections.value
        .map((section) => ({
            ...section,
            entries: buildSectionEntries(section, allProducts, consumedProducts, consumedFamilies, consumedEntryKeys),
        }))
        .filter((section) => section.entries.length > 0);
});

const homeCategoryChips = computed(() => {
    const baseChips = categoryChips.filter((chip) => displaySections.value.some((section) => section.id === chip.id));
    const extraChips = displaySections.value
        .filter((section) => !categoryChips.some((chip) => chip.id === section.id))
        .map((section) => ({
            id: section.id,
            label: section.title,
            icon: section.icon,
        }));

    return [...baseChips, ...extraChips];
});

const visibleCategoryChips = computed(() =>
    homeCategoryChips.value.filter((chip) => displaySections.value.some((section) => section.id === chip.id)),
);
const activeCategory = ref('');
const activeIndex = ref(0);
const totalSlides = slides.length;
const viewportWidth = ref<number>(typeof window === 'undefined' ? 1280 : window.innerWidth);
const expandedSections = ref<Record<string, boolean>>({});
type SectionEntries = DisplaySection['entries'];

let autoplayHandle: number | null = null;
let categoryObserver: IntersectionObserver | null = null;

const visibleColumnCount = computed(() => {
    const width = viewportWidth.value;

    if (width >= 1536) {
        return 7;
    }

    if (width >= 1280) {
        return 6;
    }

    if (width >= 1024) {
        return 5;
    }

    if (width >= 768) {
        return 4;
    }

    if (width >= 640) {
        return 3;
    }

    return 3;
});

const maxVisibleProducts = computed(() => visibleColumnCount.value * 2);

const syncViewportWidth = () => {
    if (typeof window === 'undefined') {
        return;
    }

    viewportWidth.value = window.innerWidth;
};

const goToSlide = (index: number) => {
    activeIndex.value = (index + totalSlides) % totalSlides;
};

const nextSlide = () => {
    goToSlide(activeIndex.value + 1);
};

const previousSlide = () => {
    goToSlide(activeIndex.value - 1);
};

const stopAutoplay = () => {
    if (autoplayHandle !== null) {
        window.clearInterval(autoplayHandle);
        autoplayHandle = null;
    }
};

const restartAutoplay = () => {
    if (typeof window === 'undefined') {
        return;
    }

    stopAutoplay();
    autoplayHandle = window.setInterval(() => {
        nextSlide();
    }, 4500);
};

const selectSlide = (index: number) => {
    goToSlide(index);
    restartAutoplay();
};

const offsetFromActive = (index: number) => {
    let offset = index - activeIndex.value;

    if (offset > totalSlides / 2) {
        offset -= totalSlides;
    }

    if (offset < -totalSlides / 2) {
        offset += totalSlides;
    }

    return offset;
};

const isPreviewSlide = (index: number) => Math.abs(offsetFromActive(index)) <= 1;

const slideStyle = (index: number) => {
    const offset = offsetFromActive(index);

    if (offset === 0) {
        return {
            transform: 'translate(-50%, -50%) scale(1)',
            opacity: 1,
            zIndex: 30,
            filter: 'none',
        };
    }

    if (offset === -1) {
        return {
            transform: 'translate(calc(-50% - clamp(8rem, 16vw, 13rem)), -50%) scale(0.82)',
            opacity: 0.5,
            zIndex: 20,
            filter: 'saturate(0.85)',
        };
    }

    if (offset === 1) {
        return {
            transform: 'translate(calc(-50% + clamp(8rem, 16vw, 13rem)), -50%) scale(0.82)',
            opacity: 0.5,
            zIndex: 20,
            filter: 'saturate(0.85)',
        };
    }

    return {
        transform: 'translate(-50%, -50%) scale(0.7)',
        opacity: 0,
        zIndex: 0,
        filter: 'blur(10px)',
    };
};

const selectCategory = (categoryId: string) => {
    activeCategory.value = categoryId;

    if (typeof window === 'undefined') {
        return;
    }

    const targetSection = document.getElementById(`section-${categoryId}`);

    if (!targetSection) {
        return;
    }

    const topPosition = targetSection.getBoundingClientRect().top + window.scrollY - 154;

    window.scrollTo({
        top: topPosition,
        behavior: 'smooth',
    });
};

const isSectionExpanded = (sectionId: string) => Boolean(expandedSections.value[sectionId]);

const visibleProducts = (sectionId: string, totalItems: SectionEntries) =>
    isSectionExpanded(sectionId) ? totalItems : totalItems.slice(0, maxVisibleProducts.value);

const canExpandSection = (sectionId: string, totalItems: SectionEntries) =>
    totalItems.length > maxVisibleProducts.value && !isSectionExpanded(sectionId);

const hasCustomProductArtwork = (coverImage?: string | null) => Boolean(coverImage && !coverImage.startsWith('data:image/svg+xml'));

const getHomeCardImage = (entry: ProductSectionEntry) =>
    entry.product.iconImage || entry.product.coverImage || ('coverImage' in entry ? entry.coverImage || null : null);

const expandSection = (sectionId: string) => {
    expandedSections.value = {
        ...expandedSections.value,
        [sectionId]: true,
    };
};

const observeCategorySections = () => {
    if (typeof window === 'undefined' || !('IntersectionObserver' in window)) {
        return;
    }

    categoryObserver?.disconnect();

    categoryObserver = new IntersectionObserver(
        (entries) => {
            const currentEntry = entries
                .filter((entry) => entry.isIntersecting)
                .sort((left, right) => right.intersectionRatio - left.intersectionRatio)[0];

            if (!currentEntry) {
                return;
            }

            activeCategory.value = currentEntry.target.id.replace('section-', '');
        },
        {
            threshold: [0.25, 0.45, 0.65],
            rootMargin: '-20% 0px -58% 0px',
        },
    );

    displaySections.value.forEach((section) => {
        const element = document.getElementById(`section-${section.id}`);

        if (element) {
            categoryObserver?.observe(element);
        }
    });
};

watch(
    visibleCategoryChips,
    (chips) => {
        if (!chips.some((chip) => chip.id === activeCategory.value)) {
            activeCategory.value = chips[0]?.id ?? '';
        }
    },
    { immediate: true },
);

onMounted(() => {
    window.setTimeout(() => {
        isPromoPopupOpen.value = true;
    }, 500);

    syncViewportWidth();
    restartAutoplay();
    observeCategorySections();
    window.addEventListener('resize', syncViewportWidth, { passive: true });
});

onBeforeUnmount(() => {
    stopAutoplay();
    categoryObserver?.disconnect();
    categoryObserver = null;
    window.removeEventListener('resize', syncViewportWidth);
});

const closePromoPopup = () => {
    isPromoPopupOpen.value = false;
};
</script>

<template>
    <Head :title="homeTitle">
        <meta name="description" :content="homeDescription" />
        <meta name="keywords" :content="homeKeywords" />
        <link rel="canonical" :href="siteUrl" />
        <meta property="og:title" :content="homeTitle" />
        <meta property="og:description" :content="homeDescription" />
        <meta property="og:url" :content="siteUrl" />
        <meta property="og:image" :content="`${siteUrl}/brand/lyva-mascot-hd.png`" />
        <meta name="twitter:title" :content="homeTitle" />
        <meta name="twitter:description" :content="homeDescription" />
        <meta name="twitter:image" :content="`${siteUrl}/brand/lyva-mascot-hd.png`" />
        <component :is="'script'" :nonce="cspNonce" type="application/ld+json">
            {{ homeStructuredData }}
        </component>
    </Head>

    <PublicLayout active-nav="topup">
        <Dialog :open="isPromoPopupOpen" @update:open="(open) => (isPromoPopupOpen = open)">
            <DialogContent class="max-w-[34rem] gap-0 overflow-hidden rounded-[30px] border-0 bg-transparent p-0 shadow-[0_32px_90px_rgba(15,23,42,0.28)]">
                <DialogTitle class="sr-only">Promo popup</DialogTitle>
                <div class="relative">
                    <button
                        type="button"
                        class="absolute right-3 top-3 z-10 inline-flex h-10 w-10 items-center justify-center rounded-full bg-black/55 text-sm font-black text-white backdrop-blur transition hover:bg-black/70"
                        @click="closePromoPopup"
                    >
                        X
                    </button>
                    <img src="/popup/Gemini_Generated_Image_r465hpr465hpr465.png" alt="Popup promo LYVA Indonesia" class="block w-full rounded-[30px]" />
                </div>
            </DialogContent>
        </Dialog>

        <main class="pb-12 pt-6 sm:pb-16 sm:pt-8">
            <section class="relative isolate overflow-hidden">
                <div
                    class="pointer-events-none absolute inset-x-0 top-0 h-72"
                    style="background-image: radial-gradient(circle at top, rgba(67, 56, 202, 0.18), transparent 58%)"
                />

                <div class="mx-auto max-w-[1100px] px-4 sm:px-6 lg:px-8 xl:max-w-[1280px] 2xl:max-w-[1400px]">
                    <div class="relative mt-2 py-8 lg:py-10">
                        <div class="pointer-events-none absolute inset-0 overflow-hidden">
                            <div
                                class="hero-backdrop-card absolute left-[-2%] top-6 h-52 w-64 rotate-[-14deg] rounded-[40px] bg-white/70"
                                style="box-shadow: 0 18px 45px rgba(148, 163, 184, 0.1)"
                            />
                            <div
                                class="hero-backdrop-card hero-backdrop-card--alt absolute right-[-1%] top-10 h-56 w-64 rotate-[14deg] rounded-[40px] bg-white/55"
                                style="box-shadow: 0 18px 45px rgba(148, 163, 184, 0.08)"
                            />
                            <div
                                class="hero-backdrop-card hero-backdrop-card--soft absolute bottom-2 left-[8%] h-44 w-72 rotate-[8deg] rounded-[38px] bg-indigo-100/50"
                                style="box-shadow: 0 18px 45px rgba(99, 102, 241, 0.06)"
                            />
                            <div
                                class="hero-backdrop-card hero-backdrop-card--alt hero-backdrop-card--soft absolute bottom-4 right-[8%] h-40 w-64 rotate-[-10deg] rounded-[34px] bg-sky-100/60"
                                style="box-shadow: 0 18px 45px rgba(56, 189, 248, 0.06)"
                            />
                        </div>

                        <div class="relative" @mouseenter="stopAutoplay" @mouseleave="restartAutoplay">
                            <button
                                type="button"
                                class="absolute left-0 top-1/2 z-30 hidden h-12 w-12 -translate-y-1/2 items-center justify-center rounded-full bg-white text-slate-700 shadow-[0_18px_40px_rgba(15,23,42,0.15)] transition hover:-translate-y-[52%] hover:text-indigo-700 lg:inline-flex"
                                aria-label="Slide sebelumnya"
                                @click="
                                    previousSlide();
                                    restartAutoplay();
                                "
                            >
                                <ChevronLeft class="size-5" />
                            </button>

                            <div class="relative h-[180px] sm:h-[240px] lg:h-[300px]">
                                <article
                                    v-for="(slide, index) in slides"
                                    :key="slide.id"
                                    class="absolute left-1/2 top-1/2 aspect-[181/65] w-[92%] max-w-[720px] -translate-y-1/2 overflow-hidden rounded-[32px] transition-[transform,opacity,filter] duration-500"
                                    :class="isPreviewSlide(index) ? 'cursor-pointer' : 'pointer-events-none'"
                                    :style="slideStyle(index)"
                                    @click="selectSlide(index)"
                                >
                                    <div class="relative h-full w-full">
                                        <img
                                            :src="slide.image"
                                            :alt="`Promo slide ${index + 1}`"
                                            class="absolute inset-0 h-full w-full object-contain transition duration-500"
                                        />
                                        <div
                                            class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(255,255,255,0.12),transparent_34%)]"
                                        />
                                        <div
                                            class="hero-slide-sheen absolute inset-0 bg-[linear-gradient(135deg,transparent_0%,transparent_48%,rgba(255,255,255,0.12)_54%,transparent_62%,transparent_100%)]"
                                        />
                                    </div>
                                </article>
                            </div>

                            <button
                                type="button"
                                class="absolute right-0 top-1/2 z-30 hidden h-12 w-12 -translate-y-1/2 items-center justify-center rounded-full bg-white text-slate-700 shadow-[0_18px_40px_rgba(15,23,42,0.15)] transition hover:-translate-y-[52%] hover:text-indigo-700 lg:inline-flex"
                                aria-label="Slide berikutnya"
                                @click="
                                    nextSlide();
                                    restartAutoplay();
                                "
                            >
                                <ChevronRight class="size-5" />
                            </button>
                        </div>

                        <div class="mt-6 flex justify-center gap-2.5">
                            <button
                                v-for="(slide, index) in slides"
                                :key="`indicator-${slide.id}`"
                                type="button"
                                class="h-2.5 rounded-full transition-all duration-300"
                                :class="activeIndex === index ? 'w-12 bg-rose-500' : 'w-6 bg-slate-400/55 hover:bg-slate-500/70'"
                                :aria-label="`Buka slide ${index + 1}`"
                                @click="selectSlide(index)"
                            />
                        </div>

                        <div class="sr-only">
                            <p
                                class="bg-white/88 inline-flex items-center rounded-full border border-indigo-200 px-3 py-1 text-[0.65rem] font-black uppercase tracking-[0.2em] text-indigo-700 shadow-[0_12px_28px_rgba(67,56,202,0.08)]"
                            >
                                Brand Resmi Lyva
                            </p>
                            <h1
                                class="[font-family:'Space Grotesk',sans-serif] mt-3 text-[1.4rem] font-black tracking-tight text-slate-950 sm:text-[1.9rem]"
                            >
                                {{ homeIntroTitle }}
                            </h1>
                            <p class="mx-auto mt-2 max-w-3xl text-sm leading-7 text-slate-600">
                                {{ homeIntroDescription }}
                            </p>
                            <div class="mx-auto mt-4 flex max-w-5xl flex-wrap justify-center gap-2">
                                <span
                                    v-for="tag in homeSearchTags"
                                    :key="tag"
                                    class="bg-white/88 inline-flex items-center rounded-full border border-slate-200 px-3 py-1.5 text-[0.7rem] font-bold text-slate-700 shadow-[0_10px_24px_rgba(15,23,42,0.05)]"
                                >
                                    {{ tag }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="relative pb-6">
                <div class="mx-auto max-w-[1100px] px-4 sm:px-6 lg:px-8">
                    <div class="flex gap-2 overflow-x-auto pb-2 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                        <button
                            v-for="chip in visibleCategoryChips"
                            :key="chip.id"
                            type="button"
                            class="inline-flex shrink-0 items-center gap-2 rounded-full border px-3.5 py-1.5 text-[0.82rem] font-medium transition sm:px-4 sm:py-2 sm:text-sm"
                            :class="
                                activeCategory === chip.id
                                    ? 'border-indigo-700 bg-white text-indigo-700 shadow-[0_10px_24px_rgba(67,56,202,0.08)]'
                                    : 'bg-white/88 border-slate-300 text-slate-700 hover:border-slate-400'
                            "
                            :aria-pressed="activeCategory === chip.id"
                            @click="selectCategory(chip.id)"
                        >
                            <component
                                :is="chip.icon"
                                class="size-3 sm:size-3.5"
                                :class="activeCategory === chip.id ? 'text-orange-500' : 'text-slate-500'"
                            />
                            <span>{{ chip.label }}</span>
                        </button>
                    </div>

                    <div class="mt-6 space-y-10 lg:space-y-12">
                        <section v-for="section in displaySections" :id="`section-${section.id}`" :key="section.id" class="scroll-mt-40">
                            <div class="mb-4 flex items-center gap-2.5 sm:gap-3">
                                <span
                                    class="flex h-8 w-8 items-center justify-center rounded-2xl bg-white text-orange-500 shadow-[0_10px_24px_rgba(15,23,42,0.06)] sm:h-9 sm:w-9"
                                >
                                    <component :is="section.icon" class="sm:size-4.5 size-4" />
                                </span>
                                <h2
                                    class="[font-family:'Space Grotesk',sans-serif] text-[1.3rem] font-black tracking-tight text-slate-950 sm:text-[1.82rem]"
                                >
                                    {{ section.title }}
                                </h2>
                            </div>

                            <div class="grid grid-cols-3 gap-2.5 sm:grid-cols-3 sm:gap-3.5 md:grid-cols-4 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6">
                                <a
                                    v-for="entry in visibleProducts(section.id, section.entries)"
                                    :key="entry.key"
                                    :href="route('products.show', { product: entry.product.id })"
                                    class="group block transition duration-300 hover:-translate-y-1"
                                >
                                    <div
                                        class="relative aspect-[0.72] overflow-hidden rounded-[16px] transition duration-300 group-hover:scale-[1.02] group-hover:shadow-[0_18px_34px_rgba(15,23,42,0.12)] sm:aspect-[0.88] sm:rounded-[18px] xl:aspect-[0.92]"
                                        :style="
                                            hasCustomProductArtwork(getHomeCardImage(entry))
                                                ? { backgroundColor: '#ffffff' }
                                                : { backgroundImage: entry.product.background }
                                        "
                                    >
                                        <div
                                            v-if="!hasCustomProductArtwork(getHomeCardImage(entry))"
                                            class="absolute inset-0 bg-[radial-gradient(circle_at_top,rgba(255,255,255,0.28),transparent_38%),linear-gradient(160deg,rgba(255,255,255,0.16),transparent_58%)]"
                                        />
                                        <div
                                            v-else
                                            class="via-slate-950/18 absolute inset-x-0 bottom-0 h-24 bg-gradient-to-t from-slate-950/60 to-transparent"
                                        />
                                        <img
                                            v-if="getHomeCardImage(entry)"
                                            :src="getHomeCardImage(entry) ?? undefined"
                                            :alt="entry.type === 'family' ? entry.title : entry.product.name"
                                            :class="[
                                                'pointer-events-none absolute inset-0 h-full w-full object-cover transition duration-500 group-hover:scale-[1.04]',
                                                hasCustomProductArtwork(getHomeCardImage(entry)) ? 'p-0' : 'p-2',
                                            ]"
                                        />
                                        <div
                                            class="absolute inset-x-3 bottom-2.5 h-3.5 rounded-full bg-black/20 blur-xl sm:inset-x-4 sm:bottom-3 sm:h-4"
                                        />
                                        <div
                                            v-if="!hasCustomProductArtwork(getHomeCardImage(entry))"
                                            class="absolute right-3 top-3 h-9 w-9 rounded-full opacity-80 blur-xl"
                                            :style="{ backgroundColor: entry.product.accent }"
                                        />
                                        <div
                                            class="absolute bottom-2 left-2 inline-flex max-w-[82%] items-center rounded-full border border-white/20 bg-white/10 px-2 py-1 text-[0.58rem] font-bold leading-none text-white backdrop-blur-sm sm:bottom-2.5 sm:left-2.5 sm:max-w-[74%] sm:px-2.5 sm:py-1.5 sm:text-[0.7rem]"
                                        >
                                            <span class="truncate">{{ entry.type === 'family' ? entry.title : entry.product.name }}</span>
                                        </div>
                                        <span
                                            v-if="entry.type === 'family' || entry.product.badge"
                                            class="absolute right-2 top-2 inline-flex items-center gap-1 rounded-full border border-white/70 bg-white/95 px-1.5 py-0.5 text-[0.45rem] font-black uppercase tracking-[0.08em] text-rose-500 shadow-[0_10px_24px_rgba(15,23,42,0.2)] sm:right-2.5 sm:top-2.5 sm:px-2 sm:py-1 sm:text-[0.52rem] sm:tracking-[0.12em]"
                                        >
                                            <span
                                                class="h-2.5 w-2.5 rounded-full bg-amber-400 shadow-[inset_0_1px_1px_rgba(255,255,255,0.6)] sm:h-3.5 sm:w-3.5"
                                            />
                                            {{ entry.type === 'family' ? entry.badge : entry.product.badge }}
                                        </span>
                                    </div>
                                </a>
                            </div>

                            <div v-if="canExpandSection(section.id, section.entries)" class="mt-6 text-center">
                                <button
                                    type="button"
                                    class="text-[0.82rem] font-bold text-indigo-700 transition hover:text-indigo-600 sm:text-sm"
                                    @click="expandSection(section.id)"
                                >
                                    Tampilkan lebih banyak
                                </button>
                            </div>
                        </section>
                    </div>
                </div>
            </section>
        </main>
    </PublicLayout>
</template>
