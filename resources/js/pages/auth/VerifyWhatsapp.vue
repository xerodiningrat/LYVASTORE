<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { MessageCircleMore, LoaderCircle, ShieldCheck } from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps<{
    status?: string;
    maskedWhatsappNumber: string;
    cooldownSeconds: number;
    expiresAt?: string | null;
}>();

const form = useForm({
    code: '',
});

const resendForm = useForm({});
const remainingCooldown = ref(Math.max(0, Number(props.cooldownSeconds ?? 0)));
let cooldownTimer: ReturnType<typeof window.setInterval> | null = null;

const resendButtonLabel = computed(() => (remainingCooldown.value > 0 ? `Kirim ulang ${remainingCooldown.value} dtk` : 'Kirim ulang kode'));

const submit = () => {
    form.post(route('verification.whatsapp.verify'));
};

const resend = () => {
    resendForm.post(route('verification.whatsapp.send'), {
        onStart: () => {
            if (typeof window !== 'undefined' && cooldownTimer) {
                window.clearInterval(cooldownTimer);
                cooldownTimer = null;
            }
        },
    });
};

onMounted(() => {
    if (typeof window === 'undefined' || remainingCooldown.value <= 0) {
        return;
    }

    cooldownTimer = window.setInterval(() => {
        if (remainingCooldown.value <= 1) {
            remainingCooldown.value = 0;

            if (cooldownTimer) {
                window.clearInterval(cooldownTimer);
                cooldownTimer = null;
            }

            return;
        }

        remainingCooldown.value -= 1;
    }, 1000);
});

onBeforeUnmount(() => {
    if (typeof window !== 'undefined' && cooldownTimer) {
        window.clearInterval(cooldownTimer);
        cooldownTimer = null;
    }
});
</script>

<template>
    <Head title="Verifikasi WhatsApp">
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
            style="background-image: radial-gradient(circle at top, rgba(34, 197, 94, 0.18), transparent 58%)"
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
            </div>
        </header>

        <main class="relative overflow-hidden px-4 py-10 sm:px-6 lg:min-h-[calc(100vh-84px)] lg:px-8 lg:py-14">
            <div class="relative mx-auto flex w-full max-w-6xl items-center justify-center">
                <div class="relative w-full max-w-[560px]">
                    <section class="relative overflow-hidden rounded-[38px] border border-white/85 bg-[linear-gradient(180deg,rgba(255,255,255,0.95)_0%,rgba(244,247,255,0.96)_100%)] p-7 shadow-[0_36px_120px_rgba(34,197,94,0.12)] backdrop-blur-xl sm:p-10">
                        <div class="absolute inset-x-0 top-0 h-40 bg-[radial-gradient(circle_at_top,rgba(34,197,94,0.16),transparent_62%)]" />

                        <div class="relative z-10">
                            <div class="flex items-center gap-3">
                                <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700">
                                    <MessageCircleMore class="size-4.5" />
                                </span>
                                <div>
                                    <p class="text-[0.68rem] font-semibold uppercase tracking-[0.24em] text-slate-500">Verifikasi WhatsApp</p>
                                    <h1 class="text-2xl font-black tracking-tight text-slate-950 [font-family:'Space Grotesk',sans-serif]">
                                        Masukkan kode dari WhatsApp
                                    </h1>
                                </div>
                            </div>

                            <p class="mt-4 text-sm leading-7 text-slate-600">
                                Kami kirim kode OTP ke <span class="font-bold text-slate-900">{{ maskedWhatsappNumber }}</span>. Masukkan 6 digit kode untuk
                                mengaktifkan akun kamu.
                            </p>

                            <div class="mt-6 rounded-[24px] border border-white/80 bg-white/70 p-4 shadow-[0_18px_36px_rgba(15,23,42,0.05)]">
                                <div class="flex items-start gap-3">
                                    <span class="mt-0.5 flex h-9 w-9 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700">
                                        <ShieldCheck class="size-4" />
                                    </span>
                                    <div class="space-y-1">
                                        <p class="text-sm font-bold text-slate-900">Kode berlaku 10 menit</p>
                                        <p class="text-sm leading-6 text-slate-600">
                                            Kalau belum masuk, kamu bisa kirim ulang kode dari halaman ini.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div
                                v-if="status"
                                class="mt-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700"
                            >
                                {{ status }}
                            </div>

                            <form @submit.prevent="submit" class="mt-8 flex flex-col gap-5">
                                <div class="grid gap-2">
                                    <Label for="code" class="text-sm font-semibold text-slate-700">Kode verifikasi</Label>
                                    <Input
                                        id="code"
                                        v-model="form.code"
                                        type="text"
                                        required
                                        autofocus
                                        inputmode="numeric"
                                        maxlength="6"
                                        placeholder="Masukkan 6 digit kode"
                                        class="h-12 rounded-2xl border-slate-200 bg-white/90 px-4 text-center text-lg font-bold tracking-[0.32em] shadow-[0_10px_24px_rgba(15,23,42,0.04)] focus-visible:ring-2 focus-visible:ring-emerald-500"
                                    />
                                    <InputError :message="form.errors.code" />
                                </div>

                                <Button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="h-[3.35rem] w-full rounded-[20px] bg-emerald-600 text-base font-bold text-white shadow-[0_22px_40px_rgba(34,197,94,0.28)] hover:bg-emerald-700"
                                >
                                    <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                                    Verifikasi Sekarang
                                </Button>
                            </form>

                            <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <Button
                                    type="button"
                                    variant="outline"
                                    :disabled="resendForm.processing || remainingCooldown > 0"
                                    class="rounded-full border-slate-200 px-5 py-2.5 text-sm font-bold text-slate-700"
                                    @click="resend"
                                >
                                    <LoaderCircle v-if="resendForm.processing" class="mr-2 h-4 w-4 animate-spin" />
                                    {{ resendButtonLabel }}
                                </Button>

                                <Link :href="route('logout')" method="post" as="button" class="text-sm font-semibold text-slate-500 transition hover:text-slate-700">
                                    Pakai akun lain
                                </Link>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </main>
    </div>
</template>
