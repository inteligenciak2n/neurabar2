<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import AppBadge from '@/Components/AppBadge.vue';
import AppButton from '@/Components/AppButton.vue';
import AppEmptyState from '@/Components/AppEmptyState.vue';
import Modal from '@/Components/Modal.vue';
import { router, usePage } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref, computed, watch } from 'vue';
import { useTranslate } from '@/Composables/useTranslate';
import { useNotificationSound } from '@/Composables/useNotificationSound';

const __ = useTranslate();

const WAITER_CALLER_CODE = 'waiter_caller';

const props = defineProps({
    stations: Array,
    preparationStatuses: Array,
    openItems: Object,
    readyDeliveryOrders: Array,
    waiterCalls: Array,
    availableTabs: {
        type: Array,
        default: () => [],
    },
    openTabbedAttendances: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const showStationPicker = ref(false);
const pickerMode = ref('open');
const draftStationIds = ref([]);
const selectedStationIds = ref([]);
const hiddenStationIds = ref([]);
const now = ref(Date.now());
const isFullscreen = ref(false);

const venueId = computed(() => page.props.defs?.venue?.id);

const parseStationQuery = () => {
    const search = page.url.includes('?') ? page.url.slice(page.url.indexOf('?') + 1) : '';
    const params = new URLSearchParams(search);

    return {
        display: params.get('display') === '1',
        stations: (params.get('stations') ?? '')
            .split(',')
            .map((id) => id.trim())
            .filter(Boolean),
    };
};

const isDedicatedDisplay = computed(() => parseStationQuery().display);

watch(() => page.url, () => {
    selectedStationIds.value = parseStationQuery().stations;
}, { immediate: true });

const selectableStations = computed(() => props.stations ?? []);

const isStationDraftSelected = (stationId) => draftStationIds.value.includes(String(stationId));

function toggleDraftStation(stationId) {
    const key = String(stationId);

    if (isStationDraftSelected(key)) {
        draftStationIds.value = draftStationIds.value.filter((id) => id !== key);

        return;
    }

    draftStationIds.value = [...draftStationIds.value, key];
}

function openStationPicker(mode) {
    pickerMode.value = mode;
    draftStationIds.value = mode === 'edit'
        ? [...selectedStationIds.value]
        : selectableStations.value.map((station) => String(station.id));
    showStationPicker.value = true;
}

function dedicatedScreenUrl(stationIds) {
    const params = new URLSearchParams({
        display: '1',
        stations: stationIds.join(','),
    });

    return `${route('kitchen.kds')}?${params.toString()}`;
}

function confirmStationPicker() {
    if (!draftStationIds.value.length) {
        return;
    }

    showStationPicker.value = false;

    if (pickerMode.value === 'open') {
        window.open(dedicatedScreenUrl(draftStationIds.value), '_blank', 'noopener');

        return;
    }

    selectedStationIds.value = [...draftStationIds.value];
    router.get(route('kitchen.kds'), {
        display: 1,
        stations: draftStationIds.value.join(','),
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function isWaiterCaller(station) {
    return station.code === WAITER_CALLER_CODE;
}

function stationLabel(station) {
    return isWaiterCaller(station) ? __('Waiter caller (text message)') : station.name;
}

function stationChipLabel(station) {
    return isWaiterCaller(station) ? __('Waiter') : station.name;
}

function stationColumnLabel(station) {
    return isWaiterCaller(station) ? __('Waiter calls') : station.name;
}

function stationCount(station) {
    return isWaiterCaller(station) ? waiterCallGroups.value.length : cardsForStation(station.id).length;
}

function getItemsForStation(stationId) {
    return props.openItems?.[stationId] ?? [];
}

function isStationVisible(station) {
    return !hiddenStationIds.value.includes(String(station.id));
}

function toggleStationVisibility(stationId) {
    const key = String(stationId);

    if (hiddenStationIds.value.includes(key)) {
        hiddenStationIds.value = hiddenStationIds.value.filter((id) => id !== key);

        return;
    }

    if (visibleStations.value.length <= 1) {
        return;
    }

    hiddenStationIds.value = [...hiddenStationIds.value, key];
}

const sortedStatuses = computed(() =>
    [...(props.preparationStatuses ?? [])].sort((a, b) => (a.sort_order ?? 0) - (b.sort_order ?? 0)),
);

function minutesWaiting(createdAt) {
    return Math.max(0, (now.value - new Date(createdAt).getTime()) / 60000);
}

function formatWait(createdAt) {
    const mins = Math.floor(minutesWaiting(createdAt));

    if (mins < 60) {
        return `${mins} min`;
    }

    const hours = Math.floor(mins / 60);
    const rest = mins % 60;

    return `${hours}h ${rest}min`;
}

function urgencyTone(createdAt) {
    const mins = minutesWaiting(createdAt);

    if (mins < 10) {
        return 'green';
    }

    if (mins < 20) {
        return 'amber';
    }

    return 'red';
}

function urgencyHeaderClass(createdAt) {
    const tone = urgencyTone(createdAt);

    if (tone === 'green') {
        return 'bg-emerald-500';
    }

    if (tone === 'amber') {
        return 'bg-amber-500';
    }

    return 'bg-red-500';
}

function attendanceNeedsTabLink(attendance) {
    return Boolean(attendance)
        && !attendance.tab_id
        && !attendance.tab?.id
        && !String(attendance.code ?? '').trim();
}

function attendanceTabLabel(attendance) {
    return attendance?.tab?.code ?? attendance?.code ?? null;
}

function attendanceTableLabel(attendance) {
    return attendance?.service_location?.name
        ?? attendance?.customer_identifier
        ?? __('Guest');
}

function linkTabOptions(item) {
    const locationId = item.order?.attendance?.service_location_id
        ?? item.order?.attendance?.service_location?.id;
    const seen = new Set();
    const options = [];

    (props.openTabbedAttendances ?? []).forEach((attendance) => {
        if (String(attendance.service_location_id ?? '') !== String(locationId ?? '')) {
            return;
        }

        if (!attendance.tab_id || seen.has(attendance.tab_id)) {
            return;
        }

        seen.add(attendance.tab_id);
        options.push({ id: attendance.tab_id, label: attendance.tab?.code ?? '' });
    });

    (props.availableTabs ?? []).forEach((tab) => {
        if (seen.has(tab.id)) {
            return;
        }

        seen.add(tab.id);
        options.push({ id: tab.id, label: tab.code });
    });

    return options;
}

const linkingItem = ref(null);
const pendingStatusId = ref(null);
const pendingAdvanceItems = ref([]);

function openLinkTab(item, statusId = null, items = []) {
    linkingItem.value = item;
    pendingStatusId.value = statusId;
    pendingAdvanceItems.value = items.length ? items : [item];
}

function closeLinkTab() {
    linkingItem.value = null;
    pendingStatusId.value = null;
    pendingAdvanceItems.value = [];
}

function confirmLinkTab(tabId) {
    const item = linkingItem.value;

    if (!tabId || !item?.order?.attendance?.id) {
        return;
    }

    const items = [...pendingAdvanceItems.value];
    const statusId = pendingStatusId.value;

    router.put(route('attendances.link-tab', item.order.attendance.id), { tab_id: tabId }, {
        preserveScroll: true,
        onSuccess: () => {
            if (statusId) {
                items.forEach((advanceItem) => {
                    router.put(route('kitchen.items.status', advanceItem.id), {
                        preparation_status_id: statusId,
                    }, {
                        preserveScroll: true,
                    });
                });
            }
            closeLinkTab();
        },
    });
}

function putItemStatus(item, statusId) {
    router.put(route('kitchen.items.status', item.id), { preparation_status_id: statusId }, {
        preserveScroll: true,
    });
}

function updateStatus(item, statusId) {
    if (attendanceNeedsTabLink(item.order?.attendance)) {
        openLinkTab(item, statusId);

        return;
    }

    putItemStatus(item, statusId);
}

function itemStatusIndex(item) {
    const currentId = item?.preparation_status_id ?? item?.preparation_status?.id;

    return sortedStatuses.value.findIndex((status) => status.id === currentId);
}

function nextStatusFor(item) {
    const index = itemStatusIndex(item);

    if (index === -1) {
        return sortedStatuses.value.find((status) => !status.is_initial) ?? sortedStatuses.value[0] ?? null;
    }

    return sortedStatuses.value[index + 1] ?? null;
}

function slowestItem(items) {
    return [...items].sort((a, b) => {
        const aIndex = itemStatusIndex(a);
        const bIndex = itemStatusIndex(b);
        const aOrder = aIndex === -1 ? -1 : aIndex;
        const bOrder = bIndex === -1 ? -1 : bIndex;

        return aOrder - bOrder;
    })[0];
}

function cardStatus(group) {
    const item = slowestItem(group.items);
    const status = item?.preparation_status;
    const index = itemStatusIndex(item);

    if (!status || status.is_initial || index <= 0) {
        return { label: __('New'), tone: 'new' };
    }

    return { label: status.name, tone: 'prep' };
}

function cardNextStatus(group) {
    return nextStatusFor(slowestItem(group.items));
}

function advanceGroup(group) {
    const next = cardNextStatus(group);

    if (!next) {
        return;
    }

    const items = group.items.filter((item) => item.preparation_status_id !== next.id);
    const unlinked = items.find((item) => attendanceNeedsTabLink(item.order?.attendance));

    if (unlinked) {
        openLinkTab(unlinked, next.id, items);

        return;
    }

    items.forEach((item) => putItemStatus(item, next.id));
}

function cardsForStation(stationId) {
    const items = [...getItemsForStation(stationId)].sort(
        (a, b) => new Date(a.created_at) - new Date(b.created_at),
    );
    const groups = [];
    const indexByKey = {};

    items.forEach((item) => {
        const key = item.order?.id ?? item.id;
        let group = indexByKey[key];

        if (!group) {
            group = {
                id: key,
                items: [],
                createdAt: item.created_at,
            };
            indexByKey[key] = group;
            groups.push(group);
        }

        group.items.push(item);

        if (new Date(item.created_at) < new Date(group.createdAt)) {
            group.createdAt = item.created_at;
        }
    });

    return groups.sort((a, b) => new Date(a.createdAt) - new Date(b.createdAt));
}

const waiterCallGroups = computed(() => {
    const groups = [];
    const indexByKey = {};

    (props.waiterCalls ?? []).forEach((request) => {
        const key = request.service_location?.id ?? request.id;
        let group = indexByKey[key];

        if (!group) {
            group = {
                id: key,
                table: request.service_location?.name ?? __('Guest'),
                createdAt: request.created_at,
                requests: [],
            };
            indexByKey[key] = group;
            groups.push(group);
        }

        group.requests.push(request);

        if (new Date(request.created_at) < new Date(group.createdAt)) {
            group.createdAt = request.created_at;
        }
    });

    return groups.sort((a, b) => new Date(a.createdAt) - new Date(b.createdAt));
});

function groupHasPending(group) {
    return group.requests.some((request) => request.status === 'pending');
}

const { playSound } = useNotificationSound();

function reload() {
    router.reload({ only: ['openItems', 'readyDeliveryOrders', 'waiterCalls', 'availableTabs', 'openTabbedAttendances'] });
}

function acknowledgeCall(request) {
    router.put(route('service-requests.acknowledge', request.id), {}, { preserveScroll: true });
}

function resolveCall(request) {
    router.put(route('service-requests.resolve', request.id), {}, { preserveScroll: true });
}

function acknowledgeGroup(group) {
    group.requests.filter((request) => request.status === 'pending').forEach((request) => acknowledgeCall(request));
}

function resolveGroup(group) {
    group.requests.forEach((request) => resolveCall(request));
}

function advanceDeliveryStatus(order) {
    router.put(route('kitchen.orders.advance-delivery-status', order.id), {}, {
        preserveScroll: true,
    });
}

function deliveryActionLabel(order) {
    const isDelivery = order.attendance?.delivery_order?.fulfillment_type === 'delivery';

    if (order.status === 'ready') {
        return isDelivery ? __('Mark as out for delivery') : __('Mark as picked up');
    }

    return __('Mark as delivered');
}

async function toggleFullscreen() {
    try {
        if (document.fullscreenElement) {
            await document.exitFullscreen();

            return;
        }

        await document.documentElement.requestFullscreen();
    } catch (error) {
        openStationPicker('open');
    }
}

function syncFullscreen() {
    isFullscreen.value = Boolean(document.fullscreenElement);
}

let kitchenChannel = null;
let serviceRequestsChannel = null;
let timeTicker = null;

onMounted(() => {
    timeTicker = window.setInterval(() => {
        now.value = Date.now();
    }, 15000);

    document.addEventListener('fullscreenchange', syncFullscreen);

    if (!venueId.value) {
        return;
    }

    kitchenChannel = window.Echo.private(`venue.${venueId.value}.kitchen`)
        .listen('.OrderPlaced', () => {
            playSound();
            reload();
        })
        .listen('.ItemStatusUpdated', () => {
            reload();
        })
        .listen('.OrderStatusUpdated', () => {
            reload();
        });

    serviceRequestsChannel = window.Echo.private(`venue.${venueId.value}.service-requests`)
        .listen('.ServiceRequestCreated', (event) => {
            if (event.type !== 'message') return;
            playSound();
            reload();
        })
        .listen('.ServiceRequestUpdated', (event) => {
            if (event.type !== 'message') return;
            reload();
        });
});

onUnmounted(() => {
    if (timeTicker) {
        window.clearInterval(timeTicker);
    }

    document.removeEventListener('fullscreenchange', syncFullscreen);

    if (!venueId.value) {
        return;
    }

    if (kitchenChannel) {
        window.Echo.leaveChannel(`venue.${venueId.value}.kitchen`);
    }

    if (serviceRequestsChannel) {
        window.Echo.leaveChannel(`venue.${venueId.value}.service-requests`);
    }
});

const allStations = computed(() => {
    const unassignedItems = getItemsForStation('unassigned');
    const extra = !isDedicatedDisplay.value && unassignedItems.length > 0
        ? [{ id: 'unassigned', name: __('Unassigned') }]
        : [];
    const list = [...(props.stations ?? []), ...extra];

    if (!isDedicatedDisplay.value) {
        return list;
    }

    const allowed = new Set(selectedStationIds.value.map((id) => String(id)));

    return list.filter((station) => allowed.has(String(station.id)));
});

const visibleStations = computed(() => allStations.value.filter((station) => isStationVisible(station)));
</script>

<template>
    <AppLayout :title="__('Kitchen KDS')">
        <template #header>
            <div class="flex flex-col gap-3">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex min-w-0 items-center gap-3">
                        <h1 class="font-heading text-xl font-bold text-ocean-deep dark:text-gray-100 sm:text-2xl">{{ __('Kitchen KDS') }}</h1>
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500" />
                            {{ __('Live') }}
                        </span>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-xs font-medium text-muted-foreground">{{ __('Show') }}:</span>
                        <button
                            v-for="station in selectableStations"
                            :key="station.id"
                            type="button"
                            data-station-chip
                            class="rounded-full px-3 py-1.5 text-xs font-semibold transition-colors"
                            :class="isStationVisible(station)
                                ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900'
                                : 'bg-white text-slate-600 ring-1 ring-border dark:bg-gray-800 dark:text-gray-300'"
                            @click="toggleStationVisibility(station.id)"
                        >
                            {{ stationChipLabel(station) }}
                        </button>
                        <button
                            v-if="!isDedicatedDisplay"
                            type="button"
                            data-open-separate-screen
                            class="inline-flex items-center justify-center rounded-full bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700"
                            @click="openStationPicker('open')"
                        >
                            {{ __('Open separate screen') }}
                        </button>
                        <button
                            v-else
                            type="button"
                            data-manage-stations
                            class="inline-flex items-center justify-center rounded-full bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700"
                            @click="openStationPicker('edit')"
                        >
                            {{ __('Add or remove stations') }}
                        </button>
                        <button
                            type="button"
                            data-fullscreen
                            class="inline-flex items-center gap-1.5 rounded-full border border-border bg-white px-3 py-1.5 text-xs font-semibold text-ocean-deep hover:bg-muted dark:bg-gray-800 dark:text-gray-100"
                            @click="toggleFullscreen"
                        >
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75v4.5m0-4.5h4.5m12 0v4.5m0-4.5h-4.5m-12 12v4.5m0-4.5h4.5m12 0v4.5m0-4.5h-4.5" />
                            </svg>
                            {{ isFullscreen ? __('Exit full screen') : __('Full screen') }}
                        </button>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-3 text-[11px] font-medium text-muted-foreground">
                    <span class="inline-flex items-center gap-1.5">
                        <span class="h-2 w-2 rounded-full bg-emerald-500" />
                        {{ __('up to 10 min') }}
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        <span class="h-2 w-2 rounded-full bg-amber-400" />
                        {{ __('10 to 20 min') }}
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        <span class="h-2 w-2 rounded-full bg-red-500" />
                        {{ __('over 20 min') }}
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        <span class="rounded bg-teal-500 px-1 py-px text-[10px] font-bold text-white">2+</span>
                        {{ __('more than 1 unit') }}
                    </span>
                    <span class="text-slate-400">·</span>
                    <span>{{ __('Oldest first') }}</span>
                </div>
            </div>
        </template>

        <div>
            <!-- Ready for delivery/pickup lane -->
            <div v-if="!isDedicatedDisplay && readyDeliveryOrders?.length" class="mb-6">
                <h3 class="mb-2 font-heading text-sm font-semibold text-ocean-deep dark:text-gray-100">
                    {{ __('Ready for delivery/pickup') }}
                </h3>
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="order in readyDeliveryOrders"
                        :key="order.id"
                        class="flex flex-col gap-2 rounded-2xl bg-white p-4 shadow-card dark:bg-gray-800"
                    >
                        <p class="font-heading text-sm font-semibold text-ocean-deep dark:text-gray-100">
                            {{ order.attendance?.customer_identifier ?? __('Guest') }}
                        </p>
                        <p class="text-xs text-muted-foreground">{{ __('Order') }} #{{ order.attendance?.delivery_order?.code ?? order.order_number }}</p>
                        <AppBadge :label="order.status" variant="primary" />
                        <AppButton size="sm" @click="advanceDeliveryStatus(order)">
                            {{ deliveryActionLabel(order) }}
                        </AppButton>
                    </div>
                </div>
            </div>

            <AppEmptyState
                v-if="allStations.length === 0"
                :title="isDedicatedDisplay ? __('This screen has no stations') : __('No kitchen stations')"
                :description="isDedicatedDisplay
                    ? __('Choose stations to show on this monitor')
                    : __('Configure kitchen stations in Settings to start using the KDS.')"
            />

            <div
                v-else
                class="grid gap-4"
                :style="`grid-template-columns: repeat(${visibleStations.length || 1}, minmax(260px, 1fr))`"
            >
                <div v-for="station in visibleStations" :key="station.id" class="flex flex-col gap-3">
                    <div class="sticky top-36 z-10 flex items-center justify-between px-1">
                        <span class="inline-flex items-center gap-1.5 font-heading text-sm font-semibold text-slate-700 dark:text-gray-100">
                            <svg v-if="isWaiterCaller(station)" class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                            </svg>
                            {{ stationColumnLabel(station) }}
                        </span>
                        <span class="inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-slate-900 px-1.5 text-[11px] font-bold text-white">
                            {{ stationCount(station) }}
                        </span>
                    </div>

                    <template v-if="isWaiterCaller(station)">
                        <div
                            v-if="!waiterCallGroups.length"
                            class="rounded-2xl border-2 border-dashed border-border p-6 text-center text-sm text-muted-foreground"
                        >
                            {{ __('No waiter calls') }}
                        </div>

                        <article
                            v-for="group in waiterCallGroups"
                            :key="group.id"
                            data-kds-card
                            class="overflow-hidden rounded-2xl bg-white shadow-card dark:bg-gray-800"
                        >
                            <div
                                class="flex items-center justify-between px-4 py-2.5 text-white"
                                :class="urgencyHeaderClass(group.createdAt)"
                            >
                                <p data-kds-table class="font-heading text-sm font-bold">{{ group.table }}</p>
                                <p class="text-sm font-semibold tabular-nums">{{ formatWait(group.createdAt) }}</p>
                            </div>
                            <div class="flex flex-col gap-3 p-4">
                                <div class="flex flex-wrap gap-2">
                                    <span
                                        v-for="request in group.requests"
                                        :key="request.id"
                                        class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700 dark:bg-gray-700 dark:text-gray-100"
                                    >
                                        {{ request.message || __('(no message)') }}
                                    </span>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <button
                                        v-if="groupHasPending(group)"
                                        type="button"
                                        class="rounded-xl border border-border px-4 py-2 text-sm font-semibold text-ocean-deep dark:text-gray-100"
                                        @click="acknowledgeGroup(group)"
                                    >
                                        {{ __('Acknowledge') }}
                                    </button>
                                    <button
                                        type="button"
                                        class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700"
                                        @click="resolveGroup(group)"
                                    >
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                        </svg>
                                        {{ __('Resolve') }}
                                    </button>
                                </div>
                            </div>
                        </article>
                    </template>

                    <div
                        v-else-if="cardsForStation(station.id).length === 0"
                        class="rounded-2xl border-2 border-dashed border-border p-6 text-center text-sm text-muted-foreground"
                    >
                        {{ __('No pending items') }}
                    </div>

                    <article
                        v-for="group in isWaiterCaller(station) ? [] : cardsForStation(station.id)"
                        :key="group.id"
                        data-kds-card
                        class="overflow-hidden rounded-2xl bg-white shadow-card dark:bg-gray-800"
                    >
                        <div
                            class="flex items-center justify-between px-4 py-2.5 text-white"
                            :class="urgencyHeaderClass(group.createdAt)"
                        >
                            <p data-kds-table class="font-heading text-sm font-bold">
                                {{ attendanceTableLabel(group.items[0]?.order?.attendance) }}
                            </p>
                            <p class="text-sm font-semibold tabular-nums">{{ formatWait(group.createdAt) }}</p>
                        </div>
                        <div class="flex flex-col gap-3 p-4">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <p
                                        v-if="attendanceTabLabel(group.items[0]?.order?.attendance)"
                                        class="text-xs font-medium text-slate-500"
                                    >
                                        {{ __('Tab :code · Order #:number', {
                                            code: attendanceTabLabel(group.items[0].order.attendance),
                                            number: group.items[0].order?.order_number,
                                        }) }}
                                    </p>
                                    <button
                                        v-else-if="attendanceNeedsTabLink(group.items[0]?.order?.attendance)"
                                        type="button"
                                        data-link-tab
                                        class="text-xs font-bold text-amber-700 underline"
                                        @click="openLinkTab(group.items[0])"
                                    >
                                        {{ __('Link tab') }}
                                    </button>
                                    <p v-else class="text-xs font-medium text-slate-500">
                                        {{ __('Order #:number', { number: group.items[0].order?.order_number }) }}
                                    </p>
                                </div>
                                <span
                                    class="shrink-0 rounded-md px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide"
                                    :class="cardStatus(group).tone === 'new'
                                        ? 'bg-slate-900 text-white'
                                        : 'bg-amber-100 text-amber-800'"
                                >
                                    {{ cardStatus(group).label }}
                                </span>
                            </div>

                            <ul class="space-y-1">
                                <li
                                    v-for="item in group.items"
                                    :key="item.id"
                                    class="text-sm font-semibold text-ocean-deep dark:text-gray-100"
                                >
                                    <span
                                        v-if="Number(item.quantity) > 1"
                                        data-qty-highlight
                                        class="mr-1.5 inline-flex items-center rounded-md bg-teal-500 px-1.5 py-0.5 text-xs font-bold text-white"
                                    >
                                        {{ item.quantity }}x
                                    </span>
                                    <span v-else class="mr-1 font-medium text-slate-500">{{ item.quantity }}x</span>
                                    {{ item.product?.name ?? __('Custom request') }}
                                    <span v-if="item.variation" class="block text-xs font-normal text-muted-foreground">
                                        {{ item.variation.name }}
                                    </span>
                                    <span v-if="item.combo" class="mt-0.5 block text-xs font-medium text-amber-700">
                                        {{ item.combo.name }}
                                    </span>
                                    <ul v-if="item.modifiers?.length" class="mt-0.5 space-y-0.5">
                                        <li
                                            v-for="modifier in item.modifiers"
                                            :key="modifier.id"
                                            class="text-xs font-normal text-muted-foreground"
                                        >
                                            + {{ modifier.modifier_option?.name }}
                                        </li>
                                    </ul>
                                    <span v-if="item.notes" class="block text-xs font-normal italic text-muted-foreground">
                                        {{ item.notes }}
                                    </span>
                                </li>
                            </ul>

                            <button
                                v-if="cardNextStatus(group)"
                                type="button"
                                class="inline-flex w-full items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-white"
                                :class="cardNextStatus(group).is_final ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-slate-900 hover:bg-slate-800'"
                                @click="advanceGroup(group)"
                            >
                                <svg v-if="cardNextStatus(group).is_final" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                                <svg v-else class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M8 5.14v13.72L19 12 8 5.14z" />
                                </svg>
                                {{ cardNextStatus(group).is_final ? cardNextStatus(group).name : __('Start prep') }}
                            </button>
                        </div>
                    </article>
                </div>
            </div>
        </div>

        <Modal :show="showStationPicker" max-width="md" @close="showStationPicker = false">
            <div data-station-picker class="p-6">
                <h2 class="font-heading text-lg font-semibold text-ocean-deep dark:text-gray-100">
                    {{ pickerMode === 'open' ? __('Open separate screen') : __('Add or remove stations') }}
                </h2>
                <p class="mt-2 text-sm text-muted-foreground dark:text-gray-400">
                    {{ __('Which stations do you want on this screen?') }}
                </p>
                <div class="mt-4 max-h-80 space-y-2 overflow-y-auto">
                    <label
                        v-for="station in selectableStations"
                        :key="station.id"
                        class="flex cursor-pointer items-center gap-3 rounded-md border border-border px-3 py-2 dark:border-gray-700"
                    >
                        <input
                            type="checkbox"
                            class="h-4 w-4 rounded border-border text-emerald-600 focus:ring-emerald-600"
                            :checked="isStationDraftSelected(station.id)"
                            @change="toggleDraftStation(station.id)"
                        />
                        <span class="text-sm font-medium text-ocean-deep dark:text-gray-100">{{ stationLabel(station) }}</span>
                    </label>
                </div>
                <div class="mt-6 flex items-center justify-end gap-3">
                    <AppButton variant="secondary" @click="showStationPicker = false">{{ __('Cancel') }}</AppButton>
                    <AppButton
                        variant="success"
                        data-confirm-stations
                        :disabled="!draftStationIds.length"
                        @click="confirmStationPicker"
                    >
                        {{ pickerMode === 'open' ? __('Open screen') : __('Save stations') }}
                    </AppButton>
                </div>
            </div>
        </Modal>

        <Modal :show="Boolean(linkingItem)" max-width="sm" @close="closeLinkTab">
            <div data-link-tab-picker class="p-6">
                <h2 class="font-heading text-lg font-semibold text-ocean-deep dark:text-gray-100">
                    {{ __('Link tab') }}
                </h2>
                <p class="mt-2 text-sm text-muted-foreground">
                    {{ attendanceTableLabel(linkingItem?.order?.attendance) }}
                </p>
                <div class="mt-4 flex flex-wrap gap-2">
                    <button
                        v-for="tab in (linkingItem ? linkTabOptions(linkingItem) : [])"
                        :key="tab.id"
                        type="button"
                        class="rounded-full border border-primary bg-primary/10 px-3 py-2 text-sm font-semibold text-primary"
                        @click="confirmLinkTab(tab.id)"
                    >
                        {{ __('Tab :code', { code: tab.label }) }}
                    </button>
                    <p
                        v-if="linkingItem && !linkTabOptions(linkingItem).length"
                        class="text-sm text-muted-foreground"
                    >
                        {{ __('No free tabs.') }}
                    </p>
                </div>
                <div class="mt-6 flex justify-end">
                    <AppButton variant="secondary" @click="closeLinkTab">{{ __('Cancel') }}</AppButton>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
