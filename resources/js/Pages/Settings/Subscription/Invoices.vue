<script setup>
import SettingsLayout from '@/Layouts/SettingsLayout.vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useTranslate } from '@/Composables/useTranslate';

const props = defineProps({
    venueInvoices: Object,
    corporationInvoices: Object,
    paymentMethods: Array,
    paymentMethodOptions: Array,
    filters: Object,
});

const __ = useTranslate();
const page = usePage();
const payingInvoice = ref(null);

const payForm = useForm({
    method: 'credit_card',
    payment_method_id: props.paymentMethods.find(m => m.is_default)?.id ?? '',
});

const openPayModal = (invoice) => {
    payingInvoice.value = invoice;
    payForm.method = 'credit_card';
    payForm.payment_method_id = props.paymentMethods.find(m => m.is_default)?.id ?? '';
};

const submitPayment = () => {
    if (! payingInvoice.value) {
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

const allInvoices = computed(() => {
    const venue = (props.venueInvoices?.data ?? []).map(i => ({ ...i, type: 'venue' }));
    const corp = (props.corporationInvoices?.data ?? []).map(i => ({ ...i, type: 'corporation' }));

    return [...venue, ...corp].sort((a, b) => b.period.localeCompare(a.period) || new Date(b.due_date) - new Date(a.due_date));
});

const statusClass = (status) => {
    return {
        open: 'text-amber-600',
        overdue: 'text-red-600',
        paid: 'text-green-600',
        canceled: 'text-gray-500',
        refunded: 'text-gray-500',
    }[status] ?? 'text-gray-600';
};
</script>

<template>
    <SettingsLayout :title="__('Invoices')">
        <template #header>
            <h1 class="font-heading text-2xl font-bold text-ocean-deep dark:text-gray-100">{{ __('Invoices') }}</h1>
        </template>

        <div class="rounded-xl border border-border bg-white p-6 shadow-card">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-left text-muted-foreground">
                            <th class="pb-2 font-medium">{{ __('Period') }}</th>
                            <th class="pb-2 font-medium">{{ __('Venue / Corporation') }}</th>
                            <th class="pb-2 font-medium">{{ __('Due Date') }}</th>
                            <th class="pb-2 font-medium">{{ __('Status') }}</th>
                            <th class="pb-2 font-medium text-right">{{ __('Total') }}</th>
                            <th class="pb-2 text-right">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="invoice in allInvoices" :key="invoice.id + invoice.type" class="border-b last:border-b-0">
                            <td class="py-3">{{ invoice.period }}</td>
                            <td class="py-3">{{ invoice.venue?.name ?? corporation?.name ?? '-' }}</td>
                            <td class="py-3">{{ invoice.due_date }}</td>
                            <td class="py-3 font-medium capitalize" :class="statusClass(invoice.status)">{{ invoice.status }}</td>
                            <td class="py-3 text-right">R$ {{ parseFloat(invoice.total_value).toFixed(2) }}</td>
                            <td class="py-3 text-right">
                                <button
                                    v-if="invoice.status === 'open' || invoice.status === 'overdue'"
                                    type="button"
                                    class="text-sm font-medium text-primary hover:underline"
                                    @click="openPayModal(invoice)"
                                >
                                    {{ __('Pay') }}
                                </button>
                            </td>
                        </tr>
                        <tr v-if="allInvoices.length === 0">
                            <td colspan="6" class="py-6 text-center text-muted-foreground">{{ __('No invoices found.') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div v-if="payingInvoice" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-lg">
                <h3 class="font-heading text-lg font-semibold">{{ __('Pay Invoice') }}</h3>
                <p class="mt-1 text-sm text-muted-foreground">
                    {{ payingInvoice.period }} — R$ {{ parseFloat(payingInvoice.total_value).toFixed(2) }}
                </p>

                <form class="mt-4 space-y-4" @submit.prevent="submitPayment">
                    <div>
                        <label class="block text-sm font-medium">{{ __('Payment Method') }}</label>
                        <select v-model="payForm.method" class="mt-1 block w-full rounded-md border-border shadow-sm">
                            <option v-for="option in paymentMethodOptions" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>
                    </div>

                    <div v-if="payForm.method === 'credit_card'">
                        <label class="block text-sm font-medium">{{ __('Saved Card') }}</label>
                        <select v-model="payForm.payment_method_id" class="mt-1 block w-full rounded-md border-border shadow-sm">
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
                        <button type="button" class="rounded-md border px-4 py-2 text-sm" @click="payingInvoice = null">{{ __('Cancel') }}</button>
                        <button type="submit" class="rounded-md bg-primary px-4 py-2 text-sm text-white" :disabled="payForm.processing">
                            {{ __('Confirm') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </SettingsLayout>
</template>
