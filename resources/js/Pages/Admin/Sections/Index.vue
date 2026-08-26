<script setup>
import { ref, computed } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'
import Modal from '@/Components/UI/Modal.vue'
import Swal from 'sweetalert2'

const props = defineProps({
    sections: Array,
    years: Array,
    levels: Array,
    activeYear: Object,
    filters: Object,
})

const schoolYearId = ref(props.filters.school_year_id || '')
const showModal = ref(false)
const editingSection = ref(null)
const activeLevel = ref(null)

const form = useForm({
    school_year_id: schoolYearId.value,
    grade_level_id: '',
    name: '',
    capacity: 100,
})

const groupedSections = computed(() => {
    const groups = {}
    props.sections.forEach(sec => {
        const lvlName = sec.grade_level?.name || 'Sin Nivel'
        if (!groups[lvlName]) {
            groups[lvlName] = []
        }
        groups[lvlName].push(sec)
    })
    
    // Sort sections within each group by name
    Object.keys(groups).forEach(key => {
        groups[key].sort((a, b) => a.name.localeCompare(b.name))
    })
    
    // If activeLevel is null and groups has keys, auto-open the first one
    if (activeLevel.value === null && Object.keys(groups).length > 0) {
        activeLevel.value = Object.keys(groups)[0]
    }
    
    return groups
})

function toggleLevel(levelName) {
    activeLevel.value = activeLevel.value === levelName ? null : levelName
}

function loadSections() {
    activeLevel.value = null // reset accordion state when loading new data
    router.get('/admin/sections', { 
        school_year_id: schoolYearId.value,
        
    }, { preserveState: true })
}

function openCreateModal() {
    editingSection.value = null
    form.reset()
    form.school_year_id = schoolYearId.value
    form.clearErrors()
    showModal.value = true
}

function openEditModal(section) {
    editingSection.value = section
    form.school_year_id = section.school_year_id
    form.grade_level_id = section.grade_level_id
    form.name = section.name
    form.capacity = section.capacity
    form.clearErrors()
    showModal.value = true
}

function submit() {
    if (editingSection.value) {
        form.put(`/admin/sections/${editingSection.value.id}`, {
            onSuccess: () => { showModal.value = false },
        })
    } else {
        form.post('/admin/sections', {
            onSuccess: () => { showModal.value = false },
        })
    }
}

function destroy(section) {
    Swal.fire({
        title: '¿Confirmar Eliminación?',
        text: `¿Seguro que desea eliminar: ${section.name}?`,
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
            router.delete(`/admin/sections/${section.id}`, {
            onSuccess: () => {}
        })
        }
    })
}
</script>

<template>
    <AppLayout title="Gestión de Secciones">
        <div class="space-y-8 max-w-7xl mx-auto pb-10">
            <!-- Header Section -->
            <div class="flex flex-col lg:flex-row gap-6 items-center justify-between animate-fade-in-up">
                <div>
                    <h2 class="text-3xl font-extrabold text-slate-800">
                        Gestión de <span class="gradient-text">Secciones</span>
                    </h2>
                    <p class="text-slate-400 font-medium mt-2">Crea y administra las unidades académicas por nivel</p>
                </div>
                
                <div class="flex flex-col sm:flex-row gap-4 w-full lg:w-auto">
                    <div class="relative flex-1 sm:min-w-[200px]">
                        <select 
                            v-model="schoolYearId" 
                            @change="loadSections" 
                            class="w-full bg-white border-2 border-slate-100 rounded-2xl px-4 py-3.5 text-slate-700 text-sm font-bold focus:border-primary-400 focus:ring-0 outline-none transition-all appearance-none cursor-pointer shadow-sm"
                        >
                            <option value="">Seleccione Año Escolar</option>
                            <option v-for="year in years" :key="year.id" :value="year.id">{{ year.name }}</option>
                        </select>
                        <i class="fas fa-calendar absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 pointer-events-none"></i>
                    </div>



                    <button
                        v-if="$can('sections.manage') && schoolYearId"
                        @click="openCreateModal"
                        class="flex items-center justify-center gap-2 px-6 py-3.5 bg-primary-600 text-white text-[11px] font-black uppercase tracking-widest rounded-2xl shadow-lg shadow-primary-600/20 hover:bg-primary-500 hover:-translate-y-0.5 transition-all"
                    >
                        <i class="fas fa-plus"></i>
                        Nueva Sección
                    </button>
                </div>
            </div>

            <!-- Content Area -->
            <div v-if="!schoolYearId" class="glass-card rounded-3xl p-20 text-center animate-fade-in-up">
                <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 text-slate-200">
                    <i class="fas fa-arrow-up text-2xl"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-400">Selecciona un Año Escolar</h3>
                <p class="text-slate-300 text-sm mt-1">Para visualizar las secciones disponibles, primero selecciona un periodo académico.</p>
            </div>

            <!-- Accordion Layout -->
            <div v-else class="space-y-4">
                <div 
                    v-for="(sectionsList, levelName) in groupedSections" 
                    :key="levelName"
                    class="glass-card rounded-3xl overflow-hidden shadow-xl border-2 transition-all animate-fade-in-up"
                    :class="activeLevel === levelName ? 'border-indigo-100' : 'border-transparent'"
                >
                    <!-- Accordion Header -->
                    <button 
                        @click="toggleLevel(levelName)"
                        class="w-full flex items-center justify-between p-5 sm:p-6 bg-white hover:bg-slate-50 transition-colors"
                    >
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center text-xl shadow-sm transition-transform"
                                :class="activeLevel === levelName ? 'rotate-6 bg-indigo-500 text-white shadow-indigo-500/30' : ''"
                            >
                                <i class="fas fa-layer-group"></i>
                            </div>
                            <div class="text-left">
                                <h3 class="text-lg sm:text-xl font-black text-slate-800">{{ levelName }}</h3>
                                <p class="text-xs font-bold text-slate-400 mt-0.5">{{ sectionsList.length }} Secciones Registradas</p>
                            </div>
                        </div>
                        <div class="w-10 h-10 rounded-full bg-slate-50 flex items-center justify-center text-slate-400 transition-transform"
                            :class="activeLevel === levelName ? 'rotate-180 bg-indigo-100 text-indigo-600' : ''"
                        >
                            <i class="fas fa-chevron-down"></i>
                        </div>
                    </button>
                    
                    <!-- Accordion Body -->
                    <transition
                        enter-active-class="transition-all duration-500 ease-in-out overflow-hidden"
                        enter-from-class="opacity-0 max-h-0"
                        enter-to-class="opacity-100 max-h-[2000px]"
                        leave-active-class="transition-all duration-300 ease-in-out overflow-hidden"
                        leave-from-class="opacity-100 max-h-[2000px]"
                        leave-to-class="opacity-0 max-h-0"
                    >
                        <div 
                            v-show="activeLevel === levelName"
                            class="p-5 sm:p-6 pt-2 bg-slate-50/50 border-t-2 border-slate-50 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4"
                        >
                        <!-- Section Card -->
                        <div
                            v-for="section in sectionsList"
                            :key="section.id"
                            class="bg-white rounded-2xl p-5 relative overflow-hidden transition-all duration-300 group hover:-translate-y-1 shadow-sm border-2 border-slate-100 hover:border-indigo-200 hover:shadow-md"
                        >
                            <!-- Header -->
                            <div class="flex justify-between items-start mb-4">
                                <div class="w-10 h-10 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center text-lg shadow-sm font-black border border-emerald-100">
                                    {{ section.name }}
                                </div>
                                <div class="flex gap-1.5 opacity-100 sm:opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button v-if="$can('sections.manage')" @click="openEditModal(section)" class="w-10 h-10 sm:w-8 sm:h-8 rounded-xl sm:rounded-lg bg-slate-50 text-slate-400 hover:bg-primary-50 hover:text-primary-600 transition-all flex items-center justify-center border border-transparent hover:border-primary-100 shadow-sm" title="Editar">
                                        <i class="fas fa-edit text-sm sm:text-[10px]"></i>
                                    </button>
                                    <button v-if="section.enrollments_count === 0 && section.academic_loads_count === 0" @click="destroy(section)" class="w-10 h-10 sm:w-8 sm:h-8 rounded-xl sm:rounded-lg bg-red-50 text-red-400 hover:bg-red-500 hover:text-white transition-all flex items-center justify-center border border-transparent hover:border-red-200 shadow-sm" title="Eliminar">
                                        <i class="fas fa-trash text-sm sm:text-[10px]"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Body -->
                            <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-1">Sección</h4>
                            <p class="text-sm font-bold text-slate-700 mb-3 truncate">
                                {{ levelName }} — {{ section.name }}
                            </p>
                            
                            <!-- Stats -->
                            <div class="flex items-center justify-between pt-3 border-t-2 border-slate-50">
                                <div class="flex items-center gap-1.5 text-slate-400">
                                    <i class="fas fa-user-friends text-[10px]"></i>
                                    <span class="text-[9px] font-black uppercase tracking-widest">Capacidad</span>
                                </div>
                                <span class="text-xs font-black text-slate-700 bg-slate-50 px-2 py-0.5 rounded-lg border border-slate-100">{{ section.capacity }}</span>
                            </div>
                        </div>
                        </div>
                    </transition>
                </div>

                <!-- Empty Sections -->
                <div v-if="Object.keys(groupedSections).length === 0" class="glass-card rounded-3xl p-20 text-center animate-fade-in-up">
                    <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 text-slate-200">
                        <i class="fas fa-folder-open text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-400">No hay secciones registradas</h3>
                    <p class="text-slate-300 text-sm mt-1">Comienza creando una nueva sección para este nivel.</p>
                </div>
            </div>
        </div>

        <!-- Form Modal -->
        <Modal :show="showModal" @close="showModal = false" max-width="md">
            <div class="p-8">
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h3 class="text-2xl font-black text-slate-800">
                            {{ editingSection ? 'Editar' : 'Nueva' }} <span class="text-primary-500">Sección</span>
                        </h3>
                        <p class="text-sm font-medium text-slate-400 mt-1">Configura los parámetros de la sección</p>
                    </div>
                    <button @click="showModal = false" class="w-10 h-10 rounded-full bg-slate-50 text-slate-400 hover:bg-slate-100 transition-all shadow-sm">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="space-y-2">
                        <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Nivel / Año Académico</label>
                        <div class="relative">
                            <select 
                                v-model="form.grade_level_id" 
                                class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all appearance-none cursor-pointer disabled:opacity-50 shadow-sm" 
                                :disabled="!!editingSection"
                            >
                                <option v-for="lvl in levels" :key="lvl.id" :value="lvl.id">{{ lvl.name }}</option>
                            </select>
                            <i class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 pointer-events-none"></i>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Nombre (ej. A, B)</label>
                            <input v-model="form.name" type="text" class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all shadow-sm" required>
                        </div>
                        <div class="space-y-2">
                            <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Capacidad Máxima</label>
                            <input v-model="form.capacity" type="number" min="1" max="100" class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all shadow-sm" required>
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
                            Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AppLayout>
</template>