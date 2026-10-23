<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import AppButton from '@/Components/AppButton.vue';
import ProductDetailDrawer from '@/Components/Guest/ProductDetailDrawer.vue';
import DeliveryCheckoutPanel from '@/Components/Guest/Delivery/DeliveryCheckoutPanel.vue';
import GuestMenuCatalog from '@/Components/Guest/GuestMenuCatalog.vue';
import { mergeGuestCartItem } from '@/Utils/guestCart';
import { ref, computed } from 'vue';
import { useTranslate } from '@/Composables/useTranslate';

const props = defineProps({
    token: {
        type: String,
        default: null,
    },
    preview: {
        type: Boolean,
        default: false,
    },
    venue: Object,
    categories: Array,
    deliveryEnabled: Boolean,
    pickupEnabled: Boolean,
    acceptedPaymentMethods: Array,
    serviceFeePercent: Number,
});

const __ = useTranslate();

const selectedProduct = ref(null);
const showProductDrawer = ref(false);
const checkoutOpen = ref(false);
const cartItems = ref([]);
const editingCartIndex = ref(null);

const cartCount = computed(() => cartItems.value.reduce((s, i) => s + i.quantity, 0));
const editingItem = computed(() => (
    editingCartIndex.value === null ? null : cartItems.value[editingCartIndex.value] ?? null
));

function findProduct(productId) {
    for (const category of props.categories ?? []) {
        const product = category.products?.find((item) => item.id === productId);
        if (product) {
            return product;
        }
    }

    return null;
}

function openProduct(product) {
    editingCartIndex.value = null;
    selectedProduct.value = product;
    showProductDrawer.value = true;
}

function openCartItem(index) {
    const item = cartItems.value[index];
    const product = findProduct(item?.product_id);
    if (!product) {
        return;
    }

    editingCartIndex.value = index;
    selectedProduct.value = product;
    showProductDrawer.value = true;
}

function addToCart(item) {
    if (editingCartIndex.value !== null) {
        cartItems.value.splice(editingCartIndex.value, 1, item);
        editingCartIndex.value = null;
        return;
    }

    mergeGuestCartItem(cartItems.value, item);
}

function removeFromCart(index) {
    cartItems.value.splice(index, 1);
}

function handleOrderPlaced(orderId) {
    cartItems.value = [];
    checkoutOpen.value = false;

    if (props.preview) {
        return;
    }

    window.location.href = route('orders.track', orderId);
}
</script>

<template>
    <GuestLayout
        :title="venue.name + ' — Delivery'"
        :venue="venue"
        :preview-watermark="preview ? __('Delivery version') : null"
    >
        <template v-if="preview" #headerTitle>{{ __('Customer version - Delivery') }}</template>
        <template v-if="preview" #headerSubtitle>{{ __('Adaptive screen for phone, tablet or computer') }}</template>
        <template v-if="preview" #header>
            <div class="flex flex-wrap items-center justify-end gap-2">
                <AppButton size="sm" variant="accent" :href="route('menu.preview.customer')">{{ __('Customer version - Table') }}</AppButton>
                <AppButton size="sm" :href="route('menu.index')">{{ __('Back') }}</AppButton>
            </div>
        </template>

        <GuestMenuCatalog
            :venue="venue"
            :categories="categories"
            :preview="preview"
            :empty-description="__('This venue has no menu items yet.')"
            @open-product="openProduct"
        />

        <!-- Cart FAB -->
        <Teleport to="body">
            <button
                v-if="cartCount > 0"
                class="fixed bottom-24 left-1/2 z-50 flex -translate-x-1/2 items-center gap-2 rounded-full border-4 border-warm-gold bg-primary px-6 py-3 text-sm font-semibold text-white shadow-lg active:opacity-80"
                @click="checkoutOpen = true"
            >
                <span class="flex h-5 w-5 items-center justify-center rounded-full bg-white text-xs font-bold text-primary">{{ cartCount }}</span>
                {{ __('View order') }}
            </button>
        </Teleport>

        <ProductDetailDrawer
            v-if="selectedProduct"
            v-model="showProductDrawer"
            :product="selectedProduct"
            :preview="preview"
            :editing-item="editingItem"
            @add-to-cart="addToCart"
        />

        <DeliveryCheckoutPanel
            v-model="checkoutOpen"
            :token="token"
            :items="cartItems"
            :preview="preview"
            :delivery-enabled="deliveryEnabled"
            :pickup-enabled="pickupEnabled"
            :accepted-payment-methods="acceptedPaymentMethods"
            :service-fee-percent="serviceFeePercent"
            :venue="venue"
            @remove="removeFromCart"
            @select-item="openCartItem"
            @order-placed="handleOrderPlaced"
        />
    </GuestLayout>
</template>
