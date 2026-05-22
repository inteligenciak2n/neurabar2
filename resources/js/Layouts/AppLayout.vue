<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import ApplicationMark from '@/Components/ApplicationMark.vue';
import Banner from '@/Components/Banner.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import AppToast from '@/Components/AppToast.vue';

defineProps({
    title: String,
});

const sidebarOpen = ref(false);

const navItems = [
    { label: 'Dashboard',   icon: 'home',        routeName: 'dashboard' },
    { label: 'Attendances', icon: 'table',        routeName: 'dashboard' },
    { label: 'Order Taker', icon: 'clipboard',    routeName: 'dashboard' },
    { label: 'Kitchen',     icon: 'fire',         routeName: 'dashboard' },
    { label: 'Payment',     icon: 'cash',         routeName: 'dashboard' },
    { label: 'Menu',        icon: 'menu-book',    routeName: 'dashboard' },
    { label: 'Settings',    icon: 'settings',     routeName: 'dashboard' },
];

const iconPaths = {
    home:        'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
    table:       'M3 10h18M3 14h18M10 3v18M6 3h12a1 1 0 011 1v16a1 1 0 01-1 1H6a1 1 0 01-1-1V4a1 1 0 011-1z',
    clipboard:   'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
    fire:        'M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z',
    cash:        'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z',
    'menu-book': 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
    settings:    'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z',
};

const logout = () => {
    router.post(route('logout'));
};
</script>

<template>
    <div>
        <Head :title="title" />

        <Banner />

        <AppToast />

        <div class="flex h-screen bg-muted overflow-hidden">
            <!-- Sidebar overlay (mobile) -->
            <div
                v-if="sidebarOpen"
                class="fixed inset-0 z-20 bg-ocean-deep/40 lg:hidden"
                @click="sidebarOpen = false"
            />

            <!-- Sidebar -->
            <aside
                :class="[
                    'fixed inset-y-0 left-0 z-30 flex w-64 flex-col bg-ocean-deep transition-transform duration-200',
                    'lg:static lg:translate-x-0',
                    sidebarOpen ? 'translate-x-0' : '-translate-x-full',
                ]"
            >
                <!-- Logo -->
                <div class="flex h-16 shrink-0 items-center gap-3 px-6 border-b border-white/10">
                    <Link :href="route('dashboard')" class="flex items-center gap-3">
                        <ApplicationMark class="h-8 w-auto text-white" />
                        <span class="font-heading text-lg font-bold text-white tracking-tight">NeuraBar</span>
                    </Link>
                </div>

                <!-- Venue name badge -->
                <div
                    v-if="$page.props.auth.venue"
                    class="mx-4 mt-4 rounded-md bg-white/10 px-3 py-2"
                >
                    <p class="text-xs font-body text-white/60 uppercase tracking-wider">Active Venue</p>
                    <p class="mt-0.5 text-sm font-heading font-semibold text-white truncate">
                        {{ $page.props.auth.venue.name }}
                    </p>
                </div>

                <!-- Nav -->
                <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
                    <Link
                        v-for="item in navItems"
                        :key="item.label"
                        :href="route(item.routeName)"
                        :class="[
                            'flex items-center gap-3 rounded-md px-3 py-2.5 text-sm font-body font-medium transition-colors',
                            route().current(item.routeName)
                                ? 'bg-white/15 text-white'
                                : 'text-white/70 hover:bg-white/10 hover:text-white',
                        ]"
                    >
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" :d="iconPaths[item.icon]" />
                        </svg>
                        {{ item.label }}
                    </Link>
                </nav>

                <!-- User footer -->
                <div class="shrink-0 border-t border-white/10 p-4">
                    <div class="flex items-center gap-3">
                        <img
                            class="h-8 w-8 rounded-full object-cover ring-2 ring-white/20"
                            :src="$page.props.auth.user.profile_photo_url"
                            :alt="$page.props.auth.user.name"
                        >
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-heading font-semibold text-white truncate">
                                {{ $page.props.auth.user.name }}
                            </p>
                            <p class="text-xs font-body text-white/50 truncate">
                                {{ $page.props.auth.user.role ?? 'staff' }}
                            </p>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- Main content area -->
            <div class="flex flex-1 flex-col overflow-hidden">
                <!-- Topbar -->
                <header class="flex h-16 shrink-0 items-center justify-between gap-4 bg-white border-b border-muted px-4 sm:px-6 shadow-card">
                    <!-- Mobile hamburger -->
                    <button
                        class="rounded-md p-2 text-muted-foreground hover:bg-muted hover:text-ocean-deep transition-colors lg:hidden"
                        @click="sidebarOpen = !sidebarOpen"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>

                    <!-- Page heading -->
                    <div class="flex-1">
                        <slot name="header" />
                    </div>

                    <!-- User dropdown -->
                    <Dropdown align="right" width="48">
                        <template #trigger>
                            <button class="flex items-center gap-2 rounded-md px-2 py-1.5 text-sm font-body text-ocean-deep hover:bg-muted transition-colors">
                                <img
                                    class="h-7 w-7 rounded-full object-cover"
                                    :src="$page.props.auth.user.profile_photo_url"
                                    :alt="$page.props.auth.user.name"
                                >
                                <span class="hidden sm:block">{{ $page.props.auth.user.name }}</span>
                                <svg class="h-4 w-4 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                </svg>
                            </button>
                        </template>

                        <template #content>
                            <div class="block px-4 py-2 text-xs text-muted-foreground">Manage Account</div>
                            <DropdownLink :href="route('profile.show')">Profile</DropdownLink>
                            <DropdownLink v-if="$page.props.jetstream.hasApiFeatures" :href="route('api-tokens.index')">
                                API Tokens
                            </DropdownLink>
                            <div class="border-t border-muted" />
                            <form @submit.prevent="logout">
                                <DropdownLink as="button">Log Out</DropdownLink>
                            </form>
                        </template>
                    </Dropdown>
                </header>

                <!-- Page content -->
                <main class="flex-1 overflow-y-auto p-4 sm:p-6">
                    <slot />
                </main>
            </div>
        </div>
    </div>
</template>

