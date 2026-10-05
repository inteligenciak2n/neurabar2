<script setup>
import { ref, computed, watch } from 'vue';
import { useTranslate } from '@/Composables/useTranslate';
import axios from 'axios';

defineOptions({ name: 'DeliveryCheckoutPanel' });

const props = defineProps({
    token: {
        type: String,
        default: null,
    },
    venue: {
        type: Object,
        default: null,
    },
    items: Array,
    modelValue: Boolean,
    preview: {
        type: Boolean,
        default: false,
    },
    deliveryEnabled: Boolean,
    pickupEnabled: Boolean,
    acceptedPaymentMethods: Array,
    serviceFeePercent: Number,
});

const emit = defineEmits(['update:modelValue', 'remove', 'order-placed', 'select-item']);

const __ = useTranslate();

const fieldBaseClass = 'box-border w-full min-w-0 max-w-full rounded-lg border px-3 py-2 text-sm placeholder:text-xs placeholder:italic';
const growingBaseClass = `${fieldBaseClass} resize-none overflow-hidden break-words [field-sizing:content]`;
const fieldClass = `${fieldBaseClass} border-border`;
const growingFieldClass = `${growingBaseClass} border-border`;

function requiredClass(value, growing = false) {
    const empty = String(value ?? '').trim() === '';

    return [
        growing ? growingBaseClass : fieldBaseClass,
        empty ? 'border-red-500' : 'border-border',
    ];
}

const orderCode = ref('');
const selectedPaymentTypes = ref([]);
const activeTab = ref('order');
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
const deliveryComment = ref('');
const saveAddress = ref(true);
const logoFailed = ref(false);

const venueAddress = computed(() => {
    const venue = props.venue ?? {};
    const street = [venue.street, venue.number].filter(Boolean).join(', ');
    const cityState = [venue.city, venue.state].filter(Boolean).join(' - ');
    const locality = [venue.neighborhood, cityState].filter(Boolean).join(', ');

    return [street, venue.complement, locality].filter(Boolean).join('\n');
});

const feeZone = ref({ fee: null, label: null, loading: false, error: null });

function allocateOrderCode() {
    return String(Math.floor(1000 + Math.random() * 9000));
}

function formatReais(value) {
    return `R$${Number(value ?? 0).toLocaleString('pt-BR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })}`;
}

const itemsTotal = computed(() => props.items.reduce((s, i) => s + i.unit_price * i.quantity, 0));
const itemsCount = computed(() => props.items.reduce((sum, item) => sum + Number(item.quantity || 0), 0));
const orderTabSummary = computed(() => {
    const count = itemsCount.value;
    const itemsLabel = count === 1
        ? __(':count item', { count })
        : __(':count items', { count });

    return `${itemsLabel} - ${__('Total')} - ${formatReais(itemsTotal.value)}`;
});
const locationStreetLine = computed(() => {
    if (fulfillmentType.value === 'pickup') {
        const venue = props.venue ?? {};

        return [venue.street, venue.number].filter(Boolean).join(', ');
    }

    return [address.value.street, address.value.number].filter(Boolean).join(', ');
});
const locationKindLabel = computed(() => (
    fulfillmentType.value === 'pickup' ? __('Pickup') : __('Delivery')
));
const serviceFeeTotal = computed(() => Math.round(itemsTotal.value * ((props.serviceFeePercent ?? 0) / 100) * 100) / 100);
const deliveryFeeTotal = computed(() => {
    if (fulfillmentType.value !== 'delivery') {
        return 0;
    }

    return Number(feeZone.value.fee ?? 0);
});
const grandTotal = computed(() => Math.round((itemsTotal.value + serviceFeeTotal.value + deliveryFeeTotal.value) * 100) / 100);

const checkoutPaymentOptions = computed(() => {
    const accepted = props.acceptedPaymentMethods ?? [];
    const options = [];

    if (accepted.includes('credit_card') || accepted.includes('debit_card')) {
        options.push({
            value: accepted.includes('credit_card') ? 'credit_card' : 'debit_card',
            label: __('Card'),
        });
    }

    if (accepted.includes('cash')) {
        options.push({ value: 'cash', label: __('Cash') });
    }

    if (accepted.includes('pix')) {
        options.push({ value: 'pix', label: __('Advance PIX') });
    }

    return options;
});

const selectedPaymentMethods = computed(() => {
    const types = selectedPaymentTypes.value;
    const total = grandTotal.value;

    if (types.length === 0) {
        return [];
    }

    if (types.length === 1) {
        return [{ type: types[0], amount: total }];
    }

    const share = Math.floor((total * 100) / types.length) / 100;

    return types.map((type, index) => ({
        type,
        amount: index === types.length - 1
            ? Math.round((total - share * (types.length - 1)) * 100) / 100
            : share,
    }));
});

const selectedPaymentLabel = computed(() => checkoutPaymentOptions.value
    .filter((option) => selectedPaymentTypes.value.includes(option.value))
    .map((option) => option.label)
    .join(', '));

watch(checkoutPaymentOptions, (options) => {
    const allowed = options.map((option) => option.value);
    const kept = selectedPaymentTypes.value.filter((type) => allowed.includes(type));

    if (kept.length === 0 && options[0]) {
        selectedPaymentTypes.value = [options[0].value];

        return;
    }

    if (kept.length !== selectedPaymentTypes.value.length) {
        selectedPaymentTypes.value = kept;
    }
}, { immediate: true });

watch(() => props.modelValue, (open) => {
    if (open && !orderCode.value) {
        orderCode.value = allocateOrderCode();
    }
}, { immediate: true });

let feeLookupTimeout = null;

watch(() => address.value.zip_code, (zip) => {
    clearTimeout(feeLookupTimeout);
    feeZone.value.error = null;

    if (props.preview || fulfillmentType.value !== 'delivery' || !zip || zip.replace(/\D/g, '').length < 8) {
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
    if (props.preview || !customerPhone.value) return;

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
    if (props.preview) return;

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

const canGoToPayment = computed(() => {
    if (!customerName.value || !customerPhone.value) return false;
    if (fulfillmentType.value === 'delivery') {
        const addressOk = address.value.street && address.value.number && address.value.neighborhood
            && address.value.city && address.value.state && address.value.zip_code;

        if (props.preview) {
            return addressOk;
        }

        return addressOk
            && !feeZone.value.loading && feeZone.value.fee !== null && !feeZone.value.error;
    }
    return true;
});

const canSubmit = computed(() => selectedPaymentTypes.value.length > 0 && grandTotal.value > 0);

const completedTabs = ref({
    order: false,
    location: false,
});

const tabLockMessage = ref('');

const isTabActive = (tab) => activeTab.value === tab;

const isTabLocked = (tab) => tab !== 'order' && !completedTabs.value.order;

const showOrderTabSummary = computed(() => !isTabActive('order') && itemsCount.value > 0);
const showLocationTabSummary = computed(() => (
    completedTabs.value.order
    && !isTabActive('location')
    && (completedTabs.value.location || isTabActive('payment'))
));
const showPaymentTabSummary = computed(() => (
    completedTabs.value.order && !isTabActive('payment') && Boolean(selectedPaymentLabel.value)
));

const checkoutTabClass = (tab) => [
    'relative -mb-px flex min-w-0 flex-1 flex-col items-center justify-center gap-0.5 rounded-t-lg border-x border-t px-1.5 py-2 text-xs font-medium transition-colors',
    isTabActive(tab)
        ? "z-10 border-border bg-white text-ocean-deep after:absolute after:inset-x-0 after:-bottom-px after:h-px after:bg-white after:content-['']"
        : isTabLocked(tab)
            ? 'cursor-not-allowed border-border/70 bg-muted text-muted-foreground opacity-60'
            : 'border-border/70 bg-muted text-muted-foreground hover:bg-sand hover:text-ocean-deep',
];

function selectTab(tab) {
    if (isTabLocked(tab)) {
        tabLockMessage.value = __('Confirm the order to continue');

        return;
    }

    tabLockMessage.value = '';
    activeTab.value = tab;
}

function advanceFromOrder() {
    completedTabs.value.order = true;
    tabLockMessage.value = '';
    activeTab.value = 'location';
}

function advanceFromLocation() {
    completedTabs.value.order = true;
    completedTabs.value.location = true;
    tabLockMessage.value = '';
    activeTab.value = 'payment';
}

async function submitOrder() {
    submitError.value = null;

    if (!canGoToPayment.value) {
        activeTab.value = 'location';
        return;
    }

    if (props.preview) {
        return;
    }

    submitting.value = true;

    try {
        const payload = {
            fulfillment_type: fulfillmentType.value,
            customer: { name: customerName.value, phone: customerPhone.value },
            address: fulfillmentType.value === 'delivery' ? { ...address.value, save_address: saveAddress.value } : undefined,
            delivery_comment: deliveryComment.value || null,
            code: orderCode.value ? Number(orderCode.value) : undefined,
            items: props.items.map((item) => ({
                product_id: item.product_id,
                variation_id: item.variation_id,
                quantity: item.quantity,
                notes: item.notes,
                modifiers: item.modifiers,
            })),
            methods: selectedPaymentMethods.value,
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
    activeTab.value = 'order';
    completedTabs.value = { order: false, location: false };
    tabLockMessage.value = '';
    orderCode.value = '';
}
</script>

<template>
    <Teleport to="body">
        <div v-if="modelValue" class="fixed inset-0 z-50 flex items-end justify-center pb-4 sm:items-center sm:pb-0">
            <div class="absolute inset-0 bg-black/50" @click="close" />

            <div class="relative flex max-h-[90vh] w-full min-w-0 max-w-lg flex-col overflow-hidden rounded-t-2xl bg-white shadow-xl sm:rounded-2xl" @click.stop>
                <div class="flex shrink-0 items-center justify-between px-5 pt-5 pb-2">
                    <div class="min-w-0">
                        <h3 class="font-heading text-lg font-bold text-ocean-deep">
                            {{ __('Your order') }}
                        </h3>
                        <p v-if="orderCode" class="text-xs font-medium text-muted-foreground">
                            {{ __('Order code') }} #{{ orderCode }}
                        </p>
                    </div>
                    <button class="rounded-full p-1 text-muted-foreground hover:bg-muted" @click="close">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="shrink-0 overflow-hidden border-b border-border bg-muted/40 px-3 pt-2">
                    <div role="tablist">
                        <nav class="flex w-full items-stretch gap-1">
                            <button
                                type="button"
                                role="tab"
                                :aria-selected="isTabActive('order')"
                                :class="checkoutTabClass('order')"
                                @click="selectTab('order')"
                            >
                                <span class="inline-flex items-center justify-center gap-0.5 whitespace-nowrap">
                                    {{ __('The order') }}
                                    <span
                                        v-if="completedTabs.order"
                                        class="inline-flex h-4 w-4 items-center justify-center rounded-full bg-green-600 text-white"
                                        aria-hidden="true"
                                    >
                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </span>
                                </span>
                                <span
                                    v-if="showOrderTabSummary"
                                    class="mt-0.5 w-full whitespace-normal text-[10px] font-medium leading-tight text-ocean-deep break-words"
                                >
                                    {{ orderTabSummary }}
                                </span>
                            </button>
                            <button
                                type="button"
                                role="tab"
                                :aria-selected="isTabActive('location')"
                                :aria-disabled="isTabLocked('location')"
                                :class="checkoutTabClass('location')"
                                @click="selectTab('location')"
                            >
                                <span class="inline-flex items-center justify-center gap-0.5 whitespace-nowrap">
                                    {{ __('Delivery location') }}
                                    <span
                                        v-if="completedTabs.location"
                                        class="inline-flex h-4 w-4 items-center justify-center rounded-full bg-green-600 text-white"
                                        aria-hidden="true"
                                    >
                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </span>
                                </span>
                                <span
                                    v-if="showLocationTabSummary"
                                    class="mt-0.5 w-full whitespace-normal text-[10px] font-medium leading-tight text-ocean-deep break-words"
                                >
                                    <span class="block">{{ locationKindLabel }}</span>
                                    <span v-if="locationStreetLine" class="block">{{ locationStreetLine }}</span>
                                </span>
                            </button>
                            <button
                                type="button"
                                role="tab"
                                :aria-selected="isTabActive('payment')"
                                :aria-disabled="isTabLocked('payment')"
                                :class="checkoutTabClass('payment')"
                                @click="selectTab('payment')"
                            >
                                {{ __('Payment') }}
                                <span
                                    v-if="showPaymentTabSummary"
                                    class="mt-0.5 w-full whitespace-normal text-[10px] font-medium leading-tight text-ocean-deep break-words"
                                >
                                    {{ selectedPaymentLabel }}
                                </span>
                            </button>
                        </nav>
                    </div>
                    <p v-if="tabLockMessage" class="px-1 pb-2 pt-1 text-center text-xs font-medium text-destructive">
                        {{ tabLockMessage }}
                    </p>
                </div>

                <div class="min-h-0 min-w-0 flex-1 space-y-4 overflow-x-hidden overflow-y-auto px-5 py-4">
                    <template v-if="activeTab === 'order'">
                        <div class="space-y-2">
                            <div
                                v-for="(item, index) in items"
                                :key="index"
                                class="flex min-w-0 items-start gap-3 rounded-xl border border-border p-3"
                            >
                                <button
                                    type="button"
                                    class="min-w-0 flex-1 break-words text-left text-sm"
                                    @click="emit('select-item', index)"
                                >
                                    <p class="font-medium text-ocean-deep">{{ item.product_name }}</p>
                                    <p v-if="item.variation_name" class="text-xs text-muted-foreground">{{ item.variation_name }}</p>
                                    <ul v-if="item.modifier_details?.length" class="mt-1 space-y-0.5">
                                        <li
                                            v-for="(detail, detailIndex) in item.modifier_details"
                                            :key="detailIndex"
                                            class="text-xs text-muted-foreground"
                                        >
                                            {{ detail.group }}: {{ detail.name }}
                                        </li>
                                    </ul>
                                    <p v-if="item.notes" class="mt-0.5 text-xs italic text-muted-foreground">{{ item.notes }}</p>
                                    <p class="mt-1 text-xs font-semibold text-primary">
                                        {{ item.quantity }}x · R$ {{ (item.unit_price * item.quantity).toFixed(2) }}
                                    </p>
                                </button>
                                <button type="button" class="shrink-0 rounded-full p-1 text-destructive hover:bg-destructive/10" @click="emit('remove', index)">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </template>

                    <template v-else-if="activeTab === 'location'">
                        <div v-if="deliveryEnabled && pickupEnabled" class="flex min-w-0 gap-2">
                            <button
                                type="button"
                                class="inline-flex min-w-0 flex-1 items-center justify-center gap-1.5 rounded-lg py-2 text-sm font-medium"
                                :class="fulfillmentType === 'delivery' ? 'bg-primary text-white' : 'bg-muted text-ocean-deep'"
                                @click="fulfillmentType = 'delivery'"
                            >
                                <span
                                    class="inline-flex h-4 w-4 shrink-0 items-center justify-center rounded-full"
                                    :class="fulfillmentType === 'delivery' ? 'bg-green-600 text-white' : 'bg-red-500 text-white'"
                                    aria-hidden="true"
                                >
                                    <svg v-if="fulfillmentType === 'delivery'" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <svg v-else class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </span>
                                {{ __('Delivery') }}
                            </button>
                            <button
                                type="button"
                                class="inline-flex min-w-0 flex-1 items-center justify-center gap-1.5 rounded-lg py-2 text-sm font-medium"
                                :class="fulfillmentType === 'pickup' ? 'bg-primary text-white' : 'bg-muted text-ocean-deep'"
                                @click="fulfillmentType = 'pickup'"
                            >
                                <span
                                    class="inline-flex h-4 w-4 shrink-0 items-center justify-center rounded-full"
                                    :class="fulfillmentType === 'pickup' ? 'bg-green-600 text-white' : 'bg-red-500 text-white'"
                                    aria-hidden="true"
                                >
                                    <svg v-if="fulfillmentType === 'pickup'" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <svg v-else class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </span>
                                {{ __('Pickup') }}
                            </button>
                        </div>

                        <div class="min-w-0 space-y-2">
                            <textarea v-model="customerName" rows="1" :placeholder="__('Full name')" :class="requiredClass(customerName, true)" />
                            <input v-model="customerPhone" type="tel" :placeholder="__('Phone')" :class="requiredClass(customerPhone)" @blur="lookupCustomer" />
                            <p v-if="customerFound" class="text-xs text-muted-foreground">{{ __('Welcome back! Please confirm your details below.') }}</p>

                            <div v-if="customerFound && !phoneVerified" class="min-w-0 space-y-2">
                                <button
                                    v-if="!otpReferenceId"
                                    type="button"
                                    class="text-xs font-medium text-primary disabled:opacity-50"
                                    :disabled="sendingOtp"
                                    @click="requestOtp"
                                >{{ sendingOtp ? __('Sending code...') : __('Send verification code to reuse my data') }}</button>
                                <div v-else class="flex min-w-0 gap-2">
                                    <input v-model="otpCode" type="text" inputmode="numeric" :placeholder="__('Verification code')" :class="fieldClass" />
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

                        <div v-if="fulfillmentType === 'pickup'" class="min-w-0 space-y-3">
                            <div class="min-w-0">
                                <label class="mb-1 block text-xs font-medium text-ocean-deep">{{ __('Comment at pickup') }}</label>
                                <textarea v-model="deliveryComment" rows="1" :placeholder="__('Pick up at the counter')" :class="growingFieldClass" />
                            </div>
                            <div class="flex min-w-0 items-center gap-3 rounded-xl border border-border bg-muted/40 p-3">
                                <div class="h-10 w-10 shrink-0 overflow-hidden rounded-lg bg-muted">
                                    <img
                                        v-if="venue?.logo_url && !logoFailed"
                                        :src="venue.logo_url"
                                        :alt="venue.name"
                                        class="h-full w-full object-cover"
                                        @error="logoFailed = true"
                                    />
                                </div>
                                <div class="min-w-0">
                                    <p class="font-heading text-sm font-bold leading-tight text-ocean-deep">{{ venue?.name }}</p>
                                    <p v-if="venueAddress" class="mt-0.5 whitespace-pre-line text-xs leading-snug text-muted-foreground">{{ venueAddress }}</p>
                                </div>
                            </div>
                        </div>

                        <div v-if="fulfillmentType === 'delivery'" class="min-w-0 space-y-2">
                            <input v-model="address.zip_code" type="text" :placeholder="__('ZIP code')" :class="requiredClass(address.zip_code)" />
                            <p v-if="feeZone.loading" class="text-xs text-muted-foreground">{{ __('Checking delivery fee...') }}</p>
                            <p v-else-if="feeZone.error" class="text-xs text-destructive">{{ feeZone.error }}</p>
                            <p v-else-if="feeZone.label || feeZone.fee" class="break-words text-xs text-green-700">
                                {{ __('Delivery fee') }}: R$ {{ Number(feeZone.fee).toFixed(2) }} <span v-if="feeZone.label">— {{ feeZone.label }}</span>
                            </p>
                            <div class="flex min-w-0 items-start gap-2">
                                <div class="min-w-0 flex-[2]">
                                    <textarea v-model="address.street" rows="1" :placeholder="__('Street')" :class="requiredClass(address.street, true)" />
                                </div>
                                <div class="w-24 shrink-0">
                                    <input v-model="address.number" type="text" :placeholder="__('Number')" :class="requiredClass(address.number)" />
                                </div>
                            </div>
                            <textarea v-model="address.complement" rows="1" :placeholder="__('Complement (optional)')" :class="growingFieldClass" />
                            <textarea v-model="address.neighborhood" rows="1" :placeholder="__('Neighborhood')" :class="requiredClass(address.neighborhood, true)" />
                            <div class="flex min-w-0 items-start gap-2">
                                <div class="min-w-0 flex-1">
                                    <textarea v-model="address.city" rows="1" :placeholder="__('City')" :class="requiredClass(address.city, true)" />
                                </div>
                                <div class="w-16 shrink-0">
                                    <input v-model="address.state" type="text" maxlength="2" :placeholder="__('State')" :class="requiredClass(address.state)" />
                                </div>
                            </div>
                            <textarea v-model="address.reference_point" rows="1" :placeholder="__('Reference point')" :class="growingFieldClass" />
                            <div class="min-w-0">
                                <label class="mb-1 block text-xs font-medium text-ocean-deep">{{ __('Comment at delivery') }}</label>
                                <textarea v-model="deliveryComment" rows="1" :placeholder="__('Leave at the reception')" :class="growingFieldClass" />
                            </div>
                            <label class="flex min-w-0 items-start gap-2 text-xs text-muted-foreground">
                                <input v-model="saveAddress" type="checkbox" class="mt-0.5 shrink-0 rounded border-border" />
                                <span class="min-w-0 break-words">{{ __('Save this address for next time') }}</span>
                            </label>
                        </div>
                    </template>

                    <template v-else>
                        <div class="space-y-1 text-sm">
                            <div class="flex justify-between"><span class="text-muted-foreground">{{ __('Subtotal') }}</span><span>R$ {{ itemsTotal.toFixed(2) }}</span></div>
                            <div v-if="serviceFeeTotal > 0" class="flex justify-between"><span class="text-muted-foreground">{{ __('Service fee') }}</span><span>R$ {{ serviceFeeTotal.toFixed(2) }}</span></div>
                            <div v-if="fulfillmentType === 'delivery'" class="flex justify-between"><span class="text-muted-foreground">{{ __('Delivery fee') }}</span><span>R$ {{ deliveryFeeTotal.toFixed(2) }}</span></div>
                            <div class="flex justify-between font-bold text-ocean-deep border-t border-border pt-1 mt-1"><span>{{ __('Total') }}</span><span>R$ {{ grandTotal.toFixed(2) }}</span></div>
                        </div>

                        <div v-if="orderCode" class="rounded-xl border border-border bg-muted/40 px-4 py-3 text-center">
                            <p class="text-xs font-medium text-muted-foreground">{{ __('Order code') }}</p>
                            <p class="mt-1 font-heading text-2xl font-bold tracking-wide text-ocean-deep">#{{ orderCode }}</p>
                        </div>

                        <div class="space-y-2">
                            <p class="text-sm font-semibold text-ocean-deep">
                                {{ __('Payment method: At delivery time') }}
                            </p>
                            <label
                                v-for="option in checkoutPaymentOptions"
                                :key="option.value"
                                class="flex cursor-pointer items-center gap-3 rounded-xl border px-3 py-2.5 transition-colors"
                                :class="selectedPaymentTypes.includes(option.value) ? 'border-primary bg-primary/5' : 'border-border'"
                            >
                                <input
                                    v-model="selectedPaymentTypes"
                                    type="checkbox"
                                    :value="option.value"
                                    class="rounded border-border text-primary focus:ring-primary"
                                />
                                <span class="min-w-0 flex-1 text-sm text-ocean-deep">{{ option.label }}</span>
                            </label>
                        </div>

                        <div v-if="submitError" class="rounded-lg bg-destructive/5 border border-destructive/30 px-3 py-2 text-xs text-destructive">
                            {{ submitError }}
                        </div>
                        <p v-if="preview" class="text-center text-xs text-muted-foreground">{{ __('This is a preview. Orders cannot be placed from here.') }}</p>
                    </template>
                </div>

                <div class="shrink-0 border-t border-border bg-white px-5 py-4 pb-[max(1rem,env(safe-area-inset-bottom))]">
                    <div v-if="activeTab === 'order'" class="mb-3 flex items-center justify-between text-sm">
                        <span class="font-bold text-ocean-deep">{{ __('Total') }}</span>
                        <span class="font-bold text-ocean-deep">R$ {{ itemsTotal.toFixed(2) }}</span>
                    </div>
                    <button
                        v-if="activeTab === 'order'"
                        :disabled="!items.length"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-primary py-3 text-sm font-semibold text-white disabled:opacity-50"
                        @click="advanceFromOrder"
                    >
                        {{ __('Order correct, advance') }}
                        <span aria-hidden="true" class="text-base font-bold tracking-tight">>></span>
                    </button>
                    <button
                        v-else-if="activeTab === 'location'"
                        :disabled="!canGoToPayment"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-primary py-3 text-sm font-semibold text-white disabled:opacity-50"
                        @click="advanceFromLocation"
                    >
                        {{ __('Advance') }}
                        <span aria-hidden="true" class="text-base font-bold tracking-tight">>></span>
                    </button>
                    <button
                        v-else
                        :disabled="preview || submitting || !canSubmit"
                        class="w-full rounded-xl bg-primary py-3 text-sm font-semibold text-white disabled:opacity-50"
                        @click="submitOrder"
                    >{{ submitting ? __('Placing order...') : __('Send order') }}</button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
