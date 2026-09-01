<script setup>
import { ref, watch } from 'vue'
import { router, useForm, Link } from '@inertiajs/vue3'
import axios from 'axios'
import AppLayout from '@/Components/Layout/AppLayout.vue'
import Modal from '@/Components/UI/Modal.vue'

const props = defineProps({
    students: Object,
    filters: Object,
})

const search = ref(props.filters.search || '')
const statusFilter = ref(props.filters.status_filter || 'regular')
const sortCol = ref(props.filters.sort || 'last_name')
const sortDir = ref(props.filters.direction || 'asc')
const showModal = ref(false)
const editingStudent = ref(null)

const form = useForm({
    first_name: '',
    last_name: '',
    cedula: '',
    birth_date: '',
    gender: 'M',
    status: 'regular',
    guardian_id: '',
})

const guardianSearchCedula = ref('')
const searchingGuardian = ref(false)
const guardianNotFound = ref(false)
const selectedGuardian = ref(null)

const showGuardianModal = ref(false)
const guardianFormErrors = ref({})
const guardianForm = useForm({
    cedula: '',
    name: '',
    phone: '',
    email: '',
})

function searchGuardian() {
    if (!guardianSearchCedula.value) return;
    searchingGuardian.value = true;
    guardianNotFound.value = false;
    selectedGuardian.value = null;
    form.guardian_id = '';
    
    axios.get('/admin/guardians/search', { params: { cedula: guardianSearchCedula.value } })
        .then(response => {
            if (response.data.guardian) {
                selectedGuardian.value = response.data.guardian;
                form.guardian_id = response.data.guardian.id;
            } else {
                guardianNotFound.value = true;
            }
        })
        .finally(() => {
            searchingGuardian.value = false;
        });
}

function openCreateGuardian() {
    guardianForm.reset()
    guardianForm.cedula = guardianSearchCedula.value
    guardianFormErrors.value = {}
    showGuardianModal.value = true
}

function submitGuardian() {
    axios.post('/admin/guardians', guardianForm.data())
        .then(response => {
            showGuardianModal.value = false;
            selectedGuardian.value = response.data.guardian;
            form.guardian_id = response.data.guardian.id;
            guardianSearchCedula.value = response.data.guardian.cedula;
            guardianNotFound.value = false;
        })
        .catch(error => {
            if (error.response && error.response.status === 422) {
                guardianFormErrors.value = error.response.data.errors;
            }
        });
}

function formatDate(dateStr) {
    if (!dateStr) return '—'
    const dateOnly = dateStr.split('T')[0]
    const parts = dateOnly.split('-')
    if (parts.length === 3) {
        return `${parts[2]}-${parts[1]}-${parts[0]}`
    }
    return dateStr
}

function doSearch() {
    router.get('/admin/students', { search: search.value, sort: sortCol.value, direction: sortDir.value, status_filter: statusFilter.value }, { preserveState: true, replace: true })
}

function setStatusFilter(status) {
    statusFilter.value = status;
    doSearch();
}

let searchTimeout = null;
watch(search, (value) => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        doSearch();
    }, 500); // 500ms debounce
});

// Update sorting when sortCol changes
watch(sortCol, () => {
    doSearch();
});

function formatPhone(value) {
    if (!value) return '';
    let val = value.replace(/[^0-9]/g, '');
    if (val.length > 0 && val[0] !== '0') {
        val = '0' + val;
    }
    if (val.length > 4) {
        val = val.substring(0, 4) + '-' + val.substring(4);
    }
    if (val.length > 12) {
        val = val.substring(0, 12);
    }
    return val;
}

function formatCedula(value) {
    if (!value) return '';
    let val = value.toUpperCase().replace(/[^VEP0-9]/g, '');
    if (val.length > 0 && !['V', 'E', 'P'].includes(val[0])) {
        if (/[0-9]/.test(val[0])) {
            val = 'V' + val;
        } else {
            val = val.substring(1);
        }
    }
    if (val.length > 9) {
        val = val.substring(0, 9);
    }
    if (val.length > 1) {
        val = val[0] + '-' + val.substring(1);
    }
    return val;
}

function openCreateModal() {
    editingStudent.value = null
    form.reset()
    form.clearErrors()
    selectedGuardian.value = null
    guardianSearchCedula.value = ''
    guardianNotFound.value = false
    showModal.value = true
}

function openEditModal(student) {
    editingStudent.value = student
    form.first_name = student.first_name
    form.last_name = student.last_name
    form.cedula = student.cedula
    form.birth_date = student.birth_date ? student.birth_date.split('T')[0] : ''
    form.gender = student.gender
    form.status = student.status
    form.guardian_id = student.guardian_id || ''
    
    if (student.guardian) {
        selectedGuardian.value = student.guardian
        guardianSearchCedula.value = student.guardian.cedula || ''
    } else {
        selectedGuardian.value = null
        guardianSearchCedula.value = ''
    }
    
    guardianNotFound.value = false
    form.clearErrors()
    showModal.value = true
}

function submit() {
    if (editingStudent.value) {
        form.put(`/admin/students/${editingStudent.value.id}`, {
            onSuccess: () => showModal.value = false,
        })
    } else {
        form.post('/admin/students', {
            onSuccess: () => showModal.value = false,
        })
    }
}
</script>

<template>
    <AppLayout title="Directorio de Estudiantes">
        <div class="space-y-6 max-w-7xl mx-auto pb-10">
            <!-- Header Section -->
            <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-6 animate-fade-in-up">
                <div>
                    <h1 class="text-3xl font-extrabold text-slate-800">
                        Directorio de <span class="gradient-text">Estudiantes</span>
                    </h1>
                    <p class="text-slate-400 font-bold mt-2 text-sm">Administra el registro y datos personales de los alumnos</p>
                </div>

                <div class="flex flex-col sm:flex-row gap-4 w-full lg:w-auto">
                    <button v-if="$can('students.create')" @click="openCreateModal"
                        class="flex items-center justify-center gap-2 px-6 py-3.5 bg-primary-600 text-white text-[11px] font-black uppercase tracking-widest rounded-2xl shadow-lg shadow-primary-600/20 hover:bg-primary-500 hover:-translate-y-0.5 transition-all"
                    >
                        <i class="fas fa-plus"></i>
                        Nuevo Estudiante
                    </button>
                </div>
            </div>

            <!-- Toolbar (Search, Filter, Sort) -->
            <div class="glass-card rounded-3xl p-5 shadow-xl animate-fade-in-up flex flex-col gap-5" style="animation-delay: 50ms">
                
                <!-- Buscador y Filtros -->
                <div class="flex flex-col md:flex-row gap-4 items-center justify-between w-full">
                    <!-- Status Tabs -->
                    <div class="flex flex-wrap gap-2 w-full md:w-auto overflow-x-auto custom-scrollbar pb-2 md:pb-0">
                        <button @click="setStatusFilter('all')" :class="statusFilter === 'all' ? 'bg-slate-800 text-white' : 'bg-slate-100 text-slate-500 hover:bg-slate-200'" class="px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all whitespace-nowrap">Todos</button>
                        <button @click="setStatusFilter('regular')" :class="statusFilter === 'regular' ? 'bg-emerald-500 text-white shadow-md shadow-emerald-500/20' : 'bg-slate-100 text-slate-500 hover:bg-slate-200'" class="px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all whitespace-nowrap">Regulares</button>
                        <button @click="setStatusFilter('graduated')" :class="statusFilter === 'graduated' ? 'bg-sky-500 text-white shadow-md shadow-sky-500/20' : 'bg-slate-100 text-slate-500 hover:bg-slate-200'" class="px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all whitespace-nowrap">Graduados</button>
                        <button @click="setStatusFilter('withdrawn')" :class="statusFilter === 'withdrawn' ? 'bg-gray-600 text-white shadow-md shadow-gray-500/20' : 'bg-slate-100 text-slate-500 hover:bg-slate-200'" class="px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all whitespace-nowrap">Retirados</button>
                        <button @click="setStatusFilter('suspended')" :class="statusFilter === 'suspended' ? 'bg-red-500 text-white shadow-md shadow-red-500/20' : 'bg-slate-100 text-slate-500 hover:bg-slate-200'" class="px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all whitespace-nowrap">Suspendidos</button>
                    </div>

                    <!-- Buscador -->
                    <div class="relative w-full md:w-96 shrink-0">
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Buscar por nombre, apellido o cédula..."
                            class="w-full pl-11 pr-4 py-2.5 bg-white border-2 border-slate-100 rounded-2xl text-slate-700 text-sm font-bold focus:border-primary-400 focus:ring-0 outline-none transition-all shadow-sm"
                        >
                        <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    </div>
                </div>

                <hr class="border-slate-100">

                <!-- Conteo y Ordenamiento -->
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">
                        Mostrando {{ students.data.length }} de {{ students.total }} registros
                    </span>

                    <div class="relative w-full sm:w-64 shrink-0">
                        <select v-model="sortCol" class="w-full bg-white border-2 border-slate-100 rounded-2xl px-4 py-2.5 text-slate-700 text-sm font-bold focus:border-primary-400 focus:ring-0 outline-none transition-all appearance-none shadow-sm cursor-pointer">
                            <option value="last_name">Ordenar por Apellido</option>
                            <option value="first_name">Ordenar por Nombre</option>
                            <option value="cedula">Ordenar por Cédula</option>
                            <option value="birth_date">Ordenar por Edad</option>
                        </select>
                        <i class="fas fa-sort absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                    </div>
                </div>
            </div>

            <!-- Listado de Tarjetas -->
            <div class="space-y-3 animate-fade-in-up" style="animation-delay: 100ms">
                <div v-if="students.data.length === 0" class="glass-card rounded-3xl p-16 text-center">
                    <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 text-slate-200">
                        <i class="fas fa-user-slash text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-400">No se encontraron estudiantes</h3>
                    <p class="text-slate-300 text-sm mt-1">Intenta con otro término de búsqueda o filtro.</p>
                </div>

                <div 
                    v-for="student in students.data" 
                    :key="student.id"
                    class="bg-white p-4 sm:p-5 rounded-2xl shadow-sm border-2 transition-all flex flex-col md:flex-row md:items-center justify-between gap-4 group hover:border-slate-300"
                    :class="[
                        student.status === 'regular' ? 'border-slate-100' : 'border-slate-100 opacity-90'
                    ]"
                >
                    <!-- Info Principal -->
                    <div class="flex items-center gap-4 flex-1">
                        <div class="w-12 h-12 rounded-full flex items-center justify-center font-black text-sm shrink-0 shadow-sm border"
                            :class="student.gender === 'F' ? 'bg-accent-50 text-accent-500 border-accent-100' : 'bg-blue-50 text-blue-500 border-blue-100'"
                        >
                            {{ student.last_name?.charAt(0) }}
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-base font-black text-slate-800 truncate">{{ student.last_name }}, {{ student.first_name }}</h3>
                            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 mt-1 text-xs font-bold text-slate-500">
                                <span class="flex items-center gap-1.5"><i class="fas fa-id-card opacity-50"></i> {{ student.cedula || 'Sin cédula' }}</span>
                                <span class="hidden sm:flex items-center gap-1.5"><i class="far fa-calendar-alt opacity-50"></i> {{ formatDate(student.birth_date) }}</span>
                                <span class="hidden md:flex items-center gap-1.5" v-if="student.guardian">
                                    <i class="fas fa-user-tie opacity-50"></i> Rep: {{ student.guardian.name.split(' ')[0] }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Estatus y Acciones -->
                    <div class="flex items-center justify-between md:justify-end gap-4 w-full md:w-auto">
                        
                        <!-- Status Badge -->
                        <div class="shrink-0 flex justify-center">
                            <span v-if="student.status === 'regular'" class="px-2.5 py-1 text-[10px] font-black uppercase tracking-widest rounded-lg border shadow-sm bg-emerald-50 text-emerald-600 border-emerald-100">
                                Regular
                            </span>
                            <span v-else-if="student.status === 'graduated'" class="px-2.5 py-1 text-[10px] font-black uppercase tracking-widest rounded-lg border shadow-sm bg-sky-50 text-sky-600 border-sky-100">
                                Graduado
                            </span>
                            <span v-else-if="student.status === 'withdrawn'" class="px-2.5 py-1 text-[10px] font-black uppercase tracking-widest rounded-lg border shadow-sm bg-gray-50 text-gray-600 border-gray-200">
                                Retirado
                            </span>
                            <span v-else-if="student.status === 'suspended'" class="px-2.5 py-1 text-[10px] font-black uppercase tracking-widest rounded-lg border shadow-sm bg-red-50 text-red-600 border-red-100">
                                Suspendido
                            </span>
                        </div>

                        <!-- Botones -->
                        <div class="flex items-center gap-2 bg-slate-50 p-1.5 rounded-xl border border-slate-100">
                            <Link 
                                :href="'/admin/students/' + student.id"
                                class="w-9 h-9 rounded-lg bg-white text-slate-500 hover:text-primary-600 hover:bg-primary-50 hover:border-primary-200 hover:shadow-sm transition-all border border-slate-200 flex items-center justify-center"
                                title="Ver Expediente"
                            >
                                <i class="fas fa-folder-open text-[11px]"></i>
                            </Link>
                            <button v-if="$can('students.edit')" @click="openEditModal(student)" 
                                class="w-9 h-9 rounded-lg bg-white text-slate-500 hover:text-primary-600 hover:bg-primary-50 hover:border-primary-200 hover:shadow-sm transition-all border border-slate-200 flex items-center justify-center"
                                title="Editar Estudiante"
                            >
                                <i class="fas fa-edit text-[11px]"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Paginación Inferior -->
            <div class="flex justify-center mt-6" v-if="students.links && students.links.length > 3">
                <div class="flex flex-wrap justify-center items-center gap-1.5 p-1 bg-white rounded-2xl shadow-sm border-2 border-slate-100">
                    <Link
                        v-for="(link, i) in students.links"
                        :key="i"
                        :href="link.url || '#'"
                        class="px-4 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all"
                        :class="[
                            link.active ? 'bg-primary-500 text-white shadow-sm shadow-primary-500/30' : 'bg-transparent text-slate-500 hover:bg-slate-100',
                            !link.url ? 'opacity-30 cursor-not-allowed' : 'cursor-pointer hover:-translate-y-0.5'
                        ]"
                        v-html="link.label.replace(/pagination\.previous|previous/i, '&laquo;').replace(/pagination\.next|next/i, '&raquo;')"
                    ></Link>
                </div>
            </div>
        </div>

        <!-- Create/Edit Modal (Same as before, stylized) -->
        <Modal :show="showModal" @close="showModal = false" max-width="2xl">
            <div class="p-8 max-h-[90vh] overflow-y-auto custom-scrollbar">
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h3 class="text-2xl font-black text-slate-800">
                            {{ editingStudent ? 'Editar' : 'Nuevo' }} <span class="text-primary-500">Estudiante</span>
                        </h3>
                        <p class="text-sm font-medium text-slate-400 mt-1">Completa los datos del expediente académico</p>
                    </div>
                    <button @click="showModal = false" class="w-10 h-10 rounded-full bg-slate-50 text-slate-400 hover:bg-slate-100 transition-all shadow-sm">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Nombres</label>
                            <input v-model="form.first_name" type="text" class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all shadow-sm" required>
                            <p v-if="form.errors.first_name" class="text-xs text-red-500 font-bold mt-1 ml-1">{{ form.errors.first_name }}</p>
                        </div>
                        <div class="space-y-2">
                            <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Apellidos</label>
                            <input v-model="form.last_name" type="text" class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all shadow-sm" required>
                            <p v-if="form.errors.last_name" class="text-xs text-red-500 font-bold mt-1 ml-1">{{ form.errors.last_name }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Cédula Escolar</label>
                            <input v-model="form.cedula" @input="form.cedula = formatCedula($event.target.value)" type="text" class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all shadow-sm" maxlength="12">
                            <p v-if="form.errors.cedula" class="text-xs text-red-500 font-bold mt-1 ml-1">{{ form.errors.cedula }}</p>
                        </div>
                        <div class="space-y-2">
                            <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Fecha de Nacimiento</label>
                            <div class="relative">
                                <input v-model="form.birth_date" type="date" class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all shadow-sm" required>
                            </div>
                            <p v-if="form.errors.birth_date" class="text-xs text-red-500 font-bold mt-1 ml-1">{{ form.errors.birth_date }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Género</label>
                            <div class="relative">
                                <select v-model="form.gender" class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all appearance-none cursor-pointer shadow-sm">
                                    <option value="M">Masculino</option>
                                    <option value="F">Femenino</option>
                                </select>
                                <i class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 pointer-events-none"></i>
                            </div>
                        </div>
                        <div v-if="editingStudent" class="space-y-2">
                            <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Estatus</label>
                            <div class="relative">
                                <select v-model="form.status" class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all appearance-none cursor-pointer shadow-sm">
                                    <option value="regular">Regular</option>
                                    <option value="withdrawn">Retirado</option>
                                    <option value="suspended">Suspendido</option>
                                </select>
                                <i class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 pointer-events-none"></i>
                            </div>
                        </div>
                    </div>

                    
                    <!-- Guardian Section -->
                    <div class="pt-6 border-t border-slate-100">
                        <h4 class="text-sm font-black text-slate-800 uppercase tracking-widest mb-4">Representante</h4>
                        
                        <div class="bg-slate-50 border border-slate-200 rounded-2xl p-6 relative shadow-sm">
                            <div v-if="selectedGuardian" class="flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 bg-primary-50 text-primary-500 rounded-full flex items-center justify-center shadow-sm">
                                        <i class="fas fa-user-tie"></i>
                                    </div>
                                    <div>
                                        <p class="font-bold text-slate-800">{{ selectedGuardian.name }}</p>
                                        <p class="text-xs text-slate-500 font-medium">C.I: {{ selectedGuardian.cedula || 'N/A' }} | Tel: {{ selectedGuardian.phone || 'N/A' }}</p>
                                    </div>
                                </div>
                                <button type="button" @click="selectedGuardian = null; form.guardian_id = ''; guardianSearchCedula = '';" class="text-[10px] font-black text-red-500 hover:text-white uppercase tracking-widest bg-red-50 hover:bg-red-500 px-3 py-2 rounded-lg border border-red-100 transition-colors shadow-sm">
                                    Cambiar
                                </button>
                            </div>
                            
                            <div v-else class="space-y-4">
                                <div>
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1 mb-2 block">Buscar por Cédula</label>
                                    <div class="flex gap-3">
                                        <div class="relative flex-1">
                                            <input v-model="guardianSearchCedula" @input="guardianSearchCedula = formatCedula($event.target.value)" @keyup.enter="searchGuardian" type="text" placeholder="Ej: V-12345678" class="w-full bg-white border-2 border-slate-200 rounded-xl px-4 py-2.5 text-slate-700 text-sm font-bold focus:border-primary-400 focus:ring-0 outline-none transition-all shadow-sm" maxlength="12">
                                        </div>
                                        <button type="button" @click="searchGuardian" :disabled="searchingGuardian || !guardianSearchCedula" class="px-5 py-2.5 bg-primary-600 text-white text-xs font-black uppercase tracking-widest rounded-xl shadow-md shadow-primary-600/20 hover:bg-primary-500 hover:-translate-y-0.5 transition-all disabled:opacity-50 disabled:translate-y-0">
                                            <i v-if="searchingGuardian" class="fas fa-spinner fa-spin"></i>
                                            <i v-else class="fas fa-search"></i>
                                        </button>
                                    </div>
                                    <p v-if="form.errors.guardian_id" class="text-xs text-red-500 font-bold mt-1 ml-1">{{ form.errors.guardian_id }}</p>
                                </div>
                                
                                <div v-if="guardianNotFound" class="bg-amber-50 border border-amber-200 rounded-xl p-4 flex items-center justify-between animate-fade-in-up shadow-sm">
                                    <p class="text-[11px] font-bold text-amber-700">No se encontró representante con esta cédula.</p>
                                    <button type="button" @click="openCreateGuardian" class="px-4 py-2 bg-amber-600 text-white text-[10px] font-black uppercase tracking-widest rounded-lg shadow-sm hover:bg-amber-500 transition-all">
                                        Registrar
                                    </button>
                                </div>
                            </div>
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
                            {{ editingStudent ? 'Guardar Cambios' : 'Registrar Estudiante' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Guardian Create Modal -->
        <Modal :show="showGuardianModal" @close="showGuardianModal = false" max-width="md">
            <div class="p-8">
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h3 class="text-xl font-black text-slate-800">
                            Nuevo <span class="text-primary-500">Representante</span>
                        </h3>
                        <p class="text-xs font-medium text-slate-400 mt-1">Ingresa los datos para registrar un nuevo apoderado.</p>
                    </div>
                    <button @click="showGuardianModal = false" class="w-8 h-8 rounded-full bg-slate-50 text-slate-400 hover:bg-slate-100 transition-all shadow-sm">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div class="space-y-4">
                    <div class="space-y-2">
                        <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Cédula</label>
                        <input v-model="guardianForm.cedula" @input="guardianForm.cedula = formatCedula($event.target.value)" type="text" class="w-full bg-slate-50 border-2 border-slate-200 rounded-xl px-4 py-2.5 text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all shadow-sm" maxlength="12">
                        <p v-if="guardianFormErrors && guardianFormErrors.cedula" class="text-xs text-red-500 font-bold mt-1 ml-1">{{ guardianFormErrors.cedula[0] }}</p>
                    </div>
                    <div class="space-y-2">
                        <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Nombre Completo</label>
                        <input v-model="guardianForm.name" type="text" class="w-full bg-slate-50 border-2 border-slate-200 rounded-xl px-4 py-2.5 text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all shadow-sm">
                        <p v-if="guardianFormErrors && guardianFormErrors.name" class="text-xs text-red-500 font-bold mt-1 ml-1">{{ guardianFormErrors.name[0] }}</p>
                    </div>
                    <div class="space-y-2">
                        <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Teléfono</label>
                        <input v-model="guardianForm.phone" @input="guardianForm.phone = formatPhone($event.target.value)" type="tel" placeholder="0414-1234567" maxlength="12" class="w-full bg-slate-50 border-2 border-slate-200 rounded-xl px-4 py-2.5 text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all shadow-sm">
                        <p v-if="guardianFormErrors && guardianFormErrors.phone" class="text-xs text-red-500 font-bold mt-1 ml-1">{{ guardianFormErrors.phone[0] }}</p>
                    </div>
                    <div class="space-y-2">
                        <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Correo Electrónico</label>
                        <input v-model="guardianForm.email" type="email" class="w-full bg-slate-50 border-2 border-slate-200 rounded-xl px-4 py-2.5 text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all shadow-sm">
                        <p v-if="guardianFormErrors && guardianFormErrors.email" class="text-xs text-red-500 font-bold mt-1 ml-1">{{ guardianFormErrors.email[0] }}</p>
                    </div>
                    
                    <div class="flex justify-end pt-6">
                        <button type="button" @click="submitGuardian" class="px-6 py-3 bg-primary-600 text-white text-[11px] font-black uppercase tracking-widest rounded-xl shadow-lg shadow-primary-600/20 hover:bg-primary-500 hover:-translate-y-0.5 transition-all">
                            Guardar y Seleccionar
                        </button>
                    </div>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>