<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    href: String,
    as: String,
    description: String,
});

const rowClass = 'flex w-full items-stretch gap-2 px-1.5 py-0.5 text-start transition-colors hover:bg-muted focus:bg-muted focus:outline-none dark:hover:bg-gray-700 dark:focus:bg-gray-700';
const labelClass = 'flex shrink-0 items-center rounded-md bg-primary px-3 py-2.5 text-xs font-medium uppercase tracking-wide text-white';
const descriptionClass = 'flex items-center text-sm text-gray-400';
</script>

<template>
    <div>
        <button v-if="as == 'button'" type="submit" :class="rowClass">
            <span :class="labelClass"><slot /></span>
            <span v-if="description" :class="descriptionClass">{{ description }}</span>
        </button>

        <a v-else-if="as =='a'" :href="href" :class="rowClass">
            <span :class="labelClass"><slot /></span>
            <span v-if="description" :class="descriptionClass">{{ description }}</span>
        </a>

        <Link v-else :href="href" :class="rowClass">
            <span :class="labelClass"><slot /></span>
            <span v-if="description" :class="descriptionClass">{{ description }}</span>
        </Link>
    </div>
</template>
