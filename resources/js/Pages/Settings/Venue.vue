<script setup>
import SettingsLayout from '@/Layouts/SettingsLayout.vue';
import AppCard from '@/Components/AppCard.vue';
import AppButton from '@/Components/AppButton.vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    venue: Object,
});

const form = useForm({
    name: props.venue.name ?? '',
    tax_id: props.venue.tax_id ?? '',
    phone: props.venue.phone ?? '',
    whatsapp_agent: props.venue.whatsapp_agent ?? '',
    street: props.venue.street ?? '',
    number: props.venue.number ?? '',
    complement: props.venue.complement ?? '',
    neighborhood: props.venue.neighborhood ?? '',
    city: props.venue.city ?? '',
    state: props.venue.state ?? '',
    zip_code: props.venue.zip_code ?? '',
    timezone: props.venue.timezone ?? '',
    require_table: props.venue.require_table ?? false,
    require_tab: props.venue.require_tab ?? false,
    require_location: props.venue.require_location ?? false,
    call_waiter_slug: props.venue.call_waiter_slug ?? '',
    logo_url: props.venue.logo_url ?? '',
    latitude: props.venue.latitude ?? '',
    longitude: props.venue.longitude ?? '',
    require_geolocation: props.venue.require_geolocation ?? false,
});

const submit = () => {
    form.put(route('settings.venue.update'));
};
</script>

<template>
    <SettingsLayout :title="__('Venue Settings')">
        <template #header>
            <h1 class="font-heading text-2xl font-bold text-ocean-deep dark:text-gray-100">{{ __('Venue Settings') }}</h1>
        </template>

        <form @submit.prevent="submit">
            <div class="space-y-6">
                <AppCard :title="__('Basic Information')">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-ocean-deep dark:text-gray-100 mb-1">{{ __('Name') }} <span class="text-destructive">*</span></label>
                            <input
                                v-model="form.name"
                                type="text"
                                class="w-full rounded-md border border-border dark:border-gray-700 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-gray-100"
                            />
                            <p v-if="form.errors.name" class="mt-1 text-xs text-destructive">{{ form.errors.name }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-ocean-deep dark:text-gray-100 mb-1">{{ __('Tax ID') }}</label>
                            <input
                                v-model="form.tax_id"
                                type="text"
                                class="w-full rounded-md border border-border dark:border-gray-700 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-gray-100"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-ocean-deep dark:text-gray-100 mb-1">{{ __('Phone') }}</label>
                            <input
                                v-model="form.phone"
                                type="text"
                                class="w-full rounded-md border border-border dark:border-gray-700 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-gray-100"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-ocean-deep dark:text-gray-100 mb-1">{{ __('WhatsApp Agent') }}</label>
                            <input
                                v-model="form.whatsapp_agent"
                                type="text"
                                class="w-full rounded-md border border-border dark:border-gray-700 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-gray-100"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-ocean-deep dark:text-gray-100 mb-1">{{ __('Timezone') }}</label>
                            <input
                                v-model="form.timezone"
                                type="text"
                                placeholder="America/Sao_Paulo"
                                class="w-full rounded-md border border-border dark:border-gray-700 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-gray-100"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-ocean-deep dark:text-gray-100 mb-1">{{ __('Logo URL') }}</label>
                            <input
                                v-model="form.logo_url"
                                type="url"
                                class="w-full rounded-md border border-border dark:border-gray-700 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-gray-100"
                            />
                        </div>
                    </div>
                </AppCard>

                <AppCard :title="__('Address')">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-ocean-deep dark:text-gray-100 mb-1">{{ __('Street') }}</label>
                            <input
                                v-model="form.street"
                                type="text"
                                class="w-full rounded-md border border-border dark:border-gray-700 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-gray-100"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-ocean-deep dark:text-gray-100 mb-1">{{ __('Number') }}</label>
                            <input
                                v-model="form.number"
                                type="text"
                                class="w-full rounded-md border border-border dark:border-gray-700 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-gray-100"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-ocean-deep dark:text-gray-100 mb-1">{{ __('Complement') }}</label>
                            <input
                                v-model="form.complement"
                                type="text"
                                class="w-full rounded-md border border-border dark:border-gray-700 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-gray-100"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-ocean-deep dark:text-gray-100 mb-1">{{ __('Neighborhood') }}</label>
                            <input
                                v-model="form.neighborhood"
                                type="text"
                                class="w-full rounded-md border border-border dark:border-gray-700 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-gray-100"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-ocean-deep dark:text-gray-100 mb-1">{{ __('City') }}</label>
                            <input
                                v-model="form.city"
                                type="text"
                                class="w-full rounded-md border border-border dark:border-gray-700 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-gray-100"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-ocean-deep dark:text-gray-100 mb-1">{{ __('State') }}</label>
                            <input
                                v-model="form.state"
                                type="text"
                                class="w-full rounded-md border border-border dark:border-gray-700 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-gray-100"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-ocean-deep dark:text-gray-100 mb-1">{{ __('ZIP Code') }}</label>
                            <input
                                v-model="form.zip_code"
                                type="text"
                                class="w-full rounded-md border border-border dark:border-gray-700 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-gray-100"
                            />
                        </div>
                    </div>
                </AppCard>

                <AppCard :title="__('Operational Requirements')">
                    <div class="space-y-3">
                        <label class="flex cursor-pointer items-center gap-3">
                            <input v-model="form.require_table" type="checkbox" class="h-4 w-4 rounded border-border dark:border-gray-700 text-primary focus:ring-primary" />
                            <span class="text-sm text-ocean-deep dark:text-gray-100">{{ __('Require table number on orders') }}</span>
                        </label>

                        <label class="flex cursor-pointer items-center gap-3">
                            <input v-model="form.require_tab" type="checkbox" class="h-4 w-4 rounded border-border dark:border-gray-700 text-primary focus:ring-primary" />
                            <span class="text-sm text-ocean-deep dark:text-gray-100">{{ __('Require tab (customer name) on orders') }}</span>
                        </label>

                        <label class="flex cursor-pointer items-center gap-3">
                            <input v-model="form.require_location" type="checkbox" class="h-4 w-4 rounded border-border dark:border-gray-700 text-primary focus:ring-primary" />
                            <span class="text-sm text-ocean-deep dark:text-gray-100">{{ __('Require service location on orders') }}</span>
                        </label>
                    </div>
                </AppCard>

                <AppCard :title="__('Geolocation')">                    <div class="space-y-4">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-ocean-deep dark:text-gray-100 mb-1">{{ __('Latitude') }}</label>
                                <input
                                    v-model="form.latitude"
                                    type="number"
                                    step="any"
                                    placeholder="-23.5505"
                                    class="w-full rounded-md border border-border dark:border-gray-700 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-gray-100"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-ocean-deep dark:text-gray-100 mb-1">{{ __('Longitude') }}</label>
                                <input
                                    v-model="form.longitude"
                                    type="number"
                                    step="any"
                                    placeholder="-46.6333"
                                    class="w-full rounded-md border border-border dark:border-gray-700 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-gray-100"
                                />
                            </div>
                        </div>
                        <label class="flex cursor-pointer items-center gap-3">
                            <input v-model="form.require_geolocation" type="checkbox" class="h-4 w-4 rounded border-border dark:border-gray-700 text-primary focus:ring-primary" />
                            <span class="text-sm text-ocean-deep dark:text-gray-100">{{ __('Require guest geolocation to place orders') }}</span>
                        </label>
                        <p class="text-xs text-muted-foreground">{{ __('Guests will be asked for their location when accessing via QR code. Only allow orders when within 200m.') }}</p>
                    </div>
                </AppCard>

                <AppCard :title="__('Call Waiter')">
                    <div>
                        <label class="block text-sm font-medium text-ocean-deep dark:text-gray-100 mb-1">{{ __('Slug') }}</label>
                        <input
                            v-model="form.call_waiter_slug"
                            type="text"
                            class="w-full rounded-md border border-border dark:border-gray-700 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-gray-100"
                        />
                        <p class="mt-1 text-xs text-muted-foreground">{{ __('Used in the public guest QR code URL.') }}</p>
                    </div>
                </AppCard>

                <div class="flex justify-end">
                    <AppButton type="submit" :loading="form.processing">{{ __('Save Changes') }}</AppButton>
                </div>
            </div>
        </form>
    </SettingsLayout>
</template>
