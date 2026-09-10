<script setup>
import { computed } from 'vue';
import AppCard from '@/Components/AppCard.vue';

const props = defineProps({
    label: {
        type: String,
        required: true,
    },
    value: {
        type: [String, Number],
        required: true,
    },
    // percentual de variação vs período anterior; null oculta o indicador
    delta: {
        type: Number,
        default: null,
    },
});

const deltaClass = computed(() => (props.delta >= 0 ? 'text-success' : 'text-destructive'));
</script>

<template>
    <AppCard>
        <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">{{ label }}</p>
        <p class="mt-2 font-heading text-2xl font-bold text-ocean-deep dark:text-gray-100">{{ value }}</p>
        <p v-if="delta !== null" :class="['mt-1 text-xs font-medium', deltaClass]">
            {{ delta >= 0 ? '+' : '' }}{{ delta.toFixed(1) }}% {{ __('vs previous period') }}
        </p>
    </AppCard>
</template>
