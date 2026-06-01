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
    <SettingsLayout :title="__('Kitchen Stations')">
        <template #header>
            <div class="flex items-center justify-between">
                <h1 class="font-heading text-2xl font-bold text-ocean-deep dark:text-gray-100">{{ __('Kitchen Stations') }}</h1>
                <AppButton @click="openCreate">{{ __('Add Station') }}</AppButton>
            </div>
        </template>

        <AppCard>
            <AppEmptyState
                v-if="!stations.length && !showForm"
                :title="__('No kitchen stations yet')"
                :description="__('Add stations to organize your kitchen workflow.')"
            />

            <div v-if="stations.length" class="divide-y divide-muted">
                <div
                    v-for="station in stations"
                    :key="station.id"
                    class="flex items-center justify-between py-3"
                >
                    <div class="flex items-center gap-3">
                        <span class="font-body text-sm font-medium text-ocean-deep dark:text-gray-100">{{ station.name }}</span>
                        <span
                            :class="station.active ? 'bg-accent/10 text-accent' : 'bg-muted text-muted-foreground'"
                            class="rounded-full px-2 py-0.5 text-xs font-semibold"
                        >
                            {{ station.active ? __('Active') : __('Inactive') }}
                        </span>
                    </div>
                    <div class="flex gap-2">
                        <AppButton size="sm" variant="secondary" @click="openEdit(station)">{{ __('Edit') }}</AppButton>
                        <AppButton size="sm" variant="destructive" @click="confirmDelete(station)">{{ __('Delete') }}</AppButton>
                    </div>
                </div>
            </div>

            <div v-if="showForm" class="mt-4 rounded-lg border border-border dark:border-gray-700 p-4">
                <h3 class="mb-3 font-heading text-sm font-semibold text-ocean-deep dark:text-gray-100">
                    {{ editingStation ? __('Edit Station') : __('New Station') }}
                </h3>
                <form @submit.prevent="submit" class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-ocean-deep dark:text-gray-100 mb-1">{{ __('Name') }} <span class="text-destructive">*</span></label>
                        <input
                            v-model="form.name"
                            type="text"
                            class="w-full rounded-md border border-border dark:border-gray-700 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-gray-100"
                        />
                        <p v-if="form.errors.name" class="mt-1 text-xs text-destructive">{{ form.errors.name }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-ocean-deep dark:text-gray-100 mb-1">{{ __('Sort Order') }}</label>
                        <input
                            v-model="form.sort_order"
                            type="number"
                            min="0"
                            class="w-full rounded-md border border-border dark:border-gray-700 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-gray-100"
                        />
                    </div>

                    <label class="flex cursor-pointer items-center gap-3">
                        <input v-model="form.active" type="checkbox" class="h-4 w-4 rounded border-border dark:border-gray-700 text-primary focus:ring-primary" />
                        <span class="text-sm text-ocean-deep dark:text-gray-100">{{ __('Active') }}</span>
                    </label>

                    <div class="flex gap-2 pt-1">
                        <AppButton type="submit" :loading="form.processing">{{ __('Save') }}</AppButton>
                        <AppButton type="button" variant="ghost" @click="closeForm">{{ __('Cancel') }}</AppButton>
                    </div>
                </form>
            </div>
        </AppCard>

        <AppConfirmModal
            :show="!!stationToDelete"
            :title="__('Delete Kitchen Station')"
            :message="__('Are you sure you want to delete this station? This action cannot be undone.')"
            :confirm-label="__('Delete')"
            variant="destructive"
            @confirm="deleteStation"
            @cancel="stationToDelete = null"
        />
    </SettingsLayout>
</template>
