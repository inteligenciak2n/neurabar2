<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
defineProps({
    Title: {
        type: String
    },
    title: {
        type: String
    }
});
</script>
<template>
    <Head>
        <title>{{Title ?? title}}</title>

        <meta head-key="description" name="description" content="">
        <meta name="keywords" content="">
        <meta name="author" content="NeuraBar">
        <meta name="robots" content="index, follow">
        <meta name="canonical" href="https://www.neurabar.com">
        <meta property="og:title" :content="Title">
        <meta property="og:description" content="">
        <meta property="og:image" content="">

        <link rel="shortcut icon"  :href="$page.props.assets + '/images/favicon.png'" />
    </Head>    
</template>
