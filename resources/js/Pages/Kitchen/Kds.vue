<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import AppBadge from '@/Components/AppBadge.vue';
import AppButton from '@/Components/AppButton.vue';
import AppEmptyState from '@/Components/AppEmptyState.vue';
import AppSkeleton from '@/Components/AppSkeleton.vue';
import { router, usePage } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref, computed } from 'vue';
import axios from 'axios';

const props = defineProps({
    stations: Array,
    preparationStatuses: Array,
    openItems: Object,
});

const page = usePage();
const isLoading = ref(false);

const venueId = computed(() => page.props.defs?.venue?.id);

function getItemsForStation(stationId) {
    return props.openItems?.[stationId] ?? [];
}

function getTimeBadge(createdAt) {
    const mins = (Date.now() - new Date(createdAt).getTime()) / 60000;
    if (mins < 5) return { label: `${Math.floor(mins)}m`, class: 'bg-green-100 text-green-800' };
    if (mins < 10) return { label: `${Math.floor(mins)}m`, class: 'bg-yellow-100 text-yellow-800' };
    return { label: `${Math.floor(mins)}m`, class: 'bg-red-100 text-red-800' };
}

function updateStatus(item, statusId) {
    router.put(route('kitchen.items.status', item.id), { preparation_status_id: statusId }, {
        preserveScroll: true,
    });
}

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

function reload() {
    router.reload({ only: ['openItems'] });
}

let kitchenChannel = null;

onMounted(() => {
    if (!venueId.value) return;

    kitchenChannel = window.Echo.private(`venue.${venueId.value}.kitchen`)
        .listen('.OrderPlaced', () => {
            playSound();
            reload();
        })
        .listen('.ItemStatusUpdated', () => {
            reload();
        });
});

onUnmounted(() => {
    if (venueId.value && kitchenChannel) {
        window.Echo.leaveChannel(`venue.${venueId.value}.kitchen`);
    }
});

const allStations = computed(() => {
    const unassignedItems = getItemsForStation('unassigned');
    const extra = unassignedItems.length > 0
        ? [{ id: 'unassigned', name: 'Unassigned' }]
        : [];
    return [...(props.stations ?? []), ...extra];
});
</script>

<template>
    <AppLayout :title="__('Kitchen KDS')">
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-heading text-xl font-semibold text-ocean-deep">{{ __('Kitchen KDS') }}</h2>
                <AppButton variant="ghost" size="sm" @click="reload">{{ __('Refresh') }}</AppButton>
            </div>
        </template>

        <div class="py-6 px-4 sm:px-6">
            <!-- Empty state -->
            <AppEmptyState
                v-if="allStations.length === 0"
                :title="__('No kitchen stations')"
                :description="__('Configure kitchen stations in Settings to start using the KDS.')"
            />

            <!-- Columns by station -->
            <div v-else class="grid gap-4" :style="`grid-template-columns: repeat(${allStations.length}, minmax(260px, 1fr))`">
                <div v-for="station in allStations" :key="station.id" class="flex flex-col gap-3">
                    <!-- Station header -->
                    <div class="sticky top-20 z-10 rounded-lg bg-white shadow-card px-4 py-2 flex items-center justify-between">
                        <span class="font-heading font-semibold text-sm text-ocean-deep">{{ station.name }}</span>
                        <AppBadge
                            :label="`${getItemsForStation(station.id).length}`"
                            variant="primary"
                        />
                    </div>

                    <!-- Empty station -->
                    <div
                        v-if="getItemsForStation(station.id).length === 0"
                        class="rounded-lg border-2 border-dashed border-border p-6 text-center text-sm text-muted-foreground"
                    >
                        {{ __('No pending items') }}
                    </div>

                    <!-- Order item cards -->
                    <div
                        v-for="item in getItemsForStation(station.id)"
                        :key="item.id"
                        class="rounded-lg bg-white shadow-card p-4 flex flex-col gap-3"
                    >
                        <!-- Header row -->
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <p class="font-heading font-semibold text-sm text-ocean-deep">
                                    {{ item.order?.attendance?.customer_identifier ?? __('Guest') }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{ __('Order') }} #{{ item.order?.order_number }}
                                </p>
                            </div>
                            <span
                                class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                                :class="getTimeBadge(item.created_at).class"
                            >
                                {{ getTimeBadge(item.created_at).label }}
                            </span>
                        </div>

                        <!-- Product -->
                        <div>
                            <p class="font-body font-medium text-sm text-ocean-deep">
                                {{ item.quantity }}× {{ item.product?.name ?? __('Custom request') }}
                            </p>
                            <p v-if="item.variation" class="text-xs text-muted-foreground">
                                {{ item.variation.name }}
                            </p>
                            <!-- Combo badge -->
                            <span
                                v-if="item.combo"
                                class="mt-1 inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800"
                            >
                                🍱 {{ item.combo.name }}
                            </span>
                            <!-- Modifiers list -->
                            <ul v-if="item.modifiers?.length" class="mt-1 space-y-0.5">
                                <li
                                    v-for="modifier in item.modifiers"
                                    :key="modifier.id"
                                    class="text-xs text-muted-foreground"
                                >
                                    + {{ modifier.modifier_option?.name }}
                                    <span v-if="modifier.extra_price_snapshot > 0" class="text-primary">
                                        (+R$ {{ Number(modifier.extra_price_snapshot).toFixed(2) }})
                                    </span>
                                </li>
                            </ul>
                            <p v-if="item.notes" class="mt-1 text-xs text-muted-foreground italic">
                                {{ item.notes }}
                            </p>
                        </div>

                        <!-- Current status -->
                        <div v-if="item.preparation_status" class="flex items-center gap-1.5">
                            <AppBadge :label="item.preparation_status.name" :color="item.preparation_status.color" />
                        </div>

                        <!-- Status buttons -->
                        <div class="flex flex-wrap gap-1.5 pt-1 border-t border-border">
                            <button
                                v-for="status in preparationStatuses"
                                :key="status.id"
                                class="rounded-md px-2.5 py-1 text-xs font-body font-medium transition-colors border"
                                :style="item.preparation_status_id === status.id
                                    ? { backgroundColor: status.color, borderColor: status.color, color: '#fff' }
                                    : { borderColor: status.color + '60', color: status.color }"
                                @click="updateStatus(item, status.id)"
                            >
                                {{ status.name }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
