<script setup>
import { ref, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'

const props = defineProps({
    activeYear: Object,
    sections:   Array,
    subjects:   Array,
    lapses:     Array,
    rows:       Array,
    filters:    Object,
})

const gradeLevelId = ref(props.filters.grade_level_id ? (parseInt(props.filters.grade_level_id) || '') : '')
const sectionId = ref(props.filters.section_id ? (parseInt(props.filters.section_id) || '') : '')
const subjectId = ref(props.filters.subject_id ? (parseInt(props.filters.subject_id) || '') : '')

const activeLapse = props.lapses.find(l => l.is_open)
const lapseId   = ref(props.filters.lapse_id   ? (parseInt(props.filters.lapse_id) || '') : (activeLapse ? activeLapse.id : ''))

const selectedLapseObj = computed(() => props.lapses.find(l => l.id === lapseId.value))
const showLapseWarning = computed(() => selectedLapseObj.value && !selectedLapseObj.value.is_open)

// Si hay una sección pero no hay grade_level_id en la URL, inicializarlo
if (!gradeLevelId.value && sectionId.value) {
    const sec = props.sections.find(s => s.id === sectionId.value)
    if (sec) gradeLevelId.value = sec.grade_level_id
}

const gradeLevels = computed(() => {
    const unique = []
    const ids = new Set()
    props.sections.forEach(s => {
        if (s.grade_level && !ids.has(s.grade_level.id)) {
            unique.push(s.grade_level)
            ids.add(s.grade_level.id)
        }
    })
    return unique.sort((a, b) => a.level - b.level)
})

const filteredSections = computed(() => {
    if (!gradeLevelId.value) return []
    return props.sections.filter(s => s.grade_level_id === gradeLevelId.value)
})

const isLoading = ref(false)
const searchQuery = ref('')
const sortOrder = ref('name')
let debounceTimer = null

// Ajustes locales (por grade_id)
const localRows = ref([])

const initAdjustments = () => {
    localRows.value = props.rows.map(row => ({
        ...row,
        council_adjustment: row.council_adjustment ?? 0,
        saving: false,
        saved: false
    }))
    isLoading.value = false
}
initAdjustments()

const definitiveOf = (row) => {
    return Math.min(20, Math.max(1, row.score + row.council_adjustment))
}

const processedRows = computed(() => {
    let result = [...localRows.value]

    // Filtrar por búsqueda
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase()
        result = result.filter(row => {
            const nameMatch = row.student_name.toLowerCase().includes(query)
            const cedulaMatch = (row.student_cedula || '').toLowerCase().includes(query)
            return nameMatch || cedulaMatch
        })
    }

    // Ordenar
    if (sortOrder.value === 'cedula') {
        result.sort((a, b) => {
            const valA = a.student_cedula || ''
            const valB = b.student_cedula || ''
            return valA.localeCompare(valB)
        })
    } else {
        result.sort((a, b) => {
            const valA = a.student_name || ''
            const valB = b.student_name || ''
            return valA.localeCompare(valB)
        })
    }

    return result
})

watch(gradeLevelId, (newId, oldId) => {
    if (oldId && newId !== oldId) {
        sectionId.value = ''
        subjectId.value = ''
    }
    if (newId) {
        router.get('/admin/council-adjustments', { grade_level_id: newId }, { preserveState: true, only: ['subjects'] })
    }
})

watch([sectionId, subjectId, lapseId], ([sec, sub, lap]) => {
    if (sec && sub && lap) {
        isLoading.value = true
        router.get('/admin/council-adjustments', {
            grade_level_id: gradeLevelId.value,
            section_id: sec,
            subject_id: sub,
            lapse_id: lap
        }, { preserveState: true, onSuccess: initAdjustments })
    }
})

function getIconColor(adjustment) {
    if (adjustment === 0) return 'text-slate-300 group-hover:text-slate-400'
    if (adjustment > 0) return 'text-emerald-400 group-hover:text-emerald-500'
    return 'text-red-400 group-hover:text-red-500'
}

function incrementAdjustment(row) {
    if (row.council_adjustment < 5) {
        row.council_adjustment += 1
        triggerSave(row)
    }
}

function decrementAdjustment(row) {
    if (row.council_adjustment > -5) {
        row.council_adjustment -= 1
        triggerSave(row)
    }
}

function updateQualitativeAdjustment(row) {
    if (row.qualitative_definitive !== undefined) {
        let diff = parseInt(row.qualitative_definitive) - parseInt(row.score);
        row.council_adjustment = diff;
        validateAndSave(row);
    }
}

function validateAndSave(row) {
    let s = parseInt(row.council_adjustment)
    if (isNaN(s)) {
        row.council_adjustment = 0
    } else {
        if (s > 5) row.council_adjustment = 5
        if (s < -5) row.council_adjustment = -5
    }
    triggerSave(row)
}

function triggerSave(row) {
    row.saving = true
    row.saved = false
    
    if (debounceTimer) clearTimeout(debounceTimer)
    
    debounceTimer = setTimeout(() => {
        saveToServer(row)
    }, 500)
}

function saveToServer(row) {
    router.patch('/admin/council-adjustments', {
        grade_id: row.grade_id,
        council_adjustment: row.council_adjustment,
    }, { 
        preserveScroll: true, 
        preserveState: true,
        onFinish: () => {
            row.saving = false
            row.saved = true
            setTimeout(() => { row.saved = false }, 2000)
        }
    })
}

const hasFilters = computed(() => sectionId.value && subjectId.value && lapseId.value)
</script>

<template>
    <AppLayout title="Ajuste de Consejo">
        <div class="space-y-6 max-w-7xl mx-auto pb-10">
            <!-- Header -->
            <div class="animate-fade-in-up">
                <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-6 relative z-10 mb-6">
                    <div>
                        <div class="flex items-center gap-4">
                            <h1 class="text-3xl font-extrabold text-slate-800">
                                Ajuste de <span class="gradient-text">Consejo</span>
                            </h1>
                            <span v-if="activeYear" class="px-3 py-1 bg-primary-50 text-primary-600 rounded-lg text-xs font-black uppercase tracking-widest border border-primary-100 shadow-sm">
                                {{ activeYear.name }}
                            </span>
                        </div>
                        <p class="text-slate-500 font-bold mt-2">Aplica puntos de ajuste del consejo docente a las notas definitivas.</p>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="glass-card rounded-3xl p-5 sm:p-6 shadow-xl relative overflow-hidden flex flex-wrap items-end gap-4" style="animation-delay: 50ms">
                    <div class="absolute top-0 right-0 p-6 opacity-5 pointer-events-none hidden sm:block">
                        <i class="fas fa-balance-scale text-8xl text-primary-500"></i>
                    </div>

                    <!-- Año -->
                    <div class="flex flex-col gap-2 flex-1 min-w-[140px] relative z-10">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Año</label>
                        <div class="relative">
                            <select v-model="gradeLevelId"
                                class="w-full bg-white border-2 border-slate-100 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:ring-0 outline-none transition-all appearance-none shadow-sm">
                                <option value="">Año...</option>
                                <option v-for="g in gradeLevels" :key="g.id" :value="g.id">
                                    {{ g.name }}
                                </option>
                            </select>
                            <i class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none" :class="showLapseWarning ? 'text-amber-400' : 'text-slate-300'"></i>
                        </div>
                        <p v-if="showLapseWarning" class="absolute -bottom-5 left-1 text-[9.5px] font-black text-amber-500 flex items-center gap-1 animate-fade-in whitespace-nowrap uppercase tracking-widest">
                            <i class="fas fa-exclamation-triangle"></i> Editando lapso inactivo
                        </p>
                    </div>

                    <!-- Sección -->
                    <div class="flex flex-col gap-2 flex-1 min-w-[140px] relative z-10">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Sección</label>
                        <div class="relative">
                            <select v-model="sectionId" :disabled="!gradeLevelId"
                                class="w-full bg-white border-2 border-slate-100 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:ring-0 outline-none transition-all appearance-none shadow-sm disabled:opacity-50">
                                <option value="">Sección...</option>
                                <option v-for="s in filteredSections" :key="s.id" :value="s.id">
                                    Sec. {{ s.name }}
                                </option>
                            </select>
                            <i class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none" :class="showLapseWarning ? 'text-amber-400' : 'text-slate-300'"></i>
                        </div>
                        <p v-if="showLapseWarning" class="absolute -bottom-5 left-1 text-[9.5px] font-black text-amber-500 flex items-center gap-1 animate-fade-in whitespace-nowrap uppercase tracking-widest">
                            <i class="fas fa-exclamation-triangle"></i> Editando lapso inactivo
                        </p>
                    </div>

                    <!-- Materia -->
                    <div class="flex flex-col gap-2 flex-1 min-w-[160px] relative z-10" v-if="subjects.length > 0">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Materia</label>
                        <div class="relative">
                            <select v-model="subjectId"
                                class="w-full bg-white border-2 border-slate-100 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:ring-0 outline-none transition-all appearance-none shadow-sm">
                                <option value="">Materia...</option>
                                <option v-for="s in subjects" :key="s.id" :value="s.id">{{ s.name }}</option>
                            </select>
                            <i class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none" :class="showLapseWarning ? 'text-amber-400' : 'text-slate-300'"></i>
                        </div>
                        <p v-if="showLapseWarning" class="absolute -bottom-5 left-1 text-[9.5px] font-black text-amber-500 flex items-center gap-1 animate-fade-in whitespace-nowrap uppercase tracking-widest">
                            <i class="fas fa-exclamation-triangle"></i> Editando lapso inactivo
                        </p>
                    </div>

                    <!-- Lapso -->
                    <div class="flex flex-col gap-2 flex-1 min-w-[140px] relative z-10" v-if="lapses.length > 0">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Lapso</label>
                        <div class="relative">
                            <select v-model="lapseId"
                                class="w-full bg-white border-2 border-slate-100 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:ring-0 outline-none transition-all appearance-none shadow-sm"
                                :class="{'border-amber-300 ring-4 ring-amber-50 text-amber-800': showLapseWarning}">
                                <option value="">Lapso...</option>
                                <option v-for="l in lapses" :key="l.id" :value="l.id">{{ l.name }}</option>
                            </select>
                            <i class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none" :class="showLapseWarning ? 'text-amber-400' : 'text-slate-300'"></i>
                        </div>
                        <p v-if="showLapseWarning" class="absolute -bottom-5 left-1 text-[9.5px] font-black text-amber-500 flex items-center gap-1 animate-fade-in whitespace-nowrap uppercase tracking-widest">
                            <i class="fas fa-exclamation-triangle"></i> Editando lapso inactivo
                        </p>
                    </div>
                </div>
            </div>

            <!-- Loading State -->
            <div v-if="isLoading" class="glass-card rounded-3xl p-16 text-center animate-fade-in-up">
                <div class="w-16 h-16 bg-primary-50 rounded-full flex items-center justify-center mx-auto mb-4 text-primary-500 shadow-inner">
                    <i class="fas fa-spinner fa-spin text-2xl"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-700">Cargando información...</h3>
                <p class="text-slate-500 text-sm mt-1">Obteniendo datos de los estudiantes.</p>
            </div>

            <div v-else-if="localRows.length > 0" class="space-y-4">
                <!-- Buscador y Ordenamiento -->
                <div class="flex flex-col sm:flex-row items-center gap-3 w-full animate-fade-in-up" style="animation-delay: 150ms">
                    <div class="relative w-full flex-1">
                        <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input 
                            v-model="searchQuery" 
                            type="text" 
                            placeholder="Buscar estudiante por nombre o cédula..." 
                            class="w-full bg-white border-2 border-slate-100 rounded-2xl pl-11 pr-4 py-3 text-sm text-slate-700 placeholder:text-slate-400 focus:border-primary-400 focus:ring-0 outline-none transition-all shadow-sm disabled:bg-slate-50 disabled:text-slate-500"
                        >
                    </div>
                    <div class="w-full sm:w-auto relative">
                        <select v-model="sortOrder" class="w-full sm:w-56 bg-white border-2 border-slate-100 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:ring-0 outline-none transition-all shadow-sm appearance-none cursor-pointer">
                            <option value="name">Ordenar por Apellido</option>
                            <option value="cedula">Ordenar por Cédula</option>
                        </select>
                        <i class="fas fa-sort absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                    </div>
                </div>

                <!-- Lista de Tarjetas -->
                <div class="space-y-3 animate-fade-in-up" style="animation-delay: 200ms">
                    <div v-if="processedRows.length === 0" class="p-10 bg-white rounded-3xl shadow-sm border-2 border-slate-100 text-center text-slate-400 font-bold">
                        <i class="fas fa-search text-3xl mb-3 opacity-20"></i>
                        <p>No se encontraron estudiantes.</p>
                    </div>

                    <div 
                        v-for="(row, idx) in processedRows" 
                        :key="row.grade_id"
                        class="bg-white p-4 sm:p-5 rounded-2xl shadow-sm border-2 border-slate-100 hover:shadow-md hover:border-slate-300 transition-all flex flex-col lg:flex-row lg:items-center justify-between gap-4"
                    >
                        <!-- Info Estudiante -->
                        <div class="flex items-center gap-4 flex-1">
                            <div class="w-8 h-8 rounded-full bg-slate-50 text-slate-500 flex items-center justify-center font-black text-[10px] shrink-0 shadow-sm border border-slate-200">
                                {{ idx + 1 }}
                            </div>
                            <div class="min-w-0">
                                <h3 class="text-sm font-black text-slate-800 truncate">{{ row.student_name }}</h3>
                                <p class="text-xs font-bold text-slate-500 mt-0.5 flex items-center gap-1.5">
                                    <i class="fas fa-id-card opacity-50"></i> {{ row.student_cedula || 'Sin Cédula' }}
                                </p>
                            </div>
                        </div>

                        <!-- Panel de Ajuste y Definitiva -->
                        <div class="flex flex-wrap lg:flex-nowrap items-center justify-between lg:justify-end gap-4 w-full lg:w-auto bg-slate-50/50 p-2 lg:p-1 rounded-xl">
                            
                            <!-- Nota Docente Original -->
                            <div class="flex flex-col items-center px-4">
                                <span class="text-[9px] font-black uppercase text-slate-400 tracking-wider mb-1">Docente</span>
                                <span class="text-lg font-black text-slate-700 bg-slate-100 px-3 py-1 rounded-lg">{{ row.score }}</span>
                            </div>

                            <div class="h-8 w-px bg-slate-200 hidden sm:block"></div>

                            <!-- Controles de Ajuste -->
                            <div class="flex items-center gap-2 relative">
                                <!-- Status Indicator (Spinner/Check) -->
                                <div class="absolute -left-6 w-6 flex justify-center">
                                    <i v-if="row.saving" class="fas fa-spinner fa-spin text-slate-400 text-xs"></i>
                                    <i v-else-if="row.saved" class="fas fa-check-circle text-emerald-500 text-xs animate-bounce"></i>
                                </div>

                                <button 
                                    @click="decrementAdjustment(row)" :disabled="!$can('council.manage')"
                                    class="w-10 h-10 rounded-xl bg-white shadow-sm border-b-2 flex items-center justify-center text-sm transition-all group active:scale-95 disabled:opacity-50"
                                    :class="[row.council_adjustment > -5 ? 'border-slate-200 hover:border-slate-300' : 'border-slate-100 opacity-50']"
                                >
                                    <i class="fas fa-minus" :class="getIconColor(row.council_adjustment)"></i>
                                </button>

                                <div class="relative w-16 h-10">
                                    <input 
                                        v-model="row.council_adjustment"
                                        @change="validateAndSave(row)"
                                        @blur="validateAndSave(row)"
                                        :disabled="!$can('council.manage')" type="number" 
                                        min="-5" max="5"
                                        class="w-full h-full bg-white border-2 border-slate-200 rounded-xl text-center text-sm font-black text-slate-800 focus:border-primary-400 focus:ring-0 outline-none transition-all shadow-sm disabled:bg-slate-50 disabled:text-slate-500"
                                        placeholder="0"
                                    >
                                </div>

                                <button 
                                    @click="incrementAdjustment(row)" :disabled="!$can('council.manage')"
                                    class="w-10 h-10 rounded-xl bg-white shadow-sm border-b-2 flex items-center justify-center text-sm transition-all group active:scale-95 disabled:opacity-50"
                                    :class="[row.council_adjustment < 5 ? 'border-slate-200 hover:border-slate-300' : 'border-slate-100 opacity-50']"
                                >
                                    <i class="fas fa-plus" :class="getIconColor(row.council_adjustment)"></i>
                                </button>
                            </div>

                            <div class="h-8 w-px bg-slate-200 hidden sm:block"></div>

                            <!-- Nota Definitiva -->
                            <div class="flex items-center px-2">
                                <div class="flex flex-col items-end mr-3">
                                    <span class="text-[9px] font-black uppercase text-slate-400 tracking-wider">Definitiva</span>
                                    <span v-if="definitiveOf(row) >= 10" class="text-[10px] text-emerald-500 font-bold">Aprobado</span>
                                    <span v-else class="text-[10px] text-red-500 font-bold">Reprobado</span>
                                </div>
                                <span 
                                    class="w-14 h-12 flex items-center justify-center rounded-xl text-xl font-black shadow-inner border transition-colors"
                                    :class="definitiveOf(row) >= 10 
                                        ? 'bg-emerald-50 text-emerald-600 border-emerald-200' 
                                        : 'bg-red-50 text-red-600 border-red-200'"
                                >
                                    <template v-if="selectedSubject && selectedSubject.grading_type === 'qualitative'">
                                        {{ definitiveOf(row) >= 20 ? 'A' : (definitiveOf(row) >= 16 ? 'B' : (definitiveOf(row) >= 12 ? 'C' : (definitiveOf(row) >= 10 ? 'D' : 'E'))) }}
                                    </template>
                                    <template v-else>
                                        {{ definitiveOf(row) }}
                                    </template>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else-if="hasFilters && !isLoading" class="glass-card rounded-3xl p-16 text-center animate-fade-in-up">
                <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 text-slate-200">
                    <i class="fas fa-inbox text-2xl"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-400">Sin notas registradas</h3>
                <p class="text-slate-300 text-sm mt-1">No hay notas cargadas para esta combinación de sección, materia y lapso.</p>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
input[type=number]::-webkit-inner-spin-button, 
input[type=number]::-webkit-outer-spin-button { 
  -webkit-appearance: none; 
  margin: 0; 
}
input[type=number] {
  -moz-appearance: textfield;
}
</style>