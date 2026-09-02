<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import AppCard from '@/Components/AppCard.vue';
import AppButton from '@/Components/AppButton.vue';
import AppBadge from '@/Components/AppBadge.vue';
import AppEmptyState from '@/Components/AppEmptyState.vue';
import { useForm, Link, router } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref } from 'vue';
import { useNotificationSound } from '@/Composables/useNotificationSound';

const props = defineProps({
    attendances: Array,
    serviceLocations: Array,
    channels: Array,
    venueId: String,
    serviceCallRequests: Array,
});

const showForm = ref(false);
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
    service_location_id: '',
    party_size: 1,
    notes: '',
});

const stateServiceLocation = ref([props.serviceLocations?.[0]?.type]); // Track which service location types are expanded
const serviceLocationsByType = () => {
    let types = {};
    for (const loc of props.serviceLocations) {
        if (!types[loc.type]) {
            types[loc.type] = [];
        }
        types[loc.type].push(loc);
    }
    return types;
};

const toggleServiceLocationType = (type) => {
    if (stateServiceLocation.value.includes(type)) {
        stateServiceLocation.value = stateServiceLocation.value.filter(t => t !== type);
    } else {
        stateServiceLocation.value.push(type);
    }
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
    form.service_location_id = '';
    form.party_size = 1;
    form.notes = '';
};

const submit = () => {
    form.post(route('attendances.store'), {
        onSuccess: () => {
            showForm.value = false;
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
            <div class="flex items-center justify-between">
                <h1 class="font-heading text-2xl font-bold text-ocean-deep dark:text-gray-100">{{ __('Attendances') }}</h1>
                <AppButton @click="initForm(); showForm = true">{{ __('New Attendance') }}</AppButton>
            </div>
        </template>

        <!-- New attendance form -->
        <AppCard v-if="showForm" class="mb-6">
            <h3 class="mb-3 font-heading text-sm font-semibold text-ocean-deep dark:text-gray-100">{{ __('New Attendance') }}</h3>
            <form class="grid grid-cols-1 gap-3 sm:grid-cols-2" @submit.prevent="submit">
                <div>
                    <label class="mb-1 block text-sm font-medium text-ocean-deep dark:text-gray-300">{{ __('Channel') }} <span class="text-destructive">*</span></label>
                    <template v-for="ch in channels" :key="ch.id">
                        <button
                            type="button"
                            @click="form.attendance_channel_id = ch.id"
                            :class="{'bg-primary text-white': form.attendance_channel_id === ch.id, 'bg-gray-200 text-gray-700 dark:bg-gray-600 dark:text-gray-200': form.attendance_channel_id !== ch.id}"
                            class="mr-2 mb-2 rounded-md px-3 py-2 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-primary"
                        >
                            {{ ch.name }}
                        </button>
                    </template>
                    <p v-if="form.errors.attendance_channel_id" class="mt-1 text-xs text-destructive">{{ form.errors.attendance_channel_id }}</p>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-ocean-deep dark:text-gray-300">{{ __('Identifier (table/name)') }}</label>
                    <input
                        v-model="form.customer_identifier"
                        type="text"
                        :placeholder="__('e.g. Table 5 or John')"
                        class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                    />
                    <p v-if="form.errors.customer_identifier" class="mt-1 text-xs text-destructive">{{ form.errors.customer_identifier }}</p>
                </div>

                <div v-if="serviceLocations.length">
                    <label class="mb-1 block text-sm font-medium text-ocean-deep dark:text-gray-300">{{ __('Service Location') }}</label>
                    
                    <template v-for="(locType, type) in serviceLocationsByType()" :key="type" >
                        <div class="mb-2 font-semibold text-ocean-deep dark:text-gray-300 border dark:border-gray-700 rounded-md relative">
                            <button 
                                type="button" 
                                class="mr-2 mb-2 rounded-md px-3 py-2 text-sm font-medium"
                                @click="() => toggleServiceLocationType(type)"
                                >{{ __(type) }}</button>
                            <div v-if="stateServiceLocation.includes(type)" class="p-2">
                                <template v-for="loc in locType" :key="loc.id">
                                    <button
                                        type="button"
                                        @click="form.service_location_id = loc.id"
                                        :class="{'bg-primary text-white': form.service_location_id === loc.id, 'bg-gray-200 text-gray-700 dark:bg-gray-600 dark:text-gray-200': form.service_location_id !== loc.id}"
                                        class="mr-2 mb-2 rounded-md px-3 py-2 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-primary"
                                    >
                                        {{ loc.name }}
                                    </button>
                                </template>
                            </div>
                            <div v-else
                            class="w-full h-full absolute top-0 left-0 opacity-50 cursor-pointer"
                            @click="() => toggleServiceLocationType(type)"
                            ></div>
                        </div>
                    </template>
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

        <AppEmptyState
            v-if="!attendances.length && !showForm"
            :title="__('No open attendances')"
            :description="__('Open a new attendance to start taking orders.')"
            :action-label="__('New Attendance')"
            @action="initForm(); showForm = true"
        />
        <div v-if="attendances.length && !showForm" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <AppCard v-for="attendance in attendances" :key="attendance.id">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            <AppBadge :label="attendance.attendance_channel?.name ?? ''" color="#3b82f6" />
                            <span v-if="attendance.customer_identifier" class="text-sm font-semibold text-ocean-deep dark:text-gray-100">
                                {{ __('Identifier:') }} {{ attendance.customer_identifier }}
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
