<script setup>
import SettingsLayout from '@/Layouts/SettingsLayout.vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useTranslate } from '@/Composables/useTranslate';
import AppBadge from '@/Components/AppBadge.vue';
import AppButton from '@/Components/AppButton.vue';
import AppCard from '@/Components/AppCard.vue';
import AppConfirmModal from '@/Components/AppConfirmModal.vue';
import AppEmptyState from '@/Components/AppEmptyState.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';

defineProps({
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
    if (form.processing) {
        return;
    }

    form.post(route('settings.subscription.payment-methods.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};

const settingDefaultId = ref(null);
const methodPendingRemoval = ref(null);
const removing = ref(false);

const setAsDefault = (method) => {
    if (settingDefaultId.value) {
        return;
    }

    settingDefaultId.value = method.id;

    router.post(route('settings.subscription.payment-methods.default', method.id), {}, {
        preserveScroll: true,
        onFinish: () => (settingDefaultId.value = null),
    });
};

const confirmRemoval = () => {
    if (! methodPendingRemoval.value || removing.value) {
        return;
    }

    removing.value = true;

    router.delete(route('settings.subscription.payment-methods.destroy', methodPendingRemoval.value.id), {
        preserveScroll: true,
        onFinish: () => {
            removing.value = false;
            methodPendingRemoval.value = null;
        },
    });
};
</script>

<template>
    <SettingsLayout :title="__('Payment Methods')">
        <template #header>
            <h1 class="font-heading text-2xl font-bold text-ocean-deep dark:text-gray-100">{{ __('Payment Methods') }}</h1>
        </template>

        <div class="space-y-6">
            <AppCard>
                <div class="flex items-center gap-3">
                    <Link :href="route('settings.subscription.index')" class="flex items-center text-sm text-primary hover:underline">
                        <svg class="inline-block h-4 w-4 mr-1" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                        {{ __('Back') }}
                    </Link>
                    <h2 class="font-heading text-lg font-semibold">{{ __('Saved Cards') }}</h2>
                </div>
                <ul class="mt-4 space-y-3">
                    <li v-for="method in paymentMethods" :key="method.id" class="flex items-center justify-between rounded-lg border border-border p-4">
                        <div>
                            <p class="font-medium">
                                {{ method.brand }} **** {{ method.last4 }}
                                <AppBadge v-if="method.is_default" class="ml-2" variant="primary" :label="__('Default')" />
                            </p>
                            <p class="text-xs text-muted-foreground">{{ method.holder_name }} — {{ method.expiration_month }}/{{ method.expiration_year }}</p>
                        </div>
                        <div class="flex gap-2">
                            <AppButton
                                v-if="! method.is_default"
                                variant="ghost"
                                size="sm"
                                :loading="settingDefaultId === method.id"
                                @click="setAsDefault(method)"
                            >
                                {{ __('Set as default') }}
                            </AppButton>
                            <AppButton
                                variant="destructive"
                                size="sm"
                                @click="methodPendingRemoval = method"
                            >
                                {{ __('Remove') }}
                            </AppButton>
                        </div>
                    </li>
                </ul>
                <AppEmptyState
                    v-if="paymentMethods.length === 0"
                    :title="__('No payment methods saved.')"
                    :description="__('Add a card below to enable automatic billing.')"
                />
            </AppCard>

            <AppCard :title="__('Add Card')">
                <form class="mt-4 grid gap-4 sm:grid-cols-2" @submit.prevent="submit">
                    <div class="sm:col-span-2">
                        <InputLabel for="card-number" :value="__('Card Number')" />
                        <TextInput
                            id="card-number"
                            v-model="form.number"
                            type="text"
                            inputmode="numeric"
                            autocomplete="cc-number"
                            class="mt-1 block w-full"
                            placeholder="4111111111111111"
                            :aria-invalid="form.errors.number ? 'true' : undefined"
                            :aria-describedby="form.errors.number ? 'card-number-error' : undefined"
                        />
                        <InputError id="card-number-error" :message="form.errors.number" />
                    </div>
                    <div class="sm:col-span-2">
                        <InputLabel for="holder-name" :value="__('Holder Name')" />
                        <TextInput
                            id="holder-name"
                            v-model="form.holder_name"
                            type="text"
                            autocomplete="cc-name"
                            class="mt-1 block w-full"
                            :aria-invalid="form.errors.holder_name ? 'true' : undefined"
                            :aria-describedby="form.errors.holder_name ? 'holder-name-error' : undefined"
                        />
                        <InputError id="holder-name-error" :message="form.errors.holder_name" />
                    </div>
                    <div>
                        <InputLabel for="holder-document" :value="__('Holder Document')" />
                        <TextInput
                            id="holder-document"
                            v-model="form.holder_document"
                            type="text"
                            inputmode="numeric"
                            class="mt-1 block w-full"
                            :aria-invalid="form.errors.holder_document ? 'true' : undefined"
                            :aria-describedby="form.errors.holder_document ? 'holder-document-error' : undefined"
                        />
                        <InputError id="holder-document-error" :message="form.errors.holder_document" />
                    </div>
                    <div>
                        <InputLabel for="holder-email" :value="__('Holder Email')" />
                        <TextInput
                            id="holder-email"
                            v-model="form.holder_email"
                            type="email"
                            autocomplete="email"
                            class="mt-1 block w-full"
                            :aria-invalid="form.errors.holder_email ? 'true' : undefined"
                            :aria-describedby="form.errors.holder_email ? 'holder-email-error' : undefined"
                        />
                        <InputError id="holder-email-error" :message="form.errors.holder_email" />
                    </div>
                    <div>
                        <InputLabel for="holder-phone" :value="__('Holder Phone')" />
                        <TextInput
                            id="holder-phone"
                            v-model="form.holder_phone"
                            type="tel"
                            autocomplete="tel"
                            class="mt-1 block w-full"
                            :aria-invalid="form.errors.holder_phone ? 'true' : undefined"
                            :aria-describedby="form.errors.holder_phone ? 'holder-phone-error' : undefined"
                        />
                        <InputError id="holder-phone-error" :message="form.errors.holder_phone" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="holder-postal-code" :value="__('Postal Code')" />
                            <TextInput
                                id="holder-postal-code"
                                v-model="form.holder_postal_code"
                                type="text"
                                autocomplete="postal-code"
                                class="mt-1 block w-full"
                                :aria-invalid="form.errors.holder_postal_code ? 'true' : undefined"
                                :aria-describedby="form.errors.holder_postal_code ? 'holder-postal-code-error' : undefined"
                            />
                            <InputError id="holder-postal-code-error" :message="form.errors.holder_postal_code" />
                        </div>
                        <div>
                            <InputLabel for="holder-address-number" :value="__('Address Number')" />
                            <TextInput
                                id="holder-address-number"
                                v-model="form.holder_address_number"
                                type="text"
                                class="mt-1 block w-full"
                                :aria-invalid="form.errors.holder_address_number ? 'true' : undefined"
                                :aria-describedby="form.errors.holder_address_number ? 'holder-address-number-error' : undefined"
                            />
                            <InputError id="holder-address-number-error" :message="form.errors.holder_address_number" />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="expiration-month" :value="__('Month')" />
                            <TextInput
                                id="expiration-month"
                                v-model="form.expiration_month"
                                type="number"
                                min="1"
                                max="12"
                                autocomplete="cc-exp-month"
                                class="mt-1 block w-full"
                                :aria-invalid="form.errors.expiration_month ? 'true' : undefined"
                                :aria-describedby="form.errors.expiration_month ? 'expiration-month-error' : undefined"
                            />
                            <InputError id="expiration-month-error" :message="form.errors.expiration_month" />
                        </div>
                        <div>
                            <InputLabel for="expiration-year" :value="__('Year')" />
                            <TextInput
                                id="expiration-year"
                                v-model="form.expiration_year"
                                type="number"
                                :min="new Date().getFullYear()"
                                autocomplete="cc-exp-year"
                                class="mt-1 block w-full"
                                :aria-invalid="form.errors.expiration_year ? 'true' : undefined"
                                :aria-describedby="form.errors.expiration_year ? 'expiration-year-error' : undefined"
                            />
                            <InputError id="expiration-year-error" :message="form.errors.expiration_year" />
                        </div>
                    </div>
                    <div>
                        <InputLabel for="card-cvv" :value="__('CVV')" />
                        <TextInput
                            id="card-cvv"
                            v-model="form.cvv"
                            type="text"
                            inputmode="numeric"
                            autocomplete="cc-csc"
                            class="mt-1 block w-full"
                            :aria-invalid="form.errors.cvv ? 'true' : undefined"
                            :aria-describedby="form.errors.cvv ? 'card-cvv-error' : undefined"
                        />
                        <InputError id="card-cvv-error" :message="form.errors.cvv" />
                    </div>
                    <div class="sm:col-span-2">
                        <AppButton type="submit" :loading="form.processing" :disabled="form.processing">
                            {{ __('Save Card') }}
                        </AppButton>
                    </div>
                </form>
            </AppCard>
        </div>

        <AppConfirmModal
            :show="methodPendingRemoval !== null"
            :title="__('Remove card')"
            :message="__('This card will be removed from your account and can no longer be used to pay invoices.')"
            :confirm-label="__('Remove')"
            variant="destructive"
            :loading="removing"
            @confirm="confirmRemoval"
            @cancel="methodPendingRemoval = null"
        />
    </SettingsLayout>
</template>
