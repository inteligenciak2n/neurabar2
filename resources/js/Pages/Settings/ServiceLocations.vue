<script setup>
import SettingsLayout from '@/Layouts/SettingsLayout.vue';
import SettingsSectionHeader from '@/Components/SettingsSectionHeader.vue';
import AppCard from '@/Components/AppCard.vue';
import AppButton from '@/Components/AppButton.vue';
import AppConfirmModal from '@/Components/AppConfirmModal.vue';
import AppEmptyState from '@/Components/AppEmptyState.vue';
import { useTranslate } from '@/Composables/useTranslate';
import { useAutosaveForm } from '@/Composables/useAutosaveForm';
import { useSettingsEditing } from '@/Composables/useSettingsEditing';
import { useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { toast } from 'vue-sonner';

defineOptions({ name: 'ServiceLocations' });

const __ = useTranslate();
const t = (key) => __(key, {}, 'ServiceLocations');
const { enableEditing } = useSettingsEditing();

const props = defineProps({
    locations: Array,
    locationTypes: Array,
    attendanceChannels: Array,
});

const showForm = ref(false);
const editingLocation = ref(null);
const locationToDelete = ref(null);
const generatingQrId = ref(null);

const form = useForm({
    name: '',
    type: 'table',
    active: true,
    default_attendance_channel_id: null,
});

const openCreate = () => {
    enableEditing();
    editingLocation.value = null;
    form.reset();
    form.type = 'table';
    form.active = true;
    form.default_attendance_channel_id = null;
    form.defaults();
    showForm.value = true;
};

const openEdit = (location) => {
    enableEditing();
    editingLocation.value = location;
    form.name = location.name;
    form.type = location.type;
    form.active = location.active;
    form.default_attendance_channel_id = location.default_attendance_channel_id ?? null;
    form.clearErrors();
    form.defaults();
    showForm.value = true;
};

const closeForm = () => {
    showForm.value = false;
    editingLocation.value = null;
    form.reset();
};

const persist = () => {
    const locationId = editingLocation.value?.id;
    if (!locationId || !form.isDirty || form.processing) {
        return;
    }

    form.put(route('settings.service-locations.update', locationId), {
        preserveScroll: true,
        onSuccess: () => form.defaults(),
    });
};

const submit = () => {
    if (editingLocation.value) {
        persist();
        return;
    }

    form.post(route('settings.service-locations.store'), {
        onSuccess: closeForm,
    });
};

useAutosaveForm(form, persist);

const confirmDelete = (location) => {
    locationToDelete.value = location;
};

const deleteLocation = () => {
    router.delete(route('settings.service-locations.destroy', locationToDelete.value.id), {
        onSuccess: () => { locationToDelete.value = null; },
    });
};

const generateQr = (location) => {
    generatingQrId.value = location.id;

    router.post(route('settings.service-locations.qr', location.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success(t('QR code generated.'));
            window.open(route('settings.service-locations.qr-pdf', location.id), '_blank', 'noopener,noreferrer');
        },
        onError: () => {
            toast.error(t('Could not generate QR code.'));
        },
        onFinish: () => {
            generatingQrId.value = null;
        },
    });
};
</script>

<template>
    <SettingsLayout :title="t('Service Locations')">
        <template #header>
            <SettingsSectionHeader :title="t('Service Locations')">
                <span class="block">{{ t('Tables, counters and areas') }}</span>
                <span class="block">{{ t('served by your venue') }}</span>
                <template #actions>
                    <AppButton @click="openCreate">{{ t('Add Location') }}</AppButton>
                </template>
            </SettingsSectionHeader>
        </template>

        <AppCard>
            <AppEmptyState
                v-if="!locations.length && !showForm"
                :title="t('No service locations yet')"
                :description="t('Add locations such as tables, bars, or areas.')"
            />

            <div v-if="locations.length && !showForm" class="divide-y divide-muted">
                <div
                    v-for="location in locations"
                    :key="location.id"
                    class="flex items-center justify-between py-3"
                >
                    <div class="flex items-center gap-3">
                        <span class="font-body text-sm font-medium text-ocean-deep dark:text-gray-100">{{ location.name }}</span>
                        <span class="rounded-full bg-muted px-2 py-0.5 text-xs font-semibold text-muted-foreground capitalize">
                            {{ location.type }}
                        </span>
                        <span
                            :class="location.active ? 'bg-accent/10 text-accent' : 'bg-muted text-muted-foreground'"
                            class="rounded-full px-2 py-0.5 text-xs font-semibold"
                        >
                            {{ location.active ? t('Active') : t('Inactive') }}
                        </span>
                        <span v-if="location.default_attendance_channel" class="rounded-full bg-primary/10 text-primary px-2 py-0.5 text-xs font-semibold">
                            {{ location.default_attendance_channel.name }}
                        </span>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <a
                            v-if="location.hub_url"
                            :href="location.hub_url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="text-sm font-medium text-primary hover:underline"
                        >
                            {{ t('View Hub') }}
                        </a>
                        <a
                            v-if="location.qr_token"
                            :href="route('settings.service-locations.qr-pdf', location.id)"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center justify-center rounded bg-muted px-3 py-1.5 text-sm font-medium text-ocean-deep transition-colors hover:bg-sand dark:bg-gray-700 dark:text-gray-100 dark:hover:bg-gray-600"
                        >
                            {{ t('PDF QR') }}
                        </a>
                        <AppButton
                            size="sm"
                            variant="secondary"
                            :loading="generatingQrId === location.id"
                            @click="generateQr(location)"
                        >
                            {{ location.qr_token ? t('Regenerate QR') : t('Generate QR') }}
                        </AppButton>
                        <AppButton size="sm" variant="secondary" data-enable-editing @click="openEdit(location)">{{ t('Edit') }}</AppButton>
                        <AppButton size="sm" variant="destructive" @click="confirmDelete(location)">{{ t('Delete') }}</AppButton>
                    </div>
                </div>
            </div>

            <div v-if="showForm" class="mt-4 rounded-lg border border-border dark:border-gray-700 p-4">
                <h3 class="mb-3 font-heading text-sm font-semibold text-ocean-deep dark:text-gray-100">
                    {{ editingLocation ? t('Edit Location') : t('New Location') }}
                </h3>
                <form @submit.prevent="submit" class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-ocean-deep dark:text-gray-100 mb-1">{{ t('Name') }} <span class="text-destructive">*</span></label>
                        <input
                            v-model="form.name"
                            type="text"
                            class="w-full rounded-md border border-border dark:border-gray-700 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-gray-100"
                        />
                        <p v-if="form.errors.name" class="mt-1 text-xs text-destructive">{{ form.errors.name }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-ocean-deep dark:text-gray-100 mb-1">{{ t('Type') }} <span class="text-destructive">*</span></label>
                        <select
                            v-model="form.type"
                            class="w-full rounded-md border border-border dark:border-gray-700 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-gray-100"
                        >
                            <option v-for="type in locationTypes" :key="type" :value="type" class="capitalize">
                                {{ t(type) }}
                            </option>
                        </select>
                        <p v-if="form.errors.type" class="mt-1 text-xs text-destructive">{{ form.errors.type }}</p>
                    </div>

                    <div v-if="attendanceChannels.length">
                        <label class="block text-sm font-medium text-ocean-deep dark:text-gray-100 mb-1">{{ t('Default Channel') }}</label>
                        <select
                            v-model="form.default_attendance_channel_id"
                            class="w-full rounded-md border border-border dark:border-gray-700 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-gray-100"
                        >
                            <option :value="null">{{ t('— None —') }}</option>
                            <option v-for="channel in attendanceChannels" :key="channel.id" :value="channel.id">
                                {{ channel.name }}
                            </option>
                        </select>
                        <p v-if="form.errors.default_attendance_channel_id" class="mt-1 text-xs text-destructive">{{ form.errors.default_attendance_channel_id }}</p>
                    </div>

                    <label class="flex cursor-pointer items-center gap-3">
                        <input v-model="form.active" type="checkbox" class="h-4 w-4 rounded border-border dark:border-gray-700 text-primary focus:ring-primary" />
                        <span class="text-sm text-ocean-deep dark:text-gray-100">{{ t('Active') }}</span>
                    </label>

                    <div class="flex gap-2 pt-1">
                        <AppButton v-if="!editingLocation" type="submit" :loading="form.processing">{{ t('Save') }}</AppButton>
                        <AppButton type="button" variant="ghost" @click="closeForm">{{ t('Cancel') }}</AppButton>
                    </div>
                </form>
            </div>
        </AppCard>

        <AppConfirmModal
            :show="!!locationToDelete"
            :title="t('Delete Service Location')"
            :message="t('Are you sure you want to delete this location?')"
            :confirm-label="t('Delete')"
            variant="destructive"
            @confirm="deleteLocation"
            @cancel="locationToDelete = null"
        />
    </SettingsLayout>
</template>
