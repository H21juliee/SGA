<script setup>
import { computed } from 'vue'
import { Doughnut } from 'vue-chartjs'
import {
    Chart as ChartJS,
    ArcElement,
    Tooltip,
    Legend
} from 'chart.js'

ChartJS.register(ArcElement, Tooltip, Legend)

const props = defineProps({
    labels: {
        type: Array,
        required: true,
    },
    data: {
        type: Array,
        required: true,
    },
    colors: {
        type: Array,
        default: () => ['#10b981', '#ef4444', '#f59e0b', '#336b87'],
    },
    cutout: {
        type: String,
        default: '70%',
    }
})

const chartData = computed(() => ({
    labels: props.labels,
    datasets: [
        {
            backgroundColor: props.colors,
            borderWidth: 2,
            borderColor: '#ffffff',
            hoverOffset: 6,
            data: props.data,
        },
    ],
}))

const chartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'bottom',
            labels: {
                boxWidth: 12,
                padding: 16,
                font: {
                    family: "'Inter', sans-serif",
                    weight: 600,
                    size: 11,
                },
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
                    const value = context.raw || 0
                    const total = context.dataset.data.reduce((a, b) => a + b, 0)
                    const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0
                    return ` ${context.label}: ${value} (${percentage}%)`
                },
            },
        },
    },
    cutout: props.cutout,
}))
</script>

<template>
    <div class="relative w-full h-full min-h-[220px]">
        <Doughnut :data="chartData" :options="chartOptions" />
    </div>
</template>
