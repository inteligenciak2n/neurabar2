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

const mobileMenuOpen = ref(false);

const navItems = [
    { label: 'Dashboard',   routeName: 'dashboard',         activePattern: 'dashboard' },
    { label: 'Attendances', routeName: 'attendances.index', activePattern: 'attendances.*' },
    { label: 'Order Taker', routeName: 'orders.index',      activePattern: 'orders.*' },
    { label: 'Kitchen',     routeName: 'kitchen.index',     activePattern: 'kitchen.*' },
    { label: 'Payment',     routeName: 'payment.index',     activePattern: 'payment.*' },
    { label: 'Menu',        routeName: 'menu.index',        activePattern: 'menu.*' },
    { label: 'Settings',    routeName: 'settings.index',    activePattern: 'settings.*' },
];

const logout = () => {
    router.post(route('logout'));
};
</script>

<template>
    <div>
        <Head :title="title" />

        <Banner />

        <AppToast />

        <div class="flex min-h-screen flex-col bg-muted">
            <!-- Top Header -->
            <header class="sticky top-0 z-20 flex h-16 shrink-0 items-center justify-between gap-4 border-b border-border bg-white px-4 shadow-card sm:px-6">
                <!-- Left: Logo + Venue badge -->
                <div class="flex items-center gap-4">
                    <Link :href="route('dashboard')" class="flex items-center gap-2.5">
                        <ApplicationMark class="h-8 w-auto text-primary" />
                        <span class="font-heading text-lg font-bold text-ocean-deep tracking-tight">NeuraBar</span>
                    </Link>

                    <div
                        v-if="$page.props.auth.venue"
                        class="hidden items-center gap-1.5 rounded-md border border-border bg-muted px-3 py-1.5 sm:flex"
                    >
                        <span class="text-xs font-body text-muted-foreground">Venue:</span>
                        <span class="text-xs font-heading font-semibold text-ocean-deep truncate max-w-[140px]">
                            {{ $page.props.auth.venue.name }}
                        </span>
                    </div>
                </div>

                <!-- Center: Main nav (desktop) -->
                <nav class="hidden items-center gap-1 lg:flex">
                    <Link
                        v-for="item in navItems"
                        :key="item.label"
                        :href="route(item.routeName)"
                        :class="[
                            'rounded-md px-3 py-2 text-sm font-body font-medium transition-colors',
                            route().current(item.activePattern)
                                ? 'bg-primary-light text-primary'
                                : 'text-muted-foreground hover:bg-muted hover:text-ocean-deep',
                        ]"
                    >
                        {{ item.label }}
                    </Link>
                </nav>

                <!-- Right: User dropdown + mobile toggle -->
                <div class="flex items-center gap-2">
                    <!-- User dropdown -->
                    <Dropdown align="right" width="48">
                        <template #trigger>
                            <button class="flex items-center gap-2 rounded-md px-2 py-1.5 text-sm font-body text-ocean-deep hover:bg-muted transition-colors">
                                <img
                                    class="h-7 w-7 rounded-full object-cover ring-2 ring-border"
                                    :src="$page.props.auth.user.profile_photo_url"
                                    :alt="$page.props.auth.user.name"
                                >
                                <span class="hidden sm:block font-medium">{{ $page.props.auth.user.name }}</span>
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
                            <div class="border-t border-border" />
                            <form @submit.prevent="logout">
                                <DropdownLink as="button">Log Out</DropdownLink>
                            </form>
                        </template>
                    </Dropdown>

                    <!-- Mobile menu toggle -->
                    <button
                        class="rounded-md p-2 text-muted-foreground hover:bg-muted hover:text-ocean-deep transition-colors lg:hidden"
                        @click="mobileMenuOpen = !mobileMenuOpen"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path v-if="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </header>

            <!-- Mobile nav drawer -->
            <div
                v-if="mobileMenuOpen"
                class="sticky top-16 z-10 border-b border-border bg-white px-4 py-3 shadow-card lg:hidden"
            >
                <nav class="flex flex-col gap-1">
                    <Link
                        v-for="item in navItems"
                        :key="item.label"
                        :href="route(item.routeName)"
                        :class="[
                            'rounded-md px-3 py-2.5 text-sm font-body font-medium transition-colors',
                            route().current(item.activePattern)
                                ? 'bg-primary-light text-primary'
                                : 'text-muted-foreground hover:bg-muted hover:text-ocean-deep',
                        ]"
                        @click="mobileMenuOpen = false"
                    >
                        {{ item.label }}
                    </Link>
                </nav>
            </div>

            <!-- Page content -->
            <main class="flex-1 p-4 sm:p-6">
                <div v-if="$slots.header" class="mb-6">
                    <slot name="header" />
                </div>
                <slot />
            </main>
        </div>
    </div>
</template>

