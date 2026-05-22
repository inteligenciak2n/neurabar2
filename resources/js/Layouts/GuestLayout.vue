<script setup>
import { Head } from '@inertiajs/vue3';
import ApplicationMark from '@/Components/ApplicationMark.vue';
import AppToast from '@/Components/AppToast.vue';

defineProps({
    title: String,
});
</script>

<template>
    <div>
        <Head :title="title" />

        <AppToast />

        <div class="min-h-screen bg-muted flex flex-col">
            <!-- Header -->
            <header class="bg-white border-b border-muted shadow-card">
                <div class="mx-auto max-w-lg px-4 py-4 flex items-center gap-3">
                    <ApplicationMark class="h-8 w-auto text-primary" />
                    <span class="font-heading text-lg font-bold text-ocean-deep">NeuraBar</span>
                </div>
            </header>

            <!-- Content -->
            <main class="flex-1 flex flex-col items-center justify-start px-4 py-8">
                <div class="w-full max-w-lg">
                    <slot />
                </div>
            </main>
        </div>
    </div>
</template>
