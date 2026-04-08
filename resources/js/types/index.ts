import type { PageProps as InertiaPageProps } from '@inertiajs/core';
import type { LucideIcon } from 'lucide-vue-next';

export interface Auth {
    user: User;
    coins?: {
        balance: number;
    };
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon;
    isActive?: boolean;
    external?: boolean;
}

export interface SharedData extends InertiaPageProps {
    [key: string]: unknown;
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    coinProgram?: {
        coinValueRupiah: number;
        maxRewardPercentOfSellingPrice: number;
        rewardShareOfEstimatedProfit: number;
        minimumRewardCoins: number;
        rewardRateLabel: string;
        tiers: Array<{
            max: number | null;
            percent: number;
            fixed: number;
            roundTo: number;
        }>;
    };
    recentPurchases?: RecentPurchaseItem[];
    support?: {
        chatUrl: string;
        chatEndpoint?: string;
        aiEnabled?: boolean;
    };
    flash?: {
        status?: string | null;
    };
    security?: {
        checkoutIntentToken?: string;
        cspNonce?: string;
    };
    promos?: Array<{
        id: string;
        code: string;
        label: string;
        description?: string | null;
        type: 'fixed' | 'percent';
        value: number;
        typeLabel: string;
        minimumSubtotal: number;
        maxDiscount?: number | null;
        productIds: string[];
        startsAt?: string | null;
        expiresAt?: string | null;
    }>;
    unavailableProductIds?: string[];
    adminPanel: {
        branding: {
            title: string;
            tagline: string;
            logoUrl: string | null;
            logoPath?: string | null;
        };
        deployStatus?: {
            deployedAt: string | null;
            deployedAtLabel: string | null;
            mode: string | null;
        };
        productArtworkOverrides: Record<
            string,
            {
                coverImage: string;
                iconImage: string;
            }
        >;
        productDisplayOverrides: Record<
            string,
            {
                name?: string | null;
                categoryTitle?: string | null;
                badge?: string | null;
            }
        >;
        hiddenProductIds: string[];
        productOrderingOverrides: Record<
            string,
            {
                pinned?: boolean;
                sortOrder?: number | null;
            }
        >;
    };
    ziggy: {
        location: string;
        url: string;
        port: null | number;
        defaults: Record<string, unknown>;
        routes: Record<string, string>;
    };
}

export interface User {
    id: number;
    name: string;
    email: string;
    whatsapp_number?: string | null;
    whatsapp_verified_at?: string | null;
    affiliate_status?: string | null;
    affiliate_code?: string | null;
    affiliate_applied_at?: string | null;
    affiliate_approved_at?: string | null;
    avatar?: string | null;
    is_admin?: boolean;
    is_owner?: boolean;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
}

export interface RecentPurchaseItem {
    id: string;
    customerLabel: string;
    productLabel: string;
    amountLabel: string;
    timeLabel: string;
    occurredAt?: string | null;
    statusLabel: string;
    productImage?: string | null;
}

export type BreadcrumbItemType = BreadcrumbItem;
