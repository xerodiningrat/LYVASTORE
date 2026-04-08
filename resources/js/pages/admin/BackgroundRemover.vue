<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import AdminLayout from '@/layouts/AdminLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { Download, ImageUp, LoaderCircle, ScanSearch, Scissors, ShieldCheck, Sparkles, TriangleAlert } from 'lucide-vue-next';
import { computed, onBeforeUnmount, ref } from 'vue';

const props = defineProps<{
    status?: string;
    error?: string;
    tool: {
        ready: boolean;
        message: string;
        installCommand: string;
        pythonBinary: string;
        scriptPath: string;
        modelDirectory: string;
        timeoutSeconds: number;
        tempRetentionMinutes: number;
    };
    result?: {
        originalName: string;
        originalUrl: string;
        resultName: string;
        resultUrl: string;
        processedAtLabel: string;
        outputSizeLabel: string;
    } | null;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard Admin', href: '/dashboard' },
    { title: 'Hapus Background', href: '/dashboard/hapus-background' },
];

const fileInput = ref<HTMLInputElement | null>(null);
const previewUrl = ref<string | null>(null);

const form = useForm({
    image: null as File | null,
});

const resultAvailable = computed(() => Boolean(props.result?.resultUrl));

const releasePreview = () => {
    if (previewUrl.value?.startsWith('blob:')) {
        URL.revokeObjectURL(previewUrl.value);
    }

    previewUrl.value = null;
};

const openPicker = () => fileInput.value?.click();

const onFileChange = (event: Event) => {
    const input = event.target as HTMLInputElement | null;
    const file = input?.files?.[0] ?? null;

    releasePreview();
    form.image = file;
    previewUrl.value = file ? URL.createObjectURL(file) : null;
};

const submit = () => {
    if (!form.image || !props.tool.ready) {
        return;
    }

    form.post('/dashboard/hapus-background', {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            releasePreview();

            if (fileInput.value) {
                fileInput.value.value = '';
            }
        },
    });
};

onBeforeUnmount(() => {
    releasePreview();
});
</script>

<template>
    <Head title="Hapus Background" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4">
            <section
                class="overflow-hidden rounded-[34px] border border-slate-200/80 bg-[radial-gradient(circle_at_top_left,rgba(14,165,233,0.14),transparent_32%),radial-gradient(circle_at_bottom_right,rgba(16,185,129,0.12),transparent_34%),linear-gradient(180deg,rgba(255,255,255,0.98),rgba(248,250,252,0.96))] p-6 shadow-[0_28px_80px_rgba(15,23,42,0.08)] lg:p-8"
            >
                <div class="grid gap-6 xl:grid-cols-[minmax(0,1.08fr)_minmax(320px,0.92fr)]">
                    <div>
                        <div class="text-[11px] font-semibold uppercase tracking-[0.28em] text-sky-500">Background Remover</div>
                        <h1 class="mt-3 max-w-3xl text-3xl font-semibold tracking-[-0.04em] text-slate-950 lg:text-[2.65rem]">
                            Upload gambar, hapus latar belakangnya, lalu download hasil PNG transparan.
                        </h1>
                        <p class="mt-4 max-w-2xl text-sm leading-7 text-slate-600">
                            Tool ini berjalan lokal di server memakai Python + AI segmentation, jadi kamu tidak perlu kirim gambar ke layanan pihak
                            ketiga.
                        </p>

                        <div class="mt-6 grid gap-3 sm:grid-cols-3">
                            <div class="rounded-[24px] border border-sky-100 bg-sky-50/80 p-4 shadow-[0_14px_34px_rgba(14,165,233,0.08)]">
                                <div class="text-[11px] font-semibold uppercase tracking-[0.2em] text-sky-500">Format</div>
                                <div class="mt-2 text-xl font-semibold text-sky-700">JPG, PNG, WEBP</div>
                            </div>
                            <div class="rounded-[24px] border border-emerald-100 bg-emerald-50/80 p-4 shadow-[0_14px_34px_rgba(16,185,129,0.08)]">
                                <div class="text-[11px] font-semibold uppercase tracking-[0.2em] text-emerald-500">Output</div>
                                <div class="mt-2 text-xl font-semibold text-emerald-700">PNG transparan</div>
                            </div>
                            <div class="rounded-[24px] border border-slate-200 bg-slate-50/90 p-4 shadow-[0_14px_34px_rgba(148,163,184,0.08)]">
                                <div class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-400">Batas Upload</div>
                                <div class="mt-2 text-xl font-semibold text-slate-950">Maks. 8 MB</div>
                            </div>
                        </div>
                    </div>

                    <div
                        class="rounded-[30px] border p-5 shadow-[0_18px_44px_rgba(148,163,184,0.12)] backdrop-blur-sm"
                        :class="
                            tool.ready
                                ? 'border-emerald-200/80 bg-white/85'
                                : 'border-amber-200/80 bg-[linear-gradient(180deg,rgba(255,251,235,0.96),rgba(255,255,255,0.98))]'
                        "
                    >
                        <div class="flex items-start gap-3">
                            <div
                                class="flex size-11 items-center justify-center rounded-[20px]"
                                :class="tool.ready ? 'bg-emerald-50 text-emerald-600' : 'bg-amber-50 text-amber-600'"
                            >
                                <ShieldCheck v-if="tool.ready" class="size-5" />
                                <TriangleAlert v-else class="size-5" />
                            </div>
                            <div class="min-w-0">
                                <div class="text-base font-semibold text-slate-950">
                                    {{ tool.ready ? 'Tool siap dipakai' : 'Perlu setup Python dulu' }}
                                </div>
                                <p class="mt-1 text-sm leading-6 text-slate-600">{{ tool.message }}</p>
                            </div>
                        </div>

                        <div class="mt-5 space-y-3 rounded-[24px] border border-slate-200/80 bg-slate-50/80 p-4 text-sm text-slate-600">
                            <div class="flex items-start gap-3">
                                <Sparkles class="mt-0.5 size-4 text-sky-500" />
                                <div>Run pertama bisa sedikit lebih lama karena model AI diunduh dan disimpan lokal.</div>
                            </div>
                            <div class="flex items-start gap-3">
                                <ScanSearch class="mt-0.5 size-4 text-emerald-500" />
                                <div>
                                    Python: <span class="font-medium text-slate-900">{{ tool.pythonBinary }}</span>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <Scissors class="mt-0.5 size-4 text-rose-500" />
                                <div>File upload dan hasil akan dihapus otomatis sekitar {{ tool.tempRetentionMinutes }} menit setelah dibuat.</div>
                            </div>
                            <div class="rounded-[18px] border border-slate-200 bg-white px-3 py-3">
                                <div class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-400">Command install</div>
                                <code class="mt-2 block break-all text-[12px] leading-6 text-slate-700">{{ tool.installCommand }}</code>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="grid gap-6 xl:grid-cols-[minmax(340px,0.92fr)_minmax(0,1.08fr)]">
                <section class="rounded-[30px] border border-slate-200/80 bg-white p-6 shadow-[0_24px_70px_rgba(15,23,42,0.07)]">
                    <div class="text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-400">Upload Gambar</div>
                    <h2 class="mt-2 text-2xl font-semibold tracking-tight text-slate-950">Pilih gambar yang mau dibersihkan</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">
                        Cocok untuk foto produk, logo, gambar profil, atau aset marketplace yang butuh background transparan.
                    </p>

                    <div
                        v-if="status"
                        class="mt-5 rounded-[22px] border border-emerald-200 bg-emerald-50/90 px-4 py-3 text-sm font-medium text-emerald-700"
                    >
                        {{ status }}
                    </div>

                    <div v-if="error" class="mt-5 rounded-[22px] border border-rose-200 bg-rose-50/90 px-4 py-3 text-sm font-medium text-rose-700">
                        {{ error }}
                    </div>

                    <div class="mt-5 space-y-4">
                        <input ref="fileInput" type="file" accept=".jpg,.jpeg,.png,.webp" class="hidden" @change="onFileChange" />

                        <button
                            type="button"
                            class="group flex w-full flex-col items-center justify-center rounded-[28px] border border-dashed border-slate-300 bg-[linear-gradient(180deg,rgba(248,250,252,0.9),rgba(255,255,255,1))] px-6 py-10 text-center transition hover:border-sky-300 hover:bg-sky-50/40"
                            @click="openPicker"
                        >
                            <span
                                class="flex size-14 items-center justify-center rounded-full bg-slate-100 text-slate-700 transition group-hover:bg-sky-100 group-hover:text-sky-700"
                            >
                                <ImageUp class="size-6" />
                            </span>
                            <span class="mt-4 text-lg font-semibold text-slate-950">
                                {{ form.image ? form.image.name : 'Klik untuk pilih gambar' }}
                            </span>
                            <span class="mt-2 max-w-sm text-sm leading-6 text-slate-500">
                                Upload satu file dulu. Hasilnya akan dibuat sebagai PNG transparan siap download.
                            </span>
                        </button>

                        <InputError :message="form.errors.image" />

                        <div v-if="previewUrl" class="overflow-hidden rounded-[26px] border border-slate-200/80 bg-slate-50">
                            <div class="px-4 py-3 text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-400">Preview upload</div>
                            <div class="border-t border-slate-200 p-6">
                                <div class="flex justify-center">
                                    <div
                                        class="inline-flex rounded-[28px] border border-slate-200 bg-white p-3 shadow-[0_18px_36px_rgba(15,23,42,0.08)]"
                                    >
                                        <img :src="previewUrl" alt="Preview upload" class="block max-h-[340px] rounded-[22px] object-contain" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <Button
                            :disabled="form.processing || !form.image || !tool.ready"
                            class="h-12 w-full rounded-full text-base font-semibold"
                            @click="submit"
                        >
                            <LoaderCircle v-if="form.processing" class="mr-2 size-4 animate-spin" />
                            {{ form.processing ? 'Memproses gambar...' : 'Hapus background sekarang' }}
                        </Button>
                    </div>
                </section>

                <section class="rounded-[30px] border border-slate-200/80 bg-white p-6 shadow-[0_24px_70px_rgba(15,23,42,0.07)]">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <div class="text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-400">Hasil</div>
                            <h2 class="mt-2 text-2xl font-semibold tracking-tight text-slate-950">Sebelum dan sesudah</h2>
                        </div>
                        <a
                            v-if="resultAvailable && result"
                            :href="result.resultUrl"
                            :download="result.resultName"
                            class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:text-slate-950"
                        >
                            <Download class="size-4" />
                            Download PNG
                        </a>
                    </div>

                    <template v-if="resultAvailable && result">
                        <div class="mt-5 grid gap-4 lg:grid-cols-2">
                            <div class="overflow-hidden rounded-[26px] border border-slate-200/80 bg-slate-50">
                                <div class="px-4 py-3 text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-400">Original</div>
                                <div class="border-t border-slate-200 p-6">
                                    <div class="flex justify-center">
                                        <div
                                            class="inline-flex rounded-[28px] border border-slate-200 bg-white p-3 shadow-[0_18px_36px_rgba(15,23,42,0.08)]"
                                        >
                                            <img
                                                :src="result.originalUrl"
                                                :alt="result.originalName"
                                                class="block max-h-[320px] rounded-[22px] object-contain"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="overflow-hidden rounded-[26px] border border-emerald-200/80 bg-emerald-50/40">
                                <div class="px-4 py-3 text-[11px] font-semibold uppercase tracking-[0.2em] text-emerald-600">PNG Transparan</div>
                                <div class="border-t border-emerald-200/70 p-6">
                                    <div class="flex justify-center">
                                        <div
                                            class="inline-flex rounded-[28px] border border-emerald-200/80 p-3 shadow-[0_18px_36px_rgba(15,23,42,0.08)] [background-image:linear-gradient(45deg,#dbeafe_25%,transparent_25%),linear-gradient(-45deg,#dbeafe_25%,transparent_25%),linear-gradient(45deg,transparent_75%,#dbeafe_75%),linear-gradient(-45deg,transparent_75%,#dbeafe_75%)] [background-position:0_0,0_10px,10px_-10px,-10px_0] [background-size:20px_20px]"
                                        >
                                            <img :src="result.resultUrl" :alt="result.resultName" class="block max-h-[320px] object-contain" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-5 grid gap-3 sm:grid-cols-3">
                            <div class="rounded-[22px] border border-slate-200 bg-slate-50/80 p-4">
                                <div class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-400">Nama hasil</div>
                                <div class="mt-2 break-all text-sm font-semibold text-slate-950">{{ result.resultName }}</div>
                            </div>
                            <div class="rounded-[22px] border border-slate-200 bg-slate-50/80 p-4">
                                <div class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-400">Ukuran file</div>
                                <div class="mt-2 text-sm font-semibold text-slate-950">{{ result.outputSizeLabel }}</div>
                            </div>
                            <div class="rounded-[22px] border border-slate-200 bg-slate-50/80 p-4">
                                <div class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-400">Diproses</div>
                                <div class="mt-2 text-sm font-semibold text-slate-950">{{ result.processedAtLabel }}</div>
                            </div>
                        </div>
                    </template>

                    <div
                        v-else
                        class="mt-5 flex min-h-[420px] flex-col items-center justify-center rounded-[28px] border border-dashed border-slate-200 bg-[linear-gradient(180deg,rgba(248,250,252,0.92),rgba(255,255,255,1))] px-6 text-center"
                    >
                        <span class="flex size-14 items-center justify-center rounded-full bg-slate-100 text-slate-700">
                            <Sparkles class="size-6" />
                        </span>
                        <h3 class="mt-4 text-lg font-semibold text-slate-950">Belum ada hasil diproses</h3>
                        <p class="mt-2 max-w-md text-sm leading-6 text-slate-500">
                            Upload satu gambar di panel kiri, lalu sistem akan membuat versi tanpa background di area ini.
                        </p>
                    </div>
                </section>
            </section>
        </div>
    </AdminLayout>
</template>
