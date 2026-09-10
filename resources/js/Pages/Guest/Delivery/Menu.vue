<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import AppEmptyState from '@/Components/AppEmptyState.vue';
import ProductDetailDrawer from '@/Components/Guest/ProductDetailDrawer.vue';
import DeliveryCheckoutPanel from '@/Components/Guest/Delivery/DeliveryCheckoutPanel.vue';
import { ref, computed } from 'vue';
import { useTranslate } from '@/Composables/useTranslate';

const props = defineProps({
    token: String,
    venue: Object,
    categories: Array,
    deliveryEnabled: Boolean,
    pickupEnabled: Boolean,
    acceptedPaymentMethods: Array,
    serviceFeePercent: Number,
});

const __ = useTranslate();

const selectedCategoryId = ref(props.categories?.[0]?.id ?? null);
const selectedProduct = ref(null);
const showProductDrawer = ref(false);
const checkoutOpen = ref(false);
const cartItems = ref([]);

const selectedCategory = computed(() =>
    props.categories.find((c) => c.id === selectedCategoryId.value),
);

const cartCount = computed(() => cartItems.value.reduce((s, i) => s + i.quantity, 0));
const cartSubtotal = computed(() => cartItems.value.reduce((s, i) => s + i.unit_price * i.quantity, 0));

function openProduct(product) {
    selectedProduct.value = product;
    showProductDrawer.value = true;
}

function addToCart(item) {
    const existing = cartItems.value.find(
        (i) => i.product_id === item.product_id && i.variation_id === item.variation_id,
    );
    if (existing) {
        existing.quantity += item.quantity;
    } else {
        cartItems.value.push(item);
    }
}

function removeFromCart(index) {
    cartItems.value.splice(index, 1);
}

function handleOrderPlaced(orderId) {
    cartItems.value = [];
    checkoutOpen.value = false;
    window.location.href = route('orders.track', orderId);
}
</script>

<template>
    <GuestLayout :title="venue.name + ' — Delivery'" :venue="venue">
        <AppEmptyState
            v-if="!categories.length"
            :title="__('Menu not available')"
            :description="__('This venue has no items available for delivery yet.')"
        />

        <template v-else>
            <!-- Category tabs -->
            <div class="mb-4 flex gap-2 overflow-x-auto pb-1 border-b border-muted">
                <button
                    v-for="category in categories"
                    :key="category.id"
                    class="whitespace-nowrap rounded-md px-4 py-2 text-sm font-medium transition-colors"
                    :class="selectedCategoryId === category.id ? 'bg-primary text-white' : 'bg-white text-ocean-deep shadow-card hover:bg-ocean-light'"
                    @click="selectedCategoryId = category.id"
                >
                    {{ category.name }}
                </button>
            </div>

            <template v-if="selectedCategory">
                <AppEmptyState
                    v-if="!selectedCategory.products?.length"
                    :title="__('No items in this category')"
                    :description="__('Check back later.')"
                />
                <div v-else class="grid grid-cols-1 gap-3 sm:grid-cols-2 pb-24">
                    <button
                        v-for="product in selectedCategory.products"
                        :key="product.id"
                        class="rounded-xl bg-white p-4 shadow-card text-left active:scale-95 transition-transform"
                        @click="openProduct(product)"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex-1">
                                <p class="font-body text-sm font-semibold text-ocean-deep">{{ product.name }}</p>
                                <p v-if="product.description" class="mt-1 text-xs text-muted-foreground line-clamp-2">{{ product.description }}</p>
                            </div>
                            <span class="whitespace-nowrap font-heading text-sm font-bold text-primary">
                                R$ {{ Number(product.price).toFixed(2) }}
                            </span>
                        </div>
                    </button>
                </div>
            </template>
        </template>

        <!-- Cart FAB -->
        <button
            v-if="cartCount > 0"
            class="fixed bottom-6 left-1/2 -translate-x-1/2 flex items-center gap-2 rounded-full bg-primary px-6 py-3 text-sm font-semibold text-white shadow-lg active:opacity-80 z-40"
            @click="checkoutOpen = true"
        >
            <span class="flex h-5 w-5 items-center justify-center rounded-full bg-white text-primary text-xs font-bold">{{ cartCount }}</span>
            {{ __('Checkout') }} · R$ {{ cartSubtotal.toFixed(2) }}
        </button>

        <ProductDetailDrawer
            v-if="selectedProduct"
            v-model="showProductDrawer"
            :product="selectedProduct"
            @add-to-cart="addToCart"
        />

        <DeliveryCheckoutPanel
            v-model="checkoutOpen"
            :token="token"
            :items="cartItems"
            :delivery-enabled="deliveryEnabled"
            :pickup-enabled="pickupEnabled"
            :accepted-payment-methods="acceptedPaymentMethods"
            :service-fee-percent="serviceFeePercent"
            @remove="removeFromCart"
            @order-placed="handleOrderPlaced"
        />
    </GuestLayout>
</template>
