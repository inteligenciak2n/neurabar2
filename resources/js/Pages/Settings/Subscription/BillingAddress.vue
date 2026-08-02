<script setup>
import SettingsLayout from '@/Layouts/SettingsLayout.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useTranslate } from '@/Composables/useTranslate';
import InputError from '@/Components/InputError.vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    corporation: Object,
    venue: Object,
});

const __ = useTranslate();
const activeTab = ref('corporation');

const corporationForm = useForm({
    street: props.corporation.billing_address?.street ?? '',
    number: props.corporation.billing_address?.number ?? '',
    complement: props.corporation.billing_address?.complement ?? '',
    neighborhood: props.corporation.billing_address?.neighborhood ?? '',
    city: props.corporation.billing_address?.city ?? '',
    state: props.corporation.billing_address?.state ?? '',
    zip_code: props.corporation.billing_address?.zip_code ?? '',
    country: props.corporation.billing_address?.country ?? 'BR',
    billing_tax_regime: props.corporation.billing_tax_regime ?? '',
    billing_state_registration: props.corporation.billing_state_registration ?? '',
});

const venueForm = useForm({
    street: props.venue.billing_address?.street ?? '',
    number: props.venue.billing_address?.number ?? '',
    complement: props.venue.billing_address?.complement ?? '',
    neighborhood: props.venue.billing_address?.neighborhood ?? '',
    city: props.venue.billing_address?.city ?? '',
    state: props.venue.billing_address?.state ?? '',
    zip_code: props.venue.billing_address?.zip_code ?? '',
    country: props.venue.billing_address?.country ?? 'BR',
    billing_email: props.venue.billing_email ?? '',
    billing_phone: props.venue.billing_phone ?? '',
});

const submitCorporation = () => {
    corporationForm.put(route('settings.subscription.billing-address.update', 'corporation'), {
        preserveScroll: true,
    });
};

const submitVenue = () => {
    venueForm.put(route('settings.subscription.billing-address.update', 'venue'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <SettingsLayout :title="__('Billing Address')">
        <template #header>
            <h1 class="font-heading text-2xl font-bold text-ocean-deep dark:text-gray-100">{{ __('Billing Address') }}</h1>
        </template>

        <div class="rounded-xl border border-border bg-white p-6 shadow-card">
            <Link :href="route('settings.subscription.index')" class="flex items-center text-sm text-primary hover:underline mb-3">
                <svg class="inline-block h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
                {{ __('Back') }}
            </Link>
            <div class="flex gap-4 border-b">
                <button
                    type="button"
                    class="pb-2 text-sm font-medium"
                    :class="activeTab === 'corporation' ? 'border-b-2 border-primary text-primary' : 'text-muted-foreground'"
                    @click="activeTab = 'corporation'"
                >
                    {{ corporation.name }}
                </button>
                <button
                    type="button"
                    class="pb-2 text-sm font-medium"
                    :class="activeTab === 'venue' ? 'border-b-2 border-primary text-primary' : 'text-muted-foreground'"
                    @click="activeTab = 'venue'"
                >
                    {{ venue.name }}
                </button>
            </div>

            <form v-if="activeTab === 'corporation'" class="mt-4 grid gap-4 sm:grid-cols-2" @submit.prevent="submitCorporation">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium">{{ __('Street') }}</label>
                    <input v-model="corporationForm.street" type="text" class="mt-1 block w-full rounded-md border-border shadow-sm">
                    <InputError :message="corporationForm.errors.street" />
                </div>
                <div>
                    <label class="block text-sm font-medium">{{ __('Number') }}</label>
                    <input v-model="corporationForm.number" type="text" class="mt-1 block w-full rounded-md border-border shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium">{{ __('Complement') }}</label>
                    <input v-model="corporationForm.complement" type="text" class="mt-1 block w-full rounded-md border-border shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium">{{ __('Neighborhood') }}</label>
                    <input v-model="corporationForm.neighborhood" type="text" class="mt-1 block w-full rounded-md border-border shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium">{{ __('City') }}</label>
                    <input v-model="corporationForm.city" type="text" class="mt-1 block w-full rounded-md border-border shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium">{{ __('State') }}</label>
                    <input v-model="corporationForm.state" type="text" class="mt-1 block w-full rounded-md border-border shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium">{{ __('ZIP Code') }}</label>
                    <input v-model="corporationForm.zip_code" type="text" class="mt-1 block w-full rounded-md border-border shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium">{{ __('Country') }}</label>
                    <input v-model="corporationForm.country" type="text" class="mt-1 block w-full rounded-md border-border shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium">{{ __('Tax Regime') }}</label>
                    <input v-model="corporationForm.billing_tax_regime" type="text" class="mt-1 block w-full rounded-md border-border shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium">{{ __('State Registration') }}</label>
                    <input v-model="corporationForm.billing_state_registration" type="text" class="mt-1 block w-full rounded-md border-border shadow-sm">
                </div>
                <div class="sm:col-span-2">
                    <button type="submit" class="rounded-md bg-primary px-4 py-2 text-sm text-white" :disabled="corporationForm.processing">
                        {{ __('Save') }}
                    </button>
                </div>
            </form>

            <form v-else class="mt-4 grid gap-4 sm:grid-cols-2" @submit.prevent="submitVenue">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium">{{ __('Street') }}</label>
                    <input v-model="venueForm.street" type="text" class="mt-1 block w-full rounded-md border-border shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium">{{ __('Number') }}</label>
                    <input v-model="venueForm.number" type="text" class="mt-1 block w-full rounded-md border-border shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium">{{ __('Complement') }}</label>
                    <input v-model="venueForm.complement" type="text" class="mt-1 block w-full rounded-md border-border shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium">{{ __('Neighborhood') }}</label>
                    <input v-model="venueForm.neighborhood" type="text" class="mt-1 block w-full rounded-md border-border shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium">{{ __('City') }}</label>
                    <input v-model="venueForm.city" type="text" class="mt-1 block w-full rounded-md border-border shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium">{{ __('State') }}</label>
                    <input v-model="venueForm.state" type="text" class="mt-1 block w-full rounded-md border-border shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium">{{ __('ZIP Code') }}</label>
                    <input v-model="venueForm.zip_code" type="text" class="mt-1 block w-full rounded-md border-border shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium">{{ __('Country') }}</label>
                    <input v-model="venueForm.country" type="text" class="mt-1 block w-full rounded-md border-border shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium">{{ __('Billing Email') }}</label>
                    <input v-model="venueForm.billing_email" type="email" class="mt-1 block w-full rounded-md border-border shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium">{{ __('Billing Phone') }}</label>
                    <input v-model="venueForm.billing_phone" type="text" class="mt-1 block w-full rounded-md border-border shadow-sm">
                </div>
                <div class="sm:col-span-2">
                    <button type="submit" class="rounded-md bg-primary px-4 py-2 text-sm text-white" :disabled="venueForm.processing">
                        {{ __('Save') }}
                    </button>
                </div>
            </form>
        </div>
    </SettingsLayout>
</template>
