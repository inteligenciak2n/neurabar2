<script setup>
import { computed } from 'vue';
import { Doughnut } from 'vue-chartjs';
import { Chart as ChartJS, ArcElement, Tooltip, Legend } from 'chart.js';

ChartJS.register(ArcElement, Tooltip, Legend);

const props = defineProps({
    labels: {
        type: Array,
        required: true,
    },
    values: {
        type: Array,
        required: true,
    },
    colors: {
        type: Array,
        default: () => ['#293b4f', '#a28665', '#16a34a', '#0f172a', '#c4b39b'],
    },
});

const chartData = computed(() => ({
    labels: props.labels,
    datasets: [
        {
            data: props.values,
            backgroundColor: props.colors,
            borderWidth: 0,
        },
    ],
}));

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { position: 'bottom' },
    },
};
</script>

<template>
    <div class="h-64">
        <Doughnut :data="chartData" :options="chartOptions" />
    </div>
</template>
