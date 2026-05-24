<script setup>
import { computed, onMounted, ref } from 'vue';

const props = defineProps({
    openItems: Array,
});

const lastUpdated = ref(new Date().toLocaleTimeString());

function getStatusBadgeStyle(status) {
    if (!status?.color) return {};
    return { backgroundColor: status.color + '20', color: status.color };
}

// Refresh via polling every 30s
onMounted(() => {
    setInterval(() => {
        window.location.reload();
    }, 30000);
});
</script>

<template>
    <div class="min-h-screen bg-gray-950 text-white p-6">
        <div class="mb-8 flex items-center justify-between">
            <h1 class="font-heading text-2xl font-bold text-white">Kitchen Monitor</h1>
            <span class="text-sm text-gray-400">Updated: {{ lastUpdated }}</span>
        </div>

        <div v-if="openItems.length === 0" class="flex items-center justify-center h-64">
            <p class="text-xl text-gray-500 font-heading">No items in preparation</p>
        </div>

        <div v-else class="grid gap-4 grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
            <div
                v-for="item in openItems"
                :key="item.id"
                class="rounded-xl bg-gray-800 p-4 flex flex-col gap-3"
            >
                <div class="flex items-center justify-between">
                    <span class="font-heading font-bold text-white text-lg">
                        {{ item.quantity }}×
                    </span>
                    <span
                        v-if="item.preparation_status"
                        class="rounded-full px-2.5 py-0.5 text-xs font-medium"
                        :style="getStatusBadgeStyle(item.preparation_status)"
                    >
                        {{ item.preparation_status.name }}
                    </span>
                </div>
                <p class="font-body font-medium text-white">
                    {{ item.product?.name ?? 'Item' }}
                </p>
                <p v-if="item.order?.attendance?.customer_identifier" class="text-sm text-gray-400">
                    Table: {{ item.order.attendance.customer_identifier }}
                </p>
            </div>
        </div>
    </div>
</template>
