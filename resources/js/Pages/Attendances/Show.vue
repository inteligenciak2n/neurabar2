<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import AppButton from '@/Components/AppButton.vue';
import AppBadge from '@/Components/AppBadge.vue';
import AppEmptyState from '@/Components/AppEmptyState.vue';
import NumberScrollPicker from '@/Components/NumberScrollPicker.vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { toast } from 'vue-sonner';
import { useTranslate } from '@/Composables/useTranslate';
import { useNotificationSound } from '@/Composables/useNotificationSound';

const props = defineProps({
    attendance: Object,
    venueId: String,
    tableAttendances: {
        type: Array,
        default: () => [],
    },
    availableTabs: {
        type: Array,
        default: () => [],
    },
});

const __ = useTranslate();

const now = ref(Date.now());
const registerTabPickerOpen = ref(false);
const linkTabPickerOpen = ref(false);
const linkingAttendanceId = ref(null);
const registerTabForm = useForm({
    attendance_channel_id: '',
    tab_id: '',
    service_location_id: '',
    stay: true,
});

const tableLabel = computed(() => String(props.attendance?.service_location?.name ?? '').trim());

const tableAttendancesList = computed(() => (
    (props.tableAttendances ?? []).length ? props.tableAttendances : [props.attendance]
));

const tableTabLinks = computed(() => tableAttendancesList.value
    .map((item) => ({
        id: item.id,
        code: String(item?.tab?.code ?? item?.code ?? '').trim(),
    }))
    .filter((item) => item.code)
    .sort((a, b) => a.code.localeCompare(b.code, undefined, { numeric: true, sensitivity: 'base' })));

const headingFallback = computed(() => {
    const tab = String(props.attendance?.tab?.code ?? props.attendance?.code ?? '').trim();

    if (tab) {
        return `${__('Tab')} ${tab}`;
    }

    return props.attendance.customer_identifier ?? props.attendance.attendance_channel?.name ?? '';
});

const canRegisterTab = computed(() => {
    const locationId = props.attendance?.service_location_id ?? props.attendance?.service_location?.id;

    return Boolean(locationId) && Boolean(props.attendance?.attendance_channel?.requires_tab);
});

const attendanceNeedsTabLink = (attendance) =>
    !attendance?.tab_id
    && !attendance?.tab?.id
    && !String(attendance?.code ?? '').trim();

const attendanceHasItems = (attendance) =>
    (attendance.orders ?? []).some((order) => (order.items ?? []).length > 0);

const unlinkedAttendances = computed(() => tableAttendancesList.value.filter((attendance) => (
    attendanceNeedsTabLink(attendance) && attendanceHasItems(attendance)
)));

const hasUnlinkedItems = computed(() => unlinkedAttendances.value.length > 0);

const availableTabPickerItems = computed(() =>
    (props.availableTabs ?? []).map((tab) => ({
        id: tab.id,
        label: String(tab.code ?? ''),
    })),
);

const linkTabPickerItems = computed(() => {
    const seen = new Set();
    const items = [];

    tableAttendancesList.value.forEach((attendance) => {
        const tabId = attendance?.tab?.id ?? attendance?.tab_id;
        const code = String(attendance?.tab?.code ?? attendance?.code ?? '').trim();

        if (!tabId || seen.has(tabId)) {
            return;
        }

        seen.add(tabId);
        items.push({ id: tabId, label: code });
    });

    availableTabPickerItems.value.forEach((tab) => {
        if (seen.has(tab.id)) {
            return;
        }

        seen.add(tab.id);
        items.push(tab);
    });

    return items;
});

const registerTab = (tabId) => {
    if (!tabId) {
        return;
    }

    registerTabPickerOpen.value = false;
    registerTabForm.attendance_channel_id =
        props.attendance.attendance_channel_id
        ?? props.attendance.attendance_channel?.id
        ?? '';
    registerTabForm.tab_id = tabId;
    registerTabForm.service_location_id =
        props.attendance.service_location_id
        ?? props.attendance.service_location?.id
        ?? '';
    registerTabForm.stay = true;
    registerTabForm.post(route('attendances.store'), {
        preserveScroll: true,
    });
};

const openLinkTab = (attendanceId = null) => {
    linkingAttendanceId.value = attendanceId ?? unlinkedAttendances.value[0]?.id ?? null;
    linkTabPickerOpen.value = !linkTabPickerOpen.value;
};

const confirmLinkTab = (tabId) => {
    if (!tabId || !linkingAttendanceId.value) {
        return;
    }

    linkTabPickerOpen.value = false;
    router.put(route('attendances.link-tab', linkingAttendanceId.value), { tab_id: tabId }, {
        preserveScroll: true,
    });
};

const formatMoney = (value) => `R$ ${Number(value ?? 0).toFixed(2).replace('.', ',')}`;

const timestampOf = (value) => {
    if (value == null || value === '') {
        return null;
    }

    if (typeof value === 'number' && Number.isFinite(value)) {
        return value;
    }

    const time = new Date(value).getTime();

    return Number.isFinite(time) ? time : null;
};

const minutesBetween = (from, to) => {
    const start = timestampOf(from);

    if (!start) {
        return 0;
    }

    return Math.max(0, Math.floor((to - start) / 60000));
};

const formatDurationHm = (from) => {
    const minutes = minutesBetween(from, now.value);
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;

    return `${String(hours).padStart(2, '0')}h${String(mins).padStart(2, '0')}`;
};

const itemIsServed = (item) => Boolean(item?.ready_at) || Boolean(item?.preparation_status?.is_final);

const itemName = (item) => item?.product?.name ?? item?.combo?.name ?? __('Item');

const flattenItems = computed(() => {
    const lines = [];

    tableAttendancesList.value.forEach((attendance) => {
        const tabCode = String(attendance?.tab?.code ?? attendance?.code ?? '').trim();

        (attendance.orders ?? []).forEach((order) => {
            (order.items ?? []).forEach((item) => {
                const category = item.product?.category;
                const quantity = Number(item.quantity ?? 0);
                const unitPrice = Number(item.unit_price ?? 0);

                lines.push({
                    id: item.id,
                    attendanceId: attendance.id,
                    name: itemName(item),
                    quantity,
                    unitPrice,
                    total: quantity * unitPrice,
                    notes: item.notes,
                    tabCode,
                    served: itemIsServed(item),
                    orderAt: item.created_at ?? order.created_at,
                    categoryId: category?.id ?? (item.combo_id ? 'combo' : 'other'),
                    categoryName: category?.name ?? (item.combo_id ? __('Combos') : __('Other')),
                    categorySort: Number(category?.sort_order ?? 9999),
                });
            });
        });
    });

    return lines;
});

const pendingItems = computed(() => flattenItems.value.filter((item) => !item.served));
const servedItems = computed(() => flattenItems.value.filter((item) => item.served));

const quantityOf = (items) => items.reduce((sum, item) => sum + item.quantity, 0);
const totalOf = (items) => items.reduce((sum, item) => sum + item.total, 0);

const pendingSummary = computed(() => ({
    count: quantityOf(pendingItems.value),
    total: totalOf(pendingItems.value),
}));

const oldestPendingAt = computed(() => {
    const times = pendingItems.value
        .map((item) => timestampOf(item.orderAt))
        .filter(Boolean);

    return times.length ? Math.min(...times) : null;
});

const pendingWaitDuration = computed(() => (
    oldestPendingAt.value ? formatDurationHm(oldestPendingAt.value) : null
));

const servedSummary = computed(() => ({
    count: quantityOf(servedItems.value),
    total: totalOf(servedItems.value),
}));

const tableSummary = computed(() => ({
    count: quantityOf(flattenItems.value),
    total: totalOf(flattenItems.value),
}));

const openedAt = computed(() => {
    const times = tableAttendancesList.value
        .map((attendance) => attendance?.created_at)
        .filter(Boolean)
        .map((value) => new Date(value).getTime())
        .filter((value) => Number.isFinite(value));

    return times.length ? Math.min(...times) : null;
});

const lastOrderAt = computed(() => {
    const times = [];

    tableAttendancesList.value.forEach((attendance) => {
        (attendance.orders ?? []).forEach((order) => {
            if (order?.created_at) {
                times.push(new Date(order.created_at).getTime());
            }
        });
    });

    const valid = times.filter((value) => Number.isFinite(value));

    return valid.length ? Math.max(...valid) : null;
});

const tableDuration = computed(() => formatDurationHm(openedAt.value));
const lastOrderMinutesAgo = computed(() => (lastOrderAt.value ? minutesBetween(lastOrderAt.value, now.value) : null));

const oldestAttendance = computed(() =>
    [...tableAttendancesList.value].sort((a, b) => (
        new Date(a?.created_at ?? 0).getTime() - new Date(b?.created_at ?? 0).getTime()
    ))[0] ?? props.attendance,
);

const openedByName = computed(() =>
    oldestAttendance.value?.created_by?.name ?? props.attendance.created_by?.name ?? '',
);

const partySize = computed(() => tableAttendancesList.value.reduce(
    (max, attendance) => Math.max(max, Number(attendance?.party_size ?? 0)),
    Number(props.attendance.party_size ?? 0),
));

const tabCount = computed(() => tableTabLinks.value.length || tableAttendancesList.value.length);

const categoryGroups = computed(() => {
    const map = new Map();

    flattenItems.value.forEach((item) => {
        if (!map.has(item.categoryId)) {
            map.set(item.categoryId, {
                id: item.categoryId,
                name: item.categoryName,
                sort: item.categorySort,
                total: 0,
                pendingQty: 0,
                servedQty: 0,
                pending: [],
                served: [],
            });
        }

        const group = map.get(item.categoryId);
        group.total += item.total;

        if (item.served) {
            group.served.push(item);
            group.servedQty += item.quantity;
        } else {
            group.pending.push(item);
            group.pendingQty += item.quantity;
        }
    });

    return [...map.values()].sort((a, b) => a.sort - b.sort || a.name.localeCompare(b.name));
});

const tabGroups = computed(() => tableAttendancesList.value
    .map((attendance) => {
        const code = String(attendance?.tab?.code ?? attendance?.code ?? '').trim();
        const items = flattenItems.value.filter((item) => item.attendanceId === attendance.id);
        const pending = items.filter((item) => !item.served);
        const served = items.filter((item) => item.served);

        return {
            id: attendance.id,
            code,
            needsTabLink: !code && items.length > 0,
            items: [...pending, ...served],
            pendingCount: quantityOf(pending),
            itemCount: quantityOf(items),
            total: totalOf(items),
        };
    })
    .sort((a, b) => a.code.localeCompare(b.code, undefined, { numeric: true, sensitivity: 'base' })));

const isTableAttendance = (attendanceId) => tableAttendancesList.value.some(
    (item) => String(item.id) === String(attendanceId),
);

const reloadTable = () => {
    router.reload({
        only: ['attendance', 'tableAttendances'],
        preserveScroll: true,
    });
};

const { playSound } = useNotificationSound();

let kitchenChannel = null;
let timeTicker = null;

onMounted(() => {
    timeTicker = window.setInterval(() => {
        now.value = Date.now();
    }, 15000);

    if (!props.venueId) {
        return;
    }

    kitchenChannel = window.Echo.private(`venue.${props.venueId}.kitchen`)
        .listen('.ItemStatusUpdated', async (event) => {
            if (!isTableAttendance(event.attendance_id)) {
                return;
            }

            if (event.ready_at) {
                playSound();
                toast.success(__('Item ready to serve'), {
                    description: event.preparation_status?.name ?? __('Item is ready'),
                });
            }

            reloadTable();
        })
        .listen('.OrderPlaced', async (event) => {
            if (!isTableAttendance(event.order?.attendance_id)) {
                return;
            }

            reloadTable();
        });
});

onUnmounted(() => {
    if (timeTicker) {
        window.clearInterval(timeTicker);
    }

    if (props.venueId && kitchenChannel) {
        window.Echo.leaveChannel(`venue.${props.venueId}.kitchen`);
    }
});
</script>

<template>
    <AppLayout :title="`${__('Attendance')} — ${tableLabel || headingFallback}`">
        <template #header>
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="min-w-0">
                    <Link :href="route('attendances.index')" class="text-sm font-medium text-primary hover:underline">← {{ __('Attendances') }}</Link>
                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        <h1 class="font-heading text-3xl font-bold text-ocean-deep dark:text-gray-100">
                            <span v-if="tableLabel" data-show-table>{{ tableLabel }}</span>
                            <span v-else>{{ headingFallback }}</span>
                        </h1>
                        <AppBadge
                            :label="tableLabel ? __('Open table') : attendance.status"
                            :color="attendance.status === 'open' ? '#22c55e' : '#94a3b8'"
                        />
                        <span
                            v-if="tabCount"
                            data-show-tabs
                            class="text-sm font-medium text-muted-foreground"
                        >
                            {{ tabCount }} {{ tabCount === 1 ? __('tab') : __('tabs') }}
                        </span>
                    </div>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{ __('Opened by') }} {{ openedByName }}
                        <span v-if="partySize"> · {{ partySize }} {{ partySize === 1 ? __('guest') : __('guests') }}</span>
                        <span v-if="tabCount"> · {{ tabCount }} {{ tabCount === 1 ? __('tab') : __('tabs') }}</span>
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <AppButton
                        v-if="canRegisterTab"
                        type="button"
                        variant="secondary"
                        data-register-tab
                        :loading="registerTabForm.processing"
                        @click="registerTabPickerOpen = !registerTabPickerOpen"
                    >
                        {{ __('Register tab') }}
                    </AppButton>
                    <AppButton
                        v-if="hasUnlinkedItems"
                        type="button"
                        variant="accent"
                        data-link-tab
                        @click="openLinkTab()"
                    >
                        {{ __('Link tab') }}
                    </AppButton>
                    <AppButton
                        variant="secondary"
                        :href="hasUnlinkedItems ? null : route('payment.show', attendance.id)"
                        :disabled="hasUnlinkedItems"
                    >
                        {{ __('Payment') }}
                    </AppButton>
                    <AppButton :href="route('orders.take', attendance.id)">{{ __('Take Order') }}</AppButton>
                </div>
            </div>
        </template>

        <div
            v-if="attendance.delivery_address"
            data-delivery-address-line
            class="mb-4 rounded-lg border border-border bg-white p-3 text-sm text-ocean-deep"
        >
            <p class="text-xs font-semibold text-muted-foreground">{{ __('Delivery address') }}</p>
            <p class="mt-1 leading-snug">
                <span>{{ attendance.delivery_address.street }}{{ attendance.delivery_address.number ? `, ${attendance.delivery_address.number}` : '' }}{{ attendance.delivery_address.complement ? `, ${attendance.delivery_address.complement}` : '' }}</span>
                <span v-if="attendance.delivery_address.neighborhood" class="font-bold"> – {{ attendance.delivery_address.neighborhood }}</span>
                <span v-if="attendance.delivery_address.reference_point" class="font-bold"> – (ref: {{ attendance.delivery_address.reference_point }})</span>
                <span v-if="attendance.delivery_address.delivery_phone"> – Tel: {{ attendance.delivery_address.delivery_phone }}</span>
            </p>
            <a
                v-if="attendance.delivery_address.maps_url"
                :href="attendance.delivery_address.maps_url"
                target="_blank"
                rel="noopener noreferrer"
                data-open-in-maps
                class="mt-2 inline-block text-xs font-semibold text-primary underline"
            >
                {{ __('Open in maps') }}
            </a>
        </div>

        <div
            v-if="canRegisterTab && registerTabPickerOpen"
            data-register-tab-picker
            class="mb-4"
        >
            <NumberScrollPicker
                :open="true"
                hide-trigger
                :items="availableTabPickerItems"
                :placeholder="__('Choose a tab number')"
                :empty-text="__('No free tabs.')"
                @update:open="(isOpen) => { if (!isOpen) registerTabPickerOpen = false; }"
                @update:model-value="registerTab"
            />
            <p v-if="registerTabForm.errors.tab_id" class="mt-1 text-xs text-destructive">{{ __(registerTabForm.errors.tab_id) }}</p>
        </div>
        <div
            v-if="hasUnlinkedItems && linkTabPickerOpen"
            data-link-tab-picker
            class="mb-4"
        >
            <NumberScrollPicker
                :open="true"
                hide-trigger
                :items="linkTabPickerItems"
                :placeholder="__('Choose a tab number')"
                :empty-text="__('No free tabs.')"
                @update:open="(isOpen) => { if (!isOpen) linkTabPickerOpen = false; }"
                @update:model-value="confirmLinkTab"
            />
        </div>

        <div class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-2xl bg-ocean-deep p-4 text-white shadow-card">
                <p class="text-xs font-medium text-white/70">{{ __('Table total') }}</p>
                <p class="mt-2 font-heading text-3xl font-bold tabular-nums">{{ formatMoney(tableSummary.total) }}</p>
                <p class="mt-1 text-xs text-white/70">{{ tableSummary.count }} {{ tableSummary.count === 1 ? __('item') : __('items') }}</p>
            </article>
            <article
                data-pending-summary
                class="rounded-2xl bg-red-600 p-4 text-white shadow-card"
            >
                <p class="text-xs font-semibold text-white/80">{{ __('Pending') }}</p>
                <p class="mt-2 font-heading text-3xl font-bold tabular-nums">
                    {{ pendingSummary.count }} {{ pendingSummary.count === 1 ? __('item') : __('items') }}
                </p>
                <p class="mt-1 text-xs font-medium text-white/80">
                    {{ formatMoney(pendingSummary.total) }} {{ __('awaiting delivery') }}
                </p>
            </article>
            <article class="rounded-2xl bg-white p-4 shadow-card">
                <p class="text-xs font-semibold text-ocean-deep">{{ __('Served') }}</p>
                <p class="mt-2 font-heading text-3xl font-bold tabular-nums text-ocean-deep">
                    {{ servedSummary.count }} {{ servedSummary.count === 1 ? __('item') : __('items') }}
                </p>
                <p class="mt-1 text-xs font-medium text-muted-foreground">
                    {{ formatMoney(servedSummary.total) }} {{ __('delivered') }}
                </p>
            </article>
            <article data-table-time class="rounded-2xl bg-white p-4 shadow-card">
                <p class="text-xs font-semibold text-ocean-deep">{{ __('Table time') }}</p>
                <p data-table-duration class="mt-2 font-heading text-3xl font-bold tabular-nums text-ocean-deep">{{ tableDuration }}</p>
                <p class="mt-1 text-xs font-medium text-muted-foreground">
                    <template v-if="lastOrderMinutesAgo !== null">
                        {{ __('Last order :minutes min ago', { minutes: lastOrderMinutesAgo }) }}
                    </template>
                    <template v-else>
                        {{ __('No orders yet') }}
                    </template>
                </p>
            </article>
        </div>

        <section
            v-if="pendingItems.length"
            data-pending-items
            class="mb-4 rounded-2xl border border-red-200 bg-red-50/80 p-4"
        >
            <div class="mb-3 flex items-center justify-between gap-3">
                <p class="flex items-center gap-2 text-sm font-semibold text-red-700">
                    <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-red-100 text-red-600">⏱</span>
                    {{ __('Waiting for delivery') }}
                    <span
                        v-if="pendingWaitDuration"
                        data-waiting-duration
                        class="rounded-full bg-red-100 px-2 py-0.5 font-heading text-sm font-bold tabular-nums text-red-800"
                    >
                        {{ pendingWaitDuration }}
                    </span>
                </p>
                <p class="text-sm font-bold tabular-nums text-red-800">{{ formatMoney(pendingSummary.total) }}</p>
            </div>
            <div class="grid grid-cols-1 gap-x-8 gap-y-2 md:grid-cols-2">
                <div
                    v-for="item in pendingItems"
                    :key="item.id"
                    class="flex items-start justify-between gap-3"
                >
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-ocean-deep">{{ item.quantity }}× {{ item.name }}</p>
                        <p class="text-xs text-muted-foreground">
                            <span v-if="item.tabCode" data-item-tab>{{ __('Tab') }} {{ item.tabCode }}</span>
                            <span v-if="item.tabCode && item.categoryName"> · </span>
                            <span>{{ item.categoryName }}</span>
                            <span
                                v-if="item.orderAt"
                                data-waiting-time
                                class="font-semibold tabular-nums text-red-700"
                            > · {{ formatDurationHm(item.orderAt) }}</span>
                        </p>
                    </div>
                    <p class="shrink-0 text-sm font-bold tabular-nums text-red-700">{{ formatMoney(item.total) }}</p>
                </div>
            </div>
        </section>

        <AppEmptyState
            v-if="!flattenItems.length"
            :title="__('No orders yet')"
            :description="__('Take the first order for this attendance.')"
        />

        <div v-else class="grid grid-cols-1 gap-3 lg:grid-cols-3">
            <article
                v-for="tab in tabGroups"
                :key="tab.id"
                data-tab-card
                class="rounded-2xl bg-white p-4 shadow-card"
            >
                <div class="mb-3 flex items-start justify-between gap-3">
                    <div>
                        <h2 data-show-tab class="font-heading text-base font-bold text-ocean-deep">
                            {{ tab.code ? `${__('Tab')} ${tab.code}` : (tab.needsTabLink ? __('Link tab') : headingFallback) }}
                        </h2>
                        <button
                            v-if="tab.needsTabLink"
                            type="button"
                            data-link-tab
                            class="mt-1 text-xs font-semibold text-amber-700 underline"
                            @click="openLinkTab(tab.id)"
                        >
                            {{ __('Link tab') }}
                        </button>
                        <p class="mt-0.5 text-xs text-muted-foreground">
                            {{ tab.itemCount }} {{ tab.itemCount === 1 ? __('item') : __('items') }}
                            <span
                                v-if="tab.pendingCount"
                                class="ml-2 rounded-full bg-red-50 px-2 py-0.5 font-semibold text-red-600"
                            >
                                {{ tab.pendingCount }} {{ __('pending items') }}
                            </span>
                        </p>
                    </div>
                    <p class="font-heading text-lg font-bold tabular-nums text-ocean-deep">{{ formatMoney(tab.total) }}</p>
                </div>
                <div class="divide-y divide-border">
                    <div
                        v-for="item in tab.items"
                        :key="item.id"
                        class="flex items-center justify-between gap-3 py-1.5"
                    >
                        <p class="min-w-0 text-sm text-ocean-deep">
                            <span
                                class="mr-1.5 inline-block h-2 w-2 rounded-full"
                                :class="item.served ? 'bg-emerald-500' : 'bg-red-500'"
                            />
                            {{ item.quantity }}× {{ item.name }}
                        </p>
                        <p class="shrink-0 text-sm font-semibold tabular-nums text-ocean-deep">{{ formatMoney(item.total) }}</p>
                    </div>
                </div>
            </article>

            <article data-category-summary class="rounded-2xl bg-white p-4 shadow-card">
                <h2 class="mb-3 font-heading text-base font-bold text-ocean-deep">{{ __('By category') }}</h2>
                <div class="space-y-3">
                    <div v-for="category in categoryGroups" :key="category.id">
                        <div class="mb-1 flex items-baseline justify-between gap-3">
                            <p class="text-sm font-semibold text-ocean-deep">
                                {{ category.name }}
                                <span class="ml-1 text-xs font-medium text-muted-foreground">
                                    {{ category.pendingQty + category.servedQty }}
                                    {{ (category.pendingQty + category.servedQty) === 1 ? __('item') : __('items') }}
                                </span>
                            </p>
                            <p class="text-sm font-bold tabular-nums text-ocean-deep">{{ formatMoney(category.total) }}</p>
                        </div>
                        <div class="flex h-1.5 overflow-hidden rounded-full bg-muted">
                            <span
                                v-if="category.pendingQty"
                                data-pending-bar
                                class="bg-red-500"
                                :style="{ width: `${(category.pendingQty / (category.pendingQty + category.servedQty)) * 100}%` }"
                            />
                            <span
                                v-if="category.servedQty"
                                data-served-bar
                                class="bg-emerald-500"
                                :style="{ width: `${(category.servedQty / (category.pendingQty + category.servedQty)) * 100}%` }"
                            />
                        </div>
                    </div>
                </div>
                <p class="mt-4 flex items-center gap-4 text-xs text-muted-foreground">
                    <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-red-500" /> {{ __('Pending') }}</span>
                    <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-emerald-500" /> {{ __('Served') }}</span>
                </p>
            </article>
        </div>
    </AppLayout>
</template>
