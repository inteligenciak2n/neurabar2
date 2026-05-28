<script setup>
import { Link } from '@inertiajs/vue3';
import ApplicationLogo from './ApplicationLogo.vue';
</script>

<template>
    <Link :href="'/'">
        <ApplicationLogo class="w-20 h-20 fill-current text-gray-500" />
    </Link>
</template>
