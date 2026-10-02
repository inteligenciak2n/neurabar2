<script setup>
import AppCard from '@/Components/AppCard.vue';
import AppButton from '@/Components/AppButton.vue';
import AppConfirmModal from '@/Components/AppConfirmModal.vue';
import AppEmptyState from '@/Components/AppEmptyState.vue';
import { useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    combos: {
        type: Array,
        default: () => [],
    },
    products: {
        type: Array,
        default: () => [],
    },
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

const comboProducts = computed(() => (props.products ?? []).filter((product) => product.active));

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

const toggleActive = (combo) => {
    router.post(route('menu.combos.toggle', combo.id));
};

const deleteCombo = () => {
    router.delete(route('menu.combos.destroy', comboToDelete.value.id), {
        onSuccess: () => { comboToDelete.value = null; },
    });
};

const itemCount = (combo) => combo.items?.length ?? 0;
</script>

<template>
    <section>
        <AppCard>
            <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
                <div class="flex min-w-0 items-center gap-3">
                    <h2 class="shrink-0 font-heading text-3xl font-bold tracking-tight text-ocean-deep dark:text-gray-100">
                        {{ __('Combos') }}
                        <span class="mt-2 block h-1 w-16 rounded-full bg-warm-gold" aria-hidden="true" />
                    </h2>
                    <p class="text-sm leading-snug text-muted-foreground dark:text-gray-400">
                        <span class="block">{{ __('Bundle products into a special-price offer.') }}</span>
                        <span class="block">{{ __('The customer orders the combo as one item.') }}</span>
                    </p>
                </div>
                <AppButton variant="success" @click="openCreate">{{ __('Add Combo') }}</AppButton>
            </div>

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
                        <div class="grid min-w-0 flex-1 grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="min-w-0">
                                <span class="font-heading text-sm font-semibold text-ocean-deep dark:text-gray-100">{{ combo.name }}</span>
                                <p class="mt-1 text-xs text-muted-foreground">
                                    R$ {{ Number(combo.price).toFixed(2) }}
                                </p>
                                <p class="mt-0.5 text-xs text-muted-foreground">
                                    {{ itemCount(combo) }} {{ itemCount(combo) === 1 ? __('item') : __('items') }}
                                </p>
                            </div>
                            <ul v-if="combo.items?.length" class="space-y-0.5">
                                <li
                                    v-for="item in combo.items"
                                    :key="item.id"
                                    class="text-sm text-ocean-deep dark:text-gray-100"
                                >
                                    {{ item.quantity }}× {{ item.product?.name }}
                                    <span v-if="item.variation" class="text-muted-foreground">— {{ item.variation.name }}</span>
                                </li>
                            </ul>
                        </div>
                        <div class="flex shrink-0 items-center gap-3">
                            <div class="flex flex-col items-center gap-0.5">
                                <button
                                    type="button"
                                    role="switch"
                                    :aria-checked="combo.active"
                                    :aria-label="__('Active')"
                                    class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2"
                                    :class="combo.active
                                        ? 'bg-[#5c9a6c] focus:ring-[#5c9a6c]'
                                        : 'bg-gray-400 focus:ring-gray-300'"
                                    @click="toggleActive(combo)"
                                >
                                    <span
                                        class="inline-block h-4 w-4 transform rounded-full transition-transform"
                                        :class="combo.active ? 'translate-x-6 bg-white' : 'translate-x-1 bg-gray-100'"
                                    />
                                </button>
                                <p
                                    class="text-[10px] leading-tight"
                                    :class="combo.active
                                        ? 'text-[#4e7d5a] dark:text-[#7aab86]'
                                        : 'text-muted-foreground dark:text-gray-400'"
                                >
                                    {{ __('Current status: :status', { status: combo.active ? __('on') : __('off') }) }}
                                </p>
                            </div>
                            <AppButton size="sm" variant="secondary" @click="openEdit(combo)">
                                <span class="flex flex-col leading-tight">
                                    <span>{{ __('Edit') }}</span>
                                    <span>{{ __('Combo') }}</span>
                                </span>
                            </AppButton>
                            <AppButton size="sm" variant="destructive" @click="confirmDelete(combo)">
                                <span class="flex flex-col leading-tight">
                                    <span>{{ __('Delete') }}</span>
                                    <span>{{ __('Combo') }}</span>
                                </span>
                            </AppButton>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="showForm" class="mt-4 rounded-lg border-4 border-[#5c9a6c] p-4">
                <div class="mb-3 flex flex-wrap items-center gap-3">
                    <h3 class="font-heading text-lg font-bold tracking-tight text-ocean-deep dark:text-gray-100">
                        {{ editingCombo ? __('Edit Combo') : __('New Combo') }}
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

                <form class="grid grid-cols-1 items-start gap-4 lg:grid-cols-2" @submit.prevent="submit">
                    <div class="flex flex-col gap-3">
                        <div>
                            <label class="mb-1 block text-sm font-bold text-ocean-deep dark:text-gray-100">{{ __('Combo Name') }} <span class="text-destructive">*</span></label>
                            <input
                                v-model="form.name"
                                type="text"
                                class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:border-gray-700 dark:bg-gray-700 dark:text-gray-100"
                                :class="{ 'text-muted-foreground line-through': !form.active }"
                            />
                            <p v-if="form.errors.name" class="mt-1 text-xs text-destructive">{{ form.errors.name }}</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-bold text-ocean-deep dark:text-gray-100">{{ __('Combo Price') }} (R$) <span class="text-destructive">*</span></label>
                            <input
                                v-model="form.price"
                                type="number"
                                min="0"
                                step="0.01"
                                class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:border-gray-700 dark:bg-gray-700 dark:text-gray-100"
                            />
                            <p v-if="form.errors.price" class="mt-1 text-xs text-destructive">{{ form.errors.price }}</p>
                        </div>
                        <div class="flex min-h-0 flex-1 flex-col">
                            <label class="mb-1 block text-sm font-bold text-ocean-deep dark:text-gray-100">{{ __('Combo Description') }}</label>
                            <textarea
                                v-model="form.description"
                                rows="6"
                                class="w-full flex-1 rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:border-gray-700 dark:bg-gray-700 dark:text-gray-100"
                            />
                        </div>
                        <div class="flex justify-start gap-2">
                            <AppButton type="submit" :loading="form.processing">{{ __('Save') }}</AppButton>
                            <AppButton type="button" variant="ghost" @click="closeForm">{{ __('Cancel') }}</AppButton>
                        </div>
                    </div>

                    <div class="flex flex-col gap-4 lg:border-l lg:border-border lg:pl-4 dark:lg:border-gray-700">
                        <div>
                            <div class="mb-2 flex items-start justify-between gap-2">
                                <h4 class="font-heading text-lg font-bold tracking-tight text-ocean-deep dark:text-gray-100">
                                    {{ __('Items') }}
                                    <span class="mt-1 block h-0.5 w-10 rounded-full bg-warm-gold" aria-hidden="true" />
                                </h4>
                                <AppButton type="button" size="sm" variant="secondary" @click="addItem">{{ __('Add Item') }}</AppButton>
                            </div>
                            <p v-if="form.errors.items" class="mb-2 text-xs text-destructive">{{ form.errors.items }}</p>

                            <div class="space-y-3">
                                <div
                                    v-for="(item, index) in form.items"
                                    :key="index"
                                    class="rounded-lg border-2 border-[#5c9a6c] bg-gray-50 p-3 shadow-sm dark:bg-gray-800"
                                >
                                    <div class="grid grid-cols-1 gap-2">
                                        <div>
                                            <label class="mb-1 block text-xs font-medium text-ocean-deep dark:text-gray-100">{{ __('Product') }}</label>
                                            <select
                                                v-model="item.product_id"
                                                class="w-full rounded-md border border-border px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:border-gray-700 dark:bg-gray-700 dark:text-gray-100"
                                                @change="item.variation_id = ''"
                                            >
                                                <option value="">{{ __('Select...') }}</option>
                                                <option v-for="product in comboProducts" :key="product.id" :value="product.id">
                                                    {{ product.name }}
                                                </option>
                                            </select>
                                            <p v-if="form.errors[`items.${index}.product_id`]" class="mt-1 text-xs text-destructive">
                                                {{ form.errors[`items.${index}.product_id`] }}
                                            </p>
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-xs font-medium text-ocean-deep dark:text-gray-100">{{ __('Variation') }}</label>
                                            <select
                                                v-model="item.variation_id"
                                                :disabled="!variationsForProduct(item.product_id).length"
                                                class="w-full rounded-md border border-border px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary disabled:opacity-50 dark:border-gray-700 dark:bg-gray-700 dark:text-gray-100"
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
                                        <div class="flex flex-wrap items-end gap-2">
                                            <div class="w-28">
                                                <label class="mb-1 block text-xs font-medium text-ocean-deep dark:text-gray-100">{{ __('Qty') }}</label>
                                                <input
                                                    v-model.number="item.quantity"
                                                    type="number"
                                                    min="1"
                                                    class="w-full rounded-md border border-border px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:border-gray-700 dark:bg-gray-700 dark:text-gray-100"
                                                />
                                                <p v-if="form.errors[`items.${index}.quantity`]" class="mt-1 text-xs text-destructive">
                                                    {{ form.errors[`items.${index}.quantity`] }}
                                                </p>
                                            </div>
                                            <button
                                                type="button"
                                                class="pb-1.5 text-xs text-destructive hover:underline disabled:opacity-50"
                                                :disabled="form.items.length === 1"
                                                @click="removeItem(index)"
                                            >
                                                {{ __('Remove') }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
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
    </section>
</template>
