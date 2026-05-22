<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppToast from '@/Components/AppToast.vue';

defineProps({
    title: String,
});

const logout = () => {
    router.post(route('logout'));
};

const platformNavItems = [
    { label: 'Dashboard',    routeName: 'platform.dashboard',          activePattern: 'platform.dashboard' },
    { label: 'Corporations', routeName: 'platform.corporations.index',  activePattern: 'platform.corporations.*' },
    { label: 'Plans',        routeName: 'platform.plans.index',         activePattern: 'platform.plans.*' },
    { label: 'Users',        routeName: 'platform.users.index',         activePattern: 'platform.users.*' },
];
</script>

<template>
    <div>
        <Head :title="title" />

        <AppToast />

        <div class="flex h-screen bg-muted overflow-hidden">
            <!-- Sidebar -->
            <aside class="flex w-60 flex-col bg-white border-r border-border shadow-card">
                <!-- Logo -->
                <div class="flex h-14 shrink-0 items-center gap-2 px-5 border-b border-border">
                    <span class="font-heading text-base font-bold text-ocean-deep tracking-tight">NeuraBar</span>
                    <span class="rounded-full bg-warm-gold/20 px-2 py-0.5 text-xs font-medium text-warm-gold">Admin</span>
                </div>

                <!-- Nav -->
                <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-0.5">
                    <Link
                        v-for="item in platformNavItems"
                        :key="item.label"
                        :href="route(item.routeName)"
                        :class="[
                            'flex items-center rounded-md px-3 py-2 text-sm font-body font-medium transition-colors',
                            route().current(item.activePattern)
                                ? 'bg-primary-light text-primary'
                                : 'text-muted-foreground hover:bg-muted hover:text-ocean-deep',
                        ]"
                    >
                        {{ item.label }}
                    </Link>
                </nav>

                <!-- User footer -->
                <div class="shrink-0 border-t border-border p-4">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-body text-muted-foreground truncate">
                            {{ $page.props.auth.user?.name ?? 'Platform User' }}
                        </span>
                        <button
                            class="rounded px-2 py-1 text-xs font-body text-muted-foreground hover:text-ocean-deep hover:bg-muted transition-colors"
                            @click="logout"
                        >
                            Logout
                        </button>
                    </div>
                </div>
            </aside>

            <!-- Main -->
            <div class="flex flex-1 flex-col overflow-hidden">
                <header class="flex h-14 shrink-0 items-center justify-between px-6 bg-white border-b border-border shadow-card">
                    <slot name="header" />
                </header>
                <main class="flex-1 overflow-y-auto p-6 text-foreground">
                    <slot />
                </main>
            </div>
        </div>
    </div>
</template>
