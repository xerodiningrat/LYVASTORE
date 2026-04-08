<script setup lang="ts">
import AmbientParticles from '@/components/AmbientParticles.vue';
import FloatingChatButton from '@/components/FloatingChatButton.vue';
import RecentPurchasePopups from '@/components/RecentPurchasePopups.vue';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { useInitials } from '@/composables/useInitials';
import { catalogProducts, isPublicCatalogProductVisible } from '@/data/catalog';
import { applyProductDisplayOverride } from '@/data/product-display-overrides';
import { compareProductsByOrdering } from '@/data/product-ordering';
import type { SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    ArrowRight,
    BadgeCheck,
    Calculator,
    ChevronDown,
    Clock3,
    Coins,
    Download,
    House,
    Instagram,
    Mail,
    MapPin,
    ReceiptText,
    Search,
    ShieldCheck,
    Sparkles,
    Trophy,
    UserRound,
    WalletCards,
    X,
    type LucideIcon,
} from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

type NavLink = {
    id: string;
    label: string;
    href?: string;
};

type MobileDockItem = {
    id: string;
    label: string;
    href: string;
    icon: LucideIcon;
    active: boolean;
    prominent?: boolean;
};

type CalculatorLink = {
    label: string;
    href: string;
    description: string;
};

type FooterLink = {
    label: string;
    href: string;
};

type FooterLinkGroup = {
    title: string;
    links: FooterLink[];
};

type FooterFeature = {
    title: string;
    description: string;
    icon: LucideIcon;
};

type FooterSocial = {
    label: string;
    href: string;
    icon: LucideIcon;
    detail: string;
};

type FooterSignal = {
    label: string;
    value: string;
    icon: LucideIcon;
};

type FooterPaymentMethod = {
    id: string;
    label: string;
    image: string | null;
};

type SearchProduct = {
    id: string;
    name: string;
    categoryTitle?: string;
    searchAliases?: string[];
};

const props = withDefaults(
    defineProps<{
        activeNav?: string;
    }>(),
    {
        activeNav: 'topup',
    },
);

const baseNavLinks: NavLink[] = [
    { id: 'topup', label: 'Top Up', href: '/' },
    { id: 'akun-bermasalah', label: 'Akun Bermasalah' },
    { id: 'riwayat-transaksi', label: 'Riwayat Transaksi', href: '/riwayat-transaksi' },
    { id: 'leaderboard', label: 'Leaderboard', href: '/leaderboard' },
    { id: 'artikel', label: 'Artikel', href: '/artikel' },
    { id: 'kalkulator', label: 'Kalkulator' },
];

const calculatorLinks: CalculatorLink[] = [
    {
        label: 'Hitung Win Rate ML',
        href: '/kalkulator#winrate-mobile-legends',
        description: 'Cek berapa kemenangan tambahan untuk capai target win rate.',
    },
    {
        label: 'Magic Wheel ML',
        href: '/kalkulator#magic-wheel-mobile-legends',
        description: 'Estimasi budget spin berdasarkan harga normal dan diskon.',
    },
    {
        label: 'Zodiac ML',
        href: '/kalkulator#zodiac-mobile-legends',
        description: 'Hitung kebutuhan diamond untuk draw zodiac sesuai skenario kamu.',
    },
];

const footerLinkGroups: FooterLinkGroup[] = [
    {
        title: 'Navigasi',
        links: [
            { label: 'Beranda', href: '/' },
            { label: 'Promo', href: '#' },
            { label: 'Top Up Langsung', href: '/' },
            { label: 'Voucher', href: '#' },
            { label: 'Riwayat Transaksi', href: '/riwayat-transaksi' },
            { label: 'Leaderboard', href: '/leaderboard' },
            { label: 'Artikel', href: '/artikel' },
            { label: 'Kalkulator', href: '/kalkulator' },
        ],
    },
    {
        title: 'Produk',
        links: [
            { label: 'Mobile Legends', href: '#' },
            { label: 'Free Fire', href: '#' },
            { label: 'Genshin Impact', href: '#' },
            { label: 'Honor of Kings', href: '#' },
            { label: 'Steam Wallet', href: '#' },
        ],
    },
    {
        title: 'Bantuan',
        links: [
            { label: 'Pusat Bantuan', href: '#' },
            { label: 'Hubungi Kami', href: '#' },
            { label: 'Syarat & Ketentuan', href: '/terms-of-service' },
            { label: 'Kebijakan Privasi', href: '/privacy-policy' },
            { label: 'FAQ', href: '#' },
        ],
    },
];

const footerFeatures: FooterFeature[] = [
    {
        title: 'Transaksi Aman',
        description: 'Checkout cepat dengan lapisan verifikasi dan metode pembayaran populer.',
        icon: ShieldCheck,
    },
    {
        title: 'Layanan 24/7',
        description: 'Tim support siap bantu kapan pun kamu butuh update pesanan atau kendala top up.',
        icon: Clock3,
    },
    {
        title: 'Produk Terverifikasi',
        description: 'Daftar game dan voucher kurasi dengan proses pengiriman yang lebih konsisten.',
        icon: BadgeCheck,
    },
];

const footerSocials: FooterSocial[] = [
    { label: 'Instagram', href: '#', icon: Instagram, detail: 'Instagram LYVA Indonesia' },
    { label: 'Email', href: '#', icon: Mail, detail: 'Email support LYVA Indonesia' },
    { label: 'Lokasi', href: '#', icon: MapPin, detail: 'Banyuwangi, Glenmore, Jawa Timur' },
];

const footerSignals: FooterSignal[] = [
    {
        label: 'Checkout otomatis',
        value: 'QRIS, VA, dan e-wallet favorit tetap siap dipakai.',
        icon: ShieldCheck,
    },
    {
        label: 'Cashback aktif',
        value: 'Lyva Coins bertambah setelah pesanan selesai diproses.',
        icon: Coins,
    },
    {
        label: 'Pantau instan',
        value: 'Update transaksi bisa dicek cepat dari satu alur.',
        icon: Clock3,
    },
];

const footerPulseItems = ['Top up cepat', 'Voucher digital', 'Cashback aktif', 'Status real-time', 'Promo mingguan', 'Metode populer'];

const footerPaymentPreviewAmount = 50000;
const footerPaymentCacheKey = 'lyva-footer-duitku-payments-v1';
const installBannerStorageKey = 'lyva-install-banner-dismissed-v1';
const androidApkUrl = '/download-aplikasi';
const androidApkFilename = 'lyvaindonesia-latest.apk';

const fallbackPaymentMethods: FooterPaymentMethod[] = [
    { id: 'qris', label: 'QRIS', image: null },
    { id: 'visa', label: 'Visa', image: null },
    { id: 'mastercard', label: 'Mastercard', image: null },
    { id: 'gopay', label: 'GoPay', image: null },
    { id: 'ovo', label: 'OVO', image: null },
    { id: 'dana', label: 'DANA', image: null },
];

const footerPayments = ref<FooterPaymentMethod[]>(fallbackPaymentMethods);
const isInstallBannerVisible = ref(true);
const page = usePage<SharedData>();
const currentUser = computed(() => page.props.auth?.user ?? null);
const currentCoinBalance = computed(() => page.props.auth?.coins?.balance ?? 0);
const recentPurchases = computed(() => page.props.recentPurchases ?? []);
const supportChatUrl = computed(() => page.props.support?.chatUrl ?? '/riwayat-transaksi');
const supportChatEndpoint = computed(() => page.props.support?.chatEndpoint ?? '/support/chat');
const supportAiEnabled = computed(() => Boolean(page.props.support?.aiEnabled));
const showCurrentUserAvatar = computed(() => Boolean(currentUser.value?.avatar));
const { getInitials } = useInitials();

const formatCoinBalance = (value: number) =>
    `${new Intl.NumberFormat('id-ID', {
        maximumFractionDigits: 0,
    }).format(Math.max(0, Math.round(value)))} Coins`;

const currentCoinBalanceLabel = computed(() => formatCoinBalance(currentCoinBalance.value));

const footerMarqueePayments = computed(() => {
    if (footerPayments.value.length <= 4) {
        return footerPayments.value;
    }

    return [...footerPayments.value, ...footerPayments.value];
});

const searchQuery = ref('');
const searchOpen = ref(false);
const mobileSearchVisible = ref(false);
const mobileSearchInput = ref<HTMLInputElement | null>(null);
const searchCloseTimeout = ref<number | null>(null);
const productDisplayOverrides = computed(() => page.props.adminPanel?.productDisplayOverrides ?? {});
const hiddenProductIds = computed(() => new Set(page.props.adminPanel?.hiddenProductIds ?? []));
const unavailableProductIds = computed(() => new Set(page.props.unavailableProductIds ?? []));
const productOrderingOverrides = computed(() => page.props.adminPanel?.productOrderingOverrides ?? {});
const brandSearchAliases = ['lyva', 'lyva indonesia', 'lyvaindonesia', 'top up lyva', 'lyva top up'];

const vipCatalogProducts = computed<SearchProduct[]>(() => {
    const propsWithVipCatalog = page.props as SharedData & {
        vipCatalogProducts?: Array<{ id?: string; name?: string; categoryTitle?: string }>;
    };

    return Array.isArray(propsWithVipCatalog.vipCatalogProducts)
        ? propsWithVipCatalog.vipCatalogProducts
              .map((product) => ({
                  ...applyProductDisplayOverride(
                      {
                          id: String(product.id ?? ''),
                          name: String(product.name ?? ''),
                          categoryTitle: product.categoryTitle ? String(product.categoryTitle) : undefined,
                          searchAliases: [...brandSearchAliases],
                      },
                      productDisplayOverrides.value[String(product.id ?? '')],
                  ),
              }))
              .filter((product) => product.id !== '' && product.name !== '')
        : [];
});

const searchableProducts = computed<SearchProduct[]>(() => {
    const mappedLocalProducts = catalogProducts.map((product) =>
        applyProductDisplayOverride(
            {
                id: product.id,
                name: product.name,
                categoryTitle: product.categoryTitle,
                searchAliases: [...brandSearchAliases],
            },
            productDisplayOverrides.value[product.id],
        ),
    );

    const uniqueProducts = new Map<string, SearchProduct>();

    [...mappedLocalProducts, ...vipCatalogProducts.value]
        .filter(
            (product) =>
                isPublicCatalogProductVisible(product) && !hiddenProductIds.value.has(product.id) && !unavailableProductIds.value.has(product.id),
        )
        .forEach((product) => {
            if (!uniqueProducts.has(product.id)) {
                uniqueProducts.set(product.id, product);
            }
        });

    return [...uniqueProducts.values()];
});

const normalizedSearchQuery = computed(() => searchQuery.value.trim().toLowerCase());

const searchResults = computed<SearchProduct[]>(() => {
    const query = normalizedSearchQuery.value;

    if (query.length < 2) {
        return [];
    }

    const scoreProduct = (product: SearchProduct) => {
        const aliases = (product.searchAliases ?? []).map((alias) => alias.toLowerCase());
        const aliasHaystack = aliases.join(' ');
        const haystack = `${product.name} ${product.id} ${product.categoryTitle ?? ''} ${aliasHaystack}`.toLowerCase();
        const name = product.name.toLowerCase();
        const id = product.id.toLowerCase();

        if (name === query) {
            return 0;
        }

        if (id === query) {
            return 1;
        }

        if (aliases.includes(query)) {
            return 2;
        }

        if (name.startsWith(query)) {
            return 3;
        }

        if (id.startsWith(query)) {
            return 4;
        }

        if (aliases.some((alias) => alias.startsWith(query))) {
            return 5;
        }

        if (haystack.includes(query)) {
            return 6;
        }

        return Number.POSITIVE_INFINITY;
    };

    return searchableProducts.value
        .map((product) => ({
            product,
            score: scoreProduct(product),
        }))
        .filter((entry) => Number.isFinite(entry.score))
        .sort((left, right) => {
            if (left.score !== right.score) {
                return left.score - right.score;
            }

            return compareProductsByOrdering(left.product, right.product, productOrderingOverrides.value);
        })
        .slice(0, 6)
        .map((entry) => entry.product);
});

const clearSearchCloseTimeout = () => {
    if (searchCloseTimeout.value !== null && typeof window !== 'undefined') {
        window.clearTimeout(searchCloseTimeout.value);
        searchCloseTimeout.value = null;
    }
};

const openSearch = () => {
    clearSearchCloseTimeout();
    searchOpen.value = true;
};

const scheduleCloseSearch = () => {
    if (typeof window === 'undefined') {
        searchOpen.value = false;
        return;
    }

    clearSearchCloseTimeout();
    searchCloseTimeout.value = window.setTimeout(() => {
        searchOpen.value = false;
    }, 140);
};

const goToProduct = (productId: string) => {
    const destination = route('products.show', { product: productId });

    searchQuery.value = '';
    searchOpen.value = false;
    mobileSearchVisible.value = false;
    clearSearchCloseTimeout();
    window.location.assign(destination);
};

const submitSearch = () => {
    const firstMatch = searchResults.value[0];

    if (!firstMatch) {
        return;
    }

    goToProduct(firstMatch.id);
};

const showMobileSearch = () => {
    mobileSearchVisible.value = true;

    if (typeof window !== 'undefined') {
        window.setTimeout(() => {
            mobileSearchInput.value?.focus();
        }, 20);
    }
};

const hideMobileSearch = () => {
    searchQuery.value = '';
    searchOpen.value = false;
    mobileSearchVisible.value = false;
    clearSearchCloseTimeout();
};

onBeforeUnmount(() => {
    clearSearchCloseTimeout();
});

const navLinks = computed(() =>
    baseNavLinks.map((link) => ({
        ...link,
        href: link.id === 'akun-bermasalah' ? route('account-issues.index') : link.href,
        active: link.id === props.activeNav,
    })),
);

const mobileDockLinks = computed<MobileDockItem[]>(() => {
    const profileHref = currentUser.value ? route('profile.edit') : route('login');

    return [
        {
            id: 'topup',
            label: 'Home',
            href: route('home'),
            icon: House,
            active: props.activeNav === 'topup',
        },
        {
            id: 'riwayat-transaksi',
            label: 'Riwayat',
            href: route('transactions.history'),
            icon: ReceiptText,
            active: props.activeNav === 'riwayat-transaksi',
        },
        {
            id: 'coins',
            label: 'Coins',
            href: route('coins.index'),
            icon: Coins,
            active: props.activeNav === 'coins',
            prominent: true,
        },
        {
            id: 'leaderboard',
            label: 'Rank',
            href: route('leaderboard'),
            icon: Trophy,
            active: props.activeNav === 'leaderboard',
        },
        {
            id: 'profile',
            label: 'Profil',
            href: profileHref,
            icon: UserRound,
            active: props.activeNav === 'profile' || props.activeNav === 'security',
        },
    ];
});

const normalizeFooterPayment = (
    payment: Partial<FooterPaymentMethod> & { paymentName?: string; paymentImage?: string | null; label?: string; image?: string | null },
) => ({
    id: String(payment.id ?? payment.label ?? payment.paymentName ?? ''),
    label: String(payment.label ?? payment.paymentName ?? 'Metode Pembayaran'),
    image:
        typeof (payment.image ?? payment.paymentImage) === 'string' && (payment.image ?? payment.paymentImage)
            ? String(payment.image ?? payment.paymentImage)
            : null,
});

onMounted(async () => {
    if (typeof window === 'undefined') {
        return;
    }

    isInstallBannerVisible.value = !window.localStorage.getItem(installBannerStorageKey);

    const cachedPayments = window.sessionStorage.getItem(footerPaymentCacheKey);

    if (cachedPayments) {
        try {
            const parsed = JSON.parse(cachedPayments) as FooterPaymentMethod[];

            if (Array.isArray(parsed) && parsed.length > 0) {
                footerPayments.value = parsed;
            }
        } catch {
            window.sessionStorage.removeItem(footerPaymentCacheKey);
        }
    }

    try {
        const response = await fetch(route('duitku.payment-methods', { amount: footerPaymentPreviewAmount }), {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) {
            return;
        }

        const payload = await response.json();
        const payments = Array.isArray(payload.data)
            ? payload.data
                  .map((payment: Partial<FooterPaymentMethod> & { paymentName?: string; paymentImage?: string | null }) =>
                      normalizeFooterPayment(payment),
                  )
                  .filter((payment: FooterPaymentMethod) => payment.id && payment.label)
            : [];

        const uniquePayments = payments.filter(
            (payment: FooterPaymentMethod, index: number, collection: FooterPaymentMethod[]) =>
                collection.findIndex((candidate) => candidate.id === payment.id || candidate.label === payment.label) === index,
        );

        if (uniquePayments.length > 0) {
            footerPayments.value = uniquePayments;
            window.sessionStorage.setItem(footerPaymentCacheKey, JSON.stringify(uniquePayments));
        }
    } catch {
        // Footer tetap pakai fallback statis kalau Duitku gagal diambil.
    }
});

const closeInstallBanner = () => {
    isInstallBannerVisible.value = false;

    if (typeof window !== 'undefined') {
        window.localStorage.setItem(installBannerStorageKey, '1');
    }
};
</script>

<template>
    <Head>
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous" />
        <link
            href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;700&display=swap"
            rel="stylesheet"
        />
    </Head>

    <div
        class="[font-family:'Plus Jakarta Sans',sans-serif] relative min-h-screen overflow-x-hidden bg-[#f8fbff] pb-36 text-slate-950 sm:pb-40 md:pb-0"
    >
        <AmbientParticles variant="public" class="fixed inset-0 z-0" />
        <RecentPurchasePopups :items="recentPurchases" />
        <FloatingChatButton :fallback-href="supportChatUrl" :endpoint="supportChatEndpoint" :ai-enabled="supportAiEnabled" />
        <header class="fixed inset-x-0 top-0 z-50 border-b border-slate-200/80 bg-white/90 shadow-[0_14px_32px_rgba(15,23,42,0.06)] backdrop-blur-xl">
            <div
                v-if="isInstallBannerVisible"
                class="border-b border-indigo-100 bg-[linear-gradient(90deg,rgba(15,23,42,0.98),rgba(49,46,129,0.96),rgba(14,116,144,0.95))]"
            >
                <div class="mx-auto flex max-w-[1100px] items-center gap-2 px-3.5 py-2.5 sm:px-6 lg:px-8">
                    <div class="min-w-0 flex-1 pr-2">
                        <p class="text-[0.58rem] font-black uppercase tracking-[0.22em] text-cyan-200 sm:text-[0.62rem]">Install aplikasi</p>
                        <p class="mt-0.5 text-[0.83rem] font-semibold text-white sm:text-[0.9rem]">
                            Download APK Android Lyva Indonesia langsung dari website resmi.
                        </p>
                    </div>

                    <a
                        :href="androidApkUrl"
                        :download="androidApkFilename"
                        class="inline-flex h-9 shrink-0 items-center justify-center gap-1.5 rounded-full border border-white/15 bg-white px-3.5 text-[0.82rem] font-bold text-slate-950 shadow-[0_12px_28px_rgba(8,15,30,0.22)] transition hover:-translate-y-0.5 hover:bg-cyan-50 hover:text-cyan-700 sm:h-10 sm:px-4 sm:text-[0.88rem]"
                    >
                        <Download class="size-4" />
                        Install aplikasi
                    </a>

                    <button
                        type="button"
                        class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-white/20 bg-white/10 text-white/90 transition hover:bg-white/18 hover:text-white"
                        aria-label="Sembunyikan banner install aplikasi"
                        @click="closeInstallBanner"
                    >
                        <X class="size-4" />
                    </button>
                </div>
            </div>

            <div class="mx-auto flex max-w-[1100px] items-center gap-2 px-3.5 py-2.5 sm:gap-3 sm:px-6 lg:gap-5 lg:px-8">
                <Link :href="route('home')" class="navbar-brand-link min-w-0 flex-1 py-0.5 lg:min-w-[15.5rem] lg:flex-none">
                    <img src="/brand/lyva-mascot.png" alt="Lyva Indonesia" class="navbar-brand-mark h-10 w-10 sm:h-11 sm:w-11" />
                    <span class="navbar-brand-copy gap-0.5">
                        <span class="lyva-wordmark lyva-wordmark--lg navbar-brand-title origin-left scale-[0.84]">LYVA INDONESIA</span>
                        <span class="navbar-brand-tagline text-[0.72rem] tracking-[0.2em] sm:text-[0.76rem]">Top up & voucher hub</span>
                    </span>
                </Link>

                <div class="relative hidden flex-1 items-center justify-center md:flex">
                    <form class="relative w-full max-w-[31rem]" @submit.prevent="submitSearch">
                        <label
                            class="flex h-10 w-full items-center gap-2.5 rounded-full border border-slate-200 bg-white px-4 shadow-[0_12px_32px_rgba(15,23,42,0.05)]"
                        >
                            <Search class="size-4 text-slate-400" />
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Cari game..."
                                class="w-full border-none bg-transparent p-0 text-[0.88rem] text-slate-700 placeholder:text-slate-400 focus:outline-none focus:ring-0"
                                @focus="openSearch"
                                @blur="scheduleCloseSearch"
                            />
                        </label>

                        <div
                            v-if="searchOpen && searchResults.length > 0"
                            class="absolute inset-x-0 top-[calc(100%+0.65rem)] z-50 overflow-hidden rounded-[24px] border border-slate-200/90 bg-white/95 p-2 shadow-[0_22px_54px_rgba(15,23,42,0.12)] backdrop-blur"
                        >
                            <button
                                v-for="product in searchResults"
                                :key="product.id"
                                type="button"
                                class="flex w-full items-center justify-between gap-3 rounded-[18px] px-3 py-3 text-left transition hover:bg-slate-50"
                                @mousedown.prevent="goToProduct(product.id)"
                            >
                                <span class="min-w-0">
                                    <span class="block truncate text-sm font-bold text-slate-950">{{ product.name }}</span>
                                    <span class="block truncate text-xs text-slate-500">{{ product.categoryTitle ?? 'Produk' }}</span>
                                </span>
                                <ArrowRight class="size-4 shrink-0 text-slate-400" />
                            </button>
                        </div>
                    </form>
                </div>

                <div class="ml-auto flex shrink-0 items-center gap-2 sm:gap-3">
                    <Link
                        :href="route('coins.index')"
                        class="coin-entry hidden h-10 w-10 items-center justify-center rounded-full border border-amber-200/80 bg-[linear-gradient(135deg,rgba(255,248,235,0.98),rgba(255,255,255,0.98))] px-0 text-slate-900 transition hover:-translate-y-0.5 hover:shadow-[0_16px_30px_rgba(245,158,11,0.16)] sm:inline-flex sm:h-10 sm:w-auto sm:justify-start sm:gap-2.5 sm:px-2 sm:pr-3"
                        aria-label="Lyva Coins"
                    >
                        <span
                            class="coin-entry__icon inline-flex h-8 w-8 items-center justify-center rounded-full bg-amber-100 text-amber-500 shadow-[inset_0_1px_0_rgba(255,255,255,0.6)] sm:h-7 sm:w-7"
                        >
                            <Coins class="coin-entry__glyph size-4" />
                        </span>
                        <span class="hidden min-w-0 flex-col text-left sm:flex">
                            <span class="text-[0.54rem] font-bold uppercase tracking-[0.16em] text-slate-400">Lyva Coins</span>
                            <span class="truncate text-[0.92rem] font-black text-slate-950">
                                {{ currentUser ? currentCoinBalanceLabel : 'Lihat cashback' }}
                            </span>
                        </span>
                    </Link>

                    <button
                        type="button"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-600 transition hover:-translate-y-0.5 hover:text-slate-900 hover:shadow-[0_12px_24px_rgba(15,23,42,0.08)] md:hidden"
                        aria-label="Buka pencarian"
                        @click="mobileSearchVisible ? hideMobileSearch() : showMobileSearch()"
                    >
                        <Search class="size-4.5" />
                    </button>

                    <DropdownMenu v-if="currentUser">
                        <DropdownMenuTrigger as-child>
                            <button
                                type="button"
                                class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-indigo-700/70 bg-white text-indigo-700 transition hover:-translate-y-0.5 hover:shadow-[0_12px_24px_rgba(67,56,202,0.18)] sm:h-12 sm:w-12"
                                aria-label="Menu akun"
                            >
                                <Avatar class="h-10 w-10 rounded-full border border-indigo-100 bg-indigo-50">
                                    <AvatarImage
                                        v-if="showCurrentUserAvatar && currentUser?.avatar"
                                        :src="currentUser.avatar"
                                        :alt="currentUser.name ?? 'User'"
                                    />
                                    <AvatarFallback class="bg-indigo-50 text-sm font-bold text-indigo-700">
                                        {{ getInitials(currentUser?.name ?? 'User') }}
                                    </AvatarFallback>
                                </Avatar>
                            </button>
                        </DropdownMenuTrigger>

                        <DropdownMenuContent
                            align="end"
                            side="bottom"
                            :side-offset="10"
                            class="w-64 rounded-[22px] border border-slate-200 bg-white/95 p-2 shadow-[0_18px_48px_rgba(15,23,42,0.12)] backdrop-blur"
                        >
                            <UserMenuContent :user="currentUser" />
                        </DropdownMenuContent>
                    </DropdownMenu>
                    <Link
                        v-else
                        :href="route('login')"
                        class="inline-flex h-10 items-center rounded-full border border-indigo-700 px-4 text-[0.86rem] font-bold text-indigo-700 transition hover:bg-indigo-700 hover:text-white sm:h-10 sm:px-5 sm:text-[0.9rem]"
                    >
                        Masuk
                    </Link>
                </div>
            </div>

            <div class="hidden border-t border-slate-200/70 md:block">
                <nav
                    class="mx-auto flex max-w-[1100px] items-center gap-1 overflow-x-auto px-4 py-2.5 [-ms-overflow-style:none] [scrollbar-width:none] sm:px-6 lg:gap-1.5 lg:px-8 [&::-webkit-scrollbar]:hidden"
                >
                    <template v-for="link in navLinks" :key="link.id">
                        <DropdownMenu v-if="link.id === 'kalkulator'">
                            <DropdownMenuTrigger as-child>
                                <button
                                    type="button"
                                    class="inline-flex h-9 shrink-0 items-center gap-1.5 border-b-2 border-transparent px-3 text-[0.88rem] font-bold outline-none transition lg:px-3.5"
                                    :class="
                                        link.active
                                            ? 'border-indigo-600 text-indigo-700'
                                            : 'text-slate-600 hover:border-slate-300 hover:text-slate-950'
                                    "
                                >
                                    <span>{{ link.label }}</span>
                                    <ChevronDown class="size-4" />
                                </button>
                            </DropdownMenuTrigger>

                            <DropdownMenuContent
                                align="start"
                                side="bottom"
                                :side-offset="10"
                                class="w-[min(22rem,calc(100vw-2rem))] rounded-[22px] border border-slate-200 bg-white/95 p-2 shadow-[0_18px_48px_rgba(15,23,42,0.12)] backdrop-blur"
                            >
                                <DropdownMenuLabel class="px-3 py-2">
                                    <div class="flex items-center gap-3">
                                        <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-700">
                                            <Calculator class="size-4.5" />
                                        </span>
                                        <div>
                                            <p class="text-[0.68rem] font-semibold uppercase tracking-[0.18em] text-slate-500">Kalkulator</p>
                                            <p
                                                class="[font-family:'Space Grotesk',sans-serif] text-sm font-black uppercase tracking-[0.08em] text-slate-950"
                                            >
                                                Mobile Legends Tools
                                            </p>
                                        </div>
                                    </div>
                                </DropdownMenuLabel>
                                <DropdownMenuSeparator />

                                <DropdownMenuItem
                                    v-for="calculator in calculatorLinks"
                                    :key="calculator.label"
                                    :as-child="true"
                                    class="rounded-2xl px-3 py-3 focus:bg-slate-50"
                                >
                                    <a class="block w-full" :href="calculator.href">
                                        <div class="space-y-1">
                                            <p class="text-sm font-bold text-slate-950">{{ calculator.label }}</p>
                                            <p class="text-xs leading-5 text-slate-500">{{ calculator.description }}</p>
                                        </div>
                                    </a>
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>

                        <component
                            v-else
                            :is="link.href && link.href.startsWith('/') ? Link : 'a'"
                            :href="link.href"
                            class="inline-flex h-9 shrink-0 items-center border-b-2 border-transparent px-3 text-[0.88rem] font-bold transition lg:px-3.5"
                            :class="link.active ? 'border-indigo-600 text-indigo-700' : 'text-slate-600 hover:border-slate-300 hover:text-slate-950'"
                        >
                            {{ link.label }}
                        </component>
                    </template>
                </nav>
            </div>

            <div v-if="mobileSearchVisible" class="mx-auto px-3.5 pb-3 sm:px-6 md:hidden">
                <form class="relative" @submit.prevent="submitSearch">
                    <label
                        class="flex h-12 items-center gap-3 rounded-full border border-slate-200 bg-white px-5 shadow-[0_10px_24px_rgba(15,23,42,0.05)]"
                    >
                        <Search class="size-4.5 text-slate-400" />
                        <input
                            ref="mobileSearchInput"
                            v-model="searchQuery"
                            type="text"
                            placeholder="Cari game..."
                            class="w-full border-none bg-transparent p-0 text-[0.95rem] text-slate-700 placeholder:text-slate-400 focus:outline-none focus:ring-0"
                            @focus="openSearch"
                            @blur="scheduleCloseSearch"
                        />
                        <button
                            v-if="searchQuery || searchOpen"
                            type="button"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
                            aria-label="Tutup pencarian"
                            @mousedown.prevent
                            @click="hideMobileSearch"
                        >
                            <X class="size-4" />
                        </button>
                    </label>

                    <div
                        v-if="searchOpen && searchResults.length > 0"
                        class="absolute inset-x-0 top-[calc(100%+0.6rem)] z-50 overflow-hidden rounded-[22px] border border-slate-200/90 bg-white/95 p-2 shadow-[0_20px_44px_rgba(15,23,42,0.12)] backdrop-blur"
                    >
                        <button
                            v-for="product in searchResults"
                            :key="product.id"
                            type="button"
                            class="flex w-full items-center justify-between gap-3 rounded-[16px] px-3 py-3 text-left transition hover:bg-slate-50"
                            @mousedown.prevent="goToProduct(product.id)"
                        >
                            <span class="min-w-0">
                                <span class="block truncate text-sm font-bold text-slate-950">{{ product.name }}</span>
                                <span class="block truncate text-xs text-slate-500">{{ product.categoryTitle ?? 'Produk' }}</span>
                            </span>
                            <ArrowRight class="size-4 shrink-0 text-slate-400" />
                        </button>
                    </div>
                </form>
            </div>
        </header>

        <div class="h-[10.7rem] sm:h-[11rem] md:h-[9.4rem]"></div>

        <main class="relative z-10 pt-3 sm:pt-4 md:pt-5">
            <slot />
        </main>

        <footer id="site-footer" class="relative z-10 overflow-hidden bg-[linear-gradient(180deg,#ffffff_0%,#eef2ff_45%,#dbeafe_100%)]">
            <div class="pointer-events-none absolute inset-x-0 top-0 z-10 h-20 -translate-y-[46%]">
                <svg
                    viewBox="0 0 1440 120"
                    preserveAspectRatio="none"
                    class="h-full w-full text-white/95 drop-shadow-[0_16px_28px_rgba(148,163,184,0.1)]"
                >
                    <path
                        fill="currentColor"
                        d="M0,88L48,80C96,72,192,56,288,52C384,48,480,56,576,68C672,80,768,96,864,98.7C960,101,1056,91,1152,80C1248,69,1344,59,1392,54L1440,48L1440,0L1392,0C1344,0,1248,0,1152,0C1056,0,960,0,864,0C768,0,672,0,576,0C480,0,384,0,288,0C192,0,96,0,48,0L0,0Z"
                    />
                </svg>
                <svg viewBox="0 0 1440 120" preserveAspectRatio="none" class="absolute inset-x-0 top-4 h-full w-full text-indigo-100/60">
                    <path
                        fill="currentColor"
                        d="M0,92L60,86.7C120,81,240,71,360,70.7C480,71,600,81,720,84C840,87,960,83,1080,74.7C1200,67,1320,55,1380,49.3L1440,44L1440,0L1380,0C1320,0,1200,0,1080,0C960,0,840,0,720,0C600,0,480,0,360,0C240,0,120,0,60,0L0,0Z"
                    />
                </svg>
            </div>

            <div class="pointer-events-none absolute inset-0">
                <div class="absolute left-[-8%] top-10 h-56 w-72 rounded-full bg-indigo-200/30 blur-3xl" />
                <div class="absolute bottom-8 right-[-6%] h-52 w-72 rounded-full bg-sky-200/30 blur-3xl" />
                <div
                    class="absolute inset-0 opacity-40 [background-image:linear-gradient(rgba(148,163,184,0.08)_1px,transparent_1px),linear-gradient(90deg,rgba(148,163,184,0.08)_1px,transparent_1px)] [background-size:40px_40px] [mask-image:radial-gradient(circle_at_center,black_20%,transparent_78%)]"
                />
            </div>

            <div class="relative mx-auto w-full max-w-[1100px] px-4 pb-12 pt-14 sm:px-6 lg:px-8 lg:pb-16 lg:pt-20">
                <div class="grid gap-6 lg:gap-8 xl:grid-cols-[minmax(0,1.08fr),minmax(22rem,0.92fr)] xl:items-start">
                    <div class="space-y-6">
                        <section
                            class="footer-hero-panel bg-white/78 relative overflow-hidden rounded-[28px] border border-white/80 p-5 shadow-[0_28px_70px_rgba(15,23,42,0.07)] backdrop-blur-xl sm:rounded-[36px] sm:p-8"
                        >
                            <div class="footer-hero-orb footer-hero-orb--one"></div>
                            <div class="footer-hero-orb footer-hero-orb--two"></div>

                            <div class="relative">
                                <div class="flex flex-wrap gap-2">
                                    <span
                                        class="bg-white/88 inline-flex items-center gap-2 rounded-full border border-fuchsia-200/80 px-3 py-1.5 text-[0.68rem] font-black uppercase tracking-[0.22em] text-fuchsia-600 shadow-[0_10px_24px_rgba(217,70,239,0.12)]"
                                    >
                                        <Sparkles class="size-3.5" />
                                        Footer pulse
                                    </span>
                                    <span
                                        class="inline-flex items-center gap-2 rounded-full border border-indigo-200/80 bg-indigo-50/90 px-3 py-1.5 text-[0.68rem] font-black uppercase tracking-[0.22em] text-indigo-700"
                                    >
                                        <Coins class="size-3.5" />
                                        {{ currentUser ? currentCoinBalanceLabel : 'Cashback reward aktif' }}
                                    </span>
                                </div>

                                <div class="mt-5 flex items-center gap-3 sm:mt-6 sm:gap-4">
                                    <img
                                        src="/brand/lyva-mascot-hd.png"
                                        alt="Lyva Indonesia"
                                        class="h-16 w-16 object-contain drop-shadow-[0_20px_38px_rgba(244,114,182,0.24)] sm:h-20 sm:w-20"
                                    />

                                    <div class="min-w-0">
                                        <p class="lyva-wordmark lyva-wordmark--xl footer-brand-title">LYVA INDONESIA</p>
                                        <p class="footer-brand-tagline">
                                            Top up game, voucher digital, dan promo mingguan dalam satu tempat yang ringkas dan mudah dipakai.
                                        </p>
                                    </div>
                                </div>

                                <div class="footer-pulse-mask mt-5 sm:mt-7">
                                    <div class="footer-pulse-track">
                                        <div
                                            v-for="(item, index) in [...footerPulseItems, ...footerPulseItems]"
                                            :key="`footer-pulse-${item}-${index}`"
                                            class="footer-pulse-chip"
                                        >
                                            <span class="footer-pulse-chip__dot"></span>
                                            <span>{{ item }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <div class="grid gap-3 sm:gap-4 lg:grid-cols-3">
                            <article
                                v-for="feature in footerFeatures"
                                :key="feature.title"
                                class="footer-feature-card bg-white/76 group relative overflow-hidden rounded-[22px] border border-white/80 p-4 shadow-[0_16px_36px_rgba(15,23,42,0.05)] backdrop-blur sm:rounded-[28px] sm:p-5"
                            >
                                <div class="absolute inset-x-6 top-0 h-px bg-gradient-to-r from-transparent via-indigo-300/70 to-transparent"></div>
                                <div
                                    class="absolute -right-10 top-5 h-24 w-24 rounded-full bg-indigo-100/60 blur-2xl transition duration-500 group-hover:scale-110"
                                ></div>
                                <span
                                    class="relative flex h-12 w-12 items-center justify-center rounded-[18px] bg-[linear-gradient(135deg,rgba(238,242,255,0.96),rgba(255,255,255,0.96))] text-indigo-700 shadow-[0_12px_24px_rgba(99,102,241,0.12)]"
                                >
                                    <component :is="feature.icon" class="size-4.5" />
                                </span>
                                <h3
                                    class="[font-family:'Space Grotesk',sans-serif] relative mt-5 text-sm font-black uppercase tracking-[0.08em] text-slate-950"
                                >
                                    {{ feature.title }}
                                </h3>
                                <p class="relative mt-2 text-sm leading-6 text-slate-600">
                                    {{ feature.description }}
                                </p>
                            </article>
                        </div>
                    </div>

                    <div>
                        <section
                            class="bg-white/72 rounded-[24px] border border-white/80 p-5 shadow-[0_18px_40px_rgba(15,23,42,0.05)] backdrop-blur sm:rounded-[32px] sm:p-6"
                        >
                            <div class="grid grid-cols-2 gap-6 sm:grid-cols-3 sm:gap-8">
                                <div v-for="group in footerLinkGroups" :key="group.title">
                                    <h3
                                        class="[font-family:'Space Grotesk',sans-serif] text-sm font-black uppercase tracking-[0.12em] text-slate-950"
                                    >
                                        {{ group.title }}
                                    </h3>
                                    <ul class="mt-3 space-y-2.5 text-[0.92rem] text-slate-600 sm:mt-4 sm:space-y-3 sm:text-sm">
                                        <li v-for="link in group.links" :key="link.label">
                                            <component
                                                :is="link.href.startsWith('/') ? Link : 'a'"
                                                :href="link.href"
                                                class="inline-flex items-center gap-2 transition duration-200 hover:translate-x-1 hover:text-indigo-700"
                                            >
                                                <span class="h-1.5 w-1.5 rounded-full bg-slate-300"></span>
                                                <span>{{ link.label }}</span>
                                            </component>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </section>
                    </div>
                </div>

                <div
                    class="relative mt-8 overflow-hidden rounded-[24px] border border-white/10 bg-[linear-gradient(135deg,rgba(5,10,24,0.96),rgba(30,41,59,0.96)_48%,rgba(30,64,175,0.88)_118%)] p-4 shadow-[0_24px_60px_rgba(15,23,42,0.18)] backdrop-blur sm:mt-10 sm:rounded-[30px] sm:p-6"
                >
                    <div class="bg-fuchsia-500/16 pointer-events-none absolute -left-12 top-0 h-36 w-36 rounded-full blur-3xl"></div>
                    <div class="bg-cyan-400/16 pointer-events-none absolute bottom-[-14%] right-[-8%] h-40 w-40 rounded-full blur-3xl"></div>

                    <div class="relative flex flex-col gap-5 sm:gap-6 xl:flex-row xl:items-center xl:justify-between">
                        <div class="min-w-0 flex-1">
                            <p class="[font-family:'Space Grotesk',sans-serif] text-sm font-black uppercase tracking-[0.12em] text-white">
                                Pembayaran
                            </p>
                            <p class="mt-2 max-w-xl text-[0.92rem] leading-6 text-slate-300 sm:text-sm">
                                Metode populer bergerak terus di footer biar area bawah tetap terasa aktif, relevan, dan langsung kasih konteks
                                checkout.
                            </p>
                            <div class="payment-marquee-mask mt-4">
                                <div class="payment-marquee-track" :class="{ 'payment-marquee-track--static': footerPayments.length <= 4 }">
                                    <div
                                        v-for="(payment, index) in footerMarqueePayments"
                                        :key="`${payment.id}-${index}`"
                                        class="payment-marquee-chip"
                                    >
                                        <img v-if="payment.image" :src="payment.image" :alt="payment.label" class="payment-marquee-logo" />
                                        <span v-else class="payment-marquee-fallback">{{ payment.label }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="shrink-0 xl:max-w-sm">
                            <p class="[font-family:'Space Grotesk',sans-serif] text-sm font-black uppercase tracking-[0.12em] text-white">
                                Ikuti Kami
                            </p>
                            <p class="mt-2 text-[0.92rem] leading-6 text-slate-300 sm:text-sm">
                                Simpan kanal utama Lyva buat update promo, balas bantuan cepat, dan info terbaru dari tim.
                            </p>
                            <div class="mt-4 flex flex-wrap gap-2">
                                <TooltipProvider :delay-duration="100">
                                    <Tooltip v-for="social in footerSocials" :key="social.label">
                                        <TooltipTrigger as-child>
                                            <a
                                                :href="social.href"
                                                class="border-white/14 hover:border-white/24 hover:bg-white/16 inline-flex items-center gap-2 rounded-full border bg-white/10 px-3.5 py-2.5 text-xs font-bold uppercase tracking-[0.1em] text-white transition hover:-translate-y-0.5"
                                            >
                                                <component :is="social.icon" class="size-3.5" />
                                                <span>{{ social.label }}</span>
                                            </a>
                                        </TooltipTrigger>
                                        <TooltipContent
                                            side="top"
                                            class="max-w-[240px] rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-[0_14px_34px_rgba(15,23,42,0.16)]"
                                        >
                                            <p>{{ social.detail }}</p>
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    class="mt-8 flex flex-col gap-2.5 border-t border-slate-200/80 pt-5 text-[0.88rem] text-slate-500 sm:pt-6 sm:text-sm md:flex-row md:items-center md:justify-between md:gap-3"
                >
                    <p>© 2026 Lyva Indonesia. Semua hak dilindungi.</p>
                    <p class="flex items-center gap-2 leading-6">
                        <WalletCards class="size-4 text-indigo-700" />
                        Promo, top up, dan voucher digital dalam satu halaman.
                    </p>
                </div>
            </div>
        </footer>

        <nav class="mobile-dock md:hidden" aria-label="Navigasi cepat mobile">
            <div class="mobile-dock__shell">
                <Link
                    v-for="item in mobileDockLinks"
                    :key="item.id"
                    :href="item.href"
                    class="mobile-dock__item"
                    :class="{
                        'mobile-dock__item--active': item.active && !item.prominent,
                        'mobile-dock__item--prominent': item.prominent,
                        'mobile-dock__item--prominent-active': item.prominent && item.active,
                    }"
                >
                    <span v-if="item.prominent" class="mobile-dock__coin-wrap">
                        <span class="mobile-dock__coin-glow"></span>
                        <span class="mobile-dock__coin-button">
                            <component :is="item.icon" class="size-5" />
                        </span>
                    </span>
                    <span v-else class="mobile-dock__icon-wrap">
                        <component :is="item.icon" class="size-[1.05rem]" />
                    </span>
                    <span class="mobile-dock__label">{{ item.label }}</span>
                </Link>
            </div>
        </nav>
    </div>
</template>

<style scoped>
.coin-entry__icon {
    animation: coin-entry-pulse 2.8s ease-in-out infinite;
}

.coin-entry__glyph {
    animation: coin-entry-glyph 2.8s ease-in-out infinite;
    transform-origin: center;
}

.mobile-dock {
    position: fixed;
    inset-inline: 0;
    bottom: 0;
    z-index: 65;
    padding-top: 0;
    padding-bottom: 0;
    background: linear-gradient(180deg, rgba(255, 255, 255, 0), rgba(248, 250, 255, 0.92) 32%, rgba(248, 250, 255, 0.98) 100%);
    transform: translateZ(0);
    -webkit-transform: translateZ(0);
    will-change: transform;
}

.mobile-dock__shell {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    align-items: end;
    gap: 0.08rem;
    width: 100%;
    padding: 0.04rem 0.22rem calc(0.08rem + env(safe-area-inset-bottom, 0px));
    border: 1px solid rgba(226, 232, 240, 0.95);
    border-radius: 1.45rem 1.45rem 0 0;
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.96), rgba(247, 250, 255, 0.93));
    box-shadow:
        0 20px 45px rgba(15, 23, 42, 0.12),
        0 8px 20px rgba(99, 102, 241, 0.08);
    backdrop-filter: blur(22px);
    -webkit-backdrop-filter: blur(22px);
    transform: translateZ(0);
    -webkit-transform: translateZ(0);
}

.mobile-dock__item {
    position: relative;
    display: inline-flex;
    min-width: 0;
    flex-direction: column;
    align-items: center;
    justify-content: flex-end;
    gap: 0.14rem;
    color: rgb(100 116 139);
    text-decoration: none;
    transition:
        transform 180ms ease,
        color 180ms ease,
        opacity 180ms ease;
}

.mobile-dock__item:active {
    transform: translateY(1px) scale(0.985);
}

.mobile-dock__item--active {
    color: rgb(67 56 202);
}

.mobile-dock__item--prominent {
    transform: translateY(-0.82rem);
}

.mobile-dock__item--prominent:active {
    transform: translateY(-0.76rem) scale(0.985);
}

.mobile-dock__item--prominent-active .mobile-dock__coin-button {
    box-shadow:
        0 18px 32px rgba(99, 102, 241, 0.34),
        0 6px 18px rgba(56, 189, 248, 0.2);
}

.mobile-dock__icon-wrap,
.mobile-dock__coin-button {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 9999px;
}

.mobile-dock__icon-wrap {
    height: 1.82rem;
    width: 1.82rem;
    border: 1px solid transparent;
    transition:
        border-color 180ms ease,
        background-color 180ms ease,
        box-shadow 180ms ease;
}

.mobile-dock__item--active .mobile-dock__icon-wrap {
    border-color: rgba(165, 180, 252, 0.55);
    background: rgba(238, 242, 255, 0.95);
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.72);
}

.mobile-dock__coin-wrap {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.mobile-dock__coin-glow {
    position: absolute;
    inset: auto;
    height: 3.55rem;
    width: 3.55rem;
    border-radius: 9999px;
    background: radial-gradient(circle, rgba(99, 102, 241, 0.24) 0%, rgba(99, 102, 241, 0) 72%);
    filter: blur(10px);
    animation: mobile-dock-glow 3.4s ease-in-out infinite;
}

.mobile-dock__coin-button {
    z-index: 1;
    height: 3.02rem;
    width: 3.02rem;
    border: 3px solid rgba(248, 251, 255, 0.98);
    background: linear-gradient(180deg, #7c6cff 0%, #5b6bff 55%, #4757f5 100%);
    color: white;
    box-shadow:
        0 12px 22px rgba(99, 102, 241, 0.24),
        inset 0 1px 0 rgba(255, 255, 255, 0.35);
    animation: mobile-dock-float 3.6s ease-in-out infinite;
}

.mobile-dock__label {
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-size: 0.61rem;
    font-weight: 700;
    letter-spacing: 0.01em;
    line-height: 1;
    display: inline-flex;
    align-items: center;
    min-height: 0.9rem;
}

.mobile-dock__item--prominent .mobile-dock__label {
    font-size: 0.64rem;
    color: rgb(55 65 81);
    margin-top: 0;
}

@keyframes mobile-dock-float {
    0%,
    100% {
        transform: translate3d(0, 0, 0);
    }

    50% {
        transform: translate3d(0, -3px, 0);
    }
}

@keyframes mobile-dock-glow {
    0%,
    100% {
        opacity: 0.7;
        transform: scale(0.96);
    }

    50% {
        opacity: 1;
        transform: scale(1.08);
    }
}

@keyframes coin-entry-pulse {
    0%,
    100% {
        transform: scale(1);
        box-shadow:
            inset 0 1px 0 rgba(255, 255, 255, 0.6),
            0 0 0 0 rgba(245, 158, 11, 0.18);
    }

    50% {
        transform: scale(1.06);
        box-shadow:
            inset 0 1px 0 rgba(255, 255, 255, 0.6),
            0 0 0 8px rgba(245, 158, 11, 0);
    }
}

@keyframes coin-entry-glyph {
    0%,
    100% {
        transform: rotate(0deg) translateY(0);
    }

    25% {
        transform: rotate(-8deg) translateY(-0.5px);
    }

    75% {
        transform: rotate(8deg) translateY(0.5px);
    }
}

@media (prefers-reduced-motion: reduce) {
    .coin-entry__icon,
    .coin-entry__glyph,
    .mobile-dock__item,
    .mobile-dock__icon-wrap,
    .mobile-dock__coin-button,
    .mobile-dock__coin-glow {
        animation: none !important;
        transition: none !important;
    }
}
</style>
