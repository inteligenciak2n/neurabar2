<script setup>
import PlatformLayout from '@/Layouts/PlatformLayout.vue';
import { useForm } from '@inertiajs/vue3';
import { useCurrency } from '@/Composables/useCurrency';
import { useTranslate } from '@/Composables/useTranslate';

const props = defineProps({
    corporation: Object,
    modules: Object,
});

const __ = useTranslate();
const { formatMoney } = useCurrency();

const form = useForm({
    module_code: '',
    custom_monthly_price: '',
});

const submit = () => {
    form.post(route('platform.corporations.modules.store', props.corporation.id), {
        onSuccess: () => form.reset(),
    });
};

const destroy = (moduleId) => {
    if (confirm(__('Are you sure you want to disable this module?'))) {
        form.delete(route('platform.corporations.modules.destroy', [props.corporation.id, moduleId]));
    }
};
</script>

<template>
    <PlatformLayout :title="__('Modules')">
        <template #header>
            <h1 class="font-heading text-xl font-bold text-ocean-deep dark:text-gray-100">{{ corporation.name }} - {{ __('Modules') }}</h1>
        </template>

        <div class="max-w-3xl space-y-6">
            <form @submit.prevent="submit" class="space-y-4 bg-white rounded-xl shadow-card p-6 dark:bg-gray-800">
                <h2 class="font-heading font-semibold text-ocean-deep border-b pb-2 dark:text-gray-100 dark:border-gray-700">{{ __('Enable Module') }}</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-ocean-deep mb-1 dark:text-gray-300">{{ __('Module') }}</label>
                        <input v-model="form.module_code" type="text" class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" />
                        <p v-if="form.errors.module_code" class="mt-1 text-xs text-destructive">{{ form.errors.module_code }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-ocean-deep mb-1 dark:text-gray-300">{{ __('Custom Monthly Price') }}</label>
                        <input v-model="form.custom_monthly_price" type="number" step="0.01" class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" />
                        <p v-if="form.errors.custom_monthly_price" class="mt-1 text-xs text-destructive">{{ form.errors.custom_monthly_price }}</p>
                    </div>
                </div>
                <div class="flex justify-end">
                    <button type="submit" :disabled="form.processing" class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary/90 disabled:opacity-60 transition-colors">
                        {{ __('Enable') }}
                    </button>
                </div>
            </form>

            <div class="bg-white rounded-xl shadow-card overflow-hidden dark:bg-gray-800">
                <table class="min-w-full text-sm">
                    <thead class="bg-ocean-deep text-white">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">{{ __('Module') }}</th>
                            <th class="px-4 py-3 text-left font-medium">{{ __('Status') }}</th>
                            <th class="px-4 py-3 text-left font-medium">{{ __('Price') }}</th>
                            <th class="px-4 py-3 text-right font-medium">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border dark:divide-gray-700">
                        <tr v-for="module in modules.data" :key="module.id">
                            <td class="px-4 py-3 text-ocean-deep dark:text-gray-100">{{ module.catalog?.name ?? module.module_code }}</td>
                            <td class="px-4 py-3">
                                <span :class="module.status.value === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'" class="rounded-full px-2 py-1 text-xs font-medium">
                                    {{ module.status.label }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-ocean-deep dark:text-gray-100">
                                {{ module.custom_monthly_price ? formatMoney(module.custom_monthly_price) : formatMoney(module.catalog?.base_monthly_price ?? 0) }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button v-if="module.status.value === 'active'" @click="destroy(module.id)" class="text-destructive hover:underline text-xs">
                                    {{ __('Disable') }}
                                </button>
                            </td>
                        </tr>
                        <tr v-if="!modules.data.length">
                            <td colspan="4" class="px-4 py-6 text-center text-muted-foreground">{{ __('No modules found.') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </PlatformLayout>
</template>
