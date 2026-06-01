<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import AppEmptyState from '@/Components/AppEmptyState.vue';
import AppButton from '@/Components/AppButton.vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    categories: Array,
});
</script>

<template>
    <AppLayout title="Tutoriais e Manuais">
        <template #header>
            <div class="flex items-center justify-between">
                <h1 class="font-heading text-xl font-bold text-ocean-deep">Tutoriais e Manuais</h1>
                <AppButton :href="route('support.tickets.create')" as="a" variant="secondary">
                    Precisa de ajuda? Abrir Chamado
                </AppButton>
            </div>
        </template>

        <div v-if="categories.length === 0">
            <AppEmptyState message="Nenhum tutorial disponível no momento." />
        </div>

        <div v-else class="space-y-10">
            <div v-for="category in categories" :key="category.id">
                <div class="mb-4 flex items-center gap-2">
                    <span v-if="category.icon" class="text-2xl">{{ category.icon }}</span>
                    <div>
                        <h2 class="text-lg font-semibold text-ocean-deep dark:text-gray-100">{{ category.name }}</h2>
                        <p v-if="category.description" class="text-sm text-muted-foreground">{{ category.description }}</p>
                    </div>
                </div>

                <AppEmptyState
                    v-if="category.published_tutorials.length === 0"
                    message="Nenhum tutorial nesta categoria."
                />

                <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <Link
                        v-for="tutorial in category.published_tutorials"
                        :key="tutorial.id"
                        :href="route('support.tutorials.show', tutorial.slug)"
                        class="group rounded-xl border border-border bg-white p-5 hover:shadow-card hover:border-primary/30 transition-all dark:border-gray-700 dark:bg-gray-800"
                    >
                        <div v-if="tutorial.featured_image" class="mb-3 aspect-video overflow-hidden rounded-lg">
                            <img
                                :src="tutorial.featured_image"
                                :alt="tutorial.title"
                                class="h-full w-full object-cover"
                            />
                        </div>
                        <h3 class="font-medium text-ocean-deep group-hover:text-primary transition-colors line-clamp-2 dark:text-gray-100">
                            {{ tutorial.title }}
                        </h3>
                        <p v-if="tutorial.summary" class="mt-1 text-xs text-muted-foreground line-clamp-2">
                            {{ tutorial.summary }}
                        </p>
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
