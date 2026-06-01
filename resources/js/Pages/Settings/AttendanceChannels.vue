<script setup>
import SettingsLayout from '@/Layouts/SettingsLayout.vue';
import AppCard from '@/Components/AppCard.vue';
import AppButton from '@/Components/AppButton.vue';
import AppConfirmModal from '@/Components/AppConfirmModal.vue';
import AppEmptyState from '@/Components/AppEmptyState.vue';
import { useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    channels: Array,
});

const showForm = ref(false);
const editingChannel = ref(null);
const channelToDelete = ref(null);

const form = useForm({
    name: '',
    is_trackable: true,
    requires_customer_identifier: false,
    active: true,
    sort_order: 0,
});

const openCreate = () => {
    editingChannel.value = null;
    form.reset();
    form.is_trackable = true;
    form.requires_customer_identifier = false;
    form.active = true;
    form.sort_order = 0;
    showForm.value = true;
};

const openEdit = (channel) => {
    editingChannel.value = channel;
    form.name = channel.name;
    form.is_trackable = channel.is_trackable;
    form.requires_customer_identifier = channel.requires_customer_identifier;
    form.active = channel.active;
    form.sort_order = channel.sort_order;
    showForm.value = true;
};

const closeForm = () => {
    showForm.value = false;
    editingChannel.value = null;
    form.reset();
};

const submit = () => {
    if (editingChannel.value) {
        form.put(route('settings.attendance-channels.update', editingChannel.value.id), {
            onSuccess: closeForm,
        });
    } else {
        form.post(route('settings.attendance-channels.store'), {
            onSuccess: closeForm,
        });
    }
};

const confirmDelete = (channel) => {
    channelToDelete.value = channel;
};

const deleteChannel = () => {
    router.delete(route('settings.attendance-channels.destroy', channelToDelete.value.id), {
        onSuccess: () => { channelToDelete.value = null; },
    });
};
</script>

<template>
    <SettingsLayout :title="__('Attendance Channels')">
        <template #header>
            <div class="flex items-center justify-between">
                <h1 class="font-heading text-2xl font-bold text-ocean-deep dark:text-gray-100">{{ __('Attendance Channels') }}</h1>
                <AppButton @click="openCreate">{{ __('Add Channel') }}</AppButton>
            </div>
        </template>

        <AppCard>
            <AppEmptyState
                v-if="!channels.length && !showForm"
                :title="__('No attendance channels yet')"
                :description="__('Add channels like counter, table, or delivery.')"
            />

            <div v-if="channels.length && !showForm" class="divide-y divide-muted">
                <div
                    v-for="channel in channels"
                    :key="channel.id"
                    class="flex items-center justify-between py-3"
                >
                    <div class="flex items-center gap-3">
                        <span class="font-body text-sm font-medium text-ocean-deep dark:text-gray-100">{{ channel.name }}</span>
                        <span
                            :class="channel.active ? 'bg-accent/10 text-accent' : 'bg-muted text-muted-foreground'"
                            class="rounded-full px-2 py-0.5 text-xs font-semibold"
                        >
                            {{ channel.active ? __('Active') : __('Inactive') }}
                        </span>
                        <span v-if="channel.is_trackable" class="rounded-full bg-blue-50 px-2 py-0.5 text-xs font-semibold text-blue-600">{{ __('Trackable') }}</span>
                        <span v-if="channel.requires_customer_identifier" class="rounded-full bg-yellow-50 px-2 py-0.5 text-xs font-semibold text-yellow-700">{{ __('Requires Identifier') }}</span>
                    </div>
                    <div class="flex gap-2">
                        <AppButton size="sm" variant="secondary" @click="openEdit(channel)">{{ __('Edit') }}</AppButton>
                        <AppButton size="sm" variant="destructive" @click="confirmDelete(channel)">{{ __('Delete') }}</AppButton>
                    </div>
                </div>
            </div>

            <div v-if="showForm" class="mt-4 rounded-lg border border-border dark:border-gray-700 p-4">
                <h3 class="mb-3 font-heading text-sm font-semibold text-ocean-deep dark:text-gray-100">
                    {{ editingChannel ? __('Edit Channel') : __('New Channel') }}
                </h3>
                <form class="space-y-3" @submit.prevent="submit">
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-ocean-deep dark:text-gray-100">{{ __('Name') }} <span class="text-destructive">*</span></label>
                            <input
                                v-model="form.name"
                                type="text"
                                :placeholder="__('e.g. Counter')"
                                class="w-full rounded-md border border-border dark:border-gray-700 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-gray-100"
                            />
                            <p v-if="form.errors.name" class="mt-1 text-xs text-destructive">{{ form.errors.name }}</p>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-ocean-deep dark:text-gray-100">{{ __('Sort Order') }}</label>
                            <input
                                v-model="form.sort_order"
                                type="number"
                                min="0"
                                class="w-full rounded-md border border-border dark:border-gray-700 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-gray-100"
                            />
                        </div>
                    </div>

                    <div class="flex flex-col gap-2">
                        <label class="flex cursor-pointer items-center gap-3">
                            <input v-model="form.active" type="checkbox" class="h-4 w-4 rounded border-border dark:border-gray-700 text-primary focus:ring-primary" />
                            <span class="text-sm text-ocean-deep dark:text-gray-100">{{ __('Active') }}</span>
                        </label>
                        <label class="flex cursor-pointer items-center gap-3">
                            <input v-model="form.is_trackable" type="checkbox" class="h-4 w-4 rounded border-border dark:border-gray-700 text-primary focus:ring-primary" />
                            <span class="text-sm text-ocean-deep dark:text-gray-100">{{ __('Trackable by customer') }}</span>
                        </label>
                        <label class="flex cursor-pointer items-center gap-3">
                            <input v-model="form.requires_customer_identifier" type="checkbox" class="h-4 w-4 rounded border-border dark:border-gray-700 text-primary focus:ring-primary" />
                            <span class="text-sm text-ocean-deep dark:text-gray-100">{{ __('Requires customer identifier') }}</span>
                        </label>
                    </div>

                    <div class="flex gap-2 pt-1">
                        <AppButton type="submit" :loading="form.processing">{{ __('Save') }}</AppButton>
                        <AppButton type="button" variant="ghost" @click="closeForm">{{ __('Cancel') }}</AppButton>
                    </div>
                </form>
            </div>
        </AppCard>

        <AppConfirmModal
            :show="!!channelToDelete"
            :title="__('Delete Attendance Channel')"
            :message="__('Are you sure you want to delete this channel?')"
            :confirm-label="__('Delete')"
            variant="destructive"
            @confirm="deleteChannel"
            @cancel="channelToDelete = null"
        />
    </SettingsLayout>
</template>
