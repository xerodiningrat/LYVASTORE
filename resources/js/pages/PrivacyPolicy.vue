<script setup lang="ts">
import PublicLayout from '@/layouts/PublicLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Database, LockKeyhole, ShieldCheck } from 'lucide-vue-next';

const highlights = [
    {
        title: 'Data yang dikumpulkan',
        description: 'Kami hanya mengumpulkan data yang diperlukan untuk transaksi, dukungan pelanggan, dan peningkatan layanan.',
        icon: Database,
    },
    {
        title: 'Keamanan data',
        description: 'Lyva Indonesia berupaya menjaga data pengguna dengan langkah teknis dan operasional yang wajar.',
        icon: LockKeyhole,
    },
    {
        title: 'Penggunaan yang transparan',
        description: 'Data digunakan untuk memproses pesanan, mengirim informasi penting, dan meningkatkan kualitas layanan.',
        icon: ShieldCheck,
    },
];

const sections = [
    {
        title: '1. Informasi yang Kami Kumpulkan',
        body: [
            'Lyva Indonesia dapat mengumpulkan informasi seperti nama, alamat email, nomor WhatsApp, data akun tujuan top up, riwayat transaksi, metode pembayaran, serta informasi teknis dasar saat pengguna mengakses situs.',
            'Jenis data yang dikumpulkan dapat berbeda tergantung fitur yang digunakan, jenis produk digital, dan proses verifikasi yang diperlukan.',
        ],
    },
    {
        title: '2. Cara Kami Menggunakan Data',
        body: [
            'Data pengguna digunakan untuk memproses transaksi, memvalidasi pembayaran, mengirim pembaruan pesanan, memberikan bantuan pelanggan, menangani kendala akun, dan menjaga keamanan layanan.',
            'Dalam beberapa kondisi, data juga dapat digunakan untuk analisis internal dan pengembangan fitur agar pengalaman penggunaan menjadi lebih baik.',
        ],
    },
    {
        title: '3. Pembagian Data kepada Pihak Ketiga',
        body: [
            'Lyva Indonesia dapat membagikan data yang relevan kepada mitra pembayaran, penyedia produk digital, atau penyedia layanan teknis hanya sejauh diperlukan untuk menjalankan layanan.',
            'Kami tidak menjual data pribadi pengguna kepada pihak lain untuk tujuan yang tidak terkait dengan operasional layanan.',
        ],
    },
    {
        title: '4. Penyimpanan dan Keamanan Data',
        body: [
            'Kami menerapkan langkah pengamanan yang wajar untuk melindungi data pengguna dari akses tanpa izin, penyalahgunaan, atau perubahan yang tidak sah.',
            'Walaupun demikian, tidak ada sistem yang dapat dijamin sepenuhnya bebas risiko, sehingga pengguna juga diharapkan menjaga keamanan akun dan perangkatnya sendiri.',
        ],
    },
    {
        title: '5. Cookies dan Data Teknis',
        body: [
            'Situs dapat menggunakan cookies, sesi login, dan data teknis dasar untuk menjaga performa layanan, pengalaman pengguna, serta kebutuhan autentikasi dan keamanan.',
            'Data teknis tersebut dapat mencakup jenis perangkat, browser, halaman yang diakses, atau waktu akses.',
        ],
    },
    {
        title: '6. Retensi Data',
        body: [
            'Data pengguna disimpan selama diperlukan untuk keperluan transaksi, dukungan pelanggan, keamanan layanan, kepatuhan internal, dan penyelesaian sengketa jika diperlukan.',
            'Periode penyimpanan dapat berbeda tergantung jenis data dan konteks penggunaan layanan.',
        ],
    },
    {
        title: '7. Hak Pengguna',
        body: [
            'Pengguna dapat menghubungi Lyva Indonesia untuk menanyakan informasi umum terkait data pribadi, pembaruan informasi kontak, atau permintaan terkait data sepanjang memungkinkan secara operasional dan hukum.',
        ],
    },
    {
        title: '8. Perubahan Kebijakan Privasi',
        body: [
            'Kebijakan Privasi ini dapat diperbarui sewaktu-waktu untuk menyesuaikan perubahan layanan, teknologi, maupun kebutuhan operasional Lyva Indonesia.',
            'Versi terbaru yang ditampilkan di situs merupakan versi yang berlaku saat ini.',
        ],
    },
    {
        title: '9. Kontak',
        body: [
            'Untuk pertanyaan terkait privasi dan penggunaan data, pengguna dapat menghubungi kanal resmi Lyva Indonesia yang tersedia di situs.',
        ],
    },
];
</script>

<template>
    <Head title="Kebijakan Privasi" />

    <PublicLayout active-nav="topup">
        <main class="relative overflow-hidden px-4 py-8 sm:px-6 lg:px-8 lg:py-10">
            <div class="pointer-events-none absolute inset-x-0 top-0 h-72 bg-[radial-gradient(circle_at_top,rgba(14,165,233,0.14),transparent_58%)]" />
            <div class="pointer-events-none absolute inset-0">
                <div class="absolute left-[-6%] top-[12%] h-72 w-72 rounded-full bg-sky-200/30 blur-3xl" />
                <div class="absolute right-[-2%] top-[16%] h-80 w-80 rounded-full bg-indigo-200/24 blur-3xl" />
            </div>

            <section class="relative mx-auto max-w-6xl overflow-hidden rounded-[36px] border border-white/85 bg-[linear-gradient(180deg,rgba(255,255,255,0.96)_0%,rgba(240,249,255,0.97)_100%)] p-6 shadow-[0_32px_90px_rgba(14,165,233,0.10)] backdrop-blur-xl sm:p-8 lg:p-10">
                <div class="pointer-events-none absolute inset-x-0 top-0 h-40 bg-[radial-gradient(circle_at_top,rgba(14,165,233,0.14),transparent_65%)]" />

                <div class="relative z-10 grid gap-8 lg:grid-cols-[1.05fr,0.95fr] lg:gap-10">
                    <div>
                        <p class="text-[0.72rem] font-semibold uppercase tracking-[0.26em] text-sky-600">Privacy</p>
                        <h1 class="mt-4 text-4xl font-black tracking-tight text-slate-950 [font-family:'Space Grotesk',sans-serif] sm:text-5xl">
                            Kebijakan Privasi Lyva Indonesia
                        </h1>
                        <p class="mt-5 max-w-2xl text-sm leading-7 text-slate-600 sm:text-base">
                            Halaman ini menjelaskan bagaimana Lyva Indonesia mengumpulkan, menggunakan, menyimpan, dan melindungi informasi pengguna saat menggunakan situs dan layanan kami.
                        </p>
                        <p class="mt-4 text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">
                            Berlaku sejak 31 Maret 2026
                        </p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-3 lg:grid-cols-1">
                        <article
                            v-for="item in highlights"
                            :key="item.title"
                            class="rounded-[24px] border border-white/80 bg-white/78 p-5 shadow-[0_18px_40px_rgba(15,23,42,0.06)] backdrop-blur"
                        >
                            <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-sky-500/10 text-sky-600">
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

            <section class="relative mx-auto mt-8 max-w-6xl rounded-[32px] border border-white/85 bg-white/84 p-6 shadow-[0_24px_70px_rgba(15,23,42,0.06)] backdrop-blur sm:p-8 lg:p-10">
                <div class="space-y-8">
                    <article v-for="section in sections" :key="section.title" class="rounded-[24px] border border-slate-200/80 bg-slate-50/80 p-5 sm:p-6">
                        <h2 class="text-lg font-black text-slate-950 [font-family:'Space Grotesk',sans-serif] sm:text-xl">
                            {{ section.title }}
                        </h2>
                        <div class="mt-4 space-y-3 text-sm leading-7 text-slate-600 sm:text-[0.98rem]">
                            <p v-for="paragraph in section.body" :key="paragraph">
                                {{ paragraph }}
                            </p>
                        </div>
                    </article>
                </div>
            </section>
        </main>
    </PublicLayout>
</template>
