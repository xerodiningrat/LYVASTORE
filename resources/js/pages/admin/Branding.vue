<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AdminLayout from '@/layouts/AdminLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { ImagePlus, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps<{
    branding: {
        title: string;
        tagline: string;
        logoUrl: string | null;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard Admin', href: '/dashboard' },
    { title: 'Branding Sidebar', href: '/dashboard/branding' },
];

const logoInput = ref<HTMLInputElement | null>(null);
const previewUrl = ref<string | null>(null);

const form = useForm({
    title: props.branding.title,
    tagline: props.branding.tagline,
    logo: null as File | null,
    remove_logo: false as boolean,
});

const openPicker = () => logoInput.value?.click();

const onFileChange = (event: Event) => {
    const input = event.target as HTMLInputElement | null;
    const file = input?.files?.[0] ?? null;

    if (previewUrl.value?.startsWith('blob:')) {
        URL.revokeObjectURL(previewUrl.value);
    }

    form.logo = file;
    form.remove_logo = false;
    previewUrl.value = file ? URL.createObjectURL(file) : null;
};

const submit = () => {
    form.transform((data) => ({
        ...data,
        _method: 'patch',
    })).post('/dashboard/branding', {
        forceFormData: true,
        preserveScroll: true,
        onFinish: () => {
            form.transform((data) => data);
        },
    });
};

const removeLogo = () => {
    form.logo = null;
    form.remove_logo = true;
    previewUrl.value = null;
    submit();
};
</script>

<template>
    <Head title="Branding Sidebar" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4">
            <section class="rounded-[30px] border border-slate-200/80 bg-white p-6 shadow-[0_24px_70px_rgba(15,23,42,0.07)]">
                <div class="grid gap-6 xl:grid-cols-[0.95fr,1.05fr]">
                    <div class="rounded-[28px] border border-slate-200/80 bg-slate-50/70 p-5">
                        <div class="text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-400">Preview sidebar</div>
                        <div class="mt-5 rounded-[28px] border border-slate-200 bg-white p-5">
                            <div class="flex items-center gap-4">
                                <div class="flex size-16 items-center justify-center overflow-hidden rounded-[22px] bg-slate-100 ring-1 ring-slate-200">
                                    <img v-if="previewUrl || branding.logoUrl" :src="previewUrl || branding.logoUrl || ''" :alt="form.title" class="size-full object-cover" />
                                    <img v-else src="/brand/lyva-mascot-mark.png" alt="Lyva Admin" class="size-10 object-contain" />
                                </div>
                                <div>
                                    <div class="text-lg font-black uppercase tracking-[0.18em] text-slate-950">{{ form.title || 'LYVA ADMIN' }}</div>
                                    <div class="text-sm text-slate-500">{{ form.tagline || 'Control Center' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <div class="text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-400">Branding Sidebar</div>
                        <h1 class="mt-3 text-3xl font-semibold tracking-tight text-slate-950">Ganti gambar dan copy sidebar admin</h1>
                        <p class="mt-3 text-sm leading-7 text-slate-600">
                            Setting ini dipakai khusus untuk panel owner/admin. Kamu bisa ganti logo sidebar, judul panel, dan tagline pendek.
                        </p>

                        <div class="mt-8 space-y-5">
                            <div class="grid gap-2">
                                <Label class="text-sm font-semibold text-slate-700">Judul sidebar</Label>
                                <Input v-model="form.title" class="h-12 rounded-2xl border-slate-200 bg-white" placeholder="LYVA ADMIN" />
                                <InputError :message="form.errors.title" />
                            </div>

                            <div class="grid gap-2">
                                <Label class="text-sm font-semibold text-slate-700">Tagline</Label>
                                <Input v-model="form.tagline" class="h-12 rounded-2xl border-slate-200 bg-white" placeholder="Control Center" />
                                <InputError :message="form.errors.tagline" />
                            </div>

                            <div class="space-y-3 rounded-[24px] border border-slate-200/80 bg-slate-50/70 p-4">
                                <input ref="logoInput" type="file" accept=".jpg,.jpeg,.png,.webp" class="hidden" @change="onFileChange" />
                                <div class="text-sm font-semibold text-slate-950">Gambar sidebar</div>
                                <p class="text-sm leading-6 text-slate-500">Format yang didukung JPG, PNG, atau WEBP maksimal 3 MB.</p>
                                <div class="flex flex-wrap gap-3">
                                    <Button type="button" class="rounded-full" @click="openPicker">
                                        <ImagePlus class="mr-2 size-4" />
                                        Pilih gambar
                                    </Button>
                                    <Button
                                        v-if="branding.logoUrl || previewUrl"
                                        type="button"
                                        variant="outline"
                                        class="rounded-full border-rose-200 text-rose-600 hover:bg-rose-50 hover:text-rose-700"
                                        @click="removeLogo"
                                    >
                                        <Trash2 class="mr-2 size-4" />
                                        Hapus gambar
                                    </Button>
                                </div>
                                <InputError :message="form.errors.logo" />
                            </div>

                            <Button :disabled="form.processing" class="rounded-full" @click="submit">
                                Simpan branding sidebar
                            </Button>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
