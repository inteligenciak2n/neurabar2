<script setup>
import Modal from '@/Components/Modal.vue';
import AppButton from '@/Components/AppButton.vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: 'Confirm action',
    },
    message: {
        type: String,
        default: 'Are you sure you want to proceed?',
    },
    confirmLabel: {
        type: String,
        default: 'Confirm',
    },
    variant: {
        type: String,
        default: 'primary',
        validator: (v) => ['primary', 'destructive'].includes(v),
    },
    loading: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['confirm', 'cancel']);
</script>

<template>
    <Modal :show="show" max-width="md" @close="emit('cancel')">
        <div class="p-6">
            <h2 class="font-heading text-lg font-semibold text-ocean-deep dark:text-gray-100">{{ title }}</h2>
            <p class="mt-2 text-sm text-muted-foreground font-body dark:text-gray-400">{{ message }}</p>

            <div class="mt-6 flex items-center justify-end gap-3">
                <AppButton variant="ghost" :disabled="loading" @click="emit('cancel')">
                    Cancel
                </AppButton>
                <AppButton :variant="variant" :loading="loading" @click="emit('confirm')">
                    {{ confirmLabel }}
                </AppButton>
            </div>
        </div>
    </Modal>
</template>
