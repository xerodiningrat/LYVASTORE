<script setup lang="ts">
import UserInfo from '@/components/UserInfo.vue';
import { DropdownMenuGroup, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator } from '@/components/ui/dropdown-menu';
import type { User } from '@/types';
import { Link } from '@inertiajs/vue3';
import { Coins, LayoutGrid, LogOut, Settings, ShieldCheck } from 'lucide-vue-next';

interface Props {
    user: User;
}

defineProps<Props>();
</script>

<template>
    <DropdownMenuLabel class="p-0 font-normal">
        <div class="flex items-center gap-2 rounded-2xl px-3 py-3 text-left text-sm text-slate-900">
            <UserInfo :user="user" :show-email="true" />
        </div>
    </DropdownMenuLabel>
    <DropdownMenuSeparator />
    <DropdownMenuGroup>
        <DropdownMenuItem
            v-if="user.is_admin"
            :as-child="true"
            class="rounded-2xl text-slate-700 focus:bg-slate-100 focus:text-slate-950"
        >
            <Link class="flex w-full items-center font-semibold text-slate-700" href="/dashboard" as="button">
                <LayoutGrid class="mr-2 h-4 w-4" />
                Panel Admin
            </Link>
        </DropdownMenuItem>
        <DropdownMenuItem :as-child="true" class="rounded-2xl text-slate-700 focus:bg-slate-100 focus:text-slate-950">
            <Link class="flex w-full items-center font-semibold text-slate-700" :href="route('profile.edit')" as="button">
                <Settings class="mr-2 h-4 w-4" />
                Profil Saya
            </Link>
        </DropdownMenuItem>
        <DropdownMenuItem :as-child="true" class="rounded-2xl text-slate-700 focus:bg-slate-100 focus:text-slate-950">
            <Link class="flex w-full items-center font-semibold text-slate-700" :href="route('password.edit')" as="button">
                <ShieldCheck class="mr-2 h-4 w-4" />
                Keamanan Akun
            </Link>
        </DropdownMenuItem>
        <DropdownMenuItem :as-child="true" class="rounded-2xl text-slate-700 focus:bg-slate-100 focus:text-slate-950">
            <Link class="flex w-full items-center font-semibold text-slate-700" :href="route('coins.index')" as="button">
                <Coins class="mr-2 h-4 w-4" />
                Lyva Coins
            </Link>
        </DropdownMenuItem>
    </DropdownMenuGroup>
    <DropdownMenuSeparator />
    <DropdownMenuItem :as-child="true" class="rounded-2xl text-rose-600 focus:bg-rose-50 focus:text-rose-700">
        <Link class="flex w-full items-center font-semibold text-rose-600" method="post" :href="route('logout')" as="button">
            <LogOut class="mr-2 h-4 w-4" />
            Log out
        </Link>
    </DropdownMenuItem>
</template>
