<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { ref, computed, onMounted } from 'vue';
import { useTranslate } from '@/Composables/useTranslate';
import axios from 'axios';

const props = defineProps({
    token: String,
    venue: Object,
    serviceLocation: Object,
    attendanceChannel: Object,
    hasSession: Boolean,
    geolocationVerified: Boolean,
});

const __ = useTranslate();

// ── State ──────────────────────────────────────────────────────────────────
const view = ref(props.hasSession ? 'hub' : 'pin-setup');
const pinInput = ref('');
const pinConfirm = ref('');
const pinError = ref(null);
const pinLoading = ref(false);

const signalOpen = ref(false);
const signalMessage = ref('');
const signalOnly = ref(false);
const signalLoading = ref(false);
const signalSent = ref(false);

const ordersOpen = ref(false);
const ordersPin = ref('');
const ordersPinError = ref(null);
const ordersLoading = ref(false);
const orders = ref([]);
const ordersVerified = ref(false);

const checkoutConfirm = ref(false);
const checkoutLoading = ref(false);
const checkoutDone = ref(false);

const geoBlocked = ref(false);
const geoChecking = ref(false);
const isDeliveryFallback = ref(!props.serviceLocation);
const deliveryConfirmed = ref(false);

// ── Geolocalização ─────────────────────────────────────────────────────────
const requiresGeo = computed(() => props.venue.require_geolocation && !props.geolocationVerified);
const canOrder = computed(() => !requiresGeo.value || !geoBlocked.value);

function requestGeolocation() {
    if (!navigator.geolocation) {
        geoBlocked.value = true;
        return;
    }
    geoChecking.value = true;
    navigator.geolocation.getCurrentPosition(
        async (pos) => {
            try {
                await axios.post(`/g/${props.token}/verify-location`, {
                    lat: pos.coords.latitude,
                    lng: pos.coords.longitude,
                });
                geoBlocked.value = false;
            } catch {
                geoBlocked.value = true;
            } finally {
                geoChecking.value = false;
            }
        },
        () => {
            geoBlocked.value = true;
            geoChecking.value = false;
        },
    );
}

// ── PIN Setup ──────────────────────────────────────────────────────────────
async function submitPin() {
    pinError.value = null;
    if (pinInput.value.length !== 4 || !/^\d{4}$/.test(pinInput.value)) {
        pinError.value = __('PIN must be 4 digits.');
        return;
    }
    if (pinInput.value !== pinConfirm.value) {
        pinError.value = __('PINs do not match.');
        return;
    }
    pinLoading.value = true;
    try {
        await axios.post(`/g/${props.token}/session`, { pin: pinInput.value });
        view.value = 'hub';
        if (props.venue.require_geolocation && !props.geolocationVerified) {
            requestGeolocation();
        }
    } catch (e) {
        pinError.value = e.response?.data?.message ?? __('Error creating session.');
    } finally {
        pinLoading.value = false;
    }
}

// ── Signal ─────────────────────────────────────────────────────────────────
async function sendSignal() {
    signalLoading.value = true;
    try {
        await axios.post(`/g/${props.token}/signal`, {
            message: signalOnly.value ? null : signalMessage.value || null,
            signal_only: signalOnly.value,
        });
        signalSent.value = true;
        signalOpen.value = false;
        signalMessage.value = '';
        signalOnly.value = false;
        setTimeout(() => { signalSent.value = false; }, 4000);
    } catch {
        // noop
    } finally {
        signalLoading.value = false;
    }
}

// ── Orders ─────────────────────────────────────────────────────────────────
async function loadOrders() {
    ordersPinError.value = null;
    if (ordersPin.value.length !== 4) {
        ordersPinError.value = __('Enter your 4-digit PIN.');
        return;
    }
    ordersLoading.value = true;
    try {
        const res = await axios.get(`/g/${props.token}/orders`, { params: { pin: ordersPin.value } });
        orders.value = res.data.orders;
        ordersVerified.value = true;
    } catch (e) {
        ordersPinError.value = e.response?.status === 403
            ? __('Invalid PIN.')
            : __('Error loading orders.');
    } finally {
        ordersLoading.value = false;
    }
}

// ── Checkout ───────────────────────────────────────────────────────────────
async function requestCheckout() {
    checkoutLoading.value = true;
    try {
        await axios.post(`/g/${props.token}/checkout`);
        checkoutDone.value = true;
        checkoutConfirm.value = false;
    } catch {
        // noop
    } finally {
        checkoutLoading.value = false;
    }
}

// ── Init ───────────────────────────────────────────────────────────────────
onMounted(() => {
    if (props.hasSession && props.venue.require_geolocation && !props.geolocationVerified) {
        requestGeolocation();
    }
});
</script>

<template>
    <GuestLayout :title="venue.name" :venue="venue">

        <!-- ── Delivery Fallback Modal ── -->
        <div v-if="isDeliveryFallback && !deliveryConfirmed" class="fixed inset-0 z-50 bg-black/50 flex items-end sm:items-center justify-center p-4">
            <div class="w-full max-w-sm bg-white rounded-2xl p-6 shadow-xl">
                <h2 class="font-heading text-lg font-bold text-ocean-deep mb-2">{{ __('Delivery or Takeaway?') }}</h2>
                <p class="text-sm text-muted-foreground mb-4">
                    {{ __('This link is not associated with a specific table or location. Your order will be treated as delivery or takeaway.') }}
                </p>
                <button
                    class="w-full rounded-xl bg-primary py-3 text-sm font-semibold text-white active:opacity-80"
                    @click="deliveryConfirmed = true"
                >
                    {{ __('Continue') }}
                </button>
            </div>
        </div>

        <!-- ── PIN Setup ── -->
        <div v-if="view === 'pin-setup'" class="min-h-[60vh] flex flex-col items-center justify-center gap-6 py-8">
            <div class="text-center">
                <div class="mx-auto mb-3 flex h-16 w-16 items-center justify-center rounded-full bg-primary/10">
                    <svg class="h-8 w-8 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
                <h2 class="font-heading text-xl font-bold text-ocean-deep">{{ __('Create your PIN') }}</h2>
                <p class="mt-1 text-sm text-muted-foreground">{{ __('You will use this 4-digit PIN to view your orders.') }}</p>
            </div>

            <div class="w-full max-w-xs space-y-3">
                <div>
                    <label class="mb-1 block text-sm font-medium text-ocean-deep">{{ __('PIN') }}</label>
                    <input
                        v-model="pinInput"
                        type="password"
                        inputmode="numeric"
                        maxlength="4"
                        pattern="\d{4}"
                        placeholder="••••"
                        class="w-full rounded-xl border border-border px-4 py-3 text-center text-xl tracking-widest focus:outline-none focus:ring-2 focus:ring-primary"
                    />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ocean-deep">{{ __('Confirm PIN') }}</label>
                    <input
                        v-model="pinConfirm"
                        type="password"
                        inputmode="numeric"
                        maxlength="4"
                        pattern="\d{4}"
                        placeholder="••••"
                        class="w-full rounded-xl border border-border px-4 py-3 text-center text-xl tracking-widest focus:outline-none focus:ring-2 focus:ring-primary"
                    />
                </div>
                <p v-if="pinError" class="text-center text-sm text-destructive">{{ pinError }}</p>
                <button
                    :disabled="pinLoading"
                    class="w-full rounded-xl bg-primary py-3 text-sm font-semibold text-white disabled:opacity-60 active:opacity-80"
                    @click="submitPin"
                >
                    {{ pinLoading ? __('Saving...') : __('Access') }}
                </button>
            </div>
        </div>

        <!-- ── Hub ── -->
        <template v-if="view === 'hub'">

            <!-- Location badge -->
            <div v-if="serviceLocation" class="mb-4 flex items-center gap-2 rounded-xl bg-white px-4 py-3 shadow-card">
                <svg class="h-4 w-4 text-primary shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span class="text-sm font-medium text-ocean-deep capitalize">{{ serviceLocation.name }}</span>
                <span class="rounded-full bg-muted px-2 py-0.5 text-xs text-muted-foreground capitalize">{{ serviceLocation.type }}</span>
            </div>

            <!-- Geo blocked warning -->
            <div v-if="requiresGeo && geoBlocked" class="mb-4 rounded-xl border border-destructive/30 bg-destructive/5 px-4 py-3 text-sm text-destructive">
                <p class="font-semibold">{{ __('Location required') }}</p>
                <p class="mt-1">{{ __('This venue requires your location to place orders. Allow location access to continue.') }}</p>
                <button class="mt-2 text-xs underline" @click="requestGeolocation">{{ __('Try again') }}</button>
            </div>

            <!-- Geo checking -->
            <div v-if="geoChecking" class="mb-4 rounded-xl bg-muted px-4 py-3 text-sm text-muted-foreground">
                {{ __('Checking your location...') }}
            </div>

            <!-- Signal sent toast -->
            <div v-if="signalSent" class="mb-4 rounded-xl bg-accent/10 border border-accent/30 px-4 py-3 text-sm text-accent font-medium">
                {{ __('Waiter notified!') }}
            </div>

            <!-- Checkout done toast -->
            <div v-if="checkoutDone" class="mb-4 rounded-xl bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700 font-medium">
                {{ __('Bill requested! The waiter will be with you shortly.') }}
            </div>

            <!-- 4 Action cards -->
            <div class="grid grid-cols-2 gap-3">

                <!-- Chamar atendente -->
                <button
                    class="flex flex-col items-center gap-3 rounded-2xl bg-white p-5 shadow-card active:scale-95 transition-transform col-span-2 sm:col-span-1"
                    @click="signalOpen = !signalOpen"
                >
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-amber-100">
                        <svg class="h-6 w-6 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                    </div>
                    <span class="text-sm font-semibold text-ocean-deep">{{ __('Call Waiter') }}</span>
                </button>

                <!-- Ver cardápio -->
                <a
                    :href="`/g/${token}/menu`"
                    class="flex flex-col items-center gap-3 rounded-2xl bg-white p-5 shadow-card active:scale-95 transition-transform col-span-2 sm:col-span-1"
                >
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-primary/10">
                        <svg class="h-6 w-6 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </div>
                    <span class="text-sm font-semibold text-ocean-deep">{{ __('Menu & Order') }}</span>
                    <span v-if="!canOrder" class="text-xs text-muted-foreground">{{ __('(location required)') }}</span>
                </a>

                <!-- Meu pedido -->
                <button
                    class="flex flex-col items-center gap-3 rounded-2xl bg-white p-5 shadow-card active:scale-95 transition-transform"
                    @click="ordersOpen = !ordersOpen"
                >
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-ocean-light">
                        <svg class="h-6 w-6 text-ocean-deep" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                    </div>
                    <span class="text-sm font-semibold text-ocean-deep">{{ __('My Order') }}</span>
                </button>

                <!-- Fechar conta -->
                <button
                    :disabled="!canOrder || checkoutDone"
                    class="flex flex-col items-center gap-3 rounded-2xl bg-white p-5 shadow-card active:scale-95 transition-transform disabled:opacity-40"
                    @click="checkoutConfirm = true"
                >
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-green-100">
                        <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z" />
                        </svg>
                    </div>
                    <span class="text-sm font-semibold text-ocean-deep">{{ __('Request Bill') }}</span>
                </button>
            </div>

            <!-- ── Signal Panel ── -->
            <div v-if="signalOpen" class="mt-4 rounded-2xl bg-white p-5 shadow-card">
                <h3 class="mb-3 font-heading text-sm font-semibold text-ocean-deep">{{ __('Call Waiter') }}</h3>

                <label class="mb-3 flex cursor-pointer items-center gap-3">
                    <input v-model="signalOnly" type="checkbox" class="h-4 w-4 rounded border-border text-primary focus:ring-primary" />
                    <span class="text-sm text-ocean-deep">{{ __('Signal only (no message)') }}</span>
                </label>

                <textarea
                    v-if="!signalOnly"
                    v-model="signalMessage"
                    rows="3"
                    maxlength="500"
                    :placeholder="__('Type your message to the waiter...')"
                    class="w-full resize-none rounded-xl border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                />

                <button
                    :disabled="signalLoading || (!signalOnly && !signalMessage.trim())"
                    class="mt-3 w-full rounded-xl bg-amber-500 py-3 text-sm font-semibold text-white disabled:opacity-50 active:opacity-80"
                    @click="sendSignal"
                >
                    {{ signalLoading ? __('Sending...') : __('Send') }}
                </button>
            </div>

            <!-- ── Orders Panel ── -->
            <div v-if="ordersOpen" class="mt-4 rounded-2xl bg-white p-5 shadow-card">
                <h3 class="mb-3 font-heading text-sm font-semibold text-ocean-deep">{{ __('My Order') }}</h3>

                <template v-if="!ordersVerified">
                    <p class="mb-3 text-xs text-muted-foreground">{{ __('Enter your PIN to view your orders.') }}</p>
                    <input
                        v-model="ordersPin"
                        type="password"
                        inputmode="numeric"
                        maxlength="4"
                        pattern="\d{4}"
                        placeholder="••••"
                        class="w-full rounded-xl border border-border px-4 py-3 text-center text-xl tracking-widest focus:outline-none focus:ring-2 focus:ring-primary"
                    />
                    <p v-if="ordersPinError" class="mt-1 text-xs text-destructive">{{ ordersPinError }}</p>
                    <button
                        :disabled="ordersLoading"
                        class="mt-3 w-full rounded-xl bg-primary py-3 text-sm font-semibold text-white disabled:opacity-50 active:opacity-80"
                        @click="loadOrders"
                    >
                        {{ ordersLoading ? __('Loading...') : __('View Orders') }}
                    </button>
                </template>

                <template v-else>
                    <div v-if="!orders.length" class="py-4 text-center text-sm text-muted-foreground">
                        {{ __('No orders yet.') }}
                    </div>
                    <div v-else class="space-y-4">
                        <div v-for="order in orders" :key="order.id">
                            <p class="mb-1 text-xs font-semibold text-muted-foreground uppercase tracking-wide">
                                {{ __('Order') }} #{{ order.order_number }}
                            </p>
                            <ul class="space-y-2">
                                <li
                                    v-for="item in order.items"
                                    :key="item.id"
                                    class="flex items-start gap-2 rounded-lg border border-border p-3 text-sm"
                                >
                                    <span
                                        class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full"
                                        :style="{ backgroundColor: item.status.color ?? '#94a3b8' }"
                                    />
                                    <div class="flex-1">
                                        <p class="font-medium text-ocean-deep">
                                            {{ item.product_name }}
                                            <span v-if="item.variation_name" class="font-normal text-muted-foreground"> — {{ item.variation_name }}</span>
                                        </p>
                                        <p class="text-xs text-muted-foreground">{{ __('Qty') }}: {{ item.quantity }}</p>
                                    </div>
                                    <span
                                        class="shrink-0 rounded-full px-2 py-0.5 text-xs font-medium"
                                        :style="{ backgroundColor: item.status.color + '22', color: item.status.color }"
                                    >
                                        {{ item.status.name }}
                                    </span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </template>
            </div>

            <!-- ── Checkout Confirm Modal ── -->
            <div v-if="checkoutConfirm" class="fixed inset-0 z-50 bg-black/50 flex items-end sm:items-center justify-center p-4">
                <div class="w-full max-w-sm bg-white rounded-2xl p-6 shadow-xl">
                    <h2 class="font-heading text-lg font-bold text-ocean-deep mb-2">{{ __('Request Bill?') }}</h2>
                    <p class="text-sm text-muted-foreground mb-4">{{ __('The waiter will be notified to bring your bill.') }}</p>
                    <div class="flex gap-3">
                        <button
                            :disabled="checkoutLoading"
                            class="flex-1 rounded-xl bg-primary py-3 text-sm font-semibold text-white disabled:opacity-50 active:opacity-80"
                            @click="requestCheckout"
                        >
                            {{ checkoutLoading ? __('Sending...') : __('Yes, request bill') }}
                        </button>
                        <button
                            class="flex-1 rounded-xl border border-border py-3 text-sm font-semibold text-ocean-deep active:opacity-80"
                            @click="checkoutConfirm = false"
                        >
                            {{ __('Cancel') }}
                        </button>
                    </div>
                </div>
            </div>

        </template>

    </GuestLayout>
</template>
