<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { TransitionRoot } from '@headlessui/vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { KeyRound, LockKeyhole, ShieldCheck, TriangleAlert, UserRound } from 'lucide-vue-next';
import { ref } from 'vue';

interface Props {
    status?: string;
}

defineProps<Props>();

const passwordInput = ref<HTMLInputElement>();
const currentPasswordInput = ref<HTMLInputElement>();

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const inputClass =
    'h-12 rounded-2xl border-slate-200 bg-white text-slate-950 placeholder:text-slate-400 shadow-[0_10px_24px_rgba(15,23,42,0.04)] focus-visible:ring-indigo-500 focus-visible:ring-offset-0';

const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: (errors: Record<string, string>) => {
            if (errors.password) {
                form.reset('password', 'password_confirmation');
                if (passwordInput.value instanceof HTMLInputElement) {
                    passwordInput.value.focus();
                }
            }

            if (errors.current_password) {
                form.reset('current_password');
                if (currentPasswordInput.value instanceof HTMLInputElement) {
                    currentPasswordInput.value.focus();
                }
            }
        },
    });
};
</script>

<template>
    <PublicLayout>
        <Head title="Keamanan Akun" />

        <main class="relative overflow-hidden">
            <div class="pointer-events-none absolute inset-0">
                <div class="absolute left-[-8%] top-8 h-72 w-72 rounded-full bg-indigo-200/35 blur-3xl" />
                <div class="absolute right-[-10%] top-24 h-80 w-80 rounded-full bg-sky-200/30 blur-3xl" />
            </div>

            <div class="relative mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <section class="overflow-hidden rounded-[32px] border border-white/80 bg-white/85 shadow-[0_24px_80px_rgba(15,23,42,0.08)] backdrop-blur">
                    <div class="grid gap-8 px-6 py-7 lg:grid-cols-[1.15fr,0.85fr] lg:px-8 lg:py-8">
                        <div>
                            <p class="text-[0.72rem] font-bold uppercase tracking-[0.22em] text-indigo-600">Keamanan Akun</p>
                            <h1 class="mt-3 text-3xl font-black tracking-tight text-slate-950 [font-family:'Space Grotesk',sans-serif]">
                                Atur password dengan aman
                            </h1>
                            <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-600">
                                Halaman ini khusus untuk keamanan akun, jadi tidak ikut layout dashboard lama. Ganti password secara berkala supaya akun tetap aman.
                            </p>

                            <form class="mt-8 space-y-5" @submit.prevent="updatePassword">
                                <div class="grid gap-2">
                                    <Label for="current_password" class="text-sm font-semibold text-slate-700">Password saat ini</Label>
                                    <Input
                                        id="current_password"
                                        ref="currentPasswordInput"
                                        v-model="form.current_password"
                                        type="password"
                                        autocomplete="current-password"
                                        placeholder="Masukkan password saat ini"
                                        :class="inputClass"
                                    />
                                    <InputError :message="form.errors.current_password" />
                                </div>

                                <div class="grid gap-2">
                                    <Label for="password" class="text-sm font-semibold text-slate-700">Password baru</Label>
                                    <Input
                                        id="password"
                                        ref="passwordInput"
                                        v-model="form.password"
                                        type="password"
                                        autocomplete="new-password"
                                        placeholder="Masukkan password baru"
                                        :class="inputClass"
                                    />
                                    <InputError :message="form.errors.password" />
                                </div>

                                <div class="grid gap-2">
                                    <Label for="password_confirmation" class="text-sm font-semibold text-slate-700">Konfirmasi password baru</Label>
                                    <Input
                                        id="password_confirmation"
                                        v-model="form.password_confirmation"
                                        type="password"
                                        autocomplete="new-password"
                                        placeholder="Ulangi password baru"
                                        :class="inputClass"
                                    />
                                    <InputError :message="form.errors.password_confirmation" />
                                </div>

                                <div class="flex flex-wrap items-center gap-3 pt-2">
                                    <Button :disabled="form.processing" class="h-12 rounded-full px-6">
                                        Simpan password baru
                                    </Button>

                                    <TransitionRoot
                                        :show="form.recentlySuccessful || Boolean(status)"
                                        enter="transition ease-in-out duration-200"
                                        enter-from="opacity-0 translate-y-1"
                                        leave="transition ease-in-out duration-150"
                                        leave-to="opacity-0 translate-y-1"
                                    >
                                        <p class="text-sm font-medium text-emerald-600">
                                            {{ status ?? 'Password berhasil diperbarui.' }}
                                        </p>
                                    </TransitionRoot>
                                </div>
                            </form>
                        </div>

                        <div class="space-y-6">
                            <article class="rounded-[28px] border border-indigo-100 bg-[linear-gradient(135deg,rgba(238,242,255,0.95),rgba(255,255,255,0.96))] p-5">
                                <p class="text-[0.72rem] font-bold uppercase tracking-[0.22em] text-indigo-600">Panduan Singkat</p>
                                <div class="mt-4 space-y-3">
                                    <div class="rounded-[22px] border border-white/90 bg-white/85 p-4">
                                        <div class="flex items-start gap-3">
                                            <span class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-700">
                                                <KeyRound class="size-4.5" />
                                            </span>
                                            <div>
                                                <p class="text-sm font-bold text-slate-900">Gunakan password yang unik</p>
                                                <p class="mt-1 text-sm leading-6 text-slate-600">
                                                    Hindari password yang sama dengan email, media sosial, atau akun game kamu.
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="rounded-[22px] border border-white/90 bg-white/85 p-4">
                                        <div class="flex items-start gap-3">
                                            <span class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-sky-50 text-sky-700">
                                                <ShieldCheck class="size-4.5" />
                                            </span>
                                            <div>
                                                <p class="text-sm font-bold text-slate-900">Ganti secara berkala</p>
                                                <p class="mt-1 text-sm leading-6 text-slate-600">
                                                    Kalau merasa akun pernah dipakai di perangkat lain, langsung ubah password dari halaman ini.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </article>

                            <article class="rounded-[28px] border border-slate-200 bg-white/85 p-5 shadow-[0_18px_48px_rgba(15,23,42,0.05)]">
                                <p class="text-[0.72rem] font-bold uppercase tracking-[0.22em] text-slate-500">Akses Cepat</p>
                                <div class="mt-4 space-y-3">
                                    <Link
                                        :href="route('profile.edit')"
                                        class="flex items-center justify-between rounded-[22px] border border-slate-200 bg-slate-50/80 px-4 py-3 text-sm font-bold text-slate-700 transition hover:-translate-y-0.5 hover:text-indigo-700"
                                    >
                                        <span class="flex items-center gap-3">
                                            <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-700">
                                                <UserRound class="size-4.5" />
                                            </span>
                                            Buka profil saya
                                        </span>
                                    </Link>

                                    <Link
                                        :href="route('home')"
                                        class="flex items-center justify-between rounded-[22px] border border-slate-200 bg-slate-50/80 px-4 py-3 text-sm font-bold text-slate-700 transition hover:-translate-y-0.5 hover:text-indigo-700"
                                    >
                                        <span class="flex items-center gap-3">
                                            <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-slate-100 text-slate-700">
                                                <LockKeyhole class="size-4.5" />
                                            </span>
                                            Kembali ke beranda
                                        </span>
                                    </Link>
                                </div>
                            </article>

                            <article class="rounded-[28px] border border-amber-200 bg-amber-50/85 p-5">
                                <div class="flex items-start gap-3">
                                    <span class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-amber-100 text-amber-700">
                                        <TriangleAlert class="size-4.5" />
                                    </span>
                                    <div>
                                        <p class="text-sm font-bold text-amber-900">Penting</p>
                                        <p class="mt-1 text-sm leading-6 text-amber-800">
                                            Setelah password diubah, simpan password barunya baik-baik dan jangan dibagikan ke siapa pun.
                                        </p>
                                    </div>
                                </div>
                            </article>
                        </div>
                    </div>
                </section>
            </div>
        </main>
    </PublicLayout>
</template>
