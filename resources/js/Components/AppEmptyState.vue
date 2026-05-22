<script setup>
defineProps({
    title: {
        type: String,
        default: 'No items found',
    },
    description: {
        type: String,
        default: null,
    },
    actionLabel: {
        type: String,
        default: null,
    },
});

const emit = defineEmits(['action']);
</script>

<template>
    <div class="flex flex-col items-center justify-center py-16 text-center">
        <div class="rounded-full bg-muted p-4 mb-4">
            <svg class="h-8 w-8 text-muted-foreground" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
            </svg>
        </div>
        <h3 class="font-heading text-base font-semibold text-ocean-deep">{{ title }}</h3>
        <p v-if="description" class="mt-1 text-sm text-muted-foreground font-body">{{ description }}</p>
        <button
            v-if="actionLabel"
            class="mt-4 inline-flex items-center rounded bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary-hover transition-colors"
            @click="emit('action')"
        >
            {{ actionLabel }}
        </button>
    </div>
</template>
