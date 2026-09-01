<template>
    <AppLayout title="Carga Académica">
        <div class="space-y-6">
            <!-- Header & Filter -->
            <div class="glass-card rounded-2xl p-6 flex flex-col md:flex-row gap-4 justify-between items-center">
                <div>
                    <h2 class="text-2xl font-bold text-slate-800">Planificación de Carga Académica</h2>
                    <p class="text-slate-500 text-sm">Asigna docentes a las materias por sección</p>
                </div>
                
                <div class="w-full md:w-64">
                    <label class="block text-sm font-bold text-slate-700 mb-1">Año Escolar</label>
                    <select v-model="schoolYearId" @change="loadData" class="w-full rounded-xl border-slate-200 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <option value="">Seleccione un año...</option>
                        <option v-for="year in years" :key="year.id" :value="year.id">
                            {{ year.name }}
                        </option>
                    </select>
                </div>
            </div>

            <!-- Tree Structure -->
            <div v-if="tree.length > 0" class="space-y-4">
                <div v-for="grade in tree" :key="grade.id" class="glass-card rounded-2xl overflow-hidden shadow-sm">
                    <!-- Accordion Header (Grade Level) -->
                    <button 
                        @click="toggleGrade(grade.id)"
                        class="w-full flex justify-between items-center p-5 bg-white hover:bg-slate-50 transition-colors border-b border-slate-100"
                    >
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-primary-50 text-primary-600 flex items-center justify-center font-extrabold text-xl">
                                {{ grade.name.charAt(0) }}
                            </div>
                            <h3 class="font-bold text-slate-700 text-lg">{{ grade.name }}</h3>
                            
                            <!-- Progress Badge (Aggregated for all sections in grade) -->
                            <span class="ml-4 px-3 py-1 rounded-full text-xs font-bold" 
                                  :class="getGradeCompletionClass(grade)">
                                {{ getGradeAssignedCount(grade) }} / {{ getGradeTotalCount(grade) }} Asignadas
                            </span>
                        </div>
                        <i class="fas fa-chevron-down transition-transform duration-300" :class="{'rotate-180': openGrades.includes(grade.id)}"></i>
                    </button>

                    <!-- Accordion Body -->
                    <div v-show="openGrades.includes(grade.id)" class="p-5 bg-slate-50/50 space-y-6">
                        
                        <!-- Sections within Grade Level -->
                        <div v-for="(section, sIndex) in grade.sections" :key="section.id" class="bg-white rounded-2xl p-4 border border-slate-100 shadow-sm relative transition-all" :style="{ zIndex: 50 - sIndex }">
                            <!-- Section Accordion Header -->
                            <button @click="toggleSection(section.id)" class="w-full flex justify-between items-center bg-white hover:opacity-80 transition-opacity">
                                <h4 class="font-bold text-slate-800 text-md flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-primary-50 text-primary-600 flex items-center justify-center text-sm font-bold shadow-sm">
                                        {{ section.name }}
                                    </div>
                                    Sección {{ section.name }}
                                </h4>
                                <div class="flex items-center gap-4">
                                    <span class="px-3 py-1 rounded-full text-xs font-bold" :class="getSectionCompletionClass(section)">
                                        {{ getSectionAssignedCount(section) }} / {{ section.subjects.length }}
                                    </span>
                                    <div class="w-6 h-6 flex items-center justify-center rounded-full bg-slate-50 text-slate-400">
                                        <i class="fas fa-chevron-down transition-transform duration-300" :class="{'rotate-180': openSections.includes(section.id)}"></i>
                                    </div>
                                </div>
                            </button>
                            
                            <!-- Subjects Grid -->
                            <div v-show="openSections.includes(section.id)" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 mt-5 pt-5 border-t border-slate-100">
                                <div v-for="(subject, index) in section.subjects" :key="subject.id" class="bg-slate-50 rounded-xl p-4 border border-slate-100 flex flex-col justify-between transition-all hover:shadow-md hover:border-primary-200 hover:bg-white relative" :style="{ zIndex: 100 - index }">
                                    <div class="mb-3">
                                        <div class="font-bold text-slate-700 flex items-center gap-2 text-sm">
                                            <i class="fas fa-book text-primary-400"></i>
                                            {{ subject.name }}
                                        </div>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 mb-1">Docente Asignado</label>
                                        <div class="flex gap-2">
                                            <div class="flex-1">
                                                <SearchableSelect
                                                    :modelValue="subject.teacher_id"
                                                    :options="teachersOptions"
                                                    placeholder="Sin asignar"
                                                    @update:modelValue="handleTeacherChange(section, subject, $event)"
                                                    :disabled="!$can('academic_load.assign')"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
            
            <div v-else-if="schoolYearId" class="glass-card rounded-2xl p-12 text-center text-slate-500">
                <div class="text-4xl mb-4 text-slate-300"><i class="fas fa-folder-open"></i></div>
                <p>No hay secciones configuradas para este año escolar.</p>
            </div>
            <div v-else class="glass-card rounded-2xl p-12 text-center text-slate-500">
                <div class="text-4xl mb-4 text-slate-300"><i class="fas fa-calendar-alt"></i></div>
                <p>Seleccione un año escolar para gestionar la carga académica.</p>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'
import SearchableSelect from '@/Components/UI/SearchableSelect.vue'
import Swal from 'sweetalert2'

const props = defineProps({
    tree: { type: Array, default: () => [] },
    years: { type: Array, default: () => [] },
    teachers: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
})

const schoolYearId = ref(props.filters.school_year_id || '')
const openGrades = ref([])
const openSections = ref([]) // IDs de secciones expandidas

// Automáticamente abrir el primer año y su primera sección si hay datos
watch(() => props.tree, (newTree) => {
    if (newTree.length > 0 && openGrades.value.length === 0) {
        openGrades.value = [newTree[0].id]
        if (newTree[0].sections.length > 0 && openSections.value.length === 0) {
            openSections.value = [newTree[0].sections[0].id]
        }
    }
}, { immediate: true })

function toggleSection(id) {
    const index = openSections.value.indexOf(id)
    if (index > -1) {
        openSections.value.splice(index, 1)
    } else {
        openSections.value.push(id)
    }
}

const teachersOptions = computed(() => {
    // FIX: SearchableSelect requires 'value' and 'label' properties, not 'id' and 'name'
    return props.teachers.map(t => ({ value: t.id, label: t.name }))
})

function loadData() {
    router.get('/admin/academic-loads', { 
        school_year_id: schoolYearId.value
    }, { preserveState: true })
}

function toggleGrade(id) {
    const index = openGrades.value.indexOf(id)
    if (index > -1) {
        openGrades.value.splice(index, 1)
    } else {
        openGrades.value.push(id)
    }
}

// Section Progress
function getSectionAssignedCount(section) {
    return section.subjects.filter(s => s.teacher_id).length
}

function getSectionCompletionClass(section) {
    const count = getSectionAssignedCount(section)
    const total = section.subjects.length
    if (total === 0) return 'bg-slate-100 text-slate-500'
    if (count === 0) return 'bg-rose-100 text-rose-600'
    if (count < total) return 'bg-amber-100 text-amber-600'
    return 'bg-emerald-100 text-emerald-600'
}

// Grade Progress
function getGradeAssignedCount(grade) {
    return grade.sections.reduce((acc, section) => acc + getSectionAssignedCount(section), 0)
}

function getGradeTotalCount(grade) {
    return grade.sections.reduce((acc, section) => acc + section.subjects.length, 0)
}

function getGradeCompletionClass(grade) {
    const count = getGradeAssignedCount(grade)
    const total = getGradeTotalCount(grade)
    if (total === 0) return 'bg-slate-100 text-slate-500'
    if (count === 0) return 'bg-rose-100 text-rose-600 border border-rose-200'
    if (count < total) return 'bg-amber-100 text-amber-600 border border-amber-200'
    return 'bg-emerald-100 text-emerald-600 border border-emerald-200'
}

function assignTeacher(sectionId, subjectId, teacherId) {
    router.post('/admin/academic-loads/assign', {
        school_year_id: schoolYearId.value,
        section_id: sectionId,
        subject_id: subjectId,
        teacher_id: teacherId || null
    }, {
        preserveScroll: true,
        preserveState: true,
    })
}

function handleTeacherChange(section, subject, newTeacherId) {
    // Si es el mismo, no hacer nada
    if (newTeacherId === subject.teacher_id) return;

    if (!newTeacherId) {
        Swal.fire({
            title: '¿Remover docente?',
            text: `¿Estás seguro de quitar al docente de ${subject.name}?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'Sí, remover',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                subject.teacher_id = null;
                assignTeacher(section.id, subject.id, null);
            } else {
                revertSearchableSelect(subject);
            }
        });
    } else {
        const teacherName = teachersOptions.value.find(t => t.value === newTeacherId)?.label;
        Swal.fire({
            title: 'Confirmar Asignación',
            html: `¿Asignar a <b>${teacherName}</b> a la materia de <b>${subject.name}</b>?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3b82f6',
            confirmButtonText: 'Sí, asignar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                subject.teacher_id = newTeacherId;
                assignTeacher(section.id, subject.id, newTeacherId);
            } else {
                revertSearchableSelect(subject);
            }
        });
    }
}

// Pequeño hack para forzar la reactividad en el hijo si se cancela la alerta
// Dado que el hijo modificó su 'searchQuery' asumiendo que el cambio se aprobaría.
function revertSearchableSelect(subject) {
    const original = subject.teacher_id;
    subject.teacher_id = -1; // Temporal invalido
    setTimeout(() => {
        subject.teacher_id = original;
    }, 10);
}
</script>