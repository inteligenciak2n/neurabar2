<script setup>
import { Link } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';

const props = defineProps({
    label: { type: String, required: true },
    description: { type: String, required: true },
    routeName: { type: String, required: true },
    icon: { type: String, required: true },
});

const titleRef = ref(null);
const titleWrapRef = ref(null);
const isMultiLine = ref(false);

const titleClass = computed(() => {
    const base = 'line-clamp-4 min-w-0 whitespace-pre-line font-heading font-bold text-ocean-deep transition-colors group-hover:text-primary';

    return isMultiLine.value
        ? `${base} text-lg leading-snug sm:text-xl`
        : `${base} text-2xl leading-tight sm:text-3xl`;
});

const measureTitle = async () => {
    await nextTick();

    const el = titleRef.value;

    if (! el) {
        return;
    }

    isMultiLine.value = false;
    await nextTick();

    const style = getComputedStyle(el);
    let lineHeight = parseFloat(style.lineHeight);

    if (Number.isNaN(lineHeight)) {
        lineHeight = parseFloat(style.fontSize) * 1.25;
    }

    isMultiLine.value = el.scrollHeight > lineHeight * 1.35;
};

let resizeObserver;

onMounted(() => {
    measureTitle();

    if (titleWrapRef.value) {
        resizeObserver = new ResizeObserver(() => measureTitle());
        resizeObserver.observe(titleWrapRef.value);
    }
});

onUnmounted(() => {
    resizeObserver?.disconnect();
});

watch(() => props.label, measureTitle);

const parseDescriptionLine = (line) => {
    const separator = line.includes(' — ') ? ' — ' : ' - ';
    const [label, ...rest] = line.split(separator);

    if (rest.length === 0) {
        return { label: null, text: line };
    }

    return { label: label.trim(), text: rest.join(separator).trim() };
};

const descriptionTopics = computed(() => {
    const rawLines = props.description.split('\n').filter((line) => line.trim());

    if (rawLines.length <= 1) {
        return null;
    }

    const topics = [];

    rawLines.forEach((rawLine) => {
        const isSubTopic = /^\s+/.test(rawLine);
        const parsed = parseDescriptionLine(rawLine.trim());

        if (isSubTopic && topics.length > 0) {
            const parent = topics[topics.length - 1];

            if (! parent.subtopics) {
                parent.subtopics = [];
            }

            parent.subtopics.push(parsed);

            return;
        }

        topics.push({ ...parsed, subtopics: [] });
    });

    return topics;
});
</script>

<template>
    <Link
        :href="route(routeName)"
        class="group flex min-h-[7.5rem] overflow-hidden rounded-lg border border-border bg-white shadow-card transition-shadow hover:shadow-ocean dark:border-gray-700 dark:bg-gray-800"
    >
        <div
            ref="titleWrapRef"
            class="flex w-48 shrink-0 items-center gap-3 bg-gradient-to-br from-sand to-warm-gold px-4 sm:w-64"
        >
            <svg
                class="h-5 w-5 shrink-0 text-ocean-deep"
                fill="none"
                stroke="currentColor"
                stroke-width="1.5"
                viewBox="0 0 24 24"
            >
                <path stroke-linecap="round" stroke-linejoin="round" :d="icon" />
            </svg>
            <p ref="titleRef" :class="titleClass">
                {{ label }}
            </p>
        </div>
        <div class="flex min-w-0 flex-1 items-center px-4 py-3">
            <ul
                v-if="descriptionTopics"
                class="list-disc space-y-1.5 pl-4 text-sm leading-relaxed text-muted-foreground dark:text-gray-400"
            >
                <li v-for="(topic, index) in descriptionTopics" :key="index">
                    <template v-if="topic.label">
                        <span class="font-bold text-ocean-deep dark:text-gray-200">{{ topic.label }}</span>
                        <span> — {{ topic.text }}</span>
                    </template>
                    <template v-else>
                        {{ topic.text }}
                    </template>
                    <ul
                        v-if="topic.subtopics?.length"
                        class="mt-1 list-[circle] space-y-1 pl-5"
                    >
                        <li v-for="(subtopic, subIndex) in topic.subtopics" :key="subIndex">
                            <template v-if="subtopic.label">
                                <span class="font-bold text-ocean-deep dark:text-gray-200">{{ subtopic.label }}</span>
                                <span> — {{ subtopic.text }}</span>
                            </template>
                            <template v-else>
                                {{ subtopic.text }}
                            </template>
                        </li>
                    </ul>
                </li>
            </ul>
            <p v-else class="text-sm leading-relaxed text-muted-foreground dark:text-gray-400">
                {{ description }}
            </p>
        </div>
    </Link>
</template>
