<script setup>
import { useDark } from '@vueuse/core';

const isDark = useDark()

const toggleDark = () => {
    isDark.value = !isDark.value
}

</script>
<template>
    <button class="h-6 my-auto px-2 border rounded-full
    bg-gray-200 hover:bg-primary-hover text-gray-700 hover:text-gray-200 text-sm
    dark:bg-primary dark:text-gray-800 dark:border-gray-900 dark:hover:bg-gray-200 dark:hover:text-gray-700
    "
    @click="toggleDark()"
    type="button"
    v-tippy="isDark ? 'Modo claro' : 'Modo escuro'"
    >
        <svg v-if="!isDark" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>
        <svg v-else xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><path d="M12 1v2M12 21v2M4.2 4.2l1.4 1.4M18.4 18.4l1.4 1.4M1 12h2M21 12h2M4.2 19.8l1.4-1.4M18.4 5.6l1.4-1.4"/></svg>
    </button>
</template>