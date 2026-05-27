<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import AppCard from '@/Components/AppCard.vue';
import AppButton from '@/Components/AppButton.vue';
import AppBadge from '@/Components/AppBadge.vue';
import AppEmptyState from '@/Components/AppEmptyState.vue';
import { Link, router } from '@inertiajs/vue3';
import { onMounted, onUnmounted } from 'vue';
import { toast } from 'vue-sonner';

const props = defineProps({
    attendance: Object,
    venueId: String,
});

const statusColor = (status) => ({
    open: '#3b82f6',
    in_preparation: '#f59e0b',
    ready: '#22c55e',
    delivered: '#64748b',
}[status] ?? '#94a3b8');

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

let kitchenChannel = null;

onMounted(() => {
    if (!props.venueId) return;

    kitchenChannel = window.Echo.private(`venue.${props.venueId}.kitchen`)
        .listen('.ItemStatusUpdated', (event) => {
            if (event.attendance_id !== props.attendance.id) return;

            if (event.ready_at) {
                playSound();
                toast.success(__('Item ready to serve'), {
                    description: event.preparation_status?.name ?? __('Item is ready'),
                });
            }

            router.reload({ only: ['attendance'] });
        })
        .listen('.OrderPlaced', (event) => {
            if (event.order?.attendance_id !== props.attendance.id) return;

            router.reload({ only: ['attendance'] });
        });
});

onUnmounted(() => {
    if (props.venueId && kitchenChannel) {
        window.Echo.leaveChannel(`venue.${props.venueId}.kitchen`);
    }
});
</script>

<template>
    <AppLayout :title="`${__('Attendance')} — ${attendance.customer_identifier ?? attendance.channel}`">
        <template #header>
            <div class="flex items-center gap-4">
                <Link :href="route('attendances.index')" class="text-sm font-medium text-primary hover:underline">← {{ __('Attendances') }}</Link>
                <h1 class="font-heading text-2xl font-bold text-ocean-deep">
                    {{ attendance.customer_identifier ?? attendance.channel }}
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
            v-if="!attendance.orders?.length"
            :title="__('No orders yet')"
            :description="__('Take the first order for this attendance.')"
        />

        <div v-else class="space-y-4">
            <AppCard v-for="order in attendance.orders" :key="order.id">
                <div class="mb-2 flex items-center justify-between">
                    <span class="font-heading text-sm font-semibold text-ocean-deep">{{ __('Order') }} #{{ order.order_number }}</span>
                    <AppBadge :label="order.status" :color="statusColor(order.status)" />
                </div>

                <div v-if="order.items?.length" class="divide-y divide-muted">
                    <div v-for="item in order.items" :key="item.id" class="flex items-center justify-between py-2">
                        <div>
                            <p class="text-sm text-ocean-deep">{{ item.product?.name }}</p>
                            <p v-if="item.notes" class="text-xs text-muted-foreground">{{ item.notes }}</p>
                            <AppBadge
                                v-if="item.preparation_status"
                                :label="item.preparation_status?.status ? __(item.preparation_status?.status) : __('pending')"
                                :color="statusColor(item.preparation_status?.status)"
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
