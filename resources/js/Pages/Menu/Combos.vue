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
    combos: Array,
    products: Array,
});

const showForm = ref(false);
const editingCombo = ref(null);
const comboToDelete = ref(null);

const emptyItem = () => ({ product_id: '', variation_id: '', quantity: 1 });

const form = useForm({
    name: '',
    description: '',
    price: '',
    active: true,
    items: [emptyItem()],
});

const openCreate = () => {
    editingCombo.value = null;
    form.reset();
    form.active = true;
    form.items = [emptyItem()];
    showForm.value = true;
};

const openEdit = (combo) => {
    editingCombo.value = combo;
    form.name = combo.name;
    form.description = combo.description ?? '';
    form.price = combo.price;
    form.active = combo.active;
    form.items = combo.items.map((i) => ({
        product_id: i.product_id,
        variation_id: i.variation_id ?? '',
        quantity: i.quantity,
    }));
    showForm.value = true;
};

const closeForm = () => {
    showForm.value = false;
    editingCombo.value = null;
    form.reset();
};

const addItem = () => {
    form.items.push(emptyItem());
};

const removeItem = (index) => {
    if (form.items.length > 1) {
        form.items.splice(index, 1);
    }
};

const variationsForProduct = (productId) => {
    const product = props.products?.find((p) => p.id === productId);
    return product?.variations ?? [];
};

const productName = (productId) => {
    return props.products?.find((p) => p.id === productId)?.name ?? '—';
};

const submit = () => {
    const payload = {
        ...form.data(),
        items: form.items.map((i) => ({
            product_id: i.product_id,
            variation_id: i.variation_id || null,
            quantity: i.quantity,
        })),
    };

    if (editingCombo.value) {
        form.transform(() => payload).put(route('menu.combos.update', editingCombo.value.id), {
            onSuccess: closeForm,
        });
    } else {
        form.transform(() => payload).post(route('menu.combos.store'), {
            onSuccess: closeForm,
        });
    }
};

const confirmDelete = (combo) => {
    comboToDelete.value = combo;
};

const deleteCombo = () => {
    router.delete(route('menu.combos.destroy', comboToDelete.value.id), {
        onSuccess: () => { comboToDelete.value = null; },
    });
};

const itemCount = (combo) => combo.items?.length ?? 0;
</script>

<template>
    <AppLayout :title="__('Combos')">
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link :href="route('menu.index')" class="text-sm font-medium text-primary hover:underline">← {{ __('Menu') }}</Link>
                    <h1 class="font-heading text-2xl font-bold text-ocean-deep">{{ __('Combos') }}</h1>
                </div>
                <AppButton @click="openCreate">{{ __('Add Combo') }}</AppButton>
            </div>
        </template>

        <AppCard>
            <AppEmptyState
                v-if="!combos?.length && !showForm"
                :title="__('No combos yet')"
                :description="__('Create a combo to bundle products at a special price.')"
                :action-label="__('Add Combo')"
                @action="openCreate"
            />

            <div v-if="combos?.length && !showForm" class="divide-y divide-muted">
                <div
                    v-for="combo in combos"
                    :key="combo.id"
                    class="py-4"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-body text-sm font-semibold text-ocean-deep">{{ combo.name }}</span>
                                <AppBadge
                                    :label="combo.active ? __('Active') : __('Inactive')"
                                    :color="combo.active ? '#22c55e' : '#94a3b8'"
                                />
                            </div>
                            <div class="mt-0.5 flex flex-wrap items-center gap-3 text-xs text-muted-foreground">
                                <span class="font-heading font-bold text-primary">R$ {{ Number(combo.price).toFixed(2) }}</span>
                                <span>{{ itemCount(combo) }} {{ itemCount(combo) === 1 ? __('item') : __('items') }}</span>
                            </div>
                            <ul v-if="combo.items?.length" class="mt-2 space-y-0.5">
                                <li
                                    v-for="item in combo.items"
                                    :key="item.id"
                                    class="text-xs text-muted-foreground"
                                >
                                    {{ item.quantity }}× {{ item.product?.name }}
                                    <span v-if="item.variation">— {{ item.variation.name }}</span>
                                </li>
                            </ul>
                        </div>
                        <div class="flex shrink-0 gap-2">
                            <AppButton size="sm" variant="secondary" @click="openEdit(combo)">{{ __('Edit') }}</AppButton>
                            <AppButton size="sm" variant="destructive" @click="confirmDelete(combo)">{{ __('Delete') }}</AppButton>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Create / Edit Form -->
            <div v-if="showForm" class="mt-4 rounded-lg border border-border p-4">
                <h3 class="mb-4 font-heading text-sm font-semibold text-ocean-deep">
                    {{ editingCombo ? __('Edit Combo') : __('New Combo') }}
                </h3>

                <form class="space-y-4" @submit.prevent="submit">
                    <!-- Basic fields -->
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
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

                        <div class="flex items-center gap-2 self-end pb-2">
                            <label class="flex cursor-pointer items-center gap-2">
                                <input v-model="form.active" type="checkbox" class="h-4 w-4 rounded border-border text-primary focus:ring-primary" />
                                <span class="text-sm text-ocean-deep">{{ __('Active') }}</span>
                            </label>
                        </div>

                        <div class="sm:col-span-2">
                            <label class="mb-1 block text-sm font-medium text-ocean-deep">{{ __('Description') }}</label>
                            <textarea
                                v-model="form.description"
                                rows="2"
                                class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                            />
                        </div>
                    </div>

                    <!-- Items -->
                    <div>
                        <div class="mb-2 flex items-center justify-between">
                            <label class="text-sm font-medium text-ocean-deep">{{ __('Items') }} <span class="text-destructive">*</span></label>
                            <AppButton type="button" size="sm" variant="secondary" @click="addItem">{{ __('Add Item') }}</AppButton>
                        </div>
                        <p v-if="form.errors.items" class="mb-2 text-xs text-destructive">{{ form.errors.items }}</p>

                        <div class="space-y-2">
                            <div
                                v-for="(item, index) in form.items"
                                :key="index"
                                class="flex items-start gap-2 rounded-md border border-border p-2"
                            >
                                <div class="grid flex-1 grid-cols-1 gap-2 sm:grid-cols-3">
                                    <!-- Product -->
                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-ocean-deep">{{ __('Product') }}</label>
                                        <select
                                            v-model="item.product_id"
                                            class="w-full rounded-md border border-border px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                                            @change="item.variation_id = ''"
                                        >
                                            <option value="">{{ __('Select...') }}</option>
                                            <option v-for="product in products" :key="product.id" :value="product.id">
                                                {{ product.name }}
                                            </option>
                                        </select>
                                        <p v-if="form.errors[`items.${index}.product_id`]" class="mt-1 text-xs text-destructive">
                                            {{ form.errors[`items.${index}.product_id`] }}
                                        </p>
                                    </div>

                                    <!-- Variation -->
                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-ocean-deep">{{ __('Variation') }}</label>
                                        <select
                                            v-model="item.variation_id"
                                            :disabled="!variationsForProduct(item.product_id).length"
                                            class="w-full rounded-md border border-border px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary disabled:opacity-50"
                                        >
                                            <option value="">{{ __('None') }}</option>
                                            <option
                                                v-for="variation in variationsForProduct(item.product_id)"
                                                :key="variation.id"
                                                :value="variation.id"
                                            >
                                                {{ variation.name }}
                                            </option>
                                        </select>
                                    </div>

                                    <!-- Quantity -->
                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-ocean-deep">{{ __('Qty') }}</label>
                                        <input
                                            v-model.number="item.quantity"
                                            type="number"
                                            min="1"
                                            class="w-full rounded-md border border-border px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                                        />
                                        <p v-if="form.errors[`items.${index}.quantity`]" class="mt-1 text-xs text-destructive">
                                            {{ form.errors[`items.${index}.quantity`] }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Remove item -->
                                <button
                                    type="button"
                                    class="mt-6 text-xs text-destructive hover:underline"
                                    :disabled="form.items.length === 1"
                                    @click="removeItem(index)"
                                >
                                    {{ __('Remove') }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <AppButton type="submit" :loading="form.processing">{{ __('Save') }}</AppButton>
                        <AppButton type="button" variant="ghost" @click="closeForm">{{ __('Cancel') }}</AppButton>
                    </div>
                </form>
            </div>
        </AppCard>

        <AppConfirmModal
            :show="!!comboToDelete"
            :title="__('Delete Combo')"
            :message="__('Are you sure you want to delete this combo?')"
            :confirm-label="__('Delete')"
            variant="destructive"
            @confirm="deleteCombo"
            @cancel="comboToDelete = null"
        />
    </AppLayout>
</template>
