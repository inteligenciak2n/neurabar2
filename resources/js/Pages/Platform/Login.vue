<script setup>
import CustomHead from '@/Components/CustomHead.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('platform.login.store'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <div>
        <CustomHead :title="__('Platform Login')" />

        <div class="flex min-h-screen items-center justify-center bg-muted">
            <div class="w-full max-w-sm">
                <div class="mb-8 text-center">
                    <h1 class="font-heading text-2xl font-bold text-ocean-deep">NeuraBar</h1>
                    <p class="text-sm text-muted-foreground mt-1">{{ __('Platform Administration') }}</p>
                </div>

                <div class="bg-white rounded-xl shadow-card p-8">
                    <form @submit.prevent="submit" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-ocean-deep mb-1">{{ __('Email') }}</label>
                            <input
                                v-model="form.email"
                                type="email"
                                autocomplete="email"
                                class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                            />
                            <p v-if="form.errors.email" class="mt-1 text-xs text-destructive">{{ form.errors.email }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-ocean-deep mb-1">{{ __('Password') }}</label>
                            <input
                                v-model="form.password"
                                type="password"
                                autocomplete="current-password"
                                class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                            />
                            <p v-if="form.errors.password" class="mt-1 text-xs text-destructive">{{ form.errors.password }}</p>
                        </div>

                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full rounded-md bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary/90 disabled:opacity-60 transition-colors"
                        >
                            {{ __('Sign In') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>
