<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import AppButton from '@/Components/AppButton.vue';
import ProductDetailDrawer from '@/Components/Guest/ProductDetailDrawer.vue';
import CartPanel from '@/Components/Guest/CartPanel.vue';
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
    serviceLocation: Object,
    categories: Array,
});

const __ = useTranslate();

const selectedProduct = ref(null);
const showProductDrawer = ref(false);
const cartOpen = ref(false);
const cartItems = ref([]);
const orderPlaced = ref(false);
const orderError = ref(null);
const editingCartIndex = ref(null);

const cartCount = computed(() => cartItems.value.reduce((s, i) => s + i.quantity, 0));
const editingItem = computed(() => (
    editingCartIndex.value === null ? null : cartItems.value[editingCartIndex.value] ?? null
));

const tableLabel = computed(() => {
    const name = props.serviceLocation?.name ?? '';
    const match = String(name).match(/(\d+)\s*$/);

    if (match) {
        return __('Table no. :number', { number: match[1] });
    }

    return name;
});

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

function handleOrderPlaced() {
    cartItems.value = [];
    orderPlaced.value = true;
    setTimeout(() => { orderPlaced.value = false; }, 4000);
}
</script>

<template>
    <GuestLayout
        :title="venue.name + ' — Cardápio'"
        :venue="venue"
        :preview-watermark="preview ? __('Table version') : null"
    >
        <template v-if="preview" #headerTitle>{{ __('Customer version - Table') }}</template>
        <template v-if="preview" #headerSubtitle>{{ __('Adaptive screen for phone, tablet or computer') }}</template>
        <template v-if="preview" #header>
            <div class="flex flex-wrap items-center justify-end gap-2">
                <AppButton size="sm" variant="accent" :href="route('menu.preview.customer.delivery')">{{ __('Customer version - Delivery') }}</AppButton>
                <AppButton size="sm" :href="route('menu.index')">{{ __('Back') }}</AppButton>
            </div>
        </template>
        <template v-if="tableLabel" #frameLabel>
            <span class="whitespace-nowrap rounded-full border-2 border-ocean-deep bg-primary px-4 py-1 text-center font-heading text-sm font-bold text-white">
                {{ tableLabel }}
            </span>
        </template>

        <!-- Back link -->
        <a v-if="!preview" :href="`/g/${token}`" class="mb-4 flex items-center gap-1 text-sm text-primary hover:underline">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
            </svg>
            {{ __('Back') }}
        </a>

        <!-- Order placed toast -->
        <div v-if="orderPlaced" class="mb-4 rounded-xl bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700 font-medium">
            {{ __('Order placed! We are preparing it for you.') }}
        </div>
        <div v-if="orderError" class="mb-4 rounded-xl bg-destructive/5 border border-destructive/30 px-4 py-3 text-sm text-destructive">
            {{ orderError }}
        </div>

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
                @click="cartOpen = true"
            >
                <span class="flex h-5 w-5 items-center justify-center rounded-full bg-white text-xs font-bold text-primary">{{ cartCount }}</span>
                {{ __('View Cart') }}
            </button>
        </Teleport>

        <!-- Product drawer -->
        <ProductDetailDrawer
            v-if="selectedProduct"
            v-model="showProductDrawer"
            :product="selectedProduct"
            :preview="preview"
            :editing-item="editingItem"
            @add-to-cart="addToCart"
        />

        <!-- Cart panel -->
        <CartPanel
            v-model="cartOpen"
            :token="token"
            :items="cartItems"
            :preview="preview"
            @remove="removeFromCart"
            @select-item="openCartItem"
            @order-placed="handleOrderPlaced"
            @error="orderError = $event"
        />

    </GuestLayout>
</template>
