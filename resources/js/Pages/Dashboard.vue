<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import AppCard from '@/Components/AppCard.vue';
import AppBadge from '@/Components/AppBadge.vue';
import AppButton from '@/Components/AppButton.vue';
import AppEmptyState from '@/Components/AppEmptyState.vue';
import { router, usePage, Link } from '@inertiajs/vue3';
import { onMounted, onUnmounted, computed } from 'vue';

const props = defineProps({
    open_attendances_count: Number,
    items_in_preparation: Number,
    todays_revenue: Number,
    attendances_list: Array,
    stations_summary: Array,
});

const page = usePage();
const venueId = computed(() => page.props.auth?.venue?.id);

const channelLabels = {
    table: 'Table',
    counter: 'Counter',
    delivery: 'Delivery',
    service_request: 'Service',
};

function elapsedMinutes(createdAt) {
    const mins = Math.floor((Date.now() - new Date(createdAt).getTime()) / 60000);
    if (mins < 60) return `${mins}m`;
    return `${Math.floor(mins / 60)}h ${mins % 60}m`;
}

function partialTotal(attendance) {
    let total = 0;
    for (const order of attendance.orders ?? []) {
        for (const item of order.items ?? []) {
            total += parseFloat(item.unit_price ?? 0) * (item.quantity ?? 1);
        }
    }
    return 'R$ ' + total.toFixed(2);
}

function reloadMetrics() {
    router.reload({ only: ['open_attendances_count', 'items_in_preparation', 'todays_revenue', 'attendances_list', 'stations_summary'] });
}

let kitchenChannel = null;

onMounted(() => {
    if (!venueId.value) return;
    kitchenChannel = window.Echo.private(`venue.${venueId.value}.kitchen`)
        .listen('.OrderPlaced', reloadMetrics)
        .listen('.ItemStatusUpdated', reloadMetrics);
});

onUnmounted(() => {
    if (venueId.value && kitchenChannel) {
        window.Echo.leaveChannel(`venue.${venueId.value}.kitchen`);
    }
});
</script>

<template>
    <AppLayout title="Dashboard">
        <template #header>
            <h2 class="font-heading text-xl font-semibold text-ocean-deep">Dashboard</h2>
        </template>

        <div class="py-6 px-4 sm:px-6 max-w-7xl mx-auto space-y-6">
            <!-- Metrics row -->
            <div class="grid gap-4 sm:grid-cols-3">
                <AppCard>
                    <p class="text-sm font-body text-muted-foreground">Open Attendances</p>
                    <p class="font-heading text-3xl font-bold text-primary mt-1">{{ open_attendances_count }}</p>
                </AppCard>
                <AppCard>
                    <p class="text-sm font-body text-muted-foreground">Items in Preparation</p>
                    <p class="font-heading text-3xl font-bold text-warm-gold mt-1">{{ items_in_preparation }}</p>
                </AppCard>
                <AppCard>
                    <p class="text-sm font-body text-muted-foreground">Today's Revenue</p>
                    <p class="font-heading text-3xl font-bold text-accent mt-1">
                        R$ {{ todays_revenue.toFixed(2) }}
                    </p>
                </AppCard>
            </div>

            <!-- Quick actions + Stations -->
            <div class="grid gap-4 lg:grid-cols-3">
                <!-- Quick actions -->
                <AppCard title="Quick Actions">
                    <div class="flex flex-col gap-2">
                        <Link :href="route('attendances.index')">
                            <AppButton variant="primary" class="w-full">New Attendance</AppButton>
                        </Link>
                        <Link :href="route('kitchen.kds')">
                            <AppButton variant="secondary" class="w-full">Open KDS</AppButton>
                        </Link>
                        <Link :href="route('menu.index')">
                            <AppButton variant="ghost" class="w-full">Manage Menu</AppButton>
                        </Link>
                    </div>
                </AppCard>

                <!-- Stations summary -->
                <AppCard title="Stations" class="lg:col-span-2">
                    <div v-if="!stations_summary.length" class="text-sm text-muted-foreground">
                        No kitchen stations configured.
                    </div>
                    <div v-else class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                        <div
                            v-for="station in stations_summary"
                            :key="station.id"
                            class="rounded-lg border border-border p-3 flex flex-col gap-1"
                        >
                            <p class="text-xs font-medium text-muted-foreground">{{ station.name }}</p>
                            <p class="font-heading text-2xl font-bold" :class="station.pending_items_count > 0 ? 'text-warm-gold' : 'text-muted-foreground'">
                                {{ station.pending_items_count }}
                            </p>
                            <p class="text-xs text-muted-foreground">pending</p>
                        </div>
                    </div>
                </AppCard>
            </div>

            <!-- Open attendances table -->
            <AppCard title="Open Attendances">
                <AppEmptyState
                    v-if="!attendances_list.length"
                    title="No open attendances"
                    description="Open a new attendance to start taking orders."
                    action-label="New Attendance"
                    @action="router.visit(route('attendances.index'))"
                />

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm font-body">
                        <thead>
                            <tr class="border-b border-border text-left text-muted-foreground">
                                <th class="pb-2 pr-4 font-medium">Identifier</th>
                                <th class="pb-2 pr-4 font-medium">Channel</th>
                                <th class="pb-2 pr-4 font-medium">Open for</th>
                                <th class="pb-2 pr-4 font-medium">Partial total</th>
                                <th class="pb-2 font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-muted">
                            <tr v-for="attendance in attendances_list" :key="attendance.id" class="hover:bg-muted/40">
                                <td class="py-2 pr-4 font-medium text-ocean-deep">
                                    {{ attendance.customer_identifier ?? '—' }}
                                </td>
                                <td class="py-2 pr-4">
                                    <AppBadge :label="channelLabels[attendance.channel] ?? attendance.channel" variant="muted" />
                                </td>
                                <td class="py-2 pr-4 text-muted-foreground">{{ elapsedMinutes(attendance.created_at) }}</td>
                                <td class="py-2 pr-4 text-muted-foreground">{{ partialTotal(attendance) }}</td>
                                <td class="py-2">
                                    <div class="flex gap-2">
                                        <Link :href="route('orders.take', attendance.id)">
                                            <AppButton size="sm" variant="ghost">Order</AppButton>
                                        </Link>
                                        <Link :href="route('payment.show', attendance.id)">
                                            <AppButton size="sm" variant="ghost">Pay</AppButton>
                                        </Link>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </AppCard>
        </div>
    </AppLayout>
</template>
