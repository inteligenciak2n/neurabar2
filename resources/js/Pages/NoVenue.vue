<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    userName: String,
});

const form = useForm({
    name: '',
});

const submit = () => {
    form.post(route('no-venue.store'));
};
</script>

<template>
    <GuestLayout :title="__('Create a new Venue')">
        <Head :title="__('Create your first Venue')" />

        <div class="bg-white shadow-card rounded-xl p-8">
            <div class="text-center mb-6">
                <h1 class="text-2xl font-heading font-bold text-ocean-deep">
                    {{ __('Welcome, :name!', { name: userName }) }}
                </h1>
                <p class="text-muted-foreground mt-2">
                    {{ __('You do not have a venue registered yet. Create your first venue to start operating.') }}
                </p>
            </div>

            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <InputLabel for="name" :value="__('Venue Name')" />
                    <TextInput
                        id="name"
                        v-model="form.name"
                        type="text"
                        class="mt-1 block w-full"
                        :placeholder="__('Ex: John\'s Bar')"
                        required
                        autofocus
                    />
                    <InputError class="mt-2" :message="form.errors.name" />
                </div>

                <PrimaryButton
                    class="w-full justify-center"
                    :class="{ 'opacity-50': form.processing }"
                    :disabled="form.processing"
                >
                    {{ __('Create Venue') }}
                </PrimaryButton>
            </form>
        </div>
    </GuestLayout>
</template>
