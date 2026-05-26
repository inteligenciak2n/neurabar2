<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';

defineProps({
    venues: Array,
});
</script>

<template>
    <AppLayout :title="__('Venues')">
        <template #header>
            <h1 class="font-heading text-2xl font-bold text-ocean-deep">{{ __('Venues') }}</h1>
        </template>

        <div class="space-y-4">
            <div class="flex justify-end">
                <Link :href="route('corporation.venues.create')" class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary/90 transition-colors">
                    {{ __('Add Venue') }}
                </Link>
            </div>

            <div class="bg-white rounded-xl shadow-card overflow-hidden">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-border bg-muted/50">
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">{{ __('Name') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">{{ __('City') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">{{ __('Status') }}</th>
                            <th class="px-4 py-3" />
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="venue in venues" :key="venue.id" class="border-b border-border last:border-0 hover:bg-muted/30 transition-colors">
                            <td class="px-4 py-3 font-medium text-ocean-deep">{{ venue.name }}</td>
                            <td class="px-4 py-3 text-muted-foreground">{{ venue.city }}</td>
                            <td class="px-4 py-3">
                                <span :class="venue.active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'" class="rounded-full px-2 py-0.5 text-xs font-medium">
                                    {{ venue.active ? __('Active') : __('Inactive') }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <Link :href="route('corporation.venues.edit', venue.id)" class="text-primary hover:underline text-xs">{{ __('Edit') }}</Link>
                            </td>
                        </tr>
                        <tr v-if="!venues?.length">
                            <td colspan="4" class="px-4 py-8 text-center text-muted-foreground text-sm">{{ __('No venues yet.') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
