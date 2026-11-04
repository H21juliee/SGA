<script setup>
import { ref, computed } from 'vue'
import { router, useForm, Link } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'
import Modal from '@/Components/UI/Modal.vue'
import Swal from 'sweetalert2'

const props = defineProps({
    sections: Array,
    years: Array,
    levels: Array,
    teachers: Array,
    activeYear: Object,
    filters: Object,
    isCoordinator: Boolean,
})

const schoolYearId = ref(props.filters.school_year_id || '')
const showModal = ref(false)
const showCoordinatorModal = ref(false)
const editingSection = ref(null)
const selectedLevelForCoordinator = ref(null)
const activeLevel = ref(props.levels?.[0]?.name || null)

const form = useForm({
    school_year_id: schoolYearId.value,
    grade_level_id: '',
    name: '',
    capacity: 100,
})

const coordinatorForm = useForm({
    school_year_id: schoolYearId.value,
    user_id: '',
})

// Mapeo completo de niveles (1er a 5to año) con sus coordinadores y secciones correspondientes
const levelsData = computed(() => {
    return (props.levels || []).map(level => {
        const levelSections = (props.sections || []).filter(sec => sec.grade_level_id === level.id)
        levelSections.sort((a, b) => a.name.localeCompare(b.name))

        const totalCapacity = levelSections.reduce((sum, s) => sum + (s.capacity || 0), 0)
        const totalEnrolled = levelSections.reduce((sum, s) => sum + (s.enrollments_count || 0), 0)

        return {
            ...level,
            sections: levelSections,
            totalCapacity,
            totalEnrolled,
        }
    })
})

// Abre el primer nivel por defecto si no hay ninguno seleccionado
if (activeLevel.value === null && levelsData.value.length > 0) {
    activeLevel.value = levelsData.value[0].name
}

function toggleLevel(levelName) {
    activeLevel.value = activeLevel.value === levelName ? null : levelName
}

function loadSections() {
    activeLevel.value = null
    router.get('/admin/sections', { 
        school_year_id: schoolYearId.value,
    }, { preserveState: true })
}

function resetSectionForm() {
    form.school_year_id = schoolYearId.value
    form.grade_level_id = ''
    form.name = ''
    form.capacity = 100
    form.clearErrors()
}

function openCreateModal(gradeLevelId = null) {
    editingSection.value = null
    resetSectionForm()
    if (gradeLevelId) {
        form.grade_level_id = gradeLevelId
    } else if (props.levels?.length > 0) {
        form.grade_level_id = props.levels[0].id
    }
    showModal.value = true
}

function openEditModal(section) {
    editingSection.value = section
    resetSectionForm()
    
    form.school_year_id = section.school_year_id
    form.grade_level_id = section.grade_level_id
    form.name = section.name
    form.capacity = section.capacity
    
    showModal.value = true
}

function submit() {
    if (editingSection.value) {
        form.put(`/admin/sections/${editingSection.value.id}`, {
            onSuccess: () => { 
                showModal.value = false
                resetSectionForm()
            },
        })
    } else {
        form.post('/admin/sections', {
            onSuccess: () => { 
                showModal.value = false
                resetSectionForm()
            },
        })
    }
}

function destroy(section) {
    Swal.fire({
        title: '¿Confirmar Eliminación?',
        text: `¿Seguro que desea eliminar la sección: ${section.name}?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        buttonsStyling: false,
        customClass: {
            popup: 'rounded-3xl border-2 border-slate-100 shadow-2xl',
            title: 'text-2xl font-black text-slate-800',
            htmlContainer: 'text-slate-500 font-medium',
            confirmButton: 'px-6 py-3 bg-red-500 text-white text-[11px] font-black uppercase tracking-widest rounded-2xl shadow-lg shadow-red-500/20 hover:bg-red-400 transition-all mx-2 cursor-pointer',
            cancelButton: 'px-6 py-3 bg-slate-100 text-slate-600 text-[11px] font-black uppercase tracking-widest rounded-2xl hover:bg-slate-200 transition-all mx-2 cursor-pointer'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/admin/sections/${section.id}`, { preserveScroll: true })
        }
    })
}

// Coordinador de Año
function openCoordinatorModal(level) {
    selectedLevelForCoordinator.value = level
    coordinatorForm.school_year_id = schoolYearId.value
    coordinatorForm.user_id = level.current_coordinator?.id || ''
    coordinatorForm.clearErrors()
    showCoordinatorModal.value = true
}

function submitCoordinator() {
    if (!selectedLevelForCoordinator.value) return

    coordinatorForm.post(`/admin/grade-levels/${selectedLevelForCoordinator.value.id}/coordinator`, {
        preserveScroll: true,
        onSuccess: () => {
            showCoordinatorModal.value = false
        }
    })
}

function removeCoordinator(level) {
    Swal.fire({
        title: '¿Remover Coordinador?',
        text: `¿Desea desasignar al coordinador de ${level.name} para este período escolar?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, remover',
        cancelButtonText: 'Cancelar',
        buttonsStyling: false,
        customClass: {
            popup: 'rounded-3xl border-2 border-slate-100 shadow-2xl',
            title: 'text-2xl font-black text-slate-800',
            htmlContainer: 'text-slate-500 font-medium',
            confirmButton: 'px-6 py-3 bg-red-500 text-white text-[11px] font-black uppercase tracking-widest rounded-2xl shadow-lg shadow-red-500/20 hover:bg-red-400 transition-all mx-2 cursor-pointer',
            cancelButton: 'px-6 py-3 bg-slate-100 text-slate-600 text-[11px] font-black uppercase tracking-widest rounded-2xl hover:bg-slate-200 transition-all mx-2 cursor-pointer'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(`/admin/grade-levels/${level.id}/coordinator`, {
                school_year_id: schoolYearId.value,
                user_id: null,
            }, { preserveScroll: true })
        }
    })
}
</script>

<template>
    <AppLayout title="Años y Secciones">
        <div class="space-y-6 sm:space-y-8 max-w-7xl mx-auto pb-10">
            <!-- Header Section -->
            <div class="flex flex-col lg:flex-row gap-6 items-start lg:items-center justify-between animate-fade-in-up">
                <div>
                    <h2 class="text-3xl font-extrabold text-slate-800">
                        Años y <span class="gradient-text">Secciones</span>
                    </h2>
                    <p class="text-slate-400 font-medium mt-1 text-sm">
                        Estructura académica de 1° a 5° año, asignación de coordinadores y secciones
                    </p>
                </div>
                
                <div class="flex flex-col sm:flex-row gap-3 w-full lg:w-auto">
                    <!-- Selector de Período Escolar -->
                    <div class="relative flex-1 sm:min-w-[220px]">
                        <select 
                            v-model="schoolYearId" 
                            @change="loadSections" 
                            class="w-full bg-white border-2 border-slate-100 rounded-2xl px-4 py-3.5 text-slate-700 text-sm font-bold focus:border-primary-400 focus:ring-0 outline-none transition-all appearance-none cursor-pointer shadow-sm"
                        >
                            <option value="">Seleccione Período Escolar</option>
                            <option v-for="year in years" :key="year.id" :value="year.id">
                                Período {{ year.name }} {{ year.is_active ? '(Activo)' : '' }}
                            </option>
                        </select>
                        <i class="fas fa-calendar absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none text-xs"></i>
                    </div>

                    <button
                        v-if="$can('sections.manage') && schoolYearId"
                        @click="openCreateModal()"
                        class="flex items-center justify-center gap-2 px-6 py-3.5 bg-primary-600 text-white text-[11px] font-black uppercase tracking-widest rounded-2xl shadow-lg shadow-primary-600/20 hover:bg-primary-500 hover:-translate-y-0.5 transition-all shrink-0 active:scale-95"
                    >
                        <i class="fas fa-plus"></i>
                        Nueva Sección
                    </button>
                </div>
            </div>

            <!-- Warning State: Sin Período Seleccionado -->
            <div v-if="!schoolYearId" class="glass-card rounded-3xl p-16 sm:p-20 text-center animate-fade-in-up">
                <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 text-slate-300">
                    <i class="fas fa-calendar-alt text-2xl"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-700">Selecciona un Período Escolar</h3>
                <p class="text-slate-400 text-sm mt-1 max-w-md mx-auto">
                    Para visualizar los años escolares, sus coordinadores y secciones, selecciona un período lectivo en el selector superior.
                </p>
            </div>

            <!-- Listado de Años y Secciones (Acordeón de 1° a 5° Año) -->
            <div v-else-if="levelsData.length > 0" class="space-y-4">
                <div 
                    v-for="level in levelsData" 
                    :key="level.id"
                    class="glass-card rounded-3xl overflow-hidden shadow-xl border-2 transition-all animate-fade-in-up"
                    :class="activeLevel === level.name ? 'border-primary-100 ring-2 ring-primary-50/50' : 'border-transparent hover:border-slate-100'"
                >
                    <!-- Cabecera del Año Escolar -->
                    <div 
                        @click="toggleLevel(level.name)"
                        class="w-full p-4 sm:p-6 bg-white hover:bg-slate-50/80 transition-colors cursor-pointer flex flex-col md:flex-row md:items-center justify-between gap-4 select-none"
                    >
                        <!-- Información del Año -->
                        <div class="flex items-center gap-3.5 sm:gap-4">
                            <div 
                                class="w-12 h-12 rounded-2xl flex items-center justify-center text-lg sm:text-xl font-black shadow-sm shrink-0 transition-transform"
                                :class="activeLevel === level.name 
                                    ? 'bg-primary-600 text-white shadow-primary-600/30 scale-105' 
                                    : 'bg-primary-50 text-primary-600'"
                            >
                                {{ level.order_num }}°
                            </div>
                            <div>
                                <h3 class="text-lg sm:text-xl font-black text-slate-800 leading-tight">
                                    {{ level.name }}
                                </h3>
                                <p class="text-xs font-bold text-slate-400 mt-1 flex flex-wrap items-center gap-x-2 gap-y-0.5">
                                    <span><i class="fas fa-users-class opacity-60"></i> {{ level.sections.length }} Secciones</span>
                                    <span>•</span>
                                    <span>{{ level.totalEnrolled }} alumnos matriculados</span>
                                    <span v-if="level.totalCapacity > 0" class="text-slate-300">/ {{ level.totalCapacity }} cupos</span>
                                </p>
                            </div>
                        </div>

                        <!-- Bloque del Coordinador de Año -->
                        <div class="flex flex-wrap items-center justify-between md:justify-end gap-3 pt-2 md:pt-0 border-t md:border-t-0 border-slate-100">
                            <!-- Coordinador Asignado -->
                            <div v-if="level.current_coordinator" class="flex items-center gap-2.5 px-3.5 py-1.5 rounded-xl bg-slate-50 border border-slate-200/80 shadow-xs">
                                <div class="w-7 h-7 rounded-lg bg-primary-100 text-primary-700 flex items-center justify-center text-[11px] font-black shrink-0">
                                    {{ level.current_coordinator.name?.charAt(0) }}
                                </div>
                                <div class="text-left min-w-0 max-w-[180px] sm:max-w-[220px]">
                                    <p class="text-[9px] font-black uppercase tracking-wider text-slate-400 leading-none">Coordinador</p>
                                    <p class="text-xs font-black text-slate-700 truncate leading-tight mt-0.5">{{ level.current_coordinator.name }}</p>
                                </div>
                                <div v-if="$can('sections.manage')" class="flex items-center gap-1 ml-1">
                                    <button 
                                        @click.stop="openCoordinatorModal(level)" 
                                        class="p-1 text-slate-400 hover:text-primary-600 transition-colors"
                                        title="Cambiar Coordinador"
                                    >
                                        <i class="fas fa-user-edit text-xs"></i>
                                    </button>
                                    <button 
                                        @click.stop="removeCoordinator(level)" 
                                        class="p-1 text-slate-300 hover:text-red-500 transition-colors"
                                        title="Remover Coordinador"
                                    >
                                        <i class="fas fa-times text-xs"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Sin Coordinador -->
                            <div v-else class="flex items-center gap-2">
                                <span class="px-2.5 py-1 bg-amber-50 text-amber-700 border border-amber-200/80 rounded-xl text-[10px] font-black uppercase tracking-wider flex items-center gap-1.5">
                                    <i class="fas fa-user-clock text-amber-500"></i>
                                    <span>Sin Coordinador</span>
                                </span>
                                <button 
                                    v-if="$can('sections.manage')"
                                    @click.stop="openCoordinatorModal(level)"
                                    class="px-3 py-1 bg-primary-50 hover:bg-primary-600 text-primary-600 hover:text-white border border-primary-200/80 rounded-xl text-[10px] font-black uppercase tracking-wider transition-all"
                                >
                                    + Asignar
                                </button>
                            </div>

                            <!-- Botón Rápido: Crear Sección en este año -->
                            <button
                                v-if="$can('sections.manage')"
                                @click.stop="openCreateModal(level.id)"
                                class="hidden sm:flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-[10px] font-black uppercase tracking-wider transition-all"
                                title="Crear sección en este año escolar"
                            >
                                <i class="fas fa-plus text-[9px]"></i>
                                <span>Sección</span>
                            </button>

                            <!-- Chevron Acordeón -->
                            <div 
                                class="w-8 h-8 rounded-full bg-slate-50 flex items-center justify-center text-slate-400 transition-transform shrink-0"
                                :class="activeLevel === level.name ? 'rotate-180 bg-primary-50 text-primary-600' : ''"
                            >
                                <i class="fas fa-chevron-down text-xs"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Cuerpo del Año: Tarjetas de Secciones -->
                    <transition
                        enter-active-class="transition-all duration-300 ease-in-out overflow-hidden"
                        enter-from-class="opacity-0 max-h-0"
                        enter-to-class="opacity-100 max-h-[2000px]"
                        leave-active-class="transition-all duration-200 ease-in-out overflow-hidden"
                        leave-from-class="opacity-100 max-h-[2000px]"
                        leave-to-class="opacity-0 max-h-0"
                    >
                        <div 
                            v-show="activeLevel === level.name"
                            class="p-4 sm:p-6 pt-3 bg-slate-50/50 border-t border-slate-100"
                        >
                            <!-- Grid de Secciones -->
                            <div v-if="level.sections.length > 0" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                                <div
                                    v-for="section in level.sections"
                                    :key="section.id"
                                    class="bg-white rounded-2xl p-5 relative overflow-hidden transition-all duration-300 group hover:-translate-y-1 shadow-xs border-2 border-slate-100 hover:border-primary-300 hover:shadow-md"
                                >
                                    <!-- Cabecera de la tarjeta de Sección -->
                                    <div class="flex justify-between items-start mb-3">
                                        <div class="w-11 h-11 bg-primary-50 text-primary-700 rounded-xl flex items-center justify-center text-xl shadow-xs font-black border border-primary-100/80">
                                            {{ section.name }}
                                        </div>
                                        <div class="flex gap-1.5 opacity-100 sm:opacity-0 group-hover:opacity-100 transition-opacity">
                                            <button 
                                                v-if="$can('sections.manage')" 
                                                @click="openEditModal(section)" 
                                                class="w-8 h-8 rounded-lg bg-slate-50 text-slate-400 hover:bg-primary-50 hover:text-primary-600 transition-all flex items-center justify-center border border-slate-200/80 shadow-xs cursor-pointer" 
                                                title="Editar Sección"
                                            >
                                                <i class="fas fa-edit text-xs"></i>
                                            </button>
                                            <button 
                                                v-if="section.enrollments_count === 0 && section.academic_loads_count === 0" 
                                                @click="destroy(section)" 
                                                class="w-8 h-8 rounded-lg bg-red-50 text-red-500 hover:bg-red-500 hover:text-white transition-all flex items-center justify-center border border-red-100 shadow-xs cursor-pointer" 
                                                title="Eliminar Sección"
                                            >
                                                <i class="fas fa-trash text-xs"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <!-- Datos de Sección -->
                                    <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-0.5">Sección Académica</h4>
                                    <p class="text-sm font-black text-slate-800 truncate mb-3">
                                        {{ level.name }} — "{{ section.name }}"
                                    </p>
                                    
                                    <!-- Capacidad y Ocupación -->
                                    <div class="space-y-2 pt-3 border-t border-slate-100">
                                        <div class="flex items-center justify-between text-xs">
                                            <span class="text-slate-400 font-bold flex items-center gap-1.5">
                                                <i class="fas fa-user-graduate text-[11px] text-primary-500"></i> Matriculados
                                            </span>
                                            <span class="font-black text-slate-700">
                                                {{ section.enrollments_count || 0 }} / {{ section.capacity }}
                                            </span>
                                        </div>
                                        
                                        <!-- Barra de progreso de cupos -->
                                        <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                                            <div 
                                                class="h-full rounded-full transition-all"
                                                :class="[
                                                    ((section.enrollments_count || 0) / section.capacity) >= 1 ? 'bg-red-500' :
                                                    ((section.enrollments_count || 0) / section.capacity) >= 0.8 ? 'bg-amber-500' : 'bg-primary-500'
                                                ]"
                                                :style="{ width: Math.min(100, Math.round(((section.enrollments_count || 0) / section.capacity) * 100)) + '%' }"
                                            ></div>
                                        </div>

                                        <div class="pt-1 flex items-center justify-between text-[10px]">
                                            <Link 
                                                :href="`/admin/enrollments?grade_level_id=${level.id}&section_id=${section.id}`"
                                                class="text-primary-600 font-bold hover:underline flex items-center gap-1"
                                            >
                                                <span>Ver Matrícula</span>
                                                <i class="fas fa-arrow-right text-[8px]"></i>
                                            </Link>
                                            <span class="text-slate-400 font-bold">
                                                {{ Math.max(0, section.capacity - (section.enrollments_count || 0)) }} cupos libres
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Estado Vacío: Sin secciones en este año -->
                            <div v-else class="p-8 text-center bg-white rounded-2xl border-2 border-dashed border-slate-200">
                                <div class="w-12 h-12 bg-slate-50 text-slate-300 rounded-full flex items-center justify-center mx-auto mb-2 text-lg">
                                    <i class="fas fa-folder-plus"></i>
                                </div>
                                <h4 class="text-sm font-black text-slate-700">No hay secciones en {{ level.name }}</h4>
                                <p class="text-xs text-slate-400 font-medium mt-0.5 max-w-sm mx-auto">
                                    Crea las secciones (ej. "A", "B") para comenzar a matricular estudiantes en este año escolar.
                                </p>
                                <button
                                    v-if="$can('sections.manage')"
                                    @click="openCreateModal(level.id)"
                                    class="mt-4 px-4 py-2 bg-primary-600 hover:bg-primary-500 text-white rounded-xl text-xs font-black uppercase tracking-wider shadow-sm transition-all inline-flex items-center gap-1.5"
                                >
                                    <i class="fas fa-plus text-[10px]"></i>
                                    <span>Crear Sección en {{ level.name }}</span>
                                </button>
                            </div>
                        </div>
                    </transition>
                </div>
            </div>

            <!-- Warning State: Sin Años Asignados -->
            <div v-else class="glass-card rounded-3xl p-16 sm:p-20 text-center animate-fade-in-up">
                <div class="w-16 h-16 bg-amber-50 rounded-full flex items-center justify-center mx-auto mb-4 text-amber-500">
                    <i class="fas fa-user-shield text-2xl"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-700">Sin Años Académicos Asignados</h3>
                <p class="text-slate-400 text-sm mt-1 max-w-md mx-auto">
                    No tienes años académicos asignados como coordinador para este período escolar. Comunícate con la dirección para tu asignación.
                </p>
            </div>
        </div>

        <!-- Modal para Crear / Editar Sección -->
        <Modal :show="showModal" @close="showModal = false" max-width="md">
            <div class="p-8">
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h3 class="text-2xl font-black text-slate-800">
                            {{ editingSection ? 'Editar' : 'Nueva' }} <span class="gradient-text">Sección</span>
                        </h3>
                        <p class="text-sm font-medium text-slate-400 mt-1">Configura los parámetros de la sección académica</p>
                    </div>
                    <button @click="showModal = false" class="w-10 h-10 rounded-full bg-slate-50 text-slate-400 hover:bg-slate-100 flex items-center justify-center transition-all shadow-sm">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="space-y-2">
                        <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Año Académico</label>
                        <div class="relative">
                            <select 
                                v-model="form.grade_level_id" 
                                class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all appearance-none cursor-pointer disabled:opacity-50 shadow-sm" 
                                :disabled="!!editingSection"
                                required
                            >
                                <option value="">Seleccione Año...</option>
                                <option v-for="lvl in levels" :key="lvl.id" :value="lvl.id">{{ lvl.name }}</option>
                            </select>
                            <i class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none text-xs"></i>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Nombre (ej. A, B, C)</label>
                            <input 
                                v-model="form.name" 
                                type="text" 
                                maxlength="5"
                                placeholder="A"
                                class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all shadow-sm uppercase" 
                                required
                            >
                        </div>
                        <div class="space-y-2">
                            <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Capacidad Máxima</label>
                            <input 
                                v-model="form.capacity" 
                                type="number" 
                                min="1" 
                                max="100" 
                                class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all shadow-sm" 
                                required
                            >
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                        <button type="button" @click="showModal = false" class="px-5 py-2.5 text-xs font-black uppercase tracking-widest text-slate-400 hover:text-slate-600 transition-all">
                            Cancelar
                        </button>
                        <button 
                            type="submit" 
                            :disabled="form.processing" 
                            class="px-8 py-3.5 bg-primary-600 text-white text-[11px] font-black uppercase tracking-widest rounded-2xl shadow-lg shadow-primary-600/20 hover:bg-primary-500 hover:-translate-y-0.5 transition-all disabled:opacity-50"
                        >
                            <i class="fas fa-save mr-1.5"></i>
                            Guardar Sección
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Modal para Asignar Coordinador de Año -->
        <Modal :show="showCoordinatorModal" @close="showCoordinatorModal = false" max-width="md">
            <div class="p-8">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-xl font-black text-slate-800">
                            Asignar Coordinador de <span class="gradient-text">{{ selectedLevelForCoordinator?.name }}</span>
                        </h3>
                        <p class="text-xs font-bold text-slate-400 mt-1">
                            Período: {{ years.find(y => y.id === schoolYearId)?.name || 'Período Activo' }}
                        </p>
                    </div>
                    <button @click="showCoordinatorModal = false" class="w-9 h-9 rounded-full bg-slate-50 text-slate-400 hover:bg-slate-100 flex items-center justify-center transition-all shadow-sm">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </div>

                <form @submit.prevent="submitCoordinator" class="space-y-6">
                    <div class="space-y-2">
                        <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">
                            Docente o Coordinador Responsable
                        </label>
                        <div class="relative">
                            <select 
                                v-model="coordinatorForm.user_id" 
                                class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all appearance-none cursor-pointer shadow-sm"
                            >
                                <option value="">Seleccione un docente o coordinador...</option>
                                <option v-for="teacher in teachers" :key="teacher.id" :value="teacher.id">
                                    {{ teacher.name }} ({{ teacher.email }})
                                </option>
                            </select>
                            <i class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none text-xs"></i>
                        </div>
                        <p class="text-[11px] text-slate-400 font-medium ml-1">
                            Este usuario será el responsable académico de supervisar la matrícula, secciones y rendimiento de {{ selectedLevelForCoordinator?.name }}.
                        </p>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                        <button 
                            type="button" 
                            @click="showCoordinatorModal = false" 
                            class="px-5 py-2.5 text-xs font-black uppercase tracking-widest text-slate-400 hover:text-slate-600 transition-all"
                        >
                            Cancelar
                        </button>
                        <button 
                            type="submit" 
                            :disabled="coordinatorForm.processing" 
                            class="px-7 py-3.5 bg-primary-600 text-white text-[11px] font-black uppercase tracking-widest rounded-2xl shadow-lg shadow-primary-600/20 hover:bg-primary-500 hover:-translate-y-0.5 transition-all disabled:opacity-50"
                        >
                            <i class="fas fa-save mr-1.5"></i>
                            Guardar Asignación
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AppLayout>
</template>