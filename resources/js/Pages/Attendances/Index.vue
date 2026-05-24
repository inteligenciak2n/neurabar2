<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import AppCard from '@/Components/AppCard.vue';
import AppButton from '@/Components/AppButton.vue';
import AppBadge from '@/Components/AppBadge.vue';
import AppConfirmModal from '@/Components/AppConfirmModal.vue';
import AppEmptyState from '@/Components/AppEmptyState.vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    attendances: Array,
    serviceLocations: Array,
});

const showForm = ref(false);
const attendanceToClose = ref(null);

const form = useForm({
    channel: 'counter',
    customer_identifier: '',
    service_location_id: '',
    party_size: '',
    notes: '',
});

const channelLabels = {
    counter: 'Counter',
    table: 'Table',
    delivery: 'Delivery',
    service_request: 'Service Request',
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

const submit = () => {
    form.post(route('attendances.store'), {
        onSuccess: () => {
            showForm.value = false;
            form.reset();
        },
    });
};

const confirmClose = (attendance) => {
    attendanceToClose.value = attendance;
};

const closeAttendance = () => {
    router.post(route('attendances.close', attendanceToClose.value.id), {}, {
        onSuccess: () => { attendanceToClose.value = null; },
    });
};
</script>

<template>
    <AppLayout title="Attendances">
        <template #header>
            <div class="flex items-center justify-between">
                <h1 class="font-heading text-2xl font-bold text-ocean-deep">Attendances</h1>
                <AppButton @click="showForm = true">New Attendance</AppButton>
            </div>
        </template>

        <!-- New attendance form -->
        <AppCard v-if="showForm" class="mb-6">
            <h3 class="mb-3 font-heading text-sm font-semibold text-ocean-deep">New Attendance</h3>
            <form class="grid grid-cols-1 gap-3 sm:grid-cols-2" @submit.prevent="submit">
                <div>
                    <label class="mb-1 block text-sm font-medium text-ocean-deep">Channel <span class="text-destructive">*</span></label>
                    <select
                        v-model="form.channel"
                        class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                    >
                        <option value="counter">Counter</option>
                        <option value="table">Table</option>
                        <option value="delivery">Delivery</option>
                        <option value="service_request">Service Request</option>
                    </select>
                    <p v-if="form.errors.channel" class="mt-1 text-xs text-destructive">{{ form.errors.channel }}</p>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-ocean-deep">Identifier (table/name)</label>
                    <input
                        v-model="form.customer_identifier"
                        type="text"
                        placeholder="e.g. Table 5 or John"
                        class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                    />
                    <p v-if="form.errors.customer_identifier" class="mt-1 text-xs text-destructive">{{ form.errors.customer_identifier }}</p>
                </div>

                <div v-if="serviceLocations.length">
                    <label class="mb-1 block text-sm font-medium text-ocean-deep">Service Location</label>
                    <select
                        v-model="form.service_location_id"
                        class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                    >
                        <option value="">None</option>
                        <option v-for="loc in serviceLocations" :key="loc.id" :value="loc.id">{{ loc.name }}</option>
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-ocean-deep">Party Size</label>
                    <input
                        v-model="form.party_size"
                        type="number"
                        min="1"
                        class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                    />
                </div>

                <div class="sm:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-ocean-deep">Notes</label>
                    <textarea
                        v-model="form.notes"
                        rows="2"
                        class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                    />
                </div>

                <div class="flex gap-2 sm:col-span-2">
                    <AppButton type="submit" :loading="form.processing">Open Attendance</AppButton>
                    <AppButton type="button" variant="ghost" @click="showForm = false; form.reset()">Cancel</AppButton>
                </div>
            </form>
        </AppCard>

        <AppEmptyState
            v-if="!attendances.length && !showForm"
            title="No open attendances"
            description="Open a new attendance to start taking orders."
            action-label="New Attendance"
            @action="showForm = true"
        />

        <div v-if="attendances.length" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <AppCard v-for="attendance in attendances" :key="attendance.id">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            <AppBadge :label="channelLabels[attendance.channel] ?? attendance.channel" color="#3b82f6" />
                            <span v-if="attendance.customer_identifier" class="text-sm font-semibold text-ocean-deep">
                                {{ attendance.customer_identifier }}
                            </span>
                        </div>
                        <p class="mt-1 text-xs text-muted-foreground">
                            Open for {{ elapsedMinutes(attendance.created_at) }} min
                        </p>
                        <p class="mt-0.5 text-xs text-muted-foreground">
                            Total: R$ {{ ordersTotal(attendance) }}
                        </p>
                    </div>
                    <AppBadge label="open" color="#22c55e" />
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                    <Link :href="route('orders.take', attendance.id)" class="inline-flex">
                        <AppButton size="sm">Take Order</AppButton>
                    </Link>
                    <Link :href="route('payment.show', attendance.id)" class="inline-flex">
                        <AppButton size="sm" variant="secondary">Payment</AppButton>
                    </Link>
                    <Link :href="route('attendances.show', attendance.id)" class="inline-flex">
                        <AppButton size="sm" variant="ghost">View</AppButton>
                    </Link>
                    <AppButton size="sm" variant="destructive" @click="confirmClose(attendance)">Close</AppButton>
                </div>
            </AppCard>
        </div>

        <AppConfirmModal
            :show="!!attendanceToClose"
            title="Close Attendance"
            message="Are you sure? Make sure payment has been registered before closing."
            confirm-label="Close"
            variant="destructive"
            @confirm="closeAttendance"
            @cancel="attendanceToClose = null"
        />
    </AppLayout>
</template>
