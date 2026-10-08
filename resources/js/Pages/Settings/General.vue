<script setup>
import SettingsLayout from '@/Layouts/SettingsLayout.vue';
import SettingsSectionHeader from '@/Components/SettingsSectionHeader.vue';
import AppCard from '@/Components/AppCard.vue';
import { useAutosaveForm } from '@/Composables/useAutosaveForm';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    settings: Object,
    venue: Object,
});

const form = useForm({
    cover_charge: props.settings?.cover_charge ?? '',
    service_fee_percent: props.settings?.service_fee_percent ?? '',
    table_count: props.settings?.table_count ?? '',
    require_table: props.venue?.require_table ?? false,
    require_tab: props.venue?.require_tab ?? false,
    require_location: props.venue?.require_location ?? false,
    latitude: props.venue?.latitude ?? '',
    longitude: props.venue?.longitude ?? '',
    require_geolocation: props.venue?.require_geolocation ?? false,
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
            <SettingsSectionHeader :title="__('General Settings')">
                <span class="block">{{ __('Cover charge and service fee') }}</span>
                <span class="block">{{ __('and how many tables you have') }}</span>
            </SettingsSectionHeader>
        </template>

        <form class="space-y-6" @submit.prevent="submit">
            <AppCard :title="__('Fees and services charged to the customer')">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-ocean-deep dark:text-gray-100">{{ __('Cover Charge (R$)') }}</label>
                        <input
                            v-model="form.cover_charge"
                            type="number"
                            step="0.01"
                            min="0"
                            class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:border-gray-700 dark:bg-gray-700 dark:text-gray-100"
                        />
                        <p class="mt-1 text-xs text-muted-foreground dark:text-gray-400">{{ __('Enter the establishment cover charge or entry fee if it applies equally to all customers.') }}</p>
                        <p v-if="form.errors.cover_charge" class="mt-1 text-xs text-destructive">{{ form.errors.cover_charge }}</p>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-ocean-deep dark:text-gray-100">{{ __('Service Fee (%)') }}</label>
                        <div class="relative">
                            <input
                                v-model="form.service_fee_percent"
                                type="number"
                                step="0.01"
                                min="0"
                                max="100"
                                class="w-full rounded-md border border-border px-3 py-2 pr-8 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:border-gray-700 dark:bg-gray-700 dark:text-gray-100"
                            />
                            <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-muted-foreground dark:text-gray-400">%</span>
                        </div>
                        <p class="mt-1 text-xs text-muted-foreground dark:text-gray-400">{{ __('Fee charged on the products from the menu.') }}</p>
                        <p v-if="form.errors.service_fee_percent" class="mt-1 text-xs text-destructive">{{ form.errors.service_fee_percent }}</p>
                    </div>
                </div>
            </AppCard>

            <AppCard :title="__('Maximum number of tables')">
                <div>
                    <div class="max-w-xs">
                        <label class="mb-1 block text-sm font-medium text-ocean-deep dark:text-gray-100">{{ __('Table Count') }}</label>
                        <input
                            v-model="form.table_count"
                            type="number"
                            min="0"
                            class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:border-gray-700 dark:bg-gray-700 dark:text-gray-100"
                        />
                    </div>
                    <p class="mt-1 max-w-md text-xs text-muted-foreground dark:text-gray-400">{{ __('This information helps the waiter find the table when taking the order.') }}</p>
                    <p v-if="form.errors.table_count" class="mt-1 text-xs text-destructive">{{ form.errors.table_count }}</p>
                </div>
            </AppCard>

            <AppCard :title="__('Operational Requirements')">
                <div class="space-y-3">
                    <label class="flex cursor-pointer items-center gap-3">
                        <input v-model="form.require_table" type="checkbox" class="h-4 w-4 rounded border-border text-primary focus:ring-primary dark:border-gray-700" />
                        <span class="text-sm text-ocean-deep dark:text-gray-100">{{ __('Require table number on orders') }}</span>
                    </label>

                    <label class="flex cursor-pointer items-center gap-3">
                        <input v-model="form.require_tab" type="checkbox" class="h-4 w-4 rounded border-border text-primary focus:ring-primary dark:border-gray-700" />
                        <span class="text-sm text-ocean-deep dark:text-gray-100">{{ __('Require tab (customer name) on orders') }}</span>
                    </label>

                    <label class="flex cursor-pointer items-center gap-3">
                        <input v-model="form.require_location" type="checkbox" class="h-4 w-4 rounded border-border text-primary focus:ring-primary dark:border-gray-700" />
                        <span class="text-sm text-ocean-deep dark:text-gray-100">{{ __('Require service location on orders') }}</span>
                    </label>
                </div>
            </AppCard>

            <AppCard :title="__('Geolocation')">
                <div class="space-y-4">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-ocean-deep dark:text-gray-100">{{ __('Latitude') }}</label>
                            <input
                                v-model="form.latitude"
                                type="number"
                                step="any"
                                placeholder="-23.5505"
                                class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:border-gray-700 dark:bg-gray-700 dark:text-gray-100"
                            />
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-ocean-deep dark:text-gray-100">{{ __('Longitude') }}</label>
                            <input
                                v-model="form.longitude"
                                type="number"
                                step="any"
                                placeholder="-46.6333"
                                class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:border-gray-700 dark:bg-gray-700 dark:text-gray-100"
                            />
                        </div>
                    </div>
                    <label class="flex cursor-pointer items-center gap-3">
                        <input v-model="form.require_geolocation" type="checkbox" class="h-4 w-4 rounded border-border text-primary focus:ring-primary dark:border-gray-700" />
                        <span class="text-sm text-ocean-deep dark:text-gray-100">{{ __('Require guest geolocation to place orders') }}</span>
                    </label>
                    <p class="text-xs text-muted-foreground">{{ __('Guests will be asked for their location when accessing via QR code. Only allow orders when within 200m.') }}</p>
                </div>
            </AppCard>
        </form>
    </SettingsLayout>
</template>
