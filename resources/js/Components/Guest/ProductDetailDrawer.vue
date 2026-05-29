<script setup>
import { ref, computed } from 'vue';
import { useTranslate } from '@/Composables/useTranslate';

const props = defineProps({
    product: Object,
    modelValue: Boolean,
});

const emit = defineEmits(['update:modelValue', 'add-to-cart']);

const __ = useTranslate();

const selectedVariationId = ref(props.product.variations?.[0]?.id ?? null);
const quantity = ref(1);
const notes = ref('');
const selectedModifiers = ref({});

// initialise modifier selections
props.product.modifier_groups?.forEach((group) => {
    selectedModifiers.value[group.id] = group.min_selections === 1 && group.max_selections === 1
        ? (group.options?.[0]?.id ?? null)
        : [];
});

const selectedVariation = computed(() =>
    props.product.variations?.find((v) => v.id === selectedVariationId.value),
);

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

function findOption(optId) {
    for (const group of props.product.modifier_groups ?? []) {
        const opt = group.options.find((o) => o.id === optId);
        if (opt) return opt;
    }
    return null;
}

function addToCart() {
    const modifiers = [];
    Object.entries(selectedModifiers.value).forEach(([, val]) => {
        if (Array.isArray(val)) {
            val.forEach((id) => modifiers.push(id));
        } else if (val) {
            modifiers.push(val);
        }
    });

    emit('add-to-cart', {
        product_id: props.product.id,
        product_name: props.product.name,
        variation_id: selectedVariationId.value,
        variation_name: selectedVariation.value?.name ?? null,
        quantity: quantity.value,
        notes: notes.value || null,
        modifiers,
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
            class="fixed inset-0 z-50 flex items-end sm:items-center justify-center"
            @click.self="emit('update:modelValue', false)"
        >
            <div class="absolute inset-0 bg-black/50" @click="emit('update:modelValue', false)" />

            <div class="relative w-full max-w-sm bg-white rounded-t-2xl sm:rounded-2xl p-5 shadow-xl max-h-[90vh] overflow-y-auto">
                <!-- Header -->
                <div class="mb-4 flex items-start justify-between gap-2">
                    <div>
                        <h3 class="font-heading text-lg font-bold text-ocean-deep">{{ product.name }}</h3>
                        <p v-if="product.description" class="mt-1 text-xs text-muted-foreground">{{ product.description }}</p>
                    </div>
                    <button
                        class="shrink-0 rounded-full p-1 text-muted-foreground hover:bg-muted"
                        @click="emit('update:modelValue', false)"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Variations -->
                <div v-if="product.variations?.length" class="mb-4">
                    <p class="mb-2 text-sm font-semibold text-ocean-deep">{{ __('Choose an option') }}</p>
                    <div class="space-y-2">
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
                    <p class="mb-1 text-sm font-semibold text-ocean-deep">{{ group.name }}</p>
                    <p class="mb-2 text-xs text-muted-foreground">
                        <template v-if="group.min_selections === group.max_selections">
                            {{ __('Choose') }} {{ group.min_selections }}
                        </template>
                        <template v-else>
                            {{ __('Choose up to') }} {{ group.max_selections }}
                        </template>
                    </p>

                    <!-- Radio (max 1) -->
                    <template v-if="group.max_selections === 1">
                        <div class="space-y-1.5">
                            <label
                                v-for="option in group.options"
                                :key="option.id"
                                class="flex cursor-pointer items-center justify-between rounded-xl border px-3 py-2.5 transition-colors"
                                :class="selectedModifiers[group.id] === option.id ? 'border-primary bg-primary/5' : 'border-border'"
                            >
                                <div class="flex items-center gap-2">
                                    <input
                                        v-model="selectedModifiers[group.id]"
                                        type="radio"
                                        :value="option.id"
                                        class="text-primary focus:ring-primary"
                                    />
                                    <span class="text-sm text-ocean-deep">{{ option.name }}</span>
                                </div>
                                <span v-if="option.extra_price > 0" class="text-xs font-medium text-primary">+R$ {{ Number(option.extra_price).toFixed(2) }}</span>
                            </label>
                        </div>
                    </template>

                    <!-- Checkbox (max > 1) -->
                    <template v-else>
                        <div class="space-y-1.5">
                            <label
                                v-for="option in group.options"
                                :key="option.id"
                                class="flex cursor-pointer items-center justify-between rounded-xl border px-3 py-2.5 transition-colors"
                                :class="selectedModifiers[group.id]?.includes(option.id) ? 'border-primary bg-primary/5' : 'border-border'"
                            >
                                <div class="flex items-center gap-2">
                                    <input
                                        v-model="selectedModifiers[group.id]"
                                        type="checkbox"
                                        :value="option.id"
                                        class="rounded text-primary focus:ring-primary"
                                    />
                                    <span class="text-sm text-ocean-deep">{{ option.name }}</span>
                                </div>
                                <span v-if="option.extra_price > 0" class="text-xs font-medium text-primary">+R$ {{ Number(option.extra_price).toFixed(2) }}</span>
                            </label>
                        </div>
                    </template>
                </div>

                <!-- Notes -->
                <div class="mb-4">
                    <label class="mb-1 block text-sm font-medium text-ocean-deep">{{ __('Notes') }} <span class="text-xs text-muted-foreground">({{ __('optional') }})</span></label>
                    <textarea
                        v-model="notes"
                        rows="2"
                        maxlength="500"
                        :placeholder="__('e.g. No onions')"
                        class="w-full resize-none rounded-xl border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                    />
                </div>

                <!-- Quantity + Add button -->
                <div class="flex items-center gap-3">
                    <div class="flex items-center rounded-xl border border-border">
                        <button
                            class="px-3 py-2 text-lg font-bold text-ocean-deep disabled:opacity-30"
                            :disabled="quantity <= 1"
                            @click="quantity--"
                        >−</button>
                        <span class="min-w-[32px] text-center text-sm font-semibold text-ocean-deep">{{ quantity }}</span>
                        <button class="px-3 py-2 text-lg font-bold text-ocean-deep" @click="quantity++">+</button>
                    </div>
                    <button
                        class="flex-1 rounded-xl bg-primary py-3 text-sm font-semibold text-white active:opacity-80"
                        @click="addToCart"
                    >
                        {{ __('Add') }} · R$ {{ totalPrice.toFixed(2) }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
