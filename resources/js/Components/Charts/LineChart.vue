<script setup>
import { computed } from 'vue'
import { Line } from 'vue-chartjs'
import {
    Chart as ChartJS,
    Title,
    Tooltip,
    Legend,
    LineElement,
    PointElement,
    CategoryScale,
    LinearScale,
    Filler
} from 'chart.js'

ChartJS.register(Title, Tooltip, Legend, LineElement, PointElement, CategoryScale, LinearScale, Filler)

const props = defineProps({
    labels: {
        type: Array,
        required: true,
    },
    datasets: {
        type: Array,
        required: true,
    },
    maxVal: {
        type: Number,
        default: 20,
    },
    unit: {
        type: String,
        default: ' pts',
    }
})

const chartData = computed(() => ({
    labels: props.labels,
    datasets: props.datasets.map(ds => ({
        tension: 0.35,
        pointRadius: 5,
        pointHoverRadius: 7,
        borderWidth: 3,
        fill: true,
        ...ds,
    })),
}))

const chartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: props.datasets.length > 1,
            position: 'top',
            labels: {
                boxWidth: 12,
                font: { family: "'Inter', sans-serif", weight: 600, size: 11 },
                color: '#64748b',
            },
        },
        tooltip: {
            padding: 12,
            cornerRadius: 12,
            titleFont: { family: "'Inter', sans-serif", weight: 700 },
            bodyFont: { family: "'Inter', sans-serif" },
            callbacks: {
                label: function (context) {
                    let label = context.dataset.label || ''
                    if (label) label += ': '
                    label += context.raw + props.unit
                    return label
                }
            }
        },
    },
    scales: {
        x: {
            grid: {
                display: false,
            },
            ticks: {
                font: { family: "'Inter', sans-serif", size: 10 },
                color: '#64748b',
            },
        },
        y: {
            grid: {
                color: 'rgba(226, 232, 240, 0.6)',
            },
            ticks: {
                font: { family: "'Inter', sans-serif", size: 10 },
                color: '#64748b',
            },
            max: props.maxVal,
            beginAtZero: true,
        },
    },
}))
</script>

<template>
    <div class="relative w-full h-full min-h-[260px]">
        <Line :data="chartData" :options="chartOptions" />
    </div>
</template>
