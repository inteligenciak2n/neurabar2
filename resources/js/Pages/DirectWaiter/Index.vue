<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
</script>

<template>
    <AppLayout :title="__('Direct Waiter')">
        <div class="p-6">
            <h1 class="font-heading text-2xl font-bold text-ocean-deep dark:text-gray-100">{{ __('Direct Waiter') }}</h1>
            <p class="mt-2 text-muted-foreground">{{ __('Direct waiter module scaffold.') }}</p>
        </div>
    </AppLayout>
</template>
