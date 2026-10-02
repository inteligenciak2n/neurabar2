<script setup>
import { nextTick, onMounted, onUnmounted, ref, watch } from 'vue';

const props = defineProps({
    activeId: {
        type: [String, Number],
        default: null,
    },
});

const scroller = ref(null);
const inner = ref(null);
const canScrollLeft = ref(false);
const canScrollRight = ref(false);
const dragging = ref(false);
let resizeObserver = null;
let dragStartX = 0;
let dragStartScroll = 0;
let dragMoved = false;
let scrollChipTimer = null;

const updateOverflow = () => {
    const el = scroller.value;
    if (!el) {
        canScrollLeft.value = false;
        canScrollRight.value = false;

        return;
    }

    const maxScroll = el.scrollWidth - el.clientWidth;
    canScrollLeft.value = maxScroll > 2 && el.scrollLeft > 2;
    canScrollRight.value = maxScroll > 2 && el.scrollLeft < maxScroll - 2;
};

const scrollActiveChipIntoView = () => {
    const el = scroller.value;
    if (!el || dragging.value || props.activeId == null) {
        return;
    }

    const escaped = typeof CSS !== 'undefined' && typeof CSS.escape === 'function'
        ? CSS.escape(String(props.activeId))
        : String(props.activeId);
    const chip = el.querySelector(`[data-category-chip][data-category-id="${escaped}"]`);
    if (!chip) {
        return;
    }

    const scrollerRect = el.getBoundingClientRect();
    const chipRect = chip.getBoundingClientRect();
    const chipCenter = chipRect.left - scrollerRect.left + el.scrollLeft + (chipRect.width / 2);
    const maxScroll = Math.max(0, el.scrollWidth - el.clientWidth);
    const nextLeft = Math.max(0, Math.min(maxScroll, chipCenter - (el.clientWidth / 2)));

    el.scrollTo({ left: nextLeft, behavior: 'smooth' });
};

const scheduleScrollActiveChip = () => {
    if (dragging.value) {
        return;
    }

    clearTimeout(scrollChipTimer);
    scrollChipTimer = setTimeout(() => {
        nextTick(() => {
            scrollActiveChipIntoView();
            updateOverflow();
        });
    }, 60);
};

watch(() => props.activeId, scheduleScrollActiveChip);

const onPointerDown = (event) => {
    const el = scroller.value;
    if (!el || el.scrollWidth <= el.clientWidth + 2) {
        return;
    }

    dragging.value = true;
    dragMoved = false;
    dragStartX = event.clientX;
    dragStartScroll = el.scrollLeft;
    el.setPointerCapture?.(event.pointerId);
};

const onPointerMove = (event) => {
    if (!dragging.value || !scroller.value) {
        return;
    }

    const delta = event.clientX - dragStartX;
    if (Math.abs(delta) > 4) {
        dragMoved = true;
    }

    scroller.value.scrollLeft = dragStartScroll - delta;
};

const onPointerUp = (event) => {
    if (!dragging.value) {
        return;
    }

    dragging.value = false;
    scroller.value?.releasePointerCapture?.(event.pointerId);
};

const onChipClickCapture = (event) => {
    if (!dragMoved) {
        return;
    }

    event.preventDefault();
    event.stopPropagation();
    dragMoved = false;
};

const onWheel = (event) => {
    const el = scroller.value;
    if (!el || el.scrollWidth <= el.clientWidth + 2) {
        return;
    }

    if (Math.abs(event.deltaY) < Math.abs(event.deltaX)) {
        return;
    }

    event.preventDefault();
    el.scrollLeft += event.deltaY;
};

onMounted(() => {
    nextTick(() => {
        updateOverflow();
        scroller.value?.addEventListener('wheel', onWheel, { passive: false });
        if (typeof ResizeObserver === 'undefined') {
            return;
        }

        resizeObserver = new ResizeObserver(updateOverflow);
        if (scroller.value) {
            resizeObserver.observe(scroller.value);
        }
        if (inner.value) {
            resizeObserver.observe(inner.value);
        }
    });
});

onUnmounted(() => {
    clearTimeout(scrollChipTimer);
    scroller.value?.removeEventListener('wheel', onWheel);
    resizeObserver?.disconnect();
});
</script>

<template>
    <div class="relative mb-4 border-b border-muted">
        <div
            ref="scroller"
            class="overflow-x-auto overscroll-x-contain touch-pan-x pb-1 [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden"
            :class="canScrollLeft || canScrollRight ? (dragging ? 'cursor-grabbing select-none' : 'cursor-grab') : ''"
            @scroll="updateOverflow"
            @pointerdown="onPointerDown"
            @pointermove="onPointerMove"
            @pointerup="onPointerUp"
            @pointercancel="onPointerUp"
        >
            <div ref="inner" class="flex w-max gap-2" @click.capture="onChipClickCapture">
                <slot />
            </div>
        </div>
        <div
            v-show="canScrollLeft"
            class="pointer-events-none absolute inset-y-0 left-0 w-12 bg-gradient-to-r from-muted to-transparent"
            aria-hidden="true"
        />
        <div
            v-show="canScrollRight"
            class="pointer-events-none absolute inset-y-0 right-0 w-12 bg-gradient-to-l from-muted to-transparent"
            aria-hidden="true"
        />
    </div>
</template>
