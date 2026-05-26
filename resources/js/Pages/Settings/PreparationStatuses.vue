<script setup>
import SettingsLayout from '@/Layouts/SettingsLayout.vue';
import AppCard from '@/Components/AppCard.vue';
import AppButton from '@/Components/AppButton.vue';
import AppConfirmModal from '@/Components/AppConfirmModal.vue';
import AppEmptyState from '@/Components/AppEmptyState.vue';
import { useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    statuses: Array,
});

const showForm = ref(false);
const editingStatus = ref(null);
const statusToDelete = ref(null);

const form = useForm({
    name: '',
    color: '#6366f1',
    sort_order: '',
    show_to_customer: false,
});

const openCreate = () => {
    editingStatus.value = null;
    form.reset();
    form.color = '#6366f1';
    showForm.value = true;
};

const openEdit = (status) => {
    editingStatus.value = status;
    form.name = status.name;
    form.color = status.color ?? '#6366f1';
    form.sort_order = status.sort_order ?? '';
    form.show_to_customer = status.show_to_customer;
    showForm.value = true;
};

const closeForm = () => {
    showForm.value = false;
    editingStatus.value = null;
    form.reset();
};

const submit = () => {
    if (editingStatus.value) {
        form.put(route('settings.preparation-statuses.update', editingStatus.value.id), {
            onSuccess: closeForm,
        });
    } else {
        form.post(route('settings.preparation-statuses.store'), {
            onSuccess: closeForm,
        });
    }
};

const confirmDelete = (status) => {
    statusToDelete.value = status;
};

const deleteStatus = () => {
    router.delete(route('settings.preparation-statuses.destroy', statusToDelete.value.id), {
        onSuccess: () => { statusToDelete.value = null; },
    });
};
</script>

<template>
    <SettingsLayout title="Preparation Statuses">
        <template #header>
            <div class="flex items-center justify-between">
                <h1 class="font-heading text-2xl font-bold text-ocean-deep">Preparation Statuses</h1>
                <AppButton @click="openCreate">Add Status</AppButton>
            </div>
        </template>

        <AppCard>
            <AppEmptyState
                v-if="!statuses.length && !showForm"
                title="No preparation statuses yet"
                description="Add statuses to track kitchen order progress."
            />

            <div v-if="statuses.length" class="divide-y divide-muted">
                <div
                    v-for="status in statuses"
                    :key="status.id"
                    class="flex items-center justify-between py-3"
                >
                    <div class="flex items-center gap-3">
                        <span
                            class="h-4 w-4 rounded-full border border-border"
                            :style="{ backgroundColor: status.color ?? '#e5e7eb' }"
                        />
                        <span class="font-body text-sm font-medium text-ocean-deep">{{ status.name }}</span>
                        <span
                            v-if="status.show_to_customer"
                            class="rounded-full bg-accent/10 px-2 py-0.5 text-xs font-semibold text-accent"
                        >
                            Visible to customer
                        </span>
                    </div>
                    <div class="flex gap-2">
                        <AppButton size="sm" variant="secondary" @click="openEdit(status)">Edit</AppButton>
                        <AppButton size="sm" variant="destructive" @click="confirmDelete(status)">Delete</AppButton>
                    </div>
                </div>
            </div>

            <div v-if="showForm" class="mt-4 rounded-lg border border-border p-4">
                <h3 class="mb-3 font-heading text-sm font-semibold text-ocean-deep">
                    {{ editingStatus ? 'Edit Status' : 'New Status' }}
                </h3>
                <form @submit.prevent="submit" class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-ocean-deep mb-1">Name <span class="text-destructive">*</span></label>
                        <input
                            v-model="form.name"
                            type="text"
                            class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                        />
                        <p v-if="form.errors.name" class="mt-1 text-xs text-destructive">{{ form.errors.name }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-ocean-deep mb-1">Color</label>
                        <div class="flex items-center gap-3">
                            <input
                                v-model="form.color"
                                type="color"
                                class="h-9 w-14 cursor-pointer rounded border border-border p-1"
                            />
                            <span class="text-sm text-muted-foreground font-mono">{{ form.color }}</span>
                        </div>
                        <p v-if="form.errors.color" class="mt-1 text-xs text-destructive">{{ form.errors.color }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-ocean-deep mb-1">Sort Order</label>
                        <input
                            v-model="form.sort_order"
                            type="number"
                            min="0"
                            class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                        />
                    </div>

                    <label class="flex cursor-pointer items-center gap-3">
                        <input v-model="form.show_to_customer" type="checkbox" class="h-4 w-4 rounded border-border text-primary focus:ring-primary" />
                        <span class="text-sm text-ocean-deep">Visible to customer</span>
                    </label>

                    <div class="flex gap-2 pt-1">
                        <AppButton type="submit" :loading="form.processing">Save</AppButton>
                        <AppButton type="button" variant="ghost" @click="closeForm">Cancel</AppButton>
                    </div>
                </form>
            </div>
        </AppCard>

        <AppConfirmModal
            :show="!!statusToDelete"
            title="Delete Preparation Status"
            message="Are you sure you want to delete this status?"
            confirm-label="Delete"
            variant="destructive"
            @confirm="deleteStatus"
            @cancel="statusToDelete = null"
        />
    </SettingsLayout>
</template>
