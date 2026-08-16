<script setup>
import { ref, computed } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'
import Modal from '@/Components/UI/Modal.vue'

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
    if (!searchStudent.value) return props.availableStudents.slice(0, 50) // max 50 for perf
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

function toggleSort(col) {
    if (sortCol.value === col) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
    } else {
        sortCol.value = col
        sortDir.value = 'asc'
    }
}

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
    if(confirm('¿Seguro que desea eliminar a este estudiante de la sección?')) {
        router.delete(`/admin/enrollments/${id}`, { preserveScroll: true })
    }
}
</script>

<template>
    <AppLayout title="Inscripciones">
        <div class="space-y-8 max-w-12xl mx-auto">
            <!-- Header Section -->
            <div class="flex flex-col lg:flex-row gap-6 items-center justify-between animate-fade-in-up">
                <div>
                    <h2 class="text-3xl font-extrabold text-slate-800">
                        Inscripciones y <span class="gradient-text">Matriculación</span>
                    </h2>
                    <div class="flex items-center gap-2 mt-2">
                        <span class="text-slate-400 font-medium">Periodo en curso:</span>
                        <span class="px-3 py-1 rounded-lg bg-primary-50 text-primary-600 text-[11px] font-black uppercase tracking-widest border border-primary-100 shadow-sm">
                            {{ activeYear ? activeYear.name : 'No Establecido' }}
                        </span>
                    </div>
                </div>
                
                <div v-if="activeYear" class="flex flex-col sm:flex-row gap-4 w-full lg:w-auto">
                    <div class="relative flex-1 sm:min-w-[200px]">
                        <select 
                            v-model="gradeLevelId" 
                            @change="onGradeChange" 
                            class="w-full bg-white border-2 border-slate-100 rounded-2xl px-4 py-3.5 text-slate-700 text-sm font-bold focus:border-primary-400 focus:ring-0 outline-none transition-all appearance-none cursor-pointer shadow-sm"
                        >
                            <option value="">Nivel (Año)</option>
                            <option v-for="grade in gradeLevels" :key="grade.id" :value="grade.id">{{ grade.name }}</option>
                        </select>
                        <i class="fas fa-layer-group absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 pointer-events-none"></i>
                    </div>

                    <div class="relative flex-1 sm:min-w-[200px]">
                        <select 
                            v-model="sectionId" 
                            @change="loadData" 
                            class="w-full bg-white border-2 border-slate-100 rounded-2xl px-4 py-3.5 text-slate-700 text-sm font-bold focus:border-primary-400 focus:ring-0 outline-none transition-all appearance-none cursor-pointer shadow-sm disabled:opacity-50" 
                            :disabled="!gradeLevelId"
                        >
                            <option value="">Sección</option>
                            <option v-for="sec in sections" :key="sec.id" :value="sec.id">{{ sec.name }}</option>
                        </select>
                        <i class="fas fa-users absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 pointer-events-none"></i>
                    </div>
                </div>
            </div>

            <!-- Warning States -->
            <div v-if="!activeYear" class="glass-card rounded-3xl p-20 text-center animate-fade-in-up">
                <div class="w-16 h-16 bg-amber-50 rounded-full flex items-center justify-center mx-auto mb-4 text-amber-500">
                    <i class="fas fa-exclamation-triangle text-2xl"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-800">Año Escolar Inactivo</h3>
                <p class="text-slate-400 text-sm mt-1 max-w-md mx-auto">Debes establecer un Año Escolar Activo desde Administración > Años Escolares para gestionar las inscripciones.</p>
            </div>

            <div v-else-if="!gradeLevelId || !sectionId" class="glass-card rounded-3xl p-20 text-center animate-fade-in-up">
                <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 text-slate-200">
                    <i class="fas fa-arrow-up text-2xl"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-400">Selecciona Nivel y Sección</h3>
                <p class="text-slate-300 text-sm mt-1">Para matricular estudiantes, primero define el destino académico en los selectores superiores.</p>
            </div>

            <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
                <!-- Enrollment Panel -->
                <div class="lg:col-span-1 glass-card rounded-3xl p-8 flex flex-col h-[650px] animate-fade-in-up shadow-xl" style="animation-delay: 100ms">
                    <div class="flex items-center gap-3 mb-8">
                        <div class="w-10 h-10 bg-primary-50 text-primary-600 rounded-xl flex items-center justify-center text-lg shadow-sm">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-black text-slate-800 leading-tight">Matricular Alumno</h3>
                            <p class="text-xs font-medium text-slate-400">Busca e inscribe nuevos estudiantes</p>
                        </div>
                    </div>

                    <div class="relative group mb-6">
                        <input 
                            v-model="searchStudent" 
                            type="text" 
                            placeholder="Buscar por nombre o cédula..." 
                            class="w-full pl-12 pr-4 py-4 bg-slate-50 border-2 border-slate-400 rounded-2xl text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all"
                        >
                        <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-300 group-focus-within:text-primary-500 transition-colors"></i>
                    </div>
                    
                    <div class="flex-1 overflow-y-auto pr-2 custom-scrollbar space-y-3">
                        <div 
                            v-for="student in filteredStudents" 
                            :key="student.id" 
                            class="p-4 bg-white border-2 border-slate-50 rounded-2xl hover:border-primary-200 hover:shadow-md transition-all flex justify-between items-center group cursor-default"
                        >
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-slate-50 text-slate-400 flex items-center justify-center text-[10px] font-black group-hover:bg-primary-50 group-hover:text-primary-600 transition-colors">
                                    {{ student.last_name?.charAt(0) }}
                                </div>
                                <div>
                                    <p class="text-xs font-black text-slate-700 leading-tight group-hover:text-primary-700 transition-colors">{{ student.last_name }}, {{ student.first_name }}</p>
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">{{ student.cedula || 'Sin cédula' }}</p>
                                </div>
                            </div>
                            <button 
                                @click="enroll(student.id)" 
                                :disabled="form.processing"
                                class="w-8 h-8 rounded-xl bg-primary-50 text-primary-600 flex items-center justify-center lg:opacity-0 group-hover:opacity-100 transition-all hover:bg-primary-600 hover:text-white shadow-sm"
                                title="Inscribir"
                            >
                                <i class="fas fa-plus text-[10px]"></i>
                            </button>
                        </div>
                        <div v-if="filteredStudents.length === 0" class="text-center py-12">
                            <div class="w-12 h-12 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-3 text-slate-200">
                                <i class="fas fa-user-slash text-xl"></i>
                            </div>
                            <p class="text-xs font-bold text-slate-400">Sin estudiantes disponibles</p>
                        </div>
                    </div>
                </div>

                <!-- Section Enrollment List -->
                <div class="lg:col-span-2 glass-card rounded-3xl overflow-hidden shadow-2xl flex flex-col h-[650px] animate-fade-in-up" style="animation-delay: 200ms">
                    <div class="p-6 border-b border-slate-50 flex justify-between items-center bg-slate-50/50">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-white text-primary-500 rounded-lg flex items-center justify-center shadow-sm">
                                <i class="fas fa-users text-sm"></i>
                            </div>
                            <h3 class="font-black text-slate-800 uppercase tracking-widest text-xs">Estudiantes Inscritos</h3>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="relative hidden sm:block">
                                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                                <input 
                                    v-model="searchEnrolled"
                                    type="text" 
                                    placeholder="Buscar inscrito..." 
                                    class="w-48 bg-white border border-slate-200 rounded-xl pl-8 pr-3 py-1.5 text-xs text-slate-700 placeholder:text-slate-400 focus:border-primary-400 focus:ring-0 outline-none transition-all shadow-sm"
                                >
                            </div>
                            <span class="px-3 py-1 bg-emerald-50 text-emerald-600 text-[10px] font-black uppercase tracking-widest rounded-lg border border-emerald-100">{{ enrollments.length }} Registrados</span>
                        </div>
                    </div>
                    
                    <div class="flex-1 overflow-y-auto custom-scrollbar">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-slate-50 text-slate-400 text-[10px] uppercase font-black tracking-[0.2em] sticky top-0 z-10 shadow-sm">
                                <tr>
                                    <th @click="toggleSort('student')" class="px-8 py-4 cursor-pointer hover:bg-slate-100 hover:text-slate-600 transition-colors select-none group">
                                        Estudiante
                                        <span v-if="sortCol === 'student'" class="ml-1 text-primary-500">
                                            <i class="fas" :class="sortDir === 'asc' ? 'fa-sort-up' : 'fa-sort-down'"></i>
                                        </span>
                                        <span v-else class="ml-1 opacity-0 group-hover:opacity-100 transition-opacity"><i class="fas fa-sort"></i></span>
                                    </th>
                                    <th @click="toggleSort('cedula')" class="px-8 py-4 cursor-pointer hover:bg-slate-100 hover:text-slate-600 transition-colors select-none group">
                                        Cédula
                                        <span v-if="sortCol === 'cedula'" class="ml-1 text-primary-500">
                                            <i class="fas" :class="sortDir === 'asc' ? 'fa-sort-up' : 'fa-sort-down'"></i>
                                        </span>
                                        <span v-else class="ml-1 opacity-0 group-hover:opacity-100 transition-opacity"><i class="fas fa-sort"></i></span>
                                    </th>
                                    <th @click="toggleSort('enrolled_at')" class="px-8 py-4 cursor-pointer hover:bg-slate-100 hover:text-slate-600 transition-colors select-none group">
                                        Inscrito el
                                        <span v-if="sortCol === 'enrolled_at'" class="ml-1 text-primary-500">
                                            <i class="fas" :class="sortDir === 'asc' ? 'fa-sort-up' : 'fa-sort-down'"></i>
                                        </span>
                                        <span v-else class="ml-1 opacity-0 group-hover:opacity-100 transition-opacity"><i class="fas fa-sort"></i></span>
                                    </th>
                                    <th class="px-8 py-4 cursor-pointer hover:bg-slate-100 hover:text-slate-600 transition-colors select-none">
                                        Estatus
                                    </th>
                                    <th class="px-8 py-4 text-right"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <tr v-for="enrollment in processedEnrollments" :key="enrollment.id" class="group hover:bg-slate-50 transition-colors" :class="{'opacity-60': enrollment.status !== 'active'}">
                                    <td class="px-8 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center text-[10px] font-black group-hover:bg-primary-50 group-hover:text-primary-600 transition-all shadow-sm">
                                                {{ enrollment.student?.last_name?.charAt(0) }}
                                            </div>
                                            <span class="font-black text-slate-700 group-hover:text-primary-700 transition-colors">
                                                {{ enrollment.student?.last_name }}, {{ enrollment.student?.first_name }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-8 py-4 font-bold text-slate-400 tracking-widest text-[11px]">{{ enrollment.student?.cedula }}</td>
                                    <td class="px-8 py-4">
                                        <div class="flex items-center gap-2 text-slate-400 font-medium">
                                            <i class="far fa-calendar-check text-[10px]"></i>
                                            {{ new Date(enrollment.enrolled_at).toLocaleDateString() }}
                                        </div>
                                    </td>
                                    <td class="px-8 py-4">
                                        <span v-if="enrollment.status === 'active'" class="px-2 py-1 text-[10px] font-black uppercase tracking-wider rounded-md bg-emerald-50 text-emerald-600 border border-emerald-100">Activo</span>
                                        <span v-else-if="enrollment.status === 'promoted'" class="px-2 py-1 text-[10px] font-black uppercase tracking-wider rounded-md bg-sky-50 text-sky-600 border border-sky-100">Promovido</span>
                                        <span v-else-if="enrollment.status === 'withdrawn'" class="px-2 py-1 text-[10px] font-black uppercase tracking-wider rounded-md bg-gray-100 text-gray-500 border border-gray-200">Retirado</span>
                                        <span v-else class="px-2 py-1 text-[10px] font-black uppercase tracking-wider rounded-md bg-slate-100 text-slate-500 border border-slate-200">{{ enrollment.status }}</span>
                                    </td>
                                    <td class="px-8 py-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <button 
                                                @click="openStatusModal(enrollment)" 
                                                class="w-8 h-8 rounded-xl bg-slate-50 text-slate-400 hover:bg-slate-200 hover:text-slate-600 transition-all border border-transparent shadow-sm"
                                                title="Cambiar Estatus"
                                            >
                                                <i class="fas fa-cog text-[10px]"></i>
                                            </button>
                                            <button 
                                                @click="openTransferModal(enrollment)" 
                                                class="w-8 h-8 rounded-xl bg-sky-50 text-sky-400 hover:bg-sky-500 hover:text-white transition-all border border-transparent shadow-sm"
                                                title="Transferir de Sección"
                                            >
                                                <i class="fas fa-exchange-alt text-[10px]"></i>
                                            </button>
                                            <button 
                                                @click="destroy(enrollment.id)" 
                                                class="w-8 h-8 rounded-xl bg-red-50 text-red-400 hover:bg-red-500 hover:text-white transition-all border border-transparent shadow-sm"
                                                title="Eliminar Inscripción"
                                            >
                                                <i class="fas fa-trash-alt text-[10px]"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="processedEnrollments.length === 0">
                                    <td colspan="4" class="px-8 py-32 text-center">
                                        <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 text-slate-200">
                                            <i class="fas fa-users-slash text-2xl"></i>
                                        </div>
                                        <h3 class="text-lg font-bold text-slate-400">Sección Vacía</h3>
                                        <p class="text-slate-300 text-sm mt-1">Aún no hay estudiantes matriculados en esta sección.</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Status Modal -->
        <Modal :show="showStatusModal" @close="showStatusModal = false" maxWidth="md">
            <div class="p-8">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-12 h-12 rounded-2xl bg-slate-50 text-slate-400 flex items-center justify-center text-xl shadow-sm">
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
                            <select v-model="statusForm.status" class="w-full bg-slate-50 border-2 border-slate-400 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all appearance-none cursor-pointer">
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

        <!-- Transfer Modal -->
        <Modal :show="showTransferModal" @close="showTransferModal = false" maxWidth="md">
            <div class="p-8">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-12 h-12 rounded-2xl bg-sky-50 text-sky-500 flex items-center justify-center text-xl shadow-sm">
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
                            <select v-model="transferForm.section_id" required class="w-full bg-slate-50 border-2 border-sky-200 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-sky-400 focus:bg-white focus:ring-0 outline-none transition-all appearance-none cursor-pointer">
                                <option value="" disabled>Seleccione una sección</option>
                                <option v-for="sec in sections" :key="sec.id" :value="sec.id" :disabled="sec.id === targetEnrollment?.section_id">{{ sec.name }}</option>
                            </select>
                            <i class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 pointer-events-none"></i>
                        </div>
                    </div>
                    
                    <div class="p-4 bg-sky-50 rounded-2xl border border-sky-100 flex gap-3 text-sky-800 text-sm">
                        <i class="fas fa-info-circle mt-0.5"></i>
                        <p>El estudiante será transferido a la nueva sección. Todo su historial de notas (de este año escolar) viajará con él.</p>
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
