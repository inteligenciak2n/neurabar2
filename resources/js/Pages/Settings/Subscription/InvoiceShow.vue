<script setup>
import SettingsLayout from '@/Layouts/SettingsLayout.vue';
import { Link } from '@inertiajs/vue3';
import { useTranslate } from '@/Composables/useTranslate';
import { useCurrency } from '@/Composables/useCurrency';

const props = defineProps({
    invoice: Object,
    type: String,
});

const __ = useTranslate();
const { formatMoney } = useCurrency();

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

            <div v-if="invoice.status === 'open' || invoice.status === 'overdue'" class="mt-6">
                <Link
                    :href="route('settings.subscription.invoices.index')"
                    class="rounded-md bg-primary px-4 py-2 text-sm text-white"
                >
                    {{ __('Pay Invoice') }}
                </Link>
            </div>
        </div>
    </SettingsLayout>
</template>
