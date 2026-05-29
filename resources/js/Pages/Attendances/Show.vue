<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import AppCard from '@/Components/AppCard.vue';
import AppButton from '@/Components/AppButton.vue';
import AppBadge from '@/Components/AppBadge.vue';
import AppEmptyState from '@/Components/AppEmptyState.vue';
import { Link } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref } from 'vue';
import { toast } from 'vue-sonner';
import axios from 'axios';
import { useTranslate } from '@/Composables/useTranslate';

const props = defineProps({
    attendance: Object,
    venueId: String,
});

const __ = useTranslate();

const orders = ref(props.attendance.orders ?? []);

const statusColor = (status) => ({
    open: '#3b82f6',
    in_preparation: '#f59e0b',
    ready: '#22c55e',
    delivered: '#64748b',
}[status] ?? '#94a3b8');

const statusName = (status) => ({
    open: __('Open'),
    in_preparation: __('In Preparation'),
    ready: __('Ready'),
    delivered: __('Delivered'),
}[status] ?? __('Unknown'));

const channelsLabel = (channel) => channel;

let notificationSound = null;

function playSound() {
    try {
        if (!notificationSound) {
            notificationSound = new Audio('/sounds/new-order.mp3');
        }
        notificationSound.play().catch(() => {});
    } catch {
        // Audio not available
    }
}

async function fetchOrders() {
    const { data } = await axios.get(route('attendances.orders', props.attendance.id));
    orders.value = data;
}

let kitchenChannel = null;

onMounted(() => {
    if (!props.venueId) return;

    kitchenChannel = window.Echo.private(`venue.${props.venueId}.kitchen`)
        .listen('.ItemStatusUpdated', async (event) => {
            if (event.attendance_id !== props.attendance.id) return;

            if (event.ready_at) {
                playSound();
                toast.success(__('Item ready to serve'), {
                    description: event.preparation_status?.name ?? __('Item is ready'),
                });
            }

            await fetchOrders();
        })
        .listen('.OrderPlaced', async (event) => {
            if (event.order?.attendance_id !== props.attendance.id) return;

            await fetchOrders();
        });
});

onUnmounted(() => {
    if (props.venueId && kitchenChannel) {
        window.Echo.leaveChannel(`venue.${props.venueId}.kitchen`);
    }
});
</script>

<template>
    <AppLayout :title="`${__('Attendance')} — ${attendance.customer_identifier ?? attendance.attendance_channel?.name ?? ''}`">
        <template #header>
            <div class="flex items-center gap-4">
                <Link :href="route('attendances.index')" class="text-sm font-medium text-primary hover:underline">← {{ __('Attendances') }}</Link>
                <h1 class="font-heading text-2xl font-bold text-ocean-deep">
                    {{ attendance.customer_identifier ?? attendance.attendance_channel?.name ?? '' }}
                </h1>
                <AppBadge :label="attendance.status" :color="attendance.status === 'open' ? '#22c55e' : '#94a3b8'" />
            </div>
        </template>

        <div class="mb-4 flex items-center justify-between">
            <p class="text-sm text-muted-foreground">
                {{ __('Opened by') }} {{ attendance.created_by?.name }} · {{ attendance.party_size ? attendance.party_size + ' ' + __('guests') : '' }}
            </p>
            <div class="flex gap-2">
                <Link :href="route('orders.take', attendance.id)">
                    <AppButton>{{ __('Take Order') }}</AppButton>
                </Link>
                <Link :href="route('payment.show', attendance.id)">
                    <AppButton variant="secondary">{{ __('Payment') }}</AppButton>
                </Link>
            </div>
        </div>

        <AppEmptyState
            v-if="!orders.length"
            :title="__('No orders yet')"
            :description="__('Take the first order for this attendance.')"
        />

        <div v-else class="space-y-4">
            <AppCard v-for="order in orders" :key="order.id">
                <div class="mb-2 flex items-center justify-between">
                    <span class="font-heading text-sm font-semibold text-ocean-deep">{{ __('Order') }} #{{ order.order_number }}</span>
                    <AppBadge :label="statusName(order.status)" :color="statusColor(order.status)" />
                </div>

                <div v-if="order.items?.length" class="divide-y divide-muted">
                    <div v-for="item in order.items" :key="item.id" class="flex items-center justify-between py-2">
                        <div>
                            <p class="text-sm text-ocean-deep">{{ item.product?.name }}</p>
                            <p v-if="item.notes" class="text-xs text-muted-foreground">{{ item.notes }}</p>
                            <AppBadge
                                v-if="item.preparation_status"
                                :label="item.preparation_status.name"
                                :color="item.preparation_status.color"
                                class="mt-1"
                            />
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-ocean-deep">{{ item.quantity }}×</p>
                            <p class="text-xs text-muted-foreground">R$ {{ Number(item.unit_price).toFixed(2) }}</p>
                        </div>
                    </div>
                </div>
            </AppCard>
        </div>
    </AppLayout>
</template>
