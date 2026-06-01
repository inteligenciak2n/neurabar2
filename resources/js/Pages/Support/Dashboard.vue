<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import AppCard from '@/Components/AppCard.vue';
import AppBadge from '@/Components/AppBadge.vue';
import AppButton from '@/Components/AppButton.vue';
import AppEmptyState from '@/Components/AppEmptyState.vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    openTickets: Array,
    recentlyResolved: Array,
    tutorialCategories: Array,
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
</script>

<template>
    <AppLayout title="Suporte">
        <template #header>
            <div class="flex items-center justify-between">
                <h1 class="font-heading text-xl font-bold text-ocean-deep">Central de Suporte</h1>
                <AppButton :href="route('support.tickets.create')" as="a">Abrir Chamado</AppButton>
            </div>
        </template>

        <div class="space-y-8">
            <!-- Open tickets -->
            <AppCard>
                <template #title>Meus Chamados Abertos</template>
                <template #actions>
                    <Link :href="route('support.tickets.index')" class="text-sm text-primary hover:underline">
                        Ver todos
                    </Link>
                </template>

                <AppEmptyState v-if="openTickets.length === 0" message="Nenhum chamado aberto." />

                <div v-else class="divide-y divide-border">
                    <Link
                        v-for="ticket in openTickets"
                        :key="ticket.id"
                        :href="route('support.tickets.show', ticket.id)"
                        class="flex items-center justify-between py-3 hover:bg-muted/30 px-2 -mx-2 rounded-lg transition-colors"
                    >
                        <div class="min-w-0">
                            <p class="truncate font-medium text-ocean-deep">{{ ticket.subject }}</p>
                            <p class="text-xs text-muted-foreground">{{ ticket.category?.name }}</p>
                        </div>
                        <AppBadge :variant="statusColors[ticket.status]" class="ml-4 shrink-0">
                            {{ statusLabels[ticket.status] }}
                        </AppBadge>
                    </Link>
                </div>
            </AppCard>

            <!-- Tutorials -->
            <div v-for="category in tutorialCategories" :key="category.id">
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="text-base font-semibold text-ocean-deep">{{ category.name }}</h2>
                    <Link :href="route('support.tutorials.index')" class="text-sm text-primary hover:underline">
                        Ver todos
                    </Link>
                </div>

                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <Link
                        v-for="tutorial in category.published_tutorials"
                        :key="tutorial.id"
                        :href="route('support.tutorials.show', tutorial.slug)"
                        class="rounded-xl border border-border bg-white p-4 hover:shadow-card transition-shadow"
                    >
                        <p class="font-medium text-ocean-deep line-clamp-2">{{ tutorial.title }}</p>
                        <p v-if="tutorial.summary" class="mt-1 text-xs text-muted-foreground line-clamp-2">
                            {{ tutorial.summary }}
                        </p>
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
