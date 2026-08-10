<script setup>
import PlatformLayout from '@/Layouts/PlatformLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useTranslate } from '@/Composables/useTranslate';
import { useCurrency } from '@/Composables/useCurrency';

const props = defineProps({
    plans: Array,
    modules: Array,
    canManage: Boolean,
});

const __ = useTranslate();
const { formatMoney, toAmount } = useCurrency();
const showCreate = ref(false);
const editingPlan = ref(null);

const createForm = useForm({
    code: '',
    name: '',
    description: '',
    monthly_price: '',
    dedicated_surcharge: '',
    plan_type: 'shared',
    included_modules: [],
    sort_order: 0,
    active: true,
});

const editForm = useForm({
    code: '',
    name: '',
    description: '',
    monthly_price: '',
    dedicated_surcharge: '',
    plan_type: 'shared',
    included_modules: [],
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
    // O backend guarda centavos; o formulário edita reais.
    editForm.monthly_price = toAmount(plan.monthly_price);
    editForm.dedicated_surcharge = toAmount(plan.dedicated_surcharge);
    editForm.plan_type = plan.plan_type ?? 'shared';
    editForm.included_modules = [...(plan.included_modules ?? [])];
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
            <div v-if="canManage" class="flex justify-end">
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
                        <InputError :message="createForm.errors.code" class="mt-1" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-ocean-deep mb-1 dark:text-gray-300">{{ __('Name') }}</label>
                        <input v-model="createForm.name" type="text" class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" />
                        <InputError :message="createForm.errors.name" class="mt-1" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-ocean-deep mb-1 dark:text-gray-300">{{ __('Monthly Price') }}</label>
                        <input v-model="createForm.monthly_price" type="number" step="0.01" class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" />
                        <InputError :message="createForm.errors.monthly_price" class="mt-1" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-ocean-deep mb-1 dark:text-gray-300">{{ __('Dedicated Infrastructure Surcharge') }}</label>
                        <input v-model="createForm.dedicated_surcharge" type="number" step="0.01" min="0" class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" />
                        <InputError :message="createForm.errors.dedicated_surcharge" class="mt-1" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-ocean-deep mb-1 dark:text-gray-300">{{ __('Plan Type') }}</label>
                        <select v-model="createForm.plan_type" class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                            <option value="shared">{{ __('Shared') }}</option>
                            <option value="dedicated">{{ __('Dedicated') }}</option>
                        </select>
                        <InputError :message="createForm.errors.plan_type" class="mt-1" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-ocean-deep mb-1 dark:text-gray-300">{{ __('Sort Order') }}</label>
                        <input v-model="createForm.sort_order" type="number" min="0" class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" />
                        <InputError :message="createForm.errors.sort_order" class="mt-1" />
                    </div>
                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-ocean-deep mb-1 dark:text-gray-300">{{ __('Description') }}</label>
                        <textarea v-model="createForm.description" rows="2" class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" />
                        <InputError :message="createForm.errors.description" class="mt-1" />
                    </div>
                    <fieldset class="sm:col-span-3">
                        <legend class="block text-sm font-medium text-ocean-deep mb-2 dark:text-gray-300">{{ __('Included Modules') }}</legend>
                        <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                            <label v-for="module in modules" :key="module.code" class="flex items-center gap-2 rounded-md border border-border px-3 py-2 text-sm dark:border-gray-600">
                                <input v-model="createForm.included_modules" :value="module.code" type="checkbox" class="rounded border-border text-primary focus:ring-primary dark:border-gray-600" />
                                <span>{{ module.name }}</span>
                            </label>
                        </div>
                        <InputError :message="createForm.errors.included_modules" class="mt-1" />
                    </fieldset>
                    <div class="flex items-center gap-2">
                        <input id="create-active" v-model="createForm.active" type="checkbox" class="rounded border-border text-primary focus:ring-primary dark:border-gray-600" />
                        <label for="create-active" class="text-sm font-medium text-ocean-deep dark:text-gray-300">{{ __('Active') }}</label>
                    </div>
                </div>
                <div class="flex justify-end gap-2 mt-4">
                    <button type="button" @click="showCreate = false" class="rounded-md border border-border px-4 py-2 text-sm text-muted-foreground hover:bg-muted transition-colors dark:border-gray-600 dark:text-gray-400 dark:hover:bg-gray-700">{{ __('Cancel') }}</button>
                    <button type="submit" :disabled="createForm.processing" class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary/90 disabled:opacity-60 transition-colors">{{ __('Create') }}</button>
                </div>
            </form>

            <!-- Edit form -->
            <form v-if="editingPlan" @submit.prevent="submitEdit({ id: editingPlan })" class="bg-white rounded-xl shadow-card p-6 dark:bg-gray-800">
                <h2 class="font-heading font-semibold text-ocean-deep mb-4 dark:text-gray-100">{{ __('Edit Plan') }}</h2>
                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <label class="block text-sm font-medium text-ocean-deep mb-1 dark:text-gray-300">{{ __('Code') }}</label>
                        <input v-model="editForm.code" type="text" class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" />
                        <InputError :message="editForm.errors.code" class="mt-1" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-ocean-deep mb-1 dark:text-gray-300">{{ __('Name') }}</label>
                        <input v-model="editForm.name" type="text" class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" />
                        <InputError :message="editForm.errors.name" class="mt-1" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-ocean-deep mb-1 dark:text-gray-300">{{ __('Monthly Price') }}</label>
                        <input v-model="editForm.monthly_price" type="number" step="0.01" class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" />
                        <InputError :message="editForm.errors.monthly_price" class="mt-1" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-ocean-deep mb-1 dark:text-gray-300">{{ __('Dedicated Infrastructure Surcharge') }}</label>
                        <input v-model="editForm.dedicated_surcharge" type="number" step="0.01" min="0" class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" />
                        <InputError :message="editForm.errors.dedicated_surcharge" class="mt-1" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-ocean-deep mb-1 dark:text-gray-300">{{ __('Plan Type') }}</label>
                        <select v-model="editForm.plan_type" class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                            <option value="shared">{{ __('Shared') }}</option>
                            <option value="dedicated">{{ __('Dedicated') }}</option>
                        </select>
                        <InputError :message="editForm.errors.plan_type" class="mt-1" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-ocean-deep mb-1 dark:text-gray-300">{{ __('Sort Order') }}</label>
                        <input v-model="editForm.sort_order" type="number" min="0" class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" />
                        <InputError :message="editForm.errors.sort_order" class="mt-1" />
                    </div>
                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-ocean-deep mb-1 dark:text-gray-300">{{ __('Description') }}</label>
                        <textarea v-model="editForm.description" rows="2" class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" />
                        <InputError :message="editForm.errors.description" class="mt-1" />
                    </div>
                    <fieldset class="sm:col-span-3">
                        <legend class="block text-sm font-medium text-ocean-deep mb-2 dark:text-gray-300">{{ __('Included Modules') }}</legend>
                        <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                            <label v-for="module in modules" :key="module.code" class="flex items-center gap-2 rounded-md border border-border px-3 py-2 text-sm dark:border-gray-600">
                                <input v-model="editForm.included_modules" :value="module.code" type="checkbox" class="rounded border-border text-primary focus:ring-primary dark:border-gray-600" />
                                <span>{{ module.name }}</span>
                            </label>
                        </div>
                        <InputError :message="editForm.errors.included_modules" class="mt-1" />
                    </fieldset>
                    <div class="flex items-center gap-2">
                        <input v-model="editForm.active" type="checkbox" id="edit-active" class="rounded border-border text-primary focus:ring-primary dark:border-gray-600" />
                        <label for="edit-active" class="text-sm font-medium text-ocean-deep dark:text-gray-300">{{ __('Active') }}</label>
                    </div>
                </div>
                <div class="flex justify-end gap-2 mt-4">
                    <button type="button" @click="editingPlan = null" class="rounded-md border border-border px-4 py-2 text-sm text-muted-foreground hover:bg-muted transition-colors dark:border-gray-600 dark:text-gray-400 dark:hover:bg-gray-700">{{ __('Cancel') }}</button>
                    <button type="submit" :disabled="editForm.processing" class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary/90 disabled:opacity-60 transition-colors">{{ __('Save') }}</button>
                </div>
            </form>

            <!-- Plans list -->
            <div class="overflow-x-auto rounded-xl bg-white shadow-card dark:bg-gray-800" v-if="!editingPlan">
                <table class="min-w-[820px] w-full text-sm">
                    <thead>
                        <tr class="border-b border-border bg-muted/50 dark:border-gray-700 dark:bg-gray-700/50">
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground dark:text-gray-400">{{ __('Code') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground dark:text-gray-400">{{ __('Name') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground dark:text-gray-400">{{ __('Price/mo') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground dark:text-gray-400">{{ __('Type') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground dark:text-gray-400">{{ __('Modules') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground dark:text-gray-400">{{ __('Status') }}</th>
                            <th class="px-4 py-3" />
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="plan in plans" :key="plan.id" class="border-b border-border last:border-0 dark:border-gray-700">
                            <td class="px-4 py-3 font-mono text-xs text-muted-foreground dark:text-gray-400">{{ plan.code }}</td>
                            <td class="px-4 py-3 font-medium text-ocean-deep dark:text-gray-100">{{ plan.name }}</td>
                            <td class="px-4 py-3 dark:text-gray-300">{{ formatMoney(plan.monthly_price) }}</td>
                            <td class="px-4 py-3 capitalize dark:text-gray-300">{{ __(plan.plan_type ?? 'shared') }}</td>
                            <td class="px-4 py-3 text-muted-foreground dark:text-gray-400">{{ plan.included_modules?.length ?? 0 }}</td>
                            <td class="px-4 py-3">
                                <span :class="plan.active ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'" class="rounded-full px-2 py-0.5 text-xs font-medium">
                                    {{ plan.active ? __('Active') : __('Inactive') }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <Link :href="route('platform.plans.usage-pricing.show', plan.id)" class="text-primary hover:underline text-xs mr-3">
                                    {{ __('Usage tiers') }}
                                </Link>
                                <template v-if="canManage">
                                    <button @click="startEdit(plan)" class="text-primary hover:underline text-xs mr-3">{{ __('Edit') }}</button>
                                    <button @click="destroy(plan)" class="text-destructive hover:underline text-xs">{{ __('Delete') }}</button>
                                </template>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </PlatformLayout>
</template>
