<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import AppSkeleton from '@/Components/AppSkeleton.vue';
import AppEmptyState from '@/Components/AppEmptyState.vue';
import { ref } from 'vue';

const props = defineProps({
    venue: Object,
    categories: Array,
});

const selectedCategoryId = ref(props.categories?.[0]?.id ?? null);

const selectedCategory = () => props.categories.find((c) => c.id === selectedCategoryId.value);
</script>

<template>
    <GuestLayout :title="venue.name + ' — Menu'" :venue="venue">
        <!-- Venue header -->
        <div class="mb-6 text-center">
            <img
                v-if="venue.logo_url"
                :src="venue.logo_url"
                :alt="venue.name"
                class="mx-auto mb-3 h-16 w-auto rounded-lg object-contain"
            />
            <h1 class="font-heading text-2xl font-bold text-ocean-deep">{{ venue.name }}</h1>
        </div>

        <AppEmptyState
            v-if="!categories.length"
            :title=" __('Menu not available')"
            :description=" __('This venue has no menu items yet.')"
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

            <!-- Products grid -->
            <template v-if="selectedCategory()">
                <AppEmptyState
                    v-if="!selectedCategory().products?.length"
                    title="No items in this category"
                    description="Check back later."
                />
                <div v-else class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div
                        v-for="product in selectedCategory().products"
                        :key="product.id"
                        class="rounded-lg bg-white p-4 shadow-card"
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
                    </div>
                </div>
            </template>
        </template>
    </GuestLayout>
</template>
