<script setup>
import PlatformLayout from '@/Layouts/PlatformLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    corporations: Object,
    filters: Object,
});

const search = ref(props.filters?.search ?? '');

const applySearch = () => {
    router.get(route('platform.corporations.index'), { search: search.value }, { preserveState: true, replace: true });
};
</script>

<template>
    <PlatformLayout :title="__('Corporations')">
        <template #header>
            <h1 class="font-heading text-xl font-bold text-ocean-deep dark:text-gray-100">{{ __('Corporations') }}</h1>
        </template>

        <div class="space-y-4">
            <div class="flex items-center justify-between gap-4">
                <input
                    v-model="search"
                    type="text"
                    :placeholder="__('Search by name or email…')"
                    class="rounded-md border border-border px-3 py-2 text-sm w-64 focus:outline-none focus:ring-2 focus:ring-primary dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 dark:placeholder-gray-400"
                    @input="applySearch"
                />
                <Link :href="route('platform.corporations.create')" class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary/90 transition-colors">
                    {{ __('New Corporation') }}
                </Link>
            </div>

            <div class="bg-white rounded-xl shadow-card overflow-hidden dark:bg-gray-800">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-border bg-muted/50 dark:border-gray-700 dark:bg-gray-700/50">
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground dark:text-gray-400">{{ __('Name') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground dark:text-gray-400">{{ __('Email') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground dark:text-gray-400">{{ __('Plan') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground dark:text-gray-400">{{ __('MRR') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground dark:text-gray-400">{{ __('Status') }}</th>
                            <th class="px-4 py-3" />
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="corp in corporations.data"
                            :key="corp.id"
                            class="border-b border-border last:border-0 hover:bg-muted/30 transition-colors dark:border-gray-700 dark:hover:bg-gray-700/30"
                        >
                            <td class="px-4 py-3 font-medium text-ocean-deep dark:text-gray-100">{{ corp.name }}</td>
                            <td class="px-4 py-3 text-muted-foreground dark:text-gray-400">{{ corp.email }}</td>
                            <td class="px-4 py-3 dark:text-gray-300">{{ corp.subscription?.plan_catalog?.name ?? '—' }}</td>
                            <td class="px-4 py-3 dark:text-gray-300">R$ {{ Number(corp.subscription?.total_value ?? 0).toLocaleString('pt-BR', { minimumFractionDigits: 2 }) }}</td>
                            <td class="px-4 py-3">
                                <span
                                    :class="corp.active ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'"
                                    class="rounded-full px-2 py-0.5 text-xs font-medium"
                                >
                                    {{ corp.active ? __('Active') : __('Inactive') }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <Link :href="route('platform.corporations.edit', corp.id)" class="text-primary hover:underline text-xs">{{ __('Edit') }}</Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="corporations.last_page > 1" class="flex gap-2">
                <Link
                    v-for="link in corporations.links"
                    :key="link.label"
                    :href="link.url ?? '#'"
                    :class="[
                        'rounded px-3 py-1 text-sm',
                        link.active ? 'bg-primary text-white' : 'bg-white text-muted-foreground hover:bg-muted dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700',
                        !link.url ? 'opacity-50 pointer-events-none' : '',
                    ]"
                    v-html="link.label"
                />
            </div>
        </div>
    </PlatformLayout>
</template>
