<script setup>
import PlatformLayout from '@/Layouts/PlatformLayout.vue';
import AppCard from '@/Components/AppCard.vue';
import AppBadge from '@/Components/AppBadge.vue';
import AppButton from '@/Components/AppButton.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    ticket: Object,
    ticketUser: Object,
    assignedAgent: Object,
    agents: Array,
    categories: Array,
    statuses: Array,
    priorities: Array,
});

const replyForm = useForm({
    body: '',
    is_internal: false,
    attachments: [],
});

const updateForm = useForm({
    status: props.ticket.status,
    priority: props.ticket.priority,
    assigned_to: props.ticket.assigned_to ?? '',
});

const showUpdatePanel = ref(false);

const statusColors = {
    open: 'primary',
    in_progress: 'accent',
    resolved: 'success',
    closed: 'muted',
};

function submitReply() {
    replyForm.post(route('platform.support.tickets.messages.store', props.ticket.id), {
        forceFormData: true,
        onSuccess: () => replyForm.reset(),
    });
}

function submitUpdate() {
    updateForm.put(route('platform.support.tickets.update', props.ticket.id), {
        onSuccess: () => { showUpdatePanel.value = false; },
    });
}

function onFilesChange(event) {
    replyForm.attachments = Array.from(event.target.files);
}

function formatDate(dateStr) {
    return new Date(dateStr).toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' });
}
</script>

<template>
    <PlatformLayout :title="ticket.subject">
        <template #header>
            <div class="flex justify-between w-full">
                <h1 class="font-heading text-xl font-bold text-ocean-deep dark:text-gray-100">{{ ticket.subject }}</h1>
                <div class="mt-1 flex items-center gap-2 text-sm text-muted-foreground">
                    <span>{{ ticketUser?.name ?? ticket.user_id }}</span>
                    <span>·</span>
                    <span>{{ ticket.category?.name }}</span>
                </div>
            </div>
        </template>
           
        <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="font-heading text-xl font-bold text-ocean-deep dark:text-gray-100">{{ ticket.subject }}</h1>
                    <div class="mt-1 flex items-center gap-2 text-sm text-muted-foreground">
                        <span>{{ ticketUser?.name ?? ticket.user_id }}</span>
                        <span>·</span>
                        <span>{{ ticket.category?.name }}</span>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <AppBadge :variant="statusColors[ticket.status]" :label="ticket.status" />
                    <AppButton variant="secondary" @click="showUpdatePanel = !showUpdatePanel">
                        {{ showUpdatePanel ? 'Fechar Painel' : 'Gerenciar Chamado' }}
                    </AppButton>
                </div>
        </div>

        <div class="mx-auto max-w-4xl space-y-4">
            <!-- Management panel -->
            <AppCard v-if="showUpdatePanel">
                <template #title>Gerenciar Chamado</template>
                <form @submit.prevent="submitUpdate" class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <label class="block text-xs font-medium mb-1 dark:text-gray-300">Status</label>
                        <select v-model="updateForm.status" class="w-full rounded-lg border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                            <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1 dark:text-gray-300">Prioridade</label>
                        <select v-model="updateForm.priority" class="w-full rounded-lg border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                            <option v-for="p in priorities" :key="p.value" :value="p.value">{{ p.label }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1 dark:text-gray-300">Atribuir a</label>
                        <select v-model="updateForm.assigned_to" class="w-full rounded-lg border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                            <option value="">— Sem atribuição —</option>
                            <option v-for="agent in agents" :key="agent.id" :value="agent.id">{{ agent.name }}</option>
                        </select>
                    </div>
                    <div class="sm:col-span-3 flex justify-end">
                        <AppButton type="submit" :disabled="updateForm.processing">Salvar Alterações</AppButton>
                    </div>
                </form>
            </AppCard>

            <!-- Ticket rating -->
            <AppCard v-if="ticket.rating" class="border-green-200 bg-green-50">
                <template #title>Avaliação do Cliente</template>
                <div class="flex items-center gap-3">
                    <div class="flex gap-1">
                        <span v-for="n in 5" :key="n" :class="['text-lg', n <= ticket.rating.score ? 'text-warm-gold' : 'text-gray-300']">★</span>
                    </div>
                    <p v-if="ticket.rating.comment" class="text-sm text-muted-foreground italic">"{{ ticket.rating.comment }}"</p>
                </div>
            </AppCard>

            <!-- Messages thread -->
            <AppCard>
                <div class="space-y-4">
                    <div
                        v-for="message in ticket.messages"
                        :key="message.id"
                        :class="[
                            'rounded-xl p-4',
                            message.is_internal ? 'bg-yellow-50 border border-yellow-200 dark:bg-yellow-900/20 dark:border-yellow-800' : message.author_type === 'platform_user' ? 'bg-primary/5 border border-primary/10 dark:bg-primary/10' : 'bg-muted/90 dark:bg-gray-700/60',
                            message.author_type === 'platform_user' ? 'text-primary ml-16' : 'text-ocean-deep mr-16 dark:text-gray-100',
                        ]"
                    >
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-semibold uppercase tracking-wider"
                                    :class="message.author_type === 'platform_user' ? 'text-primary' : 'text-ocean-deep dark:text-gray-300'">
                                    {{ message.author_type === 'platform_user' ? 'Agente' : ticketUser?.name ?? 'Cliente' }}
                                </span>
                                <AppBadge v-if="message.is_internal" variant="warning" size="sm">Nota Interna</AppBadge>
                            </div>
                            <span class="text-xs text-muted-foreground">{{ formatDate(message.created_at) }}</span>
                        </div>
                        <p class="whitespace-pre-wrap text-sm text-ocean-deep dark:text-gray-100">{{ message.body }}</p>

                        <div v-if="message.attachments?.length" class="mt-3 flex flex-wrap gap-2">
                            <a
                                v-for="att in message.attachments"
                                :key="att.id"
                                :href="`/support/attachments/${att.id}`"
                                target="_blank"
                                class="inline-flex items-center gap-1 rounded-md border border-border bg-white px-2 py-1 text-xs text-primary hover:bg-muted/40 dark:border-gray-600 dark:bg-gray-700 dark:hover:bg-gray-600"
                            >
                                📎 {{ att.filename }}
                            </a>
                        </div>
                    </div>
                </div>
            </AppCard>

            <!-- Reply form -->
            <AppCard>
                <template #title>Responder</template>
                <form @submit.prevent="submitReply" class="space-y-3">
                    <div class="flex items-center gap-3">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" v-model="replyForm.is_internal" class="rounded border-border" />
                            <span class="text-sm">Nota interna (visível apenas para o backoffice)</span>
                        </label>
                    </div>
                    <textarea
                        v-model="replyForm.body"
                        rows="4"
                        :class="[
                            'w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2',
                            replyForm.is_internal
                                ? 'border-yellow-300 bg-yellow-50 focus:ring-yellow-400 dark:bg-yellow-900/20 dark:border-yellow-700'
                                : 'border-border focus:ring-primary dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100',
                        ]"
                        placeholder="Escreva sua mensagem..."
                        required
                    />
                    <p v-if="replyForm.errors.body" class="text-xs text-red-500">{{ replyForm.errors.body }}</p>

                    <div>
                        <input
                            type="file"
                            multiple
                            accept=".jpg,.jpeg,.png,.gif,.pdf,.docx,.xlsx,.txt,.zip"
                            @change="onFilesChange"
                            class="block w-full text-sm text-muted-foreground file:mr-3 file:rounded-lg file:border-0 file:bg-primary/10 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-primary hover:file:bg-primary/20"
                        />
                    </div>

                    <div class="flex justify-end">
                        <AppButton type="submit" :disabled="replyForm.processing">
                            {{ replyForm.is_internal ? 'Adicionar Nota Interna' : 'Enviar Resposta' }}
                        </AppButton>
                    </div>
                </form>
            </AppCard>
        </div>
    </PlatformLayout>
</template>
