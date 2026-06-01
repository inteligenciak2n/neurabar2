<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import AppButton from '@/Components/AppButton.vue';
import AppCard from '@/Components/AppCard.vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    tutorial: Object,
    related: Array,
});

function formatDate(dateStr) {
    return new Date(dateStr).toLocaleDateString('pt-BR', { dateStyle: 'long' });
}
</script>

<template>
    <AppLayout :title="tutorial.title">
        <template #header>
            <div class="flex items-center gap-2 text-sm text-muted-foreground">
                <Link :href="route('support.tutorials.index')" class="hover:text-primary">Tutoriais</Link>
                <span>/</span>
                <span>{{ tutorial.category?.name }}</span>
            </div>
        </template>

        <div class="mx-auto max-w-4xl">
            <div class="grid gap-8 lg:grid-cols-3">
                <!-- Main content -->
                <div class="lg:col-span-2">
                    <AppCard>
                        <div v-if="tutorial.featured_image" class="mb-6 overflow-hidden rounded-xl">
                            <img
                                :src="tutorial.featured_image"
                                :alt="tutorial.title"
                                class="w-full object-cover"
                            />
                        </div>

                        <h1 class="text-2xl font-bold text-ocean-deep mb-2">{{ tutorial.title }}</h1>

                        <p v-if="tutorial.summary" class="text-muted-foreground mb-4">{{ tutorial.summary }}</p>

                        <p class="text-xs text-muted-foreground mb-6">
                            Publicado em {{ formatDate(tutorial.published_at) }}
                        </p>

                        <!-- Rendered markdown as HTML -->
                        <!-- eslint-disable-next-line vue/no-v-html -->
                        <div
                            class="prose prose-sm max-w-none prose-headings:text-ocean-deep prose-a:text-primary"
                            v-html="tutorial.body_html ?? tutorial.body"
                        />
                    </AppCard>

                    <div class="mt-6 rounded-xl border border-border bg-muted/30 p-5">
                        <p class="text-sm text-ocean-deep font-medium">Este tutorial não resolveu seu problema?</p>
                        <p class="mt-1 text-xs text-muted-foreground">Nossa equipe está pronta para ajudar você.</p>
                        <AppButton :href="route('support.tickets.create')" as="a" variant="secondary" class="mt-3">
                            Abrir Chamado de Suporte
                        </AppButton>
                    </div>
                </div>

                <!-- Related tutorials -->
                <div v-if="related.length">
                    <h2 class="text-sm font-semibold text-muted-foreground uppercase tracking-wider mb-3">
                        Relacionados
                    </h2>
                    <div class="space-y-3">
                        <Link
                            v-for="item in related"
                            :key="item.id"
                            :href="route('support.tutorials.show', item.slug)"
                            class="block rounded-xl border border-border bg-white p-3 hover:border-primary/30 hover:shadow-card transition-all"
                        >
                            <p class="font-medium text-ocean-deep text-sm line-clamp-2">{{ item.title }}</p>
                            <p v-if="item.summary" class="mt-1 text-xs text-muted-foreground line-clamp-2">
                                {{ item.summary }}
                            </p>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
