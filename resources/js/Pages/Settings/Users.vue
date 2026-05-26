<script setup>
import SettingsLayout from '@/Layouts/SettingsLayout.vue';
import AppCard from '@/Components/AppCard.vue';
import AppButton from '@/Components/AppButton.vue';
import AppBadge from '@/Components/AppBadge.vue';
import AppConfirmModal from '@/Components/AppConfirmModal.vue';
import AppEmptyState from '@/Components/AppEmptyState.vue';
import { useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    users: Array,
});

const OPERATIONAL_ROLES = [
    'owner',
    'general_manager',
    'section_manager',
    'attendant',
];

const roleLabel = (role) => {
    const labels = {
        owner: 'Owner',
        general_manager: 'General Manager',
        section_manager: 'Section Manager',
        attendant: 'Attendant',
        corporation_admin: 'Corporation Admin',
    };
    return labels[role] ?? role;
};

const roleVariant = (role) => {
    const map = {
        owner: 'primary',
        general_manager: 'accent',
        section_manager: 'muted',
        attendant: 'muted',
        corporation_admin: 'primary',
    };
    return map[role] ?? 'muted';
};

const showForm = ref(false);
const editingUser = ref(null);
const userToDelete = ref(null);

const form = useForm({
    name: '',
    email: '',
    password: '',
    role: 'attendant',
    pin: '',
    active: true,
});

const openCreate = () => {
    editingUser.value = null;
    form.reset();
    form.role = 'attendant';
    form.active = true;
    showForm.value = true;
};

const openEdit = (user) => {
    editingUser.value = user;
    form.name = user.name;
    form.email = user.email;
    form.password = '';
    form.role = user.role;
    form.pin = '';
    form.active = user.active;
    showForm.value = true;
};

const closeForm = () => {
    showForm.value = false;
    editingUser.value = null;
    form.reset();
};

const submit = () => {
    if (editingUser.value) {
        form.put(route('settings.users.update', editingUser.value.id), {
            onSuccess: closeForm,
        });
    } else {
        form.post(route('settings.users.store'), {
            onSuccess: closeForm,
        });
    }
};

const confirmDelete = (user) => {
    userToDelete.value = user;
};

const deleteUser = () => {
    router.delete(route('settings.users.destroy', userToDelete.value.id), {
        onSuccess: () => { userToDelete.value = null; },
    });
};
</script>

<template>
    <SettingsLayout :title="__('Users')">
        <template #header>
            <div class="flex items-center justify-between">
                <h1 class="font-heading text-2xl font-bold text-ocean-deep">{{ __('Users') }}</h1>
                <AppButton @click="openCreate">{{ __('Add User') }}</AppButton>
            </div>
        </template>

        <AppCard>
            <AppEmptyState
                v-if="!users.length && !showForm"
                :title="__('No users yet')"
                :description="__('Add team members to this venue.')"
            />

            <div v-if="users.length" class="divide-y divide-muted">
                <div
                    v-for="user in users"
                    :key="user.id"
                    class="flex items-center justify-between py-3"
                >
                    <div class="flex items-center gap-3">
                        <div>
                            <p class="font-body text-sm font-medium text-ocean-deep">{{ user.name }}</p>
                            <p class="text-xs text-muted-foreground">{{ user.email }}</p>
                        </div>
                        <AppBadge :label="roleLabel(user.role)" :variant="roleVariant(user.role)" />
                        <span
                            :class="user.active ? 'bg-accent/10 text-accent' : 'bg-muted text-muted-foreground'"
                            class="rounded-full px-2 py-0.5 text-xs font-semibold"
                        >
                            {{ user.active ? __('Active') : __('Inactive') }}
                        </span>
                    </div>
                    <div class="flex gap-2">
                        <AppButton size="sm" variant="secondary" @click="openEdit(user)">{{ __('Edit') }}</AppButton>
                        <AppButton size="sm" variant="destructive" @click="confirmDelete(user)">{{ __('Delete') }}</AppButton>
                    </div>
                </div>
            </div>

            <div v-if="showForm" class="mt-4 rounded-lg border border-border p-4">
                <h3 class="mb-3 font-heading text-sm font-semibold text-ocean-deep">
                    {{ editingUser ? __('Edit User') : __('New User') }}
                </h3>
                <form @submit.prevent="submit" class="space-y-3">
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-ocean-deep mb-1">{{ __('Name') }} <span class="text-destructive">*</span></label>
                            <input
                                v-model="form.name"
                                type="text"
                                class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                            />
                            <p v-if="form.errors.name" class="mt-1 text-xs text-destructive">{{ form.errors.name }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-ocean-deep mb-1">{{ __('Email') }} <span class="text-destructive">*</span></label>
                            <input
                                v-model="form.email"
                                type="email"
                                class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                            />
                            <p v-if="form.errors.email" class="mt-1 text-xs text-destructive">{{ form.errors.email }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-ocean-deep mb-1">
                                {{ __('Password') }} <span v-if="!editingUser" class="text-destructive">*</span>
                                <span v-else class="text-muted-foreground">({{ __('leave blank to keep current') }})</span>
                            </label>
                            <input
                                v-model="form.password"
                                type="password"
                                autocomplete="new-password"
                                class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                            />
                            <p v-if="form.errors.password" class="mt-1 text-xs text-destructive">{{ form.errors.password }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-ocean-deep mb-1">{{ __('Role') }} <span class="text-destructive">*</span></label>
                            <select
                                v-model="form.role"
                                class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                            >
                                <option v-for="r in OPERATIONAL_ROLES" :key="r" :value="r">
                                    {{ roleLabel(r) }}
                                </option>
                            </select>
                            <p v-if="form.errors.role" class="mt-1 text-xs text-destructive">{{ form.errors.role }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-ocean-deep mb-1">{{ __('PIN') }}</label>
                            <input
                                v-model="form.pin"
                                type="text"
                                maxlength="10"
                                class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                            />
                        </div>
                    </div>

                    <label class="flex cursor-pointer items-center gap-3">
                        <input v-model="form.active" type="checkbox" class="h-4 w-4 rounded border-border text-primary focus:ring-primary" />
                        <span class="text-sm text-ocean-deep">{{ __('Active') }}</span>
                    </label>

                    <div class="flex gap-2 pt-1">
                        <AppButton type="submit" :loading="form.processing">{{ __('Save') }}</AppButton>
                        <AppButton type="button" variant="ghost" @click="closeForm">{{ __('Cancel') }}</AppButton>
                    </div>
                </form>
            </div>
        </AppCard>

        <AppConfirmModal
            :show="!!userToDelete"
            :title="__('Delete User')"
            :message="`{{ __('Are you sure you want to delete') }} ${userToDelete?.name}?`"
            :confirm-label="__('Delete')"
            variant="destructive"
            @confirm="deleteUser"
            @cancel="userToDelete = null"
        />
    </SettingsLayout>
</template>
