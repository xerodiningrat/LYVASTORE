import { homeProductArtworkMap } from './home-product-artwork-map';
import { vipProductArtworkMap } from './vip-product-artwork-map';

export type ProductArtwork = {
    coverImage: string;
    iconImage: string;
};

export type ProductArtworkOverride = {
    coverImage?: string | null;
    iconImage?: string | null;
};

export type ProductVisual = {
    monogram: string;
    background: string;
    accent: string;
};

const mobileLegendsPublicIcon = '/icon/Gemini_Generated_Image_lcdryjlcdryjlcdr.png';
const pubgMobilePublicIcon = '/icon/Gemini_Generated_Image_sl9zufsl9zufsl9z.png';
const freeFirePublicIcon = '/icon/Gemini_Generated_Image_46l36e46l36e46l3.png';
const genshinImpactPublicIcon = '/icon/Gemini_Generated_Image_ylg7vnylg7vnylg7.png';
const robloxLoginPublicIcon = '/icon/Gemini_Generated_Image_s47vdes47vdes47v.png';
const chatGptPublicIcon = '/icon/Gemini_Generated_Image_nyj630nyj630nyj6.png';
const capCutPublicIcon = '/icon/Gemini_Generated_Image_yn91rtyn91rtyn91.png';
const zepetoPublicIcon = '/icon/Gemini_Generated_Image_a2jgbqa2jgbqa2jg.png';

const customArtworkMap: Record<string, ProductArtwork> = {
    'mobile-legends-a': {
        coverImage: '/0cc60e46d433f8cf76392556373ea3fd.jpg',
        iconImage: mobileLegendsPublicIcon,
    },
    'mobile-legends-b': {
        coverImage: '/product-artwork/mobile-legends-b.webp',
        iconImage: mobileLegendsPublicIcon,
    },
    'mobile-legends-gift-skin': {
        coverImage: '/product-artwork/mobile-legends-gift-skin.jpg',
        iconImage: mobileLegendsPublicIcon,
    },
    'mobile-legends-login': {
        coverImage: '/product-artwork/mobile-legends-login.jpg',
        iconImage: mobileLegendsPublicIcon,
    },
    'mobile-legends-global': {
        coverImage: '/product-artwork/mobile-legends-global.jpg',
        iconImage: mobileLegendsPublicIcon,
    },
    'honor-of-kings': {
        coverImage: '/product-artwork/honor-of-kings.png',
        iconImage: '/product-artwork/honor-of-kings.png',
    },
    'vip-game-honor-of-kings': {
        coverImage: '/product-artwork/honor-of-kings.png',
        iconImage: '/product-artwork/honor-of-kings.png',
    },
    'pubgm-login': {
        coverImage: '/product-artwork/pubg-mobile.webp',
        iconImage: pubgMobilePublicIcon,
    },
    'vip-game-pubg-mobile': {
        coverImage: '/product-artwork/pubg-mobile.webp',
        iconImage: pubgMobilePublicIcon,
    },
    'free-fire': {
        coverImage: '/08c437acdaac4adccdc9cef0f0e662dc.jpg',
        iconImage: freeFirePublicIcon,
    },
    'vip-game-free-fire': {
        coverImage: '/08c437acdaac4adccdc9cef0f0e662dc.jpg',
        iconImage: freeFirePublicIcon,
    },
    'free-fire-login': {
        coverImage: '/product-artwork/free-fire-max.webp',
        iconImage: '/product-artwork/free-fire-max.webp',
    },
    'vip-game-free-fire-max': {
        coverImage: '/product-artwork/free-fire-max.webp',
        iconImage: '/product-artwork/free-fire-max.webp',
    },
    'vip-game-mobile-legends-a': {
        coverImage: '/product-artwork/vip-game-mobile-legends-a.png',
        iconImage: mobileLegendsPublicIcon,
    },
    'vip-game-mobile-legends-b': {
        coverImage: '/product-artwork/vip-game-mobile-legends-b.png',
        iconImage: mobileLegendsPublicIcon,
    },
    'vip-game-mobile-legends-brazil': {
        coverImage: '/product-artwork/vip-game-mobile-legends-brazil.png',
        iconImage: mobileLegendsPublicIcon,
    },
    'vip-game-mobile-legends-gift': {
        coverImage: '/product-artwork/vip-game-mobile-legends-gift.png',
        iconImage: mobileLegendsPublicIcon,
    },
    'vip-game-mobile-legends-global': {
        coverImage: '/product-artwork/vip-game-mobile-legends-global.png',
        iconImage: mobileLegendsPublicIcon,
    },
    'vip-game-mobile-legends-malaysia': {
        coverImage: '/product-artwork/vip-game-mobile-legends-malaysia.png',
        iconImage: mobileLegendsPublicIcon,
    },
    'vip-game-mobile-legends-philippines': {
        coverImage: '/product-artwork/vip-game-mobile-legends-philippines.png',
        iconImage: mobileLegendsPublicIcon,
    },
    'vip-game-mobile-legends-russia': {
        coverImage: '/product-artwork/vip-game-mobile-legends-russia.png',
        iconImage: mobileLegendsPublicIcon,
    },
    'vip-game-mobile-legends-singapore': {
        coverImage: '/product-artwork/vip-game-mobile-legends-singapore.png',
        iconImage: mobileLegendsPublicIcon,
    },
    'vip-game-arena-breakout-infinite': {
        coverImage: '/ce9c8dc9823a9480eb1a2abc6880a645.jpg',
        iconImage: '/ce9c8dc9823a9480eb1a2abc6880a645.jpg',
    },
    'vip-game-arena-breakout': {
        coverImage: '/ce9c8dc9823a9480eb1a2abc6880a645.jpg',
        iconImage: '/ce9c8dc9823a9480eb1a2abc6880a645.jpg',
    },
    'vip-game-arena-breakout-infinite-pc': {
        coverImage: '/ce9c8dc9823a9480eb1a2abc6880a645.jpg',
        iconImage: '/ce9c8dc9823a9480eb1a2abc6880a645.jpg',
    },
    'vip-game-magic-chess-go-go': {
        coverImage: '/f04110e5670519859c97c6400dd5341b.jpg',
        iconImage: '/f04110e5670519859c97c6400dd5341b.jpg',
    },
    'vip-game-magic-chess-go-go-global': {
        coverImage: '/f04110e5670519859c97c6400dd5341b.jpg',
        iconImage: '/f04110e5670519859c97c6400dd5341b.jpg',
    },
    'free-fire-global': {
        coverImage: '/product-artwork/free-fire-global.png',
        iconImage: freeFirePublicIcon,
    },
    'vip-game-free-fire-global': {
        coverImage: '/product-artwork/vip-game-free-fire-global.png',
        iconImage: freeFirePublicIcon,
    },
    'genshin-impact': {
        coverImage: '/product-artwork/genshin-impact.png',
        iconImage: genshinImpactPublicIcon,
    },
    'vip-game-genshin-impact': {
        coverImage: '/product-artwork/vip-game-genshin-impact.png',
        iconImage: genshinImpactPublicIcon,
    },
    'vip-game-pubg-mobile-id': {
        coverImage: '/product-artwork/vip-game-pubg-mobile-id.png',
        iconImage: pubgMobilePublicIcon,
    },
    'vip-game-pubg-mobile-global': {
        coverImage: '/product-artwork/vip-game-pubg-mobile-global.png',
        iconImage: pubgMobilePublicIcon,
    },
    'vip-game-chatgpt': {
        coverImage: '/product-artwork/vip-game-chatgpt.png',
        iconImage: chatGptPublicIcon,
    },
    'vip-game-capcut-pro': {
        coverImage: '/product-artwork/vip-game-capcut-pro.png',
        iconImage: capCutPublicIcon,
    },
    'vip-game-zepeto': {
        coverImage: '/product-artwork/vip-game-zepeto.png',
        iconImage: zepetoPublicIcon,
    },
    'vip-game-roblox-via-login': {
        coverImage: '/product-artwork/vip-game-roblox-via-login.png',
        iconImage: robloxLoginPublicIcon,
    },
};

type ArtworkSource = {
    id: string;
    name: string;
    monogram: string;
    background: string;
    accent: string;
};

type ProductVisualSeed = {
    id: string;
    name: string;
    monogram?: string;
    background?: string;
    accent?: string;
};

type ArtworkVariant = 'cover' | 'icon';
type ArtworkMotif = 'diamond' | 'gift' | 'ticket' | 'signal' | 'play' | 'bolt' | 'shield' | 'star' | 'speed' | 'ring' | 'controller';

const fallbackColors = ['#1d4ed8', '#60a5fa', '#c4b5fd'];
const visualPalette = [
    ['linear-gradient(145deg, #172554 0%, #2563eb 44%, #60a5fa 100%)', '#2563eb'],
    ['linear-gradient(145deg, #1e1b4b 0%, #7c3aed 42%, #f472b6 100%)', '#7c3aed'],
    ['linear-gradient(145deg, #052e16 0%, #16a34a 42%, #86efac 100%)', '#16a34a'],
    ['linear-gradient(145deg, #111827 0%, #7c2d12 44%, #f97316 100%)', '#f97316'],
    ['linear-gradient(145deg, #0f172a 0%, #1d4ed8 45%, #f43f5e 100%)', '#f43f5e'],
    ['linear-gradient(145deg, #f8fafc 0%, #0ea5e9 42%, #2563eb 100%)', '#0ea5e9'],
    ['linear-gradient(145deg, #111827 0%, #475569 44%, #cbd5e1 100%)', '#64748b'],
    ['linear-gradient(145deg, #fef3c7 0%, #f59e0b 42%, #7c2d12 100%)', '#f59e0b'],
] as const;

const extractGradientColors = (background: string) => background.match(/#[0-9a-fA-F]{6}/g) ?? fallbackColors;

const hexToRgb = (hex: string) => {
    const normalized = hex.replace('#', '');
    const safeHex =
        normalized.length === 3
            ? normalized
                  .split('')
                  .map((character) => `${character}${character}`)
                  .join('')
            : normalized;
    const value = Number.parseInt(safeHex, 16);

    return {
        r: (value >> 16) & 255,
        g: (value >> 8) & 255,
        b: value & 255,
    };
};

const withOpacity = (hex: string, opacity: number) => {
    const { r, g, b } = hexToRgb(hex);

    return `rgba(${r}, ${g}, ${b}, ${opacity})`;
};

const toDataUri = (svg: string) => `data:image/svg+xml;charset=UTF-8,${encodeURIComponent(svg)}`;

const escapeSvgText = (value: string) =>
    value.replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;').replaceAll("'", '&apos;');

const buildMonogram = (value: string) => {
    const words = value
        .replace(/[^\p{L}\p{N}\s]/gu, ' ')
        .split(/\s+/)
        .filter(Boolean);

    if (words.length === 0) {
        return 'VP';
    }

    if (words.length === 1) {
        return words[0].slice(0, 2).toUpperCase();
    }

    return `${words[0][0] ?? ''}${words[1][0] ?? ''}`.toUpperCase();
};

const resolveMotif = (product: ArtworkSource): ArtworkMotif => {
    const id = product.id.toLowerCase();
    const name = product.name.toLowerCase();

    if (id.includes('gift') || name.includes('gift')) {
        return 'gift';
    }

    if (id.includes('telkomsel') || id.includes('indosat') || id.includes('smartfren') || id === 'xl' || id === 'tri') {
        return 'signal';
    }

    if (
        id.includes('steam') ||
        id.includes('wallet') ||
        id.includes('voucher') ||
        id.includes('google-play') ||
        id.includes('apple-store') ||
        id.includes('ps-store')
    ) {
        return 'ticket';
    }

    if (id.includes('spotify') || id.includes('netflix') || id.includes('youtube') || id.includes('viu') || id.includes('vidio')) {
        return 'play';
    }

    if (id.includes('bigo')) {
        return 'ring';
    }

    if (id.includes('free-fire') || id.includes('razer') || id.includes('blood-strike')) {
        return 'bolt';
    }

    if (id.includes('genshin') || id.includes('zenless') || id.includes('wuthering') || id.includes('isekai')) {
        return 'star';
    }

    if (id.includes('delta-force') || id.includes('pubgm') || id.includes('honor-of-kings') || id.includes('hok') || id.includes('monster-hunter')) {
        return 'shield';
    }

    if (id.includes('ace-racer')) {
        return 'speed';
    }

    if (id.includes('mobile-legends') || id.includes('afk') || id.includes('eggy-party')) {
        return 'diamond';
    }

    if (id.includes('login') || id.includes('pragmata') || id.includes('resident-evil')) {
        return 'controller';
    }

    return 'diamond';
};

const motifMarkup = (motif: ArtworkMotif, accent: string, secondary: string, size: 'large' | 'small') => {
    const stroke = size === 'large' ? 10 : 8;
    const glow = size === 'large' ? 0.24 : 0.2;

    if (motif === 'diamond') {
        return `
            <g transform="translate(0 4)">
                <path d="M0 -72 L60 -12 L0 72 L-60 -12 Z" fill="${withOpacity(accent, glow)}" stroke="rgba(255,255,255,0.95)" stroke-width="${stroke}" stroke-linejoin="round" />
                <path d="M0 -48 L34 -6 L0 42 L-34 -6 Z" fill="rgba(255,255,255,0.24)" />
            </g>
        `;
    }

    if (motif === 'gift') {
        return `
            <g transform="translate(0 2)">
                <rect x="-58" y="-28" width="116" height="82" rx="22" fill="${withOpacity(secondary, glow)}" stroke="rgba(255,255,255,0.95)" stroke-width="${stroke}" />
                <rect x="-12" y="-28" width="24" height="82" rx="12" fill="rgba(255,255,255,0.88)" />
                <rect x="-58" y="-4" width="116" height="22" rx="11" fill="rgba(255,255,255,0.18)" />
                <path d="M-10 -28 C-34 -52 -12 -72 8 -62 C24 -54 20 -34 0 -24" fill="none" stroke="rgba(255,255,255,0.95)" stroke-width="${stroke - 2}" stroke-linecap="round" />
                <path d="M10 -28 C34 -52 12 -72 -8 -62 C-24 -54 -20 -34 0 -24" fill="none" stroke="rgba(255,255,255,0.95)" stroke-width="${stroke - 2}" stroke-linecap="round" />
            </g>
        `;
    }

    if (motif === 'ticket') {
        return `
            <g transform="translate(0 2) rotate(-8)">
                <path d="M-70 -34 H44 C56 -34 66 -24 66 -12 C66 -6 70 -1 76 0 C70 1 66 6 66 12 C66 24 56 34 44 34 H-70 C-82 34 -92 24 -92 12 C-92 6 -96 1 -102 0 C-96 -1 -92 -6 -92 -12 C-92 -24 -82 -34 -70 -34 Z" fill="${withOpacity(accent, glow)}" stroke="rgba(255,255,255,0.95)" stroke-width="${stroke}" />
                <path d="M-12 -24 V24" stroke="rgba(255,255,255,0.82)" stroke-width="${stroke - 4}" stroke-linecap="round" stroke-dasharray="8 9" />
                <circle cx="-48" cy="0" r="12" fill="rgba(255,255,255,0.9)" />
            </g>
        `;
    }

    if (motif === 'signal') {
        return `
            <g transform="translate(0 8)">
                <rect x="-54" y="4" width="20" height="38" rx="10" fill="rgba(255,255,255,0.86)" />
                <rect x="-24" y="-16" width="20" height="58" rx="10" fill="${withOpacity(accent, glow + 0.08)}" stroke="rgba(255,255,255,0.92)" stroke-width="${stroke - 4}" />
                <rect x="6" y="-40" width="20" height="82" rx="10" fill="rgba(255,255,255,0.9)" />
                <rect x="36" y="-64" width="20" height="106" rx="10" fill="${withOpacity(secondary, glow + 0.08)}" stroke="rgba(255,255,255,0.92)" stroke-width="${stroke - 4}" />
            </g>
        `;
    }

    if (motif === 'play') {
        return `
            <g transform="translate(0 2)">
                <rect x="-62" y="-62" width="124" height="124" rx="34" fill="${withOpacity(accent, glow)}" stroke="rgba(255,255,255,0.95)" stroke-width="${stroke}" />
                <path d="M-12 -24 L34 0 L-12 24 Z" fill="rgba(255,255,255,0.96)" />
            </g>
        `;
    }

    if (motif === 'bolt') {
        return `
            <g transform="translate(0 2)">
                <path d="M20 -76 L-8 -16 H24 L-18 76 L8 12 H-24 Z" fill="${withOpacity(accent, glow + 0.12)}" stroke="rgba(255,255,255,0.95)" stroke-width="${stroke}" stroke-linejoin="round" />
            </g>
        `;
    }

    if (motif === 'shield') {
        return `
            <g transform="translate(0 2)">
                <path d="M0 -78 L62 -50 V-6 C62 38 30 72 0 86 C-30 72 -62 38 -62 -6 V-50 Z" fill="${withOpacity(accent, glow)}" stroke="rgba(255,255,255,0.95)" stroke-width="${stroke}" stroke-linejoin="round" />
                <path d="M0 -46 V48" stroke="rgba(255,255,255,0.86)" stroke-width="${stroke - 2}" stroke-linecap="round" />
                <path d="M-28 -2 H28" stroke="rgba(255,255,255,0.86)" stroke-width="${stroke - 2}" stroke-linecap="round" />
            </g>
        `;
    }

    if (motif === 'star') {
        return `
            <g transform="translate(0 0)">
                <path d="M0 -82 L18 -24 L78 -24 L30 12 L48 72 L0 34 L-48 72 L-30 12 L-78 -24 L-18 -24 Z" fill="${withOpacity(accent, glow + 0.06)}" stroke="rgba(255,255,255,0.95)" stroke-width="${stroke}" stroke-linejoin="round" />
            </g>
        `;
    }

    if (motif === 'speed') {
        return `
            <g transform="translate(0 6)">
                <path d="M-84 8 H10" stroke="rgba(255,255,255,0.96)" stroke-width="${stroke}" stroke-linecap="round" />
                <path d="M-68 -22 H36" stroke="${withOpacity(accent, glow + 0.08)}" stroke-width="${stroke}" stroke-linecap="round" />
                <path d="M-42 -52 H64" stroke="rgba(255,255,255,0.72)" stroke-width="${stroke - 2}" stroke-linecap="round" />
                <circle cx="30" cy="20" r="34" fill="${withOpacity(secondary, glow + 0.06)}" stroke="rgba(255,255,255,0.94)" stroke-width="${stroke - 2}" />
            </g>
        `;
    }

    if (motif === 'ring') {
        return `
            <g transform="translate(0 0)">
                <circle cx="0" cy="0" r="56" fill="none" stroke="rgba(255,255,255,0.96)" stroke-width="${stroke}" />
                <circle cx="0" cy="0" r="30" fill="${withOpacity(accent, glow + 0.06)}" />
                <circle cx="54" cy="-44" r="14" fill="${withOpacity(secondary, glow + 0.12)}" stroke="rgba(255,255,255,0.92)" stroke-width="${stroke - 4}" />
            </g>
        `;
    }

    return `
        <g transform="translate(0 2)">
            <path d="M-78 0 C-78 -28 -58 -44 -30 -44 H-12 C2 -60 24 -68 46 -68 C68 -68 86 -56 86 -34 C86 -20 80 -8 68 0 C80 8 86 20 86 34 C86 56 68 68 46 68 C22 68 2 58 -12 42 H-30 C-58 42 -78 28 -78 0 Z" fill="${withOpacity(accent, glow)}" stroke="rgba(255,255,255,0.95)" stroke-width="${stroke}" stroke-linejoin="round" />
            <circle cx="-28" cy="-6" r="10" fill="rgba(255,255,255,0.94)" />
            <circle cx="18" cy="10" r="10" fill="rgba(255,255,255,0.94)" />
            <path d="M-2 -14 H36" stroke="rgba(255,255,255,0.9)" stroke-width="${stroke - 3}" stroke-linecap="round" />
        </g>
    `;
};

const buildArtworkSvg = (product: ArtworkSource, variant: ArtworkVariant) => {
    const [primary, secondary = product.accent, tertiary = fallbackColors[2]] = extractGradientColors(product.background);
    const motif = resolveMotif(product);
    const monogram = escapeSvgText(product.monogram);
    const width = variant === 'cover' ? 480 : 160;
    const height = variant === 'cover' ? 640 : 160;
    const haloSize = variant === 'cover' ? 220 : 74;
    const motifScale = variant === 'cover' ? '1' : '0.5';
    const motifY = variant === 'cover' ? '250' : '82';
    const monoX = variant === 'cover' ? '338' : '80';
    const monoY = variant === 'cover' ? '566' : '132';
    const monoSize = variant === 'cover' ? '136' : '46';
    const monoOpacity = variant === 'cover' ? '0.12' : '0.2';

    return `
        <svg xmlns="http://www.w3.org/2000/svg" width="${width}" height="${height}" viewBox="0 0 ${width} ${height}" fill="none">
            <defs>
                <radialGradient id="glow-${product.id}-${variant}" cx="0" cy="0" r="1" gradientUnits="userSpaceOnUse" gradientTransform="translate(${width / 2} ${variant === 'cover' ? 240 : 80}) rotate(90) scale(${haloSize})">
                    <stop offset="0" stop-color="${withOpacity(secondary, 0.28)}" />
                    <stop offset="0.62" stop-color="${withOpacity(primary, 0.16)}" />
                    <stop offset="1" stop-color="${withOpacity(tertiary, 0)}" />
                </radialGradient>
                <linearGradient id="mono-${product.id}-${variant}" x1="0" y1="0" x2="${width}" y2="${height}" gradientUnits="userSpaceOnUse">
                    <stop offset="0" stop-color="rgba(255,255,255,0.94)" />
                    <stop offset="1" stop-color="${withOpacity(secondary, 0.82)}" />
                </linearGradient>
            </defs>
            <circle cx="${width / 2}" cy="${variant === 'cover' ? 242 : 78}" r="${haloSize}" fill="url(#glow-${product.id}-${variant})" />
            <g transform="translate(${width / 2} ${motifY}) scale(${motifScale})">
                ${motifMarkup(motif, product.accent, secondary, variant === 'cover' ? 'large' : 'small')}
            </g>
            <text x="${monoX}" y="${monoY}" font-family="Arial, sans-serif" font-size="${monoSize}" font-weight="800" letter-spacing="0.06em" fill="url(#mono-${product.id}-${variant})" opacity="${monoOpacity}">
                ${monogram}
            </text>
        </svg>
    `;
};

export const withProductArtwork = <T extends ArtworkSource>(product: T): T & ProductArtwork => ({
    ...product,
    coverImage:
        customArtworkMap[product.id]?.coverImage ??
        homeProductArtworkMap[product.id]?.coverImage ??
        vipProductArtworkMap[product.id]?.coverImage ??
        toDataUri(buildArtworkSvg(product, 'cover')),
    iconImage:
        customArtworkMap[product.id]?.iconImage ??
        homeProductArtworkMap[product.id]?.iconImage ??
        vipProductArtworkMap[product.id]?.iconImage ??
        toDataUri(buildArtworkSvg(product, 'icon')),
});

export const withProductVisuals = <T extends ProductVisualSeed>(product: T): T & ProductVisual => {
    const paletteIndex =
        Math.abs(product.id.split('').reduce((carry, character, index) => carry + character.charCodeAt(0) * (index + 3), 0)) % visualPalette.length;
    const [background, accent] = visualPalette[paletteIndex];

    return {
        ...product,
        monogram: product.monogram ?? buildMonogram(product.name),
        background: product.background ?? background,
        accent: product.accent ?? accent,
    };
};

export const withFullProductArtwork = <T extends ProductVisualSeed>(product: T): T & ProductVisual & ProductArtwork =>
    withProductArtwork(withProductVisuals(product));

export const applyProductArtworkOverride = <T extends ProductArtwork>(product: T, override?: ProductArtworkOverride | null): T => ({
    ...product,
    coverImage: override?.coverImage || product.coverImage,
    iconImage: override?.iconImage || override?.coverImage || product.iconImage,
});
