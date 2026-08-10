<script setup>
import AppButton from '@/Components/AppButton.vue';
import PlatformLayout from '@/Layouts/PlatformLayout.vue';
import { useCurrency } from '@/Composables/useCurrency';
import { Link, router, useForm } from '@inertiajs/vue3';

defineProps({
    requests: Object,
    filters: Object,
});

const { formatMoney } = useCurrency();
const reviewForm = useForm({ review_notes: '' });

const filterByStatus = (status) => router.get(route('platform.plan-change-requests.index'), { status }, { preserveState: true, replace: true });
const approve = (changeRequest) => reviewForm.post(route('platform.plan-change-requests.approve', changeRequest.id), { preserveScroll: true });
const reject = (changeRequest) => {
    const notes = prompt(__('Reason for rejection'));

    if (notes) {
        reviewForm.review_notes = notes;
        reviewForm.post(route('platform.plan-change-requests.reject', changeRequest.id), {
            preserveScroll: true,
            onFinish: () => reviewForm.reset(),
        });
    }
};
</script>

<template>
    <PlatformLayout :title="__('Plan changes')">
        <template #header>
            <h1 class="font-heading text-xl font-bold text-ocean-deep dark:text-gray-100">{{ __('Plan changes') }}</h1>
        </template>

        <div class="mx-auto flex max-w-7xl flex-col gap-5">
            <div class="flex flex-wrap gap-2 border-b border-border pb-4 dark:border-gray-700">
                <button
                    v-for="status in ['pending', 'approved', 'rejected', 'canceled']"
                    :key="status"
                    type="button"
                    class="rounded-md px-3 py-2 text-sm font-medium transition-colors"
                    :class="filters.status === status ? 'bg-primary text-white' : 'bg-muted text-muted-foreground hover:text-ocean-deep dark:bg-gray-800 dark:hover:text-gray-100'"
                    @click="filterByStatus(status)"
                >
                    {{ __(status) }}
                </button>
            </div>

            <div v-if="requests.data.length" class="overflow-x-auto rounded-md border border-border bg-white dark:border-gray-700 dark:bg-gray-800">
                <table class="min-w-[980px] w-full text-sm">
                    <thead class="bg-muted/50 text-left text-muted-foreground dark:bg-gray-700/50">
                        <tr>
                            <th class="px-4 py-3 font-medium">{{ __('Corporation / venue') }}</th>
                            <th class="px-4 py-3 font-medium">{{ __('Requested plan') }}</th>
                            <th class="px-4 py-3 font-medium">{{ __('Commitment') }}</th>
                            <th class="px-4 py-3 font-medium">{{ __('Effective on') }}</th>
                            <th class="px-4 py-3 font-medium">{{ __('Requested by') }}</th>
                            <th class="px-4 py-3 font-medium">{{ __('Decision') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="changeRequest in requests.data" :key="changeRequest.id" class="border-t border-border dark:border-gray-700">
                            <td class="px-4 py-3">
                                <strong class="block text-ocean-deep dark:text-gray-100">{{ changeRequest.venue.corporation.name }}</strong>
                                <span class="text-muted-foreground">{{ changeRequest.venue.name }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <strong>{{ changeRequest.requested_plan_catalog.name }}</strong>
                                <span class="block text-xs text-muted-foreground">v{{ changeRequest.requested_plan_catalog_version.version }} · {{ changeRequest.requested_plan_catalog_version.infrastructure_type === 'dedicated' ? __('Dedicated recommended') : __('Shared') }}</span>
                            </td>
                            <td class="px-4 py-3">{{ formatMoney(changeRequest.requested_plan_catalog_version.minimum_monthly_price) }}</td>
                            <td class="px-4 py-3">{{ changeRequest.effective_on }}</td>
                            <td class="px-4 py-3">
                                <span class="block">{{ changeRequest.requester.name }}</span>
                                <span class="text-xs text-muted-foreground">{{ changeRequest.requester.email }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <div v-if="changeRequest.status === 'pending'" class="flex gap-2">
                                    <AppButton size="sm" :loading="reviewForm.processing" @click="approve(changeRequest)">{{ __('Approve') }}</AppButton>
                                    <AppButton size="sm" variant="destructive" :disabled="reviewForm.processing" @click="reject(changeRequest)">{{ __('Reject') }}</AppButton>
                                </div>
                                <div v-else>
                                    <span class="font-medium">{{ __(changeRequest.status) }}</span>
                                    <span v-if="changeRequest.reviewer" class="block text-xs text-muted-foreground">{{ changeRequest.reviewer.name }}</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-else class="py-14 text-center text-sm text-muted-foreground">{{ __('No plan change requests found.') }}</div>

            <div v-if="requests.last_page > 1" class="flex flex-wrap gap-2">
                <Link v-for="link in requests.links" :key="link.label" :href="link.url ?? '#'" class="rounded-md px-3 py-1.5 text-sm" :class="[link.active ? 'bg-primary text-white' : 'bg-muted text-muted-foreground', !link.url ? 'pointer-events-none opacity-50' : '']" v-html="link.label" />
            </div>
        </div>
    </PlatformLayout>
</template>