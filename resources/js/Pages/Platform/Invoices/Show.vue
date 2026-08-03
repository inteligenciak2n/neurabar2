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
        </div>
    </PlatformLayout>
</template>
