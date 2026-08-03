<script setup>
import SettingsLayout from '@/Layouts/SettingsLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useTranslate } from '@/Composables/useTranslate';
import { useCurrency } from '@/Composables/useCurrency';
import AppCard from '@/Components/AppCard.vue';
import AppButton from '@/Components/AppButton.vue';
import AppPagination from '@/Components/AppPagination.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    venueInvoices: Object,
    corporationInvoices: Object,
    paymentMethods: Array,
    paymentMethodOptions: Array,
    filters: Object,
});

const __ = useTranslate();
const { formatMoney } = useCurrency();
const payingInvoice = ref(null);

const defaultPaymentMethodId = () => props.paymentMethods.find((m) => m.is_default)?.id ?? '';

const payForm = useForm({
    method: 'credit_card',
    payment_method_id: defaultPaymentMethodId(),
});

const openPayModal = (invoice) => {
    payingInvoice.value = invoice;
    payForm.clearErrors();
    payForm.method = 'credit_card';
    payForm.payment_method_id = defaultPaymentMethodId();
};

const closePayModal = () => {
    if (payForm.processing) {
        return;
    }

    payingInvoice.value = null;
};

const submitPayment = () => {
    if (! payingInvoice.value || payForm.processing) {
        return;
    }

    payForm.post(route('settings.subscription.invoices.pay', {
        invoiceType: payingInvoice.value.type,
        invoiceId: payingInvoice.value.id,
    }), {
        preserveScroll: true,
        onSuccess: () => {
            payingInvoice.value = null;
        },
    });
};

const withType = (paginator, type) => (paginator?.data ?? []).map((invoice) => ({ ...invoice, type }));

const venueRows = computed(() => withType(props.venueInvoices, 'venue'));
const corporationRows = computed(() => withType(props.corporationInvoices, 'corporation'));

const isPayable = (invoice) => invoice.status === 'open' || invoice.status === 'overdue';

const formatAmount = (cents) => formatMoney(cents);

const statusClass = (status) => ({
    open: 'text-amber-600',
    overdue: 'text-red-600',
    paid: 'text-green-600',
    canceled: 'text-gray-500',
    refunded: 'text-gray-500',
}[status] ?? 'text-gray-600');
</script>

<template>
    <SettingsLayout :title="__('Invoices')">
        <template #header>
            <h1 class="font-heading text-2xl font-bold text-ocean-deep dark:text-gray-100">{{ __('Invoices') }}</h1>
        </template>

        <div class="space-y-6">
            <Link :href="route('settings.subscription.index')" class="flex items-center text-sm text-primary hover:underline">
                <svg class="inline-block h-4 w-4 mr-1" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
                {{ __('Back') }}
            </Link>

            <AppCard v-for="section in [
                { key: 'venue', title: __('Venue invoices'), rows: venueRows, paginator: venueInvoices },
                { key: 'corporation', title: __('Corporation invoices'), rows: corporationRows, paginator: corporationInvoices },
            ]" :key="section.key" :title="section.title">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <caption class="sr-only">{{ section.title }}</caption>
                        <thead>
                            <tr class="border-b text-left text-muted-foreground">
                                <th scope="col" class="pb-2 font-medium">{{ __('Period') }}</th>
                                <th scope="col" class="pb-2 font-medium">{{ __('Venue / Corporation') }}</th>
                                <th scope="col" class="pb-2 font-medium">{{ __('Due Date') }}</th>
                                <th scope="col" class="pb-2 font-medium">{{ __('Status') }}</th>
                                <th scope="col" class="pb-2 font-medium text-right">{{ __('Total') }}</th>
                                <th scope="col" class="pb-2 text-right">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="invoice in section.rows" :key="invoice.id" class="border-b last:border-b-0">
                                <td class="py-3">{{ invoice.period }}</td>
                                <td class="py-3">{{ invoice.venue?.name ?? invoice.corporation?.name ?? '-' }}</td>
                                <td class="py-3">{{ invoice.due_date }}</td>
                                <td class="py-3 font-medium capitalize" :class="statusClass(invoice.status)">{{ invoice.status }}</td>
                                <td class="py-3 text-right">{{ formatAmount(invoice.total_value) }}</td>
                                <td class="py-3 text-right">
                                    <AppButton v-if="isPayable(invoice)" variant="ghost" size="sm" @click="openPayModal(invoice)">
                                        {{ __('Pay') }}
                                    </AppButton>
                                </td>
                            </tr>
                            <tr v-if="section.rows.length === 0">
                                <td colspan="6" class="py-6 text-center text-muted-foreground">{{ __('No invoices found.') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <AppPagination v-if="section.paginator?.links" class="mt-4" :links="section.paginator.links" />
            </AppCard>
        </div>

        <Modal :show="payingInvoice !== null" max-width="md" @close="closePayModal">
            <div class="p-6">
                <h3 id="pay-invoice-title" class="font-heading text-lg font-semibold">{{ __('Pay Invoice') }}</h3>
                <p v-if="payingInvoice" class="mt-1 text-sm text-muted-foreground">
                    {{ payingInvoice.period }} — {{ formatAmount(payingInvoice.total_value) }}
                </p>

                <form class="mt-4 space-y-4" @submit.prevent="submitPayment">
                    <div>
                        <InputLabel for="pay-method" :value="__('Payment Method')" />
                        <select id="pay-method" v-model="payForm.method" class="mt-1 block w-full rounded-md border-border shadow-sm">
                            <option v-for="option in paymentMethodOptions" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>
                    </div>

                    <div v-if="payForm.method === 'credit_card'">
                        <InputLabel for="pay-card" :value="__('Saved Card')" />
                        <select id="pay-card" v-model="payForm.payment_method_id" class="mt-1 block w-full rounded-md border-border shadow-sm">
                            <option v-for="method in paymentMethods" :key="method.id" :value="method.id">
                                {{ method.brand }} **** {{ method.last4 }} — {{ method.holder_name }}
                            </option>
                        </select>
                    </div>

                    <div v-if="payForm.method === 'pix'" class="rounded-lg bg-gray-50 p-4 text-sm text-muted-foreground">
                        {{ __('A PIX QR code will be generated after confirmation.') }}
                    </div>

                    <div v-if="payForm.method === 'boleto'" class="rounded-lg bg-gray-50 p-4 text-sm text-muted-foreground">
                        {{ __('A boleto will be generated after confirmation.') }}
                    </div>

                    <div class="flex justify-end gap-3">
                        <AppButton variant="secondary" :disabled="payForm.processing" @click="closePayModal">
                            {{ __('Cancel') }}
                        </AppButton>
                        <AppButton type="submit" :loading="payForm.processing" :disabled="payForm.processing">
                            {{ __('Confirm') }}
                        </AppButton>
                    </div>
                </form>
            </div>
        </Modal>
    </SettingsLayout>
</template>
