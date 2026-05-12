<script setup>
import { ref } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'
import Modal from '@/Components/UI/Modal.vue'

const props = defineProps({
    sections: Array,
    years: Array,
    levels: Array,
    activeYear: Object,
    filters: Object,
})

const schoolYearId = ref(props.filters.school_year_id || '')
const gradeLevelId = ref(props.filters.grade_level_id || '')
const showModal = ref(false)
const editingSection = ref(null)

const form = useForm({
    school_year_id: schoolYearId.value,
    grade_level_id: '',
    name: '',
    capacity: 35,
})

function loadSections() {
    router.get('/admin/sections', { 
        school_year_id: schoolYearId.value,
        grade_level_id: gradeLevelId.value
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
            onSuccess: () => { showModal.value = false; loadSections() },
        })
    } else {
        form.post('/admin/sections', {
            onSuccess: () => { showModal.value = false; loadSections() },
        })
    }
}

function destroy(section) {
    if (confirm(`¿Seguro que desea eliminar la sección ${section.name}?`)) {
        router.delete(`/admin/sections/${section.id}`, {
            onSuccess: () => loadSections()
        })
    }
}
</script>

<template>
    <AppLayout title="Gestión de Secciones">
        <div class="space-y-8 max-w-7xl mx-auto">
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

                    <div class="relative flex-1 sm:min-w-[200px]">
                        <select 
                            v-model="gradeLevelId" 
                            @change="loadSections" 
                            class="w-full bg-white border-2 border-slate-100 rounded-2xl px-4 py-3.5 text-slate-700 text-sm font-bold focus:border-primary-400 focus:ring-0 outline-none transition-all appearance-none cursor-pointer shadow-sm"
                        >
                            <option value="">Todos los Niveles</option>
                            <option v-for="lvl in levels" :key="lvl.id" :value="lvl.id">{{ lvl.name }}</option>
                        </select>
                        <i class="fas fa-layer-group absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 pointer-events-none"></i>
                    </div>

                    <button
                        v-if="schoolYearId"
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

            <div v-else class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-6">
                <div
                    v-for="(section, index) in sections"
                    :key="section.id"
                    class="glass-card rounded-3xl p-6 relative overflow-hidden transition-all duration-300 group hover:-translate-y-2 animate-fade-in-up shadow-xl"
                    :style="{ animationDelay: `${(index + 1) * 50}ms` }"
                >
                    <!-- Header -->
                    <div class="flex justify-between items-start mb-6">
                        <div class="w-12 h-12 bg-primary-50 text-primary-600 rounded-2xl flex items-center justify-center text-xl shadow-sm group-hover:rotate-6 transition-transform">
                            <i class="fas fa-chalkboard"></i>
                        </div>
                        <div class="flex gap-2 translate-x-2 -translate-y-2 lg:opacity-0 group-hover:opacity-100 transition-opacity">
                            <button @click="openEditModal(section)" class="w-8 h-8 rounded-lg bg-slate-100 text-slate-400 hover:bg-primary-50 hover:text-primary-600 transition-all">
                                <i class="fas fa-edit text-xs"></i>
                            </button>
                            <button @click="destroy(section)" class="w-8 h-8 rounded-lg bg-red-50 text-red-400 hover:bg-red-500 hover:text-white transition-all">
                                <i class="fas fa-trash text-xs"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Body -->
                    <h4 class="text-lg font-black text-slate-800 mb-1 leading-tight group-hover:text-primary-600 transition-colors">
                        {{ section.grade_level?.name }}
                    </h4>
                    <div class="flex items-center gap-2 mb-4">
                        <span class="text-xs font-black text-slate-400 uppercase tracking-widest">Sección</span>
                        <span class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 text-xs font-black">{{ section.name }}</span>
                    </div>

                    <!-- Stats -->
                    <div class="flex items-center justify-between pt-4 border-t border-slate-50">
                        <div class="flex items-center gap-2 text-slate-400">
                            <i class="fas fa-user-friends text-[10px]"></i>
                            <span class="text-[10px] font-black uppercase tracking-widest">Capacidad</span>
                        </div>
                        <span class="text-xs font-black text-slate-700">{{ section.capacity }}</span>
                    </div>
                </div>

                <!-- Empty Sections -->
                <div v-if="sections.length === 0" class="col-span-full glass-card rounded-3xl p-20 text-center">
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
                    <button @click="showModal = false" class="w-10 h-10 rounded-full bg-slate-50 text-slate-400 hover:bg-slate-100 transition-all">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="space-y-2">
                        <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Nivel / Año Académico</label>
                        <div class="relative">
                            <select 
                                v-model="form.grade_level_id" 
                                class="w-full bg-slate-50 border-2 border-slate-50 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all appearance-none cursor-pointer disabled:opacity-50" 
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
                            <input v-model="form.name" type="text" class="w-full bg-slate-50 border-2 border-slate-50 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all" required>
                        </div>
                        <div class="space-y-2">
                            <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Capacidad Máxima</label>
                            <input v-model="form.capacity" type="number" min="1" max="100" class="w-full bg-slate-50 border-2 border-slate-50 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all" required>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-4 mt-12">
                        <button type="button" @click="showModal = false" class="px-6 py-3 text-sm font-black uppercase tracking-widest text-slate-400 hover:text-slate-600 transition-all">
                            Cancelar
                        </button>
                        <button 
                            type="submit" 
                            :disabled="form.processing" 
                            class="px-10 py-3.5 bg-primary-600 text-white text-[11px] font-black uppercase tracking-widest rounded-2xl shadow-lg shadow-primary-600/20 hover:bg-primary-500 hover:-translate-y-0.5 transition-all"
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
