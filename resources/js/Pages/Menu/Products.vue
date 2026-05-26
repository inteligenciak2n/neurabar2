<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import AppCard from '@/Components/AppCard.vue';
import AppButton from '@/Components/AppButton.vue';
import AppBadge from '@/Components/AppBadge.vue';
import AppConfirmModal from '@/Components/AppConfirmModal.vue';
import AppEmptyState from '@/Components/AppEmptyState.vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    products: Array,
    categories: Array,
    stations: Array,
    filters: Object,
});

const showForm = ref(false);
const editingProduct = ref(null);
const productToDelete = ref(null);

const form = useForm({
    name: '',
    price: '',
    description: '',
    category_id: props.filters?.category_id ?? '',
    kitchen_station_id: '',
    active: true,
});

const selectedCategoryId = ref(props.filters?.category_id ?? '');

const filteredProducts = computed(() => {
    if (!selectedCategoryId.value) { return props.products; }
    return props.products.filter((p) => p.category_id === selectedCategoryId.value);
});

const filterByCategory = (categoryId) => {
    selectedCategoryId.value = categoryId;
    router.get(route('menu.products.index'), { category_id: categoryId || undefined }, { preserveState: true, replace: true });
};

const openCreate = () => {
    editingProduct.value = null;
    form.reset();
    form.active = true;
    form.category_id = selectedCategoryId.value ?? '';
    showForm.value = true;
};

const openEdit = (product) => {
    editingProduct.value = product;
    form.name = product.name;
    form.price = product.price;
    form.description = product.description ?? '';
    form.category_id = product.category_id;
    form.kitchen_station_id = product.kitchen_station_id ?? '';
    form.active = product.active;
    showForm.value = true;
};

const closeForm = () => {
    showForm.value = false;
    editingProduct.value = null;
    form.reset();
};

const submit = () => {
    if (editingProduct.value) {
        form.put(route('menu.products.update', editingProduct.value.id), {
            onSuccess: closeForm,
        });
    } else {
        form.post(route('menu.products.store'), {
            onSuccess: closeForm,
        });
    }
};

const toggleActive = (product) => {
    router.post(route('menu.products.toggle', product.id));
};

const confirmDelete = (product) => {
    productToDelete.value = product;
};

const deleteProduct = () => {
    router.delete(route('menu.products.destroy', productToDelete.value.id), {
        onSuccess: () => { productToDelete.value = null; },
    });
};
</script>

<template>
    <AppLayout :title="__('Products')">
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link :href="route('menu.index')" class="text-sm font-medium text-primary hover:underline">← {{ __('Menu') }}</Link>
                    <h1 class="font-heading text-2xl font-bold text-ocean-deep">{{ __('Products') }}</h1>
                </div>
                <AppButton @click="openCreate">{{ __('Add Product') }}</AppButton>
            </div>
        </template>

        <!-- Category filter tabs -->
        <div class="mb-4 flex gap-2 overflow-x-auto">
            <button
                class="whitespace-nowrap rounded-full px-3 py-1.5 text-sm font-medium transition-colors"
                :class="!selectedCategoryId ? 'bg-primary text-white' : 'bg-muted text-ocean-deep hover:bg-sand'"
                @click="filterByCategory('')"
            >
                {{ __('All') }}
            </button>
            <button
                v-for="category in categories"
                :key="category.id"
                class="whitespace-nowrap rounded-full px-3 py-1.5 text-sm font-medium transition-colors"
                :class="selectedCategoryId === category.id ? 'bg-primary text-white' : 'bg-muted text-ocean-deep hover:bg-sand'"
                @click="filterByCategory(category.id)"
            >
                {{ category.name }}
            </button>
        </div>

        <AppCard>
            <AppEmptyState
                v-if="!filteredProducts.length && !showForm"
                :title="__('No products yet')"
                :description="__('Add a product to start building your menu.')"
                :action-label="__('Add Product')"
                @action="openCreate"
            />

            <div v-if="filteredProducts.length" class="divide-y divide-muted">
                <div
                    v-for="product in filteredProducts"
                    :key="product.id"
                    class="flex items-center justify-between py-3"
                >
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-body text-sm font-semibold text-ocean-deep">{{ product.name }}</span>
                            <AppBadge :label="product.active ? __('Active') : __('Inactive')" :color="product.active ? '#22c55e' : '#94a3b8'" />
                        </div>
                        <div class="mt-0.5 flex items-center gap-3 text-xs text-muted-foreground">
                            <span>R$ {{ Number(product.price).toFixed(2) }}</span>
                            <span v-if="product.category">{{ product.category.name }}</span>
                            <span v-if="product.kitchen_station">{{ product.kitchen_station.name }}</span>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <AppButton size="sm" variant="secondary" @click="toggleActive(product)">
                            {{ product.active ? __('Deactivate') : __('Activate') }}
                        </AppButton>
                        <AppButton size="sm" variant="secondary" @click="openEdit(product)">{{ __('Edit') }}</AppButton>
                        <AppButton size="sm" variant="destructive" @click="confirmDelete(product)">{{ __('Delete') }}</AppButton>
                    </div>
                </div>
            </div>

            <div v-if="showForm" class="mt-4 rounded-lg border border-border p-4">
                <h3 class="mb-3 font-heading text-sm font-semibold text-ocean-deep">
                    {{ editingProduct ? __('Edit Product') : __('New Product') }}
                </h3>
                <form class="grid grid-cols-1 gap-3 sm:grid-cols-2" @submit.prevent="submit">
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-ocean-deep">{{ __('Name') }} <span class="text-destructive">*</span></label>
                        <input
                            v-model="form.name"
                            type="text"
                            class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                        />
                        <p v-if="form.errors.name" class="mt-1 text-xs text-destructive">{{ form.errors.name }}</p>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-ocean-deep">{{ __('Price') }} (R$) <span class="text-destructive">*</span></label>
                        <input
                            v-model="form.price"
                            type="number"
                            min="0"
                            step="0.01"
                            class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                        />
                        <p v-if="form.errors.price" class="mt-1 text-xs text-destructive">{{ form.errors.price }}</p>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-ocean-deep">{{ __('Category') }} <span class="text-destructive">*</span></label>
                        <select
                            v-model="form.category_id"
                            class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                        >
                            <option value="">{{ __('Select a category') }}</option>
                            <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                        </select>
                        <p v-if="form.errors.category_id" class="mt-1 text-xs text-destructive">{{ form.errors.category_id }}</p>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-ocean-deep">{{ __('Kitchen Station') }}</label>
                        <select
                            v-model="form.kitchen_station_id"
                            class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                        >
                            <option value="">{{ __('None') }}</option>
                            <option v-for="station in stations" :key="station.id" :value="station.id">{{ station.name }}</option>
                        </select>
                        <p v-if="form.errors.kitchen_station_id" class="mt-1 text-xs text-destructive">{{ form.errors.kitchen_station_id }}</p>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-ocean-deep">{{ __('Description') }}</label>
                        <textarea
                            v-model="form.description"
                            rows="2"
                            class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                        />
                    </div>

                    <div class="flex items-center gap-2 sm:col-span-2">
                        <label class="flex cursor-pointer items-center gap-2">
                            <input v-model="form.active" type="checkbox" class="h-4 w-4 rounded border-border text-primary focus:ring-primary" />
                            <span class="text-sm text-ocean-deep">{{ __('Active') }}</span>
                        </label>
                    </div>

                    <div class="flex gap-2 sm:col-span-2">
                        <AppButton type="submit" :loading="form.processing">{{ __('Save') }}</AppButton>
                        <AppButton type="button" variant="ghost" @click="closeForm">{{ __('Cancel') }}</AppButton>
                    </div>
                </form>
            </div>
        </AppCard>

        <AppConfirmModal
            :show="!!productToDelete"
            :title="__('Delete Product')"
            :message="__('Are you sure you want to delete this product?')"
            :confirm-label="__('Delete')"
            variant="destructive"
            @confirm="deleteProduct"
            @cancel="productToDelete = null"
        />
    </AppLayout>
</template>
