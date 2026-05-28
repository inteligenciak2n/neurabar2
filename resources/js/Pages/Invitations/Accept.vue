<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    token: String,
    venueName: String,
    role: String,
    invitedBy: String,
});

const form = useForm({});

const accept = () => {
    form.post(route('invitations.accept', props.token));
};
</script>

<template>
    <GuestLayout :title="__('Accept Invitation')">
        <Head :title="__('Accept Invitation')" />

        <div class="bg-white shadow-card rounded-xl p-8">
            <div class="text-center mb-6">
                <h1 class="text-2xl font-heading font-bold text-ocean-deep">
                    {{ __('You have been invited!') }}
                </h1>
                <p class="text-muted-foreground mt-2">
                    <span v-if="invitedBy">
                        <strong>{{ invitedBy }}</strong> {{ __('invited you to collaborate on') }}
                    </span>
                    <span v-else>{{ __('You have been invited to collaborate on') }}</span>
                    <strong> {{ venueName }}</strong>.
                </p>
                <p class="mt-2 text-sm text-muted-foreground">
                    {{ __('Role') }}: <span class="font-semibold text-primary">{{ role }}</span>
                </p>
            </div>

            <form @submit.prevent="accept">
                <PrimaryButton
                    class="w-full justify-center"
                    :class="{ 'opacity-50': form.processing }"
                    :disabled="form.processing"
                >
                    {{ __('Accept Invitation') }}
                </PrimaryButton>
            </form>
        </div>
    </GuestLayout>
</template>
