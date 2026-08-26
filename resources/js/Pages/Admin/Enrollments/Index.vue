<script setup>
import { ref, computed } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'
import Modal from '@/Components/UI/Modal.vue'
import Swal from 'sweetalert2'

const props = defineProps({
    activeYear: Object,
    gradeLevels: Array,
    sections: Array,
    enrollments: Array,
    availableStudents: Array,
    filters: Object,
})

const gradeLevelId = ref(props.filters.grade_level_id || '')
const sectionId = ref(props.filters.section_id || '')
const searchStudent = ref('')
const searchEnrolled = ref('')
const sortCol = ref('student')
const sortDir = ref('asc')

const form = useForm({
    section_id: sectionId.value,
    student_id: '',
})

const showStatusModal = ref(false)
const showTransferModal = ref(false)
const targetEnrollment = ref(null)

const statusForm = useForm({
    status: 'active'
})

const transferForm = useForm({
    section_id: ''
})

function openStatusModal(enrollment) {
    targetEnrollment.value = enrollment
    statusForm.status = enrollment.status || 'active'
    showStatusModal.value = true
}

function openTransferModal(enrollment) {
    targetEnrollment.value = enrollment
    transferForm.section_id = ''
    showTransferModal.value = true
}

function updateStatus() {
    statusForm.patch(`/admin/enrollments/${targetEnrollment.value.id}/status`, {
        preserveScroll: true,
        onSuccess: () => showStatusModal.value = false
    })
}

function transferStudent() {
    transferForm.patch(`/admin/enrollments/${targetEnrollment.value.id}/transfer`, {
        preserveScroll: true,
        onSuccess: () => {
            showTransferModal.value = false
            loadData()
        }
    })
}

function loadData() {
    router.get('/admin/enrollments', { 
        grade_level_id: gradeLevelId.value,
        section_id: sectionId.value 
    }, { preserveState: true })
}

function onGradeChange() {
    sectionId.value = ''
    loadData()
}

const filteredStudents = computed(() => {
    if (!searchStudent.value) return props.availableStudents.slice(0, 50)
    const query = searchStudent.value.toLowerCase()
    return props.availableStudents.filter(s => 
        s.first_name.toLowerCase().includes(query) || 
        s.last_name.toLowerCase().includes(query) ||
        (s.cedula && s.cedula.toLowerCase().includes(query))
    ).slice(0, 50)
})

const processedEnrollments = computed(() => {
    let result = [...props.enrollments]

    if (searchEnrolled.value) {
        const query = searchEnrolled.value.toLowerCase()
        result = result.filter(enrollment => {
            const firstName = enrollment.student?.first_name?.toLowerCase() || ''
            const lastName = enrollment.student?.last_name?.toLowerCase() || ''
            const cedula = enrollment.student?.cedula?.toLowerCase() || ''
            return firstName.includes(query) || lastName.includes(query) || cedula.includes(query)
        })
    }

    result.sort((a, b) => {
        let valA = '', valB = ''
        
        if (sortCol.value === 'student') {
            valA = ((a.student?.last_name || '') + ' ' + (a.student?.first_name || '')).toLowerCase()
            valB = ((b.student?.last_name || '') + ' ' + (b.student?.first_name || '')).toLowerCase()
        } else if (sortCol.value === 'cedula') {
            valA = (a.student?.cedula || '').toLowerCase()
            valB = (b.student?.cedula || '').toLowerCase()
        } else if (sortCol.value === 'enrolled_at') {
            valA = a.enrolled_at || ''
            valB = b.enrolled_at || ''
        }

        if (valA < valB) return sortDir.value === 'asc' ? -1 : 1
        if (valA > valB) return sortDir.value === 'asc' ? 1 : -1
        return 0
    })

    return result
})

function enroll(studentId) {
    form.section_id = sectionId.value
    form.student_id = studentId
    
    form.post('/admin/enrollments', {
        preserveScroll: true,
        onSuccess: () => {
            searchStudent.value = ''
            form.student_id = ''
        }
    })
}

function destroy(id) {
    Swal.fire({
        title: '¿Seguro que desea eliminar a este estudiante de la sección?',
        text: 'Esta acción no se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#94a3b8',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/admin/enrollments/${id}`, { preserveScroll: true })
        }
    })
}
</script>

<template>
    <AppLayout title="Inscripciones">
        <div class="space-y-6 max-w-7xl mx-auto pb-10">
            <!-- Header Section -->
            <div class="flex flex-col lg:flex-row gap-6 items-center justify-between animate-fade-in-up">
                <div>
                    <div class="flex items-center gap-3">
                        <h2 class="text-3xl font-extrabold text-slate-800">
                            Inscripciones y <span class="gradient-text">Matriculación</span>
                        </h2>
                    </div>
                    <div class="flex items-center gap-2 mt-2">
                        <span class="text-slate-400 font-bold text-sm">Gestiona la matrícula de estudiantes.</span>
                        <span class="px-3 py-1 bg-primary-50 text-primary-600 rounded-lg text-[10px] font-black uppercase tracking-widest border border-primary-100 shadow-sm ml-2 hidden sm:block">
                            {{ activeYear ? activeYear.name : 'Sin Año Activo' }}
                        </span>
                    </div>
                </div>
                
                <div v-if="activeYear" class="flex flex-col sm:flex-row gap-4 w-full lg:w-auto">
                    <div class="relative flex-1 sm:min-w-[180px]">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1 absolute -top-5">Año (Nivel)</label>
                        <select 
                            v-model="gradeLevelId" 
                            @change="onGradeChange" 
                            class="w-full bg-white border-2 border-slate-100 rounded-2xl px-4 py-3.5 text-slate-700 text-sm font-bold focus:border-primary-400 focus:ring-0 outline-none transition-all appearance-none cursor-pointer shadow-sm"
                        >
                            <option value="">Seleccionar Nivel...</option>
                            <option v-for="grade in gradeLevels" :key="grade.id" :value="grade.id">{{ grade.name }}</option>
                        </select>
                        <i class="fas fa-layer-group absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 pointer-events-none"></i>
                    </div>

                    <div class="relative flex-1 sm:min-w-[180px]">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1 absolute -top-5">Sección</label>
                        <select 
                            v-model="sectionId" 
                            @change="loadData" 
                            class="w-full bg-white border-2 border-slate-100 rounded-2xl px-4 py-3.5 text-slate-700 text-sm font-bold focus:border-primary-400 focus:ring-0 outline-none transition-all appearance-none cursor-pointer shadow-sm disabled:opacity-50 disabled:bg-slate-50" 
                            :disabled="!gradeLevelId"
                        >
                            <option value="">Seleccionar Sección...</option>
                            <option v-for="sec in sections" :key="sec.id" :value="sec.id">{{ sec.name }}</option>
                        </select>
                        <i class="fas fa-users absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 pointer-events-none"></i>
                    </div>
                </div>
            </div>

            <!-- Warning States -->
            <div v-if="!activeYear" class="glass-card rounded-3xl p-20 text-center animate-fade-in-up mt-8">
                <div class="w-16 h-16 bg-amber-50 rounded-full flex items-center justify-center mx-auto mb-4 text-amber-500">
                    <i class="fas fa-exclamation-triangle text-2xl"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-800">Año Escolar Inactivo</h3>
                <p class="text-slate-400 text-sm mt-1 max-w-md mx-auto">Debes establecer un Año Escolar Activo desde Administración > Años Escolares para gestionar las inscripciones.</p>
            </div>

            <div v-else-if="!gradeLevelId || !sectionId" class="glass-card rounded-3xl p-20 text-center animate-fade-in-up mt-8">
                <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 text-slate-200">
                    <i class="fas fa-arrow-up text-2xl"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-400">Selecciona Nivel y Sección</h3>
                <p class="text-slate-300 text-sm mt-1">Para ver o matricular estudiantes, primero define el destino académico en los selectores superiores.</p>
            </div>

            <div v-else class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start mt-8">
                
                <!-- Columna Izquierda: Matricular (3/12 de ancho) -->
                <div class="lg:col-span-4 glass-card rounded-3xl p-6 flex flex-col h-[700px] animate-fade-in-up shadow-xl" style="animation-delay: 100ms">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 bg-primary-50 text-primary-600 rounded-xl flex items-center justify-center text-lg shadow-sm">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-black text-slate-800 leading-tight">Buscar Alumno</h3>
                            <p class="text-xs font-medium text-slate-400">Para inscribir en la sección</p>
                        </div>
                    </div>

                    <div class="relative group mb-4">
                        <input 
                            v-model="searchStudent" 
                            type="text" 
                            placeholder="Buscar por nombre o cédula..." 
                            class="w-full pl-11 pr-4 py-3 bg-white border-2 border-slate-100 rounded-2xl text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all shadow-sm"
                        >
                        <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-primary-500 transition-colors"></i>
                    </div>
                    
                    <div class="flex-1 overflow-y-auto pr-2 custom-scrollbar space-y-2">
                        <div 
                            v-for="student in filteredStudents" 
                            :key="student.id" 
                            class="p-4 bg-white border-2 border-slate-100 rounded-2xl hover:border-primary-200 hover:shadow-md transition-all flex justify-between items-center group cursor-default"
                        >
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-8 h-8 rounded-full bg-slate-50 text-slate-400 flex items-center justify-center text-[10px] font-black shrink-0 group-hover:bg-primary-50 group-hover:text-primary-600 transition-colors shadow-sm border border-slate-200">
                                    {{ student.last_name?.charAt(0) }}
                                </div>
                                <div class="min-w-0 truncate">
                                    <p class="text-xs font-black text-slate-700 truncate group-hover:text-primary-700 transition-colors">{{ student.last_name }}, {{ student.first_name }}</p>
                                    <p class="text-[10px] font-bold text-slate-400 flex items-center gap-1 mt-0.5"><i class="fas fa-id-card text-[8px] opacity-50"></i> {{ student.cedula || 'Sin cédula' }}</p>
                                </div>
                            </div>
                            <button 
                                v-if="$can('enrollments.manage') || $can('enrollments.create')"
                                @click="enroll(student.id)" 
                                :disabled="form.processing"
                                class="w-8 h-8 shrink-0 rounded-xl bg-primary-50 text-primary-600 flex items-center justify-center transition-all hover:bg-primary-600 hover:text-white shadow-sm disabled:opacity-50"
                                title="Inscribir"
                            >
                                <i class="fas fa-plus text-[10px]"></i>
                            </button>
                        </div>
                        <div v-if="filteredStudents.length === 0" class="text-center py-12">
                            <div class="w-12 h-12 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-3 text-slate-200">
                                <i class="fas fa-user-slash text-xl"></i>
                            </div>
                            <p class="text-xs font-bold text-slate-400">Sin resultados</p>
                        </div>
                    </div>
                </div>

                <!-- Columna Derecha: Inscritos (8/12 de ancho) -->
                <div class="lg:col-span-8 flex flex-col h-[700px] animate-fade-in-up" style="animation-delay: 200ms">
                    
                    <!-- Barra Superior Listado -->
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-4 mb-4">
                        <div class="flex items-center gap-3">
                            <span class="px-3 py-1.5 bg-emerald-50 text-emerald-600 text-[10px] font-black uppercase tracking-widest rounded-lg border border-emerald-100 shadow-sm flex items-center gap-2">
                                <i class="fas fa-users opacity-50"></i> {{ enrollments.length }} Registrados
                            </span>
                        </div>
                        
                        <!-- Buscador y Orden -->
                        <div class="flex w-full sm:w-auto flex-col sm:flex-row gap-2">
                            <div class="relative flex-1 min-w-[200px]">
                                <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                <input 
                                    v-model="searchEnrolled" 
                                    type="text" 
                                    placeholder="Buscar inscrito..." 
                                    class="w-full bg-white border-2 border-slate-100 rounded-2xl pl-10 pr-4 py-2.5 text-sm text-slate-700 placeholder:text-slate-400 focus:border-primary-400 focus:ring-0 outline-none transition-all shadow-sm"
                                >
                            </div>
                            <div class="relative w-full sm:w-48 shrink-0">
                                <select v-model="sortCol" class="w-full bg-white border-2 border-slate-100 rounded-2xl px-4 py-2.5 text-slate-700 text-sm font-bold focus:border-primary-400 focus:ring-0 outline-none transition-all appearance-none shadow-sm cursor-pointer">
                                    <option value="student">Ordenar por Apellido</option>
                                    <option value="cedula">Ordenar por Cédula</option>
                                    <option value="enrolled_at">Ordenar por Fecha</option>
                                </select>
                                <i class="fas fa-sort absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Lista de Tarjetas (Roster) -->
                    <div class="flex-1 overflow-y-auto custom-scrollbar space-y-3 pr-2">
                        <div v-if="processedEnrollments.length === 0" class="glass-card rounded-3xl p-16 text-center">
                            <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 text-slate-200">
                                <i class="fas fa-users-slash text-2xl"></i>
                            </div>
                            <h3 class="text-lg font-bold text-slate-400">Sección Vacía</h3>
                            <p class="text-slate-300 text-sm mt-1">Aún no hay estudiantes matriculados o no coinciden con la búsqueda.</p>
                        </div>

                        <div 
                            v-for="(enrollment, idx) in processedEnrollments" 
                            :key="enrollment.id"
                            class="bg-white p-4 rounded-2xl shadow-sm border-2 transition-all flex flex-col md:flex-row md:items-center justify-between gap-4 group"
                            :class="[
                                enrollment.status === 'active' ? 'border-slate-100 hover:border-slate-300' : 'border-slate-100 opacity-75 grayscale hover:grayscale-0 hover:border-slate-300'
                            ]"
                        >
                            <!-- Info Estudiante -->
                            <div class="flex items-center gap-4 flex-1">
                                <div class="w-10 h-10 rounded-full bg-slate-50 text-slate-400 flex items-center justify-center font-black text-xs shrink-0 shadow-sm border border-slate-200">
                                    {{ idx + 1 }}
                                </div>
                                <div class="min-w-0">
                                    <h3 class="text-sm font-black text-slate-800 truncate">{{ enrollment.student?.last_name }}, {{ enrollment.student?.first_name }}</h3>
                                    <p class="text-xs font-bold text-slate-500 mt-0.5 flex items-center gap-3">
                                        <span class="flex items-center gap-1.5"><i class="fas fa-id-card opacity-50"></i> {{ enrollment.student?.cedula || 'Sin cédula' }}</span>
                                        <span class="hidden sm:flex items-center gap-1.5"><i class="far fa-calendar-alt opacity-50"></i> {{ new Date(enrollment.enrolled_at).toLocaleDateString() }}</span>
                                    </p>
                                </div>
                            </div>

                            <!-- Panel Derecho: Estatus + Botones -->
                            <div class="flex items-center justify-between md:justify-end gap-4 w-full md:w-auto">
                                
                                <!-- Estatus Badge -->
                                <div class="flex justify-center shrink-0">
                                    <span v-if="enrollment.status === 'active'" class="px-2.5 py-1 text-[10px] font-black uppercase tracking-widest rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-100 shadow-sm">Activo</span>
                                    <span v-else-if="enrollment.status === 'promoted'" class="px-2.5 py-1 text-[10px] font-black uppercase tracking-widest rounded-lg bg-sky-50 text-sky-600 border border-sky-100 shadow-sm">Promovido</span>
                                    <span v-else-if="enrollment.status === 'withdrawn'" class="px-2.5 py-1 text-[10px] font-black uppercase tracking-widest rounded-lg bg-gray-50 text-gray-500 border border-gray-200 shadow-sm">Retirado</span>
                                    <span v-else class="px-2.5 py-1 text-[10px] font-black uppercase tracking-widest rounded-lg bg-slate-100 text-slate-500 border border-slate-200 shadow-sm">{{ enrollment.status }}</span>
                                </div>

                                <!-- Action Buttons -->
                                <div class="flex items-center gap-2 bg-slate-50 p-1.5 rounded-xl border border-slate-100">
                                    <button v-if="$can('enrollments.update_status')" @click="openStatusModal(enrollment)" 
                                        class="w-8 h-8 rounded-lg bg-white text-slate-500 hover:text-slate-800 hover:shadow-sm transition-all border border-slate-200 flex items-center justify-center"
                                        title="Cambiar Estatus"
                                    >
                                        <i class="fas fa-cog text-xs"></i>
                                    </button>
                                    <button v-if="$can('enrollments.transfer')" @click="openTransferModal(enrollment)" 
                                        class="w-8 h-8 rounded-lg bg-white text-sky-500 hover:bg-sky-50 hover:text-sky-600 hover:border-sky-200 hover:shadow-sm transition-all border border-slate-200 flex items-center justify-center"
                                        title="Transferir de Sección"
                                    >
                                        <i class="fas fa-exchange-alt text-xs"></i>
                                    </button>
                                    <button 
                                        v-if="$can('enrollments.manage') || $can('enrollments.delete')"
                                        @click="destroy(enrollment.id)" 
                                        class="w-8 h-8 rounded-lg bg-white text-red-400 hover:bg-red-50 hover:text-red-500 hover:border-red-200 hover:shadow-sm transition-all border border-slate-200 flex items-center justify-center"
                                        title="Eliminar Inscripción"
                                    >
                                        <i class="fas fa-trash-alt text-xs"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
        
        <!-- Status Modal (Same as before but stylized) -->
        <Modal :show="showStatusModal" @close="showStatusModal = false" maxWidth="md">
            <div class="p-8">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-12 h-12 rounded-2xl bg-slate-50 text-slate-500 flex items-center justify-center text-xl shadow-sm border border-slate-100">
                        <i class="fas fa-cog"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-black text-slate-800 leading-tight">Cambiar Estatus</h3>
                        <p class="text-xs font-medium text-slate-400 mt-1">Modificar estado de inscripción</p>
                    </div>
                </div>
                
                <form @submit.prevent="updateStatus" class="space-y-6">
                    <div class="space-y-2">
                        <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Estatus Actual</label>
                        <div class="relative">
                            <select v-model="statusForm.status" class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all appearance-none cursor-pointer shadow-sm">
                                <option value="active">Activo</option>
                                <option value="withdrawn">Retirado</option>
                                <option value="promoted">Promovido</option>
                                <option value="promoted_pending">Promovido con Pendientes</option>
                                <option value="failed">Reprobado</option>
                            </select>
                            <i class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 pointer-events-none"></i>
                        </div>
                    </div>
                    
                    <div class="flex items-center justify-end gap-4 mt-8">
                        <button type="button" @click="showStatusModal = false" class="px-6 py-3 text-sm font-black uppercase tracking-widest text-slate-400 hover:text-slate-600 transition-all">Cancelar</button>
                        <button type="submit" :disabled="statusForm.processing" class="px-8 py-3 bg-slate-800 text-white text-[11px] font-black uppercase tracking-widest rounded-2xl shadow-lg hover:bg-slate-700 hover:-translate-y-0.5 transition-all disabled:opacity-50">Guardar</button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Transfer Modal (Same as before but stylized) -->
        <Modal :show="showTransferModal" @close="showTransferModal = false" maxWidth="md">
            <div class="p-8">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-12 h-12 rounded-2xl bg-sky-50 text-sky-500 flex items-center justify-center text-xl shadow-sm border border-sky-100">
                        <i class="fas fa-exchange-alt"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-black text-slate-800 leading-tight">Transferir Sección</h3>
                        <p class="text-xs font-medium text-slate-400 mt-1">Mover estudiante conservando notas</p>
                    </div>
                </div>
                
                <form @submit.prevent="transferStudent" class="space-y-6">
                    <div class="space-y-2">
                        <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Sección Destino</label>
                        <div class="relative">
                            <select v-model="transferForm.section_id" required class="w-full bg-slate-50 border-2 border-sky-200 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-sky-400 focus:bg-white focus:ring-0 outline-none transition-all appearance-none cursor-pointer shadow-sm">
                                <option value="" disabled>Seleccione una sección</option>
                                <option v-for="sec in sections" :key="sec.id" :value="sec.id" :disabled="sec.id === targetEnrollment?.section_id">{{ sec.name }}</option>
                            </select>
                            <i class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-sky-300 pointer-events-none"></i>
                        </div>
                    </div>
                    
                    <div class="p-4 bg-sky-50 rounded-2xl border border-sky-100 flex gap-3 text-sky-800 text-sm shadow-sm">
                        <i class="fas fa-info-circle mt-0.5 opacity-70"></i>
                        <p class="leading-relaxed">El estudiante será transferido a la nueva sección. Todo su historial de notas (de este año escolar) viajará con él.</p>
                    </div>
                    
                    <div class="flex items-center justify-end gap-4 mt-8">
                        <button type="button" @click="showTransferModal = false" class="px-6 py-3 text-sm font-black uppercase tracking-widest text-slate-400 hover:text-slate-600 transition-all">Cancelar</button>
                        <button type="submit" :disabled="transferForm.processing" class="px-8 py-3 bg-sky-500 text-white text-[11px] font-black uppercase tracking-widest rounded-2xl shadow-lg shadow-sky-500/30 hover:bg-sky-600 hover:-translate-y-0.5 transition-all disabled:opacity-50">Transferir</button>
                    </div>
                </form>
            </div>
        </Modal>
    </AppLayout>
</template>