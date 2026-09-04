<script setup>
import { ref, computed } from 'vue'
import AppLayout from '@/Components/Layout/AppLayout.vue'
import { Link, router } from '@inertiajs/vue3'

const props = defineProps({
    loads: { type: Array, default: () => [] },
    activeYear: { type: Object, default: null },
    schoolYears: { type: Array, default: () => [] },
    allLapsesClosed: { type: Boolean, default: false },
})

const selectedYearId = ref(props.activeYear?.id)
const gradeLevelId = ref('')
const sectionName = ref('')

const gradeLevels = computed(() => {
    const uniqueLevels = []
    const levelIds = new Set()
    
    props.loads.forEach(load => {
        const lvl = load.section?.grade_level
        if (lvl && !levelIds.has(lvl.id)) {
            uniqueLevels.push(lvl)
            levelIds.add(lvl.id)
        }
    })
    
    return uniqueLevels.sort((a, b) => a.order_num - b.order_num)
})

const sections = computed(() => {
    const uniqueNames = new Set()
    
    props.loads.forEach(load => {
        const name = load.section?.name
        if (name) {
            uniqueNames.add(name)
        }
    })
    
    return Array.from(uniqueNames).sort()
})

const filteredLoads = computed(() => {
    let result = props.loads
    
    if (gradeLevelId.value) {
        result = result.filter(load => load.section?.grade_level?.id == gradeLevelId.value)
    }
    
    if (sectionName.value) {
        result = result.filter(load => load.section?.name === sectionName.value)
    }
    
    return result
})

function changeYear() {
    router.get('/revisions', { school_year_id: selectedYearId.value })
}
</script>

<template>
    <AppLayout title="Revisiones">
        <div class="space-y-8">
            <!-- Header Section -->
            <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-6 animate-fade-in-up">
                <div>
                    <h1 class="text-3xl font-extrabold text-slate-800">
                        Evaluación de <span class="text-red-500">Revisiones</span>
                    </h1>
                    <p class="text-slate-400 font-medium mt-2">Gestione las notas de revisión de estudiantes aplazados</p>
                </div>

                <div class="flex flex-wrap items-end gap-4">
                    <!-- Año Escolar Selector -->
                    <div class="flex flex-col gap-2 min-w-[200px]">
                        <label class="text-[11px] font-bold text-slate-400 uppercase tracking-widest ml-1">Año Escolar</label>
                        <div class="relative">
                            <select 
                                v-model="selectedYearId" 
                                @change="changeYear"
                                class="w-full bg-white border-2 border-slate-100 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-red-400 focus:ring-0 outline-none transition-all appearance-none cursor-pointer shadow-sm"
                            >
                                <option v-for="year in schoolYears" :key="year.id" :value="year.id">
                                    {{ year.name }} {{ year.is_active ? '— (Año Actual)' : '' }}
                                </option>
                            </select>
                            <i class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                        </div>
                    </div>

                    <!-- Año (Nivel) Selector -->
                    <div v-if="activeYear && loads.length > 0" class="flex flex-col gap-2 min-w-[180px]">
                        <label class="text-[11px] font-bold text-slate-400 uppercase tracking-widest ml-1">Filtrar por Año</label>
                        <div class="relative">
                            <select 
                                v-model="gradeLevelId" 
                                class="w-full bg-white border-2 border-slate-100 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-red-400 focus:ring-0 outline-none transition-all appearance-none cursor-pointer shadow-sm"
                            >
                                <option value="">Todos los Años</option>
                                <option v-for="lvl in gradeLevels" :key="lvl.id" :value="lvl.id">{{ lvl.name }}</option>
                            </select>
                            <i class="fas fa-filter absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                        </div>
                    </div>

                    <!-- Sección Selector -->
                    <div v-if="activeYear && loads.length > 0" class="flex flex-col gap-2 min-w-[150px]">
                        <label class="text-[11px] font-bold text-slate-400 uppercase tracking-widest ml-1">Sección</label>
                        <div class="relative">
                            <select 
                                v-model="sectionName" 
                                class="w-full bg-white border-2 border-slate-100 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-red-400 focus:ring-0 outline-none transition-all appearance-none cursor-pointer shadow-sm"
                            >
                                <option value="">Todas</option>
                                <option v-for="sec in sections" :key="sec" :value="sec">Sección {{ sec }}</option>
                            </select>
                            <i class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status Warnings -->
            <div v-if="!activeYear" class="glass-card rounded-2xl bg-amber-500/5 border border-amber-500/20 p-6 animate-fade-in-up">
                <div class="flex items-center gap-4 text-amber-600">
                    <i class="fas fa-exclamation-circle text-xl"></i>
                    <p class="font-bold">No hay un año escolar activo configurado para este periodo.</p>
                </div>
            </div>

            <div v-else-if="!allLapsesClosed" class="glass-card rounded-2xl bg-amber-500/5 border border-amber-500/20 p-6 animate-fade-in-up">
                <div class="flex items-center gap-4 text-amber-600">
                    <i class="fas fa-lock-open text-xl"></i>
                    <p class="font-bold">Las revisiones no están disponibles. Debe cerrar todos los lapsos del año escolar primero.</p>
                </div>
            </div>

            <div v-else-if="loads.length === 0" class="glass-card rounded-3xl p-16 text-center animate-fade-in-up">
                <div class="w-20 h-20 bg-emerald-50 rounded-full flex items-center justify-center mx-auto mb-6 text-emerald-500 shadow-sm border border-emerald-100">
                    <i class="fas fa-check-circle text-4xl"></i>
                </div>
                <h3 class="text-xl font-bold text-slate-700">Sin materias pendientes de revisión</h3>
                <p class="text-slate-400 mt-2 max-w-md mx-auto">No hay estudiantes aplazados que requieran evaluación de revisión en el año escolar seleccionado.</p>
            </div>

            <!-- Empty Filter Results -->
            <div v-else-if="filteredLoads.length === 0" class="glass-card rounded-3xl p-16 text-center animate-fade-in-up">
                <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-6 text-slate-200">
                    <i class="fas fa-search text-4xl"></i>
                </div>
                <h3 class="text-xl font-bold text-slate-600">No se encontraron materias</h3>
                <p class="text-slate-400 mt-2 max-w-sm mx-auto">Intenta ajustar los filtros de año de estudio o sección.</p>
            </div>

            <!-- Loads Grid -->
            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <Link
                    v-for="(load, index) in filteredLoads"
                    :key="load.id"
                    :href="`/revisions/${load.section_id}/${load.subject_id}`"
                    class="glass-card rounded-3xl p-6 shadow-xl relative overflow-hidden group hover:-translate-y-1 transition-all duration-300 animate-fade-in-up border-2 border-transparent hover:border-red-100 block"
                    :style="{ animationDelay: `${(index + 1) * 50}ms` }"
                >
                    <!-- Gradient accent line -->
                    <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-red-400 to-rose-400 opacity-20 group-hover:opacity-100 transition-opacity"></div>

                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-2" v-if="load.failed_students_count">
                                <span class="px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider rounded-lg bg-red-50 text-red-600 border border-red-100 shadow-sm flex items-center gap-1.5">
                                    <i class="fas fa-user-clock text-[9px]"></i>
                                    {{ load.failed_students_count }} {{ load.failed_students_count === 1 ? 'estudiante a revisión' : 'estudiantes a revisión' }}
                                </span>
                            </div>
                            <h3 class="text-lg font-black text-slate-800 group-hover:text-red-600 transition-colors leading-tight">
                                {{ load.subject?.name }}
                            </h3>
                            <p class="text-xs font-bold text-slate-400 mt-1 uppercase tracking-wider">
                                {{ load.section?.grade_level?.name }} · Sección {{ load.section?.name }}
                            </p>
                            <p v-if="load.teacher" class="text-[11px] font-medium text-slate-400 mt-2 flex items-center gap-1.5">
                                <i class="fas fa-chalkboard-teacher text-slate-300"></i>
                                <span>{{ load.teacher.name }}</span>
                            </p>
                        </div>
                        <div class="w-10 h-10 bg-red-50 text-red-600 rounded-xl flex items-center justify-center text-xs font-bold shadow-sm group-hover:bg-red-600 group-hover:text-white transition-all">
                            <i class="fas fa-chevron-right"></i>
                        </div>
                    </div>
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
