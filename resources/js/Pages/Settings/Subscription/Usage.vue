<script setup>
import AppCard from '@/Components/AppCard.vue';
import SettingsLayout from '@/Layouts/SettingsLayout.vue';
import { useCurrency } from '@/Composables/useCurrency';
import { useTranslate } from '@/Composables/useTranslate';
import { router } from '@inertiajs/vue3';
import { useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    venues: Array,
    filters: Object,
    plan: Object,
    availablePlans: Array,
    recommendations: Array,
    pendingPlanChange: Object,
    nextCycle: String,
    usage: Array,
});

const __ = useTranslate();
const { formatMoney } = useCurrency();
const venueId = ref(props.filters.venue_id);
const period = ref(props.filters.period);
const planChangeForm = useForm({
    venue_id: props.filters.venue_id,
    plan_catalog_id: '',
    reason: '',
});

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

const requestPlanChange = () => {
    planChangeForm.venue_id = venueId.value;
    planChangeForm.post(route('settings.subscription.plan-change-requests.store'), {
        preserveScroll: true,
        onSuccess: () => planChangeForm.reset('plan_catalog_id', 'reason'),
    });
};

const selectPlan = (recommendation) => {
    if (recommendation.is_available && !recommendation.is_current) {
        planChangeForm.plan_catalog_id = recommendation.plan_id;
    }
};

const cancelPlanChange = () => {
    if (confirm(__('Cancel this plan change request?'))) {
        router.delete(route('settings.subscription.plan-change-requests.destroy', props.pendingPlanChange.id), { preserveScroll: true });
    }
};
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

            <section class="flex flex-col gap-4 border-b border-border pb-6 dark:border-gray-700">
                <div class="flex flex-col justify-between gap-2 sm:flex-row sm:items-end">
                    <div>
                        <h2 class="font-heading text-lg font-semibold text-ocean-deep dark:text-gray-100">{{ __('Projected plan cost') }}</h2>
                        <p class="text-sm text-muted-foreground">{{ filters.period }} · {{ __('plan commitment and measured usage') }}</p>
                    </div>
                </div>

                <div class="grid gap-3 lg:grid-cols-3">
                    <article
                        v-for="recommendation in recommendations"
                        :key="recommendation.version_id"
                        class="flex min-h-56 flex-col gap-4 rounded-md border bg-white p-5 dark:bg-gray-800"
                        :class="recommendation.is_recommended ? 'border-primary shadow-card' : 'border-border dark:border-gray-700'"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h3 class="truncate font-heading font-semibold text-ocean-deep dark:text-gray-100">{{ recommendation.name }}</h3>
                                <p class="text-xs text-muted-foreground">v{{ recommendation.version }} · {{ recommendation.infrastructure_type === 'dedicated' ? __('Dedicated recommended') : __('Shared') }}</p>
                            </div>
                            <span v-if="recommendation.is_recommended" class="rounded-full bg-primary-light px-2 py-1 text-xs font-medium text-primary dark:bg-primary/20">{{ __('Recommended') }}</span>
                            <span v-else-if="recommendation.is_current" class="rounded-full bg-muted px-2 py-1 text-xs font-medium text-muted-foreground dark:bg-gray-700">{{ __('Current') }}</span>
                        </div>

                        <div>
                            <strong class="font-heading text-2xl text-ocean-deep dark:text-gray-100">{{ formatMoney(recommendation.projected_total) }}</strong>
                            <span class="text-xs text-muted-foreground"> / {{ __('month') }}</span>
                        </div>

                        <dl class="grid grid-cols-2 gap-3 text-sm">
                            <div><dt class="text-muted-foreground">{{ __('Commitment') }}</dt><dd class="font-semibold">{{ formatMoney(recommendation.minimum_monthly_price) }}</dd></div>
                            <div><dt class="text-muted-foreground">{{ __('Measured usage') }}</dt><dd class="font-semibold">{{ formatMoney(recommendation.projected_usage_price) }}</dd></div>
                        </dl>

                        <div class="mt-auto flex items-center justify-between gap-3">
                            <span class="text-xs font-medium" :class="recommendation.savings_vs_current > 0 ? 'text-green-600 dark:text-green-400' : 'text-muted-foreground'">
                                {{ recommendation.savings_vs_current > 0 ? `${formatMoney(recommendation.savings_vs_current)} ${__('savings')}` : '' }}
                            </span>
                            <button
                                v-if="recommendation.is_available && !recommendation.is_current"
                                type="button"
                                class="text-sm font-medium text-primary hover:underline"
                                @click="selectPlan(recommendation)"
                            >
                                {{ __('Select') }}
                            </button>
                        </div>
                    </article>
                </div>
            </section>

            <section class="border-b border-border pb-6 dark:border-gray-700">
                <div class="flex flex-col justify-between gap-4 md:flex-row md:items-start">
                    <div class="max-w-xl">
                        <h2 class="font-heading text-lg font-semibold text-ocean-deep dark:text-gray-100">{{ __('Plan change') }}</h2>
                        <p class="text-sm text-muted-foreground">{{ __('Changes approved by the backoffice take effect on') }} <strong>{{ nextCycle }}</strong>.</p>
                    </div>

                    <div v-if="pendingPlanChange" class="w-full max-w-xl rounded-md border border-amber-300 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-950/30">
                        <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                            <div>
                                <p class="text-xs font-medium uppercase text-amber-700 dark:text-amber-300">{{ __('Pending approval') }}</p>
                                <p class="font-semibold text-ocean-deep dark:text-gray-100">{{ pendingPlanChange.requested_plan_catalog.name }}</p>
                                <p class="text-xs text-muted-foreground">{{ __('Effective on') }} {{ pendingPlanChange.effective_on }}</p>
                            </div>
                            <button type="button" class="text-sm font-medium text-destructive hover:underline" @click="cancelPlanChange">{{ __('Cancel request') }}</button>
                        </div>
                    </div>

                    <form v-else class="grid w-full max-w-xl gap-3 sm:grid-cols-[minmax(0,1fr)_auto]" @submit.prevent="requestPlanChange">
                        <div class="flex flex-col gap-1">
                            <select v-model="planChangeForm.plan_catalog_id" required class="rounded-md border-border dark:border-gray-600 dark:bg-gray-800">
                                <option value="" disabled>{{ __('Select a plan') }}</option>
                                <option v-for="availablePlan in availablePlans" :key="availablePlan.version_id" :value="availablePlan.id" :disabled="availablePlan.is_current">
                                    {{ availablePlan.name }} · v{{ availablePlan.version }} · {{ formatMoney(availablePlan.minimum_monthly_price) }}
                                </option>
                            </select>
                            <span v-if="planChangeForm.errors.plan_catalog_id" class="text-xs text-destructive">{{ planChangeForm.errors.plan_catalog_id }}</span>
                        </div>
                        <button type="submit" class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary-hover disabled:opacity-50" :disabled="planChangeForm.processing || !planChangeForm.plan_catalog_id">
                            {{ __('Request change') }}
                        </button>
                    </form>
                </div>
            </section>

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