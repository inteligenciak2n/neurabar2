<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    venues: Array,
    currentVenueId: String,
});

const switchVenue = (id) => {
    useForm({}).post(route('corporation.venues.switch', id));
};
</script>

<template>
    <AppLayout title="Corporation Dashboard">
        <template #header>
            <h1 class="font-heading text-2xl font-bold text-ocean-deep">Corporation Dashboard</h1>
        </template>

        <div class="space-y-6">
            <div class="flex justify-end">
                <Link :href="route('corporation.venues.create')" class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary/90 transition-colors">
                    Add Venue
                </Link>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="venue in venues"
                    :key="venue.id"
                    :class="[
                        'rounded-xl bg-white shadow-card p-5 border-2 transition-colors',
                        venue.id === currentVenueId ? 'border-primary' : 'border-transparent',
                    ]"
                >
                    <div class="flex items-start justify-between mb-3">
                        <div>
                            <h3 class="font-heading font-semibold text-ocean-deep">{{ venue.name }}</h3>
                            <span :class="venue.active ? 'text-green-600' : 'text-muted-foreground'" class="text-xs">
                                {{ venue.active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                        <span v-if="venue.id === currentVenueId" class="rounded-full bg-primary/10 px-2 py-0.5 text-xs font-medium text-primary">Current</span>
                    </div>
                    <p class="text-2xl font-bold text-ocean-deep">{{ venue.today_attendances }}</p>
                    <p class="text-xs text-muted-foreground">Attendances today</p>
                    <div class="flex gap-2 mt-4">
                        <button
                            v-if="venue.id !== currentVenueId"
                            @click="switchVenue(venue.id)"
                            class="rounded-md border border-border px-3 py-1.5 text-xs font-medium text-muted-foreground hover:bg-muted transition-colors"
                        >
                            Switch to this
                        </button>
                        <Link :href="route('corporation.venues.edit', venue.id)" class="rounded-md border border-border px-3 py-1.5 text-xs font-medium text-muted-foreground hover:bg-muted transition-colors">
                            Edit
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
