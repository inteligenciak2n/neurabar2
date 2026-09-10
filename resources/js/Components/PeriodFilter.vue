<script setup>
import { computed, ref } from 'vue';
import { useTranslate } from '@/Composables/useTranslate';

const __ = useTranslate();

const props = defineProps({
    // { period: 'today'|'7d'|'30d'|'month'|'custom', from: String|null, to: String|null }
    modelValue: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['update:modelValue']);

const presets = [
    { value: 'today', label: __('Today') },
    { value: '7d', label: __('7 days') },
    { value: '30d', label: __('30 days') },
    { value: 'month', label: __('This month') },
];

const customFrom = ref(props.modelValue.from ?? '');
const customTo = ref(props.modelValue.to ?? '');

const isCustom = computed(() => props.modelValue.period === 'custom');

const selectPreset = (period) => {
    emit('update:modelValue', { period, from: null, to: null });
};

const applyCustomRange = () => {
    if (customFrom.value && customTo.value) {
        emit('update:modelValue', { period: 'custom', from: customFrom.value, to: customTo.value });
    }
};
</script>

<template>
    <div class="flex flex-wrap items-center gap-2">
        <button
            v-for="preset in presets"
            :key="preset.value"
            type="button"
            :class="[
                'rounded-md px-3 py-1.5 text-sm font-medium transition-colors',
                modelValue.period === preset.value
                    ? 'bg-primary text-white'
                    : 'bg-muted text-muted-foreground hover:bg-muted/70 dark:bg-gray-700 dark:text-gray-300',
            ]"
            @click="selectPreset(preset.value)"
        >
            {{ preset.label }}
        </button>

        <div class="flex items-center gap-2">
            <input
                v-model="customFrom"
                type="date"
                :class="[
                    'rounded-md border-border text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100',
                    isCustom ? 'ring-1 ring-primary' : '',
                ]"
                @change="applyCustomRange"
            />
            <span class="text-sm text-muted-foreground">{{ __('to') }}</span>
            <input
                v-model="customTo"
                type="date"
                :class="[
                    'rounded-md border-border text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100',
                    isCustom ? 'ring-1 ring-primary' : '',
                ]"
                @change="applyCustomRange"
            />
        </div>
    </div>
</template>
