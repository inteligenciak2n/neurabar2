<script setup>
import { ref, computed, watch } from 'vue';
import { useTranslate } from '@/Composables/useTranslate';
import axios from 'axios';

const props = defineProps({
    token: String,
    items: Array,
    modelValue: Boolean,
    deliveryEnabled: Boolean,
    pickupEnabled: Boolean,
    acceptedPaymentMethods: Array,
    serviceFeePercent: Number,
});

const emit = defineEmits(['update:modelValue', 'remove', 'order-placed']);

const __ = useTranslate();

const step = ref(1);
const submitting = ref(false);
const submitError = ref(null);

const fulfillmentType = ref(props.deliveryEnabled ? 'delivery' : 'pickup');

const customerName = ref('');
const customerPhone = ref('');
const lookingUpCustomer = ref(false);
const customerFound = ref(false);

const otpReferenceId = ref(null);
const otpCode = ref('');
const sendingOtp = ref(false);
const verifyingOtp = ref(false);
const otpError = ref(null);
const phoneVerified = ref(false);

const address = ref({
    street: '',
    number: '',
    complement: '',
    neighborhood: '',
    city: '',
    state: '',
    zip_code: '',
    reference_point: '',
});
const saveAddress = ref(true);

const feeZone = ref({ fee: null, label: null, loading: false, error: null });

const methods = ref([{ type: props.acceptedPaymentMethods?.[0] ?? 'cash', amount: 0 }]);

const itemsTotal = computed(() => props.items.reduce((s, i) => s + i.unit_price * i.quantity, 0));
const serviceFeeTotal = computed(() => Math.round(itemsTotal.value * ((props.serviceFeePercent ?? 0) / 100) * 100) / 100);
const deliveryFeeTotal = computed(() => (fulfillmentType.value === 'delivery' ? feeZone.value.fee : 0));
const grandTotal = computed(() => Math.round((itemsTotal.value + serviceFeeTotal.value + deliveryFeeTotal.value) * 100) / 100);

const methodsTotal = computed(() => methods.value.reduce((s, m) => s + Number(m.amount || 0), 0));
const remaining = computed(() => Math.round((grandTotal.value - methodsTotal.value) * 100) / 100);

// Mantém o valor do único método sincronizado com o total quando não há split.
watch([grandTotal, () => methods.value.length], () => {
    if (methods.value.length === 1) {
        methods.value[0].amount = grandTotal.value;
    }
});

let feeLookupTimeout = null;

watch(() => address.value.zip_code, (zip) => {
    clearTimeout(feeLookupTimeout);
    feeZone.value.error = null;

    if (fulfillmentType.value !== 'delivery' || !zip || zip.replace(/\D/g, '').length < 8) {
        return;
    }

    feeLookupTimeout = setTimeout(async () => {
        feeZone.value.loading = true;
        try {
            const { data } = await axios.get(`/delivery/${props.token}/fee-zones/lookup`, { params: { zip_code: zip } });
            feeZone.value = { fee: data.fee, label: data.label, loading: false, error: null };
        } catch (e) {
            feeZone.value = { fee: null, label: null, loading: false, error: __(e.response?.data?.message ?? 'Error looking up delivery fee.') };
        }
    }, 500);
});

async function lookupCustomer() {
    if (!customerPhone.value) return;

    lookingUpCustomer.value = true;
    try {
        // The endpoint only confirms whether the phone is known — it never returns
        // name/address, so nothing is prefilled here (avoids leaking PII to whoever
        // holds the publicly-shared delivery link).
        const { data } = await axios.get(`/delivery/${props.token}/customer`, { params: { phone: customerPhone.value } });
        customerFound.value = Boolean(data.found);
    } finally {
        lookingUpCustomer.value = false;
    }
}

// Editing the phone after verification invalidates it — it belonged to the previous number.
watch(customerPhone, () => {
    phoneVerified.value = false;
    otpReferenceId.value = null;
    otpCode.value = '';
    otpError.value = null;
});

async function requestOtp() {
    otpError.value = null;
    sendingOtp.value = true;
    try {
        const { data } = await axios.post(`/delivery/${props.token}/phone/otp`, { phone: customerPhone.value });
        otpReferenceId.value = data.reference_id;
    } catch (e) {
        otpError.value = __(e.response?.data?.message ?? 'Error sending verification code.');
    } finally {
        sendingOtp.value = false;
    }
}

async function verifyOtp() {
    if (!otpCode.value) return;

    otpError.value = null;
    verifyingOtp.value = true;
    try {
        const { data } = await axios.post(`/delivery/${props.token}/phone/otp/verify`, {
            phone: customerPhone.value,
            reference_id: otpReferenceId.value,
            code: otpCode.value,
        });

        if (!data.verified) {
            otpError.value = __('Invalid or expired code.');
            return;
        }

        phoneVerified.value = true;

        const { data: saved } = await axios.get(`/delivery/${props.token}/customer/data`, { params: { phone: customerPhone.value } });
        if (saved.name) customerName.value = saved.name;
        if (saved.address) {
            address.value = {
                street: saved.address.street ?? '',
                number: saved.address.number ?? '',
                complement: saved.address.complement ?? '',
                neighborhood: saved.address.neighborhood ?? '',
                city: saved.address.city ?? '',
                state: saved.address.state ?? '',
                zip_code: saved.address.zip_code ?? '',
                reference_point: saved.address.reference_point ?? '',
            };
        }
    } catch (e) {
        otpError.value = __(e.response?.data?.message ?? 'Error verifying code.');
    } finally {
        verifyingOtp.value = false;
    }
}

function addMethod() {
    const used = methods.value.map((m) => m.type);
    const next = props.acceptedPaymentMethods.find((m) => !used.includes(m)) ?? props.acceptedPaymentMethods[0];
    methods.value.push({ type: next, amount: Math.max(remaining.value, 0) });
}

function removeMethod(index) {
    methods.value.splice(index, 1);
}

const canGoToPayment = computed(() => {
    if (!customerName.value || !customerPhone.value) return false;
    if (fulfillmentType.value === 'delivery') {
        return address.value.street && address.value.number && address.value.neighborhood
            && address.value.city && address.value.state && address.value.zip_code
            && !feeZone.value.loading && feeZone.value.fee !== null && !feeZone.value.error;
    }
    return true;
});

const canSubmit = computed(() => methods.value.length > 0 && Math.abs(remaining.value) < 0.01);

async function submitOrder() {
    submitError.value = null;
    submitting.value = true;

    try {
        const payload = {
            fulfillment_type: fulfillmentType.value,
            customer: { name: customerName.value, phone: customerPhone.value },
            address: fulfillmentType.value === 'delivery' ? { ...address.value, save_address: saveAddress.value } : undefined,
            items: props.items.map((item) => ({
                product_id: item.product_id,
                variation_id: item.variation_id,
                quantity: item.quantity,
                notes: item.notes,
                modifiers: item.modifiers,
            })),
            methods: methods.value.map((m) => ({ type: m.type, amount: Number(m.amount) })),
        };

        const { data } = await axios.post(`/delivery/${props.token}/orders`, payload);
        emit('order-placed', data.order_id);
    } catch (e) {
        submitError.value = __(e.response?.data?.message ?? 'Error placing order.');
    } finally {
        submitting.value = false;
    }
}

function close() {
    emit('update:modelValue', false);
    step.value = 1;
}
</script>

<template>
    <Teleport to="body">
        <div v-if="modelValue" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center">
            <div class="absolute inset-0 bg-black/50" @click="close" />

            <div class="relative w-full max-w-sm bg-white rounded-t-2xl sm:rounded-2xl shadow-xl flex flex-col max-h-[90vh]">
                <div class="flex items-center justify-between px-5 pt-5 pb-3 border-b border-border">
                    <h3 class="font-heading text-lg font-bold text-ocean-deep">
                        {{ step === 1 ? __('Your order') : __('Payment') }}
                    </h3>
                    <button class="rounded-full p-1 text-muted-foreground hover:bg-muted" @click="close">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-5 py-4 space-y-4">
                    <!-- Step 1: cart + fulfillment + customer/address -->
                    <template v-if="step === 1">
                        <div class="space-y-2">
                            <div
                                v-for="(item, index) in items"
                                :key="index"
                                class="flex items-start gap-3 rounded-xl border border-border p-3"
                            >
                                <div class="flex-1 text-sm">
                                    <p class="font-medium text-ocean-deep">{{ item.product_name }}</p>
                                    <p v-if="item.variation_name" class="text-xs text-muted-foreground">{{ item.variation_name }}</p>
                                    <p class="mt-1 text-xs font-semibold text-primary">
                                        {{ item.quantity }}x · R$ {{ (item.unit_price * item.quantity).toFixed(2) }}
                                    </p>
                                </div>
                                <button class="shrink-0 rounded-full p-1 text-destructive hover:bg-destructive/10" @click="emit('remove', index)">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Fulfillment type -->
                        <div v-if="deliveryEnabled && pickupEnabled" class="flex gap-2">
                            <button
                                class="flex-1 rounded-lg py-2 text-sm font-medium"
                                :class="fulfillmentType === 'delivery' ? 'bg-primary text-white' : 'bg-muted text-ocean-deep'"
                                @click="fulfillmentType = 'delivery'"
                            >{{ __('Delivery') }}</button>
                            <button
                                class="flex-1 rounded-lg py-2 text-sm font-medium"
                                :class="fulfillmentType === 'pickup' ? 'bg-primary text-white' : 'bg-muted text-ocean-deep'"
                                @click="fulfillmentType = 'pickup'"
                            >{{ __('Pickup') }}</button>
                        </div>

                        <!-- Customer -->
                        <div class="space-y-2">
                            <input v-model="customerName" type="text" :placeholder="__('Full name')" class="w-full rounded-lg border border-border px-3 py-2 text-sm" />
                            <div class="flex gap-2">
                                <input v-model="customerPhone" type="tel" :placeholder="__('Phone')" class="flex-1 rounded-lg border border-border px-3 py-2 text-sm" @blur="lookupCustomer" />
                            </div>
                            <p v-if="customerFound" class="text-xs text-muted-foreground">{{ __('Welcome back! Please confirm your details below.') }}</p>

                            <div v-if="customerFound && !phoneVerified" class="space-y-2">
                                <button
                                    v-if="!otpReferenceId"
                                    type="button"
                                    class="text-xs font-medium text-primary disabled:opacity-50"
                                    :disabled="sendingOtp"
                                    @click="requestOtp"
                                >{{ sendingOtp ? __('Sending code...') : __('Send verification code to reuse my data') }}</button>
                                <div v-else class="flex gap-2">
                                    <input v-model="otpCode" type="text" inputmode="numeric" :placeholder="__('Verification code')" class="flex-1 rounded-lg border border-border px-3 py-2 text-sm" />
                                    <button
                                        type="button"
                                        class="shrink-0 rounded-lg bg-primary px-3 py-2 text-xs font-semibold text-white disabled:opacity-50"
                                        :disabled="verifyingOtp || !otpCode"
                                        @click="verifyOtp"
                                    >{{ verifyingOtp ? __('Verifying...') : __('Verify') }}</button>
                                </div>
                                <p v-if="otpError" class="text-xs text-destructive">{{ otpError }}</p>
                            </div>
                            <p v-if="phoneVerified" class="text-xs text-green-700">{{ __('Phone verified! Your saved data was filled in.') }}</p>
                        </div>

                        <!-- Address -->
                        <div v-if="fulfillmentType === 'delivery'" class="space-y-2">
                            <input v-model="address.zip_code" type="text" :placeholder="__('ZIP code')" class="w-full rounded-lg border border-border px-3 py-2 text-sm" />
                            <p v-if="feeZone.loading" class="text-xs text-muted-foreground">{{ __('Checking delivery fee...') }}</p>
                            <p v-else-if="feeZone.error" class="text-xs text-destructive">{{ feeZone.error }}</p>
                            <p v-else-if="feeZone.label || feeZone.fee" class="text-xs text-green-700">
                                {{ __('Delivery fee') }}: R$ {{ Number(feeZone.fee).toFixed(2) }} <span v-if="feeZone.label">— {{ feeZone.label }}</span>
                            </p>
                            <div class="flex gap-2">
                                <input v-model="address.street" type="text" :placeholder="__('Street')" class="flex-[2] rounded-lg border border-border px-3 py-2 text-sm" />
                                <input v-model="address.number" type="text" :placeholder="__('Number')" class="flex-1 rounded-lg border border-border px-3 py-2 text-sm" />
                            </div>
                            <input v-model="address.complement" type="text" :placeholder="__('Complement (optional)')" class="w-full rounded-lg border border-border px-3 py-2 text-sm" />
                            <input v-model="address.neighborhood" type="text" :placeholder="__('Neighborhood')" class="w-full rounded-lg border border-border px-3 py-2 text-sm" />
                            <div class="flex gap-2">
                                <input v-model="address.city" type="text" :placeholder="__('City')" class="flex-[2] rounded-lg border border-border px-3 py-2 text-sm" />
                                <input v-model="address.state" type="text" maxlength="2" :placeholder="__('State')" class="flex-1 rounded-lg border border-border px-3 py-2 text-sm" />
                            </div>
                            <input v-model="address.reference_point" type="text" :placeholder="__('Reference point')" class="w-full rounded-lg border border-border px-3 py-2 text-sm" />
                            <label class="flex items-center gap-2 text-xs text-muted-foreground">
                                <input v-model="saveAddress" type="checkbox" class="rounded border-border" />
                                {{ __('Save this address for next time') }}
                            </label>
                        </div>
                    </template>

                    <!-- Step 2: payment -->
                    <template v-else>
                        <div class="space-y-1 text-sm">
                            <div class="flex justify-between"><span class="text-muted-foreground">{{ __('Subtotal') }}</span><span>R$ {{ itemsTotal.toFixed(2) }}</span></div>
                            <div v-if="serviceFeeTotal > 0" class="flex justify-between"><span class="text-muted-foreground">{{ __('Service fee') }}</span><span>R$ {{ serviceFeeTotal.toFixed(2) }}</span></div>
                            <div v-if="fulfillmentType === 'delivery'" class="flex justify-between"><span class="text-muted-foreground">{{ __('Delivery fee') }}</span><span>R$ {{ deliveryFeeTotal.toFixed(2) }}</span></div>
                            <div class="flex justify-between font-bold text-ocean-deep border-t border-border pt-1 mt-1"><span>{{ __('Total') }}</span><span>R$ {{ grandTotal.toFixed(2) }}</span></div>
                        </div>

                        <div class="space-y-2">
                            <div v-for="(method, index) in methods" :key="index" class="flex items-center gap-2">
                                <select v-model="method.type" class="rounded-lg border border-border px-2 py-2 text-sm">
                                    <option v-for="m in acceptedPaymentMethods" :key="m" :value="m">{{ m }}</option>
                                </select>
                                <input
                                    v-model.number="method.amount"
                                    type="number"
                                    step="0.01"
                                    :disabled="methods.length === 1"
                                    class="flex-1 rounded-lg border border-border px-3 py-2 text-sm disabled:bg-muted"
                                />
                                <button v-if="methods.length > 1" class="shrink-0 text-destructive" @click="removeMethod(index)">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            <button
                                v-if="methods.length < acceptedPaymentMethods.length"
                                class="text-xs font-medium text-primary"
                                @click="addMethod"
                            >{{ __('Split into another payment method') }}</button>
                            <p v-if="Math.abs(remaining) >= 0.01" class="text-xs text-destructive">
                                {{ __('Remaining') }}: R$ {{ remaining.toFixed(2) }}
                            </p>
                        </div>

                        <div v-if="submitError" class="rounded-lg bg-destructive/5 border border-destructive/30 px-3 py-2 text-xs text-destructive">
                            {{ submitError }}
                        </div>
                    </template>
                </div>

                <div class="border-t border-border px-5 py-4">
                    <button
                        v-if="step === 1"
                        :disabled="!canGoToPayment"
                        class="w-full rounded-xl bg-primary py-3 text-sm font-semibold text-white disabled:opacity-50"
                        @click="step = 2"
                    >{{ __('Continue to payment') }}</button>
                    <div v-else class="flex gap-2">
                        <button class="rounded-xl border border-border px-4 py-3 text-sm font-semibold text-ocean-deep" @click="step = 1">{{ __('Back') }}</button>
                        <button
                            :disabled="!canSubmit || submitting"
                            class="flex-1 rounded-xl bg-primary py-3 text-sm font-semibold text-white disabled:opacity-50"
                            @click="submitOrder"
                        >{{ submitting ? __('Placing order...') : __('Place Order') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>
