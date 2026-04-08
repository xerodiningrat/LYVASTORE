<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import Button from '@/components/ui/button/Button.vue';
import Input from '@/components/ui/input/Input.vue';
import Label from '@/components/ui/label/Label.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
import type { SharedData, User } from '@/types';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import {
    BadgeCheck,
    ChevronRight,
    CircleAlert,
    KeyRound,
    Mail,
    MessageCircleMore,
    Paperclip,
    Send,
    ShieldCheck,
    Sparkles,
    UserRound,
} from 'lucide-vue-next';
import { computed, ref, type Component } from 'vue';

type IssueCategory = {
    value: 'login-failed' | 'premium-not-active' | 'wrong-account' | 'limit-or-verification' | 'other';
    title: string;
    description: string;
    examples: string[];
    icon: Component;
    tone: string;
};

type SupportedProduct = {
    id: string;
    name: string;
};

const props = defineProps<{
    products: SupportedProduct[];
}>();

const page = usePage<SharedData>();
const currentUser = computed(() => (page.props.auth?.user ?? null) as User | null);
const flashStatus = computed(() => String(page.props.flash?.status ?? ''));
const supportChatUrl = computed(() => page.props.support?.chatUrl ?? route('transactions.history'));
const proofInput = ref<HTMLInputElement | null>(null);
const profileEditUrl = route('profile.edit');

const issueCategories: IssueCategory[] = [
    {
        value: 'login-failed',
        title: 'Login tidak masuk',
        description: 'Cocok untuk akun ChatGPT, CapCut, Canva, Netflix, Spotify, dan akun premium lain yang gagal login setelah pembelian.',
        examples: ['Email atau password tidak cocok', 'Invite belum masuk', 'Akun logout terus', 'Family slot tidak aktif'],
        icon: KeyRound,
        tone: 'border-amber-200 bg-amber-50 text-amber-700',
    },
    {
        value: 'premium-not-active',
        title: 'Akses premium belum aktif',
        description: 'Dipakai kalau akun berhasil masuk tapi benefit premium belum muncul atau durasi belum bertambah.',
        examples: ['ChatGPT masih Free', 'CapCut Pro belum aktif', 'Netflix masih plan lama', 'Spotify belum Premium'],
        icon: Sparkles,
        tone: 'border-sky-200 bg-sky-50 text-sky-700',
    },
    {
        value: 'wrong-account',
        title: 'Email / akun salah',
        description: 'Pakai jalur ini kalau ada typo email, username, atau data akun yang terlanjur dikirim saat checkout.',
        examples: ['Email typo', 'Username tertukar', 'Akun tujuan salah', 'Perlu revisi data akun'],
        icon: Mail,
        tone: 'border-rose-200 bg-rose-50 text-rose-700',
    },
    {
        value: 'limit-or-verification',
        title: 'Akun kena limit / verifikasi',
        description: 'Untuk akun yang minta verifikasi tambahan, OTP, device check, atau dibatasi region / family.',
        examples: ['Minta OTP lagi', 'Kena device limit', 'Region tidak cocok', 'Family lock aktif'],
        icon: ShieldCheck,
        tone: 'border-emerald-200 bg-emerald-50 text-emerald-700',
    },
    {
        value: 'other',
        title: 'Masalah lain',
        description: 'Kalau kendalanya belum masuk kategori di atas, tulis saja detailnya supaya support bisa baca konteks lengkapnya.',
        examples: ['Order salah paket', 'Akun perlu dicek ulang', 'Kendala aktivasi lain'],
        icon: CircleAlert,
        tone: 'border-slate-200 bg-slate-50 text-slate-700',
    },
];

const preparationChecklist = [
    'Pilih produk entertainment yang benar, misalnya ChatGPT atau CapCut Pro.',
    'Isi kronologi kendala sejelas mungkin agar tim support cepat paham masalahnya.',
    'Upload screenshot error, bukti login gagal, atau bukti premium belum aktif.',
    'Isi email dan nomor WhatsApp aktif supaya admin bisa follow up lebih cepat.',
];

const form = useForm({
    product_id: props.products[0]?.id ?? '',
    product_name: props.products[0]?.name ?? '',
    issue_type: 'login-failed' as IssueCategory['value'],
    transaction_reference: '',
    account_email: '',
    issue_message: '',
    proof: null as File | null,
    website: '',
    formStartedAt: Date.now(),
});

const selectedIssue = computed(() => issueCategories.find((issue) => issue.value === form.issue_type) ?? issueCategories[0]);
const selectedProofName = computed(() => form.proof?.name ?? '');
const profileWhatsapp = computed(() => currentUser.value?.whatsapp_number?.trim() ?? '');
const hasProfileWhatsapp = computed(() => profileWhatsapp.value !== '');
const selectedProductLabel = computed(() => form.product_name.toLowerCase());
const needsInviteEmailOnly = computed(() => selectedProductLabel.value.includes('chatgpt'));
const accountEmailLabel = computed(() => (needsInviteEmailOnly.value ? 'Email akun yang mau di-invite ulang' : 'Email akun yang bermasalah'));
const accountEmailPlaceholder = computed(() =>
    needsInviteEmailOnly.value ? 'Masukkan email akun ChatGPT yang mau di-invite ulang' : 'Masukkan email akun yang sedang bermasalah',
);
const selectedProductHelp = computed(() => {
    if (!form.product_name) {
        return 'Pilih dulu produk akun yang bermasalah supaya laporanmu masuk ke jalur yang tepat.';
    }

    if (needsInviteEmailOnly.value) {
        return `Form ini fokus ke email akun yang perlu di-invite ulang. Nomor WhatsApp akan otomatis diambil dari profil akunmu.`;
    }

    return 'Detail laporan akan dikirim ke admin support, dan nomor WhatsApp akan otomatis diambil dari profil akunmu.';
});

const selectProduct = (event: Event) => {
    const target = event.target as HTMLSelectElement | null;
    const nextId = target?.value ?? '';
    const matchedProduct = props.products.find((product) => product.id === nextId);

    form.product_id = nextId;
    form.product_name = matchedProduct?.name ?? '';
};

const updateProof = (event: Event) => {
    const input = event.target as HTMLInputElement | null;
    form.proof = input?.files?.[0] ?? null;
};

const submit = () => {
    form.post(route('account-issues.store'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.reset('transaction_reference', 'issue_message', 'proof', 'website');
            form.account_email = '';
            form.formStartedAt = Date.now();

            if (proofInput.value) {
                proofInput.value.value = '';
            }
        },
    });
};
</script>

<template>
    <Head title="Akun Bermasalah" />

    <PublicLayout active-nav="akun-bermasalah">
        <main class="relative overflow-hidden px-4 py-8 sm:px-6 lg:px-8 lg:py-10">
            <div
                class="pointer-events-none absolute inset-x-0 top-0 h-80"
                style="background-image: radial-gradient(circle at top, rgba(14, 165, 233, 0.18), transparent 60%)"
            />
            <div class="pointer-events-none absolute left-[-6%] top-[12%] h-72 w-72 rounded-full bg-sky-200/30 blur-3xl" />
            <div class="pointer-events-none absolute right-[-4%] top-[18%] h-80 w-80 rounded-full bg-amber-200/25 blur-3xl" />

            <section class="relative mx-auto max-w-7xl overflow-hidden rounded-[40px] border border-white/85 bg-[linear-gradient(180deg,rgba(255,255,255,0.96),rgba(245,247,255,0.98))] p-6 shadow-[0_36px_120px_rgba(15,23,42,0.08)] backdrop-blur-xl sm:p-8 lg:p-10">
                <div class="pointer-events-none absolute inset-x-0 top-0 h-44 bg-[radial-gradient(circle_at_top,rgba(14,165,233,0.14),transparent_60%)]" />

                <div class="relative z-10">
                    <div class="max-w-3xl">
                        <p class="text-[0.72rem] font-semibold uppercase tracking-[0.26em] text-sky-600">Akun Bermasalah</p>
                        <h1 class="mt-3 text-4xl font-black tracking-tight text-slate-950 [font-family:'Space Grotesk',sans-serif] sm:text-5xl">
                            Laporkan kendala akun entertainment dari satu form.
                        </h1>
                        <p class="mt-4 text-sm leading-8 text-slate-600 sm:text-[15px]">
                            Halaman ini khusus user yang sudah login. Pilih dulu produk yang bermasalah, lalu isi email akun target, tulis kendalanya, dan unggah bukti.
                            Nomor WhatsApp akan otomatis diambil dari profil untuk update hasil proses.
                        </p>
                    </div>

                    <div class="mt-8 flex flex-wrap gap-3">
                        <span class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-4 py-2 text-xs font-bold uppercase tracking-[0.16em] text-emerald-700">
                            <BadgeCheck class="size-4" />
                            Support akun khusus
                        </span>
                        <span class="inline-flex items-center gap-2 rounded-full border border-sky-200 bg-sky-50 px-4 py-2 text-xs font-bold uppercase tracking-[0.16em] text-sky-700">
                            <UserRound class="size-4" />
                            Hanya produk entertainment
                        </span>
                    </div>

                    <section
                        v-if="flashStatus"
                        class="mt-8 rounded-[28px] border border-emerald-200 bg-[linear-gradient(135deg,rgba(236,253,245,0.98),rgba(255,255,255,0.98))] px-5 py-4 shadow-[0_22px_50px_rgba(16,185,129,0.12)]"
                    >
                        <div class="flex items-start gap-3">
                            <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-emerald-500 text-white shadow-[0_14px_30px_rgba(16,185,129,0.22)]">
                                <BadgeCheck class="size-5" />
                            </span>
                            <div class="min-w-0">
                                <p class="text-[0.72rem] font-black uppercase tracking-[0.2em] text-emerald-600">Laporan berhasil dikirim</p>
                                <p class="mt-1 text-sm leading-7 text-slate-700">{{ flashStatus }}</p>
                            </div>
                        </div>
                    </section>

                    <div class="mt-10 grid gap-6 xl:grid-cols-[0.88fr_1.12fr]">
                        <div class="order-2 space-y-6 xl:order-1">
                            <section class="rounded-[30px] border border-slate-200/80 bg-white/90 p-6 shadow-[0_18px_38px_rgba(15,23,42,0.05)]">
                                <div class="flex items-center gap-3">
                                    <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-slate-900 text-white">
                                        <CircleAlert class="size-5" />
                                    </span>
                                    <div>
                                        <p class="text-[0.68rem] font-semibold uppercase tracking-[0.18em] text-slate-500">Sebelum Kirim Laporan</p>
                                        <h2 class="text-xl font-black tracking-tight text-slate-950 [font-family:'Space Grotesk',sans-serif]">Siapkan data ini dulu</h2>
                                    </div>
                                </div>

                                <div class="mt-6 space-y-4">
                                    <div v-for="(item, index) in preparationChecklist" :key="item" class="flex gap-4 rounded-[24px] border border-slate-200 bg-slate-50/80 px-4 py-4">
                                        <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-sky-100 text-sm font-black text-sky-700">
                                            {{ index + 1 }}
                                        </span>
                                        <p class="text-sm leading-7 text-slate-600">{{ item }}</p>
                                    </div>
                                </div>
                            </section>

                            <section class="rounded-[30px] border border-slate-200/80 bg-white/90 p-6 shadow-[0_18px_38px_rgba(15,23,42,0.05)]">
                                <p class="text-[0.68rem] font-semibold uppercase tracking-[0.18em] text-slate-500">Jenis Kendala</p>
                                <div class="mt-5 space-y-4">
                                    <article
                                        v-for="issue in issueCategories"
                                        :key="issue.value"
                                        class="rounded-[26px] border border-white/80 bg-white/88 p-5 shadow-[0_12px_32px_rgba(15,23,42,0.04)]"
                                    >
                                        <div class="flex items-start gap-4">
                                            <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl" :class="issue.tone">
                                                <component :is="issue.icon" class="size-5" />
                                            </span>
                                            <div>
                                                <h2 class="text-lg font-black tracking-tight text-slate-950 [font-family:'Space Grotesk',sans-serif]">{{ issue.title }}</h2>
                                                <p class="mt-2 text-sm leading-7 text-slate-600">{{ issue.description }}</p>
                                            </div>
                                        </div>

                                        <div class="mt-4 flex flex-wrap gap-2">
                                            <span
                                                v-for="example in issue.examples"
                                                :key="example"
                                                class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-semibold text-slate-600"
                                            >
                                                {{ example }}
                                            </span>
                                        </div>
                                    </article>
                                </div>
                            </section>
                        </div>

                        <section class="order-1 rounded-[32px] border border-slate-200/80 bg-slate-950 p-6 text-white shadow-[0_24px_60px_rgba(15,23,42,0.18)] sm:p-7 xl:order-2">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div>
                                    <p class="text-[0.68rem] font-semibold uppercase tracking-[0.18em] text-sky-200">Form laporan</p>
                                    <h2 class="mt-3 text-3xl font-black tracking-tight [font-family:'Space Grotesk',sans-serif]">Pilih produk lalu kirim bukti masalahnya.</h2>
                                </div>
                                <a
                                    :href="supportChatUrl"
                                    class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/5 px-4 py-2 text-xs font-bold uppercase tracking-[0.12em] text-slate-100 transition hover:bg-white/10"
                                >
                                    <MessageCircleMore class="size-4" />
                                    Chat support
                                </a>
                            </div>

                            <div class="mt-6 rounded-[24px] border border-white/10 bg-white/5 p-4 text-sm leading-7 text-slate-300">
                                {{ selectedProductHelp }}
                            </div>

                            <form class="mt-6 space-y-5" @submit.prevent="submit">
                                <div class="grid gap-5 md:grid-cols-2">
                                    <div class="space-y-2 md:col-span-2">
                                        <Label for="issue-product" class="text-sm font-bold text-slate-100">Pilih produk yang bermasalah</Label>
                                        <select
                                            id="issue-product"
                                            :value="form.product_id"
                                            class="h-[52px] w-full rounded-[22px] border border-white/10 bg-white/10 px-4 text-sm font-medium text-white outline-none transition focus:border-sky-300"
                                            @change="selectProduct"
                                        >
                                            <option v-for="product in props.products" :key="product.id" :value="product.id" class="text-slate-950">
                                                {{ product.name }}
                                            </option>
                                        </select>
                                        <InputError :message="form.errors.product_id || form.errors.product_name" />
                                    </div>

                                    <div class="space-y-2">
                                        <Label for="issue-type" class="text-sm font-bold text-slate-100">Jenis masalah</Label>
                                        <select
                                            id="issue-type"
                                            v-model="form.issue_type"
                                            class="h-[52px] w-full rounded-[22px] border border-white/10 bg-white/10 px-4 text-sm font-medium text-white outline-none transition focus:border-sky-300"
                                        >
                                            <option v-for="issue in issueCategories" :key="issue.value" :value="issue.value" class="text-slate-950">
                                                {{ issue.title }}
                                            </option>
                                        </select>
                                        <InputError :message="form.errors.issue_type" />
                                    </div>

                                    <div class="space-y-2">
                                        <Label for="issue-reference" class="text-sm font-bold text-slate-100">Invoice / ID transaksi</Label>
                                        <Input
                                            id="issue-reference"
                                            v-model="form.transaction_reference"
                                            type="text"
                                            placeholder="Opsional, mis. INV-12345"
                                            class="h-[52px] rounded-[22px] border-white/10 bg-white/10 text-white placeholder:text-slate-400"
                                        />
                                        <InputError :message="form.errors.transaction_reference" />
                                    </div>

                                    <div class="space-y-2">
                                        <Label for="issue-email" class="text-sm font-bold text-slate-100">{{ accountEmailLabel }}</Label>
                                        <Input
                                            id="issue-email"
                                            v-model="form.account_email"
                                            type="email"
                                            :placeholder="accountEmailPlaceholder"
                                            class="h-[52px] rounded-[22px] border-white/10 bg-white/10 text-white placeholder:text-slate-400"
                                        />
                                        <InputError :message="form.errors.account_email" />
                                    </div>

                                    <div class="space-y-2">
                                        <Label class="text-sm font-bold text-slate-100">WhatsApp dari profil</Label>
                                        <div
                                            class="flex min-h-[52px] items-center rounded-[22px] border border-white/10 bg-white/10 px-4 text-sm font-medium text-white"
                                            :class="hasProfileWhatsapp ? '' : 'border-amber-300/40 bg-amber-400/10 text-amber-100'"
                                        >
                                            {{ hasProfileWhatsapp ? profileWhatsapp : 'Nomor WhatsApp belum ada di profil akunmu.' }}
                                        </div>
                                        <p class="text-xs leading-6 text-slate-400">
                                            Update hasil proses akan dikirim ke nomor ini.
                                        </p>
                                    </div>
                                </div>

                                <div v-if="!hasProfileWhatsapp" class="rounded-[24px] border border-amber-300/30 bg-amber-400/10 px-4 py-4 text-sm leading-7 text-amber-100">
                                    Isi nomor WhatsApp dulu di profil supaya laporan bisa diproses dan hasil invite ulang bisa dikabari ke akunmu.
                                    <a :href="profileEditUrl" class="ml-1 font-bold underline underline-offset-4">Buka profil</a>
                                </div>

                                <div class="rounded-[26px] border border-white/10 bg-white/6 p-4">
                                    <div class="flex items-start gap-3">
                                        <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl" :class="selectedIssue.tone">
                                            <component :is="selectedIssue.icon" class="size-5" />
                                        </span>
                                        <div>
                                            <p class="text-sm font-black text-white">{{ selectedIssue.title }}</p>
                                            <p class="mt-1 text-sm leading-7 text-slate-300">{{ selectedIssue.description }}</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <Label for="issue-message" class="text-sm font-bold text-slate-100">Ceritakan masalahnya</Label>
                                    <textarea
                                        id="issue-message"
                                        v-model="form.issue_message"
                                        rows="6"
                                        placeholder="Contoh: akun ChatGPT sudah berhasil login, tapi masih tampil Free. Saya sudah relogin dua kali dan premium belum aktif."
                                        class="w-full rounded-[24px] border border-white/10 bg-white/10 px-4 py-4 text-sm leading-7 text-white outline-none transition placeholder:text-slate-400 focus:border-sky-300"
                                    />
                                    <InputError :message="form.errors.issue_message" />
                                </div>

                                <div class="space-y-3">
                                    <Label for="issue-proof" class="text-sm font-bold text-slate-100">Upload bukti masalah</Label>
                                    <label
                                        for="issue-proof"
                                        class="flex cursor-pointer flex-col gap-3 rounded-[24px] border border-dashed border-white/20 bg-white/5 px-4 py-5 text-sm text-slate-300 transition hover:bg-white/10"
                                    >
                                        <span class="inline-flex w-fit items-center gap-2 rounded-full border border-white/10 bg-white/10 px-3 py-1.5 text-xs font-bold uppercase tracking-[0.12em] text-sky-200">
                                            <Paperclip class="size-4" />
                                            {{ selectedProofName ? 'Bukti siap diunggah' : 'Pilih screenshot / PDF' }}
                                        </span>
                                        <span class="leading-7">
                                            {{ selectedProofName || 'Upload bukti login gagal, akun masih Free, pesan error, atau screenshot yang menjelaskan kendalanya.' }}
                                        </span>
                                    </label>
                                    <input id="issue-proof" ref="proofInput" type="file" accept=".jpg,.jpeg,.png,.webp,.pdf" class="hidden" @change="updateProof" />
                                    <InputError :message="form.errors.proof" />
                                </div>

                                <div class="flex flex-col gap-3 sm:flex-row">
                                    <Button
                                        type="submit"
                                        :disabled="form.processing || !hasProfileWhatsapp"
                                        class="h-[52px] flex-1 rounded-full bg-white px-6 text-sm font-black text-slate-950 shadow-[0_16px_36px_rgba(255,255,255,0.14)] hover:bg-slate-100"
                                    >
                                        <Send class="mr-2 size-4.5" />
                                        {{ form.processing ? 'Mengirim laporan...' : 'Kirim laporan akun' }}
                                    </Button>

                                    <a
                                        :href="supportChatUrl"
                                        class="inline-flex h-[52px] items-center justify-center gap-2 rounded-full border border-white/10 px-6 text-sm font-bold text-slate-100 transition hover:bg-white/10"
                                    >
                                        Hubungi support
                                        <ChevronRight class="size-4.5" />
                                    </a>
                                </div>
                            </form>
                        </section>
                    </div>
                </div>
            </section>
        </main>
    </PublicLayout>
</template>
