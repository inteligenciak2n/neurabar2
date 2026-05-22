<script setup>
defineProps({
    columns: {
        type: Array,
        required: true,
    },
    rows: {
        type: Array,
        required: true,
    },
    loading: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <div class="overflow-hidden rounded-lg border border-muted shadow-card">
        <div v-if="loading" class="divide-y divide-muted">
            <div v-for="i in 5" :key="i" class="flex gap-4 px-6 py-4">
                <div v-for="col in columns" :key="col.key" class="h-4 flex-1 animate-pulse rounded bg-muted" />
            </div>
        </div>

        <table v-else class="min-w-full divide-y divide-muted">
            <thead class="bg-muted/50">
                <tr>
                    <th
                        v-for="col in columns"
                        :key="col.key"
                        :class="['px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground font-body', col.class]"
                    >
                        {{ col.label }}
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-muted bg-white">
                <tr v-for="(row, i) in rows" :key="i" class="hover:bg-muted/30 transition-colors">
                    <td
                        v-for="col in columns"
                        :key="col.key"
                        :class="['px-6 py-4 text-sm text-ocean-deep font-body', col.cellClass]"
                    >
                        <slot :name="`cell-${col.key}`" :row="row" :value="row[col.key]">
                            {{ row[col.key] }}
                        </slot>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
