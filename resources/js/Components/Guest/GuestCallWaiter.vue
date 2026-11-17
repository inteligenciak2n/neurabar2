<script setup>
import { computed, ref } from 'vue';
import axios from 'axios';
import { Link } from '@inertiajs/vue3';
import { useTranslate } from '@/Composables/useTranslate';

const props = defineProps({
    token: {
        type: String,
        default: null,
    },
    preview: {
        type: Boolean,
        default: false,
    },
    pendingCall: {
        type: Object,
        default: null,
    },
    phrases: {
        type: Array,
        default: () => [],
    },
});

const __ = useTranslate();

const chips = computed(() => {
    const seen = new Set();
    const labels = [];

    for (const phrase of props.phrases ?? []) {
        const label = String(phrase ?? '').trim();

        if (!label) {
            continue;
        }

        const key = label.toLowerCase();

        if (seen.has(key)) {
            continue;
        }

        seen.add(key);
        labels.push(label);
    }

    return labels;
});

const selectedChips = ref([]);
const extraMessage = ref('');
const loading = ref(false);
const activeCall = ref(props.pendingCall);

const called = computed(() => activeCall.value !== null);

const composedMessage = computed(() => {
    const parts = [
        ...selectedChips.value,
        extraMessage.value.trim(),
    ].filter(Boolean);

    return parts.join('. ');
});

function toggleChip(chip) {
    if (selectedChips.value.includes(chip)) {
        selectedChips.value = selectedChips.value.filter((item) => item !== chip);
        return;
    }

    selectedChips.value = [...selectedChips.value, chip];
}

function formatCalledAt(iso) {
    if (!iso) {
        return '';
    }

    const date = new Date(iso);

    if (Number.isNaN(date.getTime())) {
        return '';
    }

    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

async function callWaiter() {
    if (loading.value) {
        return;
    }

    loading.value = true;

    try {
        if (props.preview) {
            activeCall.value = {
                id: 'preview',
                message: composedMessage.value || null,
                created_at: new Date().toISOString(),
            };
            return;
        }

        const { data } = await axios.post(`/g/${props.token}/signal`, {
            message: composedMessage.value || null,
        });

        activeCall.value = {
            id: data.id,
            message: data.message,
            created_at: data.created_at,
        };
        selectedChips.value = [];
        extraMessage.value = '';
    } finally {
        loading.value = false;
    }
}

async function cancelCall() {
    if (loading.value || !activeCall.value) {
        return;
    }

    loading.value = true;

    try {
        if (!props.preview && activeCall.value.id) {
            await axios.post(`/g/${props.token}/signal/${activeCall.value.id}/cancel`);
        }

        activeCall.value = null;
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <div data-call-waiter class="mb-4 rounded-2xl bg-white p-5 shadow-card">
        <template v-if="!called">
            <div class="flex items-start gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-ocean-deep/10 text-ocean-deep">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <h3 class="font-heading text-base font-bold text-ocean-deep">{{ __('Call waiter') }}</h3>
                    <p class="mt-0.5 text-sm leading-snug text-muted-foreground">
                        {{ __('Tell us what you need and we will notify the team') }}
                    </p>
                </div>
            </div>

            <div v-if="chips.length || preview" class="mt-4 flex flex-wrap gap-2">
                <button
                    v-for="chip in chips"
                    :key="chip"
                    type="button"
                    class="rounded-full border px-3 py-1.5 text-sm transition-colors"
                    :class="selectedChips.includes(chip)
                        ? 'border-ocean-deep bg-ocean-deep text-white'
                        : 'border-border bg-white text-ocean-deep hover:border-ocean-deep'"
                    @click="toggleChip(chip)"
                >
                    {{ chip }}
                </button>
                <Link
                    v-if="preview"
                    :href="route('settings.waiter-call-phrases.index')"
                    data-register-phrases
                    class="rounded-full border border-dashed border-warm-gold bg-warm-gold/10 px-3 py-1.5 text-sm font-medium text-warm-gold hover:bg-warm-gold/20"
                >
                    {{ __('Register your own phrases here') }}
                </Link>
            </div>

            <label class="mt-4 block text-sm font-medium text-ocean-deep">
                {{ __('What do you need?') }}
                <span class="font-normal text-muted-foreground"> ({{ __('optional') }})</span>
            </label>
            <textarea
                v-model="extraMessage"
                rows="3"
                maxlength="500"
                :placeholder="__('Ex: more ice, change an order, a question about a dish...')"
                class="mt-1 w-full resize-none rounded-xl border border-border px-3 py-2 text-sm text-ocean-deep placeholder:text-muted-foreground/70 focus:outline-none focus:ring-2 focus:ring-primary"
            />

            <button
                type="button"
                :disabled="loading"
                class="mt-4 flex w-full items-center justify-center gap-2 rounded-full bg-ocean-deep py-3 text-sm font-semibold text-white disabled:opacity-50 active:opacity-80"
                @click="callWaiter"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>
                {{ loading ? __('Sending...') : __('Call waiter') }}
            </button>
        </template>

        <div
            v-else
            class="rounded-2xl border-2 border-emerald-400 bg-white px-4 py-6 text-center"
        >
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-emerald-50 text-emerald-500">
                <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <h3 class="mt-3 font-heading text-lg font-bold text-ocean-deep">{{ __('Waiter called!') }}</h3>
            <p class="mt-1 text-sm text-muted-foreground">
                {{ __('Someone from the team will come to your table shortly') }}
            </p>

            <div
                v-if="activeCall.message"
                class="mt-4 rounded-xl bg-muted px-4 py-3 text-left"
            >
                <p class="text-xs font-medium text-muted-foreground">{{ __('Your request') }}</p>
                <p class="mt-1 text-sm font-medium text-ocean-deep">{{ activeCall.message }}</p>
            </div>

            <p v-if="formatCalledAt(activeCall.created_at)" class="mt-3 text-xs text-muted-foreground">
                {{ __('Called at :time', { time: formatCalledAt(activeCall.created_at) }) }}
            </p>

            <button
                type="button"
                :disabled="loading"
                class="mt-4 w-full rounded-full border border-border py-2.5 text-sm font-semibold text-ocean-deep disabled:opacity-50 active:opacity-80"
                @click="cancelCall"
            >
                {{ loading ? __('Sending...') : __('Cancel call') }}
            </button>
        </div>
    </div>
</template>
