<script setup>
import { Toaster } from 'vue-sonner';
</script>

<template>
    <Toaster
        position="bottom-right"
        :toast-options="{
            style: {
                fontFamily: 'Manrope, sans-serif',
            },
        }"
        rich-colors
    />
</template>
