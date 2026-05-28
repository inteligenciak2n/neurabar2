<script setup>
import { Head } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';

const props = defineProps({
    venueName: String,
});
</script>

<template>
    <GuestLayout :title="__('Expired Invitation')">
        <Head :title="__('Expired Invitation')" />

        <div class="bg-white shadow-card rounded-xl p-8 text-center">
            <h1 class="text-2xl font-heading font-bold text-ocean-deep">
                {{ __('Expired Invitation') }}
            </h1>
            <p class="text-muted-foreground mt-2">
                {{ __('The invitation for :venueName has expired. Please ask the venue manager to send a new invitation.', { venueName }) }}
            </p>
        </div>
    </GuestLayout>
</template>
