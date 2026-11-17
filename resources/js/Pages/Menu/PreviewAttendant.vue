<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import AppBadge from '@/Components/AppBadge.vue';
import AppButton from '@/Components/AppButton.vue';
import AppEmptyState from '@/Components/AppEmptyState.vue';
import ProductDetailDrawer from '@/Components/Guest/ProductDetailDrawer.vue';
import GuestMenuCatalog from '@/Components/Guest/GuestMenuCatalog.vue';
import NumberScrollPicker from '@/Components/NumberScrollPicker.vue';
import DeliveryAddressFields from '@/Components/Orders/DeliveryAddressFields.vue';
import { mergeGuestCartItem } from '@/Utils/guestCart';
import { emptyDeliveryAddress, zipDigits } from '@/Utils/deliveryAddress';
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { toast } from 'vue-sonner';
import { useTranslate } from '@/Composables/useTranslate';

defineOptions({ name: 'PreviewAttendant' });

const props = defineProps({
    preview: {
        type: Boolean,
        default: true,
    },
    startMode: {
        type: String,
        default: 'order',
    },
    attendance: {
        type: Object,
        default: null,
    },
    venue: Object,
    channels: {
        type: Array,
        default: () => [],
    },
    serviceLocations: {
        type: Array,
        default: () => [],
    },
    tabs: {
        type: Array,
        default: () => [],
    },
    attendances: {
        type: Array,
        default: () => [],
    },
    categories: Array,
    combos: Array,
    deliveryNeighborhoods: {
        type: Array,
        default: () => [],
    },
});

const __ = useTranslate();

const mainTab = ref('order');
const liveStartMode = ref(props.startMode || 'order');
const selectedChannelId = ref(props.channels?.[0]?.id ?? null);
const selectedTabId = ref('');
const selectedLocationId = ref('');
const customerName = ref('');
const customerPhone = ref('');
const deliveryAddress = ref(emptyDeliveryAddress());
const neighborhoodOptions = ref([...(props.deliveryNeighborhoods ?? [])]);
const locationOptions = ref([...(props.serviceLocations ?? [])]);
const registeringLocation = ref(false);
const savedAddresses = ref([]);
let customerLookupTimer = null;
const suggestedLocationId = ref('');
const selectionByChannel = ref({});
const openPicker = ref(null);
const tabPickerMode = ref(null);
const movingLineName = ref('');
const movingCartKey = ref('');
const movingCartIndex = ref(null);
const headerCompact = ref(false);
const catalogStickyTop = ref(null);
let stickyOffsetObserver = null;
const summarySize = ref(2);
const statusTabId = ref('');
const statusLocationId = ref('');
const statusIdentifierId = ref('');
const statusLookupBy = ref('');
const STATUS_WITH_ORDERS_HEADER_ID = '__status-with-orders__';
const STATUS_WITHOUT_ORDERS_HEADER_ID = '__status-without-orders__';
const selectedProduct = ref(null);
const showProductDrawer = ref(false);
const orderSuccessVisible = ref(false);
const orderSuccessName = ref('');
const orderSuccessTable = ref('');
const orderSuccessTab = ref('');
const cartsByContext = ref({});
const tabLocationByTabId = ref({});
const editingCartIndex = ref(null);
const NEW_TABS_HEADER_ID = '__new-tabs-header__';

const cartKeyFor = (locationId, tabId) => {
    if (tabId && locationId) {
        return `loc:${locationId}:tab:${tabId}`;
    }

    if (tabId) {
        return `tab:${tabId}`;
    }

    if (locationId) {
        return `loc:${locationId}`;
    }

    return `ch:${selectedChannelId.value ?? 'none'}`;
};

const currentCartKey = computed(() => cartKeyFor(selectedLocationId.value, selectedTabId.value));

const cartListFor = (locationId, tabId) => {
    const key = cartKeyFor(locationId, tabId);

    if (Array.isArray(cartsByContext.value[key])) {
        return cartsByContext.value[key];
    }

    if (tabId && Array.isArray(cartsByContext.value[`tab:${tabId}`])) {
        return cartsByContext.value[`tab:${tabId}`];
    }

    return [];
};

const cartItems = computed(() => cartListFor(selectedLocationId.value, selectedTabId.value));
const editingItem = computed(() => (
    editingCartIndex.value === null ? null : cartItems.value[editingCartIndex.value] ?? null
));

const cartItemLabel = (item) => {
    const name = String(item?.product_name ?? '').trim();
    const variation = String(item?.variation_name ?? '').trim();

    return variation ? `${name} — ${variation}` : name;
};

const ensureCurrentCart = () => {
    const key = currentCartKey.value;
    const existing = cartListFor(selectedLocationId.value, selectedTabId.value);

    if (Array.isArray(cartsByContext.value[key])) {
        return cartsByContext.value[key];
    }

    cartsByContext.value = {
        ...cartsByContext.value,
        [key]: [...existing],
    };

    return cartsByContext.value[key];
};

const catalogCategories = computed(() => {
    const categories = [...(props.categories ?? [])];

    if (!props.combos?.length) {
        return categories;
    }

    categories.push({
        id: 'combos',
        name: __('Combos'),
        products: props.combos.map((combo) => ({
            id: combo.id,
            name: combo.name,
            description: comboDescription(combo),
            price: combo.price,
            image_url: null,
            servings: null,
            variations: [],
            modifier_groups: [],
        })),
    });

    return categories;
});

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

const selectedChannel = computed(() =>
    (props.channels ?? []).find((channel) => channel.id === selectedChannelId.value),
);

const selectedChannelColor = computed(() => selectedChannel.value?.color || '');

const channelPanelStyle = computed(() => {
    const hex = selectedChannelColor.value;
    if (!hex) {
        return null;
    }

    return {
        borderColor: hex,
        backgroundColor: hexToRgba(hex, 0.06) || undefined,
    };
});

const isOrderMode = computed(() => mainTab.value === 'order');
const isStatusMode = computed(() => mainTab.value === 'status');

const needsTab = computed(() => Boolean(selectedChannel.value?.requires_tab));
const usesServiceLocation = computed(() => Boolean(selectedChannel.value?.uses_service_location));
const needsIdentifier = computed(() => Boolean(selectedChannel.value?.requires_customer_identifier));

const locationNumericSuffix = (location) => {
    const match = String(location?.name ?? '').match(/(\d+)\s*$/);

    return match ? Number(match[1]) : null;
};

const compareLocationsByNumber = (a, b) => {
    const aNum = locationNumericSuffix(a);
    const bNum = locationNumericSuffix(b);

    if (aNum !== null && bNum !== null && aNum !== bNum) {
        return aNum - bNum;
    }

    return String(a?.name ?? '').localeCompare(String(b?.name ?? ''), undefined, {
        numeric: true,
        sensitivity: 'base',
    });
};

const channelLocations = computed(() => {
    const channelId = selectedChannelId.value;
    if (!channelId || !usesServiceLocation.value) {
        return [];
    }

    return locationOptions.value
        .filter((location) => location.default_attendance_channel_id === channelId)
        .slice()
        .sort(compareLocationsByNumber);
});

const needsLocation = computed(() => usesServiceLocation.value && channelLocations.value.length > 0);
const missingRequiredLocation = computed(() =>
    Boolean(!props.preview && usesServiceLocation.value && channelLocations.value.length === 0),
);
const needsDeliveryAddress = computed(() => needsIdentifier.value && !usesServiceLocation.value);

const phoneDigits = (value) => String(value ?? '').replace(/\D/g, '').slice(0, 11);

const formatPhoneWithDdd = (value) => {
    const digits = phoneDigits(value);

    if (digits.length === 0) {
        return '';
    }

    if (digits.length <= 2) {
        return `(${digits}`;
    }

    if (digits.length <= 6) {
        return `(${digits.slice(0, 2)}) ${digits.slice(2)}`;
    }

    if (digits.length <= 10) {
        return `(${digits.slice(0, 2)}) ${digits.slice(2, 6)}-${digits.slice(6)}`;
    }

    return `(${digits.slice(0, 2)}) ${digits.slice(2, 7)}-${digits.slice(7, 11)}`;
};

const isValidPhoneWithDdd = (value) => {
    const length = phoneDigits(value).length;

    return length === 10 || length === 11;
};

const deliveryDataComplete = computed(() => {
    if (!needsIdentifier.value) {
        return true;
    }

    if (!customerName.value.trim() || !isValidPhoneWithDdd(customerPhone.value)) {
        return false;
    }

    if (!needsDeliveryAddress.value) {
        return true;
    }

    if (zipDigits(deliveryAddress.value.zip_code).length !== 8) {
        return false;
    }

    if (!String(deliveryAddress.value.street ?? '').trim() || !String(deliveryAddress.value.number ?? '').trim()) {
        return false;
    }

    return Boolean(String(deliveryAddress.value.neighborhood ?? '').trim());
});

const attendanceCustomer = (attendance) => attendance?.customer ?? attendance?.venue_customer ?? null;

const locationPickerLabel = (location) => {
    const name = String(location?.name ?? '').trim();
    const channelName = String(selectedChannel.value?.name ?? '').trim();

    if (!name) {
        return channelName;
    }

    if (channelName && !name.toLowerCase().startsWith(channelName.toLowerCase())) {
        return `${channelName} ${name}`;
    }

    return name;
};

const tabPickerItems = computed(() =>
    (props.tabs ?? []).map((tab) => ({
        id: tab.id,
        label: String(tab.code ?? ''),
    })),
);

const attendanceLocationByTabId = computed(() => {
    const map = {};

    (props.attendances ?? []).forEach((attendance) => {
        const tabId = attendance.tab_id ?? attendance.tab?.id;
        const locationId = attendance.service_location_id
            ?? attendance.service_location?.id
            ?? attendance.serviceLocation?.id;

        if (tabId && locationId) {
            map[tabId] = locationId;
        }
    });

    return map;
});

const registeredLocationId = (tabId) => {
    const key = tabId;
    const asString = String(tabId ?? '');

    return attendanceLocationByTabId.value[key]
        ?? attendanceLocationByTabId.value[asString]
        ?? tabLocationByTabId.value[key]
        ?? tabLocationByTabId.value[asString]
        ?? null;
};

const tabLocationById = computed(() => {
    const map = {};

    (props.tabs ?? []).forEach((tab) => {
        const locationId = registeredLocationId(tab.id);

        map[tab.id] = locationId
            ? { id: locationId, label: locationNameById(locationId) || __('another table') }
            : null;
    });

    return map;
});

const locationNameById = (locationId) => {
    const location = locationOptions.value.find((item) => String(item.id) === String(locationId));

    return location ? locationPickerLabel(location) : '';
};

const locationIdMatchingValue = (value) => {
    const needle = String(value ?? '').trim().toLowerCase();

    if (!needle) {
        return '';
    }

    const locations = locationOptions.value;
    const exact = locations.find((location) => String(location.name ?? '').trim().toLowerCase() === needle);

    if (exact) {
        return exact.id;
    }

    const labeled = locations.find((location) => locationPickerLabel(location).trim().toLowerCase() === needle);

    if (labeled) {
        return labeled.id;
    }

    const suffix = needle.match(/(\d+)\s*$/);

    if (!suffix) {
        return '';
    }

    const number = Number(suffix[1]);
    const inChannel = locations.filter((location) => (
        !selectedChannelId.value
        || String(location.default_attendance_channel_id) === String(selectedChannelId.value)
    ));
    const byNumber = inChannel.find((location) => locationNumericSuffix(location) === number);

    return byNumber?.id ?? '';
};

const applyAttendanceContext = (attendance) => {
    if (!attendance?.id) {
        return false;
    }

    clearDraftCarts();

    if (attendance.attendance_channel_id) {
        selectedChannelId.value = attendance.attendance_channel_id;
    }

    selectedTabId.value = attendance.tab_id || attendance.tab?.id || '';

    const locationId = attendance.service_location_id
        || attendance.service_location?.id
        || locationIdMatchingValue(attendance.service_location?.name)
        || locationIdMatchingValue(attendance.customer_identifier)
        || registeredLocationId(selectedTabId.value)
        || '';

    selectedLocationId.value = locationId || '';

    const customer = attendanceCustomer(attendance);
    const identifier = String(customer?.name ?? attendance.customer_identifier ?? '').trim();
    const identifierLooksLikeLocation = Boolean(
        identifier && locationId && String(locationIdMatchingValue(identifier)) === String(locationId),
    );

    customerName.value = identifierLooksLikeLocation ? String(customer?.name ?? '').trim() : identifier;
    customerPhone.value = formatPhoneWithDdd(customer?.phone ?? '');
    deliveryAddress.value = {
        ...emptyDeliveryAddress(),
        ...(attendance.delivery_address ?? attendance.deliveryAddress ?? {}),
        delivery_fee_zone_id: attendance.delivery_address?.delivery_fee_zone_id
            ?? attendance.deliveryAddress?.delivery_fee_zone_id
            ?? '',
    };

    if (selectedTabId.value && selectedLocationId.value) {
        tabLocationByTabId.value = {
            ...tabLocationByTabId.value,
            [selectedTabId.value]: selectedLocationId.value,
        };
    }

    persistSelectionMemory();

    return true;
};

const tabPickerItemFor = (tab, tone = 'default') => ({
    id: tab.id,
    label: String(tab.code ?? ''),
    tone,
});

const groupedTabPickerItems = computed(() => {
    const locationId = selectedLocationId.value;
    const tabs = props.tabs ?? [];

    if (!locationId || !tabs.length) {
        return tabs.map((tab) => tabPickerItemFor(tab, 'new'));
    }

    const tableTabs = [];
    const newTabs = [];

    tabs.forEach((tab) => {
        if (String(registeredLocationId(tab.id) ?? '') === String(locationId)) {
            tableTabs.push(tabPickerItemFor(tab));
        } else {
            newTabs.push(tabPickerItemFor(tab, 'new'));
        }
    });

    if (!newTabs.length) {
        return tableTabs;
    }

    return [
        ...tableTabs,
        { id: NEW_TABS_HEADER_ID, kind: 'header', label: __('New tabs') },
        ...newTabs,
    ];
});

const tabsOnCurrentLocation = computed(() => {
    const locationId = selectedLocationId.value;

    if (!locationId) {
        return selectedTabId.value
            ? tabPickerItems.value.filter((tab) => String(tab.id) === String(selectedTabId.value))
            : [];
    }

    return tabPickerItems.value.filter((tab) => {
        if (String(tab.id) === String(selectedTabId.value)) {
            return true;
        }

        return String(registeredLocationId(tab.id) ?? '') === String(locationId);
    });
});

const orderSummaryGroups = computed(() => {
    const locationId = selectedLocationId.value;
    const activeId = selectedTabId.value;
    const tabs = tabsOnCurrentLocation.value;
    const sources = tabs.length
        ? tabs
        : [{ id: null, label: '' }];

    const groups = sources.map((tab) => {
        const items = tab.id ? cartListFor(locationId, tab.id) : cartItems.value;
        const lines = [];

        items.forEach((item, index) => {
            const name = cartItemLabel(item) || __('Item');
            const existing = lines.find((line) => line.name === name);

            if (existing) {
                existing.quantity += Number(item.quantity) || 0;

                return;
            }

            lines.push({
                name,
                quantity: Number(item.quantity) || 0,
                index,
                tabId: tab.id,
                tabLabel: tab.label,
                cartKey: cartKeyFor(locationId, tab.id),
            });
        });

        return {
            tabId: tab.id,
            tabLabel: tab.label,
            isActive: Boolean(tab.id && String(tab.id) === String(activeId)),
            lines,
        };
    });

    return [
        ...groups.filter((group) => !group.isActive && group.lines.length > 0),
        ...groups.filter((group) => group.isActive),
    ];
});

const orderSummaryLines = computed(() =>
    orderSummaryGroups.value.flatMap((group) => group.lines),
);

const locationPickerItems = computed(() =>
    channelLocations.value.map((location) => ({
        id: location.id,
        label: locationPickerLabel(location),
    })),
);

const tabNumericSuffix = (tab) => {
    const match = String(tab?.code ?? '').match(/(\d+)$/);

    return match ? match[1] : '';
};

const locationMatchingTab = (tab) => {
    const locations = channelLocations.value;
    if (!tab || !locations.length) {
        return null;
    }

    const code = String(tab.code ?? '').toUpperCase();
    const suffix = tabNumericSuffix(tab);
    const exact = locations.find((location) => String(location.name ?? '').toUpperCase() === code)
        || locations.find((location) => String(locationPickerLabel(location)).toUpperCase() === code);
    if (exact) {
        return exact;
    }

    if (!suffix) {
        return null;
    }

    return locations.find((location) => String(locationNumericSuffix(location)) === suffix) ?? null;
};

const pendingLocationConfirmation = computed(() => (
    Boolean(suggestedLocationId.value)
    && selectedLocationId.value !== suggestedLocationId.value
));

const selectedTabLabel = computed(() =>
    tabPickerItems.value.find((item) => item.id === selectedTabId.value)?.label ?? '',
);

const selectedLocationLabel = computed(() =>
    locationPickerItems.value.find((item) => item.id === selectedLocationId.value)?.label ?? '',
);

const tabSummaryVisible = computed(() =>
    Boolean(needsTab.value && selectedTabId.value),
);

const locationSummaryVisible = computed(() =>
    Boolean(needsLocation.value && selectedLocationId.value && openPicker.value !== 'location'),
);

const showOrderPanel = computed(() => orderSummaryLines.value.length > 0);

const visibleOrderSummaryGroups = computed(() => {
    if (summarySize.value === 0) {
        return orderSummaryGroups.value.filter((group) => group.isActive);
    }

    return orderSummaryGroups.value;
});

const compactOrderSummary = computed(() => {
    const active = orderSummaryGroups.value.find((group) => group.isActive)
        ?? orderSummaryGroups.value.at(-1);
    const quantity = (active?.lines ?? []).reduce(
        (sum, line) => sum + (Number(line.quantity) || 0),
        0,
    );

    return {
        tab: active?.tabLabel || selectedTabLabel.value || '—',
        quantity,
    };
});

const newTabColumnSpan = computed(() => 1);

const shrinkSummary = () => {
    summarySize.value = Math.max(0, summarySize.value - 1);
};

const expandSummary = () => {
    summarySize.value = Math.min(2, summarySize.value + 1);
};

const payloadModifiers = (item) =>
    (item?.modifiers ?? []).map((modifier) => (
        typeof modifier === 'object'
            ? { modifier_option_id: modifier.modifier_option_id ?? modifier.id }
            : { modifier_option_id: modifier }
    ));

const pendingOrderGroups = computed(() =>
    orderSummaryGroups.value.filter((group) => group.lines.length > 0),
);

const cartForGroup = (group) => cartListFor(
    selectedLocationId.value,
    group?.tabId || selectedTabId.value,
);

const payloadFromCart = (cart) => {
    const comboIds = new Set((props.combos ?? []).map((combo) => String(combo.id)));
    const items = [];
    const combos = [];

    (cart ?? []).forEach((item) => {
        if (comboIds.has(String(item.product_id))) {
            for (let index = 0; index < (Number(item.quantity) || 1); index += 1) {
                combos.push({ combo_id: item.product_id });
            }

            return;
        }

        items.push({
            product_id: item.product_id,
            variation_id: item.variation_id ?? null,
            quantity: item.quantity,
            notes: item.notes || null,
            modifiers: payloadModifiers(item),
        });
    });

    return { items, combos };
};

const sendOrderPayloadFor = (group) => {
    const { items, combos } = payloadFromCart(cartForGroup(group));

    return {
        attendance_id: props.attendance?.id ?? null,
        attendance_channel_id: selectedChannelId.value,
        tab_id: group?.tabId || selectedTabId.value || null,
        service_location_id: selectedLocationId.value || null,
        customer_identifier: customerName.value.trim() || null,
        customer_name: customerName.value.trim() || null,
        customer_phone: phoneDigits(customerPhone.value) || null,
        delivery_address: needsDeliveryAddress.value
            ? {
                ...deliveryAddress.value,
                zip_code: zipDigits(deliveryAddress.value.zip_code),
                delivery_phone: phoneDigits(deliveryAddress.value.delivery_phone) || null,
            }
            : null,
        items,
        combos,
    };
};

const sendOrderPayloads = () => pendingOrderGroups.value
    .map((group) => ({ group, payload: sendOrderPayloadFor(group) }))
    .filter(({ payload }) => payload.items.length || payload.combos.length);

const clearCartForGroup = (group) => {
    const tabId = group?.tabId || selectedTabId.value;
    const key = cartKeyFor(selectedLocationId.value, tabId);
    const list = cartsByContext.value[key];

    if (Array.isArray(list)) {
        list.splice(0, list.length);
    }

    if (tabId && Array.isArray(cartsByContext.value[`tab:${tabId}`])) {
        cartsByContext.value[`tab:${tabId}`].splice(0, cartsByContext.value[`tab:${tabId}`].length);
    }
};

const showOrderError = (errors) => {
    const message = Object.values(errors ?? {})[0];
    toast.error(Array.isArray(message) ? message[0] : (message || __('Could not send the order.')));
};

const sendOrder = () => {
    if (!orderSummaryLines.value.length) {
        return;
    }

    if (props.preview) {
        toast.info(__('This is a preview. Orders cannot be placed from here.'));

        return;
    }

    const queued = sendOrderPayloads();

    if (!queued.length) {
        return;
    }

    if (needsTab.value && queued.every(({ payload }) => !payload.tab_id)) {
        toast.error(__('Choose a tab number'));
        openTabPicker('switch');

        return;
    }

    if (needsIdentifier.value && !customerName.value.trim()) {
        toast.error(__('Enter the name'));

        return;
    }

    if (needsIdentifier.value && !isValidPhoneWithDdd(customerPhone.value)) {
        toast.error(__('Enter the phone with area code'));

        return;
    }

    if (needsDeliveryAddress.value && zipDigits(deliveryAddress.value.zip_code).length !== 8) {
        toast.error(__('Enter the ZIP code'));

        return;
    }

    if (needsDeliveryAddress.value && !String(deliveryAddress.value.street ?? '').trim()) {
        toast.error(__('Enter the street'));

        return;
    }

    if (needsDeliveryAddress.value && !String(deliveryAddress.value.number ?? '').trim()) {
        toast.error(__('Enter the number'));

        return;
    }

    if (needsDeliveryAddress.value && !String(deliveryAddress.value.neighborhood ?? '').trim()) {
        toast.error(__('Choose a neighborhood'));

        return;
    }

    const placedForName = customerName.value.trim();
    const tableLabel = selectedLocationLabel.value || '';
    const sentTabLabels = [];

    const postNext = (index) => {
        if (index >= queued.length) {
            persistSelectionMemory();
            orderSuccessName.value = placedForName;
            orderSuccessTable.value = tableLabel;
            orderSuccessTab.value = sentTabLabels.join(', ') || selectedTabLabel.value || '';
            orderSuccessVisible.value = true;

            return;
        }

        const { group, payload } = queued[index];

        router.post(route('attendances.take-order'), payload, {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                if (group.tabLabel) {
                    sentTabLabels.push(group.tabLabel);
                }
                clearCartForGroup(group);
                persistSelectionMemory();
                postNext(index + 1);
            },
            onError: showOrderError,
        });
    };

    postNext(0);
};

const successHighlight = (value) => {
    const text = String(value ?? '').trim();
    const match = text.match(/(\d+)\s*$/);

    return match ? match[1] : text;
};

const orderSuccessTableHighlight = computed(() => successHighlight(orderSuccessTable.value));
const orderSuccessTabHighlight = computed(() => successHighlight(orderSuccessTab.value));

const closeOrderSuccess = () => {
    orderSuccessVisible.value = false;
    orderSuccessName.value = '';
    orderSuccessTable.value = '';
    orderSuccessTab.value = '';
};

const clearDraftCarts = () => {
    cartsByContext.value = {};
    persistSelectionMemory();
};

const resetWorkingContext = () => {
    selectedTabId.value = '';
    selectedLocationId.value = '';
    suggestedLocationId.value = '';
    customerName.value = '';
    customerPhone.value = '';
    deliveryAddress.value = emptyDeliveryAddress();
    savedAddresses.value = [];
    openPicker.value = null;
    closeTabPicker();
    orderSuccessVisible.value = false;
    orderSuccessName.value = '';
    orderSuccessTable.value = '';
    orderSuccessTab.value = '';
    clearDraftCarts();

    if (selectedChannelId.value) {
        selectionByChannel.value = {
            ...selectionByChannel.value,
            [selectedChannelId.value]: emptySelection(),
        };
    }

    persistSelectionMemory();
};

const promptOrderContext = () => {
    if (needsLocation.value && !selectedLocationId.value) {
        setPicker('location', true);

        return;
    }

    if (needsTab.value && !selectedTabId.value) {
        openTabPicker('switch');
    }
};

const startChannelFor = (mode) => {
    if (mode === 'order' || mode === 'table') {
        return (props.channels ?? []).find((item) => item.uses_service_location && item.requires_tab)
            ?? (props.channels ?? []).find((item) => item.uses_service_location)
            ?? (props.channels ?? []).find((item) => item.requires_tab)
            ?? props.channels?.[0];
    }

    return (props.channels ?? []).find((item) => item.requires_tab)
        ?? props.channels?.[0];
};

const applyStartMode = (mode) => {
    liveStartMode.value = mode;
    mainTab.value = 'order';

    const channel = startChannelFor(mode);

    if (channel) {
        selectChannel(channel.id);
    }

    resetWorkingContext();
    nextTick(() => promptOrderContext());
};

const startNew = (mode) => {
    applyStartMode(mode);
};

const addLocationPickerItems = computed(() => {
    const used = new Set(
        channelLocations.value
            .map((location) => locationNumericSuffix(location))
            .filter((value) => value !== null),
    );

    return Array.from({ length: 50 }, (_, index) => index + 1)
        .filter((number) => !used.has(number))
        .map((number) => ({
            id: String(number),
            label: String(number),
        }));
});

const openAddLocationPicker = () => {
    setPicker('add-location', true);
};

const registerLocationNumber = async (value) => {
    const number = Number(value);
    if (!number || registeringLocation.value || props.preview || !selectedChannelId.value) {
        return;
    }

    registeringLocation.value = true;
    setPicker('add-location', false);

    try {
        const { data } = await axios.post(route('attendances.locations.store'), {
            attendance_channel_id: selectedChannelId.value,
            number,
        });
        const location = data?.location;

        if (location?.id && !locationOptions.value.some((item) => item.id === location.id)) {
            locationOptions.value = [...locationOptions.value, location];
        }

        if (location?.id) {
            chooseLocation(location.id);
        }
    } catch (error) {
        const message = error?.response?.data?.errors?.number?.[0]
            || error?.response?.data?.message
            || __('Could not add the consumption location.');
        toast.error(message);
    } finally {
        registeringLocation.value = false;
    }
};

const requirementColumnsClass = computed(() => {
    const hasOrder = showOrderPanel.value;
    const hasLocation = needsLocation.value || missingRequiredLocation.value;
    const hasTab = needsTab.value;

    if (hasOrder && hasLocation && hasTab) {
        return 'grid-cols-[auto_auto_minmax(0,1fr)]';
    }

    if (hasOrder && (hasLocation || hasTab)) {
        return 'grid-cols-[auto_minmax(0,1fr)]';
    }

    if (hasLocation && hasTab) {
        return 'grid-cols-2';
    }

    return 'grid-cols-1';
});

const selectionMemoryKey = computed(() => `neurabar.attendant-context.${props.venue?.id ?? 'preview'}`);

const emptySelection = () => ({
    tabId: '',
    locationId: '',
    name: '',
    phone: '',
    deliveryAddress: emptyDeliveryAddress(),
    suggestedLocationId: '',
});

const captureSelection = () => ({
    tabId: selectedTabId.value,
    locationId: selectedLocationId.value,
    name: customerName.value,
    phone: customerPhone.value,
    deliveryAddress: { ...deliveryAddress.value },
    suggestedLocationId: suggestedLocationId.value,
});

const applySelection = (saved) => {
    const next = saved ?? emptySelection();
    selectedTabId.value = next.tabId ?? '';
    selectedLocationId.value = next.locationId ?? '';
    customerName.value = next.name ?? next.identifier ?? '';
    customerPhone.value = formatPhoneWithDdd(next.phone ?? '');
    deliveryAddress.value = { ...emptyDeliveryAddress(), ...(next.deliveryAddress ?? {}) };
    suggestedLocationId.value = next.suggestedLocationId ?? '';
};

const persistSelectionMemory = () => {
    if (selectedChannelId.value) {
        selectionByChannel.value = {
            ...selectionByChannel.value,
            [selectedChannelId.value]: captureSelection(),
        };
    }

    try {
        sessionStorage.setItem(selectionMemoryKey.value, JSON.stringify({
            channelId: selectedChannelId.value,
            byChannel: selectionByChannel.value,
            cartsByContext: cartsByContext.value,
            tabLocationByTabId: tabLocationByTabId.value,
        }));
    } catch {
        // Private mode or quota: keep the in-memory copy only.
    }
};

const selectChannel = (channelId) => {
    if (channelId === selectedChannelId.value) {
        return;
    }

    if (selectedChannelId.value) {
        selectionByChannel.value[selectedChannelId.value] = captureSelection();
    }

    selectedChannelId.value = channelId;
    applySelection(selectionByChannel.value[channelId]);
    statusTabId.value = '';
    statusLocationId.value = '';
    statusIdentifierId.value = '';
    statusLookupBy.value = '';
    closeTabPicker();
    persistSelectionMemory();
};

const HEADER_COMPACT_AFTER_PX = 80;
const HEADER_EXPAND_BEFORE_PX = 4;

const updateHeaderCompact = () => {
    const y = window.scrollY;

    if (y > HEADER_COMPACT_AFTER_PX) {
        headerCompact.value = true;

        return;
    }

    if (y <= HEADER_EXPAND_BEFORE_PX) {
        headerCompact.value = false;
    }
};

const updateCatalogStickyTop = () => {
    if (props.preview) {
        catalogStickyTop.value = null;

        return;
    }

    const nav = document.querySelector('[data-app-nav]');
    const pageHeader = document.querySelector('[data-page-header]');
    const offset = (nav?.getBoundingClientRect().height ?? 0)
        + (pageHeader?.getBoundingClientRect().height ?? 0);

    catalogStickyTop.value = `${offset > 0 ? offset : 64}px`;
};

const focusCustomerSection = () => {
    document.querySelector('[data-customer-section]')?.scrollIntoView({
        behavior: 'smooth',
        block: 'start',
    });
};

const closeTabPicker = () => {
    if (openPicker.value === 'tab') {
        openPicker.value = null;
    }

    tabPickerMode.value = null;
    movingLineName.value = '';
    movingCartKey.value = '';
    movingCartIndex.value = null;
};

const openTabPicker = (mode) => {
    mainTab.value = 'order';
    tabPickerMode.value = mode;
    movingLineName.value = '';
    setPicker('tab', true);
};

const openNewTabPicker = () => {
    openTabPicker('switch');
};

const startMoveOrderLine = (line) => {
    if (!needsTab.value || !tabPickerItems.value.length) {
        openCartItem(line.index);

        return;
    }

    mainTab.value = 'order';
    tabPickerMode.value = 'move';
    movingLineName.value = line.name;
    movingCartKey.value = line.cartKey || currentCartKey.value;
    movingCartIndex.value = Number.isInteger(line.index) ? line.index : null;
    setPicker('tab', true);
};

const selectWorkingTab = (tabId) => {
    if (!tabId) {
        return;
    }

    tabPickerMode.value = 'switch';
    movingLineName.value = '';
    movingCartKey.value = '';
    movingCartIndex.value = null;

    if (String(tabId) === String(selectedTabId.value)) {
        closeTabPicker();

        return;
    }

    chooseTab(tabId);
};

const ensureIndependentTabCart = (tabId, { migrateLocationCart = false } = {}) => {
    const locationId = selectedLocationId.value;
    const key = cartKeyFor(locationId, tabId);

    if (Array.isArray(cartsByContext.value[key]) || Array.isArray(cartsByContext.value[`tab:${tabId}`])) {
        return;
    }

    let next = [];
    const locKey = locationId ? `loc:${locationId}` : '';
    const locItems = locKey ? cartsByContext.value[locKey] : null;

    if (migrateLocationCart && Array.isArray(locItems) && locItems.length) {
        next = [...locItems];
        cartsByContext.value = {
            ...cartsByContext.value,
            [locKey]: [],
            [key]: next,
        };

        return;
    }

    cartsByContext.value = {
        ...cartsByContext.value,
        [key]: [],
    };
};

const openContextPicker = (name) => {
    mainTab.value = 'order';

    if (name === 'tab') {
        tabPickerMode.value = 'switch';
    }

    setPicker(name, true);
};

watch(
    [selectedTabId, selectedLocationId, customerName, customerPhone, deliveryAddress, suggestedLocationId],
    persistSelectionMemory,
    { deep: true },
);

watch(
    () => props.deliveryNeighborhoods,
    (list) => {
        neighborhoodOptions.value = [...(list ?? [])];
    },
);

watch(
    () => props.serviceLocations,
    (list) => {
        locationOptions.value = [...(list ?? [])];
    },
);

const onNeighborhoodRegistered = (neighborhood) => {
    if (!neighborhood?.id) {
        return;
    }

    if (!neighborhoodOptions.value.some((item) => item.id === neighborhood.id)) {
        neighborhoodOptions.value = [...neighborhoodOptions.value, neighborhood];
    }
};

watch(customerPhone, (value) => {
    window.clearTimeout(customerLookupTimer);

    if (!needsDeliveryAddress.value || props.preview || !isValidPhoneWithDdd(value)) {
        savedAddresses.value = [];

        return;
    }

    const digits = phoneDigits(value);

    customerLookupTimer = window.setTimeout(async () => {
        try {
            const { data } = await axios.get(route('attendances.customers.lookup'), {
                params: { phone: digits },
            });

            if (data?.customer) {
                if (!customerName.value.trim()) {
                    customerName.value = data.customer.name ?? '';
                }

                savedAddresses.value = data.customer.addresses ?? [];
            } else {
                savedAddresses.value = [];
            }
        } catch {
            savedAddresses.value = [];
        }
    }, 400);
});

watch(mainTab, () => {
    openPicker.value = null;
});

onMounted(() => {
    updateHeaderCompact();
    window.addEventListener('scroll', updateHeaderCompact, { passive: true });
    window.addEventListener('resize', updateCatalogStickyTop, { passive: true });
    nextTick(() => {
        updateCatalogStickyTop();

        if (typeof ResizeObserver === 'undefined' || props.preview) {
            return;
        }

        stickyOffsetObserver = new ResizeObserver(updateCatalogStickyTop);
        const nav = document.querySelector('[data-app-nav]');
        const pageHeader = document.querySelector('[data-page-header]');

        if (nav) {
            stickyOffsetObserver.observe(nav);
        }

        if (pageHeader) {
            stickyOffsetObserver.observe(pageHeader);
        }
    });

    let storedTabLocations = {};

    try {
        const parsed = JSON.parse(sessionStorage.getItem(selectionMemoryKey.value) || 'null');
        if (parsed && typeof parsed === 'object') {
            selectionByChannel.value = parsed.byChannel && typeof parsed.byChannel === 'object'
                ? parsed.byChannel
                : {};

            cartsByContext.value = parsed.cartsByContext && typeof parsed.cartsByContext === 'object'
                ? parsed.cartsByContext
                : {};

            storedTabLocations = parsed.tabLocationByTabId && typeof parsed.tabLocationByTabId === 'object'
                ? parsed.tabLocationByTabId
                : {};

            const rememberedChannel = (props.channels ?? []).find((channel) => channel.id === parsed.channelId);
            if (rememberedChannel) {
                selectedChannelId.value = rememberedChannel.id;
            }

            applySelection(selectionByChannel.value[selectedChannelId.value]);
        }
    } catch {
        // Ignore invalid stored context.
    }

    tabLocationByTabId.value = {
        ...attendanceLocationByTabId.value,
        ...storedTabLocations,
    };

    if (applyAttendanceContext(props.attendance)) {
        return;
    }

    if (props.preview) {
        return;
    }

    if (props.startMode && props.startMode !== 'order') {
        applyStartMode(props.startMode);

        return;
    }

    if (!selectedChannelId.value) {
        const channel = startChannelFor('order');

        if (channel) {
            selectChannel(channel.id);
        }
    }
});

onUnmounted(() => {
    stickyOffsetObserver?.disconnect();
    window.removeEventListener('scroll', updateHeaderCompact);
    window.removeEventListener('resize', updateCatalogStickyTop);
});

const rememberTabLocation = (tabId) => {
    if (!tabId || !selectedLocationId.value) {
        return;
    }

    tabLocationByTabId.value = {
        ...tabLocationByTabId.value,
        [tabId]: selectedLocationId.value,
    };
};

const warnIfTabOnOtherLocation = (tabId) => {
    const registeredId = registeredLocationId(tabId);
    if (!tabId || !selectedLocationId.value || !registeredId) {
        return;
    }

    if (String(registeredId) === String(selectedLocationId.value)) {
        return;
    }

    const locationName = locationNameById(registeredId) || __('another table');

    toast.warning(__('This tab is already registered at :location. You can still take the order.', {
        location: locationName,
    }));
};

const moveOrderLineToTab = (tabId, locationId = null) => {
    const name = movingLineName.value;
    const index = movingCartIndex.value;
    const currentId = selectedTabId.value;
    const fromKey = movingCartKey.value || currentCartKey.value;
    const destLocationId = locationId || selectedLocationId.value;

    closeTabPicker();

    if (!tabId || String(tabId) === String(currentId)) {
        return;
    }

    const toKey = cartKeyFor(destLocationId, tabId);
    const source = [...(cartsByContext.value[fromKey] ?? [])];
    const destination = [...(cartsByContext.value[toKey] ?? [])];
    let remaining = source;

    if (index !== null && source[index]) {
        const [item] = source.splice(index, 1);
        remaining = source;
        mergeGuestCartItem(destination, {
            ...item,
            modifiers: [...(item.modifiers ?? [])],
            modifier_details: [...(item.modifier_details ?? [])],
        });
    } else if (name) {
        remaining = [];
        source.forEach((item) => {
            if ((cartItemLabel(item) || __('Item')) === name) {
                mergeGuestCartItem(destination, {
                    ...item,
                    modifiers: [...(item.modifiers ?? [])],
                    modifier_details: [...(item.modifier_details ?? [])],
                });

                return;
            }

            remaining.push(item);
        });
    } else {
        return;
    }

    cartsByContext.value = {
        ...cartsByContext.value,
        [fromKey]: remaining,
        [toKey]: destination,
    };
    persistSelectionMemory();
};

const chooseTab = (tabId) => {
    if (!tabId || tabId === NEW_TABS_HEADER_ID) {
        return;
    }

    const keepLocation = Boolean(selectedLocationId.value);
    const previousTabId = selectedTabId.value;

    warnIfTabOnOtherLocation(tabId);

    if (tabPickerMode.value === 'move') {
        moveOrderLineToTab(tabId);
        rememberTabLocation(tabId);
        persistSelectionMemory();

        return;
    }

    const tab = (props.tabs ?? []).find((item) => item.id === tabId);
    selectedTabId.value = tabId || '';

    if (keepLocation) {
        rememberTabLocation(tabId);
        ensureIndependentTabCart(tabId, {
            migrateLocationCart: !previousTabId || String(previousTabId) === String(tabId),
        });
        persistSelectionMemory();
    } else {
        selectedLocationId.value = '';
        suggestedLocationId.value = locationMatchingTab(tab)?.id ?? '';
    }

    closeTabPicker();
};

const chooseLocation = (locationId) => {
    selectedLocationId.value = locationId || '';
    openPicker.value = null;
    persistSelectionMemory();

    if (needsTab.value && !selectedTabId.value) {
        nextTick(() => openTabPicker('switch'));
    }
};

const setPicker = (name, isOpen) => {
    openPicker.value = isOpen ? name : (openPicker.value === name ? null : openPicker.value);

    if (!isOpen && name === 'tab') {
        tabPickerMode.value = null;
        movingLineName.value = '';
        movingCartKey.value = '';
        movingCartIndex.value = null;
    }
};

const channelTabStyle = (channel, selected) => {
    const hex = channel?.color;
    if (!hex) {
        return {};
    }

    if (selected) {
        return { backgroundColor: hex, color: '#fff', borderColor: hex, opacity: '1' };
    }

    return {
        color: '#9ca3af',
        borderColor: '#e5e7eb',
        backgroundColor: hexToRgba(hex, 0.05) || '#f3f4f6',
        opacity: '0.4',
    };
};

function comboDescription(combo) {
    if (combo.description) {
        return combo.description;
    }

    return (combo.items ?? []).map((item) => {
        const name = item.product?.name ?? '';
        const variation = item.variation?.name ? ` — ${item.variation.name}` : '';
        const quantity = item.quantity > 1 ? ` ×${item.quantity}` : '';

        return `${name}${variation}${quantity}`;
    }).filter(Boolean).join(', ');
}

function findProduct(productId) {
    for (const category of catalogCategories.value) {
        const product = category.products?.find((item) => item.id === productId);
        if (product) {
            return product;
        }
    }

    return null;
}

function openProduct(product) {
    editingCartIndex.value = null;
    selectedProduct.value = product;
    showProductDrawer.value = true;
}

function openCartItem(index) {
    const item = cartItems.value[index];
    const product = findProduct(item?.product_id);
    if (!product) {
        return;
    }

    editingCartIndex.value = index;
    selectedProduct.value = product;
    showProductDrawer.value = true;
}

function openOrderLine(line) {
    closeTabPicker();

    if (line?.tabId) {
        selectWorkingTab(line.tabId);
    }

    openCartItem(line.index);
}

function removeEditingItem() {
    if (editingCartIndex.value === null) {
        return;
    }

    const key = currentCartKey.value;
    const list = [...ensureCurrentCart()];
    list.splice(editingCartIndex.value, 1);
    cartsByContext.value = {
        ...cartsByContext.value,
        [key]: list,
    };
    editingCartIndex.value = null;
    showProductDrawer.value = false;
    persistSelectionMemory();
}

function changeEditingItemTab(tabId, locationId = null) {
    const item = editingItem.value;
    const index = editingCartIndex.value;

    if (!item || index === null || !tabId) {
        return;
    }

    movingLineName.value = cartItemLabel(item) || __('Item');
    movingCartKey.value = currentCartKey.value;
    movingCartIndex.value = index;
    moveOrderLineToTab(tabId, locationId);

    if (locationId && String(locationId) !== String(selectedLocationId.value)) {
        selectedLocationId.value = locationId;
    }

    selectedTabId.value = tabId;

    if (locationId && !registeredLocationId(tabId)) {
        tabLocationByTabId.value = {
            ...tabLocationByTabId.value,
            [tabId]: locationId,
        };
    }

    editingCartIndex.value = null;
    showProductDrawer.value = false;
    persistSelectionMemory();
}

function addToCart(item) {
    const list = ensureCurrentCart();

    if (editingCartIndex.value !== null) {
        list.splice(editingCartIndex.value, 1, item);
        editingCartIndex.value = null;
        persistSelectionMemory();

        return;
    }

    mergeGuestCartItem(list, item);
    persistSelectionMemory();
}

const attendanceHeading = (attendance) => {
    const parts = [];

    if (attendance?.tab?.code) {
        parts.push(`${__('Tab')} ${attendance.tab.code}`);
    }

    if (attendance?.service_location?.name) {
        parts.push(attendance.service_location.name);
    }

    if (attendance?.customer_identifier) {
        parts.push(attendance.customer_identifier);
    }

    return parts.join(' · ') || attendance?.attendance_channel?.name || __('Order');
};

const attendanceItems = (attendance) =>
    (attendance?.orders ?? []).flatMap((order) => order.items ?? []);

const attendanceHasItems = (attendance) => attendanceItems(attendance).length > 0;

const attendancesForTab = (tabId) =>
    (props.attendances ?? []).filter((attendance) =>
        String(attendance.tab_id ?? attendance.tab?.id) === String(tabId),
    );

const attendancesForLocation = (locationId) =>
    (props.attendances ?? []).filter((attendance) =>
        String(attendance.service_location_id ?? attendance.service_location?.id) === String(locationId),
    );

const statusTabOptions = computed(() =>
    (props.tabs ?? []).map((tab) => ({
        id: tab.id,
        label: String(tab.code ?? ''),
        hasOrders: attendancesForTab(tab.id).some(attendanceHasItems),
    })),
);

const statusLocationOptions = computed(() =>
    channelLocations.value.map((location) => ({
        id: location.id,
        label: locationPickerLabel(location),
        hasOrders: attendancesForLocation(location.id).some(attendanceHasItems),
    })),
);

const statusIdentifierOptions = computed(() => {
    const seen = new Set();
    const options = [];

    (props.attendances ?? []).forEach((attendance) => {
        const customer = attendanceCustomer(attendance);
        const label = String(customer?.name ?? attendance.customer_identifier ?? '').trim();
        const key = label.toLowerCase();

        if (!label || seen.has(key)) {
            return;
        }

        seen.add(key);
        options.push({
            id: label,
            label,
            hasOrders: attendanceHasItems(attendance),
        });
    });

    return options;
});

const groupStatusPickerItems = (options) => {
    const withOrders = options
        .filter((option) => option.hasOrders)
        .map((option) => ({ id: option.id, label: option.label }));
    const withoutOrders = options
        .filter((option) => !option.hasOrders)
        .map((option) => ({ id: option.id, label: option.label, tone: 'muted' }));
    const items = [];

    if (withOrders.length) {
        items.push({
            id: STATUS_WITH_ORDERS_HEADER_ID,
            kind: 'header',
            label: __('With orders'),
        });
        items.push(...withOrders);
    }

    if (withoutOrders.length) {
        items.push({
            id: STATUS_WITHOUT_ORDERS_HEADER_ID,
            kind: 'header',
            label: __('Without orders'),
            tone: 'muted',
        });
        items.push(...withoutOrders);
    }

    return items;
};

const statusTabPickerItems = computed(() => groupStatusPickerItems(statusTabOptions.value));
const statusLocationPickerItems = computed(() => {
    const items = groupStatusPickerItems(statusLocationOptions.value);
    const locatedId = statusLocationId.value;

    if (!locatedId || statusLocationOptions.value.some((option) => String(option.id) === String(locatedId))) {
        return items;
    }

    return [
        {
            id: locatedId,
            label: locationNameById(locatedId) || __('another table'),
        },
        ...items,
    ];
});
const statusIdentifierPickerItems = computed(() => groupStatusPickerItems(statusIdentifierOptions.value));

const locateStatusLocationForTab = (tabId) => {
    const fromOpen = attendancesForTab(tabId).find((attendance) => (
        attendance.service_location_id ?? attendance.service_location?.id
    ));

    if (fromOpen) {
        return fromOpen.service_location_id ?? fromOpen.service_location?.id ?? '';
    }

    return registeredLocationId(tabId) ?? '';
};

const chooseStatusTab = (id) => {
    statusLookupBy.value = 'tab';
    statusTabId.value = id;
    statusIdentifierId.value = '';
    statusLocationId.value = locateStatusLocationForTab(id) || '';
};

const chooseStatusLocation = (id) => {
    statusLookupBy.value = 'location';
    statusLocationId.value = id;
    statusTabId.value = '';
    statusIdentifierId.value = '';
};

const chooseStatusIdentifier = (id) => {
    statusLookupBy.value = 'identifier';
    statusIdentifierId.value = id;
    statusTabId.value = '';
    statusLocationId.value = '';
};

const hasStatusOptions = computed(() =>
    statusTabOptions.value.length > 0
    || statusLocationOptions.value.length > 0
    || statusIdentifierOptions.value.length > 0,
);

const statusLookupActive = computed(() => Boolean(statusLookupBy.value));

const statusLookupColumnsClass = computed(() => {
    const count = [needsTab.value, needsLocation.value, needsIdentifier.value].filter(Boolean).length;

    return count >= 2 ? 'grid-cols-2' : 'grid-cols-1';
});

const statusLookupHint = computed(() => {
    if (needsTab.value && needsLocation.value) {
        return __('Type the tab or the table to see the status.');
    }

    if (needsTab.value) {
        return __('Type the tab to see the status.');
    }

    if (needsLocation.value) {
        return __('Type the table to see the status.');
    }

    if (needsIdentifier.value) {
        return __('Type the customer identifier to see the status.');
    }

    return __('No orders in progress');
});

const visibleStatusAttendances = computed(() => {
    if (statusLookupBy.value === 'tab' && statusTabId.value) {
        return attendancesForTab(statusTabId.value);
    }

    if (statusLookupBy.value === 'location' && statusLocationId.value) {
        return attendancesForLocation(statusLocationId.value);
    }

    if (statusLookupBy.value === 'identifier' && statusIdentifierId.value) {
        return (props.attendances ?? []).filter((attendance) => {
            const customer = attendanceCustomer(attendance);
            const label = String(customer?.name ?? attendance.customer_identifier ?? '').trim();

            return label === String(statusIdentifierId.value);
        });
    }

    return [];
});

const selectedStatusOption = (options, selectedId) =>
    options.find((option) => String(option.id) === String(selectedId)) ?? null;

const locatedStatusLocationLabel = computed(() =>
    selectedStatusOption(statusLocationOptions.value, statusLocationId.value)?.label
    || locationNameById(statusLocationId.value)
    || '',
);

const statusResultCards = computed(() => {
    if (!statusLookupActive.value) {
        return [];
    }

    const cards = visibleStatusAttendances.value.map((attendance) => ({
        key: `att-${attendance.id}`,
        attendance,
        heading: attendanceHeading(attendance),
        empty: !attendanceHasItems(attendance),
    }));

    if (cards.length) {
        return cards;
    }

    if (statusLookupBy.value === 'tab') {
        const option = selectedStatusOption(statusTabOptions.value, statusTabId.value);
        if (option) {
            cards.push({
                key: `tab-${option.id}`,
                attendance: null,
                heading: [`${__('Tab')} ${option.label}`, locatedStatusLocationLabel.value].filter(Boolean).join(' · '),
                empty: true,
            });
        }
    } else if (statusLookupBy.value === 'location') {
        const option = selectedStatusOption(statusLocationOptions.value, statusLocationId.value);
        if (option) {
            cards.push({
                key: `loc-${option.id}`,
                attendance: null,
                heading: option.label,
                empty: true,
            });
        }
    } else if (statusLookupBy.value === 'identifier') {
        const option = selectedStatusOption(statusIdentifierOptions.value, statusIdentifierId.value);
        if (option) {
            cards.push({
                key: `id-${option.id}`,
                attendance: null,
                heading: option.label,
                empty: true,
            });
        }
    }

    return cards;
});

const itemIsDelivered = (item) => Boolean(item?.ready_at) || Boolean(item?.preparation_status?.is_final);

const undeliveredItems = (attendance) => attendanceItems(attendance).filter((item) => !itemIsDelivered(item));

const deliveredItems = (attendance) => attendanceItems(attendance).filter((item) => itemIsDelivered(item));

const itemName = (item) => item?.product?.name ?? item?.combo?.name ?? '';
</script>

<template>
    <component
        :is="preview ? GuestLayout : AppLayout"
        :title="venue.name + ' — ' + (preview ? __('Attendant version') : __('New Attendance'))"
        v-bind="preview ? { venue, previewWatermark: __('Attendant version') } : {}"
    >
        <template v-if="preview" #headerTitle>{{ __('Attendant version') }}</template>
        <template v-if="preview" #headerSubtitle>{{ __('Adaptive screen for phone, tablet or computer') }}</template>
        <template v-if="preview" #header>
            <div class="flex flex-wrap items-center justify-end gap-2">
                <AppButton size="sm" variant="accent" :href="route('menu.preview.customer.delivery')">{{ __('Customer version - Delivery') }}</AppButton>
                <AppButton size="sm" :href="route('menu.index')">{{ __('Back') }}</AppButton>
            </div>
        </template>

        <p
            v-if="!preview"
            data-live-side-title
            class="pointer-events-none fixed left-6 top-28 z-0 hidden select-none whitespace-pre-line font-heading text-[clamp(4rem,8vw,8.5rem)] font-bold leading-[0.85] tracking-tight text-ocean-deep/[0.08] lg:block dark:text-white/10"
            aria-hidden="true"
        >{{ __('New\nAttendance') }}</p>

        <div :class="preview ? '' : 'mx-auto w-full max-w-lg'">
        <GuestMenuCatalog
            :venue="venue"
            :categories="catalogCategories"
            :preview="preview"
            :show-category-chips="isOrderMode"
            :empty-description="__('This venue has no menu items yet.')"
            :sticky-panel-style="channelPanelStyle"
            :sticky-top="catalogStickyTop"
            @open-product="openProduct"
        >
            <template #above-categories>
                <div
                    class="grid grid-cols-2 gap-1 [overflow-anchor:none]"
                    :class="headerCompact ? 'mb-0' : 'mb-1'"
                    role="tablist"
                    data-mode-tabs
                    :data-compact-header="headerCompact"
                    :aria-label="__('New order')"
                >
                    <button
                        type="button"
                        role="tab"
                        data-mode-order
                        :aria-selected="isOrderMode"
                        class="touch-manipulation rounded-t-lg border transition-colors focus:outline-none focus:ring-2 focus:ring-primary"
                        :class="[
                            headerCompact ? 'min-h-8 px-2 py-1 text-sm font-semibold' : 'min-h-16 px-3 py-3 text-lg font-bold',
                            isOrderMode ? 'bg-ocean-deep text-white border-ocean-deep opacity-100' : 'bg-gray-100 text-gray-400 border-gray-200 opacity-40',
                        ]"
                        @click="mainTab = 'order'"
                    >
                        {{ __('New order') }}
                    </button>
                    <button
                        type="button"
                        role="tab"
                        data-mode-status
                        :aria-selected="isStatusMode"
                        class="touch-manipulation rounded-t-lg border transition-colors focus:outline-none focus:ring-2 focus:ring-primary"
                        :class="[
                            headerCompact ? 'min-h-8 px-2 py-1 text-sm font-semibold' : 'min-h-16 px-3 py-3 text-lg font-bold',
                            isStatusMode ? 'bg-ocean-deep text-white border-ocean-deep opacity-100' : 'bg-gray-100 text-gray-400 border-gray-200 opacity-40',
                        ]"
                        @click="mainTab = 'status'"
                    >
                        {{ __('Check status') }}
                    </button>
                </div>
                <div
                    v-if="channels.length"
                    class="flex gap-1 overflow-x-auto pb-px [overflow-anchor:none]"
                    role="tablist"
                    data-channel-tabs
                    :aria-label="__('Channel')"
                >
                    <button
                        v-for="channel in channels"
                        :key="channel.id"
                        type="button"
                        role="tab"
                        :aria-selected="selectedChannelId === channel.id"
                        class="min-w-[4.5rem] flex-1 touch-manipulation rounded-t-lg border transition-colors focus:outline-none focus:ring-2 focus:ring-primary"
                        :class="[
                            headerCompact ? 'min-h-8 px-2 py-1 text-xs font-semibold' : 'min-h-12 px-3 py-2.5 text-sm font-semibold',
                            {
                                'bg-primary text-white opacity-100': selectedChannelId === channel.id && !channel.color,
                                'bg-gray-100 text-gray-400 border-gray-200 opacity-40': selectedChannelId !== channel.id && !channel.color,
                            },
                        ]"
                        :style="channelTabStyle(channel, selectedChannelId === channel.id)"
                        @click="selectChannel(channel.id)"
                    >
                        {{ channel.name }}
                    </button>
                </div>
                <section
                    v-if="isOrderMode && needsIdentifier"
                    data-customer-section
                    data-customer-fields
                    class="mt-2 space-y-2 rounded-md border border-border bg-slate-50 p-2"
                >
                    <div class="grid gap-2 sm:grid-cols-2">
                        <div class="min-w-0">
                            <label class="mb-1 block text-xs font-semibold text-ocean-deep">
                                {{ __('Name') }} <span class="text-destructive">*</span>
                            </label>
                            <input
                                v-model="customerName"
                                type="text"
                                data-customer-name
                                autocomplete="name"
                                :placeholder="__('e.g. John')"
                                class="w-full rounded-md border border-border px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                            />
                        </div>
                        <div class="min-w-0">
                            <label class="mb-1 block text-xs font-semibold text-ocean-deep">
                                {{ __('Phone with area code') }} <span class="text-destructive">*</span>
                            </label>
                            <input
                                :value="customerPhone"
                                type="tel"
                                inputmode="tel"
                                data-customer-phone
                                autocomplete="tel"
                                :placeholder="__('(00) 00000-0000')"
                                class="w-full rounded-md border border-border px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                                @input="customerPhone = formatPhoneWithDdd($event.target.value)"
                            />
                        </div>
                        <DeliveryAddressFields
                            v-if="needsDeliveryAddress"
                            v-model="deliveryAddress"
                            :neighborhoods="neighborhoodOptions"
                            :saved-addresses="savedAddresses"
                            :venue-city="venue?.city"
                            :venue-state="venue?.state"
                            :can-register="!preview"
                            @registered="onNeighborhoodRegistered"
                        />
                    </div>
                </section>
            </template>
            <template #after-catalog>
                <div v-if="isStatusMode" class="space-y-3 pt-1" data-status-board>
                    <AppEmptyState
                        v-if="!statusLookupActive && !hasStatusOptions"
                        :title="statusLookupHint"
                        :description="__('Open a new order to follow the kitchen status here.')"
                    />
                    <AppEmptyState
                        v-else-if="statusLookupActive && !statusResultCards.length"
                        :title="__('No orders found for this search.')"
                        :description="__('Check the tab or table number and try again.')"
                    />
                    <article
                        v-for="card in statusResultCards"
                        :key="card.key"
                        class="rounded-xl bg-white p-3 shadow-card"
                        data-status-summary
                        :data-status-empty="card.empty"
                    >
                        <div class="mb-2 flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p class="font-heading text-sm font-bold leading-tight text-ocean-deep">{{ card.heading }}</p>
                                <p v-if="card.attendance?.attendance_channel?.name" class="mt-0.5 text-[11px] text-muted-foreground">
                                    {{ card.attendance.attendance_channel.name }}
                                </p>
                                <p
                                    v-if="card.attendance?.delivery_address?.line"
                                    data-delivery-address-line
                                    class="mt-1 text-xs leading-snug text-ocean-deep"
                                >
                                    <span>{{ card.attendance.delivery_address.street }}{{ card.attendance.delivery_address.number ? `, ${card.attendance.delivery_address.number}` : '' }}{{ card.attendance.delivery_address.complement ? `, ${card.attendance.delivery_address.complement}` : '' }}</span>
                                    <span v-if="card.attendance.delivery_address.neighborhood" class="font-bold"> – {{ card.attendance.delivery_address.neighborhood }}</span>
                                    <span v-if="card.attendance.delivery_address.reference_point" class="font-bold"> – (ref: {{ card.attendance.delivery_address.reference_point }})</span>
                                    <span v-if="card.attendance.delivery_address.delivery_phone"> – Tel: {{ card.attendance.delivery_address.delivery_phone }}</span>
                                </p>
                                <a
                                    v-if="card.attendance?.delivery_address?.maps_url"
                                    :href="card.attendance.delivery_address.maps_url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    data-open-in-maps
                                    class="mt-1 inline-block text-[11px] font-semibold text-primary underline"
                                >
                                    {{ __('Open in maps') }}
                                </a>
                            </div>
                            <AppBadge
                                v-if="card.attendance"
                                :label="__('Open')"
                                color="#22c55e"
                            />
                            <AppBadge
                                v-else
                                :label="__('Without orders')"
                                color="#94a3b8"
                            />
                        </div>
                        <p v-if="card.empty" class="text-xs text-muted-foreground">
                            {{ __('No orders yet') }}
                        </p>
                        <div v-else class="space-y-3">
                            <section v-if="undeliveredItems(card.attendance).length" data-undelivered-items>
                                <h3 class="mb-1 text-[11px] font-bold uppercase tracking-wide text-amber-700">{{ __('Not delivered') }}</h3>
                                <div class="divide-y divide-muted">
                                    <div
                                        v-for="item in undeliveredItems(card.attendance)"
                                        :key="item.id"
                                        class="flex items-center justify-between gap-2 py-1.5"
                                    >
                                        <p class="min-w-0 truncate text-sm font-medium text-ocean-deep">
                                            {{ item.quantity }}× {{ itemName(item) }}
                                        </p>
                                        <AppBadge
                                            v-if="item.preparation_status"
                                            :label="item.preparation_status.name"
                                            :color="item.preparation_status.color"
                                        />
                                    </div>
                                </div>
                            </section>
                            <section v-if="deliveredItems(card.attendance).length" data-delivered-items>
                                <h3 class="mb-1 text-[11px] font-bold uppercase tracking-wide text-emerald-700">{{ __('Delivered') }}</h3>
                                <div class="divide-y divide-muted">
                                    <div
                                        v-for="item in deliveredItems(card.attendance)"
                                        :key="item.id"
                                        class="flex items-center justify-between gap-2 py-1.5"
                                    >
                                        <p class="min-w-0 truncate text-sm text-muted-foreground line-through">
                                            {{ item.quantity }}× {{ itemName(item) }}
                                        </p>
                                        <AppBadge
                                            v-if="item.preparation_status"
                                            :label="item.preparation_status.name"
                                            :color="item.preparation_status.color"
                                        />
                                    </div>
                                </div>
                            </section>
                        </div>
                    </article>
                </div>
            </template>
            <template #panel-fields>
                <button
                    v-if="isOrderMode && needsDeliveryAddress"
                    type="button"
                    data-delivery-status
                    :data-delivery-ready="deliveryDataComplete ? true : undefined"
                    class="mb-2 flex min-h-9 w-full items-center justify-center rounded-md px-3 text-sm font-bold touch-manipulation"
                    :class="deliveryDataComplete ? 'bg-emerald-600 text-white' : 'bg-red-600 text-white'"
                    @click="focusCustomerSection"
                >
                    {{ deliveryDataComplete ? __('Data OK') : __('Delivery data pending') }}
                </button>
                <div
                    v-if="isStatusMode && (needsTab || needsLocation || needsIdentifier)"
                    data-status-lookup
                    class="grid items-start gap-2"
                    :class="statusLookupColumnsClass"
                >
                    <div v-if="needsTab" class="min-w-0" data-status-tab-field data-status-tab-query data-status-tab-options>
                        <p class="mb-1 text-[10px] font-normal leading-none text-muted-foreground">{{ __('Type the tab') }}</p>
                        <NumberScrollPicker
                            :open="openPicker === 'status-tab'"
                            :items="statusTabPickerItems"
                            :model-value="statusTabId"
                            :accent-color="selectedChannelColor"
                            :placeholder="__('Type the tab')"
                            :empty-text="__('No free tabs.')"
                            @update:open="(isOpen) => setPicker('status-tab', isOpen)"
                            @update:model-value="chooseStatusTab"
                        />
                    </div>
                    <div v-if="needsLocation" class="min-w-0" data-status-location-field data-status-location-query data-status-location-options>
                        <p class="mb-1 text-[10px] font-normal leading-none text-muted-foreground">{{ __('Type the table') }}</p>
                        <NumberScrollPicker
                            :open="openPicker === 'status-location'"
                            :items="statusLocationPickerItems"
                            :model-value="statusLocationId"
                            :accent-color="selectedChannelColor"
                            :placeholder="__('Type the table')"
                            :empty-text="__('No service locations for this channel.')"
                            @update:open="(isOpen) => setPicker('status-location', isOpen)"
                            @update:model-value="chooseStatusLocation"
                        />
                    </div>
                    <div v-if="needsIdentifier" class="min-w-0" data-status-identifier-field data-status-identifier-query>
                        <p class="mb-1 text-[10px] font-normal leading-none text-muted-foreground">{{ __('Type the customer identifier') }}</p>
                        <NumberScrollPicker
                            :open="openPicker === 'status-identifier'"
                            :items="statusIdentifierPickerItems"
                            :model-value="statusIdentifierId"
                            :accent-color="selectedChannelColor"
                            :placeholder="__('Type the customer identifier')"
                            :empty-text="__('No orders in progress')"
                            @update:open="(isOpen) => setPicker('status-identifier', isOpen)"
                            @update:model-value="chooseStatusIdentifier"
                        />
                    </div>
                </div>
                <div v-if="isOrderMode && (needsTab || needsLocation || missingRequiredLocation || showOrderPanel)" class="space-y-2" data-channel-requirements>
                    <section
                        v-if="needsTab || needsLocation || missingRequiredLocation || showOrderPanel"
                        data-order-section
                        class="space-y-2 rounded-md border border-border bg-white p-2"
                    >
                    <div
                        data-requirement-columns
                        class="grid items-stretch gap-x-2 gap-y-1"
                        :class="requirementColumnsClass"
                    >
                        <template v-if="locationSummaryVisible && tabSummaryVisible && showOrderPanel">
                            <p data-location-field class="text-[10px] font-normal leading-none text-muted-foreground">{{ __('Consumption location') }}</p>
                            <p v-show="summarySize > 0" data-tab-summary data-tab-field class="text-[10px] font-normal leading-none text-muted-foreground">{{ __('Tab') }}</p>
                            <p v-show="summarySize > 0" class="text-[10px] font-normal leading-none text-muted-foreground">{{ __('Order') }}</p>

                            <div data-order-summary class="contents">
                                <div
                                    class="col-start-1 row-start-2 flex h-full min-h-0 flex-col"
                                    :style="{ gridRow: '2 / span ' + newTabColumnSpan }"
                                >
                                    <button
                                        type="button"
                                        data-location-summary
                                        class="self-start text-left touch-manipulation"
                                        @click="openContextPicker('location')"
                                    >
                                        <span
                                            data-location-value
                                            class="text-xl font-bold leading-none tracking-tight"
                                            :style="selectedChannelColor ? { color: selectedChannelColor } : {}"
                                            :class="selectedChannelColor ? '' : 'text-ocean-deep'"
                                        >{{ selectedLocationLabel }}</span>
                                    </button>
                                    <button
                                        v-if="isOrderMode && tabSummaryVisible"
                                        type="button"
                                        data-new-tab
                                        class="mt-auto min-h-8 rounded-md bg-emerald-600 px-1.5 py-1 text-[10px] font-semibold leading-tight text-white touch-manipulation"
                                        @click="openNewTabPicker"
                                    >
                                        {{ __('New tab') }}
                                    </button>
                                </div>
                                <p
                                    v-if="summarySize === 0"
                                    data-compact-summary
                                    class="col-start-2 col-span-2 self-center rounded-md bg-emerald-100 px-2 py-1.5 text-xs font-semibold leading-tight text-emerald-900"
                                >
                                    {{ __('Current tab: :tab Qty items: :count items', {
                                        tab: compactOrderSummary.tab,
                                        count: compactOrderSummary.quantity,
                                    }) }}
                                </p>
                                <div
                                    v-else
                                    data-order-groups
                                    class="col-start-2 col-span-2 min-w-0"
                                    :class="summarySize === 1 ? 'max-h-32 overflow-y-auto overscroll-contain' : ''"
                                >
                                    <div class="grid grid-cols-[auto_minmax(0,1fr)] items-stretch gap-x-1.5 gap-y-1">
                                        <template v-for="group in visibleOrderSummaryGroups" :key="group.tabId ?? 'none'">
                                            <button
                                                type="button"
                                                data-tab-value
                                                data-order-tab-group
                                                :data-active-tab-line="group.isActive ? true : undefined"
                                                class="min-h-8 cursor-pointer rounded-l-md px-1.5 py-1 text-left text-sm font-bold leading-none tabular-nums touch-manipulation"
                                                :class="group.isActive ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-200 text-gray-500'"
                                                @click="selectWorkingTab(group.tabId)"
                                            >{{ group.tabLabel }}</button>
                                            <div
                                                class="min-h-8 min-w-0 cursor-pointer rounded-r-md px-1.5 py-1"
                                                :class="group.isActive ? 'bg-emerald-100' : 'bg-gray-200'"
                                                @click="selectWorkingTab(group.tabId)"
                                            >
                                                <button
                                                    v-for="line in group.lines"
                                                    :key="line.name"
                                                    type="button"
                                                    class="flex w-full min-h-6 items-baseline justify-between gap-2 touch-manipulation text-left"
                                                    @click.stop="openOrderLine(line)"
                                                >
                                                    <span class="min-w-0 truncate text-xs font-medium leading-tight text-ocean-deep">{{ line.name }}</span>
                                                    <span class="shrink-0 text-sm font-bold tabular-nums text-ocean-deep">{{ line.quantity }}</span>
                                                </button>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                                <div
                                    v-if="isOrderMode"
                                    data-summary-toolbar
                                    class="col-start-2 col-span-2 mt-1 flex items-center gap-2"
                                >
                                    <button
                                        type="button"
                                        data-send-order
                                        class="min-h-9 flex-1 rounded-md bg-primary px-3 text-sm font-semibold text-white touch-manipulation disabled:opacity-40"
                                        :disabled="!orderSummaryLines.length"
                                        @click="sendOrder"
                                    >
                                        {{ __('Send order') }}
                                    </button>
                                    <button
                                        type="button"
                                        data-shrink-summary
                                        class="flex h-9 w-10 shrink-0 items-center justify-center rounded-md border border-border bg-white text-base font-bold leading-none tracking-tighter text-ocean-deep touch-manipulation disabled:opacity-30"
                                        :disabled="summarySize <= 0"
                                        :aria-label="__('Shrink summary')"
                                        @click="shrinkSummary"
                                    >
                                        <span class="inline-block rotate-90" aria-hidden="true">>></span>
                                    </button>
                                    <button
                                        type="button"
                                        data-expand-summary
                                        class="flex h-9 w-10 shrink-0 items-center justify-center rounded-md border border-border bg-white text-base font-bold leading-none tracking-tighter text-ocean-deep touch-manipulation disabled:opacity-30"
                                        :disabled="summarySize >= 2"
                                        :aria-label="__('Expand summary')"
                                        @click="expandSummary"
                                    >
                                        <span class="inline-block -rotate-90" aria-hidden="true">>></span>
                                    </button>
                                </div>
                            </div>
                        </template>
                        <template v-else>
                        <div v-if="needsLocation || missingRequiredLocation" class="min-w-0" data-location-field>
                            <button
                                v-if="locationSummaryVisible"
                                type="button"
                                data-location-summary
                                class="flex min-h-10 w-full flex-col items-start gap-0.5 touch-manipulation text-left"
                                @click="openContextPicker('location')"
                            >
                                <span class="text-[10px] font-normal leading-none text-muted-foreground">{{ __('Consumption location') }}</span>
                                <span
                                    data-location-value
                                    class="text-xl font-bold leading-none tracking-tight"
                                    :style="selectedChannelColor ? { color: selectedChannelColor } : {}"
                                    :class="selectedChannelColor ? '' : 'text-ocean-deep'"
                                >{{ selectedLocationLabel }}</span>
                            </button>
                            <template v-else-if="missingRequiredLocation && isOrderMode">
                                <label class="mb-1 block text-[10px] font-medium text-ocean-deep">
                                    {{ __('Consumption location') }} <span class="text-destructive">*</span>
                                </label>
                                <button
                                    type="button"
                                    data-add-location
                                    class="min-h-9 w-full rounded-md bg-emerald-600 px-3 py-2 text-sm font-semibold text-white touch-manipulation disabled:opacity-40"
                                    :disabled="registeringLocation"
                                    @click="openAddLocationPicker"
                                >
                                    {{ __('Add consumption location') }}
                                </button>
                                <div v-if="openPicker === 'add-location'" class="pt-1">
                                    <NumberScrollPicker
                                        compact
                                        :open="true"
                                        :items="addLocationPickerItems"
                                        :accent-color="selectedChannelColor"
                                        :placeholder="__('Choose a table number')"
                                        :empty-text="__('No service locations for this channel.')"
                                        @update:open="(isOpen) => setPicker('add-location', isOpen)"
                                        @update:model-value="registerLocationNumber"
                                    />
                                </div>
                            </template>
                            <template v-else-if="isOrderMode">
                                <label class="mb-1 block text-[10px] font-medium text-ocean-deep">
                                    {{ __('Consumption location') }} <span class="text-destructive">*</span>
                                </label>
                                <p v-if="pendingLocationConfirmation" class="mb-1 text-[11px] text-muted-foreground">
                                    {{ __('Tap the table number to confirm. The order is not locked.') }}
                                </p>
                                <NumberScrollPicker
                                    compact
                                    :open="openPicker === 'location'"
                                    :items="locationPickerItems"
                                    :model-value="selectedLocationId"
                                    :highlight-id="suggestedLocationId"
                                    :accent-color="selectedChannelColor"
                                    :placeholder="__('Choose a table number')"
                                    :empty-text="__('No service locations for this channel.')"
                                    @update:open="(isOpen) => setPicker('location', isOpen)"
                                    @update:model-value="chooseLocation"
                                />
                            </template>
                        </div>
                        <div v-if="needsTab" class="min-w-0" data-tab-field>
                            <template v-if="tabSummaryVisible">
                            <div class="flex min-h-10 items-end justify-between gap-2">
                            <button
                                type="button"
                                data-tab-summary
                                class="flex min-w-0 flex-col items-start gap-0.5 touch-manipulation text-left"
                                @click="openNewTabPicker"
                            >
                                <span class="text-[10px] font-normal leading-none text-muted-foreground">{{ __('Tab') }}</span>
                                <span
                                    data-tab-value
                                    class="text-xl font-bold leading-none tracking-tight"
                                    :style="selectedChannelColor ? { color: selectedChannelColor } : {}"
                                    :class="selectedChannelColor ? '' : 'text-ocean-deep'"
                                >{{ selectedTabLabel }}</span>
                            </button>
                            <button
                                v-if="isOrderMode"
                                type="button"
                                data-new-tab
                                class="min-h-8 shrink-0 rounded-md bg-emerald-600 px-2 py-1 text-[10px] font-semibold leading-tight text-white touch-manipulation"
                                @click="openNewTabPicker"
                            >
                                {{ __('New tab') }}
                            </button>
                            </div>
                            </template>
                            <template v-else-if="isOrderMode">
                                <label class="mb-1 block text-[10px] font-medium text-ocean-deep">
                                    {{ __('Tab') }} <span class="text-destructive">*</span>
                                </label>
                                <NumberScrollPicker
                                    compact
                                    :open="openPicker === 'tab'"
                                    :items="tabPickerItems"
                                    :model-value="selectedTabId"
                                    :accent-color="selectedChannelColor"
                                    :placeholder="__('Choose a tab number')"
                                    :empty-text="__('No free tabs.')"
                                    @update:open="(isOpen) => setPicker('tab', isOpen)"
                                    @update:model-value="chooseTab"
                                />
                            </template>
                        </div>
                        <div
                            v-if="showOrderPanel"
                            data-order-summary
                            class="min-w-0 w-full"
                        >
                            <p class="mb-0.5 text-[10px] font-normal leading-none text-muted-foreground">{{ __('Order') }}</p>
                            <div
                                v-for="group in orderSummaryGroups"
                                :key="group.tabId ?? 'none'"
                                data-order-tab-group
                                class="mb-1 rounded-md bg-gray-200 px-1.5 py-1"
                            >
                                <button
                                    v-for="line in group.lines"
                                    :key="line.name"
                                    type="button"
                                    class="flex w-full min-h-6 items-baseline justify-between gap-2 touch-manipulation text-left"
                                    @click="openOrderLine(line)"
                                >
                                    <span class="min-w-0 truncate text-xs font-medium leading-tight text-ocean-deep">{{ line.name }}</span>
                                    <span class="shrink-0 text-sm font-bold tabular-nums text-ocean-deep">{{ line.quantity }}</span>
                                </button>
                            </div>
                            <button
                                v-if="isOrderMode"
                                type="button"
                                data-send-order
                                class="mt-1 min-h-9 w-full rounded-md bg-primary px-3 text-sm font-semibold text-white touch-manipulation disabled:opacity-40"
                                :disabled="!orderSummaryLines.length"
                                @click="sendOrder"
                            >
                                {{ __('Send order') }}
                            </button>
                        </div>
                        </template>
                    </div>
                    <div
                        v-if="isOrderMode && openPicker === 'tab' && tabSummaryVisible"
                        data-tab-picker
                        class="pt-1"
                    >
                        <p class="mb-1 text-[10px] font-medium text-muted-foreground">
                            {{ tabPickerMode === 'move' ? __('Move to tab') : __('New tab') }}
                        </p>
                        <NumberScrollPicker
                            compact
                            :open="true"
                            :items="groupedTabPickerItems"
                            :model-value="selectedTabId"
                            :accent-color="selectedChannelColor"
                            :placeholder="tabPickerMode === 'move' ? __('Move to tab') : __('Choose a tab number')"
                            :empty-text="__('No free tabs.')"
                            @update:open="(isOpen) => setPicker('tab', isOpen)"
                            @update:model-value="chooseTab"
                        />
                        <p
                            data-new-tab-legend
                            class="mt-1 flex items-center gap-1 text-[9px] font-normal leading-none text-muted-foreground"
                        >
                            <span class="inline-block h-1.5 w-1.5 rounded-full bg-emerald-400" aria-hidden="true" />
                            *{{ __('New tab') }}
                        </p>
                    </div>
                    </section>
                </div>
            </template>
        </GuestMenuCatalog>
        </div>

        <div
            v-if="orderSuccessVisible"
            data-order-success
            class="fixed inset-0 z-[60] flex items-center justify-center bg-white px-6"
        >
            <div class="w-full max-w-sm text-center">
                <p class="font-heading text-2xl font-bold leading-tight text-ocean-deep">{{ __('Order') }}</p>
                <div v-if="orderSuccessTableHighlight" class="mt-8">
                    <p class="text-sm font-semibold uppercase tracking-[0.2em] text-muted-foreground">{{ __('Table') }}</p>
                    <p
                        data-order-success-table
                        class="mt-1 font-heading text-[clamp(3.5rem,18vw,6.5rem)] font-bold leading-none text-ocean-deep"
                    >
                        {{ orderSuccessTableHighlight }}
                    </p>
                </div>
                <div v-if="orderSuccessTabHighlight" class="mt-6">
                    <p class="text-sm font-semibold uppercase tracking-[0.2em] text-muted-foreground">{{ __('Tab') }}</p>
                    <p
                        data-order-success-tab
                        class="mt-1 font-heading text-[clamp(3.5rem,18vw,6.5rem)] font-bold leading-none text-ocean-deep"
                    >
                        {{ orderSuccessTabHighlight }}
                    </p>
                </div>
                <p
                    v-else-if="orderSuccessName"
                    class="mt-6 font-heading text-3xl font-bold leading-tight text-ocean-deep"
                >
                    {{ orderSuccessName }}
                </p>
                <p class="mt-6 text-lg font-medium text-ocean-deep">{{ __('placed successfully') }}</p>
                <AppButton
                    data-order-success-ok
                    size="lg"
                    class="mt-8 min-h-12 w-full text-lg"
                    @click="closeOrderSuccess"
                >
                    {{ __('OK') }}
                </AppButton>
            </div>
        </div>

        <ProductDetailDrawer
            v-if="selectedProduct"
            v-model="showProductDrawer"
            :product="selectedProduct"
            :preview="preview"
            :editing-item="editingItem"
            can-remove
            :can-change-tab="needsTab"
            :tab-items="groupedTabPickerItems"
            :current-tab-id="selectedTabId"
            :accent-color="selectedChannelColor"
            :tab-locations="tabLocationById"
            :current-location-id="selectedLocationId"
            :current-location-label="selectedLocationLabel"
            @add-to-cart="addToCart"
            @remove-item="removeEditingItem"
            @change-tab="changeEditingItemTab"
        />
    </component>
</template>
