<script setup>
import { computed } from 'vue';
import { Line } from 'vue-chartjs';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Tooltip,
    Legend,
    Filler,
} from 'chart.js';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Tooltip, Legend, Filler);

const props = defineProps({
    labels: {
        type: Array,
        required: true,
    },
    values: {
        type: Array,
        required: true,
    },
    label: {
        type: String,
        default: '',
    },
    color: {
        type: String,
        default: '#293b4f',
    },
});

const chartData = computed(() => ({
    labels: props.labels,
    datasets: [
        {
            label: props.label,
            data: props.values,
            borderColor: props.color,
            backgroundColor: `${props.color}20`,
            fill: true,
            tension: 0.3,
            pointRadius: 2,
        },
    ],
}));

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
    },
    scales: {
        x: { grid: { display: false } },
        y: { beginAtZero: true },
    },
};
</script>

<template>
    <div class="h-64">
        <Line :data="chartData" :options="chartOptions" />
    </div>
</template>
