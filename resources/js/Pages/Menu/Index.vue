<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import AppCard from '@/Components/AppCard.vue';
import AppButton from '@/Components/AppButton.vue';
import AppConfirmModal from '@/Components/AppConfirmModal.vue';
import AppEmptyState from '@/Components/AppEmptyState.vue';
import AppSkeleton from '@/Components/AppSkeleton.vue';
import Products from '@/Pages/Menu/Products.vue';
import Modifiers from '@/Pages/Menu/Modifiers.vue';
import Combos from '@/Pages/Menu/Combos.vue';
import { useForm, router, Deferred } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';

defineProps({
    categories: Array,
    menuId: String,
    products: {
        type: Array,
        default: () => [],
    },
    stations: {
        type: Array,
        default: () => [],
    },
    modifierGroups: {
        type: Array,
        default: () => [],
    },
    combos: {
        type: Array,
        default: () => [],
    },
});

const CATEGORIES_SECTION_ID = 'menu-categories';
const PRODUCTS_SECTION_ID = 'menu-products';
const MODIFIERS_SECTION_ID = 'menu-modifiers';
const COMBOS_SECTION_ID = 'menu-combos';

const scrollToSection = (id) => {
    document.getElementById(id)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
};

const scrollToCategories = () => scrollToSection(CATEGORIES_SECTION_ID);
const scrollToProducts = () => scrollToSection(PRODUCTS_SECTION_ID);
const scrollToModifiers = () => scrollToSection(MODIFIERS_SECTION_ID);
const scrollToCombos = () => scrollToSection(COMBOS_SECTION_ID);

onMounted(() => {
    const hash = window.location.hash.replace('#', '');
    if ([CATEGORIES_SECTION_ID, PRODUCTS_SECTION_ID, MODIFIERS_SECTION_ID, COMBOS_SECTION_ID].includes(hash)) {
        scrollToSection(hash);
    }
});

const showForm = ref(false);
const editingCategory = ref(null);
const categoryToDelete = ref(null);

const form = useForm({
    name: '',
});

const openCreate = () => {
    editingCategory.value = null;
    form.reset();
    showForm.value = true;
};

const openEdit = (category) => {
    editingCategory.value = category;
    form.name = category.name;
    showForm.value = true;
};

const closeForm = () => {
    showForm.value = false;
    editingCategory.value = null;
    form.reset();
};

const submit = () => {
    if (editingCategory.value) {
        form.put(route('menu.categories.update', editingCategory.value.id), {
            onSuccess: closeForm,
        });
    } else {
        form.post(route('menu.categories.store'), {
            onSuccess: closeForm,
        });
    }
};

const confirmDelete = (category) => {
    categoryToDelete.value = category;
};

const deleteCategory = () => {
    router.delete(route('menu.categories.destroy', categoryToDelete.value.id), {
        onSuccess: () => { categoryToDelete.value = null; },
    });
};

const moveUp = (categories, index) => {
    if (index === 0) { return; }
    const ids = categories.map((c) => c.id);
    [ids[index - 1], ids[index]] = [ids[index], ids[index - 1]];
    router.post(route('menu.categories.reorder'), { ids });
};

const moveDown = (categories, index) => {
    if (index === categories.length - 1) { return; }
    const ids = categories.map((c) => c.id);
    [ids[index], ids[index + 1]] = [ids[index + 1], ids[index]];
    router.post(route('menu.categories.reorder'), { ids });
};

const PRODUCTS_PER_COLUMN = 3;

const productsInColumns = (products, size = PRODUCTS_PER_COLUMN) => {
    const list = products ?? [];
    const columns = [];

    for (let i = 0; i < list.length; i += size) {
        columns.push(list.slice(i, i + size));
    }

    return columns;
};
</script>

<template>
    <AppLayout :title="__('Menu')">
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex min-w-0 items-center gap-3">
                    <h1 class="shrink-0 font-heading text-4xl font-bold text-ocean-deep dark:text-gray-100">{{ __('Menu') }}</h1>
                    <p class="text-sm leading-snug text-muted-foreground dark:text-gray-400">
                        <span class="block">{{ __('Add, edit and include photos of your products.') }}</span>
                        <span class="block">{{ __('See the menu as the customer sees it and as the attendant sees it.') }}</span>
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <AppButton size="sm" @click="scrollToCategories">{{ __('Categories') }}</AppButton>
                    <AppButton size="sm" @click="scrollToProducts">{{ __('Products') }}</AppButton>
                    <AppButton size="sm" @click="scrollToModifiers">{{ __('Modifiers') }}</AppButton>
                    <AppButton size="sm" @click="scrollToCombos">{{ __('Combos') }}</AppButton>
                    <span class="mx-0.5 hidden h-5 w-px bg-ocean-deep/20 dark:bg-white/30 sm:inline-block" aria-hidden="true" />
                    <AppButton size="sm" variant="accent" :href="route('menu.preview.customer')">{{ __('Customer version') }}</AppButton>
                    <AppButton size="sm" variant="accent" :href="route('menu.preview.attendant')">{{ __('Attendant version') }}</AppButton>
                </div>
            </div>
        </template>

        <div id="menu-categories" class="scroll-mt-48">
        <AppCard>
            <div class="mb-1 flex items-end justify-between gap-2">
                <div class="flex min-w-0 items-center gap-3">
                <h2 class="shrink-0 font-heading text-3xl font-bold tracking-tight text-ocean-deep dark:text-gray-100">
                    {{ __('Categories') }}
                    <span class="mt-2 block h-1 w-16 rounded-full bg-warm-gold" aria-hidden="true" />
                </h2>
                <p class="text-sm leading-snug text-muted-foreground dark:text-gray-400">
                    <span class="block">{{ __('View and organize') }}</span>
                    <span class="block">{{ __('the categories on your menu') }}</span>
                </p>
                </div>
                <div class="flex items-end gap-6">
                <p
                    v-if="categories.length && !showForm"
                    class="text-center text-xs leading-tight text-muted-foreground dark:text-gray-400"
                >
                    <span class="block whitespace-nowrap">{{ __('Use the arrows (↑ ↓) to change') }}</span>
                    <span class="block whitespace-nowrap">{{ __('the category order on the menu') }}</span>
                </p>
                <AppButton variant="success" @click="openCreate">{{ __('Add Category') }}</AppButton>
                </div>
            </div>

            <AppEmptyState
                v-if="!categories.length && !showForm"
                :title="__('No categories yet')"
                :description="__('Add a category to start building your menu.')"
                :action-label="__('Add Category')"
                @action="openCreate"
            />

            <div v-if="categories.length && !showForm" class="divide-y divide-muted">
                <div
                    v-for="(category, index) in categories"
                    :key="category.id"
                    class="py-4"
                >
                    <div class="flex flex-col gap-3 md:flex-row md:items-stretch">
                        <div class="flex w-full shrink-0 flex-col justify-center rounded-lg bg-warm-gold/20 px-4 py-3 dark:bg-warm-gold/10 md:w-52">
                            <h3 class="font-heading text-sm font-semibold leading-tight text-ocean-deep dark:text-gray-100">{{ category.name }}</h3>
                            <p class="mt-1 text-xs text-muted-foreground">{{ category.products?.length ?? 0 }} {{ __('products') }}</p>
                        </div>
                        <div class="flex min-w-0 flex-1 items-start gap-3">
                            <div class="min-w-0 flex-1">
                                <div v-if="category.products?.length" class="flex flex-wrap gap-x-6 gap-y-2">
                                    <div
                                        v-for="(column, columnIndex) in productsInColumns(category.products)"
                                        :key="columnIndex"
                                        class="flex flex-col gap-1.5"
                                    >
                                        <span
                                            v-for="product in column"
                                            :key="product.id"
                                            class="rounded-full bg-ocean-light dark:bg-gray-500 px-2 py-0.5 text-xs text-ocean-deep dark:text-gray-100"
                                        >
                                            {{ product.name }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="ml-auto flex shrink-0 gap-2">
                                <div class="flex w-[11rem] justify-center gap-2">
                                    <AppButton size="sm" variant="ghost" :disabled="index === 0" @click="moveUp(categories, index)">↑</AppButton>
                                    <AppButton size="sm" variant="ghost" :disabled="index === categories.length - 1" @click="moveDown(categories, index)">↓</AppButton>
                                </div>
                                <AppButton size="sm" variant="secondary" @click="openEdit(category)">
                                    <span class="flex flex-col leading-tight">
                                        <span>{{ __('Edit') }}</span>
                                        <span>{{ __('Category') }}</span>
                                    </span>
                                </AppButton>
                                <AppButton size="sm" variant="destructive" @click="confirmDelete(category)">
                                    <span class="flex flex-col leading-tight">
                                        <span>{{ __('Delete') }}</span>
                                        <span>{{ __('Category') }}</span>
                                    </span>
                                </AppButton>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="showForm" class="mt-4 rounded-lg border-4 border-[#5c9a6c] p-4">
                <h3 class="mb-3 font-heading text-lg font-bold tracking-tight text-ocean-deep dark:text-gray-100">
                    {{ editingCategory ? __('Edit Category') : __('New Category') }}
                </h3>
                <form class="space-y-3" @submit.prevent="submit">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-ocean-deep dark:text-gray-100">
                            {{ __('Name') }} <span class="text-destructive">*</span>
                        </label>
                        <input
                            v-model="form.name"
                            type="text"
                            class="w-full rounded-md border border-border dark:border-gray-700 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-gray-100"
                        />
                        <p v-if="form.errors.name" class="mt-1 text-xs text-destructive">{{ form.errors.name }}</p>
                    </div>
                    <div class="flex gap-2 pt-1">
                        <AppButton type="submit" :loading="form.processing">{{ __('Save') }}</AppButton>
                        <AppButton type="button" variant="ghost" @click="closeForm">{{ __('Cancel') }}</AppButton>
                    </div>
                </form>
            </div>
        </AppCard>
        </div>

        <div id="menu-products" class="mt-6 scroll-mt-48">
        <Deferred :data="['products', 'stations', 'modifierGroups']">
            <template #fallback>
                <AppCard>
                    <div class="py-10">
                        <AppSkeleton :lines="6" />
                    </div>
                </AppCard>
            </template>
            <Products
                :products="products"
                :categories="categories"
                :stations="stations"
                :modifier-groups="modifierGroups"
            />
        </Deferred>
        </div>

        <div id="menu-modifiers" class="mt-6 scroll-mt-48">
        <Deferred :data="['modifierGroups']">
            <template #fallback>
                <AppCard>
                    <div class="py-10">
                        <AppSkeleton :lines="6" />
                    </div>
                </AppCard>
            </template>
            <Modifiers :modifier-groups="modifierGroups" />
        </Deferred>
        </div>

        <div id="menu-combos" class="mt-6 scroll-mt-48">
        <Deferred :data="['combos', 'products']">
            <template #fallback>
                <AppCard>
                    <div class="py-10">
                        <AppSkeleton :lines="6" />
                    </div>
                </AppCard>
            </template>
            <Combos :combos="combos" :products="products" />
        </Deferred>
        </div>

        <AppConfirmModal
            :show="!!categoryToDelete"
            :title="__('Delete Category')"
            :message="__('Are you sure you want to delete this category? All products in it will also be deleted.')"
            :confirm-label="__('Delete')"
            variant="destructive"
            @confirm="deleteCategory"
            @cancel="categoryToDelete = null"
        />
    </AppLayout>
</template>
