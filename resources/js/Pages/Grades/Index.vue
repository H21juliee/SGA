<script setup>
import { ref, computed } from 'vue'
import AppLayout from '@/Components/Layout/AppLayout.vue'
import { Link, router, usePage } from '@inertiajs/vue3'

const page = usePage()
const userRoles = computed(() => page.props.auth?.roles ?? [])
const isAdmin = computed(() => userRoles.value.includes('SuperAdmin') || userRoles.value.includes('Administrador'))

const props = defineProps({
    loads: { type: Array, default: () => [] },
    activeYear: { type: Object, default: null },
    lapses: { type: Array, default: () => [] },
    schoolYears: { type: Array, default: () => [] },
    openLapseId: { type: Number, default: null },
})

const selectedYearId = ref(props.activeYear?.id)
const gradeLevelId = ref('')
const sectionName = ref('')
const statusFilter = ref('') // '', 'pending', 'complete', 'empty'

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

const statsSummary = computed(() => {
    const total = props.loads.length
    let complete = 0
    let partial = 0
    let empty = 0

    props.loads.forEach(l => {
        if (l.status === 'complete') complete++
        else if (l.status === 'partial') partial++
        else empty++
    })

    return { total, complete, partial, empty }
})

const filteredLoads = computed(() => {
    let result = props.loads
    
    if (gradeLevelId.value) {
        result = result.filter(load => load.section?.grade_level?.id == gradeLevelId.value)
    }
    
    if (sectionName.value) {
        result = result.filter(load => load.section?.name === sectionName.value)
    }

    if (statusFilter.value === 'pending') {
        result = result.filter(load => load.status !== 'complete')
    } else if (statusFilter.value === 'complete') {
        result = result.filter(load => load.status === 'complete')
    } else if (statusFilter.value === 'empty') {
        result = result.filter(load => load.status === 'empty')
    } else if (statusFilter.value === 'partial') {
        result = result.filter(load => load.status === 'partial')
    }
    
    return result
})

function changeYear() {
    router.get('/grades', { school_year_id: selectedYearId.value })
}
</script>

<template>
    <AppLayout title="Gestión de Notas">
        <div class="space-y-8 max-w-7xl mx-auto pb-10">
            <!-- Header Section -->
            <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-6 animate-fade-in-up">
                <div>
                    <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">
                        Gestión de <span class="gradient-text">Notas</span>
                    </h1>
                    <p class="text-slate-400 font-medium mt-2">Supervisión y registro de calificaciones por asignatura</p>
                </div>

                <div class="flex flex-wrap items-end gap-3">
                    <!-- Año Escolar Selector -->
                    <div class="flex flex-col gap-1.5 min-w-[180px]">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Año Escolar</label>
                        <div class="relative">
                            <select 
                                v-model="selectedYearId" 
                                @change="changeYear"
                                class="w-full bg-white border-2 border-slate-100 rounded-2xl px-4 py-2.5 text-slate-700 text-xs font-bold focus:border-primary-400 focus:ring-0 outline-none transition-all appearance-none cursor-pointer shadow-sm"
                            >
                                <option v-for="year in schoolYears" :key="year.id" :value="year.id">
                                    {{ year.name }} {{ year.is_active ? '— (Año Actual)' : '' }}
                                </option>
                            </select>
                            <i class="fas fa-chevron-down absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none text-xs"></i>
                        </div>
                    </div>

                    <!-- Año (Nivel) Selector -->
                    <div v-if="activeYear && loads.length > 0" class="flex flex-col gap-1.5 min-w-[150px]">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Año / Grado</label>
                        <div class="relative">
                            <select 
                                v-model="gradeLevelId" 
                                class="w-full bg-white border-2 border-slate-100 rounded-2xl px-4 py-2.5 text-slate-700 text-xs font-bold focus:border-primary-400 focus:ring-0 outline-none transition-all appearance-none cursor-pointer shadow-sm"
                            >
                                <option value="">Todos los Años</option>
                                <option v-for="lvl in gradeLevels" :key="lvl.id" :value="lvl.id">{{ lvl.name }}</option>
                            </select>
                            <i class="fas fa-filter absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none text-xs"></i>
                        </div>
                    </div>

                    <!-- Sección Selector -->
                    <div v-if="activeYear && loads.length > 0" class="flex flex-col gap-1.5 min-w-[120px]">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Sección</label>
                        <div class="relative">
                            <select 
                                v-model="sectionName" 
                                class="w-full bg-white border-2 border-slate-100 rounded-2xl px-4 py-2.5 text-slate-700 text-xs font-bold focus:border-primary-400 focus:ring-0 outline-none transition-all appearance-none cursor-pointer shadow-sm"
                            >
                                <option value="">Todas</option>
                                <option v-for="sec in sections" :key="sec" :value="sec">Sección {{ sec }}</option>
                            </select>
                            <i class="fas fa-chevron-down absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none text-xs"></i>
                        </div>
                    </div>

                    <!-- Filtro por Estado de Carga -->
                    <div v-if="activeYear && loads.length > 0" class="flex flex-col gap-1.5 min-w-[170px]">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Estado de Carga</label>
                        <div class="relative">
                            <select 
                                v-model="statusFilter" 
                                class="w-full bg-white border-2 border-slate-100 rounded-2xl px-4 py-2.5 text-slate-700 text-xs font-bold focus:border-primary-400 focus:ring-0 outline-none transition-all appearance-none cursor-pointer shadow-sm"
                            >
                                <option value="">Todos los Estados</option>
                                <option value="pending">⚠️ Incompletas / Pendientes</option>
                                <option value="complete">✅ Completadas (100%)</option>
                                <option value="partial">🔄 En Progreso (1-99%)</option>
                                <option value="empty">⭕ Sin Iniciar (0%)</option>
                            </select>
                            <i class="fas fa-tasks absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none text-xs"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Summary Bar (for Admin / Teachers) -->
            <div v-if="activeYear && loads.length > 0" class="grid grid-cols-2 sm:grid-cols-4 gap-4 animate-fade-in-up" style="animation-delay: 100ms">
                <div 
                    @click="statusFilter = ''" 
                    class="p-4 rounded-2xl bg-white border border-slate-100 shadow-sm cursor-pointer hover:border-slate-300 transition-all"
                    :class="{ 'ring-2 ring-primary-500 bg-primary-50/20': statusFilter === '' }"
                >
                    <div class="text-[10px] font-black uppercase text-slate-400 tracking-wider">Total Materias</div>
                    <div class="text-2xl font-black text-slate-800 mt-1">{{ statsSummary.total }}</div>
                </div>

                <div 
                    @click="statusFilter = 'complete'" 
                    class="p-4 rounded-2xl bg-white border border-slate-100 shadow-sm cursor-pointer hover:border-emerald-300 transition-all"
                    :class="{ 'ring-2 ring-emerald-500 bg-emerald-50/20': statusFilter === 'complete' }"
                >
                    <div class="text-[10px] font-black uppercase text-emerald-600 tracking-wider flex items-center gap-1.5">
                        <i class="fas fa-check-circle text-xs"></i> Completadas
                    </div>
                    <div class="text-2xl font-black text-emerald-700 mt-1">{{ statsSummary.complete }}</div>
                </div>

                <div 
                    @click="statusFilter = 'partial'" 
                    class="p-4 rounded-2xl bg-white border border-slate-100 shadow-sm cursor-pointer hover:border-amber-300 transition-all"
                    :class="{ 'ring-2 ring-amber-500 bg-amber-50/20': statusFilter === 'partial' }"
                >
                    <div class="text-[10px] font-black uppercase text-amber-600 tracking-wider flex items-center gap-1.5">
                        <i class="fas fa-spinner fa-spin text-xs"></i> En Progreso
                    </div>
                    <div class="text-2xl font-black text-amber-700 mt-1">{{ statsSummary.partial }}</div>
                </div>

                <div 
                    @click="statusFilter = 'empty'" 
                    class="p-4 rounded-2xl bg-white border border-slate-100 shadow-sm cursor-pointer hover:border-red-300 transition-all"
                    :class="{ 'ring-2 ring-red-500 bg-red-50/20': statusFilter === 'empty' }"
                >
                    <div class="text-[10px] font-black uppercase text-red-500 tracking-wider flex items-center gap-1.5">
                        <i class="fas fa-clock text-xs"></i> Sin Iniciar
                    </div>
                    <div class="text-2xl font-black text-red-600 mt-1">{{ statsSummary.empty }}</div>
                </div>
            </div>

            <!-- Status Warnings -->
            <div v-if="!activeYear" class="glass-card rounded-2xl bg-amber-500/5 border border-amber-500/20 p-6 animate-fade-in-up">
                <div class="flex items-center gap-4 text-amber-600">
                    <i class="fas fa-exclamation-circle text-xl"></i>
                    <p class="font-bold">No hay un año escolar activo configurado para este periodo.</p>
                </div>
            </div>

            <div v-else-if="loads.length === 0" class="glass-card rounded-3xl p-16 text-center animate-fade-in-up">
                <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-6 text-slate-200">
                    <i class="fas fa-book-open text-4xl"></i>
                </div>
                <h3 class="text-xl font-bold text-slate-600">Sin carga académica</h3>
                <p class="text-slate-400 mt-2 max-w-sm mx-auto">No hay asignaturas asignadas en el año escolar seleccionado.</p>
            </div>

            <!-- Empty Filter Results -->
            <div v-else-if="filteredLoads.length === 0" class="glass-card rounded-3xl p-16 text-center animate-fade-in-up">
                <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-6 text-slate-200">
                    <i class="fas fa-search text-4xl"></i>
                </div>
                <h3 class="text-xl font-bold text-slate-600">No se encontraron materias con el filtro actual</h3>
                <p class="text-slate-400 mt-2 max-w-sm mx-auto">Intenta cambiar o limpiar los filtros seleccionados arriba.</p>
                <button @click="gradeLevelId = ''; sectionName = ''; statusFilter = ''" class="mt-4 px-4 py-2 bg-primary-50 text-primary-600 font-bold text-xs rounded-xl hover:bg-primary-100 transition-colors">
                    Limpiar Filtros
                </button>
            </div>

            <!-- Loads Grid -->
            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div
                    v-for="(load, index) in filteredLoads"
                    :key="load.id"
                    class="glass-card rounded-3xl p-6 shadow-xl relative overflow-hidden group hover:-translate-y-1 transition-all duration-300 animate-fade-in-up flex flex-col justify-between"
                    :style="{ animationDelay: `${(index + 1) * 40}ms` }"
                >
                    <!-- Top accent line -->
                    <div 
                        class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r"
                        :class="load.status === 'complete' 
                            ? 'from-emerald-500 to-teal-400' 
                            : (load.status === 'partial' ? 'from-amber-400 to-orange-400' : 'from-slate-200 to-slate-300')"
                    ></div>

                    <div>
                        <!-- Section Badge & Students count -->
                        <div class="flex items-center justify-between gap-2 mb-3">
                            <div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-primary-50 text-primary-700 rounded-lg text-[10px] font-black uppercase tracking-widest border border-primary-100 shadow-sm">
                                <i class="fas fa-users opacity-50"></i>
                                {{ load.section?.grade_level?.name }} — Sec. {{ load.section?.name }}
                            </div>
                            <span class="text-[11px] font-bold text-slate-400">
                                {{ load.students_count }} Estudiantes
                            </span>
                        </div>

                        <!-- Subject Name & Code -->
                        <div class="flex items-start justify-between gap-2 mb-2">
                            <h3 class="text-lg font-black text-slate-800 group-hover:text-primary-600 transition-colors leading-snug">
                                {{ load.subject?.name }}
                            </h3>
                            <span class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-500 font-bold text-[10px] shrink-0">
                                {{ load.subject?.code }}
                            </span>
                        </div>

                        <!-- Teacher Info (if Admin) -->
                        <div v-if="isAdmin && load.teacher" class="flex items-center gap-2 mt-1 mb-4 text-xs font-semibold text-slate-500">
                            <i class="fas fa-chalkboard-teacher text-slate-400 text-[11px]"></i>
                            <span class="truncate">{{ load.teacher.name }}</span>
                        </div>

                        <!-- Active Lapse Progress Bar -->
                        <div class="my-4 p-3.5 rounded-2xl bg-slate-50/80 border border-slate-100">
                            <div class="flex items-center justify-between text-xs font-bold mb-1.5">
                                <span class="text-slate-500 text-[11px]">Avance Lapso Activo:</span>
                                <span 
                                    class="font-black text-[11px]"
                                    :class="load.status === 'complete' 
                                        ? 'text-emerald-600' 
                                        : (load.status === 'partial' ? 'text-amber-600' : 'text-slate-400')"
                                >
                                    {{ load.active_lapse_loaded }} / {{ load.students_count }} ({{ load.active_lapse_percentage }}%)
                                </span>
                            </div>
                            <div class="w-full bg-slate-200 rounded-full h-2.5 overflow-hidden">
                                <div
                                    class="h-full rounded-full transition-all duration-500"
                                    :class="load.status === 'complete' 
                                        ? 'bg-emerald-500' 
                                        : (load.status === 'partial' ? 'bg-amber-500' : 'bg-slate-300')"
                                    :style="{ width: `${Math.min(load.active_lapse_percentage, 100)}%` }"
                                ></div>
                            </div>
                        </div>
                    </div>

                    <!-- Lapses Selection Buttons -->
                    <div class="space-y-2 mt-2 pt-3 border-t border-slate-100">
                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em]">Seleccionar Lapso</p>
                        <div class="grid grid-cols-3 gap-2">
                            <Link
                                v-for="lapse in lapses"
                                :key="lapse.id"
                                :href="`/grades/${load.section_id}/${load.subject_id}/${lapse.id}`"
                                class="flex flex-col items-center py-2.5 px-1 rounded-xl border-2 transition-all duration-200 group/btn relative overflow-hidden"
                                :class="lapse.is_open
                                    ? 'border-emerald-200 bg-emerald-50/40 text-emerald-800 hover:border-emerald-400 hover:bg-emerald-50 shadow-sm'
                                    : 'border-slate-100 bg-slate-50/60 text-slate-400 hover:border-slate-300'"
                            >
                                <span class="text-[11px] font-black uppercase tracking-tight truncate w-full text-center">{{ lapse.name }}</span>
                                
                                <div class="mt-1 flex items-center gap-1">
                                    <span class="text-[9px] font-bold text-slate-500">
                                        {{ load.lapses_progress?.[lapse.id]?.loaded ?? 0 }}/{{ load.students_count }}
                                    </span>
                                </div>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
