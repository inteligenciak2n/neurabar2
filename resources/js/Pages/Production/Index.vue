<script setup>
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import AppCard from '@/Components/AppCard.vue';
import AppTable from '@/Components/AppTable.vue';
import StatCard from '@/Components/StatCard.vue';
import PeriodFilter from '@/Components/PeriodFilter.vue';
import BarChart from '@/Components/Charts/BarChart.vue';

const props = defineProps({
    filters: Object,
    metrics: Object,
});

const weekdayLabels = [__('Sun'), __('Mon'), __('Tue'), __('Wed'), __('Thu'), __('Fri'), __('Sat')];

const formatMoney = (value) => Number(value ?? 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });

const reload = (overrides = {}) => {
    router.get(
        route('production.index'),
        {
            period: props.filters.period,
            from: props.filters.from,
            to: props.filters.to,
            ...overrides,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

const topItem = computed(() => props.metrics.top_items[0] ?? null);
const topAttendant = computed(() => props.metrics.top_attendants[0] ?? null);
const overallAvgMinutes = computed(() => {
    const totalItems = props.metrics.station_speed.reduce((sum, station) => sum + station.items_count, 0);
    if (totalItems === 0) {
        return null;
    }

    const weightedSum = props.metrics.station_speed.reduce((sum, station) => sum + station.avg_minutes * station.items_count, 0);

    return weightedSum / totalItems;
});

const peakHoursLabels = computed(() => props.metrics.peak_hours.map((point) => `${point.hour}h`));
const peakHoursValues = computed(() => props.metrics.peak_hours.map((point) => point.orders_count));

const peakWeekdaysLabels = computed(() => props.metrics.peak_weekdays.map((point) => weekdayLabels[point.weekday]));
const peakWeekdaysValues = computed(() => props.metrics.peak_weekdays.map((point) => point.orders_count));

const topItemsColumns = [
    { key: 'name', label: __('Product') },
    { key: 'quantity', label: __('Quantity sold') },
    { key: 'revenue', label: __('Revenue') },
];

const stationSpeedColumns = [
    { key: 'name', label: __('Kitchen station') },
    { key: 'avg_minutes', label: __('Avg. time (min)') },
    { key: 'items_count', label: __('Items') },
];

const attendantsColumns = [
    { key: 'name', label: __('Attendant') },
    { key: 'attendances_count', label: __('Attendances') },
    { key: 'revenue', label: __('Revenue generated') },
];
</script>

<template>
    <AppLayout :title="__('Production Dashboard')">
        <template #header>
            <h1 class="font-heading text-2xl font-bold text-ocean-deep dark:text-gray-100">{{ __('Production Dashboard') }}</h1>
        </template>

        <div class="space-y-6">
            <PeriodFilter
                :model-value="{ period: filters.period, from: filters.from, to: filters.to }"
                @update:model-value="(value) => reload(value)"
            />

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <StatCard :label="__('Best seller')" :value="topItem ? topItem.name : __('No sales in this period.')" />
                <StatCard
                    :label="__('Avg. production time')"
                    :value="overallAvgMinutes !== null ? `${overallAvgMinutes.toFixed(1)} min` : __('No data')"
                />
                <StatCard :label="__('Top attendant')" :value="topAttendant ? topAttendant.name : __('No data')" />
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <AppCard :title="__('Peak hours')">
                    <BarChart :labels="peakHoursLabels" :datasets="[{ label: __('Orders'), data: peakHoursValues, color: '#293b4f' }]" />
                </AppCard>
                <AppCard :title="__('Peak weekdays')">
                    <BarChart :labels="peakWeekdaysLabels" :datasets="[{ label: __('Orders'), data: peakWeekdaysValues, color: '#a28665' }]" />
                </AppCard>
            </div>

            <AppCard :title="__('Best selling items')">
                <AppTable :columns="topItemsColumns" :rows="metrics.top_items">
                    <template #cell-revenue="{ value }">{{ formatMoney(value) }}</template>
                </AppTable>
            </AppCard>

            <div class="grid gap-4 lg:grid-cols-2">
                <AppCard :title="__('Production speed by station')">
                    <AppTable :columns="stationSpeedColumns" :rows="metrics.station_speed" />
                </AppCard>
                <AppCard :title="__('Top attendants')">
                    <AppTable :columns="attendantsColumns" :rows="metrics.top_attendants">
                        <template #cell-revenue="{ value }">{{ formatMoney(value) }}</template>
                    </AppTable>
                </AppCard>
            </div>
        </div>
    </AppLayout>
</template>
