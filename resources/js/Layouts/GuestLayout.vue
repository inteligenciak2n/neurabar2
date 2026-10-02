<script setup>
import ApplicationMark from '@/Components/ApplicationMark.vue';
import AppToast from '@/Components/AppToast.vue';
import CustomHead from '@/Components/CustomHead.vue';
import { ref, watch } from 'vue';

const props = defineProps({
    title: String,
    venue: Object,
    previewWatermark: {
        type: String,
        default: null,
    },
});

const logoFailed = ref(false);

watch(() => props.venue?.logo_url, () => {
    logoFailed.value = false;
});
</script>

<template>
    <div>
        <CustomHead :title="title" />

        <AppToast />

        <div class="relative min-h-screen bg-muted flex flex-col">
            <div
                v-if="previewWatermark"
                class="pointer-events-none absolute inset-0 z-0 overflow-hidden"
                aria-hidden="true"
            >
                <div class="absolute -left-1/3 top-0 flex h-[160%] w-[160%] flex-wrap content-evenly justify-evenly gap-x-12 gap-y-16 rotate-[-22deg]">
                    <span
                        v-for="index in 18"
                        :key="index"
                        class="select-none whitespace-nowrap font-heading text-5xl font-bold tracking-wide text-ocean-deep/[0.12]"
                    >{{ previewWatermark }}</span>
                </div>
            </div>

            <!-- Header -->
            <header class="relative z-10 bg-white border-b border-border shadow-card">
                <div
                    class="mx-auto flex max-w-lg items-center gap-3 px-4"
                    :class="$slots.headerTitle ? 'py-2' : 'py-4'"
                >
                    <template v-if="!$slots.headerTitle">
                        <img
                            v-if="venue?.logo_url && !logoFailed"
                            :src="venue.logo_url"
                            :alt="venue.name"
                            class="h-8 w-auto"
                            @error="logoFailed = true"
                        />
                        <ApplicationMark v-else-if="!venue" class="h-8 w-auto text-primary" />
                        <span class="min-w-0 font-heading text-lg font-bold leading-tight text-ocean-deep">{{ venue?.name ?? 'NeuraBar' }}</span>
                    </template>
                    <div v-else class="min-w-0">
                        <p class="font-heading text-sm font-bold leading-tight text-ocean-deep">
                            <slot name="headerTitle" />
                        </p>
                        <p
                            v-if="$slots.headerSubtitle"
                            class="mt-0.5 text-[10px] font-normal leading-tight text-gray-400 dark:text-gray-500"
                        >
                            <slot name="headerSubtitle" />
                        </p>
                    </div>
                    <div v-if="$slots.header" class="ml-auto flex shrink-0 items-center gap-2">
                        <slot name="header" />
                    </div>
                </div>
            </header>

            <!-- Content -->
            <main
                class="flex flex-1 flex-col items-center justify-start px-4"
                :class="$slots.headerTitle ? 'pt-3 pb-8' : 'py-8'"
            >
                <div class="relative z-10 w-full max-w-lg">
                    <div
                        v-if="$slots.frameLabel"
                        class="mb-2 flex justify-center"
                    >
                        <slot name="frameLabel" />
                    </div>
                    <div
                        :class="previewWatermark ? 'z-10 rounded-2xl border-2 border-white/40 bg-muted p-3' : ''"
                    >
                        <slot />
                    </div>
                </div>
            </main>

            <!-- Footer -->
            <footer class="relative z-10 bg-white border-t border-border shadow-card flex justify-center">
                <div class="mx-auto max-w-lg px-4 py-4 text-center text-sm text-muted-foreground">
                    <span>
                        &copy; {{ new Date().getFullYear() }} {{ venue?.name ?? 'NeuraBar' }}. {{ __('All rights reserved') }}
                    </span>
                    <span class="mx-2">|</span>
                    <span>
                        powered by <a href="https://neurabar.com" target="_blank" class="text-primary hover:underline">NeuraBar</a>
                    </span>
                </div>
            </footer>
        </div>
    </div>
</template>
