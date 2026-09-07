<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'
import DoughnutChart from '@/Components/Charts/DoughnutChart.vue'
import BarChart from '@/Components/Charts/BarChart.vue'
import LineChart from '@/Components/Charts/LineChart.vue'

const props = defineProps({
    schoolYears: { type: Array, default: () => [] },
    selectedYearId: { type: Number, default: null },
    lapses: { type: Array, default: () => [] },
    selectedLapseId: { type: Number, default: null },
    gradeLevels: { type: Array, default: () => [] },
    selectedGradeLevelId: { type: Number, default: null },
    sections: { type: Array, default: () => [] },
    selectedSectionId: { type: Number, default: null },
    metrics: { type: Object, default: () => ({}) },
})

const yearFilter = ref(props.selectedYearId)
const lapseFilter = ref(props.selectedLapseId ?? 'all')
const gradeLevelFilter = ref(props.selectedGradeLevelId ?? 'all')
const sectionFilter = ref(props.selectedSectionId ?? 'all')
const subjectSearch = ref('')

function applyFilters() {
    router.get('/analytics', {
        school_year_id: yearFilter.value,
        lapse_id: lapseFilter.value,
        grade_level_id: gradeLevelFilter.value,
        section_id: sectionFilter.value,
    }, {
        preserveState: true,
        replace: true,
    })
}

function onYearChange() {
    lapseFilter.value = 'all'
    gradeLevelFilter.value = 'all'
    sectionFilter.value = 'all'
    applyFilters()
}

function onGradeLevelChange() {
    sectionFilter.value = 'all'
    applyFilters()
}

// Filtered sections for dropdown
const filteredSections = computed(() => {
    if (gradeLevelFilter.value === 'all') return props.sections
    return props.sections.filter(s => s.grade_level_id === parseInt(gradeLevelFilter.value))
})

// Filtered subjects table
const filteredSubjects = computed(() => {
    const list = props.metrics?.subjects || []
    if (!subjectSearch.value.trim()) return list
    const q = subjectSearch.value.toLowerCase()
    return list.filter(s => s.name.toLowerCase().includes(q) || s.code.toLowerCase().includes(q))
})

// Charts Data Preparation
const doughnutLabels = ['Aprobados (≥ 10)', 'Reprobados (< 10)']
const doughnutData = computed(() => {
    const pf = props.metrics?.pass_fail || {}
    return [pf.passed || 0, pf.failed || 0]
})
const doughnutColors = ['#10b981', '#ef4444']

// Histogram Data
const histogramLabels = computed(() => props.metrics?.histogram?.labels || [])
const histogramDatasets = computed(() => ([
    {
        label: 'Estudiantes',
        data: props.metrics?.histogram?.counts || [],
        backgroundColor: ['#ef4444', '#f59e0b', '#336b87', '#10b981'],
    }
]))

// Top Failed Subjects Data (Top 7)
const topFailedSubjects = computed(() => {
    const subs = [...(props.metrics?.subjects || [])]
    return subs.sort((a, b) => b.fail_rate - a.fail_rate).slice(0, 7)
})
const topFailedLabels = computed(() => topFailedSubjects.value.map(s => s.name))
const topFailedDatasets = computed(() => ([
    {
        label: '% Reprobados',
        data: topFailedSubjects.value.map(s => s.fail_rate),
        backgroundColor: '#ef4444',
    }
]))

// Sections Comparison Data
const sectionsLabels = computed(() => (props.metrics?.sections || []).map(s => s.name))
const sectionsDatasets = computed(() => ([
    {
        label: 'Promedio de Calificación (pts)',
        data: (props.metrics?.sections || []).map(s => s.average),
        backgroundColor: '#336b87',
    }
]))

// Lapses Trend Data
const lapsesTrendLabels = computed(() => (props.metrics?.lapses_trend || []).map(l => l.name))
const lapsesTrendDatasets = computed(() => ([
    {
        label: 'Promedio del Lapso',
        data: (props.metrics?.lapses_trend || []).map(l => l.average),
        borderColor: '#336b87',
        backgroundColor: 'rgba(51, 107, 135, 0.1)',
    },
    {
        label: '% Aprobación',
        data: (props.metrics?.lapses_trend || []).map(l => l.pass_rate !== null ? (l.pass_rate / 5).toFixed(2) : null), // Normalized to 20 scale for dual comparison
        borderColor: '#10b981',
        backgroundColor: 'rgba(16, 185, 129, 0.05)',
        borderDash: [5, 5],
    }
]))

// Export URLs with current query parameters
const exportExcelUrl = computed(() => {
    return `/analytics/export/excel?school_year_id=${yearFilter.value}&lapse_id=${lapseFilter.value}&grade_level_id=${gradeLevelFilter.value}&section_id=${sectionFilter.value}`
})
const exportPdfUrl = computed(() => {
    return `/analytics/export/pdf?school_year_id=${yearFilter.value}&lapse_id=${lapseFilter.value}&grade_level_id=${gradeLevelFilter.value}&section_id=${sectionFilter.value}`
})
</script>

<template>
    <AppLayout title="Estadísticas y Análisis">
        <div class="space-y-6">
            <!-- Header Banner -->
            <div class="glass-card rounded-3xl p-6 lg:p-8 relative overflow-hidden border border-white/60 shadow-xl shadow-primary-900/5">
                <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-primary-500 via-primary-400 to-accent-400"></div>
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-primary-100/70 text-primary-700 text-xs font-bold mb-2">
                            <i class="fas fa-chart-line text-[11px]"></i>
                            Panel de Inteligencia Académica
                        </div>
                        <h1 class="text-2xl lg:text-3xl font-black text-slate-800 tracking-tight">
                            Estadísticas y Análisis
                        </h1>
                        <p class="text-slate-500 text-sm mt-1">
                            Monitoreo en tiempo real de rendimiento, índices de aprobación e indicadores clave del departamento.
                        </p>
                    </div>

                    <!-- Action buttons -->
                    <div class="flex items-center gap-3">
                        <a
                            :href="exportExcelUrl"
                            target="_blank"
                            class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 hover:border-slate-300 transition-all shadow-sm flex items-center gap-2 cursor-pointer"
                        >
                            <i class="fas fa-file-excel text-emerald-600 text-sm"></i>
                            Exportar Excel
                        </a>
                        <a
                            :href="exportPdfUrl"
                            target="_blank"
                            class="px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-primary-600 hover:bg-primary-700 transition-all shadow-md shadow-primary-600/20 flex items-center gap-2 cursor-pointer"
                        >
                            <i class="fas fa-file-pdf text-sm"></i>
                            Informe PDF
                        </a>
                    </div>
                </div>

                <!-- Filter Bar -->
                <div class="mt-6 pt-6 border-t border-slate-100 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- School Year -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Año Escolar</label>
                        <select
                            v-model="yearFilter"
                            @change="onYearChange"
                            class="w-full text-xs font-medium text-slate-700 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 focus:bg-white focus:border-primary-500 focus:ring-2 focus:ring-primary-100 outline-none transition-all"
                        >
                            <option v-for="y in schoolYears" :key="y.id" :value="y.id">
                                {{ y.name }} {{ y.is_active ? '(Activo)' : '' }}
                            </option>
                        </select>
                    </div>

                    <!-- Lapse -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Lapso</label>
                        <select
                            v-model="lapseFilter"
                            @change="applyFilters"
                            class="w-full text-xs font-medium text-slate-700 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 focus:bg-white focus:border-primary-500 focus:ring-2 focus:ring-primary-100 outline-none transition-all"
                        >
                            <option value="all">Todos los lapsos (Consolidado)</option>
                            <option v-for="l in lapses" :key="l.id" :value="l.id">
                                {{ l.name }} {{ l.is_open ? '(Abierto)' : '' }}
                            </option>
                        </select>
                    </div>

                    <!-- Grade Level -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Año / Grado</label>
                        <select
                            v-model="gradeLevelFilter"
                            @change="onGradeLevelChange"
                            class="w-full text-xs font-medium text-slate-700 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 focus:bg-white focus:border-primary-500 focus:ring-2 focus:ring-primary-100 outline-none transition-all"
                        >
                            <option value="all">Todos los grados</option>
                            <option v-for="lvl in gradeLevels" :key="lvl.id" :value="lvl.id">
                                {{ lvl.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Section -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Sección</label>
                        <select
                            v-model="sectionFilter"
                            @change="applyFilters"
                            class="w-full text-xs font-medium text-slate-700 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 focus:bg-white focus:border-primary-500 focus:ring-2 focus:ring-primary-100 outline-none transition-all"
                        >
                            <option value="all">Todas las secciones</option>
                            <option v-for="s in filteredSections" :key="s.id" :value="s.id">
                                {{ s.grade_level?.name }} {{ s.name }}
                            </option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- KPI Cards Row -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                <!-- Tasa de Aprobación -->
                <div class="glass-card rounded-2xl p-5 border border-white/70 shadow-lg shadow-emerald-900/5 relative overflow-hidden group hover:-translate-y-1 transition-all duration-300">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Tasa de Aprobación</span>
                        <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center">
                            <i class="fas fa-user-check"></i>
                        </div>
                    </div>
                    <div class="mt-4 flex items-baseline gap-2">
                        <span class="text-3xl font-black text-slate-800">{{ metrics.kpis?.pass_rate }}%</span>
                        <span class="text-xs font-semibold text-emerald-600">({{ metrics.kpis?.passed_count }} aprobados)</span>
                    </div>
                    <div class="mt-3 w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                        <div class="bg-emerald-500 h-full rounded-full transition-all duration-500" :style="{ width: `${metrics.kpis?.pass_rate || 0}%` }"></div>
                    </div>
                </div>

                <!-- Promedio General -->
                <div class="glass-card rounded-2xl p-5 border border-white/70 shadow-lg shadow-primary-900/5 relative overflow-hidden group hover:-translate-y-1 transition-all duration-300">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Promedio General</span>
                        <div class="w-10 h-10 rounded-xl bg-primary-100 text-primary-600 flex items-center justify-center">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                    </div>
                    <div class="mt-4 flex items-baseline gap-2">
                        <span class="text-3xl font-black text-slate-800">{{ metrics.kpis?.overall_average }}</span>
                        <span class="text-xs text-slate-400 font-bold">/ 20 pts</span>
                    </div>
                    <p class="text-xs text-slate-500 mt-2 font-medium">
                        Escala oficial de evaluación vigesimal
                    </p>
                </div>

                <!-- Materia Crítica -->
                <div class="glass-card rounded-2xl p-5 border border-white/70 shadow-lg shadow-red-900/5 relative overflow-hidden group hover:-translate-y-1 transition-all duration-300">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Mayor Alerta</span>
                        <div class="w-10 h-10 rounded-xl bg-red-100 text-red-600 flex items-center justify-center">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="text-sm font-black text-slate-800 line-clamp-1" :title="metrics.kpis?.critical_subject?.name || 'Ninguna'">
                            {{ metrics.kpis?.critical_subject?.name || 'Ninguna' }}
                        </div>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="text-xs font-bold text-red-600">
                                {{ metrics.kpis?.critical_subject?.fail_rate || 0 }}% aplazados
                            </span>
                            <span class="text-slate-300">•</span>
                            <span class="text-xs text-slate-500 font-medium">
                                Prom: {{ metrics.kpis?.critical_subject?.average || 0 }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Total Evaluaciones -->
                <div class="glass-card rounded-2xl p-5 border border-white/70 shadow-lg shadow-sky-900/5 relative overflow-hidden group hover:-translate-y-1 transition-all duration-300">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Evaluaciones</span>
                        <div class="w-10 h-10 rounded-xl bg-sky-100 text-sky-600 flex items-center justify-center">
                            <i class="fas fa-clipboard-check"></i>
                        </div>
                    </div>
                    <div class="mt-4 flex items-baseline gap-2">
                        <span class="text-3xl font-black text-slate-800">{{ metrics.kpis?.total_grades }}</span>
                        <span class="text-xs font-semibold text-slate-500">notas</span>
                    </div>
                    <p class="text-xs text-slate-500 mt-2 font-medium">
                        {{ metrics.kpis?.total_students }} alumnos evaluados
                    </p>
                </div>
            </div>

            <!-- Charts Row 1: Aprobados vs Reprobados & Histograma -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                <!-- Doughnut Chart: Aprobados vs Reprobados -->
                <div class="lg:col-span-5 glass-card rounded-3xl p-6 border border-white/60 shadow-xl shadow-primary-900/5 flex flex-col justify-between">
                    <div>
                        <h3 class="text-base font-bold text-slate-800">Proporción de Aprobación</h3>
                        <p class="text-xs text-slate-400 mt-0.5">Relación entre calificaciones aprobadas y reprobadas</p>
                    </div>
                    <div class="py-4">
                        <DoughnutChart
                            v-if="metrics.kpis?.total_grades > 0"
                            :labels="doughnutLabels"
                            :data="doughnutData"
                            :colors="doughnutColors"
                        />
                        <div v-else class="text-center py-12 text-slate-400 text-xs">
                            No hay calificaciones registradas para este filtro
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2 pt-4 border-t border-slate-100 text-center">
                        <div class="bg-emerald-50 rounded-xl p-2.5">
                            <span class="text-[10px] uppercase font-bold text-emerald-600 tracking-wider">Aprobados</span>
                            <div class="text-lg font-black text-emerald-700">{{ metrics.kpis?.pass_rate }}%</div>
                        </div>
                        <div class="bg-red-50 rounded-xl p-2.5">
                            <span class="text-[10px] uppercase font-bold text-red-600 tracking-wider">Reprobados</span>
                            <div class="text-lg font-black text-red-700">{{ metrics.kpis?.fail_rate }}%</div>
                        </div>
                    </div>
                </div>

                <!-- Bar Chart: Distribución Pedagógica (Histograma) -->
                <div class="lg:col-span-7 glass-card rounded-3xl p-6 border border-white/60 shadow-xl shadow-primary-900/5 flex flex-col justify-between">
                    <div>
                        <h3 class="text-base font-bold text-slate-800">Distribución de Calificaciones</h3>
                        <p class="text-xs text-slate-400 mt-0.5">Conteo de calificaciones según escala pedagógica de rendimiento</p>
                    </div>
                    <div class="py-4">
                        <BarChart
                            v-if="metrics.kpis?.total_grades > 0"
                            :labels="histogramLabels"
                            :datasets="histogramDatasets"
                            unit=" notas"
                        />
                        <div v-else class="text-center py-12 text-slate-400 text-xs">
                            No hay datos para graficar
                        </div>
                    </div>
                    <div class="text-xs text-slate-400 text-right">
                        Total evaluado: <strong class="text-slate-600">{{ metrics.kpis?.total_grades }} calificaciones</strong>
                    </div>
                </div>
            </div>

            <!-- Charts Row 2: Top Aplazados & Comparativa de Secciones -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                <!-- Top Materias con más Aplazados -->
                <div class="lg:col-span-6 glass-card rounded-3xl p-6 border border-white/60 shadow-xl shadow-primary-900/5">
                    <div>
                        <h3 class="text-base font-bold text-slate-800">Top Materias con Mayor Índice de Aplazados</h3>
                        <p class="text-xs text-slate-400 mt-0.5">Asignaturas que requieren mayor atención y refuerzo académico</p>
                    </div>
                    <div class="mt-4">
                        <BarChart
                            v-if="topFailedSubjects.length > 0"
                            :labels="topFailedLabels"
                            :datasets="topFailedDatasets"
                            :horizontal="true"
                            :maxVal="100"
                            unit="%"
                        />
                        <div v-else class="text-center py-12 text-slate-400 text-xs">
                            Sin materias reprobadas en este filtro
                        </div>
                    </div>
                </div>

                <!-- Comparativa de Secciones -->
                <div class="lg:col-span-6 glass-card rounded-3xl p-6 border border-white/60 shadow-xl shadow-primary-900/5">
                    <div>
                        <h3 class="text-base font-bold text-slate-800">Rendimiento Promedio por Sección</h3>
                        <p class="text-xs text-slate-400 mt-0.5">Comparativa del promedio académico obtenido por cada aula</p>
                    </div>
                    <div class="mt-4">
                        <BarChart
                            v-if="sectionsLabels.length > 0"
                            :labels="sectionsLabels"
                            :datasets="sectionsDatasets"
                            :maxVal="20"
                            unit=" pts"
                        />
                        <div v-else class="text-center py-12 text-slate-400 text-xs">
                            No hay secciones con evaluaciones registradas
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row 3: Evolución Inter-Lapsos -->
            <div v-if="metrics.lapses_trend && metrics.lapses_trend.length > 0" class="glass-card rounded-3xl p-6 border border-white/60 shadow-xl shadow-primary-900/5">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-bold text-slate-800">Evolución de Rendimiento a lo Largo del Año</h3>
                        <p class="text-xs text-slate-400 mt-0.5">Comparativa del promedio general obtenido en cada lapso académico</p>
                    </div>
                    <div class="hidden sm:flex items-center gap-4 text-xs font-semibold">
                        <span class="flex items-center gap-1.5 text-primary-600">
                            <span class="w-3 h-0.5 bg-primary-500 inline-block"></span> Promedio (pts)
                        </span>
                        <span class="flex items-center gap-1.5 text-emerald-600">
                            <span class="w-3 h-0.5 border-t-2 border-dashed border-emerald-500 inline-block"></span> % Aprobación normalizado
                        </span>
                    </div>
                </div>
                <div class="mt-4">
                    <LineChart
                        :labels="lapsesTrendLabels"
                        :datasets="lapsesTrendDatasets"
                        :maxVal="20"
                    />
                </div>
            </div>

            <!-- Tabla de Rendimiento por Materia -->
            <div class="glass-card rounded-3xl p-6 border border-white/60 shadow-xl shadow-primary-900/5">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                    <div>
                        <h3 class="text-base font-bold text-slate-800">Rendimiento Detallado por Asignatura</h3>
                        <p class="text-xs text-slate-400 mt-0.5">Sábana estadística consolidada con porcentajes y promedios</p>
                    </div>
                    <div class="relative w-full md:w-64">
                        <i class="fas fa-search absolute left-3 top-3 text-slate-400 text-xs"></i>
                        <input
                            v-model="subjectSearch"
                            type="text"
                            placeholder="Buscar materia o código..."
                            class="w-full text-xs pl-8 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-primary-500 focus:ring-2 focus:ring-primary-100 outline-none transition-all"
                        />
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-600">
                        <thead class="bg-slate-50 text-slate-400 font-bold uppercase tracking-wider text-[10px] border-b border-slate-100">
                            <tr>
                                <th class="py-3 px-4">Código</th>
                                <th class="py-3 px-4">Materia</th>
                                <th class="py-3 px-4 text-center">Evaluados</th>
                                <th class="py-3 px-4 text-center">Aprobados</th>
                                <th class="py-3 px-4 text-center">Reprobados</th>
                                <th class="py-3 px-4 text-center">% Aprobación</th>
                                <th class="py-3 px-4 text-center">Promedio</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium">
                            <tr
                                v-for="sub in filteredSubjects"
                                :key="sub.id"
                                class="hover:bg-slate-50/80 transition-colors"
                            >
                                <td class="py-3.5 px-4 font-mono text-[11px] text-slate-500">{{ sub.code }}</td>
                                <td class="py-3.5 px-4 font-bold text-slate-800">{{ sub.name }}</td>
                                <td class="py-3.5 px-4 text-center">{{ sub.evaluated }}</td>
                                <td class="py-3.5 px-4 text-center text-emerald-600 font-bold">{{ sub.passed }}</td>
                                <td class="py-3.5 px-4 text-center text-red-600 font-bold">{{ sub.failed }}</td>
                                <td class="py-3.5 px-4 text-center">
                                    <span
                                        class="px-2.5 py-1 rounded-full text-[10px] font-black tracking-wider uppercase"
                                        :class="sub.pass_rate >= 75 ? 'bg-emerald-100 text-emerald-700' : (sub.pass_rate >= 50 ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700')"
                                    >
                                        {{ sub.pass_rate }}%
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-center font-black text-slate-800">
                                    {{ sub.average }}
                                </td>
                            </tr>
                            <tr v-if="filteredSubjects.length === 0">
                                <td colspan="7" class="py-8 text-center text-slate-400">
                                    No se encontraron materias registradas para los filtros seleccionados.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
