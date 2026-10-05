<script setup>
import SettingsLayout from '@/Layouts/SettingsLayout.vue';
import AppCard from '@/Components/AppCard.vue';
import { useAutosaveForm } from '@/Composables/useAutosaveForm';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    settings: Object,
});

const form = useForm({
    cover_charge: props.settings?.cover_charge ?? '',
    service_fee_percent: props.settings?.service_fee_percent ?? '',
    table_count: props.settings?.table_count ?? '',
});

const submit = () => {
    form.put(route('settings.general.update'), {
        preserveScroll: true,
    });
};

useAutosaveForm(form, submit);
</script>

<template>
    <SettingsLayout :title="__('General Settings')">
        <template #header>
            <h1 class="font-heading text-2xl font-bold text-ocean-deep dark:text-gray-100">{{ __('General Settings') }}</h1>
        </template>

        <form @submit.prevent="submit">
            <AppCard :title="__('Financial & Capacity')">
                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <label class="block text-sm font-medium text-ocean-deep dark:text-gray-100 mb-1">{{ __('Cover Charge (R$)') }}</label>
                        <input
                            v-model="form.cover_charge"
                            type="number"
                            step="0.01"
                            min="0"
                            class="w-full rounded-md border border-border dark:border-gray-700 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-gray-100"
                        />
                        <p class="mt-1 text-xs text-muted-foreground dark:text-gray-400">{{ __('Enter the establishment cover charge or entry fee if it applies equally to all customers.') }}</p>
                        <p v-if="form.errors.cover_charge" class="mt-1 text-xs text-destructive">{{ form.errors.cover_charge }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-ocean-deep dark:text-gray-100 mb-1">{{ __('Service Fee (%)') }}</label>
                        <div class="relative">
                            <input
                                v-model="form.service_fee_percent"
                                type="number"
                                step="0.01"
                                min="0"
                                max="100"
                                class="w-full rounded-md border border-border dark:border-gray-700 px-3 py-2 pr-8 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-gray-100"
                            />
                            <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-muted-foreground dark:text-gray-400">%</span>
                        </div>
                        <p v-if="form.errors.service_fee_percent" class="mt-1 text-xs text-destructive">{{ form.errors.service_fee_percent }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-ocean-deep dark:text-gray-100 mb-1">{{ __('Table Count') }}</label>
                        <input
                            v-model="form.table_count"
                            type="number"
                            min="0"
                            class="w-full rounded-md border border-border dark:border-gray-700 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-gray-100"
                        />
                        <p v-if="form.errors.table_count" class="mt-1 text-xs text-destructive">{{ form.errors.table_count }}</p>
                    </div>
                </div>
            </AppCard>
        </form>
    </SettingsLayout>
</template>
