<script setup>
import PlatformLayout from '@/Layouts/PlatformLayout.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    users: Array,
});

const showCreate = ref(false);
const editingUser = ref(null);

const createForm = useForm({
    name: '',
    email: '',
    password: '',
    role: 'read_only',
    active: true,
});

const editForm = useForm({
    name: '',
    email: '',
    password: '',
    role: '',
    active: true,
});

const roles = [
    { value: 'super_admin', label: 'Super Admin' },
    { value: 'finance', label: 'Finance' },
    { value: 'registration', label: 'Registration' },
    { value: 'read_only', label: 'Read Only' },
];

const submitCreate = () => {
    createForm.post(route('platform.users.store'), {
        onSuccess: () => { createForm.reset(); showCreate.value = false; },
    });
};

const startEdit = (user) => {
    editingUser.value = user.id;
    editForm.name = user.name;
    editForm.email = user.email;
    editForm.password = '';
    editForm.role = user.role;
    editForm.active = user.active;
};

const submitEdit = (user) => {
    editForm.put(route('platform.users.update', user.id), {
        onSuccess: () => { editingUser.value = null; },
    });
};

const destroy = (user) => {
    if (confirm('Delete this user?')) {
        useForm({}).delete(route('platform.users.destroy', user.id));
    }
};
</script>

<template>
    <PlatformLayout :title="__('Platform Users')">
        <template #header>
            <h1 class="font-heading text-xl font-bold text-ocean-deep dark:text-gray-100">{{ __('Platform Users') }}</h1>
        </template>

        <div class="space-y-4">
            <div class="flex justify-end">
                <button @click="showCreate = !showCreate" class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary/90 transition-colors">
                    {{ __('New User') }}
                </button>
            </div>

            <form v-if="showCreate" @submit.prevent="submitCreate" class="bg-white rounded-xl shadow-card p-6 space-y-4 dark:bg-gray-800">
                <h2 class="font-heading font-semibold text-ocean-deep mb-2 dark:text-gray-100">{{ __('New Platform User') }}</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-ocean-deep mb-1 dark:text-gray-300">{{ __('Name') }}</label>
                        <input v-model="createForm.name" type="text" class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-ocean-deep mb-1 dark:text-gray-300">{{ __('Email') }}</label>
                        <input v-model="createForm.email" type="email" class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-ocean-deep mb-1 dark:text-gray-300">{{ __('Password') }}</label>
                        <input v-model="createForm.password" type="password" class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-ocean-deep mb-1 dark:text-gray-300">{{ __('Role') }}</label>
                        <select v-model="createForm.role" class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                            <option v-for="r in roles" :key="r.value" :value="r.value">{{ r.label }}</option>
                        </select>
                    </div>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showCreate = false" class="rounded-md border border-border px-4 py-2 text-sm text-muted-foreground hover:bg-muted transition-colors dark:border-gray-600 dark:text-gray-400 dark:hover:bg-gray-700">{{ __('Cancel') }}</button>
                    <button type="submit" :disabled="createForm.processing" class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary/90 disabled:opacity-60 transition-colors">{{ __('Create') }}</button>
                </div>
            </form>

            <div class="bg-white rounded-xl shadow-card overflow-hidden dark:bg-gray-800">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-border bg-muted/50 dark:border-gray-700 dark:bg-gray-700/50">
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground dark:text-gray-400">{{ __('Name') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground dark:text-gray-400">{{ __('Email') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground dark:text-gray-400">{{ __('Role') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground dark:text-gray-400">{{ __('Status') }}</th>
                            <th class="px-4 py-3" />
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="user in users" :key="user.id" class="border-b border-border last:border-0 dark:border-gray-700">
                            <td class="px-4 py-3 font-medium text-ocean-deep dark:text-gray-100">{{ user.name }}</td>
                            <td class="px-4 py-3 text-muted-foreground dark:text-gray-400">{{ user.email }}</td>
                            <td class="px-4 py-3 capitalize dark:text-gray-300">{{ user.role }}</td>
                            <td class="px-4 py-3">
                                <span :class="user.active ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'" class="rounded-full px-2 py-0.5 text-xs font-medium">
                                    {{ user.active ? __('Active') : __('Inactive') }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button @click="startEdit(user)" class="text-primary hover:underline text-xs mr-3">{{ __('Edit') }}</button>
                                <button @click="destroy(user)" class="text-destructive hover:underline text-xs">{{ __('Delete') }}</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </PlatformLayout>
</template>
