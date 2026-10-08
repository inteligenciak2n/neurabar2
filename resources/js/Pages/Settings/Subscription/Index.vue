<script setup>
import SettingsLayout from '@/Layouts/SettingsLayout.vue';
import SettingsSectionHeader from '@/Components/SettingsSectionHeader.vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import { useTranslate } from '@/Composables/useTranslate';
import { useCurrency } from '@/Composables/useCurrency';
import AppConfirmModal from '@/Components/AppConfirmModal.vue';
import SubscriptionModuleCard from '@/Components/SubscriptionModuleCard.vue';
import { refreshTranslations } from '@/Translations/translationStore';

const props = defineProps({
    subscription: Object,
    corporation: Object,
    availableModules: Array,
    venues: Array,
    blocked: Boolean,
    inGracePeriod: Boolean,
    hasPaymentMethod: Boolean,
});

const translate = useTranslate();
const __ = (text, bindings = {}) => translate(text, bindings, 'Index');
const { formatMoney } = useCurrency();
const page = usePage();

const dateLocale = computed(() => {
    const locale = page.props.language?.locale ?? 'pt';

    return {
        pt: 'pt-BR',
        en: 'en-GB',
        es: 'es',
    }[locale] ?? 'pt-BR';
});

const formatDate = (value) => {
    if (! value) {
        return '-';
    }

    const normalized = String(value).split('T')[0];
    const [year, month, day] = normalized.split('-').map(Number);

    if (! year || ! month || ! day) {
        return value;
    }

    return new Intl.DateTimeFormat(dateLocale.value, {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    }).format(new Date(year, month - 1, day));
};

onMounted(async () => {
    await refreshTranslations(['Index']);
});

const confirmingCancellation = ref(false);
const canceling = ref(false);
const activatingGateway = ref(false);
const pendingModuleKey = ref(null);

const cancelSubscription = () => {
    if (canceling.value) {
        return;
    }

    canceling.value = true;

    router.post(route('settings.subscription.cancel'), {}, {
        preserveScroll: true,
        onFinish: () => {
            canceling.value = false;
            confirmingCancellation.value = false;
        },
    });
};

const activateGateway = (venueId = null) => {
    if (activatingGateway.value) {
        return;
    }

    activatingGateway.value = true;

    router.post(route('settings.subscription.gateway.activate'), venueId ? { venue_id: venueId } : {}, {
        preserveScroll: true,
        onFinish: () => (activatingGateway.value = false),
    });
};

const hasModule = (venue, moduleCode) => venue.modules.some((m) => m.code === moduleCode);

const moduleSalesCopy = {
    delivery: 'Delivery sales copy',
    menu: 'Menu sales copy',
    self_order: 'Self order sales copy',
    taker: 'Taker sales copy',
    kds: 'Kds sales copy',
    direct_print: 'Direct print sales copy',
    direct_waiter: 'Direct waiter sales copy',
    financial_dashboard: 'Financial dashboard sales copy',
    production_dashboard: 'Production dashboard sales copy',
    fiscal_note: 'Fiscal note sales copy',
    voice_command: 'Voice command sales copy',
};

const moduleDescriptionFallbacks = {
    delivery: 'Delivery module scaffold.',
    production_dashboard: 'Production dashboard module scaffold.',
    financial_dashboard: 'Financial dashboard module scaffold.',
    voice_command: 'Voice command module scaffold.',
    direct_waiter: 'Direct waiter module scaffold.',
    direct_print: 'Direct print module scaffold.',
};

const moduleDescription = (module) => {
    const salesKey = moduleSalesCopy[module.code];

    if (salesKey) {
        return __(salesKey);
    }

    if (module.description?.trim()) {
        return module.description;
    }

    const fallbackKey = moduleDescriptionFallbacks[module.code];

    if (fallbackKey) {
        return __(fallbackKey);
    }

    return __('Describe the functionality of this module here.');
};

const modulePriceLabel = (module) => __(':price / month', { price: formatMoney(module.monthly_price) });

const moduleClickToAddLine1 = () => __('Click here to add line 1');

const moduleClickToAddLine2 = () => __('Click here to add line 2');

const moduleClickToAddPriceLine = (module) => __('Click here to add for price', { price: formatMoney(module.monthly_price) });

const proratedAmountFor = (monthlyPrice) => {
    const now = new Date();
    const daysInMonth = new Date(now.getFullYear(), now.getMonth() + 1, 0).getDate();
    const remainingDays = daysInMonth - now.getDate() + 1;

    return Math.round((monthlyPrice * remainingDays) / daysInMonth);
};

const moduleLearnMoreMonthlyValue = (module) => __('Learn more monthly value', {
    price: formatMoney(module.monthly_price),
    amount: formatMoney(proratedAmountFor(module.monthly_price)),
});

const moduleLearnMoreCustomerAccessTitle = (module) => {
    if (module.code === 'direct_print' || module.code === 'voice_command' || module.code === 'production_dashboard' || module.code === 'taker' || module.code === 'kds' || module.code === 'menu' || module.code === 'self_order') {
        return __('How it works');
    }

    return __('How the customer accesses');
};

const moduleLearnMoreCustomerAccess = (module) => {
    if (module.code === 'delivery') {
        return __('Learn more delivery customer access');
    }

    if (module.code === 'direct_print') {
        return __('Learn more direct print how it works');
    }

    if (module.code === 'direct_waiter') {
        return __('Learn more direct waiter customer access');
    }

    if (module.code === 'voice_command') {
        return __('Learn more voice command how it works');
    }

    if (module.code === 'production_dashboard') {
        return __('Learn more production dashboard how it works');
    }

    if (module.code === 'taker') {
        return __('Learn more taker how it works');
    }

    if (module.code === 'kds') {
        return __('Learn more kds how it works');
    }

    if (module.code === 'menu') {
        return __('Learn more menu how it works');
    }

    if (module.code === 'self_order') {
        return __('Learn more self order how it works');
    }

    if (module.code === 'financial_dashboard') {
        return '';
    }

    return __('Learn more customer access');
};

const moduleLearnMoreOrderFlowTitle = (module) => {
    if (module.code === 'direct_print') {
        return __('Advantage');
    }

    return __('How the order reaches the restaurant');
};

const moduleLearnMoreOrderFlow = (module) => {
    if (module.code === 'delivery') {
        return __('Learn more delivery order flow');
    }

    if (module.code === 'direct_print') {
        return __('Learn more direct print advantage');
    }

    if (module.code === 'direct_waiter') {
        return __('Learn more direct waiter order flow');
    }

    if (module.code === 'financial_dashboard' || module.code === 'voice_command' || module.code === 'production_dashboard' || module.code === 'taker' || module.code === 'kds' || module.code === 'menu' || module.code === 'self_order') {
        return '';
    }

    return __('Learn more order flow');
};

const moduleLearnMoreAdvantageTitle = (module) => {
    if (module.code === 'direct_waiter' || module.code === 'financial_dashboard' || module.code === 'voice_command' || module.code === 'production_dashboard' || module.code === 'taker' || module.code === 'kds' || module.code === 'menu' || module.code === 'self_order') {
        return __('Advantage');
    }

    return '';
};

const moduleLearnMoreAdvantage = (module) => {
    if (module.code === 'direct_waiter') {
        return __('Learn more direct waiter advantage');
    }

    if (module.code === 'financial_dashboard') {
        return __('Learn more financial dashboard advantage');
    }

    if (module.code === 'voice_command') {
        return __('Learn more voice command advantage');
    }

    if (module.code === 'production_dashboard') {
        return __('Learn more production dashboard advantage');
    }

    if (module.code === 'taker') {
        return __('Learn more taker advantage');
    }

    if (module.code === 'kds') {
        return __('Learn more kds advantage');
    }

    if (module.code === 'menu') {
        return __('Learn more menu advantage');
    }

    if (module.code === 'self_order') {
        return __('Learn more self order advantage');
    }

    return '';
};

const moduleLearnMoreSavingsTitle = (module) => {
    if (module.code === 'delivery' || module.code === 'direct_print' || module.code === 'direct_waiter') {
        return __('How you save with this module');
    }

    return '';
};

const moduleLearnMoreSavingsKind = (module) => {
    if (module.code === 'direct_print' || module.code === 'direct_waiter') {
        return 'print';
    }

    return 'delivery';
};

const moduleLearnMoreSavingsDescription = (module) => {
    if (module.code === 'direct_print') {
        return __('Learn more print savings copy');
    }

    if (module.code === 'direct_waiter') {
        return __('Learn more direct waiter savings copy');
    }

    return __('Learn more savings copy');
};

const moduleLearnMoreSavingsEstimateLabel = (module) => {
    if (module.code === 'direct_print' || module.code === 'direct_waiter') {
        return __('Estimated print monthly savings');
    }

    return __('Estimated monthly savings');
};

const moduleLearnMoreSavingsDisclaimer = (module) => {
    if (module.code === 'direct_print' || module.code === 'direct_waiter') {
        return __('Estimated savings disclaimer');
    }

    return '';
};

const moduleLearnMoreKitchenTripTimeLabel = (module) => {
    if (module.code === 'direct_waiter') {
        return __('Simple service time');
    }

    return __('Kitchen trip time');
};

const moduleLearnMoreKitchenTripCountLabel = (module) => {
    if (module.code === 'direct_waiter') {
        return __('Simple order count');
    }

    return __('Kitchen trip count');
};

const moduleLearnMoreInitialWorkDays = (module) => {
    if (module.code === 'direct_waiter') {
        return 20;
    }

    return 30;
};

const isModuleLoading = (venue, moduleCode) => pendingModuleKey.value === `${venue.id}:${moduleCode}`;

// Contratar um módulo pago custa dinheiro: a troca acontecia em um clique,
// sem confirmação e sem mostrar quanto seria cobrado.
const pendingModule = ref(null);

const trialDaysLeft = computed(() => {
    if (! props.subscription?.trial_ends_at || props.subscription.status !== 'trial') {
        return null;
    }

    const endsAt = new Date(props.subscription.trial_ends_at);
    const days = Math.ceil((endsAt.getTime() - Date.now()) / 86400000);

    return days > 0 ? days : 0;
});

// A cobrança do mês de adesão é proporcional aos dias restantes, igual ao que
// o `SubscriptionCalculator` faz no faturamento.
const proratedAmount = computed(() => {
    if (! pendingModule.value) {
        return 0;
    }

    return proratedAmountFor(pendingModule.value.module.monthly_price);
});

const remainingBillingDays = computed(() => {
    const now = new Date();
    const daysInMonth = new Date(now.getFullYear(), now.getMonth() + 1, 0).getDate();

    return daysInMonth - now.getDate() + 1;
});

const confirmTitle = computed(() => {
    if (! pendingModule.value) {
        return '';
    }

    const { module, action } = pendingModule.value;

    if (action === 'remove') {
        return __('Do you want to remove the module :module?', { module: __(module.name) });
    }

    return __('Do you want to enable the module :module?', { module: __(module.name) });
});

const confirmMessage = computed(() => {
    if (! pendingModule.value) {
        return '';
    }

    const { action } = pendingModule.value;

    if (action === 'remove') {
        return [
            __('When removing this module it will be deactivated on the next billing cycle.'),
            __('You still have :days days to use it', { days: remainingBillingDays.value }),
        ].join('\n\n');
    }

    return [
        __('When enabling this module it will work immediately.'),
        __('A partial charge of :amount will be billed on the next invoice.', {
            amount: formatMoney(proratedAmount.value),
        }),
    ].join('\n\n');
});

const confirmButtonLabel = computed(() => {
    if (pendingModule.value?.action === 'remove') {
        return __('Yes, I want to remove');
    }

    return __('Yes, I want to enable');
});

const cancelButtonLabel = computed(() => {
    if (pendingModule.value?.action === 'remove' || pendingModule.value?.action === 'add') {
        return __('Cancel, do not change anything');
    }

    return __('Cancel');
});

const requestToggle = (venue, module) => {
    if (pendingModuleKey.value) {
        return;
    }

    pendingModule.value = {
        venue,
        module,
        action: hasModule(venue, module.code) ? 'remove' : 'add',
    };
};

const confirmToggle = () => {
    if (! pendingModule.value) {
        return;
    }

    const { venue, module } = pendingModule.value;

    pendingModule.value = null;
    toggleModule(venue, module.code);
};

const toggleModule = (venue, moduleCode) => {
    const key = `${venue.id}:${moduleCode}`;

    if (pendingModuleKey.value) {
        return;
    }

    pendingModuleKey.value = key;

    const options = {
        preserveScroll: true,
        onFinish: () => (pendingModuleKey.value = null),
    };

    if (hasModule(venue, moduleCode)) {
        router.delete(route('settings.subscription.modules.destroy', { venue: venue.id, moduleCode }), options);

        return;
    }

    router.post(route('settings.subscription.modules.store', { venue: venue.id }), {
        module_code: moduleCode,
        quantity: 1,
    }, options);
};

const statusLabel = (status) => ({
    trial: __('Trial'),
    active: __('Active'),
    past_due: __('Past due'),
    suspended: __('Suspended'),
    canceled: __('Canceled'),
}[status] ?? status);
</script>

<template>
    <SettingsLayout :title="__('Subscription')">
        <template #header>
            <SettingsSectionHeader :title="__('Subscription')">
                <span class="block">{{ __('Save more time by subscribing to more modules') }}</span>
                <span class="block">{{ __('See each new feature and subscribe whenever you want') }}</span>
                <span class="block">{{ __('View your current subscription') }}</span>
            </SettingsSectionHeader>
        </template>

        <div class="space-y-6">
            <div class="flex justify-end">
                <Link :href="route('settings.subscription.usage')" class="text-sm font-medium text-primary hover:underline">
                    {{ __('View usage and limits') }}
                </Link>
            </div>
            <div v-if="blocked" class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                {{ __('Your access is suspended due to billing issues. Please pay the overdue invoices.') }}
            </div>

            <div v-else-if="inGracePeriod" class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-700">
                {{ __('Your subscription is in grace period. Regularize your payment to avoid suspension.') }}
            </div>

            <div v-if="trialDaysLeft !== null" class="rounded-xl border border-ocean-light bg-ocean-light/30 p-4 text-sm text-ocean-deep">
                <template v-if="trialDaysLeft > 0">
                    {{ __('Your trial ends in:') }}
                    <strong>{{ trialDaysLeft }} {{ __('Day(s)') }}</strong>.
                    <span v-if="!hasPaymentMethod">{{ ' ' }}{{ __('Add a credit card to keep your access after the trial period.') }}</span>
                </template>
                <template v-else>
                    {{ __('Your trial ends today.') }}
                    <span v-if="!hasPaymentMethod">{{ ' ' }}{{ __('Add a credit card to keep your access after the trial period.') }}</span>
                </template>
            </div>

            <div class="rounded-xl border border-border bg-white p-6 shadow-card">
                <div class="flex items-start justify-between">
                    <h2 class="font-heading text-lg font-semibold">{{ __('Subscription Summary') }}</h2>
                    <button
                        v-if="subscription.status !== 'canceled'"
                        type="button"
                        class="text-sm font-medium text-red-600 hover:underline"
                        @click="confirmingCancellation = true"
                    >
                        {{ __('Cancel Subscription') }}
                    </button>
                </div>
                <dl class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <dt class="text-xs text-muted-foreground">{{ __('System Status') }}</dt>
                        <dd class="mt-1 font-medium capitalize">{{ statusLabel(subscription.status) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-muted-foreground">{{ __('Billing Mode') }}</dt>
                        <dd class="mt-1 font-medium">{{ subscription.billing_mode === 'unified' ? __('Unified') : __('Per Venue') }}</dd>
                        <small class="text-xs text-muted-foreground">{{ __('To unify your billing, please contact support.') }}</small>
                    </div>
                    <div>
                        <dt class="text-xs text-muted-foreground">{{ __('Billing Day') }}</dt>
                        <dd class="mt-1 font-medium">{{ subscription.billing_day }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-muted-foreground">{{ __('Next Due Date') }}</dt>
                        <dd class="mt-1 font-medium">{{ formatDate(subscription.next_due_date) }}</dd>
                    </div>
                </dl>
            </div>

            <div v-if="subscription.billing_mode === 'unified'" class="rounded-xl border border-border bg-white p-6 shadow-card">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h2 class="font-heading text-lg font-semibold">{{ __('Automatic Billing') }}</h2>
                        <p class="mt-1 text-sm text-muted-foreground">
                            <span v-if="subscription.is_billed_by_gateway" class="font-medium text-emerald-600">{{ __('Automatic billing is active.') }}</span>
                            <span v-else-if="!hasPaymentMethod">{{ __('Add a credit card before enabling automatic billing.') }}</span>
                            <span v-else>{{ __('Enable automatic billing with your default credit card.') }}</span>
                        </p>
                    </div>
                    <Link
                        v-if="!subscription.is_billed_by_gateway && !hasPaymentMethod"
                        :href="route('settings.subscription.payment-methods.index')"
                        class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white hover:opacity-90"
                    >
                        {{ __('Add Credit Card') }}
                    </Link>
                    <button
                        v-else-if="!subscription.is_billed_by_gateway"
                        type="button"
                        class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white hover:opacity-90"
                        @click="activateGateway()"
                    >
                        {{ __('Activate Automatic Billing') }}
                    </button>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <Link
                    :href="route('settings.subscription.invoices.index')"
                    prefetch
                    class="rounded-xl border border-border bg-white p-5 shadow-card transition-shadow hover:shadow-ocean"
                >
                    <p class="font-heading font-semibold">{{ __('Invoices') }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">{{ __('View and pay your invoices.') }}</p>
                </Link>
                <Link
                    :href="route('settings.subscription.payment-methods.index')"
                    prefetch
                    class="rounded-xl border border-border bg-white p-5 shadow-card transition-shadow hover:shadow-ocean"
                >
                    <p class="font-heading font-semibold">{{ __('Payment Methods') }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">{{ __('Manage saved credit cards.') }}</p>
                </Link>
                <Link
                    :href="route('settings.subscription.billing-address.edit')"
                    prefetch
                    class="rounded-xl border border-border bg-white p-5 shadow-card transition-shadow hover:shadow-ocean"
                >
                    <p class="font-heading font-semibold">{{ __('Billing Address') }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">{{ __('Fiscal and billing address information.') }}</p>
                </Link>
                <Link
                    :href="route('settings.subscription.usage')"
                    prefetch
                    class="rounded-xl border border-border bg-white p-5 shadow-card transition-shadow hover:shadow-ocean"
                >
                    <p class="font-heading font-semibold">{{ __('Usage') }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">{{ __('View your subscription usage.') }}</p>
                </Link>
            </div>

            <div class="rounded-xl border border-border bg-white p-6 shadow-card dark:border-gray-700 dark:bg-gray-800">
                <h2 class="font-heading text-lg font-semibold">{{ __('Modules by Venue') }}</h2>
                <div class="mt-4 space-y-8">
                    <div v-for="venue in venues" :key="venue.id">
                        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                            <h3 class="font-medium text-ocean-deep dark:text-gray-100">{{ venue.name }}</h3>
                            <template v-if="subscription.billing_mode === 'per_venue'">
                                <span v-if="venue.is_billed_by_gateway" class="text-xs font-medium text-emerald-600">
                                    {{ __('Automatic billing is active.') }}
                                </span>
                                <button
                                    v-else
                                    type="button"
                                    class="text-xs font-medium text-primary hover:underline disabled:cursor-not-allowed disabled:opacity-50"
                                    :disabled="!hasPaymentMethod"
                                    @click="activateGateway(venue.id)"
                                >
                                    {{ __('Activate Automatic Billing') }}
                                </button>
                            </template>
                        </div>
                        <div class="flex max-w-5xl flex-col gap-2">
                            <SubscriptionModuleCard
                                v-for="(module, moduleIndex) in availableModules"
                                :key="`${venue.id}-${module.code}`"
                                :module="module"
                                :venue-name="venue.name"
                                :enabled="hasModule(venue, module.code)"
                                :disabled="blocked || pendingModuleKey !== null"
                                :loading="isModuleLoading(venue, module.code)"
                                :description="moduleDescription(module)"
                                :price-label="modulePriceLabel(module)"
                                :status-title="__('Current status label')"
                                :status-value="hasModule(venue, module.code) ? __('Status on') : __('Status off')"
                                :click-to-add-line1="moduleClickToAddLine1()"
                                :click-to-add-line2="moduleClickToAddLine2()"
                                :click-to-add-price-line="moduleClickToAddPriceLine(module)"
                                :sequence="moduleIndex + 1"
                                :learn-more-label="__('Click here and learn more')"
                                :learn-more-title="__('Learn more about this module')"
                                :monthly-value-title="__('Monthly value')"
                                :customer-access-title="moduleLearnMoreCustomerAccessTitle(module)"
                                :order-flow-title="moduleLearnMoreOrderFlowTitle(module)"
                                :advantage-title="moduleLearnMoreAdvantageTitle(module)"
                                :monthly-value="moduleLearnMoreMonthlyValue(module)"
                                :customer-access="moduleLearnMoreCustomerAccess(module)"
                                :order-flow="moduleLearnMoreOrderFlow(module)"
                                :advantage="moduleLearnMoreAdvantage(module)"
                                :close-label="__('Close')"
                                :activate-label="__('Click here to activate this module')"
                                :savings-title="moduleLearnMoreSavingsTitle(module)"
                                :savings-kind="moduleLearnMoreSavingsKind(module)"
                                :savings-description="moduleLearnMoreSavingsDescription(module)"
                                :average-order-label="__('Average order value')"
                                :monthly-orders-label="__('Orders per month')"
                                :marketplace-fee-label="__('Marketplace fee percent')"
                                :savings-estimate-label="moduleLearnMoreSavingsEstimateLabel(module)"
                                :savings-disclaimer="moduleLearnMoreSavingsDisclaimer(module)"
                                :kitchen-trip-time-label="moduleLearnMoreKitchenTripTimeLabel(module)"
                                :kitchen-trip-count-label="moduleLearnMoreKitchenTripCountLabel(module)"
                                :initial-work-days="moduleLearnMoreInitialWorkDays(module)"
                                :freelancer-value-label="__('Freelancer daily rate')"
                                :waiter-count-label="__('Waiter count')"
                                :work-days-label="__('Work days')"
                                :seconds-label="__('Seconds')"
                                @toggle="requestToggle(venue, module)"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <AppConfirmModal
            :show="confirmingCancellation"
            :title="__('Cancel Subscription')"
            :message="__('Are you sure you want to cancel your subscription? You will keep access until the end of the current billing period.')"
            :confirm-label="__('Cancel Subscription')"
            variant="destructive"
            :loading="canceling"
            @confirm="cancelSubscription"
            @cancel="confirmingCancellation = false"
        />

        <AppConfirmModal
            :show="pendingModule !== null"
            :title="confirmTitle"
            :message="confirmMessage"
            :confirm-label="confirmButtonLabel"
            :cancel-label="cancelButtonLabel"
            :variant="pendingModule?.action === 'remove' ? 'destructive' : 'primary'"
            @confirm="confirmToggle"
            @cancel="pendingModule = null"
        />
    </SettingsLayout>
</template>
