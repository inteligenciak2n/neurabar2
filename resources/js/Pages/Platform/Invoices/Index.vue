<script setup>
import PlatformLayout from '@/Layouts/PlatformLayout.vue';
import { useForm } from '@inertiajs/vue3';
import { useCurrency } from '@/Composables/useCurrency';

const props = defineProps({
    filters: Object,
    corporationInvoices: Object,
    venueInvoices: Object,
    statuses: Array,
});

const { formatMoney } = useCurrency();

const form = useForm({
    period: props.filters.period ?? '',
});

const submit = () => {
    form.get(route('platform.invoices.index'), { preserveState: true });
};
</script>

<template>
    <PlatformLayout :title="__('Invoices')">
        <template #header>
            <h1 class="font-heading text-xl font-bold text-ocean-deep dark:text-gray-100">{{ __('Invoices') }}</h1>
        </template>

        <div class="space-y-6">
            <form @submit.prevent="submit" class="flex items-end gap-4 bg-white rounded-xl shadow-card p-4 dark:bg-gray-800">
                <div>
                    <label class="block text-sm font-medium text-ocean-deep mb-1 dark:text-gray-300">{{ __('Period') }}</label>
                    <input v-model="form.period" type="month" class="rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" />
                </div>
                <button type="submit" :disabled="form.processing" class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary/90 disabled:opacity-60 transition-colors">
                    {{ __('Filter') }}
                </button>
            </form>

            <div class="bg-white rounded-xl shadow-card overflow-hidden dark:bg-gray-800">
                <h2 class="font-heading font-semibold text-ocean-deep border-b px-4 py-3 dark:text-gray-100 dark:border-gray-700">{{ __('Corporation Invoices') }}</h2>
                <table class="min-w-full text-sm">
                    <thead class="bg-ocean-deep text-white">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">{{ __('Corporation') }}</th>
                            <th class="px-4 py-3 text-left font-medium">{{ __('Period') }}</th>
                            <th class="px-4 py-3 text-left font-medium">{{ __('Due Date') }}</th>
                            <th class="px-4 py-3 text-left font-medium">{{ __('Status') }}</th>
                            <th class="px-4 py-3 text-right font-medium">{{ __('Total') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border dark:divide-gray-700">
                        <tr v-for="invoice in corporationInvoices.data" :key="invoice.id">
                            <td class="px-4 py-3 text-ocean-deep dark:text-gray-100">{{ invoice.corporation?.name ?? '-' }}</td>
                            <td class="px-4 py-3 text-ocean-deep dark:text-gray-100">{{ invoice.period }}</td>
                            <td class="px-4 py-3 text-ocean-deep dark:text-gray-100">{{ invoice.due_date }}</td>
                            <td class="px-4 py-3">
                                <span :class="statuses.find(s => s.value === invoice.status.value)?.class ?? 'bg-gray-100 text-gray-700'" class="rounded-full px-2 py-1 text-xs font-medium">
                                    {{ invoice.status.label }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right text-ocean-deep dark:text-gray-100">{{ formatMoney(invoice.total_value) }}</td>
                        </tr>
                        <tr v-if="!corporationInvoices.data.length">
                            <td colspan="5" class="px-4 py-6 text-center text-muted-foreground">{{ __('No corporation invoices found.') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="bg-white rounded-xl shadow-card overflow-hidden dark:bg-gray-800">
                <h2 class="font-heading font-semibold text-ocean-deep border-b px-4 py-3 dark:text-gray-100 dark:border-gray-700">{{ __('Venue Invoices') }}</h2>
                <table class="min-w-full text-sm">
                    <thead class="bg-ocean-deep text-white">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">{{ __('Venue') }}</th>
                            <th class="px-4 py-3 text-left font-medium">{{ __('Period') }}</th>
                            <th class="px-4 py-3 text-left font-medium">{{ __('Due Date') }}</th>
                            <th class="px-4 py-3 text-left font-medium">{{ __('Status') }}</th>
                            <th class="px-4 py-3 text-right font-medium">{{ __('Total') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border dark:divide-gray-700">
                        <tr v-for="invoice in venueInvoices.data" :key="invoice.id">
                            <td class="px-4 py-3 text-ocean-deep dark:text-gray-100">{{ invoice.venue?.name ?? '-' }}</td>
                            <td class="px-4 py-3 text-ocean-deep dark:text-gray-100">{{ invoice.period }}</td>
                            <td class="px-4 py-3 text-ocean-deep dark:text-gray-100">{{ invoice.due_date }}</td>
                            <td class="px-4 py-3">
                                <span :class="statuses.find(s => s.value === invoice.status.value)?.class ?? 'bg-gray-100 text-gray-700'" class="rounded-full px-2 py-1 text-xs font-medium">
                                    {{ invoice.status.label }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right text-ocean-deep dark:text-gray-100">{{ formatMoney(invoice.total_value) }}</td>
                        </tr>
                        <tr v-if="!venueInvoices.data.length">
                            <td colspan="5" class="px-4 py-6 text-center text-muted-foreground">{{ __('No venue invoices found.') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </PlatformLayout>
</template>
