<script setup>
import ApplicationMark from '@/Components/ApplicationMark.vue';
import AppToast from '@/Components/AppToast.vue';
import CustomHead from '@/Components/CustomHead.vue';

defineProps({
    title: String,
    venue: Object,
});
</script>

<template>
    <div>
        <CustomHead :title="title" />

        <AppToast />

        <div class="min-h-screen bg-muted flex flex-col">
            <!-- Header -->
            <header class="bg-white border-b border-border shadow-card">
                <div class="mx-auto max-w-lg px-4 py-4 flex items-center gap-3">
                    <div v-if="venue?.logo_url">
                        <img :src="venue.logo_url" :alt="venue.name" class="h-8 w-auto" />
                    </div>
                    <ApplicationMark v-else class="h-8 w-auto text-primary" />
                    <span class="font-heading text-lg font-bold text-ocean-deep">{{ venue.name }}</span>
                </div>
            </header>

            <!-- Content -->
            <main class="flex-1 flex flex-col items-center justify-start px-4 py-8">
                <div class="w-full max-w-lg">
                    <slot />
                </div>
            </main>

            <!-- Footer -->
            <footer class="bg-white border-t border-border shadow-card flex justify-center">
                <div class="mx-auto max-w-lg px-4 py-4 text-center text-sm text-muted-foreground">
                    <span>
                        &copy; {{ new Date().getFullYear() }} {{ venue.name }}. {{ __('All rights reserved') }}
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
