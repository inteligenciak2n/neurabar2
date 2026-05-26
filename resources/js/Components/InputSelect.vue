<script setup>
defineProps({
    modelValue: String,
    options: {
        type: Array,
        required: true
    }
});

defineEmits(['update:modelValue']);
</script>
<template>
    <select class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" :value="modelValue" @change="$emit('update:modelValue', $event.target.value)">
        <option v-for="option in options" :key="option.value" :value="option.value">
            {{ option.label }}
        </option>
    </select>
</template>