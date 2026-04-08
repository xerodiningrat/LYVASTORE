<script setup lang="ts">
import PublicLayout from '@/layouts/PublicLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    BadgeCheck,
    ChevronLeft,
    CircleAlert,
    Clock3,
    Copy,
    HelpCircle,
    Landmark,
    LoaderCircle,
    MessageSquareText,
    QrCode,
    ShieldCheck,
    Star,
    WalletCards,
    X,
} from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

type SummaryRow = {
    label: string;
    value: string;
};

type CheckoutConnectorTone = 'active' | 'processing' | 'done' | 'error' | 'idle';

type CheckoutPreview = {
    publicId: string;
    productId: string;
    productName: string;
    productImage?: string | null;
    packageLabel: string;
    quantity: number;
    paymentLabel: string;
    paymentImage?: string | null;
    paymentBadge?: string | null;
    paymentCaption?: string | null;
    paymentType: string;
    paymentDisplayType?: 'qris' | 'bank-transfer' | 'code';
    paymentReferenceLabel?: string | null;
    paymentReferenceValue?: string | null;
    paymentUrl?: string | null;
    qrString?: string | null;
    total: number;
    checkoutNotice?: string | null;
    guaranteeText?: string | null;
    notes?: string[];
    summaryRows?: SummaryRow[];
    transactionId: string;
    status: 'pending' | 'processing' | 'completed' | 'failed' | 'expired';
    paymentStatus: 'unpaid' | 'paid' | 'failed' | 'expired';
    statusLabel: string;
    errorMessage?: string | null;
    paymentDeadlineLabel: string;
    createdAtLabel: string;
    expiresInMinutes: number;
    expiresAtIso?: string | null;
    ratingScore?: number | null;
    ratingComment?: string | null;
    ratedAtLabel?: string | null;
};

const props = defineProps<{
    checkout: CheckoutPreview;
}>();

const formatCurrency = (value: number) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);

const productRows = computed(() => props.checkout.summaryRows?.filter((row) => row.value?.trim()) ?? []);
const productMonogram = computed(() =>
    props.checkout.productName
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part.charAt(0))
        .join('')
        .toUpperCase(),
);
const normalizedPaymentDescriptor = computed(() =>
    [props.checkout.paymentType, props.checkout.paymentLabel, props.checkout.paymentBadge, props.checkout.paymentCaption]
        .filter((value): value is string => typeof value === 'string' && value.trim().length > 0)
        .join(' ')
        .toLowerCase(),
);
const hasQrisLikeDescriptor = computed(() => /qris|qr|scan/.test(normalizedPaymentDescriptor.value));
const paymentDisplayType = computed(() => {
    if (props.checkout.qrString?.trim()) {
        return 'qris';
    }

    if (props.checkout.paymentDisplayType) {
        return props.checkout.paymentDisplayType;
    }

    if (hasQrisLikeDescriptor.value) {
        return 'qris';
    }

    return props.checkout.paymentType === 'bank-transfer' ? 'bank-transfer' : 'code';
});
const isQrisPayment = computed(() => paymentDisplayType.value === 'qris');
const isBankTransferPayment = computed(() => paymentDisplayType.value === 'bank-transfer');
const qrisTitle = computed(() => {
    const label = props.checkout.paymentLabel?.trim();

    return label && /qris|qr/i.test(label) ? label : 'QRIS';
});
const brandedQrisTemplateUrl = '/Gemini_Generated_Image_w132g7w132g7w132.png';
const brandedQrisBoardStyle = {
    left: '6.4%',
    top: '20.4%',
    width: '40.3%',
    height: '40.3%',
    borderRadius: '1.45rem',
};
const brandedQrisImageStyle = {
    left: '9.6%',
    top: '26.2%',
    width: '35.9%',
    height: '35.9%',
};
const paymentReferenceLabel = computed(
    () => props.checkout.paymentReferenceLabel ?? (isBankTransferPayment.value ? 'Nomor Virtual Account' : 'Kode Pembayaran'),
);
const isManualBankAccountPayment = computed(() => /rekening|seabank/i.test(paymentReferenceLabel.value ?? ''));
const rawPaymentReferenceValue = computed(() => {
    if (typeof props.checkout.paymentReferenceValue !== 'string') {
        return '';
    }

    return props.checkout.paymentReferenceValue.trim();
});
const bankTransferReferenceValue = computed(() => {
    if (!isBankTransferPayment.value) {
        return '';
    }

    const normalized = rawPaymentReferenceValue.value.replace(/\s+/g, '');

    return /^\d+$/.test(normalized) ? normalized : '';
});
const hasBankTransferReferenceValue = computed(() => bankTransferReferenceValue.value.length > 0);
const bankTransferInstruction = computed(() =>
    hasBankTransferReferenceValue.value
        ? isManualBankAccountPayment.value
            ? 'Transfer manual ke rekening di bawah ini, lalu cek notifikasi uang masuk melalui email.'
            : 'Selesaikan pembayaran melalui ATM atau mobile banking ke nomor virtual account di bawah ini.'
        : 'Nomor pembayaran bank sedang kami siapkan.',
);
const bankTransferPrimaryTitle = computed(() => (hasBankTransferReferenceValue.value ? paymentReferenceLabel.value : 'Lanjutkan pembayaran'));
const codePaymentReferenceValue = computed(() => {
    if (isBankTransferPayment.value || isQrisPayment.value) {
        return '';
    }

    return rawPaymentReferenceValue.value || props.checkout.transactionId.replace('#', '');
});
const displayPaymentReferenceValue = computed(() =>
    isBankTransferPayment.value ? bankTransferReferenceValue.value : codePaymentReferenceValue.value,
);
const hasCopiedPaymentReference = ref(false);
const hasPaymentReferenceValue = computed(() => Boolean(displayPaymentReferenceValue.value.trim()));
const isRefreshingCheckout = ref(false);
const now = ref(Date.now());
const expiresAt = computed(() => (props.checkout.expiresAtIso ? new Date(props.checkout.expiresAtIso).getTime() : Number.POSITIVE_INFINITY));
const isExpired = computed(
    () =>
        props.checkout.status === 'expired' ||
        (props.checkout.status === 'pending' && Number.isFinite(expiresAt.value) && now.value >= expiresAt.value),
);
const isFailed = computed(() => props.checkout.status === 'failed');
const isProcessing = computed(() => props.checkout.status === 'processing');
const isCompleted = computed(() => props.checkout.status === 'completed');
const isPending = computed(() => props.checkout.status === 'pending');
const isPaymentSettled = computed(() => props.checkout.paymentStatus === 'paid' || isProcessing.value || isCompleted.value);
const isPaymentStepFailed = computed(() => (isFailed.value || isExpired.value) && !isPaymentSettled.value);
const isProcessingStepFailed = computed(() => isFailed.value && isPaymentSettled.value);
const shouldRefreshCheckout = computed(() => isPending.value || isProcessing.value);
const failureReason = computed(() => props.checkout.errorMessage?.trim() ?? '');
const qrisImageUrl = computed(() => {
    const value = props.checkout.qrString?.trim();

    if (!value) {
        return null;
    }

    return `https://api.qrserver.com/v1/create-qr-code/?size=420x420&format=png&margin=12&data=${encodeURIComponent(value)}`;
});
const brandedQrisTemplateStyle = computed(() => ({
    backgroundImage: `url(${brandedQrisTemplateUrl})`,
}));
const remainingMs = computed(() => Math.max(0, expiresAt.value - now.value));
const countdownLabel = computed(() => {
    const totalSeconds = Math.floor(remainingMs.value / 1000);
    const hours = Math.floor(totalSeconds / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);
    const seconds = totalSeconds % 60;
    const hh = String(hours).padStart(2, '0');
    const mm = String(minutes).padStart(2, '0');
    const ss = String(seconds).padStart(2, '0');

    return hours > 0 ? `${hh}:${mm}:${ss}` : `${mm}:${ss}`;
});
const statusBadgeTone = computed(() => {
    if (isCompleted.value) {
        return 'bg-emerald-50 text-emerald-600';
    }

    if (isProcessing.value) {
        return 'bg-blue-50 text-blue-600';
    }

    if (isFailed.value || isExpired.value) {
        return 'bg-red-50 text-red-600';
    }

    return 'bg-orange-50 text-orange-500';
});
const paymentConnectorTone = computed<CheckoutConnectorTone>(() => {
    if (isPaymentSettled.value) {
        return 'done';
    }

    if (isPaymentStepFailed.value) {
        return 'error';
    }

    return 'active';
});
const processingConnectorTone = computed<CheckoutConnectorTone>(() => {
    if (isCompleted.value) {
        return 'done';
    }

    if (isProcessing.value) {
        return 'processing';
    }

    if (isProcessingStepFailed.value) {
        return 'error';
    }

    return 'idle';
});
const connectorFillWidth: Record<CheckoutConnectorTone, string> = {
    active: '58%',
    processing: '58%',
    done: '100%',
    error: '58%',
    idle: '0%',
};
const paymentConnectorFillWidth = computed(() => connectorFillWidth[paymentConnectorTone.value]);
const processingConnectorFillWidth = computed(() => connectorFillWidth[processingConnectorTone.value]);
const ratingScore = ref(5);
const ratingComment = ref('');
const isSubmittingRating = ref(false);
const hasRating = computed(() => Number(props.checkout.ratingScore ?? 0) > 0);
const canRequestRating = computed(() => isCompleted.value && !hasRating.value);

let expiryTimer: ReturnType<typeof setInterval> | null = null;
let checkoutRefreshTimer: ReturnType<typeof setInterval> | null = null;
let copyFeedbackTimer: ReturnType<typeof setTimeout> | null = null;
let initialCheckoutRefreshTimer: ReturnType<typeof setTimeout> | null = null;
const checkoutRefreshIntervalMs = 4000;

const openPaymentPage = () => {
    if (typeof window === 'undefined' || !props.checkout.paymentUrl) {
        return;
    }

    window.open(props.checkout.paymentUrl, '_blank', 'noopener,noreferrer');
};

const reloadCheckoutStatus = () => {
    if (typeof window === 'undefined' || isRefreshingCheckout.value || !shouldRefreshCheckout.value) {
        return;
    }

    if (typeof document !== 'undefined' && document.visibilityState === 'hidden') {
        return;
    }

    isRefreshingCheckout.value = true;

    router.reload({
        only: ['checkout'],
        onSuccess: () => {
            now.value = Date.now();
        },
        onFinish: () => {
            isRefreshingCheckout.value = false;
        },
    });
};

const handleCheckoutVisibilityChange = () => {
    if (typeof document === 'undefined' || document.visibilityState !== 'visible') {
        return;
    }

    reloadCheckoutStatus();
};

const handleCheckoutWindowFocus = () => {
    reloadCheckoutStatus();
};

onMounted(() => {
    expiryTimer = window.setInterval(() => {
        now.value = Date.now();
    }, 1000);

    initialCheckoutRefreshTimer = window.setTimeout(() => {
        reloadCheckoutStatus();
    }, 650);

    checkoutRefreshTimer = window.setInterval(() => {
        reloadCheckoutStatus();
    }, checkoutRefreshIntervalMs);

    document.addEventListener('visibilitychange', handleCheckoutVisibilityChange);
    window.addEventListener('focus', handleCheckoutWindowFocus);
});

onBeforeUnmount(() => {
    if (expiryTimer !== null) {
        window.clearInterval(expiryTimer);
    }

    if (checkoutRefreshTimer !== null) {
        window.clearInterval(checkoutRefreshTimer);
    }

    if (copyFeedbackTimer !== null) {
        window.clearTimeout(copyFeedbackTimer);
    }

    if (initialCheckoutRefreshTimer !== null) {
        window.clearTimeout(initialCheckoutRefreshTimer);
    }

    document.removeEventListener('visibilitychange', handleCheckoutVisibilityChange);
    window.removeEventListener('focus', handleCheckoutWindowFocus);
});

const copyPaymentReference = async () => {
    const value = displayPaymentReferenceValue.value.trim();

    if (!value || typeof window === 'undefined') {
        return;
    }

    try {
        await navigator.clipboard.writeText(value);
        hasCopiedPaymentReference.value = true;

        if (copyFeedbackTimer !== null) {
            window.clearTimeout(copyFeedbackTimer);
        }

        copyFeedbackTimer = window.setTimeout(() => {
            hasCopiedPaymentReference.value = false;
        }, 2200);
    } catch {
        hasCopiedPaymentReference.value = false;
    }
};

const submitRating = () => {
    if (!canRequestRating.value || isSubmittingRating.value) {
        return;
    }

    isSubmittingRating.value = true;

    router.post(
        route('checkout.rating.store', { transaction: props.checkout.publicId }),
        {
            score: ratingScore.value,
            comment: ratingComment.value,
        },
        {
            preserveScroll: true,
            preserveState: true,
            onFinish: () => {
                isSubmittingRating.value = false;
            },
        },
    );
};
</script>

<template>
    <Head :title="`Pembayaran ${checkout.productName}`" />

    <PublicLayout active-nav="topup">
        <main
            class="bg-[radial-gradient(circle_at_top,rgba(37,99,235,0.08),transparent_32%),linear-gradient(180deg,#f8fbff_0%,#eef4ff_100%)] pb-6 pt-4 sm:pb-6 sm:pt-6 lg:pt-8"
        >
            <div class="mx-auto max-w-[1080px] px-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-2 text-sm font-medium text-slate-500">
                    <Link
                        :href="route('products.show', { product: checkout.productId })"
                        class="inline-flex items-center gap-1.5 text-blue-600 transition hover:text-blue-700"
                    >
                        <ChevronLeft class="size-4" />
                        Kembali
                    </Link>
                    <span>/</span>
                    <span class="font-semibold text-slate-700">{{ checkout.transactionId }}</span>
                </div>

                <div class="mt-5 grid gap-4 xl:grid-cols-[minmax(0,1.18fr)_18rem] xl:gap-5">
                    <section class="space-y-4">
                        <div
                            class="rounded-[24px] border border-white/90 bg-white/95 px-3 py-4 shadow-[0_24px_70px_rgba(15,23,42,0.08)] sm:px-5 sm:py-6"
                        >
                            <div
                                class="grid grid-cols-[minmax(0,1fr)_2.2rem_minmax(0,1fr)_2.2rem_minmax(0,1fr)] items-start gap-y-2 sm:grid-cols-[auto_1fr_auto_1fr_auto] sm:gap-x-4 sm:gap-y-3"
                            >
                                <div class="checkout-step-group flex min-w-0 flex-col items-center text-center">
                                    <span
                                        class="checkout-step-icon inline-flex h-9 w-9 items-center justify-center rounded-full text-white sm:h-11 sm:w-11"
                                        :class="
                                            isPaymentStepFailed
                                                ? 'checkout-step-icon--expired bg-red-600'
                                                : isPaymentSettled
                                                  ? 'checkout-step-icon--done bg-emerald-500'
                                                  : 'checkout-step-icon--active bg-orange-500'
                                        "
                                    >
                                        <X v-if="isPaymentStepFailed" class="size-4 sm:size-5" />
                                        <BadgeCheck v-else-if="isPaymentSettled" class="size-4 sm:size-5" />
                                        <WalletCards v-else class="size-4 sm:size-5" />
                                    </span>
                                    <p
                                        class="mt-2 text-[0.92rem] font-medium sm:mt-3 sm:text-[1.05rem]"
                                        :class="isPaymentStepFailed ? 'text-red-600' : isPaymentSettled ? 'text-emerald-600' : 'text-orange-500'"
                                    >
                                        Bayar
                                    </p>
                                </div>
                                <div
                                    class="checkout-step-line mt-4 block sm:mt-5"
                                    :class="`checkout-step-line--${paymentConnectorTone}`"
                                    aria-hidden="true"
                                >
                                    <span
                                        class="checkout-step-line__fill"
                                        :class="`checkout-step-line__fill--${paymentConnectorTone}`"
                                        :style="{ width: paymentConnectorFillWidth }"
                                    ></span>
                                </div>
                                <div class="checkout-step-group flex min-w-0 flex-col items-center text-center">
                                    <span
                                        class="checkout-step-icon inline-flex h-9 w-9 items-center justify-center rounded-full text-white sm:h-11 sm:w-11"
                                        :class="
                                            isProcessingStepFailed
                                                ? 'checkout-step-icon--expired bg-red-600'
                                                : isProcessing
                                                  ? 'checkout-step-icon--processing bg-blue-600'
                                                  : isCompleted
                                                    ? 'checkout-step-icon--done bg-emerald-500'
                                                    : 'bg-slate-400'
                                        "
                                    >
                                        <X v-if="isProcessingStepFailed" class="size-4 sm:size-5" />
                                        <LoaderCircle v-else-if="isProcessing" class="size-4 animate-spin sm:size-5" />
                                        <BadgeCheck v-else-if="isCompleted" class="size-4 sm:size-5" />
                                        <LoaderCircle v-else class="size-4 sm:size-5" />
                                    </span>
                                    <p
                                        class="mt-2 text-[0.92rem] font-medium sm:mt-3 sm:text-[1.05rem]"
                                        :class="
                                            isProcessingStepFailed
                                                ? 'text-red-600'
                                                : isProcessing
                                                  ? 'text-blue-600'
                                                  : isCompleted
                                                    ? 'text-emerald-600'
                                                    : 'text-slate-600'
                                        "
                                    >
                                        Diproses
                                    </p>
                                </div>
                                <div
                                    class="checkout-step-line mt-4 block sm:mt-5"
                                    :class="`checkout-step-line--${processingConnectorTone}`"
                                    aria-hidden="true"
                                >
                                    <span
                                        class="checkout-step-line__fill"
                                        :class="`checkout-step-line__fill--${processingConnectorTone}`"
                                        :style="{ width: processingConnectorFillWidth }"
                                    ></span>
                                </div>
                                <div class="checkout-step-group flex min-w-0 flex-col items-center text-center">
                                    <span
                                        class="checkout-step-icon inline-flex h-9 w-9 items-center justify-center rounded-full text-white sm:h-11 sm:w-11"
                                        :class="isCompleted ? 'checkout-step-icon--done bg-emerald-500' : 'bg-slate-400'"
                                    >
                                        <BadgeCheck class="size-4 sm:size-5" />
                                    </span>
                                    <p
                                        class="mt-2 text-[0.92rem] font-medium sm:mt-3 sm:text-[1.05rem]"
                                        :class="isCompleted ? 'text-emerald-600' : 'text-slate-600'"
                                    >
                                        Selesai
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-[26px] border border-white/90 bg-white/95 p-4 shadow-[0_24px_70px_rgba(15,23,42,0.08)] sm:p-5">
                            <div
                                class="rounded-[20px] px-4 py-4"
                                :class="
                                    isCompleted
                                        ? 'border border-emerald-100 bg-emerald-50'
                                        : isProcessing
                                          ? 'border border-blue-100 bg-blue-50'
                                          : isExpired || isFailed
                                            ? 'border border-red-100 bg-red-50'
                                            : 'border border-orange-100 bg-orange-50'
                                "
                            >
                                <p class="[font-family:'Space Grotesk',sans-serif] text-base font-black text-slate-900">
                                    {{
                                        isCompleted
                                            ? 'Pesanan kamu berhasil diproses.'
                                            : isProcessing
                                              ? 'Pembayaran diterima, pesanan sedang diproses.'
                                              : isExpired
                                                ? 'Pembayaran sudah expired'
                                                : isFailed
                                                  ? 'Transaksi mengalami kendala.'
                                                  : 'Bayar transaksi kamu, yuk!'
                                    }}
                                </p>
                                <p
                                    class="mt-1 text-sm font-medium"
                                    :class="
                                        isCompleted
                                            ? 'text-emerald-600'
                                            : isProcessing
                                              ? 'text-blue-600'
                                              : isExpired || isFailed
                                                ? 'text-red-600'
                                                : 'text-orange-600'
                                    "
                                >
                                    {{
                                        isCompleted
                                            ? 'Item sudah masuk ke tahap selesai.'
                                            : isProcessing
                                              ? 'Status pembayaran sudah lunas dan kami sedang memproses pesananmu.'
                                              : isExpired
                                                ? 'Batas waktu pembayaran sudah lewat.'
                                                : isFailed
                                                  ? 'Silakan cek kembali detail pembayaran atau buat transaksi baru.'
                                                  : `Batas waktu bayar ${countdownLabel}`
                                    }}
                                </p>
                                <p v-if="isFailed && failureReason" class="mt-3 text-sm leading-6 text-red-700">
                                    {{ failureReason }}
                                </p>
                                <p
                                    v-else-if="isPending || isProcessing"
                                    class="mt-3 text-xs font-semibold uppercase tracking-[0.08em] text-slate-500"
                                >
                                    Status transaksi diperbarui otomatis setiap beberapa detik.
                                </p>
                            </div>

                            <div v-if="canRequestRating || hasRating" class="mt-4 rounded-[20px] border border-slate-200 bg-slate-50/80 px-4 py-4">
                                <div class="flex items-start gap-3">
                                    <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-amber-100 text-amber-500">
                                        <MessageSquareText class="size-5" />
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <p class="[font-family:'Space Grotesk',sans-serif] text-base font-black tracking-tight text-slate-950">
                                            {{ hasRating ? 'Terima kasih untuk rating kamu.' : 'Pesanan berhasil. Mau kasih rating?' }}
                                        </p>
                                        <p class="mt-1 text-sm leading-6 text-slate-600">
                                            {{
                                                hasRating
                                                    ? `Kamu sudah kasih rating ${checkout.ratingScore}/5${checkout.ratedAtLabel ? ` pada ${checkout.ratedAtLabel}` : ''}.`
                                                    : 'Boleh kasih penilaian singkat untuk bantu kami jaga kualitas layanan.'
                                            }}
                                        </p>
                                    </div>
                                </div>

                                <div class="mt-4 flex flex-wrap gap-2">
                                    <button
                                        v-for="score in 5"
                                        :key="`rating-${score}`"
                                        type="button"
                                        class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border transition"
                                        :class="
                                            score <= (hasRating ? (checkout.ratingScore ?? 0) : ratingScore)
                                                ? 'border-amber-200 bg-amber-50 text-amber-500'
                                                : 'border-slate-200 bg-white text-slate-300'
                                        "
                                        :disabled="hasRating"
                                        @click="ratingScore = score"
                                    >
                                        <Star
                                            class="size-5"
                                            :class="score <= (hasRating ? (checkout.ratingScore ?? 0) : ratingScore) ? 'fill-current' : ''"
                                        />
                                    </button>
                                </div>

                                <div class="mt-4">
                                    <textarea
                                        v-model="ratingComment"
                                        rows="3"
                                        maxlength="500"
                                        class="w-full rounded-[18px] border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-blue-300 focus:ring-0 disabled:cursor-not-allowed disabled:bg-slate-100"
                                        :disabled="hasRating"
                                        placeholder="Tulis komentar singkat kalau mau..."
                                    ></textarea>
                                </div>

                                <div v-if="!hasRating" class="mt-4">
                                    <button
                                        type="button"
                                        class="inline-flex h-12 items-center justify-center rounded-[18px] bg-slate-950 px-5 text-sm font-black text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"
                                        :disabled="isSubmittingRating"
                                        @click="submitRating"
                                    >
                                        {{ isSubmittingRating ? 'Mengirim rating...' : 'Kirim rating' }}
                                    </button>
                                </div>
                            </div>

                            <div
                                class="mt-4 flex flex-col gap-3 rounded-[20px] border border-slate-200 bg-slate-50 px-4 py-4 sm:flex-row sm:items-center"
                            >
                                <div class="flex min-w-0 items-center gap-3">
                                    <img
                                        v-if="checkout.productImage"
                                        :src="checkout.productImage"
                                        :alt="checkout.productName"
                                        class="h-14 w-14 rounded-[16px] object-cover"
                                    />
                                    <div class="min-w-0 flex-1">
                                        <p
                                            class="[font-family:'Space Grotesk',sans-serif] break-words text-sm font-black leading-6 text-slate-900 sm:leading-6"
                                        >
                                            {{ checkout.packageLabel }}
                                        </p>
                                        <p class="mt-1 text-xs font-medium text-slate-500">{{ checkout.productName }}</p>
                                    </div>
                                </div>
                                <div class="border-t border-slate-200 pt-3 text-left sm:ml-auto sm:border-t-0 sm:pt-0 sm:text-right">
                                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">Total pembayaran</p>
                                    <p class="[font-family:'Space Grotesk',sans-serif] mt-1 text-lg font-black text-slate-950">
                                        {{ formatCurrency(checkout.total) }}
                                    </p>
                                </div>
                            </div>

                            <div class="mt-4 flex flex-wrap items-center gap-3 text-sm text-slate-600">
                                <span>Bayar dengan</span>
                                <div class="inline-flex items-center gap-2 rounded-[14px] border border-slate-200 bg-white px-3 py-2">
                                    <img
                                        v-if="checkout.paymentImage"
                                        :src="checkout.paymentImage"
                                        :alt="checkout.paymentLabel"
                                        class="h-6 w-auto max-w-[4rem] object-contain"
                                    />
                                    <span
                                        v-else
                                        class="inline-flex h-7 min-w-7 items-center justify-center rounded-[10px] bg-blue-50 px-2 text-[0.62rem] font-black uppercase tracking-[0.08em] text-blue-700"
                                    >
                                        {{ checkout.paymentBadge || 'PY' }}
                                    </span>
                                    <span class="text-sm font-black uppercase tracking-[0.08em] text-slate-800">{{ checkout.paymentLabel }}</span>
                                </div>
                            </div>

                            <template v-if="isPaymentSettled">
                                <div
                                    class="mt-5 rounded-[24px] border border-emerald-100 bg-[linear-gradient(135deg,rgba(236,253,245,0.98),rgba(255,255,255,0.98))] p-5 shadow-[0_24px_55px_rgba(16,185,129,0.08)]"
                                >
                                    <div class="flex items-start gap-3">
                                        <span
                                            class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-emerald-500 text-white shadow-[0_14px_30px_rgba(16,185,129,0.22)]"
                                        >
                                            <BadgeCheck class="size-5" />
                                        </span>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-black uppercase tracking-[0.12em] text-emerald-700">Pembayaran diterima</p>
                                            <p class="mt-2 text-base font-semibold text-slate-900">
                                                {{ isCompleted ? 'Pembayaran selesai diproses.' : 'Pembayaran sudah diterima dan sedang diproses.' }}
                                            </p>
                                            <p class="mt-2 text-sm leading-6 text-slate-600">
                                                Detail pembayaran sudah tidak perlu ditampilkan lagi. Kamu tinggal menunggu status pesanan diperbarui
                                                otomatis.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <template v-else-if="isQrisPayment">
                                <p class="mt-4 text-sm leading-6 text-slate-600">
                                    Scan kode QR di bawah ini dengan aplikasi digital wallet atau mobile banking yang mendukung QRIS.
                                </p>

                                <div class="mt-5 grid gap-4 xl:grid-cols-[minmax(0,19rem)_minmax(15rem,1fr)]">
                                    <div
                                        class="rounded-[24px] border border-slate-200 bg-[linear-gradient(180deg,rgba(255,255,255,0.98),rgba(241,245,249,0.96))] p-5 shadow-[0_24px_55px_rgba(15,23,42,0.06)]"
                                    >
                                        <div class="flex items-center justify-between gap-3">
                                            <div class="flex items-center gap-2 text-slate-800">
                                                <QrCode class="size-5 text-blue-600" />
                                                <span class="text-sm font-black uppercase tracking-[0.12em]">{{ qrisTitle }}</span>
                                            </div>
                                            <span
                                                class="rounded-full bg-emerald-50 px-3 py-1 text-[0.68rem] font-black uppercase tracking-[0.12em] text-emerald-600"
                                            >
                                                Scan & Bayar
                                            </span>
                                        </div>

                                        <div class="mt-4 rounded-[22px] border border-slate-200 bg-white p-4">
                                            <div
                                                v-if="qrisImageUrl"
                                                class="relative mx-auto aspect-square w-full max-w-[360px] overflow-hidden rounded-[20px] bg-cover bg-center bg-no-repeat shadow-[0_20px_44px_rgba(37,99,235,0.14)]"
                                                :style="brandedQrisTemplateStyle"
                                            >
                                                <img
                                                    :src="qrisImageUrl"
                                                    :alt="`${qrisTitle} pembayaran`"
                                                    class="absolute object-contain"
                                                    :style="brandedQrisImageStyle"
                                                />
                                            </div>
                                            <div
                                                v-else
                                                class="flex aspect-square w-full items-center justify-center rounded-[18px] border border-dashed border-slate-300 bg-slate-50 text-center text-sm font-medium text-slate-500"
                                            >
                                                QRIS belum tersedia. Coba buka halaman pembayaran resmi.
                                            </div>
                                        </div>
                                    </div>

                                    <div
                                        class="rounded-[24px] border border-slate-200 bg-[linear-gradient(135deg,rgba(239,246,255,0.96),rgba(255,255,255,0.98))] p-5 shadow-[0_24px_55px_rgba(37,99,235,0.08)]"
                                    >
                                        <p class="text-sm font-black uppercase tracking-[0.12em] text-slate-500">Total pembayaran</p>
                                        <p
                                            class="[font-family:'Space Grotesk',sans-serif] mt-3 text-[1.95rem] font-black tracking-tight text-slate-950 xl:text-[2.1rem]"
                                        >
                                            {{ formatCurrency(checkout.total) }}
                                        </p>
                                        <p class="mt-3 text-sm leading-6 text-slate-600">
                                            Scan QRIS di samping menggunakan aplikasi e-wallet atau mobile banking, lalu selesaikan pembayaran sesuai
                                            nominal.
                                        </p>
                                        <div class="mt-5 rounded-[20px] border border-white/80 bg-white/90 px-4 py-4">
                                            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">Status saat ini</p>
                                            <p class="mt-2 text-sm font-semibold text-slate-800">
                                                {{
                                                    isCompleted
                                                        ? 'Pembayaran selesai diproses.'
                                                        : isProcessing
                                                          ? 'Pembayaran sudah diterima dan sedang diproses.'
                                                          : 'Menunggu pembayaran QRIS masuk.'
                                                }}
                                            </p>
                                        </div>
                                        <button
                                            v-if="checkout.paymentUrl"
                                            type="button"
                                            class="mt-5 inline-flex h-11 w-full items-center justify-center rounded-[16px] bg-blue-600 px-5 text-sm font-bold text-white transition hover:bg-blue-700"
                                            @click="openPaymentPage"
                                        >
                                            Buka halaman pembayaran
                                        </button>
                                    </div>
                                </div>
                            </template>

                            <template v-else-if="isBankTransferPayment">
                                <p class="mt-4 text-sm leading-6 text-slate-600">
                                    {{ bankTransferInstruction }}
                                </p>

                                <div v-if="hasBankTransferReferenceValue" class="mt-5 grid gap-4 sm:grid-cols-2">
                                    <div class="rounded-[22px] border border-slate-200 bg-white p-5">
                                        <div class="flex items-center gap-2 text-slate-800">
                                            <Landmark class="size-5 text-blue-600" />
                                            <p class="text-sm font-black uppercase tracking-[0.12em]">{{ bankTransferPrimaryTitle }}</p>
                                        </div>
                                        <p
                                            class="[font-family:'Space Grotesk',sans-serif] mt-4 break-all text-[1.65rem] font-black tracking-[0.08em] text-slate-950"
                                        >
                                            {{ bankTransferReferenceValue }}
                                        </p>
                                        <button
                                            type="button"
                                            class="mt-4 inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-500 transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700"
                                            @click="copyPaymentReference"
                                        >
                                            <Copy class="size-3.5" />
                                            {{ hasCopiedPaymentReference ? 'Nomor berhasil disalin' : 'Salin nomor virtual account' }}
                                        </button>
                                    </div>
                                    <div class="rounded-[22px] border border-slate-200 bg-slate-50 p-5">
                                        <p class="text-sm font-black uppercase tracking-[0.12em] text-slate-500">Total yang harus dibayar</p>
                                        <p class="[font-family:'Space Grotesk',sans-serif] mt-4 text-[2rem] font-black tracking-tight text-slate-950">
                                            {{ formatCurrency(checkout.total) }}
                                        </p>
                                        <p class="mt-2 text-sm leading-6 text-slate-500">
                                            Pastikan nominal transfer sama persis agar pembayaran bisa terverifikasi otomatis.
                                        </p>
                                    </div>
                                </div>
                                <div
                                    v-else
                                    class="mt-5 rounded-[24px] border border-blue-100 bg-[linear-gradient(135deg,rgba(239,246,255,0.96),rgba(255,255,255,0.98))] p-6 shadow-[0_18px_40px_rgba(37,99,235,0.08)]"
                                >
                                    <div class="flex flex-col items-start gap-4 sm:flex-row sm:items-center sm:justify-between">
                                        <div class="max-w-2xl">
                                            <div
                                                class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-blue-600 text-white shadow-[0_16px_32px_rgba(37,99,235,0.24)]"
                                            >
                                                <Landmark class="size-5" />
                                            </div>
                                            <h3
                                                class="[font-family:'Space Grotesk',sans-serif] mt-4 text-[1.2rem] font-black tracking-tight text-slate-950"
                                            >
                                                Lanjutkan ke halaman pembayaran bank
                                            </h3>
                                            <p class="mt-2 text-sm leading-6 text-slate-600">
                                                Duitku tidak mengembalikan nomor virtual account di respons invoice untuk metode ini. Nomor akun
                                                virtual dan instruksi transfer akan tampil di halaman pembayaran resmi Duitku.
                                            </p>
                                        </div>

                                        <div class="w-full rounded-[20px] border border-white/80 bg-white/90 p-5 sm:max-w-xs">
                                            <p class="text-sm font-black uppercase tracking-[0.12em] text-slate-500">Total yang harus dibayar</p>
                                            <p
                                                class="[font-family:'Space Grotesk',sans-serif] mt-3 text-[1.9rem] font-black tracking-tight text-slate-950"
                                            >
                                                {{ formatCurrency(checkout.total) }}
                                            </p>
                                            <button
                                                v-if="checkout.paymentUrl"
                                                type="button"
                                                class="mt-4 inline-flex h-11 w-full items-center justify-center rounded-[14px] bg-blue-600 px-4 text-sm font-bold text-white transition hover:bg-blue-700"
                                                @click="openPaymentPage"
                                            >
                                                Buka halaman pembayaran resmi
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <template v-else>
                                <p class="mt-4 text-sm leading-6 text-slate-600">
                                    Gunakan kode pembayaran di bawah ini pada aplikasi atau channel pembayaran yang kamu pilih.
                                </p>

                                <div class="mt-5 rounded-[22px] border border-slate-200 bg-white p-5">
                                    <p class="text-sm font-black uppercase tracking-[0.12em] text-slate-500">{{ paymentReferenceLabel }}</p>
                                    <p
                                        class="[font-family:'Space Grotesk',sans-serif] mt-3 break-all text-[1.5rem] font-black tracking-[0.08em] text-slate-950"
                                    >
                                        {{ codePaymentReferenceValue }}
                                    </p>
                                    <p class="mt-4 text-sm leading-6 text-slate-500">
                                        Total pembayaran: <span class="font-black text-slate-800">{{ formatCurrency(checkout.total) }}</span>
                                    </p>
                                    <button
                                        type="button"
                                        class="mt-4 inline-flex h-11 items-center justify-center gap-2 rounded-[16px] border border-slate-200 bg-slate-50 px-5 text-sm font-bold text-slate-700 transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700"
                                        @click="copyPaymentReference"
                                    >
                                        <Copy class="size-4" />
                                        {{ hasCopiedPaymentReference ? 'Kode pembayaran tersalin' : 'Salin kode pembayaran' }}
                                    </button>
                                    <a
                                        v-if="checkout.paymentUrl"
                                        :href="checkout.paymentUrl"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="mt-4 inline-flex h-11 items-center justify-center rounded-[16px] bg-blue-600 px-5 text-sm font-bold text-white transition hover:bg-blue-700"
                                    >
                                        Buka halaman pembayaran
                                    </a>
                                </div>
                            </template>
                        </div>

                        <div class="grid gap-4 lg:grid-cols-2">
                            <section class="rounded-[24px] border border-white/90 bg-white/95 p-4 shadow-[0_24px_70px_rgba(15,23,42,0.08)] sm:p-5">
                                <h2 class="[font-family:'Space Grotesk',sans-serif] text-xl font-black tracking-tight text-slate-950">
                                    Status transaksi
                                </h2>
                                <div class="mt-4 space-y-3 text-sm">
                                    <div class="flex items-center justify-between gap-4">
                                        <span class="text-slate-400">Status transaksi</span>
                                        <span
                                            class="rounded-full px-3 py-1 text-xs font-black uppercase tracking-[0.12em]"
                                            :class="statusBadgeTone"
                                            >{{ checkout.statusLabel }}</span
                                        >
                                    </div>
                                    <div class="flex items-center justify-between gap-4">
                                        <span class="text-slate-400">No. transaksi</span>
                                        <span class="font-semibold text-slate-800">{{ checkout.transactionId }}</span>
                                    </div>
                                    <div class="flex items-center justify-between gap-4">
                                        <span class="text-slate-400">Waktu transaksi</span>
                                        <span class="font-semibold text-slate-800">{{ checkout.createdAtLabel }}</span>
                                    </div>
                                </div>
                            </section>

                            <section class="rounded-[24px] border border-white/90 bg-white/95 p-4 shadow-[0_24px_70px_rgba(15,23,42,0.08)] sm:p-5">
                                <h2 class="[font-family:'Space Grotesk',sans-serif] text-xl font-black tracking-tight text-slate-950">
                                    Detail pembayaran
                                </h2>
                                <div class="mt-4 space-y-3 text-sm">
                                    <div class="flex items-center justify-between gap-4">
                                        <span class="text-slate-400">Total transaksi</span>
                                        <span class="font-semibold text-slate-800">{{ formatCurrency(checkout.total) }}</span>
                                    </div>
                                    <div class="flex items-center justify-between gap-4 border-t border-slate-100 pt-3">
                                        <span class="text-slate-400">Total pembayaran</span>
                                        <span class="[font-family:'Space Grotesk',sans-serif] text-xl font-black text-slate-950">{{
                                            formatCurrency(checkout.total)
                                        }}</span>
                                    </div>
                                </div>
                            </section>
                        </div>

                        <section class="rounded-[24px] border border-white/90 bg-white/95 p-4 shadow-[0_24px_70px_rgba(15,23,42,0.08)] sm:p-5">
                            <h2 class="[font-family:'Space Grotesk',sans-serif] text-xl font-black tracking-tight text-slate-950">Detail Produk</h2>
                            <div class="mt-4 grid gap-4 lg:grid-cols-[minmax(0,1.35fr)_minmax(15rem,0.85fr)]">
                                <article
                                    class="rounded-[24px] border border-slate-200 bg-[linear-gradient(135deg,rgba(239,246,255,0.94),rgba(255,255,255,0.98))] p-5 shadow-[0_18px_40px_rgba(15,23,42,0.04)]"
                                >
                                    <div class="flex items-start gap-4">
                                        <div
                                            class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-[20px] bg-slate-950 text-sm font-black tracking-[0.12em] text-white shadow-[0_14px_28px_rgba(15,23,42,0.14)]"
                                        >
                                            <img
                                                v-if="checkout.productImage"
                                                :src="checkout.productImage"
                                                :alt="checkout.productName"
                                                class="h-full w-full object-cover"
                                            />
                                            <template v-else>
                                                {{ productMonogram }}
                                            </template>
                                        </div>

                                        <div class="min-w-0 flex-1">
                                            <p class="text-[0.68rem] font-semibold uppercase tracking-[0.16em] text-slate-400">Produk</p>
                                            <p
                                                class="[font-family:'Space Grotesk',sans-serif] mt-1 text-[1.2rem] font-black leading-tight text-slate-950"
                                            >
                                                {{ checkout.productName }}
                                            </p>
                                            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                                <div class="rounded-[18px] border border-white/80 bg-white/90 px-4 py-3">
                                                    <p class="text-[0.68rem] font-semibold uppercase tracking-[0.12em] text-slate-400">Nominal</p>
                                                    <p class="mt-2 text-sm font-semibold leading-6 text-slate-800">{{ checkout.packageLabel }}</p>
                                                </div>
                                                <div class="rounded-[18px] border border-white/80 bg-white/90 px-4 py-3">
                                                    <p class="text-[0.68rem] font-semibold uppercase tracking-[0.12em] text-slate-400">
                                                        Metode bayar
                                                    </p>
                                                    <p class="mt-2 text-sm font-semibold leading-6 text-slate-800">{{ checkout.paymentLabel }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </article>

                                <article
                                    class="rounded-[24px] border border-slate-900/90 bg-[linear-gradient(145deg,rgba(15,23,42,0.98),rgba(30,41,59,0.98))] p-5 text-white shadow-[0_24px_48px_rgba(15,23,42,0.18)]"
                                >
                                    <p class="text-[0.68rem] font-semibold uppercase tracking-[0.16em] text-slate-300">Ringkasan Pesanan</p>
                                    <p class="[font-family:'Space Grotesk',sans-serif] mt-3 text-4xl font-black tracking-tight">
                                        {{ checkout.quantity }}
                                    </p>
                                    <p class="mt-1 text-sm font-semibold text-slate-200">Jumlah item yang dibeli</p>

                                    <div class="mt-5 rounded-[18px] border border-white/10 bg-white/10 px-4 py-4 backdrop-blur">
                                        <p class="text-[0.68rem] font-semibold uppercase tracking-[0.12em] text-slate-300">Total pembayaran</p>
                                        <p class="[font-family:'Space Grotesk',sans-serif] mt-2 text-[1.35rem] font-black tracking-tight text-white">
                                            {{ formatCurrency(checkout.total) }}
                                        </p>
                                    </div>
                                </article>
                            </div>

                            <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                                <article
                                    v-for="row in productRows"
                                    :key="row.label"
                                    class="rounded-[20px] border border-slate-200 bg-slate-50/90 px-4 py-4 shadow-[0_12px_30px_rgba(15,23,42,0.03)]"
                                >
                                    <p class="text-[0.68rem] font-semibold uppercase tracking-[0.12em] text-slate-400">{{ row.label }}</p>
                                    <p class="mt-2 break-words text-sm font-semibold leading-6 text-slate-800">{{ row.value }}</p>
                                </article>

                                <article
                                    class="rounded-[20px] border border-slate-200 bg-slate-50/90 px-4 py-4 shadow-[0_12px_30px_rgba(15,23,42,0.03)]"
                                >
                                    <p class="text-[0.68rem] font-semibold uppercase tracking-[0.12em] text-slate-400">Jumlah</p>
                                    <p class="mt-2 text-sm font-semibold leading-6 text-slate-800">{{ checkout.quantity }}</p>
                                </article>
                            </div>
                        </section>
                    </section>

                    <aside class="space-y-4">
                        <section class="rounded-[24px] border border-white/90 bg-white/95 p-4 shadow-[0_24px_70px_rgba(15,23,42,0.08)] sm:p-5">
                            <div class="flex items-start gap-3 rounded-[18px] border border-blue-100 bg-blue-50 px-4 py-4 text-blue-900">
                                <Clock3 class="mt-0.5 size-5 shrink-0" />
                                <div>
                                    <p class="text-sm font-black">Transaksi ini dijamin aman dan cepat untuk kamu bayar.</p>
                                    <p class="mt-1 text-sm font-medium text-blue-700">
                                        {{ checkout.paymentDeadlineLabel || 'Verifikasi pembayaran dilakukan manual setelah dana masuk.' }}
                                    </p>
                                </div>
                            </div>
                        </section>

                        <section class="rounded-[24px] border border-white/90 bg-white/95 p-4 shadow-[0_24px_70px_rgba(15,23,42,0.08)] sm:p-5">
                            <h2 class="[font-family:'Space Grotesk',sans-serif] text-xl font-black tracking-tight text-slate-950">Catatan</h2>
                            <ul class="mt-4 space-y-3 text-sm leading-6 text-slate-600">
                                <li v-if="checkout.checkoutNotice" class="flex gap-3">
                                    <CircleAlert class="size-4.5 mt-0.5 shrink-0 text-blue-600" />
                                    <span>{{ checkout.checkoutNotice }}</span>
                                </li>
                                <li v-if="checkout.guaranteeText" class="flex gap-3">
                                    <ShieldCheck class="size-4.5 mt-0.5 shrink-0 text-emerald-600" />
                                    <span>{{ checkout.guaranteeText }}</span>
                                </li>
                                <li v-for="note in checkout.notes ?? []" :key="note" class="flex gap-3">
                                    <span class="mt-[0.45rem] h-2 w-2 shrink-0 rounded-full bg-blue-500" />
                                    <span>{{ note }}</span>
                                </li>
                            </ul>
                        </section>

                        <section class="rounded-[24px] border border-white/90 bg-white/95 p-4 shadow-[0_24px_70px_rgba(15,23,42,0.08)] sm:p-5">
                            <h2 class="[font-family:'Space Grotesk',sans-serif] text-xl font-black tracking-tight text-slate-950">
                                Jika kamu butuh bantuan
                            </h2>
                            <div class="mt-4 rounded-[18px] border border-slate-200 bg-slate-50 px-4 py-4">
                                <div class="flex gap-3">
                                    <HelpCircle class="mt-0.5 size-5 shrink-0 text-orange-500" />
                                    <p class="text-sm leading-6 text-slate-600">
                                        Jika bukti pembayaran atau status transaksi belum berubah, simpan halaman ini dan hubungi admin melalui
                                        WhatsApp agar kami bantu cek lebih cepat.
                                    </p>
                                </div>
                            </div>
                        </section>
                    </aside>
                </div>
            </div>
        </main>
    </PublicLayout>
</template>

<style scoped>
.checkout-step-icon {
    position: relative;
    box-shadow: 0 14px 32px rgba(15, 23, 42, 0.12);
    transition:
        transform 0.35s ease,
        box-shadow 0.35s ease;
}

.checkout-step-icon::after {
    content: '';
    position: absolute;
    inset: -6px;
    border-radius: 9999px;
    opacity: 0;
    pointer-events: none;
}

.checkout-step-icon--active::after {
    background: rgba(249, 115, 22, 0.18);
    animation: checkout-step-pulse 1.8s ease-out infinite;
}

.checkout-step-icon--processing {
    transform: translateY(-1px);
}

.checkout-step-icon--processing::after {
    background: rgba(37, 99, 235, 0.16);
    animation: checkout-step-pulse 1.8s ease-out infinite;
    opacity: 1;
}

.checkout-step-icon--done {
    box-shadow: 0 14px 32px rgba(16, 185, 129, 0.24);
}

.checkout-step-icon--expired {
    box-shadow: 0 14px 32px rgba(220, 38, 38, 0.18);
}

.checkout-step-line {
    position: relative;
    align-self: flex-start;
    height: 0.45rem;
    overflow: hidden;
    border-radius: 9999px;
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.92), rgba(226, 232, 240, 0.92)), rgba(226, 232, 240, 0.8);
    box-shadow: inset 0 1px 2px rgba(148, 163, 184, 0.2);
}

.checkout-step-line__fill {
    display: block;
    position: absolute;
    top: 0;
    bottom: 0;
    left: 0;
    width: 0;
    border-radius: inherit;
    will-change: width, background-position;
    transition:
        width 0.45s ease,
        background 0.45s ease,
        opacity 0.35s ease;
}

.checkout-step-line--done {
    background: linear-gradient(180deg, rgba(236, 253, 245, 0.95), rgba(209, 250, 229, 0.88)), rgba(209, 250, 229, 0.75);
}

.checkout-step-line--error {
    background: linear-gradient(180deg, rgba(254, 242, 242, 0.96), rgba(254, 226, 226, 0.88)), rgba(254, 226, 226, 0.78);
}

.checkout-step-line__fill--idle {
    opacity: 0;
}

.checkout-step-line__fill--active,
.checkout-step-line__fill--processing,
.checkout-step-line__fill--done,
.checkout-step-line__fill--error {
    opacity: 1;
    box-shadow: 0 0 18px rgba(59, 130, 246, 0.18);
}

.checkout-step-line__fill--active {
    background: linear-gradient(90deg, #fdba74 0%, #f97316 32%, #fb923c 68%, #fdba74 100%);
    background-size: 200% 100%;
    animation: checkout-line-flow 1.35s linear infinite;
    box-shadow: 0 0 18px rgba(249, 115, 22, 0.24);
}

.checkout-step-line__fill--processing {
    background: linear-gradient(90deg, #93c5fd 0%, #2563eb 32%, #60a5fa 68%, #93c5fd 100%);
    background-size: 200% 100%;
    animation: checkout-line-flow 1.2s linear infinite;
    box-shadow: 0 0 18px rgba(37, 99, 235, 0.24);
}

.checkout-step-line__fill--done {
    background: linear-gradient(90deg, #6ee7b7 0%, #10b981 45%, #34d399 100%);
    box-shadow: 0 0 18px rgba(16, 185, 129, 0.2);
}

.checkout-step-line__fill--error {
    background: linear-gradient(90deg, #fca5a5 0%, #dc2626 50%, #f87171 100%);
    box-shadow: 0 0 18px rgba(220, 38, 38, 0.18);
}

.checkout-step-line--done .checkout-step-line__fill {
    width: 100%;
}

.checkout-step-line--error .checkout-step-line__fill {
    width: 58%;
}

@media (max-width: 639px) {
    .checkout-step-group p {
        line-height: 1.15;
        word-break: break-word;
    }

    .checkout-step-icon {
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.12);
    }
}

@keyframes checkout-line-flow {
    0% {
        background-position: 0% 50%;
    }

    100% {
        background-position: 200% 50%;
    }
}

@keyframes checkout-step-pulse {
    0% {
        transform: scale(0.86);
        opacity: 0;
    }

    30% {
        opacity: 0.9;
    }

    100% {
        transform: scale(1.22);
        opacity: 0;
    }
}
</style>
