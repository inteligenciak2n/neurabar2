<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import AppCard from '@/Components/AppCard.vue';
import AppBadge from '@/Components/AppBadge.vue';
import AppButton from '@/Components/AppButton.vue';
import AppEmptyState from '@/Components/AppEmptyState.vue';
import AppPagination from '@/Components/AppPagination.vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    tickets: Object,
});

const statusColors = {
    open: 'blue',
    in_progress: 'yellow',
    resolved: 'green',
    closed: 'gray',
};

const statusLabels = {
    open: 'Aberto',
    in_progress: 'Em Atendimento',
    resolved: 'Resolvido',
    closed: 'Encerrado',
};

const priorityColors = {
    low: 'muted',
    medium: 'blue',
    high: 'accent',
    urgent: 'danger',
};

const priorityLabels = {
    low: 'Baixa',
    medium: 'Média',
    high: 'Alta',
    urgent: 'Urgente',
};
</script>

<template>
    <AppLayout title="Meus Chamados">
        <template #header>
            <div class="flex items-center justify-between">
                <h1 class="font-heading text-xl font-bold text-ocean-deep">Meus Chamados</h1>
                <AppButton :href="route('support.tickets.create')" as="a">Novo Chamado</AppButton>
            </div>
        </template>

        <AppCard>
            <AppEmptyState
                v-if="tickets.data.length === 0"
                message="Você ainda não abriu nenhum chamado."
            />

            <div v-else class="divide-y divide-border">
                <Link
                    v-for="ticket in tickets.data"
                    :key="ticket.id"
                    :href="route('support.tickets.show', ticket.id)"
                    class="flex items-center justify-between py-4 hover:bg-muted/30 px-2 -mx-2 rounded-lg transition-colors"
                >
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <p class="truncate font-medium text-ocean-deep">{{ ticket.subject }}</p>
                            <AppBadge v-if="ticket.rating" variant="green" size="sm">Avaliado</AppBadge>
                        </div>
                        <p class="text-xs text-muted-foreground">
                            {{ ticket.category?.name }} · {{ new Date(ticket.created_at).toLocaleDateString('pt-BR') }}
                        </p>
                    </div>
                    <div class="ml-4 flex shrink-0 flex-col items-end gap-1">
                        <AppBadge :variant="statusColors[ticket.status]">{{ statusLabels[ticket.status] }}</AppBadge>
                        <AppBadge :variant="priorityColors[ticket.priority]" size="sm">
                            {{ priorityLabels[ticket.priority] }}
                        </AppBadge>
                    </div>
                </Link>
            </div>

            <AppPagination v-if="tickets.last_page > 1" :links="tickets.links" class="mt-4" />
        </AppCard>
    </AppLayout>
</template>
