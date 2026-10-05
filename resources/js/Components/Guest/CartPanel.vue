<script setup>
import { computed } from 'vue';
import { useTranslate } from '@/Composables/useTranslate';
import axios from 'axios';

const props = defineProps({
    token: {
        type: String,
        default: null,
    },
    items: Array,
    modelValue: Boolean,
    preview: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:modelValue', 'remove', 'order-placed', 'error', 'select-item']);

const __ = useTranslate();

const subtotal = computed(() =>
    props.items.reduce((sum, item) => sum + item.unit_price * item.quantity, 0),
);

async function placeOrder() {
    if (props.preview) {
        emit('error', __('This is a preview. Orders cannot be placed from here.'));
        return;
    }

    try {
        const payload = props.items.map((item) => ({
            product_id: item.product_id,
            variation_id: item.variation_id,
            quantity: item.quantity,
            notes: item.notes,
            modifiers: item.modifiers,
        }));
        await axios.post(`/g/${props.token}/orders`, { items: payload });
        emit('order-placed');
        emit('update:modelValue', false);
    } catch (e) {
        emit('error', e.response?.data?.message ?? __('Error placing order.'));
    }
}
</script>

<template>
    <Teleport to="body">
        <div
            v-if="modelValue"
            class="fixed inset-0 z-50 flex items-end sm:items-center justify-center"
        >
            <div class="absolute inset-0 bg-black/50" @click="emit('update:modelValue', false)" />

            <div class="relative w-full max-w-sm bg-white rounded-t-2xl sm:rounded-2xl shadow-xl flex flex-col max-h-[90vh]">
                <!-- Header -->
                <div class="flex items-center justify-between px-5 pt-5 pb-3 border-b border-border">
                    <h3 class="font-heading text-lg font-bold text-ocean-deep">{{ __('Cart') }}</h3>
                    <button class="rounded-full p-1 text-muted-foreground hover:bg-muted" @click="emit('update:modelValue', false)">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Items -->
                <div class="flex-1 overflow-y-auto px-5 py-3 space-y-3">
                    <div
                        v-for="(item, index) in items"
                        :key="index"
                        class="flex items-start gap-3 rounded-xl border border-border p-3"
                    >
                        <button
                            type="button"
                            class="flex-1 text-left text-sm"
                            @click="emit('select-item', index)"
                        >
                            <p class="font-medium text-ocean-deep">{{ item.product_name }}</p>
                            <p v-if="item.variation_name" class="text-xs text-muted-foreground">{{ item.variation_name }}</p>
                            <ul v-if="item.modifier_details?.length" class="mt-1 space-y-0.5">
                                <li
                                    v-for="(detail, detailIndex) in item.modifier_details"
                                    :key="detailIndex"
                                    class="text-xs text-muted-foreground"
                                >
                                    {{ detail.group }}: {{ detail.name }}
                                </li>
                            </ul>
                            <p v-if="item.notes" class="mt-0.5 text-xs italic text-muted-foreground">{{ item.notes }}</p>
                            <p class="mt-1 text-xs font-semibold text-primary">
                                {{ item.quantity }}x · R$ {{ (item.unit_price * item.quantity).toFixed(2) }}
                            </p>
                        </button>
                        <button
                            class="shrink-0 rounded-full p-1 text-destructive hover:bg-destructive/10"
                            @click="emit('remove', index)"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Footer -->
                <div class="border-t border-border px-5 py-4">
                    <div class="mb-3 flex items-center justify-between text-sm">
                        <span class="text-muted-foreground">{{ __('Subtotal') }}</span>
                        <span class="font-bold text-ocean-deep">R$ {{ subtotal.toFixed(2) }}</span>
                    </div>
                    <button
                        :disabled="preview || !items.length"
                        class="w-full rounded-xl bg-primary py-3 text-sm font-semibold text-white disabled:opacity-50 active:opacity-80"
                        @click="placeOrder"
                    >
                        {{ __('Place Order') }}
                    </button>
                    <p v-if="preview" class="mt-2 text-center text-xs text-muted-foreground">{{ __('This is a preview. Orders cannot be placed from here.') }}</p>
                </div>
            </div>
        </div>
    </Teleport>
</template>
