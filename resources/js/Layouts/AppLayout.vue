<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import ApplicationMark from '@/Components/ApplicationMark.vue';
import Banner from '@/Components/Banner.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import AppToast from '@/Components/AppToast.vue';
import CustomHead from '@/Components/CustomHead.vue';
import { useTranslate } from '@/Composables/useTranslate'
import { useCheckRole } from '@/Composables/useCheckRole';
import { useDark } from '@vueuse/core';


const __ = useTranslate();
const page = usePage();
const { isManager } = useCheckRole();
const isDark = useDark()

defineProps({
    title: String,
});

const mobileMenuOpen = ref(false);
const venueDropdownOpen = ref(false);

const toggleDark = () => {
    isDark.value = !isDark.value
}

const switchVenue = (id) => {
    if (id === page.props.defs.venue?.id) {
        venueDropdownOpen.value = false;
        return;
    }
    router.post(route('venue.select', id), {}, {
        onSuccess: () => { venueDropdownOpen.value = false; },
    });
};

const navItems = [
    { label: __('Dashboard'),   routeName: 'dashboard',         activePattern: 'dashboard', roles: ['owner', 'general_manager', 'section_manager', 'attendant'] },
    { label: __('Attendances'), routeName: 'attendances.index', activePattern: 'attendances.*', roles: ['owner', 'general_manager', 'section_manager', 'attendant'] },
    { label: __('Kitchen'),     routeName: 'kitchen.kds',       activePattern: 'kitchen.*', roles: ['owner', 'general_manager', 'section_manager'] },
    { label: __('Menu'),        routeName: 'menu.index',        activePattern: 'menu.*', roles: ['owner', 'general_manager', 'section_manager',] },
];

const logout = () => {
    router.post(route('logout'));
};

watch(
    () => page.props.venue_switched,
    (switched) => {
        if (switched && window.Echo) {
            window.Echo.connector.pusher.connection.connect();
        }
    },
);

const roleLabel = (role) => {
    const labels = {
        owner: __('Owner'),
        general_manager: __('General Manager'),
        section_manager: __('Section Manager'),
        attendant: __('Attendant'),
        corporation_admin: __('Corporation Admin'),
    };
    return labels[role] ?? role;
};
</script>

<template>
    <div>
        <CustomHead :title="title" />

        <Banner />

        <AppToast />

        <div class="flex min-h-screen flex-col bg-muted dark:bg-gray-950">
            <!-- Top Header -->
            <header class="sticky top-0 z-20 flex h-16 shrink-0 items-center justify-between gap-4 border-b border-border bg-white px-4 shadow-card sm:px-6 dark:border-gray-700 dark:bg-gray-900">
                <!-- Left: Logo + Venue badge -->
                <div class="flex items-center gap-4">
                    <Link :href="route('dashboard')" class="flex items-center gap-2.5">
                        <ApplicationMark class="h-8 w-auto text-primary" />
                        <span class="font-heading text-lg font-bold text-ocean-deep tracking-tight dark:text-gray-100">NeuraBar</span>
                    </Link>

                    <!-- Venue Switcher -->
                    <div v-if="$page.props.defs.venue" class="relative hidden sm:block">
                        <button
                            @click="venueDropdownOpen = !venueDropdownOpen"
                            class="flex items-center gap-1.5 rounded-md border border-border bg-muted px-3 py-1.5 hover:bg-border/60 transition-colors dark:border-gray-700 dark:bg-gray-800 dark:hover:bg-gray-700"
                        >
                            <span class="text-xs font-body text-muted-foreground dark:text-gray-400">{{ __('Venue:') }}</span>
                            <span class="text-xs font-heading font-semibold text-ocean-deep truncate max-w-[140px] dark:text-gray-100">
                                {{ $page.props.defs.venue.name }}
                            </span>
                            <svg class="h-3 w-3 text-muted-foreground shrink-0 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                            </svg>
                        </button>

                        <!-- Overlay -->
                        <div v-if="venueDropdownOpen" class="fixed inset-0 z-40" @click="venueDropdownOpen = false" />

                        <!-- Dropdown panel -->
                        <transition
                            enter-active-class="transition ease-out duration-200"
                            enter-from-class="transform opacity-0 scale-95"
                            enter-to-class="transform opacity-100 scale-100"
                            leave-active-class="transition ease-in duration-75"
                            leave-from-class="transform opacity-100 scale-100"
                            leave-to-class="transform opacity-0 scale-95"
                        >
                            <div
                                v-if="venueDropdownOpen"
                                class="absolute left-0 z-50 mt-2 w-56 rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 dark:bg-gray-800 dark:ring-gray-700"
                            >
                                <div class="py-1">
                                    <div class="px-3 py-1.5 text-xs font-medium text-muted-foreground dark:text-gray-400">{{ __('Switch Venue') }}</div>

                                    <button
                                        v-for="venue in $page.props.defs.venues"
                                        :key="venue.id"
                                        @click="switchVenue(venue.id)"
                                        class="flex flex-col w-full justify-start text-start text-sm transition-colors hover:bg-muted px-3 py-2 dark:hover:bg-gray-700"
                                        :class="venue.id === $page.props.defs.venue.id ? 'font-semibold text-primary' : 'text-ocean-deep dark:text-gray-100'"
                                    >
                                            <div class="flex w-full items-center gap-2 text-sm transition-colors hover:bg-muted dark:hover:bg-gray-700">
                                            <svg
                                                v-if="venue.id === $page.props.defs.venue.id"
                                                class="h-3.5 w-3.5 shrink-0 text-primary"
                                                fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                            >
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                            <span v-else class="h-3.5 w-3.5 shrink-0" />
                                            <span class="truncate">{{ venue.name }}</span>
                                        </div>
                                        <span class="px-3 py-1.5 text-xs font-medium text-muted-foreground dark:text-gray-400">{{ roleLabel(venue.role) }}</span>
                                    </button>

                                    <template v-if="['owner', 'general_manager'].includes($page.props.defs.current_venue_role)">
                                        <div class="my-1 border-t border-border dark:border-gray-700" />
                                        <Link
                                            :href="route('corporation.venues.create')"
                                            class="flex items-center gap-2 px-3 py-2 text-sm text-muted-foreground transition-colors hover:bg-muted hover:text-ocean-deep dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-100"
                                            @click="venueDropdownOpen = false"
                                        >
                                            <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15" />
                                            </svg>
                                            {{ __('New Venue') }}
                                        </Link>
                                    </template>

                                    <Link
                                    :href="route('corporation.dashboard')"
                                    class="flex flex-col w-full justify-start text-start text-sm transition-colors hover:bg-muted px-3 py-2 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-100"
                                    >
                                    {{ __('Management') }}
                                    </Link>
                                </div>
                            </div>
                        </transition>
                    </div>
                </div>

                <!-- Center: Main nav (desktop) -->
                <nav class="hidden items-center gap-1 lg:flex">
                    <template                        
                        v-for="item in navItems"
                        :key="item.label"
                    >
                    <Link
                        v-if="item.roles.includes($page.props.defs.current_venue_role)"
                        :href="route(item.routeName)"
                        :class="[
                            'rounded-md px-3 py-2 text-sm font-body font-medium transition-colors',
                            route().current(item.activePattern)
                                ? 'bg-primary-light text-primary dark:bg-primary/20'
                                : 'text-muted-foreground hover:bg-muted hover:text-ocean-deep dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-100',
                        ]"
                    >
                        {{ item.label }}
                    </Link>
                    </template>
                </nav>

                <!-- Right: User dropdown + mobile toggle -->
                <div class="flex items-center gap-2">
                    <div class="flex w-full py-3">
                        <button @click="toggleDark()" class="flex w-full hover:bg-[#f1f9fc] dark:hover:bg-gray-700 p-3 cursor-pointer justify-center hover:text-[#594cda] transition-all duration-200" v-tippy="isDark ? 'Modo claro' : 'Modo escuro'">
                            {{  isDark ? 'sun' : 'moon' }}
                            <span v-if="menu" class="mx-3">{{ isDark ? 'Modo Claro' : 'Modo Escuro' }}</span>
                        </button>
                    </div>

                    <!-- User dropdown -->
                    <Dropdown align="right" width="48">
                        <template #trigger>
                            <button class="flex items-center gap-2 rounded-md px-2 py-1.5 text-sm font-body text-ocean-deep hover:bg-muted transition-colors dark:text-gray-100 dark:hover:bg-gray-800">
                                <img
                                    class="h-7 w-7 rounded-full object-cover ring-2 ring-border dark:ring-gray-600"
                                    :src="$page.props.auth.user.profile_photo_url"
                                    :alt="$page.props.auth.user.name"
                                >
                                <span class="hidden sm:block font-medium">{{ $page.props.auth.user.name }}</span>
                                <svg class="h-4 w-4 text-muted-foreground dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                </svg>
                            </button>
                        </template>

                        <template #content>
                            <div class="block px-4 py-2 text-xs text-muted-foreground dark:text-gray-400">{{ __('Manage Account') }}</div>
                            <DropdownLink 
                            :href="route('profile.show')">
                                {{ __('Profile') }}
                            </DropdownLink>

                            <DropdownLink 
                            v-if="isManager()"
                            :href="route('settings.index')">
                                {{ __('Settings') }}
                            </DropdownLink>
                            
                            
                            <DropdownLink 
                            v-if="isManager()"
                            :href="route('support.dashboard')">
                                {{ __('Support') }}
                            </DropdownLink>

                            <DropdownLink 
                            v-if="$page.props.jetstream.hasApiFeatures" 
                            :href="route('api-tokens.index')">
                                {{ __('API Tokens') }}
                            </DropdownLink>

                            <div class="border-t border-border dark:border-gray-700" />
                            <form @submit.prevent="logout">
                                <DropdownLink as="button">{{ __('Log Out') }}</DropdownLink>
                            </form>
                        </template>
                    </Dropdown>

                    <!-- Mobile menu toggle -->
                    <button
                        class="rounded-md p-2 text-muted-foreground hover:bg-muted hover:text-ocean-deep transition-colors lg:hidden dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-100"
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
                class="sticky top-16 z-10 border-b border-border bg-white px-4 py-3 shadow-card lg:hidden dark:border-gray-700 dark:bg-gray-900"
            >
                <nav class="flex flex-col gap-1">
                    <template                         
                        v-for="item in navItems"
                        :key="item.label"
                    >
                    <Link
                        v-if="item.roles.includes($page.props.defs.current_venue_role)"
                        :href="route(item.routeName)"
                        :class="[
                            'rounded-md px-3 py-2.5 text-sm font-body font-medium transition-colors',
                            route().current(item.activePattern)
                                ? 'bg-primary-light text-primary dark:bg-primary/20'
                                : 'text-muted-foreground hover:bg-muted hover:text-ocean-deep dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-100',
                        ]"
                        @click="mobileMenuOpen = false"
                    >
                        {{ item.label }}
                    </Link>
                    </template>
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

