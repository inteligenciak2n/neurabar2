<script setup>
import SettingsLayout from '@/Layouts/SettingsLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useTranslate } from '@/Composables/useTranslate';
import AppConfirmModal from '@/Components/AppConfirmModal.vue';

const props = defineProps({
    subscription: Object,    corporation: Object,
    availableModules: Array,
    venues: Array,
    blocked: Boolean,
    inGracePeriod: Boolean,
    hasPaymentMethod: Boolean,
});

const __ = useTranslate();

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
            <h1 class="font-heading text-2xl font-bold text-ocean-deep dark:text-gray-100">{{ __('Subscription') }}</h1>
        </template>

        <div class="space-y-6">
            <div v-if="blocked" class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                {{ __('Your access is suspended due to billing issues. Please pay the overdue invoices.') }}
            </div>

            <div v-else-if="inGracePeriod" class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-700">
                {{ __('Your subscription is in grace period. Regularize your payment to avoid suspension.') }}
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
                        <dt class="text-xs text-muted-foreground">{{ __('Status') }}</dt>
                        <dd class="mt-1 font-medium capitalize">{{ statusLabel(subscription.status) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-muted-foreground">{{ __('Billing Mode') }}</dt>
                        <dd class="mt-1 font-medium">{{ subscription.billing_mode === 'unified' ? __('Unified') : __('Per Venue') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-muted-foreground">{{ __('Billing Day') }}</dt>
                        <dd class="mt-1 font-medium">{{ subscription.billing_day }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-muted-foreground">{{ __('Next Due Date') }}</dt>
                        <dd class="mt-1 font-medium">{{ subscription.next_due_date ?? '-' }}</dd>
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
                    class="rounded-xl border border-border bg-white p-5 shadow-card transition-shadow hover:shadow-ocean"
                >
                    <p class="font-heading font-semibold">{{ __('Invoices') }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">{{ __('View and pay your invoices.') }}</p>
                </Link>
                <Link
                    :href="route('settings.subscription.payment-methods.index')"
                    class="rounded-xl border border-border bg-white p-5 shadow-card transition-shadow hover:shadow-ocean"
                >
                    <p class="font-heading font-semibold">{{ __('Payment Methods') }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">{{ __('Manage saved credit cards.') }}</p>
                </Link>
                <Link
                    :href="route('settings.subscription.billing-address.edit')"
                    class="rounded-xl border border-border bg-white p-5 shadow-card transition-shadow hover:shadow-ocean"
                >
                    <p class="font-heading font-semibold">{{ __('Billing Address') }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">{{ __('Fiscal and billing address information.') }}</p>
                </Link>
            </div>

            <div class="rounded-xl border border-border bg-white p-6 shadow-card">
                <h2 class="font-heading text-lg font-semibold">{{ __('Modules by Venue') }}</h2>
                <div class="mt-4 space-y-6">
                    <div v-for="venue in venues" :key="venue.id">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <h3 class="font-medium text-ocean-deep">{{ venue.name }}</h3>
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
                        <div class="mt-2 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            <div
                                v-for="module in availableModules"
                                :key="module.code"
                                class="rounded-lg border p-4"
                                :class="hasModule(venue, module.code) ? 'border-primary bg-ocean-light/30' : 'border-border'"
                            >
                                <div class="flex items-start justify-between">
                                    <div>
                                        <p class="font-medium">{{ module.name }}</p>
                                        <p class="text-xs text-muted-foreground">R$ {{ module.monthly_price.toFixed(2) }}/mês</p>
                                    </div>
                                    <button
                                        type="button"
                                        role="switch"
                                        :aria-checked="hasModule(venue, module.code)"
                                        :aria-label="`${module.name} — ${venue.name}`"
                                        class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                        :class="hasModule(venue, module.code) ? 'bg-primary' : 'bg-gray-200'"
                                        :disabled="blocked || pendingModuleKey !== null"
                                        @click="toggleModule(venue, module.code)"
                                    >
                                        <span
                                            class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                                            :class="hasModule(venue, module.code) ? 'translate-x-6' : 'translate-x-1'"
                                        />
                                    </button>
                                </div>
                            </div>
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
    </SettingsLayout>
</template>
