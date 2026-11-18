<script setup>
import { useTranslate } from '@/Composables/useTranslate';

const __ = useTranslate();

function scrollToCatalog() {
    const target = document.querySelector('[data-catalog-sticky]')
        ?? document.querySelector('[data-menu-category]');

    if (!target) {
        return;
    }

    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
}
</script>

<template>
    <div data-catalog-divider class="mb-4 flex flex-col items-center">
        <button
            type="button"
            class="flex flex-col items-center text-muted-foreground active:opacity-70"
            @click="scrollToCatalog"
        >
            <span class="text-xs leading-none">{{ __('Scroll to see the menu') }}</span>
            <svg
                class="mt-1 h-4 w-4 animate-nudge"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2"
            >
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <div class="mt-3 flex w-full items-center gap-3">
            <span class="h-px flex-1 bg-border" />
            <span class="flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-[0.18em] text-muted-foreground">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 3v6a2 2 0 01-2 2H6v10M6 3v4M10 3v6M16 4c2.5 3.5 4 8 4 12v5M16 4v17" />
                </svg>
                {{ __('Menu') }}
            </span>
            <span class="h-px flex-1 bg-border" />
        </div>
    </div>
</template>
