<script setup>
import SettingsLayout from '@/Layouts/SettingsLayout.vue';
import SettingsSectionHeader from '@/Components/SettingsSectionHeader.vue';
import AppCard from '@/Components/AppCard.vue';
import AppButton from '@/Components/AppButton.vue';
import AppConfirmModal from '@/Components/AppConfirmModal.vue';
import AppEmptyState from '@/Components/AppEmptyState.vue';
import { useForm, router } from '@inertiajs/vue3';
import { computed, nextTick, ref } from 'vue';
import { toast } from 'vue-sonner';
import { useAutosaveForm } from '@/Composables/useAutosaveForm';
import { useSettingsEditing } from '@/Composables/useSettingsEditing';
import { useTranslate } from '@/Composables/useTranslate';
import { formatPrefixLabel, groupTabsForDisplay, prefixFromCode, tabSeriesDomId } from '@/Utils/tabRows';

const props = defineProps({
    channels: Array,
    tabSeries: Array,
    serviceLocations: Array,
});

const __ = useTranslate();
const { enableEditing } = useSettingsEditing();

const showForm = ref(false);
const editingChannel = ref(null);
const channelToDelete = ref(null);
const locationToDelete = ref(null);
const channelLocationsToDelete = ref(null);
const tabToDelete = ref(null);
const seriesToDelete = ref(null);

const form = useForm({
    name: '',
    description: '',
    is_trackable: true,
    requires_customer_identifier: false,
    requires_tab: false,
    active: true,
    sort_order: 0,
});

const tabForm = useForm({
    code: '',
    notes: '',
});

const rangeForm = useForm({
    prefix: '',
    from: 1,
    to: 100,
    notes: '',
});

const locationNumber = ref(1);
const locationForm = useForm({
    from: 1,
    to: 100,
    default_attendance_channel_id: null,
});

const anyChannelUsesTabs = computed(() =>
    (props.channels ?? []).some((channel) => channel.requires_tab),
);

const channelLocations = computed(() =>
    (props.serviceLocations ?? []).filter(
        (location) => location.default_attendance_channel_id === editingChannel.value?.id,
    ),
);

const resetLocationForm = () => {
    locationForm.reset();
    locationForm.from = 1;
    locationForm.to = 100;
    locationForm.default_attendance_channel_id = editingChannel.value?.id ?? null;
    locationNumber.value = 1;
    locationForm.clearErrors();
};

const openCreate = () => {
    enableEditing();
    editingChannel.value = null;
    form.reset();
    form.description = '';
    form.is_trackable = true;
    form.requires_customer_identifier = false;
    form.requires_tab = false;
    form.active = true;
    form.sort_order = 0;
    form.defaults();
    showForm.value = true;
    resetLocationForm();
};

const openEdit = (channel) => {
    enableEditing();
    editingChannel.value = channel;
    form.name = channel.name;
    form.description = channel.description ?? '';
    form.is_trackable = channel.is_trackable;
    form.requires_customer_identifier = channel.requires_customer_identifier;
    form.requires_tab = channel.requires_tab;
    form.active = channel.active;
    form.sort_order = channel.sort_order;
    form.clearErrors();
    form.defaults();
    showForm.value = true;
    resetLocationForm();
};

const closeForm = () => {
    showForm.value = false;
    editingChannel.value = null;
    form.reset();
    resetLocationForm();
};

const persist = () => {
    const channelId = editingChannel.value?.id;
    if (!channelId || !form.isDirty || form.processing) {
        return;
    }

    form.put(route('settings.attendance-channels.update', channelId), {
        preserveScroll: true,
        onSuccess: () => form.defaults(),
    });
};

const submit = () => {
    if (editingChannel.value) {
        persist();
        return;
    }

    form.post(route('settings.attendance-channels.store'), {
        onSuccess: closeForm,
    });
};

useAutosaveForm(form, persist);

const confirmDelete = (channel) => {
    channelToDelete.value = channel;
};

const deleteChannel = () => {
    router.delete(route('settings.attendance-channels.destroy', channelToDelete.value.id), {
        onSuccess: () => { channelToDelete.value = null; },
    });
};

const postLocationRange = (from, to) => {
    if (!editingChannel.value) {
        return;
    }

    enableEditing();
    const previousFrom = locationForm.from;
    const previousTo = locationForm.to;
    locationForm.default_attendance_channel_id = editingChannel.value.id;
    locationForm.from = from;
    locationForm.to = to;
    locationForm.post(route('settings.service-locations.range'), {
        preserveScroll: true,
        onSuccess: () => {
            toast.success(__('Locations created successfully'));
            locationForm.reset();
            locationForm.from = 1;
            locationForm.to = 100;
            locationForm.default_attendance_channel_id = editingChannel.value.id;
            locationNumber.value = Number(to) + 1;
        },
        onError: () => {
            locationForm.from = previousFrom;
            locationForm.to = previousTo;
        },
    });
};

const addOneLocation = () => {
    postLocationRange(locationNumber.value, locationNumber.value);
};

const addLocationRange = () => {
    postLocationRange(locationForm.from, locationForm.to);
};

const confirmDeleteLocation = (location) => {
    enableEditing();
    locationToDelete.value = location;
};

const deleteLocation = () => {
    if (!locationToDelete.value) {
        return;
    }

    router.delete(route('settings.service-locations.destroy', locationToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => { locationToDelete.value = null; },
        onError: (errors) => {
            toast.error(__(errors.location || 'Could not delete this location.'));
        },
    });
};

const confirmDeleteChannelLocations = () => {
    enableEditing();
    channelLocationsToDelete.value = editingChannel.value;
};

const deleteChannelLocations = () => {
    if (!channelLocationsToDelete.value) {
        return;
    }

    router.delete(route('settings.attendance-channels.locations.destroy', channelLocationsToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => { channelLocationsToDelete.value = null; },
        onError: (errors) => {
            toast.error(__(errors.locations || 'Could not delete these locations.'));
        },
    });
};

const revealCreatedSeries = (prefix) => {
    toast.success(__('Tabs created successfully'));

    nextTick(() => {
        document.getElementById(tabSeriesDomId(prefix))?.scrollIntoView({
            behavior: 'smooth',
            block: 'start',
        });
    });
};

const addTab = () => {
    enableEditing();
    const prefix = prefixFromCode(tabForm.code);
    tabForm.post(route('settings.tabs.store'), {
        preserveScroll: true,
        onSuccess: () => {
            const notes = tabForm.notes;
            tabForm.reset();
            tabForm.notes = notes;
            revealCreatedSeries(prefix);
        },
    });
};

const addTabRange = () => {
    enableEditing();
    const prefix = rangeForm.prefix;
    rangeForm.post(route('settings.tabs.range'), {
        preserveScroll: true,
        onSuccess: () => {
            const notes = rangeForm.notes;
            rangeForm.reset();
            rangeForm.prefix = prefix;
            rangeForm.from = 1;
            rangeForm.to = 100;
            rangeForm.notes = notes;
            revealCreatedSeries(prefix);
        },
    });
};

const saveSeriesNotes = (series, notes) => {
    if ((series.notes ?? '') === (notes ?? '')) {
        return;
    }

    router.put(route('settings.tab-series.update', series.id), { notes: notes || null }, {
        preserveScroll: true,
    });
};

const confirmDeleteTab = (tab) => {
    tabToDelete.value = tab;
};

const deleteTab = () => {
    if (!tabToDelete.value) {
        return;
    }

    router.delete(route('settings.tabs.destroy', tabToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => { tabToDelete.value = null; },
    });
};

const confirmDeleteSeries = (series) => {
    enableEditing();
    seriesToDelete.value = series;
};

const deleteSeries = () => {
    if (!seriesToDelete.value) {
        return;
    }

    router.delete(route('settings.tab-series.destroy', seriesToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => { seriesToDelete.value = null; },
        onError: (errors) => {
            toast.error(__(errors.tabs || 'Could not delete these tabs.'));
        },
    });
};

const seriesTabRows = (series) => groupTabsForDisplay(series?.tabs ?? []);
</script>

<template>
    <SettingsLayout :title="__('Attendance Channels')" :nested="showForm" @close-nested="closeForm">
        <template #header>
            <SettingsSectionHeader :title="__('Attendance Channels')">
                <span class="block">{{ __('Choose how the customer is served') }}</span>
                <span class="block">{{ __('Table, counter, delivery or pickup') }}</span>
                <template #actions>
                    <AppButton @click="openCreate">{{ __('Add Channel') }}</AppButton>
                </template>
            </SettingsSectionHeader>
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
                    <div class="flex min-w-0 flex-1 items-center gap-3 pr-4">
                        <span class="font-body text-sm font-medium text-ocean-deep dark:text-gray-100">{{ channel.name }}</span>
                        <span
                            :class="channel.active ? 'bg-accent/10 text-accent' : 'bg-muted text-muted-foreground'"
                            class="shrink-0 rounded-full px-2 py-0.5 text-xs font-semibold"
                        >
                            {{ channel.active ? __('Active') : __('Inactive') }}
                        </span>
                        <span v-if="channel.is_trackable" class="shrink-0 rounded-full bg-blue-50 px-2 py-0.5 text-xs font-semibold text-blue-600">{{ __('Trackable') }}</span>
                        <span v-if="channel.requires_customer_identifier" class="shrink-0 rounded-full bg-yellow-50 px-2 py-0.5 text-xs font-semibold text-yellow-700">{{ __('Requires Identifier') }}</span>
                        <span v-if="channel.requires_tab" class="shrink-0 rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700">{{ __('Requires Tab') }}</span>
                        <span v-if="channel.description" class="min-w-0 truncate text-sm text-muted-foreground dark:text-gray-400">{{ channel.description }}</span>
                    </div>
                    <div class="flex gap-2">
                        <AppButton size="sm" variant="secondary" data-enable-editing @click="openEdit(channel)">{{ __('Edit') }}</AppButton>
                        <AppButton size="sm" variant="destructive" @click="confirmDelete(channel)">{{ __('Delete') }}</AppButton>
                    </div>
                </div>
            </div>

            <div v-if="showForm" class="mt-4 rounded-lg border border-border dark:border-gray-700 p-4">
                <h3 class="mb-3 font-heading text-lg font-bold text-ocean-deep dark:text-gray-100">
                    {{ editingChannel ? __('Edit Channel') : __('New Channel') }}
                </h3>
                <form class="space-y-3" @submit.prevent="submit">
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div class="space-y-3">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-ocean-deep dark:text-gray-100">{{ __('Name') }} <span class="text-destructive">*</span></label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    :placeholder="__('e.g. Counter')"
                                    :disabled="!!editingChannel"
                                    :readonly="!!editingChannel"
                                    class="w-full rounded-md border border-border dark:border-gray-700 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary disabled:cursor-not-allowed disabled:bg-muted disabled:text-muted-foreground dark:bg-gray-700 dark:text-gray-100"
                                />
                                <p v-if="editingChannel" class="mt-1 text-xs text-muted-foreground dark:text-gray-400">
                                    {{ __('The channel name cannot be changed after it is created.') }}
                                </p>
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

                            <label class="flex cursor-pointer items-start gap-3">
                                <input v-model="form.is_trackable" type="checkbox" class="mt-0.5 h-4 w-4 rounded border-border dark:border-gray-700 text-primary focus:ring-primary" />
                                <span>
                                    <span class="block text-sm text-ocean-deep dark:text-gray-100">{{ __('Trackable by customer') }}</span>
                                    <span class="mt-0.5 block text-xs italic text-muted-foreground dark:text-gray-400">{{ __('The customer can check the preparation status of the order.') }}</span>
                                </span>
                            </label>

                            <label class="flex cursor-pointer items-center gap-3">
                                <input v-model="form.active" type="checkbox" class="h-4 w-4 rounded border-border dark:border-gray-700 text-primary focus:ring-primary" />
                                <span class="text-sm text-ocean-deep dark:text-gray-100">{{ __('Active') }}</span>
                            </label>

                            <label class="flex cursor-pointer items-center gap-3">
                                <input v-model="form.requires_customer_identifier" type="checkbox" class="h-4 w-4 rounded border-border dark:border-gray-700 text-primary focus:ring-primary" />
                                <span class="text-sm text-ocean-deep dark:text-gray-100">{{ __('Requires customer identifier') }}</span>
                            </label>

                            <label class="flex cursor-pointer items-start gap-3">
                                <input v-model="form.requires_tab" type="checkbox" class="mt-0.5 h-4 w-4 rounded border-border dark:border-gray-700 text-primary focus:ring-primary" />
                                <span>
                                    <span class="block text-sm text-ocean-deep dark:text-gray-100">{{ __('Requires tab') }}</span>
                                    <span class="mt-0.5 block text-xs italic text-muted-foreground dark:text-gray-400">{{ __('Each attendance must pick a registered tab. The same tabs are shared by every channel that uses them.') }}</span>
                                </span>
                            </label>
                        </div>

                        <div class="space-y-3">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-ocean-deep dark:text-gray-100">{{ __('Description') }}</label>
                                <textarea
                                    v-model="form.description"
                                    rows="8"
                                    class="w-full rounded-md border border-border dark:border-gray-700 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-gray-100"
                                />
                                <p v-if="form.errors.description" class="mt-1 text-xs text-destructive">{{ form.errors.description }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-2 pt-1">
                        <AppButton v-if="!editingChannel" type="submit" :loading="form.processing">{{ __('Save') }}</AppButton>
                        <AppButton type="button" variant="ghost" @click="closeForm">{{ __('Cancel') }}</AppButton>
                    </div>
                </form>

                <div v-if="editingChannel" class="mt-6 border-t border-border pt-4 dark:border-gray-700">
                    <h3 class="mb-3 font-heading text-lg font-bold text-ocean-deep dark:text-gray-100">
                        {{ __('Add') }} {{ form.name }}
                    </h3>

                    <div class="rounded-lg border border-border p-4 dark:border-gray-700">
                        <h4 class="mb-3 font-heading text-base font-bold text-ocean-deep dark:text-gray-100">{{ __('Create one') }}</h4>
                        <div class="flex flex-wrap items-end gap-2">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-ocean-deep dark:text-gray-100">{{ __('Number') }}</label>
                                <input
                                    v-model="locationNumber"
                                    type="number"
                                    min="1"
                                    class="w-24 rounded-md border border-border dark:border-gray-700 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-gray-100"
                                />
                            </div>
                            <AppButton type="button" data-enable-editing :loading="locationForm.processing" @click="addOneLocation">{{ __('Add') }}</AppButton>
                        </div>
                    </div>

                    <div class="mt-4 rounded-lg border border-border p-4 dark:border-gray-700">
                        <h4 class="mb-3 font-heading text-base font-bold text-ocean-deep dark:text-gray-100">{{ __('Create several') }}</h4>
                        <div class="flex flex-wrap items-end gap-2">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-ocean-deep dark:text-gray-100">{{ __('From') }}</label>
                                <input
                                    v-model="locationForm.from"
                                    type="number"
                                    min="1"
                                    class="w-24 rounded-md border border-border dark:border-gray-700 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-gray-100"
                                />
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-ocean-deep dark:text-gray-100">{{ __('To') }}</label>
                                <input
                                    v-model="locationForm.to"
                                    type="number"
                                    min="1"
                                    class="w-24 rounded-md border border-border dark:border-gray-700 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-gray-100"
                                />
                            </div>
                            <AppButton type="button" variant="secondary" data-enable-editing :loading="locationForm.processing" @click="addLocationRange">{{ __('Create range') }}</AppButton>
                        </div>
                        <p v-if="locationForm.errors.from" class="mt-2 text-xs text-destructive">{{ __(locationForm.errors.from) }}</p>
                        <p v-if="locationForm.errors.to" class="mt-2 text-xs text-destructive">{{ __(locationForm.errors.to) }}</p>
                    </div>

                    <div v-if="channelLocations.length" class="mt-4">
                        <div class="mb-3 flex justify-end">
                            <AppButton
                                type="button"
                                size="sm"
                                variant="destructive"
                                data-enable-editing
                                class="shrink-0"
                                @click="confirmDeleteChannelLocations"
                            >
                                {{ __('Delete all') }}
                            </AppButton>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <span
                                v-for="location in channelLocations"
                                :key="location.id"
                                class="inline-flex min-w-0 items-center justify-center gap-1 rounded-full bg-muted px-2 py-0.5 text-xs font-semibold text-ocean-deep dark:bg-gray-700 dark:text-gray-100"
                            >
                                {{ location.name }}
                                <button
                                    type="button"
                                    data-enable-editing
                                    class="text-muted-foreground hover:text-destructive"
                                    :aria-label="__('Delete location')"
                                    @click="confirmDeleteLocation(location)"
                                >
                                    ×
                                </button>
                            </span>
                        </div>
                    </div>
                    <p v-else class="mt-4 text-xs text-muted-foreground dark:text-gray-400">{{ __('No locations yet.') }}</p>
                </div>
            </div>
        </AppCard>

        <div v-if="anyChannelUsesTabs && !showForm" class="mt-8">
            <div class="flex min-w-0 items-center gap-3">
                <h2 class="shrink-0 font-heading text-2xl font-bold text-ocean-deep dark:text-gray-100">{{ __('Tabs') }}</h2>
                <p class="text-sm leading-snug text-muted-foreground dark:text-gray-400">
                    <span class="block">{{ __('All channels that require a tab share this list.') }}</span>
                    <span class="block">{{ __('Use a prefix before the numbers to organize internally.') }}</span>
                </p>
            </div>

            <AppCard class="mt-6" :title="__('Create a new tab number')" title-class="mb-4 font-heading text-lg font-bold text-ocean-deep dark:text-gray-100">
                <p class="mb-4 text-xs text-muted-foreground dark:text-gray-400">
                    {{ __('Each tab code can be used only once.') }}
                </p>
                <div class="rounded-lg border border-border p-4 dark:border-gray-700">
                    <h3 class="mb-3 font-heading text-lg font-bold text-ocean-deep dark:text-gray-100">{{ __('Create a single tab') }}</h3>
                    <form class="space-y-3" @submit.prevent="addTab">
                        <div class="flex flex-wrap items-end gap-2">
                            <div class="min-w-[10rem] flex-1">
                                <input
                                    v-model="tabForm.code"
                                    type="text"
                                    maxlength="20"
                                    :placeholder="__('e.g. 15 or A1')"
                                    :aria-label="__('Create a single tab')"
                                    class="w-full rounded-md border border-border dark:border-gray-700 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-gray-100"
                                />
                                <p v-if="tabForm.errors.code" class="mt-1 text-xs text-destructive">{{ __(tabForm.errors.code) }}</p>
                            </div>
                            <AppButton type="submit" data-enable-editing :loading="tabForm.processing">{{ __('Add tab') }}</AppButton>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-ocean-deep dark:text-gray-100">{{ __('Observation') }}</label>
                            <textarea
                                v-model="tabForm.notes"
                                rows="2"
                                :placeholder="__('Optional description for this prefix')"
                                class="w-full rounded-md border border-border dark:border-gray-700 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-gray-100"
                            />
                            <p v-if="tabForm.errors.notes" class="mt-1 text-xs text-destructive">{{ tabForm.errors.notes }}</p>
                        </div>
                    </form>
                </div>

                <div class="mt-4 rounded-lg border border-border p-4 dark:border-gray-700">
                    <h3 class="mb-3 font-heading text-lg font-bold text-ocean-deep dark:text-gray-100">{{ __('Create several tabs') }}</h3>
                    <form class="space-y-3" @submit.prevent="addTabRange">
                        <div class="flex flex-wrap items-end gap-2">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-ocean-deep dark:text-gray-100">{{ __('Prefix') }}</label>
                                <input
                                    v-model="rangeForm.prefix"
                                    type="text"
                                    maxlength="10"
                                    :placeholder="__('e.g. A or CASA1')"
                                    class="w-40 rounded-md border border-border dark:border-gray-700 px-3 py-2 text-sm uppercase focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-gray-100"
                                />
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-ocean-deep dark:text-gray-100">{{ __('From') }}</label>
                                <input
                                    v-model="rangeForm.from"
                                    type="number"
                                    min="1"
                                    class="w-24 rounded-md border border-border dark:border-gray-700 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-gray-100"
                                />
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-ocean-deep dark:text-gray-100">{{ __('To') }}</label>
                                <input
                                    v-model="rangeForm.to"
                                    type="number"
                                    min="1"
                                    class="w-24 rounded-md border border-border dark:border-gray-700 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-gray-100"
                                />
                            </div>
                            <AppButton type="submit" variant="secondary" data-enable-editing :loading="rangeForm.processing">{{ __('Create range') }}</AppButton>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-ocean-deep dark:text-gray-100">{{ __('Observation') }}</label>
                            <textarea
                                v-model="rangeForm.notes"
                                rows="2"
                                :placeholder="__('Optional description for this prefix')"
                                class="w-full rounded-md border border-border dark:border-gray-700 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-gray-100"
                            />
                        </div>
                        <p v-if="rangeForm.errors.prefix" class="text-xs text-destructive">{{ __(rangeForm.errors.prefix) }}</p>
                        <p v-if="rangeForm.errors.from" class="text-xs text-destructive">{{ __(rangeForm.errors.from) }}</p>
                        <p v-if="rangeForm.errors.to" class="text-xs text-destructive">{{ __(rangeForm.errors.to) }}</p>
                    </form>
                </div>
            </AppCard>

            <AppCard class="mt-6" :title="__('Created tab numbers')" title-class="mb-4 font-heading text-lg font-bold text-ocean-deep dark:text-gray-100">
                <div v-if="tabSeries?.length" class="space-y-4">
                <div
                    v-for="series in tabSeries"
                    :key="series.id"
                    :id="tabSeriesDomId(series.prefix)"
                    class="scroll-mt-24 rounded-md border border-border p-3 dark:border-gray-700"
                >
                    <h5 v-if="series.prefix" class="mb-2 font-heading text-base font-bold text-ocean-deep dark:text-gray-100">
                        {{ `${__('Prefix')} ${formatPrefixLabel(series.prefix)}` }}
                    </h5>
                    <div class="mb-3 flex flex-wrap items-start gap-2">
                        <textarea
                            :value="series.notes ?? ''"
                            rows="2"
                            :placeholder="__('Optional description for this prefix')"
                            class="min-w-[12rem] flex-1 rounded-md border border-border dark:border-gray-700 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-gray-100"
                            @blur="saveSeriesNotes(series, $event.target.value)"
                        />
                        <AppButton
                            type="button"
                            size="sm"
                            variant="destructive"
                            data-enable-editing
                            class="shrink-0"
                            @click="confirmDeleteSeries(series)"
                        >
                            {{ __('Delete all') }}
                        </AppButton>
                    </div>
                    <div v-if="series.tabs?.length" class="max-h-56 overflow-y-auto">
                        <div class="space-y-2">
                            <div
                                v-for="(row, rowIndex) in seriesTabRows(series)"
                                :key="rowIndex"
                                class="grid grid-cols-5 gap-2 sm:grid-cols-10"
                            >
                                <span
                                    v-for="tab in row"
                                    :key="tab.id"
                                    class="inline-flex min-w-0 items-center justify-center gap-1 rounded-full bg-muted px-2 py-0.5 text-xs font-semibold text-ocean-deep dark:bg-gray-700 dark:text-gray-100"
                                >
                                    {{ tab.code }}
                                    <button
                                        type="button"
                                        data-enable-editing
                                        class="text-muted-foreground hover:text-destructive"
                                        :aria-label="__('Delete tab')"
                                        @click="confirmDeleteTab(tab)"
                                    >
                                        ×
                                    </button>
                                </span>
                            </div>
                        </div>
                    </div>
                    <p v-else class="text-xs text-muted-foreground dark:text-gray-400">{{ __('No tabs registered yet.') }}</p>
                </div>
            </div>
                <p v-else class="text-xs text-muted-foreground dark:text-gray-400">{{ __('No tabs registered yet.') }}</p>
            </AppCard>
        </div>

        <AppConfirmModal
            :show="!!channelToDelete"
            :title="__('Delete Attendance Channel')"
            :message="__('Are you sure you want to delete this channel?')"
            :confirm-label="__('Delete')"
            variant="destructive"
            @confirm="deleteChannel"
            @cancel="channelToDelete = null"
        />

        <AppConfirmModal
            :show="!!locationToDelete"
            :title="__('Delete location')"
            :message="__('Are you sure you want to delete this location?')"
            :confirm-label="__('Delete')"
            variant="destructive"
            @confirm="deleteLocation"
            @cancel="locationToDelete = null"
        />

        <AppConfirmModal
            :show="!!channelLocationsToDelete"
            :title="__('Delete all')"
            :message="__('Are you sure you want to delete all locations for this channel?')"
            :confirm-label="__('Delete all')"
            variant="destructive"
            @confirm="deleteChannelLocations"
            @cancel="channelLocationsToDelete = null"
        />

        <AppConfirmModal
            :show="!!tabToDelete"
            :title="__('Delete tab')"
            :message="__('Are you sure you want to delete this tab?')"
            :confirm-label="__('Delete')"
            variant="destructive"
            @confirm="deleteTab"
            @cancel="tabToDelete = null"
        />

        <AppConfirmModal
            :show="!!seriesToDelete"
            :title="__('Delete all')"
            :message="__('Are you sure you want to delete all tabs in this prefix?')"
            :confirm-label="__('Delete all')"
            variant="destructive"
            @confirm="deleteSeries"
            @cancel="seriesToDelete = null"
        />
    </SettingsLayout>
</template>
