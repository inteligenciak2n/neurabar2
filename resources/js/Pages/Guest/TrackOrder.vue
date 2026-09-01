<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { usePoll } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useTranslate } from '@/Composables/useTranslate';

const props = defineProps({
    order: Object,
});

const __ = useTranslate();

usePoll(15000);

const statusLabels = {
    open: __('Order received'),
    in_preparation: __('Preparing'),
    ready: props.order.fulfillment_type === 'pickup' ? __('Ready for pickup') : __('Ready'),
    out_for_delivery: __('Out for delivery'),
    delivered: __('Delivered'),
};

const statusLabel = computed(() => statusLabels[props.order.status] ?? props.order.status);
</script>

<template>
    <GuestLayout :title="__('Track Order')">
        <div class="min-h-screen bg-muted flex items-center justify-center p-4">
            <div class="w-full max-w-md">
                <div class="bg-white rounded-xl shadow-card p-6">
                    <h1 class="font-heading text-xl font-bold text-ocean-deep mb-1">
                        {{ __('Order') }} #{{ order.order_number }}
                    </h1>
                    <p class="text-sm text-muted-foreground mb-6">
                        {{ __('Status') }}: <span class="font-medium">{{ statusLabel }}</span>
                    </p>

                    <div v-if="order.items.length === 0" class="text-center py-8 text-muted-foreground">
                        <p>{{ __('No items to display yet.') }}</p>
                    </div>

                    <ul v-else class="space-y-3">
                        <li
                            v-for="item in order.items"
                            :key="item.id"
                            class="flex items-start gap-3 rounded-lg border border-border p-3"
                        >
                            <span
                                class="mt-0.5 h-3 w-3 shrink-0 rounded-full"
                                :style="{ backgroundColor: item.status.color ?? '#94a3b8' }"
                            />
                            <div class="flex-1">
                                <p class="text-sm font-medium text-ocean-deep">
                                    {{ item.product_name }}
                                    <span v-if="item.variation_name" class="font-normal text-muted-foreground"> — {{ item.variation_name }}</span>
                                </p>
                                <p class="text-xs text-muted-foreground">{{ __('Qty') }}: {{ item.quantity }}</p>
                                <p v-if="item.notes" class="text-xs text-muted-foreground italic">{{ item.notes }}</p>
                            </div>
                            <span
                                class="shrink-0 rounded-full px-2 py-0.5 text-xs font-medium"
                                :style="{ backgroundColor: item.status.color + '22', color: item.status.color }"
                            >
                                {{ item.status.name }}
                            </span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>
