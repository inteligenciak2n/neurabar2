<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Checkbox from '@/Components/Checkbox.vue';

const props = defineProps({
    userName: String,
    corporationName: String,
    venueCount: {
        type: Number,
        default: 1,
    },
});

const buildVenue = () => ({
    skip: false,
    name: '',
    tax_id: '',
    phone: '',
    city: '',
    state: '',
    timezone: 'America/Sao_Paulo',
});

const form = useForm({
    name: props.corporationName,
    tax_id: '',
    email: '',
    contact_phone: '',
    venues: Array.from({ length: props.venueCount }, buildVenue),
});

const submit = () => {
    form.post(route('onboarding.corporation.store'));
};
</script>

<template>
    <GuestLayout :title="__('Dados da empresa')">
        <Head :title="__('Dados da empresa')" />

        <div class="bg-white shadow-card rounded-xl p-8">
            <div class="text-center mb-6">
                <h1 class="text-2xl font-heading font-bold text-ocean-deep">
                    {{ __('Quase lá,') }} {{ userName }}!
                </h1>
                <p class="text-muted-foreground mt-2">
                    {{ __('Conte um pouco sobre sua empresa e seus pontos de venda.') }}
                </p>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <div class="space-y-4">
                    <div>
                        <InputLabel for="name" :value="__('Nome da empresa')" />
                        <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" required />
                        <InputError class="mt-1.5" :message="form.errors.name" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="tax_id" :value="__('CNPJ/CPF')" />
                            <TextInput id="tax_id" v-model="form.tax_id" type="text" class="mt-1 block w-full" />
                            <InputError class="mt-1.5" :message="form.errors.tax_id" />
                        </div>
                        <div>
                            <InputLabel for="contact_phone" :value="__('Telefone')" />
                            <TextInput id="contact_phone" v-model="form.contact_phone" type="text" class="mt-1 block w-full" />
                            <InputError class="mt-1.5" :message="form.errors.contact_phone" />
                        </div>
                    </div>
                    <div>
                        <InputLabel for="email" :value="__('Email da empresa')" />
                        <TextInput id="email" v-model="form.email" type="email" class="mt-1 block w-full" />
                        <InputError class="mt-1.5" :message="form.errors.email" />
                    </div>
                </div>

                <div class="space-y-4">
                    <InputLabel :value="__('Seus pontos de venda')" />

                    <div
                        v-for="(venue, index) in form.venues"
                        :key="index"
                        class="rounded-md border border-border p-4 space-y-3"
                    >
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-ocean-deep">{{ __('Venue') }} {{ index + 1 }}</span>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <Checkbox v-model:checked="venue.skip" />
                                <span class="text-xs text-muted-foreground">{{ __('Preencher depois') }}</span>
                            </label>
                        </div>

                        <template v-if="!venue.skip">
                            <div>
                                <InputLabel :value="__('Nome')" />
                                <TextInput v-model="venue.name" type="text" class="mt-1 block w-full" />
                                <InputError class="mt-1.5" :message="form.errors[`venues.${index}.name`]" />
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <InputLabel :value="__('Cidade')" />
                                    <TextInput v-model="venue.city" type="text" class="mt-1 block w-full" />
                                </div>
                                <div>
                                    <InputLabel :value="__('Estado')" />
                                    <TextInput v-model="venue.state" type="text" maxlength="2" class="mt-1 block w-full" />
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <InputLabel :value="__('CNPJ/CPF')" />
                                    <TextInput v-model="venue.tax_id" type="text" class="mt-1 block w-full" />
                                </div>
                                <div>
                                    <InputLabel :value="__('Telefone')" />
                                    <TextInput v-model="venue.phone" type="text" class="mt-1 block w-full" />
                                </div>
                            </div>
                        </template>
                        <p v-else class="text-xs text-muted-foreground">
                            {{ __('Vamos criar essa venue com dados temporários. Você pode editar depois em Configurações.') }}
                        </p>
                    </div>
                </div>

                <PrimaryButton
                    class="w-full justify-center"
                    :class="{ 'opacity-50': form.processing }"
                    :disabled="form.processing"
                >
                    {{ __('Começar meu período de teste') }}
                </PrimaryButton>
            </form>
        </div>
    </GuestLayout>
</template>
