<script setup>
import PlatformLayout from '@/Layouts/PlatformLayout.vue';
import AppCard from '@/Components/AppCard.vue';
import AppBadge from '@/Components/AppBadge.vue';
import AppButton from '@/Components/AppButton.vue';
import AppEmptyState from '@/Components/AppEmptyState.vue';
import AppPagination from '@/Components/AppPagination.vue';
import AppConfirmModal from '@/Components/AppConfirmModal.vue';
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    tutorials: Object,
    categories: Array,
});

const tutorialToDelete = ref(null);

function togglePublished(tutorialId) {
    router.post(route('platform.support.tutorials.toggle-published', tutorialId), {}, { preserveScroll: true });
}

function confirmDelete(tutorial) {
    tutorialToDelete.value = tutorial;
}

function deleteTutorial() {
    router.delete(route('platform.support.tutorials.destroy', tutorialToDelete.value.id), {
        onSuccess: () => { tutorialToDelete.value = null; },
    });
}

function formatDate(dateStr) {
    return new Date(dateStr).toLocaleDateString('pt-BR');
}
</script>

<template>
    <PlatformLayout title="Tutoriais">
        <template #header>
            <div class="flex items-center justify-between">
                <h1 class="font-heading text-xl font-bold text-ocean-deep">Tutoriais e Manuais</h1>
                <AppButton :href="route('platform.support.tutorials.create')" as="a">Novo Tutorial</AppButton>
            </div>
        </template>

        <AppCard>
            <AppEmptyState v-if="tutorials.data.length === 0" message="Nenhum tutorial cadastrado." />

            <div v-else class="divide-y divide-border">
                <div
                    v-for="tutorial in tutorials.data"
                    :key="tutorial.id"
                    class="flex items-center justify-between py-3"
                >
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-medium text-ocean-deep">{{ tutorial.title }}</p>
                        <p class="text-xs text-muted-foreground">
                            {{ tutorial.category?.name }} · Atualizado em {{ formatDate(tutorial.updated_at) }}
                        </p>
                    </div>
                    <div class="ml-4 flex shrink-0 items-center gap-2">
                        <AppBadge :variant="tutorial.published ? 'green' : 'muted'">
                            {{ tutorial.published ? 'Publicado' : 'Rascunho' }}
                        </AppBadge>
                        <AppButton variant="muted" size="sm" @click="togglePublished(tutorial.id)">
                            {{ tutorial.published ? 'Despublicar' : 'Publicar' }}
                        </AppButton>
                        <AppButton
                            variant="secondary"
                            size="sm"
                            as="a"
                            :href="route('platform.support.tutorials.edit', tutorial.id)"
                        >
                            Editar
                        </AppButton>
                        <AppButton variant="danger" size="sm" @click="confirmDelete(tutorial)">Excluir</AppButton>
                    </div>
                </div>
            </div>

            <AppPagination v-if="tutorials.last_page > 1" :links="tutorials.links" class="mt-4" />
        </AppCard>

        <AppConfirmModal
            v-if="tutorialToDelete"
            title="Excluir Tutorial"
            :message="`Tem certeza que deseja excluir &quot;${tutorialToDelete.title}&quot;? Esta ação não pode ser desfeita.`"
            confirm-label="Excluir"
            @confirm="deleteTutorial"
            @cancel="tutorialToDelete = null"
        />
    </PlatformLayout>
</template>
