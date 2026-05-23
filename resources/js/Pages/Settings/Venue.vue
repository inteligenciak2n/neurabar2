<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import AppCard from '@/Components/AppCard.vue';
import AppButton from '@/Components/AppButton.vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    venue: Object,
});

const form = useForm({
    name: props.venue.name ?? '',
    tax_id: props.venue.tax_id ?? '',
    phone: props.venue.phone ?? '',
    whatsapp_agent: props.venue.whatsapp_agent ?? '',
    street: props.venue.street ?? '',
    number: props.venue.number ?? '',
    complement: props.venue.complement ?? '',
    neighborhood: props.venue.neighborhood ?? '',
    city: props.venue.city ?? '',
    state: props.venue.state ?? '',
    zip_code: props.venue.zip_code ?? '',
    timezone: props.venue.timezone ?? '',
    require_table: props.venue.require_table ?? false,
    require_tab: props.venue.require_tab ?? false,
    require_location: props.venue.require_location ?? false,
    call_waiter_slug: props.venue.call_waiter_slug ?? '',
    logo_url: props.venue.logo_url ?? '',
});

const submit = () => {
    form.put(route('settings.venue.update'));
};
</script>

<template>
    <AppLayout title="Venue Settings">
        <template #header>
            <h1 class="font-heading text-2xl font-bold text-ocean-deep">Venue Settings</h1>
        </template>

        <form @submit.prevent="submit">
            <div class="space-y-6">
                <AppCard title="Basic Information">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-ocean-deep mb-1">Name <span class="text-destructive">*</span></label>
                            <input
                                v-model="form.name"
                                type="text"
                                class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                            />
                            <p v-if="form.errors.name" class="mt-1 text-xs text-destructive">{{ form.errors.name }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-ocean-deep mb-1">Tax ID</label>
                            <input
                                v-model="form.tax_id"
                                type="text"
                                class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-ocean-deep mb-1">Phone</label>
                            <input
                                v-model="form.phone"
                                type="text"
                                class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-ocean-deep mb-1">WhatsApp Agent</label>
                            <input
                                v-model="form.whatsapp_agent"
                                type="text"
                                class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-ocean-deep mb-1">Timezone</label>
                            <input
                                v-model="form.timezone"
                                type="text"
                                placeholder="America/Sao_Paulo"
                                class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-ocean-deep mb-1">Logo URL</label>
                            <input
                                v-model="form.logo_url"
                                type="url"
                                class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                            />
                        </div>
                    </div>
                </AppCard>

                <AppCard title="Address">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-ocean-deep mb-1">Street</label>
                            <input
                                v-model="form.street"
                                type="text"
                                class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-ocean-deep mb-1">Number</label>
                            <input
                                v-model="form.number"
                                type="text"
                                class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-ocean-deep mb-1">Complement</label>
                            <input
                                v-model="form.complement"
                                type="text"
                                class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-ocean-deep mb-1">Neighborhood</label>
                            <input
                                v-model="form.neighborhood"
                                type="text"
                                class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-ocean-deep mb-1">City</label>
                            <input
                                v-model="form.city"
                                type="text"
                                class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-ocean-deep mb-1">State</label>
                            <input
                                v-model="form.state"
                                type="text"
                                class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-ocean-deep mb-1">ZIP Code</label>
                            <input
                                v-model="form.zip_code"
                                type="text"
                                class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                            />
                        </div>
                    </div>
                </AppCard>

                <AppCard title="Operational Requirements">
                    <div class="space-y-3">
                        <label class="flex cursor-pointer items-center gap-3">
                            <input v-model="form.require_table" type="checkbox" class="h-4 w-4 rounded border-border text-primary focus:ring-primary" />
                            <span class="text-sm text-ocean-deep">Require table number on orders</span>
                        </label>

                        <label class="flex cursor-pointer items-center gap-3">
                            <input v-model="form.require_tab" type="checkbox" class="h-4 w-4 rounded border-border text-primary focus:ring-primary" />
                            <span class="text-sm text-ocean-deep">Require tab (customer name) on orders</span>
                        </label>

                        <label class="flex cursor-pointer items-center gap-3">
                            <input v-model="form.require_location" type="checkbox" class="h-4 w-4 rounded border-border text-primary focus:ring-primary" />
                            <span class="text-sm text-ocean-deep">Require service location on orders</span>
                        </label>
                    </div>
                </AppCard>

                <AppCard title="Call Waiter">
                    <div>
                        <label class="block text-sm font-medium text-ocean-deep mb-1">Slug</label>
                        <input
                            v-model="form.call_waiter_slug"
                            type="text"
                            class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                        />
                        <p class="mt-1 text-xs text-muted-foreground">Used in the public guest QR code URL.</p>
                    </div>
                </AppCard>

                <div class="flex justify-end">
                    <AppButton type="submit" :loading="form.processing">Save Changes</AppButton>
                </div>
            </div>
        </form>
    </AppLayout>
</template>
