<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import AppCard from '@/Components/AppCard.vue';
import AppButton from '@/Components/AppButton.vue';
import AppConfirmModal from '@/Components/AppConfirmModal.vue';
import AppEmptyState from '@/Components/AppEmptyState.vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    categories: Array,
    menuId: String,
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
</script>

<template>
    <AppLayout :title="__('Menu')">
        <template #header>
            <div class="flex items-center justify-between">
                <h1 class="font-heading text-2xl font-bold text-ocean-deep">{{ __('Menu') }}</h1>
                <div class="flex items-center gap-3">
                    <Link :href="route('menu.products.index')" class="text-sm font-medium text-primary hover:underline">
                        {{ __('Manage Products') }}
                    </Link>
                    <AppButton @click="openCreate">{{ __('Add Category') }}</AppButton>
                </div>
            </div>
        </template>

        <AppCard>
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
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="font-body text-sm font-semibold text-ocean-deep">{{ category.name }}</span>
                            <span class="ml-2 text-xs text-muted-foreground">{{ category.products?.length ?? 0 }} {{ __('products') }}</span>
                        </div>
                        <div class="flex gap-2">
                            <AppButton size="sm" variant="ghost" :disabled="index === 0" @click="moveUp(categories, index)">↑</AppButton>
                            <AppButton size="sm" variant="ghost" :disabled="index === categories.length - 1" @click="moveDown(categories, index)">↓</AppButton>
                            <AppButton size="sm" variant="secondary" @click="openEdit(category)">{{ __('Edit') }}</AppButton>
                            <AppButton size="sm" variant="destructive" @click="confirmDelete(category)">{{ __('Delete') }}</AppButton>
                        </div>
                    </div>

                    <div v-if="category.products?.length" class="mt-2 flex flex-wrap gap-2 pl-2">
                        <span
                            v-for="product in category.products.slice(0, 5)"
                            :key="product.id"
                            class="rounded-full bg-ocean-light px-2 py-0.5 text-xs text-ocean-deep"
                        >
                            {{ product.name }}
                        </span>
                        <span
                            v-if="category.products.length > 5"
                            class="rounded-full bg-muted px-2 py-0.5 text-xs text-muted-foreground"
                        >
                            +{{ category.products.length - 5 }} {{ __('more') }}
                        </span>
                    </div>
                </div>
            </div>

            <div v-if="showForm" class="mt-4 rounded-lg border border-border p-4">
                <h3 class="mb-3 font-heading text-sm font-semibold text-ocean-deep">
                    {{ editingCategory ? __('Edit Category') : __('New Category') }}
                </h3>
                <form class="space-y-3" @submit.prevent="submit">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-ocean-deep">
                            {{ __('Name') }} <span class="text-destructive">*</span>
                        </label>
                        <input
                            v-model="form.name"
                            type="text"
                            class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
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
