<script setup>
import { ref, computed } from 'vue'
import { router, useForm, Link } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'
import Modal from '@/Components/UI/Modal.vue'
import Swal from 'sweetalert2'

const props = defineProps({
    subjects: Array,
    levels: Array,
    filters: Object,
})

const showModal = ref(false)
const editingSubject = ref(null)
const searchQuery = ref(props.filters.search || '')
const activeLevel = ref(null)

const form = useForm({
    grade_level_id: '',
    name: '',
    code: '',
    weight: 10,
    grading_type: 'numeric',
})

const groupedSubjects = computed(() => {
    const groups = {}
    
    // Inicializar grupos según los niveles en orden
    props.levels.forEach(lvl => {
        groups[lvl.name] = []
    })
    groups['Sin Nivel'] = []

    if (props.subjects && props.subjects.length > 0) {
        props.subjects.forEach(subject => {
            const levelName = subject.grade_level?.name || 'Sin Nivel'
            if (groups[levelName]) {
                groups[levelName].push(subject)
            } else {
                // Por si acaso hay un nivel que no vino en props.levels
                groups[levelName] = [subject]
            }
        })
    }
    
    // Filtrar grupos vacíos
    const filteredGroups = {}
    for (const [key, value] of Object.entries(groups)) {
        if (value.length > 0) {
            filteredGroups[key] = value
        }
    }

    // Auto-abrir el primero si no hay ninguno activo
    if (activeLevel.value === null && Object.keys(filteredGroups).length > 0) {
        activeLevel.value = Object.keys(filteredGroups)[0]
    }
    
    return filteredGroups
})

function toggleLevel(levelName) {
    activeLevel.value = activeLevel.value === levelName ? null : levelName
}

function doSearch() {
    activeLevel.value = null // reset accordion
    router.get('/admin/subjects', { search: searchQuery.value }, { preserveState: true, replace: true })
}

function resetSubjectForm() {
    form.grade_level_id = ''
    form.name = ''
    form.code = ''
    form.weight = 10
    form.grading_type = 'numeric'
    form.clearErrors()
}

function openCreateModal() {
    editingSubject.value = null
    resetSubjectForm()
    showModal.value = true
}

function openEditModal(subject) {
    editingSubject.value = subject
    resetSubjectForm()
    
    form.grade_level_id = subject.grade_level_id
    form.name = subject.name
    form.code = subject.code
    form.weight = subject.weight || 10
    form.grading_type = subject.grading_type || 'numeric'
    
    showModal.value = true
}

function submit() {
    if (editingSubject.value) {
        form.put(`/admin/subjects/${editingSubject.value.id}`, { 
            onSuccess: () => { 
                showModal.value = false
                resetSubjectForm()
            } 
        })
    } else {
        form.post('/admin/subjects', { 
            onSuccess: () => { 
                showModal.value = false
                resetSubjectForm()
            } 
        })
    }
}

function destroy(subject) {
    Swal.fire({
        title: '¿Confirmar Eliminación?',
        text: `¿Seguro que desea eliminar: ${subject.name}?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        buttonsStyling: false,
        customClass: {
            popup: 'rounded-3xl border-2 border-slate-100 shadow-2xl',
            title: 'text-2xl font-black text-slate-800',
            htmlContainer: 'text-slate-500 font-medium',
            confirmButton: 'px-6 py-3 bg-red-500 text-white text-[11px] font-black uppercase tracking-widest rounded-2xl shadow-lg shadow-red-500/20 hover:bg-red-400 transition-all mx-2',
            cancelButton: 'px-6 py-3 bg-slate-500 text-white text-[11px] font-black uppercase tracking-widest rounded-2xl shadow-lg shadow-slate-500/20 hover:bg-slate-400 transition-all mx-2'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/admin/subjects/${subject.id}`, {
            onSuccess: () => {}
        })
        }
    })
}
</script>

<template>
    <AppLayout title="Pensum de Materias">
        <div class="space-y-8 max-w-7xl mx-auto pb-10">
            <!-- Header Section -->
            <div class="flex flex-col lg:flex-row gap-6 items-center justify-between animate-fade-in-up">
                <div>
                    <h2 class="text-3xl font-extrabold text-slate-800">
                        Materias y <span class="gradient-text">Pensum</span>
                    </h2>
                    <p class="text-slate-400 font-medium mt-2">Gestiona las asignaturas impartidas en cada nivel académico</p>
                </div>
                
                <div class="flex flex-col sm:flex-row gap-4 w-full lg:w-auto">
                    <!-- Buscador -->
                    <div class="relative flex-1 sm:min-w-[250px]">
                        <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                        <input 
                            v-model="searchQuery" 
                            @keyup.enter="doSearch"
                            type="text" 
                            placeholder="Buscar por materia o código..." 
                            class="w-full bg-white border-2 border-slate-100 rounded-2xl pl-10 pr-4 py-3.5 text-sm text-slate-700 placeholder:text-slate-400 focus:border-primary-400 focus:ring-0 outline-none transition-all shadow-sm"
                        >
                    </div>
                    
                    <button v-if="$can('subjects.manage')" @click="openCreateModal"
                        class="flex items-center justify-center gap-2 px-6 py-3.5 bg-primary-600 text-white text-[11px] font-black uppercase tracking-widest rounded-2xl shadow-lg shadow-primary-600/20 hover:bg-primary-500 hover:-translate-y-0.5 transition-all w-full sm:w-auto shrink-0"
                    >
                        <i class="fas fa-plus"></i>
                        Nueva Materia
                    </button>
                </div>
            </div>

            <!-- Accordion Layout -->
            <div class="space-y-4">
                <div 
                    v-for="(subjectsList, levelName) in groupedSubjects" 
                    :key="levelName"
                    class="glass-card rounded-3xl overflow-hidden shadow-xl border-2 transition-all animate-fade-in-up"
                    :class="activeLevel === levelName ? 'border-primary-100' : 'border-transparent'"
                >
                    <!-- Accordion Header -->
                    <button 
                        @click="toggleLevel(levelName)"
                        class="w-full flex items-center justify-between p-5 sm:p-6 bg-white hover:bg-slate-50 transition-colors"
                    >
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-primary-50 text-primary-600 rounded-2xl flex items-center justify-center text-xl shadow-sm transition-transform"
                                :class="activeLevel === levelName ? 'rotate-6 bg-primary-500 text-white shadow-primary-500/30' : ''"
                            >
                                <i class="fas fa-book"></i>
                            </div>
                            <div class="text-left">
                                <h3 class="text-lg sm:text-xl font-black text-slate-800">{{ levelName }}</h3>
                                <p class="text-xs font-bold text-slate-400 mt-0.5">{{ subjectsList.length }} Materias Asignadas</p>
                            </div>
                        </div>
                        <div class="w-10 h-10 rounded-full bg-slate-50 flex items-center justify-center text-slate-400 transition-transform"
                            :class="activeLevel === levelName ? 'rotate-180 bg-primary-100 text-primary-600' : ''"
                        >
                            <i class="fas fa-chevron-down"></i>
                        </div>
                    </button>
                    
                    <!-- Accordion Body -->
                    <transition
                        enter-active-class="transition-all duration-500 ease-in-out overflow-hidden"
                        enter-from-class="opacity-0 max-h-0"
                        enter-to-class="opacity-100 max-h-[3000px]"
                        leave-active-class="transition-all duration-300 ease-in-out overflow-hidden"
                        leave-from-class="opacity-100 max-h-[3000px]"
                        leave-to-class="opacity-0 max-h-0"
                    >
                        <div 
                            v-show="activeLevel === levelName"
                            class="p-5 sm:p-6 pt-2 bg-slate-50/50 border-t-2 border-slate-50 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4"
                        >
                            <!-- Subject Card -->
                            <div
                                v-for="subject in subjectsList"
                                :key="subject.id"
                                class="bg-white rounded-2xl p-5 relative overflow-hidden transition-all duration-300 group hover:-translate-y-1 shadow-sm border-2 border-slate-100 hover:border-primary-200 hover:shadow-md flex flex-col justify-between"
                            >
                                <!-- Header -->
                                <div>
                                    <div class="flex justify-between items-start mb-4">
                                        <div class="px-3 py-1 bg-slate-50 text-slate-500 rounded-lg text-[10px] font-black uppercase tracking-widest border border-slate-200">
                                            {{ subject.code }}
                                        </div>
                                        <div class="flex gap-1.5 opacity-100 sm:opacity-0 group-hover:opacity-100 transition-opacity">
                                            <button v-if="$can('subjects.manage')" @click="openEditModal(subject)" class="w-10 h-10 sm:w-8 sm:h-8 rounded-xl sm:rounded-lg bg-slate-50 text-slate-400 hover:bg-primary-50 hover:text-primary-600 transition-all flex items-center justify-center border border-transparent hover:border-primary-100 shadow-sm" title="Editar">
                                                <i class="fas fa-edit text-sm sm:text-[10px]"></i>
                                            </button>
                                            <button v-if="subject.academic_loads_count === 0 && subject.grades_count === 0" @click="destroy(subject)" class="w-10 h-10 sm:w-8 sm:h-8 rounded-xl sm:rounded-lg bg-red-50 text-red-400 hover:bg-red-500 hover:text-white transition-all flex items-center justify-center border border-transparent hover:border-red-200 shadow-sm" title="Eliminar">
                                                <i class="fas fa-trash text-sm sm:text-[10px]"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <!-- Body -->
                                    <h4 class="text-sm font-bold text-slate-800 leading-tight mb-2">
                                        {{ subject.name }}
                                    </h4>
                                </div>
                                
                                <!-- Stats/Footer -->
                                <div class="flex items-center justify-between pt-4 mt-2 border-t border-slate-50">
                                    <span v-if="subject.grading_type === 'qualitative'" class="px-2 py-1 rounded-md bg-amber-50 text-amber-600 border border-amber-100 text-[9px] font-black uppercase tracking-widest flex items-center gap-1">
                                        <i class="fas fa-font"></i> Cualitativa
                                    </span>
                                    <span v-else class="px-2 py-1 rounded-md bg-emerald-50 text-emerald-600 border border-emerald-100 text-[9px] font-black uppercase tracking-widest flex items-center gap-1">
                                        <i class="fas fa-hashtag"></i> Numérica
                                    </span>
                                </div>
                            </div>
                        </div>
                    </transition>
                </div>

                <!-- Empty Subjects -->
                <div v-if="Object.keys(groupedSubjects).length === 0" class="glass-card rounded-3xl p-20 text-center animate-fade-in-up">
                    <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 text-slate-200">
                        <i class="fas fa-search text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-400">No se encontraron materias</h3>
                    <p class="text-slate-300 text-sm mt-1">Intenta con otros términos de búsqueda o crea una nueva.</p>
                </div>
            </div>
        </div>

        <!-- Form Modal -->
        <Modal :show="showModal" @close="showModal = false" max-width="md">
            <div class="p-8">
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h3 class="text-2xl font-black text-slate-800">
                            {{ editingSubject ? 'Editar' : 'Nueva' }} <span class="text-primary-500">Materia</span>
                        </h3>
                        <p class="text-sm font-medium text-slate-400 mt-1">Configura los detalles de la asignatura</p>
                    </div>
                    <button @click="showModal = false" class="w-10 h-10 rounded-full bg-slate-50 text-slate-400 hover:bg-slate-100 transition-all shadow-sm">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="space-y-2">
                        <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Nivel / Año Académico</label>
                        <div class="relative">
                            <select v-model="form.grade_level_id" class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all appearance-none cursor-pointer shadow-sm" required>
                                <option v-for="lvl in levels" :key="lvl.id" :value="lvl.id">{{ lvl.name }}</option>
                            </select>
                            <i class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 pointer-events-none"></i>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Nombre de la Materia</label>
                        <input v-model="form.name" type="text" class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all shadow-sm" required>
                    </div>

                    <div class="space-y-2">
                        <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Código Único</label>
                        <input v-model="form.code" type="text" class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all shadow-sm" required placeholder="ej. MAT-101">
                    </div>

                    <div class="space-y-2">
                        <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Tipo de Calificación</label>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="flex items-center gap-3 p-3 rounded-2xl border-2 cursor-pointer transition-all"
                                :class="form.grading_type === 'numeric' ? 'border-primary-400 bg-primary-50 shadow-sm' : 'border-slate-200 bg-slate-50 hover:border-slate-300'">
                                <input type="radio" v-model="form.grading_type" value="numeric" class="hidden">
                                <div class="w-8 h-8 rounded-xl flex items-center justify-center text-sm font-black transition-all"
                                    :class="form.grading_type === 'numeric' ? 'bg-primary-500 text-white shadow-md' : 'bg-slate-200 text-slate-500'">
                                    20
                                </div>
                                <div>
                                    <div class="font-black text-slate-700 text-sm">Numérica</div>
                                    <div class="text-[10px] text-slate-400">Del 1 al 20</div>
                                </div>
                            </label>
                            <label class="flex items-center gap-3 p-3 rounded-2xl border-2 cursor-pointer transition-all"
                                :class="form.grading_type === 'qualitative' ? 'border-amber-400 bg-amber-50 shadow-sm' : 'border-slate-200 bg-slate-50 hover:border-slate-300'">
                                <input type="radio" v-model="form.grading_type" value="qualitative" class="hidden">
                                <div class="w-8 h-8 rounded-xl flex items-center justify-center text-sm font-black transition-all"
                                    :class="form.grading_type === 'qualitative' ? 'bg-amber-500 text-white shadow-md' : 'bg-slate-200 text-slate-500'">
                                    A
                                </div>
                                <div>
                                    <div class="font-black text-slate-700 text-sm">Cualitativa</div>
                                    <div class="text-[10px] text-slate-400">A, B, C, D</div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-4 mt-8 pt-4">
                        <button type="button" @click="showModal = false" class="px-6 py-3 text-sm font-black uppercase tracking-widest text-slate-400 hover:text-slate-600 transition-all">
                            Cancelar
                        </button>
                        <button 
                            type="submit" 
                            :disabled="form.processing" 
                            class="px-10 py-3.5 bg-primary-600 text-white text-[11px] font-black uppercase tracking-widest rounded-2xl shadow-lg shadow-primary-600/20 hover:bg-primary-500 hover:-translate-y-0.5 transition-all disabled:opacity-50 disabled:translate-y-0"
                        >
                            <i class="fas fa-save mr-2"></i>
                            Guardar Materia
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AppLayout>
</template>