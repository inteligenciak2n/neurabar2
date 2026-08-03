<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Checkbox from '@/Components/Checkbox.vue';
import { useCurrency } from '@/Composables/useCurrency';
import { computed } from 'vue';

const props = defineProps({
    modules: {
        type: Array,
        default: () => [],
    },
    trialDays: {
        type: Number,
        default: 14,
    },
});

const { formatMoney } = useCurrency();

const toggleableModules = computed(() => props.modules.filter((module) => module.code !== 'menu'));

const form = useForm({
    module_codes: [],
    venue_count: 1,
    terms: false,
});

const isSelected = (code) => form.module_codes.includes(code);

const toggleModule = (code) => {
    if (isSelected(code)) {
        form.module_codes = form.module_codes.filter((value) => value !== code);
    } else {
        form.module_codes = [...form.module_codes, code];
    }
};

const monthlyTotal = computed(() => {
    const selected = props.modules.filter(
        (module) => module.code === 'menu' || form.module_codes.includes(module.code),
    );

    // Os preços chegam em centavos.
    const perVenue = selected.reduce((sum, module) => sum + Number(module.base_monthly_price ?? 0), 0);

    return perVenue * (Number(form.venue_count) || 1);
});

const formattedTotal = computed(() => formatMoney(monthlyTotal.value));

const submit = () => {
    form.post(route('onboarding.subscription.store'));
};
</script>

<template>
    <GuestLayout :title="__('Monte sua assinatura')">
        <Head :title="__('Monte sua assinatura')" />

        <div class="bg-white shadow-card rounded-xl p-8">
            <div class="text-center mb-6">
                <h1 class="text-2xl font-heading font-bold text-ocean-deep">
                    {{ __('Monte sua assinatura') }}
                </h1>
                <p class="text-muted-foreground mt-2">
                    {{ __('Selecione os módulos que deseja usar. O Cardápio já está incluso gratuitamente.') }}
                    {{ __('Você terá :days dias de teste grátis.', { days: trialDays }) }}
                </p>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <div>
                    <InputLabel :value="__('Módulos')" />
                    <div class="mt-2 space-y-2">
                        <div class="flex items-center justify-between rounded-md border border-border px-3 py-2.5 bg-muted/50">
                            <span class="text-sm font-medium text-ocean-deep">{{ __('Cardápio') }}</span>
                            <span class="text-xs text-muted-foreground">{{ __('Incluso') }}</span>
                        </div>

                        <label
                            v-for="module in toggleableModules"
                            :key="module.code"
                            class="flex items-center justify-between rounded-md border border-border px-3 py-2.5 cursor-pointer hover:bg-muted/50 transition-colors"
                        >
                            <span class="flex items-center gap-3">
                                <Checkbox
                                    :checked="isSelected(module.code)"
                                    @update:checked="toggleModule(module.code)"
                                />
                                <span class="text-sm text-ocean-deep">{{ module.name }}</span>
                            </span>
                            <span class="text-xs text-muted-foreground whitespace-nowrap">
                                {{ formatMoney(module.base_monthly_price) }} / venue
                            </span>
                        </label>
                    </div>
                    <InputError class="mt-1.5" :message="form.errors.module_codes" />
                </div>

                <div>
                    <InputLabel for="venue_count" :value="__('Quantas venues você vai operar?')" />
                    <input
                        id="venue_count"
                        v-model.number="form.venue_count"
                        type="number"
                        min="1"
                        max="20"
                        class="mt-1 block w-full rounded-md border-border focus:border-primary focus:ring-primary"
                    />
                    <InputError class="mt-1.5" :message="form.errors.venue_count" />
                </div>

                <div class="rounded-md bg-muted/50 border border-border px-4 py-3 flex items-center justify-between">
                    <span class="text-sm text-muted-foreground">{{ __('Total estimado por mês') }}</span>
                    <span class="text-lg font-semibold text-ocean-deep">{{ formattedTotal }}</span>
                </div>

                <div>
                    <label class="flex items-start gap-3 cursor-pointer">
                        <Checkbox v-model:checked="form.terms" required class="mt-0.5" />
                        <span class="text-sm text-muted-foreground leading-relaxed">
                            {{ __('Concordo com os') }}
                            <a target="_blank" :href="route('terms.show')" class="underline text-ocean-deep hover:text-primary transition-colors">{{ __('Termos de Serviço') }}</a>
                            {{ __('e') }}
                            <a target="_blank" :href="route('policy.show')" class="underline text-ocean-deep hover:text-primary transition-colors">{{ __('Política de Privacidade') }}</a>
                        </span>
                    </label>
                    <InputError class="mt-1.5" :message="form.errors.terms" />
                </div>

                <PrimaryButton
                    class="w-full justify-center"
                    :class="{ 'opacity-50': form.processing }"
                    :disabled="form.processing"
                >
                    {{ __('Continuar') }}
                </PrimaryButton>
            </form>
        </div>
    </GuestLayout>
</template>
