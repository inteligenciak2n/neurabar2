<script setup>
import PlatformLayout from '@/Layouts/PlatformLayout.vue';

defineProps({
    summary: Object,
});
</script>

<template>
    <PlatformLayout :title="__('Platform Dashboard')">
        <template #header>
            <h1 class="font-heading text-xl font-bold text-ocean-deep">{{ __('Dashboard') }}</h1>
        </template>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl bg-white shadow-card p-5">
                <p class="text-xs font-medium text-muted-foreground uppercase tracking-wider">{{ __('Total Corporations') }}</p>
                <p class="mt-2 text-3xl font-bold text-ocean-deep">{{ summary.total_corporations }}</p>
            </div>
            <div class="rounded-xl bg-white shadow-card p-5">
                <p class="text-xs font-medium text-muted-foreground uppercase tracking-wider">{{ __('Active') }}</p>
                <p class="mt-2 text-3xl font-bold text-green-600">{{ summary.active_corporations }}</p>
            </div>
            <div class="rounded-xl bg-white shadow-card p-5">
                <p class="text-xs font-medium text-muted-foreground uppercase tracking-wider">{{ __('Inactive') }}</p>
                <p class="mt-2 text-3xl font-bold text-muted-foreground">{{ summary.inactive_corporations }}</p>
            </div>
            <div class="rounded-xl bg-white shadow-card p-5">
                <p class="text-xs font-medium text-muted-foreground uppercase tracking-wider">{{ __('MRR') }}</p>
                <p class="mt-2 text-3xl font-bold text-warm-gold">
                    R$ {{ Number(summary.mrr).toLocaleString('pt-BR', { minimumFractionDigits: 2 }) }}
                </p>
            </div>
        </div>
    </PlatformLayout>
</template>
