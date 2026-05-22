<script setup>
import { Toaster } from 'vue-sonner';
</script>

<template>
    <Toaster
        position="bottom-right"
        :toast-options="{
            style: {
                fontFamily: 'DM Sans, sans-serif',
            },
        }"
        rich-colors
    />
</template>
