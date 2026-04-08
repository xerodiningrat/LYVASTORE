<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { BadgeCheck, LoaderCircle } from 'lucide-vue-next';

type LoginParticle = {
    id: number;
    size: string;
    left: string;
    top: string;
    delay: string;
    duration: string;
    opacity: number;
};

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

const authRedirect =
    typeof window === 'undefined'
        ? ''
        : new URLSearchParams(window.location.search).get('redirect') ?? '';

const form = useForm({
    email: '',
    password: '',
    remember: false,
    redirect: authRedirect,
});

const loginParticles: LoginParticle[] = [
    { id: 1, size: '10px', left: '8%', top: '16%', delay: '0s', duration: '8.4s', opacity: 0.48 },
    { id: 2, size: '14px', left: '18%', top: '28%', delay: '1.2s', duration: '10.2s', opacity: 0.34 },
    { id: 3, size: '8px', left: '26%', top: '20%', delay: '0.8s', duration: '9.4s', opacity: 0.52 },
    { id: 4, size: '12px', left: '34%', top: '34%', delay: '2.6s', duration: '11.3s', opacity: 0.28 },
    { id: 5, size: '16px', left: '44%', top: '18%', delay: '1.6s', duration: '9.8s', opacity: 0.36 },
    { id: 6, size: '10px', left: '58%', top: '26%', delay: '0.4s', duration: '8.8s', opacity: 0.46 },
    { id: 7, size: '18px', left: '68%', top: '14%', delay: '2.1s', duration: '12.4s', opacity: 0.22 },
    { id: 8, size: '9px', left: '76%', top: '30%', delay: '1.4s', duration: '9.5s', opacity: 0.44 },
    { id: 9, size: '13px', left: '84%', top: '22%', delay: '3s', duration: '10.6s', opacity: 0.28 },
    { id: 10, size: '10px', left: '14%', top: '62%', delay: '1.1s', duration: '8.9s', opacity: 0.4 },
    { id: 11, size: '16px', left: '30%', top: '76%', delay: '2.4s', duration: '11.6s', opacity: 0.24 },
    { id: 12, size: '9px', left: '50%', top: '70%', delay: '0.2s', duration: '9.7s', opacity: 0.42 },
    { id: 13, size: '14px', left: '72%', top: '78%', delay: '1.8s', duration: '10.8s', opacity: 0.28 },
    { id: 14, size: '11px', left: '88%', top: '66%', delay: '2.8s', duration: '8.6s', opacity: 0.46 },
];

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Masuk">
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
            style="background-image: radial-gradient(circle at top, rgba(67, 56, 202, 0.2), transparent 58%)"
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
                        :href="authRedirect ? `${route('register')}?redirect=${encodeURIComponent(authRedirect)}` : route('register')"
                        class="inline-flex rounded-full border border-indigo-700 px-5 py-2.5 text-sm font-bold text-indigo-700 transition hover:bg-indigo-700 hover:text-white"
                    >
                        Daftar
                    </Link>
                </div>
            </div>
        </header>

        <main class="relative overflow-hidden px-4 py-10 sm:px-6 lg:min-h-[calc(100vh-84px)] lg:px-8 lg:py-14">
            <div class="pointer-events-none absolute inset-0">
                <div class="login-orb absolute left-[-4%] top-[8%] h-64 w-64 rounded-full bg-indigo-300/30 blur-3xl" />
                <div class="login-orb login-orb--alt absolute right-[-2%] top-[14%] h-72 w-72 rounded-full bg-sky-200/35 blur-3xl" />
                <div class="login-orb login-orb--soft absolute bottom-[4%] left-[12%] h-80 w-80 rounded-full bg-violet-200/24 blur-3xl" />
                <div class="login-orb login-orb--alt login-orb--soft absolute bottom-[10%] right-[10%] h-64 w-64 rounded-full bg-indigo-200/24 blur-3xl" />

                <span
                    v-for="particle in loginParticles"
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
                <div class="relative w-full max-w-[540px]">
                    <div class="absolute inset-x-14 top-0 h-28 rounded-full bg-indigo-400/20 blur-3xl" />
                    <div class="absolute -left-10 top-16 h-52 w-44 rotate-[-10deg] rounded-[34px] border border-white/35 bg-white/35 backdrop-blur-md" />
                    <div class="absolute -right-8 bottom-12 h-44 w-40 rotate-[12deg] rounded-[34px] border border-white/30 bg-white/30 backdrop-blur-md" />

                    <section class="relative overflow-hidden rounded-[38px] border border-white/85 bg-[linear-gradient(180deg,rgba(255,255,255,0.95)_0%,rgba(244,247,255,0.96)_100%)] p-7 shadow-[0_36px_120px_rgba(99,102,241,0.14)] backdrop-blur-xl sm:p-10">
                        <div class="absolute inset-x-0 top-0 h-40 bg-[radial-gradient(circle_at_top,rgba(67,56,202,0.16),transparent_62%)]" />
                        <div class="absolute inset-x-10 bottom-0 h-px bg-gradient-to-r from-transparent via-white/70 to-transparent" />
                        <div class="relative z-10">
                            <div class="flex items-center gap-3">
                                <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-700">
                                    <BadgeCheck class="size-4.5" />
                                </span>
                                <div>
                                    <p class="text-[0.68rem] font-semibold uppercase tracking-[0.24em] text-slate-500">Masuk Akun</p>
                                    <h1 class="text-2xl font-black tracking-tight text-slate-950 [font-family:'Space Grotesk',sans-serif]">
                                        Selamat datang kembali
                                    </h1>
                                </div>
                            </div>

                            <p class="mt-4 text-sm leading-7 text-slate-600">
                                Masukkan email dan password untuk masuk. Kalau nomor WhatsApp kamu belum terverifikasi, kami arahkan dulu ke halaman OTP.
                            </p>

                            <div
                                v-if="status"
                                class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700"
                            >
                                {{ status }}
                            </div>

                            <form @submit.prevent="submit" class="mt-8 flex flex-col gap-5">
                                <div class="grid gap-5">
                                    <div class="grid gap-2">
                                        <Label for="email" class="text-sm font-semibold text-slate-700">Email</Label>
                                        <Input
                                            id="email"
                                            v-model="form.email"
                                            type="email"
                                            required
                                            autofocus
                                            tabindex="1"
                                            autocomplete="email"
                                            placeholder="email@contoh.com"
                                            class="h-12 rounded-2xl border-slate-200 bg-white/90 px-4 shadow-[0_10px_24px_rgba(15,23,42,0.04)] focus-visible:ring-2 focus-visible:ring-indigo-500"
                                        />
                                        <InputError :message="form.errors.email" />
                                    </div>

                                    <div class="grid gap-2">
                                        <div class="flex items-center justify-between gap-3">
                                            <Label for="password" class="text-sm font-semibold text-slate-700">Password</Label>
                                            <Link
                                                v-if="canResetPassword"
                                                :href="route('password.request')"
                                                class="text-sm font-semibold text-indigo-700 transition hover:text-indigo-600"
                                            >
                                                Lupa password?
                                            </Link>
                                        </div>
                                        <Input
                                            id="password"
                                            v-model="form.password"
                                            type="password"
                                            required
                                            tabindex="2"
                                            autocomplete="current-password"
                                            placeholder="Masukkan password"
                                            class="h-12 rounded-2xl border-slate-200 bg-white/90 px-4 shadow-[0_10px_24px_rgba(15,23,42,0.04)] focus-visible:ring-2 focus-visible:ring-indigo-500"
                                        />
                                        <InputError :message="form.errors.password" />
                                    </div>

                                    <div class="flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-3">
                                        <Label for="remember" class="flex items-center gap-3 text-sm font-medium text-slate-700">
                                            <Checkbox
                                                id="remember"
                                                v-model:checked="form.remember"
                                                tabindex="4"
                                                class="rounded-md border-slate-300 data-[state=checked]:border-indigo-700 data-[state=checked]:bg-indigo-700"
                                            />
                                            <span>Ingat saya</span>
                                        </Label>
                                        <span class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Aman</span>
                                    </div>

                                    <Button
                                        type="submit"
                                        tabindex="5"
                                        :disabled="form.processing"
                                        class="mt-3 h-[3.35rem] w-full rounded-[20px] bg-indigo-700 text-base font-bold text-white shadow-[0_22px_40px_rgba(67,56,202,0.3)] hover:bg-indigo-800"
                                    >
                                        <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                                        Masuk Sekarang
                                    </Button>
                                </div>

                                <div class="text-center text-sm text-slate-500">
                                    Belum punya akun?
                                    <Link
                                        :href="authRedirect ? `${route('register')}?redirect=${encodeURIComponent(authRedirect)}` : route('register')"
                                        class="font-bold text-indigo-700 transition hover:text-indigo-600"
                                    >
                                        Buat akun
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
