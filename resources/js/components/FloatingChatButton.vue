<script setup lang="ts">
import { MessageCircleMore, Send, Sparkles, X } from 'lucide-vue-next';
import { computed, nextTick, ref, watch } from 'vue';

type ChatMessage = {
    id: string;
    role: 'assistant' | 'user';
    content: string;
};

const props = defineProps<{
    fallbackHref: string;
    endpoint?: string;
    aiEnabled?: boolean;
}>();

const isOpen = ref(false);
const isSending = ref(false);
const draftMessage = ref('');
const scroller = ref<HTMLElement | null>(null);
const storageKey = 'lyva-support-chat-history-v1';
const welcomeMessage = computed<ChatMessage>(() => ({
    id: 'welcome-message',
    role: 'assistant',
    content: props.aiEnabled
        ? 'Halo, aku Lyva Assistant. Tanyakan produk, pembayaran, atau kendala transaksi, nanti aku bantu jawab lewat chatbot Lyva.'
        : 'Halo, aku Lyva Assistant. Chatbot lokal Lyva belum tersambung penuh, tapi aku tetap bantu sebisa mungkin untuk produk, pembayaran, dan transaksi.',
}));

const messages = ref<ChatMessage[]>([welcomeMessage.value]);
const chatFormStartedAt = ref<number>(Date.now());

const normalizedDraft = computed(() => draftMessage.value.trim());
const canSend = computed(() => normalizedDraft.value.length > 0 && !isSending.value);

const delay = (ms: number) => new Promise((resolve) => window.setTimeout(resolve, ms));

const typingDelayFor = (userMessage: string, assistantReply: string) => {
    const longestTextLength = Math.max(userMessage.trim().length, assistantReply.trim().length);

    return Math.min(2200, Math.max(950, 700 + longestTextLength * 18));
};

const restoreMessages = () => {
    if (typeof window === 'undefined') {
        return;
    }

    const cached = window.localStorage.getItem(storageKey);

    if (!cached) {
        return;
    }

    try {
        const parsed = JSON.parse(cached) as ChatMessage[];

        if (Array.isArray(parsed) && parsed.length > 0) {
            messages.value = [welcomeMessage.value, ...parsed.filter((item) => item.role === 'user' || item.role === 'assistant').slice(-10)];
        }
    } catch {
        window.localStorage.removeItem(storageKey);
    }
};

const persistMessages = () => {
    if (typeof window === 'undefined') {
        return;
    }

    const payload = messages.value.filter((message) => message.id !== welcomeMessage.value.id).slice(-10);
    window.localStorage.setItem(storageKey, JSON.stringify(payload));
};

const scrollToBottom = async () => {
    await nextTick();

    if (!scroller.value) {
        return;
    }

    scroller.value.scrollTop = scroller.value.scrollHeight;
};

const generateId = () => `chat-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;

const csrfToken = () => {
    if (typeof document === 'undefined') {
        return '';
    }

    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
};

const sendMessage = async () => {
    if (!canSend.value) {
        return;
    }

    const message = normalizedDraft.value;
    draftMessage.value = '';

    messages.value.push({
        id: generateId(),
        role: 'user',
        content: message,
    });
    persistMessages();
    await scrollToBottom();

    isSending.value = true;
    const typingStartedAt = Date.now();

    try {
        const response = await fetch(props.endpoint ?? '/support/chat', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken(),
            },
            body: JSON.stringify({
                message,
                website: '',
                formStartedAt: chatFormStartedAt.value,
                history: messages.value
                    .filter((item) => item.id !== welcomeMessage.value.id)
                    .slice(-8)
                    .map((item) => ({
                        role: item.role,
                        content: item.content,
                    })),
            }),
        });

        const payload = await response.json().catch(() => null);
        const reply = String(payload?.data?.reply ?? '').trim();
        const assistantReply = reply || 'Maaf, aku belum bisa balas sekarang. Coba ulang lagi sebentar ya.';
        const elapsed = Date.now() - typingStartedAt;
        const targetDelay = typingDelayFor(message, assistantReply);

        if (elapsed < targetDelay) {
            await delay(targetDelay - elapsed);
        }

        messages.value.push({
            id: generateId(),
            role: 'assistant',
            content: assistantReply,
        });
    } catch {
        const fallbackReply = 'Koneksi ke agent lagi bermasalah. Kamu bisa coba lagi atau lanjut cek riwayat transaksi dulu ya.';
        const elapsed = Date.now() - typingStartedAt;
        const targetDelay = typingDelayFor(message, fallbackReply);

        if (elapsed < targetDelay) {
            await delay(targetDelay - elapsed);
        }

        messages.value.push({
            id: generateId(),
            role: 'assistant',
            content: fallbackReply,
        });
    } finally {
        isSending.value = false;
        persistMessages();
        await scrollToBottom();
    }
};

watch(isOpen, async (open) => {
    if (open) {
        chatFormStartedAt.value = Date.now();
        await scrollToBottom();
    }
});

restoreMessages();
</script>

<template>
    <div class="pointer-events-none fixed bottom-[5.95rem] right-4 z-[70] sm:bottom-5 sm:right-5">
        <Transition name="chat-panel">
            <section
                v-if="isOpen"
                class="pointer-events-auto absolute bottom-[calc(100%+0.9rem)] right-0 flex h-[28rem] w-[min(92vw,22rem)] flex-col overflow-hidden rounded-[28px] border border-white/80 bg-white/96 shadow-[0_28px_70px_rgba(15,23,42,0.18)] backdrop-blur-xl"
            >
                <div class="relative overflow-hidden border-b border-slate-200/80 bg-[linear-gradient(135deg,rgba(79,70,229,0.96),rgba(14,165,233,0.92))] px-5 py-4 text-white">
                    <div class="absolute right-[-10%] top-[-30%] h-24 w-24 rounded-full bg-white/10 blur-2xl"></div>
                    <div class="relative flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <p class="text-[0.68rem] font-black uppercase tracking-[0.18em] text-white/75">Chatbot Support</p>
                            <h3 class="mt-1 text-lg font-black tracking-tight">Lyva Assistant</h3>
                            <p class="mt-1 text-sm text-white/80">
                                {{ aiEnabled ? 'Chatbot Lyva aktif dan siap bantu.' : 'Mode bantuan cadangan aktif.' }}
                            </p>
                        </div>
                        <button
                            type="button"
                            class="inline-flex size-9 items-center justify-center rounded-full border border-white/20 bg-white/10 text-white transition hover:bg-white/18"
                            @click="isOpen = false"
                        >
                            <X class="size-4.5" />
                        </button>
                    </div>
                </div>

                <div ref="scroller" class="flex-1 space-y-3 overflow-y-auto bg-[linear-gradient(180deg,rgba(248,250,252,0.86),rgba(255,255,255,0.98))] px-4 py-4">
                    <article
                        v-for="message in messages"
                        :key="message.id"
                        class="flex"
                        :class="message.role === 'user' ? 'justify-end' : 'justify-start'"
                    >
                        <div
                            class="max-w-[85%] rounded-[22px] px-4 py-3 text-sm leading-6 shadow-[0_14px_30px_rgba(15,23,42,0.06)]"
                            :class="
                                message.role === 'user'
                                    ? 'bg-[linear-gradient(135deg,rgba(79,70,229,0.96),rgba(14,165,233,0.9))] text-white'
                                    : 'border border-slate-200/80 bg-white text-slate-700'
                            "
                        >
                            <p class="whitespace-pre-line">{{ message.content }}</p>
                        </div>
                    </article>

                    <article v-if="isSending" class="flex justify-start">
                        <div class="inline-flex items-center gap-2 rounded-[22px] border border-slate-200/80 bg-white px-4 py-3 text-sm text-slate-500 shadow-[0_14px_30px_rgba(15,23,42,0.06)]">
                            <span class="chat-panel__typing-dot"></span>
                            <span class="chat-panel__typing-dot" style="animation-delay: 120ms"></span>
                            <span class="chat-panel__typing-dot" style="animation-delay: 240ms"></span>
                        </div>
                    </article>
                </div>

                <div class="border-t border-slate-200/80 bg-white px-4 py-4">
                    <form class="space-y-3" @submit.prevent="sendMessage">
                        <textarea
                            v-model="draftMessage"
                            rows="3"
                            placeholder="Tulis pertanyaanmu..."
                            class="min-h-[5.5rem] w-full resize-none rounded-[20px] border border-slate-200 bg-slate-50/70 px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-indigo-300 focus:bg-white focus:ring-0"
                        ></textarea>

                        <div class="flex items-center justify-between gap-3">
                            <a
                                :href="fallbackHref"
                                class="text-xs font-bold text-slate-500 transition hover:text-slate-700"
                            >
                                Buka riwayat transaksi
                            </a>
                            <button
                                type="submit"
                                class="inline-flex items-center gap-2 rounded-full bg-[linear-gradient(135deg,rgba(79,70,229,0.98),rgba(14,165,233,0.94))] px-4 py-2.5 text-sm font-black text-white shadow-[0_16px_28px_rgba(79,70,229,0.24)] transition disabled:cursor-not-allowed disabled:opacity-60"
                                :disabled="!canSend"
                            >
                                <Send class="size-4" />
                                Kirim
                            </button>
                        </div>
                    </form>
                </div>
            </section>
        </Transition>

        <button
            type="button"
            class="chat-fab group pointer-events-auto relative inline-flex h-[3.6rem] w-[3.6rem] items-center justify-center rounded-full transition-transform duration-200 hover:-translate-y-1"
            aria-label="Buka chat bantuan"
            @click="isOpen = !isOpen"
        >
            <span class="chat-fab__halo"></span>

            <span class="chat-fab__icon-wrap">
                <span class="chat-fab__icon-bg">
                    <MessageCircleMore v-if="!isOpen" class="size-6" />
                    <X v-else class="size-6" />
                </span>
                <span class="chat-fab__badge">
                    <Sparkles class="size-3" />
                </span>
                <span class="chat-fab__live-dot"></span>
            </span>
        </button>
    </div>
</template>

<style scoped>
.chat-fab {
    overflow: visible;
}

.chat-fab__halo {
    pointer-events: none;
    position: absolute;
    inset: -0.3rem;
    border-radius: 9999px;
    background: radial-gradient(circle, rgba(99, 102, 241, 0.24) 0%, rgba(99, 102, 241, 0) 72%);
    filter: blur(12px);
    animation: chat-fab-halo 4.8s ease-in-out infinite;
}

.chat-fab__icon-wrap {
    position: relative;
    flex: none;
}

.chat-fab__icon-bg {
    position: relative;
    display: inline-flex;
    height: 3.45rem;
    width: 3.45rem;
    align-items: center;
    justify-content: center;
    border-radius: 9999px;
    border: 3px solid rgba(255, 255, 255, 0.9);
    background: linear-gradient(135deg, rgba(79, 70, 229, 0.98), rgba(14, 165, 233, 0.94));
    color: white;
    box-shadow:
        0 14px 24px rgba(79, 70, 229, 0.24),
        inset 0 1px 0 rgba(255, 255, 255, 0.3);
    animation: chat-fab-float 3.8s ease-in-out infinite;
}

.chat-fab__badge {
    position: absolute;
    right: 0.05rem;
    top: -0.05rem;
    display: inline-flex;
    height: 1.1rem;
    width: 1.1rem;
    align-items: center;
    justify-content: center;
    border-radius: 9999px;
    border: 1px solid rgba(255, 255, 255, 0.95);
    background: linear-gradient(135deg, rgba(251, 191, 36, 0.98), rgba(249, 115, 22, 0.96));
    color: white;
    box-shadow: 0 10px 18px rgba(249, 115, 22, 0.24);
}

.chat-fab__live-dot {
    position: absolute;
    right: 0.2rem;
    bottom: 0.18rem;
    height: 0.72rem;
    width: 0.72rem;
    border: 2px solid rgba(255, 255, 255, 0.96);
    border-radius: 9999px;
    background: rgb(16 185 129);
    animation: chat-fab-live-dot 1.9s ease-in-out infinite;
}

.chat-panel-enter-active,
.chat-panel-leave-active {
    transition:
        opacity 0.22s ease,
        transform 0.22s ease;
}

.chat-panel-enter-from,
.chat-panel-leave-to {
    opacity: 0;
    transform: translate3d(0, 16px, 0) scale(0.98);
}

.chat-panel__typing-dot {
    height: 0.42rem;
    width: 0.42rem;
    border-radius: 9999px;
    background: rgb(99 102 241);
    animation: chat-panel-typing 1.1s ease-in-out infinite;
}

@keyframes chat-fab-float {
    0%,
    100% {
        transform: translate3d(0, 0, 0) scale(1);
    }

    50% {
        transform: translate3d(0, -3px, 0) scale(1.03);
    }
}

@keyframes chat-fab-halo {
    0%,
    100% {
        transform: scale(1);
        opacity: 0.75;
    }

    50% {
        transform: scale(1.14);
        opacity: 1;
    }
}

@keyframes chat-fab-live-dot {
    0%,
    100% {
        transform: scale(1);
        box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.2);
    }

    50% {
        transform: scale(1.1);
        box-shadow: 0 0 0 7px rgba(16, 185, 129, 0);
    }
}

@keyframes chat-panel-typing {
    0%,
    80%,
    100% {
        transform: translateY(0);
        opacity: 0.45;
    }

    40% {
        transform: translateY(-3px);
        opacity: 1;
    }
}

@media (prefers-reduced-motion: reduce) {
    .chat-fab__halo,
    .chat-fab__icon-bg,
    .chat-fab__live-dot,
    .chat-panel__typing-dot {
        animation: none !important;
    }
}
</style>
