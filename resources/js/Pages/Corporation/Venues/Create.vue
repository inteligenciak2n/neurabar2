<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    tax_id: '',
    phone: '',
    city: '',
    state: '',
    timezone: 'America/Sao_Paulo',
});

const submit = () => {
    form.post(route('corporation.venues.store'));
};
</script>

<template>
    <AppLayout title="Add Venue">
        <template #header>
            <h1 class="font-heading text-2xl font-bold text-ocean-deep">Add Venue</h1>
        </template>

        <div class="max-w-lg">
            <form @submit.prevent="submit" class="space-y-4 bg-white rounded-xl shadow-card p-6">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-ocean-deep mb-1">Name *</label>
                        <input v-model="form.name" type="text" class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary" />
                        <p v-if="form.errors.name" class="mt-1 text-xs text-destructive">{{ form.errors.name }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-ocean-deep mb-1">Tax ID</label>
                        <input v-model="form.tax_id" type="text" class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-ocean-deep mb-1">Phone</label>
                        <input v-model="form.phone" type="text" class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-ocean-deep mb-1">City</label>
                        <input v-model="form.city" type="text" class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-ocean-deep mb-1">State</label>
                        <input v-model="form.state" type="text" maxlength="2" class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-ocean-deep mb-1">Timezone *</label>
                        <input v-model="form.timezone" type="text" class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary" />
                    </div>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <a :href="route('corporation.venues.index')" class="rounded-md border border-border px-4 py-2 text-sm font-medium text-muted-foreground hover:bg-muted transition-colors">Cancel</a>
                    <button type="submit" :disabled="form.processing" class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary/90 disabled:opacity-60 transition-colors">Create Venue</button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
