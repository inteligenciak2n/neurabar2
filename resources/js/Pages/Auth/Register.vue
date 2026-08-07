<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import AuthenticationCard from '@/Components/AuthenticationCard.vue';
import AuthenticationCardLogo from '@/Components/AuthenticationCardLogo.vue';
import DialogModal from '@/Components/DialogModal.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import CustomHead from '@/Components/CustomHead.vue';
import { ref, computed } from 'vue';

const page = usePage();

const readPromoFromUrl = () => {
    const queryString = page.url.split('?')[1] ?? '';
    const promo = new URLSearchParams(queryString).get('promo') ?? '';

    return promo.trim().slice(0, 64);
};

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    affiliate_code: readPromoFromUrl(),
});

const showPassword = ref(false);
const showConfirm = ref(false);

const showAffiliateModal = ref(false);
const affiliateCodeInput = ref(form.affiliate_code);

const openAffiliateModal = () => {
    affiliateCodeInput.value = form.affiliate_code;
    showAffiliateModal.value = true;
};

const applyAffiliateCode = () => {
    form.affiliate_code = affiliateCodeInput.value.trim().slice(0, 64);
    showAffiliateModal.value = false;
};

const clearAffiliateCode = () => {
    form.affiliate_code = '';
    affiliateCodeInput.value = '';
};

const passwordStrength = computed(() => {
    const val = form.password;
    let score = 0;
    if (val.length >= 8) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;
    return score;
});

const strengthLabel = computed(() => {
    const s = passwordStrength.value;
    if (form.password.length === 0) return { text: 'Use pelo menos 8 caracteres com letras e números.', color: 'text-gray-400' };
    if (s <= 2) return { text: 'Senha fraca — adicione maiúsculas e símbolos.', color: 'text-red-500' };
    if (s === 3) return { text: 'Senha razoável — boa para começar.', color: 'text-amber-500' };
    return { text: 'Senha forte — excelente!', color: 'text-green-500' };
});

const strengthColor = (index) => {
    const s = passwordStrength.value;
    if (s === 0) return 'bg-gray-200';
    if (index >= s) return 'bg-gray-200';
    if (s <= 2) return 'bg-red-500';
    if (s === 3) return 'bg-amber-500';
    return 'bg-green-500';
};

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <CustomHead :title="__('Register')" />

    <AuthenticationCard>
        <template #logo>
            <AuthenticationCardLogo />
        </template>

        <div class="mb-6">
            <h2 class="text-2xl font-semibold text-gray-900 dark:text-white tracking-tight">
                {{ __('Create your account') }}
            </h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ __('Fill in the details below to get started.') }}
            </p>
        </div>

        <form @submit.prevent="submit">
            <!-- Name -->
            <div>
                <InputLabel for="name" :value="__('Full Name')" class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5" />
                <TextInput
                    id="name"
                    v-model="form.name"
                    type="text"
                    class="mt-0 block w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 focus:border-gray-900 dark:focus:border-white focus:ring-gray-900 dark:focus:ring-white transition-all"
                    required
                    autofocus
                    autocomplete="name"
                    placeholder="Seu nome completo"
                />
                <InputError class="mt-1.5" :message="form.errors.name" />
            </div>

            <!-- Email -->
            <div class="mt-4">
                <InputLabel for="email" :value="__('Email')" class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5" />
                <TextInput
                    id="email"
                    v-model="form.email"
                    type="email"
                    class="mt-0 block w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 focus:border-gray-900 dark:focus:border-white focus:ring-gray-900 dark:focus:ring-white transition-all"
                    required
                    autocomplete="username"
                    placeholder="voce@email.com"
                />
                <InputError class="mt-1.5" :message="form.errors.email" />
            </div>

            <!-- Password -->
            <div class="mt-5">
                <InputLabel for="password" :value="__('Password')" class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5" />
                <div class="relative">
                    <TextInput
                        id="password"
                        v-model="form.password"
                        :type="showPassword ? 'text' : 'password'"
                        class="mt-0 block w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 focus:border-gray-900 dark:focus:border-white focus:ring-gray-900 dark:focus:ring-white transition-all pr-10"
                        required
                        autocomplete="new-password"
                        placeholder="••••••••"
                    />
                    <button
                        type="button"
                        @click="showPassword = !showPassword"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
                    >
                        <svg v-if="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                        </svg>
                    </button>
                </div>
                <!-- Strength bar -->
                <div class="flex gap-1 mt-2">
                    <div v-for="i in 4" :key="i" :class="['h-1 flex-1 rounded-full transition-colors duration-300', strengthColor(i - 1)]" />
                </div>
                <p :class="['text-xs mt-1.5 transition-colors', strengthLabel.color]">
                    {{ strengthLabel.text }}
                </p>
                <InputError class="mt-1" :message="form.errors.password" />
            </div>

            <!-- Confirm Password -->
            <div class="mt-4">
                <InputLabel for="password_confirmation" :value="__('Confirm Password')" class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5" />
                <div class="relative">
                    <TextInput
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        :type="showConfirm ? 'text' : 'password'"
                        class="mt-0 block w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 focus:border-gray-900 dark:focus:border-white focus:ring-gray-900 dark:focus:ring-white transition-all pr-10"
                        required
                        autocomplete="new-password"
                        placeholder="••••••••"
                    />
                    <button
                        type="button"
                        @click="showConfirm = !showConfirm"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
                    >
                        <svg v-if="!showConfirm" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                        </svg>
                    </button>
                </div>
                <InputError class="mt-1.5" :message="form.errors.password_confirmation" />
            </div>

            <!-- Actions -->
            <div class="mt-6">
                <PrimaryButton
                    class="w-full justify-center py-3 rounded-xl text-base font-medium transition-all duration-200 hover:opacity-90 active:scale-[0.99]"
                    :class="{ 'opacity-50 cursor-not-allowed': form.processing }"
                    :disabled="form.processing"
                >
                    {{ form.processing ? __('Creating account...') : __('Create account') }}
                </PrimaryButton>
            </div>

            <div class="mt-5 flex items-center justify-center gap-3">
                <Link :href="route('login')" class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                    {{ __('Already registered?') }} <span class="font-medium text-gray-900 dark:text-white">{{ __('Sign in') }}</span>
                </Link>

                <button
                    type="button"
                    :title="__('I have a promotional code')"
                    :aria-label="__('I have a promotional code')"
                    @click="openAffiliateModal"
                    class="text-gray-300 hover:text-gray-600 dark:text-gray-600 dark:hover:text-gray-300 transition-colors"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 6h.008v.008H6V6z" />
                    </svg>
                </button>
            </div>

            <div v-if="form.affiliate_code" class="mt-3 flex items-center justify-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                <span>{{ __('Promotional code') }}: <span class="font-medium text-gray-900 dark:text-white">{{ form.affiliate_code }}</span></span>
                <button type="button" @click="clearAffiliateCode" class="text-gray-400 hover:text-red-500 transition-colors">
                    {{ __('Remove') }}
                </button>
            </div>
            <InputError class="mt-1.5 text-center" :message="form.errors.affiliate_code" />
        </form>

        <DialogModal :show="showAffiliateModal" max-width="md" @close="showAffiliateModal = false">
            <template #title>
                {{ __('I have a promotional code') }}
            </template>

            <template #content>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ __('Enter the promotional code you received. You can leave it blank if you do not have one.') }}
                </p>

                <TextInput
                    v-model="affiliateCodeInput"
                    type="text"
                    maxlength="64"
                    class="mt-3 block w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 focus:border-gray-900 dark:focus:border-white focus:ring-gray-900 dark:focus:ring-white transition-all"
                    :placeholder="__('Promotional code')"
                    @keyup.enter="applyAffiliateCode"
                />
            </template>

            <template #footer>
                <SecondaryButton @click="showAffiliateModal = false">
                    {{ __('Cancel') }}
                </SecondaryButton>

                <PrimaryButton class="ms-3" @click="applyAffiliateCode">
                    {{ __('Apply') }}
                </PrimaryButton>
            </template>
        </DialogModal>
    </AuthenticationCard>
</template>