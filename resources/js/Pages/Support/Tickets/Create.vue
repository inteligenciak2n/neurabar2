<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import AppCard from '@/Components/AppCard.vue';
import AppButton from '@/Components/AppButton.vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    categories: Array,
});

const form = useForm({
    category_id: '',
    subject: '',
    body: '',
    attachments: [],
});

function submit() {
    form.post(route('support.tickets.store'), {
        forceFormData: true,
    });
}

function onFilesChange(event) {
    form.attachments = Array.from(event.target.files);
}
</script>

<template>
    <AppLayout title="Novo Chamado">
        <template #header>
            <h1 class="font-heading text-xl font-bold text-ocean-deep">Abrir Novo Chamado</h1>
        </template>

        <div class="mx-auto max-w-2xl">
            <AppCard>
                <form @submit.prevent="submit" class="space-y-5">
                    <!-- Category -->
                    <div>
                        <label class="block text-sm font-medium text-ocean-deep mb-1">Categoria</label>
                        <select
                            v-model="form.category_id"
                            class="w-full rounded-lg border border-border bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                            required
                        >
                            <option value="" disabled>Selecione uma categoria...</option>
                            <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                                {{ cat.name }}
                            </option>
                        </select>
                        <p v-if="form.errors.category_id" class="mt-1 text-xs text-red-500">{{ form.errors.category_id }}</p>
                    </div>

                    <!-- Subject -->
                    <div>
                        <label class="block text-sm font-medium text-ocean-deep mb-1">Assunto</label>
                        <input
                            v-model="form.subject"
                            type="text"
                            class="w-full rounded-lg border border-border bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                            placeholder="Descreva brevemente o problema..."
                            maxlength="255"
                            required
                        />
                        <p v-if="form.errors.subject" class="mt-1 text-xs text-red-500">{{ form.errors.subject }}</p>
                    </div>

                    <!-- Body -->
                    <div>
                        <label class="block text-sm font-medium text-ocean-deep mb-1">Descrição</label>
                        <textarea
                            v-model="form.body"
                            rows="6"
                            class="w-full rounded-lg border border-border bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                            placeholder="Descreva o problema em detalhes..."
                            required
                        />
                        <p v-if="form.errors.body" class="mt-1 text-xs text-red-500">{{ form.errors.body }}</p>
                    </div>

                    <!-- Attachments -->
                    <div>
                        <label class="block text-sm font-medium text-ocean-deep mb-1">
                            Anexos <span class="text-muted-foreground font-normal">(máx. 5 arquivos, 10MB cada)</span>
                        </label>
                        <input
                            type="file"
                            multiple
                            accept=".jpg,.jpeg,.png,.gif,.pdf,.docx,.xlsx,.txt,.zip"
                            @change="onFilesChange"
                            class="block w-full text-sm text-muted-foreground file:mr-3 file:rounded-lg file:border-0 file:bg-primary/10 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-primary hover:file:bg-primary/20"
                        />
                        <p v-if="form.errors.attachments" class="mt-1 text-xs text-red-500">{{ form.errors.attachments }}</p>
                    </div>

                    <div class="flex justify-end gap-3">
                        <AppButton variant="secondary" as="a" :href="route('support.dashboard')">Cancelar</AppButton>
                        <AppButton type="submit" :disabled="form.processing">
                            {{ form.processing ? 'Enviando...' : 'Abrir Chamado' }}
                        </AppButton>
                    </div>
                </form>
            </AppCard>
        </div>
    </AppLayout>
</template>
