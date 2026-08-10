<script setup>
import InputError from '@/Components/InputError.vue';
import { useCurrency } from '@/Composables/useCurrency';
import { useTranslate } from '@/Composables/useTranslate';
import PlatformLayout from '@/Layouts/PlatformLayout.vue';
import { useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    modules: Array,
    moduleCodes: Array,
    billingTypes: Array,
    roles: Array,
    canManage: Boolean,
});

const __ = useTranslate();
const { formatMoney, toAmount } = useCurrency();
const editingModuleId = ref(null);
const showForm = ref(false);

const form = useForm({
    code: '',
    name: '',
    description: '',
    category: 'basic',
    billing_type: 'fixed',
    base_monthly_price: '',
    unit_of_measure: '',
    dependencies: [],
    required_roles: [],
    icon: '',
    sort_order: 0,
    active: true,
});

const usedCodes = computed(() => new Set(props.modules.map((module) => module.code)));
const dependencyOptions = computed(() => props.modules.filter((module) => module.id !== editingModuleId.value));

const resetForm = () => {
    editingModuleId.value = null;
    form.reset();
    form.clearErrors();
};

const startCreate = () => {
    resetForm();
    showForm.value = true;
};

const startEdit = (module) => {
    editingModuleId.value = module.id;
    showForm.value = true;
    form.clearErrors();
    form.code = module.code;
    form.name = module.name;
    form.description = module.description ?? '';
    form.category = module.category;
    form.billing_type = module.billing_type;
    form.base_monthly_price = toAmount(module.base_monthly_price);
    form.unit_of_measure = module.unit_of_measure ?? '';
    form.dependencies = [...(module.dependencies ?? [])];
    form.required_roles = [...(module.required_roles ?? [])];
    form.icon = module.icon ?? '';
    form.sort_order = module.sort_order;
    form.active = module.active;
};

const closeForm = () => {
    showForm.value = false;
    resetForm();
};

const submit = () => {
    const options = {
        preserveScroll: true,
        onSuccess: closeForm,
    };

    if (editingModuleId.value) {
        form.put(route('platform.modules.update', editingModuleId.value), options);
        return;
    }

    form.post(route('platform.modules.store'), options);
};

const destroyModule = (module) => {
    if (confirm(__('Delete this module?'))) {
        form.delete(route('platform.modules.destroy', module.id), { preserveScroll: true });
    }
};
</script>

<template>
    <PlatformLayout :title="__('Modules')">
        <template #header>
            <div class="flex w-full items-center justify-between gap-4">
                <div>
                    <h1 class="font-heading text-xl font-bold text-ocean-deep dark:text-gray-100">{{ __('Module Catalog') }}</h1>
                    <p class="text-xs text-muted-foreground dark:text-gray-400">{{ modules.length }} {{ __('modules') }}</p>
                </div>
                <button v-if="canManage" type="button" class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-primary/90" @click="startCreate">
                    {{ __('New Module') }}
                </button>
            </div>
        </template>

        <div class="space-y-4">
            <form v-if="showForm" class="rounded-lg bg-white p-6 shadow-card dark:bg-gray-800" @submit.prevent="submit">
                <div class="mb-5 flex items-center justify-between gap-4">
                    <h2 class="font-heading font-semibold text-ocean-deep dark:text-gray-100">
                        {{ editingModuleId ? __('Edit Module') : __('New Module') }}
                    </h2>
                    <span v-if="editingModuleId" class="font-mono text-xs text-muted-foreground dark:text-gray-400">{{ form.code }}</span>
                </div>

                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-ocean-deep dark:text-gray-300">{{ __('Code') }}</label>
                        <select v-model="form.code" :disabled="Boolean(editingModuleId)" class="w-full rounded-md border border-border px-3 py-2 text-sm disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                            <option value="">{{ __('Select') }}</option>
                            <option v-for="option in moduleCodes" :key="option.value" :value="option.value" :disabled="!editingModuleId && usedCodes.has(option.value)">
                                {{ option.label }} ({{ option.value }})
                            </option>
                        </select>
                        <InputError :message="form.errors.code" class="mt-1" />
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-ocean-deep dark:text-gray-300">{{ __('Name') }}</label>
                        <input v-model="form.name" type="text" class="w-full rounded-md border border-border px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" />
                        <InputError :message="form.errors.name" class="mt-1" />
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-ocean-deep dark:text-gray-300">{{ __('Category') }}</label>
                        <input v-model="form.category" type="text" class="w-full rounded-md border border-border px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" />
                        <InputError :message="form.errors.category" class="mt-1" />
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-ocean-deep dark:text-gray-300">{{ __('Billing Type') }}</label>
                        <select v-model="form.billing_type" class="w-full rounded-md border border-border px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                            <option v-for="type in billingTypes" :key="type.value" :value="type.value">{{ __(type.label) }}</option>
                        </select>
                        <InputError :message="form.errors.billing_type" class="mt-1" />
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-ocean-deep dark:text-gray-300">{{ __('Base Monthly Price') }}</label>
                        <input v-model="form.base_monthly_price" type="number" min="0" step="0.01" class="w-full rounded-md border border-border px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" />
                        <InputError :message="form.errors.base_monthly_price" class="mt-1" />
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-ocean-deep dark:text-gray-300">{{ __('Unit of Measure') }}</label>
                        <input v-model="form.unit_of_measure" type="text" class="w-full rounded-md border border-border px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" />
                        <InputError :message="form.errors.unit_of_measure" class="mt-1" />
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-ocean-deep dark:text-gray-300">{{ __('Icon') }}</label>
                        <input v-model="form.icon" type="text" class="w-full rounded-md border border-border px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" />
                        <InputError :message="form.errors.icon" class="mt-1" />
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-ocean-deep dark:text-gray-300">{{ __('Sort Order') }}</label>
                        <input v-model="form.sort_order" type="number" min="0" class="w-full rounded-md border border-border px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" />
                        <InputError :message="form.errors.sort_order" class="mt-1" />
                    </div>

                    <div class="md:col-span-2 xl:col-span-4">
                        <label class="mb-1 block text-sm font-medium text-ocean-deep dark:text-gray-300">{{ __('Description') }}</label>
                        <textarea v-model="form.description" rows="2" class="w-full rounded-md border border-border px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" />
                        <InputError :message="form.errors.description" class="mt-1" />
                    </div>

                    <fieldset class="md:col-span-2">
                        <legend class="mb-2 text-sm font-medium text-ocean-deep dark:text-gray-300">{{ __('Dependencies') }}</legend>
                        <div class="grid gap-2 sm:grid-cols-2">
                            <label v-for="module in dependencyOptions" :key="module.code" class="flex items-center gap-2 rounded-md border border-border px-3 py-2 text-sm dark:border-gray-600">
                                <input v-model="form.dependencies" :value="module.code" type="checkbox" class="rounded border-border text-primary focus:ring-primary" />
                                <span>{{ module.name }}</span>
                            </label>
                        </div>
                        <InputError :message="form.errors.dependencies" class="mt-1" />
                    </fieldset>

                    <fieldset class="md:col-span-2">
                        <legend class="mb-2 text-sm font-medium text-ocean-deep dark:text-gray-300">{{ __('Required Roles') }}</legend>
                        <div class="grid gap-2 sm:grid-cols-2">
                            <label v-for="role in roles" :key="role.value" class="flex items-center gap-2 rounded-md border border-border px-3 py-2 text-sm dark:border-gray-600">
                                <input v-model="form.required_roles" :value="role.value" type="checkbox" class="rounded border-border text-primary focus:ring-primary" />
                                <span>{{ __(role.label) }}</span>
                            </label>
                        </div>
                        <InputError :message="form.errors.required_roles" class="mt-1" />
                    </fieldset>

                    <div class="flex items-center gap-2">
                        <input id="module-active" v-model="form.active" type="checkbox" class="rounded border-border text-primary focus:ring-primary" />
                        <label for="module-active" class="text-sm font-medium text-ocean-deep dark:text-gray-300">{{ __('Active') }}</label>
                    </div>
                </div>

                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" class="rounded-md border border-border px-4 py-2 text-sm text-muted-foreground transition-colors hover:bg-muted dark:border-gray-600 dark:text-gray-400 dark:hover:bg-gray-700" @click="closeForm">
                        {{ __('Cancel') }}
                    </button>
                    <button type="submit" :disabled="form.processing" class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-primary/90 disabled:opacity-60">
                        {{ editingModuleId ? __('Save') : __('Create') }}
                    </button>
                </div>
            </form>

            <InputError :message="form.errors.module" />

            <div class="overflow-x-auto rounded-lg bg-white shadow-card dark:bg-gray-800" v-if="!showForm">
                <table class="min-w-[860px] w-full text-sm">
                    <thead>
                        <tr class="border-b border-border bg-muted/50 dark:border-gray-700 dark:bg-gray-700/50">
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground dark:text-gray-400">{{ __('Module') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground dark:text-gray-400">{{ __('Category') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground dark:text-gray-400">{{ __('Billing') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground dark:text-gray-400">{{ __('Price/mo') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground dark:text-gray-400">{{ __('Dependencies') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground dark:text-gray-400">{{ __('Status') }}</th>
                            <th class="px-4 py-3" />
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="module in modules" :key="module.id" class="border-b border-border last:border-0 dark:border-gray-700">
                            <td class="px-4 py-3">
                                <div class="font-medium text-ocean-deep dark:text-gray-100">{{ module.name }}</div>
                                <div class="font-mono text-xs text-muted-foreground dark:text-gray-400">{{ module.code }}</div>
                            </td>
                            <td class="px-4 py-3 capitalize text-muted-foreground dark:text-gray-300">{{ module.category }}</td>
                            <td class="px-4 py-3 capitalize text-muted-foreground dark:text-gray-300">{{ __(module.billing_type) }}</td>
                            <td class="px-4 py-3 dark:text-gray-300">{{ formatMoney(module.base_monthly_price) }}</td>
                            <td class="px-4 py-3 text-muted-foreground dark:text-gray-400">{{ module.dependencies?.length ?? 0 }}</td>
                            <td class="px-4 py-3">
                                <span :class="module.active ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'" class="rounded-full px-2 py-0.5 text-xs font-medium">
                                    {{ module.active ? __('Active') : __('Inactive') }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                <template v-if="canManage">
                                    <button type="button" class="mr-3 text-xs text-primary hover:underline" @click="startEdit(module)">{{ __('Edit') }}</button>
                                    <button type="button" class="text-xs text-destructive hover:underline" @click="destroyModule(module)">{{ __('Delete') }}</button>
                                </template>
                            </td>
                        </tr>
                        <tr v-if="modules.length === 0">
                            <td colspan="7" class="px-4 py-10 text-center text-muted-foreground dark:text-gray-400">{{ __('No modules found') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </PlatformLayout>
</template>
