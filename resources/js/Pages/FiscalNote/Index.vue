<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
</script>

<template>
    <AppLayout :title="__('Fiscal Note')">
        <div class="p-6">
            <h1 class="font-heading text-2xl font-bold text-ocean-deep dark:text-gray-100">{{ __('Fiscal Note') }}</h1>
            <p class="mt-2 text-muted-foreground">{{ __('Fiscal note module scaffold.') }}</p>
        </div>
    </AppLayout>
</template>
