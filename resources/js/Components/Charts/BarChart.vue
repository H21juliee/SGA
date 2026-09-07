<script setup>
import { computed } from 'vue'
import { Bar } from 'vue-chartjs'
import {
    Chart as ChartJS,
    Title,
    Tooltip,
    Legend,
    BarElement,
    CategoryScale,
    LinearScale
} from 'chart.js'

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale)

const props = defineProps({
    labels: {
        type: Array,
        required: true,
    },
    datasets: {
        type: Array,
        required: true,
    },
    horizontal: {
        type: Boolean,
        default: false,
    },
    stacked: {
        type: Boolean,
        default: false,
    },
    maxVal: {
        type: Number,
        default: null,
    },
    unit: {
        type: String,
        default: '',
    },
})

const chartData = computed(() => ({
    labels: props.labels,
    datasets: props.datasets.map(ds => ({
        borderRadius: 8,
        borderSkipped: false,
        maxBarThickness: 32,
        ...ds,
    })),
}))

const chartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    indexAxis: props.horizontal ? 'y' : 'x',
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
            stacked: props.stacked,
            grid: {
                display: props.horizontal,
                color: 'rgba(226, 232, 240, 0.6)',
            },
            ticks: {
                font: { family: "'Inter', sans-serif", size: 10 },
                color: '#64748b',
            },
            max: props.horizontal && props.maxVal ? props.maxVal : undefined,
        },
        y: {
            stacked: props.stacked,
            grid: {
                display: !props.horizontal,
                color: 'rgba(226, 232, 240, 0.6)',
            },
            ticks: {
                font: { family: "'Inter', sans-serif", size: 10 },
                color: '#64748b',
            },
            max: !props.horizontal && props.maxVal ? props.maxVal : undefined,
            beginAtZero: true,
        },
    },
}))
</script>

<template>
    <div class="relative w-full h-full min-h-[260px]">
        <Bar :data="chartData" :options="chartOptions" />
    </div>
</template>
