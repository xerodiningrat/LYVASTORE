<script setup lang="ts">
import PublicLayout from '@/layouts/PublicLayout.vue';
import { Head } from '@inertiajs/vue3';
import { FileText, ShieldCheck, WalletCards } from 'lucide-vue-next';

const highlights = [
    {
        title: 'Penggunaan layanan',
        description: 'Setiap transaksi, pemesanan, dan penggunaan layanan digital Lyva Indonesia tunduk pada syarat resmi ini.',
        icon: FileText,
    },
    {
        title: 'Keamanan transaksi',
        description: 'Kami berupaya menjaga proses checkout, verifikasi pembayaran, dan pengiriman produk tetap aman dan terpantau.',
        icon: ShieldCheck,
    },
    {
        title: 'Pembayaran & pengiriman',
        description: 'Produk digital diproses sesuai status pembayaran dan ketentuan layanan masing-masing produk.',
        icon: WalletCards,
    },
];

const sections = [
    {
        title: '1. Persetujuan Penggunaan',
        body: [
            'Dengan mengakses atau menggunakan situs dan layanan Lyva Indonesia, pengguna dianggap telah membaca, memahami, dan menyetujui Syarat & Ketentuan ini.',
            'Jika pengguna tidak menyetujui ketentuan ini, pengguna disarankan untuk tidak menggunakan layanan Lyva Indonesia.',
        ],
    },
    {
        title: '2. Layanan yang Disediakan',
        body: [
            'Lyva Indonesia menyediakan layanan top up game, voucher digital, produk langganan, serta layanan digital terkait lainnya yang ditampilkan di situs.',
            'Ketersediaan produk, harga, metode pembayaran, dan estimasi proses dapat berubah sewaktu-waktu sesuai kebutuhan operasional dan kebijakan mitra penyedia layanan.',
        ],
    },
    {
        title: '3. Akurasi Data Pemesanan',
        body: [
            'Pengguna wajib memberikan data pemesanan yang benar, lengkap, dan sesuai kebutuhan produk, termasuk namun tidak terbatas pada email, ID akun, nomor WhatsApp, atau data akun lainnya.',
            'Kesalahan input yang berasal dari pengguna dapat menyebabkan proses tertunda, gagal, atau memerlukan verifikasi tambahan.',
        ],
    },
    {
        title: '4. Pembayaran dan Konfirmasi',
        body: [
            'Pesanan diproses setelah pembayaran terverifikasi oleh sistem atau admin Lyva Indonesia.',
            'Kami berhak menahan, menunda, atau membatalkan pesanan jika terdeteksi indikasi kesalahan pembayaran, duplikasi transaksi, penyalahgunaan promo, atau aktivitas yang dinilai berisiko.',
        ],
    },
    {
        title: '5. Pengiriman Produk Digital',
        body: [
            'Sebagian besar produk digital diproses secara otomatis atau semi-otomatis. Waktu pengiriman dapat berbeda tergantung produk, metode pembayaran, gangguan sistem, dan status layanan mitra.',
            'Untuk produk tertentu yang memerlukan tindakan manual, pengguna wajib mengikuti instruksi yang diberikan oleh sistem atau admin.',
        ],
    },
    {
        title: '6. Refund, Revisi, dan Kendala Pesanan',
        body: [
            'Permintaan refund atau revisi dipertimbangkan berdasarkan jenis produk, status proses, dan penyebab kendala. Tidak semua produk digital dapat direfund setelah diproses.',
            'Pengguna dapat menghubungi kanal bantuan resmi Lyva Indonesia jika ada pesanan yang bermasalah, belum terkirim, atau tidak sesuai.',
        ],
    },
    {
        title: '7. Larangan Penggunaan',
        body: [
            'Pengguna dilarang menggunakan layanan Lyva Indonesia untuk tindakan yang melanggar hukum, merugikan pihak lain, menyalahgunakan promo, melakukan penipuan, atau mencoba mengganggu sistem.',
            'Lyva Indonesia berhak membatasi akses, menolak pesanan, atau menonaktifkan akun pengguna jika ditemukan pelanggaran terhadap ketentuan ini.',
        ],
    },
    {
        title: '8. Batasan Tanggung Jawab',
        body: [
            'Lyva Indonesia tidak bertanggung jawab atas kerugian tidak langsung, kegagalan akibat kesalahan data dari pengguna, gangguan layanan pihak ketiga, atau perubahan kebijakan dari penyedia produk digital.',
            'Tanggung jawab Lyva Indonesia terbatas pada upaya penyelesaian terbaik yang wajar sesuai data transaksi dan status layanan yang tersedia.',
        ],
    },
    {
        title: '9. Perubahan Ketentuan',
        body: [
            'Syarat & Ketentuan ini dapat diperbarui sewaktu-waktu untuk menyesuaikan perubahan layanan, regulasi, atau kebutuhan operasional.',
            'Versi terbaru yang dipublikasikan di situs Lyva Indonesia berlaku sejak tanggal ditampilkan pada halaman ini.',
        ],
    },
    {
        title: '10. Kontak',
        body: [
            'Untuk pertanyaan terkait Syarat & Ketentuan ini, pengguna dapat menghubungi kanal resmi yang tersedia di situs Lyva Indonesia.',
        ],
    },
];
</script>

<template>
    <Head title="Syarat & Ketentuan" />

    <PublicLayout active-nav="topup">
        <main class="relative overflow-hidden px-4 py-8 sm:px-6 lg:px-8 lg:py-10">
            <div class="pointer-events-none absolute inset-x-0 top-0 h-72 bg-[radial-gradient(circle_at_top,rgba(79,70,229,0.16),transparent_58%)]" />
            <div class="pointer-events-none absolute inset-0">
                <div class="absolute left-[-6%] top-[10%] h-72 w-72 rounded-full bg-indigo-200/30 blur-3xl" />
                <div class="absolute right-[-2%] top-[14%] h-80 w-80 rounded-full bg-sky-200/28 blur-3xl" />
            </div>

            <section class="relative mx-auto max-w-6xl overflow-hidden rounded-[36px] border border-white/85 bg-[linear-gradient(180deg,rgba(255,255,255,0.96)_0%,rgba(244,247,255,0.97)_100%)] p-6 shadow-[0_32px_90px_rgba(99,102,241,0.10)] backdrop-blur-xl sm:p-8 lg:p-10">
                <div class="pointer-events-none absolute inset-x-0 top-0 h-40 bg-[radial-gradient(circle_at_top,rgba(67,56,202,0.14),transparent_65%)]" />

                <div class="relative z-10 grid gap-8 lg:grid-cols-[1.05fr,0.95fr] lg:gap-10">
                    <div>
                        <p class="text-[0.72rem] font-semibold uppercase tracking-[0.26em] text-indigo-600">Legal</p>
                        <h1 class="mt-4 text-4xl font-black tracking-tight text-slate-950 [font-family:'Space Grotesk',sans-serif] sm:text-5xl">
                            Syarat &amp; Ketentuan Lyva Indonesia
                        </h1>
                        <p class="mt-5 max-w-2xl text-sm leading-7 text-slate-600 sm:text-base">
                            Halaman ini menjelaskan ketentuan penggunaan situs, transaksi, produk digital, dan interaksi pengguna dengan layanan Lyva Indonesia.
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
                            <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-indigo-600/10 text-indigo-600">
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
