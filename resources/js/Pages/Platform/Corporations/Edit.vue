<script setup>
import PlatformLayout from '@/Layouts/PlatformLayout.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    corporation: Object,
});

const form = useForm({
    name: props.corporation.name ?? '',
    tax_id: props.corporation.tax_id ?? '',
    email: props.corporation.email ?? '',
    contact_phone: props.corporation.contact_phone ?? '',
    active: props.corporation.active ?? true,
});

const planForm = useForm({
    plan_catalog_id: props.corporation.plan_catalog_id ?? '',
    subscription_value: props.corporation.subscription_value ?? '',
    plan_start_date: props.corporation.plan_start_date ?? '',
    plan_end_date: props.corporation.plan_end_date ?? '',
});

const submit = () => {
    form.put(route('platform.corporations.update', props.corporation.id));
};

const submitPlan = () => {
    planForm.put(route('platform.corporations.plan', props.corporation.id));
};
</script>

<template>
    <PlatformLayout title="Edit Corporation">
        <template #header>
            <h1 class="font-heading text-xl font-bold text-ocean-deep">{{ corporation.name }}</h1>
        </template>

        <div class="max-w-2xl space-y-6">
            <!-- Basic info -->
            <form @submit.prevent="submit" class="space-y-4 bg-white rounded-xl shadow-card p-6">
                <h2 class="font-heading font-semibold text-ocean-deep border-b pb-2">Corporation Info</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-ocean-deep mb-1">Name</label>
                        <input v-model="form.name" type="text" class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary" />
                        <p v-if="form.errors.name" class="mt-1 text-xs text-destructive">{{ form.errors.name }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-ocean-deep mb-1">Email</label>
                        <input v-model="form.email" type="email" class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-ocean-deep mb-1">Tax ID</label>
                        <input v-model="form.tax_id" type="text" class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-ocean-deep mb-1">Contact Phone</label>
                        <input v-model="form.contact_phone" type="text" class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary" />
                    </div>
                    <div class="flex items-center gap-2">
                        <input v-model="form.active" type="checkbox" id="active" class="h-4 w-4 rounded border-border text-primary" />
                        <label for="active" class="text-sm font-medium text-ocean-deep">Active</label>
                    </div>
                </div>
                <div class="flex justify-end">
                    <button type="submit" :disabled="form.processing" class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary/90 disabled:opacity-60 transition-colors">
                        Save Changes
                    </button>
                </div>
            </form>

            <!-- Plan assignment -->
            <form @submit.prevent="submitPlan" class="space-y-4 bg-white rounded-xl shadow-card p-6">
                <h2 class="font-heading font-semibold text-ocean-deep border-b pb-2">Plan Assignment</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-ocean-deep mb-1">Plan Catalog ID</label>
                        <input v-model="planForm.plan_catalog_id" type="text" class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary" />
                        <p v-if="planForm.errors.plan_catalog_id" class="mt-1 text-xs text-destructive">{{ planForm.errors.plan_catalog_id }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-ocean-deep mb-1">Subscription Value</label>
                        <input v-model="planForm.subscription_value" type="number" step="0.01" class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-ocean-deep mb-1">Plan Start Date</label>
                        <input v-model="planForm.plan_start_date" type="date" class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-ocean-deep mb-1">Plan End Date</label>
                        <input v-model="planForm.plan_end_date" type="date" class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary" />
                    </div>
                </div>
                <div class="flex justify-end">
                    <button type="submit" :disabled="planForm.processing" class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary/90 disabled:opacity-60 transition-colors">
                        Assign Plan
                    </button>
                </div>
            </form>

            <!-- Venues list -->
            <div class="bg-white rounded-xl shadow-card p-6">
                <h2 class="font-heading font-semibold text-ocean-deep border-b pb-2 mb-4">Venues</h2>
                <ul class="space-y-2">
                    <li v-for="venue in corporation.venues" :key="venue.id" class="flex items-center justify-between rounded-lg border border-border px-3 py-2 text-sm">
                        <span class="font-medium text-ocean-deep">{{ venue.name }}</span>
                        <span :class="venue.active ? 'text-green-600' : 'text-muted-foreground'" class="text-xs">{{ venue.active ? 'Active' : 'Inactive' }}</span>
                    </li>
                    <li v-if="!corporation.venues?.length" class="text-sm text-muted-foreground">No venues yet.</li>
                </ul>
            </div>
        </div>
    </PlatformLayout>
</template>
