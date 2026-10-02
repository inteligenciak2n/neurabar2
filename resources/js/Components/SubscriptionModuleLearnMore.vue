<script setup>
import { computed, ref } from 'vue';
import Modal from '@/Components/Modal.vue';
import { useCurrency } from '@/Composables/useCurrency';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    moduleName: {
        type: String,
        required: true,
    },
    title: {
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
    enabled: {
        type: Boolean,
        default: false,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    loading: {
        type: Boolean,
        default: false,
    },
});

defineEmits(['close', 'activate']);

const activateButtonClass = 'mx-auto block rounded-full bg-warm-gold px-4 py-1.5 text-xs font-bold text-ocean-deep shadow-md transition hover:bg-sand disabled:cursor-not-allowed disabled:opacity-50';

const { formatMoney } = useCurrency();
const averageOrderValue = ref(45);
const monthlyOrderCount = ref(50);
const marketplaceFeePercent = ref(12);
const kitchenTripSeconds = ref(30);
const kitchenTripCount = ref(30);
const freelancerDailyValue = ref(150);
const waiterCount = ref(1);
const workDays = ref(props.initialWorkDays);

// Diária do freela em 8h, multiplicada pelos dias de trabalho informados.
const PRINT_SHIFT_HOURS = 8;

const estimatedSavingsAmount = computed(() => {
    if (props.savingsKind === 'print') {
        const seconds = Number(kitchenTripSeconds.value) || 0;
        const trips = Number(kitchenTripCount.value) || 0;
        const waiters = Number(waiterCount.value) || 0;
        const days = Number(workDays.value) || 0;
        const dailyRate = Number(freelancerDailyValue.value) || 0;
        const hoursSaved = (seconds * trips * waiters * days) / 3600;
        const hourlyRate = dailyRate / PRINT_SHIFT_HOURS;
        const cents = Math.round(hoursSaved * hourlyRate * 100);

        return formatMoney(cents);
    }

    const ticket = Number(averageOrderValue.value) || 0;
    const orders = Number(monthlyOrderCount.value) || 0;
    const fee = Number(marketplaceFeePercent.value) || 0;
    const cents = Math.round(ticket * orders * (fee / 100) * 100);

    return formatMoney(cents);
});

const estimatedSavingsBefore = computed(() => props.savingsEstimateLabel.split(':amount')[0] ?? '');
const estimatedSavingsAfter = computed(() => props.savingsEstimateLabel.split(':amount')[1] ?? '');
</script>

<template>
    <Modal :show="show" max-width="2xl" @close="$emit('close')">
        <div class="bg-gradient-to-br from-ocean-deep via-primary to-warm-gold p-6 text-sand sm:p-8">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-bold uppercase tracking-widest text-sand">{{ moduleName }}</p>
                    <h2 class="mt-1 font-heading text-3xl font-bold leading-tight text-sand sm:text-4xl">
                        {{ title }}
                    </h2>
                </div>
                <button
                    type="button"
                    class="shrink-0 rounded-full bg-sand px-4 py-1.5 text-sm font-bold text-ocean-deep shadow-md transition hover:bg-warm-gold"
                    @click="$emit('close')"
                >
                    {{ closeLabel }}
                </button>
            </div>

            <button
                type="button"
                class="activate-module-from-learn-more mt-4"
                :class="activateButtonClass"
                :disabled="disabled || loading"
                @click="$emit('activate')"
            >
                {{ activateLabel }}
            </button>

            <div class="mt-6 grid gap-4">
                <section v-if="customerAccess" class="rounded-2xl bg-sand p-5 text-ocean-deep shadow-lg">
                    <h3 class="font-heading text-xl font-bold">{{ customerAccessTitle }}</h3>
                    <p class="mt-2 whitespace-pre-line text-sm leading-relaxed sm:text-base">{{ customerAccess }}</p>
                </section>

                <section v-if="orderFlow" class="rounded-2xl bg-ocean-light p-5 text-primary shadow-lg">
                    <h3 class="font-heading text-xl font-bold">{{ orderFlowTitle }}</h3>
                    <p class="mt-2 whitespace-pre-line text-sm leading-relaxed sm:text-base">{{ orderFlow }}</p>
                </section>

                <section v-if="advantage" class="rounded-2xl bg-primary p-5 text-sand shadow-lg">
                    <h3 class="font-heading text-xl font-bold">{{ advantageTitle }}</h3>
                    <p class="mt-2 whitespace-pre-line text-sm leading-relaxed sm:text-base">{{ advantage }}</p>
                </section>

                <section class="rounded-2xl bg-warm-gold p-5 text-ocean-deep shadow-lg">
                    <h3 class="font-heading text-xl font-bold">{{ monthlyValueTitle }}</h3>
                    <p class="mt-2 whitespace-pre-line text-sm leading-relaxed sm:text-base">{{ monthlyValue }}</p>
                </section>

                <section v-if="savingsTitle" class="rounded-2xl bg-ocean-deep p-5 text-sand shadow-lg">
                    <h3 class="font-heading text-xl font-bold">{{ savingsTitle }}</h3>
                    <p class="mt-2 text-sm leading-relaxed sm:text-base">{{ savingsDescription }}</p>

                    <div v-if="savingsKind === 'print'" class="mt-4 grid grid-cols-2 items-stretch gap-3">
                        <label class="flex flex-col">
                            <span class="flex min-h-[2.75rem] items-end text-xs font-semibold uppercase leading-tight tracking-wide text-sand/80">
                                {{ kitchenTripTimeLabel }}
                            </span>
                            <span class="mt-1 flex h-11 items-center rounded-md bg-sand px-3 text-ocean-deep">
                                <input
                                    v-model.number="kitchenTripSeconds"
                                    type="number"
                                    min="0"
                                    step="1"
                                    class="h-full w-full border-0 bg-transparent px-0 py-0 text-sm font-bold text-ocean-deep focus:ring-0"
                                >
                                <span class="shrink-0 pl-2 text-sm font-bold">{{ secondsLabel }}</span>
                            </span>
                        </label>
                        <label class="flex flex-col">
                            <span class="flex min-h-[2.75rem] items-end text-xs font-semibold uppercase leading-tight tracking-wide text-sand/80">
                                {{ kitchenTripCountLabel }}
                            </span>
                            <span class="mt-1 flex h-11 items-center rounded-md bg-sand px-3 text-ocean-deep">
                                <input
                                    v-model.number="kitchenTripCount"
                                    type="number"
                                    min="0"
                                    step="1"
                                    class="h-full w-full border-0 bg-transparent px-0 py-0 text-sm font-bold text-ocean-deep focus:ring-0"
                                >
                            </span>
                        </label>
                        <label class="flex flex-col">
                            <span class="flex min-h-[2.75rem] items-end text-xs font-semibold uppercase leading-tight tracking-wide text-sand/80">
                                {{ freelancerValueLabel }}
                            </span>
                            <span class="mt-1 flex h-11 items-center rounded-md bg-sand px-3 text-ocean-deep">
                                <span class="w-6 shrink-0 text-sm font-bold">R$</span>
                                <input
                                    v-model.number="freelancerDailyValue"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    class="h-full w-full border-0 bg-transparent px-0 py-0 text-sm font-bold text-ocean-deep focus:ring-0"
                                >
                            </span>
                        </label>
                        <label class="flex flex-col">
                            <span class="flex min-h-[2.75rem] items-end text-xs font-semibold uppercase leading-tight tracking-wide text-sand/80">
                                {{ waiterCountLabel }}
                            </span>
                            <span class="mt-1 flex h-11 items-center rounded-md bg-sand px-3 text-ocean-deep">
                                <input
                                    v-model.number="waiterCount"
                                    type="number"
                                    min="0"
                                    step="1"
                                    class="h-full w-full border-0 bg-transparent px-0 py-0 text-sm font-bold text-ocean-deep focus:ring-0"
                                >
                            </span>
                        </label>
                        <label class="flex flex-col">
                            <span class="flex min-h-[2.75rem] items-end text-xs font-semibold uppercase leading-tight tracking-wide text-sand/80">
                                {{ workDaysLabel }}
                            </span>
                            <span class="mt-1 flex h-11 items-center rounded-md bg-sand px-3 text-ocean-deep">
                                <input
                                    v-model.number="workDays"
                                    type="number"
                                    min="0"
                                    step="1"
                                    class="h-full w-full border-0 bg-transparent px-0 py-0 text-sm font-bold text-ocean-deep focus:ring-0"
                                >
                            </span>
                        </label>
                    </div>

                    <div v-else class="mt-4 grid grid-cols-3 items-stretch gap-3">
                        <label class="flex flex-col">
                            <span class="flex min-h-[2.75rem] items-end text-xs font-semibold uppercase leading-tight tracking-wide text-sand/80">
                                {{ averageOrderLabel }}
                            </span>
                            <span class="mt-1 flex h-11 items-center rounded-md bg-sand px-3 text-ocean-deep">
                                <span class="w-6 shrink-0 text-sm font-bold">R$</span>
                                <input
                                    v-model.number="averageOrderValue"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    class="h-full w-full border-0 bg-transparent px-0 py-0 text-sm font-bold text-ocean-deep focus:ring-0"
                                >
                            </span>
                        </label>
                        <label class="flex flex-col">
                            <span class="flex min-h-[2.75rem] items-end text-xs font-semibold uppercase leading-tight tracking-wide text-sand/80">
                                {{ monthlyOrdersLabel }}
                            </span>
                            <span class="mt-1 flex h-11 items-center rounded-md bg-sand px-3 text-ocean-deep">
                                <input
                                    v-model.number="monthlyOrderCount"
                                    type="number"
                                    min="0"
                                    step="1"
                                    class="h-full w-full border-0 bg-transparent px-0 py-0 text-sm font-bold text-ocean-deep focus:ring-0"
                                >
                            </span>
                        </label>
                        <label class="flex flex-col">
                            <span class="flex min-h-[2.75rem] items-end text-xs font-semibold uppercase leading-tight tracking-wide text-sand/80">
                                {{ marketplaceFeeLabel }}
                            </span>
                            <span class="mt-1 flex h-11 items-center rounded-md bg-sand px-3 text-ocean-deep">
                                <input
                                    v-model.number="marketplaceFeePercent"
                                    type="number"
                                    min="0"
                                    max="100"
                                    step="0.1"
                                    class="h-full w-full border-0 bg-transparent px-0 py-0 text-sm font-bold text-ocean-deep focus:ring-0"
                                >
                                <span class="w-6 shrink-0 text-right text-sm font-bold">%</span>
                            </span>
                        </label>
                    </div>

                    <div class="mt-5 rounded-xl bg-sand px-4 py-5 text-center text-ocean-deep shadow-md">
                        <p class="text-xs font-semibold uppercase tracking-widest text-primary">{{ estimatedSavingsBefore }}</p>
                        <p class="mt-1 font-heading text-4xl font-bold tracking-tight sm:text-5xl">{{ estimatedSavingsAmount }}</p>
                        <p class="mt-1 text-sm font-medium">{{ estimatedSavingsAfter }}</p>
                        <p v-if="savingsDisclaimer" class="mt-3 whitespace-pre-line text-xs leading-relaxed text-ocean-deep/70">{{ savingsDisclaimer }}</p>
                    </div>
                </section>
            </div>

            <button
                type="button"
                class="activate-module-from-learn-more mt-4"
                :class="activateButtonClass"
                :disabled="disabled || loading"
                @click="$emit('activate')"
            >
                {{ activateLabel }}
            </button>
        </div>
    </Modal>
</template>
