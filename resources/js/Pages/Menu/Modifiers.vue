<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import AppCard from '@/Components/AppCard.vue';
import AppButton from '@/Components/AppButton.vue';
import AppBadge from '@/Components/AppBadge.vue';
import AppConfirmModal from '@/Components/AppConfirmModal.vue';
import AppEmptyState from '@/Components/AppEmptyState.vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    modifierGroups: {
        type: Array,
        default: () => [],
    },
});

// ── Group create ──────────────────────────────────────────────────────────────
const showCreateGroup = ref(false);

const createGroupForm = useForm({
    name: '',
    required: false,
    multiple_selection: false,
});

const submitCreateGroup = () => {
    createGroupForm.post(route('menu.modifier-groups.store'), {
        onSuccess: () => {
            createGroupForm.reset();
            showCreateGroup.value = false;
        },
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
    <AppLayout :title="__('Modifier Groups')">
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link :href="route('menu.index')" class="text-sm font-medium text-primary hover:underline">← {{ __('Menu') }}</Link>
                    <h1 class="font-heading text-2xl font-bold text-ocean-deep dark:text-gray-100">{{ __('Modifier Groups') }}</h1>
                </div>
                <AppButton @click="showCreateGroup = !showCreateGroup">{{ __('New Group') }}</AppButton>
            </div>
        </template>

        <div class="space-y-4">
            <!-- Create group form -->
            <AppCard v-if="showCreateGroup" :title="__('New Modifier Group')">
                <form @submit.prevent="submitCreateGroup" class="space-y-3">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-ocean-deep dark:text-gray-100">{{ __('Name') }} <span class="text-destructive">*</span></label>
                        <input
                            v-model="createGroupForm.name"
                            type="text"
                            class="w-full rounded-md border border-border dark:border-gray-700 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-gray-100"
                        />
                        <p v-if="createGroupForm.errors.name" class="mt-1 text-xs text-destructive">{{ createGroupForm.errors.name }}</p>
                    </div>
                    <div class="flex gap-4">
                        <label class="flex cursor-pointer items-center gap-2 text-sm">
                            <input v-model="createGroupForm.required" type="checkbox" class="h-4 w-4 rounded border-border dark:border-gray-700 text-primary" />
                            {{ __('Required') }}
                        </label>
                        <label class="flex cursor-pointer items-center gap-2 text-sm">
                            <input v-model="createGroupForm.multiple_selection" type="checkbox" class="h-4 w-4 rounded border-border dark:border-gray-700 text-primary" />
                            {{ __('Multiple Selection') }}
                        </label>
                    </div>
                    <div class="flex gap-2">
                        <AppButton type="submit" :loading="createGroupForm.processing">{{ __('Create') }}</AppButton>
                        <AppButton variant="ghost" type="button" @click="showCreateGroup = false; createGroupForm.reset()">{{ __('Cancel') }}</AppButton>
                    </div>
                </form>
            </AppCard>

            <!-- Empty state -->
            <AppEmptyState
                v-if="!modifierGroups.length && !showCreateGroup"
                :title="__('No modifier groups yet')"
                :description="__('Create a modifier group to add customizable options to your products (e.g. Cooking Point, Extras).')"
                :action-label="__('New Group')"
                @action="showCreateGroup = true"
            />

            <!-- Group cards -->
            <AppCard v-for="group in modifierGroups" :key="group.id">
                <!-- Group header -->
                <template v-if="editingGroupId !== group.id">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="font-heading text-sm font-semibold text-ocean-deep dark:text-gray-100">{{ group.name }}</h3>
                            <div class="mt-1 flex flex-wrap gap-1.5">
                                <AppBadge v-if="group.required" label="Required" color="#f59e0b" />
                                <AppBadge v-if="group.multiple_selection" label="Multi-select" color="#3b82f6" />
                                <span v-if="group.products?.length" class="text-xs text-muted-foreground">
                                    {{ __('Used in') }}: {{ group.products.map((p) => p.name).join(', ') }}
                                </span>
                            </div>
                        </div>
                        <div class="flex shrink-0 gap-2">
                            <AppButton size="sm" variant="secondary" @click="openEditGroup(group)">{{ __('Edit') }}</AppButton>
                            <AppButton size="sm" variant="destructive" @click="groupToDelete = group">{{ __('Delete') }}</AppButton>
                        </div>
                    </div>
                </template>

                <!-- Group edit form (inline) -->
                <template v-else>
                    <form @submit.prevent="submitEditGroup(group)" class="space-y-3">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-ocean-deep dark:text-gray-100">{{ __('Name') }}</label>
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

                <!-- Options list -->
                <div class="space-y-1">
                    <div v-for="option in group.options" :key="option.id">
                        <!-- Option view row -->
                        <div v-if="editingOptionId !== option.id" class="flex items-center justify-between rounded-md px-2 py-1.5 hover:bg-muted/50">
                            <div class="flex items-center gap-3">
                                <span class="text-sm text-ocean-deep dark:text-gray-100">{{ option.name }}</span>
                                <span class="text-xs text-muted-foreground">
                                    {{ option.extra_price > 0 ? `+R$ ${Number(option.extra_price).toFixed(2)}` : __('Free') }}
                                </span>
                                <AppBadge v-if="!option.active" label="Inactive" color="#94a3b8" />
                            </div>
                            <div class="flex gap-1.5">
                                <AppButton size="sm" variant="secondary" @click="openEditOption(option)">{{ __('Edit') }}</AppButton>
                                <AppButton
                                    size="sm"
                                    variant="destructive"
                                    @click="optionToDelete = { groupId: group.id, optionId: option.id, name: option.name }"
                                >{{ __('Delete') }}</AppButton>
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
                <div v-if="addingOptionForGroupId === group.id" class="mt-2 rounded-md border border-dashed border-border dark:border-gray-700 bg-muted/30 px-3 py-2">
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
                        class="text-xs font-medium text-primary hover:underline"
                        @click="openAddOption(group.id)"
                    >
                        + {{ __('Add Option') }}
                    </button>
                </div>
            </AppCard>
        </div>

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
    </AppLayout>
</template>
