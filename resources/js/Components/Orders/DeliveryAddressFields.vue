<script setup>
import { computed, nextTick, ref, watch } from 'vue';
import axios from 'axios';
import { useTranslate } from '@/Composables/useTranslate';
import {
    deliveryMapsUrl,
    emptyDeliveryAddress,
    formatZip,
    matchNeighborhood,
    zipDigits,
} from '@/Utils/deliveryAddress';

defineOptions({ name: 'DeliveryAddressFields' });

const props = defineProps({
    modelValue: {
        type: Object,
        default: () => emptyDeliveryAddress(),
    },
    neighborhoods: {
        type: Array,
        default: () => [],
    },
    savedAddresses: {
        type: Array,
        default: () => [],
    },
    venueCity: {
        type: String,
        default: '',
    },
    venueState: {
        type: String,
        default: '',
    },
    canRegister: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['update:modelValue', 'registered']);

const __ = useTranslate();
const numberInput = ref(null);
const lookingUpCep = ref(false);
const registeringNeighborhood = ref(false);
const cepWarning = ref('');
const neighborhoodWarning = ref('');
const selectedSavedId = ref('new');
const noNumber = ref(false);
const lastLookedUpCep = ref('');

const fieldClass = 'w-full min-h-11 rounded-md border border-border bg-white px-3 py-2.5 text-base focus:outline-none focus:ring-2 focus:ring-primary';

const address = computed({
    get: () => ({ ...emptyDeliveryAddress(), ...(props.modelValue ?? {}) }),
    set: (next) => emit('update:modelValue', next),
});

const labeledNeighborhoods = computed(() =>
    (props.neighborhoods ?? []).filter((neighborhood) => neighborhood.has_label !== false && neighborhood.name),
);

const hideCityState = computed(() => Boolean(String(props.venueCity ?? '').trim()));

const unmatchedNeighborhood = computed(() => {
    const name = String(address.value.neighborhood ?? '').trim();

    return Boolean(name) && !matchNeighborhood(name, labeledNeighborhoods.value);
});

const mapsUrl = computed(() => deliveryMapsUrl({
    ...address.value,
    city: address.value.city || props.venueCity,
    state: address.value.state || props.venueState,
}));

const patch = (partial) => {
    address.value = { ...address.value, ...partial };
};

const formatDeliveryPhone = (value) => {
    const digits = String(value ?? '').replace(/\D/g, '').slice(0, 11);

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

const applySavedAddress = (saved) => {
    selectedSavedId.value = saved?.id ?? 'new';
    noNumber.value = String(saved?.number ?? '').toUpperCase() === 'S/N';
    cepWarning.value = '';
    neighborhoodWarning.value = '';
    lastLookedUpCep.value = zipDigits(saved?.zip_code);

    patch({
        zip_code: formatZip(saved?.zip_code ?? ''),
        street: saved?.street ?? '',
        number: saved?.number ?? '',
        complement: saved?.complement ?? '',
        delivery_fee_zone_id: saved?.delivery_fee_zone_id ?? '',
        neighborhood: saved?.neighborhood ?? '',
        delivery_fee: saved?.delivery_fee ?? saved?.fee ?? null,
        city: saved?.city ?? '',
        state: saved?.state ?? '',
        reference_point: saved?.reference_point ?? '',
        delivery_phone: formatDeliveryPhone(saved?.delivery_phone ?? ''),
    });
};

const useNewAddress = () => {
    selectedSavedId.value = 'new';
    noNumber.value = false;
    cepWarning.value = '';
    neighborhoodWarning.value = '';
    lastLookedUpCep.value = '';
    patch(emptyDeliveryAddress());
};

const lookupCep = async (rawZip) => {
    const digits = zipDigits(rawZip);

    if (digits.length !== 8 || digits === lastLookedUpCep.value) {
        return;
    }

    lastLookedUpCep.value = digits;
    lookingUpCep.value = true;
    cepWarning.value = '';
    neighborhoodWarning.value = '';

    try {
        const response = await fetch(`https://viacep.com.br/ws/${digits}/json/`);
        const data = await response.json();

        if (!response.ok || data?.erro) {
            cepWarning.value = __('ZIP code not found, fill in manually');

            return;
        }

        const viaCepNeighborhood = String(data.bairro ?? '').trim();
        const matched = matchNeighborhood(viaCepNeighborhood, labeledNeighborhoods.value);

        patch({
            zip_code: formatZip(digits),
            street: data.logradouro || address.value.street,
            neighborhood: matched?.name ?? viaCepNeighborhood,
            delivery_fee_zone_id: matched?.id ?? '',
            delivery_fee: matched?.fee ?? address.value.delivery_fee,
            city: data.localidade || address.value.city,
            state: data.uf || address.value.state,
        });

        if (viaCepNeighborhood && !matched) {
            neighborhoodWarning.value = __('Neighborhood not served or not registered');
        }

        await nextTick();
        numberInput.value?.focus();
    } catch {
        cepWarning.value = __('ZIP code not found, fill in manually');
    } finally {
        lookingUpCep.value = false;
    }
};

watch(
    () => zipDigits(address.value.zip_code),
    (digits) => {
        if (digits.length === 8) {
            lookupCep(digits);
        }
    },
);

watch(noNumber, (checked) => {
    if (checked) {
        patch({ number: 'S/N' });
    } else if (String(address.value.number).toUpperCase() === 'S/N') {
        patch({ number: '' });
    }
});

const parseFee = (value) => {
    const normalized = String(value ?? '').replace(/\s/g, '').replace(',', '.');

    if (normalized === '') {
        return null;
    }

    const amount = Number(normalized);

    return Number.isFinite(amount) ? amount : null;
};

const onNeighborhoodInput = (value) => {
    const matched = matchNeighborhood(value, labeledNeighborhoods.value);

    neighborhoodWarning.value = value.trim() && labeledNeighborhoods.value.length && !matched
        ? __('Neighborhood not served or not registered')
        : '';

    patch({
        neighborhood: value,
        delivery_fee_zone_id: matched?.id ?? '',
        delivery_fee: matched ? Number(matched.fee) : address.value.delivery_fee,
    });
};

const registerNeighborhood = async () => {
    const name = String(address.value.neighborhood ?? '').trim();
    const zip = zipDigits(address.value.zip_code);
    const fee = parseFee(address.value.delivery_fee);

    if (!name || zip.length !== 8) {
        return;
    }

    if (fee == null) {
        neighborhoodWarning.value = __('Enter the delivery fee');

        return;
    }

    registeringNeighborhood.value = true;

    try {
        const { data } = await axios.post(route('attendances.neighborhoods.store'), {
            name,
            zip_code: zip,
            fee,
        });
        const neighborhood = data.neighborhood;

        neighborhoodWarning.value = '';
        patch({
            neighborhood: neighborhood.name,
            delivery_fee_zone_id: neighborhood.id,
            delivery_fee: neighborhood.fee,
        });
        emit('registered', neighborhood);
    } catch {
        neighborhoodWarning.value = __('Could not register the neighborhood.');
    } finally {
        registeringNeighborhood.value = false;
    }
};
</script>

<template>
    <div data-delivery-address class="space-y-2 sm:col-span-2">
        <div v-if="savedAddresses.length" class="space-y-1.5">
            <p class="text-xs font-semibold text-ocean-deep">{{ __('Saved addresses') }}</p>
            <label
                v-for="saved in savedAddresses"
                :key="saved.id"
                class="flex cursor-pointer items-start gap-2 rounded-md border border-border bg-white px-2 py-2 text-sm"
            >
                <input
                    type="radio"
                    class="mt-1"
                    name="saved-delivery-address"
                    :value="saved.id"
                    :checked="selectedSavedId === saved.id"
                    @change="applySavedAddress(saved)"
                />
                <span class="min-w-0 leading-snug text-ocean-deep">{{ saved.line }}</span>
            </label>
            <button
                type="button"
                data-new-delivery-address
                class="text-xs font-semibold text-primary underline"
                @click="useNewAddress"
            >
                {{ __('Use a new address') }}
            </button>
        </div>

        <div>
            <label class="mb-1 block text-xs font-semibold text-ocean-deep">
                {{ __('ZIP code') }} <span class="text-destructive">*</span>
            </label>
            <div class="relative">
                <input
                    :value="address.zip_code"
                    type="text"
                    inputmode="numeric"
                    maxlength="9"
                    data-delivery-zip
                    autocomplete="postal-code"
                    :placeholder="__('00000-000')"
                    :class="fieldClass"
                    @input="patch({ zip_code: formatZip($event.target.value) })"
                />
                <span
                    v-if="lookingUpCep"
                    class="absolute inset-y-0 right-3 flex items-center text-[11px] text-muted-foreground"
                >
                    {{ __('Looking up ZIP code...') }}
                </span>
            </div>
            <p v-if="cepWarning" data-cep-warning class="mt-1 text-[11px] text-amber-700">{{ cepWarning }}</p>
        </div>

        <div>
            <label class="mb-1 block text-xs font-semibold text-ocean-deep">
                {{ __('Street') }} <span class="text-destructive">*</span>
            </label>
            <input
                :value="address.street"
                type="text"
                data-delivery-street
                autocomplete="address-line1"
                :placeholder="__('Street')"
                :class="fieldClass"
                @input="patch({ street: $event.target.value })"
            />
        </div>

        <div class="grid grid-cols-[minmax(0,1fr)_auto] items-end gap-2">
            <div class="min-w-0">
                <label class="mb-1 block text-xs font-semibold text-ocean-deep">
                    {{ __('Number') }} <span class="text-destructive">*</span>
                </label>
                <input
                    ref="numberInput"
                    :value="address.number"
                    type="text"
                    data-delivery-number
                    autocomplete="address-line2"
                    :placeholder="__('120-A')"
                    :class="fieldClass"
                    @input="patch({ number: $event.target.value }); noNumber = String($event.target.value).toUpperCase() === 'S/N'"
                />
            </div>
            <label class="mb-0.5 flex min-h-11 items-center gap-1.5 whitespace-nowrap text-xs font-semibold text-ocean-deep">
                <input v-model="noNumber" type="checkbox" data-no-number class="h-4 w-4 rounded border-border" />
                {{ __('No number') }}
            </label>
        </div>

        <div>
            <label class="mb-1 block text-xs font-semibold text-ocean-deep">{{ __('Complement') }}</label>
            <input
                :value="address.complement"
                type="text"
                data-delivery-complement
                :placeholder="__('Apt, block, house...')"
                :class="fieldClass"
                @input="patch({ complement: $event.target.value })"
            />
        </div>

        <div>
            <label class="mb-1 block text-xs font-semibold text-ocean-deep">
                {{ __('Neighborhood') }} <span class="text-destructive">*</span>
            </label>
            <input
                :value="address.neighborhood"
                type="text"
                list="delivery-neighborhoods"
                data-delivery-neighborhood
                autocomplete="address-level3"
                :placeholder="__('Choose a neighborhood')"
                :class="fieldClass"
                @input="onNeighborhoodInput($event.target.value)"
            />
            <datalist id="delivery-neighborhoods">
                <option
                    v-for="neighborhood in labeledNeighborhoods"
                    :key="neighborhood.id"
                    :value="neighborhood.name"
                />
            </datalist>
            <p v-if="neighborhoodWarning" data-neighborhood-warning class="mt-1 text-[11px] text-amber-700">
                {{ neighborhoodWarning }}
            </p>
            <div class="mt-2 grid grid-cols-[minmax(0,1fr)_auto] items-end gap-2">
                <div class="min-w-0">
                    <label class="mb-1 block text-xs font-semibold text-ocean-deep">{{ __('Delivery fee') }}</label>
                    <input
                        :value="address.delivery_fee ?? ''"
                        type="text"
                        inputmode="decimal"
                        data-delivery-fee
                        :placeholder="__('0,00')"
                        :class="fieldClass"
                        @input="patch({ delivery_fee: parseFee($event.target.value) })"
                    />
                </div>
                <button
                    v-if="canRegister && unmatchedNeighborhood"
                    type="button"
                    data-register-neighborhood
                    class="mb-0.5 min-h-11 whitespace-nowrap rounded-md bg-ocean-deep px-3 text-xs font-semibold text-white touch-manipulation disabled:opacity-40"
                    :disabled="registeringNeighborhood"
                    @click="registerNeighborhood"
                >
                    {{ __('Register neighborhood') }}
                </button>
            </div>
            <a
                v-if="mapsUrl"
                :href="mapsUrl"
                target="_blank"
                rel="noopener noreferrer"
                data-open-in-maps
                class="mt-2 inline-block text-sm font-semibold text-primary underline"
            >
                {{ __('Open in maps') }}
            </a>
        </div>

        <div v-if="!hideCityState" class="grid grid-cols-[minmax(0,1fr)_5rem] gap-2">
            <div>
                <label class="mb-1 block text-xs font-semibold text-ocean-deep">{{ __('City') }}</label>
                <input :value="address.city" type="text" readonly :class="`${fieldClass} bg-muted`" />
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-ocean-deep">{{ __('State') }}</label>
                <input :value="address.state" type="text" readonly :class="`${fieldClass} bg-muted`" />
            </div>
        </div>

        <div>
            <label class="mb-1 block text-xs font-semibold text-ocean-deep">{{ __('Landmark / How to get there') }}</label>
            <textarea
                :value="address.reference_point"
                data-delivery-reference
                rows="2"
                :placeholder="__('Ex: blue gate, next to the bakery')"
                :class="fieldClass"
                @input="patch({ reference_point: $event.target.value })"
            />
        </div>

        <div>
            <label class="mb-1 block text-xs font-semibold text-ocean-deep">{{ __('Delivery contact phone') }}</label>
            <input
                :value="address.delivery_phone"
                type="tel"
                inputmode="tel"
                data-delivery-phone
                :placeholder="__('(00) 00000-0000')"
                :class="fieldClass"
                @input="patch({ delivery_phone: formatDeliveryPhone($event.target.value) })"
            />
        </div>
    </div>
</template>
