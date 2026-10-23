<script setup>
import { ref, computed, watch } from 'vue';
import { useTranslate } from '@/Composables/useTranslate';

const props = defineProps({
    product: Object,
    modelValue: Boolean,
    preview: {
        type: Boolean,
        default: false,
    },
    editingItem: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['update:modelValue', 'add-to-cart']);

const __ = useTranslate();

const selectedVariationId = ref('');
const quantity = ref(1);
const notes = ref('');
const selectedModifiers = ref({});
const isEditing = computed(() => Boolean(props.editingItem));

const selectedVariation = computed(() => {
    if (!selectedVariationId.value) {
        return null;
    }

    return props.product.variations?.find((v) => v.id === selectedVariationId.value) ?? null;
});

const basePrice = computed(() =>
    selectedVariation.value ? Number(selectedVariation.value.price) : Number(props.product.price),
);

const modifiersTotal = computed(() => {
    let total = 0;
    Object.values(selectedModifiers.value).forEach((val) => {
        if (Array.isArray(val)) {
            val.forEach((optId) => {
                const opt = findOption(optId);
                if (opt) total += Number(opt.extra_price ?? 0);
            });
        } else if (val) {
            const opt = findOption(val);
            if (opt) total += Number(opt.extra_price ?? 0);
        }
    });
    return total;
});

const totalPrice = computed(() => (basePrice.value + modifiersTotal.value) * quantity.value);

const servingsText = computed(() => {
    const count = Number(props.product?.servings) || 0;

    if (count < 1) {
        return '';
    }

    if (count === 1) {
        return __('Serves 1 person');
    }

    return __('Serves :count people', { count });
});

function findOption(optId) {
    for (const group of props.product.modifier_groups ?? []) {
        const opt = group.options.find((o) => o.id === optId);
        if (opt) return opt;
    }
    return null;
}

function isSingleChoice(group) {
    return !group.multiple_selection;
}

function isOptionSelected(group, optionId) {
    const current = selectedModifiers.value[group.id];

    if (Array.isArray(current)) {
        return current.includes(optionId);
    }

    return current === optionId;
}

function toggleOption(group, optionId) {
    if (isSingleChoice(group)) {
        selectedModifiers.value = {
            ...selectedModifiers.value,
            [group.id]: optionId,
        };

        return;
    }

    const current = Array.isArray(selectedModifiers.value[group.id])
        ? [...selectedModifiers.value[group.id]]
        : [];
    const index = current.indexOf(optionId);

    if (index >= 0) {
        current.splice(index, 1);
    } else {
        current.push(optionId);
    }

    selectedModifiers.value = {
        ...selectedModifiers.value,
        [group.id]: current,
    };
}

function resetModifiers() {
    const next = {};

    props.product?.modifier_groups?.forEach((group) => {
        next[group.id] = isSingleChoice(group) ? null : [];
    });

    selectedModifiers.value = next;
}

function applyEditingItem(item) {
    selectedVariationId.value = item.variation_id ?? '';
    quantity.value = item.quantity ?? 1;
    notes.value = item.notes ?? '';

    const selectedIds = new Set(item.modifiers ?? []);
    const next = {};

    props.product?.modifier_groups?.forEach((group) => {
        const selected = (group.options ?? [])
            .filter((option) => selectedIds.has(option.id))
            .map((option) => option.id);

        next[group.id] = isSingleChoice(group) ? (selected[0] ?? null) : selected;
    });

    selectedModifiers.value = next;
}

watch(
    () => `${props.product?.id}:${props.modelValue}:${props.editingItem?.product_id}:${props.editingItem?.quantity}:${props.editingItem?.variation_id}:${props.editingItem?.notes}:${(props.editingItem?.modifiers ?? []).join(',')}`,
    () => {
        if (!props.modelValue || !props.product) {
            return;
        }

        if (props.editingItem) {
            applyEditingItem(props.editingItem);
            return;
        }

        selectedVariationId.value = '';
        quantity.value = 1;
        notes.value = '';
        resetModifiers();
    },
    { immediate: true },
);

function addToCart() {
    const modifiers = [];
    const modifierDetails = [];

    Object.entries(selectedModifiers.value).forEach(([, val]) => {
        if (Array.isArray(val)) {
            val.forEach((id) => modifiers.push(id));
        } else if (val) {
            modifiers.push(val);
        }
    });

    (props.product.modifier_groups ?? []).forEach((group) => {
        const selected = selectedModifiers.value[group.id];
        const selectedIds = Array.isArray(selected) ? selected : (selected ? [selected] : []);

        selectedIds.forEach((optionId) => {
            const option = group.options?.find((current) => current.id === optionId);
            if (option) {
                modifierDetails.push({
                    group: group.name,
                    name: option.name,
                });
            }
        });
    });

    emit('add-to-cart', {
        product_id: props.product.id,
        product_name: props.product.name,
        variation_id: selectedVariation.value?.id ?? null,
        variation_name: selectedVariation.value?.name ?? null,
        quantity: quantity.value,
        notes: notes.value || null,
        modifiers,
        modifier_details: modifierDetails,
        unit_price: basePrice.value + modifiersTotal.value,
    });

    emit('update:modelValue', false);
    quantity.value = 1;
    notes.value = '';
}
</script>

<template>
    <Teleport to="body">
        <div
            v-if="modelValue"
            class="fixed inset-0 z-[60] flex items-end sm:items-center justify-center"
            @click.self="emit('update:modelValue', false)"
        >
            <div class="absolute inset-0 bg-black/50" @click="emit('update:modelValue', false)" />

            <div class="relative w-full max-w-sm max-h-[90vh] overflow-y-auto overflow-x-hidden rounded-t-2xl bg-white shadow-xl sm:rounded-2xl">
                <div class="sticky top-0 z-30 flex items-center justify-between bg-white px-4 py-3 shadow-sm">
                    <h2 class="font-heading text-base font-bold text-ocean-deep">{{ __('Reviewing order') }}</h2>
                    <button
                        type="button"
                        class="rounded-full p-1.5 text-muted-foreground hover:bg-muted"
                        @click="emit('update:modelValue', false)"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div v-if="product.image_url" class="bg-muted">
                    <img
                        :src="product.image_url"
                        :alt="product.name"
                        class="mx-auto h-64 w-full object-cover"
                    />
                </div>

                <div
                    class="relative bg-white px-5 pb-5"
                    :class="product.image_url ? '-mt-8 rounded-t-3xl pt-6 shadow-[0_-12px_24px_rgba(0,0,0,0.08)]' : 'pt-5'"
                >
                    <div class="mb-5 text-center">
                        <h3 class="font-heading text-xl font-bold text-ocean-deep">{{ product.name }}</h3>
                        <p v-if="product.description" class="mt-1 text-sm text-muted-foreground">{{ product.description }}</p>
                        <p v-if="servingsText" class="mt-1 text-xs text-muted-foreground">{{ servingsText }}</p>
                    </div>

                <!-- Variations -->
                <div v-if="product.variations?.length" class="mb-4">
                    <p class="mb-2 text-sm font-semibold text-ocean-deep">{{ __('Choose an option') }}</p>
                    <div class="space-y-2">
                        <label
                            class="flex cursor-pointer items-center justify-between rounded-xl border px-3 py-2.5 transition-colors"
                            :class="!selectedVariationId ? 'border-primary bg-primary/5' : 'border-border'"
                        >
                            <div class="flex items-center gap-2">
                                <input
                                    v-model="selectedVariationId"
                                    type="radio"
                                    :value="''"
                                    class="text-primary focus:ring-primary"
                                />
                                <span class="text-sm text-ocean-deep">{{ __('Default') }}</span>
                            </div>
                            <span class="text-sm font-semibold text-primary">R$ {{ Number(product.price).toFixed(2) }}</span>
                        </label>
                        <p v-if="product.variations?.length" class="pt-1 text-sm font-semibold text-ocean-deep">{{ __('Variations') }}</p>
                        <label
                            v-for="variation in product.variations"
                            :key="variation.id"
                            class="flex cursor-pointer items-center justify-between rounded-xl border px-3 py-2.5 transition-colors"
                            :class="selectedVariationId === variation.id ? 'border-primary bg-primary/5' : 'border-border'"
                        >
                            <div class="flex items-center gap-2">
                                <input
                                    v-model="selectedVariationId"
                                    type="radio"
                                    :value="variation.id"
                                    class="text-primary focus:ring-primary"
                                />
                                <span class="text-sm text-ocean-deep">{{ variation.name }}</span>
                            </div>
                            <span class="text-sm font-semibold text-primary">R$ {{ Number(variation.price).toFixed(2) }}</span>
                        </label>
                    </div>
                </div>

                <!-- Modifier groups -->
                <div v-for="group in product.modifier_groups" :key="group.id" class="mb-4">
                    <p class="mb-1 text-sm font-semibold text-ocean-deep">
                        {{ group.name }}
                        <span v-if="group.required" class="text-destructive">*</span>
                    </p>
                    <p class="mb-2 text-xs text-muted-foreground">
                        <template v-if="isSingleChoice(group)">{{ __('Choose') }} 1</template>
                        <template v-else>{{ __('Choose') }}</template>
                        <span v-if="!group.required"> ({{ __('optional') }})</span>
                    </p>

                    <div class="space-y-1.5">
                        <label
                            v-for="option in group.options"
                            :key="option.id"
                            class="flex cursor-pointer items-center justify-between rounded-xl border px-3 py-2.5 transition-colors"
                            :class="isOptionSelected(group, option.id) ? 'border-primary bg-primary/5' : 'border-border'"
                            @click.prevent="toggleOption(group, option.id)"
                        >
                            <div class="flex items-center gap-2">
                                <input
                                    :type="isSingleChoice(group) ? 'radio' : 'checkbox'"
                                    :name="`modifier-${group.id}`"
                                    :value="option.id"
                                    :checked="isOptionSelected(group, option.id)"
                                    tabindex="0"
                                    :class="isSingleChoice(group) ? 'text-primary focus:ring-primary' : 'rounded text-primary focus:ring-primary'"
                                    @click.stop.prevent="toggleOption(group, option.id)"
                                    @keydown.enter.prevent="toggleOption(group, option.id)"
                                    @keydown.space.prevent="toggleOption(group, option.id)"
                                />
                                <span class="text-sm text-ocean-deep">{{ option.name }}</span>
                            </div>
                            <span v-if="option.extra_price > 0" class="text-xs font-medium text-primary">+R$ {{ Number(option.extra_price).toFixed(2) }}</span>
                        </label>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="mb-1 block text-sm font-medium text-ocean-deep">{{ __('Observations') }}</label>
                    <p class="mb-2 text-xs text-muted-foreground">{{ __('Comments about the dish or the order.') }}</p>
                    <textarea
                        v-model="notes"
                        rows="2"
                        maxlength="500"
                        :placeholder="__('e.g. No onions')"
                        class="w-full resize-none rounded-xl border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                    />
                </div>

                <div class="mb-4 flex items-center justify-center gap-3">
                    <span class="text-sm font-semibold text-ocean-deep">{{ __('Quantity:') }}</span>
                    <div class="flex items-center rounded-xl border border-border">
                        <button
                            type="button"
                            class="px-3 py-2 text-lg font-bold text-ocean-deep disabled:opacity-30"
                            :disabled="quantity <= 1"
                            @click="quantity--"
                        >−</button>
                        <span class="min-w-[32px] text-center text-sm font-semibold text-ocean-deep">{{ quantity }}</span>
                        <button
                            type="button"
                            class="px-3 py-2 text-lg font-bold text-ocean-deep"
                            @click="quantity++"
                        >+</button>
                    </div>
                </div>

                <button
                    type="button"
                    class="w-full rounded-xl bg-primary py-3 text-sm font-semibold text-white active:opacity-80"
                    @click="addToCart"
                >
                    {{ isEditing ? __('Update item') : __('Send order') }} · R$ {{ totalPrice.toFixed(2) }}
                </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
