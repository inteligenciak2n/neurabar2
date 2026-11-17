<script setup>
import SettingsLayout from '@/Layouts/SettingsLayout.vue';
import SettingsSectionHeader from '@/Components/SettingsSectionHeader.vue';
import AppCard from '@/Components/AppCard.vue';
import AppButton from '@/Components/AppButton.vue';
import { useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useSettingsEditing } from '@/Composables/useSettingsEditing';
import { useTranslate } from '@/Composables/useTranslate';

const MAX_PHRASES = 8;

const props = defineProps({
    phrases: {
        type: Array,
        default: () => [],
    },
});

const __ = useTranslate();
const { enableEditing } = useSettingsEditing();

const newPhrase = ref('');

const form = useForm({
    phrases: [...props.phrases],
});

const trimmedPhrase = computed(() => newPhrase.value.trim());
const atLimit = computed(() => props.phrases.length >= MAX_PHRASES);

const persist = (phrases) => {
    if (form.processing) {
        return;
    }

    enableEditing();
    form.phrases = phrases;
    form.put(route('settings.waiter-call-phrases.update'), {
        preserveScroll: true,
        onSuccess: () => {
            newPhrase.value = '';
            form.defaults();
        },
    });
};

const addPhrase = () => {
    if (!trimmedPhrase.value || atLimit.value) {
        return;
    }

    persist([...props.phrases, trimmedPhrase.value]);
};

const removePhrase = (index) => {
    persist(props.phrases.filter((_, phraseIndex) => phraseIndex !== index));
};
</script>

<template>
    <SettingsLayout :title="__('Waiter call phrases')">
        <template #header>
            <SettingsSectionHeader :title="__('Waiter call phrases')">
                <span class="block">{{ __('These phrases appear as chips when the guest calls the waiter.') }}</span>
                <span class="block">{{ __('These are suggestions. You can delete any of them and create your own.') }}</span>
                <span class="block">{{ __('You can register up to 8 phrases.') }}</span>
            </SettingsSectionHeader>
        </template>

        <AppCard>
            <div v-if="phrases.length" class="divide-y divide-muted">
                <div
                    v-for="(phrase, index) in phrases"
                    :key="`${phrase}-${index}`"
                    class="flex items-center justify-between py-3"
                >
                    <span class="font-body text-sm font-medium text-ocean-deep dark:text-gray-100">{{ phrase }}</span>
                    <AppButton size="sm" variant="destructive" data-enable-editing @click="removePhrase(index)">
                        {{ __('Delete') }}
                    </AppButton>
                </div>
            </div>
            <p v-else class="text-sm text-muted-foreground">
                {{ __('Add quick phrases your guests can tap.') }}
            </p>

            <form v-if="!atLimit" class="mt-4 flex flex-col gap-2 sm:flex-row" @submit.prevent="addPhrase">
                <div class="min-w-0 flex-1">
                    <label class="block text-sm font-medium text-ocean-deep dark:text-gray-100 mb-1">{{ __('Phrase') }}</label>
                    <input
                        v-model="newPhrase"
                        type="text"
                        maxlength="60"
                        data-enable-editing
                        :placeholder="__('e.g. extra sauce')"
                        class="w-full rounded-md border border-border dark:border-gray-700 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-gray-100"
                    />
                    <p v-if="form.errors.phrases" class="mt-1 text-xs text-destructive">{{ form.errors.phrases }}</p>
                    <p v-if="form.errors['phrases.0']" class="mt-1 text-xs text-destructive">{{ form.errors['phrases.0'] }}</p>
                </div>
                <div class="flex items-end">
                    <AppButton type="submit" data-enable-editing :loading="form.processing" :disabled="!trimmedPhrase">
                        {{ __('Add phrase') }}
                    </AppButton>
                </div>
            </form>
            <p v-else class="mt-4 text-sm text-muted-foreground">{{ __('You reached the limit of 8 phrases.') }}</p>
        </AppCard>
    </SettingsLayout>
</template>
