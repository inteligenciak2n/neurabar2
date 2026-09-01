<script setup>
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import AppCard from '@/Components/AppCard.vue';
import AppTable from '@/Components/AppTable.vue';
import StatCard from '@/Components/StatCard.vue';
import PeriodFilter from '@/Components/PeriodFilter.vue';
import LineChart from '@/Components/Charts/LineChart.vue';
import DoughnutChart from '@/Components/Charts/DoughnutChart.vue';

const props = defineProps({
    filters: Object,
    canViewCorporation: Boolean,
    metrics: Object,
});

const paymentMethodLabels = {
    cash: __('Cash'),
    credit_card: __('Credit card'),
    debit_card: __('Debit card'),
    pix: __('Pix'),
    other: __('Other'),
};

const formatMoney = (value) => Number(value ?? 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });

const formatDate = (date) => new Date(`${date}T00:00:00`).toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' });

const reload = (overrides = {}) => {
    router.get(
        route('finance.index'),
        {
            period: props.filters.period,
            from: props.filters.from,
            to: props.filters.to,
            scope: props.filters.scope,
            ...overrides,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

const setScope = (scope) => reload({ scope });

const revenueTrendLabels = computed(() => props.metrics.revenue_trend.map((point) => formatDate(point.date)));
const revenueTrendValues = computed(() => props.metrics.revenue_trend.map((point) => point.total));

const paymentMethodLabelsList = computed(() =>
    props.metrics.payment_method_breakdown.map((item) => paymentMethodLabels[item.method] ?? item.method),
);
const paymentMethodValues = computed(() => props.metrics.payment_method_breakdown.map((item) => item.total));

const venuesColumns = [
    { key: 'venue_name', label: __('Venue') },
    { key: 'gross_revenue', label: __('Revenue') },
    { key: 'average_ticket', label: __('Average ticket') },
    { key: 'attendances_count', label: __('Attendances') },
];
</script>

<template>
    <AppLayout :title="__('Financial Dashboard')">
        <template #header>
            <h1 class="font-heading text-2xl font-bold text-ocean-deep dark:text-gray-100">{{ __('Financial Dashboard') }}</h1>
        </template>

        <div class="space-y-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <PeriodFilter
                    :model-value="{ period: filters.period, from: filters.from, to: filters.to }"
                    @update:model-value="(value) => reload(value)"
                />

                <div v-if="canViewCorporation" class="flex rounded-md bg-muted p-1 dark:bg-gray-700">
                    <button
                        type="button"
                        :class="[
                            'rounded px-3 py-1.5 text-sm font-medium transition-colors',
                            filters.scope === 'venue' ? 'bg-white text-ocean-deep shadow-sm dark:bg-gray-800 dark:text-gray-100' : 'text-muted-foreground',
                        ]"
                        @click="setScope('venue')"
                    >
                        {{ __('This venue') }}
                    </button>
                    <button
                        type="button"
                        :class="[
                            'rounded px-3 py-1.5 text-sm font-medium transition-colors',
                            filters.scope === 'corporation' ? 'bg-white text-ocean-deep shadow-sm dark:bg-gray-800 dark:text-gray-100' : 'text-muted-foreground',
                        ]"
                        @click="setScope('corporation')"
                    >
                        {{ __('All venues') }}
                    </button>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <StatCard :label="__('Gross revenue')" :value="formatMoney(metrics.gross_revenue)" :delta="metrics.previous_period.gross_revenue" />
                <StatCard :label="__('Average ticket')" :value="formatMoney(metrics.average_ticket)" :delta="metrics.previous_period.average_ticket" />
                <StatCard :label="__('Attendances')" :value="metrics.attendances_count" :delta="metrics.previous_period.attendances_count" />
            </div>

            <div class="grid gap-4 lg:grid-cols-3">
                <AppCard :title="__('Revenue trend')" class="lg:col-span-2">
                    <LineChart :labels="revenueTrendLabels" :values="revenueTrendValues" :label="__('Revenue')" />
                </AppCard>
                <AppCard :title="__('Payment methods')">
                    <DoughnutChart v-if="metrics.payment_method_breakdown.length" :labels="paymentMethodLabelsList" :values="paymentMethodValues" />
                    <p v-else class="text-sm text-muted-foreground">{{ __('No payments in this period.') }}</p>
                </AppCard>
            </div>

            <AppCard v-if="filters.scope === 'corporation'" :title="__('Breakdown by venue')">
                <AppTable :columns="venuesColumns" :rows="metrics.venues_breakdown">
                    <template #cell-gross_revenue="{ value }">{{ formatMoney(value) }}</template>
                    <template #cell-average_ticket="{ value }">{{ formatMoney(value) }}</template>
                </AppTable>
            </AppCard>
        </div>
    </AppLayout>
</template>
