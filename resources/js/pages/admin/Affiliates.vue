<script setup lang="ts">
import { Button } from '@/components/ui/button';
import AdminLayout from '@/layouts/AdminLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    applications: Array<{
        id: number;
        name: string;
        email: string;
        whatsapp?: string | null;
        status: string;
        statusLabel: string;
        code?: string | null;
        appliedAtLabel?: string | null;
        approvedAtLabel?: string | null;
    }>;
    withdrawals: Array<{
        id: number;
        publicId: string;
        userName?: string | null;
        userEmail?: string | null;
        whatsapp?: string | null;
        amount: number;
        status: string;
        statusLabel: string;
        requestedAtLabel?: string | null;
        notes?: string | null;
    }>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard Admin', href: '/dashboard' },
    { title: 'Affiliate Monitor', href: '/dashboard/affiliate' },
];

const applicationForm = useForm({});
const withdrawalForm = useForm({});
const rupiah = (value: number) => `Rp${new Intl.NumberFormat('id-ID').format(value)}`;
const affiliateApplicationPath = (userId: number, action: 'approve' | 'reject') =>
    `/dashboard/affiliate/${encodeURIComponent(String(userId))}/${action}`;
const affiliateWithdrawalPath = (withdrawalId: number, action: 'process' | 'pay' | 'reject') =>
    `/dashboard/affiliate/withdrawals/${encodeURIComponent(String(withdrawalId))}/${action}`;
</script>

<template>
    <Head title="Affiliate Monitor" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <section class="mx-4 mt-6 space-y-6 sm:mx-6 lg:mx-8">
            <div class="rounded-[32px] border border-white/80 bg-white/90 p-6 shadow-[0_24px_70px_rgba(15,23,42,0.08)]">
                <p class="text-[0.72rem] font-bold uppercase tracking-[0.22em] text-indigo-600">Affiliate Monitor</p>
                <h1 class="mt-2 text-3xl font-black text-slate-950 [font-family:'Space Grotesk',sans-serif]">Approval affiliate & withdrawal</h1>
            </div>

            <div class="grid gap-6 xl:grid-cols-2">
                <article class="rounded-[32px] border border-white/80 bg-white/90 p-6 shadow-[0_20px_64px_rgba(15,23,42,0.07)]">
                    <h2 class="text-xl font-black text-slate-950 [font-family:'Space Grotesk',sans-serif]">Pengajuan affiliate</h2>
                    <div class="mt-5 space-y-4">
                        <div v-for="item in props.applications" :key="item.id" class="rounded-[24px] border border-slate-200 bg-slate-50/80 p-4">
                            <div class="flex flex-col gap-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-black text-slate-950">{{ item.name }}</p>
                                        <p class="mt-1 text-sm text-slate-600">{{ item.email }}</p>
                                        <p class="mt-1 text-xs font-medium text-slate-500">{{ item.whatsapp }}</p>
                                    </div>
                                    <span class="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-bold text-slate-700">{{ item.statusLabel }}</span>
                                </div>
                                <div class="text-xs text-slate-500">
                                    <p>Kode: {{ item.code ?? '-' }}</p>
                                    <p>Didaftarkan: {{ item.appliedAtLabel ?? '-' }}</p>
                                    <p>Disetujui: {{ item.approvedAtLabel ?? '-' }}</p>
                                </div>
                                <div v-if="item.status === 'pending'" class="flex flex-wrap gap-2">
                                    <Button class="h-10 rounded-full px-4" :disabled="applicationForm.processing" @click="applicationForm.post(affiliateApplicationPath(item.id, 'approve'), { preserveScroll: true })">
                                        Approve
                                    </Button>
                                    <Button variant="outline" class="h-10 rounded-full px-4 text-rose-600" :disabled="applicationForm.processing" @click="applicationForm.post(affiliateApplicationPath(item.id, 'reject'), { preserveScroll: true })">
                                        Tolak
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>

                <article class="rounded-[32px] border border-white/80 bg-white/90 p-6 shadow-[0_20px_64px_rgba(15,23,42,0.07)]">
                    <h2 class="text-xl font-black text-slate-950 [font-family:'Space Grotesk',sans-serif]">Withdrawal affiliate</h2>
                    <div class="mt-5 space-y-4">
                        <div v-for="item in props.withdrawals" :key="item.id" class="rounded-[24px] border border-slate-200 bg-slate-50/80 p-4">
                            <div class="flex flex-col gap-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-black text-slate-950">{{ item.userName }}</p>
                                        <p class="mt-1 text-sm text-slate-600">{{ item.userEmail }}</p>
                                        <p class="mt-1 text-xs font-medium text-slate-500">{{ item.whatsapp }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-black text-emerald-600">{{ rupiah(item.amount) }}</p>
                                        <p class="mt-1 text-xs font-medium text-slate-500">{{ item.statusLabel }}</p>
                                    </div>
                                </div>
                                <div class="text-xs text-slate-500">
                                    <p>ID: {{ item.publicId }}</p>
                                    <p>Diminta: {{ item.requestedAtLabel ?? '-' }}</p>
                                    <p v-if="item.notes">Catatan: {{ item.notes }}</p>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <Button v-if="item.status === 'pending'" class="h-10 rounded-full px-4" :disabled="withdrawalForm.processing" @click="withdrawalForm.post(affiliateWithdrawalPath(item.id, 'process'), { preserveScroll: true })">
                                        Proses
                                    </Button>
                                    <Button v-if="item.status === 'processing'" class="h-10 rounded-full px-4" :disabled="withdrawalForm.processing" @click="withdrawalForm.post(affiliateWithdrawalPath(item.id, 'pay'), { preserveScroll: true })">
                                        Tandai dibayar
                                    </Button>
                                    <Button v-if="['pending', 'processing'].includes(item.status)" variant="outline" class="h-10 rounded-full px-4 text-rose-600" :disabled="withdrawalForm.processing" @click="withdrawalForm.post(affiliateWithdrawalPath(item.id, 'reject'), { preserveScroll: true })">
                                        Tolak
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>
            </div>
        </section>
    </AdminLayout>
</template>
