<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const LOADER_TEXT = 'Loading...';
const RINGS = 2;
const RING_SECTORS = 30;
const RING_RADIUS_REM = 7;
const MIN_VISIBLE_MS = 720;

const isVisible = ref(false);
const mounted = ref(false);
const visibleSince = ref(0);

let hideTimeout: ReturnType<typeof window.setTimeout> | null = null;
const cleanups: Array<() => void> = [];

const rings = Array.from({ length: RINGS }, (_, ringIndex) => ringIndex);
const sectors = computed(() =>
    Array.from({ length: RING_SECTORS }, (_, sectorIndex) => ({
        key: sectorIndex,
        label: LOADER_TEXT[sectorIndex] ?? '',
        transform: `rotateY(${(360 / RING_SECTORS) * sectorIndex}deg) translateZ(${RING_RADIUS_REM}rem)`,
    })),
);

const clearHideTimeout = () => {
    if (hideTimeout) {
        window.clearTimeout(hideTimeout);
        hideTimeout = null;
    }
};

const showLoader = () => {
    clearHideTimeout();
    visibleSince.value = Date.now();
    isVisible.value = true;
};

const hideLoader = () => {
    const elapsed = Date.now() - visibleSince.value;
    const remaining = Math.max(0, MIN_VISIBLE_MS - elapsed);

    clearHideTimeout();

    hideTimeout = window.setTimeout(() => {
        isVisible.value = false;
    }, remaining);
};

onMounted(() => {
    mounted.value = true;

    cleanups.push(
        router.on('start', (event) => {
            const visit = event.detail.visit;
            const currentPath = `${window.location.pathname}${window.location.search}`;
            const nextPath = `${visit.url.pathname}${visit.url.search}`;

            if (!visit.showProgress || visit.prefetch || visit.method !== 'get' || currentPath === nextPath) {
                return;
            }

            showLoader();
        }),
    );

    cleanups.push(
        router.on('finish', () => {
            if (!isVisible.value) {
                return;
            }

            hideLoader();
        }),
    );

    cleanups.push(
        router.on('invalid', () => {
            if (!isVisible.value) {
                return;
            }

            hideLoader();
        }),
    );

    cleanups.push(
        router.on('exception', () => {
            if (!isVisible.value) {
                return;
            }

            hideLoader();
        }),
    );
});

onBeforeUnmount(() => {
    clearHideTimeout();
    cleanups.splice(0).forEach((cleanup) => cleanup());
});
</script>

<template>
    <slot />

    <Teleport v-if="mounted" to="body">
        <Transition name="global-loader-fade">
            <div
                v-if="isVisible"
                class="global-page-loader fixed inset-0 z-[200] flex items-center justify-center overflow-hidden bg-black"
                aria-live="polite"
                aria-label="Loading halaman"
                role="status"
            >
                <div class="global-page-loader__glow global-page-loader__glow--one"></div>
                <div class="global-page-loader__glow global-page-loader__glow--two"></div>

                <div class="preloader" aria-hidden="true">
                    <div
                        v-for="ring in rings"
                        :key="`loader-ring-${ring}`"
                        class="preloader__ring"
                        :class="{ 'preloader__ring--reverse': ring % 2 === 1 }"
                    >
                        <span
                            v-for="sector in sectors"
                            :key="`loader-sector-${ring}-${sector.key}`"
                            class="preloader__sector"
                            :class="{ 'preloader__sector--empty': !sector.label }"
                            :style="{ transform: sector.transform }"
                        >
                            {{ sector.label }}
                        </span>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.global-page-loader {
    perspective: 40em;
}

.global-page-loader__glow {
    position: absolute;
    border-radius: 9999px;
    filter: blur(48px);
    opacity: 0.55;
    will-change: transform, opacity;
}

.global-page-loader__glow--one {
    left: 18%;
    top: 20%;
    height: 14rem;
    width: 14rem;
    background: radial-gradient(circle, rgba(61, 241, 241, 0.34) 0%, rgba(61, 241, 241, 0) 70%);
    animation: loader-glow-drift 4.8s ease-in-out infinite;
}

.global-page-loader__glow--two {
    right: 18%;
    bottom: 18%;
    height: 16rem;
    width: 16rem;
    background: radial-gradient(circle, rgba(59, 130, 246, 0.26) 0%, rgba(59, 130, 246, 0) 72%);
    animation: loader-glow-drift 5.6s ease-in-out infinite reverse;
}

.preloader {
    position: relative;
    display: flex;
    height: 17em;
    width: 17em;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #3df1f1;
    font-family: 'Dosis', 'Plus Jakarta Sans', sans-serif;
    animation: tilt-spin 8s linear infinite;
    transform-style: preserve-3d;
}

.preloader__ring {
    position: relative;
    height: 3rem;
    width: 1.5rem;
    font-size: 2em;
    transform-style: preserve-3d;
    animation: ring-spin 4s linear infinite;
}

.preloader__ring--reverse {
    animation-direction: reverse;
}

.preloader__sector {
    position: absolute;
    top: 0;
    left: 0;
    display: inline-flex;
    height: 100%;
    width: 100%;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    text-align: center;
    text-transform: uppercase;
}

.preloader__sector--empty::before {
    display: block;
    height: 100%;
    width: 100%;
    background: linear-gradient(transparent 45%, currentColor 45% 55%, transparent 55%);
    content: '';
}

.global-loader-fade-enter-active,
.global-loader-fade-leave-active {
    transition:
        opacity 220ms ease,
        transform 220ms ease;
}

.global-loader-fade-enter-from,
.global-loader-fade-leave-to {
    opacity: 0;
}

@keyframes tilt-spin {
    from {
        transform: rotateY(0turn) rotateX(30deg);
    }

    to {
        transform: rotateY(1turn) rotateX(30deg);
    }
}

@keyframes ring-spin {
    from {
        transform: rotateY(0turn);
    }

    to {
        transform: rotateY(1turn);
    }
}

@keyframes loader-glow-drift {
    0%,
    100% {
        transform: translate3d(0, 0, 0) scale(1);
        opacity: 0.45;
    }

    50% {
        transform: translate3d(0, -16px, 0) scale(1.08);
        opacity: 0.78;
    }
}

@media (prefers-reduced-motion: reduce) {
    .preloader,
    .preloader__ring,
    .global-page-loader__glow,
    .global-loader-fade-enter-active,
    .global-loader-fade-leave-active {
        animation: none !important;
        transition: none !important;
    }
}
</style>
