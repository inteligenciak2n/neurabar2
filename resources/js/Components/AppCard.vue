<script setup>
defineProps({
    title: {
        type: String,
        default: null,
    },
    titleClass: {
        type: String,
        default: 'font-heading text-base font-semibold text-ocean-deep mb-4 dark:text-gray-100',
    },
});
</script>

<template>
    <div class="rounded-lg bg-white shadow-card p-6 dark:bg-gray-800">
        <h2
            v-if="title"
            :class="titleClass"
        >
            {{ title }}
        </h2>
        <slot />
    </div>
</template>
