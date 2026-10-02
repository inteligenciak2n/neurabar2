<script setup>
import AppCard from '@/Components/AppCard.vue';
import AppButton from '@/Components/AppButton.vue';
import AppBadge from '@/Components/AppBadge.vue';
import AppConfirmModal from '@/Components/AppConfirmModal.vue';
import AppEmptyState from '@/Components/AppEmptyState.vue';
import Modal from '@/Components/Modal.vue';
import { useForm, router } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    products: {
        type: Array,
        default: () => [],
    },
    categories: {
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
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const showForm = ref(false);
const editingProduct = ref(null);
const productToDelete = ref(null);

const form = useForm({
    name: '',
    price: '',
    description: '',
    servings: 1,
    category_id: props.filters?.category_id ?? '',
    kitchen_station_id: '',
    active: true,
    photo: null,
    remove_photo: false,
});

const photoInput = ref(null);
const photoPreview = ref(null);
const isDraggingPhoto = ref(false);

const productPhotoSrc = (product) => {
    const url = product?.image_url;
    if (!url) {
        return null;
    }
    if (url.startsWith('http') || url.startsWith('/') || url.startsWith('blob:')) {
        return url;
    }

    return `/storage/${url}`;
};

const revokePhotoPreview = () => {
    if (photoPreview.value?.startsWith('blob:')) {
        URL.revokeObjectURL(photoPreview.value);
    }
};

const resetPhotoField = () => {
    revokePhotoPreview();
    photoPreview.value = null;
    isDraggingPhoto.value = false;
    form.photo = null;
    if (photoInput.value) {
        photoInput.value.value = '';
    }
};

const assignPhoto = (file) => {
    if (!file || !file.type.startsWith('image/')) {
        return;
    }

    revokePhotoPreview();
    form.photo = file;
    form.remove_photo = false;
    photoPreview.value = URL.createObjectURL(file);
};

const clearPhoto = (event) => {
    event.stopPropagation();
    resetPhotoField();
    form.remove_photo = true;
};

const onPhotoSelect = (event) => {
    assignPhoto(event.target.files?.[0] ?? null);
};

const onPhotoDrop = (event) => {
    isDraggingPhoto.value = false;
    assignPhoto(event.dataTransfer.files?.[0] ?? null);
};

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

const showModifierPicker = ref(false);

const selectedModifierGroups = computed(() =>
    (props.modifierGroups ?? []).filter((group) => syncForm.modifier_group_ids.includes(group.id)),
);

const isModifierGroupSelected = (groupId) => syncForm.modifier_group_ids.includes(groupId);

const formatListPrice = (value) => Number(value).toFixed(2).replace('.', ',');

const openModifierPicker = () => {
    showModifierPicker.value = true;
};

const closeModifierPicker = () => {
    showModifierPicker.value = false;
    if (editingProduct.value) {
        submitSync(editingProduct.value);
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
    return props.products.filter((p) => String(p.category_id) === String(selectedCategoryId.value));
});

const draggingProductId = ref(null);

const persistProductOrder = (ids) => {
    router.post(route('menu.products.reorder'), { ids }, { preserveScroll: true });
};

const onProductDragStart = (event, productId) => {
    draggingProductId.value = productId;
    event.dataTransfer.effectAllowed = 'move';
    event.dataTransfer.setData('text/plain', String(productId));
};

const onProductDrop = (event, targetId) => {
    const sourceId = draggingProductId.value || event.dataTransfer.getData('text/plain');
    draggingProductId.value = null;

    if (!sourceId || String(sourceId) === String(targetId)) {
        return;
    }

    const ids = filteredProducts.value.map((product) => product.id);
    const from = ids.findIndex((id) => String(id) === String(sourceId));
    const to = ids.findIndex((id) => String(id) === String(targetId));

    if (from < 0 || to < 0) {
        return;
    }

    ids.splice(from, 1);
    ids.splice(to, 0, sourceId);
    persistProductOrder(ids);
};

const applyCategoryFilter = (categoryId) => {
    showForm.value = false;
    selectedCategoryId.value = categoryId;
};

const filterByCategory = (categoryId) => {
    if (String(selectedCategoryId.value ?? '') === String(categoryId ?? '')) {
        return;
    }

    requestLeave(() => applyCategoryFilter(categoryId));
};

const isCategoryTabActive = (categoryId) => {
    if (!categoryId) {
        return !selectedCategoryId.value;
    }

    return String(selectedCategoryId.value) === String(categoryId);
};

const categoryTabClass = (categoryId) => [
    'relative -mb-px whitespace-nowrap rounded-t-lg border-x border-t px-4 py-2.5 text-sm font-medium transition-colors',
    isCategoryTabActive(categoryId)
        ? "z-10 border-border bg-white text-ocean-deep after:absolute after:inset-x-0 after:-bottom-px after:h-px after:bg-white after:content-[''] dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:after:bg-gray-800"
        : 'border-border/70 bg-muted text-muted-foreground hover:bg-sand hover:text-ocean-deep dark:border-gray-600 dark:bg-gray-700/70 dark:hover:text-gray-100',
];

const openCreate = () => {
    requestLeave(() => {
        editingProduct.value = null;
        form.reset();
        form.active = true;
        form.category_id = selectedCategoryId.value ?? '';
        form.photo = null;
        form.remove_photo = false;
        resetPhotoField();
        form.clearErrors();
        form.defaults();
        showForm.value = true;
    });
};

const openEdit = (product) => {
    requestLeave(() => {
        editingProduct.value = product;
        form.name = product.name;
        form.price = product.price;
        form.description = product.description ?? '';
        form.servings = Number(product.servings) || 1;
        form.category_id = product.category_id;
        form.kitchen_station_id = product.kitchen_station_id ?? '';
        form.active = product.active;
        form.photo = null;
        form.remove_photo = false;
        resetPhotoField();
        photoPreview.value = productPhotoSrc(product);
        form.clearErrors();
        form.defaults();
        syncForm.modifier_group_ids = (product.modifier_groups ?? []).map((g) => g.id);
        showForm.value = true;
    });
};

const closeForm = () => {
    showForm.value = false;
    editingProduct.value = null;
    form.reset();
    resetPhotoField();
    addingVariation.value = false;
    editingVariationId.value = null;
    variationToDelete.value = null;
    showModifierPicker.value = false;
};

const requestCloseForm = () => {
    requestLeave(() => closeForm());
};

const isSavingProduct = ref(false);

const submit = (onAfterSave = null) => {
    isSavingProduct.value = true;
    const options = {
        onSuccess: () => {
            closeForm();
            onAfterSave?.();
        },
        onError: () => {
            stayOnForm();
        },
        onFinish: () => {
            isSavingProduct.value = false;
        },
    };

    const withFormData = {
        forceFormData: true,
        ...options,
        onFinish: () => {
            form.transform((data) => data);
            options.onFinish?.();
        },
    };

    if (editingProduct.value) {
        form.transform((data) => ({
            ...data,
            servings: Number(data.servings) || 1,
            active: data.active ? 1 : 0,
            photo: data.photo || null,
            remove_photo: data.remove_photo ? 1 : 0,
            _method: 'put',
        }));
        form.post(route('menu.products.update', editingProduct.value.id), withFormData);
        return;
    }

    form.transform((data) => ({
        ...data,
        servings: Number(data.servings) || 1,
        active: data.active ? 1 : 0,
        photo: data.photo || null,
        remove_photo: data.remove_photo ? 1 : 0,
    }));
    form.post(route('menu.products.store'), withFormData);
};

const showUnsavedModal = ref(false);
const pendingLeaveAction = ref(null);
const allowNextVisit = ref(false);

const changeServings = (delta) => {
    const current = Number(form.servings) || 1;
    form.servings = Math.min(20, Math.max(1, current + delta));
};

const hasUnsavedChanges = () => showForm.value && form.isDirty;

const requestLeave = (action) => {
    if (!hasUnsavedChanges()) {
        action();
        return;
    }

    pendingLeaveAction.value = action;
    showUnsavedModal.value = true;
};

const stayOnForm = () => {
    pendingLeaveAction.value = null;
    showUnsavedModal.value = false;
};

const discardChangesAndLeave = () => {
    const action = pendingLeaveAction.value;
    pendingLeaveAction.value = null;
    showUnsavedModal.value = false;
    closeForm();
    action?.();
};

const saveChangesAndLeave = () => {
    const action = pendingLeaveAction.value;
    const afterSave = () => {
        pendingLeaveAction.value = null;
        showUnsavedModal.value = false;
        action?.();
    };

    if (showForm.value && form.isDirty) {
        submit(afterSave);
        return;
    }

    afterSave();
};

const warnIfUnsavedBeforeUnload = (event) => {
    if (!hasUnsavedChanges()) {
        return;
    }

    event.preventDefault();
    event.returnValue = '';
};

onMounted(() => {
    window.addEventListener('beforeunload', warnIfUnsavedBeforeUnload);

    const stopBefore = router.on('before', (event) => {
        if (allowNextVisit.value || isSavingProduct.value || form.processing) {
            allowNextVisit.value = false;
            return;
        }

        if (showUnsavedModal.value) {
            event.preventDefault();
            return;
        }

        if (!hasUnsavedChanges()) {
            return;
        }

        event.preventDefault();
        const visit = event.detail?.visit;

        requestLeave(() => {
            allowNextVisit.value = true;
            if (!visit) {
                return;
            }

            router.visit(visit.url, {
                method: visit.method,
                data: visit.data,
                replace: visit.replace,
                preserveState: visit.preserveState,
                preserveScroll: visit.preserveScroll,
            });
        });
    });

    onUnmounted(() => {
        revokePhotoPreview();
        window.removeEventListener('beforeunload', warnIfUnsavedBeforeUnload);
        stopBefore();
    });
});

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
    <section>
        <AppCard>
            <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
                <div class="flex min-w-0 items-center gap-3">
                <h2 class="shrink-0 font-heading text-3xl font-bold tracking-tight text-ocean-deep dark:text-gray-100">
                    {{ __('Products') }}
                    <span class="mt-2 block h-1 w-16 rounded-full bg-warm-gold" aria-hidden="true" />
                </h2>
                <p class="text-sm leading-snug text-muted-foreground dark:text-gray-400">
                    <span class="block">{{ __('Manage products, turn them on or off, and change photos.') }}</span>
                    <span class="block">{{ __('Add modifiers and variations to each item.') }}</span>
                </p>
                </div>
                <AppButton variant="success" @click="openCreate">{{ __('Add Product') }}</AppButton>
            </div>
            <div
                class="-mx-6 mb-6 overflow-hidden border-b border-border bg-muted/40 px-3 pt-2 dark:border-gray-700 dark:bg-gray-900/40"
            >
                <div class="overflow-x-auto overflow-y-hidden" role="tablist">
                    <nav class="flex items-end gap-1">
                        <button
                            type="button"
                            role="tab"
                            :aria-selected="isCategoryTabActive('')"
                            :class="categoryTabClass('')"
                            @click="filterByCategory('')"
                        >
                            {{ __('All Categories') }}
                        </button>
                        <button
                            v-for="category in categories"
                            :key="category.id"
                            type="button"
                            role="tab"
                            :aria-selected="isCategoryTabActive(category.id)"
                            :class="categoryTabClass(category.id)"
                            @click="filterByCategory(category.id)"
                        >
                            {{ category.name }}
                        </button>
                        <span class="pointer-events-none ml-[4cm] select-none whitespace-nowrap px-4 py-2.5 text-sm font-medium text-gray-300 dark:text-gray-600">
                            {{ __('Categories') }}
                        </span>
                    </nav>
                </div>
            </div>

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
                    class="flex flex-col gap-2 py-3 lg:flex-row lg:items-center lg:gap-4"
                    :class="{ 'opacity-50': draggingProductId === product.id }"
                    @dragover.prevent
                    @drop.prevent="onProductDrop($event, product.id)"
                >
                    <div class="flex min-w-0 items-center gap-2 lg:contents">
                        <button
                            type="button"
                            class="flex w-10 shrink-0 cursor-grab flex-col items-center gap-0.5 text-muted-foreground active:cursor-grabbing"
                            draggable="true"
                            :aria-label="__('Sort')"
                            @dragstart="onProductDragStart($event, product.id)"
                            @dragend="draggingProductId = null"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                            <span class="text-[9px] font-normal leading-tight">{{ __('Sort') }}</span>
                        </button>
                    <div class="min-w-0 lg:w-52 lg:shrink-0">
                        <div class="flex items-center gap-2">
                            <span
                                class="font-heading text-sm font-semibold"
                                :class="product.active
                                    ? 'text-ocean-deep dark:text-gray-100'
                                    : 'text-muted-foreground line-through dark:text-gray-400'"
                            >{{ product.name }}</span>
                        </div>
                        <div
                            class="mt-0.5 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-muted-foreground"
                            :class="{ 'line-through': !product.active }"
                        >
                            <span>R$ {{ Number(product.price).toFixed(2) }}</span>
                            <span v-if="product.category">
                                <span class="text-warm-gold">{{ __('Cat.:') }}</span>
                                {{ product.category.name }}
                            </span>
                            <span v-if="product.kitchen_station">
                                <span class="text-warm-gold">{{ __('Sector:') }}</span>
                                {{ product.kitchen_station.name }}
                            </span>
                        </div>
                    </div>
                    </div>
                    <div
                        class="min-w-0 text-xs lg:w-48 lg:shrink-0"
                        :class="{ 'line-through': !product.active }"
                    >
                        <span class="text-warm-gold">{{ __('Modifiers:') }}</span>
                        <span v-if="product.modifier_groups?.length" class="ml-1 inline-flex flex-wrap items-center gap-1">
                            <span
                                v-for="group in product.modifier_groups"
                                :key="group.id"
                                class="rounded-md bg-primary/10 px-1.5 py-0.5 font-medium text-primary"
                            >
                                {{ group.name }}
                            </span>
                        </span>
                        <span v-else class="ml-1 text-xs text-muted-foreground">{{ __('No modifiers') }}</span>
                    </div>
                    <div
                        class="min-w-0 text-xs lg:w-52 lg:shrink-0"
                        :class="{ 'line-through': !product.active }"
                    >
                        <div class="flex items-start gap-1">
                            <span class="shrink-0 text-warm-gold">{{ __('Variations:') }}</span>
                            <div v-if="product.variations?.length" class="min-w-0 flex-1">
                                <p
                                    v-for="variation in product.variations"
                                    :key="variation.id"
                                    class="grid grid-cols-[minmax(0,1fr)_auto] items-baseline gap-x-1 font-medium text-primary"
                                >
                                    <span class="truncate text-right">{{ variation.name }}</span>
                                    <span class="shrink-0">
                                        - <span class="text-[0.7em] font-normal opacity-60">R$</span> {{ formatListPrice(variation.price) }}
                                    </span>
                                </p>
                            </div>
                            <span v-else class="text-muted-foreground">{{ __('No variations') }}</span>
                        </div>
                    </div>
                    <div class="size-14 shrink-0 overflow-hidden rounded-md border border-gray-200 bg-transparent dark:border-gray-700">
                        <img
                            v-if="productPhotoSrc(product)"
                            :src="productPhotoSrc(product)"
                            :alt="product.name"
                            class="h-full w-full object-cover"
                        />
                        <span
                            v-else
                            class="flex h-full w-full items-center justify-center px-1 text-center text-[10px] font-normal leading-tight text-gray-300 dark:text-gray-600"
                        >
                            {{ __('No photo') }}
                        </span>
                    </div>
                    <div class="flex items-center gap-3 lg:ml-auto lg:shrink-0">
                        <div class="flex flex-col items-center gap-0.5">
                            <button
                                type="button"
                                role="switch"
                                :aria-checked="product.active"
                                :aria-label="__('Active')"
                                class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2"
                                :class="product.active
                                    ? 'bg-[#5c9a6c] focus:ring-[#5c9a6c]'
                                    : 'bg-gray-400 focus:ring-gray-300'"
                                @click="toggleActive(product)"
                            >
                                <span
                                    class="inline-block h-4 w-4 transform rounded-full transition-transform"
                                    :class="product.active ? 'translate-x-6 bg-white' : 'translate-x-1 bg-gray-100'"
                                />
                            </button>
                            <p
                                class="text-[10px] leading-tight"
                                :class="product.active
                                    ? 'text-[#4e7d5a] dark:text-[#7aab86]'
                                    : 'text-muted-foreground dark:text-gray-400'"
                            >
                                {{ __('Current status: :status', { status: product.active ? __('on') : __('off') }) }}
                            </p>
                        </div>
                        <AppButton size="sm" variant="secondary" @click="openEdit(product)">
                            <span class="flex flex-col leading-tight">
                                <span>{{ __('Edit') }}</span>
                                <span>{{ __('Product') }}</span>
                            </span>
                        </AppButton>
                        <AppButton size="sm" variant="destructive" @click="confirmDelete(product)">
                            <span class="flex flex-col leading-tight">
                                <span>{{ __('Delete') }}</span>
                                <span>{{ __('Product') }}</span>
                            </span>
                        </AppButton>
                    </div>
                </div>
            </div>

            <div v-if="showForm" class="mt-4 rounded-lg border-4 border-[#5c9a6c] p-4">
                <div class="mb-3 flex flex-wrap items-center gap-3">
                    <h3 class="font-heading text-lg font-bold tracking-tight text-ocean-deep dark:text-gray-100">
                        {{ editingProduct ? __('Edit Product') : __('New Product') }}
                        <span class="mt-1 block h-0.5 w-10 rounded-full bg-warm-gold" aria-hidden="true" />
                    </h3>
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            role="switch"
                            :aria-checked="form.active"
                            :aria-label="__('Active')"
                            class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2"
                            :class="form.active
                                ? 'bg-[#5c9a6c] focus:ring-[#5c9a6c]'
                                : 'bg-gray-400 focus:ring-gray-300'"
                            @click="form.active = !form.active"
                        >
                            <span
                                class="inline-block h-4 w-4 transform rounded-full transition-transform"
                                :class="form.active ? 'translate-x-6 bg-white' : 'translate-x-1 bg-gray-100'"
                            />
                        </button>
                        <p
                            class="text-xs"
                            :class="form.active
                                ? 'text-[#4e7d5a] dark:text-[#7aab86]'
                                : 'text-muted-foreground dark:text-gray-400'"
                        >
                            {{ __('Current status: :status', { status: form.active ? __('on') : __('off') }) }}
                        </p>
                    </div>
                </div>
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                    <form class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:col-span-2" @submit.prevent="submit">
                        <div class="flex flex-col gap-3">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-ocean-deep dark:text-gray-100">{{ __('Product Name') }} <span class="text-destructive">*</span></label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:border-gray-700 dark:bg-gray-700 dark:text-gray-100"
                                    :class="{ 'text-muted-foreground line-through': !form.active }"
                                />
                                <p v-if="form.errors.name" class="mt-1 text-xs text-destructive">{{ form.errors.name }}</p>
                            </div>
                            <div class="flex min-h-0 flex-1 flex-col">
                                <label class="mb-1 block text-sm font-medium text-ocean-deep dark:text-gray-100">{{ __('Description') }}</label>
                                <textarea
                                    v-model="form.description"
                                    rows="6"
                                    class="w-full flex-1 rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:border-gray-700 dark:bg-gray-700 dark:text-gray-100"
                                />
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-ocean-deep dark:text-gray-100">{{ __('Serves') }}</label>
                                <div class="flex items-center gap-3">
                                    <div class="flex items-center rounded-md border border-border dark:border-gray-700">
                                        <button
                                            type="button"
                                            class="px-3 py-2 text-lg font-bold text-ocean-deep disabled:opacity-30 dark:text-gray-100"
                                            :disabled="Number(form.servings) <= 1"
                                            :aria-label="__('Decrease servings')"
                                            @click="changeServings(-1)"
                                        >−</button>
                                        <span class="min-w-[32px] text-center text-sm font-semibold text-ocean-deep dark:text-gray-100">{{ Number(form.servings) || 1 }}</span>
                                        <button
                                            type="button"
                                            class="px-3 py-2 text-lg font-bold text-ocean-deep disabled:opacity-30 dark:text-gray-100"
                                            :disabled="Number(form.servings) >= 20"
                                            :aria-label="__('Increase servings')"
                                            @click="changeServings(1)"
                                        >+</button>
                                    </div>
                                    <p class="text-sm text-muted-foreground dark:text-gray-400">
                                        {{ Number(form.servings) === 1 ? __('person') : __('people') }}
                                    </p>
                                </div>
                                <p v-if="form.errors.servings" class="mt-1 text-xs text-destructive">{{ form.errors.servings }}</p>
                            </div>
                        </div>

                        <div class="flex min-h-0 flex-1 flex-col gap-3">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-ocean-deep dark:text-gray-100">{{ __('Price') }} (R$) <span class="text-destructive">*</span></label>
                                <input
                                    v-model="form.price"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:border-gray-700 dark:bg-gray-700 dark:text-gray-100"
                                />
                                <p v-if="form.errors.price" class="mt-1 text-xs text-destructive">{{ form.errors.price }}</p>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-ocean-deep dark:text-gray-100">{{ __('Menu Category') }} <span class="text-destructive">*</span></label>
                                <select
                                    v-model="form.category_id"
                                    class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:border-gray-700 dark:bg-gray-700 dark:text-gray-100"
                                >
                                    <option value="">{{ __('Select a category') }}</option>
                                    <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                                </select>
                                <p v-if="form.errors.category_id" class="mt-1 text-xs text-destructive">{{ form.errors.category_id }}</p>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-ocean-deep dark:text-gray-100">{{ __('Production Sector') }}</label>
                                <select
                                    v-model="form.kitchen_station_id"
                                    class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:border-gray-700 dark:bg-gray-700 dark:text-gray-100"
                                >
                                    <option value="">{{ __('None') }}</option>
                                    <option v-for="station in stations" :key="station.id" :value="station.id">{{ station.name }}</option>
                                </select>
                                <p v-if="form.errors.kitchen_station_id" class="mt-1 text-xs text-destructive">{{ form.errors.kitchen_station_id }}</p>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-ocean-deep dark:text-gray-100">{{ __('Photo') }}</label>
                                <div class="flex items-start gap-3">
                                    <div
                                        class="relative flex size-32 shrink-0 cursor-pointer items-center justify-center overflow-hidden rounded-md border-2 border-dashed px-1.5 text-center transition-colors"
                                        :class="isDraggingPhoto
                                            ? 'border-primary bg-primary/5 dark:bg-primary/10'
                                            : 'border-border bg-muted/40 dark:border-gray-600 dark:bg-gray-800/60'"
                                        @click="photoInput?.click()"
                                        @dragenter.prevent="isDraggingPhoto = true"
                                        @dragover.prevent="isDraggingPhoto = true"
                                        @dragleave.prevent="isDraggingPhoto = false"
                                        @drop.prevent="onPhotoDrop"
                                    >
                                        <input
                                            ref="photoInput"
                                            type="file"
                                            accept="image/jpeg,image/png,image/webp"
                                            class="hidden"
                                            @change="onPhotoSelect"
                                            @click.stop
                                        />
                                        <img
                                            v-if="photoPreview"
                                            :src="photoPreview"
                                            :alt="form.name || __('Photo')"
                                            class="pointer-events-none absolute inset-0 h-full w-full object-cover"
                                        />
                                        <div
                                            v-if="photoPreview"
                                            class="pointer-events-none absolute inset-0 bg-black/35"
                                        />
                                        <p
                                            class="pointer-events-none relative z-10 text-[11px] leading-tight"
                                            :class="photoPreview ? 'text-white' : 'text-muted-foreground dark:text-gray-400'"
                                        >
                                            {{ photoPreview
                                                ? __('Drop a new photo to replace it, or click to choose.')
                                                : __('Drag a photo here or click to upload.') }}
                                        </p>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-xs text-muted-foreground dark:text-gray-400">{{ __('Format: JPEG, PNG or WebP') }}</p>
                                        <p class="mt-1 text-xs text-muted-foreground dark:text-gray-400">{{ __('A square image is preferred.') }}</p>
                                        <p v-if="form.errors.photo" class="mt-1 text-xs text-destructive">{{ form.errors.photo }}</p>
                                        <AppButton
                                            type="button"
                                            size="sm"
                                            variant="destructive"
                                            class="mt-3"
                                            :disabled="!photoPreview"
                                            @click.stop="clearPhoto"
                                        >
                                            {{ __('Delete photo') }}
                                        </AppButton>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-start gap-2 sm:col-span-2">
                            <AppButton type="submit" :loading="form.processing">{{ __('Save') }}</AppButton>
                            <AppButton type="button" variant="ghost" @click="requestCloseForm">{{ __('Cancel') }}</AppButton>
                        </div>
                    </form>

                    <div class="flex flex-col gap-4 lg:border-l lg:border-border lg:pl-4 dark:lg:border-gray-700">
                        <p v-if="!editingProduct" class="text-sm text-muted-foreground dark:text-gray-400">
                            {{ __('Save the product first to manage modifiers and variations.') }}
                        </p>

                        <div v-if="editingProduct && modifierGroups?.length">
                            <div class="mb-2 flex items-start justify-between gap-3">
                                <h4 class="shrink-0 font-heading text-lg font-bold tracking-tight text-ocean-deep dark:text-gray-100">
                                    {{ __('Modifier Groups') }}
                                    <span class="mt-1 block h-0.5 w-10 rounded-full bg-warm-gold" aria-hidden="true" />
                                </h4>
                                <p class="min-w-0 flex-1 text-sm leading-snug text-muted-foreground dark:text-gray-400">
                                    {{ __('Select which modifier groups apply to this product.') }}
                                </p>
                            </div>
                            <AppButton size="sm" variant="secondary" class="mb-3" @click="openModifierPicker">
                                {{ __('Add Modifiers') }}
                            </AppButton>
                            <p class="mb-2 text-xs text-muted-foreground dark:text-gray-400">{{ __('Selected modifiers:') }}</p>
                            <div v-if="selectedModifierGroups.length" class="flex flex-wrap gap-2">
                                <span
                                    v-for="group in selectedModifierGroups"
                                    :key="group.id"
                                    class="rounded-md bg-primary px-3 py-1.5 text-sm text-white"
                                >
                                    {{ group.name }}
                                    <span v-if="group.required" class="ml-1 text-xs text-white/80">({{ __('req.') }})</span>
                                </span>
                            </div>
                        </div>

                        <div v-if="editingProduct">
                            <div class="mb-2 flex items-start justify-between gap-3">
                                <h4 class="shrink-0 font-heading text-lg font-bold tracking-tight text-ocean-deep dark:text-gray-100">
                                    {{ __('Variations') }}
                                    <span class="mt-1 block h-0.5 w-10 rounded-full bg-warm-gold" aria-hidden="true" />
                                </h4>
                                <p class="min-w-0 flex-1 text-sm leading-snug text-muted-foreground dark:text-gray-400">
                                    {{ __('Variations override the base price (e.g. Small, Medium, Large).') }}
                                </p>
                            </div>
                            <AppButton size="sm" variant="secondary" class="mb-3" @click="openAddVariation">{{ __('Add Variations') }}</AppButton>

                            <div v-if="editingProduct.variations?.length" class="mb-3 space-y-2">
                                <div
                                    v-for="variation in editingProduct.variations"
                                    :key="variation.id"
                                    class="rounded border border-border dark:border-gray-700 p-2"
                                >
                                    <div v-if="editingVariationId !== variation.id" class="flex items-center justify-between gap-2">
                                        <div class="flex min-w-0 flex-wrap items-center gap-2">
                                            <span class="text-sm font-medium text-ocean-deep dark:text-gray-100">{{ variation.name }}</span>
                                            <span class="text-sm text-muted-foreground">R$ {{ Number(variation.price).toFixed(2) }}</span>
                                            <AppBadge :label="variation.active ? __('Active') : __('Inactive')" :color="variation.active ? '#22c55e' : '#94a3b8'" />
                                        </div>
                                        <div class="flex shrink-0 gap-1">
                                            <AppButton size="sm" variant="secondary" @click="openEditVariation(variation)">
                                                <span class="flex flex-col leading-tight">
                                                    <span>{{ __('Edit') }}</span>
                                                    <span>{{ __('Variation') }}</span>
                                                </span>
                                            </AppButton>
                                            <AppButton size="sm" variant="destructive" @click="confirmDeleteVariation(variation)">
                                                <span class="flex flex-col leading-tight">
                                                    <span>{{ __('Delete') }}</span>
                                                    <span>{{ __('Variation') }}</span>
                                                </span>
                                            </AppButton>
                                        </div>
                                    </div>

                                    <form
                                        v-else
                                        class="grid grid-cols-1 gap-2"
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
                                        <div class="flex flex-wrap items-center gap-2">
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

                            <form
                                v-if="addingVariation"
                                class="grid grid-cols-1 gap-2 rounded border-2 border-[#5c9a6c] p-2"
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
                                <div class="flex flex-wrap items-center gap-2">
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
                </div>
            </div>
        </AppCard>

        <Modal :show="showModifierPicker" max-width="md" @close="closeModifierPicker">
            <div class="p-6">
                <h2 class="font-heading text-lg font-semibold text-ocean-deep dark:text-gray-100">{{ __('Modifier Groups') }}</h2>
                <p class="mt-2 text-sm text-muted-foreground dark:text-gray-400">
                    {{ __('Select which modifier groups apply to this product.') }}
                </p>
                <div class="mt-4 max-h-80 space-y-1 overflow-y-auto">
                    <button
                        v-for="group in modifierGroups"
                        :key="group.id"
                        type="button"
                        class="w-full rounded-lg px-3 py-2.5 text-left text-sm font-medium transition-colors"
                        :class="isModifierGroupSelected(group.id)
                            ? 'bg-ocean-deep text-sand'
                            : 'text-ocean-deep hover:text-warm-gold dark:text-gray-100 dark:hover:text-warm-gold'"
                        @click="toggleGroupInSync(group.id)"
                    >
                        {{ group.name }}
                        <span
                            v-if="group.required"
                            class="ml-1 text-xs"
                            :class="isModifierGroupSelected(group.id) ? 'text-sand/80' : 'text-amber-600'"
                        >({{ __('req.') }})</span>
                    </button>
                </div>
                <div class="mt-6 flex justify-end">
                    <AppButton @click="closeModifierPicker">{{ __('Done') }}</AppButton>
                </div>
            </div>
        </Modal>

        <Modal :show="showUnsavedModal" max-width="md" @close="stayOnForm">
            <div class="p-6">
                <h2 class="font-heading text-lg font-semibold text-ocean-deep dark:text-gray-100">{{ __('Unsaved changes') }}</h2>
                <p class="mt-2 text-sm text-muted-foreground dark:text-gray-400">
                    {{ __('You have unsaved changes. Do you want to save them?') }}
                </p>
                <div class="mt-6 flex flex-wrap items-center justify-end gap-3">
                    <AppButton variant="ghost" @click="stayOnForm">{{ __('Cancel') }}</AppButton>
                    <AppButton variant="secondary" @click="discardChangesAndLeave">{{ __("Don't save") }}</AppButton>
                    <AppButton :loading="form.processing" @click="saveChangesAndLeave">{{ __('Save') }}</AppButton>
                </div>
            </div>
        </Modal>

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
    </section>
</template>
