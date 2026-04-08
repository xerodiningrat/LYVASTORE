import {
    Flame,
    Gamepad2,
    WalletCards,
    Smartphone,
    Sparkles,
    Ticket,
    Tv,
    Zap,
    type LucideIcon,
} from 'lucide-vue-next';
import { withFullProductArtwork, type ProductArtwork } from './product-artwork';

export type CategoryChip = {
    id: string;
    label: string;
    icon: LucideIcon;
};

export type ProductCard = ProductArtwork & {
    id: string;
    name: string;
    subtitle?: string;
    monogram: string;
    background: string;
    accent: string;
    badge?: string;
};

export type ProductSection = {
    id: string;
    title: string;
    icon: LucideIcon;
    items: ProductCard[];
};

type ProductCardSeed = Omit<ProductCard, keyof ProductArtwork>;
type ProductSectionSeed = Omit<ProductSection, 'items'> & {
    items: ProductCardSeed[];
};

export type CatalogProduct = ProductCard & {
    categoryId: string;
    categoryTitle: string;
    categoryIcon: LucideIcon;
};

type ProductIdentity = Pick<ProductCard, 'id' | 'name'>;

export type ProductFamilyRule = {
    id: string;
    title: string;
    match: (product: ProductIdentity) => boolean;
    variantOrder?: string[];
};

export const categoryChips: CategoryChip[] = [
    { id: 'popular', label: 'Lagi Populer', icon: Flame },
    { id: 'instant', label: 'Top Up Langsung', icon: Zap },
    { id: 'release', label: 'Baru Rilis', icon: Sparkles },
    { id: 'login', label: 'Top Up Login', icon: Gamepad2 },
    { id: 'voucher', label: 'Voucher', icon: Ticket },
    { id: 'pulsa', label: 'Pulsa', icon: Smartphone },
    { id: 'ewallet', label: 'E-Wallet', icon: WalletCards },
    { id: 'entertainment', label: 'Entertainment', icon: Tv },
];

export const productFamilyRules: ProductFamilyRule[] = [
    {
        id: 'mobile-legends',
        title: 'Mobile Legends',
        match: (product) => product.id.startsWith('mobile-legends') || product.name.toLowerCase().startsWith('mobile legends'),
        variantOrder: ['A', 'B', 'Global', 'Gift Skin', 'Gift', 'Brazil', 'Malaysia', 'Singapore', 'Philippines', 'Russia', 'Login'],
    },
    {
        id: 'magic-chess-go-go',
        title: 'Magic Chess: Go Go',
        match: (product) => product.id.includes('magic-chess-go-go') || product.name.toLowerCase().startsWith('magic chess: go go'),
        variantOrder: ['Utama', 'Global'],
    },
    {
        id: 'free-fire',
        title: 'Free Fire',
        match: (product) => product.id.startsWith('free-fire') || product.name.toLowerCase().startsWith('free fire'),
        variantOrder: ['Utama', 'Global', 'Max', 'Max Global', 'Login'],
    },
    {
        id: 'pubg-mobile',
        title: 'PUBG Mobile',
        match: (product) => product.id.startsWith('pubg') || product.name.toLowerCase().startsWith('pubg mobile'),
        variantOrder: ['ID', 'Global', 'Login'],
    },
    {
        id: 'honor-of-kings',
        title: 'Honor of Kings',
        match: (product) =>
            product.id.startsWith('honor-of-kings') || product.id.startsWith('hok-') || product.name.toLowerCase().startsWith('honor of kings'),
        variantOrder: ['Utama', 'Global', 'Login'],
    },
];

const baseProductSections: ProductSectionSeed[] = [
    {
        id: 'popular',
        title: 'Lagi Populer',
        icon: Flame,
        items: [
            { id: 'mobile-legends-a', name: 'Mobile Legends A', monogram: 'MA', background: 'linear-gradient(145deg, #fde047 0%, #f97316 45%, #4338ca 100%)', accent: '#f97316', badge: 'Cashback' },
            { id: 'mobile-legends-b', name: 'Mobile Legends B', monogram: 'MB', background: 'linear-gradient(145deg, #172554 0%, #2563eb 44%, #60a5fa 100%)', accent: '#2563eb', badge: 'Populer' },
            { id: 'resident-evil', name: 'Resident Evil Requiem CD Keys', monogram: 'RE', background: 'linear-gradient(145deg, #111827 0%, #52525b 48%, #f8fafc 100%)', accent: '#ef4444' },
            { id: 'bigo-live', name: 'Bigo Live Diamonds', monogram: 'BG', background: 'linear-gradient(145deg, #ecfeff 0%, #f8fafc 45%, #bae6fd 100%)', accent: '#22d3ee' },
            { id: 'free-fire', name: 'Free Fire', monogram: 'FF', background: 'linear-gradient(145deg, #0f172a 0%, #1d4ed8 45%, #f43f5e 100%)', accent: '#f43f5e', badge: 'Cashback' },
            { id: 'monster-hunter', name: 'Monster Hunter Stories 3: Twisted Reflection - CD Key', monogram: 'MH', background: 'linear-gradient(145deg, #fefce8 0%, #f8fafc 44%, #94a3b8 100%)', accent: '#eab308' },
            { id: 'coins-rewards', name: 'Lapakgaming Coins Rewards', monogram: 'CR', background: 'linear-gradient(145deg, #4338ca 0%, #7c3aed 40%, #facc15 100%)', accent: '#facc15' },
            { id: 'pragmata', name: '[Pre Order] PRAGMATA CD Key', monogram: 'PG', background: 'linear-gradient(145deg, #fafaf9 0%, #d6d3d1 42%, #1f2937 100%)', accent: '#94a3b8' },
            { id: 'rainbow-six-mobile', name: 'Rainbow Six Mobile', monogram: 'R6', background: 'linear-gradient(145deg, #0f766e 0%, #164e63 40%, #e2e8f0 100%)', accent: '#06b6d4' },
            { id: 'genshin-impact', name: 'Genshin Impact', monogram: 'GI', background: 'linear-gradient(145deg, #172554 0%, #1e3a8a 46%, #0f172a 100%)', accent: '#facc15', badge: 'Cashback' },
            { id: 'mobile-legends-gift-skin', name: 'Mobile Legends Gift Skin', monogram: 'GS', background: 'linear-gradient(145deg, #1e1b4b 0%, #7c3aed 38%, #f472b6 100%)', accent: '#f472b6', badge: 'Gift' },
            { id: 'mobile-legends-global', name: 'Mobile Legends Global', monogram: 'MG', background: 'linear-gradient(145deg, #1e3a8a 0%, #2563eb 42%, #93c5fd 100%)', accent: '#60a5fa', badge: 'Global' },
        ],
    },
    {
        id: 'release',
        title: 'Baru Rilis',
        icon: Sparkles,
        items: [
            { id: 'zenless-zone-zero', name: 'Zenless Zone Zero', monogram: 'ZZ', background: 'linear-gradient(145deg, #111827 0%, #ea580c 44%, #facc15 100%)', accent: '#f97316', badge: 'New' },
            { id: 'delta-force', name: 'Delta Force', monogram: 'DF', background: 'linear-gradient(145deg, #0f172a 0%, #334155 44%, #e2e8f0 100%)', accent: '#94a3b8' },
            { id: 'crystal-of-atlan', name: 'Crystal of Atlan', monogram: 'CA', background: 'linear-gradient(145deg, #dbeafe 0%, #60a5fa 44%, #4338ca 100%)', accent: '#60a5fa' },
            { id: 'solo-leveling-arise', name: 'Solo Leveling: Arise', monogram: 'SL', background: 'linear-gradient(145deg, #020617 0%, #1d4ed8 44%, #38bdf8 100%)', accent: '#38bdf8', badge: 'Hot' },
            { id: 'wuthering-waves', name: 'Wuthering Waves', monogram: 'WW', background: 'linear-gradient(145deg, #f8fafc 0%, #d4d4d8 42%, #18181b 100%)', accent: '#a1a1aa' },
        ],
    },
    {
        id: 'login',
        title: 'Top Up Login',
        icon: Gamepad2,
        items: [
            { id: 'mobile-legends-login', name: 'Mobile Legends Login', monogram: 'ML', background: 'linear-gradient(145deg, #172554 0%, #2563eb 44%, #f59e0b 100%)', accent: '#f59e0b' },
            { id: 'free-fire-login', name: 'Free Fire Login', monogram: 'FF', background: 'linear-gradient(145deg, #0f172a 0%, #1d4ed8 45%, #f43f5e 100%)', accent: '#f43f5e' },
            { id: 'hok-login', name: 'Honor of Kings Login', monogram: 'HK', background: 'linear-gradient(145deg, #1e1b4b 0%, #7c3aed 42%, #f59e0b 100%)', accent: '#a78bfa', badge: 'Login' },
            { id: 'pubgm-login', name: 'PUBG Mobile Login', monogram: 'PM', background: 'linear-gradient(145deg, #082f49 0%, #0369a1 44%, #facc15 100%)', accent: '#38bdf8' },
            { id: 'blood-strike-login', name: 'Blood Strike Login', monogram: 'BS', background: 'linear-gradient(145deg, #1f2937 0%, #dc2626 42%, #fb7185 100%)', accent: '#ef4444' },
        ],
    },
    {
        id: 'voucher',
        title: 'Voucher',
        icon: Ticket,
        items: [
            { id: 'steam-wallet', name: 'Steam Wallet IDR', monogram: 'ST', background: 'linear-gradient(145deg, #0f172a 0%, #1d4ed8 42%, #38bdf8 100%)', accent: '#38bdf8' },
            { id: 'google-play', name: 'Google Play Gift Code', monogram: 'GP', background: 'linear-gradient(145deg, #f8fafc 0%, #22c55e 44%, #3b82f6 100%)', accent: '#22c55e' },
            { id: 'apple-store', name: 'Apple App Store', monogram: 'AP', background: 'linear-gradient(145deg, #f8fafc 0%, #d4d4d8 44%, #111827 100%)', accent: '#94a3b8' },
            { id: 'razer-gold', name: 'Razer Gold', monogram: 'RG', background: 'linear-gradient(145deg, #052e16 0%, #16a34a 42%, #86efac 100%)', accent: '#16a34a', badge: 'Value' },
            { id: 'ps-store', name: 'PlayStation Store', monogram: 'PS', background: 'linear-gradient(145deg, #172554 0%, #2563eb 44%, #93c5fd 100%)', accent: '#2563eb' },
        ],
    },
    {
        id: 'pulsa',
        title: 'Pulsa',
        icon: Smartphone,
        items: [
            { id: 'telkomsel', name: 'Telkomsel Pulsa', monogram: 'TS', background: 'linear-gradient(145deg, #991b1b 0%, #dc2626 40%, #f97316 100%)', accent: '#f97316' },
            { id: 'indosat', name: 'Indosat IM3', monogram: 'IM', background: 'linear-gradient(145deg, #fef3c7 0%, #f59e0b 44%, #7c2d12 100%)', accent: '#f59e0b' },
            { id: 'xl', name: 'XL Axiata', monogram: 'XL', background: 'linear-gradient(145deg, #1e1b4b 0%, #2563eb 42%, #a855f7 100%)', accent: '#2563eb' },
            { id: 'tri', name: 'Tri Indonesia', monogram: 'TR', background: 'linear-gradient(145deg, #ecfeff 0%, #22d3ee 44%, #0891b2 100%)', accent: '#06b6d4' },
            { id: 'smartfren', name: 'Smartfren', monogram: 'SF', background: 'linear-gradient(145deg, #fdf2f8 0%, #ec4899 44%, #7c3aed 100%)', accent: '#ec4899' },
        ],
    },
    {
        id: 'entertainment',
        title: 'Entertainment',
        icon: Tv,
        items: [
            { id: 'spotify', name: 'Spotify Premium', monogram: 'SP', background: 'linear-gradient(145deg, #052e16 0%, #16a34a 42%, #86efac 100%)', accent: '#22c55e' },
            { id: 'netflix', name: 'Netflix Gift Card', monogram: 'NF', background: 'linear-gradient(145deg, #111827 0%, #b91c1c 42%, #ef4444 100%)', accent: '#ef4444', badge: 'Promo' },
            { id: 'youtube-premium', name: 'YouTube Premium', monogram: 'YT', background: 'linear-gradient(145deg, #fef2f2 0%, #ef4444 44%, #7f1d1d 100%)', accent: '#ef4444' },
            { id: 'viu', name: 'Viu Premium', monogram: 'VI', background: 'linear-gradient(145deg, #fef3c7 0%, #f59e0b 42%, #ca8a04 100%)', accent: '#eab308' },
            { id: 'vidio', name: 'Vidio Premier', monogram: 'VD', background: 'linear-gradient(145deg, #fee2e2 0%, #fb7185 42%, #be123c 100%)', accent: '#fb7185' },
        ],
    },
    {
        id: 'instant',
        title: 'Top Up Langsung',
        icon: Zap,
        items: [
            { id: 'afk-journey', name: 'AFK Journey', monogram: 'AF', background: 'linear-gradient(145deg, #1e3a8a 0%, #312e81 42%, #e2e8f0 100%)', accent: '#60a5fa' },
            { id: 'hero-reborn', name: 'Hero Reborn Eternal Pact - Razer Link', monogram: 'HR', background: 'linear-gradient(145deg, #38bdf8 0%, #4ade80 44%, #d9f99d 100%)', accent: '#14b8a6' },
            { id: 'isekai-feast', name: 'Isekai Feast: Tales of Recipes', monogram: 'IF', background: 'linear-gradient(145deg, #f8fafc 0%, #f9a8d4 44%, #a78bfa 100%)', accent: '#fb7185' },
            { id: 'free-fire-global', name: 'Free Fire Global', monogram: 'FG', background: 'linear-gradient(145deg, #0f172a 0%, #0ea5e9 44%, #fde047 100%)', accent: '#0ea5e9' },
            { id: 'ace-racer', name: 'Ace Racer', monogram: 'AR', background: 'linear-gradient(145deg, #111827 0%, #475569 44%, #f8fafc 100%)', accent: '#94a3b8' },
            { id: 'wuxia-rising', name: 'Wuxia Rising Star - Razer Link', monogram: 'WR', background: 'linear-gradient(145deg, #bfdbfe 0%, #fef3c7 44%, #fb7185 100%)', accent: '#f59e0b' },
            { id: 'astra-knights', name: 'Astra: Knights of Veda', monogram: 'AK', background: 'linear-gradient(145deg, #f9a8d4 0%, #c084fc 44%, #312e81 100%)', accent: '#c084fc' },
            { id: 'honor-of-kings', name: 'Honor of Kings', monogram: 'HK', background: 'linear-gradient(145deg, #172554 0%, #2563eb 44%, #f59e0b 100%)', accent: '#f59e0b', badge: 'Cashback' },
            { id: 'eggy-party', name: 'Eggy Party', monogram: 'EP', background: 'linear-gradient(145deg, #60a5fa 0%, #818cf8 44%, #fde047 100%)', accent: '#fde047' },
            { id: 'arena-of-valor', name: 'Arena of Valor', monogram: 'AOV', background: 'linear-gradient(145deg, #7c2d12 0%, #b91c1c 44%, #111827 100%)', accent: '#fb7185' },
        ],
    },
];

export const productSections: ProductSection[] = baseProductSections.map((section) => ({
    ...section,
    items: section.items.map((item) => withFullProductArtwork(item)),
}));

export const vipaymentBackedProductIds = [
    'mobile-legends-a',
    'mobile-legends-b',
    'mobile-legends-gift-skin',
    'mobile-legends-global',
    'bigo-live',
    'free-fire',
    'genshin-impact',
    'zenless-zone-zero',
    'delta-force',
    'crystal-of-atlan',
    'solo-leveling-arise',
    'wuthering-waves',
    'mobile-legends-login',
    'free-fire-login',
    'hok-login',
    'pubgm-login',
    'blood-strike-login',
    'steam-wallet',
    'google-play',
    'apple-store',
    'razer-gold',
    'ps-store',
    'telkomsel',
    'indosat',
    'xl',
    'tri',
    'smartfren',
    'afk-journey',
    'hero-reborn',
    'isekai-feast',
    'free-fire-global',
    'ace-racer',
    'wuxia-rising',
    'astra-knights',
    'honor-of-kings',
    'eggy-party',
    'arena-of-valor',
];

export const publicCatalogHiddenKeywords = ['lapakcoin', 'lapakgaming', 'spotify', 'netflix'];

export const isPublicCatalogProductVisible = (product: Pick<ProductIdentity, 'id' | 'name'>) => {
    const haystack = `${product.id} ${product.name}`.toLowerCase();

    return !publicCatalogHiddenKeywords.some((keyword) => haystack.includes(keyword));
};

const homeProductIds = new Set(vipaymentBackedProductIds);

const homeProductSections = productSections
    .map((section) => ({
        ...section,
        items: section.items.filter((item) => homeProductIds.has(item.id)),
    }))
    .filter((section) => section.items.length > 0);

export const orderedProductSections = categoryChips
    .map((chip) => homeProductSections.find((section) => section.id === chip.id))
    .filter((section): section is ProductSection => Boolean(section));

export const catalogProducts: CatalogProduct[] = productSections.flatMap((section) =>
    section.items.map((item) => ({
        ...item,
        categoryId: section.id,
        categoryTitle: section.title,
        categoryIcon: section.icon,
    })),
);

export const findCatalogProduct = (productId: string) => catalogProducts.find((product) => product.id === productId) ?? null;

const escapeRegExp = (value: string) => value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

export const getProductFamilyRule = (product: ProductIdentity | null | undefined) =>
    product ? productFamilyRules.find((rule) => rule.match(product)) ?? null : null;

export const getProductFamilyVariantLabel = (product: ProductIdentity, familyTitle: string) => {
    let nextLabel = product.name.replace(new RegExp(`^${escapeRegExp(familyTitle)}\\s*`, 'i'), '').trim();

    while (['-', ':', '|', '/'].includes(nextLabel.charAt(0))) {
        nextLabel = nextLabel.slice(1).trimStart();
    }

    return nextLabel || 'Utama';
};

export const sortProductFamilyProducts = <T extends ProductIdentity>(products: T[], rule?: ProductFamilyRule | null) => {
    if (!rule?.variantOrder?.length) {
        return [...products].sort((left, right) => left.name.localeCompare(right.name, 'id-ID'));
    }

    const variantRank = new Map(rule.variantOrder.map((label, index) => [label.toLowerCase(), index] as const));

    return [...products].sort((left, right) => {
        const leftRank = variantRank.get(getProductFamilyVariantLabel(left, rule.title).toLowerCase()) ?? Number.POSITIVE_INFINITY;
        const rightRank = variantRank.get(getProductFamilyVariantLabel(right, rule.title).toLowerCase()) ?? Number.POSITIVE_INFINITY;

        if (leftRank !== rightRank) {
            return leftRank - rightRank;
        }

        return left.name.localeCompare(right.name, 'id-ID');
    });
};
