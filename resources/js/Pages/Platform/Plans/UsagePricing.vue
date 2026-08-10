<script setup>
import AppButton from '@/Components/AppButton.vue';
import InputError from '@/Components/InputError.vue';
import PlatformLayout from '@/Layouts/PlatformLayout.vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import { useCurrency } from '@/Composables/useCurrency';
import { useTranslate } from '@/Composables/useTranslate';
import { ref } from 'vue';

const props = defineProps({
    plan: Object,
    versions: Array,
    modules: Array,
});

const __ = useTranslate();
const { formatMoney } = useCurrency();
const showCreate = ref(false);

const emptyTier = () => ({
    module_code: props.modules[0]?.code ?? '',
    min_quantity: 0,
    max_quantity: '',
    included_quantity: 0,
    price_per_unit: 0,
    flat_price: '',
    overage_price_per_unit: 0,
    overage_flat_fee: '',
});

const nextMonth = () => {
    const date = new Date();
    date.setMonth(date.getMonth() + 1, 1);
    return date.toISOString().slice(0, 10);
};

const form = useForm({
    effective_from: nextMonth(),
    minimum_monthly_price: '',
    infrastructure_type: 'shared',
    currency: 'BRL',
    tiers: [emptyTier()],
});

const addTier = () => form.tiers.push(emptyTier());
const removeTier = (index) => form.tiers.splice(index, 1);

const submit = () => {
    form.post(route('platform.plans.usage-pricing.store', props.plan.id), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            form.effective_from = nextMonth();
            form.currency = 'BRL';
            form.infrastructure_type = 'shared';
            form.tiers = [emptyTier()];
            showCreate.value = false;
        },
    });
};

const publishVersion = (version) => router.post(route('platform.plans.usage-pricing.publish', [props.plan.id, version.id]));
const deleteVersion = (version) => {
    if (confirm(__('Delete this draft?'))) {
        router.delete(route('platform.plans.usage-pricing.destroy', [props.plan.id, version.id]));
    }
};
</script>

<template>
    <PlatformLayout :title="__('Usage tiers')">
        <template #header>
            <div class="flex min-w-0 items-center gap-3">
                <Link :href="route('platform.plans.index')" class="text-sm text-muted-foreground hover:text-primary">{{ __('Plans') }}</Link>
                <span class="text-muted-foreground">/</span>
                <h1 class="truncate font-heading text-xl font-bold text-ocean-deep dark:text-gray-100">{{ plan.name }}</h1>
            </div>
        </template>

        <div class="mx-auto flex max-w-7xl flex-col gap-6">
            <section class="flex flex-col justify-between gap-3 border-b border-border pb-5 dark:border-gray-700 sm:flex-row sm:items-end">
                <div>
                    <p class="text-sm text-muted-foreground dark:text-gray-400">{{ __('Pricing commitment and graduated usage limits') }}</p>
                    <h2 class="font-heading text-lg font-semibold text-ocean-deep dark:text-gray-100">{{ __('Published versions') }}</h2>
                </div>
                <AppButton @click="showCreate = !showCreate">{{ showCreate ? __('Cancel') : __('New version') }}</AppButton>
            </section>

            <form v-if="showCreate" class="flex flex-col gap-5 border-b border-border pb-6 dark:border-gray-700" @submit.prevent="submit">
                <div class="grid gap-4 md:grid-cols-4">
                    <label class="flex flex-col gap-1 text-sm font-medium">
                        {{ __('Effective from') }}
                        <input v-model="form.effective_from" type="date" class="rounded-md border-border dark:border-gray-600 dark:bg-gray-800" />
                        <InputError :message="form.errors.effective_from" />
                    </label>
                    <label class="flex flex-col gap-1 text-sm font-medium">
                        {{ __('Minimum monthly price') }}
                        <input v-model="form.minimum_monthly_price" type="number" min="0" step="0.01" class="rounded-md border-border dark:border-gray-600 dark:bg-gray-800" />
                        <InputError :message="form.errors.minimum_monthly_price" />
                    </label>
                    <label class="flex flex-col gap-1 text-sm font-medium">
                        {{ __('Infrastructure') }}
                        <select v-model="form.infrastructure_type" class="rounded-md border-border dark:border-gray-600 dark:bg-gray-800">
                            <option value="shared">{{ __('Shared') }}</option>
                            <option value="dedicated">{{ __('Dedicated recommended') }}</option>
                        </select>
                    </label>
                    <label class="flex flex-col gap-1 text-sm font-medium">
                        {{ __('Currency') }}
                        <input v-model="form.currency" maxlength="3" class="rounded-md border-border uppercase dark:border-gray-600 dark:bg-gray-800" />
                    </label>
                </div>

                <div class="overflow-x-auto rounded-lg border border-border dark:border-gray-700">
                    <table class="min-w-[1100px] w-full text-sm">
                        <thead class="bg-muted/50 dark:bg-gray-800">
                            <tr>
                                <th v-for="label in ['Module', 'Min', 'Max', 'Included', 'Base/unit', 'Flat base', 'Overage/unit', 'Flat overage', '']" :key="label" class="px-3 py-2 text-left font-medium text-muted-foreground">{{ __(label) }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(tier, index) in form.tiers" :key="index" class="border-t border-border dark:border-gray-700">
                                <td class="p-2"><select v-model="tier.module_code" class="w-40 rounded-md border-border text-sm dark:border-gray-600 dark:bg-gray-800"><option v-for="module in modules" :key="module.code" :value="module.code">{{ module.name }}</option></select></td>
                                <td class="p-2"><input v-model="tier.min_quantity" type="number" min="0" class="w-24 rounded-md border-border text-sm dark:border-gray-600 dark:bg-gray-800" /></td>
                                <td class="p-2"><input v-model="tier.max_quantity" type="number" min="0" placeholder="∞" class="w-24 rounded-md border-border text-sm dark:border-gray-600 dark:bg-gray-800" /></td>
                                <td class="p-2"><input v-model="tier.included_quantity" type="number" min="0" class="w-24 rounded-md border-border text-sm dark:border-gray-600 dark:bg-gray-800" /></td>
                                <td class="p-2"><input v-model="tier.price_per_unit" type="number" min="0" step="0.0001" class="w-28 rounded-md border-border text-sm dark:border-gray-600 dark:bg-gray-800" /></td>
                                <td class="p-2"><input v-model="tier.flat_price" type="number" min="0" step="0.01" class="w-28 rounded-md border-border text-sm dark:border-gray-600 dark:bg-gray-800" /></td>
                                <td class="p-2"><input v-model="tier.overage_price_per_unit" type="number" min="0" step="0.0001" class="w-28 rounded-md border-border text-sm dark:border-gray-600 dark:bg-gray-800" /></td>
                                <td class="p-2"><input v-model="tier.overage_flat_fee" type="number" min="0" step="0.01" class="w-28 rounded-md border-border text-sm dark:border-gray-600 dark:bg-gray-800" /></td>
                                <td class="p-2 text-right"><button type="button" class="text-destructive hover:underline" :disabled="form.tiers.length === 1" @click="removeTier(index)">{{ __('Remove') }}</button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <InputError :message="form.errors.tiers" />
                <div class="flex justify-between gap-3">
                    <AppButton type="button" variant="secondary" @click="addTier">{{ __('Add tier') }}</AppButton>
                    <AppButton type="submit" :loading="form.processing">{{ __('Create draft') }}</AppButton>
                </div>
            </form>

            <div v-if="versions.length === 0" class="py-12 text-center text-sm text-muted-foreground">{{ __('No pricing versions configured.') }}</div>

            <section v-for="version in versions" :key="version.id" class="flex flex-col gap-4 border-b border-border pb-6 last:border-0 dark:border-gray-700">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <h3 class="font-heading font-semibold">v{{ version.version }}</h3>
                        <span class="rounded-full px-2 py-1 text-xs font-medium" :class="version.status === 'published' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300'">{{ __(version.status) }}</span>
                        <span class="text-sm text-muted-foreground">{{ version.effective_from }} · {{ formatMoney(version.minimum_monthly_price) }}</span>
                    </div>
                    <div v-if="version.status === 'draft'" class="flex gap-2">
                        <AppButton size="sm" variant="secondary" @click="deleteVersion(version)">{{ __('Delete') }}</AppButton>
                        <AppButton size="sm" @click="publishVersion(version)">{{ __('Publish') }}</AppButton>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-[850px] w-full text-sm">
                        <thead><tr class="text-left text-muted-foreground"><th class="py-2">{{ __('Module') }}</th><th>{{ __('Range') }}</th><th>{{ __('Included') }}</th><th>{{ __('Base/unit') }}</th><th>{{ __('Overage/unit') }}</th></tr></thead>
                        <tbody>
                            <tr v-for="tier in version.usage_tiers" :key="tier.id" class="border-t border-border dark:border-gray-700">
                                <td class="py-2 font-medium">{{ modules.find((module) => module.code === tier.module_code)?.name ?? tier.module_code }}</td>
                                <td>{{ tier.min_quantity }} – {{ tier.max_quantity ?? '∞' }}</td>
                                <td>{{ tier.included_quantity }}</td>
                                <td>{{ (tier.price_per_unit / 10000).toLocaleString('pt-BR', { minimumFractionDigits: 4 }) }}</td>
                                <td>{{ (tier.overage_price_per_unit / 10000).toLocaleString('pt-BR', { minimumFractionDigits: 4 }) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </PlatformLayout>
</template>