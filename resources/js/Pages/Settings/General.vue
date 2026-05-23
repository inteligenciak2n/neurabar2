<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import AppCard from '@/Components/AppCard.vue';
import AppButton from '@/Components/AppButton.vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    settings: Object,
});

const form = useForm({
    cover_charge: props.settings?.cover_charge ?? '',
    service_fee_percent: props.settings?.service_fee_percent ?? '',
    table_count: props.settings?.table_count ?? '',
});

const submit = () => {
    form.put(route('settings.general.update'));
};
</script>

<template>
    <AppLayout title="General Settings">
        <template #header>
            <h1 class="font-heading text-2xl font-bold text-ocean-deep">General Settings</h1>
        </template>

        <form @submit.prevent="submit">
            <AppCard title="Financial & Capacity">
                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <label class="block text-sm font-medium text-ocean-deep mb-1">Cover Charge (R$)</label>
                        <input
                            v-model="form.cover_charge"
                            type="number"
                            step="0.01"
                            min="0"
                            class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                        />
                        <p v-if="form.errors.cover_charge" class="mt-1 text-xs text-destructive">{{ form.errors.cover_charge }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-ocean-deep mb-1">Service Fee (%)</label>
                        <input
                            v-model="form.service_fee_percent"
                            type="number"
                            step="0.01"
                            min="0"
                            max="100"
                            class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                        />
                        <p v-if="form.errors.service_fee_percent" class="mt-1 text-xs text-destructive">{{ form.errors.service_fee_percent }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-ocean-deep mb-1">Table Count</label>
                        <input
                            v-model="form.table_count"
                            type="number"
                            min="0"
                            class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                        />
                        <p v-if="form.errors.table_count" class="mt-1 text-xs text-destructive">{{ form.errors.table_count }}</p>
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <AppButton type="submit" :loading="form.processing">Save Changes</AppButton>
                </div>
            </AppCard>
        </form>
    </AppLayout>
</template>
