<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import AppCard from '@/Components/AppCard.vue';
import AppButton from '@/Components/AppButton.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    modifierGroups: {
        type: Array,
        default: () => [],
    },
});

const showCreate = ref(false);

const createForm = useForm({
    name: '',
    required: false,
    multiple_selection: false,
});

const submitCreate = () => {
    createForm.post(route('menu.modifier-groups.store'), {
        onSuccess: () => {
            createForm.reset();
            showCreate.value = false;
        },
    });
};
</script>

<template>
    <AppLayout title="Modifier Groups">
        <template #header>
            <h1 class="font-heading text-2xl font-bold text-ocean-deep">Modifier Groups</h1>
        </template>

        <div class="space-y-4">
            <div class="flex justify-end">
                <AppButton @click="showCreate = !showCreate">New Group</AppButton>
            </div>

            <AppCard v-if="showCreate" title="New Modifier Group">
                <form @submit.prevent="submitCreate" class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-ocean-deep mb-1">Name</label>
                        <input v-model="createForm.name" type="text" class="w-full rounded-md border border-border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary" />
                        <p v-if="createForm.errors.name" class="mt-1 text-xs text-destructive">{{ createForm.errors.name }}</p>
                    </div>
                    <div class="flex gap-4">
                        <label class="flex items-center gap-2 text-sm">
                            <input v-model="createForm.required" type="checkbox" class="h-4 w-4 rounded border-border text-primary" />
                            Required
                        </label>
                        <label class="flex items-center gap-2 text-sm">
                            <input v-model="createForm.multiple_selection" type="checkbox" class="h-4 w-4 rounded border-border text-primary" />
                            Multiple Selection
                        </label>
                    </div>
                    <div class="flex justify-end gap-2">
                        <AppButton variant="secondary" type="button" @click="showCreate = false">Cancel</AppButton>
                        <AppButton type="submit" :loading="createForm.processing">Create</AppButton>
                    </div>
                </form>
            </AppCard>

            <AppCard v-for="group in modifierGroups" :key="group.id" :title="group.name">
                <div class="flex items-center gap-3 mb-3 text-xs text-muted-foreground">
                    <span v-if="group.required" class="rounded-full bg-amber-100 text-amber-700 px-2 py-0.5">Required</span>
                    <span v-if="group.multiple_selection" class="rounded-full bg-blue-100 text-blue-700 px-2 py-0.5">Multi-select</span>
                </div>
                <ul class="space-y-1">
                    <li v-for="option in group.options" :key="option.id" class="flex items-center justify-between text-sm border-b border-border pb-1 last:border-0 last:pb-0">
                        <span class="text-ocean-deep">{{ option.name }}</span>
                        <span class="text-muted-foreground">
                            {{ option.extra_price > 0 ? `+R$ ${Number(option.extra_price).toFixed(2)}` : 'Free' }}
                        </span>
                    </li>
                    <li v-if="!group.options?.length" class="text-sm text-muted-foreground">No options yet.</li>
                </ul>
            </AppCard>

            <p v-if="!modifierGroups?.length" class="text-center text-muted-foreground py-8">No modifier groups yet.</p>
        </div>
    </AppLayout>
</template>
