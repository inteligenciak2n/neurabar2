<script setup>
import SettingsLayout from '@/Layouts/SettingsLayout.vue';
import AppCard from '@/Components/AppCard.vue';
import AppButton from '@/Components/AppButton.vue';
import AppConfirmModal from '@/Components/AppConfirmModal.vue';
import AppEmptyState from '@/Components/AppEmptyState.vue';
import { useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    stations: Array,
});

const showForm = ref(false);
const editingStation = ref(null);
const stationToDelete = ref(null);

const form = useForm({
    name: '',
    sort_order: '',
    active: true,
});

const openCreate = () => {
    editingStation.value = null;
    form.reset();
    form.active = true;
    showForm.value = true;
};

const openEdit = (station) => {
    editingStation.value = station;
    form.name = station.name;
    form.sort_order = station.sort_order ?? '';
    form.active = station.active;
    showForm.value = true;
};

const closeForm = () => {
    showForm.value = false;
    editingStation.value = null;
    form.reset();
};

const submit = () => {
    if (editingStation.value) {
        form.put(route('settings.kitchen-stations.update', editingStation.value.id), {
            onSuccess: closeForm,
        });
    } else {
        form.post(route('settings.kitchen-stations.store'), {
            onSuccess: closeForm,
        });
    }
};

const confirmDelete = (station) => {
    stationToDelete.value = station;
};

const deleteStation = () => {
    router.delete(route('settings.kitchen-stations.destroy', stationToDelete.value.id), {
        onSuccess: () => { stationToDelete.value = null; },
    });
};
</script>

<template>
    <SettingsLayout title="Kitchen Stations">
        <template #header>
            <div class="flex items-center justify-between">
                <h1 class="font-heading text-2xl font-bold text-ocean-deep">Kitchen Stations</h1>
                <AppButton @click="openCreate">Add Station</AppButton>
            </div>
        </template>

        <AppCard>
            <AppEmptyState
                v-if="!stations.length && !showForm"
                title="No kitchen stations yet"
                description="Add stations to organize your kitchen workflow."
            />

            <div v-if="stations.length" class="divide-y divide-muted">
                <div
                    v-for="station in stations"
                    :key="station.id"
                    class="flex items-center justify-between py-3"
                >
                    <div class="flex items-center gap-3">
                        <span class="font-body text-sm font-medium text-ocean-deep">{{ station.name }}</span>
                        <span
                            :class="station.active ? 'bg-accent/10 text-accent' : 'bg-muted text-muted-foreground'"
                            class="rounded-full px-2 py-0.5 text-xs font-semibold"
                        >
                            {{ station.active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                    <div class="flex gap-2">
                        <AppButton size="sm" variant="secondary" @click="openEdit(station)">Edit</AppButton>
                        <AppButton size="sm" variant="destructive" @click="confirmDelete(station)">Delete</AppButton>
                    </div>
                </div>
            </div>

            <div v-if="showForm" class="mt-4 rounded-lg border border-border p-4">
                <h3 class="mb-3 font-heading text-sm font-semibold text-ocean-deep">
                    {{ editingStation ? 'Edit Station' : 'New Station' }}
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
                        <label class="block text-sm font-medium text-ocean-deep mb-1">Sort Order</label>
                        <input
                            v-model="form.sort_order"
                            type="number"
                            min="0"
                            class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                        />
                    </div>

                    <label class="flex cursor-pointer items-center gap-3">
                        <input v-model="form.active" type="checkbox" class="h-4 w-4 rounded border-border text-primary focus:ring-primary" />
                        <span class="text-sm text-ocean-deep">Active</span>
                    </label>

                    <div class="flex gap-2 pt-1">
                        <AppButton type="submit" :loading="form.processing">Save</AppButton>
                        <AppButton type="button" variant="ghost" @click="closeForm">Cancel</AppButton>
                    </div>
                </form>
            </div>
        </AppCard>

        <AppConfirmModal
            :show="!!stationToDelete"
            title="Delete Kitchen Station"
            message="Are you sure you want to delete this station? This action cannot be undone."
            confirm-label="Delete"
            variant="destructive"
            @confirm="deleteStation"
            @cancel="stationToDelete = null"
        />
    </SettingsLayout>
</template>
