<script setup>
import AppButton from '@/Components/AppButton.vue';
import InputError from '@/Components/InputError.vue';
import PlatformLayout from '@/Layouts/PlatformLayout.vue';
import { Link, router, useForm } from '@inertiajs/vue3';

const props = defineProps({
    corporation: Object,
    venue: Object,
    assignments: Array,
    modules: Array,
});

const emptyTier = (minimum = 0) => ({
    min_quantity: minimum,
    max_quantity: '',
    included_quantity: 0,
    price_per_unit: 0,
    flat_price: '',
    overage_price_per_unit: 0,
    overage_flat_fee: '',
});

const form = useForm({
    venue_plan_assignment_id: props.assignments[0]?.id ?? '',
    module_code: props.modules[0]?.code ?? '',
    tiers: [emptyTier()],
});

const addTier = () => {
    const previousMaximum = form.tiers.at(-1)?.max_quantity;
    form.tiers.push(emptyTier(previousMaximum === '' ? 0 : Number(previousMaximum) + 1));
};
const removeTier = (index) => form.tiers.splice(index, 1);
const submit = () => form.post(route('platform.corporations.venues.usage-pricing.store', [props.corporation.id, props.venue.id]), { preserveScroll: true });
const removeOverride = (assignment, moduleCode) => {
    if (confirm(__('Remove this override and restore plan defaults?'))) {
        router.delete(route('platform.corporations.venues.usage-pricing.destroy', [props.corporation.id, props.venue.id, assignment.id, moduleCode]), { preserveScroll: true });
    }
};
const moduleName = (code) => props.modules.find((module) => module.code === code)?.name ?? code;
</script>

<template>
    <PlatformLayout :title="__('Venue usage overrides')">
        <template #header>
            <div class="flex min-w-0 items-center gap-3 text-sm">
                <Link :href="route('platform.corporations.edit', corporation.id)" class="text-muted-foreground hover:text-primary">{{ corporation.name }}</Link>
                <span class="text-muted-foreground">/</span>
                <h1 class="truncate font-heading text-xl font-bold text-ocean-deep dark:text-gray-100">{{ venue.name }}</h1>
            </div>
        </template>

        <div class="mx-auto flex max-w-7xl flex-col gap-7">
            <section class="border-b border-border pb-6 dark:border-gray-700">
                <div class="pb-5">
                    <h2 class="font-heading text-lg font-semibold text-ocean-deep dark:text-gray-100">{{ __('New usage override') }}</h2>
                    <p class="text-sm text-muted-foreground">{{ __('Saving replaces all override tiers for the selected assignment and module.') }}</p>
                </div>

                <form class="flex flex-col gap-5" @submit.prevent="submit">
                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="flex flex-col gap-1 text-sm font-medium">
                            {{ __('Plan assignment') }}
                            <select v-model="form.venue_plan_assignment_id" required class="rounded-md border-border dark:border-gray-600 dark:bg-gray-800">
                                <option v-for="assignment in assignments" :key="assignment.id" :value="assignment.id">
                                    {{ assignment.plan_catalog.name }} · v{{ assignment.plan_catalog_version.version }} · {{ assignment.starts_on }}
                                </option>
                            </select>
                        </label>
                        <label class="flex flex-col gap-1 text-sm font-medium">
                            {{ __('Module') }}
                            <select v-model="form.module_code" required class="rounded-md border-border dark:border-gray-600 dark:bg-gray-800">
                                <option v-for="module in modules" :key="module.code" :value="module.code">{{ module.name }}</option>
                            </select>
                        </label>
                    </div>

                    <div class="overflow-x-auto rounded-md border border-border dark:border-gray-700">
                        <table class="min-w-[980px] w-full text-sm">
                            <thead class="bg-muted/50 text-left text-muted-foreground dark:bg-gray-800">
                                <tr>
                                    <th v-for="label in ['Min', 'Max', 'Included', 'Base/unit', 'Flat base', 'Overage/unit', 'Flat overage', '']" :key="label" class="px-3 py-2 font-medium">{{ __(label) }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(tier, index) in form.tiers" :key="index" class="border-t border-border dark:border-gray-700">
                                    <td class="p-2"><input v-model="tier.min_quantity" type="number" min="0" class="w-24 rounded-md border-border text-sm dark:border-gray-600 dark:bg-gray-800" /></td>
                                    <td class="p-2"><input v-model="tier.max_quantity" type="number" min="0" placeholder="∞" class="w-24 rounded-md border-border text-sm dark:border-gray-600 dark:bg-gray-800" /></td>
                                    <td class="p-2"><input v-model="tier.included_quantity" type="number" min="0" class="w-24 rounded-md border-border text-sm dark:border-gray-600 dark:bg-gray-800" /></td>
                                    <td class="p-2"><input v-model="tier.price_per_unit" type="number" min="0" step="0.0001" class="w-28 rounded-md border-border text-sm dark:border-gray-600 dark:bg-gray-800" /></td>
                                    <td class="p-2"><input v-model="tier.flat_price" type="number" min="0" step="0.01" class="w-28 rounded-md border-border text-sm dark:border-gray-600 dark:bg-gray-800" /></td>
                                    <td class="p-2"><input v-model="tier.overage_price_per_unit" type="number" min="0" step="0.0001" class="w-28 rounded-md border-border text-sm dark:border-gray-600 dark:bg-gray-800" /></td>
                                    <td class="p-2"><input v-model="tier.overage_flat_fee" type="number" min="0" step="0.01" class="w-28 rounded-md border-border text-sm dark:border-gray-600 dark:bg-gray-800" /></td>
                                    <td class="p-2 text-right"><button type="button" class="text-destructive hover:underline disabled:opacity-40" :disabled="form.tiers.length === 1" @click="removeTier(index)">{{ __('Remove') }}</button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <InputError :message="form.errors.tiers" />
                    <div class="flex justify-between gap-3">
                        <AppButton type="button" variant="secondary" @click="addTier">{{ __('Add tier') }}</AppButton>
                        <AppButton type="submit" :loading="form.processing" :disabled="!assignments.length || !modules.length">{{ __('Save override') }}</AppButton>
                    </div>
                </form>
            </section>

            <section v-for="assignment in assignments" :key="assignment.id" class="flex flex-col gap-4 border-b border-border pb-6 last:border-0 dark:border-gray-700">
                <div>
                    <h2 class="font-heading font-semibold text-ocean-deep dark:text-gray-100">{{ assignment.plan_catalog.name }} · v{{ assignment.plan_catalog_version.version }}</h2>
                    <p class="text-sm text-muted-foreground">{{ assignment.starts_on }} – {{ assignment.ends_on ?? '∞' }}</p>
                </div>

                <div v-if="assignment.usage_tier_overrides.length" class="flex flex-col gap-4">
                    <div v-for="moduleCode in [...new Set(assignment.usage_tier_overrides.map((tier) => tier.module_code))]" :key="moduleCode" class="overflow-x-auto rounded-md border border-border dark:border-gray-700">
                        <div class="flex items-center justify-between gap-3 bg-muted/50 px-4 py-3 dark:bg-gray-800">
                            <strong>{{ moduleName(moduleCode) }}</strong>
                            <button type="button" class="text-sm font-medium text-destructive hover:underline" @click="removeOverride(assignment, moduleCode)">{{ __('Restore plan defaults') }}</button>
                        </div>
                        <table class="min-w-[720px] w-full text-sm">
                            <thead><tr class="text-left text-muted-foreground"><th class="px-4 py-2">{{ __('Range') }}</th><th>{{ __('Included') }}</th><th>{{ __('Base/unit') }}</th><th>{{ __('Overage/unit') }}</th></tr></thead>
                            <tbody>
                                <tr v-for="tier in assignment.usage_tier_overrides.filter((item) => item.module_code === moduleCode)" :key="tier.id" class="border-t border-border dark:border-gray-700">
                                    <td class="px-4 py-2">{{ tier.min_quantity }} – {{ tier.max_quantity ?? '∞' }}</td>
                                    <td>{{ tier.included_quantity }}</td>
                                    <td>{{ (tier.price_per_unit / 10000).toLocaleString('pt-BR', { minimumFractionDigits: 4 }) }}</td>
                                    <td>{{ (tier.overage_price_per_unit / 10000).toLocaleString('pt-BR', { minimumFractionDigits: 4 }) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <p v-else class="text-sm text-muted-foreground">{{ __('This assignment uses all plan defaults.') }}</p>
            </section>
        </div>
    </PlatformLayout>
</template>