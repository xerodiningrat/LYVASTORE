<script setup lang="ts">
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import {
    AlertTriangle,
    ArrowUpRight,
    BadgeDollarSign,
    Boxes,
    House,
    ImagePlus,
    LayoutGrid,
    Package2,
    ReceiptText,
    Scissors,
    ShieldAlert,
    ShieldCheck,
    Sparkles,
    Ticket,
    TrendingUp,
    UserRound,
    Users,
} from 'lucide-vue-next';
import { computed } from 'vue';

const page = usePage<SharedData>();
const branding = computed(() => page.props.adminPanel.branding);

const mainNavItems: NavItem[] = [
    {
        title: 'Ringkasan',
        href: '/dashboard',
        icon: LayoutGrid,
    },
    {
        title: 'Setting Produk',
        href: '/dashboard/produk',
        icon: Package2,
    },
    {
        title: 'Provider Kosong',
        href: '/dashboard/provider-kosong',
        icon: AlertTriangle,
    },
    {
        title: 'Kode Promo',
        href: '/dashboard/promo',
        icon: Ticket,
    },
    {
        title: 'Stok Manual',
        href: '/dashboard/stok-manual',
        icon: Boxes,
    },
    {
        title: 'Keuangan',
        href: '/dashboard/keuangan',
        icon: TrendingUp,
    },
    {
        title: 'Setting Margin',
        href: '/dashboard/margin',
        icon: BadgeDollarSign,
    },
    {
        title: 'Branding Sidebar',
        href: '/dashboard/branding',
        icon: ImagePlus,
    },
    {
        title: 'Hapus Background',
        href: '/dashboard/hapus-background',
        icon: Scissors,
    },
    {
        title: 'Monitor Transaksi',
        href: '/dashboard/transaksi',
        icon: ReceiptText,
    },
    {
        title: 'Security Monitor',
        href: '/dashboard/security',
        icon: ShieldAlert,
    },
    {
        title: 'Affiliate Monitor',
        href: '/dashboard/affiliate',
        icon: Users,
    },
];

const utilityNavItems: NavItem[] = [
    {
        title: 'Profil Saya',
        href: '/profil',
        icon: UserRound,
    },
    {
        title: 'Keamanan Akun',
        href: '/keamanan-akun',
        icon: ShieldCheck,
    },
    {
        title: 'Home Website',
        href: '/',
        icon: House,
    },
];
</script>

<template>
    <Sidebar
        collapsible="icon"
        variant="inset"
        class="admin-sidebar border-r border-sidebar-border/80 shadow-[inset_-1px_0_0_rgba(148,163,184,0.12)]"
    >
        <SidebarHeader class="px-3 pb-2 pt-3">
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton
                        size="lg"
                        as-child
                        class="h-auto rounded-[28px] border border-white/80 bg-white/75 px-4 py-4 shadow-[0_20px_48px_rgba(148,163,184,0.16)] backdrop-blur-sm transition-all duration-200 hover:bg-white hover:shadow-[0_22px_52px_rgba(148,163,184,0.2)]"
                    >
                        <Link href="/dashboard" class="flex items-center gap-3">
                            <div
                                class="flex size-12 items-center justify-center overflow-hidden rounded-[22px] bg-slate-100 shadow-[0_14px_30px_rgba(148,163,184,0.18)] ring-1 ring-slate-200"
                            >
                                <img v-if="branding.logoUrl" :src="branding.logoUrl" :alt="branding.title" class="size-full object-cover" />
                                <img v-else src="/brand/lyva-mascot-mark.png" alt="Lyva Admin" class="size-8 object-contain" />
                            </div>
                            <div class="min-w-0 text-left group-data-[collapsible=icon]:hidden">
                                <div class="truncate text-[0.95rem] font-black uppercase tracking-[0.24em] text-slate-950">
                                    {{ branding.title }}
                                </div>
                                <div class="truncate pt-1 text-[0.72rem] font-semibold uppercase tracking-[0.16em] text-slate-500">
                                    {{ branding.tagline }}
                                </div>
                            </div>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent class="flex min-h-0 flex-1 flex-col px-2 pb-2 pt-1">
            <div class="space-y-4">
                <NavMain
                    :items="mainNavItems"
                    label="Control Panel"
                    button-size="lg"
                    group-class="px-3 py-2"
                    label-class="px-3 pb-3 pt-1 text-[0.72rem] font-bold uppercase tracking-[0.26em] text-slate-400"
                    menu-class="gap-2.5"
                    item-class="overflow-visible"
                    button-class="rounded-[22px] border border-transparent bg-transparent px-4 text-[15.5px] font-semibold text-slate-700 transition-all duration-200 hover:border-slate-200/90 hover:bg-white/90 hover:text-slate-950 hover:shadow-[0_14px_34px_rgba(148,163,184,0.16)] data-[active=true]:border-slate-200/90 data-[active=true]:bg-white data-[active=true]:text-slate-950 data-[active=true]:shadow-[0_18px_40px_rgba(148,163,184,0.22)] [&>svg]:size-[19px] [&>span]:text-[15.5px] [&>span]:font-semibold [&>span]:tracking-[-0.01em]"
                />

                <NavMain
                    :items="utilityNavItems"
                    label="Akun & Akses"
                    button-size="default"
                    group-class="px-3 py-0"
                    label-class="px-1 pb-3 pt-1 text-[0.68rem] font-black uppercase tracking-[0.24em] text-slate-400"
                    menu-class="gap-2"
                    item-class="overflow-visible"
                    button-class="rounded-[20px] border border-slate-200/70 bg-white/72 px-4 text-[14.5px] font-semibold text-slate-700 shadow-[0_10px_28px_rgba(148,163,184,0.08)] transition-all duration-200 hover:border-slate-300/90 hover:bg-white hover:text-slate-950 hover:shadow-[0_16px_34px_rgba(148,163,184,0.14)] data-[active=true]:border-slate-300/90 data-[active=true]:bg-white data-[active=true]:text-slate-950 data-[active=true]:shadow-[0_16px_34px_rgba(148,163,184,0.16)] [&>svg]:size-[18px] [&>span]:tracking-[-0.01em]"
                />
            </div>

            <div
                class="mx-3 mt-auto overflow-hidden rounded-[28px] border border-white/80 bg-[radial-gradient(circle_at_top_left,rgba(99,102,241,0.12),transparent_40%),linear-gradient(180deg,rgba(255,255,255,0.94),rgba(248,250,252,0.92))] p-4 shadow-[0_18px_40px_rgba(148,163,184,0.14)] group-data-[collapsible=icon]:hidden"
            >
                <div class="flex items-center gap-2">
                    <span
                        class="inline-flex items-center gap-2 rounded-full border border-indigo-200 bg-indigo-50 px-2.5 py-1 text-[0.62rem] font-black uppercase tracking-[0.2em] text-indigo-700"
                    >
                        <Sparkles class="size-3 text-fuchsia-500" />
                        Admin pulse
                    </span>
                </div>

                <p class="mt-3 text-[0.98rem] font-black leading-6 tracking-[-0.02em] text-slate-950">
                    Sidebar dibikin penuh biar kontrol panel terasa nyambung sampai bawah.
                </p>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Semua akses penting tetap kebaca cepat tanpa ada area kosong yang terasa nanggung.
                </p>

                <div class="mt-4 space-y-2.5">
                    <div class="bg-white/82 rounded-[20px] border border-slate-200/80 px-3 py-3">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-[0.58rem] font-black uppercase tracking-[0.18em] text-slate-500">Kontrol inti</p>
                                <p class="mt-1 text-sm font-semibold text-slate-950">Produk, stok, margin</p>
                            </div>
                            <ShieldCheck class="size-4 text-emerald-600" />
                        </div>
                    </div>

                    <div class="bg-white/82 rounded-[20px] border border-slate-200/80 px-3 py-3">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-[0.58rem] font-black uppercase tracking-[0.18em] text-slate-500">Fokus harian</p>
                                <p class="mt-1 text-sm font-semibold text-slate-950">Pantau transaksi & trend</p>
                            </div>
                            <TrendingUp class="size-4 text-sky-600" />
                        </div>
                    </div>
                </div>

                <Link
                    href="/dashboard/transaksi"
                    class="mt-4 inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3.5 py-2 text-sm font-bold text-slate-700 transition hover:border-slate-300 hover:text-slate-950"
                >
                    Buka monitor
                    <ArrowUpRight class="size-4" />
                </Link>
            </div>
        </SidebarContent>

        <SidebarFooter class="border-t border-slate-200/70 px-3 pb-3 pt-2">
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
