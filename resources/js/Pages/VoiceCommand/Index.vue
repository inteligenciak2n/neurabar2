<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
</script>

<template>
    <AppLayout :title="__('Voice Command')">
        <div class="p-6">
            <h1 class="font-heading text-2xl font-bold text-ocean-deep dark:text-gray-100">{{ __('Voice Command') }}</h1>
            <p class="mt-2 text-muted-foreground">{{ __('Voice command module scaffold.') }}</p>
        </div>
    </AppLayout>
</template>
