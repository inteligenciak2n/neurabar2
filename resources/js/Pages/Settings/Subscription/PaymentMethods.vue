<script setup>
import SettingsLayout from '@/Layouts/SettingsLayout.vue';
import { useForm } from '@inertiajs/vue3';
import { useTranslate } from '@/Composables/useTranslate';
import InputError from '@/Components/InputError.vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    paymentMethods: Array,
});

const __ = useTranslate();

const form = useForm({
    number: '',
    holder_name: '',
    holder_document: '',
    holder_email: '',
    holder_postal_code: '',
    holder_address_number: '',
    holder_phone: '',
    expiration_month: '',
    expiration_year: '',
    cvv: '',
});

const submit = () => {
    form.post(route('settings.subscription.payment-methods.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <SettingsLayout :title="__('Payment Methods')">
        <template #header>
            <h1 class="font-heading text-2xl font-bold text-ocean-deep dark:text-gray-100">{{ __('Payment Methods') }}</h1>
        </template>

        <div class="space-y-6">
            <div class="rounded-xl border border-border bg-white p-6 shadow-card">
                <div class="flex items-center gap-3">
                    <Link :href="route('settings.subscription.index')" class="flex items-center text-sm text-primary hover:underline">
                        <svg class="inline-block h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                        {{ __('Back') }}
                    </Link>
                    <h2 class="font-heading text-lg font-semibold">{{ __('Saved Cards') }}</h2>
                </div>
                <div class="mt-4 space-y-3">
                    <div v-for="method in paymentMethods" :key="method.id" class="flex items-center justify-between rounded-lg border border-border p-4">
                        <div>
                            <p class="font-medium">
                                {{ method.brand }} **** {{ method.last4 }}
                                <span v-if="method.is_default" class="ml-2 rounded-full bg-primary/10 px-2 py-0.5 text-xs text-primary">{{ __('Default') }}</span>
                            </p>
                            <p class="text-xs text-muted-foreground">{{ method.holder_name }} — {{ method.expiration_month }}/{{ method.expiration_year }}</p>
                        </div>
                        <div class="flex gap-2">
                            <button
                                v-if="! method.is_default"
                                type="button"
                                class="text-sm text-primary hover:underline"
                                @click="$inertia.post(route('settings.subscription.payment-methods.default', method.id))"
                            >
                                {{ __('Set as default') }}
                            </button>
                            <button
                                type="button"
                                class="text-sm text-red-600 hover:underline"
                                @click="$inertia.delete(route('settings.subscription.payment-methods.destroy', method.id))"
                            >
                                {{ __('Remove') }}
                            </button>
                        </div>
                    </div>
                    <p v-if="paymentMethods.length === 0" class="text-sm text-muted-foreground">{{ __('No payment methods saved.') }}</p>
                </div>
            </div>

            <div class="rounded-xl border border-border bg-white p-6 shadow-card">
                <h2 class="font-heading text-lg font-semibold">{{ __('Add Card') }}</h2>
                <form class="mt-4 grid gap-4 sm:grid-cols-2" @submit.prevent="submit">
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium">{{ __('Card Number') }}</label>
                        <input v-model="form.number" type="text" class="mt-1 block w-full rounded-md border-border shadow-sm" placeholder="4111111111111111">
                        <InputError :message="form.errors.number" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium">{{ __('Holder Name') }}</label>
                        <input v-model="form.holder_name" type="text" class="mt-1 block w-full rounded-md border-border shadow-sm">
                        <InputError :message="form.errors.holder_name" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium">{{ __('Holder Document') }}</label>
                        <input v-model="form.holder_document" type="text" class="mt-1 block w-full rounded-md border-border shadow-sm">
                        <InputError :message="form.errors.holder_document" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium">{{ __('Holder Email') }}</label>
                        <input v-model="form.holder_email" type="email" class="mt-1 block w-full rounded-md border-border shadow-sm">
                        <InputError :message="form.errors.holder_email" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium">{{ __('Holder Phone') }}</label>
                        <input v-model="form.holder_phone" type="tel" class="mt-1 block w-full rounded-md border-border shadow-sm">
                        <InputError :message="form.errors.holder_phone" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium">{{ __('Postal Code') }}</label>
                            <input v-model="form.holder_postal_code" type="text" class="mt-1 block w-full rounded-md border-border shadow-sm">
                            <InputError :message="form.errors.holder_postal_code" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium">{{ __('Address Number') }}</label>
                            <input v-model="form.holder_address_number" type="text" class="mt-1 block w-full rounded-md border-border shadow-sm">
                            <InputError :message="form.errors.holder_address_number" />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium">{{ __('Month') }}</label>
                            <input v-model="form.expiration_month" type="number" min="1" max="12" class="mt-1 block w-full rounded-md border-border shadow-sm">
                            <InputError :message="form.errors.expiration_month" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium">{{ __('Year') }}</label>
                            <input v-model="form.expiration_year" type="number" :min="new Date().getFullYear()" class="mt-1 block w-full rounded-md border-border shadow-sm">
                            <InputError :message="form.errors.expiration_year" />
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">{{ __('CVV') }}</label>
                        <input v-model="form.cvv" type="text" class="mt-1 block w-full rounded-md border-border shadow-sm">
                        <InputError :message="form.errors.cvv" />
                    </div>
                    <div class="sm:col-span-2">
                        <button type="submit" class="rounded-md bg-primary px-4 py-2 text-sm text-white" :disabled="form.processing">
                            {{ __('Save Card') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </SettingsLayout>
</template>
