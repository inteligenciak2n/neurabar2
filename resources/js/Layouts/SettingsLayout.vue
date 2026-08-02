<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';

defineProps({
    title: String,
});
import { useTranslate } from '@/Composables/useTranslate';

const __ = useTranslate();

const navItems = [
    {
        label: __('Subscription'),
        description: __('Manage modules, invoices and payment methods.'),
        routeName: 'settings.subscription.index',
        activePattern: 'settings.subscription.*',
        icon: 'M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
    },
    {
        label: 'Venue Info',
        routeName: 'settings.venue',
        activePattern: 'settings.venue',
        icon: 'M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z',
    },
    {
        label: 'General',
        routeName: 'settings.general',
        activePattern: 'settings.general',
        icon: 'M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z',
    },
    {
        label: 'Kitchen Stations',
        routeName: 'settings.kitchen-stations.index',
        activePattern: 'settings.kitchen-stations.*',
        icon: 'M15.362 5.214A8.252 8.252 0 0 1 12 21 8.25 8.25 0 0 1 6.038 7.047 8.287 8.287 0 0 0 9 9.601a8.983 8.983 0 0 1 3.361-6.867 8.21 8.21 0 0 0 3 2.48Z M12 18a3.75 3.75 0 0 0 .495-7.468 5.99 5.99 0 0 0-1.925 3.547 5.975 5.975 0 0 1-2.133-1.001A3.75 3.75 0 0 0 12 18Z',
    },
    {
        label: 'Preparation Statuses',
        routeName: 'settings.preparation-statuses.index',
        activePattern: 'settings.preparation-statuses.*',
        icon: 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
    },
    {
        label: 'Service Locations',
        routeName: 'settings.service-locations.index',
        activePattern: 'settings.service-locations.*',
        icon: 'M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z',
    },
    {
        label: 'Attendance Channels',
        routeName: 'settings.attendance-channels.index',
        activePattern: 'settings.attendance-channels.*',
        icon: 'M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z',
    },
    {
        label: 'Users',
        routeName: 'settings.users.index',
        activePattern: 'settings.users.*',
        icon: 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z',
    },
];
</script>

<template>
    <AppLayout :title="title">
        <template #header>
            <slot name="header" />
        </template>

        <div class="flex gap-6">
            <!-- Settings sidebar -->
            <aside class="hidden w-56 shrink-0 lg:block">
                <nav class="sticky top-24 flex flex-col gap-0.5">
                    <p class="mb-2 px-3 text-xs font-semibold uppercase tracking-wider text-muted-foreground">Settings</p>
                    <Link
                        v-for="item in navItems"
                        :key="item.label"
                        :href="route(item.routeName)"
                        :class="[
                            'flex items-center gap-2.5 rounded-md px-3 py-2 text-sm font-medium transition-colors',
                            route().current(item.activePattern)
                                ? 'bg-ocean-light text-primary font-semibold dark:bg-primary/20'
                                : 'text-muted-foreground hover:bg-muted hover:text-ocean-deep dark:hover:bg-gray-800 dark:hover:text-gray-100',
                        ]"
                    >
                        <svg
                            class="h-4 w-4 shrink-0"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.5"
                            viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" :d="item.icon" />
                        </svg>
                        {{ item.label }}
                    </Link>
                </nav>
            </aside>

            <!-- Main content -->
            <div class="min-w-0 flex-1">
                <!-- Mobile nav -->
                <div class="mb-4 flex gap-1 overflow-x-auto pb-1 lg:hidden">
                    <Link
                        v-for="item in navItems"
                        :key="item.label"
                        :href="route(item.routeName)"
                        :class="[
                            'flex shrink-0 items-center gap-1.5 rounded-md px-3 py-1.5 text-xs font-medium transition-colors',
                            route().current(item.activePattern)
                                ? 'bg-ocean-light text-primary font-semibold dark:bg-primary/20'
                                : 'text-muted-foreground bg-white border border-border hover:text-ocean-deep dark:bg-gray-800 dark:border-gray-700 dark:hover:text-gray-100',
                        ]"
                    >
                        {{ item.label }}
                    </Link>
                </div>

                <slot />
            </div>
        </div>
    </AppLayout>
</template>
