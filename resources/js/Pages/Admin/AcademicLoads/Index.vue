<script setup>
import { ref, computed } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'

const props = defineProps({
    loads: Array,
    years: Array,
    levels: Array,
    teachers: Array,
    sections: Array,
    subjects: Array,
    filters: Object,
})

const schoolYearId = ref(props.filters.school_year_id || '')
const gradeLevelId = ref(props.filters.grade_level_id || '')
const sectionId = ref(props.filters.section_id || '')

const form = useForm({
    school_year_id: schoolYearId.value,
    grade_level_id: '',
    teacher_id: '',
    section_id: '',
    subject_id: '',
})

function loadData() {
    router.get('/admin/academic-loads', { 
        school_year_id: schoolYearId.value,
        grade_level_id: gradeLevelId.value,
        section_id: sectionId.value
    }, { preserveState: true })
    form.school_year_id = schoolYearId.value
}

// Secciones filtradas para el encabezado (dependen del nivel seleccionado en el filtro)
const filterSections = computed(() => {
    if (!gradeLevelId.value) return props.sections
    return props.sections.filter(s => s.grade_level_id === gradeLevelId.value)
})

// Filtrar secciones dependiendo del nivel seleccionado en el formulario
const availableSections = computed(() => {
    if (!form.grade_level_id) return []
    return props.sections.filter(s => s.grade_level_id === form.grade_level_id)
})

// Filtrar materias dependiendo del nivel seleccionado en el formulario
const availableSubjects = computed(() => {
    if (!form.grade_level_id) return []
    return props.subjects.filter(s => s.grade_level_id === form.grade_level_id)
})

function submit() {
    form.post('/admin/academic-loads', {
        preserveScroll: true,
        onSuccess: () => {
            form.teacher_id = ''
            form.section_id = ''
            form.subject_id = ''
        }
    })
}

function destroy(id) {
    if(confirm('¿Remover esta asignación?')) {
        router.delete(`/admin/academic-loads/${id}`, { preserveScroll: true })
    }
}
</script>

<template>
    <AppLayout title="Carga Académica">
        <div class="space-y-8 max-w-7xl mx-auto">
            <!-- Header Section -->
            <div class="flex flex-col lg:flex-row gap-6 items-center justify-between animate-fade-in-up">
                <div>
                    <h2 class="text-3xl font-extrabold text-slate-800">
                        Carga <span class="gradient-text">Académica</span>
                    </h2>
                    <p class="text-slate-400 font-medium mt-2">Gestiona la asignación de materias y docentes por sección</p>
                </div>
                
                <div class="flex flex-wrap gap-4 justify-end w-full lg:w-auto">
                    <div class="relative flex-1 sm:min-w-[140px]">
                        <select v-model="schoolYearId" @change="loadData" class="w-full bg-white border-2 border-slate-100 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:ring-0 outline-none transition-all appearance-none cursor-pointer shadow-sm">
                            <option value="">Año Escolar</option>
                            <option v-for="year in years" :key="year.id" :value="year.id">{{ year.name }}</option>
                        </select>
                        <i class="fas fa-calendar absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 pointer-events-none"></i>
                    </div>

                    <div class="relative flex-1 sm:min-w-[160px]">
                        <select v-model="gradeLevelId" @change="sectionId = ''; loadData()" class="w-full bg-white border-2 border-slate-100 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:ring-0 outline-none transition-all appearance-none cursor-pointer shadow-sm">
                            <option value="">Todos los Niveles</option>
                            <option v-for="lvl in levels" :key="lvl.id" :value="lvl.id">{{ lvl.name }}</option>
                        </select>
                        <i class="fas fa-layer-group absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 pointer-events-none"></i>
                    </div>

                    <div class="relative flex-1 sm:min-w-[140px]">
                        <select v-model="sectionId" @change="loadData" class="w-full bg-white border-2 border-slate-100 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:ring-0 outline-none transition-all appearance-none cursor-pointer shadow-sm">
                            <option value="">Sección</option>
                            <option v-for="s in filterSections" :key="s.id" :value="s.id">{{ s.gradeLevel?.name.split(' ')[0] }} {{ s.name }}</option>
                        </select>
                        <i class="fas fa-users absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 pointer-events-none"></i>
                    </div>
                </div>
            </div>

            <!-- Content Area -->
            <div v-if="!schoolYearId" class="glass-card rounded-3xl p-20 text-center animate-fade-in-up">
                <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 text-slate-200">
                    <i class="fas fa-arrow-up text-2xl"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-400">Selecciona un Año Escolar</h3>
                <p class="text-slate-300 text-sm mt-1">Para gestionar las cargas académicas, primero selecciona un periodo académico activo.</p>
            </div>

            <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
                <!-- Quick Assignment Form -->
                <div class="lg:col-span-1 glass-card rounded-3xl p-8 sticky top-24 animate-fade-in-up shadow-xl" style="animation-delay: 100ms">
                    <div class="flex items-center gap-3 mb-8">
                        <div class="w-10 h-10 bg-primary-50 text-primary-600 rounded-xl flex items-center justify-center text-lg shadow-sm">
                            <i class="fas fa-plus-circle"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-black text-slate-800 leading-tight">Nueva Asignación</h3>
                            <p class="text-xs font-medium text-slate-400">Vincula docente, sección y materia</p>
                        </div>
                    </div>

                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="space-y-2">
                            <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Docente</label>
                            <div class="relative">
                                <select v-model="form.teacher_id" class="w-full bg-slate-50 border-2 border-slate-50 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all appearance-none cursor-pointer" required>
                                    <option value="" disabled>Seleccionar docente...</option>
                                    <option v-for="t in teachers" :key="t.id" :value="t.id">{{ t.name }}</option>
                                </select>
                                <i class="fas fa-user-tie absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 pointer-events-none"></i>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Nivel Académico</label>
                            <div class="relative">
                                <select v-model="form.grade_level_id" class="w-full bg-slate-50 border-2 border-slate-50 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all appearance-none cursor-pointer" required @change="form.section_id = ''; form.subject_id = ''">
                                    <option value="" disabled>Seleccionar nivel...</option>
                                    <option v-for="lvl in levels" :key="lvl.id" :value="lvl.id">{{ lvl.name }}</option>
                                </select>
                                <i class="fas fa-graduation-cap absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 pointer-events-none"></i>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-1">
                            <div class="space-y-2">
                                <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Sección</label>
                                <div class="relative">
                                    <select v-model="form.section_id" class="w-full bg-slate-50 border-2 border-slate-50 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all appearance-none cursor-pointer disabled:opacity-50" required :disabled="!form.grade_level_id">
                                        <option value="" disabled>Sección...</option>
                                        <option v-for="s in availableSections" :key="s.id" :value="s.id">{{ s.name }}</option>
                                    </select>
                                    <i class="fas fa-users absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 pointer-events-none"></i>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Materia</label>
                                <div class="relative">
                                    <select v-model="form.subject_id" class="w-full bg-slate-50 border-2 border-slate-50 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all appearance-none cursor-pointer disabled:opacity-50" required :disabled="!form.grade_level_id">
                                        <option value="" disabled>Materia...</option>
                                        <option v-for="sub in availableSubjects" :key="sub.id" :value="sub.id">{{ sub.name }}</option>
                                    </select>
                                    <i class="fas fa-book absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 pointer-events-none"></i>
                                </div>
                            </div>
                        </div>
                        
                        <button 
                            type="submit" 
                            :disabled="form.processing" 
                            class="w-full flex items-center justify-center gap-2 py-4 bg-primary-600 text-white text-[11px] font-black uppercase tracking-widest rounded-2xl shadow-lg shadow-primary-600/20 hover:bg-primary-500 hover:-translate-y-0.5 transition-all disabled:opacity-50 mt-4"
                        >
                            <i class="fas fa-save"></i>
                            Asignar Carga
                        </button>
                    </form>
                </div>

                <!-- Loads Table -->
                <div class="lg:col-span-2 glass-card rounded-3xl overflow-hidden shadow-2xl animate-fade-in-up" style="animation-delay: 200ms">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-slate-50 text-slate-400 text-[10px] uppercase font-black tracking-[0.2em]">
                            <tr>
                                <th class="px-6 py-5">Docente</th>
                                <th class="px-6 py-5">Sección</th>
                                <th class="px-6 py-5">Materia</th>
                                <th class="px-6 py-5 text-right"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="load in loads" :key="load.id" class="group hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-5">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-primary-50 text-primary-600 flex items-center justify-center text-[10px] font-black shadow-sm group-hover:bg-primary-500 group-hover:text-white transition-all">
                                            {{ load.teacher?.name.charAt(0) }}
                                        </div>
                                        <span class="font-black text-slate-700 group-hover:text-primary-700 transition-colors">
                                            {{ load.teacher?.name }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-5">
                                    <div class="flex flex-col">
                                        <span class="text-xs font-black text-slate-700 leading-tight">
                                            {{ load.subject?.gradeLevel?.name }}
                                        </span>
                                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">
                                            Sección {{ load.section?.name }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-5">
                                    <div class="flex items-center gap-2">
                                        <span class="px-2 py-1 rounded-lg bg-slate-100 text-slate-400 text-[9px] font-black tracking-tighter border border-slate-200">
                                            {{ load.subject?.code }}
                                        </span>
                                        <span class="text-sm font-bold text-slate-600">
                                            {{ load.subject?.name }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-5 text-right">
                                    <button 
                                        @click="destroy(load.id)" 
                                        class="w-8 h-8 rounded-lg bg-red-50 text-red-400 hover:bg-red-500 hover:text-white transition-all border border-transparent"
                                        title="Eliminar Asignación"
                                    >
                                        <i class="fas fa-trash-alt text-xs"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="loads.length === 0">
                                <td colspan="4" class="px-6 py-20 text-center">
                                    <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 text-slate-200">
                                        <i class="fas fa-unlink text-2xl"></i>
                                    </div>
                                    <h3 class="text-lg font-bold text-slate-400">Sin cargas asignadas</h3>
                                    <p class="text-slate-300 text-sm mt-1">No se encontraron asignaciones para los filtros seleccionados.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
