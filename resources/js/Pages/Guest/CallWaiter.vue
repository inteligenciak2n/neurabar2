<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import AppButton from '@/Components/AppButton.vue';
import AppCard from '@/Components/AppCard.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import axios from 'axios';

const props = defineProps({
    venueName: String,
    headerUrl: String,
    passphraseRequired: Boolean,
});

const protocol = ref(null);
const submitted = ref(false);
const serverError = ref(null);

const form = useForm({
    customer_identifier: '',
    message: '',
    passphrase: '',
});

async function submit() {
    serverError.value = null;

    try {
        const response = await axios.post(window.location.href, {
            customer_identifier: form.customer_identifier,
            message: form.message,
            passphrase: props.passphraseRequired ? form.passphrase : undefined,
        });

        protocol.value = response.data.protocol;
        submitted.value = true;
    } catch (error) {
        if (error.response?.status === 422) {
            const errors = error.response.data.errors ?? {};
            form.errors.passphrase = errors.passphrase?.[0] ?? null;
            form.errors.message = errors.message?.[0] ?? null;
        } else {
            serverError.value = 'An error occurred. Please try again.';
        }
    }
}
</script>

<template>
    <GuestLayout :title="`${__('Call Waiter')} — ${venueName}`">
        <!-- Header image -->
        <div v-if="headerUrl" class="mb-6 -mt-8 rounded-xl overflow-hidden">
            <img :src="headerUrl" :alt="venueName" class="w-full h-40 object-cover" />
        </div>

        <!-- Success state -->
        <AppCard v-if="submitted">
            <div class="flex flex-col items-center gap-4 py-6 text-center">
                <div class="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center">
                    <svg class="w-8 h-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <div>
                    <h2 class="font-heading text-xl font-bold text-ocean-deep">{{ __('Request sent!') }}</h2>
                    <p class="mt-1 text-sm text-muted-foreground">{{ __('The waiter has been notified and will be with you shortly.') }}</p>
                </div>
                <p class="text-xs text-muted-foreground">{{ __('Protocol') }}: <span class="font-mono font-medium">{{ protocol }}</span></p>
            </div>
        </AppCard>

        <!-- Request form -->
        <AppCard v-else :title="`Call Waiter — ${venueName}`">
            <form @submit.prevent="submit" class="flex flex-col gap-4">
                <div>
                    <label class="block text-sm font-medium text-ocean-deep mb-1">
                        {{ __('Your table / name') }} <span class="text-muted-foreground text-xs">({{ __('optional') }})</span>
                    </label>
                    <input
                        v-model="form.customer_identifier"
                        type="text"
                        placeholder="e.g. Table 5 or João"
                        class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                    />
                </div>

                <div>
                    <label class="block text-sm font-medium text-ocean-deep mb-1">
                        {{ __('Message') }} <span class="text-destructive">*</span>
                    </label>
                    <textarea
                        v-model="form.message"
                        rows="3"
                        placeholder="{{ __('How can we help? e.g. \'Need more napkins\' or \'Ready to order\'') }}"
                        class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary resize-none"
                        maxlength="500"
                    />
                    <p v-if="form.errors.message" class="mt-1 text-xs text-destructive">{{ form.errors.message }}</p>
                </div>

                <div v-if="passphraseRequired">
                    <label class="block text-sm font-medium text-ocean-deep mb-1">
                        {{ __('Passphrase') }} <span class="text-destructive">*</span>
                    </label>
                    <input
                        v-model="form.passphrase"
                        type="text"
                        placeholder="{{ __('Enter the venue passphrase') }}"
                        class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                    />
                    <p v-if="form.errors.passphrase" class="mt-1 text-xs text-destructive">{{ form.errors.passphrase }}</p>
                </div>

                <p v-if="serverError" class="text-xs text-destructive">{{ serverError }}</p>

                <AppButton type="submit" class="w-full" size="lg">
                    {{ __('Call Waiter') }}
                </AppButton>
            </form>
        </AppCard>
    </GuestLayout>
</template>
