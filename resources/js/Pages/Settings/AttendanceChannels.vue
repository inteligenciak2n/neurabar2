<script setup>
import SettingsLayout from '@/Layouts/SettingsLayout.vue';
import SettingsSectionHeader from '@/Components/SettingsSectionHeader.vue';
import AppButton from '@/Components/AppButton.vue';
import AppConfirmModal from '@/Components/AppConfirmModal.vue';
import AppEmptyState from '@/Components/AppEmptyState.vue';
import Modal from '@/Components/Modal.vue';
import { useForm, router } from '@inertiajs/vue3';
import { computed, nextTick, ref } from 'vue';
import { toast } from 'vue-sonner';
import { useAutosaveForm } from '@/Composables/useAutosaveForm';
import { useSettingsEditing } from '@/Composables/useSettingsEditing';
import { useTranslate } from '@/Composables/useTranslate';

const CHANNEL_COLORS = [
    '#293b4f',
    '#2563eb',
    '#ea580c',
    '#16a34a',
    '#7c3aed',
    '#dc2626',
    '#0d9488',
    '#db2777',
];
const DEFAULT_CHANNEL_COLOR = CHANNEL_COLORS[0];

const props = defineProps({
    channels: Array,
    serviceLocations: Array,
    locationCreateResult: Object,
    channelColors: Array,
});

const __ = useTranslate();
const { enableEditing } = useSettingsEditing();

const showForm = ref(false);
const editingChannel = ref(null);
const channelToDelete = ref(null);
const locationToDelete = ref(null);
const generatingQrId = ref(null);
const qrLocation = ref(null);
const qrPreviewKey = ref(0);
const channelLocationsToDelete = ref(null);

const form = useForm({
    name: '',
    description: '',
    color: DEFAULT_CHANNEL_COLOR,
    is_trackable: true,
    requires_customer_identifier: false,
    requires_tab: false,
    uses_service_location: true,
    active: true,
    sort_order: 0,
});

const palette = computed(() => (props.channelColors?.length ? props.channelColors : CHANNEL_COLORS));

const resolveChannelColor = (color) => {
    const hex = String(color ?? '').toLowerCase();

    return CHANNEL_COLORS.includes(hex) ? hex : DEFAULT_CHANNEL_COLOR;
};

const isChannelColorSelected = (color) => String(form.color ?? '').toLowerCase() === String(color).toLowerCase();

const channelColor = (color) => color || DEFAULT_CHANNEL_COLOR;

const hexToRgba = (hex, alpha) => {
    const value = String(hex ?? '').replace('#', '');
    if (!/^[0-9A-Fa-f]{6}$/.test(value)) {
        return null;
    }

    const r = Number.parseInt(value.slice(0, 2), 16);
    const g = Number.parseInt(value.slice(2, 4), 16);
    const b = Number.parseInt(value.slice(4, 6), 16);

    return `rgba(${r}, ${g}, ${b}, ${alpha})`;
};

const channelSectionStyle = (color) => {
    const hex = channelColor(color);

    return {
        borderColor: hex,
        backgroundColor: hexToRgba(hex, 0.08) || undefined,
    };
};

const channelNameStyle = (color) => (color ? { color } : undefined);

const locationNumber = ref(1);
const locationResult = ref(null);
const locationForm = useForm({
    from: 1,
    to: 100,
    default_attendance_channel_id: null,
    source: 'several',
});

const channelLocations = computed(() =>
    (props.serviceLocations ?? [])
        .filter((location) => location.default_attendance_channel_id === editingChannel.value?.id)
        .slice()
        .sort((a, b) => String(a.name ?? '').localeCompare(String(b.name ?? ''), undefined, {
            numeric: true,
            sensitivity: 'base',
        })),
);

const isPositiveInteger = (value) => {
    const number = Number(value);

    return Number.isInteger(number) && number >= 1;
};

const locationNumberIsValid = computed(() => isPositiveInteger(locationNumber.value));
const locationRangeIsValid = computed(() => (
    isPositiveInteger(locationForm.from)
    && isPositiveInteger(locationForm.to)
    && Number(locationForm.to) >= Number(locationForm.from)
));

const locationAlreadyExistsMessage = computed(() => __(
    'The ":name" number already exists.',
    { name: form.name || '' },
));

const formatLocationNames = (names) => (names ?? []).join(', ');

const singleLocationResult = computed(() => (
    locationResult.value?.source === 'one' && locationResult.value?.skipped_names?.length
        ? locationResult.value
        : null
));

const severalLocationResult = computed(() => (
    locationResult.value?.source === 'several' && locationResult.value?.skipped_names?.length
        ? locationResult.value
        : null
));

const resetLocationForm = () => {
    locationForm.reset();
    locationForm.from = 1;
    locationForm.to = 100;
    locationForm.default_attendance_channel_id = editingChannel.value?.id ?? null;
    locationForm.source = 'several';
    locationNumber.value = 1;
    locationResult.value = null;
    locationForm.clearErrors();
};

const openEdit = (channel) => {
    enableEditing();
    editingChannel.value = channel;
    form.name = channel.name;
    form.description = channel.description ?? '';
    form.color = resolveChannelColor(channel.color);
    form.is_trackable = channel.is_trackable;
    form.requires_customer_identifier = channel.requires_customer_identifier;
    form.requires_tab = channel.requires_tab;
    form.uses_service_location = channel.uses_service_location !== false;
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

const moveUp = (index) => {
    if (index === 0) {
        return;
    }

    const ids = (props.channels ?? []).map((channel) => channel.id);
    [ids[index - 1], ids[index]] = [ids[index], ids[index - 1]];
    router.post(route('settings.attendance-channels.reorder'), { ids }, { preserveScroll: true });
};

const moveDown = (index) => {
    const list = props.channels ?? [];
    if (index === list.length - 1) {
        return;
    }

    const ids = list.map((channel) => channel.id);
    [ids[index], ids[index + 1]] = [ids[index + 1], ids[index]];
    router.post(route('settings.attendance-channels.reorder'), { ids }, { preserveScroll: true });
};

const confirmDelete = (channel) => {
    channelToDelete.value = channel;
};

const deleteChannel = () => {
    router.delete(route('settings.attendance-channels.destroy', channelToDelete.value.id), {
        onSuccess: () => { channelToDelete.value = null; },
    });
};

const postLocationRange = (from, to, source) => {
    if (!editingChannel.value) {
        return;
    }

    enableEditing();
    const previousFrom = locationForm.from;
    const previousTo = locationForm.to;
    locationResult.value = null;
    locationForm.default_attendance_channel_id = editingChannel.value.id;
    locationForm.from = from;
    locationForm.to = to;
    locationForm.source = source;
    locationForm.post(route('settings.service-locations.range'), {
        preserveScroll: true,
        onSuccess: (page) => {
            locationForm.from = previousFrom;
            locationForm.to = previousTo;
            const result = page.props.locationCreateResult ?? null;
            locationResult.value = result;

            if (result?.skipped_names?.length) {
                return;
            }

            toast.success(__('Locations created successfully'));
            locationForm.reset();
            locationForm.from = 1;
            locationForm.to = 100;
            locationForm.default_attendance_channel_id = editingChannel.value.id;
            locationForm.source = 'several';
            locationNumber.value = Number(to) + 1;
            locationResult.value = null;
        },
        onError: () => {
            locationForm.from = previousFrom;
            locationForm.to = previousTo;
        },
    });
};

const addOneLocation = () => {
    if (!locationNumberIsValid.value) {
        toast.error(__('A number is required.'));
        return;
    }

    postLocationRange(locationNumber.value, locationNumber.value, 'one');
};

const addLocationRange = () => {
    if (!locationRangeIsValid.value) {
        toast.error(__('A number is required.'));
        return;
    }

    postLocationRange(locationForm.from, locationForm.to, 'several');
};

const showLocationQr = (location) => {
    qrLocation.value = location;
    qrPreviewKey.value = Date.now();
};

const qrImageSrc = computed(() => {
    if (!qrLocation.value) {
        return '';
    }

    return `${route('settings.service-locations.qr-png', qrLocation.value.id)}?t=${qrPreviewKey.value}`;
});

const qrPdfDownloadUrl = computed(() => {
    if (!qrLocation.value) {
        return '#';
    }

    return `${route('settings.service-locations.qr-pdf', qrLocation.value.id)}?download=1`;
});

const qrPngDownloadUrl = computed(() => {
    if (!qrLocation.value) {
        return '#';
    }

    return `${route('settings.service-locations.qr-png', qrLocation.value.id)}?download=1`;
});

const qrSvgDownloadUrl = computed(() => {
    if (!qrLocation.value) {
        return '#';
    }

    return `${route('settings.service-locations.qr-svg', qrLocation.value.id)}?download=1`;
});

const generateLocationQr = (location) => {
    if (location.qr_token) {
        showLocationQr(location);
        return;
    }

    enableEditing();
    generatingQrId.value = location.id;
    router.post(route('settings.service-locations.qr', location.id), {}, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            toast.success(__('QR code generated.'));
            showLocationQr(location);
        },
        onError: () => {
            toast.error(__('Could not generate QR code.'));
        },
        onFinish: () => {
            generatingQrId.value = null;
        },
    });
};

const inactivateLocation = (location) => {
    enableEditing();
    locationToDelete.value = location;

    router.put(route('settings.service-locations.update', location.id), {
        name: location.name,
        active: false,
        default_attendance_channel_id: location.default_attendance_channel_id,
    }, {
        preserveScroll: true,
        preserveState: true,
        onError: () => {
            toast.error(__('Could not inactivate this location.'));
            locationToDelete.value = null;
        },
        onFinish: () => {
            locationToDelete.value = null;
        },
    });
};

const confirmDeleteChannelLocations = () => {
    enableEditing();
    channelLocationsToDelete.value = editingChannel.value;

    nextTick(() => deleteChannelLocations());
};

const deleteChannelLocations = () => {
    if (!channelLocationsToDelete.value) {
        return;
    }

    router.delete(route('settings.attendance-channels.locations.destroy', { channel: channelLocationsToDelete.value.id }), {
        preserveScroll: true,
        preserveState: true,
        onError: (errors) => {
            toast.error(__(errors.locations || 'Could not delete these locations.'), { duration: 8000 });
        },
        onFinish: () => { channelLocationsToDelete.value = null; },
    });
};

const locationIsInactive = (location) => location.active === false;

const locationIsPendingDelete = (location) => (
    locationToDelete.value?.id === location.id
    || channelLocationsToDelete.value?.id === location.default_attendance_channel_id
    || locationIsInactive(location)
);

const chipClass = (pending) => (
    pending
        ? 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300'
        : 'bg-muted text-ocean-deep dark:bg-gray-700 dark:text-gray-100'
);
</script>

<template>
    <SettingsLayout :title="__('Attendance Channels')" :nested="showForm" @close-nested="closeForm">
        <template #header>
            <SettingsSectionHeader :title="__('Attendance Channels')">
                <span class="block">{{ __('Choose how the customer is served') }}</span>
                <span class="block">{{ __('Table, counter, delivery or pickup') }}</span>
            </SettingsSectionHeader>
        </template>

        <section
            data-section="channels"
            class="rounded-xl border-2 border-ocean-deep/25 bg-white p-6 shadow-card dark:border-gray-600 dark:bg-gray-800"
        >
            <div class="mb-6 flex min-w-0 flex-wrap items-center gap-3 border-b-2 border-ocean-deep/15 pb-4 dark:border-gray-600">
                <div class="flex min-w-0 items-center gap-3">
                    <h2 class="shrink-0 font-heading text-2xl font-bold text-ocean-deep dark:text-gray-100">{{ __('Attendance Channels') }}</h2>
                    <p class="text-sm leading-snug text-muted-foreground dark:text-gray-400">
                        <span class="block">{{ __('Choose how the customer is served') }}</span>
                        <span class="block">{{ __('Table, counter, delivery or pickup') }}</span>
                    </p>
                </div>
            </div>

            <AppEmptyState
                v-if="!channels.length && !showForm"
                :title="__('No attendance channels yet')"
                :description="__('Add channels like counter, table, or delivery.')"
            />

            <div v-if="channels.length && !showForm" class="divide-y divide-muted">
                <div
                    v-for="(channel, index) in channels"
                    :key="channel.id"
                    class="flex items-center justify-between py-3"
                >
                    <div class="flex min-w-0 flex-1 items-center gap-3 pr-4">
                        <span
                            class="h-4 w-4 shrink-0 rounded border border-border dark:border-gray-600"
                            :style="{ backgroundColor: channelColor(channel.color) }"
                        />
                        <span
                            class="font-body text-sm font-medium"
                            :class="channel.color ? '' : 'text-ocean-deep dark:text-gray-100'"
                            :style="channelNameStyle(channel.color)"
                        >{{ channel.name }}</span>
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
                        <AppButton size="sm" variant="ghost" :disabled="index === 0" @click="moveUp(index)">↑</AppButton>
                        <AppButton size="sm" variant="ghost" :disabled="index === channels.length - 1" @click="moveDown(index)">↓</AppButton>
                        <AppButton size="sm" variant="secondary" data-enable-editing @click="openEdit(channel)">{{ __('Edit') }}</AppButton>
                        <AppButton size="sm" variant="destructive" @click="confirmDelete(channel)">{{ __('Delete') }}</AppButton>
                    </div>
                </div>
            </div>
            <p v-if="channels.length && !showForm" class="mt-3 text-xs leading-snug text-muted-foreground dark:text-gray-400">
                <span class="block">{{ __('Sort the attendance channels. This order is used by the order taker in New Order.') }}</span>
                <span class="block">{{ __('This makes New Order more practical.') }}</span>
            </p>

            <div v-if="showForm" class="channel-form-card mt-4 rounded-lg border-2 p-4 dark:border-gray-700" :style="channelSectionStyle(form.color)">
                <div class="mb-3 flex flex-wrap items-center gap-3">
                    <h3 class="font-heading text-lg font-bold text-ocean-deep dark:text-gray-100">
                        {{ editingChannel ? __('Edit Channel') : __('New Channel') }}
                    </h3>
                    <div class="inline-flex shrink-0 items-center rounded-full bg-muted p-1 dark:bg-gray-700">
                        <button
                            type="button"
                            class="rounded-full px-3 py-1 text-xs font-semibold transition-colors"
                            :class="form.active ? 'bg-success text-white shadow-sm' : 'text-muted-foreground'"
                            @click="form.active = true"
                        >
                            {{ __('Active') }}
                        </button>
                        <button
                            type="button"
                            class="rounded-full px-3 py-1 text-xs font-semibold transition-colors"
                            :class="!form.active ? 'bg-destructive text-white shadow-sm' : 'text-muted-foreground'"
                            @click="form.active = false"
                        >
                            {{ __('Inactive') }}
                        </button>
                    </div>
                </div>
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

                            <label class="flex cursor-pointer items-start gap-3">
                                <input v-model="form.requires_customer_identifier" type="checkbox" class="mt-0.5 h-4 w-4 rounded border-border dark:border-gray-700 text-primary focus:ring-primary" />
                                <span>
                                    <span class="block text-sm text-ocean-deep dark:text-gray-100">{{ __('Requires customer identifier') }}</span>
                                    <span class="mt-0.5 block text-xs italic text-muted-foreground dark:text-gray-400">{{ __('Ideal for Delivery (example).') }}</span>
                                </span>
                            </label>

                            <label class="flex cursor-pointer items-start gap-3">
                                <input v-model="form.requires_tab" type="checkbox" class="mt-0.5 h-4 w-4 rounded border-border dark:border-gray-700 text-primary focus:ring-primary" />
                                <span>
                                    <span class="block text-sm text-ocean-deep dark:text-gray-100">{{ __('Requires tab') }}</span>
                                    <span class="mt-0.5 block text-xs italic text-muted-foreground dark:text-gray-400">{{ __('Each attendance must pick a registered tab. The same tabs are shared by every channel that uses them.') }}</span>
                                </span>
                            </label>

                            <label class="flex cursor-pointer items-start gap-3">
                                <input v-model="form.uses_service_location" type="checkbox" class="mt-0.5 h-4 w-4 rounded border-border dark:border-gray-700 text-primary focus:ring-primary" />
                                <span>
                                    <span class="block text-sm text-ocean-deep dark:text-gray-100">{{ __('Uses service location') }}</span>
                                    <span class="mt-0.5 block text-xs italic text-muted-foreground dark:text-gray-400">{{ __('Show tables or counters for this channel in New Order.') }}</span>
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
                            <div>
                                <div class="flex flex-nowrap items-center gap-2">
                                    <label class="shrink-0 whitespace-nowrap text-sm font-medium" :style="channelNameStyle(form.color)">
                                        {{ __('Color of :name', { name: form.name || __('New Channel') }) }}
                                    </label>
                                    <button
                                        v-for="color in palette"
                                        :key="color"
                                        type="button"
                                        class="h-8 w-8 shrink-0 rounded-md border-2 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-1"
                                        :class="isChannelColorSelected(color) ? 'border-ocean-deep ring-2 ring-ocean-deep ring-offset-1' : 'border-white shadow-sm'"
                                        :style="{ backgroundColor: color }"
                                        :aria-label="color"
                                        :aria-pressed="isChannelColorSelected(color)"
                                        @click="form.color = color"
                                    />
                                </div>
                                <p v-if="form.errors.color" class="mt-1 text-xs text-destructive">{{ form.errors.color }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-2 pt-1">
                        <AppButton v-if="!editingChannel" type="submit" :loading="form.processing">{{ __('Save') }}</AppButton>
                        <AppButton type="button" variant="ghost" @click="closeForm">{{ __('Cancel') }}</AppButton>
                    </div>
                </form>

                <div v-if="editingChannel && form.uses_service_location" class="mt-6 border-t border-border pt-4 dark:border-gray-700">
                    <h3 class="mb-3 font-heading text-lg font-bold text-ocean-deep dark:text-gray-100">
                        {{ __('Add') }} {{ form.name }}
                    </h3>

                    <div class="rounded-lg border border-border p-4 dark:border-gray-700">
                        <h4 class="mb-3 font-heading text-base font-bold text-ocean-deep dark:text-gray-100">{{ __('Create one') }} {{ form.name }}</h4>
                        <div class="flex flex-wrap items-end gap-2">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-ocean-deep dark:text-gray-100">{{ __('Number') }}</label>
                                <input
                                    v-model="locationNumber"
                                    type="number"
                                    min="1"
                                    required
                                    class="w-24 rounded-md border border-border dark:border-gray-700 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-gray-100"
                                />
                            </div>
                            <AppButton type="button" data-enable-editing :disabled="!locationNumberIsValid" :loading="locationForm.processing" @click="addOneLocation">{{ __('Add') }}</AppButton>
                        </div>
                        <p v-if="!locationNumberIsValid" class="mt-2 text-xs text-destructive">{{ __('A number is required.') }}</p>
                        <p v-else-if="singleLocationResult" class="mt-2 text-xs text-destructive">{{ locationAlreadyExistsMessage }}</p>
                    </div>

                    <div class="mt-4 rounded-lg border border-border p-4 dark:border-gray-700">
                        <h4 class="mb-3 font-heading text-base font-bold text-ocean-deep dark:text-gray-100">{{ __('Create several') }} {{ form.name }}</h4>
                        <div class="flex flex-wrap items-end gap-2">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-ocean-deep dark:text-gray-100">{{ __('From') }}</label>
                                <input
                                    v-model="locationForm.from"
                                    type="number"
                                    min="1"
                                    required
                                    class="w-24 rounded-md border border-border dark:border-gray-700 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-gray-100"
                                />
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-ocean-deep dark:text-gray-100">{{ __('To') }}</label>
                                <input
                                    v-model="locationForm.to"
                                    type="number"
                                    min="1"
                                    required
                                    class="w-24 rounded-md border border-border dark:border-gray-700 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-gray-100"
                                />
                            </div>
                            <AppButton type="button" variant="secondary" data-enable-editing :disabled="!locationRangeIsValid" :loading="locationForm.processing" @click="addLocationRange">{{ __('Create range') }}</AppButton>
                        </div>
                        <p v-if="!locationRangeIsValid" class="mt-2 text-xs text-destructive">{{ __('A number is required.') }}</p>
                        <p v-if="locationForm.errors.from" class="mt-2 text-xs text-destructive">{{ __(locationForm.errors.from) }}</p>
                        <p v-if="locationForm.errors.to" class="mt-2 text-xs text-destructive">{{ __(locationForm.errors.to) }}</p>
                        <div v-if="severalLocationResult" class="mt-2 space-y-1 text-xs text-destructive">
                            <p>{{ locationAlreadyExistsMessage }}</p>
                            <p>{{ __('Not created: :names', { names: formatLocationNames(severalLocationResult.skipped_names) }) }}</p>
                            <p v-if="severalLocationResult.created_names?.length">{{ __('Created: :names', { names: formatLocationNames(severalLocationResult.created_names) }) }}</p>
                        </div>
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
                                class="inline-flex min-w-0 items-center justify-center gap-1.5 rounded-full px-2 py-1 text-xs font-semibold"
                                :class="chipClass(locationIsPendingDelete(location))"
                            >
                                <span :class="{ 'line-through': locationIsPendingDelete(location) }">{{ location.name }}</span>
                                <button
                                    type="button"
                                    data-enable-editing
                                    class="text-muted-foreground hover:text-ocean-deep disabled:opacity-50 dark:hover:text-gray-100"
                                    :aria-label="__('Generate QR')"
                                    :disabled="generatingQrId === location.id"
                                    @click="generateLocationQr(location)"
                                >
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                        <path d="M3 3h7v7H3V3zm2 2v3h3V5H5zM14 3h7v7h-7V3zm2 2v3h3V5h-3zM3 14h7v7H3v-7zm2 2v3h3v-3H5zM14 14h3v3h-3v-3zm5 0h2v2h-2v-2zm-3 3h2v2h-2v-2zm3 2h3v3h-3v-3zm-5 1h2v2h-2v-2z" />
                                    </svg>
                                </button>
                                <button
                                    v-if="!locationIsInactive(location)"
                                    type="button"
                                    data-enable-editing
                                    class="text-muted-foreground hover:text-destructive"
                                    :aria-label="__('Inactivate location')"
                                    @click="inactivateLocation(location)"
                                >
                                    ×
                                </button>
                            </span>
                        </div>
                    </div>
                    <p v-else class="mt-4 text-xs text-muted-foreground dark:text-gray-400">{{ __('No locations yet.') }}</p>
                </div>
            </div>
        </section>

        <Modal :show="!!qrLocation" max-width="2xl" @close="qrLocation = null">
            <div v-if="qrLocation" class="p-6">
                <h2 class="text-center font-heading text-lg font-semibold text-ocean-deep dark:text-gray-100">{{ qrLocation.name }}</h2>
                <div class="mt-6 flex flex-col items-center gap-4 sm:flex-row sm:items-center sm:justify-center sm:gap-6">
                    <a
                        :href="qrPdfDownloadUrl"
                        class="inline-flex w-full max-w-[11rem] items-center justify-center rounded bg-muted px-4 py-2 text-center text-sm font-medium text-ocean-deep hover:bg-sand sm:w-40 dark:bg-gray-700 dark:text-gray-100 dark:hover:bg-gray-600"
                    >
                        {{ __('Download PDF format') }}
                    </a>
                    <img
                        :src="qrImageSrc"
                        :alt="qrLocation.name"
                        class="h-52 w-52 rounded-lg border border-border bg-white p-3"
                    />
                    <a
                        :href="qrPngDownloadUrl"
                        class="inline-flex w-full max-w-[11rem] items-center justify-center rounded bg-muted px-4 py-2 text-center text-sm font-medium text-ocean-deep hover:bg-sand sm:w-40 dark:bg-gray-700 dark:text-gray-100 dark:hover:bg-gray-600"
                    >
                        {{ __('Download PNG image format') }}
                    </a>
                </div>
                <div class="mt-4 flex justify-center">
                    <a
                        :href="qrSvgDownloadUrl"
                        class="inline-flex w-full max-w-xs items-center justify-center rounded bg-muted px-4 py-2 text-center text-sm font-medium text-ocean-deep hover:bg-sand dark:bg-gray-700 dark:text-gray-100 dark:hover:bg-gray-600"
                    >
                        {{ __('Download vectorized version') }}
                    </a>
                </div>
                <div class="mt-6 flex justify-end">
                    <AppButton type="button" variant="ghost" @click="qrLocation = null">{{ __('Cancel') }}</AppButton>
                </div>
            </div>
        </Modal>

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
