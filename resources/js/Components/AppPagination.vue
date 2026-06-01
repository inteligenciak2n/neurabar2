<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    links: {
        type: Array,
        required: true,
    },
});
</script>

<template>
    <div class="flex flex-wrap items-center justify-center gap-1 mt-4">
        <template v-for="link in links" :key="link.label">
            <span
                v-if="!link.url"
                class="inline-flex h-9 min-w-[2.25rem] items-center justify-center rounded px-3 text-sm font-body text-muted-foreground cursor-not-allowed"
                v-html="link.label"
            />
            <Link
                v-else
                :href="link.url"
                :class="[
                    'inline-flex h-9 min-w-[2.25rem] items-center justify-center rounded px-3 text-sm font-body transition-colors',
                    link.active
                        ? 'bg-primary text-primary-foreground'
                        : 'text-ocean-deep hover:bg-muted dark:text-gray-300 dark:hover:bg-gray-800',
                ]"
                v-html="link.label"
            />
        </template>
    </div>
</template>
