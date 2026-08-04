<script setup>
import PlatformLayout from '@/Layouts/PlatformLayout.vue';
import { useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import { useTranslate } from '@/Composables/useTranslate';
import { useCurrency } from '@/Composables/useCurrency';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    corporation: Object,
    plans: Array,
    invoices: Array,
    venueInvoices: Array,
    statusHistory: {
        type: Array,
        default: () => [],
    },
    auditLogs: {
        type: Array,
        default: () => [],
    },
    moduleCatalog: Array,
    subscriptionStatuses: Array,
    billingModes: Array,
    invoiceStatuses: Array,
});

const __ = useTranslate();
const { formatMoney: formatCurrency, toAmount } = useCurrency();

const activeTab = ref('info');
const tabs = [
    { key: 'info', label: 'Info' },
    { key: 'subscription', label: 'Assinatura' },
    { key: 'modules', label: 'Módulos' },
    { key: 'discounts', label: 'Descontos' },
    { key: 'invoices', label: 'Faturas' },
    { key: 'affiliate', label: 'Afiliado' },
    { key: 'audit', label: 'Auditoria' },
];

const form = useForm({
    name: props.corporation.name ?? '',
    tax_id: props.corporation.tax_id ?? '',
    email: props.corporation.email ?? '',
    contact_phone: props.corporation.contact_phone ?? '',
    active: props.corporation.active ?? true,
});

const planForm = useForm({
    plan_catalog_id: props.corporation.subscription?.plan_catalog_id ?? '',
    // O backend guarda centavos; o formulário edita reais.
    subscription_value: props.corporation.subscription?.base_value != null
        ? toAmount(props.corporation.subscription.base_value)
        : '',
    billing_mode: props.corporation.subscription?.billing_mode ?? 'per_venue',
    billing_day: props.corporation.subscription?.billing_day ?? 1,
    grace_period_days: props.corporation.subscription?.grace_period_days ?? 3,
    started_at: props.corporation.subscription?.started_at ? props.corporation.subscription.started_at.split('T')[0] : '',
    trial_ends_at: props.corporation.subscription?.trial_ends_at ? props.corporation.subscription.trial_ends_at.split('T')[0] : '',
});

const subscriptionForm = useForm({
    billing_mode: props.corporation.subscription?.billing_mode ?? 'per_venue',
    status: props.corporation.subscription?.status ?? 'trial',
    billing_day: props.corporation.subscription?.billing_day ?? 1,
    grace_period_days: props.corporation.subscription?.grace_period_days ?? 3,
    started_at: props.corporation.subscription?.started_at ? props.corporation.subscription.started_at.split('T')[0] : '',
    trial_ends_at: props.corporation.subscription?.trial_ends_at ? props.corporation.subscription.trial_ends_at.split('T')[0] : '',
    ended_at: props.corporation.subscription?.ended_at ? props.corporation.subscription.ended_at.split('T')[0] : '',
});

const moduleForm = useForm({
    module_code: '',
    custom_monthly_price: '',
});

const venueModuleForms = ref({});
props.corporation.venues?.forEach((venue) => {
    if (!venueModuleForms.value[venue.id]) {
        venueModuleForms.value[venue.id] = useForm({ module_code: '', quantity: 1 });
    }
});

const discountForm = useForm({
    type: 'fixed',
    value: '',
    description: '',
    valid_from: '',
    valid_until: '',
    max_months: '',
});

const invoiceForm = useForm({
    invoiceable_type: 'corporation',
    invoiceable_id: props.corporation.id,
    period: '',
    due_date: '',
    base_value: '',
    modules_value: '',
    metered_value: '',
    dedicated_surcharge: '',
    discount_value: '',
});

watch(() => invoiceForm.invoiceable_type, (type) => {
    if (type === 'corporation') {
        invoiceForm.invoiceable_id = props.corporation.id;
    }
});


const unifiedInvoices = computed(() => props.invoices.filter(i => i.venue_total === null || i.venue_total === 0));
const allInvoices = computed(() => [...props.invoices, ...props.venueInvoices].sort((a, b) => b.period.localeCompare(a.period)));

const statusChangeForms = ref({});
allInvoices.value.forEach((invoice) => {
    if (!statusChangeForms.value[invoice.id]) {
        statusChangeForms.value[invoice.id] = useForm({ status: invoice.status });
    }
});

watch(() => allInvoices.value, (invoices) => {
    invoices.forEach((invoice) => {
        if (!statusChangeForms.value[invoice.id]) {
            statusChangeForms.value[invoice.id] = useForm({ status: invoice.status });
        }
    });
});

const submit = () => {
    form.put(route('platform.corporations.update', props.corporation.id),{
        onSuccess: () => {
            toast.success(__('Corporation updated successfully'));
        },
    });
};

const submitPlan = () => {
    planForm.put(route('platform.corporations.plan', props.corporation.id));
};

const submitSubscription = () => {
    subscriptionForm.put(route('platform.corporations.subscription.update', props.corporation.id),{
        onSuccess: () => {
            toast.success(__('Corporation updated successfully'));
        },
    });
};

const submitModule = () => {
    moduleForm.post(route('platform.corporations.modules.store', props.corporation.id), {
        onSuccess: () => {
            moduleForm.reset();
            toast.success(__('Corporation updated successfully'));
        },
    });
};

const destroyModule = (moduleId) => {
    if (confirm(__('Are you sure you want to disable this module?'))) {
        moduleForm.delete(route('platform.corporations.modules.destroy', [props.corporation.id, moduleId]),{
            onSuccess: () => {
                toast.success(__('Module disabled successfully'));
            },
        });
    }
};

const submitVenueModule = (venue) => {
    const vf = venueModuleForms.value[venue.id];
    if (!vf || !vf.module_code) return;
    vf.post(route('platform.corporations.venues.modules.store', [props.corporation.id, venue.id]), {
        onSuccess: () => {
            vf.reset();
            toast.success(__('Module enabled successfully for venue'));
        },
    });
};

const destroyVenueModule = (venue, moduleId) => {
    if (confirm(__('Are you sure you want to disable this venue module?'))) {
        const vf = useForm({});
        vf.delete(route('platform.corporations.venues.modules.destroy', [props.corporation.id, venue.id, moduleId]),{
            onSuccess: () => {
                toast.success(__('Venue module disabled successfully'));
            },
        });
    }
};

const submitDiscount = () => {
    discountForm.post(route('platform.corporations.discounts.store', props.corporation.id), {
        onSuccess: () => {
            discountForm.reset();
            toast.success(__('Discount added successfully'));
        },
    });
};

const destroyDiscount = (discountId) => {
    if (confirm(__('Are you sure you want to remove this discount?'))) {
        const df = useForm({});
        df.delete(route('platform.corporations.discounts.destroy', [props.corporation.id, discountId]), {
            onSuccess: () => {
                toast.success(__('Discount removed successfully'));
            },
        });
    }
};

const submitInvoice = () => {
    invoiceForm.post(route('platform.corporations.invoices.store', props.corporation.id), {
        onSuccess: () => {
            invoiceForm.reset();
            toast.success(__('Invoice created successfully'));
        }
    });
};

const changeInvoiceStatus = (invoice) => {
    const sf = statusChangeForms.value[invoice.id];
    if (!sf) return;

    sf.put(route('platform.corporations.invoices.status', [props.corporation.id, invoice.id]), {
        onSuccess: () => {
            toast.success(__('Invoice status updated successfully'));
        },
    });
};

const formatDate = (value) => value ? value.split('T')[0] : '-';

const changedKeys = (log) => {
    const keys = new Set([...Object.keys(log.before ?? {}), ...Object.keys(log.after ?? {})]);

    return [...keys].filter((key) => (log.before?.[key] ?? null) !== (log.after?.[key] ?? null));
};

const getStatusClass = (status) => {
    return {
        open: 'bg-blue-100 text-blue-700',
        overdue: 'bg-red-100 text-red-700',
        paid: 'bg-green-100 text-green-700',
        canceled: 'bg-gray-100 text-gray-700',
        refunded: 'bg-yellow-100 text-yellow-700',
    }[status] ?? 'bg-gray-100 text-gray-700';
};
</script>

<template>
    <PlatformLayout :title="__('Edit Corporation')">
        <template #header>
            <div class="flex items-center gap-4">
                <h1 class="font-heading text-xl font-bold text-ocean-deep dark:text-gray-100">{{ corporation.name }}</h1>
                <span :class="corporation.active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'" class="rounded-full px-2 py-0.5 text-xs font-medium">
                    {{ corporation.active ? __('Active') : __('Inactive') }}
                </span>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Tabs -->
            <div class="border-b border-border dark:border-gray-700">
                <nav class="flex gap-4 overflow-x-auto">
                    <button
                        v-for="tab in tabs"
                        :key="tab.key"
                        @click="activeTab = tab.key"
                        :class="[
                            'whitespace-nowrap px-3 py-2 text-sm font-medium transition-colors',
                            activeTab === tab.key
                                ? 'border-b-2 border-primary text-primary'
                                : 'text-muted-foreground hover:text-ocean-deep dark:hover:text-gray-300',
                        ]"
                    >
                        {{ __(tab.label) }}
                    </button>
                </nav>
            </div>

            <!-- Info -->
            <div v-if="activeTab === 'info'" class="max-w-2xl space-y-6">
                <form @submit.prevent="submit" class="space-y-4 bg-white rounded-xl shadow-card p-6 dark:bg-gray-800">
                    <h2 class="font-heading font-semibold text-ocean-deep border-b pb-2 dark:text-gray-100 dark:border-gray-700">{{ __('Corporation Info') }}</h2>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-ocean-deep mb-1 dark:text-gray-300">{{ __('Name') }}</label>
                            <input v-model="form.name" type="text" class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" />
                            <p v-if="form.errors.name" class="mt-1 text-xs text-destructive">{{ form.errors.name }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-ocean-deep mb-1 dark:text-gray-300">{{ __('Email') }}</label>
                            <input v-model="form.email" type="email" class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-ocean-deep mb-1 dark:text-gray-300">{{ __('Tax ID') }}</label>
                            <input v-model="form.tax_id" type="text" class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-ocean-deep mb-1 dark:text-gray-300">{{ __('Contact Phone') }}</label>
                            <input v-model="form.contact_phone" type="text" class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" />
                        </div>
                        <div class="flex items-center gap-2">
                            <input v-model="form.active" type="checkbox" id="active" class="h-4 w-4 rounded border-border text-primary" />
                            <label for="active" class="text-sm font-medium text-ocean-deep dark:text-gray-300">{{ __('Active') }}</label>
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" :disabled="form.processing" class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary/90 disabled:opacity-60 transition-colors">
                            {{ __('Save Changes') }}
                        </button>
                    </div>
                </form>

                <div class="bg-white rounded-xl shadow-card p-6 dark:bg-gray-800">
                    <h2 class="font-heading font-semibold text-ocean-deep border-b pb-2 mb-4 dark:text-gray-100 dark:border-gray-700">{{ __('Owner') }}</h2>
                    <div class="text-sm text-ocean-deep dark:text-gray-100">
                        <p><span class="font-medium">{{ __('Name') }}:</span> {{ corporation.owner?.name ?? '-' }}</p>
                        <p><span class="font-medium">{{ __('Email') }}:</span> {{ corporation.owner?.email ?? '-' }}</p>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-card p-6 dark:bg-gray-800">
                    <h2 class="font-heading font-semibold text-ocean-deep border-b pb-2 mb-4 dark:text-gray-100 dark:border-gray-700">{{ __('Venues') }}</h2>
                    <ul class="space-y-2">
                        <li v-for="venue in corporation.venues" :key="venue.id" class="flex items-center justify-between rounded-lg border border-border px-3 py-2 text-sm dark:border-gray-700">
                            <span class="font-medium text-ocean-deep dark:text-gray-100">{{ venue.name }}</span>
                            <span :class="venue.active ? 'text-green-600' : 'text-muted-foreground'" class="text-xs">{{ venue.active ? __('Active') : __('Inactive') }}</span>
                        </li>
                        <li v-if="!corporation.venues?.length" class="text-sm text-muted-foreground">{{ __('No venues yet.') }}</li>
                    </ul>
                </div>
            </div>

            <!-- Subscription -->
            <div v-if="activeTab === 'subscription'" class="max-w-2xl space-y-6">
                <form @submit.prevent="submitPlan" class="space-y-4 bg-white rounded-xl shadow-card p-6 dark:bg-gray-800">
                    <h2 class="font-heading font-semibold text-ocean-deep border-b pb-2 dark:text-gray-100 dark:border-gray-700">{{ __('Plan Assignment') }}</h2>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-ocean-deep mb-1 dark:text-gray-300">{{ __('Plan') }}</label>
                            <select v-model="planForm.plan_catalog_id" class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                                <option value="">{{ __('Select plan') }}</option>
                                <option v-for="plan in plans" :key="plan.id" :value="plan.id">{{ plan.name }} - {{ formatCurrency(plan.monthly_price) }}</option>
                            </select>
                            <p v-if="planForm.errors.plan_catalog_id" class="mt-1 text-xs text-destructive">{{ planForm.errors.plan_catalog_id }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-ocean-deep mb-1 dark:text-gray-300">{{ __('Subscription Value') }}</label>
                            <input v-model="planForm.subscription_value" type="number" step="0.01" class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-ocean-deep mb-1 dark:text-gray-300">{{ __('Billing Mode') }}</label>
                            <select v-model="planForm.billing_mode" class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                                <option v-for="mode in billingModes" :key="mode.value" :value="mode.value">{{ mode.label }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-ocean-deep mb-1 dark:text-gray-300">{{ __('Billing Day') }}</label>
                            <input v-model="planForm.billing_day" type="number" min="1" max="28" class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-ocean-deep mb-1 dark:text-gray-300">{{ __('Grace Period (days)') }}</label>
                            <input v-model="planForm.grace_period_days" type="number" min="0" max="30" class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-ocean-deep mb-1 dark:text-gray-300">{{ __('Plan Start Date') }}</label>
                            <input v-model="planForm.started_at" type="date" class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-ocean-deep mb-1 dark:text-gray-300">{{ __('Trial Ends At') }}</label>
                            <input v-model="planForm.trial_ends_at" type="date" class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" />
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" :disabled="planForm.processing" class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary/90 disabled:opacity-60 transition-colors">
                            {{ __('Assign Plan') }}
                        </button>
                    </div>
                </form>

                <form @submit.prevent="submitSubscription" class="space-y-4 bg-white rounded-xl shadow-card p-6 dark:bg-gray-800">
                    <h2 class="font-heading font-semibold text-ocean-deep border-b pb-2 dark:text-gray-100 dark:border-gray-700">{{ __('Subscription Settings') }}</h2>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-ocean-deep mb-1 dark:text-gray-300">{{ __('Status') }}</label>
                            <select v-model="subscriptionForm.status" class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                                <option v-for="status in subscriptionStatuses" :key="status.value" :value="status.value">{{ status.label }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-ocean-deep mb-1 dark:text-gray-300">{{ __('Billing Mode') }}</label>
                            <select v-model="subscriptionForm.billing_mode" class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                                <option v-for="mode in billingModes" :key="mode.value" :value="mode.value">{{ mode.label }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-ocean-deep mb-1 dark:text-gray-300">{{ __('Billing Day') }}</label>
                            <input v-model="subscriptionForm.billing_day" type="number" min="1" max="28" class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-ocean-deep mb-1 dark:text-gray-300">{{ __('Grace Period (days)') }}</label>
                            <input v-model="subscriptionForm.grace_period_days" type="number" min="0" max="30" class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-ocean-deep mb-1 dark:text-gray-300">{{ __('Start Date') }}</label>
                            <input v-model="subscriptionForm.started_at" type="date" class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-ocean-deep mb-1 dark:text-gray-300">{{ __('Trial Ends At') }}</label>
                            <input v-model="subscriptionForm.trial_ends_at" type="date" class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-ocean-deep mb-1 dark:text-gray-300">{{ __('Ended At') }}</label>
                            <input v-model="subscriptionForm.ended_at" type="date" class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" />
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" :disabled="subscriptionForm.processing" class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary/90 disabled:opacity-60 transition-colors">
                            {{ __('Update Subscription') }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Modules -->
            <div v-if="activeTab === 'modules'" class="space-y-6">
                <div class="bg-white rounded-xl shadow-card p-6 dark:bg-gray-800">
                    <h2 class="font-heading font-semibold text-ocean-deep border-b pb-2 mb-4 dark:text-gray-100 dark:border-gray-700">{{ __('Corporate Modules') }}</h2>
                    <form @submit.prevent="submitModule" class="grid gap-4 sm:grid-cols-3 mb-6">
                        <div>
                            <select v-model="moduleForm.module_code" class="w-full rounded-md border border-border px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                                <option value="">{{ __('Select module') }}</option>
                                <option v-for="code in moduleCatalog" :key="code.value" :value="code.value">{{ code.label ?? code.value }}</option>
                            </select>
                            <p v-if="moduleForm.errors.module_code" class="mt-1 text-xs text-destructive">{{ moduleForm.errors.module_code }}</p>
                        </div>
                        <div>
                            <input v-model="moduleForm.custom_monthly_price" type="number" step="0.01" :placeholder="__('Custom price')" class="w-full rounded-md border border-border px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" />
                        </div>
                        <div>
                            <button type="submit" :disabled="moduleForm.processing" class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary/90 disabled:opacity-60 transition-colors">
                                {{ __('Enable') }}
                            </button>
                        </div>
                    </form>

                    <table class="min-w-full text-sm">
                        <thead class="bg-ocean-deep text-white">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium">{{ __('Module') }}</th>
                                <th class="px-4 py-3 text-left font-medium">{{ __('Status') }}</th>
                                <th class="px-4 py-3 text-left font-medium">{{ __('Price') }}</th>
                                <th class="px-4 py-3 text-right font-medium">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border dark:divide-gray-700">
                            <tr v-for="module in corporation.modules" :key="module.id">
                                <td class="px-4 py-3 text-ocean-deep dark:text-gray-100">{{ module.catalog?.name ?? module.module_code }}</td>
                                <td class="px-4 py-3">
                                    <span :class="module.status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'" class="rounded-full px-2 py-1 text-xs font-medium">
                                        {{ module.status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-ocean-deep dark:text-gray-100">
                                    {{ module.custom_monthly_price ? formatCurrency(module.custom_monthly_price) : formatCurrency(module.catalog?.base_monthly_price ?? 0) }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <button v-if="module.status === 'active'" @click="destroyModule(module.id)" class="text-destructive hover:underline text-xs">
                                        {{ __('Disable') }}
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="!corporation.modules?.length">
                                <td colspan="4" class="px-4 py-6 text-center text-muted-foreground">{{ __('No modules found.') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-for="venue in corporation.venues" :key="venue.id" class="bg-white rounded-xl shadow-card p-6 dark:bg-gray-800">
                    <h3 class="font-heading font-semibold text-ocean-deep border-b pb-2 mb-4 dark:text-gray-100 dark:border-gray-700">{{ venue.name }} - {{ __('Modules') }}</h3>
                    <form @submit.prevent="submitVenueModule(venue)" class="grid gap-4 sm:grid-cols-3 mb-4">
                        <div>
                            <select v-model="venueModuleForms[venue.id].module_code" class="w-full rounded-md border border-border px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                                <option value="">{{ __('Select module') }}</option>
                                <option v-for="code in moduleCatalog" :key="code.value" :value="code.value">{{ code.label ?? code.value }}</option>
                            </select>
                        </div>
                        <div>
                            <input v-model="venueModuleForms[venue.id].quantity" type="number" min="1" :placeholder="__('Quantity')" class="w-full rounded-md border border-border px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" />
                        </div>
                        <div>
                            <button type="submit" :disabled="venueModuleForms[venue.id].processing" class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary/90 disabled:opacity-60 transition-colors">
                                {{ __('Activate') }}
                            </button>
                        </div>
                    </form>
                    <table class="min-w-full text-sm">
                        <thead class="bg-ocean-deep text-white">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium">{{ __('Module') }}</th>
                                <th class="px-4 py-3 text-left font-medium">{{ __('Status') }}</th>
                                <th class="px-4 py-3 text-left font-medium">{{ __('Quantity') }}</th>
                                <th class="px-4 py-3 text-right font-medium">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border dark:divide-gray-700">
                            <tr v-for="module in venue.modules" :key="module.id">
                                <td class="px-4 py-3 text-ocean-deep dark:text-gray-100">{{ module.catalog?.name ?? module.module_code }}</td>
                                <td class="px-4 py-3">
                                    <span :class="module.status.value === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'" class="rounded-full px-2 py-1 text-xs font-medium">
                                        {{ module.status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-ocean-deep dark:text-gray-100">{{ module.quantity }}</td>
                                <td class="px-4 py-3 text-right">
                                    <button v-if="module.status.value === 'active'" @click="destroyVenueModule(venue, module.id)" class="text-destructive hover:underline text-xs">
                                        {{ __('Disable') }}
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="!venue.modules?.length">
                                <td colspan="4" class="px-4 py-6 text-center text-muted-foreground">{{ __('No modules found.') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Discounts -->
            <div v-if="activeTab === 'discounts'" class="max-w-3xl space-y-6">
                <form @submit.prevent="submitDiscount" class="space-y-4 bg-white rounded-xl shadow-card p-6 dark:bg-gray-800">
                    <h2 class="font-heading font-semibold text-ocean-deep border-b pb-2 dark:text-gray-100 dark:border-gray-700">{{ __('Create Discount') }}</h2>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-ocean-deep mb-1 dark:text-gray-300">{{ __('Type') }}</label>
                            <select v-model="discountForm.type" class="w-full rounded-md border border-border px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                                <option value="fixed">{{ __('Fixed Value') }}</option>
                                <option value="percentage">{{ __('Percentage') }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-ocean-deep mb-1 dark:text-gray-300">{{ __('Value') }}</label>
                            <input v-model="discountForm.value" type="number" step="0.01" min="0" :max="discountForm.type === 'percentage' ? 100 : null" class="w-full rounded-md border border-border px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" />
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-ocean-deep mb-1 dark:text-gray-300">{{ __('Description') }}</label>
                            <input v-model="discountForm.description" type="text" class="w-full rounded-md border border-border px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-ocean-deep mb-1 dark:text-gray-300">{{ __('Valid From') }}</label>
                            <input v-model="discountForm.valid_from" type="date" class="w-full rounded-md border border-border px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-ocean-deep mb-1 dark:text-gray-300">{{ __('Valid Until') }}</label>
                            <input v-model="discountForm.valid_until" type="date" class="w-full rounded-md border border-border px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-ocean-deep mb-1 dark:text-gray-300">{{ __('Max Months') }}</label>
                            <input v-model="discountForm.max_months" type="number" min="1" class="w-full rounded-md border border-border px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" />
                            <p class="text-xs text-muted-foreground">{{ __('Leave blank for unlimited.') }}</p>
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" :disabled="discountForm.processing" class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary/90 disabled:opacity-60 transition-colors">
                            {{ __('Create Discount') }}
                        </button>
                    </div>
                </form>

                <div class="bg-white rounded-xl shadow-card overflow-hidden dark:bg-gray-800">
                    <table class="min-w-full text-sm">
                        <thead class="bg-ocean-deep text-white">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium">{{ __('Type') }}</th>
                                <th class="px-4 py-3 text-left font-medium">{{ __('Value') }}</th>
                                <th class="px-4 py-3 text-left font-medium">{{ __('Validity') }}</th>
                                <th class="px-4 py-3 text-left font-medium">{{ __('Max Months') }}</th>
                                <th class="px-4 py-3 text-right font-medium">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border dark:divide-gray-700">
                            <tr v-for="discount in corporation.discounts" :key="discount.id">
                                <td class="px-4 py-3 text-ocean-deep dark:text-gray-100">{{ discount.type === 'percentage' ? __('Percentage') : __('Fixed') }}</td>
                                <td class="px-4 py-3 text-ocean-deep dark:text-gray-100">{{ discount.type === 'percentage' ? toAmount(discount.value) + '%' : formatCurrency(discount.value) }}</td>
                                <td class="px-4 py-3 text-ocean-deep dark:text-gray-100">{{ formatDate(discount.valid_from) }} {{ discount.valid_until ? '→ ' + formatDate(discount.valid_until) : '' }}</td>
                                <td class="px-4 py-3 text-ocean-deep dark:text-gray-100">{{ discount.max_months ?? '-' }}</td>
                                <td class="px-4 py-3 text-right">
                                    <button @click="destroyDiscount(discount.id)" class="text-destructive hover:underline text-xs">{{ __('Remove') }}</button>
                                </td>
                            </tr>
                            <tr v-if="!corporation.discounts?.length">
                                <td colspan="5" class="px-4 py-6 text-center text-muted-foreground">{{ __('No discounts found.') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Invoices -->
            <div v-if="activeTab === 'invoices'" class="space-y-6">
                <form @submit.prevent="submitInvoice" class="bg-white rounded-xl shadow-card p-6 dark:bg-gray-800">
                    <h2 class="font-heading font-semibold text-ocean-deep border-b pb-2 mb-4 dark:text-gray-100 dark:border-gray-700">{{ __('Create Manual Invoice') }}</h2>
                    <div class="grid gap-4 sm:grid-cols-3">
                        <div>
                            <label class="block text-sm font-medium text-ocean-deep mb-1 dark:text-gray-300">{{ __('Type') }}</label>
                            <select v-model="invoiceForm.invoiceable_type" class="w-full rounded-md border border-border px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                                <option value="corporation">{{ __('Corporation') }}</option>
                                <option value="venue">{{ __('Venue') }}</option>
                            </select>
                            <InputError v-if="invoiceForm.errors.invoiceable_type" :message="invoiceForm.errors.invoiceable_type" class="mt-1" />
                        </div>
                        <div v-if="invoiceForm.invoiceable_type === 'venue'">
                            <label class="block text-sm font-medium text-ocean-deep mb-1 dark:text-gray-300">{{ __('Venue') }}</label>
                            <select v-model="invoiceForm.invoiceable_id" class="w-full rounded-md border border-border px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                                <option v-for="venue in corporation.venues" :key="venue.id" :value="venue.id">{{ venue.name }}</option>
                            </select>
                            <InputError v-if="invoiceForm.errors.invoiceable_id" :message="invoiceForm.errors.invoiceable_id" class="mt-1" />
                        </div>
                        <div v-else>
                            <label class="block text-sm font-medium text-ocean-deep mb-1 dark:text-gray-300">{{ __('Corporation') }}</label>
                            <input :value="corporation.name" disabled class="w-full rounded-md border border-border px-3 py-2 text-sm bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-ocean-deep mb-1 dark:text-gray-300">{{ __('Period') }}</label>
                            <input v-model="invoiceForm.period" type="month" class="w-full rounded-md border border-border px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-ocean-deep mb-1 dark:text-gray-300">{{ __('Due Date') }}</label>
                            <input v-model="invoiceForm.due_date" type="date" class="w-full rounded-md border border-border px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" />
                            <InputError v-if="invoiceForm.errors.due_date" :message="invoiceForm.errors.due_date" class="mt-1" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-ocean-deep mb-1 dark:text-gray-300">{{ __('Base Value') }}</label>
                            <input v-model="invoiceForm.base_value" type="number" step="0.01" class="w-full rounded-md border border-border px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" />
                            <InputError v-if="invoiceForm.errors.base_value" :message="invoiceForm.errors.base_value" class="mt-1" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-ocean-deep mb-1 dark:text-gray-300">{{ __('Modules Value') }}</label>
                            <input v-model="invoiceForm.modules_value" type="number" step="0.01" class="w-full rounded-md border border-border px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-ocean-deep mb-1 dark:text-gray-300">{{ __('Metered Value') }}</label>
                            <input v-model="invoiceForm.metered_value" type="number" step="0.01" class="w-full rounded-md border border-border px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-ocean-deep mb-1 dark:text-gray-300">{{ __('Dedicated Surcharge') }}</label>
                            <input v-model="invoiceForm.dedicated_surcharge" type="number" step="0.01" class="w-full rounded-md border border-border px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-ocean-deep mb-1 dark:text-gray-300">{{ __('Discount Value') }}</label>
                            <input v-model="invoiceForm.discount_value" type="number" step="0.01" class="w-full rounded-md border border-border px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" />
                        </div>
                    </div>
                    <div class="flex justify-end mt-4">
                        <button type="submit" :disabled="invoiceForm.processing" class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary/90 disabled:opacity-60 transition-colors">
                            {{ __('Create Invoice') }}
                        </button>
                    </div>
                </form>

                <div class="bg-white rounded-xl shadow-card overflow-hidden dark:bg-gray-800">
                    <h2 class="font-heading font-semibold text-ocean-deep border-b px-4 py-3 dark:text-gray-100 dark:border-gray-700">{{ __('Invoices') }}</h2>
                    <table class="min-w-full text-sm">
                        <thead class="bg-ocean-deep text-white">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium">{{ __('Target') }}</th>
                                <th class="px-4 py-3 text-left font-medium">{{ __('Period') }}</th>
                                <th class="px-4 py-3 text-left font-medium">{{ __('Due Date') }}</th>
                                <th class="px-4 py-3 text-left font-medium">{{ __('Status') }}</th>
                                <th class="px-4 py-3 text-right font-medium">{{ __('Total') }}</th>
                                <th class="px-4 py-3 text-right font-medium">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border dark:divide-gray-700">
                            <tr v-for="invoice in allInvoices" :key="invoice.id">
                                <td class="px-4 py-3 text-ocean-deep dark:text-gray-100">{{ invoice.venue?.name ?? corporation.name }}</td>
                                <td class="px-4 py-3 text-ocean-deep dark:text-gray-100">{{ invoice.period }}</td>
                                <td class="px-4 py-3 text-ocean-deep dark:text-gray-100">{{ formatDate(invoice.due_date) }}</td>
                                <td class="px-4 py-3">
                                    <span :class="getStatusClass(invoice.status)" class="rounded-full px-2 py-1 text-xs font-medium">{{ invoice.status }}</span>
                                </td>
                                <td class="px-4 py-3 text-right text-ocean-deep dark:text-gray-100">{{ formatCurrency(invoice.total_value) }}</td>
                                <td class="px-4 py-3 text-right">
                                    <div v-if="!invoice.is_finalized" class="flex items-center justify-end gap-2">
                                        <select v-model="statusChangeForms[invoice.id].status" class="rounded-md border border-border px-2 py-1 text-xs dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                                            <option v-for="s in invoiceStatuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                                        </select>
                                        <button @click="changeInvoiceStatus(invoice)" class="text-primary hover:underline text-xs">{{ __('Update') }}</button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!allInvoices.length">
                                <td colspan="6" class="px-4 py-6 text-center text-muted-foreground">{{ __('No invoices found.') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Affiliate -->
            <div v-if="activeTab === 'affiliate'" class="max-w-2xl bg-white rounded-xl shadow-card p-6 dark:bg-gray-800">
                <h2 class="font-heading font-semibold text-ocean-deep border-b pb-2 mb-4 dark:text-gray-100 dark:border-gray-700">{{ __('Affiliate Information') }}</h2>
                <div v-if="corporation.affiliate" class="space-y-2 text-sm text-ocean-deep dark:text-gray-100">
                    <p><span class="font-medium">{{ __('Code') }}:</span> {{ corporation.affiliate.code }}</p>
                    <p><span class="font-medium">{{ __('Name') }}:</span> {{ corporation.affiliate.name }}</p>
                    <p><span class="font-medium">{{ __('Email') }}:</span> {{ corporation.affiliate.email }}</p>
                </div>
                <p v-else class="text-sm text-muted-foreground">{{ __('No affiliate associated.') }}</p>
            </div>

            <!-- Audit -->
            <div v-if="activeTab === 'audit'" class="space-y-6">
                <div class="bg-white rounded-xl shadow-card overflow-hidden dark:bg-gray-800">
                    <h2 class="font-heading font-semibold text-ocean-deep border-b px-4 py-3 dark:text-gray-100 dark:border-gray-700">{{ __('Subscription Status History') }}</h2>
                    <table class="min-w-full text-sm">
                        <thead class="bg-ocean-deep text-white">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium">{{ __('Date') }}</th>
                                <th class="px-4 py-3 text-left font-medium">{{ __('From') }}</th>
                                <th class="px-4 py-3 text-left font-medium">{{ __('To') }}</th>
                                <th class="px-4 py-3 text-left font-medium">{{ __('Reason') }}</th>
                                <th class="px-4 py-3 text-left font-medium">{{ __('Actor') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border dark:divide-gray-700">
                            <tr v-for="entry in statusHistory" :key="entry.id">
                                <td class="px-4 py-3 text-ocean-deep dark:text-gray-100">{{ formatDate(entry.created_at) }}</td>
                                <td class="px-4 py-3 text-muted-foreground">{{ entry.from_status ?? '-' }}</td>
                                <td class="px-4 py-3 text-ocean-deep dark:text-gray-100">{{ entry.to_status }}</td>
                                <td class="px-4 py-3 text-muted-foreground">{{ entry.reason ?? '-' }}</td>
                                <td class="px-4 py-3 text-muted-foreground">{{ entry.actor_name ?? __('System') }}</td>
                            </tr>
                            <tr v-if="!statusHistory.length">
                                <td colspan="5" class="px-4 py-6 text-center text-muted-foreground">{{ __('No status changes recorded.') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="bg-white rounded-xl shadow-card overflow-hidden dark:bg-gray-800">
                    <h2 class="font-heading font-semibold text-ocean-deep border-b px-4 py-3 dark:text-gray-100 dark:border-gray-700">{{ __('Audit Log') }}</h2>
                    <table class="min-w-full text-sm">
                        <thead class="bg-ocean-deep text-white">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium">{{ __('Date') }}</th>
                                <th class="px-4 py-3 text-left font-medium">{{ __('Action') }}</th>
                                <th class="px-4 py-3 text-left font-medium">{{ __('Actor') }}</th>
                                <th class="px-4 py-3 text-left font-medium">{{ __('Changes') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border dark:divide-gray-700">
                            <tr v-for="log in auditLogs" :key="log.id">
                                <td class="px-4 py-3 text-ocean-deep dark:text-gray-100">{{ formatDate(log.created_at) }}</td>
                                <td class="px-4 py-3 text-ocean-deep dark:text-gray-100">{{ log.action }}</td>
                                <td class="px-4 py-3 text-muted-foreground">{{ log.actor_name ?? __('System') }}</td>
                                <td class="px-4 py-3 text-xs text-muted-foreground">
                                    <span v-for="key in changedKeys(log)" :key="key" class="mr-2 inline-block">
                                        {{ key }}: {{ log.before?.[key] ?? '-' }} &rarr; {{ log.after?.[key] ?? '-' }}
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="!auditLogs.length">
                                <td colspan="4" class="px-4 py-6 text-center text-muted-foreground">{{ __('No audit entries recorded.') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </PlatformLayout>
</template>
