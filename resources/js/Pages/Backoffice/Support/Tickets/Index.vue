<script setup>
import PlatformLayout from '@/Layouts/PlatformLayout.vue';
import AppCard from '@/Components/AppCard.vue';
import AppBadge from '@/Components/AppBadge.vue';
import AppButton from '@/Components/AppButton.vue';
import AppEmptyState from '@/Components/AppEmptyState.vue';
import AppPagination from '@/Components/AppPagination.vue';
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    tickets: Object,
    users: Object,
    agents: Array,
    categories: Array,
    statuses: Array,
    priorities: Array,
    filters: Object,
});

const statusColors = {
    open: 'blue',
    in_progress: 'yellow',
    resolved: 'green',
    closed: 'gray',
};

const priorityColors = {
    low: 'muted',
    medium: 'blue',
    high: 'accent',
    urgent: 'danger',
};

const search = ref(props.filters?.search ?? '');
const filterStatus = ref(props.filters?.status ?? '');
const filterPriority = ref(props.filters?.priority ?? '');
const filterAgent = ref(props.filters?.assigned_to ?? '');

function applyFilters() {
    router.get(route('platform.support.tickets.index'), {
        search: search.value || undefined,
        status: filterStatus.value || undefined,
        priority: filterPriority.value || undefined,
        assigned_to: filterAgent.value || undefined,
    }, { preserveState: true, replace: true });
}

function formatDate(dateStr) {
    return new Date(dateStr).toLocaleDateString('pt-BR', { dateStyle: 'short' });
}

function getUserName(userId) {
    return props.users[userId]?.name ?? userId;
}

function getAgentName(agentId) {
    if (!agentId) { return '—'; }
    return props.agents.find(a => a.id === agentId)?.name ?? agentId;
}
</script>

<template>
    <PlatformLayout title="Chamados de Suporte">
        <template #header>
            <h1 class="font-heading text-xl font-bold text-ocean-deep">Chamados de Suporte</h1>
        </template>

        <!-- Filters -->
        <AppCard class="mb-4">
            <div class="flex flex-wrap gap-3">
                <input
                    v-model="search"
                    type="search"
                    placeholder="Buscar por assunto..."
                    class="rounded-lg border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary w-56"
                    @keyup.enter="applyFilters"
                />
                <select v-model="filterStatus" @change="applyFilters" class="rounded-lg border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="">Todos os status</option>
                    <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                </select>
                <select v-model="filterPriority" @change="applyFilters" class="rounded-lg border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="">Todas as prioridades</option>
                    <option v-for="p in priorities" :key="p.value" :value="p.value">{{ p.label }}</option>
                </select>
                <select v-model="filterAgent" @change="applyFilters" class="rounded-lg border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="">Todos os agentes</option>
                    <option v-for="agent in agents" :key="agent.id" :value="agent.id">{{ agent.name }}</option>
                </select>
                <AppButton @click="applyFilters">Filtrar</AppButton>
            </div>
        </AppCard>

        <!-- Table -->
        <AppCard>
            <AppEmptyState v-if="tickets.data.length === 0" message="Nenhum chamado encontrado." />

            <div v-else class="divide-y divide-border">
                <Link
                    v-for="ticket in tickets.data"
                    :key="ticket.id"
                    :href="route('platform.support.tickets.show', ticket.id)"
                    class="flex items-center justify-between py-3 hover:bg-muted/30 px-2 -mx-2 rounded-lg transition-colors"
                >
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-medium text-ocean-deep">{{ ticket.subject }}</p>
                        <p class="text-xs text-muted-foreground">
                            {{ getUserName(ticket.user_id) }} · {{ ticket.category?.name }} · {{ formatDate(ticket.created_at) }}
                        </p>
                    </div>
                    <div class="ml-4 flex shrink-0 items-center gap-2">
                        <span class="text-xs text-muted-foreground hidden sm:block">{{ getAgentName(ticket.assigned_to) }}</span>
                        <AppBadge :variant="priorityColors[ticket.priority]" size="sm">{{ ticket.priority }}</AppBadge>
                        <AppBadge :variant="statusColors[ticket.status]">{{ ticket.status }}</AppBadge>
                    </div>
                </Link>
            </div>

            <AppPagination v-if="tickets.last_page > 1" :links="tickets.links" class="mt-4" />
        </AppCard>
    </PlatformLayout>
</template>
