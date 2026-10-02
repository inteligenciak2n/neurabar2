<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import ApplicationMark from '@/Components/ApplicationMark.vue';
import Banner from '@/Components/Banner.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import AppToast from '@/Components/AppToast.vue';
import CustomHead from '@/Components/CustomHead.vue';
import { useTranslate } from '@/Composables/useTranslate'
import { useCheckRole } from '@/Composables/useCheckRole';
import { useModules } from '@/Composables/useModules';
import { useDirectWaiterNotifications } from '@/Composables/useDirectWaiterNotifications';
import ToggleDark from '@/Components/ToggleDark.vue';
import { ensureTranslations } from '@/Translations/translationStore';

defineOptions({ name: 'AppLayout' });

const translate = useTranslate();
const __ = (text, bindings = {}) => translate(text, bindings, 'AppLayout');
const page = usePage();
const { isManager } = useCheckRole();
const { hasModule } = useModules();
const { unreadCount: directWaiterUnreadCount, subscribe: subscribeDirectWaiterNotifications } = useDirectWaiterNotifications();

defineProps({
    title: String,
});

const mobileMenuOpen = ref(false);
const venueDropdownOpen = ref(false);


const switchVenue = (id) => {
    if (id === page.props.defs.venue?.id) {
        venueDropdownOpen.value = false;
        return;
    }
    router.post(route('venue.select', id), {}, {
        onSuccess: () => { venueDropdownOpen.value = false; },
    });
};

const managerRoles = ['owner', 'general_manager'];
const operationalRoles = ['owner', 'general_manager', 'section_manager', 'attendant'];

const coreNavItems = [
    { label: __('Dashboard'),   routeName: 'dashboard',         activePattern: 'dashboard', module: 'menu', roles: [...operationalRoles, 'attendant'] },
    { label: __('Attendances'), routeName: 'attendances.index', activePattern: 'attendances.*', module: 'menu', roles: [...operationalRoles, 'attendant'] },
    { label: __('Kitchen'),     routeName: 'kitchen.kds',       activePattern: 'kitchen.*', module: 'kds', roles: operationalRoles },
    { label: __('Menu'),        routeName: 'menu.index',        activePattern: 'menu.*', module: 'menu', roles: managerRoles },
];

// Módulos contratados ganham entrada própria no menu — sem isso as telas só
// eram alcançáveis digitando a URL.
const moduleNavItems = [
    { label: __('Delivery'),      routeName: 'delivery.index',      activePattern: 'delivery.*',      module: 'delivery',             roles: operationalRoles },
    { label: __('Production'),    routeName: 'production.index',    activePattern: 'production.*',    module: 'production_dashboard', roles: managerRoles },
    { label: __('Finance'),       routeName: 'finance.index',       activePattern: 'finance.*',       module: 'financial_dashboard',  roles: managerRoles },
    { label: __('Fiscal Note'),   routeName: 'fiscal-note.index',   activePattern: 'fiscal-note.*',   module: 'fiscal_note',          roles: managerRoles },
    { label: __('Voice Command'), routeName: 'voice-command.index', activePattern: 'voice-command.*', module: 'voice_command',        roles: operationalRoles },
    { label: __('Direct Waiter'), routeName: 'direct-waiter.index', activePattern: 'direct-waiter.*', module: 'direct_waiter',        roles: operationalRoles },
    { label: __('Direct Print'),  routeName: 'direct-print.index',  activePattern: 'direct-print.*',  module: 'direct_print',         roles: managerRoles },
];

const isVisible = (item) => item.roles.includes(page.props.defs.current_venue_role)
    && (! item.module || hasModule(item.module));

const navItems = computed(() => coreNavItems.filter(isVisible));
const moduleLinks = computed(() => moduleNavItems.filter(isVisible));

const showDirectWaiterBell = computed(() => isVisible({ module: 'direct_waiter', roles: operationalRoles }));

const logout = () => {
    router.post(route('logout'));
};

onMounted(() => {
    ensureTranslations(['AppLayout']).catch(() => {});

    subscribeDirectWaiterNotifications();
});

watch(
    () => page.props.venue_switched,
    (switched) => {
        if (switched && window.Echo) {
            window.Echo.connector.pusher.connection.connect();
            subscribeDirectWaiterNotifications();
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
                    <Link :href="route('dashboard')" class="flex items-center gap-1">
                        <ApplicationMark class="h-8 w-auto text-primary" />
                        <span class="font-heading text-lg font-bold text-ocean-deep tracking-tight dark:text-gray-100">NeuraBar</span>
                    </Link>

                    <!-- Venue Switcher -->
                    <div v-if="$page.props.defs.venue" class="relative hidden sm:block">
                        <button
                            @click="venueDropdownOpen = !venueDropdownOpen"
                            class="flex items-center gap-2 rounded-md border border-border bg-muted px-3 py-1 hover:bg-border/60 transition-colors dark:border-gray-700 dark:bg-gray-800 dark:hover:bg-gray-700"
                        >
                            <span class="flex min-w-0 flex-col items-start leading-tight">
                                <span class="max-w-[180px] truncate font-heading text-sm font-bold text-ocean-deep dark:text-gray-100">
                                    {{ $page.props.defs.venue.name }}
                                </span>
                                <span class="max-w-[180px] truncate font-body text-xs text-muted-foreground dark:text-gray-400">
                                    {{ $page.props.auth.user.name }}
                                </span>
                            </span>
                            <svg class="h-3 w-3 shrink-0 text-muted-foreground dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                    <Link
                        v-for="item in navItems"
                        :key="item.routeName"
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

                    <Dropdown v-if="moduleLinks.length" align="left" width="48">
                        <template #trigger>
                            <button class="flex items-center gap-1 rounded-md px-3 py-2 text-sm font-body font-medium text-muted-foreground transition-colors hover:bg-muted hover:text-ocean-deep dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-100">
                                {{ __('Modules') }}
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                </svg>
                            </button>
                        </template>
                        <template #content>
                            <DropdownLink
                                v-for="item in moduleLinks"
                                :key="item.routeName"
                                :href="route(item.routeName)"
                            >
                                {{ item.label }}
                            </DropdownLink>
                        </template>
                    </Dropdown>
                </nav>

                <!-- Right: User dropdown + mobile toggle -->
                <div class="flex items-center gap-2">

                    <!-- DirectWaiter notification bell -->
                    <Link
                        v-if="showDirectWaiterBell"
                        :href="route('direct-waiter.index')"
                        class="relative rounded-md p-2 text-muted-foreground hover:bg-muted hover:text-ocean-deep transition-colors dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-100"
                        v-tippy="__('Direct Waiter messages')"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                        </svg>
                        <span
                            v-if="directWaiterUnreadCount > 0"
                            class="absolute right-0.5 top-0.5 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-destructive px-1 text-[10px] font-semibold leading-none text-white"
                        >
                            {{ directWaiterUnreadCount > 9 ? '9+' : directWaiterUnreadCount }}
                        </span>
                    </Link>

                <ToggleDark />

                    <!-- User dropdown -->
                    <Dropdown align="right" width="96" :content-classes="['py-1', 'bg-white dark:bg-gray-800']">
                        <template #trigger>
                            <button class="flex items-center gap-2 rounded-md px-2 py-1.5 text-sm font-body text-ocean-deep hover:bg-muted transition-colors dark:text-gray-100 dark:hover:bg-gray-800">
                                <img
                                    class="h-7 w-7 rounded-full object-cover ring-2 ring-border dark:ring-gray-600"
                                    :src="$page.props.auth.user.profile_photo_url"
                                    :alt="$page.props.auth.user.name"
                                >
                                <span class="hidden sm:block font-medium">{{ __('Configure the System') }}</span>
                                <svg class="h-4 w-4 text-muted-foreground dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                </svg>
                            </button>
                        </template>

                        <template #content>
                            <DropdownLink
                                :href="route('profile.show')"
                                :description="__('Edit your access profile information')"
                            >
                                {{ __('Profile') }}
                            </DropdownLink>

                            <DropdownLink
                                v-if="isManager()"
                                :href="route('settings.index')"
                                :description="__('Manage the venue, users and preferences')"
                            >
                                {{ __('Settings') }}
                            </DropdownLink>

                            <DropdownLink
                                v-if="isManager()"
                                :href="route('support.dashboard')"
                                :description="__('Open tickets and browse tutorials')"
                            >
                                {{ __('Support') }}
                            </DropdownLink>

                            <DropdownLink
                                v-if="$page.props.jetstream.hasApiFeatures"
                                :href="route('api-tokens.index')"
                                :description="__('Manage API access tokens')"
                            >
                                {{ __('API Tokens') }}
                            </DropdownLink>

                            <div class="mx-1 my-1 border-t border-border dark:border-gray-600" />
                            <form @submit.prevent="logout">
                                <DropdownLink as="button" :description="__('End the current session')">
                                    {{ __('Log Out') }}
                                </DropdownLink>
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
                    <Link
                        v-for="item in navItems"
                        :key="item.routeName"
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

                    <template v-if="moduleLinks.length">
                        <p class="px-3 pt-3 text-xs font-semibold uppercase tracking-wider text-muted-foreground dark:text-gray-400">
                            {{ __('Modules') }}
                        </p>
                        <Link
                            v-for="item in moduleLinks"
                            :key="item.routeName"
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
                <div
                    v-if="$slots.header"
                    class="sticky top-16 z-10 -mx-4 -mt-4 mb-6 border-b border-border bg-muted px-4 py-4 sm:-mx-6 sm:-mt-6 sm:px-6 sm:py-6 dark:border-gray-700 dark:bg-gray-950"
                >
                    <slot name="header" />
                </div>
                <slot />
            </main>
        </div>
    </div>
</template>

