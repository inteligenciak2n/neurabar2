<script setup>
import { Link } from '@inertiajs/vue3';
import ApplicationLogo from './ApplicationLogo.vue';
</script>

<template>
    <Link :href="'/'">
        <ApplicationLogo class="h-32 fill-current text-gray-500" />
    </Link>
</template>
