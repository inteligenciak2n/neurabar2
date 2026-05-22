<script setup>
defineProps({
    label: {
        type: String,
        required: true,
    },
    color: {
        type: String,
        default: null,
    },
    variant: {
        type: String,
        default: null,
        validator: (v) => ['primary', 'accent', 'destructive', 'muted', null].includes(v),
    },
});

const variantClasses = {
    primary:     'bg-ocean-light text-primary',
    accent:      'bg-accent/10 text-accent',
    destructive: 'bg-destructive/10 text-destructive',
    muted:       'bg-muted text-muted-foreground',
};
</script>

<template>
    <span
        :class="[
            'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium font-body',
            variant ? variantClasses[variant] : 'bg-muted text-muted-foreground',
        ]"
        :style="color ? { backgroundColor: color + '20', color: color } : {}"
    >
        {{ label }}
    </span>
</template>
