<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import AppButton from '@/Components/AppButton.vue';
import { useTranslate } from '@/Composables/useTranslate';
import { useSettingsEditing } from '@/Composables/useSettingsEditing';
import { toast } from 'vue-sonner';
import { computed, onUnmounted } from 'vue';

defineOptions({ name: 'SettingsLayout' });

const props = defineProps({
    title: String,
    lockable: {
        type: Boolean,
        default: true,
    },
});

const translate = useTranslate();
const t = (key, bindings = {}) => translate(key, bindings, 'SettingsLayout');

const { editingEnabled } = useSettingsEditing();
const showBack = computed(() => !route().current('settings.index'));

onUnmounted(() => {
    editingEnabled.value = false;
});

const toggleEditing = () => {
    editingEnabled.value = !editingEnabled.value;
};

const showLockedMessage = () => {
    toast.message(t('Enable editing to change this information'), {
        id: 'settings-locked',
        class: '!border-warm-gold !bg-warm-gold !text-ocean-deep',
        classes: {
            toast: '!border-warm-gold !bg-warm-gold !text-ocean-deep',
            title: '!text-ocean-deep',
        },
        style: {
            '--normal-bg': '#a28665',
            '--normal-border': '#a28665',
            '--normal-text': '#0f172a',
        },
    });
};

const onLockedInteract = (event) => {
    if (!props.lockable || editingEnabled.value) {
        return;
    }

    if (event.target.closest('[data-enable-editing]')) {
        editingEnabled.value = true;
        return;
    }

    event.preventDefault();
    event.stopPropagation();

    if (event.type === 'focusin' && typeof event.target.blur === 'function') {
        event.target.blur();
    }

    showLockedMessage();
};
</script>

<template>
    <AppLayout :title="title">
        <div>
            <div
                v-if="$slots.header"
                class="sticky top-16 z-20 mb-4 border-b border-border bg-muted py-4 dark:border-gray-700 dark:bg-gray-950"
            >
                <div class="mx-auto flex w-full max-w-5xl items-start justify-between gap-4">
                    <div class="flex min-w-0 flex-1 items-start gap-3">
                        <AppButton
                            v-if="showBack"
                            size="sm"
                            variant="secondary"
                            class="mt-1 shrink-0"
                            :href="route('settings.index')"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                            </svg>
                            {{ t('Back') }}
                        </AppButton>
                        <div class="min-w-0 flex-1">
                            <slot name="header" />
                        </div>
                    </div>
                    <div v-if="props.lockable" class="shrink-0 text-right">
                        <label class="inline-flex items-center gap-2">
                            <span class="text-sm font-medium text-ocean-deep dark:text-gray-100">{{ t('Enable editing') }}</span>
                            <button
                                type="button"
                                role="switch"
                                :aria-checked="editingEnabled"
                                :aria-label="t('Enable editing')"
                                class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2"
                                :class="editingEnabled
                                    ? 'bg-[#5c9a6c] focus:ring-[#5c9a6c]'
                                    : 'bg-gray-400 focus:ring-gray-300'"
                                @click="toggleEditing"
                            >
                                <span
                                    class="inline-block h-4 w-4 transform rounded-full transition-transform"
                                    :class="editingEnabled ? 'translate-x-6 bg-white' : 'translate-x-1 bg-gray-100'"
                                />
                            </button>
                        </label>
                        <p class="mt-1 text-sm italic text-muted-foreground">{{ t('Automatic saving') }}</p>
                    </div>
                </div>
            </div>

            <div
                class="relative mx-auto w-full max-w-5xl"
                :class="props.lockable && !editingEnabled ? 'cursor-not-allowed select-none opacity-75' : ''"
                @click.capture="onLockedInteract"
                @focusin.capture="onLockedInteract"
            >
                <fieldset class="w-full min-w-0 border-0 p-0">
                    <slot />
                </fieldset>
            </div>
        </div>
    </AppLayout>
</template>
