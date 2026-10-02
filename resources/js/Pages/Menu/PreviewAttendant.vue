<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import AppButton from '@/Components/AppButton.vue';
import AppEmptyState from '@/Components/AppEmptyState.vue';
import { Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    venue: Object,
    categories: Array,
    combos: Array,
});

const selectedCategoryId = ref(props.categories?.[0]?.id ?? null);
const activeTab = ref('menu');
const detailProduct = ref(null);

const selectedCategory = computed(() => props.categories?.find((c) => c.id === selectedCategoryId.value));

const openProduct = (product) => {
    detailProduct.value = product;
};

const closeProduct = () => {
    detailProduct.value = null;
};
</script>

<template>
    <AppLayout :title="__('Attendant version')">
        <template #header>
            <div class="flex flex-wrap items-center gap-4">
                <Link :href="route('menu.index')" class="text-sm font-medium text-primary hover:underline">
                    ← {{ __('Back to menu') }}
                </Link>
                <h1 class="font-heading text-2xl font-bold text-ocean-deep dark:text-gray-100">{{ __('Attendant version') }}</h1>
                <p v-if="venue?.name" class="text-sm text-muted-foreground">{{ venue.name }}</p>
            </div>
        </template>

        <div class="mb-4 rounded-xl border border-warm-gold/40 bg-warm-gold/10 px-4 py-3 text-sm text-ocean-deep dark:text-gray-100">
            <p class="font-semibold">{{ __('Preview — attendant view') }}</p>
            <p class="mt-1 text-xs text-muted-foreground">{{ __('This is a preview. Orders cannot be placed from here.') }}</p>
        </div>

        <div class="flex min-h-[calc(100vh-14rem)] flex-col overflow-hidden">
            <div class="mb-3 flex gap-2">
                <button
                    class="rounded-full px-4 py-1.5 text-sm font-medium transition-colors"
                    :class="activeTab === 'menu' ? 'bg-primary text-white' : 'bg-white text-ocean-deep shadow-card hover:bg-ocean-light dark:bg-gray-700 dark:text-gray-100'"
                    @click="activeTab = 'menu'"
                >{{ __('Menu') }}</button>
                <button
                    v-if="combos?.length"
                    class="rounded-full px-4 py-1.5 text-sm font-medium transition-colors"
                    :class="activeTab === 'combos' ? 'bg-primary text-white' : 'bg-white text-ocean-deep shadow-card hover:bg-ocean-light dark:bg-gray-700 dark:text-gray-100'"
                    @click="activeTab = 'combos'"
                >{{ __('Combos') }}</button>
            </div>

            <template v-if="activeTab === 'menu'">
                <div class="mb-3 flex gap-2 overflow-x-auto pb-1">
                    <button
                        v-for="category in categories"
                        :key="category.id"
                        class="whitespace-nowrap rounded-full px-3 py-1.5 text-sm font-medium transition-colors"
                        :class="selectedCategoryId === category.id ? 'bg-ocean-deep text-white' : 'bg-white text-ocean-deep shadow-card hover:bg-ocean-light dark:bg-gray-700 dark:text-gray-100'"
                        @click="selectedCategoryId = category.id"
                    >
                        {{ category.name }}
                    </button>
                </div>

                <AppEmptyState v-if="!categories.length" :title="__('No menu available')" :description="__('There are no active products in the menu.')" />
                <AppEmptyState v-else-if="!selectedCategory?.products?.length" :title="__('No products in this category')" :description="__('Select another category.')" />
                <div v-else class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                    <button
                        v-for="product in selectedCategory.products"
                        :key="product.id"
                        class="rounded-lg bg-white p-3 text-left shadow-card transition hover:ring-2 hover:ring-primary dark:bg-gray-800"
                        @click="openProduct(product)"
                    >
                        <div
                            v-if="product.image_url"
                            class="mb-2 aspect-square overflow-hidden rounded-md bg-muted"
                        >
                            <img :src="product.image_url" :alt="product.name" class="h-full w-full object-cover" />
                        </div>
                        <p class="font-body text-sm font-semibold text-ocean-deep dark:text-gray-100">{{ product.name }}</p>
                        <p class="mt-1 font-heading text-sm font-bold text-primary">R$ {{ Number(product.price).toFixed(2) }}</p>
                        <span v-if="product.variations?.length" class="mt-1 inline-block rounded bg-ocean-light px-1.5 py-0.5 text-xs text-ocean-deep dark:bg-gray-700 dark:text-gray-300">{{ __('Variations') }}</span>
                        <span v-if="product.modifier_groups?.length" class="mt-1 inline-block rounded bg-ocean-light px-1.5 py-0.5 text-xs text-ocean-deep dark:bg-gray-700 dark:text-gray-300">{{ __('Modifiers') }}</span>
                    </button>
                </div>
            </template>

            <template v-if="activeTab === 'combos'">
                <AppEmptyState v-if="!combos?.length" :title="__('No combos available')" :description="__('No active combos configured.')" />
                <div v-else class="space-y-3">
                    <div
                        v-for="combo in combos"
                        :key="combo.id"
                        class="rounded-lg bg-white p-4 shadow-card dark:bg-gray-800"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="font-body font-semibold text-ocean-deep dark:text-gray-100">{{ combo.name }}</p>
                                <ul class="mt-1 space-y-0.5">
                                    <li v-for="item in combo.items" :key="item.id" class="text-xs text-muted-foreground">
                                        {{ item.product?.name }}<span v-if="item.variation"> — {{ item.variation.name }}</span>
                                        <span v-if="item.quantity > 1"> ×{{ item.quantity }}</span>
                                    </li>
                                </ul>
                            </div>
                            <p class="shrink-0 font-heading text-sm font-bold text-primary">R$ {{ Number(combo.price).toFixed(2) }}</p>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <div v-if="detailProduct" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="w-full max-w-md rounded-xl bg-white shadow-xl dark:bg-gray-800">
                <div class="flex items-start gap-3 border-b border-border px-6 py-4">
                    <div
                        v-if="detailProduct.image_url"
                        class="size-16 shrink-0 overflow-hidden rounded-lg bg-muted"
                    >
                        <img :src="detailProduct.image_url" :alt="detailProduct.name" class="h-full w-full object-cover" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <h3 class="font-heading text-lg font-bold text-ocean-deep dark:text-gray-100">{{ detailProduct.name }}</h3>
                        <p v-if="detailProduct.description" class="mt-1 text-xs text-muted-foreground">{{ detailProduct.description }}</p>
                        <p class="mt-1 font-heading text-sm font-bold text-primary">R$ {{ Number(detailProduct.price).toFixed(2) }}</p>
                    </div>
                </div>
                <div class="max-h-[60vh] space-y-4 overflow-y-auto px-6 py-4">
                    <div v-if="detailProduct.variations?.length">
                        <p class="mb-2 text-sm font-semibold text-ocean-deep dark:text-gray-300">{{ __('Variations') }}</p>
                        <ul class="space-y-1">
                            <li
                                v-for="variation in detailProduct.variations"
                                :key="variation.id"
                                class="flex items-center justify-between rounded-md border border-border px-3 py-2 text-sm"
                            >
                                <span>{{ variation.name }}</span>
                                <span class="text-xs text-muted-foreground">R$ {{ Number(variation.price).toFixed(2) }}</span>
                            </li>
                        </ul>
                    </div>
                    <div v-for="group in detailProduct.modifier_groups" :key="group.id">
                        <p class="mb-2 text-sm font-semibold text-ocean-deep dark:text-gray-300">{{ group.name }}</p>
                        <ul class="space-y-1">
                            <li
                                v-for="option in group.options"
                                :key="option.id"
                                class="flex items-center justify-between rounded-md border border-border px-3 py-2 text-sm"
                            >
                                <span>{{ option.name }}</span>
                                <span v-if="option.extra_price > 0" class="text-xs text-muted-foreground">+R$ {{ Number(option.extra_price).toFixed(2) }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="border-t border-border px-6 py-4">
                    <AppButton class="w-full" variant="secondary" @click="closeProduct">{{ __('Close') }}</AppButton>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
