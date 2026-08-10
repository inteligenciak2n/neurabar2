<script setup>
import AppCard from '@/Components/AppCard.vue';
import SettingsLayout from '@/Layouts/SettingsLayout.vue';
import { useCurrency } from '@/Composables/useCurrency';
import { useTranslate } from '@/Composables/useTranslate';
import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    venues: Array,
    filters: Object,
    plan: Object,
    usage: Array,
});

const __ = useTranslate();
const { formatMoney } = useCurrency();
const venueId = ref(props.filters.venue_id);
const period = ref(props.filters.period);

const totalUsage = computed(() => props.usage.reduce((total, item) => total + item.total_calculated_price, 0));
const percentage = (item) => {
    if (item.included_quantity <= 0) {
        return item.quantity > 0 ? 100 : 0;
    }

    return Math.min(100, Math.round((item.quantity / item.included_quantity) * 100));
};

const refresh = () => router.get(route('settings.subscription.usage'), {
    venue_id: venueId.value,
    period: period.value,
}, { preserveState: true, replace: true });
</script>

<template>
    <SettingsLayout :title="__('Usage and limits')">
        <template #header>
            <h1 class="font-heading text-2xl font-bold text-ocean-deep dark:text-gray-100">{{ __('Usage and limits') }}</h1>
        </template>

        <div class="flex flex-col gap-6">
            <div class="grid gap-3 border-b border-border pb-5 dark:border-gray-700 sm:grid-cols-[minmax(0,1fr)_12rem_auto] sm:items-end">
                <label class="flex flex-col gap-1 text-sm font-medium">
                    {{ __('Venue') }}
                    <select v-model="venueId" class="rounded-md border-border dark:border-gray-600 dark:bg-gray-800" @change="refresh">
                        <option v-for="venue in venues" :key="venue.id" :value="venue.id">{{ venue.name }}</option>
                    </select>
                </label>
                <label class="flex flex-col gap-1 text-sm font-medium">
                    {{ __('Period') }}
                    <input v-model="period" type="month" class="rounded-md border-border dark:border-gray-600 dark:bg-gray-800" @change="refresh" />
                </label>
                <div class="text-right text-sm text-muted-foreground">{{ __('Measured total') }} <strong class="ml-1 text-ocean-deep dark:text-gray-100">{{ formatMoney(totalUsage) }}</strong></div>
            </div>

            <AppCard v-if="plan" :title="plan.name">
                <div class="grid gap-4 text-sm sm:grid-cols-3">
                    <div><span class="block text-muted-foreground">{{ __('Version') }}</span><strong>v{{ plan.version }}</strong></div>
                    <div><span class="block text-muted-foreground">{{ __('Minimum commitment') }}</span><strong>{{ formatMoney(plan.minimum_monthly_price) }}</strong></div>
                    <div><span class="block text-muted-foreground">{{ __('Infrastructure') }}</span><strong>{{ plan.infrastructure_type === 'dedicated' ? __('Dedicated recommended') : __('Shared') }}</strong></div>
                </div>
            </AppCard>

            <div v-if="usage.length" class="grid gap-4 lg:grid-cols-2">
                <article v-for="item in usage" :key="item.module_code" class="flex min-h-52 flex-col gap-4 rounded-lg border border-border bg-white p-5 shadow-card dark:border-gray-700 dark:bg-gray-800">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <h2 class="truncate font-heading font-semibold text-ocean-deep dark:text-gray-100">{{ item.module_name }}</h2>
                            <p class="text-xs text-muted-foreground">{{ item.unit_of_measure ?? __('units') }}</p>
                        </div>
                        <strong class="shrink-0 text-sm">{{ formatMoney(item.total_calculated_price) }}</strong>
                    </div>

                    <div class="h-2 overflow-hidden rounded-full bg-muted dark:bg-gray-700">
                        <div class="h-full rounded-full transition-[width]" :class="item.overage_quantity > 0 ? 'bg-amber-500' : 'bg-primary'" :style="{ width: `${percentage(item)}%` }" />
                    </div>

                    <dl class="mt-auto grid grid-cols-3 gap-3 text-sm">
                        <div><dt class="text-muted-foreground">{{ __('Consumed') }}</dt><dd class="font-semibold">{{ item.quantity }}</dd></div>
                        <div><dt class="text-muted-foreground">{{ __('Included') }}</dt><dd class="font-semibold">{{ item.included_quantity }}</dd></div>
                        <div><dt class="text-muted-foreground">{{ __('Overage') }}</dt><dd class="font-semibold" :class="item.overage_quantity > 0 ? 'text-amber-600 dark:text-amber-400' : ''">{{ item.overage_quantity }}</dd></div>
                    </dl>
                </article>
            </div>

            <div v-else class="py-14 text-center text-sm text-muted-foreground">{{ __('No measured usage for this period.') }}</div>
        </div>
    </SettingsLayout>
</template>