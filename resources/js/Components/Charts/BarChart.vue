<script setup>
import { computed } from 'vue';
import { Bar } from 'vue-chartjs';
import { Chart as ChartJS, CategoryScale, LinearScale, BarElement, Tooltip, Legend } from 'chart.js';

ChartJS.register(CategoryScale, LinearScale, BarElement, Tooltip, Legend);

const props = defineProps({
    labels: {
        type: Array,
        required: true,
    },
    // [{ label, data, color }]
    datasets: {
        type: Array,
        required: true,
    },
    horizontal: {
        type: Boolean,
        default: false,
    },
});

const chartData = computed(() => ({
    labels: props.labels,
    datasets: props.datasets.map((dataset) => ({
        label: dataset.label,
        data: dataset.data,
        backgroundColor: dataset.color ?? '#293b4f',
        borderRadius: 4,
    })),
}));

const chartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    indexAxis: props.horizontal ? 'y' : 'x',
    plugins: {
        legend: { display: props.datasets.length > 1 },
    },
    scales: {
        x: { grid: { display: false } },
        y: { beginAtZero: true },
    },
}));
</script>

<template>
    <div class="h-64">
        <Bar :data="chartData" :options="chartOptions" />
    </div>
</template>
