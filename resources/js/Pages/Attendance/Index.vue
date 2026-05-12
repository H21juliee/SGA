<script setup>
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'

const props = defineProps({
    sections: Array,
    activeYear: Object,
    schoolYears: { type: Array, default: () => [] },
})

const gradeLevelId = ref('')
const selectedYearId = ref(props.activeYear?.id)

const levels = computed(() => {
    const uniqueLevels = []
    const levelIds = new Set()
    
    props.sections.forEach(s => {
        if (s.grade_level && !levelIds.has(s.grade_level.id)) {
            uniqueLevels.push(s.grade_level)
            levelIds.add(s.grade_level.id)
        }
    })
    
    return uniqueLevels.sort((a, b) => a.order_num - b.order_num)
})

const activeLapse = computed(() => props.activeYear?.lapses?.find(l => l.status === 'open') || props.activeYear?.lapses?.[0])

const filteredSections = computed(() => {
    let result = props.sections
    if (gradeLevelId.value) {
        result = result.filter(s => s.grade_level?.id == gradeLevelId.value)
    }
    return result.sort((a, b) => a.name.localeCompare(b.name))
})

function changeYear() {
    router.get('/attendance', { school_year_id: selectedYearId.value })
}
</script>

<template>
    <AppLayout title="Asistencia">
        <div class="space-y-8">
            <!-- Header Section -->
            <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-6 animate-fade-in-up">
                <div>
                    <h1 class="text-3xl font-extrabold text-slate-800">
                        Pase de <span class="gradient-text">Asistencia</span>
                    </h1>
                    <p class="text-slate-400 font-medium mt-2">Seleccione una sección para registrar la asistencia diaria</p>
                </div>

                <div class="flex flex-wrap items-end gap-4">
                    <!-- Year Selector -->
                    <div class="flex flex-col gap-2 min-w-[200px]">
                        <label class="text-[11px] font-bold text-slate-400 uppercase tracking-widest ml-1">Año Escolar</label>
                        <div class="relative">
                            <select 
                                v-model="selectedYearId" 
                                @change="changeYear"
                                class="w-full bg-white border-2 border-slate-100 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-emerald-400 focus:ring-0 outline-none transition-all appearance-none cursor-pointer shadow-sm"
                            >
                                <option v-for="year in schoolYears" :key="year.id" :value="year.id">
                                    {{ year.name }} {{ year.is_active ? '— (Año Actual)' : '' }}
                                </option>
                            </select>
                            <i class="fas fa-calendar-alt absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                        </div>
                    </div>

                    <!-- Level Selector -->
                    <div class="flex flex-col gap-2 min-w-[200px]">
                        <label class="text-[11px] font-bold text-slate-400 uppercase tracking-widest ml-1">Filtrar por Nivel</label>
                        <div class="relative">
                            <select 
                                v-model="gradeLevelId" 
                                class="w-full bg-white border-2 border-slate-100 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-emerald-400 focus:ring-0 outline-none transition-all appearance-none cursor-pointer shadow-sm"
                            >
                                <option value="">Todos los Niveles</option>
                                <option v-for="lvl in levels" :key="lvl.id" :value="lvl.id">{{ lvl.name }}</option>
                            </select>
                            <i class="fas fa-filter absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Warnings -->
            <div v-if="!activeYear" class="glass-card rounded-2xl bg-amber-500/5 border border-amber-500/20 p-6 animate-fade-in-up">
                <div class="flex items-center gap-4 text-amber-600">
                    <i class="fas fa-exclamation-circle text-xl"></i>
                    <p class="font-bold">No hay un año escolar activo configurado.</p>
                </div>
            </div>

            <!-- Sections Grid -->
            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div
                    v-for="(item, index) in filteredSections"
                    :key="index"
                    class="glass-card rounded-3xl p-6 shadow-xl relative overflow-hidden group hover:-translate-y-1 transition-all duration-300 animate-fade-in-up"
                    :style="{ animationDelay: `${(index + 1) * 50}ms` }"
                >
                    <!-- Accent Line -->
                    <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-emerald-400 to-teal-400 opacity-20 group-hover:opacity-100 transition-opacity"></div>

                    <div class="flex items-start justify-between mb-4">
                        <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center text-xl shadow-sm group-hover:scale-110 transition-transform duration-300">
                            <i class="fas fa-clipboard-user"></i>
                        </div>
                        <span class="px-2.5 py-1 rounded-lg bg-slate-50 text-slate-400 text-[10px] font-black uppercase tracking-wider border border-slate-100">
                            {{ item.grade_level?.name }}
                        </span>
                    </div>
                    
                    <h3 class="text-lg font-black text-slate-800 group-hover:text-emerald-600 transition-colors leading-tight">
                        {{ item.subject.name }}
                    </h3>
                    <p class="text-sm font-bold text-slate-400 mt-1 uppercase tracking-widest">Sección {{ item.name }}</p>
                    
                    <div class="h-[1px] bg-slate-100 w-full my-6"></div>

                    <div class="flex items-center gap-3 mt-auto">
                        <Link
                            :href="`/attendance/${item.id}?subject_id=${item.subject.id}`"
                            class="flex-1 flex items-center justify-center gap-2 py-3.5 bg-emerald-600 text-white text-[11px] font-black uppercase tracking-widest rounded-2xl shadow-lg shadow-emerald-600/20 hover:bg-emerald-500 hover:-translate-y-0.5 transition-all"
                        >
                            <i class="fas fa-check-double text-xs"></i>
                            Pasar Lista
                        </Link>
                        <Link
                            v-if="activeLapse"
                            :href="`/attendance/history/${item.id}/${item.subject.id}/${activeLapse.id}`"
                            class="w-12 h-12 flex items-center justify-center bg-slate-50 text-slate-400 hover:text-primary-600 hover:bg-primary-50 rounded-2xl border border-slate-100 transition-all"
                            title="Ver Historial"
                        >
                            <i class="fas fa-history"></i>
                        </Link>
                    </div>
                </div>
            </div>
            
            <!-- Empty State -->
            <div v-if="activeYear && filteredSections.length === 0" class="glass-card rounded-3xl p-16 text-center animate-fade-in-up">
                <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-6 text-slate-200">
                    <i class="fas fa-search text-4xl"></i>
                </div>
                <h3 class="text-xl font-bold text-slate-600">No se encontraron resultados</h3>
                <p class="text-slate-400 mt-2">Intenta ajustar los filtros de nivel o año escolar.</p>
            </div>
        </div>
    </AppLayout>
</template>
