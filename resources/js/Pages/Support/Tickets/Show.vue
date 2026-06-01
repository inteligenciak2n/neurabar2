<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import AppCard from '@/Components/AppCard.vue';
import AppBadge from '@/Components/AppBadge.vue';
import AppButton from '@/Components/AppButton.vue';
import { useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    ticket: Object,
    canReply: Boolean,
    canRate: Boolean,
});

const replyForm = useForm({
    body: '',
    attachments: [],
});

const ratingForm = useForm({
    score: null,
    comment: '',
});

const showRating = ref(false);

const statusLabels = {
    open: 'Aberto',
    in_progress: 'Em Atendimento',
    resolved: 'Resolvido',
    closed: 'Encerrado',
};

const statusColors = {
    open: 'blue',
    in_progress: 'yellow',
    resolved: 'green',
    closed: 'gray',
};

function submitReply() {
    replyForm.post(route('support.tickets.messages.store', props.ticket.id), {
        forceFormData: true,
        onSuccess: () => replyForm.reset(),
    });
}

function onFilesChange(event) {
    replyForm.attachments = Array.from(event.target.files);
}

function submitRating() {
    ratingForm.post(route('support.tickets.rate', props.ticket.id), {
        onSuccess: () => { showRating.value = false; },
    });
}

function closeTicket() {
    router.post(route('support.tickets.close', props.ticket.id));
}

function formatDate(dateStr) {
    return new Date(dateStr).toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' });
}
</script>

<template>
    <AppLayout :title="ticket.subject">
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="font-heading text-xl font-bold text-ocean-deep">{{ ticket.subject }}</h1>
                    <p class="text-sm text-muted-foreground">{{ ticket.category?.name }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <AppBadge :variant="statusColors[ticket.status]">{{ statusLabels[ticket.status] }}</AppBadge>
                    <AppButton v-if="canRate && !showRating" variant="secondary" @click="showRating = true">
                        Avaliar Atendimento
                    </AppButton>
                    <AppButton v-if="ticket.status === 'open' || ticket.status === 'in_progress'" variant="muted" @click="closeTicket">
                        Encerrar Chamado
                    </AppButton>
                </div>
            </div>
        </template>

        <div class="mx-auto max-w-3xl space-y-4">
            <!-- Rating form -->
            <AppCard v-if="showRating" class="border-warm-gold/30 bg-warm-gold/5">
                <template #title>Avalie o Atendimento</template>
                <form @submit.prevent="submitRating" class="space-y-4">
                    <div>
                        <p class="text-sm text-ocean-deep mb-2">Nota (1 = Péssimo · 5 = Excelente)</p>
                        <div class="flex gap-2">
                            <button
                                v-for="n in 5"
                                :key="n"
                                type="button"
                                @click="ratingForm.score = n"
                                :class="[
                                    'h-10 w-10 rounded-lg border text-sm font-bold transition-colors',
                                    ratingForm.score === n
                                        ? 'border-warm-gold bg-warm-gold text-white'
                                        : 'border-border bg-white hover:border-warm-gold',
                                ]"
                            >
                                {{ n }}
                            </button>
                        </div>
                        <p v-if="ratingForm.errors.score" class="mt-1 text-xs text-red-500">{{ ratingForm.errors.score }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Comentário (opcional)</label>
                        <textarea
                            v-model="ratingForm.comment"
                            rows="3"
                            class="w-full rounded-lg border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-warm-gold"
                        />
                    </div>
                    <div class="flex justify-end gap-2">
                        <AppButton variant="secondary" type="button" @click="showRating = false">Cancelar</AppButton>
                        <AppButton type="submit" :disabled="ratingForm.processing || !ratingForm.score">
                            Enviar Avaliação
                        </AppButton>
                    </div>
                </form>
            </AppCard>

            <!-- Rating already submitted -->
            <AppCard v-if="ticket.rating" class="border-green-200 bg-green-50">
                <div class="flex items-center gap-3">
                    <div class="flex gap-1">
                        <span
                            v-for="n in 5"
                            :key="n"
                            :class="['text-lg', n <= ticket.rating.score ? 'text-warm-gold' : 'text-gray-300']"
                        >★</span>
                    </div>
                    <p v-if="ticket.rating.comment" class="text-sm text-muted-foreground italic">
                        "{{ ticket.rating.comment }}"
                    </p>
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
                            message.author_type === 'platform_user'
                                ? 'bg-primary/5 border border-primary/10'
                                : 'bg-muted/80',
                            message.author_type === 'platform_user' ? 'text-primary ml-16' : 'text-ocean-deep mr-16',
                        ]"
                    >
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-semibold uppercase tracking-wider"
                                :class="message.author_type === 'platform_user' ? 'text-primary' : 'text-ocean-deep'">
                                {{ message.author_type === 'platform_user' ? 'Suporte' : 'Você' }}
                            </span>
                            <span class="text-xs text-muted-foreground">{{ formatDate(message.created_at) }}</span>
                        </div>
                        <p class="whitespace-pre-wrap text-sm text-ocean-deep">{{ message.body }}</p>

                        <!-- Attachments -->
                        <div v-if="message.attachments?.length" class="mt-3 flex flex-wrap gap-2">
                            <a
                                v-for="att in message.attachments"
                                :key="att.id"
                                :href="route('support.attachments.show', att.id)"
                                target="_blank"
                                class="inline-flex items-center gap-1 rounded-md border border-border bg-white px-2 py-1 text-xs text-primary hover:bg-muted/40"
                            >
                                📎 {{ att.filename }}
                            </a>
                        </div>
                    </div>
                </div>
            </AppCard>

            <!-- Reply form -->
            <AppCard v-if="canReply">
                <template #title>Responder</template>
                <form @submit.prevent="submitReply" class="space-y-3">
                    <textarea
                        v-model="replyForm.body"
                        rows="4"
                        class="w-full rounded-lg border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
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
                            {{ replyForm.processing ? 'Enviando...' : 'Enviar Mensagem' }}
                        </AppButton>
                    </div>
                </form>
            </AppCard>

            <p v-else-if="ticket.status === 'resolved'" class="text-center text-sm text-muted-foreground py-2">
                Este chamado foi resolvido. Responder reabrirá o atendimento.
            </p>
            <p v-else-if="ticket.status === 'closed'" class="text-center text-sm text-muted-foreground py-2">
                Este chamado foi encerrado.
            </p>
        </div>
    </AppLayout>
</template>
