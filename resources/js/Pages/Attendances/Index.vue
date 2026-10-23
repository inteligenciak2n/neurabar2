<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import AppCard from '@/Components/AppCard.vue';
import AppButton from '@/Components/AppButton.vue';
import AppBadge from '@/Components/AppBadge.vue';
import AppEmptyState from '@/Components/AppEmptyState.vue';
import { useForm, Link, router } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, onUnmounted, reactive, ref } from 'vue';
import { useNotificationSound } from '@/Composables/useNotificationSound';
import { formatPrefixLabel, groupTabsByPrefix } from '@/Utils/tabRows';
import { attendanceMatchesLocator } from '@/Utils/attendanceLocator';

const props = defineProps({
    attendances: Array,
    serviceLocations: Array,
    channels: Array,
    tabs: Array,
    tabSeries: Array,
    venueId: String,
    serviceCallRequests: Array,
});

const showForm = ref(false);
const formMode = ref('order');
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

function updateServiceLocation(attendance, serviceLocationId) {
    router.put(route('attendances.update', attendance.id), { service_location_id: serviceLocationId || null }, {
        preserveScroll: true,
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

const selectedChannel = computed(() =>
    (props.channels ?? []).find((channel) => channel.id === form.attendance_channel_id),
);

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

const selectChannel = (channelId) => {
    form.attendance_channel_id = channelId;
    form.tab_id = '';
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

const visibleAttendances = computed(() => {
    const list = props.attendances ?? [];
    const query = locatorQuery.value.trim();
    if (!query) {
        return list;
    }

    return list.filter((attendance) =>
        attendanceMatchesLocator(attendance, query, selectedLocatorFields.value),
    );
});

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

onMounted(() => {
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
    <AppLayout :title="__('Attendances')">
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h1 class="font-heading text-2xl font-bold text-ocean-deep dark:text-gray-100">{{ __('Attendances') }}</h1>
                <div class="flex flex-wrap items-center gap-2">
                    <AppButton size="sm" @click="openCreate('order')">{{ __('New Order') }}</AppButton>
                    <AppButton size="sm" variant="secondary" @click="openCreate('table')">{{ __('New Table') }}</AppButton>
                    <AppButton size="sm" variant="secondary" @click="openCreate('tab')">{{ __('New Tab') }}</AppButton>
                    <AppButton size="sm" variant="ghost" @click="openLocator">{{ __('Locate orders') }}</AppButton>
                </div>
            </div>
        </template>

        <!-- New attendance form -->
        <AppCard v-if="showForm" class="mb-6">
            <h3 class="mb-3 font-heading text-sm font-semibold text-ocean-deep dark:text-gray-100">
                {{ formMode === 'table' ? __('New Table') : formMode === 'tab' ? __('New Tab') : __('New Order') }}
            </h3>
            <form class="grid grid-cols-1 gap-3 sm:grid-cols-2" @submit.prevent="submit">
                <div>
                    <label class="mb-1 block text-sm font-medium text-ocean-deep dark:text-gray-300">{{ __('Channel') }} <span class="text-destructive">*</span></label>
                    <template v-for="ch in formChannels" :key="ch.id">
                        <button
                            type="button"
                            @click="selectChannel(ch.id)"
                            :class="{'bg-primary text-white': form.attendance_channel_id === ch.id, 'bg-gray-200 text-gray-700 dark:bg-gray-600 dark:text-gray-200': form.attendance_channel_id !== ch.id}"
                            class="mr-2 mb-2 rounded-md px-3 py-2 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-primary"
                        >
                            {{ ch.name }}
                        </button>
                    </template>
                    <p v-if="form.errors.attendance_channel_id" class="mt-1 text-xs text-destructive">{{ form.errors.attendance_channel_id }}</p>
                </div>

                <div v-if="selectedChannel?.requires_tab">
                    <label class="mb-1 block text-sm font-medium text-ocean-deep dark:text-gray-300">{{ __('Tab') }} <span class="text-destructive">*</span></label>
                    <p v-if="!availableTabs.length" class="mb-2 text-xs text-muted-foreground">{{ __('No free tabs.') }}</p>
                    <div v-else class="space-y-3">
                        <div
                            v-for="group in availableTabGroups"
                            :key="group.prefix || 'numbers'"
                        >
                            <p class="text-xs font-medium text-ocean-deep dark:text-gray-200">
                                {{ group.prefix ? `${__('Prefix')} ${formatPrefixLabel(group.prefix)}` : __('Numbers') }}
                            </p>
                            <p v-if="group.notes" class="mb-1 text-xs text-muted-foreground dark:text-gray-400">{{ group.notes }}</p>
                            <div class="mt-1 space-y-2">
                                <div
                                    v-for="(row, rowIndex) in group.rows"
                                    :key="rowIndex"
                                    class="grid grid-cols-5 gap-2 sm:grid-cols-10"
                                >
                                    <button
                                        v-for="tab in row"
                                        :key="tab.id"
                                        type="button"
                                        @click="form.tab_id = tab.id"
                                        :class="{'bg-primary text-white': form.tab_id === tab.id, 'bg-gray-200 text-gray-700 dark:bg-gray-600 dark:text-gray-200': form.tab_id !== tab.id}"
                                        class="rounded-md px-2 py-2 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-primary"
                                    >
                                        {{ tab.code }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <p v-if="form.errors.tab_id" class="mt-1 text-xs text-destructive">{{ form.errors.tab_id }}</p>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-ocean-deep dark:text-gray-300">{{ formMode === 'tab' ? __('Customer name') : __('Identifier (table/name)') }}</label>
                    <input
                        ref="identifierInput"
                        v-model="form.customer_identifier"
                        type="text"
                        :placeholder="formMode === 'tab' ? __('e.g. John') : __('e.g. Table 5 or John')"
                        class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                    />
                    <p v-if="form.errors.customer_identifier" class="mt-1 text-xs text-destructive">{{ form.errors.customer_identifier }}</p>
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

                <div v-if="serviceLocations?.length">
                    <label class="mb-1 block text-sm font-medium text-ocean-deep dark:text-gray-300">{{ __('Service Location') }}</label>

                    <button
                        v-for="loc in serviceLocations"
                        :key="loc.id"
                        type="button"
                        :class="{'bg-primary text-white': form.service_location_id === loc.id, 'bg-gray-200 text-gray-700 dark:bg-gray-600 dark:text-gray-200': form.service_location_id !== loc.id}"
                        class="mr-2 mb-2 rounded-md px-3 py-2 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-primary"
                        @click="form.service_location_id = loc.id"
                    >
                        {{ loc.name }}
                    </button>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-ocean-deep dark:text-gray-300">{{ __('Party Size') }}</label>

                    <template v-for="s in [1,2,3,4,5,6,7,8,9,10]" :key="s">
                        <button
                            type="button"
                            @click="form.party_size = s"
                            :class="{'bg-primary text-white': form.party_size === s, 'bg-gray-200 text-gray-700 dark:bg-gray-600 dark:text-gray-200': form.party_size !== s}"
                            class="mr-2 mb-2 rounded-md px-3 py-2 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-primary"
                        >
                            {{ s }}
                        </button>

                    </template>

                    <input
                        v-model="form.party_size"
                        type="number"
                        min="1"
                        class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                    />
                </div>

                <div class="sm:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-ocean-deep dark:text-gray-300">{{ __('Notes') }}</label>
                    <textarea
                        v-model="form.notes"
                        rows="2"
                        :placeholder="__('Enter any additional notes')"
                        class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                    />
                </div>

                <div class="flex gap-2 sm:col-span-2">
                    <AppButton type="submit" :loading="form.processing">{{ __('Open Attendance') }}</AppButton>
                    <AppButton type="button" variant="ghost" @click="showForm = false; initForm()">{{ __('Cancel') }}</AppButton>
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
            v-if="!visibleAttendances.length && !showForm"
            :title="locatorQuery ? __('No matching orders') : __('No open attendances')"
            :description="locatorQuery ? __('Try another table, name or channel.') : __('Open a new attendance to start taking orders.')"
            :action-label="locatorQuery ? null : __('New Order')"
            @action="openCreate('order')"
        />
        <div v-if="visibleAttendances.length && !showForm" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <AppCard v-for="attendance in visibleAttendances" :key="attendance.id">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            <AppBadge :label="attendance.attendance_channel?.name ?? ''" color="#3b82f6" />
                            <span v-if="attendance.tab" class="text-sm font-semibold text-ocean-deep dark:text-gray-100">
                                {{ __('Tab:') }} {{ attendance.tab.code }}
                            </span>
                            <span v-if="attendance.customer_identifier" class="text-sm font-semibold text-ocean-deep dark:text-gray-100">
                                {{ __('Identifier:') }} {{ attendance.customer_identifier }}
                            </span>
                            <span v-if="attendance.code" class="rounded-md bg-warm-gold/30 px-2 py-0.5 text-xs font-bold tracking-wide text-ocean-deep dark:text-gray-100">
                                {{ __('Tab') }} #{{ attendance.code }}
                            </span>
                            <span v-if="attendance.service_location" class="text-sm text-ocean-deep dark:text-gray-300">
                                {{ attendance.service_location.name }}
                            </span>
                        </div>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{ __('Open for') }} {{ elapsedMinutes(attendance.created_at) }} {{ __('min') }}
                        </p>
                        <p v-if="attendance?.created_by?.name" class="mt-0.5 text-xs text-muted-foreground">
                            {{ __('Initiated by:') }} {{ attendance.created_by.name }}
                        </p>
                        <p class="mt-0.5 text-xs text-muted-foreground">
                            {{ __('Total:') }} R$ {{ ordersTotal(attendance) }}
                        </p>

                        <div class="mt-2">
                            <label class="mr-2 text-xs text-muted-foreground">{{ __('Service Location') }}</label>
                            <select
                                class="rounded-md border border-border px-2 py-1 text-xs dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                :value="attendance.service_location?.id ?? ''"
                                @change="updateServiceLocation(attendance, $event.target.value)"
                            >
                                <option value="">{{ __('None') }}</option>
                                <option v-for="loc in serviceLocations" :key="loc.id" :value="loc.id">{{ loc.name }}</option>
                            </select>
                        </div>

                        <div v-if="requestsForLocation(attendance.service_location?.id).length" class="mt-2 space-y-1">
                            <div
                                v-for="request in requestsForLocation(attendance.service_location?.id)"
                                :key="request.id"
                                class="flex items-center gap-2 rounded-lg border border-amber-300 bg-amber-50 px-2 py-1 text-xs text-amber-800"
                            >
                                <span>{{ request.type === 'checkout' ? __('Requesting the bill') : __('Wants to place an order') }}</span>
                                <button v-if="request.status === 'pending'" class="underline" @click="acknowledgeRequest(request)">{{ __('Acknowledge') }}</button>
                                <button class="underline" @click="resolveRequest(request)">{{ __('Resolve') }}</button>
                            </div>
                        </div>
                    </div>
                    <AppBadge :label="__('Open')" color="#22c55e" />
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                    <Link :href="route('orders.take', attendance.id)" class="inline-flex">
                        <AppButton size="sm">{{ __('Take Order') }}</AppButton>
                    </Link>
                    <Link :href="route('payment.show', attendance.id)" class="inline-flex">
                        <AppButton size="sm" variant="secondary">{{ __('Payment') }}</AppButton>
                    </Link>
                    <Link :href="route('attendances.show', attendance.id)" class="inline-flex">
                        <AppButton size="sm" variant="ghost">{{ __('View') }}</AppButton>
                    </Link>
                </div>
            </AppCard>
        </div>
    </AppLayout>
</template>
