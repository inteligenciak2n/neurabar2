<script setup>
import PlatformLayout from '@/Layouts/PlatformLayout.vue';
import { useCurrency } from '@/Composables/useCurrency';

const props = defineProps({
    invoice: Object,
});

const { formatMoney } = useCurrency();
</script>

<template>
    <PlatformLayout :title="__('Invoice Details')">
        <template #header>
            <h1 class="font-heading text-xl font-bold text-ocean-deep dark:text-gray-100">{{ __('Invoice') }} {{ invoice.period }}</h1>
        </template>

        <div class="max-w-2xl bg-white rounded-xl shadow-card p-6 dark:bg-gray-800 space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <span class="block text-xs text-muted-foreground">{{ __('Period') }}</span>
                    <span class="text-ocean-deep dark:text-gray-100">{{ invoice.period }}</span>
                </div>
                <div>
                    <span class="block text-xs text-muted-foreground">{{ __('Due Date') }}</span>
                    <span class="text-ocean-deep dark:text-gray-100">{{ invoice.due_date }}</span>
                </div>
                <div>
                    <span class="block text-xs text-muted-foreground">{{ __('Status') }}</span>
                    <span class="text-ocean-deep dark:text-gray-100">{{ invoice.status.label }}</span>
                </div>
                <div>
                    <span class="block text-xs text-muted-foreground">{{ __('Finalized') }}</span>
                    <span class="text-ocean-deep dark:text-gray-100">{{ invoice.is_finalized ? __('Yes') : __('No') }}</span>
                </div>
            </div>

            <div class="border-t pt-4 dark:border-gray-700">
                <div class="flex justify-between py-1">
                    <span class="text-muted-foreground">{{ __('Base') }}</span>
                    <span class="text-ocean-deep dark:text-gray-100">{{ formatMoney(invoice.base_value) }}</span>
                </div>
                <div class="flex justify-between py-1">
                    <span class="text-muted-foreground">{{ __('Modules') }}</span>
                    <span class="text-ocean-deep dark:text-gray-100">{{ formatMoney(invoice.modules_value) }}</span>
                </div>
                <div class="flex justify-between py-1">
                    <span class="text-muted-foreground">{{ __('Metered') }}</span>
                    <span class="text-ocean-deep dark:text-gray-100">{{ formatMoney(invoice.metered_value) }}</span>
                </div>
                <div class="flex justify-between py-1">
                    <span class="text-muted-foreground">{{ __('Dedicated Surcharge') }}</span>
                    <span class="text-ocean-deep dark:text-gray-100">{{ formatMoney(invoice.dedicated_surcharge) }}</span>
                </div>
                <div class="flex justify-between py-1">
                    <span class="text-muted-foreground">{{ __('Discount') }}</span>
                    <span class="text-ocean-deep dark:text-gray-100">{{ formatMoney(invoice.discount_value) }}</span>
                </div>
                <div class="flex justify-between py-2 font-semibold text-lg border-t dark:border-gray-700">
                    <span class="text-ocean-deep dark:text-gray-100">{{ __('Total') }}</span>
                    <span class="text-ocean-deep dark:text-gray-100">{{ formatMoney(invoice.total_value) }}</span>
                </div>
            </div>

            <div class="border-t pt-4 dark:border-gray-700">
                <h2 class="font-heading text-sm font-bold text-ocean-deep dark:text-gray-100 mb-2">{{ __('Invoice Items') }}</h2>

                <p v-if="!invoice.items || invoice.items.length === 0" class="text-sm text-muted-foreground">
                    {{ __('No items recorded for this invoice.') }}
                </p>

                <table v-else class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-muted-foreground">
                            <th class="py-1 font-medium">{{ __('Description') }}</th>
                            <th class="py-1 font-medium">{{ __('Period') }}</th>
                            <th class="py-1 font-medium text-right">{{ __('Quantity') }}</th>
                            <th class="py-1 font-medium text-right">{{ __('Total') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in invoice.items" :key="item.id" class="border-t dark:border-gray-700">
                            <td class="py-1 text-ocean-deep dark:text-gray-100">{{ item.description }}</td>
                            <td class="py-1 text-muted-foreground">{{ item.period }}</td>
                            <td class="py-1 text-right text-muted-foreground">{{ item.quantity }}</td>
                            <td class="py-1 text-right text-ocean-deep dark:text-gray-100">{{ formatMoney(item.total_amount) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </PlatformLayout>
</template>
