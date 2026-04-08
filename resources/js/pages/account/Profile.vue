<script setup lang="ts">
import { TransitionRoot } from '@headlessui/vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useInitials } from '@/composables/useInitials';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { type SharedData, type User } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { BadgeCheck, Camera, ChevronRight, Copy, Gift, ImagePlus, KeyRound, Mail, ShieldCheck, Sparkles, TriangleAlert, Trash2, UserRound, WalletCards } from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

interface Props {
    mustVerifyEmail: boolean;
    status?: string;
    emailVerifiedFlash?: boolean;
    affiliate: {
        status: string;
        statusLabel: string;
        code?: string | null;
        referralLink?: string | null;
        commissionPercent: number;
        profitShareLimitPercent: number;
        freezeDays: number;
        minimumWithdrawal: number;
        appliedAtLabel?: string | null;
        approvedAtLabel?: string | null;
        totals: {
            referredUsers: number;
            grossEarnings: number;
            frozenEarnings: number;
            availableEarnings: number;
            processingWithdrawals: number;
            paidWithdrawals: number;
        };
        recentCommissions: Array<{
            id: string;
            customerLabel: string;
            productLabel: string;
            commission: number;
            statusLabel: string;
            timeLabel?: string | null;
        }>;
        withdrawals: Array<{
            id: string;
            amount: number;
            status: string;
            statusLabel: string;
            requestedAtLabel?: string | null;
            notes?: string | null;
        }>;
    };
}

const props = defineProps<Props>();

const page = usePage<SharedData>();
const user = computed(() => page.props.auth.user as User);
const resellerSignupHref = computed(() => page.props.support?.chatUrl ?? route('transactions.history'));
const resellerSignupIsExternal = computed(() => /^https?:\/\//i.test(resellerSignupHref.value));
const { getInitials } = useInitials();
const passwordInput = ref<HTMLInputElement | null>(null);
const avatarInput = ref<HTMLInputElement | null>(null);
const avatarPreviewUrl = ref<string | null>(null);
const showEmailVerifiedBanner = ref(Boolean(props.emailVerifiedFlash));
const copiedAffiliateLink = ref(false);
const inputClass =
    'h-12 rounded-2xl border-slate-200 bg-white text-slate-950 placeholder:text-slate-400 shadow-[0_10px_24px_rgba(15,23,42,0.04)] focus-visible:ring-indigo-500 focus-visible:ring-offset-0';

const form = useForm({
    name: user.value.name,
    email: user.value.email,
    whatsapp_number: user.value.whatsapp_number ?? '',
    avatar: null as File | null,
    remove_avatar: false as boolean,
});

const deleteForm = useForm({
    password: '',
});
const affiliateApplyForm = useForm({});
const affiliateWithdrawForm = useForm({
    notes: '',
});

const currentAvatar = computed(() => avatarPreviewUrl.value || user.value.avatar || null);
const hasAvatar = computed(() => Boolean(currentAvatar.value));
const affiliateStatus = computed(() => props.affiliate?.status ?? 'none');
const affiliateCanApply = computed(() => ['none', 'rejected'].includes(affiliateStatus.value));
const affiliateApproved = computed(() => affiliateStatus.value === 'approved');
const affiliatePending = computed(() => affiliateStatus.value === 'pending');
const currency = (value: number) => `Rp${new Intl.NumberFormat('id-ID').format(value)}`;

const resetAvatarPicker = () => {
    if (avatarInput.value) {
        avatarInput.value.value = '';
    }
};

const revokeAvatarPreview = () => {
    if (avatarPreviewUrl.value && avatarPreviewUrl.value.startsWith('blob:')) {
        URL.revokeObjectURL(avatarPreviewUrl.value);
    }

    avatarPreviewUrl.value = null;
};

const openAvatarPicker = () => {
    avatarInput.value?.click();
};

const handleAvatarChange = (event: Event) => {
    const input = event.target as HTMLInputElement | null;
    const file = input?.files?.[0] ?? null;

    revokeAvatarPreview();
    form.avatar = file;
    form.remove_avatar = false;

    if (file) {
        avatarPreviewUrl.value = URL.createObjectURL(file);
    }
};

const removeAvatar = () => {
    revokeAvatarPreview();
    form.avatar = null;
    form.remove_avatar = true;
    resetAvatarPicker();
};

const submit = () => {
    form.transform((data) => ({
        ...data,
        _method: 'patch',
    })).post(route('profile.update'), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            revokeAvatarPreview();
            form.avatar = null;
            form.remove_avatar = false;
            resetAvatarPicker();
        },
        onFinish: () => {
            form.transform((data) => data);
        },
    });
};

const deleteAccount = (event: Event) => {
    event.preventDefault();

    deleteForm.delete(route('profile.destroy'), {
        preserveScroll: true,
        onError: () => passwordInput.value?.focus(),
        onFinish: () => deleteForm.reset(),
    });
};

const closeDeleteModal = () => {
    deleteForm.clearErrors();
    deleteForm.reset();
};

const submitAffiliateApplication = () => {
    affiliateApplyForm.post(route('profile.affiliate.apply'), {
        preserveScroll: true,
    });
};

const submitAffiliateWithdrawal = () => {
    affiliateWithdrawForm.post(route('profile.affiliate.withdraw'), {
        preserveScroll: true,
        onSuccess: () => {
            affiliateWithdrawForm.reset();
        },
    });
};

const copyAffiliateLink = async () => {
    if (!props.affiliate?.referralLink) {
        return;
    }

    await navigator.clipboard.writeText(props.affiliate.referralLink);
    copiedAffiliateLink.value = true;
    window.setTimeout(() => {
        copiedAffiliateLink.value = false;
    }, 1800);
};

onBeforeUnmount(() => {
    revokeAvatarPreview();
});

onMounted(() => {
    if (!showEmailVerifiedBanner.value || typeof window === 'undefined') {
        return;
    }

    window.setTimeout(() => {
        showEmailVerifiedBanner.value = false;
    }, 4200);
});
</script>

<template>
    <PublicLayout>
        <Head title="Profil Saya" />

        <main class="relative overflow-hidden">
            <div class="pointer-events-none absolute inset-0">
                <div class="absolute left-[-10%] top-10 h-72 w-72 rounded-full bg-indigo-200/35 blur-3xl" />
                <div class="absolute right-[-12%] top-28 h-80 w-80 rounded-full bg-sky-200/30 blur-3xl" />
            </div>

            <div class="relative mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <TransitionRoot
                    :show="showEmailVerifiedBanner"
                    enter="transition duration-300 ease-out"
                    enter-from="translate-y-2 opacity-0"
                    enter-to="translate-y-0 opacity-100"
                    leave="transition duration-200 ease-in"
                    leave-from="translate-y-0 opacity-100"
                    leave-to="-translate-y-1 opacity-0"
                >
                    <section class="mb-6 overflow-hidden rounded-[28px] border border-emerald-200 bg-[linear-gradient(135deg,rgba(236,253,245,0.98),rgba(255,255,255,0.98))] px-5 py-4 shadow-[0_22px_50px_rgba(16,185,129,0.12)]">
                        <div class="flex items-start gap-3">
                            <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-emerald-500 text-white shadow-[0_14px_30px_rgba(16,185,129,0.22)]">
                                <BadgeCheck class="size-5" />
                            </span>
                            <div class="min-w-0">
                                <p class="text-[0.72rem] font-black uppercase tracking-[0.2em] text-emerald-600">Email berhasil diverifikasi</p>
                                <p class="mt-1 text-base font-black text-slate-950 [font-family:'Space Grotesk',sans-serif]">
                                    Email akun kamu sekarang sudah aktif.
                                </p>
                                <p class="mt-1 text-sm leading-6 text-slate-600">
                                    Kamu langsung diarahkan ke profil supaya bisa lanjut cek data akun tanpa balik ke dashboard.
                                </p>
                            </div>
                        </div>
                    </section>
                </TransitionRoot>

                <section class="overflow-hidden rounded-[32px] border border-white/80 bg-white/80 shadow-[0_24px_80px_rgba(15,23,42,0.08)] backdrop-blur">
                    <div class="grid gap-8 px-6 py-7 lg:grid-cols-[1.4fr,0.8fr] lg:px-8 lg:py-8">
                        <div class="flex flex-col gap-6 lg:flex-row lg:items-center">
                            <Avatar class="h-24 w-24 rounded-[28px] border border-indigo-100 bg-indigo-50 shadow-[0_18px_40px_rgba(79,70,229,0.15)]">
                                <AvatarImage v-if="currentAvatar" :src="currentAvatar" :alt="user.name" />
                                <AvatarFallback class="bg-indigo-50 text-2xl font-black text-indigo-700">
                                    {{ getInitials(user.name) }}
                                </AvatarFallback>
                            </Avatar>

                            <div class="space-y-4">
                                <div class="space-y-2">
                                    <p class="text-[0.72rem] font-bold uppercase tracking-[0.22em] text-indigo-600">Profil Akun</p>
                                    <h1 class="text-3xl font-black tracking-tight text-slate-950 [font-family:'Space Grotesk',sans-serif]">
                                        {{ user.name }}
                                    </h1>
                                    <p class="max-w-2xl text-sm leading-7 text-slate-600">
                                        Kelola identitas akun, email, dan nomor WhatsApp dari halaman khusus ini tanpa masuk ke dashboard settings lagi.
                                    </p>
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    <span
                                        class="inline-flex items-center gap-2 rounded-full border px-3 py-2 text-xs font-bold uppercase tracking-[0.12em]"
                                        :class="
                                            user.whatsapp_verified_at
                                                ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
                                                : 'border-amber-200 bg-amber-50 text-amber-700'
                                        "
                                    >
                                        <BadgeCheck class="size-3.5" />
                                        {{ user.whatsapp_verified_at ? 'WhatsApp Terverifikasi' : 'WhatsApp Belum Verifikasi' }}
                                    </span>
                                    <span
                                        class="inline-flex items-center gap-2 rounded-full border px-3 py-2 text-xs font-bold uppercase tracking-[0.12em]"
                                        :class="
                                            user.email_verified_at
                                                ? 'border-sky-200 bg-sky-50 text-sky-700'
                                                : 'border-slate-200 bg-slate-50 text-slate-600'
                                        "
                                    >
                                        <Mail class="size-3.5" />
                                        {{ user.email_verified_at ? 'Email Terverifikasi' : 'Email Belum Verifikasi' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-[28px] border border-indigo-100 bg-[linear-gradient(135deg,rgba(238,242,255,0.95),rgba(255,255,255,0.95))] p-5">
                            <p class="text-[0.72rem] font-bold uppercase tracking-[0.22em] text-indigo-600">Akses Cepat</p>
                            <div class="mt-4 space-y-3">
                                <Link
                                    :href="route('password.edit')"
                                    class="flex items-center justify-between rounded-[22px] border border-white/90 bg-white/90 px-4 py-3 text-sm font-bold text-slate-700 transition hover:-translate-y-0.5 hover:text-indigo-700"
                                >
                                    <span class="flex items-center gap-3">
                                        <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-700">
                                            <KeyRound class="size-4.5" />
                                        </span>
                                        Ganti password akun
                                    </span>
                                    <ChevronRight class="size-4" />
                                </Link>

                                <Link
                                    :href="route('home')"
                                    class="flex items-center justify-between rounded-[22px] border border-white/90 bg-white/90 px-4 py-3 text-sm font-bold text-slate-700 transition hover:-translate-y-0.5 hover:text-indigo-700"
                                >
                                    <span class="flex items-center gap-3">
                                        <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-sky-50 text-sky-700">
                                            <WalletCards class="size-4.5" />
                                        </span>
                                        Kembali ke beranda
                                    </span>
                                    <ChevronRight class="size-4" />
                                </Link>

                                <component
                                    :is="resellerSignupIsExternal ? 'a' : Link"
                                    :href="resellerSignupHref"
                                    :target="resellerSignupIsExternal ? '_blank' : undefined"
                                    :rel="resellerSignupIsExternal ? 'noopener noreferrer' : undefined"
                                    class="flex items-center justify-between rounded-[22px] border border-white/90 bg-white/90 px-4 py-3 text-sm font-bold text-slate-700 transition hover:-translate-y-0.5 hover:text-indigo-700"
                                >
                                    <span class="flex items-center gap-3">
                                        <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700">
                                            <Sparkles class="size-4.5" />
                                        </span>
                                        Pendaftaran reseller
                                    </span>
                                    <ChevronRight class="size-4" />
                                </component>
                            </div>

                            <div class="mt-5 rounded-[22px] border border-white/90 bg-white/80 p-4 text-sm text-slate-600">
                                <p class="font-bold text-slate-900">Tips akun</p>
                                <p class="mt-2 leading-6">
                                    Pakai nomor WhatsApp aktif agar OTP login, update pesanan, dan kode produk bisa masuk lebih cepat.
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="mt-8 grid gap-6 xl:grid-cols-[1.15fr,0.85fr]">
                    <article class="rounded-[32px] border border-white/80 bg-white/85 p-6 shadow-[0_20px_64px_rgba(15,23,42,0.07)] backdrop-blur lg:p-7">
                        <div class="mb-6">
                            <p class="text-[0.72rem] font-bold uppercase tracking-[0.22em] text-indigo-600">Informasi Utama</p>
                            <h2 class="mt-2 text-2xl font-black text-slate-950 [font-family:'Space Grotesk',sans-serif]">Edit profil akun</h2>
                            <p class="mt-2 text-sm leading-7 text-slate-600">
                                Ubah nama, email, dan nomor WhatsApp dari satu halaman yang lebih rapi dan langsung.
                            </p>
                        </div>

                        <form class="space-y-5" @submit.prevent="submit">
                            <div class="rounded-[28px] border border-slate-200 bg-slate-50/80 p-5">
                                <div class="flex flex-col gap-5 sm:flex-row sm:items-center">
                                    <Avatar class="h-24 w-24 rounded-[28px] border border-indigo-100 bg-white shadow-[0_14px_36px_rgba(15,23,42,0.08)]">
                                        <AvatarImage v-if="currentAvatar" :src="currentAvatar" :alt="user.name" />
                                        <AvatarFallback class="bg-indigo-50 text-2xl font-black text-indigo-700">
                                            {{ getInitials(user.name) }}
                                        </AvatarFallback>
                                    </Avatar>

                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-bold text-slate-900">Foto profil</p>
                                        <p class="mt-1 text-sm leading-6 text-slate-600">
                                            Upload foto profil agar akun kamu lebih personal. Format yang didukung JPG, PNG, atau WEBP maksimal 2 MB.
                                        </p>

                                        <div class="mt-4 flex flex-wrap gap-3">
                                            <input
                                                ref="avatarInput"
                                                type="file"
                                                accept="image/png,image/jpeg,image/jpg,image/webp"
                                                class="hidden"
                                                @change="handleAvatarChange"
                                            />

                                            <Button type="button" variant="secondary" class="h-11 rounded-full px-5" @click="openAvatarPicker">
                                                <ImagePlus class="mr-2 size-4" />
                                                {{ hasAvatar ? 'Ganti foto' : 'Upload foto' }}
                                            </Button>

                                            <Button
                                                type="button"
                                                variant="outline"
                                                class="h-11 rounded-full px-5 text-rose-600"
                                                :disabled="!hasAvatar"
                                                @click="removeAvatar"
                                            >
                                                <Trash2 class="mr-2 size-4" />
                                                Hapus foto
                                            </Button>
                                        </div>

                                        <InputError class="mt-3" :message="form.errors.avatar" />
                                    </div>
                                </div>
                            </div>

                            <div class="grid gap-2">
                                <Label for="name" class="text-sm font-semibold text-slate-700">Nama lengkap</Label>
                                <Input id="name" v-model="form.name" required autocomplete="name" :class="inputClass" placeholder="Nama lengkap" />
                                <InputError :message="form.errors.name" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="email" class="text-sm font-semibold text-slate-700">Email</Label>
                                <Input
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    required
                                    autocomplete="username"
                                    :class="inputClass"
                                    placeholder="Email aktif"
                                />
                                <InputError :message="form.errors.email" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="whatsapp_number" class="text-sm font-semibold text-slate-700">Nomor WhatsApp</Label>
                                <Input
                                    id="whatsapp_number"
                                    v-model="form.whatsapp_number"
                                    type="tel"
                                    required
                                    autocomplete="tel"
                                    inputmode="tel"
                                    :class="inputClass"
                                    placeholder="08xxxxxxxxxx"
                                />
                                <InputError :message="form.errors.whatsapp_number" />
                            </div>

                            <div v-if="mustVerifyEmail && !user.email_verified_at" class="rounded-[24px] border border-amber-200 bg-amber-50/80 p-4">
                                <p class="text-sm font-bold text-amber-800">Email kamu belum diverifikasi.</p>
                                <p class="mt-2 text-sm leading-6 text-amber-700">
                                    Klik tombol di bawah untuk mengirim ulang link verifikasi email ke inbox kamu.
                                </p>
                                <Link
                                    :href="route('verification.send')"
                                    method="post"
                                    as="button"
                                    class="mt-3 inline-flex rounded-full border border-amber-300 bg-white px-4 py-2 text-sm font-bold text-amber-700 transition hover:bg-amber-100"
                                >
                                    Kirim ulang verifikasi email
                                </Link>
                            </div>

                            <div class="flex flex-wrap items-center gap-3 pt-2">
                                <Button :disabled="form.processing" class="h-12 rounded-full px-6">
                                    <Camera class="mr-2 size-4" />
                                    Simpan perubahan
                                </Button>

                                <TransitionRoot
                                    :show="form.recentlySuccessful || Boolean(status)"
                                    enter="transition ease-in-out duration-200"
                                    enter-from="opacity-0 translate-y-1"
                                    leave="transition ease-in-out duration-150"
                                    leave-to="opacity-0 translate-y-1"
                                >
                                    <p class="text-sm font-medium text-emerald-600">
                                        {{ status ?? 'Profil berhasil diperbarui.' }}
                                    </p>
                                </TransitionRoot>
                            </div>
                        </form>
                    </article>

                    <div class="space-y-6">
                        <article class="rounded-[32px] border border-white/80 bg-white/85 p-6 shadow-[0_20px_64px_rgba(15,23,42,0.07)] backdrop-blur">
                            <p class="text-[0.72rem] font-bold uppercase tracking-[0.22em] text-indigo-600">Affiliate Lyva</p>
                            <h2 class="mt-2 text-2xl font-black text-slate-950 [font-family:'Space Grotesk',sans-serif]">Program affiliate & penarikan</h2>
                            <p class="mt-2 text-sm leading-7 text-slate-600">
                                Daftar affiliate dari profil, bagikan link referral, lalu pantau komisi yang dibekukan 2 hari sebelum siap ditarik.
                            </p>

                            <div class="mt-5 rounded-[24px] border p-4" :class="affiliateApproved ? 'border-emerald-200 bg-emerald-50/80' : affiliatePending ? 'border-amber-200 bg-amber-50/80' : 'border-slate-200 bg-slate-50/80'">
                                <div class="flex items-start gap-3">
                                    <span class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl" :class="affiliateApproved ? 'bg-emerald-100 text-emerald-700' : affiliatePending ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-700'">
                                        <Gift class="size-4.5" />
                                    </span>
                                    <div class="min-w-0">
                                        <p class="text-sm font-bold text-slate-900">{{ props.affiliate.statusLabel }}</p>
                                        <p class="mt-1 text-sm leading-6 text-slate-600">
                                            <template v-if="affiliateApproved">
                                                Affiliate aktif dengan komisi sampai {{ props.affiliate.commissionPercent }}% per transaksi selesai, tetapi tetap dibatasi maksimal {{ props.affiliate.profitShareLimitPercent }}% dari estimasi profit aman. Komisi dibekukan {{ props.affiliate.freezeDays }} hari sebelum masuk saldo tarik.
                                            </template>
                                            <template v-else-if="affiliatePending">
                                                Pendaftaran affiliate kamu sudah masuk dan sedang menunggu persetujuan admin.
                                            </template>
                                            <template v-else>
                                                User baru daftar affiliate tidak langsung aktif. Daftar dulu dari profil, lalu admin akan review sebelum komisi mulai jalan.
                                            </template>
                                        </p>
                                        <p v-if="props.affiliate.appliedAtLabel" class="mt-2 text-xs font-medium text-slate-500">
                                            Didaftarkan {{ props.affiliate.appliedAtLabel }}
                                        </p>
                                        <p v-if="props.affiliate.approvedAtLabel" class="mt-1 text-xs font-medium text-slate-500">
                                            Disetujui {{ props.affiliate.approvedAtLabel }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div v-if="affiliateCanApply" class="mt-5">
                                <Button class="h-12 rounded-full px-6" :disabled="affiliateApplyForm.processing" @click="submitAffiliateApplication">
                                    <Sparkles class="mr-2 size-4" />
                                    Daftar affiliate sekarang
                                </Button>
                            </div>

                            <div v-if="affiliateApproved" class="mt-6 space-y-5">
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div class="rounded-[24px] border border-slate-200 bg-slate-50/80 p-4">
                                        <p class="text-[0.68rem] font-bold uppercase tracking-[0.16em] text-slate-500">Kode affiliate</p>
                                        <p class="mt-2 text-lg font-black text-slate-950">{{ props.affiliate.code }}</p>
                                    </div>
                                    <div class="rounded-[24px] border border-slate-200 bg-slate-50/80 p-4">
                                        <p class="text-[0.68rem] font-bold uppercase tracking-[0.16em] text-slate-500">Member referral</p>
                                        <p class="mt-2 text-lg font-black text-slate-950">{{ props.affiliate.totals.referredUsers }}</p>
                                    </div>
                                    <div class="rounded-[24px] border border-slate-200 bg-slate-50/80 p-4">
                                        <p class="text-[0.68rem] font-bold uppercase tracking-[0.16em] text-slate-500">Saldo siap ditarik</p>
                                        <p class="mt-2 text-lg font-black text-emerald-600">{{ currency(props.affiliate.totals.availableEarnings) }}</p>
                                    </div>
                                    <div class="rounded-[24px] border border-slate-200 bg-slate-50/80 p-4">
                                        <p class="text-[0.68rem] font-bold uppercase tracking-[0.16em] text-slate-500">Saldo dibekukan</p>
                                        <p class="mt-2 text-lg font-black text-amber-600">{{ currency(props.affiliate.totals.frozenEarnings) }}</p>
                                    </div>
                                </div>

                                <div class="rounded-[24px] border border-slate-200 bg-white p-4">
                                    <p class="text-sm font-bold text-slate-900">Link referral</p>
                                    <div class="mt-3 flex flex-col gap-3 sm:flex-row">
                                        <Input :model-value="props.affiliate.referralLink ?? ''" readonly :class="inputClass" />
                                        <Button type="button" variant="secondary" class="h-12 rounded-full px-5" @click="copyAffiliateLink">
                                            <Copy class="mr-2 size-4" />
                                            {{ copiedAffiliateLink ? 'Link tersalin' : 'Salin link' }}
                                        </Button>
                                    </div>
                                </div>

                                <div class="rounded-[24px] border border-slate-200 bg-white p-4">
                                    <p class="text-sm font-bold text-slate-900">Tarik pendapatan affiliate</p>
                                    <p class="mt-2 text-sm leading-6 text-slate-600">
                                        Penarikan dilakukan lewat profil. Minimum penarikan {{ currency(props.affiliate.minimumWithdrawal) }}. Komisi baru bisa ditarik setelah melewati masa beku 2 hari.
                                    </p>
                                    <form class="mt-4 space-y-3" @submit.prevent="submitAffiliateWithdrawal">
                                        <div class="grid gap-2">
                                            <Label for="affiliate-notes" class="text-sm font-semibold text-slate-700">Catatan penarikan</Label>
                                            <Input
                                                id="affiliate-notes"
                                                v-model="affiliateWithdrawForm.notes"
                                                :class="inputClass"
                                                placeholder="Opsional, misalnya waktu terbaik dihubungi admin"
                                            />
                                            <InputError :message="affiliateWithdrawForm.errors.notes" />
                                        </div>
                                        <Button
                                            class="h-12 rounded-full px-6"
                                            :disabled="affiliateWithdrawForm.processing || props.affiliate.totals.availableEarnings < props.affiliate.minimumWithdrawal"
                                        >
                                            Ajukan tarik {{ currency(props.affiliate.totals.availableEarnings) }}
                                        </Button>
                                    </form>
                                </div>

                                <div class="rounded-[24px] border border-slate-200 bg-white p-4">
                                    <p class="text-sm font-bold text-slate-900">Komisi terbaru</p>
                                    <div v-if="props.affiliate.recentCommissions.length" class="mt-4 space-y-3">
                                        <div v-for="commission in props.affiliate.recentCommissions" :key="commission.id" class="rounded-[20px] border border-slate-200 bg-slate-50/80 p-4">
                                            <div class="flex items-start justify-between gap-3">
                                                <div class="min-w-0">
                                                    <p class="text-sm font-bold text-slate-900">{{ commission.customerLabel }}</p>
                                                    <p class="mt-1 text-sm leading-6 text-slate-600">{{ commission.productLabel }}</p>
                                                    <p class="mt-1 text-xs font-medium text-slate-500">{{ commission.timeLabel }}</p>
                                                </div>
                                                <div class="text-right">
                                                    <p class="text-sm font-black text-emerald-600">{{ currency(commission.commission) }}</p>
                                                    <p class="mt-1 text-xs font-medium text-slate-500">{{ commission.statusLabel }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <p v-else class="mt-3 text-sm leading-6 text-slate-500">
                                        Belum ada komisi masuk. Bagikan link referral kamu setelah status affiliate aktif.
                                    </p>
                                </div>

                                <div class="rounded-[24px] border border-slate-200 bg-white p-4">
                                    <p class="text-sm font-bold text-slate-900">Riwayat penarikan</p>
                                    <div v-if="props.affiliate.withdrawals.length" class="mt-4 space-y-3">
                                        <div v-for="withdrawal in props.affiliate.withdrawals" :key="withdrawal.id" class="rounded-[20px] border border-slate-200 bg-slate-50/80 p-4">
                                            <div class="flex items-start justify-between gap-3">
                                                <div>
                                                    <p class="text-sm font-bold text-slate-900">{{ withdrawal.statusLabel }}</p>
                                                    <p class="mt-1 text-xs font-medium text-slate-500">{{ withdrawal.requestedAtLabel }}</p>
                                                    <p v-if="withdrawal.notes" class="mt-2 text-sm leading-6 text-slate-600">{{ withdrawal.notes }}</p>
                                                </div>
                                                <p class="text-sm font-black text-slate-950">{{ currency(withdrawal.amount) }}</p>
                                            </div>
                                        </div>
                                    </div>
                                    <p v-else class="mt-3 text-sm leading-6 text-slate-500">Belum ada penarikan affiliate.</p>
                                </div>
                            </div>
                        </article>

                        <article class="rounded-[32px] border border-white/80 bg-white/85 p-6 shadow-[0_20px_64px_rgba(15,23,42,0.07)] backdrop-blur">
                            <p class="text-[0.72rem] font-bold uppercase tracking-[0.22em] text-indigo-600">Status Akun</p>
                            <div class="mt-5 space-y-4">
                                <div class="rounded-[24px] border border-slate-200 bg-slate-50/80 p-4">
                                    <div class="flex items-start gap-3">
                                        <span class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-700">
                                            <UserRound class="size-4.5" />
                                        </span>
                                        <div>
                                            <p class="text-sm font-bold text-slate-900">Identitas akun</p>
                                            <p class="mt-1 text-sm leading-6 text-slate-600">
                                                Nama dan email dipakai untuk login, checkout, dan riwayat transaksi kamu.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="rounded-[24px] border p-4" :class="user.whatsapp_verified_at ? 'border-emerald-200 bg-emerald-50/80' : 'border-amber-200 bg-amber-50/80'">
                                    <div class="flex items-start gap-3">
                                        <span
                                            class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl"
                                            :class="user.whatsapp_verified_at ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'"
                                        >
                                            <ShieldCheck class="size-4.5" />
                                        </span>
                                        <div>
                                            <p class="text-sm font-bold text-slate-900">
                                                {{ user.whatsapp_verified_at ? 'Nomor WhatsApp sudah aman' : 'Nomor WhatsApp perlu diverifikasi' }}
                                            </p>
                                            <p class="mt-1 text-sm leading-6 text-slate-600">
                                                {{
                                                    user.whatsapp_verified_at
                                                        ? 'Notifikasi checkout dan OTP akan dikirim ke nomor ini.'
                                                        : 'Kalau nomor diubah, sistem akan kirim OTP lagi sebelum akun bisa lanjut dipakai penuh.'
                                                }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </article>

                        <article class="rounded-[32px] border border-red-100 bg-[linear-gradient(135deg,rgba(254,242,242,0.95),rgba(255,255,255,0.96))] p-6 shadow-[0_20px_64px_rgba(239,68,68,0.08)]">
                            <p class="text-[0.72rem] font-bold uppercase tracking-[0.22em] text-red-500">Danger Zone</p>
                            <h2 class="mt-2 text-2xl font-black text-slate-950 [font-family:'Space Grotesk',sans-serif]">Hapus akun</h2>
                            <p class="mt-2 text-sm leading-7 text-slate-600">
                                Kalau akun dihapus, data login dan aksesnya akan hilang permanen. Pastikan kamu benar-benar yakin sebelum lanjut.
                            </p>

                            <Dialog>
                                <DialogTrigger as-child>
                                    <Button variant="destructive" class="mt-5 h-12 rounded-full px-6">Hapus akun sekarang</Button>
                                </DialogTrigger>

                                <DialogContent class="sm:max-w-md">
                                    <form class="space-y-6" @submit="deleteAccount">
                                        <DialogHeader class="space-y-3">
                                            <DialogTitle class="flex items-center gap-2">
                                                <TriangleAlert class="size-5 text-red-500" />
                                                Konfirmasi hapus akun
                                            </DialogTitle>
                                            <DialogDescription class="leading-6">
                                                Masukkan password akun kamu untuk memastikan penghapusan akun dilakukan oleh pemilik akun.
                                            </DialogDescription>
                                        </DialogHeader>

                                        <div class="grid gap-2">
                                            <Label for="delete-password">Password</Label>
                                            <Input
                                                id="delete-password"
                                                ref="passwordInput"
                                                v-model="deleteForm.password"
                                                type="password"
                                                name="password"
                                                autocomplete="current-password"
                                                :class="inputClass"
                                                placeholder="Masukkan password"
                                            />
                                            <InputError :message="deleteForm.errors.password" />
                                        </div>

                                        <DialogFooter class="gap-2 sm:justify-end">
                                            <DialogClose as-child>
                                                <Button variant="secondary" @click="closeDeleteModal">Batal</Button>
                                            </DialogClose>
                                            <Button variant="destructive" :disabled="deleteForm.processing">
                                                Hapus akun
                                            </Button>
                                        </DialogFooter>
                                    </form>
                                </DialogContent>
                            </Dialog>
                        </article>
                    </div>
                </section>
            </div>
        </main>
    </PublicLayout>
</template>
