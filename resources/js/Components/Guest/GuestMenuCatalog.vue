<script setup>
import AppEmptyState from '@/Components/AppEmptyState.vue';
import CategoryChipRow from '@/Components/Guest/CategoryChipRow.vue';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { useTranslate } from '@/Composables/useTranslate';

const props = defineProps({
    venue: Object,
    categories: {
        type: Array,
        default: () => [],
    },
    preview: {
        type: Boolean,
        default: false,
    },
    emptyDescription: {
        type: String,
        required: true,
    },
    stickyPanelStyle: {
        type: Object,
        default: null,
    },
    showCategoryChips: {
        type: Boolean,
        default: true,
    },
    stickyTop: {
        type: String,
        default: null,
    },
    catalogEnabled: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['open-product']);

const __ = useTranslate();

const hasCatalogFrame = computed(() => Boolean(props.stickyPanelStyle));

const stickyTopPx = computed(() => {
    const parsed = Number.parseFloat(props.stickyTop ?? '');

    return Number.isFinite(parsed) ? parsed : 0;
});

const selectedCategoryId = ref(props.categories?.[0]?.id ?? null);
const logoFailed = ref(false);
const stickyBar = ref(null);
const catalogRoot = ref(null);
const stickyHeight = ref(180);
let resizeObserver = null;
let sectionObserver = null;
let programmaticScrollUntil = 0;

function categoryAnchorId(categoryId) {
    return `menu-category-${categoryId}`;
}

function servingsText(product) {
    const count = Number(product?.servings) || 0;

    if (count < 1) {
        return '';
    }

    if (count === 1) {
        return __('Serves 1 person');
    }

    return __('Serves :count people', { count });
}

function scrollToCategory(categoryId) {
    selectedCategoryId.value = categoryId;
    programmaticScrollUntil = Date.now() + 900;

    const target = document.getElementById(categoryAnchorId(categoryId));
    if (!target) {
        return;
    }

    const top = window.scrollY + target.getBoundingClientRect().top - stickyHeight.value - stickyTopPx.value - 8;
    window.scrollTo({ top: Math.max(0, top), behavior: 'smooth' });
}

onMounted(() => {
    const updateStickyHeight = () => {
        if (stickyBar.value) {
            stickyHeight.value = stickyBar.value.offsetHeight;
        }
    };

    updateStickyHeight();

    if (typeof ResizeObserver !== 'undefined' && stickyBar.value) {
        resizeObserver = new ResizeObserver(updateStickyHeight);
        resizeObserver.observe(stickyBar.value);
    }

    if (typeof IntersectionObserver === 'undefined' || !catalogRoot.value) {
        return;
    }

    sectionObserver = new IntersectionObserver(
        (entries) => {
            const visible = entries
                .filter((entry) => entry.isIntersecting)
                .sort((a, b) => a.boundingClientRect.top - b.boundingClientRect.top);

            const topmost = visible[0]?.target?.dataset?.categoryId;
            if (topmost && Date.now() >= programmaticScrollUntil) {
                selectedCategoryId.value = topmost;
            }
        },
        {
            root: null,
            rootMargin: '-30% 0px -55% 0px',
            threshold: 0,
        },
    );

    catalogRoot.value.querySelectorAll('[data-menu-category]').forEach((section) => {
        sectionObserver.observe(section);
    });
});

onUnmounted(() => {
    resizeObserver?.disconnect();
    sectionObserver?.disconnect();
});
</script>

<template>
    <div ref="catalogRoot">
        <div
            data-venue-brand
            :class="preview ? '-mx-3 px-3 pt-0' : ''"
        >
            <div v-if="preview" class="mb-4 grid grid-cols-[minmax(0,1fr)_7rem] items-start gap-3">
                <div class="min-w-0">
                    <h2 class="font-heading text-xl font-bold leading-tight text-ocean-deep">{{ venue.name }}</h2>
                    <p v-if="venue.description" class="mt-1 text-sm leading-snug text-muted-foreground">{{ venue.description }}</p>
                </div>
                <div class="aspect-square overflow-hidden rounded-lg bg-muted shadow-card">
                    <img
                        v-if="venue?.logo_url && !logoFailed"
                        :src="venue.logo_url"
                        :alt="venue.name"
                        class="h-full w-full object-cover"
                        @error="logoFailed = true"
                    />
                </div>
            </div>
            <p
                v-else-if="venue?.description"
                class="mb-4 text-sm leading-snug text-muted-foreground"
            >{{ venue.description }}</p>

            <AppEmptyState
                v-if="catalogEnabled && !categories.length"
                :title="__('Menu not available')"
                :description="emptyDescription"
            />
        </div>

        <div
            v-if="$slots['above-categories']"
            data-above-categories
            :class="preview ? '-mx-3 px-3' : ''"
        >
            <slot name="above-categories" />
        </div>

        <div
            v-if="categories.length || $slots['panel-fields']"
            ref="stickyBar"
            data-catalog-sticky
            class="sticky top-0 z-20 bg-muted [overflow-anchor:none]"
            :class="[preview ? '-mx-3 px-3' : '', hasCatalogFrame ? 'pb-0' : 'pb-2']"
            :style="stickyTop ? { top: stickyTop } : undefined"
        >
            <div
                v-if="showCategoryChips || $slots['panel-fields']"
                data-channel-panel
                role="tabpanel"
                :class="hasCatalogFrame ? 'border-x-2 border-t-2 px-2 pt-2 pb-2' : ''"
                :style="stickyPanelStyle || undefined"
            >
            <div v-if="$slots['panel-fields']" data-panel-fields :class="showCategoryChips ? 'mb-3' : ''">
                <slot name="panel-fields" />
            </div>
            <CategoryChipRow v-if="showCategoryChips" :active-id="selectedCategoryId">
                <button
                    v-for="category in categories"
                    :key="category.id"
                    type="button"
                    data-category-chip
                    :data-category-id="category.id"
                    class="whitespace-nowrap rounded-md px-4 py-2 text-sm font-medium transition-colors"
                    :class="String(selectedCategoryId) === String(category.id) ? 'bg-primary text-white' : 'bg-white text-ocean-deep shadow-card hover:bg-ocean-light'"
                    :style="String(selectedCategoryId) === String(category.id) ? {} : (category.color ? { color: category.color } : {})"
                    @click="scrollToCategory(category.id)"
                >
                    {{ category.name }}
                </button>
            </CategoryChipRow>
            </div>
        </div>

        <div
            v-if="hasCatalogFrame || $slots['after-catalog'] || (showCategoryChips && categories.length)"
            data-channel-catalog
            :class="hasCatalogFrame ? 'border-x-2 border-b-2 rounded-b-xl px-2 pt-2' : ''"
            :style="hasCatalogFrame ? stickyPanelStyle : undefined"
        >
        <slot name="after-catalog" />

        <template v-if="showCategoryChips && categories.length">
            <section
                v-for="category in categories"
                :id="categoryAnchorId(category.id)"
                :key="category.id"
                data-menu-category
                :data-category-id="category.id"
                class="mb-6"
                :style="{ scrollMarginTop: `${stickyHeight + stickyTopPx + 8}px` }"
            >
                <h3
                    class="mb-2 font-heading text-sm font-bold"
                    :class="category.color ? '' : 'text-ocean-deep'"
                    :style="category.color ? { color: category.color } : {}"
                >{{ category.name }}</h3>
                <AppEmptyState
                    v-if="!category.products?.length"
                    :title="__('No items in this category')"
                    :description="__('Check back later.')"
                />
                <div v-else class="grid grid-cols-1 gap-2">
                    <button
                        v-for="product in category.products"
                        :key="product.id"
                        type="button"
                        class="flex min-h-[4.5rem] items-stretch overflow-hidden rounded-xl bg-white text-left shadow-card transition-transform active:scale-95"
                        :class="product.image_url ? '' : 'px-3 py-2'"
                        @click="emit('open-product', product)"
                    >
                        <div
                            v-if="product.image_url"
                            class="h-[4.5rem] w-[4.5rem] shrink-0 self-stretch overflow-hidden bg-muted"
                        >
                            <img :src="product.image_url" :alt="product.name" class="h-full w-full object-cover" />
                        </div>
                        <div
                            class="flex min-h-0 min-w-0 flex-1 flex-col"
                            :class="product.image_url ? 'px-3 py-2' : ''"
                        >
                            <div class="flex min-w-0 items-start gap-2">
                                <div class="min-w-0 flex-1">
                                    <p class="font-body text-sm font-bold leading-snug text-ocean-deep">{{ product.name }}</p>
                                    <p v-if="product.description" class="mt-0.5 text-xs font-normal leading-snug text-muted-foreground line-clamp-1">{{ product.description }}</p>
                                </div>
                                <div
                                    v-if="product.variations?.length"
                                    class="min-w-0 max-w-[48%] shrink-0"
                                >
                                    <p
                                        v-for="variation in product.variations"
                                        :key="variation.id"
                                        class="flex items-baseline justify-end gap-1 text-[10px] leading-tight text-muted-foreground"
                                    >
                                        <span class="min-w-0 truncate">{{ variation.name }}</span>
                                        <span class="shrink-0">
                                            <span class="text-[0.7em] font-normal opacity-60">R$</span> {{ Number(variation.price).toFixed(2) }}
                                        </span>
                                    </p>
                                </div>
                            </div>
                            <p class="mt-auto flex items-baseline justify-between gap-2 text-xs font-normal leading-snug text-muted-foreground">
                                <span v-if="servingsText(product)" class="min-w-0 truncate text-[10px] leading-tight">{{ servingsText(product) }}</span>
                                <span v-else />
                                <span class="shrink-0">
                                    {{ __('Price:') }} <span class="text-[0.7em] font-normal opacity-60">R$</span> {{ Number(product.price).toFixed(2) }}
                                </span>
                            </p>
                        </div>
                    </button>
                </div>
            </section>
            <div class="pb-24" />
        </template>
        </div>
    </div>
</template>
