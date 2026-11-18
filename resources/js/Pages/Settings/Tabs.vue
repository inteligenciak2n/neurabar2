<script setup>
import SettingsLayout from '@/Layouts/SettingsLayout.vue';
import SettingsSectionHeader from '@/Components/SettingsSectionHeader.vue';
import AppCard from '@/Components/AppCard.vue';
import AppButton from '@/Components/AppButton.vue';
import AppConfirmModal from '@/Components/AppConfirmModal.vue';
import { useForm, router } from '@inertiajs/vue3';
import { computed, nextTick, ref } from 'vue';
import { toast } from 'vue-sonner';
import { useSettingsEditing } from '@/Composables/useSettingsEditing';
import { useTranslate } from '@/Composables/useTranslate';
import { formatPrefixLabel, groupTabsForDisplay, prefixFromCode, tabSeriesDomId } from '@/Utils/tabRows';

defineOptions({ name: 'AttendanceChannels' });

const props = defineProps({
    tabSeries: Array,
    tabsEnabled: {
        type: Boolean,
        default: true,
    },
});

const __ = useTranslate();
const { enableEditing } = useSettingsEditing();

const tabToDelete = ref(null);
const seriesToDelete = ref(null);

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

const isPositiveInteger = (value) => {
    const number = Number(value);

    return Number.isInteger(number) && number >= 1;
};

const tabCodeHasNumber = computed(() => /\d/.test(String(tabForm.code ?? '').trim()));
const tabRangeIsValid = computed(() => (
    isPositiveInteger(rangeForm.from)
    && isPositiveInteger(rangeForm.to)
    && Number(rangeForm.to) >= Number(rangeForm.from)
));

const revealCreatedSeries = (prefix) => {
    toast.success(__('Tabs created successfully'));

    nextTick(() => {
        document.getElementById(tabSeriesDomId(prefix))?.scrollIntoView({
            behavior: 'smooth',
            block: 'start',
        });
    });
};

const setTabsEnabled = (enabled) => {
    if (enabled === props.tabsEnabled) {
        return;
    }

    enableEditing();
    router.put(route('settings.tabs.enabled'), {
        tabs_enabled: enabled,
    }, {
        preserveScroll: true,
        preserveState: true,
        onError: () => {
            toast.error(__('Could not update tabs.'));
        },
    });
};

const addTab = () => {
    if (!tabCodeHasNumber.value) {
        tabForm.setError('code', __('A tab code must include a number.'));
        return;
    }

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
    if (!tabRangeIsValid.value) {
        toast.error(__('A number is required.'));
        return;
    }

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
    enableEditing();
    tabToDelete.value = tab;

    nextTick(() => deleteTab());
};

const deleteTab = () => {
    if (!tabToDelete.value) {
        return;
    }

    router.delete(route('settings.tabs.destroy', { tab: tabToDelete.value.id }), {
        preserveScroll: true,
        preserveState: true,
        onError: (errors) => {
            toast.error(__(errors.tab || 'Could not delete this tab.'), { duration: 8000 });
            tabToDelete.value = null;
        },
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

    router.delete(route('settings.tab-series.destroy', { series: seriesToDelete.value.id }), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => { seriesToDelete.value = null; },
        onError: (errors) => {
            toast.error(__(errors.tabs || 'Could not delete these tabs.'), { duration: 8000 });
        },
        onFinish: () => { seriesToDelete.value = null; },
    });
};

const seriesTabRows = (series) => groupTabsForDisplay(series?.tabs ?? []);

const tabIsPendingDelete = (tab, series) => (
    tabToDelete.value?.id === tab.id
    || seriesToDelete.value?.id === series.id
);

const chipClass = (pending) => (
    pending
        ? 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300'
        : 'bg-muted text-ocean-deep dark:bg-gray-700 dark:text-gray-100'
);
</script>

<template>
    <SettingsLayout :title="__('Tabs')">
        <template #header>
            <SettingsSectionHeader :title="__('Tabs')">
                <span class="block">{{ __('All channels that require a tab share this list.') }}</span>
                <span class="block">{{ __('Use a prefix before the numbers to organize internally.') }}</span>
            </SettingsSectionHeader>
        </template>

        <section
            data-section="tabs"
            class="rounded-xl border-2 border-ocean-deep/25 bg-white p-6 shadow-card dark:border-gray-600 dark:bg-gray-800"
        >
            <div class="mb-6 flex min-w-0 flex-wrap items-center justify-between gap-3 border-b-2 border-ocean-deep/15 pb-4 dark:border-gray-600">
                <div class="flex min-w-0 items-center gap-3" :class="{ 'grayscale opacity-50': !tabsEnabled }">
                    <h2 class="shrink-0 font-heading text-2xl font-bold text-ocean-deep dark:text-gray-100">{{ __('Tabs') }}</h2>
                    <p class="text-sm leading-snug text-muted-foreground dark:text-gray-400">
                        <span class="block">{{ __('All channels that require a tab share this list.') }}</span>
                        <span class="block">{{ __('Use a prefix before the numbers to organize internally.') }}</span>
                    </p>
                </div>
                <div class="inline-flex shrink-0 items-center rounded-full bg-muted p-1 dark:bg-gray-700">
                    <button
                        type="button"
                        data-enable-editing
                        class="rounded-full px-3 py-1 text-xs font-semibold transition-colors"
                        :class="tabsEnabled ? 'bg-success text-white shadow-sm' : 'text-muted-foreground'"
                        @click="setTabsEnabled(true)"
                    >
                        {{ __('Enabled') }}
                    </button>
                    <button
                        type="button"
                        data-enable-editing
                        class="rounded-full px-3 py-1 text-xs font-semibold transition-colors"
                        :class="!tabsEnabled ? 'bg-destructive text-white shadow-sm' : 'text-muted-foreground'"
                        @click="setTabsEnabled(false)"
                    >
                        {{ __('Disabled') }}
                    </button>
                </div>
            </div>

            <div :class="{ 'pointer-events-none select-none grayscale opacity-50': !tabsEnabled }">
                <AppCard class="border border-border dark:border-gray-700" :title="__('Create a new tab number')" title-class="mb-4 font-heading text-lg font-bold text-ocean-deep dark:text-gray-100">
                    <p class="mb-4 text-xs text-muted-foreground dark:text-gray-400">
                        {{ __('Each tab code can be used only once.') }}
                    </p>
                    <div class="rounded-lg border border-border p-4 dark:border-gray-700">
                        <h3 class="mb-3 font-heading text-lg font-bold text-ocean-deep dark:text-gray-100">{{ __('Create a single tab') }}</h3>
                        <form class="space-y-3" @submit.prevent="addTab">
                            <div class="flex flex-wrap items-start gap-2">
                                <div class="min-w-[16rem] flex-1">
                                    <input
                                        v-model="tabForm.code"
                                        type="text"
                                        maxlength="20"
                                        required
                                        :placeholder="__('e.g. 15 or A1')"
                                        :aria-label="__('Create a single tab')"
                                        class="w-full rounded-md border border-border dark:border-gray-700 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-gray-100"
                                    />
                                    <p class="mt-1 text-xs text-muted-foreground dark:text-gray-400">
                                        {{ __('Use words at the start of each tab if you want to distinguish how the tabs are used.') }}
                                    </p>
                                    <p v-if="tabForm.errors.code" class="mt-1 text-xs text-destructive">{{ __(tabForm.errors.code) }}</p>
                                    <p v-else-if="String(tabForm.code ?? '').trim() !== '' && !tabCodeHasNumber" class="mt-1 text-xs text-destructive">{{ __('A tab code must include a number.') }}</p>
                                </div>
                                <AppButton type="submit" data-enable-editing :disabled="!tabCodeHasNumber" :loading="tabForm.processing">{{ __('Add tab') }}</AppButton>
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
                            <div class="flex flex-wrap items-start gap-2">
                                <div class="min-w-[18rem] flex-[2]">
                                    <label class="mb-1 block text-sm font-medium text-ocean-deep dark:text-gray-100">{{ __('Prefix') }}</label>
                                    <input
                                        v-model="rangeForm.prefix"
                                        type="text"
                                        maxlength="10"
                                        :placeholder="__('e.g. A or CASA1')"
                                        class="w-full rounded-md border border-border dark:border-gray-700 px-3 py-2 text-sm uppercase focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-gray-100"
                                    />
                                    <p class="mt-1 text-xs text-muted-foreground dark:text-gray-400">
                                        {{ __('Use words at the start of each tab if you want to distinguish how the tabs are used.') }}
                                    </p>
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-ocean-deep dark:text-gray-100">{{ __('From') }}</label>
                                    <input
                                        v-model="rangeForm.from"
                                        type="number"
                                        min="1"
                                        required
                                        class="w-24 rounded-md border border-border dark:border-gray-700 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-gray-100"
                                    />
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-ocean-deep dark:text-gray-100">{{ __('To') }}</label>
                                    <input
                                        v-model="rangeForm.to"
                                        type="number"
                                        min="1"
                                        required
                                        class="w-24 rounded-md border border-border dark:border-gray-700 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-gray-100"
                                    />
                                </div>
                                <div class="pt-7">
                                    <AppButton type="submit" variant="secondary" data-enable-editing :disabled="!tabRangeIsValid" :loading="rangeForm.processing">{{ __('Create range') }}</AppButton>
                                </div>
                            </div>
                            <p v-if="!tabRangeIsValid" class="text-xs text-destructive">{{ __('A number is required.') }}</p>
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

                <AppCard class="mt-6 border border-border dark:border-gray-700" :title="__('Created tab numbers')" title-class="mb-4 font-heading text-lg font-bold text-ocean-deep dark:text-gray-100">
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
                                            class="inline-flex min-w-0 items-center justify-center gap-1 rounded-full px-2 py-0.5 text-xs font-semibold"
                                            :class="chipClass(tabIsPendingDelete(tab, series))"
                                        >
                                            <span :class="{ 'line-through': tabIsPendingDelete(tab, series) }">{{ tab.code }}</span>
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
        </section>

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
