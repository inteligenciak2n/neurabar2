<script setup>
import { ref } from 'vue';
import SubscriptionModuleLearnMore from '@/Components/SubscriptionModuleLearnMore.vue';

defineProps({
    module: {
        type: Object,
        required: true,
    },
    venueName: {
        type: String,
        required: true,
    },
    enabled: {
        type: Boolean,
        required: true,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    loading: {
        type: Boolean,
        default: false,
    },
    description: {
        type: String,
        default: '',
    },
    priceLabel: {
        type: String,
        default: '',
    },
    statusTitle: {
        type: String,
        required: true,
    },
    statusValue: {
        type: String,
        required: true,
    },
    clickToAddLine1: {
        type: String,
        default: '',
    },
    clickToAddLine2: {
        type: String,
        default: '',
    },
    clickToAddPriceLine: {
        type: String,
        default: '',
    },
    sequence: {
        type: Number,
        required: true,
    },
    learnMoreLabel: {
        type: String,
        required: true,
    },
    learnMoreTitle: {
        type: String,
        required: true,
    },
    monthlyValueTitle: {
        type: String,
        required: true,
    },
    customerAccessTitle: {
        type: String,
        required: true,
    },
    orderFlowTitle: {
        type: String,
        required: true,
    },
    advantageTitle: {
        type: String,
        default: '',
    },
    monthlyValue: {
        type: String,
        default: '',
    },
    customerAccess: {
        type: String,
        default: '',
    },
    orderFlow: {
        type: String,
        default: '',
    },
    advantage: {
        type: String,
        default: '',
    },
    closeLabel: {
        type: String,
        required: true,
    },
    savingsTitle: {
        type: String,
        default: '',
    },
    savingsDescription: {
        type: String,
        default: '',
    },
    averageOrderLabel: {
        type: String,
        default: '',
    },
    monthlyOrdersLabel: {
        type: String,
        default: '',
    },
    marketplaceFeeLabel: {
        type: String,
        default: '',
    },
    savingsEstimateLabel: {
        type: String,
        default: '',
    },
    savingsDisclaimer: {
        type: String,
        default: '',
    },
    savingsKind: {
        type: String,
        default: 'delivery',
    },
    kitchenTripTimeLabel: {
        type: String,
        default: '',
    },
    kitchenTripCountLabel: {
        type: String,
        default: '',
    },
    freelancerValueLabel: {
        type: String,
        default: '',
    },
    waiterCountLabel: {
        type: String,
        default: '',
    },
    workDaysLabel: {
        type: String,
        default: '',
    },
    secondsLabel: {
        type: String,
        default: '',
    },
    initialWorkDays: {
        type: Number,
        default: 30,
    },
    activateLabel: {
        type: String,
        required: true,
    },
});

const emit = defineEmits(['toggle']);

const showLearnMore = ref(false);

const activateFromLearnMore = () => {
    showLearnMore.value = false;
    emit('toggle');
};
</script>

<template>
    <div>
        <div
            class="relative flex min-h-[11.5rem] items-stretch overflow-hidden rounded-lg border bg-white shadow-card transition-opacity dark:bg-gray-800"
            :class="enabled ? 'border-border dark:border-gray-700' : 'border-gray-200 opacity-80 dark:border-gray-700'"
        >
            <div
                class="relative flex w-48 shrink-0 flex-col justify-between gap-2 self-stretch overflow-hidden px-4 py-3 transition-colors sm:w-64"
            :class="enabled
                ? 'bg-gradient-to-br from-sand to-warm-gold'
                : 'bg-gray-200 dark:bg-gray-700/60'"
        >
            <span
                aria-hidden="true"
                class="pointer-events-none absolute -bottom-6 -left-1 select-none font-heading text-[8rem] font-bold leading-none"
                :class="enabled ? 'text-warm-gold/45' : 'text-gray-300 dark:text-white/10'"
            >
                {{ sequence }}
            </span>
            <div class="relative z-10 flex items-start justify-between gap-3">
                <p
                    class="line-clamp-2 min-h-[2.5rem] min-w-0 flex-1 font-heading text-lg font-bold leading-tight sm:min-h-[2.75rem] sm:text-xl"
                    :class="enabled ? 'text-ocean-deep' : 'text-gray-500 dark:text-gray-400'"
                >
                    {{ module.name }}
                </p>
                <div class="flex shrink-0 flex-col items-end gap-1.5">
                    <div
                        class="text-right text-[10px] leading-tight"
                        :class="enabled ? 'text-ocean-deep/70' : 'text-gray-400 dark:text-gray-500'"
                    >
                        <span class="block">{{ statusTitle }}</span>
                        <span class="block font-semibold">{{ statusValue }}</span>
                    </div>
                    <button
                        type="button"
                        role="switch"
                        :aria-checked="enabled"
                        :aria-label="`${module.name} — ${venueName}`"
                        class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                        :class="enabled
                            ? 'bg-ocean-deep focus:ring-ocean-deep'
                            : 'bg-gray-400 focus:ring-gray-300 focus:ring-offset-gray-200 dark:focus:ring-offset-gray-700'"
                        :disabled="disabled || loading"
                        @click="$emit('toggle')"
                    >
                        <span
                            class="inline-block h-4 w-4 transform rounded-full transition-transform"
                            :class="enabled ? 'translate-x-6 bg-white' : 'translate-x-1 bg-gray-100'"
                        />
                    </button>
                    <button
                        v-if="!enabled"
                        type="button"
                        class="text-xs font-bold leading-tight text-ocean-deep transition-colors hover:underline disabled:cursor-not-allowed disabled:opacity-50 dark:text-gray-100"
                        :disabled="disabled || loading"
                        @click="$emit('toggle')"
                    >
                        {{ clickToAddLine1 }}
                    </button>
                    <span
                        v-else
                        class="invisible text-xs font-bold leading-tight"
                        aria-hidden="true"
                    >
                        {{ clickToAddLine1 }}
                    </span>
                </div>
            </div>
            <div class="relative z-10 min-h-[2.5rem] w-full text-right text-xs leading-snug">
                <button
                    v-if="!enabled"
                    type="button"
                    class="w-full text-right font-semibold text-ocean-deep transition-colors hover:underline disabled:cursor-not-allowed disabled:opacity-50 dark:text-gray-100"
                    :disabled="disabled || loading"
                    @click="$emit('toggle')"
                >
                    {{ clickToAddPriceLine }}
                </button>
                <p
                    v-else-if="priceLabel"
                    class="w-full text-right text-[10px] leading-tight text-gray-500 dark:text-gray-400"
                >
                    {{ priceLabel }}
                </p>
            </div>
        </div>
        <div class="flex min-w-0 flex-1 items-center px-4 py-3 pb-12">
            <div class="space-y-2">
                <p
                    v-for="(paragraph, index) in description.split(/\n\n+/).filter(Boolean)"
                    :key="index"
                    class="text-sm leading-relaxed"
                    :class="index === 0
                        ? (enabled ? 'font-semibold text-ocean-deep dark:text-gray-100' : 'font-semibold text-gray-500 dark:text-gray-400')
                        : (enabled ? 'text-muted-foreground dark:text-gray-400' : 'text-gray-400 dark:text-gray-500')"
                >
                    {{ paragraph }}
                </p>
            </div>
        </div>
            <button
                type="button"
                class="absolute bottom-3 right-3 z-10 rounded-full bg-primary px-3 py-1.5 text-[11px] font-bold text-sand shadow-md [text-shadow:0_0_10px_rgba(196,179,155,0.9)] transition hover:bg-primary-hover hover:text-[#ead9c0]"
                @click.stop="showLearnMore = true"
            >
                {{ learnMoreLabel }}
            </button>
        </div>

        <SubscriptionModuleLearnMore
            :show="showLearnMore"
            :module-name="module.name"
            :title="learnMoreTitle"
            :monthly-value-title="monthlyValueTitle"
            :customer-access-title="customerAccessTitle"
            :order-flow-title="orderFlowTitle"
            :advantage-title="advantageTitle"
            :monthly-value="monthlyValue"
            :customer-access="customerAccess"
            :order-flow="orderFlow"
            :advantage="advantage"
            :close-label="closeLabel"
            :savings-title="savingsTitle"
            :savings-description="savingsDescription"
            :savings-kind="savingsKind"
            :average-order-label="averageOrderLabel"
            :monthly-orders-label="monthlyOrdersLabel"
            :marketplace-fee-label="marketplaceFeeLabel"
            :savings-estimate-label="savingsEstimateLabel"
            :savings-disclaimer="savingsDisclaimer"
            :kitchen-trip-time-label="kitchenTripTimeLabel"
            :kitchen-trip-count-label="kitchenTripCountLabel"
            :freelancer-value-label="freelancerValueLabel"
            :waiter-count-label="waiterCountLabel"
            :work-days-label="workDaysLabel"
            :seconds-label="secondsLabel"
            :initial-work-days="initialWorkDays"
            :activate-label="activateLabel"
            :enabled="enabled"
            :disabled="disabled"
            :loading="loading"
            @close="showLearnMore = false"
            @activate="activateFromLearnMore"
        />
    </div>
</template>
