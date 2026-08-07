<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import AppButton from '@/Components/AppButton.vue';
import { useTranslate } from '@/Composables/useTranslate';

const props = defineProps({
    access: {
        type: Object,
        required: true,
    },
    canManageSubscription: {
        type: Boolean,
        default: false,
    },
});

const __ = useTranslate();

const moduleName = props.access.module?.label ?? __('This module');

const headline = {
    not_contracted: __('The :module module is not part of your plan yet', { module: moduleName }),
    not_active_for_venue: __('The :module module is not enabled for this venue', { module: moduleName }),
    missing_dependency: __('The :module module needs another module first', { module: moduleName }),
}[props.access.reason] ?? __('This module is unavailable');

const description = {
    not_contracted: __('Enable it in your subscription and start using it right away. Billing is prorated, so you only pay for the days you use.'),
    not_active_for_venue: __('Your account already has this module. Turn it on for this venue in the subscription screen.'),
    missing_dependency: __('Enable the required module below and this one becomes available immediately.'),
}[props.access.reason] ?? props.access.message;
</script>

<template>
    <AppLayout :title="__('Module unavailable')">
        <div class="mx-auto flex max-w-xl flex-col items-center px-4 py-16 text-center">
            <div class="rounded-full bg-ocean-light p-4 dark:bg-gray-700">
                <svg class="h-8 w-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
            </div>

            <h1 class="mt-6 font-heading text-2xl font-bold text-ocean-deep dark:text-gray-100">
                {{ headline }}
            </h1>

            <p class="mt-2 text-sm text-muted-foreground font-body dark:text-gray-400">
                {{ description }}
            </p>

            <p
                v-if="access.missing_dependency"
                class="mt-4 rounded-lg bg-muted px-4 py-2 text-sm text-ocean-deep font-body dark:bg-gray-700 dark:text-gray-100"
            >
                {{ __('Required module:') }} <strong>{{ access.missing_dependency.label }}</strong>
            </p>

            <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                <AppButton
                    v-if="canManageSubscription"
                    :href="route('settings.subscription.index')"
                >
                    {{ __('Manage subscription') }}
                </AppButton>
                <p v-else class="text-sm text-muted-foreground font-body dark:text-gray-400">
                    {{ __('Ask an account owner to enable this module.') }}
                </p>

                <Link
                    :href="route('dashboard')"
                    class="text-sm font-medium text-primary hover:underline"
                >
                    {{ __('Back to dashboard') }}
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
