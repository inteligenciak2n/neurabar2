<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import AppCard from '@/Components/AppCard.vue';
import AppButton from '@/Components/AppButton.vue';
import AppBadge from '@/Components/AppBadge.vue';
import AppEmptyState from '@/Components/AppEmptyState.vue';
import NumberScrollPicker from '@/Components/NumberScrollPicker.vue';
import { useForm, Link, router } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, onUnmounted, reactive, ref } from 'vue';
import { useNotificationSound } from '@/Composables/useNotificationSound';
import { groupTabsByPrefix } from '@/Utils/tabRows';
import { attendanceMatchesLocator } from '@/Utils/attendanceLocator';

const props = defineProps({
    attendances: Array,
    serviceLocations: Array,
    channels: Array,
    tabs: Array,
    tabSeries: Array,
    venueId: String,
    serviceCallRequests: Array,
    startWithForm: {
        type: Boolean,
        default: false,
    },
});

const showForm = ref(false);
const formMode = ref('order');
const boardChannelId = ref(props.channels?.[0]?.id ?? null);
const showLocator = ref(false);
const locatorQuery = ref('');
const locatorInput = ref(null);
const locatorFieldOptions = [
    { key: 'tables', label: 'Tables' },
    { key: 'tab', label: 'Tab' },
    { key: 'order', label: 'Order' },
    { key: 'name', label: 'Name' },
    { key: 'product', label: 'Product' },
];
const locatorFields = reactive({
    tables: true,
    tab: true,
    order: true,
    name: true,
    product: true,
});
const identifierInput = ref(null);
const codeInput = ref(null);
const suggestedLocationId = ref('');
const openPicker = ref(null);
const serviceCallRequests = ref([...(props.serviceCallRequests ?? [])]);

function requestsForLocation(locationId) {
    return serviceCallRequests.value.filter((r) => r.service_location_id === locationId);
}

function acknowledgeRequest(request) {
    router.put(route('service-requests.acknowledge', request.id), {}, { preserveScroll: true });
}

function resolveRequest(request) {
    router.put(route('service-requests.resolve', request.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            serviceCallRequests.value = serviceCallRequests.value.filter((r) => r.id !== request.id);
        },
    });
}

const form = useForm({
    attendance_channel_id: '',
    customer_identifier: '',
    code: '',
    tab_id: '',
    service_location_id: '',
    party_size: 1,
    notes: '',
});

const registerTabForm = useForm({
    attendance_channel_id: '',
    tab_id: '',
    service_location_id: '',
    stay: true,
});
const registeringGroupKey = ref(null);

const selectedChannel = computed(() =>
    (props.channels ?? []).find((channel) => channel.id === form.attendance_channel_id),
);

const hexToRgba = (hex, alpha) => {
    const value = String(hex ?? '').replace('#', '');
    if (!/^[0-9A-Fa-f]{6}$/.test(value)) {
        return null;
    }

    const r = Number.parseInt(value.slice(0, 2), 16);
    const g = Number.parseInt(value.slice(2, 4), 16);
    const b = Number.parseInt(value.slice(4, 6), 16);

    return `rgba(${r}, ${g}, ${b}, ${alpha})`;
};

const channelChipStyle = (channel, selected) => {
    const hex = channel?.color;
    if (!hex) {
        return {};
    }

    if (selected) {
        return { backgroundColor: hex, color: '#fff', borderColor: hex };
    }

    return {
        color: hex,
        borderColor: hex,
        backgroundColor: hexToRgba(hex, 0.12) || undefined,
    };
};

const selectedChannelColor = computed(() => selectedChannel.value?.color || '');

const channelPanelStyle = computed(() => {
    const hex = selectedChannelColor.value;
    if (!hex) {
        return {};
    }

    return {
        borderColor: hex,
        backgroundColor: hexToRgba(hex, 0.06) || undefined,
    };
});

const needsTab = computed(() => Boolean(selectedChannel.value?.requires_tab));
const needsIdentifier = computed(() => Boolean(selectedChannel.value?.requires_customer_identifier));

const canGoToMenu = computed(() => {
    if (!form.attendance_channel_id) {
        return false;
    }

    if (needsTab.value && !form.tab_id) {
        return false;
    }

    if (needsIdentifier.value && !String(form.customer_identifier ?? '').trim()) {
        return false;
    }

    return true;
});

const formChannels = computed(() => {
    const list = props.channels ?? [];
    if (formMode.value !== 'tab') {
        return list;
    }

    const tabChannels = list.filter((channel) => channel.requires_tab);

    return tabChannels.length ? tabChannels : list;
});

const availableTabs = computed(() => props.tabs ?? []);

const availableTabGroups = computed(() => {
    const notesByPrefix = Object.fromEntries(
        (props.tabSeries ?? []).map((series) => [series.prefix ?? '', series.notes ?? '']),
    );

    return groupTabsByPrefix(availableTabs.value).map((group) => ({
        ...group,
        notes: notesByPrefix[group.prefix] ?? '',
    }));
});

const channelLocations = computed(() => {
    const channelId = form.attendance_channel_id;
    if (!channelId || !selectedChannel.value?.uses_service_location) {
        return [];
    }

    return (props.serviceLocations ?? []).filter((location) => location.default_attendance_channel_id === channelId);
});

const locationChipLabel = (location) => {
    const channelName = String(selectedChannel.value?.name ?? '').trim();
    const name = String(location?.name ?? '');

    if (channelName && name.toLowerCase().startsWith(channelName.toLowerCase())) {
        const suffix = name.slice(channelName.length).trim().replace(/^-/, '');

        return suffix || name;
    }

    return name;
};

const tabNumericSuffix = (tab) => {
    const match = String(tab?.code ?? '').match(/(\d+)$/);

    return match ? match[1] : '';
};

const locationMatchingTab = (tab) => {
    const locations = channelLocations.value;
    if (!tab || !locations.length) {
        return null;
    }

    const code = String(tab.code ?? '').toUpperCase();
    const suffix = tabNumericSuffix(tab);
    const exact = locations.find((location) => String(locationChipLabel(location)).toUpperCase() === code);
    if (exact) {
        return exact;
    }

    if (!suffix) {
        return null;
    }

    return locations.find((location) => String(locationChipLabel(location)) === suffix) ?? null;
};

const pendingLocationConfirmation = computed(() => (
    Boolean(suggestedLocationId.value)
    && form.service_location_id !== suggestedLocationId.value
));

const tabPickerItems = computed(() =>
    (availableTabs.value ?? []).map((tab) => ({
        id: tab.id,
        label: String(tab.code ?? ''),
    })),
);

const locationPickerItems = computed(() =>
    channelLocations.value.map((location) => ({
        id: location.id,
        label: locationChipLabel(location),
    })),
);

const selectChannel = (channelId) => {
    form.attendance_channel_id = channelId;
    form.tab_id = '';
    form.service_location_id = '';
    suggestedLocationId.value = '';
    openPicker.value = null;
};

const selectTab = (tab) => {
    form.tab_id = tab.id;
    form.service_location_id = '';
    suggestedLocationId.value = locationMatchingTab(tab)?.id ?? '';
};

const chooseTab = (tabId) => {
    const tab = (availableTabs.value ?? []).find((item) => item.id === tabId);
    if (tab) {
        selectTab(tab);
    }
    openPicker.value = null;
};

const chooseLocation = (locationId) => {
    form.service_location_id = locationId || '';
    openPicker.value = null;
};

const setPicker = (name, isOpen) => {
    openPicker.value = isOpen ? name : (openPicker.value === name ? null : openPicker.value);
};

const elapsedMinutes = (createdAt) => {
    const diff = Date.now() - new Date(createdAt).getTime();
    return Math.floor(diff / 60000);
};

const ordersTotal = (attendance) => {
    let total = 0;
    for (const order of attendance.orders ?? []) {
        for (const item of order.items ?? []) {
            total += Number(item.unit_price) * Number(item.quantity);
        }
    }
    return total.toFixed(2);
};

const initForm = () => {
    form.attendance_channel_id = props.channels?.[0]?.id ?? '';
    form.customer_identifier = '';
    form.code = '';
    form.tab_id = '';
    form.service_location_id = '';
    suggestedLocationId.value = '';
    openPicker.value = null;
    form.party_size = 1;
    form.notes = '';
};

const openCreate = (mode) => {
    formMode.value = mode;
    showLocator.value = false;
    initForm();
    if (mode === 'tab') {
        const tabChannel = (props.channels ?? []).find((channel) => channel.requires_tab);
        if (tabChannel) {
            form.attendance_channel_id = tabChannel.id;
        }
    }
    showForm.value = true;
    if (mode === 'tab') {
        nextTick(() => codeInput.value?.focus());
    }
};

const openLocator = () => {
    showForm.value = false;
    showLocator.value = true;
    nextTick(() => locatorInput.value?.focus());
};

const selectedLocatorFields = computed(() =>
    locatorFieldOptions
        .filter((field) => locatorFields[field.key])
        .map((field) => field.key),
);

const boardChannels = computed(() => props.channels ?? []);

const attendanceChannelId = (attendance) =>
    attendance?.attendance_channel_id ?? attendance?.attendance_channel?.id ?? '';

const channelOpenCount = (channelId) =>
    (props.attendances ?? []).filter((attendance) =>
        String(attendanceChannelId(attendance)) === String(channelId),
    ).length;

const selectBoardChannel = (channelId) => {
    boardChannelId.value = channelId;
};

const visibleAttendances = computed(() => {
    let list = props.attendances ?? [];

    if (boardChannelId.value) {
        list = list.filter((attendance) =>
            String(attendanceChannelId(attendance)) === String(boardChannelId.value),
        );
    }

    const query = locatorQuery.value.trim();
    if (!query) {
        return list;
    }

    return list.filter((attendance) =>
        attendanceMatchesLocator(attendance, query, selectedLocatorFields.value),
    );
});

const locationNumericSuffix = (location) => {
    const match = String(location?.name ?? '').match(/(\d+)\s*$/);

    return match ? Number(match[1]) : null;
};

const locationMatchingValue = (value) => {
    const needle = String(value ?? '').trim().toLowerCase();

    if (!needle) {
        return null;
    }

    const locations = props.serviceLocations ?? [];
    const exact = locations.find((location) => String(location.name ?? '').trim().toLowerCase() === needle);

    if (exact) {
        return exact;
    }

    const suffix = needle.match(/(\d+)\s*$/);

    if (!suffix) {
        return null;
    }

    const number = Number(suffix[1]);
    const inChannel = locations.filter((location) => (
        !boardChannelId.value
        || String(location.default_attendance_channel_id) === String(boardChannelId.value)
    ));

    return inChannel.find((location) => locationNumericSuffix(location) === number) ?? null;
};

const attendanceLocationId = (attendance) =>
    attendance?.service_location_id
    ?? attendance?.service_location?.id
    ?? locationMatchingValue(attendance?.customer_identifier)?.id
    ?? '';

const attendanceTableLabel = (attendance) => {
    const locationName = String(attendance?.service_location?.name ?? '').trim();

    if (locationName) {
        return locationName;
    }

    const identifier = String(attendance?.customer_identifier ?? '').trim();

    if (!identifier) {
        return '';
    }

    return locationMatchingValue(identifier)?.name ?? identifier;
};

const attendanceTabCodes = (attendance) => {
    const codes = [];

    if (attendance?.tab?.code) {
        codes.push(String(attendance.tab.code).trim());
    }

    if (attendance?.code) {
        const printed = String(attendance.code).trim();

        if (printed && !codes.includes(printed)) {
            codes.push(printed);
        }
    }

    return codes.filter(Boolean);
};

const attendanceGroupKey = (attendance) => {
    const locationId = attendanceLocationId(attendance);

    if (locationId) {
        return `loc:${locationId}`;
    }

    const tableLabel = attendanceTableLabel(attendance);

    if (tableLabel) {
        return `label:${tableLabel.toLowerCase()}`;
    }

    return `att:${attendance.id}`;
};

const compareTabCodes = (a, b) => a.localeCompare(b, undefined, { numeric: true, sensitivity: 'base' });

const visibleAttendanceGroups = computed(() => {
    const groups = [];
    const indexByKey = {};

    (visibleAttendances.value ?? []).forEach((attendance) => {
        const key = attendanceGroupKey(attendance);
        let group = indexByKey[key] !== undefined ? groups[indexByKey[key]] : null;

        if (!group) {
            group = {
                key,
                tableLabel: attendanceTableLabel(attendance),
                locationId: attendanceLocationId(attendance),
                attendances: [],
            };
            indexByKey[key] = groups.length;
            groups.push(group);
        }

        group.attendances.push(attendance);
    });

    return groups.map((group) => {
        const tabCodes = [...new Set(group.attendances.flatMap(attendanceTabCodes))]
            .sort(compareTabCodes);
        const primaryAttendance = group.attendances[0];
        const oldest = group.attendances.reduce((current, attendance) => (
            new Date(attendance.created_at) < new Date(current.created_at) ? attendance : current
        ), primaryAttendance);
        const total = group.attendances.reduce((sum, attendance) => sum + Number(ordersTotal(attendance)), 0);

        const unlinkedAttendances = group.attendances.filter((attendance) => (
            attendanceNeedsTabLink(attendance) && attendanceHasItems(attendance)
        ));

        return {
            ...group,
            tabCodes,
            primaryAttendance,
            openedAt: oldest?.created_at,
            createdBy: oldest?.created_by,
            total: total.toFixed(2),
            unlinkedAttendances,
            hasUnlinkedItems: unlinkedAttendances.length > 0,
        };
    });
});

const attendanceNeedsTabLink = (attendance) =>
    !attendance?.tab_id
    && !attendance?.tab?.id
    && !String(attendance?.code ?? '').trim();

const attendanceHasItems = (attendance) =>
    (attendance.orders ?? []).some((order) => (order.items ?? []).length > 0);

const registerTabPickerName = (group) => `register-tab:${group.key}`;
const linkTabPickerName = (group) => `link-tab:${group.key}`;
const linkingGroupKey = ref(null);

const canRegisterTab = (group) =>
    Boolean(group?.locationId)
    && Boolean(group?.primaryAttendance?.attendance_channel?.requires_tab);

const canLinkTab = (group) => Boolean(group?.locationId) && Boolean(group?.hasUnlinkedItems);

const linkTabPickerItems = (group) => {
    const seen = new Set();
    const items = [];

    (group?.attendances ?? []).forEach((attendance) => {
        const tabId = attendance?.tab?.id ?? attendance?.tab_id;
        const code = String(attendance?.tab?.code ?? attendance?.code ?? '').trim();

        if (!tabId || seen.has(tabId)) {
            return;
        }

        seen.add(tabId);
        items.push({ id: tabId, label: code });
    });

    (tabPickerItems.value ?? []).forEach((tab) => {
        if (seen.has(tab.id)) {
            return;
        }

        seen.add(tab.id);
        items.push(tab);
    });

    return items;
};

const openRegisterTab = (group) => {
    const name = registerTabPickerName(group);
    openPicker.value = openPicker.value === name ? null : name;
};

const openLinkTab = (group) => {
    const name = linkTabPickerName(group);
    openPicker.value = openPicker.value === name ? null : name;
};

const linkTabOnGroup = (group, tabId) => {
    const unlinked = group?.unlinkedAttendances?.[0];

    if (!tabId || !unlinked?.id) {
        return;
    }

    openPicker.value = null;
    linkingGroupKey.value = group.key;
    router.put(route('attendances.link-tab', unlinked.id), { tab_id: tabId }, {
        preserveScroll: true,
        onFinish: () => {
            linkingGroupKey.value = null;
        },
    });
};

const registerTabOnGroup = (group, tabId) => {
    if (!tabId || !group?.locationId) {
        return;
    }

    openPicker.value = null;
    registeringGroupKey.value = group.key;
    registerTabForm.attendance_channel_id =
        group.primaryAttendance?.attendance_channel_id
        ?? group.primaryAttendance?.attendance_channel?.id
        ?? '';
    registerTabForm.tab_id = tabId;
    registerTabForm.service_location_id = group.locationId;
    registerTabForm.stay = true;
    registerTabForm.post(route('attendances.store'), {
        preserveScroll: true,
        onFinish: () => {
            registeringGroupKey.value = null;
        },
    });
};

const submit = () => {
    if (formMode.value === 'table' && !form.customer_identifier && form.service_location_id) {
        const location = (props.serviceLocations ?? []).find((loc) => loc.id === form.service_location_id);
        if (location) {
            form.customer_identifier = location.name;
        }
    }

    form.post(route('attendances.store'), {
        onSuccess: () => {
            showForm.value = false;
            formMode.value = 'order';
            initForm();
        },
    });
};

let kitchenChannel = null;
let serviceRequestsChannel = null;

const { playSound } = useNotificationSound();

const closeForm = () => {
    if (props.startWithForm) {
        router.visit(route('attendances.index'));
        return;
    }

    showForm.value = false;
    initForm();
};

onMounted(() => {
    if (props.startWithForm) {
        openCreate('order');
    }

    if (!props.venueId) return;

    kitchenChannel = window.Echo.private(`venue.${props.venueId}.kitchen`)
        .listen('.OrderPlaced', () => {
            router.reload({ only: ['attendances'] });
        });

    serviceRequestsChannel = window.Echo.private(`venue.${props.venueId}.service-requests`)
        .listen('.ServiceRequestCreated', (event) => {
            if (event.type === 'message') return;
            serviceCallRequests.value.push({
                id: event.id,
                service_location_id: event.service_location_id,
                type: event.type,
                status: event.status,
                created_at: event.created_at,
            });
            playSound();
        })
        .listen('.ServiceRequestUpdated', (event) => {
            if (event.status === 'resolved') {
                serviceCallRequests.value = serviceCallRequests.value.filter((r) => r.id !== event.id);
            } else {
                const request = serviceCallRequests.value.find((r) => r.id === event.id);
                if (request) request.status = event.status;
            }
        });
});

onUnmounted(() => {
    if (props.venueId && kitchenChannel) {
        window.Echo.leaveChannel(`venue.${props.venueId}.kitchen`);
    }
    if (props.venueId && serviceRequestsChannel) {
        window.Echo.leaveChannel(`venue.${props.venueId}.service-requests`);
    }
});
</script>

<template>
    <AppLayout :title="startWithForm ? __('New Attendance') : __('Open Attendances')">
        <template #header>
            <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                <div class="flex min-w-0 flex-col gap-2 sm:flex-row sm:items-center sm:gap-4">
                    <h1 class="shrink-0 font-heading text-xl font-bold text-ocean-deep sm:text-2xl dark:text-gray-100">{{ startWithForm ? __('New Attendance') : __('Open Attendances') }}</h1>
                    <div
                        v-if="!startWithForm && boardChannels.length"
                        data-open-channel-tabs
                        class="flex gap-1 overflow-x-auto pb-0.5"
                        role="tablist"
                        :aria-label="__('Channel')"
                    >
                        <button
                            v-for="channel in boardChannels"
                            :key="channel.id"
                            type="button"
                            role="tab"
                            data-open-channel-tab
                            :data-open-count="channelOpenCount(channel.id)"
                            :aria-selected="boardChannelId === channel.id"
                            class="min-h-10 shrink-0 touch-manipulation rounded-lg border px-3 py-1.5 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-primary"
                            :class="{
                                'bg-primary text-white': boardChannelId === channel.id && !channel.color,
                                'bg-gray-200 text-gray-700 dark:bg-gray-600 dark:text-gray-200': boardChannelId !== channel.id && !channel.color,
                            }"
                            :style="channelChipStyle(channel, boardChannelId === channel.id)"
                            @click="selectBoardChannel(channel.id)"
                        >
                            {{ channel.name }} ({{ channelOpenCount(channel.id) }})
                        </button>
                    </div>
                </div>
                <div class="flex items-center gap-2 overflow-x-auto pb-0.5">
                    <AppButton size="sm" class="shrink-0" variant="ghost" @click="openLocator">{{ __('Locate orders') }}</AppButton>
                </div>
            </div>
        </template>

        <!-- New attendance form -->
        <AppCard v-if="showForm" class="mx-auto mb-6 w-full max-w-md">
            <h3 class="mb-4 font-heading text-base font-bold text-ocean-deep dark:text-gray-100">
                {{ formMode === 'table' ? __('New Table') : formMode === 'tab' ? __('New Tab') : __('New Order') }}
            </h3>
            <form class="flex flex-col" @submit.prevent="submit">
                <div
                    class="flex gap-1 overflow-x-auto pb-px"
                    role="tablist"
                    :aria-label="__('Channel')"
                >
                    <button
                        v-for="ch in formChannels"
                        :key="ch.id"
                        type="button"
                        role="tab"
                        :aria-selected="form.attendance_channel_id === ch.id"
                        :class="{'bg-primary text-white': form.attendance_channel_id === ch.id && !ch.color, 'bg-gray-200 text-gray-700 dark:bg-gray-600 dark:text-gray-200': form.attendance_channel_id !== ch.id && !ch.color}"
                        class="min-h-12 min-w-[4.5rem] flex-1 touch-manipulation rounded-t-lg border px-3 py-2.5 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-primary"
                        :style="channelChipStyle(ch, form.attendance_channel_id === ch.id)"
                        @click="selectChannel(ch.id)"
                    >
                        {{ ch.name }}
                    </button>
                </div>
                <p v-if="form.errors.attendance_channel_id" class="mt-1 text-xs text-destructive">{{ form.errors.attendance_channel_id }}</p>

                <div
                    class="max-h-[55vh] space-y-4 overflow-y-auto overscroll-contain rounded-b-xl border-2 p-3"
                    :style="channelPanelStyle"
                >
                <div v-if="needsTab">
                    <label class="mb-2 block text-sm font-medium text-ocean-deep dark:text-gray-300">{{ __('Tab') }} <span class="text-destructive">*</span></label>
                    <NumberScrollPicker
                        :open="openPicker === 'tab'"
                        :items="tabPickerItems"
                        :model-value="form.tab_id"
                        :accent-color="selectedChannelColor"
                        :placeholder="__('Choose a tab number')"
                        :empty-text="__('No free tabs.')"
                        @update:open="(isOpen) => setPicker('tab', isOpen)"
                        @update:model-value="chooseTab"
                    />
                    <p v-if="form.errors.tab_id" class="mt-1 text-xs text-destructive">{{ form.errors.tab_id }}</p>
                </div>

                <div v-if="channelLocations.length">
                    <label class="mb-2 block text-sm font-medium text-ocean-deep dark:text-gray-300">{{ __('Service Location') }}</label>
                    <p v-if="pendingLocationConfirmation" class="mb-2 text-xs text-muted-foreground dark:text-gray-400">
                        {{ __('Tap the table number to confirm. The order is not locked.') }}
                    </p>
                    <NumberScrollPicker
                        :open="openPicker === 'location'"
                        :items="locationPickerItems"
                        :model-value="form.service_location_id"
                        :highlight-id="suggestedLocationId"
                        :accent-color="selectedChannelColor"
                        :placeholder="__('Choose a table number')"
                        @update:open="(isOpen) => setPicker('location', isOpen)"
                        @update:model-value="chooseLocation"
                    />
                </div>

                <div v-if="needsIdentifier || formMode === 'tab'">
                    <label class="mb-1 block text-sm font-medium text-ocean-deep dark:text-gray-300">{{ formMode === 'tab' ? __('Customer name') : __('Identifier (table/name)') }} <span v-if="needsIdentifier" class="text-destructive">*</span></label>
                    <input
                        ref="identifierInput"
                        v-model="form.customer_identifier"
                        type="text"
                        :placeholder="formMode === 'tab' ? __('e.g. John') : __('e.g. Table 5 or John')"
                        class="w-full rounded-md border border-border px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                    />
                    <p v-if="form.errors.customer_identifier" class="mt-1 text-xs text-destructive">{{ form.errors.customer_identifier }}</p>
                </div>

                <details class="rounded-lg border border-border dark:border-gray-600">
                    <summary class="cursor-pointer px-3 py-2 text-sm font-medium text-ocean-deep dark:text-gray-100">{{ __('More options') }}</summary>
                    <div class="space-y-3 border-t border-border px-3 py-3 dark:border-gray-600">
                        <div v-if="!needsIdentifier && formMode !== 'tab'">
                            <label class="mb-1 block text-sm font-medium text-ocean-deep dark:text-gray-300">{{ __('Identifier (table/name)') }}</label>
                            <input
                                ref="identifierInput"
                                v-model="form.customer_identifier"
                                type="text"
                                :placeholder="__('e.g. Table 5 or John')"
                                class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                            />
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-ocean-deep dark:text-gray-300">{{ __('Tab card code') }}</label>
                            <input
                                ref="codeInput"
                                v-model="form.code"
                                type="text"
                                maxlength="32"
                                autocomplete="off"
                                :placeholder="__('Code printed on the tab card')"
                                class="w-full rounded-md border border-border px-3 py-2 text-sm uppercase focus:outline-none focus:ring-2 focus:ring-primary dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                            />
                            <p v-if="form.errors.code" class="mt-1 text-xs text-destructive">{{ __(form.errors.code) }}</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-ocean-deep dark:text-gray-300">{{ __('Party Size') }}</label>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="s in [1,2,3,4,5,6,7,8,9,10]"
                                    :key="s"
                                    type="button"
                                    :class="{'bg-primary text-white': form.party_size === s, 'bg-gray-200 text-gray-700 dark:bg-gray-600 dark:text-gray-200': form.party_size !== s}"
                                    class="min-h-10 min-w-10 rounded-md px-2 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-primary"
                                    @click="form.party_size = s"
                                >
                                    {{ s }}
                                </button>
                            </div>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-ocean-deep dark:text-gray-300">{{ __('Notes') }}</label>
                            <textarea
                                v-model="form.notes"
                                rows="2"
                                :placeholder="__('Enter any additional notes')"
                                class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                            />
                        </div>
                    </div>
                </details>
                </div>

                <div class="flex flex-col gap-2 pt-3">
                    <AppButton type="submit" class="w-full min-h-12" :disabled="!canGoToMenu" :loading="form.processing">{{ __('Go to the menu') }}</AppButton>
                    <AppButton type="button" variant="ghost" class="w-full" @click="closeForm">{{ __('Cancel') }}</AppButton>
                </div>
            </form>
        </AppCard>

        <div v-if="showLocator && !showForm" class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start">
            <div class="w-full max-w-lg">
                <label class="mb-1 block font-heading text-xl font-bold text-ocean-deep dark:text-gray-100">{{ __('Locate orders') }}</label>
                <input
                    ref="locatorInput"
                    v-model="locatorQuery"
                    type="search"
                    :placeholder="__('Search by table, name or channel')"
                    class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                />
            </div>
            <div>
                <p class="mb-1 text-sm font-medium text-ocean-deep dark:text-gray-300">{{ __('Items to search') }}</p>
                <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                    <label
                        v-for="field in locatorFieldOptions"
                        :key="field.key"
                        class="inline-flex cursor-pointer items-center gap-2"
                    >
                        <input
                            v-model="locatorFields[field.key]"
                            type="checkbox"
                            class="h-4 w-4 rounded border-border text-primary focus:ring-primary dark:border-gray-600"
                        />
                        <span class="text-sm text-ocean-deep dark:text-gray-300">{{ __(field.label) }}</span>
                    </label>
                </div>
            </div>
        </div>

        <AppEmptyState
            v-if="!visibleAttendanceGroups.length && !showForm"
            :title="locatorQuery ? __('No matching orders') : __('No open attendances')"
            :description="locatorQuery ? __('Try another table, name or channel.') : __('Open a new attendance to start taking orders.')"
            :action-label="locatorQuery ? null : __('New Order')"
            @action="router.visit(route('attendances.create'))"
        />
        <div v-if="visibleAttendanceGroups.length && !showForm" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <AppCard v-for="group in visibleAttendanceGroups" :key="group.key">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                            <span
                                v-if="group.tableLabel"
                                data-board-table
                                class="text-sm font-semibold text-ocean-deep dark:text-gray-100"
                            >
                                {{ group.tableLabel }}
                            </span>
                            <span
                                v-if="group.tabCodes.length"
                                data-board-tabs
                                class="text-sm font-semibold text-ocean-deep dark:text-gray-100"
                            >
                                {{ group.tabCodes.length > 1 ? __('Tabs:') : __('Tab:') }} {{ group.tabCodes.join(', ') }}
                            </span>
                        </div>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{ __('Open for') }} {{ elapsedMinutes(group.openedAt) }} {{ __('min') }}
                        </p>
                        <p class="mt-0.5 text-xs text-muted-foreground">
                            {{ __('Total:') }} R$ {{ group.total }}
                        </p>
                        <p
                            v-if="group.hasUnlinkedItems"
                            data-link-tab-warning
                            class="mt-2 rounded-md bg-amber-50 px-2 py-1 text-xs font-semibold text-amber-800"
                        >
                            {{ __('Link tab') }}
                        </p>
                        <p
                            v-if="group.primaryAttendance?.delivery_address"
                            data-delivery-address-line
                            class="mt-2 text-xs leading-snug text-ocean-deep"
                        >
                            <span>{{ group.primaryAttendance.delivery_address.street }}{{ group.primaryAttendance.delivery_address.number ? `, ${group.primaryAttendance.delivery_address.number}` : '' }}{{ group.primaryAttendance.delivery_address.complement ? `, ${group.primaryAttendance.delivery_address.complement}` : '' }}</span>
                            <span v-if="group.primaryAttendance.delivery_address.neighborhood" class="font-bold"> – {{ group.primaryAttendance.delivery_address.neighborhood }}</span>
                            <span v-if="group.primaryAttendance.delivery_address.reference_point" class="font-bold"> – (ref: {{ group.primaryAttendance.delivery_address.reference_point }})</span>
                            <span v-if="group.primaryAttendance.delivery_address.delivery_phone"> – Tel: {{ group.primaryAttendance.delivery_address.delivery_phone }}</span>
                        </p>
                        <a
                            v-if="group.primaryAttendance?.delivery_address?.maps_url"
                            :href="group.primaryAttendance.delivery_address.maps_url"
                            target="_blank"
                            rel="noopener noreferrer"
                            data-open-in-maps
                            class="mt-1 inline-block text-[11px] font-semibold text-primary underline"
                        >
                            {{ __('Open in maps') }}
                        </a>

                        <div v-if="requestsForLocation(group.locationId).length" class="mt-2 space-y-1">
                            <div
                                v-for="request in requestsForLocation(group.locationId)"
                                :key="request.id"
                                class="flex items-center gap-2 rounded-lg border border-amber-300 bg-amber-50 px-2 py-1 text-xs text-amber-800"
                            >
                                <span>{{ request.type === 'checkout' ? __('Requesting the bill') : __('Wants to place an order') }}</span>
                                <button v-if="request.status === 'pending'" class="underline" @click="acknowledgeRequest(request)">{{ __('Acknowledge') }}</button>
                                <button class="underline" @click="resolveRequest(request)">{{ __('Resolve') }}</button>
                            </div>
                        </div>
                    </div>
                    <div class="flex shrink-0 flex-col items-end gap-1">
                        <AppBadge :label="__('Open')" color="#22c55e" />
                        <p
                            v-if="group.createdBy?.name"
                            data-initiated-by
                            class="max-w-[8.5rem] text-right text-[10px] leading-tight text-muted-foreground"
                        >
                            {{ __('Initiated by:') }} {{ group.createdBy.name }}
                        </p>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                    <Link :href="route('orders.take', group.primaryAttendance.id)" class="inline-flex">
                        <AppButton size="sm">{{ __('New Order') }}</AppButton>
                    </Link>
                    <AppButton
                        v-if="canRegisterTab(group)"
                        type="button"
                        size="sm"
                        variant="ghost"
                        data-register-tab
                        :loading="registerTabForm.processing && registeringGroupKey === group.key"
                        @click="openRegisterTab(group)"
                    >
                        {{ __('Register tab') }}
                    </AppButton>
                    <AppButton
                        v-if="canLinkTab(group)"
                        type="button"
                        size="sm"
                        variant="accent"
                        data-link-tab
                        :loading="linkingGroupKey === group.key"
                        @click="openLinkTab(group)"
                    >
                        {{ __('Link tab') }}
                    </AppButton>
                    <AppButton
                        size="sm"
                        variant="secondary"
                        :href="group.hasUnlinkedItems ? null : route('payment.show', group.primaryAttendance.id)"
                        :disabled="group.hasUnlinkedItems"
                    >
                        {{ __('Payment') }}
                    </AppButton>
                    <Link :href="route('attendances.show', group.primaryAttendance.id)" class="inline-flex">
                        <AppButton size="sm" variant="ghost">{{ __('View') }}</AppButton>
                    </Link>
                </div>
                <div
                    v-if="canRegisterTab(group) && openPicker === registerTabPickerName(group)"
                    data-register-tab-picker
                    class="mt-3"
                >
                    <NumberScrollPicker
                        :open="true"
                        hide-trigger
                        :items="tabPickerItems"
                        :placeholder="__('Choose a tab number')"
                        :empty-text="__('No free tabs.')"
                        @update:open="(isOpen) => { if (!isOpen) openPicker = null; }"
                        @update:model-value="(tabId) => registerTabOnGroup(group, tabId)"
                    />
                    <p v-if="registerTabForm.errors.tab_id" class="mt-1 text-xs text-destructive">{{ __(registerTabForm.errors.tab_id) }}</p>
                </div>
                <div
                    v-if="canLinkTab(group) && openPicker === linkTabPickerName(group)"
                    data-link-tab-picker
                    class="mt-3"
                >
                    <NumberScrollPicker
                        :open="true"
                        hide-trigger
                        :items="linkTabPickerItems(group)"
                        :placeholder="__('Choose a tab number')"
                        :empty-text="__('No free tabs.')"
                        @update:open="(isOpen) => { if (!isOpen) openPicker = null; }"
                        @update:model-value="(tabId) => linkTabOnGroup(group, tabId)"
                    />
                </div>
            </AppCard>
        </div>
    </AppLayout>
</template>
