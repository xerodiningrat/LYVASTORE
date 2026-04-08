<script setup lang="ts">
import { AlertTriangle, BadgeDollarSign, Boxes, Package2, ReceiptText, Send, ShieldAlert } from 'lucide-vue-next';
import { Link } from '@inertiajs/vue3';

defineProps<{
    stats: Record<string, number>;
    topProducts: Array<Record<string, string | number>>;
    stockAlerts: Array<Record<string, string | number>>;
}>();

const number = new Intl.NumberFormat('id-ID');
const formatCurrency = (value: number) => `Rp ${number.format(value)}`;
</script>

<template>
    <section class="grid gap-5 xl:grid-cols-[minmax(0,1.05fr)_minmax(0,0.95fr)_minmax(320px,0.92fr)]">
        <section class="rounded-[30px] border border-slate-200/80 bg-white p-5 shadow-[0_24px_70px_rgba(15,23,42,0.07)]">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <div class="text-[11px] uppercase tracking-[0.18em] text-slate-400">Produk terlaris</div>
                    <div class="mt-2 text-xl font-semibold tracking-tight text-slate-950">Top produk berdasarkan omzet selesai</div>
                </div>
                <div class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-700">
                    {{ number.format(topProducts.length) }} produk
                </div>
            </div>

            <div class="mt-5 space-y-3">
                <article
                    v-for="product in topProducts"
                    :key="`${String(product.rank)}-${String(product.productName)}`"
                    class="flex items-center justify-between gap-4 rounded-[24px] border border-slate-200/80 bg-slate-50/80 px-4 py-4"
                >
                    <div class="flex min-w-0 items-center gap-4">
                        <div class="flex size-12 items-center justify-center rounded-[18px] bg-slate-900 text-sm font-semibold text-white">
                            #{{ product.rank }}
                        </div>
                        <div class="min-w-0">
                            <div class="text-sm font-semibold text-slate-950">{{ product.productName }}</div>
                            <div class="mt-1 text-xs text-slate-500">
                                {{ number.format(Number(product.ordersCount ?? 0)) }} order selesai • update terakhir {{ product.lastOrderLabel }}
                            </div>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-lg font-semibold tracking-tight text-slate-950">{{ formatCurrency(Number(product.revenue ?? 0)) }}</div>
                        <div class="text-[11px] uppercase tracking-[0.16em] text-emerald-600">omzet</div>
                    </div>
                </article>

                <div v-if="!topProducts.length" class="rounded-[24px] border border-dashed border-slate-200 bg-slate-50 px-4 py-5 text-sm text-slate-500">
                    Belum ada produk selesai yang bisa dirangking sekarang.
                </div>
            </div>
        </section>

        <section class="rounded-[30px] border border-slate-200/80 bg-white p-5 shadow-[0_24px_70px_rgba(15,23,42,0.07)]">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <div class="text-[11px] uppercase tracking-[0.18em] text-slate-400">Alert stok manual</div>
                    <div class="mt-2 text-xl font-semibold tracking-tight text-slate-950">Paket yang mulai tipis atau kosong</div>
                </div>
                <div class="rounded-full bg-rose-50 px-3 py-1 text-xs font-medium text-rose-700">
                    {{ number.format(Number(stats.lowStockPackages ?? 0)) }} alert
                </div>
            </div>

            <div class="mt-5 space-y-3">
                <Link
                    v-for="alert in stockAlerts"
                    :key="`${String(alert.productId)}-${String(alert.packageLabel)}`"
                    :href="String(alert.href ?? '/dashboard/stok-manual')"
                    class="block rounded-[24px] border border-slate-200/80 bg-slate-50/80 px-4 py-4 transition hover:bg-slate-50"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <div class="text-sm font-semibold text-slate-950">{{ alert.productName }}</div>
                            <div class="mt-1 text-sm leading-6 text-slate-600">{{ alert.packageLabel }}</div>
                        </div>
                        <div
                            class="rounded-full px-3 py-1 text-xs font-semibold"
                            :class="Number(alert.waitingCount ?? 0) > 0 ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700'"
                        >
                            {{
                                Number(alert.waitingCount ?? 0) > 0
                                    ? `${number.format(Number(alert.waitingCount ?? 0))} order menunggu`
                                    : `${number.format(Number(alert.availableCount ?? 0))} stok tersedia`
                            }}
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-3 gap-2 text-xs text-slate-500">
                        <div class="rounded-2xl bg-white px-3 py-2">
                            <div class="uppercase tracking-[0.16em] text-slate-400">Tersedia</div>
                            <div class="mt-1 font-semibold text-slate-950">{{ number.format(Number(alert.availableCount ?? 0)) }}</div>
                        </div>
                        <div class="rounded-2xl bg-white px-3 py-2">
                            <div class="uppercase tracking-[0.16em] text-slate-400">Reserved</div>
                            <div class="mt-1 font-semibold text-slate-950">{{ number.format(Number(alert.reservedCount ?? 0)) }}</div>
                        </div>
                        <div class="rounded-2xl bg-white px-3 py-2">
                            <div class="uppercase tracking-[0.16em] text-slate-400">Terpakai</div>
                            <div class="mt-1 font-semibold text-slate-950">{{ number.format(Number(alert.usedCount ?? 0)) }}</div>
                        </div>
                    </div>
                </Link>

                <div v-if="!stockAlerts.length" class="rounded-[24px] border border-dashed border-slate-200 bg-slate-50 px-4 py-5 text-sm text-slate-500">
                    Belum ada paket yang perlu alert stok sekarang. Kondisi stok terlihat aman.
                </div>
            </div>
        </section>

        <div class="space-y-5">
            <section class="rounded-[30px] border border-slate-200/80 bg-white p-5 shadow-[0_24px_70px_rgba(15,23,42,0.07)]">
                <div class="text-[11px] uppercase tracking-[0.18em] text-slate-400">Kontrol cepat</div>
                <div class="mt-2 text-xl font-semibold tracking-tight text-slate-950">Pintu masuk panel penting</div>

                <div class="mt-5 space-y-3">
                    <Link href="/dashboard/transaksi" class="block rounded-[24px] border border-slate-200/80 bg-slate-50/80 px-4 py-4 transition hover:bg-slate-50">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="text-sm font-semibold text-slate-950">Monitor transaksi</div>
                                <div class="mt-1 text-xs leading-5 text-slate-500">Cek pembayaran, status pesanan, dan proses manual yang masih berjalan.</div>
                            </div>
                            <ReceiptText class="mt-1 size-4 text-slate-400" />
                        </div>
                    </Link>

                    <Link href="/dashboard/stok-manual" class="block rounded-[24px] border border-slate-200/80 bg-slate-50/80 px-4 py-4 transition hover:bg-slate-50">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="text-sm font-semibold text-slate-950">Kelola stok manual</div>
                                <div class="mt-1 text-xs leading-5 text-slate-500">Isi akun ChatGPT, CapCut, owner, sharing, dan private account dengan cepat.</div>
                            </div>
                            <Boxes class="mt-1 size-4 text-slate-400" />
                        </div>
                    </Link>

                    <Link href="/dashboard/produk" class="block rounded-[24px] border border-slate-200/80 bg-slate-50/80 px-4 py-4 transition hover:bg-slate-50">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="text-sm font-semibold text-slate-950">Setting produk</div>
                                <div class="mt-1 text-xs leading-5 text-slate-500">Atur gambar, override tampilan, dan materi visual produk.</div>
                            </div>
                            <Package2 class="mt-1 size-4 text-slate-400" />
                        </div>
                    </Link>

                    <Link href="/dashboard/margin" class="block rounded-[24px] border border-slate-200/80 bg-slate-50/80 px-4 py-4 transition hover:bg-slate-50">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="text-sm font-semibold text-slate-950">Setting margin</div>
                                <div class="mt-1 text-xs leading-5 text-slate-500">Jaga harga tetap kompetitif sambil tetap aman buat server dan profit.</div>
                            </div>
                            <BadgeDollarSign class="mt-1 size-4 text-slate-400" />
                        </div>
                    </Link>
                </div>
            </section>

            <section class="rounded-[30px] border border-slate-200/80 bg-white p-5 shadow-[0_24px_70px_rgba(15,23,42,0.07)]">
                <div class="text-[11px] uppercase tracking-[0.18em] text-slate-400">Snapshot cepat</div>
                <div class="mt-2 text-xl font-semibold tracking-tight text-slate-950">Pulse operasional</div>

                <div class="mt-5 space-y-3">
                    <div class="flex items-center justify-between rounded-[22px] border border-slate-200/80 bg-slate-50 px-4 py-3">
                        <div class="flex items-center gap-3">
                            <div class="rounded-2xl bg-rose-50 p-2.5 text-rose-600">
                                <AlertTriangle class="size-4" />
                            </div>
                            <div>
                                <div class="text-sm font-semibold text-slate-950">Butuh stok manual</div>
                                <div class="text-xs text-slate-500">Order yang tertahan karena stok belum cocok</div>
                            </div>
                        </div>
                        <div class="text-lg font-semibold text-slate-950">{{ number.format(Number(stats.waitingManualOrders ?? 0)) }}</div>
                    </div>

                    <div class="flex items-center justify-between rounded-[22px] border border-slate-200/80 bg-slate-50 px-4 py-3">
                        <div class="flex items-center gap-3">
                            <div class="rounded-2xl bg-sky-50 p-2.5 text-sky-600">
                                <Send class="size-4" />
                            </div>
                            <div>
                                <div class="text-sm font-semibold text-slate-950">Siap kirim akun</div>
                                <div class="text-xs text-slate-500">Stok sudah ada dan tinggal dikirim admin</div>
                            </div>
                        </div>
                        <div class="text-lg font-semibold text-slate-950">{{ number.format(Number(stats.readyManualOrders ?? 0)) }}</div>
                    </div>

                    <div class="flex items-center justify-between rounded-[22px] border border-slate-200/80 bg-slate-50 px-4 py-3">
                        <div class="flex items-center gap-3">
                            <div class="rounded-2xl bg-amber-50 p-2.5 text-amber-600">
                                <ShieldAlert class="size-4" />
                            </div>
                            <div>
                                <div class="text-sm font-semibold text-slate-950">Riwayat gagal</div>
                                <div class="text-xs text-slate-500">Order gagal atau kedaluwarsa yang tercatat</div>
                            </div>
                        </div>
                        <div class="text-lg font-semibold text-slate-950">{{ number.format(Number(stats.failedTransactions ?? 0)) }}</div>
                    </div>
                </div>
            </section>
        </div>
    </section>
</template>
