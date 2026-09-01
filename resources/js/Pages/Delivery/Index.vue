<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import AppCard from '@/Components/AppCard.vue';
import AppButton from '@/Components/AppButton.vue';
import AppConfirmModal from '@/Components/AppConfirmModal.vue';
import AppEmptyState from '@/Components/AppEmptyState.vue';
import { useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    deliveryLink: String,
    feeZones: Array,
    settings: Object,
});

const allPaymentMethods = ['cash', 'credit_card', 'debit_card', 'pix', 'other'];

const settingsForm = useForm({
    accepted_delivery_payment_methods: props.settings.accepted_delivery_payment_methods,
    delivery_enabled: props.settings.delivery_enabled,
    pickup_enabled: props.settings.pickup_enabled,
});

function toggleMethod(method) {
    const idx = settingsForm.accepted_delivery_payment_methods.indexOf(method);
    if (idx === -1) {
        settingsForm.accepted_delivery_payment_methods.push(method);
    } else {
        settingsForm.accepted_delivery_payment_methods.splice(idx, 1);
    }
}

function saveSettings() {
    settingsForm.put(route('delivery.settings.update'), { preserveScroll: true });
}

function copyLink() {
    navigator.clipboard?.writeText(props.deliveryLink);
}

const showZoneForm = ref(false);
const editingZone = ref(null);
const zoneToDelete = ref(null);

const zoneForm = useForm({
    label: '',
    zip_code_start: '',
    zip_code_end: '',
    fee: '',
    active: true,
    sort_order: '',
});

function openCreateZone() {
    editingZone.value = null;
    zoneForm.reset();
    zoneForm.active = true;
    showZoneForm.value = true;
}

function openEditZone(zone) {
    editingZone.value = zone;
    zoneForm.label = zone.label ?? '';
    zoneForm.zip_code_start = zone.zip_code_start;
    zoneForm.zip_code_end = zone.zip_code_end;
    zoneForm.fee = zone.fee;
    zoneForm.active = zone.active;
    zoneForm.sort_order = zone.sort_order ?? '';
    showZoneForm.value = true;
}

function closeZoneForm() {
    showZoneForm.value = false;
    editingZone.value = null;
    zoneForm.reset();
}

function submitZone() {
    if (editingZone.value) {
        zoneForm.put(route('delivery.fee-zones.update', editingZone.value.id), { onSuccess: closeZoneForm });
    } else {
        zoneForm.post(route('delivery.fee-zones.store'), { onSuccess: closeZoneForm });
    }
}

function confirmDeleteZone(zone) {
    zoneToDelete.value = zone;
}

function deleteZone() {
    router.delete(route('delivery.fee-zones.destroy', zoneToDelete.value.id), {
        onSuccess: () => { zoneToDelete.value = null; },
    });
}
</script>

<template>
    <AppLayout :title="__('Delivery')">
        <template #header>
            <h2 class="font-heading text-xl font-semibold text-ocean-deep dark:text-gray-100">{{ __('Delivery') }}</h2>
        </template>

        <div class="py-6 px-4 sm:px-6 space-y-6 max-w-3xl">
            <!-- Public link -->
            <AppCard>
                <h3 class="font-heading font-semibold text-ocean-deep mb-2">{{ __('Public ordering link') }}</h3>
                <div class="flex gap-2">
                    <input :value="deliveryLink" readonly class="flex-1 rounded-lg border border-border px-3 py-2 text-sm bg-muted" />
                    <AppButton variant="secondary" @click="copyLink">{{ __('Copy') }}</AppButton>
                </div>
            </AppCard>

            <!-- Settings -->
            <AppCard>
                <h3 class="font-heading font-semibold text-ocean-deep mb-4">{{ __('Settings') }}</h3>

                <div class="flex gap-6 mb-4">
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" v-model="settingsForm.delivery_enabled" />
                        {{ __('Delivery enabled') }}
                    </label>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" v-model="settingsForm.pickup_enabled" />
                        {{ __('Pickup enabled') }}
                    </label>
                </div>

                <p class="text-sm font-medium text-ocean-deep mb-2">{{ __('Accepted payment methods') }}</p>
                <div class="flex flex-wrap gap-3 mb-4">
                    <label v-for="method in allPaymentMethods" :key="method" class="flex items-center gap-2 text-sm">
                        <input
                            type="checkbox"
                            :checked="settingsForm.accepted_delivery_payment_methods.includes(method)"
                            @change="toggleMethod(method)"
                        />
                        {{ method }}
                    </label>
                </div>

                <AppButton @click="saveSettings" :disabled="settingsForm.processing">{{ __('Save settings') }}</AppButton>
            </AppCard>

            <!-- Fee zones -->
            <AppCard>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-heading font-semibold text-ocean-deep">{{ __('Delivery fee zones (by ZIP code range)') }}</h3>
                    <AppButton size="sm" @click="openCreateZone">{{ __('New zone') }}</AppButton>
                </div>

                <AppEmptyState
                    v-if="!feeZones.length"
                    :title="__('No delivery fee zones')"
                    :description="__('Create ZIP code ranges to charge different delivery fees per region.')"
                />

                <table v-else class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-muted-foreground">
                            <th class="py-2">{{ __('Label') }}</th>
                            <th class="py-2">{{ __('ZIP range') }}</th>
                            <th class="py-2">{{ __('Fee') }}</th>
                            <th class="py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="zone in feeZones" :key="zone.id" class="border-t border-border">
                            <td class="py-2">{{ zone.label ?? '—' }}</td>
                            <td class="py-2">{{ zone.zip_code_start }} – {{ zone.zip_code_end }}</td>
                            <td class="py-2">R$ {{ Number(zone.fee).toFixed(2) }}</td>
                            <td class="py-2 text-right space-x-2">
                                <button class="text-primary text-xs font-medium" @click="openEditZone(zone)">{{ __('Edit') }}</button>
                                <button class="text-destructive text-xs font-medium" @click="confirmDeleteZone(zone)">{{ __('Delete') }}</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </AppCard>
        </div>

        <!-- Zone form modal -->
        <div v-if="showZoneForm" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div class="w-full max-w-sm rounded-xl bg-white p-5 space-y-3">
                <h3 class="font-heading font-semibold text-ocean-deep">{{ editingZone ? __('Edit zone') : __('New zone') }}</h3>
                <input v-model="zoneForm.label" type="text" :placeholder="__('Label (optional)')" class="w-full rounded-lg border border-border px-3 py-2 text-sm" />
                <div class="flex gap-2">
                    <input v-model="zoneForm.zip_code_start" type="text" :placeholder="__('ZIP start')" class="flex-1 rounded-lg border border-border px-3 py-2 text-sm" />
                    <input v-model="zoneForm.zip_code_end" type="text" :placeholder="__('ZIP end')" class="flex-1 rounded-lg border border-border px-3 py-2 text-sm" />
                </div>
                <input v-model="zoneForm.fee" type="number" step="0.01" :placeholder="__('Fee')" class="w-full rounded-lg border border-border px-3 py-2 text-sm" />
                <div class="flex justify-end gap-2 pt-2">
                    <AppButton variant="secondary" @click="closeZoneForm">{{ __('Cancel') }}</AppButton>
                    <AppButton @click="submitZone" :disabled="zoneForm.processing">{{ __('Save') }}</AppButton>
                </div>
            </div>
        </div>

        <AppConfirmModal
            :show="!!zoneToDelete"
            :title="__('Delete zone')"
            :message="__('Are you sure you want to delete this delivery fee zone?')"
            :confirm-label="__('Delete')"
            variant="destructive"
            @confirm="deleteZone"
            @cancel="zoneToDelete = null"
        />
    </AppLayout>
</template>
