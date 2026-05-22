<script setup>
defineProps({
    title: {
        type: String,
        default: null,
    },
});
</script>

<template>
    <div class="rounded-lg bg-white shadow-card p-6">
        <h2
            v-if="title"
            class="font-heading text-base font-semibold text-ocean-deep mb-4"
        >
            {{ title }}
        </h2>
        <slot />
    </div>
</template>
