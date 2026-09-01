<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import AppBadge from '@/Components/AppBadge.vue';
import AppButton from '@/Components/AppButton.vue';
import AppCard from '@/Components/AppCard.vue';
import AppEmptyState from '@/Components/AppEmptyState.vue';
import { router, usePage } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref, computed } from 'vue';

const props = defineProps({
    requests: Array,
});

const page = usePage();
const venueId = computed(() => page.props.defs?.venue?.id);
const currentUserId = computed(() => page.props.auth?.user?.id);

const requests = ref([...(props.requests ?? [])]);

const statusVariant = {
    pending: 'destructive',
    acknowledged: 'accent',
    resolved: 'muted',
};

function elapsed(createdAt) {
    const mins = Math.floor((Date.now() - new Date(createdAt).getTime()) / 60000);
    return mins < 1 ? __('just now') : __(':mins min ago', { mins });
}

function acknowledge(request) {
    router.put(route('service-requests.acknowledge', request.id), {}, { preserveScroll: true });
}

function resolve(request) {
    router.put(route('service-requests.resolve', request.id), {}, { preserveScroll: true });
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

let channel = null;

onMounted(() => {
    if (!venueId.value) return;

    channel = window.Echo.private(`venue.${venueId.value}.service-requests`)
        .listen('.ServiceRequestCreated', (event) => {
            if (event.type !== 'message') return;
            requests.value.unshift({
                id: event.id,
                type: event.type,
                message: event.message,
                status: event.status,
                created_at: event.created_at,
                assigned_user_id: event.assigned_user_id,
                service_location: event.location_name ? { name: event.location_name } : null,
                assigned_user: null,
                acknowledged_by: null,
            });
            playSound();
        })
        .listen('.ServiceRequestUpdated', (event) => {
            const request = requests.value.find((r) => r.id === event.id);
            if (!request) return;
            if (event.status === 'resolved') {
                requests.value = requests.value.filter((r) => r.id !== event.id);
            } else {
                request.status = event.status;
            }
        });
});

onUnmounted(() => {
    if (venueId.value && channel) {
        window.Echo.leaveChannel(`venue.${venueId.value}.service-requests`);
    }
});
</script>

<template>
    <AppLayout :title="__('Direct Waiter')">
        <template #header>
            <h2 class="font-heading text-xl font-semibold text-ocean-deep dark:text-gray-100">{{ __('Direct Waiter') }}</h2>
        </template>

        <div class="py-6 px-4 sm:px-6">
            <AppEmptyState
                v-if="requests.length === 0"
                :title="__('No messages right now')"
                :description="__('Guest messages sent through the QR code hub will show up here in real time.')"
            />

            <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <AppCard v-for="request in requests" :key="request.id">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <p class="font-heading text-sm font-semibold text-ocean-deep dark:text-gray-100">
                                {{ request.service_location?.name ?? __('Guest') }}
                            </p>
                            <p class="mt-1 text-xs text-muted-foreground">{{ elapsed(request.created_at) }}</p>
                        </div>
                        <AppBadge :label="__(request.status)" :variant="statusVariant[request.status]" />
                    </div>

                    <p class="mt-3 text-sm text-ocean-deep dark:text-gray-100">{{ request.message || __('(no message)') }}</p>

                    <AppBadge
                        v-if="request.assigned_user_id === currentUserId"
                        class="mt-3"
                        :label="__('Assigned to you')"
                        variant="primary"
                    />

                    <div class="mt-4 flex gap-2">
                        <AppButton v-if="request.status === 'pending'" size="sm" variant="secondary" @click="acknowledge(request)">
                            {{ __('Acknowledge') }}
                        </AppButton>
                        <AppButton size="sm" @click="resolve(request)">
                            {{ __('Resolve') }}
                        </AppButton>
                    </div>
                </AppCard>
            </div>
        </div>
    </AppLayout>
</template>
