<script setup>
import AppCard from '@/Components/AppCard.vue';
import AppButton from '@/Components/AppButton.vue';
import AppBadge from '@/Components/AppBadge.vue';
import AppConfirmModal from '@/Components/AppConfirmModal.vue';
import AppEmptyState from '@/Components/AppEmptyState.vue';
import { useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    modifierGroups: {
        type: Array,
        default: () => [],
    },
});

// ── Group create ──────────────────────────────────────────────────────────────
const showCreateGroup = ref(false);

const emptyOption = () => ({ name: '', extra_price: 0 });

const createGroupForm = useForm({
    name: '',
    required: false,
    multiple_selection: false,
    options: [emptyOption()],
});

const openCreateGroup = () => {
    createGroupForm.reset();
    createGroupForm.options = [emptyOption()];
    showCreateGroup.value = true;
};

const closeCreateGroup = () => {
    showCreateGroup.value = false;
    createGroupForm.reset();
};

const addCreateOption = () => {
    createGroupForm.options.push(emptyOption());
};

const removeCreateOption = (index) => {
    if (createGroupForm.options.length > 1) {
        createGroupForm.options.splice(index, 1);
    }
};

const submitCreateGroup = () => {
    createGroupForm.post(route('menu.modifier-groups.store'), {
        onSuccess: closeCreateGroup,
    });
};

// ── Group edit ────────────────────────────────────────────────────────────────
const editingGroupId = ref(null);

const editGroupForm = useForm({
    name: '',
    required: false,
    multiple_selection: false,
});

const openEditGroup = (group) => {
    editingGroupId.value = group.id;
    editGroupForm.name = group.name;
    editGroupForm.required = group.required;
    editGroupForm.multiple_selection = group.multiple_selection;
};

const closeEditGroup = () => {
    editingGroupId.value = null;
    editGroupForm.reset();
};

const submitEditGroup = (group) => {
    editGroupForm.put(route('menu.modifier-groups.update', group.id), {
        onSuccess: closeEditGroup,
    });
};

// ── Group delete ──────────────────────────────────────────────────────────────
const groupToDelete = ref(null);

const deleteGroup = () => {
    router.delete(route('menu.modifier-groups.destroy', groupToDelete.value.id), {
        onSuccess: () => { groupToDelete.value = null; },
    });
};

// ── Option create (per group) ─────────────────────────────────────────────────
const addingOptionForGroupId = ref(null);

const createOptionForm = useForm({
    name: '',
    extra_price: 0,
    active: true,
});

const openAddOption = (groupId) => {
    addingOptionForGroupId.value = groupId;
    createOptionForm.reset();
    createOptionForm.extra_price = 0;
    createOptionForm.active = true;
};

const cancelAddOption = () => {
    addingOptionForGroupId.value = null;
    createOptionForm.reset();
};

const submitCreateOption = (group) => {
    createOptionForm.post(route('menu.modifier-groups.options.store', group.id), {
        onSuccess: () => {
            addingOptionForGroupId.value = null;
            createOptionForm.reset();
        },
    });
};

// ── Option edit ───────────────────────────────────────────────────────────────
const editingOptionId = ref(null);

const editOptionForm = useForm({
    name: '',
    extra_price: 0,
    active: true,
});

const openEditOption = (option) => {
    editingOptionId.value = option.id;
    editOptionForm.name = option.name;
    editOptionForm.extra_price = option.extra_price;
    editOptionForm.active = option.active;
};

const closeEditOption = () => {
    editingOptionId.value = null;
    editOptionForm.reset();
};

const submitEditOption = (group, option) => {
    editOptionForm.put(route('menu.modifier-groups.options.update', { modifierGroup: group.id, option: option.id }), {
        onSuccess: closeEditOption,
    });
};

// ── Option delete ─────────────────────────────────────────────────────────────
const optionToDelete = ref(null);

const deleteOption = () => {
    router.delete(
        route('menu.modifier-groups.options.destroy', {
            modifierGroup: optionToDelete.value.groupId,
            option: optionToDelete.value.optionId,
        }),
        {
            onSuccess: () => { optionToDelete.value = null; },
        },
    );
};
</script>

<template>
    <section>
        <AppCard>
        <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
            <div class="flex min-w-0 items-center gap-3">
                <h2 class="shrink-0 font-heading text-3xl font-bold tracking-tight text-ocean-deep dark:text-gray-100">
                    {{ __('Modifiers') }}
                    <span class="mt-2 block h-1 w-16 rounded-full bg-warm-gold" aria-hidden="true" />
                </h2>
                <p class="text-sm leading-snug text-muted-foreground dark:text-gray-400">
                    <span class="block">{{ __('Register extras and options for the customer to choose.') }}</span>
                    <span class="block">{{ __('The customer customizes the order their way.') }}</span>
                </p>
            </div>
            <AppButton variant="success" @click="openCreateGroup">{{ __('Add Modifier') }}</AppButton>
        </div>

        <!-- Create group form -->
        <div v-if="showCreateGroup" class="mt-4 rounded-lg border-4 border-[#5c9a6c] p-4">
            <form class="grid grid-cols-1 items-start gap-4 lg:grid-cols-2" @submit.prevent="submitCreateGroup">
                <div class="flex flex-col gap-3">
                    <h3 class="font-heading text-lg font-bold tracking-tight text-ocean-deep dark:text-gray-100">
                        {{ __('Modifier Name') }} <span class="text-destructive">*</span>
                        <span class="mt-1 block h-0.5 w-10 rounded-full bg-warm-gold" aria-hidden="true" />
                    </h3>
                    <div>
                        <input
                            v-model="createGroupForm.name"
                            type="text"
                            class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:border-gray-700 dark:bg-gray-700 dark:text-gray-100"
                        />
                        <p v-if="createGroupForm.errors.name" class="mt-1 text-xs text-destructive">{{ createGroupForm.errors.name }}</p>
                    </div>
                    <div class="flex gap-4">
                        <label class="flex cursor-pointer items-center gap-2 text-sm">
                            <input v-model="createGroupForm.required" type="checkbox" class="h-4 w-4 rounded border-border text-primary dark:border-gray-700" />
                            {{ __('Required') }}
                        </label>
                        <label class="flex cursor-pointer items-center gap-2 text-sm">
                            <input v-model="createGroupForm.multiple_selection" type="checkbox" class="h-4 w-4 rounded border-border text-primary dark:border-gray-700" />
                            {{ __('Multiple Selection') }}
                        </label>
                    </div>
                    <div class="mt-auto flex gap-2 pt-2">
                        <AppButton type="submit" :loading="createGroupForm.processing">{{ __('Create') }}</AppButton>
                        <AppButton variant="ghost" type="button" @click="closeCreateGroup">{{ __('Cancel') }}</AppButton>
                    </div>
                </div>

                <div class="flex flex-col gap-3 lg:border-l lg:border-border lg:pl-4 dark:lg:border-gray-700">
                    <h4 class="font-heading text-lg font-bold tracking-tight text-ocean-deep dark:text-gray-100">
                        {{ __('Items') }}
                        <span class="mt-1 block h-0.5 w-10 rounded-full bg-warm-gold" aria-hidden="true" />
                    </h4>
                    <p v-if="createGroupForm.errors.options" class="text-xs text-destructive">{{ createGroupForm.errors.options }}</p>
                    <div class="space-y-2">
                        <div
                            v-for="(option, index) in createGroupForm.options"
                            :key="index"
                            class="rounded border-2 border-[#5c9a6c] p-2"
                        >
                            <div class="grid grid-cols-1 gap-2 sm:grid-cols-[1fr_8rem_auto] sm:items-start">
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-ocean-deep dark:text-gray-100">{{ __('Item Name') }}</label>
                                    <input
                                        v-model="option.name"
                                        type="text"
                                        class="w-full rounded-md border border-border px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:border-gray-700 dark:bg-gray-700 dark:text-gray-100"
                                    />
                                    <p v-if="createGroupForm.errors[`options.${index}.name`]" class="mt-1 text-xs text-destructive">
                                        {{ createGroupForm.errors[`options.${index}.name`] }}
                                    </p>
                                    <AppButton
                                        v-if="index === createGroupForm.options.length - 1"
                                        type="button"
                                        size="sm"
                                        variant="success"
                                        class="mt-2"
                                        @click="addCreateOption"
                                    >
                                        {{ __('Add Item') }}
                                    </AppButton>
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-ocean-deep dark:text-gray-100">{{ __('Value') }} (R$)</label>
                                    <input
                                        v-model.number="option.extra_price"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        class="w-full rounded-md border border-border px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:border-gray-700 dark:bg-gray-700 dark:text-gray-100"
                                    />
                                    <p v-if="createGroupForm.errors[`options.${index}.extra_price`]" class="mt-1 text-xs text-destructive">
                                        {{ createGroupForm.errors[`options.${index}.extra_price`] }}
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    class="sm:mt-6 text-xs text-destructive hover:underline disabled:opacity-50"
                                    :disabled="createGroupForm.options.length === 1"
                                    @click="removeCreateOption(index)"
                                >
                                    {{ __('Remove') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="space-y-4">
            <AppEmptyState
                v-if="!modifierGroups.length && !showCreateGroup"
                :title="__('No modifier groups yet')"
                :description="__('Create a modifier group to add customizable options to your products (e.g. Cooking Point, Extras).')"
                :action-label="__('Add Modifier')"
                @action="openCreateGroup"
            />

            <!-- Group cards -->
            <div v-if="modifierGroups.length && !showCreateGroup" class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <AppCard v-for="group in modifierGroups" :key="group.id">
                <!-- Group header -->
                <template v-if="editingGroupId !== group.id">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="font-heading text-sm font-semibold text-ocean-deep dark:text-gray-100">{{ group.name }}</h3>
                                <AppBadge v-if="group.required" :label="__('Required')" color="#f59e0b" />
                                <AppBadge v-if="group.multiple_selection" :label="__('Multiple Selection')" color="#3b82f6" />
                            </div>
                            <div v-if="group.products?.length" class="mt-1.5">
                                <p class="mb-1 text-xs text-muted-foreground">{{ __('Used in') }}</p>
                                <ul class="grid grid-cols-2 gap-1.5">
                                    <li
                                        v-for="product in group.products"
                                        :key="product.id"
                                        class="truncate rounded-md bg-gray-200 px-2 py-1 text-xs font-medium text-ocean-deep dark:bg-gray-700 dark:text-gray-100"
                                    >
                                        {{ product.name }}
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div class="flex shrink-0 gap-2">
                            <AppButton size="sm" variant="secondary" @click="openEditGroup(group)">
                                <span class="flex flex-col leading-tight">
                                    <span>{{ __('Edit') }}</span>
                                    <span>{{ __('Modifier') }}</span>
                                </span>
                            </AppButton>
                            <AppButton size="sm" variant="destructive" @click="groupToDelete = group">
                                <span class="flex flex-col leading-tight">
                                    <span>{{ __('Delete') }}</span>
                                    <span>{{ __('Modifier') }}</span>
                                </span>
                            </AppButton>
                        </div>
                    </div>
                </template>

                <!-- Group edit form (inline) -->
                <template v-else>
                    <form @submit.prevent="submitEditGroup(group)" class="space-y-3">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-ocean-deep dark:text-gray-100">{{ __('Modifier Name') }}</label>
                            <input
                                v-model="editGroupForm.name"
                                type="text"
                                class="w-full rounded-md border border-border dark:border-gray-700 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-gray-100"
                            />
                            <p v-if="editGroupForm.errors.name" class="mt-1 text-xs text-destructive">{{ editGroupForm.errors.name }}</p>
                        </div>
                        <div class="flex gap-4">
                            <label class="flex cursor-pointer items-center gap-2 text-sm">
                                <input v-model="editGroupForm.required" type="checkbox" class="h-4 w-4 rounded border-border dark:border-gray-700 text-primary" />
                                {{ __('Required') }}
                            </label>
                            <label class="flex cursor-pointer items-center gap-2 text-sm">
                                <input v-model="editGroupForm.multiple_selection" type="checkbox" class="h-4 w-4 rounded border-border dark:border-gray-700 text-primary" />
                                {{ __('Multiple Selection') }}
                            </label>
                        </div>
                        <div class="flex gap-2">
                            <AppButton type="submit" :loading="editGroupForm.processing">{{ __('Save') }}</AppButton>
                            <AppButton variant="ghost" type="button" @click="closeEditGroup">{{ __('Cancel') }}</AppButton>
                        </div>
                    </form>
                </template>

                <!-- Divider -->
                <hr class="my-3 border-border dark:border-gray-700" />

                <div class="pl-6">
                <!-- Options list -->
                <div class="space-y-1">
                    <div v-for="option in group.options" :key="option.id">
                        <!-- Option view row -->
                        <div v-if="editingOptionId !== option.id" class="flex items-center justify-between rounded-md px-2 py-1.5 hover:bg-muted/50">
                            <div class="flex items-center gap-3">
                                <span class="font-heading text-sm font-semibold text-ocean-deep dark:text-gray-100">{{ option.name }}</span>
                                <span class="text-xs text-muted-foreground">
                                    {{ option.extra_price > 0 ? `+R$ ${Number(option.extra_price).toFixed(2)}` : __('Free') }}
                                </span>
                                <AppBadge v-if="!option.active" :label="__('Inactive')" color="#94a3b8" />
                            </div>
                            <div class="flex gap-1.5">
                                <AppButton size="sm" variant="secondary" @click="openEditOption(option)">
                                    <span class="flex flex-col leading-tight">
                                        <span>{{ __('Edit') }}</span>
                                        <span>{{ __('Items') }}</span>
                                    </span>
                                </AppButton>
                                <AppButton
                                    size="sm"
                                    variant="destructive"
                                    @click="optionToDelete = { groupId: group.id, optionId: option.id, name: option.name }"
                                >
                                    <span class="flex flex-col leading-tight">
                                        <span>{{ __('Delete') }}</span>
                                        <span>{{ __('Items') }}</span>
                                    </span>
                                </AppButton>
                            </div>
                        </div>

                        <!-- Option edit form (inline) -->
                        <div v-else class="rounded-md border border-border dark:border-gray-700 bg-muted/30 px-3 py-2">
                            <form @submit.prevent="submitEditOption(group, option)" class="flex flex-wrap items-end gap-2">
                                <div class="min-w-[180px] flex-1">
                                    <label class="mb-1 block text-xs font-medium text-ocean-deep dark:text-gray-100">{{ __('Name') }}</label>
                                    <input
                                        v-model="editOptionForm.name"
                                        type="text"
                                        class="w-full rounded-md border border-border dark:border-gray-700 px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                                    />
                                    <p v-if="editOptionForm.errors.name" class="mt-1 text-xs text-destructive">{{ editOptionForm.errors.name }}</p>
                                </div>
                                <div class="w-28">
                                    <label class="mb-1 block text-xs font-medium text-ocean-deep dark:text-gray-100">{{ __('Extra price') }} (R$)</label>
                                    <input
                                        v-model.number="editOptionForm.extra_price"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        class="w-full rounded-md border border-border dark:border-gray-700 px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                                    />
                                </div>
                                <div class="flex items-center gap-1 pb-1.5">
                                    <label class="flex cursor-pointer items-center gap-1.5 text-xs">
                                        <input v-model="editOptionForm.active" type="checkbox" class="h-3.5 w-3.5 rounded border-border dark:border-gray-700 text-primary" />
                                        {{ __('Active') }}
                                    </label>
                                </div>
                                <div class="flex gap-1.5">
                                    <AppButton type="submit" size="sm" :loading="editOptionForm.processing">{{ __('Save') }}</AppButton>
                                    <AppButton type="button" size="sm" variant="ghost" @click="closeEditOption">{{ __('Cancel') }}</AppButton>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Empty options -->
                    <p v-if="!group.options?.length && addingOptionForGroupId !== group.id" class="py-1 text-xs text-muted-foreground">
                        {{ __('No options yet.') }}
                    </p>
                </div>

                <!-- Add option form (inline) -->
                <div v-if="addingOptionForGroupId === group.id" class="mt-2 rounded-md border-2 border-[#5c9a6c] bg-muted/30 px-3 py-2">
                    <form @submit.prevent="submitCreateOption(group)" class="flex flex-wrap items-end gap-2">
                        <div class="min-w-[180px] flex-1">
                            <label class="mb-1 block text-xs font-medium text-ocean-deep dark:text-gray-100">{{ __('Name') }}</label>
                            <input
                                v-model="createOptionForm.name"
                                type="text"
                                class="w-full rounded-md border border-border dark:border-gray-700 px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                            />
                            <p v-if="createOptionForm.errors.name" class="mt-1 text-xs text-destructive">{{ createOptionForm.errors.name }}</p>
                        </div>
                        <div class="w-28">
                            <label class="mb-1 block text-xs font-medium text-ocean-deep dark:text-gray-100">{{ __('Extra price') }} (R$)</label>
                            <input
                                v-model.number="createOptionForm.extra_price"
                                type="number"
                                min="0"
                                step="0.01"
                                class="w-full rounded-md border border-border dark:border-gray-700 px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                            />
                        </div>
                        <div class="flex gap-1.5">
                            <AppButton type="submit" size="sm" :loading="createOptionForm.processing">{{ __('Add') }}</AppButton>
                            <AppButton type="button" size="sm" variant="ghost" @click="cancelAddOption">{{ __('Cancel') }}</AppButton>
                        </div>
                    </form>
                </div>

                <!-- Add option button -->
                <div class="mt-3">
                    <button
                        v-if="addingOptionForGroupId !== group.id"
                        type="button"
                        class="text-xs font-medium text-warm-gold hover:text-sand hover:underline"
                        @click="openAddOption(group.id)"
                    >
                        + {{ __('Add Option') }}
                    </button>
                </div>
                </div>
            </AppCard>
            </div>
        </div>
        </AppCard>

        <!-- Delete group confirmation -->
        <AppConfirmModal
            :show="!!groupToDelete"
            :title="__('Delete Modifier Group')"
            :message="__('Are you sure you want to delete this modifier group? All options will be removed.')"
            :confirm-label="__('Delete')"
            variant="destructive"
            @confirm="deleteGroup"
            @cancel="groupToDelete = null"
        />

        <!-- Delete option confirmation -->
        <AppConfirmModal
            :show="!!optionToDelete"
            :title="__('Delete Option')"
            :message="__('Are you sure you want to delete this option?')"
            :confirm-label="__('Delete')"
            variant="destructive"
            @confirm="deleteOption"
            @cancel="optionToDelete = null"
        />
    </section>
</template>
