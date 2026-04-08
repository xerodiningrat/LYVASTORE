<script setup lang="ts">
import PublicLayout from '@/layouts/PublicLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight, CircleUserRound, LockKeyhole, ShieldCheck, Trash2 } from 'lucide-vue-next';

const steps = [
    {
        title: '1. Login ke akun kamu',
        description: 'Masuk ke akun Lyva Indonesia melalui aplikasi atau website resmi dengan email dan password akun yang aktif.',
    },
    {
        title: '2. Buka halaman Profil',
        description: 'Masuk ke menu Profil, lalu buka bagian Danger Zone yang berisi opsi penghapusan akun.',
    },
    {
        title: '3. Pilih "Hapus akun sekarang"',
        description: 'Tekan tombol hapus akun, lalu masukkan password untuk memastikan permintaan dilakukan oleh pemilik akun.',
    },
    {
        title: '4. Konfirmasi penghapusan',
        description: 'Setelah konfirmasi berhasil, akses login akun akan dihentikan dan akun tidak dapat digunakan kembali.',
    },
];

const highlights = [
    {
        title: 'Permintaan dilakukan sendiri oleh pengguna',
        description: 'Penghapusan akun tersedia langsung di akun Lyva Indonesia milik pengguna setelah login.',
        icon: CircleUserRound,
    },
    {
        title: 'Verifikasi password diperlukan',
        description: 'Sistem meminta password akun sebelum penghapusan agar permintaan tidak dilakukan oleh pihak lain.',
        icon: LockKeyhole,
    },
    {
        title: 'Akses akun dihentikan permanen',
        description: 'Setelah dikonfirmasi, akun dan akses login tidak bisa dipulihkan melalui proses biasa.',
        icon: Trash2,
    },
];

const retainedData = [
    'Riwayat transaksi, data pembayaran, atau catatan yang wajib disimpan untuk kebutuhan akuntansi, pencegahan penipuan, penyelesaian sengketa, dan kepatuhan hukum dapat disimpan selama diperlukan.',
    'Data teknis atau log keamanan tertentu dapat dipertahankan sementara untuk menjaga integritas sistem dan investigasi penyalahgunaan layanan.',
];
</script>

<template>
    <Head title="Hapus Akun" />

    <PublicLayout active-nav="topup">
        <main class="relative overflow-hidden px-4 py-8 sm:px-6 lg:px-8 lg:py-10">
            <div class="pointer-events-none absolute inset-x-0 top-0 h-72 bg-[radial-gradient(circle_at_top,rgba(239,68,68,0.14),transparent_58%)]" />
            <div class="pointer-events-none absolute inset-0">
                <div class="absolute left-[-6%] top-[10%] h-72 w-72 rounded-full bg-rose-200/28 blur-3xl" />
                <div class="absolute right-[-2%] top-[16%] h-80 w-80 rounded-full bg-orange-200/24 blur-3xl" />
            </div>

            <section class="relative mx-auto max-w-6xl overflow-hidden rounded-[36px] border border-white/85 bg-[linear-gradient(180deg,rgba(255,255,255,0.96)_0%,rgba(255,247,247,0.97)_100%)] p-6 shadow-[0_32px_90px_rgba(239,68,68,0.08)] backdrop-blur-xl sm:p-8 lg:p-10">
                <div class="pointer-events-none absolute inset-x-0 top-0 h-40 bg-[radial-gradient(circle_at_top,rgba(239,68,68,0.14),transparent_65%)]" />

                <div class="relative z-10 grid gap-8 lg:grid-cols-[1.05fr,0.95fr] lg:gap-10">
                    <div>
                        <p class="text-[0.72rem] font-semibold uppercase tracking-[0.26em] text-rose-600">Account Deletion</p>
                        <h1 class="mt-4 text-4xl font-black tracking-tight text-slate-950 [font-family:'Space Grotesk',sans-serif] sm:text-5xl">
                            Hapus akun Lyva Indonesia
                        </h1>
                        <p class="mt-5 max-w-2xl text-sm leading-7 text-slate-600 sm:text-base">
                            Halaman ini menjelaskan cara pengguna meminta penghapusan akun dan data terkait melalui fitur penghapusan akun yang tersedia di aplikasi dan website Lyva Indonesia.
                        </p>
                        <p class="mt-4 text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">
                            Berlaku sejak 2 April 2026
                        </p>

                        <div class="mt-6 flex flex-wrap gap-3">
                            <Link
                                href="/login"
                                class="inline-flex items-center gap-2 rounded-full bg-slate-950 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800"
                            >
                                Login akun
                                <ArrowRight class="h-4 w-4" />
                            </Link>
                            <Link
                                href="/profil"
                                class="inline-flex items-center gap-2 rounded-full border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-400 hover:bg-slate-50"
                            >
                                Buka profil
                                <ArrowRight class="h-4 w-4" />
                            </Link>
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-3 lg:grid-cols-1">
                        <article
                            v-for="item in highlights"
                            :key="item.title"
                            class="rounded-[24px] border border-white/80 bg-white/78 p-5 shadow-[0_18px_40px_rgba(15,23,42,0.06)] backdrop-blur"
                        >
                            <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-rose-500/10 text-rose-600">
                                <component :is="item.icon" class="h-5 w-5" />
                            </div>
                            <h2 class="mt-4 text-lg font-black text-slate-950 [font-family:'Space Grotesk',sans-serif]">
                                {{ item.title }}
                            </h2>
                            <p class="mt-2 text-sm leading-6 text-slate-600">
                                {{ item.description }}
                            </p>
                        </article>
                    </div>
                </div>
            </section>

            <section class="relative mx-auto mt-8 grid max-w-6xl gap-8 lg:grid-cols-[1.05fr,0.95fr]">
                <div class="rounded-[32px] border border-white/85 bg-white/84 p-6 shadow-[0_24px_70px_rgba(15,23,42,0.06)] backdrop-blur sm:p-8 lg:p-10">
                    <h2 class="text-2xl font-black text-slate-950 [font-family:'Space Grotesk',sans-serif]">
                        Cara meminta penghapusan akun
                    </h2>
                    <div class="mt-6 space-y-4">
                        <article
                            v-for="step in steps"
                            :key="step.title"
                            class="rounded-[24px] border border-slate-200/80 bg-slate-50/80 p-5"
                        >
                            <h3 class="text-base font-black text-slate-950 [font-family:'Space Grotesk',sans-serif]">
                                {{ step.title }}
                            </h3>
                            <p class="mt-2 text-sm leading-7 text-slate-600">
                                {{ step.description }}
                            </p>
                        </article>
                    </div>
                </div>

                <div class="space-y-8">
                    <section class="rounded-[32px] border border-white/85 bg-white/84 p-6 shadow-[0_24px_70px_rgba(15,23,42,0.06)] backdrop-blur sm:p-8">
                        <div class="flex items-start gap-3">
                            <span class="mt-1 flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-emerald-500/10 text-emerald-600">
                                <ShieldCheck class="h-5 w-5" />
                            </span>
                            <div>
                                <h2 class="text-xl font-black text-slate-950 [font-family:'Space Grotesk',sans-serif]">
                                    Data yang dihapus
                                </h2>
                                <p class="mt-3 text-sm leading-7 text-slate-600">
                                    Saat penghapusan akun dikonfirmasi, profil akun, akses login, dan data akun yang digunakan untuk mengakses layanan Lyva Indonesia akan dihentikan dan dihapus dari penggunaan aktif.
                                </p>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-[32px] border border-white/85 bg-white/84 p-6 shadow-[0_24px_70px_rgba(15,23,42,0.06)] backdrop-blur sm:p-8">
                        <h2 class="text-xl font-black text-slate-950 [font-family:'Space Grotesk',sans-serif]">
                            Data yang mungkin tetap disimpan sementara
                        </h2>
                        <div class="mt-4 space-y-3 text-sm leading-7 text-slate-600">
                            <p v-for="item in retainedData" :key="item">
                                {{ item }}
                            </p>
                        </div>
                    </section>

                    <section class="rounded-[32px] border border-rose-100 bg-[linear-gradient(135deg,rgba(254,242,242,0.96),rgba(255,255,255,0.98))] p-6 shadow-[0_24px_70px_rgba(239,68,68,0.08)] sm:p-8">
                        <h2 class="text-xl font-black text-slate-950 [font-family:'Space Grotesk',sans-serif]">
                            Tidak bisa akses aplikasi?
                        </h2>
                        <p class="mt-3 text-sm leading-7 text-slate-600">
                            Pengguna tetap dapat meminta penghapusan akun dengan login melalui website Lyva Indonesia, lalu membuka halaman profil dan memilih opsi <strong>Hapus akun sekarang</strong>.
                        </p>
                        <div class="mt-5">
                            <Link
                                href="/profil"
                                class="inline-flex items-center gap-2 rounded-full bg-rose-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-rose-500"
                            >
                                Buka halaman profil
                                <ArrowRight class="h-4 w-4" />
                            </Link>
                        </div>
                    </section>
                </div>
            </section>
        </main>
    </PublicLayout>
</template>
