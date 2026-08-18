<script setup>
import { ref, computed } from 'vue'
import AppLayout from '@/Components/Layout/AppLayout.vue'
import { Link, router } from '@inertiajs/vue3'

const props = defineProps({
    sections: { type: Array, default: () => [] },
    activeYear: { type: Object, default: null },
    levels: { type: Array, default: () => [] },
    enrollments: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
})

const gradeLevelId = ref(props.filters.grade_level_id || '')

// Buscador y ordenamiento
const searchQuery = ref('')
const sortCol = ref(null)
const sortDir = ref('asc')

const processedEnrollments = computed(() => {
    let result = [...props.enrollments]

    // Búsqueda
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase()
        result = result.filter(enrollment => {
            const student = enrollment.student
            const cedula = student?.cedula?.toLowerCase() || ''
            const name = `${student?.last_name || ''}, ${student?.first_name || ''}`.toLowerCase()
            return cedula.includes(query) || name.includes(query)
        })
    }

    // Ordenamiento
    if (sortCol.value) {
        result.sort((a, b) => {
            let valA = sortCol.value === 'cedula' ? a.student?.cedula : `${a.student?.last_name}, ${a.student?.first_name}`
            let valB = sortCol.value === 'cedula' ? b.student?.cedula : `${b.student?.last_name}, ${b.student?.first_name}`

            // Manejo de nulos
            if (!valA) valA = ''
            if (!valB) valB = ''

            if (typeof valA === 'string') valA = valA.toLowerCase()
            if (typeof valB === 'string') valB = valB.toLowerCase()

            if (valA < valB) return sortDir.value === 'asc' ? -1 : 1
            if (valA > valB) return sortDir.value === 'asc' ? 1 : -1
            return 0
        })
    }

    return result
})

function toggleSort(col) {
    if (sortCol.value === col) {
        if (sortDir.value === 'asc') sortDir.value = 'desc'
        else { sortCol.value = null; sortDir.value = 'asc' }
    } else {
        sortCol.value = col
        sortDir.value = 'asc'
    }
}

function loadFiltered() {
    router.get('/reports', {
        grade_level_id: gradeLevelId.value,
    }, { preserveState: true })
}

function printSabana(sectionId, lapseId) {
    window.open(`/reports/print-sabana/${sectionId}/${lapseId}`, '_blank')
}

function printStudents(sectionId) {
    const yearId = props.activeYear.id
    const url = `/reports/print-students/${yearId}/${sectionId}`
    window.open(url, '_blank')
}

function selectSection(id) {
    // Resetear filtros al cambiar de vista
    searchQuery.value = ''
    sortCol.value = null
    sortDir.value = 'asc'

    router.get('/reports', { 
        grade_level_id: gradeLevelId.value,
        section_id: id 
    }, { preserveState: true })
}

function download(enrollmentId) {
    window.open(`/reports/download/${enrollmentId}`, '_blank')
}

function downloadBatch() {
    window.open(`/reports/download-batch/${props.filters.section_id}`, '_blank')
}
</script>

<template>
    <AppLayout title="Reportes">
        <div class="space-y-8 max-w-12xl mx-auto">
            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row gap-6 items-center justify-between animate-fade-in-up">
                <div>
                    <h1 class="text-3xl font-extrabold text-slate-800">
                        Reportes y <span class="gradient-text">Boletas</span>
                    </h1>
                    <p class="text-slate-400 font-medium mt-2">Descarga boletas de calificaciones oficiales en PDF</p>
                </div>
                
                <div v-if="activeYear && !filters.section_id" class="flex flex-col gap-2 min-w-[200px]">
                    <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Filtrar por Nivel</label>
                    <div class="relative">
                        <select 
                            v-model="gradeLevelId" 
                            @change="loadFiltered" 
                            class="w-full bg-white border-2 border-slate-100 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:ring-0 outline-none transition-all appearance-none cursor-pointer shadow-sm"
                        >
                            <option value="">Todos los Niveles</option>
                            <option v-for="lvl in levels" :key="lvl.id" :value="lvl.id">{{ lvl.name }}</option>
                        </select>
                        <i class="fas fa-filter absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 pointer-events-none"></i>
                    </div>
                </div>
            </div>

            <!-- Status Check -->
            <div v-if="!activeYear" class="glass-card rounded-2xl bg-amber-500/5 border border-amber-500/20 p-6 animate-fade-in-up">
                <div class="flex items-center gap-4 text-amber-600">
                    <i class="fas fa-exclamation-circle text-xl"></i>
                    <p class="font-bold">No hay un año escolar activo configurado.</p>
                </div>
            </div>

            <div v-else class="space-y-8">
                <!-- Secciones Selection Grid -->
                <div v-if="!filters.section_id" class="animate-fade-in-up">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-1.5 h-6 bg-primary-500 rounded-full"></div>
                        <h3 class="text-xl font-black text-slate-800">Secciones Disponibles</h3>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div
                            v-for="(section, index) in sections"
                            :key="section.id"
                            class="glass-card rounded-3xl p-6 shadow-xl relative overflow-hidden group hover:-translate-y-1 transition-all duration-300 animate-fade-in-up"
                            :style="{ animationDelay: `${(index + 1) * 50}ms` }"
                        >
                            <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-primary-400 to-accent-400 opacity-20 group-hover:opacity-100 transition-opacity"></div>
                            
                            <div class="flex items-start justify-between mb-4">
                                <div class="w-12 h-12 bg-primary-50 text-primary-600 rounded-2xl flex items-center justify-center text-xl shadow-sm group-hover:rotate-6 transition-transform">
                                    <i class="fas fa-layer-group"></i>
                                </div>
                                <span class="px-2.5 py-1 rounded-lg bg-slate-50 text-slate-400 text-[10px] font-black uppercase tracking-wider border border-slate-100">
                                    {{ section.grade_level?.name }}
                                </span>
                            </div>

                            <h4 class="text-lg font-black text-slate-800 group-hover:text-primary-600 transition-colors">
                                Sección {{ section.name }}
                            </h4>
                            <p class="text-sm font-bold text-slate-400 mt-1 uppercase tracking-widest">
                                {{ section.grade_level?.name }}
                            </p>

                            <div class="h-[1px] bg-slate-100 w-full my-4"></div>
                            
                            <h5 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 text-center">Sábanas de Notas</h5>
                            <div class="flex gap-2 mb-4">
                                <button 
                                    v-for="lapse in activeYear.lapses" 
                                    :key="lapse.id"
                                    @click="printSabana(section.id, lapse.id)"
                                    class="flex-1 py-2 bg-indigo-50 text-indigo-600 hover:bg-indigo-600 hover:text-white rounded-xl text-[10px] font-bold uppercase transition-colors"
                                >
                                    L{{ lapse.order_num }}
                                </button>
                            </div>

                            <button 
                                @click="selectSection(section.id)"
                                class="w-full flex items-center justify-center gap-2 py-3.5 bg-slate-900 text-white text-[11px] font-black uppercase tracking-widest rounded-2xl shadow-lg hover:bg-slate-800 hover:-translate-y-0.5 transition-all"
                            >
                                <i class="fas fa-users text-xs"></i>
                                Ver Estudiantes
                            </button>
                            <button
                                @click="printStudents(section.id)"
                                class="w-full flex items-center justify-center gap-2 py-3.5 bg-primary-600 text-white text-[11px] font-black uppercase tracking-widest rounded-2xl shadow-md mt-2 hover:bg-primary-700 transition-all"
                            >
                                <i class="fas fa-print text-xs"></i>
                                Lista de Estudiantes
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Students List in Section -->
                <div v-else class="space-y-6 animate-fade-in-up">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <button 
                            @click="selectSection(null)"
                            class="flex items-center gap-3 px-5 py-2.5 bg-white text-slate-400 hover:text-primary-600 font-bold text-sm rounded-2xl border-2 border-slate-50 hover:border-primary-100 transition-all w-fit"
                        >
                            <i class="fas fa-arrow-left text-xs"></i>
                            Volver
                        </button>

                        <div class="flex flex-col sm:flex-row items-center gap-4 w-full md:w-auto">
                            <!-- Buscador -->
                            <div class="relative w-full md:w-64">
                                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                                <input 
                                    v-model="searchQuery" 
                                    type="text" 
                                    placeholder="Buscar por cédula o nombre..." 
                                    class="w-full bg-slate-50 border-2 border-slate-100 rounded-xl pl-9 pr-4 py-2 text-sm text-slate-700 placeholder:text-slate-400 focus:border-primary-400 focus:ring-0 outline-none transition-all shadow-sm"
                                >
                            </div>

                            <!-- Botón Masivo -->
                            <button 
                                @click="downloadBatch"
                                class="w-full sm:w-auto flex items-center justify-center gap-2 px-6 py-2.5 bg-slate-900 text-white font-bold text-xs uppercase tracking-widest rounded-xl hover:bg-slate-800 hover:-translate-y-0.5 transition-all shadow-lg"
                            >
                                <i class="fas fa-file-pdf"></i>
                                Descargar Todas (Masivo)
                            </button>
                        </div>
                    </div>

                    <div class="glass-card rounded-3xl overflow-hidden shadow-2xl">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-slate-50 text-slate-400 text-[10px] uppercase font-black tracking-[0.2em]">
                                <tr>
                                    <th class="px-8 py-5 cursor-pointer hover:bg-slate-100/50 hover:text-slate-600 transition-colors select-none group" @click="toggleSort('name')">
                                        <div class="flex items-center gap-2">
                                            Estudiante
                                            <div class="flex flex-col text-[8px] opacity-30 group-hover:opacity-100 transition-opacity"
                                                 :class="{ 'opacity-100 text-primary-500': sortCol === 'name' }">
                                                <i class="fas fa-chevron-up leading-[0.5]" :class="{ 'text-slate-300': sortCol === 'name' && sortDir === 'desc' }"></i>
                                                <i class="fas fa-chevron-down leading-[0.5]" :class="{ 'text-slate-300': sortCol === 'name' && sortDir === 'asc' }"></i>
                                            </div>
                                        </div>
                                    </th>
                                    <th class="px-8 py-5 cursor-pointer hover:bg-slate-100/50 hover:text-slate-600 transition-colors select-none group" @click="toggleSort('cedula')">
                                        <div class="flex items-center gap-2">
                                            Cédula
                                            <div class="flex flex-col text-[8px] opacity-30 group-hover:opacity-100 transition-opacity"
                                                 :class="{ 'opacity-100 text-primary-500': sortCol === 'cedula' }">
                                                <i class="fas fa-chevron-up leading-[0.5]" :class="{ 'text-slate-300': sortCol === 'cedula' && sortDir === 'desc' }"></i>
                                                <i class="fas fa-chevron-down leading-[0.5]" :class="{ 'text-slate-300': sortCol === 'cedula' && sortDir === 'asc' }"></i>
                                            </div>
                                        </div>
                                    </th>
                                    <th class="px-8 py-5 text-right">Documentos Disponibles</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="enrollment in processedEnrollments" :key="enrollment.id" class="group hover:bg-slate-50 transition-colors">
                                    <td class="px-8 py-4">
                                        <div class="flex items-center gap-4">
                                            <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 font-bold group-hover:bg-primary-50 group-hover:text-primary-500 transition-colors">
                                                {{ enrollment.student?.first_name?.charAt(0) }}
                                            </div>
                                            <div class="font-black text-slate-700 text-base group-hover:text-primary-700 transition-colors">
                                                {{ enrollment.student?.last_name }}, {{ enrollment.student?.first_name }}
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-8 py-4">
                                        <div class="text-sm font-bold text-slate-500 tracking-wider">
                                            {{ enrollment.student?.cedula || '—' }}
                                        </div>
                                    </td>
                                    <td class="px-8 py-4 text-right">
                                        <button 
                                            @click="download(enrollment.id)"
                                            class="inline-flex items-center gap-3 px-6 py-3 bg-primary-50 text-primary-600 hover:bg-primary-600 hover:text-white text-[11px] font-black uppercase tracking-widest rounded-2xl border-2 border-primary-100 transition-all group/btn shadow-sm"
                                        >
                                            <i class="fas fa-file-pdf text-sm"></i>
                                            Descargar Boleta
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        
                        <!-- Empty Section -->
                        <div v-if="processedEnrollments.length === 0" class="p-20 text-center">
                            <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 text-slate-200">
                                <i class="fas fa-search text-2xl"></i>
                            </div>
                            <h3 class="text-lg font-bold text-slate-400">No hay resultados</h3>
                            <p class="text-slate-300 text-sm mt-1">No se encontraron estudiantes con esos datos de búsqueda.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
