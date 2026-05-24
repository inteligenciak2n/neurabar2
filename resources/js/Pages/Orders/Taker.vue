<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import AppButton from '@/Components/AppButton.vue';
import AppBadge from '@/Components/AppBadge.vue';
import AppConfirmModal from '@/Components/AppConfirmModal.vue';
import AppEmptyState from '@/Components/AppEmptyState.vue';
import AppSkeleton from '@/Components/AppSkeleton.vue';
import { useForm, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    attendance: Object,
    categories: Array,
    stations: Array,
});

const selectedCategoryId = ref(props.categories?.[0]?.id ?? null);
const cart = ref([]); // [{ product, quantity, notes }]
const showConfirm = ref(false);

const selectedCategory = computed(() => props.categories.find((c) => c.id === selectedCategoryId.value));

const form = useForm({ items: [] });

const addToCart = (product) => {
    const existing = cart.value.find((i) => i.product.id === product.id);
    if (existing) {
        existing.quantity++;
    } else {
        cart.value.push({ product, quantity: 1, notes: '' });
    }
};

const removeFromCart = (index) => {
    cart.value.splice(index, 1);
};

const cartTotal = computed(() =>
    cart.value.reduce((sum, item) => sum + Number(item.product.price) * item.quantity, 0).toFixed(2),
);

const placeOrder = () => {
    form.items = cart.value.map((i) => ({
        product_id: i.product.id,
        quantity:   i.quantity,
        unit_price: i.product.price,
        notes:      i.notes || null,
        modifiers:  [],
    }));

    form.post(route('attendances.orders.store', props.attendance.id), {
        onSuccess: () => {
            cart.value = [];
            showConfirm.value = false;
        },
    });
};
</script>

<template>
    <AppLayout :title="`Order Taker — ${attendance.customer_identifier ?? attendance.channel}`">
        <template #header>
            <div class="flex items-center gap-4">
                <Link :href="route('attendances.show', attendance.id)" class="text-sm font-medium text-primary hover:underline">
                    ← {{ attendance.customer_identifier ?? attendance.channel }}
                </Link>
                <h1 class="font-heading text-2xl font-bold text-ocean-deep">Take Order</h1>
            </div>
        </template>

        <div class="flex h-[calc(100vh-10rem)] gap-4 overflow-hidden">
            <!-- Left: Menu -->
            <div class="flex min-w-0 flex-1 flex-col overflow-hidden">
                <!-- Category tabs -->
                <div class="mb-3 flex gap-2 overflow-x-auto pb-1">
                    <button
                        v-for="category in categories"
                        :key="category.id"
                        class="whitespace-nowrap rounded-full px-3 py-1.5 text-sm font-medium transition-colors"
                        :class="selectedCategoryId === category.id ? 'bg-primary text-white' : 'bg-white text-ocean-deep shadow-card hover:bg-ocean-light'"
                        @click="selectedCategoryId = category.id"
                    >
                        {{ category.name }}
                    </button>
                </div>

                <!-- Products -->
                <div class="flex-1 overflow-y-auto">
                    <AppEmptyState
                        v-if="!categories.length"
                        title="No menu available"
                        description="There are no active products in the menu."
                    />
                    <AppEmptyState
                        v-else-if="!selectedCategory?.products?.length"
                        title="No products in this category"
                        description="Select another category."
                    />
                    <div v-else class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                        <button
                            v-for="product in selectedCategory.products"
                            :key="product.id"
                            class="rounded-lg bg-white p-3 text-left shadow-card transition hover:ring-2 hover:ring-primary"
                            @click="addToCart(product)"
                        >
                            <p class="font-body text-sm font-semibold text-ocean-deep">{{ product.name }}</p>
                            <p class="mt-1 font-heading text-sm font-bold text-primary">R$ {{ Number(product.price).toFixed(2) }}</p>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Right: Cart -->
            <div class="flex w-72 flex-shrink-0 flex-col rounded-lg bg-white shadow-card">
                <div class="border-b border-border p-4">
                    <h2 class="font-heading text-sm font-bold text-ocean-deep">Cart</h2>
                </div>

                <div class="flex-1 overflow-y-auto p-4">
                    <AppEmptyState v-if="!cart.length" title="Cart is empty" description="Tap a product to add it." />

                    <div v-else class="space-y-3">
                        <div v-for="(item, index) in cart" :key="item.product.id" class="rounded-md border border-border p-2">
                            <div class="flex items-center justify-between">
                                <p class="text-sm font-semibold text-ocean-deep">{{ item.product.name }}</p>
                                <button class="text-destructive hover:underline text-xs" @click="removeFromCart(index)">Remove</button>
                            </div>
                            <div class="mt-1 flex items-center gap-2">
                                <button
                                    class="flex h-6 w-6 items-center justify-center rounded-full border border-border text-sm hover:bg-muted"
                                    @click="item.quantity > 1 ? item.quantity-- : removeFromCart(index)"
                                >−</button>
                                <span class="w-4 text-center text-sm">{{ item.quantity }}</span>
                                <button
                                    class="flex h-6 w-6 items-center justify-center rounded-full border border-border text-sm hover:bg-muted"
                                    @click="item.quantity++"
                                >+</button>
                                <span class="ml-auto text-xs text-muted-foreground">
                                    R$ {{ (Number(item.product.price) * item.quantity).toFixed(2) }}
                                </span>
                            </div>
                            <input
                                v-model="item.notes"
                                type="text"
                                placeholder="Notes (optional)"
                                class="mt-1 w-full rounded border border-border px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-primary"
                            />
                        </div>
                    </div>
                </div>

                <div class="border-t border-border p-4">
                    <div class="mb-3 flex justify-between text-sm font-semibold text-ocean-deep">
                        <span>Total</span>
                        <span>R$ {{ cartTotal }}</span>
                    </div>
                    <AppButton
                        class="w-full"
                        :disabled="!cart.length || form.processing"
                        :loading="form.processing"
                        @click="showConfirm = true"
                    >
                        Place Order ({{ cart.length }} items)
                    </AppButton>
                </div>
            </div>
        </div>

        <AppConfirmModal
            :show="showConfirm"
            title="Place Order"
            :message="`Send ${cart.length} item(s) to the kitchen?`"
            confirm-label="Place Order"
            @confirm="placeOrder"
            @cancel="showConfirm = false"
        />
    </AppLayout>
</template>
