<script setup>
defineProps({
    title: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex min-w-0 items-center gap-3">
            <h1 class="shrink-0 font-heading text-2xl font-bold text-ocean-deep dark:text-gray-100">{{ title }}</h1>
            <p class="text-sm leading-snug text-muted-foreground dark:text-gray-400">
                <slot />
            </p>
        </div>
        <div v-if="$slots.actions" class="shrink-0">
            <slot name="actions" />
        </div>
    </div>
</template>
