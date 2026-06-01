<script setup>
import PlatformLayout from '@/Layouts/PlatformLayout.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useTranslate } from '@/Composables/useTranslate';

const props = defineProps({
    plans: Array,
});

const __ = useTranslate();
const showCreate = ref(false);
const editingPlan = ref(null);

const createForm = useForm({
    code: '',
    name: '',
    description: '',
    monthly_price: '',
    sort_order: 0,
    active: true,
});

const editForm = useForm({
    code: '',
    name: '',
    description: '',
    monthly_price: '',
    sort_order: 0,
    active: true,
});

const submitCreate = () => {
    createForm.post(route('platform.plans.store'), {
        onSuccess: () => {
            createForm.reset();
            showCreate.value = false;
        },
    });
};

const startEdit = (plan) => {
    editingPlan.value = plan.id;
    editForm.code = plan.code;
    editForm.name = plan.name;
    editForm.description = plan.description ?? '';
    editForm.monthly_price = plan.monthly_price;
    editForm.sort_order = plan.sort_order;
    editForm.active = plan.active;
};

const submitEdit = (plan) => {
    editForm.put(route('platform.plans.update', plan.id), {
        onSuccess: () => { editingPlan.value = null; },
    });
};

const destroy = (plan) => {
    if (confirm(__('Delete this plan?'))) {
        editForm.delete(route('platform.plans.destroy', plan.id));
    }
};
</script>

<template>
    <PlatformLayout :title="__('Plans')">
        <template #header>
            <h1 class="font-heading text-xl font-bold text-ocean-deep dark:text-gray-100">{{ __('Plan Catalog') }}</h1>
        </template>

        <div class="space-y-4">
            <div class="flex justify-end">
                <button @click="showCreate = !showCreate" class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary/90 transition-colors">
                    {{ __('New Plan') }}
                </button>
            </div>

            <!-- Create form -->
            <form v-if="showCreate" @submit.prevent="submitCreate" class="bg-white rounded-xl shadow-card p-6 dark:bg-gray-800">
                <h2 class="font-heading font-semibold text-ocean-deep mb-4 dark:text-gray-100">{{ __('New Plan') }}</h2>
                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <label class="block text-sm font-medium text-ocean-deep mb-1 dark:text-gray-300">{{ __('Code') }}</label>
                        <input v-model="createForm.code" type="text" class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-ocean-deep mb-1 dark:text-gray-300">{{ __('Name') }}</label>
                        <input v-model="createForm.name" type="text" class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-ocean-deep mb-1 dark:text-gray-300">{{ __('Monthly Price') }}</label>
                        <input v-model="createForm.monthly_price" type="number" step="0.01" class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" />
                    </div>
                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-ocean-deep mb-1 dark:text-gray-300">{{ __('Description') }}</label>
                        <textarea v-model="createForm.description" rows="2" class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" />
                    </div>
                </div>
                <div class="flex justify-end gap-2 mt-4">
                    <button type="button" @click="showCreate = false" class="rounded-md border border-border px-4 py-2 text-sm text-muted-foreground hover:bg-muted transition-colors dark:border-gray-600 dark:text-gray-400 dark:hover:bg-gray-700">{{ __('Cancel') }}</button>
                    <button type="submit" :disabled="createForm.processing" class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary/90 disabled:opacity-60 transition-colors">{{ __('Create') }}</button>
                </div>
            </form>

            <!-- Plans list -->
            <div class="bg-white rounded-xl shadow-card overflow-hidden dark:bg-gray-800">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-border bg-muted/50 dark:border-gray-700 dark:bg-gray-700/50">
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground dark:text-gray-400">{{ __('Code') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground dark:text-gray-400">{{ __('Name') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground dark:text-gray-400">{{ __('Price/mo') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground dark:text-gray-400">{{ __('Status') }}</th>
                            <th class="px-4 py-3" />
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="plan in plans" :key="plan.id" class="border-b border-border last:border-0 dark:border-gray-700">
                            <td class="px-4 py-3 font-mono text-xs text-muted-foreground dark:text-gray-400">{{ plan.code }}</td>
                            <td class="px-4 py-3 font-medium text-ocean-deep dark:text-gray-100">{{ plan.name }}</td>
                            <td class="px-4 py-3 dark:text-gray-300">R$ {{ Number(plan.monthly_price).toLocaleString('pt-BR', { minimumFractionDigits: 2 }) }}</td>
                            <td class="px-4 py-3">
                                <span :class="plan.active ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'" class="rounded-full px-2 py-0.5 text-xs font-medium">
                                    {{ plan.active ? __('Active') : __('Inactive') }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button @click="startEdit(plan)" class="text-primary hover:underline text-xs mr-3">{{ __('Edit') }}</button>
                                <button @click="destroy(plan)" class="text-destructive hover:underline text-xs">{{ __('Delete') }}</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </PlatformLayout>
</template>
