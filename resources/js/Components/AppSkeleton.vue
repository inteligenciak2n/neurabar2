<script setup>
defineProps({
    lines: {
        type: Number,
        default: 3,
    },
    height: {
        type: String,
        default: 'h-4',
    },
});
</script>

<template>
    <div class="animate-pulse space-y-2">
        <div
            v-for="i in lines"
            :key="i"
            :class="['rounded bg-muted', height, i === lines && lines > 1 ? 'w-3/4' : 'w-full']"
        />
    </div>
</template>
