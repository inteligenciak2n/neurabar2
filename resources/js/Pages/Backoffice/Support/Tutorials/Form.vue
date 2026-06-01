<script setup>
import PlatformLayout from '@/Layouts/PlatformLayout.vue';
import AppCard from '@/Components/AppCard.vue';
import AppButton from '@/Components/AppButton.vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    tutorial: Object,
    categories: Array,
});

const isEdit = !!props.tutorial;

const form = useForm({
    category_id: props.tutorial?.category_id ?? '',
    title: props.tutorial?.title ?? '',
    summary: props.tutorial?.summary ?? '',
    body: props.tutorial?.body ?? '',
    featured_image: null,
    published: props.tutorial?.published ?? false,
    position: props.tutorial?.position ?? 0,
});

function submit() {
    if (isEdit) {
        form.put(route('platform.support.tutorials.update', props.tutorial.id), {
            forceFormData: true,
        });
    } else {
        form.post(route('platform.support.tutorials.store'), {
            forceFormData: true,
        });
    }
}

function onImageChange(event) {
    form.featured_image = event.target.files[0] ?? null;
}
</script>

<template>
    <PlatformLayout :title="isEdit ? 'Editar Tutorial' : 'Novo Tutorial'">
        <template #header>
            <h1 class="font-heading text-xl font-bold text-ocean-deep dark:text-gray-100">
                {{ isEdit ? 'Editar Tutorial' : 'Novo Tutorial' }}
            </h1>
        </template>

        <div class="mx-auto max-w-3xl">
            <AppCard>
                <form @submit.prevent="submit" class="space-y-5">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <!-- Category -->
                        <div>
                            <label class="block text-sm font-medium mb-1">Categoria</label>
                            <select
                                v-model="form.category_id"
                                class="w-full rounded-lg border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                                required
                            >
                                <option value="" disabled>Selecione...</option>
                                <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                            </select>
                            <p v-if="form.errors.category_id" class="mt-1 text-xs text-red-500">{{ form.errors.category_id }}</p>
                        </div>

                        <!-- Position -->
                        <div>
                            <label class="block text-sm font-medium mb-1">Posição (ordenação)</label>
                            <input
                                v-model.number="form.position"
                                type="number"
                                min="0"
                                class="w-full rounded-lg border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                            />
                        </div>
                    </div>

                    <!-- Title -->
                    <div>
                        <label class="block text-sm font-medium mb-1">Título</label>
                        <input
                            v-model="form.title"
                            type="text"
                            maxlength="255"
                            class="w-full rounded-lg border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                            required
                        />
                        <p v-if="form.errors.title" class="mt-1 text-xs text-red-500">{{ form.errors.title }}</p>
                    </div>

                    <!-- Summary -->
                    <div>
                        <label class="block text-sm font-medium mb-1">Resumo <span class="text-muted-foreground font-normal">(opcional)</span></label>
                        <input
                            v-model="form.summary"
                            type="text"
                            maxlength="500"
                            class="w-full rounded-lg border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                        />
                    </div>

                    <!-- Body (Markdown) -->
                    <div>
                        <label class="block text-sm font-medium mb-1">Conteúdo (Markdown)</label>
                        <textarea
                            v-model="form.body"
                            rows="14"
                            class="w-full rounded-lg border border-border bg-white px-3 py-2 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                            placeholder="# Título&#10;&#10;Escreva o conteúdo em Markdown..."
                            required
                        />
                        <p v-if="form.errors.body" class="mt-1 text-xs text-red-500">{{ form.errors.body }}</p>
                    </div>

                    <!-- Featured image -->
                    <div>
                        <label class="block text-sm font-medium mb-1">Imagem destacada <span class="text-muted-foreground font-normal">(opcional, máx. 2MB)</span></label>
                        <div v-if="tutorial?.featured_image && !form.featured_image" class="mb-2">
                            <img :src="tutorial.featured_image" alt="Imagem atual" class="h-24 rounded-lg object-cover" />
                        </div>
                        <input
                            type="file"
                            accept="image/*"
                            @change="onImageChange"
                            class="block w-full text-sm text-muted-foreground file:mr-3 file:rounded-lg file:border-0 file:bg-primary/10 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-primary hover:file:bg-primary/20"
                        />
                    </div>

                    <!-- Published -->
                    <div>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" v-model="form.published" class="rounded border-border" />
                            <span class="text-sm font-medium">Publicar imediatamente</span>
                        </label>
                    </div>

                    <div class="flex justify-end gap-3">
                        <AppButton variant="secondary" as="a" :href="route('platform.support.tutorials.index')">
                            Cancelar
                        </AppButton>
                        <AppButton type="submit" :disabled="form.processing">
                            {{ form.processing ? 'Salvando...' : (isEdit ? 'Salvar Alterações' : 'Criar Tutorial') }}
                        </AppButton>
                    </div>
                </form>
            </AppCard>
        </div>
    </PlatformLayout>
</template>
