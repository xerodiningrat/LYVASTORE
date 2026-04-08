<script setup lang="ts">
import Button from '@/components/ui/button/Button.vue';
import { Dialog, DialogContent, DialogTitle } from '@/components/ui/dialog';
import Input from '@/components/ui/input/Input.vue';
import Label from '@/components/ui/label/Label.vue';
import {
    catalogProducts,
    getProductFamilyRule,
    sortProductFamilyProducts,
    type CatalogProduct,
} from '@/data/catalog';
import { applyProductDisplayOverride } from '@/data/product-display-overrides';
import { lyvaCoinsForAmount } from '@/lib/lyva-coins';
import { applyProductArtworkOverride, withFullProductArtwork } from '@/data/product-artwork';
import PublicLayout from '@/layouts/PublicLayout.vue';
import type { SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    ArrowRightLeft,
    BadgeCheck,
    ChevronRight,
    CircleAlert,
    Coins,
    Flame,
    Gift,
    LayoutGrid,
    LoaderCircle,
    LogIn,
    ShieldCheck,
    Star,
    Ticket,
    UserRound,
    WalletCards,
} from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

type DetailField = {
    id: string;
    label: string;
    placeholder: string;
    inputType?: string;
    required?: boolean;
};

type PaymentOption = {
    id: string;
    label: string;
    caption: string;
    image?: string | null;
    fee?: number | null;
    group?: string | null;
};

type PackageOptionTemplate = {
    id: string;
    label: string;
    note: string;
    featured?: boolean;
};

type PackageGroupTemplate = {
    id: string;
    label: string;
    title: string;
    basePrice: number;
    stepPrice: number;
    options: PackageOptionTemplate[];
};

type PackageOption = PackageOptionTemplate & {
    code?: string;
    details?: string[];
    accountFields?: DetailField[];
    price: number;
    oldPrice?: number;
    discountLabel?: string;
};

type PackageGroup = {
    id: string;
    label: string;
    title: string;
    options: PackageOption[];
};

type DetailTab = {
    id: string;
    label: string;
    groups: PackageGroup[];
};

type DetailPreset = {
    trustBadges: string[];
    guideTitle: string;
    guideSteps: string[];
    extraTitle: string;
    extraSteps: string[];
    notes: string[];
    accountFields: DetailField[];
    contactFields: DetailField[];
    payments: PaymentOption[];
    tabs: DetailTab[];
    soldToday: string;
    guaranteeText: string;
    promoPlaceholder: string;
};

type RatingReview = {
    id: string;
    name: string;
    badge: string;
    timeLabel: string;
    rating: number;
    comment: string;
};

type ProductRatingSummary = {
    average: string;
    totalReviews: number;
    recommendationRate: number;
    fiveStarRate: number;
    processingSpeed: string;
    familyTitle: string;
};

type ProductRatings = {
    summary: ProductRatingSummary;
    reviews: RatingReview[];
};

type ProductVariantShortcut = {
    id: string;
    name: string;
};

type VipCatalogProduct = {
    id: string;
    name: string;
    categoryId: string;
    categoryTitle: string;
    badge?: string | null;
};

type PromoPreviewState = {
    status: 'idle' | 'checking' | 'applied' | 'invalid';
    message: string;
    code: string;
    label: string | null;
    discount: number;
    subtotal: number;
    finalTotal: number;
};

type NicknameLookupState = {
    status: 'idle' | 'checking' | 'success' | 'not_found' | 'unsupported' | 'error' | 'unavailable';
    message: string;
    nickname: string;
    lookupKey: string;
};

const props = defineProps<{
    productId: string;
    vipCatalogProduct?: VipCatalogProduct | null;
    initialVipaymentTabs?: DetailTab[] | null;
    initialVipaymentSource?: 'vipayment' | 'fallback' | null;
    initialVipaymentMessage?: string | null;
    initialPaymentMethods?: PaymentOption[] | null;
    initialPaymentMethodsAmount?: number | null;
    productRatings: ProductRatings;
}>();

type CachedVipaymentState = {
    tabs: DetailTab[];
    source: 'vipayment' | 'fallback';
    message: string;
};

const page = usePage<SharedData>();
const siteUrl = 'https://lyvaindonesia.com';
const productArtworkOverrides = computed(() => page.props.adminPanel?.productArtworkOverrides ?? {});
const productDisplayOverrides = computed(() => page.props.adminPanel?.productDisplayOverrides ?? {});
const cspNonce = computed(() => page.props.security?.cspNonce);
const checkoutIntentToken = computed(() => page.props.security?.checkoutIntentToken ?? '');
const coinProgram = computed(() => page.props.coinProgram ?? null);
const applyProductOverrides = <
    T extends { id: string; name: string; categoryTitle?: string | null; badge?: string | null; coverImage: string; iconImage: string },
>(
    product: T,
): T => applyProductArtworkOverride(applyProductDisplayOverride(product, productDisplayOverrides.value[product.id]), productArtworkOverrides.value[product.id]);
const hydrateVipCatalogProduct = (product: VipCatalogProduct | null | undefined): CatalogProduct | null => {
    if (!product) {
        return null;
    }

    const hydrated = applyProductOverrides(withFullProductArtwork(product));

    return {
        ...hydrated,
        badge: hydrated.badge ?? undefined,
        categoryId: hydrated.categoryId,
        categoryTitle: hydrated.categoryTitle,
        categoryIcon: LayoutGrid,
    };
};
const currentUser = computed(() => page.props.auth?.user ?? null);
const currentCoinBalance = computed(() => page.props.auth?.coins?.balance ?? 0);
const isLoggedIn = computed(() => Boolean(currentUser.value));
const profileContactOverrides = computed<Record<string, string>>(() => ({
    'buyer-email': currentUser.value?.email ?? '',
    'buyer-whatsapp': currentUser.value?.whatsapp_number ?? '',
}));
const coinRewardHint = computed(() => page.props.coinProgram?.rewardRateLabel ?? 'Cashback disesuaikan otomatis agar margin tetap aman.');
let checkoutPageWarmupPromise: Promise<unknown> | null = null;
const checkoutFormStartedAt = ref<number>(Date.now());

const formatCurrency = (value: number) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);

const formatNumber = (value: number) =>
    new Intl.NumberFormat('id-ID', {
        maximumFractionDigits: 0,
    }).format(value);

const formatCoinCount = (value: number) => `${formatNumber(Math.max(0, Math.round(value)))} Coins`;

const currentCoinBalanceLabel = computed(() => formatCoinCount(currentCoinBalance.value));

const hashString = (value: string) =>
    value.split('').reduce((carry, character, index) => carry + character.charCodeAt(0) * (index + 11), 0);

const jakartaClockFormatter = new Intl.DateTimeFormat('en-CA', {
    timeZone: 'Asia/Jakarta',
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    hourCycle: 'h23',
});

const hourlyTrafficCurve = [
    0.38, 0.34, 0.32, 0.3, 0.31, 0.36, 0.48, 0.64, 0.82, 0.96, 1.05, 1.12,
    1.18, 1.15, 1.09, 1.04, 1.1, 1.19, 1.25, 1.21, 1.04, 0.86, 0.68, 0.5,
];

const soldTodayClock = ref(new Date());
let soldTodayClockTimer: ReturnType<typeof window.setTimeout> | null = null;

const parseSoldTodayTemplate = (value: string) => {
    const match = value.trim().match(/^([\d.,]+)\s+(.*)$/);

    if (!match) {
        return {
            baseCount: 0,
            suffix: value.trim(),
        };
    }

    return {
        baseCount: Number.parseInt(match[1].replace(/[^\d]/g, ''), 10) || 0,
        suffix: match[2]?.trim() || 'item dibeli hari ini',
    };
};

const getJakartaClockParts = (value: Date) => {
    const lookup = Object.fromEntries(
        jakartaClockFormatter
            .formatToParts(value)
            .filter((part) => part.type !== 'literal')
            .map((part) => [part.type, part.value]),
    );

    return {
        year: Number.parseInt(lookup.year ?? '0', 10) || 0,
        month: Number.parseInt(lookup.month ?? '0', 10) || 0,
        day: Number.parseInt(lookup.day ?? '0', 10) || 0,
        hour: Number.parseInt(lookup.hour ?? '0', 10) || 0,
    };
};

const clearSoldTodayClockTimer = () => {
    if (soldTodayClockTimer) {
        window.clearTimeout(soldTodayClockTimer);
        soldTodayClockTimer = null;
    }
};

const scheduleSoldTodayClock = () => {
    if (typeof window === 'undefined') {
        return;
    }

    clearSoldTodayClockTimer();
    soldTodayClock.value = new Date();

    const now = new Date();
    const nextMinute = new Date(now);
    nextMinute.setSeconds(0, 0);
    nextMinute.setMinutes(nextMinute.getMinutes() + 1);
    const delay = Math.max(1000, nextMinute.getTime() - now.getTime());

    soldTodayClockTimer = window.setTimeout(() => {
        scheduleSoldTodayClock();
    }, delay);
};

const defaultContactFields = (): DetailField[] => [
    { id: 'buyer-email', label: 'Email', placeholder: 'nama@contoh.com' },
    { id: 'buyer-whatsapp', label: 'Nomor WhatsApp', placeholder: '08xxxxxxxxxx' },
];

const accountStyleKeywords = ['chatgpt', 'capcut', 'canva', 'netflix', 'spotify', 'youtube', 'prime video', 'bstation', 'alight motion', 'viu', 'vidio'];
const loginStyleKeywords = ['login', 'username', 'password', 'email / username', 'email/username'];
const zoneRequiredKeywords = ['mobile legends', 'honor of kings', 'pubg', 'magic chess'];

const productContextFlags = (product: CatalogProduct) => {
    const productContext = `${product.id} ${product.name} ${product.categoryId} ${product.categoryTitle}`.toLowerCase();
    const isLoginProduct = product.categoryId === 'login' || loginStyleKeywords.some((keyword) => productContext.includes(keyword));
    const isReleaseProduct = product.categoryId === 'release';
    const isGiftProduct = productContext.includes('gift');
    const isAccountStyleProduct = accountStyleKeywords.some((keyword) => productContext.includes(keyword));
    const usesSingleUserIdFallback = productContext.includes('blood strike');
    const requiresZoneFallback = zoneRequiredKeywords.some((keyword) => productContext.includes(keyword)) && !isAccountStyleProduct;

    return {
        productContext,
        isLoginProduct,
        isReleaseProduct,
        isGiftProduct,
        isAccountStyleProduct,
        usesSingleUserIdFallback,
        requiresZoneFallback,
    };
};

const materializeGroups = (templates: PackageGroupTemplate[], seed: number, offset = 0): PackageGroup[] =>
    templates.map((group, groupIndex) => ({
        id: group.id,
        label: group.label,
        title: group.title,
        options: group.options.map((option, optionIndex) => {
            const price = Math.round((group.basePrice + offset + group.stepPrice * optionIndex + (seed % 1700) + groupIndex * 950) / 100) * 100;
            const hasPromo = option.featured || (seed + groupIndex + optionIndex) % 5 === 0;

            return {
                ...option,
                price,
                oldPrice: hasPromo ? Math.round((price * 1.08) / 100) * 100 : undefined,
                discountLabel: hasPromo ? '2%' : undefined,
            };
        }),
    }));

const buildPreset = (product: CatalogProduct): DetailPreset => {
    const seed = hashString(product.id);

    const gamePayments: PaymentOption[] = [
        { id: 'qris', label: 'QRIS', caption: 'Verifikasi cepat' },
        { id: 'gopay', label: 'GoPay', caption: 'Wallet populer' },
        { id: 'bank-transfer', label: 'Bank Transfer', caption: 'Manual check' },
        { id: 'dana', label: 'DANA', caption: 'Checkout ringan' },
    ];

    if (product.categoryId === 'voucher') {
        return {
            trustBadges: ['Voucher resmi & aman', 'Proses lebih cepat', 'Cocok untuk isi akun game'],
            guideTitle: `Cara beli ${product.name}`,
            guideSteps: [
                'Pilih nominal voucher yang ingin kamu beli.',
                'Masukkan User ID atau data akun sesuai kebutuhan produk.',
                'Selesaikan pembayaran lalu voucher atau saldo akan diproses otomatis.',
            ],
            extraTitle: 'Cara redeem voucher',
            extraSteps: [
                'Masuk ke platform tujuan lalu buka menu redeem code jika produk berupa kode voucher.',
                'Masukkan kode voucher dari riwayat transaksi atau ikuti instruksi produk yang dibeli.',
                'Konfirmasi redeem sampai saldo atau item berhasil masuk ke akun.',
            ],
            notes: [
                'Kode voucher bersifat digital dan tidak bisa diuangkan.',
                'Pastikan region akun sesuai dengan voucher yang dibeli.',
            ],
            accountFields: [
                { id: 'account-user-id', label: 'User ID', placeholder: 'Masukkan user ID' },
            ],
            contactFields: defaultContactFields(),
            payments: [
                { id: 'qris', label: 'QRIS', caption: 'Instan' },
                { id: 'credit-card', label: 'Kartu Debit/Kredit', caption: 'Visa / Mastercard' },
                { id: 'ovo', label: 'OVO', caption: 'E-wallet' },
                { id: 'bank-transfer', label: 'Bank Transfer', caption: 'Konfirmasi manual' },
            ],
            tabs: [
                {
                    id: 'purchase',
                    label: 'Pembelian',
                    groups: materializeGroups(
                        [
                            {
                                id: 'regular-voucher',
                                label: 'Nominal Reguler',
                                title: 'Pilih nominal voucher',
                                basePrice: 20000,
                                stepPrice: 15000,
                                options: [
                                    { id: 'voucher-20', label: 'Voucher 20K', note: 'Kode digital' },
                                    { id: 'voucher-50', label: 'Voucher 50K', note: 'Kode digital', featured: true },
                                    { id: 'voucher-100', label: 'Voucher 100K', note: 'Best value' },
                                    { id: 'voucher-250', label: 'Voucher 250K', note: 'Stok aman' },
                                ],
                            },
                            {
                                id: 'best-value',
                                label: 'Paket Favorit',
                                title: 'Nominal paling sering dibeli',
                                basePrice: 75000,
                                stepPrice: 22500,
                                options: [
                                    { id: 'bundle-1', label: 'Bundle Hemat', note: 'Untuk top up ringan' },
                                    { id: 'bundle-2', label: 'Bundle Plus', note: 'Untuk belanja bulanan' },
                                    { id: 'bundle-3', label: 'Bundle Max', note: 'Untuk kebutuhan besar', featured: true },
                                ],
                            },
                        ],
                        seed,
                    ),
                },
                {
                    id: 'gift',
                    label: 'Gift Voucher',
                    groups: materializeGroups(
                        [
                            {
                                id: 'gift-fast',
                                label: 'Gift Instan',
                                title: 'Voucher kirim cepat',
                                basePrice: 25000,
                                stepPrice: 18000,
                                options: [
                                    { id: 'gift-25', label: 'Gift 25K', note: 'Siap kirim' },
                                    { id: 'gift-60', label: 'Gift 60K', note: 'Paling sering dipilih', featured: true },
                                    { id: 'gift-120', label: 'Gift 120K', note: 'Nominal besar' },
                                ],
                            },
                        ],
                        seed,
                        2200,
                    ),
                },
            ],
            soldToday: `${formatNumber(12100 + (seed % 5300))} voucher dibeli hari ini`,
            guaranteeText: 'Kode dikirim otomatis tanpa biaya tambahan.',
            promoPlaceholder: 'Masukkan kode promo voucher',
        };
    }

    if (product.categoryId === 'pulsa') {
        return {
            trustBadges: ['Transaksi operator resmi', 'Masuk cepat ke nomor tujuan', 'Cocok untuk isi pulsa rutin'],
            guideTitle: `Cara isi ${product.name}`,
            guideSteps: [
                'Pilih nominal pulsa atau paket yang diinginkan.',
                'Masukkan nomor HP penerima dengan benar.',
                'Bayar lalu pulsa akan diproses otomatis ke nomor tujuan.',
            ],
            extraTitle: 'Tips sebelum checkout',
            extraSteps: [
                'Pastikan nomor aktif dan tidak salah digit.',
                'Cek kembali operator sesuai produk yang kamu pilih.',
                'Simpan invoice untuk pengecekan transaksi jika dibutuhkan.',
            ],
            notes: [
                'Pulsa digital tidak bisa dibatalkan setelah nomor dikirim.',
                'Waktu masuk bisa berbeda tergantung operator.',
            ],
            accountFields: [{ id: 'target-number', label: 'Nomor tujuan', placeholder: '08xxxxxxxxxx' }],
            contactFields: defaultContactFields(),
            payments: [
                { id: 'qris', label: 'QRIS', caption: 'Instan' },
                { id: 'gopay', label: 'GoPay', caption: 'Cashless' },
                { id: 'dana', label: 'DANA', caption: 'Wallet populer' },
                { id: 'bank-transfer', label: 'Bank Transfer', caption: 'Manual check' },
            ],
            tabs: [
                {
                    id: 'purchase',
                    label: 'Pembelian',
                    groups: materializeGroups(
                        [
                            {
                                id: 'regular-topup',
                                label: 'Pulsa Reguler',
                                title: 'Nominal pulsa',
                                basePrice: 10000,
                                stepPrice: 5500,
                                options: [
                                    { id: 'pulsa-10', label: 'Pulsa 10K', note: 'Reguler' },
                                    { id: 'pulsa-25', label: 'Pulsa 25K', note: 'Cepat masuk' },
                                    { id: 'pulsa-50', label: 'Pulsa 50K', note: 'Paling ramai', featured: true },
                                    { id: 'pulsa-100', label: 'Pulsa 100K', note: 'Nominal besar' },
                                ],
                            },
                            {
                                id: 'combo-package',
                                label: 'Paket Hemat',
                                title: 'Paket combo',
                                basePrice: 18000,
                                stepPrice: 8500,
                                options: [
                                    { id: 'combo-mini', label: 'Combo Mini', note: 'Pulsa + bonus' },
                                    { id: 'combo-plus', label: 'Combo Plus', note: 'Untuk harian' },
                                    { id: 'combo-max', label: 'Combo Max', note: 'Untuk mingguan', featured: true },
                                ],
                            },
                        ],
                        seed,
                    ),
                },
                {
                    id: 'special',
                    label: 'Paket Favorit',
                    groups: materializeGroups(
                        [
                            {
                                id: 'special-package',
                                label: 'Promo Operator',
                                title: 'Paket pilihan',
                                basePrice: 22000,
                                stepPrice: 12000,
                                options: [
                                    { id: 'special-1', label: 'Paket Nelpon', note: 'Aktif cepat' },
                                    { id: 'special-2', label: 'Paket Chat', note: 'Value tinggi', featured: true },
                                    { id: 'special-3', label: 'Paket Internet', note: 'Bonus kuota' },
                                ],
                            },
                        ],
                        seed,
                        2400,
                    ),
                },
            ],
            soldToday: `${formatNumber(9800 + (seed % 4100))} top up diproses hari ini`,
            guaranteeText: 'Nominal yang kamu lihat sudah final tanpa biaya tersembunyi.',
            promoPlaceholder: 'Masukkan kode promo operator',
        };
    }

    if (product.categoryId === 'ewallet') {
        const walletContext = `${product.id} ${product.name}`.toLowerCase();
        const isCardBasedWallet = ['brizzi', 'brizi', 'tapcash', 'flazz', 'e-money', 'emoney', 'e toll', 'etoll'].some((keyword) =>
            walletContext.includes(keyword),
        );

        return {
            trustBadges: isCardBasedWallet
                ? ['Top up kartu resmi', 'Cocok untuk kartu tol & transit', 'Proses lebih aman dan cepat']
                : ['Saldo resmi & aman', 'Masuk cepat ke akun tujuan', 'Cocok untuk isi saldo rutin'],
            guideTitle: `Cara isi ${product.name}`,
            guideSteps: isCardBasedWallet
                ? [
                      'Pilih nominal top up kartu yang kamu butuhkan.',
                      'Masukkan nomor kartu atau nomor seri sesuai kartu yang dipakai.',
                      'Selesaikan pembayaran lalu top up akan diproses sesuai produk yang dipilih.',
                  ]
                : [
                      'Pilih nominal saldo atau paket yang kamu butuhkan.',
                      'Masukkan nomor akun atau nomor HP yang terdaftar.',
                      'Selesaikan pembayaran lalu saldo diproses ke akun tujuan.',
                  ],
            extraTitle: 'Tips sebelum checkout',
            extraSteps: isCardBasedWallet
                ? [
                      'Pastikan nomor kartu atau nomor seri dimasukkan dengan benar.',
                      'Cek kembali jenis kartu sesuai produk yang kamu pilih.',
                      'Simpan invoice untuk pengecekan transaksi jika dibutuhkan.',
                  ]
                : [
                      'Pastikan nomor akun atau nomor HP aktif dan benar.',
                      'Cek kembali layanan e-wallet sesuai produk yang kamu pilih.',
                      'Simpan invoice untuk pengecekan transaksi jika dibutuhkan.',
                  ],
            notes: isCardBasedWallet
                ? [
                      'Top up kartu digital tidak bisa dibatalkan setelah nomor kartu dikirim.',
                      'Ikuti petunjuk aktivasi atau update saldo kartu sesuai layanan yang dipakai.',
                  ]
                : [
                      'Saldo digital tidak bisa dibatalkan setelah nomor tujuan dikirim.',
                      'Waktu masuk bisa berbeda tergantung antrean provider dan layanan tujuan.',
                  ],
            accountFields: [
                isCardBasedWallet
                    ? { id: 'account-number', label: 'Nomor kartu', placeholder: 'Masukkan nomor kartu' }
                    : { id: 'account-number', label: 'Nomor akun / nomor HP', placeholder: '08xxxxxxxxxx' },
            ],
            contactFields: defaultContactFields(),
            payments: [
                { id: 'qris', label: 'QRIS', caption: 'Instan' },
                { id: 'gopay', label: 'GoPay', caption: 'Cashless' },
                { id: 'dana', label: 'DANA', caption: 'Wallet populer' },
                { id: 'bank-transfer', label: 'Bank Transfer', caption: 'Manual check' },
            ],
            tabs: [
                {
                    id: 'purchase',
                    label: 'Pembelian',
                    groups: materializeGroups(
                        [
                            {
                                id: 'wallet-balance',
                                label: 'Nominal Saldo',
                                title: 'Pilih nominal saldo',
                                basePrice: 10000,
                                stepPrice: 5000,
                                options: [
                                    { id: 'wallet-10', label: 'Saldo 10K', note: 'Nominal ringan' },
                                    { id: 'wallet-20', label: 'Saldo 20K', note: 'Cepat diproses' },
                                    { id: 'wallet-50', label: 'Saldo 50K', note: 'Paling ramai', featured: true },
                                    { id: 'wallet-100', label: 'Saldo 100K', note: 'Nominal besar' },
                                ],
                            },
                        ],
                        seed,
                    ),
                },
            ],
            soldToday: `${formatNumber(5200 + (seed % 2900))} saldo diproses hari ini`,
            guaranteeText: 'Nominal yang kamu lihat sudah final tanpa biaya tersembunyi.',
            promoPlaceholder: 'Masukkan kode promo e-wallet',
        };
    }

    if (product.categoryId === 'entertainment') {
        const entertainmentContext = `${product.id} ${product.name}`.toLowerCase();
        const usesEmailOnly = ['youtube', 'bstation', 'iqiyi', 'i qiyi'].some((keyword) => entertainmentContext.includes(keyword));
        const usesLoginCredentials = ['viu', 'vidio'].some((keyword) => entertainmentContext.includes(keyword));
        const usesUserId = ['wetv', 'we tv', 'vision plus', 'vision+', 'likee'].some((keyword) => entertainmentContext.includes(keyword));
        const usesSubscriberNumber = ['k-vision', 'k vision', 'nex parabola', 'orange tv'].some((keyword) => entertainmentContext.includes(keyword));

        return {
            trustBadges: ['Akses digital resmi', 'Aktivasi lebih cepat', 'Cocok untuk akun pribadi atau gift'],
            guideTitle: `Cara aktifkan ${product.name}`,
            guideSteps: usesSubscriberNumber
                ? [
                      'Pilih paket tayangan atau langganan yang kamu butuhkan.',
                      'Masukkan nomor pelanggan atau smart card sesuai layanan.',
                      'Selesaikan pembayaran lalu paket akan diproses otomatis.',
                  ]
                : [
                      'Pilih paket langganan yang kamu butuhkan.',
                      'Masukkan data akun sesuai kebutuhan layanan.',
                      'Selesaikan pembayaran lalu detail aktivasi dikirim otomatis.',
                  ],
            extraTitle: 'Catatan aktivasi',
            extraSteps: [
                'Gunakan email aktif untuk menerima instruksi penukaran.',
                'Beberapa layanan membutuhkan login ulang setelah aktivasi.',
                'Gift digital mengikuti syarat masing-masing platform.',
            ],
            notes: [
                'Masa aktif paket mengikuti detail pada nominal yang dipilih.',
                'Pastikan akun belum terkena batas region atau family lock.',
            ],
            accountFields: usesLoginCredentials
                ? [
                      { id: 'account-username', label: 'Email / username akun', placeholder: 'Masukkan email atau username akun' },
                      { id: 'account-password', label: 'Password akun', placeholder: 'Masukkan password akun', inputType: 'password' },
                  ]
                : usesUserId
                  ? [{ id: 'account-user-id', label: 'User ID', placeholder: 'Masukkan user ID' }]
                  : usesSubscriberNumber
                    ? [{ id: 'account-number', label: 'Nomor pelanggan / smart card', placeholder: 'Masukkan nomor pelanggan atau smart card' }]
                    : usesEmailOnly
                      ? [{ id: 'account-email', label: 'Email akun', placeholder: 'Masukkan email akun', inputType: 'email' }]
                      : [{ id: 'account-email', label: 'Email / username akun', placeholder: 'Masukkan email atau username akun' }],
            contactFields: defaultContactFields(),
            payments: [
                { id: 'qris', label: 'QRIS', caption: 'Instan' },
                { id: 'credit-card', label: 'Kartu Debit/Kredit', caption: 'Berlangganan mudah' },
                { id: 'gopay', label: 'GoPay', caption: 'Wallet' },
                { id: 'bank-transfer', label: 'Bank Transfer', caption: 'Manual check' },
            ],
            tabs: [
                {
                    id: 'subscription',
                    label: 'Langganan',
                    groups: materializeGroups(
                        [
                            {
                                id: 'personal-plan',
                                label: 'Paket Personal',
                                title: 'Langganan personal',
                                basePrice: 39000,
                                stepPrice: 22000,
                                options: [
                                    { id: 'personal-1', label: '1 Bulan', note: 'Akses personal' },
                                    { id: 'personal-3', label: '3 Bulan', note: 'Lebih hemat', featured: true },
                                    { id: 'personal-6', label: '6 Bulan', note: 'Untuk pemakaian rutin' },
                                ],
                            },
                            {
                                id: 'family-plan',
                                label: 'Paket Family',
                                title: 'Akses bareng keluarga',
                                basePrice: 69000,
                                stepPrice: 28000,
                                options: [
                                    { id: 'family-1', label: 'Family 1 Bulan', note: 'Multi profile' },
                                    { id: 'family-3', label: 'Family 3 Bulan', note: 'Best value', featured: true },
                                ],
                            },
                        ],
                        seed,
                    ),
                },
                {
                    id: 'gift',
                    label: 'Gift Voucher',
                    groups: materializeGroups(
                        [
                            {
                                id: 'gift-plan',
                                label: 'Gift Digital',
                                title: 'Hadiah siap kirim',
                                basePrice: 42000,
                                stepPrice: 24000,
                                options: [
                                    { id: 'gift-1', label: 'Gift 1 Bulan', note: 'Untuk teman' },
                                    { id: 'gift-3', label: 'Gift 3 Bulan', note: 'Pilihan favorit', featured: true },
                                    { id: 'gift-6', label: 'Gift 6 Bulan', note: 'Paket panjang' },
                                ],
                            },
                        ],
                        seed,
                        2600,
                    ),
                },
            ],
            soldToday: `${formatNumber(4300 + (seed % 2600))} langganan aktif hari ini`,
            guaranteeText: 'Harga final sudah termasuk proses aktivasi digital.',
            promoPlaceholder: 'Masukkan kode promo langganan',
        };
    }

    const { isLoginProduct, isReleaseProduct, isGiftProduct, isAccountStyleProduct, usesSingleUserIdFallback, requiresZoneFallback } =
        productContextFlags(product);

    return {
        trustBadges: isLoginProduct
            ? ['Proses khusus login product', 'Diverifikasi sebelum kirim', 'Support bantu cek data']
            : isAccountStyleProduct
              ? ['Official supply & safe', 'Proses otomatis lebih cepat', 'Cocok untuk akun premium rutin']
              : ['Official supply & safe', 'Proses otomatis lebih cepat', 'Cocok untuk top up rutin'],
        guideTitle: `Cara top up ${product.name}`,
        guideSteps: isLoginProduct
            ? [
                  'Pilih paket login atau nominal yang kamu butuhkan.',
                  'Masukkan data akun sesuai instruksi produk.',
                  'Selesaikan pembayaran lalu pesanan akan diverifikasi dan diproses.',
              ]
            : isAccountStyleProduct
              ? [
                    'Pilih paket atau durasi akun yang kamu inginkan.',
                    'Masukkan email, username, atau detail akun sesuai kebutuhan layanan.',
                    'Lanjutkan pembayaran lalu pesanan akan diproses otomatis.',
                ]
            : [
                  'Pilih nominal atau paket yang kamu inginkan.',
                  usesSingleUserIdFallback ? 'Masukkan User ID akun dengan benar.' : 'Masukkan User ID, server, atau data akun dengan benar.',
                  'Lanjutkan pembayaran dan item akan diproses otomatis.',
              ],
        extraTitle: isLoginProduct ? 'Catatan untuk produk login' : 'Tips sebelum checkout',
        extraSteps: isLoginProduct
            ? [
                  'Gunakan data login yang benar agar proses tidak tertunda.',
                  'Jangan ubah password selama transaksi sedang diproses.',
                  'Simpan bukti pembayaran untuk percepat bantuan support.',
              ]
            : isAccountStyleProduct
              ? [
                    'Cek kembali email atau username akun sebelum membayar.',
                    'Jangan ubah data login saat pesanan sedang diproses.',
                    'Simpan invoice untuk pengecekan riwayat transaksi.',
                ]
            : [
                  usesSingleUserIdFallback ? 'Cek kembali User ID sebelum membayar.' : 'Cek kembali User ID dan server sebelum membayar.',
                  'Pastikan akun tidak sedang terikat event khusus yang membatasi top up.',
                  'Simpan invoice untuk pengecekan riwayat transaksi.',
              ],
        notes: isReleaseProduct
            ? ['Harga paket bisa berubah mengikuti event launch atau promo musiman.', 'Paket founder atau starter biasanya punya stok promo terbatas.']
            : isAccountStyleProduct
              ? ['Pastikan detail akun yang dimasukkan sesuai layanan yang dipilih agar proses tidak tertunda.', 'Jika terjadi kendala, riwayat transaksi dan support bisa dipakai untuk pengecekan cepat.']
            : ['Nominal yang kamu pilih akan dikirim sesuai data akun yang dimasukkan.', 'Jika terjadi kendala, riwayat transaksi dan support bisa dipakai untuk pengecekan cepat.'],
        accountFields: isLoginProduct
            ? [
                  { id: 'account-username', label: 'Email / username akun', placeholder: 'Masukkan email atau username akun' },
                  { id: 'account-password', label: 'Password akun', placeholder: 'Masukkan password akun', inputType: 'password' },
                  ...(requiresZoneFallback ? [{ id: 'account-zone', label: 'Server / zone', placeholder: 'Masukkan server atau zone' }] : []),
              ]
            : isAccountStyleProduct
              ? [
                    { id: 'account-email', label: 'Email / username akun', placeholder: 'Masukkan email atau username akun' },
                ]
            : isGiftProduct
              ? [
                    { id: 'account-user-id', label: 'User ID penerima', placeholder: 'Masukkan user ID penerima' },
                    ...(requiresZoneFallback ? [{ id: 'account-zone', label: 'Server / zone', placeholder: 'Masukkan server atau zone' }] : []),
                ]
              : usesSingleUserIdFallback
                ? [{ id: 'account-user-id', label: 'User ID', placeholder: 'Masukkan User ID' }]
              : [
                    { id: 'account-user-id', label: 'User ID', placeholder: 'Masukkan User ID' },
                    { id: 'account-zone', label: 'Server / zone', placeholder: 'Masukkan server atau zone' },
                ],
        contactFields: defaultContactFields(),
        payments: gamePayments,
        tabs: [
            {
                id: 'purchase',
                label: 'Pembelian',
                groups: materializeGroups(
                    isReleaseProduct
                        ? [
                              {
                                  id: 'starter-launch',
                                  label: 'Starter Pack',
                                  title: 'Paket launch',
                                  basePrice: 18000,
                                  stepPrice: 11000,
                                  options: [
                                      { id: 'launch-1', label: 'Starter Pack', note: 'Untuk mulai cepat' },
                                      { id: 'launch-2', label: 'Booster Pack', note: 'Paling ramai', featured: true },
                                      { id: 'launch-3', label: 'Founders Pack', note: 'Value tinggi' },
                                      { id: 'launch-4', label: 'Elite Pack', note: 'Untuk push progress' },
                                  ],
                              },
                              {
                                  id: 'event-launch',
                                  label: 'Event Launch',
                                  title: 'Bundle event',
                                  basePrice: 26000,
                                  stepPrice: 15000,
                                  options: [
                                      { id: 'event-1', label: 'Launch Pass', note: 'Bonus item' },
                                      { id: 'event-2', label: 'Season Pass', note: 'Best value', featured: true },
                                      { id: 'event-3', label: 'Founder Bundle', note: 'Item lebih lengkap' },
                                  ],
                              },
                          ]
                        : [
                              {
                                  id: 'instant-topup',
                                  label: isLoginProduct ? 'Akses Utama' : 'Nominal Populer',
                                  title: isLoginProduct ? 'Pilihan akses akun' : 'Nominal pilihan',
                                  basePrice: 17000,
                                  stepPrice: 9000,
                                  options: [
                                      { id: 'topup-1', label: isLoginProduct ? 'VIP 7 Hari' : '86 Diamonds', note: 'Paling sering dibeli' },
                                      { id: 'topup-2', label: isLoginProduct ? 'VIP 30 Hari' : '172 Diamonds', note: 'Proses cepat', featured: true },
                                      { id: 'topup-3', label: isLoginProduct ? 'Pass Elite' : '257 Diamonds', note: 'Value aman' },
                                      { id: 'topup-4', label: isLoginProduct ? 'Pass Ultimate' : '344 Diamonds', note: 'Untuk push rank' },
                                  ],
                              },
                              {
                                  id: 'event-pack',
                                  label: isLoginProduct ? 'Paket Premium' : 'Paket Event',
                                  title: isLoginProduct ? 'Bundle login premium' : 'Pass dan bundle',
                                  basePrice: 26000,
                                  stepPrice: 14000,
                                  options: [
                                      { id: 'pack-1', label: isLoginProduct ? 'KVIP 7 Hari' : 'Weekly Pass', note: 'Aktif cepat' },
                                      { id: 'pack-2', label: isLoginProduct ? 'KVIP 30 Hari' : 'Twilight Pass', note: 'Pilihan favorit', featured: true },
                                      { id: 'pack-3', label: isLoginProduct ? 'SVIP Bundle' : 'Starlight Pass', note: 'Paket lengkap' },
                                  ],
                              },
                          ],
                    seed,
                ),
            },
            {
                id: 'gift',
                label: 'Gift Voucher',
                groups: materializeGroups(
                    [
                        {
                            id: 'gift-pack',
                            label: 'Gift Pack',
                            title: 'Kirim ke teman',
                            basePrice: 21000,
                            stepPrice: 12000,
                            options: [
                                { id: 'gift-pack-1', label: 'Gift Basic', note: 'Nominal ringan' },
                                { id: 'gift-pack-2', label: 'Gift Plus', note: 'Paling ramai', featured: true },
                                { id: 'gift-pack-3', label: 'Gift Max', note: 'Untuk event besar' },
                            ],
                        },
                    ],
                    seed,
                    2300,
                ),
            },
        ],
        soldToday: `${formatNumber(8900 + (seed % 6100))} item dibeli hari ini`,
        guaranteeText: 'Dijamin tidak ada tambahan biaya saat checkout.',
        promoPlaceholder: isLoginProduct ? 'Masukkan kode promo akses akun' : 'Masukkan kode promo top up',
    };
};

const hydratedInitialProduct = hydrateVipCatalogProduct(props.vipCatalogProduct);
const activeProductId = ref(props.productId);
const activeHydratedProduct = ref<CatalogProduct | null>(hydratedInitialProduct);
const catalogProductMap = computed(() => new Map(catalogProducts.map((item) => [item.id, applyProductOverrides(item)] as const)));
const product = computed(() => catalogProductMap.value.get(activeProductId.value) ?? activeHydratedProduct.value);
const preset = computed(() => (product.value ? buildPreset(product.value) : null));
const soldTodayLabel = computed(() => {
    if (!product.value || !preset.value) {
        return '';
    }

    const template = parseSoldTodayTemplate(preset.value.soldToday);

    if (template.baseCount <= 0) {
        return preset.value.soldToday;
    }

    const { year, month, day, hour } = getJakartaClockParts(soldTodayClock.value);
    const dateKey = `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
    const dailySeed = hashString(`${product.value.id}-${dateKey}`);
    const baselineRatio = 0.6 + ((dailySeed % 11) * 0.01);
    const targetRatio = 0.96 + (((dailySeed >> 3) % 10) * 0.01);
    const baseline = Math.max(120, Math.round(template.baseCount * baselineRatio));
    const target = Math.max(baseline + 160, Math.round(template.baseCount * targetRatio));
    const hourlyWeights = hourlyTrafficCurve.map((curveValue, index) => {
        const hourSeed = hashString(`${product.value?.id}-${dateKey}-${index}`);

        return curveValue * (0.9 + ((hourSeed % 16) * 0.01));
    });
    const totalWeight = hourlyWeights.reduce((sum, weight) => sum + weight, 0);
    const elapsedWeight = hourlyWeights.slice(0, Math.max(0, Math.min(24, hour))).reduce((sum, weight) => sum + weight, 0);
    const progress = totalWeight > 0 ? elapsedWeight / totalWeight : 0;
    const count = Math.round(baseline + (target - baseline) * progress);

    return `${formatNumber(count)} ${template.suffix}`;
});
const productFamilyRule = computed(() => getProductFamilyRule(product.value));
const productDisplayTitle = computed(() => productFamilyRule.value?.title ?? product.value?.name ?? 'Produk');
const productVariantShortcuts = computed<ProductVariantShortcut[]>(() => {
    if (!product.value || !productFamilyRule.value) {
        return [];
    }

    const familyProducts = sortProductFamilyProducts(
        [...catalogProductMap.value.values()].filter((candidate) => productFamilyRule.value?.match(candidate)),
        productFamilyRule.value,
    );

    return familyProducts.map((candidate) => ({
        id: candidate.id,
        name: candidate.name,
    }));
});
const uiAccent = '#2563eb';
const accentBackground = `${uiAccent}20`;
const accentBorder = `${uiAccent}45`;
const accentSoft = `${uiAccent}12`;
const activeTabId = ref('');
const activeDetailPanelId = ref<'purchase' | 'rating'>('purchase');
const activeGroupId = ref('');
const selectedPackageId = ref('');
const selectedPaymentId = ref('');
const orderQuantity = ref<number | string>(1);
const promoCode = ref('');
const promoPreview = ref<PromoPreviewState>({
    status: 'idle',
    message: '',
    code: '',
    label: null,
    discount: 0,
    subtotal: 0,
    finalTotal: 0,
});
const accountFieldValues = ref<Record<string, string>>({});
const contactFieldValues = ref<Record<string, string>>({});
const accountFieldDrafts = ref<Record<string, string>>({});
const contactFieldDrafts = ref<Record<string, string>>({});
const isCashbackDialogOpen = ref(false);
const isCheckoutDialogOpen = ref(false);
const isSubmittingCheckout = ref(false);
const shouldDockCheckoutBarToPageEnd = ref(false);
const vipaymentTabs = ref<DetailTab[]>(props.initialVipaymentTabs ?? []);
const packageFetchState = ref<'idle' | 'loading' | 'success' | 'fallback' | 'error'>(
    (props.initialVipaymentTabs ?? []).length ? (props.initialVipaymentSource === 'vipayment' ? 'success' : 'fallback') : 'idle',
);
const packageFetchMessage = ref(props.initialVipaymentMessage ?? '');
const duitkuPayments = ref<PaymentOption[]>(props.initialPaymentMethods ?? []);
const paymentFetchState = ref<'idle' | 'loading' | 'success' | 'error'>((props.initialPaymentMethods ?? []).length ? 'success' : 'idle');
const paymentFetchMessage = ref('');
const paymentAccent = '#2563eb';
const paymentAccentSoft = 'rgba(37, 99, 235, 0.08)';
const minimumBankTransferAmount = 10000;
let promoLookupTimer: ReturnType<typeof window.setTimeout> | undefined;
let promoRequestIndex = 0;
let nicknameLookupTimer: ReturnType<typeof setTimeout> | undefined;
let nicknameLookupRequestIndex = 0;
let fieldCommitTimer: ReturnType<typeof window.setTimeout> | undefined;
const nicknameLookup = ref<NicknameLookupState>({
    status: 'idle',
    message: '',
    nickname: '',
    lookupKey: '',
});

const syncStickyCheckoutBarOffset = () => {
    if (typeof window === 'undefined') {
        return;
    }

    const footer = document.getElementById('site-footer');

    if (!footer || window.innerWidth >= 768) {
        shouldDockCheckoutBarToPageEnd.value = false;
        return;
    }

    const footerTop = footer.getBoundingClientRect().top;
    const dockThreshold = 178;

    shouldDockCheckoutBarToPageEnd.value = footerTop <= window.innerHeight - dockThreshold;
};

const syncFieldValues = (fields: DetailField[], store: typeof accountFieldValues, defaults: Record<string, string> = {}) => {
    const nextValues: Record<string, string> = {};

    fields.forEach((field) => {
        nextValues[field.id] = store.value[field.id] ?? defaults[field.id] ?? '';
    });

    store.value = nextValues;
};

const syncFieldDrafts = (
    fields: DetailField[],
    drafts: typeof accountFieldDrafts,
    values: typeof accountFieldValues,
    defaults: Record<string, string> = {},
) => {
    const nextValues: Record<string, string> = {};

    fields.forEach((field) => {
        nextValues[field.id] = drafts.value[field.id] ?? values.value[field.id] ?? defaults[field.id] ?? '';
    });

    drafts.value = nextValues;
};

const isSensitiveField = (field: DetailField) =>
    field.inputType === 'password' || /password|kata sandi/i.test(field.label) || /password/i.test(field.id);

const formatSummaryValue = (field: DetailField, value: string) => {
    const trimmed = value.trim();

    if (!trimmed) {
        return 'Belum diisi';
    }

    if (isSensitiveField(field)) {
        return '••••••••';
    }

    return trimmed;
};

const paymentVisualMap: Record<string, { badge: string; color: string; tint: string }> = {
    'lyva-coins': { badge: 'LC', color: '#d97706', tint: 'rgba(245, 158, 11, 0.14)' },
    qris: { badge: 'QR', color: '#2563eb', tint: 'rgba(37, 99, 235, 0.12)' },
    dana: { badge: 'DA', color: '#2563eb', tint: 'rgba(37, 99, 235, 0.12)' },
    gopay: { badge: 'GP', color: '#2563eb', tint: 'rgba(37, 99, 235, 0.12)' },
    ovo: { badge: 'OV', color: '#2563eb', tint: 'rgba(37, 99, 235, 0.12)' },
    shopeepay: { badge: 'SP', color: '#2563eb', tint: 'rgba(37, 99, 235, 0.12)' },
    linkaja: { badge: 'LA', color: '#2563eb', tint: 'rgba(37, 99, 235, 0.12)' },
    paylater: { badge: 'PL', color: '#2563eb', tint: 'rgba(37, 99, 235, 0.12)' },
    'credit-card': { badge: 'CC', color: '#2563eb', tint: 'rgba(37, 99, 235, 0.12)' },
    'bank-transfer': { badge: 'BT', color: '#2563eb', tint: 'rgba(37, 99, 235, 0.12)' },
    retail: { badge: 'RT', color: '#2563eb', tint: 'rgba(37, 99, 235, 0.12)' },
};

const paymentPriority: Record<string, number> = {
    'lyva-coins': 0,
    qris: 1,
    dana: 2,
    gopay: 3,
    ovo: 4,
    'credit-card': 5,
    'bank-transfer': 6,
};

const buildPaymentBadge = (label: string) =>
    label
        .split(/[\s/-]+/)
        .map((segment) => segment.trim()[0] ?? '')
        .join('')
        .slice(0, 2)
        .toUpperCase() || 'PY';

const resolvePaymentKey = (payment: PaymentOption) => {
    const normalizedGroup = payment.group?.trim().toLowerCase();

    if (normalizedGroup) {
        return normalizedGroup;
    }

    const value = `${payment.id} ${payment.label}`.toLowerCase();

    if (value.includes('qris')) {
        return 'qris';
    }

    if (value.includes('lyva coins') || value.includes('coin payment') || value.includes('coins')) {
        return 'lyva-coins';
    }

    if (value.includes('paylater') || value.includes('indodana') || value.includes('kredivo')) {
        return 'paylater';
    }

    if (value.includes('dana')) {
        return 'dana';
    }

    if (value.includes('gopay') || value.includes('go pay')) {
        return 'gopay';
    }

    if (value.includes('ovo')) {
        return 'ovo';
    }

    if (value.includes('shopeepay')) {
        return 'shopeepay';
    }

    if (value.includes('linkaja')) {
        return 'linkaja';
    }

    if (value.includes('credit') || value.includes('kredit') || value.startsWith('vc ')) {
        return 'credit-card';
    }

    if (value.includes('retail') || value.includes('indomaret') || value.includes('alfamart')) {
        return 'retail';
    }

    if (
        value.includes('bank') ||
        value.includes('transfer') ||
        value.includes('virtual account') ||
        value.includes('va') ||
        value.includes('briva') ||
        value.includes('bniva') ||
        value.includes('mybva') ||
        value.includes('permatava') ||
        value.includes('cimbva') ||
        value.includes('danamonva') ||
        value.includes('atm bersama') ||
        value.includes('permata') ||
        value.includes('bca') ||
        value.includes('bni') ||
        value.includes('bri') ||
        value.includes('mandiri') ||
        value.includes('cimb') ||
        value.includes('danamon') ||
        value.includes('maybank')
    ) {
        return 'bank-transfer';
    }

    return payment.id.toLowerCase();
};

const getPaymentVisual = (payment: PaymentOption) => {
    const paymentKey = resolvePaymentKey(payment);

    return paymentVisualMap[paymentKey] ?? { badge: buildPaymentBadge(payment.label), color: '#2563eb', tint: 'rgba(37, 99, 235, 0.14)' };
};

const handleVariantShortcutWheel = (event: WheelEvent) => {
    const container = event.currentTarget as HTMLDivElement | null;

    if (!container || container.scrollWidth <= container.clientWidth) {
        return;
    }

    const delta = Math.abs(event.deltaY) > Math.abs(event.deltaX) ? event.deltaY : event.deltaX;

    if (!delta) {
        return;
    }

    container.scrollLeft += delta;
    event.preventDefault();
};

const shouldAttemptVipaymentCatalog = computed(() => Boolean(activeProductId.value));
const expectsVipaymentCatalog = computed(
    () => vipaymentTabs.value.length > 0 || (shouldAttemptVipaymentCatalog.value && ['loading', 'success', 'fallback'].includes(packageFetchState.value)),
);
const detailTabs = [
    { id: 'purchase', label: 'Pembelian' },
    { id: 'rating', label: 'Rating' },
] as const;
const availableTabs = computed(() => {
    if (vipaymentTabs.value.length) {
        return vipaymentTabs.value;
    }

    if (shouldAttemptVipaymentCatalog.value) {
        return ['fallback', 'error'].includes(packageFetchState.value) ? preset.value?.tabs ?? [] : [];
    }

    return preset.value?.tabs ?? [];
});
const bankTransferBlockedByAmount = computed(() => orderTotal.value > 0 && orderTotal.value < minimumBankTransferAmount);
const smallAmountAllowedPaymentKeys = ['lyva-coins', 'qris', 'dana', 'gopay', 'ovo', 'shopeepay', 'linkaja', 'paylater'];
const fallbackPayments = computed(() => preset.value?.payments ?? []);
const shouldUseFallbackPaymentCatalog = computed(() => vipaymentTabs.value.length === 0);
const isDuitkuPaymentLoading = computed(
    () => shouldAttemptVipaymentCatalog.value && orderTotal.value > 0 && !duitkuPayments.value.length && ['idle', 'loading'].includes(paymentFetchState.value),
);
const activePaymentCatalog = computed(() => {
    if (duitkuPayments.value.length) {
        return duitkuPayments.value;
    }

    if (shouldUseFallbackPaymentCatalog.value) {
        return fallbackPayments.value;
    }

    return [];
});
const requiredCoinsForCheckout = computed(() => {
    const coinValue = Math.max(1, Number(coinProgram.value?.coinValueRupiah ?? 1));

    return Math.max(0, Math.ceil(orderTotal.value / coinValue));
});
const coinPaymentOption = computed<PaymentOption | null>(() => {
    if (!isLoggedIn.value || orderTotal.value <= 0 || currentCoinBalance.value < requiredCoinsForCheckout.value) {
        return null;
    }

    return {
        id: 'lyva-coins',
        label: 'Lyva Coins',
        caption: `Bayar penuh dengan ${formatCoinCount(requiredCoinsForCheckout.value)}`,
        image: null,
        group: 'lyva-coins',
    };
});
const canPayWithCoins = computed(() => Boolean(coinPaymentOption.value));
const coinPaymentHint = computed(() => {
    if (!isLoggedIn.value) {
        return 'Login dulu untuk membuka metode bayar Lyva Coins.';
    }

    if (orderTotal.value <= 0) {
        return 'Pilih paket dulu untuk menghitung kebutuhan Lyva Coins.';
    }

    if (isCoinPaymentSelected.value) {
        return `Checkout ini dibayar pakai ${formatCoinCount(requiredCoinsForCheckout.value)}. Cashback coin tidak berlaku untuk pembayaran coin.`;
    }

    if (canPayWithCoins.value) {
        return `Saldo kamu cukup. Checkout ini bisa dibayar pakai ${formatCoinCount(requiredCoinsForCheckout.value)}.`;
    }

    return `Saldo belum cukup. Butuh ${formatCoinCount(requiredCoinsForCheckout.value)} untuk checkout ini.`;
});
const availablePayments = computed(() =>
    [...(coinPaymentOption.value ? [coinPaymentOption.value] : []), ...activePaymentCatalog.value].filter((payment, index, array) => {
        if (array.findIndex((entry) => entry.id === payment.id) !== index) {
            return false;
        }

        const paymentKey = resolvePaymentKey(payment);

        if (!bankTransferBlockedByAmount.value) {
            return true;
        }

        return smallAmountAllowedPaymentKeys.includes(paymentKey);
    }),
);

watch(
    preset,
    () => {
        selectedPaymentId.value = '';
    },
    { immediate: true },
);

watch(
    availableTabs,
    (tabs) => {
        if (!tabs.some((tab) => tab.id === activeTabId.value)) {
            activeTabId.value = tabs[0]?.id ?? '';
        }
    },
    { immediate: true },
);

const activeTab = computed(() => availableTabs.value.find((tab) => tab.id === activeTabId.value) ?? availableTabs.value[0] ?? null);
const isRatingTabActive = computed(() => activeDetailPanelId.value === 'rating');
const ratingSummary = computed<ProductRatingSummary>(() => props.productRatings?.summary ?? ({
    average: '0.00',
    totalReviews: 0,
    recommendationRate: 0,
    fiveStarRate: 0,
    processingSpeed: 'Belum ada data',
    familyTitle: productDisplayTitle.value,
}));
const hasProductRatings = computed(() => ratingSummary.value.totalReviews > 0);
const summaryStarCount = computed(() => {
    const score = Number.parseFloat(ratingSummary.value.average);

    return Number.isFinite(score) ? Math.max(0, Math.min(5, Math.round(score))) : 0;
});
const ratingHighlights = computed(() => [
    {
        label: 'Rekomendasi pembeli',
        value: hasProductRatings.value ? `${ratingSummary.value.recommendationRate}%` : 'Belum ada',
    },
    {
        label: 'Ulasan bintang 5',
        value: hasProductRatings.value ? `${ratingSummary.value.fiveStarRate}%` : 'Belum ada',
    },
    {
        label: 'Estimasi proses',
        value: ratingSummary.value.processingSpeed,
    },
]);
const ratingReviews = computed<RatingReview[]>(() => props.productRatings?.reviews ?? []);

watch(
    activeTab,
    (value) => {
        activeGroupId.value = value?.groups[0]?.id ?? '';
    },
    { immediate: true },
);

const activeGroup = computed(() => activeTab.value?.groups.find((group) => group.id === activeGroupId.value) ?? activeTab.value?.groups[0] ?? null);

watch(
    activeGroup,
    (value) => {
        if (!value?.options.some((item) => item.id === selectedPackageId.value)) {
            selectedPackageId.value = '';
        }
    },
    { immediate: true },
);

watch(orderQuantity, (value) => {
    const parsed = typeof value === 'string' ? Number.parseInt(value, 10) : value;
    const normalized = Number.isFinite(parsed) ? Math.max(1, Math.round(parsed)) : 1;

    if (parsed !== normalized || value !== normalized) {
        orderQuantity.value = normalized;
    }
});

const selectedPackage = computed(() => activeGroup.value?.options.find((item) => item.id === selectedPackageId.value) ?? null);
const usingVipaymentCatalog = computed(() => vipaymentTabs.value.length > 0);
const hasAvailableProducts = computed(() => availableTabs.value.some((tab) => tab.groups.some((group) => group.options.length > 0)));
const contactFields = computed(() => preset.value?.contactFields ?? []);
const hasVipaymentResolvedAccountFields = computed(
    () =>
        selectedPackage.value?.accountFields !== undefined
        || activeGroup.value?.options.some((option) => option.accountFields !== undefined)
        || activeTab.value?.groups.some((group) => group.options.some((option) => option.accountFields !== undefined))
        || false,
);
const vipaymentDefaultAccountFields = computed(
    () =>
        selectedPackage.value?.accountFields
        ?? activeGroup.value?.options.find((option) => (option.accountFields?.length ?? 0) > 0)?.accountFields
        ?? activeTab.value?.groups.flatMap((group) => group.options).find((option) => (option.accountFields?.length ?? 0) > 0)?.accountFields
        ?? [],
);
const resolvedAccountFields = computed(() => {
    const dynamicFields = vipaymentDefaultAccountFields.value.filter((field) => field.label.trim() !== '');
    const productId = product.value?.id ?? '';
    const isFreeFireFamily = productId.includes('free-fire');

    if (hasVipaymentResolvedAccountFields.value) {
        return isFreeFireFamily ? dynamicFields.filter((field) => field.id !== 'account-zone') : dynamicFields;
    }

    const fallbackFields = preset.value?.accountFields ?? [];

    return isFreeFireFamily ? fallbackFields.filter((field) => field.id !== 'account-zone') : fallbackFields;
});
const nicknameLookupTargetField = computed(() => resolvedAccountFields.value.find((field) => field.id === 'account-user-id') ?? null);
const nicknameLookupZoneField = computed(() => resolvedAccountFields.value.find((field) => field.id === 'account-zone') ?? null);
const nicknameLookupTargetValue = computed(() => (nicknameLookupTargetField.value ? (accountFieldValues.value[nicknameLookupTargetField.value.id] ?? '').trim() : ''));
const nicknameLookupZoneValue = computed(() => (nicknameLookupZoneField.value ? (accountFieldValues.value[nicknameLookupZoneField.value.id] ?? '').trim() : ''));
const resolveNicknameLookupCode = (productId: string) => {
    const value = productId.trim().toLowerCase();
    const isMobileLegendsRegionVariant = ['global', 'region', 'brazil', 'malaysia', 'philippines', 'russia', 'singapore'].some((keyword) =>
        value.includes(keyword),
    );

    if (value.includes('mobile-legends') && !['gift', 'login'].some((keyword) => value.includes(keyword))) {
        return isMobileLegendsRegionVariant ? 'mobile-legends-region' : 'mobile-legends';
    }

    if (value.includes('free-fire') && !value.includes('login')) {
        return 'free-fire';
    }

    if (value.includes('pubg') || value.includes('pubgm')) {
        return 'pubgm';
    }

    if (value.includes('valorant') && !value.includes('voucher')) {
        return 'valorant';
    }

    if (value.includes('genshin-impact')) {
        return 'genshin-impact';
    }

    if (value.includes('honkai-star-rail')) {
        return 'honkai-star-rail';
    }

    if (['pointblank', 'point-blank', 'pb-zepetto'].some((keyword) => value.includes(keyword))) {
        return 'pointblank';
    }

    return null;
};
const nicknameLookupCode = computed(() => resolveNicknameLookupCode(product.value?.id ?? ''));
const nicknameLookupRequiresZone = computed(() => ['mobile-legends', 'mobile-legends-region', 'genshin-impact', 'honkai-star-rail'].includes(nicknameLookupCode.value ?? ''));
const nicknameLookupEnabled = computed(
    () =>
        usingVipaymentCatalog.value
        && Boolean(nicknameLookupCode.value)
        && Boolean(selectedPackage.value)
        && Boolean(nicknameLookupTargetField.value)
        && (!nicknameLookupRequiresZone.value || Boolean(nicknameLookupZoneField.value)),
);
const nicknameLookupFeedback = computed(() => {
    if (!nicknameLookupEnabled.value) {
        return null;
    }

    if (nicknameLookup.value.status === 'checking') {
        return {
            tone: 'info',
            message: 'Sedang mengecek username...',
        };
    }

    if (nicknameLookup.value.status === 'success') {
        return {
            tone: 'success',
            message: `Username ditemukan: ${nicknameLookup.value.nickname}`,
        };
    }

    if (['not_found', 'error', 'unavailable'].includes(nicknameLookup.value.status)) {
        return {
            tone: 'error',
            message: nicknameLookup.value.message,
        };
    }

    return null;
});
const nicknameLookupRequired = computed(() => nicknameLookupEnabled.value && Boolean(nicknameLookupTargetValue.value));
const nicknameLookupPassed = computed(() => !nicknameLookupRequired.value || nicknameLookup.value.status === 'success');
const canTriggerNicknameLookup = computed(() => {
    if (!nicknameLookupEnabled.value || !product.value?.id || !nicknameLookupTargetValue.value) {
        return false;
    }

    if (nicknameLookupZoneField.value && !nicknameLookupZoneValue.value) {
        return false;
    }

    return nicknameLookup.value.status !== 'checking';
});
const selectedPackageDetails = computed(() => {
    const details = selectedPackage.value?.details?.filter((detail) => detail.trim() !== '') ?? [];

    if (details.length) {
        return details;
    }

    const note = selectedPackage.value?.note?.trim();

    return note ? [note] : [];
});
const selectedPayment = computed(() => availablePayments.value.find((item) => item.id === selectedPaymentId.value) ?? null);
const hasAvailablePayments = computed(() => availablePayments.value.length > 0);
const sortedPayments = computed(() =>
    [...availablePayments.value].sort((left, right) => (paymentPriority[resolvePaymentKey(left)] ?? 99) - (paymentPriority[resolvePaymentKey(right)] ?? 99)),
);
const recommendedPayments = computed(() => {
    const preferred = sortedPayments.value.filter((payment) => ['lyva-coins', 'qris', 'dana', 'credit-card'].includes(resolvePaymentKey(payment)));

    return preferred.length >= 2 ? preferred.slice(0, 2) : sortedPayments.value.slice(0, 2);
});
const otherPayments = computed(() =>
    sortedPayments.value.filter((payment) => !recommendedPayments.value.some((recommended) => recommended.id === payment.id)),
);
const secondaryPaymentsTitle = computed(() =>
    otherPayments.value.some((payment) => ['credit-card', 'bank-transfer'].includes(resolvePaymentKey(payment)))
        ? 'Metode Bayar Lainnya'
        : 'E-Wallet dan QRIS',
);
const normalizedQuantity = computed(() => {
    const parsed = typeof orderQuantity.value === 'string' ? Number.parseInt(orderQuantity.value, 10) : orderQuantity.value;

    return Number.isFinite(parsed) ? Math.max(1, Math.round(parsed)) : 1;
});
const packageSubtotal = computed(() => (selectedPackage.value?.price ?? 0) * normalizedQuantity.value);
const appliedPromoDiscount = computed(() => (promoPreview.value.status === 'applied' ? promoPreview.value.discount : 0));
const orderTotal = computed(() => Math.max(0, packageSubtotal.value - appliedPromoDiscount.value));
const isCoinPaymentSelected = computed(() => selectedPaymentType.value === 'lyva-coins');
const cashbackCoins = computed(() => (isCoinPaymentSelected.value ? 0 : lyvaCoinsForAmount(orderTotal.value, coinProgram.value)));
const cashbackCoinsLabel = computed(() => formatCoinCount(cashbackCoins.value));
const cashbackCoinsForAmount = (amount: number) => lyvaCoinsForAmount(amount, coinProgram.value);
const hasAppliedPromo = computed(() => appliedPromoDiscount.value > 0);
const promoStatusClass = computed(() => {
    if (promoPreview.value.status === 'applied') {
        return 'border-emerald-200 bg-emerald-50 text-emerald-700';
    }

    if (promoPreview.value.status === 'invalid') {
        return 'border-red-200 bg-red-50 text-red-600';
    }

    return 'border-blue-200 bg-blue-50 text-blue-600';
});
const hasFilledFields = (fields: DetailField[], values: Record<string, string>) =>
    fields.every((field) => field.required === false || (values[field.id] ?? '').trim().length > 0);
const accountFieldsComplete = computed(() => hasFilledFields(resolvedAccountFields.value, accountFieldValues.value));
const contactFieldsComplete = computed(() => hasFilledFields(contactFields.value, contactFieldValues.value));
const isProfileBackedContactField = (fieldId: string) => Boolean(profileContactOverrides.value[fieldId]?.trim());
const checkoutReady = computed(
    () => Boolean(selectedPackage.value && selectedPayment.value && accountFieldsComplete.value && contactFieldsComplete.value && nicknameLookupPassed.value),
);
const checkoutBlockingMessage = computed(() => {
    if (!selectedPackage.value) {
        return 'Pilih paket terlebih dulu.';
    }

    if (!selectedPayment.value) {
        return 'Pilih metode pembayaran terlebih dulu.';
    }

    if (selectedPaymentType.value === 'lyva-coins' && currentCoinBalance.value < requiredCoinsForCheckout.value) {
        return 'Saldo Lyva Coins kamu belum cukup untuk checkout ini.';
    }

    if (!accountFieldsComplete.value) {
        return 'Lengkapi detail akun terlebih dulu.';
    }

    if (!contactFieldsComplete.value) {
        return 'Lengkapi info kontak terlebih dulu.';
    }

    if (nicknameLookupRequired.value && nicknameLookup.value.status === 'checking') {
        return 'Sedang mengecek username akun...';
    }

    if (nicknameLookupRequired.value && !nicknameLookupPassed.value) {
        return nicknameLookup.value.message || 'User ID / username akun belum valid.';
    }

    return '';
});
const checkoutNotice = computed(
    () => selectedPackageDetails.value[0] ?? preset.value?.notes[0] ?? 'Pastikan detail akun, nominal, dan metode pembayaran sudah benar sebelum lanjut checkout.',
);
const paymentMethodSummaryLabel = computed(() =>
    selectedPaymentType.value === 'lyva-coins' ? `${selectedPayment.value?.label ?? 'Lyva Coins'} • ${formatCoinCount(requiredCoinsForCheckout.value)}` : (selectedPayment.value?.label ?? ''),
);
const productMetaTitle = computed(() => `${productDisplayTitle.value} | Lyva Indonesia`);
const productMetaDescription = computed(() => {
    const category = product.value?.categoryTitle ?? 'produk digital';

    return `Top up ${productDisplayTitle.value} di Lyva Indonesia dengan proses cepat, aman, dan praktis. Pilih nominal ${category} favoritmu lalu checkout dengan metode pembayaran populer.`;
});
const productCanonicalUrl = computed(() => `${siteUrl}/produk/${props.productId}`);
const schemaLowestOfferPrice = computed(() => {
    const prices = availableTabs.value
        .flatMap((tab) => tab.groups)
        .flatMap((group) => group.options)
        .map((option) => option.price)
        .filter((price) => Number.isFinite(price) && price > 0);

    if (prices.length > 0) {
        return Math.min(...prices);
    }

    return selectedPackage.value?.price ?? 0;
});
const productStructuredData = computed(() =>
    JSON.stringify({
        '@context': 'https://schema.org',
        '@type': 'Product',
        name: productDisplayTitle.value,
        description: productMetaDescription.value,
        category: product.value?.categoryTitle ?? 'Top Up Digital',
        brand: {
            '@type': 'Brand',
            name: 'Lyva Indonesia',
        },
        image: product.value?.coverImage ? `${siteUrl}${product.value.coverImage}` : `${siteUrl}/brand/lyva-mascot-hd.png`,
        url: productCanonicalUrl.value,
        offers: {
            '@type': 'Offer',
            priceCurrency: 'IDR',
            price: String(Math.max(0, schemaLowestOfferPrice.value)),
            availability: 'https://schema.org/InStock',
            url: productCanonicalUrl.value,
            seller: {
                '@type': 'Organization',
                name: 'Lyva Indonesia',
            },
            hasMerchantReturnPolicy: {
                '@type': 'MerchantReturnPolicy',
                applicableCountry: 'ID',
                returnPolicyCategory: 'https://schema.org/MerchantReturnNotPermitted',
            },
            shippingDetails: {
                '@type': 'OfferShippingDetails',
                shippingRate: {
                    '@type': 'MonetaryAmount',
                    value: '0',
                    currency: 'IDR',
                },
                shippingDestination: {
                    '@type': 'DefinedRegion',
                    addressCountry: 'ID',
                },
                deliveryTime: {
                    '@type': 'ShippingDeliveryTime',
                    handlingTime: {
                        '@type': 'QuantitativeValue',
                        minValue: 0,
                        maxValue: 1,
                        unitCode: 'DAY',
                    },
                    transitTime: {
                        '@type': 'QuantitativeValue',
                        minValue: 0,
                        maxValue: 1,
                        unitCode: 'DAY',
                    },
                },
            },
        },
        ...(hasProductRatings.value
            ? {
                  aggregateRating: {
                      '@type': 'AggregateRating',
                      ratingValue: ratingSummary.value.average,
                      reviewCount: String(ratingSummary.value.totalReviews),
                      bestRating: '5',
                      worstRating: '1',
                  },
              }
            : {}),
    }),
);
const detailSummaryRows = computed(() => {
    const accountRows = resolvedAccountFields.value.map((field) => ({
        label: field.label,
        value: formatSummaryValue(field, accountFieldValues.value[field.id] ?? ''),
    }));
    const nicknameRows =
        nicknameLookup.value.status === 'success' && nicknameLookup.value.nickname
            ? [
                  {
                      label: 'Username',
                      value: nicknameLookup.value.nickname,
                  },
              ]
            : [];
    const contactRows = contactFields.value.map((field) => ({
        label: field.label,
        value: formatSummaryValue(field, contactFieldValues.value[field.id] ?? ''),
    }));
    const paymentRows = selectedPayment.value
        ? [
              {
                  label: 'Pembayaran',
                  value: selectedPayment.value.label,
              },
          ]
        : [];

    return [...accountRows, ...nicknameRows, ...contactRows, ...paymentRows];
});
const resetPromoPreview = () => {
    promoPreview.value = {
        status: 'idle',
        message: '',
        code: '',
        label: null,
        discount: 0,
        subtotal: packageSubtotal.value,
        finalTotal: packageSubtotal.value,
    };
};

const resetNicknameLookup = () => {
    nicknameLookup.value = {
        status: 'idle',
        message: '',
        nickname: '',
        lookupKey: '',
    };
};

const queueFieldCommit = () => {
    if (typeof window === 'undefined') {
        accountFieldValues.value = { ...accountFieldDrafts.value };
        contactFieldValues.value = { ...contactFieldDrafts.value };

        return;
    }

    if (fieldCommitTimer !== undefined) {
        window.clearTimeout(fieldCommitTimer);
    }

    fieldCommitTimer = window.setTimeout(() => {
        accountFieldValues.value = { ...accountFieldDrafts.value };
        contactFieldValues.value = { ...contactFieldDrafts.value };
    }, 120);
};

const commitFieldDraftsImmediately = () => {
    if (typeof window !== 'undefined' && fieldCommitTimer !== undefined) {
        window.clearTimeout(fieldCommitTimer);
    }

    fieldCommitTimer = undefined;
    accountFieldValues.value = { ...accountFieldDrafts.value };
    contactFieldValues.value = { ...contactFieldDrafts.value };
};

const parseJsonResponse = async <T>(response: Response, fallbackMessage: string): Promise<T> => {
    const raw = await response.text();
    const contentType = response.headers.get('content-type') ?? '';

    if (!contentType.includes('application/json')) {
        throw new Error(fallbackMessage);
    }

    try {
        return JSON.parse(raw) as T;
    } catch {
        throw new Error(fallbackMessage);
    }
};

const fetchNicknameLookup = async (productId: string, target: string, zone: string) => {
    const currentRequest = ++nicknameLookupRequestIndex;
    const lookupKey = `${productId}|${target}|${zone}`;

    nicknameLookup.value = {
        status: 'checking',
        message: 'Sedang mengecek username...',
        nickname: '',
        lookupKey,
    };

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
        const response = await fetch(route('vipayment.products.nickname', { product: productId }), {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({
                target,
                zone: zone || null,
            }),
        });

        const payload = await parseJsonResponse<{
            status?: NicknameLookupState['status'];
            message?: string;
            nickname?: string;
        }>(response, 'Server validasi username sedang bermasalah. Coba lagi sebentar.');

        if (currentRequest !== nicknameLookupRequestIndex) {
            return;
        }

        const nextStatus = payload.status ?? (response.ok ? 'success' : 'error');
        const nextNickname = typeof payload.nickname === 'string' ? payload.nickname.trim() : '';
        const nextMessage =
            typeof payload.message === 'string' && payload.message.trim() !== ''
                ? payload.message
                : nextStatus === 'not_found'
                  ? 'Username tidak ditemukan.'
                  : 'Gagal mengecek username.';

        nicknameLookup.value = {
            status: nextStatus,
            message: nextMessage,
            nickname: nextNickname,
            lookupKey,
        };

    } catch (error) {
        if (currentRequest !== nicknameLookupRequestIndex) {
            return;
        }

        nicknameLookup.value = {
            status: 'error',
            message: error instanceof Error ? error.message : 'Gagal mengecek username.',
            nickname: '',
            lookupKey,
        };
    }
};

const triggerNicknameLookup = () => {
    const productId = product.value?.id ?? '';
    const target = nicknameLookupTargetValue.value;
    const zone = nicknameLookupZoneValue.value;

    if (!productId || !target) {
        resetNicknameLookup();
        return;
    }

    if (nicknameLookupZoneField.value && !zone) {
        resetNicknameLookup();
        return;
    }

    if (nicknameLookup.value.lookupKey === `${productId}|${target}|${zone}` && nicknameLookup.value.status === 'success') {
        return;
    }

    void fetchNicknameLookup(productId, target, zone);
};

const fetchPromoPreview = async (productId: string, total: number, rawCode: string) => {
    const currentRequest = ++promoRequestIndex;

    promoPreview.value = {
        ...promoPreview.value,
        status: 'checking',
        message: 'Mengecek kode promo...',
        code: rawCode.trim().toUpperCase(),
        subtotal: total,
        finalTotal: total,
    };

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
        const response = await fetch(route('checkout.promo.resolve'), {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({
                productId,
                total,
                promoCode: rawCode,
            }),
        });

        const payload = (await response.json()) as Partial<PromoPreviewState> & { applied?: boolean };

        if (currentRequest !== promoRequestIndex) {
            return;
        }

        promoPreview.value = {
            status: payload.status === 'applied' ? 'applied' : 'invalid',
            message: String(payload.message ?? 'Kode promo tidak bisa dipakai untuk checkout ini.'),
            code: String(payload.code ?? rawCode.trim().toUpperCase()),
            label: typeof payload.label === 'string' ? payload.label : null,
            discount: Number(payload.discount ?? 0),
            subtotal: Number(payload.subtotal ?? total),
            finalTotal: Number(payload.finalTotal ?? total),
        };

        if (!response.ok && promoPreview.value.status !== 'invalid') {
            promoPreview.value.status = 'invalid';
        }
    } catch (error) {
        if (currentRequest !== promoRequestIndex) {
            return;
        }

        promoPreview.value = {
            status: 'invalid',
            message: error instanceof Error ? error.message : 'Gagal mengecek kode promo.',
            code: rawCode.trim().toUpperCase(),
            label: null,
            discount: 0,
            subtotal: total,
            finalTotal: total,
        };
    }
};
const selectedPaymentType = computed(() => (selectedPayment.value ? resolvePaymentKey(selectedPayment.value) : ''));

let duitkuRequestIndex = 0;
let duitkuLookupTimer: ReturnType<typeof setTimeout> | undefined;
const duitkuPaymentCache = new Map<number, PaymentOption[]>();
const vipaymentServiceCache = new Map<string, CachedVipaymentState>();

if ((props.initialPaymentMethodsAmount ?? 0) > 0 && (props.initialPaymentMethods?.length ?? 0) > 0) {
    duitkuPaymentCache.set(props.initialPaymentMethodsAmount as number, props.initialPaymentMethods ?? []);
}

if ((props.initialVipaymentTabs ?? []).length > 0 || (props.initialVipaymentMessage ?? '') !== '') {
    vipaymentServiceCache.set(props.productId, {
        tabs: props.initialVipaymentTabs ?? [],
        source: props.initialVipaymentSource === 'vipayment' && (props.initialVipaymentTabs ?? []).length ? 'vipayment' : 'fallback',
        message: props.initialVipaymentMessage ?? '',
    });
}

watch(
    resolvedAccountFields,
    (fields) => {
        syncFieldValues(fields, accountFieldValues);
        syncFieldDrafts(fields, accountFieldDrafts, accountFieldValues);
    },
    { immediate: true },
);

watch(
    [contactFields, currentUser],
    ([fields, user]) => {
        syncFieldValues(fields, contactFieldValues, {
            'buyer-email': user?.email ?? '',
            'buyer-whatsapp': user?.whatsapp_number ?? '',
        });
        syncFieldDrafts(fields, contactFieldDrafts, contactFieldValues, {
            'buyer-email': user?.email ?? '',
            'buyer-whatsapp': user?.whatsapp_number ?? '',
        });
    },
    { immediate: true },
);

watch(
    [() => product.value?.id ?? '', nicknameLookupEnabled, nicknameLookupTargetValue, nicknameLookupZoneValue, () => selectedPackage.value?.id ?? ''],
    ([productId, enabled, targetValue, zoneValue]) => {
        if (!enabled || !productId || targetValue === '') {
            resetNicknameLookup();
            return;
        }

        if (nicknameLookupZoneField.value && zoneValue === '') {
            resetNicknameLookup();
            return;
        }

        const nextLookupKey = `${productId}|${targetValue}|${zoneValue}`;

        if (nicknameLookup.value.lookupKey !== nextLookupKey) {
            resetNicknameLookup();
        }
    },
    { immediate: true },
);

const syncSelectedPayment = (payments: PaymentOption[]) => {
    if (!payments.length) {
        selectedPaymentId.value = '';

        return;
    }

    if (!payments.some((payment) => payment.id === selectedPaymentId.value)) {
        selectedPaymentId.value = '';
    }
};

const warmCheckoutPageAssets = () => {
    if (typeof window === 'undefined') {
        return null;
    }

    if (checkoutPageWarmupPromise === null) {
        checkoutPageWarmupPromise = import('./PaymentCheckout.vue').catch(() => null);
    }

    return checkoutPageWarmupPromise;
};

const extractInitialPaymentAmount = (tabs: DetailTab[]) =>
    tabs
        .flatMap((tab) => tab.groups ?? [])
        .flatMap((group) => group.options ?? [])
        .map((option) => Number(option.price ?? 0))
        .find((price) => Number.isFinite(price) && price > 0) ?? null;

const hydrateCachedDuitkuPayments = (amount: number | null) => {
    if (!amount || !duitkuPaymentCache.has(amount)) {
        return false;
    }

    duitkuPayments.value = duitkuPaymentCache.get(amount) ?? [];
    paymentFetchState.value = 'success';
    paymentFetchMessage.value = '';

    return true;
};

const applyCachedVipaymentState = (entry: CachedVipaymentState) => {
    vipaymentTabs.value = entry.tabs;
    packageFetchState.value = entry.source === 'vipayment' && entry.tabs.length ? 'success' : 'fallback';
    packageFetchMessage.value = entry.message;

        if (!hydrateCachedDuitkuPayments(extractInitialPaymentAmount(entry.tabs)) && shouldAttemptVipaymentCatalog.value) {
            duitkuPayments.value = [];
            paymentFetchState.value = 'loading';
            paymentFetchMessage.value = '';
        }
};

const prefetchVariantPaymentMethods = (tabs: DetailTab[]) => {
    const firstAmount = extractInitialPaymentAmount(tabs);

    if (!firstAmount || duitkuPaymentCache.has(firstAmount) || typeof window === 'undefined') {
        return;
    }

    void fetch(route('duitku.payment-methods', { amount: firstAmount }), {
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
    })
        .then(async (response) => {
            const payload = await parseJsonResponse<{ data?: PaymentOption[] }>(
                response,
                'Metode pembayaran sedang bermasalah. Coba lagi sebentar.',
            );

            if (!response.ok || !Array.isArray(payload.data)) {
                return;
            }

            duitkuPaymentCache.set(firstAmount, payload.data);
        })
        .catch(() => null);
};

const openCheckoutDialog = () => {
    if (!checkoutReady.value) {
        return;
    }

    void warmCheckoutPageAssets();
    isCheckoutDialogOpen.value = true;
};

const handleCheckoutClick = () => {
    if (!checkoutReady.value) {
        return;
    }

    if (isLoggedIn.value) {
        openCheckoutDialog();
        return;
    }

    isCashbackDialogOpen.value = true;
};

const continueWithoutCashback = () => {
    isCashbackDialogOpen.value = false;
    openCheckoutDialog();
};

const goToLoginForCashback = () => {
    isCashbackDialogOpen.value = false;

    const loginUrl =
        typeof window === 'undefined'
            ? route('login')
            : `${route('login')}?redirect=${encodeURIComponent(window.location.pathname + window.location.search)}`;

    router.visit(loginUrl);
};

const confirmCheckout = () => {
    if (isSubmittingCheckout.value || !checkoutReady.value || !product.value || !selectedPackage.value || !selectedPayment.value) {
        return;
    }

    void warmCheckoutPageAssets();
    isSubmittingCheckout.value = true;

    router.post(
        route('checkout.preview.store'),
        {
            productId: product.value.id,
            productName: product.value.name,
            productImage: product.value.coverImage,
            packageCode: selectedPackage.value.code ?? null,
            packageLabel: selectedPackage.value.label,
            quantity: normalizedQuantity.value,
            paymentMethodCode: selectedPayment.value.id,
            paymentLabel: paymentMethodSummaryLabel.value,
            paymentImage: selectedPayment.value.image ?? null,
            paymentBadge: getPaymentVisual(selectedPayment.value).badge,
            paymentCaption: selectedPayment.value.caption,
            paymentType: selectedPaymentType.value,
            total: orderTotal.value,
            promoCode: promoCode.value,
            checkoutIntentToken: checkoutIntentToken.value,
            website: '',
            formStartedAt: checkoutFormStartedAt.value,
            checkoutNotice: checkoutNotice.value,
            guaranteeText: preset.value?.guaranteeText ?? '',
            notes: preset.value?.notes ?? [],
            summaryRows: detailSummaryRows.value,
            accountFields: resolvedAccountFields.value.map((field) => ({
                id: field.id,
                label: field.label,
                value: accountFieldValues.value[field.id] ?? '',
            })),
            contactFields: contactFields.value.map((field) => ({
                id: field.id,
                label: field.label,
                value: contactFieldValues.value[field.id] ?? '',
            })),
        },
        {
            onSuccess: () => {
                isCheckoutDialogOpen.value = false;
            },
            onError: (errors) => {
                if (typeof errors.promoCode === 'string') {
                    promoPreview.value = {
                        status: 'invalid',
                        message: errors.promoCode,
                        code: promoCode.value.trim().toUpperCase(),
                        label: null,
                        discount: 0,
                        subtotal: packageSubtotal.value,
                        finalTotal: packageSubtotal.value,
                    };
                    isCheckoutDialogOpen.value = true;
                }
            },
            onFinish: () => {
                isSubmittingCheckout.value = false;
            },
        },
    );
};

watch(
    checkoutReady,
    (ready) => {
        if (ready) {
            void warmCheckoutPageAssets();
        }
    },
    { immediate: true },
);

const fetchVipaymentServices = async (productId: string, background = false) => {
    if (!background && productId === activeProductId.value) {
        vipaymentTabs.value = [];
        packageFetchState.value = 'loading';
        packageFetchMessage.value = '';
    }

    try {
        const response = await fetch(route('vipayment.products.services', { product: productId }), {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        const payload = await parseJsonResponse<{
            message?: string;
            data?: DetailTab[];
            source?: 'vipayment' | 'fallback';
        }>(response, 'Data produk dari VIPayment sedang bermasalah. Coba lagi sebentar.');

        if (!response.ok) {
            throw new Error(payload.message ?? 'Gagal mengambil daftar produk dari VIPayment.');
        }

        const entry: CachedVipaymentState = {
            tabs: payload.data ?? [],
            source: payload.source === 'vipayment' && (payload.data ?? []).length ? 'vipayment' : 'fallback',
            message: payload.message ?? '',
        };

        vipaymentServiceCache.set(productId, entry);
        prefetchVariantPaymentMethods(entry.tabs);

        if (productId !== activeProductId.value) {
            return;
        }

        applyCachedVipaymentState(entry);
    } catch (error) {
        if (productId !== activeProductId.value) {
            return;
        }

        vipaymentTabs.value = [];
        packageFetchState.value = 'error';
        packageFetchMessage.value = error instanceof Error ? error.message : 'Gagal mengambil daftar produk dari VIPayment.';
    }
};

watch(
    activeProductId,
    (productId) => {
        if (!productId || typeof window === 'undefined') {
            return;
        }

        isCashbackDialogOpen.value = false;
        isCheckoutDialogOpen.value = false;

        const cachedEntry = vipaymentServiceCache.get(productId);

        if (cachedEntry) {
            applyCachedVipaymentState(cachedEntry);
            return;
        }

        duitkuPayments.value = [];
        paymentFetchState.value = 'loading';
        paymentFetchMessage.value = '';
        vipaymentTabs.value = [];
        packageFetchState.value = 'loading';
        packageFetchMessage.value = '';
        void fetchVipaymentServices(productId);
    },
    { immediate: true },
);

const switchProductVariant = (productId: string) => {
    if (productId === activeProductId.value) {
        return;
    }

    activeProductId.value = productId;
    activeHydratedProduct.value = productId === props.productId ? hydrateVipCatalogProduct(props.vipCatalogProduct) : null;

    if (typeof window !== 'undefined') {
        window.history.pushState({ productId }, '', route('products.show', { product: productId }));
    }
};

const prefetchProductVariant = (productId: string) => {
    if (productId === activeProductId.value || vipaymentServiceCache.has(productId)) {
        return;
    }

    void fetchVipaymentServices(productId, true);
};

const syncVariantFromLocation = () => {
    if (typeof window === 'undefined') {
        return;
    }

    const segments = window.location.pathname.split('/').filter(Boolean);
    const locationProductId = segments[0] === 'produk' ? decodeURIComponent(segments[1] ?? '') : '';

    if (!locationProductId || locationProductId === activeProductId.value) {
        return;
    }

    activeProductId.value = locationProductId;
    activeHydratedProduct.value = locationProductId === props.productId ? hydrateVipCatalogProduct(props.vipCatalogProduct) : null;
};

watch(
    productVariantShortcuts,
    (variants) => {
        variants.forEach((variant) => {
            prefetchProductVariant(variant.id);
        });
    },
    { immediate: true },
);

watch(
    [() => props.productId, () => props.vipCatalogProduct],
    ([productId, vipCatalogProduct]) => {
        activeProductId.value = productId;
        activeHydratedProduct.value = productId === props.productId ? hydrateVipCatalogProduct(vipCatalogProduct) : null;
    },
);

onMounted(() => {
    if (typeof window === 'undefined') {
        return;
    }

    checkoutFormStartedAt.value = Date.now();
    scheduleSoldTodayClock();
    syncStickyCheckoutBarOffset();
    window.addEventListener('popstate', syncVariantFromLocation);
    window.addEventListener('scroll', syncStickyCheckoutBarOffset, { passive: true });
    window.addEventListener('resize', syncStickyCheckoutBarOffset, { passive: true });
});

onBeforeUnmount(() => {
    if (typeof window === 'undefined') {
        return;
    }

    clearSoldTodayClockTimer();
    if (promoLookupTimer) {
        window.clearTimeout(promoLookupTimer);
    }
    if (fieldCommitTimer) {
        window.clearTimeout(fieldCommitTimer);
    }
    window.removeEventListener('popstate', syncVariantFromLocation);
    window.removeEventListener('scroll', syncStickyCheckoutBarOffset);
    window.removeEventListener('resize', syncStickyCheckoutBarOffset);
});

const fetchDuitkuPaymentMethods = async (amount: number) => {
    const currentRequest = ++duitkuRequestIndex;

    if (duitkuPaymentCache.has(amount)) {
        duitkuPayments.value = duitkuPaymentCache.get(amount) ?? [];
        paymentFetchState.value = 'success';
        paymentFetchMessage.value = '';
        syncSelectedPayment(availablePayments.value);

        return;
    }

    paymentFetchState.value = duitkuPayments.value.length ? 'success' : 'loading';
    paymentFetchMessage.value = '';

    try {
        const response = await fetch(route('duitku.payment-methods', { amount }), {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        const payload = await parseJsonResponse<{
            message?: string;
            data?: PaymentOption[];
        }>(response, 'Metode pembayaran sedang bermasalah. Coba lagi sebentar.');

        if (currentRequest !== duitkuRequestIndex) {
            return;
        }

        if (!response.ok) {
            throw new Error(payload.message ?? 'Gagal mengambil metode pembayaran dari Duitku.');
        }

        duitkuPayments.value = payload.data ?? [];
        duitkuPaymentCache.set(amount, duitkuPayments.value);
        paymentFetchState.value = 'success';
        paymentFetchMessage.value = payload.message ?? '';
        syncSelectedPayment(availablePayments.value);
    } catch (error) {
        if (currentRequest !== duitkuRequestIndex) {
            return;
        }

        duitkuPayments.value = [];
        paymentFetchState.value = 'error';
        paymentFetchMessage.value = error instanceof Error ? error.message : 'Gagal mengambil metode pembayaran dari Duitku.';
        syncSelectedPayment(availablePayments.value);
    }
};

watch(
    [promoCode, packageSubtotal, () => product.value?.id ?? ''],
    ([code, total, productId]) => {
        if (typeof window === 'undefined') {
            return;
        }

        if (promoLookupTimer) {
            window.clearTimeout(promoLookupTimer);
        }

        const trimmedCode = code.trim();

        if (!trimmedCode || !productId || total <= 0) {
            resetPromoPreview();
            return;
        }

        promoLookupTimer = window.setTimeout(() => {
            void fetchPromoPreview(productId, total, trimmedCode);
        }, 260);
    },
    { immediate: true },
);

watch(
    orderTotal,
    (amount) => {
        if (typeof window === 'undefined' || !amount) {
            duitkuPayments.value = [];
            paymentFetchState.value = 'idle';
            paymentFetchMessage.value = '';
            return;
        }

        if (duitkuLookupTimer) {
            window.clearTimeout(duitkuLookupTimer);
        }

        const hydratedAmount = props.initialPaymentMethodsAmount ?? null;
        const hydratedPayments = props.initialPaymentMethods ?? [];

        if (duitkuPaymentCache.has(amount)) {
            duitkuPayments.value = duitkuPaymentCache.get(amount) ?? [];
            paymentFetchState.value = 'success';
            paymentFetchMessage.value = '';
            syncSelectedPayment(availablePayments.value);

            return;
        }

        if (hydratedAmount === amount && hydratedPayments.length) {
            duitkuPayments.value = hydratedPayments;
            duitkuPaymentCache.set(amount, hydratedPayments);
            paymentFetchState.value = 'success';
            paymentFetchMessage.value = '';
            syncSelectedPayment(availablePayments.value);

            return;
        }

        duitkuPayments.value = [];
        paymentFetchState.value = 'loading';
        paymentFetchMessage.value = '';
        syncSelectedPayment(availablePayments.value);

        duitkuLookupTimer = window.setTimeout(() => {
            void fetchDuitkuPaymentMethods(amount);
        }, 120);
    },
    { immediate: true },
);

watch(
    availablePayments,
    (payments) => {
        syncSelectedPayment(payments);
    },
    { immediate: true },
);

</script>

<template>
    <Head :title="productMetaTitle">
        <meta name="description" :content="productMetaDescription" />
        <meta name="keywords" :content="`${productDisplayTitle}, lyva, lyva indonesia, top up ${productDisplayTitle.toLowerCase()}`" />
        <link rel="canonical" :href="productCanonicalUrl" />
        <meta property="og:title" :content="productMetaTitle" />
        <meta property="og:description" :content="productMetaDescription" />
        <meta property="og:url" :content="productCanonicalUrl" />
        <meta property="og:image" :content="product?.coverImage ? `${siteUrl}${product.coverImage}` : `${siteUrl}/brand/lyva-mascot-hd.png`" />
        <meta name="twitter:title" :content="productMetaTitle" />
        <meta name="twitter:description" :content="productMetaDescription" />
        <meta name="twitter:image" :content="product?.coverImage ? `${siteUrl}${product.coverImage}` : `${siteUrl}/brand/lyva-mascot-hd.png`" />
        <component :is="'script'" :nonce="cspNonce" type="application/ld+json">
            {{ productStructuredData }}
        </component>
    </Head>

    <PublicLayout active-nav="topup">
        <main class="relative overflow-hidden px-4 pb-44 pt-8 sm:px-6 sm:pb-48 lg:px-8 lg:pb-52 lg:pt-10">
            <div
                class="pointer-events-none absolute inset-x-0 top-0 h-80"
                style="background-image: radial-gradient(circle at top, rgba(67, 56, 202, 0.2), transparent 58%)"
            />
            <div class="pointer-events-none absolute left-[-8%] top-[8%] h-72 w-72 rounded-full bg-indigo-200/35 blur-3xl" />
            <div class="pointer-events-none absolute right-[-6%] top-[14%] h-80 w-80 rounded-full bg-sky-200/28 blur-3xl" />

            <div class="mx-auto max-w-[75rem]">
                <div class="mb-6 flex flex-wrap items-center gap-2 text-sm font-medium text-slate-500">
                    <Link :href="route('home')" class="text-indigo-700 transition hover:text-indigo-600">Home</Link>
                    <ChevronRight class="size-4 text-slate-400" />
                    <span class="text-slate-900">{{ product ? productDisplayTitle : 'Produk tidak ditemukan' }}</span>
                </div>

                <template v-if="product && preset">
                    <section class="grid gap-5 lg:grid-cols-[0.35fr,0.65fr] lg:items-start">
                        <aside class="self-start rounded-[32px] border border-white/85 bg-white/92 p-5 shadow-[0_24px_70px_rgba(15,23,42,0.08)] backdrop-blur-xl">
                            <div class="flex items-start gap-4">
                                <div
                                    class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden text-white"
                                    :class="
                                        product.iconImage
                                            ? 'rounded-[18px] bg-transparent shadow-none'
                                            : 'rounded-[22px] text-xl font-bold shadow-[0_16px_32px_rgba(15,23,42,0.12)]'
                                    "
                                    :style="product.iconImage ? undefined : { backgroundImage: product.background }"
                                >
                                    <img v-if="product.iconImage" :src="product.iconImage" :alt="product.name" class="h-full w-full rounded-[18px] object-cover" />
                                    <template v-else>
                                        {{ product.monogram }}
                                    </template>
                                </div>

                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span
                                            v-if="product.badge"
                                            class="inline-flex rounded-full bg-blue-600 px-2.5 py-1 text-[0.58rem] font-black uppercase tracking-[0.12em] text-white"
                                        >
                                            {{ product.badge }}
                                        </span>
                                        <span class="inline-flex rounded-full border border-slate-200 px-2.5 py-1 text-[0.58rem] font-black uppercase tracking-[0.12em] text-slate-500">
                                            {{ product.categoryTitle }}
                                        </span>
                                    </div>

                                    <h1 class="mt-3 text-[1.6rem] font-semibold leading-[1.12] tracking-[-0.02em] text-slate-900 sm:text-[1.75rem]">
                                        {{ productDisplayTitle }}
                                    </h1>
                                </div>
                            </div>

                            <div class="mt-6 grid grid-cols-2 gap-2">
                                <span
                                    v-for="badge in preset.trustBadges"
                                    :key="badge"
                                    class="flex min-w-0 items-center gap-2 rounded-[18px] border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-700"
                                >
                                    <ShieldCheck class="size-3.5 shrink-0 text-blue-600" />
                                    <span class="leading-5">{{ badge }}</span>
                                </span>
                            </div>

                            <div class="mt-7 rounded-[26px] border p-[1.125rem]" :style="{ borderColor: accentBorder, backgroundColor: accentSoft }">
                                <h2 class="text-lg font-black tracking-tight text-slate-950 [font-family:'Space Grotesk',sans-serif]">
                                    {{ preset.guideTitle }}
                                </h2>
                                <ol class="mt-4 space-y-3 text-sm leading-7 text-slate-600">
                                    <li v-for="(step, index) in preset.guideSteps" :key="step" class="flex gap-3">
                                        <span
                                            class="step-badge mt-1 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-white text-xs font-black text-slate-950"
                                            :style="{ animationDelay: `${index * 140}ms` }"
                                        >
                                            {{ index + 1 }}
                                        </span>
                                        <span>{{ step }}</span>
                                    </li>
                                </ol>
                            </div>

                            <div v-if="selectedPackageDetails.length" class="mt-5 rounded-[26px] border p-[1.125rem]" :style="{ borderColor: accentBorder, backgroundColor: '#eff6ff' }">
                                <h3 class="text-base font-black tracking-tight text-slate-950 [font-family:'Space Grotesk',sans-serif]">
                                    Info produk pilihan
                                </h3>
                                <p class="mt-3 text-lg font-black leading-tight tracking-tight text-slate-950 [font-family:'Space Grotesk',sans-serif]">
                                    {{ selectedPackage?.label }}
                                </p>
                                <ol class="mt-4 space-y-3 text-sm leading-7 text-slate-600">
                                    <li v-for="(detail, index) in selectedPackageDetails" :key="`${selectedPackage?.id}-${index}`" class="flex gap-3">
                                        <span
                                            class="step-badge mt-1 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-white text-xs font-black text-slate-950"
                                            :style="{ animationDelay: `${index * 140}ms` }"
                                        >
                                            {{ index + 1 }}
                                        </span>
                                        <span>{{ detail }}</span>
                                    </li>
                                </ol>
                            </div>

                            <div class="mt-5 rounded-[26px] border border-slate-200 bg-slate-50/80 p-[1.125rem]">
                                <h3 class="text-base font-black tracking-tight text-slate-950 [font-family:'Space Grotesk',sans-serif]">
                                    {{ preset.extraTitle }}
                                </h3>
                                <ol class="mt-4 space-y-3 text-sm leading-7 text-slate-600">
                                    <li v-for="(step, index) in preset.extraSteps" :key="step" class="flex gap-3">
                                        <span class="step-number mt-1 inline-flex min-w-5 justify-center text-xs font-black text-slate-400" :style="{ animationDelay: `${index * 140}ms` }">
                                            {{ index + 1 }}.
                                        </span>
                                        <span>{{ step }}</span>
                                    </li>
                                </ol>

                                <div class="mt-5 rounded-[22px] border p-4" :style="{ borderColor: accentBorder, backgroundColor: '#eff6ff' }">
                                    <div class="flex items-start gap-3">
                                        <CircleAlert class="mt-0.5 size-4 text-blue-600" />
                                        <div class="space-y-2 text-sm leading-6 text-slate-700">
                                            <p v-for="note in preset.notes" :key="note">{{ note }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </aside>

                        <section class="overflow-hidden rounded-[32px] border border-white/85 bg-white/94 shadow-[0_24px_70px_rgba(15,23,42,0.08)] backdrop-blur-xl">
                            <div class="grid border-b border-slate-200/80" :style="{ gridTemplateColumns: `repeat(${detailTabs.length}, minmax(0, 1fr))` }">
                                <button
                                    v-for="tab in detailTabs"
                                    :key="tab.id"
                                    type="button"
                                    class="relative px-6 py-5 text-center text-sm font-black uppercase tracking-[0.06em] transition sm:text-base"
                                    :class="activeDetailPanelId === tab.id ? 'bg-white text-slate-950' : 'bg-slate-50/90 text-slate-500'"
                                    @click="activeDetailPanelId = tab.id"
                                >
                                    {{ tab.label }}
                                    <span
                                        v-if="activeDetailPanelId === tab.id"
                                        class="absolute inset-x-8 bottom-0 h-1 rounded-full"
                                        :style="{ backgroundColor: uiAccent }"
                                    />
                                </button>
                            </div>

                            <div class="space-y-4 bg-slate-50/70 p-4 sm:p-5">
                                <template v-if="!isRatingTabActive">
                                    <div v-if="availableTabs.length > 1" class="flex flex-wrap gap-3">
                                        <button
                                            v-for="tab in availableTabs"
                                            :key="tab.id"
                                            type="button"
                                            class="rounded-full border px-4 py-2 text-sm font-bold transition"
                                            :class="activeTabId === tab.id ? 'bg-white text-slate-950 shadow-[0_10px_24px_rgba(15,23,42,0.06)]' : 'bg-slate-50 text-slate-500'"
                                            :style="activeTabId === tab.id ? { borderColor: accentBorder } : undefined"
                                            @click="activeTabId = tab.id"
                                        >
                                            {{ tab.label }}
                                        </button>
                                    </div>

                                <section class="rounded-[28px] border border-slate-200/90 bg-white p-[1.125rem] shadow-[0_18px_40px_rgba(15,23,42,0.04)] sm:p-5">
                                    <div class="flex items-center gap-3">
                                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-full text-sm font-black text-blue-600" :style="{ backgroundColor: '#dbeafe' }">
                                            1
                                        </span>
                                        <h2 class="text-[1.55rem] font-black tracking-tight text-slate-950 [font-family:'Space Grotesk',sans-serif] sm:text-[2.1rem]">Pilih nominal</h2>
                                    </div>

                                    <div
                                        class="sales-pill relative mt-5 inline-flex items-center gap-3 overflow-hidden rounded-full px-4 py-2.5 text-sm font-bold text-slate-700 shadow-[0_12px_26px_rgba(15,23,42,0.05)]"
                                        :style="{ backgroundImage: `linear-gradient(135deg, rgba(244,114,182,0.24) 0%, rgba(255,255,255,0.92) 48%, ${uiAccent}22 100%)` }"
                                    >
                                        <span class="sales-pill-sheen absolute inset-0" />
                                        <span class="sales-pill-icon relative inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-white/90 shadow-[0_8px_20px_rgba(244,114,182,0.18)]">
                                            <Flame class="size-4 text-rose-500" />
                                        </span>
                                        <span class="relative">{{ soldTodayLabel }}</span>
                                    </div>

                                    <div v-if="productVariantShortcuts.length > 1" class="hide-scrollbar mt-4 flex gap-2 overflow-x-auto pb-1" @wheel="handleVariantShortcutWheel">
                                        <Link
                                            v-for="variant in productVariantShortcuts"
                                            :key="variant.id"
                                            :href="route('products.show', { product: variant.id })"
                                            :prefetch="['hover']"
                                            :cache-for="60000"
                                            @click.prevent="switchProductVariant(variant.id)"
                                            @mouseenter="prefetchProductVariant(variant.id)"
                                            class="inline-flex min-h-11 shrink-0 items-center rounded-full border px-4 py-2.5 text-sm font-semibold whitespace-nowrap transition"
                                            :class="
                                                variant.id === product.id
                                                    ? 'text-slate-950 shadow-[0_10px_24px_rgba(37,99,235,0.12)]'
                                                    : 'border-slate-200 bg-slate-50 text-slate-600 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700'
                                            "
                                            :style="variant.id === product.id ? { borderColor: uiAccent, backgroundColor: accentSoft } : undefined"
                                        >
                                            {{ variant.name }}
                                        </Link>
                                    </div>

                                    <div v-if="hasAvailableProducts && (activeTab?.groups?.length ?? 0) > 1" class="mt-5 flex flex-wrap gap-3">
                                        <button
                                            v-for="group in activeTab?.groups"
                                            :key="group.id"
                                            type="button"
                                            class="rounded-full border px-4 py-2 text-sm font-bold transition"
                                            :class="
                                                activeGroupId === group.id
                                                    ? 'text-slate-950 shadow-[0_10px_24px_rgba(15,23,42,0.07)]'
                                                    : 'border-slate-300 bg-white text-slate-600 hover:border-slate-400'
                                            "
                                            :style="activeGroupId === group.id ? { borderColor: uiAccent, backgroundColor: accentSoft } : undefined"
                                            @click="activeGroupId = group.id"
                                        >
                                            {{ group.label }}
                                        </button>
                                    </div>

                                    <div v-if="activeGroup && activeGroup.options.length" class="mt-6">
                                        <h3 class="text-[1.4rem] font-black tracking-tight text-slate-950 [font-family:'Space Grotesk',sans-serif] sm:text-2xl">
                                            {{ activeGroup.title }}
                                        </h3>

                                        <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                                            <button
                                                v-for="item in activeGroup.options"
                                                :key="item.id"
                                                type="button"
                                                class="group flex min-h-[174px] w-full flex-col overflow-hidden rounded-[18px] border bg-white text-left transition duration-200 hover:-translate-y-0.5 hover:shadow-[0_12px_24px_rgba(15,23,42,0.06)]"
                                                :class="selectedPackageId === item.id ? 'shadow-[0_14px_28px_rgba(15,23,42,0.06)]' : 'border-slate-200 hover:border-slate-300'"
                                                :style="selectedPackageId === item.id ? { borderColor: uiAccent, boxShadow: `0 14px 28px ${uiAccent}14` } : undefined"
                                                @click="selectedPackageId = item.id"
                                            >
                                                <div class="flex flex-1 flex-col px-3.5 pb-3 pt-4">
                                                    <div :class="usingVipaymentCatalog ? '' : 'space-y-1'">
                                                        <p class="text-[0.82rem] font-black leading-tight tracking-tight text-slate-950 [font-family:'Space Grotesk',sans-serif] sm:text-[0.95rem]">
                                                            {{ item.label }}
                                                        </p>
                                                        <p v-if="!usingVipaymentCatalog" class="text-[0.78rem] font-medium leading-snug text-slate-700">
                                                            {{ item.note }}
                                                        </p>
                                                    </div>
                                                </div>

                                                <div class="border-t border-slate-100 bg-[linear-gradient(180deg,rgba(238,237,255,0.42)_0%,rgba(232,231,255,0.85)_100%)] px-3.5 py-2">
                                                    <p class="text-[0.62rem] font-medium text-slate-500">
                                                        Dari
                                                        <span class="ml-1 text-[0.98rem] font-black tracking-tight text-blue-600 [font-family:'Space Grotesk',sans-serif]">
                                                            {{ formatCurrency(item.price) }}
                                                        </span>
                                                    </p>

                                                    <div class="mt-1.5 flex items-center gap-1.5 text-[0.6rem] font-semibold uppercase tracking-[0.08em] text-emerald-700">
                                                        <Coins class="size-3.5 shrink-0" />
                                                        <span>Dapat {{ formatCoinCount(cashbackCoinsForAmount(item.price)) }}</span>
                                                    </div>

                                                    <div v-if="item.oldPrice || item.discountLabel" class="mt-1 flex items-center gap-1.5">
                                                        <span v-if="item.oldPrice" class="text-[0.58rem] font-semibold text-slate-400 line-through">
                                                            {{ formatCurrency(item.oldPrice) }}
                                                        </span>
                                                        <span
                                                            v-if="item.discountLabel"
                                                            class="rounded-[6px] bg-blue-600 px-1 py-0.5 text-[0.5rem] font-black uppercase tracking-[0.06em] text-white"
                                                        >
                                                            {{ item.discountLabel }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </button>
                                        </div>
                                    </div>

                                    <div v-else-if="packageFetchState !== 'loading'" class="mt-6 rounded-[22px] border border-slate-200 bg-slate-50 px-5 py-6 text-center">
                                        <p class="text-base font-black tracking-tight text-slate-950 [font-family:'Space Grotesk',sans-serif]">Produk belum tersedia.</p>
                                        <p v-if="packageFetchState === 'error' && packageFetchMessage" class="mt-2 text-sm text-slate-500">
                                            {{ packageFetchMessage }}
                                        </p>
                                    </div>
                                </section>

                                <section class="rounded-[28px] border border-slate-200/90 bg-white p-[1.125rem] shadow-[0_18px_40px_rgba(15,23,42,0.04)] sm:p-5">
                                    <div class="flex items-center gap-3">
                                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-full text-sm font-black text-blue-600" :style="{ backgroundColor: '#dbeafe' }">
                                            2
                                        </span>
                                        <h2 class="text-[1.55rem] font-black tracking-tight text-slate-950 [font-family:'Space Grotesk',sans-serif] sm:text-[2.1rem]">Masukkan jumlah pembelian</h2>
                                    </div>

                                    <div class="mt-5">
                                        <Input
                                            v-model="orderQuantity"
                                            type="number"
                                            min="1"
                                            inputmode="numeric"
                                            class="h-14 rounded-[20px] border-slate-200 bg-white px-5 text-lg font-semibold text-slate-950"
                                        />
                                    </div>
                                </section>

                                <section class="rounded-[28px] border border-slate-200/90 bg-white p-[1.125rem] shadow-[0_18px_40px_rgba(15,23,42,0.04)] sm:p-5">
                                    <div class="flex items-center gap-3">
                                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-blue-50 text-sm font-black text-blue-600">
                                            3
                                        </span>
                                        <h2 class="text-[1.55rem] font-black tracking-tight text-slate-950 [font-family:'Space Grotesk',sans-serif] sm:text-[2.1rem]">Pilih metode bayar</h2>
                                    </div>

                                    <div class="mt-5 rounded-[24px] border border-blue-100 bg-[linear-gradient(135deg,rgba(239,246,255,0.98),rgba(255,255,255,0.98),rgba(219,234,254,0.9))] px-5 py-4">
                                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                                            <div>
                                                <p class="flex items-center gap-2 text-[0.95rem] font-black tracking-tight text-slate-950 [font-family:'Space Grotesk',sans-serif] sm:text-lg">
                                                    <Coins class="size-4.5 text-blue-600" />
                                                    Lyva Coins & Cashback
                                                </p>
                                                <p class="mt-1 text-sm font-medium text-slate-600">
                                                    Lyva Coins bisa dipakai sebagai metode pembayaran kalau saldo kamu cukup. Cashback transaksi ini juga tetap mengikuti {{ coinRewardHint.toLowerCase() }}.
                                                </p>
                                            </div>

                                            <div class="grid gap-3 sm:grid-cols-2">
                                                <div class="rounded-[18px] border border-white/80 bg-white/90 px-4 py-3 shadow-[0_10px_22px_rgba(15,23,42,0.04)]">
                                                    <p class="text-[0.68rem] font-semibold uppercase tracking-[0.12em] text-slate-400">Cashback transaksi ini</p>
                                                    <p class="mt-2 text-[0.95rem] font-black tracking-tight text-emerald-600 [font-family:'Space Grotesk',sans-serif] sm:text-lg">
                                                        {{ cashbackCoinsLabel }}
                                                    </p>
                                                    <p v-if="isCoinPaymentSelected" class="mt-1 text-[0.68rem] font-semibold text-slate-500">
                                                        Bayar pakai coins tidak dapat cashback.
                                                    </p>
                                                </div>

                                                <div class="rounded-[18px] border border-white/80 bg-white/90 px-4 py-3 shadow-[0_10px_22px_rgba(15,23,42,0.04)]">
                                                    <p class="text-[0.68rem] font-semibold uppercase tracking-[0.12em] text-slate-400">Saldo coins sekarang</p>
                                                    <p class="mt-2 text-[0.95rem] font-black tracking-tight text-slate-950 [font-family:'Space Grotesk',sans-serif] sm:text-lg">
                                                        {{ currentCoinBalanceLabel }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mt-4 rounded-[20px] border px-4 py-3 text-sm font-medium" :class="canPayWithCoins ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-amber-100 bg-amber-50 text-amber-700'">
                                            {{ coinPaymentHint }}
                                        </div>
                                    </div>

                                    <div
                                        v-if="bankTransferBlockedByAmount"
                                        class="mt-4 rounded-[20px] border border-amber-100 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-700"
                                    >
                                        Untuk nominal di bawah {{ formatCurrency(minimumBankTransferAmount) }}, yang ditampilkan hanya QRIS, e-wallet, atau paylater tertentu.
                                    </div>

                                    <div v-if="isDuitkuPaymentLoading" class="mt-6">
                                        <div class="rounded-[22px] border border-slate-200 bg-slate-50/80 px-5 py-4">
                                            <p class="text-sm font-semibold text-slate-700">Memuat metode pembayaran dari Duitku...</p>
                                            <p class="mt-1 text-sm text-slate-500">Metode bayar akan muncul otomatis begitu data varian ini sudah siap.</p>
                                        </div>

                                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                                            <div
                                                v-for="index in 4"
                                                :key="`payment-loading-${index}`"
                                                class="animate-pulse rounded-[22px] border border-slate-200 bg-white px-4 py-4"
                                            >
                                                <div class="flex items-center gap-3">
                                                    <div class="h-10 w-10 rounded-[14px] bg-slate-200" />
                                                    <div class="min-w-0 flex-1 space-y-2">
                                                        <div class="h-4 w-28 rounded-full bg-slate-200" />
                                                        <div class="h-3 w-20 rounded-full bg-slate-100" />
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div v-if="hasAvailablePayments" class="mt-6">
                                        <h3 class="text-[1.15rem] font-black tracking-tight text-slate-950 [font-family:'Space Grotesk',sans-serif]">
                                            Rekomendasi Metode Bayar
                                        </h3>

                                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                                            <button
                                                v-for="payment in recommendedPayments"
                                                :key="payment.id"
                                                type="button"
                                                class="rounded-[22px] border bg-white px-4 py-4 text-left transition hover:-translate-y-0.5 hover:shadow-[0_12px_24px_rgba(15,23,42,0.06)]"
                                                :class="selectedPaymentId === payment.id ? 'shadow-[0_12px_24px_rgba(37,99,235,0.12)]' : 'border-slate-200'"
                                                :style="selectedPaymentId === payment.id ? { borderColor: paymentAccent, backgroundColor: paymentAccentSoft } : undefined"
                                                @click="selectedPaymentId = payment.id"
                                            >
                                                <div class="flex items-center justify-between gap-3">
                                                    <div class="flex items-center gap-3">
                                                        <img
                                                            v-if="payment.image"
                                                            :src="payment.image"
                                                            :alt="payment.label"
                                                            class="h-8 w-auto max-w-[5.5rem] object-contain"
                                                        />
                                                        <span
                                                            v-else
                                                            class="inline-flex h-10 min-w-10 items-center justify-center rounded-[14px] border px-2 text-[0.68rem] font-black uppercase tracking-[0.08em]"
                                                            :style="{
                                                                color: getPaymentVisual(payment).color,
                                                                backgroundColor: getPaymentVisual(payment).tint,
                                                                borderColor: `${getPaymentVisual(payment).color}22`,
                                                            }"
                                                        >
                                                            {{ getPaymentVisual(payment).badge }}
                                                        </span>
                                                        <div>
                                                            <p class="text-[0.95rem] font-black uppercase tracking-[0.06em] text-slate-950 [font-family:'Space Grotesk',sans-serif]">
                                                                {{ payment.label }}
                                                            </p>
                                                            <p class="mt-1 text-xs font-medium text-slate-500">{{ payment.caption }}</p>
                                                        </div>
                                                    </div>

                                                    <BadgeCheck v-if="selectedPaymentId === payment.id" class="size-4.5 text-blue-600" />
                                                </div>
                                            </button>
                                        </div>
                                    </div>

                                    <div v-if="hasAvailablePayments && otherPayments.length" class="mt-7">
                                        <h3 class="text-[1.15rem] font-black tracking-tight text-slate-950 [font-family:'Space Grotesk',sans-serif]">
                                            {{ secondaryPaymentsTitle }}
                                        </h3>

                                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                                            <button
                                                v-for="payment in otherPayments"
                                                :key="payment.id"
                                                type="button"
                                                class="rounded-[22px] border border-slate-200 bg-white px-4 py-4 text-left transition hover:-translate-y-0.5 hover:shadow-[0_12px_24px_rgba(15,23,42,0.06)]"
                                                :class="selectedPaymentId === payment.id ? 'shadow-[0_12px_24px_rgba(37,99,235,0.12)]' : ''"
                                                :style="selectedPaymentId === payment.id ? { borderColor: paymentAccent, backgroundColor: paymentAccentSoft } : undefined"
                                                @click="selectedPaymentId = payment.id"
                                            >
                                                <div class="flex items-center justify-between gap-3">
                                                    <div class="flex items-center gap-3">
                                                        <img
                                                            v-if="payment.image"
                                                            :src="payment.image"
                                                            :alt="payment.label"
                                                            class="h-8 w-auto max-w-[5.5rem] object-contain"
                                                        />
                                                        <span
                                                            v-else
                                                            class="inline-flex h-10 min-w-10 items-center justify-center rounded-[14px] border px-2 text-[0.68rem] font-black uppercase tracking-[0.08em]"
                                                            :style="{
                                                                color: getPaymentVisual(payment).color,
                                                                backgroundColor: getPaymentVisual(payment).tint,
                                                                borderColor: `${getPaymentVisual(payment).color}22`,
                                                            }"
                                                        >
                                                            {{ getPaymentVisual(payment).badge }}
                                                        </span>
                                                        <div>
                                                            <p class="text-[0.95rem] font-black uppercase tracking-[0.06em] text-slate-950 [font-family:'Space Grotesk',sans-serif]">
                                                                {{ payment.label }}
                                                            </p>
                                                            <p class="mt-1 text-xs font-medium text-slate-500">{{ payment.caption }}</p>
                                                        </div>
                                                    </div>

                                                    <BadgeCheck v-if="selectedPaymentId === payment.id" class="size-4.5 text-blue-600" />
                                                </div>
                                            </button>
                                        </div>
                                    </div>

                                    <div v-if="!hasAvailablePayments && paymentFetchState !== 'loading'" class="mt-6 rounded-[22px] border border-slate-200 bg-slate-50 px-5 py-6 text-center">
                                        <p class="text-base font-black tracking-tight text-slate-950 [font-family:'Space Grotesk',sans-serif]">Pembayaran tidak tersedia.</p>
                                        <p v-if="paymentFetchState === 'error' && paymentFetchMessage" class="mt-2 text-sm text-slate-500">
                                            {{ paymentFetchMessage }}
                                        </p>
                                    </div>
                                </section>

                                <section class="rounded-[28px] border border-slate-200/90 bg-white p-[1.125rem] shadow-[0_18px_40px_rgba(15,23,42,0.04)] sm:p-5">
                                    <div class="flex items-center gap-3">
                                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-blue-50 text-sm font-black text-blue-600">
                                            4
                                        </span>
                                        <h2 class="text-[1.55rem] font-black tracking-tight text-slate-950 [font-family:'Space Grotesk',sans-serif] sm:text-[2.1rem]">Masukkan detail akun</h2>
                                    </div>

                                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                                        <div
                                            v-for="field in resolvedAccountFields"
                                            :key="field.id"
                                            :class="resolvedAccountFields.length === 1 ? 'space-y-2 sm:col-span-2' : 'space-y-2'"
                                        >
                                            <Label :for="field.id" class="text-sm font-semibold text-slate-700">{{ field.label }}</Label>
                                            <div class="relative">
                                                <Input
                                                    :id="field.id"
                                                    :model-value="accountFieldDrafts[field.id] ?? ''"
                                                    :type="field.inputType ?? 'text'"
                                                    :placeholder="field.placeholder"
                                                    :inputmode="field.id === 'account-user-id' || field.id === 'account-zone' ? 'numeric' : undefined"
                                                    autocomplete="off"
                                                    class="h-12 rounded-2xl border-slate-200 bg-white px-4 pr-11 text-base sm:text-sm"
                                                    @update:model-value="
                                                        (value) => {
                                                            accountFieldDrafts[field.id] = String(value ?? '');
                                                            queueFieldCommit();
                                                        }
                                                    "
                                                    @blur="
                                                        () => {
                                                            commitFieldDraftsImmediately();

                                                            if (field.id === 'account-user-id' || field.id === 'account-zone') {
                                                                triggerNicknameLookup();
                                                            }
                                                        }
                                                    "
                                                />
                                                <UserRound class="pointer-events-none absolute right-4 top-1/2 size-4.5 -translate-y-1/2 text-slate-300" />
                                            </div>
                                        </div>
                                    </div>

                                    <div
                                        v-if="resolvedAccountFields.length === 0"
                                        class="mt-5 rounded-[20px] border border-blue-100 bg-blue-50/80 px-4 py-3 text-sm font-medium text-blue-700"
                                    >
                                        Produk ini tidak meminta data akun tambahan. Lanjut isi kontak pembeli untuk checkout.
                                    </div>

                                    <div v-if="nicknameLookupEnabled" class="mt-4 flex justify-end">
                                        <Button
                                            type="button"
                                            class="h-11 rounded-2xl px-4 text-sm font-bold text-white"
                                            :disabled="!canTriggerNicknameLookup"
                                            :style="{ backgroundColor: uiAccent }"
                                            @click="triggerNicknameLookup"
                                        >
                                            {{ nicknameLookup.status === 'checking' ? 'Mengecek...' : 'Cek username' }}
                                        </Button>
                                    </div>

                                    <div
                                        v-if="nicknameLookupFeedback"
                                        class="mt-4 rounded-[20px] border px-4 py-3 text-sm font-medium"
                                        :class="
                                            nicknameLookupFeedback.tone === 'success'
                                                ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
                                                : nicknameLookupFeedback.tone === 'error'
                                                  ? 'border-rose-200 bg-rose-50 text-rose-600'
                                                  : 'border-blue-200 bg-blue-50 text-blue-600'
                                        "
                                    >
                                        {{ nicknameLookupFeedback.message }}
                                    </div>
                                </section>

                                <section class="rounded-[28px] border border-slate-200/90 bg-white p-[1.125rem] shadow-[0_18px_40px_rgba(15,23,42,0.04)] sm:p-5">
                                    <div class="flex items-center gap-3">
                                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-blue-50 text-sm font-black text-blue-600">
                                            5
                                        </span>
                                        <h2 class="text-[1.55rem] font-black tracking-tight text-slate-950 [font-family:'Space Grotesk',sans-serif] sm:text-[2.1rem]">Masukkan info kontak</h2>
                                    </div>

                                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                                        <div v-for="field in contactFields" :key="field.id" class="space-y-2">
                                            <Label :for="field.id" class="text-sm font-semibold text-slate-700">{{ field.label }}</Label>
                                            <div
                                                v-if="isLoggedIn && isProfileBackedContactField(field.id)"
                                                class="flex min-h-12 items-center justify-between rounded-2xl border border-emerald-100 bg-emerald-50/80 px-4 py-3"
                                            >
                                                <div class="min-w-0">
                                                    <p class="truncate text-sm font-semibold text-slate-900">
                                                        {{ contactFieldValues[field.id] ?? profileContactOverrides[field.id] ?? '' }}
                                                    </p>
                                                    <p class="mt-1 text-[0.72rem] font-semibold uppercase tracking-[0.24em] text-emerald-600">
                                                        Otomatis dari profil
                                                    </p>
                                                </div>
                                                <UserRound class="ml-3 size-4.5 shrink-0 text-emerald-400" />
                                            </div>
                                            <div v-else class="relative">
                                                <Input
                                                    :id="field.id"
                                                    :model-value="contactFieldDrafts[field.id] ?? ''"
                                                    type="text"
                                                    :placeholder="field.placeholder"
                                                    class="h-12 rounded-2xl border-slate-200 bg-white px-4 pr-11 text-base sm:text-sm"
                                                    @update:model-value="
                                                        (value) => {
                                                            contactFieldDrafts[field.id] = String(value ?? '');
                                                            queueFieldCommit();
                                                        }
                                                    "
                                                    @blur="commitFieldDraftsImmediately"
                                                />
                                                <UserRound class="pointer-events-none absolute right-4 top-1/2 size-4.5 -translate-y-1/2 text-slate-300" />
                                            </div>
                                        </div>
                                    </div>
                                </section>

                                <section class="rounded-[28px] border border-slate-200/90 bg-white p-[1.125rem] shadow-[0_18px_40px_rgba(15,23,42,0.04)] sm:p-5">
                                    <div class="flex items-center gap-3">
                                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-blue-50 text-sm font-black text-blue-600">
                                            6
                                        </span>
                                        <h2 class="text-[1.55rem] font-black tracking-tight text-slate-950 [font-family:'Space Grotesk',sans-serif] sm:text-[2.1rem]">Punya kode promo?</h2>
                                    </div>

                                    <div class="mt-5 space-y-3">
                                        <div class="relative">
                                            <Input
                                                id="promo-code"
                                                v-model="promoCode"
                                                type="text"
                                                :placeholder="preset.promoPlaceholder"
                                                class="h-12 rounded-2xl border-slate-200 bg-white px-4 pr-11 text-base sm:text-sm"
                                            />
                                            <Ticket class="pointer-events-none absolute right-4 top-1/2 size-4.5 -translate-y-1/2 text-slate-300" />
                                        </div>
                                        <p class="text-sm text-slate-500">Kode promo dicek otomatis dan langsung menghitung ulang total checkout.</p>

                                        <div
                                            v-if="promoPreview.status !== 'idle'"
                                            class="rounded-[22px] border px-4 py-4"
                                            :class="promoStatusClass"
                                        >
                                            <div class="flex items-start gap-3">
                                                <LoaderCircle v-if="promoPreview.status === 'checking'" class="mt-0.5 size-4.5 animate-spin" />
                                                <BadgeCheck v-else-if="promoPreview.status === 'applied'" class="mt-0.5 size-4.5" />
                                                <CircleAlert v-else class="mt-0.5 size-4.5" />

                                                <div class="min-w-0 flex-1">
                                                    <p class="text-sm font-semibold">{{ promoPreview.message }}</p>

                                                    <div v-if="promoPreview.status === 'applied'" class="mt-3 grid gap-2 sm:grid-cols-3">
                                                        <div class="rounded-[18px] border border-white/70 bg-white/70 px-3 py-3">
                                                            <p class="text-[0.62rem] font-black uppercase tracking-[0.14em] text-slate-400">Kode</p>
                                                            <p class="mt-2 text-sm font-bold text-slate-900">{{ promoPreview.code }}</p>
                                                        </div>
                                                        <div class="rounded-[18px] border border-white/70 bg-white/70 px-3 py-3">
                                                            <p class="text-[0.62rem] font-black uppercase tracking-[0.14em] text-slate-400">Diskon</p>
                                                            <p class="mt-2 text-sm font-bold text-emerald-600">-{{ formatCurrency(promoPreview.discount) }}</p>
                                                        </div>
                                                        <div class="rounded-[18px] border border-white/70 bg-white/70 px-3 py-3">
                                                            <p class="text-[0.62rem] font-black uppercase tracking-[0.14em] text-slate-400">Total baru</p>
                                                            <p class="mt-2 text-sm font-bold text-slate-900">{{ formatCurrency(orderTotal) }}</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </section>
                                </template>

                                <template v-else>
                                    <section class="overflow-hidden rounded-[28px] border border-slate-200/90 bg-white shadow-[0_18px_40px_rgba(15,23,42,0.04)]">
                                        <div class="bg-[linear-gradient(135deg,rgba(37,99,235,0.14)_0%,rgba(255,255,255,0.98)_48%,rgba(16,185,129,0.12)_100%)] px-5 py-6 sm:px-6">
                                            <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                                                <div class="max-w-2xl">
                                                    <span class="inline-flex rounded-full border border-white/80 bg-white/80 px-3 py-1 text-xs font-black uppercase tracking-[0.12em] text-blue-600">
                                                        Rating pelanggan
                                                    </span>
                                                    <h2 class="mt-4 text-[2.2rem] font-black tracking-tight text-slate-950 [font-family:'Space Grotesk',sans-serif]">
                                                        {{ hasProductRatings ? 'Pembeli kasih nilai tinggi untuk' : 'Rating untuk' }} {{ ratingSummary.familyTitle }}
                                                    </h2>
                                                    <p class="mt-3 max-w-xl text-sm leading-7 text-slate-600">
                                                        {{
                                                            hasProductRatings
                                                                ? 'Ringkasan ini menampilkan rating transaksi asli untuk keluarga produk yang sama, termasuk kecepatan proses dan pengalaman checkout.'
                                                                : 'Belum ada rating masuk untuk produk ini. Setelah ada pesanan selesai dan pembeli kasih ulasan, ratingnya otomatis tampil di sini.'
                                                        }}
                                                    </p>
                                                </div>

                                                <div class="rounded-[24px] border border-white/80 bg-white/88 px-5 py-5 shadow-[0_18px_40px_rgba(15,23,42,0.06)]">
                                                    <p class="text-sm font-bold uppercase tracking-[0.12em] text-slate-400">Skor rata-rata</p>
                                                    <div class="mt-3 flex items-end gap-3">
                                                        <span class="text-[3rem] font-black leading-none tracking-tight text-slate-950 [font-family:'Space Grotesk',sans-serif]">
                                                            {{ ratingSummary.average }}
                                                        </span>
                                                        <span class="pb-2 text-sm font-semibold text-slate-500">/ 5.00</span>
                                                    </div>
                                                    <div class="mt-3 flex items-center gap-1">
                                                        <Star
                                                            v-for="index in 5"
                                                            :key="`summary-star-${index}`"
                                                            class="size-4.5"
                                                            :class="index <= summaryStarCount ? 'fill-amber-400 text-amber-400' : 'fill-transparent text-slate-200'"
                                                        />
                                                    </div>
                                                    <p class="mt-3 text-sm font-medium text-slate-600">
                                                        {{ formatNumber(ratingSummary.totalReviews) }} ulasan terverifikasi
                                                    </p>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="grid gap-3 border-t border-slate-200/80 bg-white px-5 py-5 sm:grid-cols-3 sm:px-6">
                                            <article
                                                v-for="highlight in ratingHighlights"
                                                :key="highlight.label"
                                                class="rounded-[22px] border border-slate-200 bg-slate-50/80 px-4 py-4"
                                            >
                                                <p class="text-xs font-black uppercase tracking-[0.12em] text-slate-400">{{ highlight.label }}</p>
                                                <p class="mt-3 text-[1.45rem] font-black tracking-tight text-slate-950 [font-family:'Space Grotesk',sans-serif]">
                                                    {{ highlight.value }}
                                                </p>
                                            </article>
                                        </div>
                                    </section>

                                    <section
                                        v-if="ratingReviews.length"
                                        class="grid gap-4 xl:grid-cols-3"
                                    >
                                        <article
                                            v-for="(review, reviewIndex) in ratingReviews"
                                            :key="review.id"
                                            class="rating-review-card rounded-[28px] border border-slate-200/90 bg-white p-5 shadow-[0_18px_40px_rgba(15,23,42,0.04)]"
                                            :style="{ animationDelay: `${reviewIndex * 140}ms` }"
                                        >
                                            <div class="flex items-start justify-between gap-4">
                                                <div>
                                                    <p class="text-lg font-black tracking-tight text-slate-950 [font-family:'Space Grotesk',sans-serif]">
                                                        {{ review.name }}
                                                    </p>
                                                    <p class="mt-1 text-sm font-medium text-slate-500">{{ review.timeLabel }}</p>
                                                </div>
                                                <span class="inline-flex rounded-full bg-emerald-50 px-3 py-1 text-[0.68rem] font-black uppercase tracking-[0.12em] text-emerald-600">
                                                    {{ review.badge }}
                                                </span>
                                            </div>

                                            <div class="mt-5 flex items-center gap-1">
                                                <Star
                                                    v-for="index in 5"
                                                    :key="`${review.name}-${index}`"
                                                    class="size-4.5"
                                                    :class="index <= review.rating ? 'fill-amber-400 text-amber-400' : 'fill-transparent text-slate-200'"
                                                />
                                            </div>

                                            <p class="mt-5 text-sm leading-7 text-slate-600">
                                                {{ review.comment }}
                                            </p>
                                        </article>
                                    </section>

                                    <section
                                        v-else
                                        class="rounded-[28px] border border-dashed border-slate-300 bg-white/90 px-6 py-8 text-center shadow-[0_18px_40px_rgba(15,23,42,0.04)]"
                                    >
                                        <p class="text-lg font-black tracking-tight text-slate-950 [font-family:'Space Grotesk',sans-serif]">
                                            Belum ada ulasan untuk {{ ratingSummary.familyTitle }}
                                        </p>
                                        <p class="mx-auto mt-3 max-w-2xl text-sm leading-7 text-slate-600">
                                            Begitu ada pembeli yang menyelesaikan pesanan dan memberi rating, ulasan untuk keluarga produk ini akan langsung tampil di sini.
                                        </p>
                                    </section>
                                </template>
                            </div>
                        </section>
                    </section>

                </template>

                <section
                    v-else
                    class="rounded-[34px] border border-white/85 bg-white/92 p-8 text-center shadow-[0_28px_80px_rgba(15,23,42,0.08)]"
                >
                    <CircleAlert class="mx-auto size-12 text-blue-600" />
                    <h1 class="mt-4 text-3xl font-black tracking-tight text-slate-950 [font-family:'Space Grotesk',sans-serif]">Produk tidak ditemukan</h1>
                    <p class="mx-auto mt-3 max-w-xl text-sm leading-7 text-slate-600">
                        Produk yang kamu buka belum tersedia atau link-nya sudah berubah. Kamu bisa kembali ke homepage untuk pilih produk lain.
                    </p>
                    <div class="mt-6 flex justify-center">
                        <Link
                            :href="route('home')"
                            class="inline-flex h-12 items-center justify-center rounded-[18px] bg-indigo-700 px-6 text-sm font-bold text-white shadow-[0_18px_40px_rgba(67,56,202,0.22)]"
                        >
                            Kembali ke beranda
                        </Link>
                    </div>
                </section>
            </div>

            <template v-if="product && preset">
                <Dialog :open="isCashbackDialogOpen" @update:open="(open) => (isCashbackDialogOpen = open)">
                    <DialogContent class="max-w-[28rem] gap-0 overflow-hidden rounded-[28px] border-0 bg-white p-0 text-slate-950 shadow-[0_30px_80px_rgba(15,23,42,0.22)]">
                        <div class="border-b border-slate-200/80 px-6 py-5">
                            <DialogTitle class="text-[2rem] font-black tracking-tight text-slate-950 [font-family:'Space Grotesk',sans-serif]">
                                Cashback Coins
                            </DialogTitle>
                        </div>

                        <div class="space-y-5 px-6 py-6">
                            <div class="rounded-[24px] bg-[linear-gradient(135deg,rgba(236,72,153,0.14)_0%,rgba(255,255,255,0.98)_48%,rgba(37,99,235,0.14)_100%)] px-5 py-6 text-center">
                                <p class="text-lg font-medium text-slate-700">Kamu bisa dapat</p>
                                <div class="mt-3 flex items-center justify-center gap-2">
                                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-amber-100 text-amber-500">
                                        <Coins class="size-4" />
                                    </span>
                                    <span class="text-[2rem] font-black tracking-tight text-emerald-600 [font-family:'Space Grotesk',sans-serif]">
                                        {{ cashbackCoinsLabel }}
                                    </span>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div class="flex gap-3 rounded-[20px] border border-slate-200 bg-slate-50 px-4 py-4">
                                    <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-blue-50 text-blue-600">
                                        <WalletCards class="size-5" />
                                    </span>
                                    <p class="text-sm font-semibold leading-6 text-slate-700">
                                        Masuk otomatis
                                        <br />
                                        saat order selesai
                                    </p>
                                </div>
                                <div class="flex gap-3 rounded-[20px] border border-slate-200 bg-slate-50 px-4 py-4">
                                    <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-blue-50 text-blue-600">
                                        <ArrowRightLeft class="size-5" />
                                    </span>
                                    <p class="text-sm font-semibold leading-6 text-slate-700">
                                        <template v-if="isCoinPaymentSelected">
                                            Bayar pakai coins
                                            <br />
                                            cashback jadi 0
                                        </template>
                                        <template v-else>
                                            Reward cashback
                                            <br />
                                            bukan alat bayar
                                        </template>
                                    </p>
                                </div>
                            </div>

                            <div class="border-t border-slate-200 pt-5">
                                <p class="text-center text-base font-medium text-slate-600">Masuk untuk dapatkan cashback coins</p>
                            </div>

                            <div class="space-y-3">
                                <Button
                                    type="button"
                                    class="h-14 w-full rounded-[20px] text-base font-black text-white shadow-[0_18px_40px_rgba(37,99,235,0.24)]"
                                    :style="{ backgroundColor: uiAccent }"
                                    @click="goToLoginForCashback"
                                >
                                    <LogIn class="mr-2 size-4.5" />
                                    Masuk & dapatkan cashback
                                </Button>

                                <Button
                                    type="button"
                                    variant="outline"
                                    class="h-14 w-full rounded-[20px] border-blue-600 bg-white text-base font-black text-blue-700 hover:bg-blue-50"
                                    @click="continueWithoutCashback"
                                >
                                    Lanjut tanpa cashback
                                </Button>
                            </div>
                        </div>
                    </DialogContent>
                </Dialog>

                <Dialog :open="isCheckoutDialogOpen" @update:open="(open) => (isCheckoutDialogOpen = isSubmittingCheckout ? true : open)">
                    <DialogContent class="w-[calc(100vw-1.4rem)] max-h-[calc(100vh-7rem)] max-w-[40rem] gap-0 overflow-hidden rounded-[18px] border-0 bg-white p-0 text-slate-950 shadow-[0_24px_64px_rgba(15,23,42,0.2)] sm:max-h-[calc(100vh-3rem)] sm:w-[min(96vw,42rem)] sm:rounded-[28px]">
                        <div class="border-b border-slate-200/80 px-4 py-3 sm:px-6 sm:py-5">
                            <DialogTitle class="text-[1.08rem] font-black tracking-tight text-slate-950 [font-family:'Space Grotesk',sans-serif] sm:text-[2rem]">
                                Konfirmasi pembelian
                            </DialogTitle>
                        </div>

                        <div class="max-h-[calc(100vh-12rem)] space-y-2.5 overflow-y-auto px-4 py-3 sm:max-h-[calc(100vh-6rem)] sm:space-y-5 sm:px-6 sm:py-6">
                            <div class="space-y-2.5">
                                <h3 class="text-[1rem] font-black tracking-tight text-slate-950 [font-family:'Space Grotesk',sans-serif] sm:text-[1.45rem]">Detail Produk</h3>

                                <div class="rounded-[18px] border border-slate-200 bg-[linear-gradient(135deg,rgba(239,246,255,0.92),rgba(255,255,255,0.98))] p-3 shadow-[0_18px_40px_rgba(15,23,42,0.04)] sm:rounded-[24px] sm:p-4">
                                    <div class="flex items-start gap-2.5 sm:gap-4">
                                        <div
                                            class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden text-white sm:h-16 sm:w-16"
                                            :class="
                                                product.iconImage
                                                    ? 'rounded-[18px] bg-transparent shadow-none'
                                                    : 'rounded-[20px] text-lg font-bold shadow-[0_14px_28px_rgba(15,23,42,0.12)]'
                                            "
                                            :style="product.iconImage ? undefined : { backgroundImage: product.background }"
                                        >
                                            <img
                                                v-if="product.coverImage"
                                                :src="product.coverImage"
                                                :alt="product.name"
                                                class="h-full w-full rounded-[18px] object-cover"
                                            />
                                            <template v-else>
                                                {{ product.monogram }}
                                            </template>
                                        </div>

                                        <div class="min-w-0 flex-1">
                                            <p class="text-[0.62rem] font-semibold uppercase tracking-[0.14em] text-slate-400">Produk dipilih</p>
                                            <p class="mt-1 text-[0.78rem] leading-4.5 text-slate-600 sm:mt-2 sm:text-sm sm:leading-6">
                                                {{ selectedPackage?.label ?? '-' }}
                                            </p>
                                        </div>

                                        <div class="shrink-0 text-right">
                                            <p class="text-[0.95rem] font-black text-blue-700 sm:text-sm">x{{ normalizedQuantity }}</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-2 lg:grid-cols-3">
                                    <div
                                        v-for="row in detailSummaryRows"
                                        :key="row.label"
                                        class="min-w-0 rounded-[14px] border border-slate-200 bg-slate-50/90 px-2.5 py-2 shadow-[0_10px_24px_rgba(15,23,42,0.03)] sm:px-3 sm:py-2.5"
                                    >
                                        <p class="text-[0.58rem] font-semibold uppercase tracking-[0.11em] text-slate-400">{{ row.label }}</p>
                                        <p class="mt-1 break-all text-[0.78rem] font-semibold leading-[1.05rem] text-slate-800 sm:text-[0.84rem] sm:leading-[1.15rem]">
                                            {{ row.value }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="border-t border-slate-200 pt-3 sm:pt-5">
                                <h3 class="text-[1rem] font-black tracking-tight text-slate-950 [font-family:'Space Grotesk',sans-serif] sm:text-[1.45rem]">Detail Bayar</h3>

                                <div class="mt-2.5 space-y-2.5 sm:mt-3 sm:space-y-3">
                                    <div v-if="hasAppliedPromo" class="flex items-center justify-between gap-4 text-sm">
                                        <span class="font-medium text-slate-400">Subtotal</span>
                                        <span class="font-semibold text-slate-500 line-through">{{ formatCurrency(packageSubtotal) }}</span>
                                    </div>

                                    <div v-if="hasAppliedPromo" class="flex items-center justify-between gap-4 text-sm">
                                        <span class="font-medium text-slate-400">Diskon promo</span>
                                        <span class="font-semibold text-emerald-600">-{{ formatCurrency(appliedPromoDiscount) }}</span>
                                    </div>

                                    <div class="flex items-center justify-between gap-3 border-t border-slate-100 pt-2.5 sm:pt-3">
                                        <span class="font-medium text-slate-400">Total bayar</span>
                                        <span class="text-[1.25rem] font-black tracking-tight text-slate-950 [font-family:'Space Grotesk',sans-serif] sm:text-[2rem]">
                                            {{ formatCurrency(orderTotal) }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <div class="border-t border-slate-200/80 bg-white px-4 pb-[calc(0.75rem+env(safe-area-inset-bottom,0px))] pt-2.5 sm:px-6 sm:pb-6 sm:pt-4">
                            <Button
                                type="button"
                                class="h-10 w-full rounded-[15px] text-[0.86rem] font-black text-white shadow-[0_18px_40px_rgba(37,99,235,0.24)] sm:h-14 sm:rounded-[20px] sm:text-base"
                                :style="{ backgroundColor: uiAccent }"
                                :disabled="!checkoutReady || isSubmittingCheckout"
                                @click="confirmCheckout"
                            >
                                <LoaderCircle v-if="isSubmittingCheckout" class="mr-2 size-4.5 animate-spin" />
                                {{ isSubmittingCheckout ? 'Memproses pembayaran...' : 'Bayar Sekarang' }}
                            </Button>

                            <p v-if="checkoutBlockingMessage" class="mt-2 text-center text-[0.78rem] font-medium text-rose-600 sm:mt-3 sm:text-sm">
                                {{ checkoutBlockingMessage }}
                            </p>
                        </div>
                    </DialogContent>
                </Dialog>

                <div
                    class="pointer-events-none inset-x-0 bottom-[calc(4.95rem+env(safe-area-inset-bottom,0px))] z-50 transition-all duration-300 md:fixed md:bottom-0"
                    :class="shouldDockCheckoutBarToPageEnd ? 'absolute' : 'fixed md:fixed'"
                >
                    <section class="pointer-events-auto overflow-hidden border-t border-white/80 bg-white/95 shadow-[0_-24px_70px_rgba(15,23,42,0.16)] backdrop-blur-xl">
                        <div class="mx-auto flex max-w-7xl flex-col gap-2.5 px-3 py-2.5 sm:px-6 sm:py-5 lg:flex-row lg:items-center lg:justify-between lg:px-8">
                            <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center sm:gap-5">
                                <div>
                                    <p v-if="hasAppliedPromo" class="text-[0.72rem] font-semibold text-slate-400 line-through sm:text-sm">
                                        {{ formatCurrency(packageSubtotal) }}
                                    </p>
                                    <p class="text-[1.2rem] font-black leading-none text-blue-600 [font-family:'Space Grotesk',sans-serif] sm:text-3xl">
                                        {{ selectedPackage ? formatCurrency(orderTotal) : '-' }}
                                    </p>
                                    <p class="mt-1 line-clamp-1 text-[0.82rem] font-medium leading-5 text-slate-600 sm:text-[0.92rem] sm:leading-6">
                                        {{ selectedPackage?.label ?? 'Pilih paket dulu' }}
                                        <span v-if="selectedPackage" class="text-slate-400"> x{{ normalizedQuantity }}</span>
                                        <span v-if="selectedPayment" class="text-slate-400">, {{ selectedPayment.label }}</span>
                                    </p>
                                    <p v-if="hasAppliedPromo" class="mt-0.5 text-[0.68rem] font-semibold text-emerald-600 sm:mt-1 sm:text-[0.78rem]">
                                        Promo {{ promoPreview.code }} aktif, hemat {{ formatCurrency(appliedPromoDiscount) }}
                                    </p>
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-[0.65rem] font-semibold text-slate-600 sm:gap-2 sm:px-3 sm:py-1.5 sm:text-[0.72rem]">
                                        <Coins class="size-3.5 text-blue-600" />
                                        Harga final
                                    </span>
                                    <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-[0.65rem] font-semibold text-slate-600 sm:gap-2 sm:px-3 sm:py-1.5 sm:text-[0.72rem]">
                                        <Gift class="size-3.5 text-blue-600" />
                                        Proses otomatis
                                    </span>
                                </div>
                            </div>

                            <Button
                                class="h-10 w-full rounded-[16px] px-6 text-[0.92rem] font-black text-white shadow-[0_18px_36px_rgba(67,56,202,0.22)] sm:h-14 sm:w-auto sm:min-w-[220px] sm:rounded-[20px] sm:px-8 sm:text-base"
                                type="button"
                                :disabled="!checkoutReady"
                                :style="{ backgroundColor: uiAccent }"
                                @click="handleCheckoutClick"
                            >
                                Beli sekarang
                            </Button>
                        </div>

                        <p v-if="checkoutBlockingMessage" class="px-3 pb-2 text-[0.72rem] font-medium text-rose-600 sm:px-6 sm:pb-3 sm:text-sm lg:text-right">
                            {{ checkoutBlockingMessage }}
                        </p>
                    </section>
                </div>
            </template>
        </main>
    </PublicLayout>

</template>

<style scoped>
.hide-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}

.hide-scrollbar::-webkit-scrollbar {
    display: none;
}

.rating-review-card {
    opacity: 1;
    transform: translate3d(0, 0, 0);
    animation: rating-review-float 3.8s ease-in-out infinite;
    will-change: transform;
}

.step-badge,
.step-number {
    animation: product-step-bounce 3.4s ease-in-out infinite;
    will-change: transform, box-shadow, opacity;
}

.step-badge {
    box-shadow: 0 8px 18px rgba(255, 255, 255, 0.5);
}

@keyframes rating-review-float {
    0% {
        transform: translate3d(0, 0, 0);
    }

    50% {
        transform: translate3d(0, -10px, 0);
    }

    100% {
        transform: translate3d(0, 0, 0);
    }
}

@keyframes product-step-bounce {
    0% {
        transform: translate3d(0, 0, 0) scale(1);
        opacity: 0.96;
    }

    50% {
        transform: translate3d(0, -3px, 0) scale(1.06);
        opacity: 1;
    }

    100% {
        transform: translate3d(0, 0, 0) scale(1);
        opacity: 0.96;
    }
}

@media (prefers-reduced-motion: reduce) {
    .rating-review-card,
    .step-badge,
    .step-number {
        opacity: 1;
        transform: none;
        animation: none;
    }
}
</style>
