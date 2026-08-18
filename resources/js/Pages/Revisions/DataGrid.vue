<script setup>
import { ref, computed, watch } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'

const props = defineProps({
    section: Object,
    subject: Object,
    enrollments: Array,
    isClosed: Boolean,
})

const searchQuery = ref('')
const sortOrder = ref('name')
let debounceTimer = null

// Initialize local state for immediate visual feedback
const localRows = ref(
    props.enrollments.map(enrollment => {
        const revGrade = enrollment.revision_grades?.[0]
        return {
            enrollment_id: enrollment.id,
            student_name: `${enrollment.student.last_name}, ${enrollment.student.first_name}`,
            cedula: enrollment.student.cedula,
            score: revGrade?.score ?? null,
            status: revGrade?.status ?? 'pending',
            saving: false,
            saved: false
        }
    })
)

watch(() => props.enrollments, (newVal) => {
    // Sync external changes (if any) while avoiding overwriting local typing
    newVal.forEach(enrollment => {
        const revGrade = enrollment.revision_grades?.[0]
        const local = localRows.value.find(r => r.enrollment_id === enrollment.id)
        if (local && !local.saving) {
            local.score = revGrade?.score ?? null
            local.status = revGrade?.status ?? 'pending'
        }
    })
}, { deep: true })

const filteredRows = computed(() => {
    let result = [...localRows.value]
    
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase()
        result = result.filter(row => {
            return row.student_name.toLowerCase().includes(query) || 
                   (row.cedula && row.cedula.toLowerCase().includes(query))
        })
    }
    
    if (sortOrder.value === 'cedula') {
        result.sort((a, b) => {
            const valA = a.cedula || ''
            const valB = b.cedula || ''
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

const progress = computed(() => {
    const total = localRows.value.length
    const loaded = localRows.value.filter(r => r.score !== null && r.score !== '').length
    const percentage = total === 0 ? 0 : Math.round((loaded / total) * 100)
    return { total, loaded, percentage }
})

function getScoreColor(score) {
    if (score === null || score === '') return 'bg-slate-100 text-slate-400 border-slate-200'
    const s = parseInt(score)
    if (isNaN(s)) return 'bg-slate-100 text-slate-400 border-slate-200'
    if (s < 10) return 'bg-red-50 text-red-600 border-red-200'
    if (s < 15) return 'bg-amber-50 text-amber-600 border-amber-200'
    return 'bg-emerald-50 text-emerald-600 border-emerald-200'
}

function getIconColor(score) {
    if (score === null || score === '') return 'text-slate-300 group-hover:text-slate-400'
    const s = parseInt(score)
    if (isNaN(s)) return 'text-slate-300'
    if (s < 10) return 'text-red-400 group-hover:text-red-500'
    if (s < 15) return 'text-amber-400 group-hover:text-amber-500'
    return 'text-emerald-400 group-hover:text-emerald-500'
}

function incrementScore(row) {
    if (props.isClosed) return
    let current = parseInt(row.score)
    if (isNaN(current)) current = 0
    if (current < 20) {
        row.score = current + 1
        triggerSave(row)
    }
}

function decrementScore(row) {
    if (props.isClosed) return
    let current = parseInt(row.score)
    if (isNaN(current)) current = 0
    if (current > 1) {
        row.score = current - 1
        triggerSave(row)
    }
}

function validateAndSave(row) {
    if (props.isClosed) return
    let s = parseInt(row.score)
    if (isNaN(s) || row.score === '') {
        row.score = null
        row.status = 'pending'
    } else {
        if (s > 20) row.score = 20
        if (s < 1) row.score = 1
        row.status = row.score >= 10 ? 'approved' : 'failed'
    }
    triggerSave(row)
}

function triggerSave(row) {
    if (props.isClosed) return
    
    // Update local status for immediate UI feedback
    if (row.score !== null && row.score !== '') {
        row.status = row.score >= 10 ? 'approved' : 'failed'
    } else {
        row.status = 'pending'
    }
    
    row.saving = true
    row.saved = false
    
    if (debounceTimer) clearTimeout(debounceTimer)
    
    debounceTimer = setTimeout(() => {
        saveToServer(row)
    }, 500)
}

function saveToServer(row) {
    router.patch('/revisions', {
        enrollment_id: row.enrollment_id,
        subject_id: props.subject.id,
        score: row.score,
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
</script>

<template>
    <AppLayout :title="`Revisiones — ${subject.name}`">
        <div class="space-y-6 max-w-7xl mx-auto pb-10">
            <!-- Header & Breadcrumbs -->
            <div class="animate-fade-in-up">
                <div class="flex items-center gap-4 mb-4">
                    <Link href="/revisions" class="px-4 py-2 rounded-xl bg-white border-2 border-slate-100 text-slate-500 font-bold text-xs uppercase tracking-wider hover:bg-slate-50 hover:text-red-500 hover:border-red-200 transition-all shadow-sm flex items-center gap-2 group">
                        <i class="fas fa-arrow-left group-hover:-translate-x-1 transition-transform"></i> Volver
                    </Link>

                    <nav class="flex items-center gap-3 text-[10px] font-black uppercase tracking-widest text-slate-400 hidden sm:flex">
                        <Link href="/revisions" class="hover:text-red-500 transition-colors">Revisiones</Link>
                        <i class="fas fa-chevron-right text-[8px]"></i>
                        <span class="text-slate-600">{{ subject.name }}</span>
                    </nav>
                </div>

                <div class="glass-card rounded-3xl p-5 sm:p-8 shadow-xl relative overflow-hidden">
                    <div class="absolute top-0 right-0 p-8 opacity-5 pointer-events-none hidden sm:block">
                        <i class="fas fa-sync-alt text-8xl text-red-500"></i>
                    </div>

                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 relative z-10">
                        <div>
                            <div class="flex flex-wrap items-center gap-3 mb-2">
                                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-800">Evaluación de Revisión</h1>
                            </div>
                            <p class="text-slate-500 font-bold uppercase tracking-widest text-xs flex items-center gap-2">
                                <span class="px-2.5 py-1 bg-slate-100 text-slate-600 rounded-lg">
                                    {{ section.grade_level?.name }} — Sec. {{ section.name }}
                                </span>
                                <span class="text-red-500">{{ subject.name }}</span>
                            </p>
                        </div>
                        
                        <div class="flex items-center gap-3 w-full sm:w-auto">
                            <div
                                class="flex w-full items-center justify-center gap-3 px-5 py-3.5 rounded-2xl shadow-sm border"
                                :class="!isClosed
                                    ? 'bg-amber-50 border-amber-100 text-amber-700'
                                    : 'bg-slate-50 border-slate-200 text-slate-500'"
                            >
                                <div
                                    class="w-3 h-3 rounded-full animate-pulse-slow"
                                    :class="!isClosed ? 'bg-amber-500 shadow-lg shadow-amber-500/50' : 'bg-slate-400'"
                                ></div>
                                <span class="text-xs font-black uppercase tracking-widest">
                                    {{ !isClosed ? 'Carga Abierta' : 'Año Cerrado (Solo Lectura)' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="mt-6 pt-6 border-t border-slate-100">
                        <div class="flex justify-between items-end mb-2">
                            <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">Progreso de Evaluaciones</span>
                            <span class="text-sm font-black" :class="progress.loaded === progress.total ? 'text-emerald-500' : 'text-amber-500'">
                                {{ progress.loaded }} / {{ progress.total }} <span class="text-[10px] text-slate-400 ml-1">({{ progress.percentage }}%)</span>
                            </span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-3 overflow-hidden shadow-inner">
                            <div 
                                class="h-3 rounded-full transition-all duration-500 ease-out"
                                :class="progress.loaded === progress.total ? 'bg-emerald-500' : 'bg-amber-500'"
                                :style="`width: ${progress.percentage}%`"
                            ></div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="localRows.length === 0" class="glass-card rounded-3xl p-16 text-center animate-fade-in-up" style="animation-delay: 50ms">
                <div class="w-20 h-20 bg-emerald-50 rounded-full flex items-center justify-center mx-auto mb-6 text-emerald-500">
                    <i class="fas fa-check-circle text-4xl"></i>
                </div>
                <h3 class="text-xl font-bold text-slate-600">No hay aplazados</h3>
                <p class="text-slate-400 mt-2 max-w-sm mx-auto">Todos los estudiantes aprobaron esta materia durante los lapsos regulares.</p>
            </div>

            <template v-else>
                <!-- Lock Warning -->
                <div v-if="isClosed" class="flex items-center gap-4 p-4 bg-slate-50 border-2 border-slate-200 rounded-2xl animate-fade-in-up" style="animation-delay: 100ms">
                    <div class="w-8 h-8 bg-slate-200 text-slate-500 rounded-xl flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-lock"></i>
                    </div>
                    <p class="text-xs font-bold text-slate-500 leading-relaxed">
                        El año escolar está cerrado. Los cambios en las notas de revisión no están permitidos.
                    </p>
                </div>

                <!-- Buscador y Ordenamiento -->
                <div class="flex flex-col sm:flex-row items-center gap-3 w-full animate-fade-in-up" style="animation-delay: 150ms">
                    <div class="relative w-full flex-1">
                        <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input 
                            v-model="searchQuery" 
                            type="text" 
                            placeholder="Buscar estudiante por nombre o cédula..." 
                            class="w-full bg-white border-2 border-slate-100 rounded-2xl pl-11 pr-4 py-3 text-sm text-slate-700 placeholder:text-slate-400 focus:border-red-400 focus:ring-0 outline-none transition-all shadow-sm"
                        >
                    </div>
                    <div class="w-full sm:w-auto relative">
                        <select v-model="sortOrder" class="w-full sm:w-56 bg-white border-2 border-slate-100 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-red-400 focus:ring-0 outline-none transition-all shadow-sm appearance-none cursor-pointer">
                            <option value="name">Ordenar por Apellido</option>
                            <option value="cedula">Ordenar por Cédula</option>
                        </select>
                        <i class="fas fa-sort absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                    </div>
                </div>

                <!-- Grades List (Card Based) -->
                <div class="space-y-3 animate-fade-in-up" style="animation-delay: 200ms">
                    <div v-if="filteredRows.length === 0" class="p-10 bg-white rounded-3xl shadow-sm border-2 border-slate-100 text-center text-slate-400 font-bold">
                        <i class="fas fa-search text-3xl mb-3 opacity-20"></i>
                        <p>No se encontraron estudiantes.</p>
                    </div>

                    <div 
                        v-for="(row, idx) in filteredRows" 
                        :key="row.enrollment_id"
                        class="bg-white p-4 sm:p-5 rounded-2xl shadow-sm border-2 transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-4"
                        :class="[getScoreColor(row.score), isClosed ? 'opacity-75 grayscale' : 'hover:shadow-md hover:border-slate-300']"
                    >
                        <!-- Info Estudiante -->
                        <div class="flex items-center gap-4 flex-1">
                            <div class="w-8 h-8 rounded-full bg-white/50 text-slate-500 flex items-center justify-center font-black text-[10px] shrink-0 shadow-sm border border-slate-200">
                                {{ idx + 1 }}
                            </div>
                            <div class="min-w-0">
                                <h3 class="text-sm font-black text-slate-800 truncate">{{ row.student_name }}</h3>
                                <p class="text-xs font-bold text-slate-500 mt-0.5 flex items-center gap-1.5">
                                    <i class="fas fa-id-card opacity-50"></i> {{ row.cedula || 'Sin Cédula' }}
                                </p>
                            </div>
                        </div>
                        
                        <!-- Status Badge (Aprobado/Reprobado) -->
                        <div class="hidden sm:flex w-24 justify-center">
                            <span v-if="row.status === 'approved'" class="text-[10px] font-black uppercase tracking-widest text-emerald-500 bg-emerald-100 px-2 py-1 rounded-md">Aprobado</span>
                            <span v-else-if="row.status === 'failed'" class="text-[10px] font-black uppercase tracking-widest text-red-500 bg-red-100 px-2 py-1 rounded-md">Reprobado</span>
                            <span v-else class="text-[10px] font-black uppercase tracking-widest text-slate-400 bg-slate-100 px-2 py-1 rounded-md">Pendiente</span>
                        </div>

                        <!-- Controles de Nota -->
                        <div class="flex items-center justify-between sm:justify-end gap-3 w-full sm:w-auto bg-white/50 p-2 rounded-xl">
                            
                            <!-- Mobile Status Badge (shows only on small screens) -->
                            <div class="sm:hidden flex w-full justify-center">
                                <span v-if="row.status === 'approved'" class="text-[10px] font-black uppercase tracking-widest text-emerald-500 bg-emerald-100 px-2 py-1 rounded-md">Aprobado</span>
                                <span v-else-if="row.status === 'failed'" class="text-[10px] font-black uppercase tracking-widest text-red-500 bg-red-100 px-2 py-1 rounded-md">Reprobado</span>
                                <span v-else class="text-[10px] font-black uppercase tracking-widest text-slate-400 bg-slate-100 px-2 py-1 rounded-md">Pendiente</span>
                            </div>

                            <!-- Status Indicator (Spinner/Check) -->
                            <div class="w-6 flex justify-center shrink-0">
                                <i v-if="row.saving" class="fas fa-spinner fa-spin text-slate-400 text-sm"></i>
                                <i v-else-if="row.saved" class="fas fa-check-circle text-emerald-500 text-sm animate-bounce"></i>
                            </div>

                            <!-- Decrement -->
                            <button 
                                @click="decrementScore(row)"
                                :disabled="isClosed"
                                class="w-10 h-10 rounded-xl bg-white shadow-sm border-b-2 flex items-center justify-center text-lg transition-all group active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed"
                                :class="[row.score > 1 ? 'border-slate-200 hover:border-slate-300' : 'border-slate-100 opacity-50']"
                            >
                                <i class="fas fa-minus" :class="getIconColor(row.score)"></i>
                            </button>

                            <!-- Input Note -->
                            <div class="relative w-16 h-12">
                                <input 
                                    v-model="row.score"
                                    @change="validateAndSave(row)"
                                    @blur="validateAndSave(row)"
                                    :disabled="isClosed"
                                    type="number" 
                                    min="1" 
                                    max="20"
                                    class="w-full h-full bg-white border-2 border-slate-200 rounded-xl text-center text-lg font-black text-slate-800 focus:border-red-400 focus:ring-0 outline-none transition-all shadow-sm disabled:bg-slate-50 disabled:text-slate-500"
                                    placeholder="--"
                                >
                            </div>

                            <!-- Increment -->
                            <button 
                                @click="incrementScore(row)"
                                :disabled="isClosed"
                                class="w-10 h-10 rounded-xl bg-white shadow-sm border-b-2 flex items-center justify-center text-lg transition-all group active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed"
                                :class="[row.score < 20 ? 'border-slate-200 hover:border-slate-300' : 'border-slate-100 opacity-50']"
                            >
                                <i class="fas fa-plus" :class="getIconColor(row.score)"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </template>
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