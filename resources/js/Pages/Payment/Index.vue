<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import AppCard from '@/Components/AppCard.vue';
import AppButton from '@/Components/AppButton.vue';
import AppBadge from '@/Components/AppBadge.vue';
import AppConfirmModal from '@/Components/AppConfirmModal.vue';
import { useForm, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { useTranslate } from '@/Composables/useTranslate';

const props = defineProps({
    attendance: Object,
    totals: Object,
    perPerson: Number,
    paymentMethods: Array,
    coverChargePerPerson: Number,
    serviceFeePercent: Number,
});

const showConfirm = ref(false);
const __ = useTranslate();

const methodLabels = {
    cash: __('Cash'),
    credit_card: __('Credit Card'),
    debit_card: __('Debit Card'),
    pix: __('Pix'),
    other: __('Other'),
};

// Pré-calcula o total inicial para preencher o primeiro método
const initialPartySize = props.attendance.party_size ?? 1;
const initialItemsTotal = parseFloat(props.totals.items_total ?? 0);
const initialCoverCharge = props.coverChargePerPerson * initialPartySize;
const initialSubtotal = initialItemsTotal + initialCoverCharge;
const initialServiceFee = Math.round(initialSubtotal * (props.serviceFeePercent / 100) * 100) / 100;
const initialGrandTotal = Math.round((initialItemsTotal + initialCoverCharge + initialServiceFee) * 100) / 100;

const form = useForm({
    party_size: initialPartySize,
    methods: [],
});

// Ref separado para evitar bug de reatividade do useForm com arrays aninhados
const localMethods = ref([{ type: 'cash', amount: initialGrandTotal.toFixed(2), notes: '' }]);

function addMethod() {
    let restValue = initialGrandTotal - localMethods.value.reduce((sum, m) => sum + parseFloat(m.amount || 0), 0);
    localMethods.value.push({ type: 'cash', amount: restValue.toFixed(2), notes: '' });
}

function removeMethod(index) {
    localMethods.value.splice(index, 1);
}

const itemsTotal = computed(() => parseFloat(props.totals.items_total ?? 0));

const coverChargeTotal = computed(() => {
    const size = parseInt(form.party_size) || 0;
    return size > 0 ? props.coverChargePerPerson * size : 0;
});

const serviceFeeTotal = computed(() => {
    const subtotal = itemsTotal.value + coverChargeTotal.value;
    return Math.round(subtotal * (props.serviceFeePercent / 100) * 100) / 100;
});

const grandTotal = computed(() => {
    return Math.round((itemsTotal.value + coverChargeTotal.value + serviceFeeTotal.value) * 100) / 100;
});

const perPersonAmount = computed(() => {
    const size = parseInt(form.party_size) || 0;
    if (size <= 0) return null;
    return Math.round((grandTotal.value / size) * 100) / 100;
});

const methodsSum = computed(() =>
    localMethods.value.reduce((sum, m) => sum + parseFloat(m.amount || 0), 0)
);

const sumMatchesTotal = computed(() =>
    Math.abs(methodsSum.value - grandTotal.value) <= 0.01
);

const confirmMessage = computed(() =>
    __('Register payment of') + ' ' + formatCurrency(grandTotal.value) + ' ' + __('and close this attendance?')
);

function confirmPayment() {
    showConfirm.value = true;
}

function submitPayment() {
    form.methods = localMethods.value.map(m => ({ ...m }));
    form.post(route('payment.store', props.attendance.id), {
        onSuccess: () => { showConfirm.value = false; },
        onError: () => { showConfirm.value = false; },
    });
}

function formatCurrency(value) {
    return 'R$ ' + parseFloat(value ?? 0).toFixed(2);
}
</script>

<template>
    <AppLayout :title="__('Payment')">
        <template #header>
            <div class="flex items-center gap-4">
                <Link :href="route('attendances.index')" class="text-sm font-medium text-primary hover:underline">
                    ← {{ __('Attendances') }}
                </Link>
                <h2 class="font-heading text-xl font-semibold text-ocean-deep dark:text-gray-100">
                    {{ __('Payment') }} — {{ attendance.customer_identifier ?? attendance.channel }}
                </h2>
            </div>
        </template>

        <div class="py-6 px-4 sm:px-6 max-w-3xl mx-auto space-y-6">
            <!-- Items summary -->
            <AppCard :title="__('Order Summary')">
                <div v-for="order in attendance.orders" :key="order.id" class="mb-4">
                    <p class="font-heading text-sm font-semibold text-ocean-deep dark:text-gray-100 mb-2">{{ __('Order') }} #{{ order.order_number }}</p>
                    <div class="divide-y divide-muted">
                        <div
                            v-for="item in order.items"
                            :key="item.id"
                            class="flex items-center justify-between py-2 text-sm"
                        >
                            <span class="text-ocean-deep dark:text-gray-100">{{ item.quantity }}× {{ item.product?.name ?? __('Item') }}</span>
                            <span class="text-muted-foreground">{{ formatCurrency(item.unit_price * item.quantity) }}</span>
                        </div>
                    </div>
                </div>
            </AppCard>

            <!-- Totals + Party Size -->
            <AppCard :title="__('Totals')">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-ocean-deep dark:text-gray-300 mb-1">{{ __('Party Size') }}</label>
                    <input
                        v-model="form.party_size"
                        type="number"
                        min="0"
                        class="w-32 rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                    />
                    <p class="mt-1 text-xs text-muted-foreground">{{ __('Affects cover charge calculation.') }}</p>
                </div>

                <div class="space-y-2 border-t border-border pt-4">
                    <div class="flex justify-between text-sm">
                        <span class="text-muted-foreground">{{ __('Items total') }}</span>
                        <span>{{ formatCurrency(itemsTotal) }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-muted-foreground">{{ __('Cover charge') }}</span>
                        <span>{{ formatCurrency(coverChargeTotal) }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-muted-foreground">{{ __('Service fee') }}</span>
                        <span>{{ formatCurrency(serviceFeeTotal) }}</span>
                    </div>
                    <div class="flex justify-between font-heading font-semibold text-base border-t border-border pt-2">
                        <span class="text-ocean-deep dark:text-gray-100">{{ __('Grand Total') }}</span>
                        <span class="text-primary">{{ formatCurrency(grandTotal) }}</span>
                    </div>
                    <div v-if="perPersonAmount" class="flex justify-between text-sm text-muted-foreground">
                        <span>{{ __('Per person') }}</span>
                        <span>{{ formatCurrency(perPersonAmount) }}</span>
                    </div>
                </div>
            </AppCard>

            <!-- Payment Methods -->
            <AppCard :title="__('Payment Methods')">
                <div class="space-y-3">
                    <div
                        v-for="(method, index) in localMethods"
                        :key="index"
                        class="flex gap-2 items-start"
                    >
                        <select
                            v-model="method.type"
                            class="rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                        >
                            <option v-for="m in paymentMethods" :key="m" :value="m">{{ methodLabels[m] ?? m }}</option>
                        </select>
                        <input
                            v-model="method.amount"
                            type="number"
                            min="0"
                            step="0.01"
                            placeholder="0.00"
                            class="w-32 rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                        />
                        <input
                            v-model="method.notes"
                            type="text"
                            placeholder="Notes (optional)"
                            class="flex-1 rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                        />
                        <button
                            v-if="localMethods.length > 1"
                            type="button"
                            class="text-destructive text-sm hover:underline"
                            @click="removeMethod(index)"
                        >
                            {{ __('Remove') }}
                        </button>
                    </div>
                </div>

                <div class="mt-3 flex items-center justify-between">
                    <AppButton variant="ghost" size="sm" @click="addMethod">+ {{ __('Add method') }}</AppButton>
                    <div class="text-sm">
                        <span class="text-muted-foreground">{{ __('Sum') }}: </span>
                        <span
                            :class="sumMatchesTotal ? 'text-green-700 font-semibold' : 'text-destructive font-semibold'"
                        >
                            {{ formatCurrency(methodsSum) }}
                        </span>
                    </div>
                </div>

                <p v-if="localMethods.length && !sumMatchesTotal" class="mt-2 text-xs text-destructive">
                    {{ __('The sum of payment methods must equal the grand total.') }} ({{ formatCurrency(grandTotal) }})
                </p>

                <p v-if="form.errors.methods" class="mt-2 text-xs text-destructive">{{ form.errors.methods }}</p>
            </AppCard>

            <!-- Submit -->
            <div class="flex justify-end gap-3">
                <Link :href="route('attendances.index')">
                    <AppButton variant="ghost">{{ __('Cancel') }}</AppButton>
                </Link>
                <AppButton
                    :disabled="!sumMatchesTotal || form.processing"
                    @click="confirmPayment"
                >
                    {{ __('Confirm Payment') }}
                </AppButton>
            </div>
        </div>

        <AppConfirmModal
            :show="showConfirm"
            :title="__('Confirm Payment')"
            :message="confirmMessage"
            :confirm-label="__('Confirm')"
            :loading="form.processing"
            @confirm="submitPayment"
            @cancel="showConfirm = false"
        />
    </AppLayout>
</template>
