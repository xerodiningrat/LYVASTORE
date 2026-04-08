<script setup lang="ts">
interface Props {
    class?: string;
}

defineProps<Props>();
</script>

<template>
    <img src="/brand/lyva-mascot-mark.png" alt="Lyva Indonesia" class="size-8 object-contain" />
    <div class="ml-1 grid flex-1 text-left text-sm">
        <span class="lyva-wordmark lyva-wordmark--sm mb-0.5 truncate leading-none">LYVA INDONESIA</span>
    </div>
</template>
