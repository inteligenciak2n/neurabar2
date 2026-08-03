<script setup>
import SettingsLayout from '@/Layouts/SettingsLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useTranslate } from '@/Composables/useTranslate';
import AppButton from '@/Components/AppButton.vue';
import AppCard from '@/Components/AppCard.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';

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

const addressFields = () => [
    { name: 'street', label: __('Street'), autocomplete: 'address-line1', wide: true },
    { name: 'number', label: __('Number'), autocomplete: 'address-line2' },
    { name: 'complement', label: __('Complement'), autocomplete: 'address-line3' },
    { name: 'neighborhood', label: __('Neighborhood'), autocomplete: 'address-level3' },
    { name: 'city', label: __('City'), autocomplete: 'address-level2' },
    { name: 'state', label: __('State'), autocomplete: 'address-level1' },
    { name: 'zip_code', label: __('ZIP Code'), autocomplete: 'postal-code' },
    { name: 'country', label: __('Country'), autocomplete: 'country-name' },
];

const corporationFields = computed(() => [
    ...addressFields(),
    { name: 'billing_tax_regime', label: __('Tax Regime') },
    { name: 'billing_state_registration', label: __('State Registration') },
]);

const venueFields = computed(() => [
    ...addressFields(),
    { name: 'billing_email', label: __('Billing Email'), type: 'email', autocomplete: 'email' },
    { name: 'billing_phone', label: __('Billing Phone'), type: 'tel', autocomplete: 'tel' },
]);

const submitCorporation = () => {
    if (corporationForm.processing) {
        return;
    }

    corporationForm.put(route('settings.subscription.billing-address.update', 'corporation'), {
        preserveScroll: true,
    });
};

const submitVenue = () => {
    if (venueForm.processing) {
        return;
    }

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

        <AppCard>
            <Link :href="route('settings.subscription.index')" class="flex items-center text-sm text-primary hover:underline mb-3">
                <svg class="inline-block h-4 w-4 mr-1" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
                {{ __('Back') }}
            </Link>
            <div class="flex gap-4 border-b" role="tablist">
                <button
                    id="billing-tab-corporation"
                    type="button"
                    role="tab"
                    aria-controls="billing-panel-corporation"
                    :aria-selected="activeTab === 'corporation'"
                    class="pb-2 text-sm font-medium"
                    :class="activeTab === 'corporation' ? 'border-b-2 border-primary text-primary' : 'text-muted-foreground'"
                    @click="activeTab = 'corporation'"
                >
                    {{ corporation.name }}
                </button>
                <button
                    id="billing-tab-venue"
                    type="button"
                    role="tab"
                    aria-controls="billing-panel-venue"
                    :aria-selected="activeTab === 'venue'"
                    class="pb-2 text-sm font-medium"
                    :class="activeTab === 'venue' ? 'border-b-2 border-primary text-primary' : 'text-muted-foreground'"
                    @click="activeTab = 'venue'"
                >
                    {{ venue.name }}
                </button>
            </div>

            <form
                v-if="activeTab === 'corporation'"
                id="billing-panel-corporation"
                role="tabpanel"
                aria-labelledby="billing-tab-corporation"
                class="mt-4 grid gap-4 sm:grid-cols-2"
                @submit.prevent="submitCorporation"
            >
                <div v-for="field in corporationFields" :key="field.name" :class="field.wide ? 'sm:col-span-2' : ''">
                    <InputLabel :for="`corporation-${field.name}`" :value="field.label" />
                    <TextInput
                        :id="`corporation-${field.name}`"
                        v-model="corporationForm[field.name]"
                        :type="field.type ?? 'text'"
                        :autocomplete="field.autocomplete"
                        class="mt-1 block w-full"
                        :aria-invalid="corporationForm.errors[field.name] ? 'true' : undefined"
                        :aria-describedby="corporationForm.errors[field.name] ? `corporation-${field.name}-error` : undefined"
                    />
                    <InputError :id="`corporation-${field.name}-error`" :message="corporationForm.errors[field.name]" />
                </div>
                <div class="sm:col-span-2">
                    <AppButton type="submit" :loading="corporationForm.processing" :disabled="corporationForm.processing">
                        {{ __('Save') }}
                    </AppButton>
                </div>
            </form>

            <form
                v-else
                id="billing-panel-venue"
                role="tabpanel"
                aria-labelledby="billing-tab-venue"
                class="mt-4 grid gap-4 sm:grid-cols-2"
                @submit.prevent="submitVenue"
            >
                <div v-for="field in venueFields" :key="field.name" :class="field.wide ? 'sm:col-span-2' : ''">
                    <InputLabel :for="`venue-${field.name}`" :value="field.label" />
                    <TextInput
                        :id="`venue-${field.name}`"
                        v-model="venueForm[field.name]"
                        :type="field.type ?? 'text'"
                        :autocomplete="field.autocomplete"
                        class="mt-1 block w-full"
                        :aria-invalid="venueForm.errors[field.name] ? 'true' : undefined"
                        :aria-describedby="venueForm.errors[field.name] ? `venue-${field.name}-error` : undefined"
                    />
                    <InputError :id="`venue-${field.name}-error`" :message="venueForm.errors[field.name]" />
                </div>
                <div class="sm:col-span-2">
                    <AppButton type="submit" :loading="venueForm.processing" :disabled="venueForm.processing">
                        {{ __('Save') }}
                    </AppButton>
                </div>
            </form>
        </AppCard>
    </SettingsLayout>
</template>
