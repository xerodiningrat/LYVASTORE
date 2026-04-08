<script setup lang="ts">
import { SidebarGroup, SidebarGroupLabel, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import type { SidebarMenuButtonVariants } from '@/components/ui/sidebar';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import type { HTMLAttributes } from 'vue';

const props = withDefaults(
    defineProps<{
        items: NavItem[];
        label?: string;
        buttonSize?: NonNullable<SidebarMenuButtonVariants['size']>;
        groupClass?: HTMLAttributes['class'];
        labelClass?: HTMLAttributes['class'];
        menuClass?: HTMLAttributes['class'];
        itemClass?: HTMLAttributes['class'];
        buttonClass?: HTMLAttributes['class'];
    }>(),
    {
        label: 'Menu',
        buttonSize: 'default',
        groupClass: 'px-2 py-0',
    },
);

const page = usePage<SharedData>();
</script>

<template>
    <SidebarGroup :class="props.groupClass">
        <SidebarGroupLabel :class="props.labelClass">{{ props.label }}</SidebarGroupLabel>
        <SidebarMenu :class="props.menuClass">
            <SidebarMenuItem v-for="item in props.items" :key="item.title" :class="props.itemClass">
                <SidebarMenuButton as-child :size="props.buttonSize" :class="props.buttonClass" :is-active="item.href === page.url">
                    <Link :href="item.href">
                        <component :is="item.icon" />
                        <span>{{ item.title }}</span>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
