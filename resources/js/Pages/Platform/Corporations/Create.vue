<script setup>
import PlatformLayout from '@/Layouts/PlatformLayout.vue';
import { useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    tax_id: '',
    email: '',
    contact_phone: '',
    city: '',
    state: '',
    timezone: 'America/Sao_Paulo',
    owner_name: '',
    owner_email: '',
});

const submit = () => {
    form.post(route('platform.corporations.store'));
};
</script>

<template>
    <PlatformLayout title="New Corporation">
        <template #header>
            <h1 class="font-heading text-xl font-bold text-ocean-deep">New Corporation</h1>
        </template>

        <div class="max-w-2xl">
            <form @submit.prevent="submit" class="space-y-6 bg-white rounded-xl shadow-card p-6">
                <h2 class="font-heading font-semibold text-ocean-deep border-b pb-2">Corporation Info</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-ocean-deep mb-1">Name *</label>
                        <input v-model="form.name" type="text" class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary" />
                        <p v-if="form.errors.name" class="mt-1 text-xs text-destructive">{{ form.errors.name }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-ocean-deep mb-1">Email *</label>
                        <input v-model="form.email" type="email" class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary" />
                        <p v-if="form.errors.email" class="mt-1 text-xs text-destructive">{{ form.errors.email }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-ocean-deep mb-1">Tax ID</label>
                        <input v-model="form.tax_id" type="text" class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-ocean-deep mb-1">Contact Phone</label>
                        <input v-model="form.contact_phone" type="text" class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-ocean-deep mb-1">City</label>
                        <input v-model="form.city" type="text" class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-ocean-deep mb-1">State</label>
                        <input v-model="form.state" type="text" maxlength="2" class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-ocean-deep mb-1">Timezone *</label>
                        <input v-model="form.timezone" type="text" class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary" />
                        <p v-if="form.errors.timezone" class="mt-1 text-xs text-destructive">{{ form.errors.timezone }}</p>
                    </div>
                </div>

                <h2 class="font-heading font-semibold text-ocean-deep border-b pb-2">Owner Account</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-ocean-deep mb-1">Owner Name *</label>
                        <input v-model="form.owner_name" type="text" class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary" />
                        <p v-if="form.errors.owner_name" class="mt-1 text-xs text-destructive">{{ form.errors.owner_name }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-ocean-deep mb-1">Owner Email *</label>
                        <input v-model="form.owner_email" type="email" class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary" />
                        <p v-if="form.errors.owner_email" class="mt-1 text-xs text-destructive">{{ form.errors.owner_email }}</p>
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <a :href="route('platform.corporations.index')" class="rounded-md border border-border px-4 py-2 text-sm font-medium text-muted-foreground hover:bg-muted transition-colors">Cancel</a>
                    <button type="submit" :disabled="form.processing" class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary/90 disabled:opacity-60 transition-colors">
                        Create Corporation
                    </button>
                </div>
            </form>
        </div>
    </PlatformLayout>
</template>
