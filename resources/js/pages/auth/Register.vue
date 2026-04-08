<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { LoaderCircle, Sparkles } from 'lucide-vue-next';

type RegisterParticle = {
    id: number;
    size: string;
    left: string;
    top: string;
    delay: string;
    duration: string;
    opacity: number;
};

const props = defineProps<{
    affiliateCode?: string;
}>();

const authRedirect =
    typeof window === 'undefined'
        ? ''
        : new URLSearchParams(window.location.search).get('redirect') ?? '';

const form = useForm({
    name: '',
    email: '',
    whatsapp_number: '',
    password: '',
    password_confirmation: '',
    redirect: authRedirect,
    affiliate_code: props.affiliateCode ?? '',
});

const registerParticles: RegisterParticle[] = [
    { id: 1, size: '10px', left: '7%', top: '14%', delay: '0s', duration: '8.6s', opacity: 0.44 },
    { id: 2, size: '14px', left: '18%', top: '24%', delay: '1.3s', duration: '10.1s', opacity: 0.3 },
    { id: 3, size: '9px', left: '29%', top: '16%', delay: '0.6s', duration: '9.1s', opacity: 0.5 },
    { id: 4, size: '12px', left: '38%', top: '32%', delay: '2.2s', duration: '11.4s', opacity: 0.24 },
    { id: 5, size: '16px', left: '48%', top: '18%', delay: '1.7s', duration: '9.9s', opacity: 0.34 },
    { id: 6, size: '11px', left: '61%', top: '27%', delay: '0.5s', duration: '8.9s', opacity: 0.42 },
    { id: 7, size: '18px', left: '72%', top: '15%', delay: '2.1s', duration: '12.1s', opacity: 0.22 },
    { id: 8, size: '10px', left: '83%', top: '24%', delay: '1.2s', duration: '9.6s', opacity: 0.4 },
    { id: 9, size: '13px', left: '91%', top: '33%', delay: '2.8s', duration: '10.8s', opacity: 0.22 },
    { id: 10, size: '10px', left: '12%', top: '63%', delay: '0.9s', duration: '8.7s', opacity: 0.36 },
    { id: 11, size: '16px', left: '24%', top: '78%', delay: '2.4s', duration: '11.2s', opacity: 0.24 },
    { id: 12, size: '9px', left: '43%', top: '72%', delay: '0.2s', duration: '9.5s', opacity: 0.44 },
    { id: 13, size: '14px', left: '64%', top: '79%', delay: '1.9s', duration: '10.9s', opacity: 0.24 },
    { id: 14, size: '11px', left: '86%', top: '68%', delay: '2.6s', duration: '8.8s', opacity: 0.42 },
];

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Daftar">
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous" />
        <link
            href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;700&display=swap"
            rel="stylesheet"
        />
    </Head>

    <div class="min-h-screen bg-[#f8fbff] text-slate-950 [font-family:'Plus Jakarta Sans',sans-serif]">
        <div
            class="pointer-events-none absolute inset-x-0 top-0 h-80"
            style="background-image: radial-gradient(circle at top, rgba(67, 56, 202, 0.22), transparent 58%)"
        />

        <header class="sticky top-0 z-40 border-b border-slate-200/80 bg-white/85 backdrop-blur-xl">
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
                <Link :href="route('home')" class="flex items-center gap-3">
                    <img
                        src="/brand/lyva-mascot-mark.png"
                        alt="Lyva Indonesia"
                        class="h-11 w-11 object-contain drop-shadow-[0_14px_24px_rgba(244,114,182,0.22)]"
                    />

                    <div class="leading-none">
                        <p class="lyva-wordmark lyva-wordmark--md">LYVA INDONESIA</p>
                    </div>
                </Link>

                <div class="flex items-center gap-3">
                    <Link
                        :href="route('home')"
                        class="hidden rounded-full border border-slate-200 px-5 py-2.5 text-sm font-bold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 sm:inline-flex"
                    >
                        Kembali ke Beranda
                    </Link>
                    <Link
                        :href="authRedirect ? `${route('login')}?redirect=${encodeURIComponent(authRedirect)}` : route('login')"
                        class="inline-flex rounded-full border border-indigo-700 px-5 py-2.5 text-sm font-bold text-indigo-700 transition hover:bg-indigo-700 hover:text-white"
                    >
                        Masuk
                    </Link>
                </div>
            </div>
        </header>

        <main class="relative overflow-hidden px-4 py-10 sm:px-6 lg:min-h-[calc(100vh-84px)] lg:px-8 lg:py-14">
            <div class="pointer-events-none absolute inset-0">
                <div class="login-orb absolute left-[-5%] top-[7%] h-72 w-72 rounded-full bg-indigo-300/30 blur-3xl" />
                <div class="login-orb login-orb--alt absolute right-[0%] top-[10%] h-80 w-80 rounded-full bg-sky-200/32 blur-3xl" />
                <div class="login-orb login-orb--soft absolute bottom-[3%] left-[8%] h-80 w-80 rounded-full bg-violet-200/24 blur-3xl" />
                <div class="login-orb login-orb--alt login-orb--soft absolute bottom-[8%] right-[12%] h-64 w-64 rounded-full bg-indigo-200/24 blur-3xl" />

                <span
                    v-for="particle in registerParticles"
                    :key="particle.id"
                    class="login-particle"
                    :style="{
                        width: particle.size,
                        height: particle.size,
                        left: particle.left,
                        top: particle.top,
                        animationDelay: particle.delay,
                        animationDuration: particle.duration,
                        opacity: particle.opacity,
                    }"
                />
            </div>

            <div class="relative mx-auto flex w-full max-w-6xl items-center justify-center">
                <div class="relative w-full max-w-[620px]">
                    <div class="absolute inset-x-16 top-2 h-32 rounded-full bg-indigo-400/22 blur-3xl" />
                    <div class="absolute -left-10 top-14 h-56 w-44 rotate-[-10deg] rounded-[34px] border border-white/35 bg-white/35 backdrop-blur-md" />
                    <div class="absolute -right-10 bottom-12 h-48 w-44 rotate-[12deg] rounded-[34px] border border-white/30 bg-white/30 backdrop-blur-md" />

                    <section class="relative overflow-hidden rounded-[38px] border border-white/85 bg-[linear-gradient(180deg,rgba(255,255,255,0.95)_0%,rgba(244,247,255,0.96)_100%)] p-7 shadow-[0_36px_120px_rgba(99,102,241,0.14)] backdrop-blur-xl sm:p-10">
                        <div class="absolute inset-x-0 top-0 h-44 bg-[radial-gradient(circle_at_top,rgba(67,56,202,0.16),transparent_62%)]" />
                        <div class="absolute inset-x-10 bottom-0 h-px bg-gradient-to-r from-transparent via-white/70 to-transparent" />

                        <div class="relative z-10">
                            <div class="flex items-center gap-3">
                                <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-700">
                                    <Sparkles class="size-4.5" />
                                </span>
                                <div>
                                    <p class="text-[0.68rem] font-semibold uppercase tracking-[0.24em] text-slate-500">Daftar Akun</p>
                                    <h1 class="text-2xl font-black tracking-tight text-slate-950 [font-family:'Space Grotesk',sans-serif]">
                                        Bikin akun baru lebih cepat
                                    </h1>
                                </div>
                            </div>

                            <p class="mt-4 text-sm leading-7 text-slate-600">
                                Isi data utama kamu dulu. Setelah daftar, kami kirim kode OTP ke WhatsApp untuk verifikasi nomor aktif kamu.
                            </p>

                            <div class="mt-6 rounded-[24px] border border-white/80 bg-white/70 p-4 shadow-[0_18px_36px_rgba(15,23,42,0.05)]">
                                <p class="text-[0.7rem] font-semibold uppercase tracking-[0.22em] text-indigo-500">Nomor Aktif</p>
                                <p class="mt-2 text-sm leading-6 text-slate-600">
                                    Pakai nomor WhatsApp yang aktif karena kode verifikasi akun akan dikirim ke sana.
                                </p>
                            </div>

                            <div
                                v-if="form.affiliate_code"
                                class="mt-4 rounded-[24px] border border-emerald-200 bg-emerald-50/80 p-4 shadow-[0_18px_36px_rgba(16,185,129,0.08)]"
                            >
                                <p class="text-[0.7rem] font-semibold uppercase tracking-[0.22em] text-emerald-600">Referral Affiliate</p>
                                <p class="mt-2 text-sm leading-6 text-slate-600">
                                    Kamu mendaftar lewat link affiliate dengan kode
                                    <span class="font-bold text-slate-900">{{ form.affiliate_code }}</span>.
                                </p>
                            </div>

                            <form @submit.prevent="submit" class="mt-8 flex flex-col gap-5">
                                <div class="grid gap-5 sm:grid-cols-2">
                                    <div class="grid gap-2 sm:col-span-2">
                                        <Label for="name" class="text-sm font-semibold text-slate-700">Nama Lengkap</Label>
                                        <Input
                                            id="name"
                                            v-model="form.name"
                                            type="text"
                                            required
                                            autofocus
                                            tabindex="1"
                                            autocomplete="name"
                                            placeholder="Nama lengkap kamu"
                                            class="h-12 rounded-2xl border-slate-200 bg-white/90 px-4 shadow-[0_10px_24px_rgba(15,23,42,0.04)] focus-visible:ring-2 focus-visible:ring-indigo-500"
                                        />
                                        <InputError :message="form.errors.name" />
                                    </div>

                                    <div class="grid gap-2">
                                        <Label for="email" class="text-sm font-semibold text-slate-700">Email</Label>
                                        <Input
                                            id="email"
                                            v-model="form.email"
                                            type="email"
                                            required
                                            tabindex="2"
                                            autocomplete="email"
                                            placeholder="email@contoh.com"
                                            class="h-12 rounded-2xl border-slate-200 bg-white/90 px-4 shadow-[0_10px_24px_rgba(15,23,42,0.04)] focus-visible:ring-2 focus-visible:ring-indigo-500"
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
                                            tabindex="3"
                                            autocomplete="tel"
                                            inputmode="tel"
                                            placeholder="08xxxxxxxxxx"
                                            class="h-12 rounded-2xl border-slate-200 bg-white/90 px-4 shadow-[0_10px_24px_rgba(15,23,42,0.04)] focus-visible:ring-2 focus-visible:ring-indigo-500"
                                        />
                                        <InputError :message="form.errors.whatsapp_number" />
                                    </div>

                                    <div class="grid gap-2">
                                        <Label for="password" class="text-sm font-semibold text-slate-700">Password</Label>
                                        <Input
                                            id="password"
                                            v-model="form.password"
                                            type="password"
                                            required
                                            tabindex="4"
                                            autocomplete="new-password"
                                            placeholder="Masukkan password"
                                            class="h-12 rounded-2xl border-slate-200 bg-white/90 px-4 shadow-[0_10px_24px_rgba(15,23,42,0.04)] focus-visible:ring-2 focus-visible:ring-indigo-500"
                                        />
                                        <InputError :message="form.errors.password" />
                                    </div>

                                    <div class="grid gap-2">
                                        <Label for="password_confirmation" class="text-sm font-semibold text-slate-700">Konfirmasi Password</Label>
                                        <Input
                                            id="password_confirmation"
                                            v-model="form.password_confirmation"
                                            type="password"
                                            required
                                            tabindex="5"
                                            autocomplete="new-password"
                                            placeholder="Ulangi password"
                                            class="h-12 rounded-2xl border-slate-200 bg-white/90 px-4 shadow-[0_10px_24px_rgba(15,23,42,0.04)] focus-visible:ring-2 focus-visible:ring-indigo-500"
                                        />
                                        <InputError :message="form.errors.password_confirmation" />
                                    </div>
                                </div>

                                <Button
                                    type="submit"
                                    tabindex="6"
                                    :disabled="form.processing"
                                    class="mt-3 h-[3.35rem] w-full rounded-[20px] bg-indigo-700 text-base font-bold text-white shadow-[0_22px_40px_rgba(67,56,202,0.3)] hover:bg-indigo-800"
                                >
                                    <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                                    Buat Akun Sekarang
                                </Button>

                                <div class="text-center text-sm text-slate-500">
                                    Sudah punya akun?
                                    <Link
                                        :href="authRedirect ? `${route('login')}?redirect=${encodeURIComponent(authRedirect)}` : route('login')"
                                        class="font-bold text-indigo-700 transition hover:text-indigo-600"
                                    >
                                        Masuk
                                    </Link>
                                </div>
                            </form>
                        </div>
                    </section>
                </div>
            </div>
        </main>
    </div>
</template>
