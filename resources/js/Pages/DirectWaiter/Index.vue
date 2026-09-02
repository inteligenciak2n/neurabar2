<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import AppBadge from '@/Components/AppBadge.vue';
import AppButton from '@/Components/AppButton.vue';
import AppCard from '@/Components/AppCard.vue';
import AppEmptyState from '@/Components/AppEmptyState.vue';
import { router, usePage } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref, computed } from 'vue';
import { useTranslate } from '@/Composables/useTranslate';
import { useNotificationSound } from '@/Composables/useNotificationSound';
import { useDirectWaiterNotifications } from '@/Composables/useDirectWaiterNotifications';

const __ = useTranslate();
const { reset: resetDirectWaiterNotifications } = useDirectWaiterNotifications();

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

function assignToMe(request) {
    router.put(route('service-requests.assign', request.id), {}, { preserveScroll: true });
}

function release(request) {
    router.put(route('service-requests.release', request.id), {}, { preserveScroll: true });
}

const { playSound } = useNotificationSound();

let channel = null;

onMounted(() => {
    resetDirectWaiterNotifications();

    if (!venueId.value) return;

    channel = window.Echo.private(`venue.${venueId.value}.service-requests`)
        .listen('.ServiceRequestCreated', (event) => {
            if (event.type !== 'message') return;
            // Reivindicada por outro atendente: só ele deve ser notificado a partir de agora.
            if (event.assigned_user_id && event.assigned_user_id !== currentUserId.value) return;
            requests.value.unshift({
                id: event.id,
                type: event.type,
                message: event.message,
                status: event.status,
                created_at: event.created_at,
                assigned_user_id: event.assigned_user_id,
                attendance_id: event.attendance_id,
                service_location: event.location_name ? { name: event.location_name } : null,
                assigned_user: null,
                acknowledged_by: null,
            });
            playSound();
            // Já está vendo a mensagem nesta página, não precisa contar no sino do header.
            resetDirectWaiterNotifications();
        })
        .listen('.ServiceRequestUpdated', (event) => {
            if (event.type !== 'message') return;

            if (event.status === 'resolved') {
                requests.value = requests.value.filter((r) => r.id !== event.id);
                return;
            }

            const visibleToMe = ! event.assigned_user_id || event.assigned_user_id === currentUserId.value;
            const request = requests.value.find((r) => r.id === event.id);

            if (! visibleToMe) {
                // Reivindicada por outro atendente: sai do meu board.
                if (request) requests.value = requests.value.filter((r) => r.id !== event.id);
                return;
            }

            if (request) {
                request.status = event.status;
                request.assigned_user_id = event.assigned_user_id;
            } else {
                // Voltou pra fila comum (mesa liberada) ou passou a ser minha sem eu ter visto a criação.
                requests.value.unshift({
                    id: event.id,
                    type: event.type,
                    message: event.message,
                    status: event.status,
                    created_at: event.created_at,
                    assigned_user_id: event.assigned_user_id,
                    attendance_id: event.attendance_id,
                    service_location: event.location_name ? { name: event.location_name } : null,
                    assigned_user: null,
                    acknowledged_by: null,
                });
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

                    <div class="mt-4 flex flex-wrap gap-2">
                        <AppButton v-if="request.status === 'pending'" size="sm" variant="secondary" @click="acknowledge(request)">
                            {{ __('Acknowledge') }}
                        </AppButton>
                        <AppButton size="sm" @click="resolve(request)">
                            {{ __('Resolve') }}
                        </AppButton>
                        <AppButton
                            v-if="request.assigned_user_id === null && request.attendance_id"
                            size="sm"
                            variant="secondary"
                            @click="assignToMe(request)"
                        >
                            {{ __('Assign to me') }}
                        </AppButton>
                        <AppButton
                            v-if="request.assigned_user_id === currentUserId"
                            size="sm"
                            variant="secondary"
                            @click="release(request)"
                        >
                            {{ __('Release table') }}
                        </AppButton>
                    </div>
                </AppCard>
            </div>
        </div>
    </AppLayout>
</template>
