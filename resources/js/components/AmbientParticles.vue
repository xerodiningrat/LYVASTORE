<script setup lang="ts">
const props = withDefaults(
    defineProps<{
        variant?: 'public' | 'admin' | 'app' | 'auth';
    }>(),
    {
        variant: 'public',
    },
);

const particles: Record<string, Array<{ left: string; top: string; size: number; delay: string; duration: string; opacity: number }>> = {
    public: [
        { left: '7%', top: '14%', size: 10, delay: '0s', duration: '12s', opacity: 0.7 },
        { left: '18%', top: '56%', size: 7, delay: '1.1s', duration: '14s', opacity: 0.48 },
        { left: '32%', top: '24%', size: 12, delay: '2.4s', duration: '13s', opacity: 0.56 },
        { left: '52%', top: '68%', size: 8, delay: '0.8s', duration: '15s', opacity: 0.52 },
        { left: '71%', top: '18%', size: 9, delay: '2s', duration: '12.5s', opacity: 0.44 },
        { left: '86%', top: '48%', size: 11, delay: '1.6s', duration: '16s', opacity: 0.58 },
    ],
    admin: [
        { left: '9%', top: '12%', size: 8, delay: '0s', duration: '11s', opacity: 0.58 },
        { left: '16%', top: '70%', size: 9, delay: '0.8s', duration: '13s', opacity: 0.46 },
        { left: '30%', top: '30%', size: 7, delay: '1.8s', duration: '14s', opacity: 0.44 },
        { left: '47%', top: '62%', size: 10, delay: '2.2s', duration: '12s', opacity: 0.6 },
        { left: '58%', top: '20%', size: 12, delay: '1.1s', duration: '15s', opacity: 0.5 },
        { left: '72%', top: '42%', size: 8, delay: '2.6s', duration: '13.5s', opacity: 0.42 },
        { left: '84%', top: '16%', size: 10, delay: '0.5s', duration: '12.8s', opacity: 0.52 },
        { left: '91%', top: '66%', size: 7, delay: '1.9s', duration: '15.6s', opacity: 0.4 },
    ],
    app: [
        { left: '8%', top: '20%', size: 8, delay: '0s', duration: '12s', opacity: 0.52 },
        { left: '25%', top: '58%', size: 10, delay: '1.2s', duration: '13s', opacity: 0.48 },
        { left: '41%', top: '16%', size: 7, delay: '2.2s', duration: '12.4s', opacity: 0.44 },
        { left: '62%', top: '72%', size: 11, delay: '0.7s', duration: '15.2s', opacity: 0.5 },
        { left: '79%', top: '26%', size: 9, delay: '1.7s', duration: '14.4s', opacity: 0.42 },
        { left: '90%', top: '52%', size: 8, delay: '2.8s', duration: '16s', opacity: 0.36 },
    ],
    auth: [
        { left: '12%', top: '14%', size: 10, delay: '0s', duration: '11s', opacity: 0.56 },
        { left: '23%', top: '66%', size: 8, delay: '1.5s', duration: '14s', opacity: 0.44 },
        { left: '38%', top: '24%', size: 12, delay: '0.9s', duration: '13s', opacity: 0.6 },
        { left: '56%', top: '72%', size: 7, delay: '2.1s', duration: '15s', opacity: 0.42 },
        { left: '69%', top: '18%', size: 9, delay: '1.8s', duration: '12s', opacity: 0.46 },
        { left: '84%', top: '58%', size: 11, delay: '0.4s', duration: '16s', opacity: 0.52 },
        { left: '92%', top: '32%', size: 6, delay: '2.6s', duration: '14.8s', opacity: 0.34 },
    ],
};
</script>

<template>
    <div aria-hidden="true" class="ambient-scene" :data-variant="props.variant">
        <div class="ambient-scene__mesh ambient-scene__mesh--one"></div>
        <div class="ambient-scene__mesh ambient-scene__mesh--two"></div>
        <div class="ambient-scene__mesh ambient-scene__mesh--three"></div>
        <div class="ambient-scene__grid"></div>

        <div class="ambient-scene__cards">
            <div class="ambient-scene__ghost-card ambient-scene__ghost-card--one"></div>
            <div class="ambient-scene__ghost-card ambient-scene__ghost-card--two"></div>
            <div class="ambient-scene__ghost-card ambient-scene__ghost-card--three"></div>
        </div>

        <span
            v-for="(particle, index) in particles[props.variant]"
            :key="`${props.variant}-${index}`"
            class="ambient-scene__particle"
            :style="{
                left: particle.left,
                top: particle.top,
                width: `${particle.size}px`,
                height: `${particle.size}px`,
                animationDelay: particle.delay,
                animationDuration: particle.duration,
                opacity: particle.opacity,
            }"
        />
    </div>
</template>
