<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AmbientParticles from '@/components/AmbientParticles.vue';
import AppShell from '@/components/AppShell.vue';
import AdminSidebar from '@/components/AdminSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import type { BreadcrumbItemType, SharedData } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { Rocket, Wrench } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage<SharedData>();
const deployStatus = computed(() => page.props.adminPanel?.deployStatus ?? null);
const deployModeLabel = computed(() => (deployStatus.value?.mode === 'build+migrate' ? 'Build + migrate' : 'Build live'));
</script>

<template>
    <AppShell variant="sidebar" class="admin-shell">
        <AdminSidebar />
        <AppContent variant="sidebar" class="relative isolate overflow-x-hidden bg-[linear-gradient(180deg,rgba(255,255,255,0.98),rgba(248,250,252,0.96))]">
            <AmbientParticles variant="admin" class="absolute inset-0 z-0" />
            <div class="relative z-10">
                <AppSidebarHeader :breadcrumbs="breadcrumbs" />
                <div
                    v-if="deployStatus?.deployedAtLabel"
                    class="mx-4 mt-4 flex items-center justify-end sm:mx-6 lg:mx-8"
                >
                    <div class="inline-flex items-center gap-3 rounded-full border border-emerald-200/70 bg-white/88 px-4 py-2 text-sm text-slate-600 shadow-[0_18px_40px_rgba(15,23,42,0.06)] backdrop-blur-xl">
                        <span class="inline-flex size-9 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
                            <Rocket v-if="deployStatus.mode !== 'build+migrate'" class="size-4.5" />
                            <Wrench v-else class="size-4.5" />
                        </span>
                        <div class="flex flex-col leading-tight">
                            <span class="text-[0.68rem] font-black uppercase tracking-[0.12em] text-emerald-600">{{ deployModeLabel }}</span>
                            <span class="font-semibold text-slate-700">Deploy terakhir {{ deployStatus.deployedAtLabel }}</span>
                        </div>
                    </div>
                </div>
                <slot />
            </div>
        </AppContent>
    </AppShell>
</template>
