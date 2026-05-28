<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import AppButton from '@/Components/AppButton.vue';
import AppConfirmModal from '@/Components/AppConfirmModal.vue';
import AppEmptyState from '@/Components/AppEmptyState.vue';
import { useForm, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    attendance: Object,
    categories: Array,
    combos: Array,
    stations: Array,
});

// Cart item: { key, product, variationId, variation, quantity, notes, modifiers: [{modifier_option_id}], comboId }
const cart = ref([]);
const selectedCategoryId = ref(props.categories?.[0]?.id ?? null);
const activeTab = ref('menu'); // 'menu' | 'combos'
const showConfirm = ref(false);

// Product modal state
const productModal = ref(false);
const modalProduct = ref(null);
const modalVariationId = ref(null);
const modalModifiers = ref({}); // groupId -> [optionId, ...]
const modalNotes = ref('');
const modalComboId = ref(null);

const selectedCategory = computed(() => props.categories?.find((c) => c.id === selectedCategoryId.value));

const form = useForm({ items: [], combos: [] });

const productPrice = (product, variationId) => {
    if (variationId) {
        const v = product.variations?.find((v) => v.id === variationId);
        if (v) return Number(v.price);
    }
    return Number(product.price);
};

const openProductModal = (product, comboId = null) => {
    modalProduct.value = product;
    modalVariationId.value = product.variations?.length ? product.variations[0].id : null;
    modalModifiers.value = {};
    modalNotes.value = '';
    modalComboId.value = comboId;
    productModal.value = true;
};

const isModifierSelected = (groupId, optionId) => {
    return (modalModifiers.value[groupId] ?? []).includes(optionId);
};

const toggleModifier = (group, optionId) => {
    if (!modalModifiers.value[group.id]) {
        modalModifiers.value[group.id] = [];
    }
    const list = modalModifiers.value[group.id];
    if (!group.multiple_selection) {
        modalModifiers.value[group.id] = [optionId];
    } else {
        const idx = list.indexOf(optionId);
        if (idx >= 0) list.splice(idx, 1);
        else list.push(optionId);
    }
};

const requiredGroupsMissing = computed(() => {
    if (!modalProduct.value) return [];
    return (modalProduct.value.modifier_groups ?? [])
        .filter((g) => g.required && !(modalModifiers.value[g.id]?.length));
});

const confirmAddToCart = () => {
    if (requiredGroupsMissing.value.length) return;

    const product = modalProduct.value;
    const variationId = modalVariationId.value;
    const variation = product.variations?.find((v) => v.id === variationId) ?? null;
    const allOptions = (product.modifier_groups ?? []).flatMap((g) => g.options ?? []);
    const flatModifiers = Object.values(modalModifiers.value).flat().map((id) => {
        const option = allOptions.find((o) => o.id === id);
        return { modifier_option_id: id, name: option?.name ?? '' };
    });

    // For combo items, always add as separate line (tagged with comboId)
    if (modalComboId.value) {
        cart.value.push({
            key: crypto.randomUUID(),
            product,
            variationId,
            variation,
            quantity: 1,
            notes: modalNotes.value || null,
            modifiers: flatModifiers,
            comboId: modalComboId.value,
        });
        productModal.value = false;
        return;
    }

    // For regular items, group by product+variation (no combo)
    const existing = cart.value.find(
        (i) => !i.comboId && i.product.id === product.id && i.variationId === variationId,
    );
    if (existing && !flatModifiers.length) {
        existing.quantity++;
    } else {
        cart.value.push({
            key: crypto.randomUUID(),
            product,
            variationId,
            variation,
            quantity: 1,
            notes: modalNotes.value || null,
            modifiers: flatModifiers,
            comboId: null,
        });
    }
    productModal.value = false;
};

const addToCart = (product) => {
    if (product.variations?.length || product.modifier_groups?.length) {
        openProductModal(product);
    } else {
        const existing = cart.value.find((i) => !i.comboId && i.product.id === product.id && !i.variationId);
        if (existing) {
            existing.quantity++;
        } else {
            cart.value.push({
                key: crypto.randomUUID(),
                product,
                variationId: null,
                variation: null,
                quantity: 1,
                notes: null,
                modifiers: [],
                comboId: null,
            });
        }
    }
};

const addComboToCart = (combo) => {
    const comboId = crypto.randomUUID(); // shared key for all items from this combo
    combo.items.forEach((comboItem) => {
        cart.value.push({
            key: crypto.randomUUID(),
            product: comboItem.product,
            variationId: comboItem.variation_id ?? null,
            variation: comboItem.variation ?? null,
            quantity: comboItem.quantity ?? 1,
            notes: null,
            modifiers: [],
            comboId,
            comboName: combo.name,
        });
    });
};

const removeFromCart = (key) => {
    const item = cart.value.find((i) => i.key === key);
    if (item?.comboId) {
        // Remove all items from the same combo
        cart.value = cart.value.filter((i) => i.comboId !== item.comboId);
    } else {
        cart.value = cart.value.filter((i) => i.key !== key);
    }
};

const cartTotal = computed(() =>
    cart.value
        .reduce((sum, item) => sum + productPrice(item.product, item.variationId) * item.quantity, 0)
        .toFixed(2),
);

const groupedCart = computed(() => {
    // separate combo groups from standalone items
    const groups = [];
    const seenCombos = new Set();

    cart.value.forEach((item) => {
        if (item.comboId) {
            if (!seenCombos.has(item.comboId)) {
                seenCombos.add(item.comboId);
                groups.push({ isCombo: true, comboId: item.comboId, comboName: item.comboName, items: [] });
            }
            groups.find((g) => g.comboId === item.comboId).items.push(item);
        } else {
            groups.push({ isCombo: false, item });
        }
    });
    return groups;
});

const placeOrder = () => {
    const regularItems = cart.value.filter((i) => !i.comboId);
    const comboItems = cart.value.filter((i) => i.comboId);

    // build unique combo entries
    const comboMap = {};
    comboItems.forEach((i) => {
        comboMap[i.comboId] = { combo_id: i.comboId };
    });

    form.items = regularItems.map((i) => ({
        product_id: i.product.id,
        variation_id: i.variationId ?? null,
        quantity: i.quantity,
        unit_price: productPrice(i.product, i.variationId),
        notes: i.notes || null,
        modifiers: i.modifiers ?? [],
    }));

    form.combos = Object.values(comboMap);

    form.post(route('attendances.orders.store', props.attendance.id), {
        onSuccess: () => {
            cart.value = [];
            showConfirm.value = false;
        },
    });
};
</script>

<template>
    <AppLayout :title="`${__('Order Taker')} — ${attendance.customer_identifier ?? attendance.channel}`">
        <template #header>
            <div class="flex items-center gap-4">
                <Link :href="route('attendances.show', attendance.id)" class="text-sm font-medium text-primary hover:underline">
                    ← {{ attendance.customer_identifier ?? attendance.channel }}
                </Link>
                <h1 class="font-heading text-2xl font-bold text-ocean-deep">{{ __('Take Order') }}</h1>
            </div>
        </template>

        <div class="flex h-[calc(100vh-10rem)] gap-4 overflow-hidden">
            <!-- Left: Menu / Combos -->
            <div class="flex min-w-0 flex-1 flex-col overflow-hidden">
                <!-- Tab bar -->
                <div class="mb-3 flex gap-2">
                    <button
                        class="rounded-full px-4 py-1.5 text-sm font-medium transition-colors"
                        :class="activeTab === 'menu' ? 'bg-primary text-white' : 'bg-white text-ocean-deep shadow-card hover:bg-ocean-light'"
                        @click="activeTab = 'menu'"
                    >{{ __('Menu') }}</button>
                    <button
                        v-if="combos?.length"
                        class="rounded-full px-4 py-1.5 text-sm font-medium transition-colors"
                        :class="activeTab === 'combos' ? 'bg-primary text-white' : 'bg-white text-ocean-deep shadow-card hover:bg-ocean-light'"
                        @click="activeTab = 'combos'"
                    >{{ __('Combos') }}</button>
                </div>

                <!-- Menu tab -->
                <template v-if="activeTab === 'menu'">
                    <!-- Category tabs -->
                    <div class="mb-3 flex gap-2 overflow-x-auto pb-1">
                        <button
                            v-for="category in categories"
                            :key="category.id"
                            class="whitespace-nowrap rounded-full px-3 py-1.5 text-sm font-medium transition-colors"
                            :class="selectedCategoryId === category.id ? 'bg-ocean-deep text-white' : 'bg-white text-ocean-deep shadow-card hover:bg-ocean-light'"
                            @click="selectedCategoryId = category.id"
                        >
                            {{ category.name }}
                        </button>
                    </div>

                    <div class="flex-1 overflow-y-auto">
                        <AppEmptyState v-if="!categories.length" :title="__('No menu available')" :description="__('There are no active products in the menu.')" />
                        <AppEmptyState v-else-if="!selectedCategory?.products?.length" :title="__('No products in this category')" :description="__('Select another category.')" />
                        <div v-else class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                            <button
                                v-for="product in selectedCategory.products"
                                :key="product.id"
                                class="rounded-lg bg-white p-3 text-left shadow-card transition hover:ring-2 hover:ring-primary"
                                @click="addToCart(product)"
                            >
                                <p class="font-body text-sm font-semibold text-ocean-deep">{{ product.name }}</p>
                                <p class="mt-1 font-heading text-sm font-bold text-primary">R$ {{ Number(product.price).toFixed(2) }}</p>
                                <span v-if="product.variations?.length" class="mt-1 inline-block rounded bg-ocean-light px-1.5 py-0.5 text-xs text-ocean-deep">{{ __('Variations') }}</span>
                                <span v-if="product.modifier_groups?.length" class="mt-1 inline-block rounded bg-ocean-light px-1.5 py-0.5 text-xs text-ocean-deep">{{ __('Modifiers') }}</span>
                            </button>
                        </div>
                    </div>
                </template>

                <!-- Combos tab -->
                <template v-if="activeTab === 'combos'">
                    <div class="flex-1 overflow-y-auto">
                        <AppEmptyState v-if="!combos?.length" :title="__('No combos available')" :description="__('No active combos configured.')" />
                        <div v-else class="space-y-3">
                            <div
                                v-for="combo in combos"
                                :key="combo.id"
                                class="rounded-lg bg-white p-4 shadow-card"
                            >
                                <div class="flex items-start justify-between">
                                    <div>
                                        <p class="font-body font-semibold text-ocean-deep">{{ combo.name }}</p>
                                        <ul class="mt-1 space-y-0.5">
                                            <li v-for="item in combo.items" :key="item.id" class="text-xs text-muted-foreground">
                                                {{ item.product?.name }}<span v-if="item.variation"> — {{ item.variation.name }}</span>
                                                <span v-if="item.quantity > 1"> ×{{ item.quantity }}</span>
                                            </li>
                                        </ul>
                                    </div>
                                    <AppButton size="sm" @click="addComboToCart(combo)">{{ __('Add') }}</AppButton>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Right: Cart -->
            <div class="flex w-72 flex-shrink-0 flex-col rounded-lg bg-white shadow-card">
                <div class="border-b border-border p-4">
                    <h2 class="font-heading text-sm font-bold text-ocean-deep">{{ __('Cart') }}</h2>
                </div>

                <div class="flex-1 overflow-y-auto p-4">
                    <AppEmptyState v-if="!cart.length" :title="__('Cart is empty')" :description="__('Tap a product to add it.')" />

                    <div v-else class="space-y-3">
                        <template v-for="group in groupedCart" :key="group.isCombo ? group.comboId : group.item.key">
                            <!-- Combo group -->
                            <div v-if="group.isCombo" class="rounded-md border border-primary/30 bg-primary/5 p-2">
                                <div class="mb-1 flex items-center justify-between">
                                    <span class="text-xs font-semibold text-primary">🍱 {{ group.comboName }}</span>
                                    <button class="text-xs text-destructive hover:underline" @click="removeFromCart(group.items[0].key)">{{ __('Remove') }}</button>
                                </div>
                                <ul class="space-y-0.5">
                                    <li v-for="ci in group.items" :key="ci.key" class="text-xs text-ocean-deep">
                                        {{ ci.product.name }}<span v-if="ci.variation"> — {{ ci.variation.name }}</span>
                                    </li>
                                </ul>
                            </div>

                            <!-- Regular item -->
                            <div v-else class="rounded-md border border-border p-2">
                                <div class="flex items-center justify-between">
                                    <p class="text-sm font-semibold text-ocean-deep">
                                        {{ group.item.product.name }}
                                        <span v-if="group.item.variation" class="text-xs text-muted-foreground"> ({{ group.item.variation.name }})</span>
                                    </p>
                                    <button class="text-xs text-destructive hover:underline" @click="removeFromCart(group.item.key)">{{ __('Remove') }}</button>
                                </div>
                                <div v-if="group.item.modifiers?.length" class="mt-0.5 flex flex-wrap gap-1">
                                    <span v-for="m in group.item.modifiers" :key="m.modifier_option_id" class="rounded bg-ocean-light px-1.5 py-0.5 text-xs text-ocean-deep">
                                        {{ m.name }}
                                    </span>
                                </div>
                                <div class="mt-1 flex items-center gap-2">
                                    <button
                                        class="flex h-6 w-6 items-center justify-center rounded-full border border-border text-sm hover:bg-muted"
                                        @click="group.item.quantity > 1 ? group.item.quantity-- : removeFromCart(group.item.key)"
                                    >−</button>
                                    <span class="w-4 text-center text-sm">{{ group.item.quantity }}</span>
                                    <button
                                        class="flex h-6 w-6 items-center justify-center rounded-full border border-border text-sm hover:bg-muted"
                                        @click="group.item.quantity++"
                                    >+</button>
                                    <span class="ml-auto text-xs text-muted-foreground">
                                        R$ {{ (productPrice(group.item.product, group.item.variationId) * group.item.quantity).toFixed(2) }}
                                    </span>
                                </div>
                                <input
                                    v-model="group.item.notes"
                                    type="text"
                                    :placeholder="__('Notes (optional)')"
                                    class="mt-1 w-full rounded border border-border px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-primary"
                                />
                            </div>
                        </template>
                    </div>
                </div>

                <div class="border-t border-border p-4">
                    <div class="mb-3 flex justify-between text-sm font-semibold text-ocean-deep">
                        <span>{{ __('Total') }}</span>
                        <span>R$ {{ cartTotal }}</span>
                    </div>
                    <AppButton
                        class="w-full"
                        :disabled="!cart.length || form.processing"
                        :loading="form.processing"
                        @click="showConfirm = true"
                    >
                        {{ __('Place Order') }} ({{ cart.length }} {{ __('items') }})
                    </AppButton>
                </div>
            </div>
        </div>

        <!-- Product Modal (variations + modifiers) -->
        <div v-if="productModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="w-full max-w-md rounded-xl bg-white shadow-xl">
                <div class="border-b border-border px-6 py-4">
                    <h3 class="font-heading text-lg font-bold text-ocean-deep">{{ modalProduct.name }}</h3>
                </div>
                <div class="max-h-[60vh] overflow-y-auto px-6 py-4 space-y-4">
                    <!-- Variations -->
                    <div v-if="modalProduct.variations?.length">
                        <p class="mb-2 text-sm font-semibold text-ocean-deep">{{ __('Choose variation') }} <span class="text-destructive">*</span></p>
                        <div class="space-y-1">
                            <label
                                v-for="v in modalProduct.variations"
                                :key="v.id"
                                class="flex cursor-pointer items-center justify-between rounded-md border p-2 text-sm"
                                :class="modalVariationId === v.id ? 'border-primary bg-primary/5' : 'border-border'"
                            >
                                <div class="flex items-center gap-2">
                                    <input type="radio" :value="v.id" v-model="modalVariationId" class="accent-primary" />
                                    {{ v.name }}
                                </div>
                                <span class="text-xs text-muted-foreground">R$ {{ Number(v.price).toFixed(2) }}</span>
                            </label>
                        </div>
                    </div>

                    <!-- Modifier groups -->
                    <div v-for="group in modalProduct.modifier_groups" :key="group.id">
                        <p class="mb-2 text-sm font-semibold text-ocean-deep">
                            {{ group.name }}
                            <span v-if="group.required" class="text-destructive">*</span>
                            <span v-else class="text-xs font-normal text-muted-foreground"> ({{ __('optional') }})</span>
                        </p>
                        <div class="space-y-1">
                            <label
                                v-for="option in group.options"
                                :key="option.id"
                                class="flex cursor-pointer items-center justify-between rounded-md border p-2 text-sm"
                                :class="isModifierSelected(group.id, option.id) ? 'border-primary bg-primary/5' : 'border-border'"
                            >
                                <div class="flex items-center gap-2">
                                    <input
                                        :type="!group.multiple_selection ? 'radio' : 'checkbox'"
                                        :name="`group-${group.id}`"
                                        :value="option.id"
                                        :checked="isModifierSelected(group.id, option.id)"
                                        class="accent-primary"
                                        @change="toggleModifier(group, option.id)"
                                    />
                                    {{ option.name }}
                                </div>
                                <span v-if="option.extra_price > 0" class="text-xs text-muted-foreground">+R$ {{ Number(option.extra_price).toFixed(2) }}</span>
                            </label>
                        </div>
                        <p v-if="group.required && !modalModifiers[group.id]?.length" class="mt-1 text-xs text-destructive">Required</p>
                    </div>

                    <!-- Notes -->
                    <div>
                        <label class="mb-1 block text-sm font-medium text-ocean-deep">{{ __('Notes (optional)') }}</label>
                        <input
                            v-model="modalNotes"
                            type="text"
                            :placeholder="__('e.g. no onions')"
                            class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                        />
                    </div>
                </div>
                <div class="flex justify-end gap-3 border-t border-border px-6 py-4">
                    <AppButton variant="secondary" @click="productModal = false">{{ __('Cancel') }}</AppButton>
                    <AppButton :disabled="requiredGroupsMissing.length > 0" @click="confirmAddToCart">{{ __('Add to Cart') }}</AppButton>
                </div>
            </div>
        </div>

        <AppConfirmModal
            :show="showConfirm"
            :title="__('Place Order')"
            :message="`${ __('Send') } ${cart.length} ${ __('item(s) to the kitchen?') }`"
            :confirm-label="__('Place Order')"
            @confirm="placeOrder"
            @cancel="showConfirm = false"
        />
    </AppLayout>
</template>
