<script setup>
import { ref, computed, watch } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'

const props = defineProps({
    section: Object,
    subject: Object,
    lapse: Object,
    enrollments: Array,
})

const searchQuery = ref('')
const sortOrder = ref('name')
let debounceTimer = null

// Initialize local state for immediate visual feedback
const localRows = ref(
    props.enrollments.map(enrollment => {
        const grade = enrollment.grades?.[0]
        return {
            enrollment_id: enrollment.id,
            student_name: `${enrollment.student.last_name}, ${enrollment.student.first_name}`,
            cedula: enrollment.student.cedula,
            score: grade?.score ?? null,
            saving: false,
            saved: false
        }
    })
)

watch(() => props.enrollments, (newVal) => {
    // Sync external changes (if any) while avoiding overwriting local typing
    newVal.forEach(enrollment => {
        const grade = enrollment.grades?.[0]
        const local = localRows.value.find(r => r.enrollment_id === enrollment.id)
        if (local && !local.saving) {
            local.score = grade?.score ?? null
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
    if (!props.lapse.is_open) return
    let current = parseInt(row.score)
    if (isNaN(current)) current = 0
    if (current < 20) {
        row.score = current + 1
        triggerSave(row)
    }
}

function decrementScore(row) {
    if (!props.lapse.is_open) return
    let current = parseInt(row.score)
    if (isNaN(current)) current = 0
    if (current > 1) {
        row.score = current - 1
        triggerSave(row)
    }
}

function validateAndSave(row) {
    if (!props.lapse.is_open) return
    let s = parseInt(row.score)
    if (isNaN(s) || row.score === '') {
        row.score = null
    } else {
        if (s > 20) row.score = 20
        if (s < 1) row.score = 1
    }
    triggerSave(row)
}

function triggerSave(row) {
    if (!props.lapse.is_open) return
    
    row.saving = true
    row.saved = false
    
    if (debounceTimer) clearTimeout(debounceTimer)
    
    debounceTimer = setTimeout(() => {
        saveToServer(row)
    }, 500) // 500ms debounce
}

function saveToServer(row) {
    router.patch('/grades', {
        enrollment_id: row.enrollment_id,
        subject_id: props.subject.id,
        lapse_id: props.lapse.id,
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
    <AppLayout :title="`Notas — ${subject.name}`">
        <div class="space-y-6 max-w-7xl mx-auto pb-10">
            <!-- Header & Breadcrumbs -->
            <div class="animate-fade-in-up">
                <div class="flex items-center gap-4 mb-4">
                    <Link href="/grades" class="px-4 py-2 rounded-xl bg-white border-2 border-slate-100 text-slate-500 font-bold text-xs uppercase tracking-wider hover:bg-slate-50 hover:text-primary-600 hover:border-primary-200 transition-all shadow-sm flex items-center gap-2 group">
                        <i class="fas fa-arrow-left group-hover:-translate-x-1 transition-transform"></i> Volver
                    </Link>

                    <nav class="flex items-center gap-3 text-[10px] font-black uppercase tracking-widest text-slate-400 hidden sm:flex">
                        <Link href="/grades" class="hover:text-primary-600 transition-colors">Notas</Link>
                        <i class="fas fa-chevron-right text-[8px]"></i>
                        <span class="text-slate-600">{{ subject.name }}</span>
                    </nav>
                </div>

                <div class="glass-card rounded-3xl p-5 sm:p-8 shadow-xl relative overflow-hidden">
                    <div class="absolute top-0 right-0 p-8 opacity-5 pointer-events-none hidden sm:block">
                        <i class="fas fa-star text-8xl text-primary-600"></i>
                    </div>

                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 relative z-10">
                        <div>
                            <div class="flex flex-wrap items-center gap-3 mb-2">
                                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-800">{{ subject.name }}</h1>
                                <span class="px-2.5 py-1 bg-primary-50 text-primary-600 text-[10px] font-black rounded-lg uppercase tracking-wider border border-primary-100">
                                    {{ lapse.name }}
                                </span>
                            </div>
                            <p class="text-slate-500 font-bold uppercase tracking-widest text-xs">
                                {{ section.grade_level?.name }} — Sección {{ section.name }}
                            </p>
                        </div>
                        
                        <div class="flex items-center gap-3 w-full sm:w-auto">
                            <div
                                class="flex w-full items-center justify-center gap-3 px-5 py-3.5 rounded-2xl shadow-sm border"
                                :class="lapse.is_open
                                    ? 'bg-emerald-50 border-emerald-100 text-emerald-700'
                                    : 'bg-red-50 border-red-100 text-red-700'"
                            >
                                <div
                                    class="w-3 h-3 rounded-full animate-pulse-slow"
                                    :class="lapse.is_open ? 'bg-emerald-500 shadow-lg shadow-emerald-500/50' : 'bg-red-500 shadow-lg shadow-red-500/50'"
                                ></div>
                                <span class="text-xs font-black uppercase tracking-widest">
                                    {{ lapse.is_open ? 'Lapso Abierto' : 'Solo Lectura' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="mt-6 pt-6 border-t border-slate-100">
                        <div class="flex justify-between items-end mb-2">
                            <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">Progreso de Carga</span>
                            <span class="text-sm font-black" :class="progress.loaded === progress.total ? 'text-emerald-500' : 'text-primary-500'">
                                {{ progress.loaded }} / {{ progress.total }} <span class="text-[10px] text-slate-400 ml-1">({{ progress.percentage }}%)</span>
                            </span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-3 overflow-hidden shadow-inner">
                            <div 
                                class="h-3 rounded-full transition-all duration-500 ease-out"
                                :class="progress.loaded === progress.total ? 'bg-emerald-500' : 'bg-primary-500'"
                                :style="`width: ${progress.percentage}%`"
                            ></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lock Warning -->
            <div v-if="!lapse.is_open" class="flex items-center gap-4 p-4 bg-amber-50 border-2 border-amber-100 rounded-2xl animate-fade-in-up" style="animation-delay: 100ms">
                <div class="w-8 h-8 bg-amber-100 text-amber-600 rounded-xl flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-lock"></i>
                </div>
                <p class="text-xs font-bold text-amber-800 leading-relaxed">
                    Este lapso ha sido cerrado. Los cambios no están permitidos y la información se presenta en modo histórico.
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
                        class="w-full bg-white border-2 border-slate-100 rounded-2xl pl-11 pr-4 py-3 text-sm text-slate-700 placeholder:text-slate-400 focus:border-primary-400 focus:ring-0 outline-none transition-all shadow-sm"
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
                    :class="[getScoreColor(row.score), !lapse.is_open ? 'opacity-75 grayscale' : 'hover:shadow-md hover:border-slate-300']"
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

                    <!-- Controles de Nota -->
                    <div class="flex items-center justify-between sm:justify-end gap-3 w-full sm:w-auto bg-white/50 p-2 rounded-xl">
                        
                        <!-- Status Indicator -->
                        <div class="w-6 flex justify-center shrink-0">
                            <i v-if="row.saving" class="fas fa-spinner fa-spin text-slate-400 text-sm"></i>
                            <i v-else-if="row.saved" class="fas fa-check-circle text-emerald-500 text-sm animate-bounce"></i>
                        </div>

                        <!-- Decrement -->
                        <button 
                            @click="decrementScore(row)"
                            :disabled="!lapse.is_open"
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
                                :disabled="!lapse.is_open"
                                type="number" 
                                min="1" 
                                max="20"
                                class="w-full h-full bg-white border-2 border-slate-200 rounded-xl text-center text-lg font-black text-slate-800 focus:border-primary-400 focus:ring-0 outline-none transition-all shadow-sm disabled:bg-slate-50 disabled:text-slate-500"
                                placeholder="--"
                            >
                        </div>

                        <!-- Increment -->
                        <button 
                            @click="incrementScore(row)"
                            :disabled="!lapse.is_open"
                            class="w-10 h-10 rounded-xl bg-white shadow-sm border-b-2 flex items-center justify-center text-lg transition-all group active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed"
                            :class="[row.score < 20 ? 'border-slate-200 hover:border-slate-300' : 'border-slate-100 opacity-50']"
                        >
                            <i class="fas fa-plus" :class="getIconColor(row.score)"></i>
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>

<style scoped>
/* Ocultar flechas del input number */
input[type=number]::-webkit-inner-spin-button, 
input[type=number]::-webkit-outer-spin-button { 
  -webkit-appearance: none; 
  margin: 0; 
}
input[type=number] {
  -moz-appearance: textfield;
}
</style>