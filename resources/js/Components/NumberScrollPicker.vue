<script setup>
import { computed, nextTick, ref, watch } from 'vue';

const props = defineProps({
    open: {
        type: Boolean,
        default: false,
    },
    items: {
        type: Array,
        default: () => [],
    },
    modelValue: {
        type: String,
        default: '',
    },
    highlightId: {
        type: String,
        default: '',
    },
    accentColor: {
        type: String,
        default: '',
    },
    placeholder: {
        type: String,
        default: '',
    },
    emptyText: {
        type: String,
        default: '',
    },
    compact: {
        type: Boolean,
        default: false,
    },
    hideTrigger: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:modelValue', 'update:open']);

const scroller = ref(null);

const selectedItem = computed(() => props.items.find((item) => item.kind !== 'header' && item.id === props.modelValue));
const triggerLabel = computed(() => selectedItem.value?.label || props.placeholder);
const activeId = computed(() => props.modelValue || props.highlightId);
const isPending = computed(() => Boolean(props.highlightId) && !props.modelValue);

const triggerStyle = computed(() => {
    const hex = props.accentColor;
    if (props.modelValue && hex) {
        return { backgroundColor: hex, color: '#fff', borderColor: hex };
    }

    if (isPending.value && hex) {
        return {
            color: hex,
            borderColor: hex,
            borderStyle: 'dashed',
            backgroundColor: `${hex}2e`,
        };
    }

    return {};
});

const toggle = () => {
    if (!props.items.length) {
        return;
    }

    emit('update:open', !props.open);
};

const choose = (item) => {
    if (item?.kind === 'header' || !item?.id) {
        return;
    }

    emit('update:modelValue', item.id);
    emit('update:open', false);
};

const scrollToActive = async () => {
    await nextTick();
    const el = scroller.value?.querySelector('[data-active="true"]');
    el?.scrollIntoView({ block: 'center', behavior: 'auto' });
};

watch(() => props.open, (isOpen) => {
    if (isOpen) {
        scrollToActive();
    }
});

const itemStyle = (item) => {
    const hex = props.accentColor;
    if (item.id === props.modelValue && hex) {
        return { backgroundColor: hex, color: '#fff', borderColor: hex };
    }

    if (item.id === props.highlightId && hex) {
        return { color: hex, borderColor: hex, borderStyle: 'dashed' };
    }

    if (item.tone === 'new') {
        return { backgroundColor: 'rgb(167 243 208 / 0.7)' };
    }

    if (item.tone === 'muted') {
        return { backgroundColor: 'rgb(243 244 246)', color: 'rgb(55 65 81)' };
    }

    return {};
};
</script>

<template>
    <div class="number-scroll-picker">
        <button
            v-if="!hideTrigger"
            type="button"
            class="flex w-full touch-manipulation items-center justify-between rounded-lg border px-3 focus:outline-none focus:ring-2 focus:ring-primary"
            :class="[
                compact ? 'min-h-9 text-xs font-medium' : 'min-h-12 text-base font-semibold',
                {
                    'bg-primary text-white': modelValue && !accentColor,
                    'bg-white text-ocean-deep dark:bg-gray-700 dark:text-gray-100': !modelValue && !isPending,
                },
            ]"
            :disabled="!items.length"
            :style="triggerStyle"
            @click="toggle"
        >
            <span>{{ triggerLabel }}</span>
            <span class="leading-none" :class="compact ? 'text-sm' : 'text-lg'" aria-hidden="true">{{ open ? '▲' : '▼' }}</span>
        </button>
        <p v-if="!items.length" class="mt-1 text-xs text-muted-foreground">{{ emptyText }}</p>
        <div
            v-if="open && items.length"
            class="relative overflow-hidden rounded-xl border border-border bg-white dark:border-gray-600 dark:bg-gray-800"
            :class="hideTrigger ? '' : 'mt-2'"
        >
            <div class="pointer-events-none absolute inset-x-3 top-0 z-10 h-10 bg-gradient-to-b from-white dark:from-gray-800" />
            <div class="pointer-events-none absolute inset-x-3 bottom-0 z-10 h-10 bg-gradient-to-t from-white dark:from-gray-800" />
            <div
                class="pointer-events-none absolute inset-x-2 top-1/2 z-10 h-12 -translate-y-1/2 rounded-lg border-y-2"
                :style="{ borderColor: accentColor || undefined }"
            />
            <div
                ref="scroller"
                class="h-44 overflow-y-auto overscroll-contain scroll-smooth py-16 snap-y snap-mandatory touch-manipulation"
            >
                <template v-for="item in items" :key="item.id">
                    <div
                        v-if="item.kind === 'header'"
                        data-picker-header
                        class="flex h-8 snap-start items-center justify-center px-2 text-[10px] font-semibold uppercase tracking-wide"
                        :class="item.tone === 'muted' ? 'bg-gray-100 text-gray-600' : 'bg-emerald-50 text-emerald-800'"
                    >
                        {{ item.label }}
                    </div>
                    <button
                        v-else
                        type="button"
                        data-picker-option
                        :data-tone="item.tone || 'default'"
                        :data-active="item.id === activeId"
                        class="flex w-full snap-center items-center justify-center focus:outline-none"
                        :class="[
                            compact ? 'h-10 text-lg font-semibold' : 'h-12 text-2xl font-bold',
                            {
                                'text-ocean-deep dark:text-gray-100': item.id !== modelValue && item.id !== highlightId,
                                'text-white': item.id === modelValue && !accentColor,
                            },
                        ]"
                        :style="itemStyle(item)"
                        @click="choose(item)"
                    >
                        {{ item.label }}
                    </button>
                </template>
            </div>
        </div>
    </div>
</template>
