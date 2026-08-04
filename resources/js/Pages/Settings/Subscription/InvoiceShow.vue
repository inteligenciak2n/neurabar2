<script setup>
import SettingsLayout from '@/Layouts/SettingsLayout.vue';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useTranslate } from '@/Composables/useTranslate';
import { useCurrency } from '@/Composables/useCurrency';

const props = defineProps({
    invoice: Object,
    type: String,
});

const __ = useTranslate();
const { formatMoney } = useCurrency();

// Fatura de venue vinculada a uma fatura da corporation é apenas o
// detalhamento do modo unificado: quem paga é a corporation.
const isPayable = computed(
    () => ['open', 'overdue'].includes(props.invoice.status) && !props.invoice.corporation_invoice_id,
);

const statusClass = (status) => {
    return {
        open: 'text-amber-600',
        overdue: 'text-red-600',
        paid: 'text-green-600',
        canceled: 'text-gray-500',
        refunded: 'text-gray-500',
    }[status] ?? 'text-gray-600';
};
</script>

<template>
    <SettingsLayout :title="__('Invoice Details')">
        <template #header>
            <h1 class="font-heading text-2xl font-bold text-ocean-deep dark:text-gray-100">{{ __('Invoice Details') }}</h1>
        </template>

        <div class="rounded-xl border border-border bg-white p-6 shadow-card">
            <div class="mb-6">
                <Link :href="route('settings.subscription.invoices.index')" class="text-sm text-primary hover:underline">
                    &larr; {{ __('Back to invoices') }}
                </Link>
            </div>

            <dl class="grid gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-xs text-muted-foreground">{{ __('Period') }}</dt>
                    <dd class="mt-1 font-medium">{{ invoice.period }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-muted-foreground">{{ __('Type') }}</dt>
                    <dd class="mt-1 font-medium capitalize">{{ type }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-muted-foreground">{{ __('Venue / Corporation') }}</dt>
                    <dd class="mt-1 font-medium">{{ invoice.venue?.name ?? invoice.corporation?.name ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-muted-foreground">{{ __('Due Date') }}</dt>
                    <dd class="mt-1 font-medium">{{ invoice.due_date }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-muted-foreground">{{ __('Status') }}</dt>
                    <dd class="mt-1 font-medium capitalize" :class="statusClass(invoice.status)">{{ invoice.status }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-muted-foreground">{{ __('Total') }}</dt>
                    <dd class="mt-1 font-medium">{{ formatMoney(invoice.total_value) }}</dd>
                </div>
            </dl>

            <div class="mt-6 border-t border-border pt-4">
                <h2 class="font-heading text-sm font-bold text-ocean-deep dark:text-gray-100">{{ __('Invoice Items') }}</h2>

                <p v-if="!invoice.items || invoice.items.length === 0" class="mt-2 text-sm text-muted-foreground">
                    {{ __('No items recorded for this invoice.') }}
                </p>

                <table v-else class="mt-2 w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-muted-foreground">
                            <th class="py-1 font-medium">{{ __('Description') }}</th>
                            <th class="py-1 font-medium">{{ __('Period') }}</th>
                            <th class="py-1 font-medium text-right">{{ __('Quantity') }}</th>
                            <th class="py-1 font-medium text-right">{{ __('Total') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in invoice.items" :key="item.id" class="border-t border-border">
                            <td class="py-1">{{ item.description }}</td>
                            <td class="py-1 text-muted-foreground">{{ item.period }}</td>
                            <td class="py-1 text-right text-muted-foreground">{{ item.quantity }}</td>
                            <td class="py-1 text-right">{{ formatMoney(item.total_amount) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="isPayable" class="mt-6">
                <Link
                    :href="route('settings.subscription.invoices.index')"
                    class="rounded-md bg-primary px-4 py-2 text-sm text-white"
                >
                    {{ __('Pay Invoice') }}
                </Link>
            </div>

            <p v-else-if="invoice.corporation_invoice_id" class="mt-6 text-sm text-muted-foreground">
                {{ __('This invoice is part of the consolidated corporation invoice and is not paid separately.') }}
            </p>
        </div>
    </SettingsLayout>
</template>
