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
    modifierGroups: Array,
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

const syncForm = useForm({
    modifier_group_ids: [],
});

const toggleGroupInSync = (groupId) => {
    const idx = syncForm.modifier_group_ids.indexOf(groupId);
    if (idx >= 0) {
        syncForm.modifier_group_ids.splice(idx, 1);
    } else {
        syncForm.modifier_group_ids.push(groupId);
    }
};

const submitSync = (product) => {
    syncForm.put(route('menu.products.modifier-groups.sync', product.id));
};

// Variations
const variationForm = useForm({ name: '', price: '', active: true });
const addingVariation = ref(false);
const editingVariationId = ref(null);
const editVariationForm = useForm({ name: '', price: '', active: true });
const variationToDelete = ref(null);

const openAddVariation = () => {
    addingVariation.value = true;
    variationForm.reset();
    variationForm.active = true;
};

const cancelAddVariation = () => {
    addingVariation.value = false;
    variationForm.reset();
};

const submitCreateVariation = (product) => {
    variationForm.post(route('menu.products.variations.store', product.id), {
        onSuccess: () => {
            router.reload({ only: ['products'],
                onFinish: () => {
                    editingProduct.value = props.products.find((p) => p.id === product.id);
                },
             });
            cancelAddVariation();
        },
    });
};

const openEditVariation = (variation) => {
    editingVariationId.value = variation.id;
    editVariationForm.name = variation.name;
    editVariationForm.price = variation.price;
    editVariationForm.active = variation.active;
};

const closeEditVariation = () => {
    editingVariationId.value = null;
};

const submitEditVariation = (product, variation) => {
    editVariationForm.put(route('menu.products.variations.update', [product.id, variation.id]), {
        onSuccess: closeEditVariation,
    });
};

const confirmDeleteVariation = (variation) => {
    variationToDelete.value = variation;
};

const deleteVariation = () => {
    router.delete(route('menu.products.variations.destroy', [editingProduct.value.id, variationToDelete.value.id]), {
        onSuccess: () => { variationToDelete.value = null; },
    });
};

const selectedCategoryId = ref(props.filters?.category_id ?? '');

const filteredProducts = computed(() => {
    if (!selectedCategoryId.value) { return props.products; }
    return props.products.filter((p) => p.category_id === selectedCategoryId.value);
});

const filterByCategory = (categoryId) => {
    showForm.value = false;
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
    syncForm.modifier_group_ids = (product.modifier_groups ?? []).map((g) => g.id);
    showForm.value = true;
};

const closeForm = () => {
    showForm.value = false;
    editingProduct.value = null;
    form.reset();
    addingVariation.value = false;
    editingVariationId.value = null;
    variationToDelete.value = null;
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
                    <Link :href="route('menu.index')" class="text-sm font-medium text-primary dark:text-gray-100 hover:underline">← {{ __('Menu') }}</Link>
                    <h1 class="font-heading text-2xl font-bold text-ocean-deep dark:text-gray-100">{{ __('Products') }}</h1>
                </div>
                <AppButton @click="openCreate">{{ __('Add Product') }}</AppButton>
            </div>
        </template>

        <!-- Category filter tabs -->
        <div class="mb-4 flex gap-2 overflow-x-auto">
            <button
                class="whitespace-nowrap rounded-full px-3 py-1.5 text-sm font-medium transition-colors"
                :class="!selectedCategoryId ? 'bg-primary text-white' : 'bg-muted text-ocean-deep dark:text-gray-600 hover:bg-sand'"
                @click="filterByCategory('')"
            >
                {{ __('All') }}
            </button>
            <button
                v-for="category in categories"
                :key="category.id"
                class="whitespace-nowrap rounded-full px-3 py-1.5 text-sm font-medium transition-colors"
                :class="selectedCategoryId === category.id ? 'bg-primary text-white' : 'bg-muted text-ocean-deep dark:text-gray-600 hover:bg-sand'"
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

            <div v-if="filteredProducts.length && !showForm" class="divide-y divide-muted">
                <div
                    v-for="product in filteredProducts"
                    :key="product.id"
                    class="flex items-center justify-between py-3"
                >
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-body text-sm font-semibold text-ocean-deep dark:text-gray-100">{{ product.name }}</span>
                            <AppBadge :label="product.active ? __('Active') : __('Inactive')" :color="product.active ? '#22c55e' : '#94a3b8'" />
                        </div>
                        <div class="mt-0.5 flex items-center gap-3 text-xs text-muted-foreground">
                            <span>R$ {{ Number(product.price).toFixed(2) }}</span>
                            <span v-if="product.category">{{ product.category.name }}</span>
                            <span v-if="product.kitchen_station">{{ product.kitchen_station.name }}</span>
                        </div>
                        <div v-if="product.modifier_groups?.length" class="mt-1 flex flex-wrap gap-1">
                            <AppBadge
                                v-for="group in product.modifier_groups"
                                :key="group.id"
                                :label="group.name"
                                color="#6366f1"
                            />
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

            <div v-if="showForm" class="mt-4 rounded-lg border border-border dark:border-gray-700 p-4">
                <h3 class="mb-3 font-heading text-sm font-semibold text-ocean-deep dark:text-gray-100">
                    {{ editingProduct ? __('Edit Product') : __('New Product') }}
                </h3>
                <form class="grid grid-cols-1 gap-3 sm:grid-cols-2" @submit.prevent="submit">
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-ocean-deep dark:text-gray-100">{{ __('Name') }} <span class="text-destructive">*</span></label>
                        <input
                            v-model="form.name"
                            type="text"
                            class="w-full rounded-md border border-border dark:border-gray-700 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-gray-100"
                        />
                        <p v-if="form.errors.name" class="mt-1 text-xs text-destructive">{{ form.errors.name }}</p>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-ocean-deep dark:text-gray-100">{{ __('Price') }} (R$) <span class="text-destructive">*</span></label>
                        <input
                            v-model="form.price"
                            type="number"
                            min="0"
                            step="0.01"
                            class="w-full rounded-md border border-border dark:border-gray-700 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-gray-100"
                        />
                        <p v-if="form.errors.price" class="mt-1 text-xs text-destructive">{{ form.errors.price }}</p>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-ocean-deep dark:text-gray-100">{{ __('Category') }} <span class="text-destructive">*</span></label>
                        <select
                            v-model="form.category_id"
                            class="w-full rounded-md border border-border dark:border-gray-700 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-gray-100"
                        >
                            <option value="">{{ __('Select a category') }}</option>
                            <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                        </select>
                        <p v-if="form.errors.category_id" class="mt-1 text-xs text-destructive">{{ form.errors.category_id }}</p>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-ocean-deep dark:text-gray-100">{{ __('Kitchen Station') }}</label>
                        <select
                            v-model="form.kitchen_station_id"
                            class="w-full rounded-md border border-border dark:border-gray-700 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-gray-100"
                        >
                            <option value="">{{ __('None') }}</option>
                            <option v-for="station in stations" :key="station.id" :value="station.id">{{ station.name }}</option>
                        </select>
                        <p v-if="form.errors.kitchen_station_id" class="mt-1 text-xs text-destructive">{{ form.errors.kitchen_station_id }}</p>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-ocean-deep dark:text-gray-100">{{ __('Description') }}</label>
                        <textarea
                            v-model="form.description"
                            rows="2"
                            class="w-full rounded-md border border-border dark:border-gray-700 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-gray-100"
                        />
                    </div>

                    <div class="flex items-center gap-2 sm:col-span-2 justify-end">
                        <label class="flex cursor-pointer items-center gap-2">
                            <input v-model="form.active" type="checkbox" class="h-4 w-4 rounded border-border dark:border-gray-700 text-primary focus:ring-primary" />
                            <span class="text-sm text-ocean-deep dark:text-gray-100">{{ __('Active') }}</span>
                        </label>
                    </div>

                    <div class="flex gap-2 sm:col-span-2 justify-end">
                        <AppButton type="submit" :loading="form.processing">{{ __('Save') }}</AppButton>
                        <AppButton type="button" variant="ghost" @click="closeForm">{{ __('Cancel') }}</AppButton>
                    </div>
                </form>

                <!-- Modifier groups sync — only for existing products -->
                <div v-if="editingProduct && modifierGroups?.length" class="mt-4 border-t border-border dark:border-gray-700 pt-4">
                    <div class="mb-2 flex items-center justify-between">
                        <p class="text-sm font-medium text-ocean-deep dark:text-gray-100">{{ __('Modifier Groups') }}</p>
                        <AppButton size="sm" :loading="syncForm.processing" @click="submitSync(editingProduct)">
                            {{ __('Save Modifiers') }}
                        </AppButton>
                    </div>
                    <p class="mb-2 text-xs text-muted-foreground">{{ __('Select which modifier groups apply to this product.') }}</p>
                    <div class="flex flex-wrap gap-2">
                        <label
                            v-for="group in modifierGroups"
                            :key="group.id"
                            class="flex cursor-pointer items-center gap-1.5 rounded-md border px-3 py-1.5 text-sm transition-colors"
                            :class="syncForm.modifier_group_ids.includes(group.id)
                                ? 'border-primary bg-primary/10 text-primary'
                                : 'border-border dark:border-gray-700 text-ocean-deep dark:text-gray-100 hover:border-primary/40'"
                        >
                            <input
                                type="checkbox"
                                :checked="syncForm.modifier_group_ids.includes(group.id)"
                                class="sr-only"
                                @change="toggleGroupInSync(group.id)"
                            />
                            {{ group.name }}
                            <span v-if="group.required" class="ml-1 text-xs text-amber-600">({{ __('req.') }})</span>
                        </label>
                    </div>
                </div>

                <!-- Variations — only for existing products -->
                <div v-if="editingProduct" class="mt-4 border-t border-border dark:border-gray-700 pt-4">
                    <div class="mb-2 flex items-center justify-between">
                        <p class="text-sm font-medium text-ocean-deep dark:text-gray-100">{{ __('Variations') }}</p>
                        <AppButton size="sm" variant="secondary" @click="openAddVariation">{{ __('Add Variation') }}</AppButton>
                    </div>
                    <p class="mb-2 text-xs text-muted-foreground">{{ __('Variations override the base price (e.g. Small, Medium, Large).') }}</p>

                    <!-- Existing variations -->
                    <div v-if="editingProduct.variations?.length" class="mb-3 space-y-2">
                        <div
                            v-for="variation in editingProduct.variations"
                            :key="variation.id"
                            class="rounded border border-border dark:border-gray-700 p-2"
                        >
                            <!-- View row -->
                            <div v-if="editingVariationId !== variation.id" class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-medium text-ocean-deep dark:text-gray-100">{{ variation.name }}</span>
                                    <span class="text-sm text-muted-foreground">R$ {{ Number(variation.price).toFixed(2) }}</span>
                                    <AppBadge :label="variation.active ? __('Active') : __('Inactive')" :color="variation.active ? '#22c55e' : '#94a3b8'" />
                                </div>
                                <div class="flex gap-1">
                                    <AppButton size="sm" variant="secondary" @click="openEditVariation(variation)">{{ __('Edit') }}</AppButton>
                                    <AppButton size="sm" variant="destructive" @click="confirmDeleteVariation(variation)">{{ __('Delete') }}</AppButton>
                                </div>
                            </div>

                            <!-- Inline edit row -->
                            <form
                                v-else
                                class="grid grid-cols-1 gap-2 sm:grid-cols-3"
                                @submit.prevent="submitEditVariation(editingProduct, variation)"
                            >
                                <div>
                                    <input
                                        v-model="editVariationForm.name"
                                        type="text"
                                        :placeholder="__('Name')"
                                        class="w-full rounded-md border border-border dark:border-gray-700 px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                                    />
                                    <p v-if="editVariationForm.errors.name" class="mt-0.5 text-xs text-destructive">{{ editVariationForm.errors.name }}</p>
                                </div>
                                <div>
                                    <input
                                        v-model="editVariationForm.price"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        :placeholder="__('Price (R$)')"
                                        class="w-full rounded-md border border-border dark:border-gray-700 px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                                    />
                                    <p v-if="editVariationForm.errors.price" class="mt-0.5 text-xs text-destructive">{{ editVariationForm.errors.price }}</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <label class="flex cursor-pointer items-center gap-1.5 text-sm text-ocean-deep dark:text-gray-100">
                                        <input v-model="editVariationForm.active" type="checkbox" class="h-4 w-4 rounded border-border dark:border-gray-700 text-primary" />
                                        {{ __('Active') }}
                                    </label>
                                    <AppButton type="submit" size="sm" :loading="editVariationForm.processing">{{ __('Save') }}</AppButton>
                                    <AppButton type="button" size="sm" variant="ghost" @click="closeEditVariation">{{ __('Cancel') }}</AppButton>
                                </div>
                            </form>
                        </div>
                    </div>

                    <p v-if="!editingProduct.variations?.length && !addingVariation" class="mb-2 text-xs italic text-muted-foreground">
                        {{ __('No variations yet. Add one to offer size or option choices.') }}
                    </p>

                    <!-- Add variation form -->
                    <form
                        v-if="addingVariation"
                        class="grid grid-cols-1 gap-2 rounded border border-dashed border-primary/40 p-2 sm:grid-cols-3"
                        @submit.prevent="submitCreateVariation(editingProduct)"
                    >
                        <div>
                            <input
                                v-model="variationForm.name"
                                type="text"
                                :placeholder="__('Name')"
                                class="w-full rounded-md border border-border dark:border-gray-700 px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                            />
                            <p v-if="variationForm.errors.name" class="mt-0.5 text-xs text-destructive">{{ variationForm.errors.name }}</p>
                        </div>
                        <div>
                            <input
                                v-model="variationForm.price"
                                type="number"
                                min="0"
                                step="0.01"
                                :placeholder="__('Price (R$)')"
                                class="w-full rounded-md border border-border dark:border-gray-700 px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                            />
                            <p v-if="variationForm.errors.price" class="mt-0.5 text-xs text-destructive">{{ variationForm.errors.price }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <label class="flex cursor-pointer items-center gap-1.5 text-sm text-ocean-deep dark:text-gray-100">
                                <input v-model="variationForm.active" type="checkbox" class="h-4 w-4 rounded border-border dark:border-gray-700 text-primary" />
                                {{ __('Active') }}
                            </label>
                            <AppButton type="submit" size="sm" :loading="variationForm.processing">{{ __('Add') }}</AppButton>
                            <AppButton type="button" size="sm" variant="ghost" @click="cancelAddVariation">{{ __('Cancel') }}</AppButton>
                        </div>
                    </form>
                </div>
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

        <AppConfirmModal
            :show="!!variationToDelete"
            :title="__('Delete Variation')"
            :message="__('Are you sure you want to delete this variation?')"
            :confirm-label="__('Delete')"
            variant="destructive"
            @confirm="deleteVariation"
            @cancel="variationToDelete = null"
        />
    </AppLayout>
</template>
