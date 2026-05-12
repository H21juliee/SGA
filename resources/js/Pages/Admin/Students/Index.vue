<script setup>
import { ref } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'
import Modal from '@/Components/UI/Modal.vue'

const props = defineProps({
    students: Object,
    filters: Object,
})

const search = ref(props.filters.search || '')
const showModal = ref(false)
const editingStudent = ref(null)

const form = useForm({
    first_name: '',
    last_name: '',
    cedula: '',
    birth_date: '',
    gender: 'M',
    is_active: true,
})

function doSearch() {
    router.get('/admin/students', { search: search.value }, { preserveState: true, replace: true })
}

function openCreateModal() {
    editingStudent.value = null
    form.reset()
    form.clearErrors()
    showModal.value = true
}

function openEditModal(student) {
    editingStudent.value = student
    form.first_name = student.first_name
    form.last_name = student.last_name
    form.cedula = student.cedula
    form.birth_date = student.birth_date
    form.gender = student.gender
    form.is_active = student.is_active
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
    <AppLayout title="Gestión de Estudiantes">
        <div class="space-y-8 max-w-7xl mx-auto">
            <!-- Header & Toolbar -->
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 animate-fade-in-up">
                <div>
                    <h1 class="text-3xl font-extrabold text-slate-800">
                        Gestión de <span class="gradient-text">Estudiantes</span>
                    </h1>
                    <p class="text-slate-400 font-medium mt-2">Administra el registro y datos personales de los alumnos</p>
                </div>

                <div class="flex flex-col sm:flex-row gap-4 w-full lg:w-auto">
                    <div class="relative group flex-1 sm:min-w-[400px]">
                        <input
                            v-model="search"
                            @keyup.enter="doSearch"
                            type="text"
                            placeholder="Buscar por nombre o cédula..."
                            class="w-full pl-12 pr-4 py-3.5 bg-white border-2 border-slate-100 rounded-2xl text-slate-700 text-sm font-bold focus:border-primary-400 focus:ring-0 outline-none transition-all shadow-sm"
                        >
                        <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-300 group-focus-within:text-primary-500 transition-colors"></i>
                    </div>
                    <button
                        @click="openCreateModal"
                        class="flex items-center justify-center gap-2 px-6 py-3.5 bg-primary-600 text-white text-[11px] font-black uppercase tracking-widest rounded-2xl shadow-lg shadow-primary-600/20 hover:bg-primary-500 hover:-translate-y-0.5 transition-all"
                    >
                        <i class="fas fa-plus"></i>
                        Nuevo Estudiante
                    </button>
                </div>
            </div>

            <!-- Table Container -->
            <div class="glass-card rounded-3xl overflow-hidden shadow-2xl animate-fade-in-up" style="animation-delay: 100ms">
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-50 text-slate-400 text-[10px] uppercase font-black tracking-[0.2em]">
                        <tr>
                            <th class="px-8 py-5">Apellidos y Nombres</th>
                            <th class="px-8 py-5">Cédula</th>
                            <th class="px-8 py-5">Fecha Nac.</th>
                            <th class="px-8 py-5">Estado</th>
                            <th class="px-8 py-5 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="student in students.data" :key="student.id" class="group hover:bg-slate-50 transition-colors">
                            <td class="px-8 py-5">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 font-bold group-hover:bg-primary-50 group-hover:text-primary-500 transition-colors">
                                        {{ student.last_name?.charAt(0) }}
                                    </div>
                                    <div class="font-black text-slate-700 text-base group-hover:text-primary-700 transition-colors">
                                        {{ student.last_name }}, {{ student.first_name }}
                                    </div>
                                </div>
                            </td>
                            <td class="px-8 py-5">
                                <span class="font-bold text-slate-400 tracking-wider">
                                    {{ student.cedula || '—' }}
                                </span>
                            </td>
                            <td class="px-8 py-5 text-slate-400 font-medium">
                                <i class="far fa-calendar-alt mr-2 opacity-50"></i>
                                {{ student.birth_date }}
                            </td>
                            <td class="px-8 py-5">
                                <span
                                    class="px-2.5 py-1 text-[10px] font-black uppercase tracking-wider rounded-lg border shadow-sm"
                                    :class="student.is_active 
                                        ? 'bg-emerald-50 text-emerald-600 border-emerald-100' 
                                        : 'bg-red-50 text-red-600 border-red-100'"
                                >
                                    {{ student.is_active ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td class="px-8 py-5 text-right">
                                <button 
                                    @click="openEditModal(student)" 
                                    class="w-10 h-10 rounded-xl bg-slate-50 text-slate-400 hover:text-primary-600 hover:bg-primary-50 transition-all border border-transparent hover:border-primary-100"
                                    title="Editar Estudiante"
                                >
                                    <i class="fas fa-edit"></i>
                                </button>
                            </td>
                        </tr>
                        <tr v-if="students.data.length === 0">
                            <td colspan="5" class="px-8 py-20 text-center">
                                <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 text-slate-200">
                                    <i class="fas fa-user-slash text-2xl"></i>
                                </div>
                                <h3 class="text-lg font-bold text-slate-400">No se encontraron estudiantes</h3>
                                <p class="text-slate-300 text-sm mt-1">Intenta con otro término de búsqueda.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Info -->
            <div class="flex items-center justify-between px-4 animate-fade-in-up" style="animation-delay: 200ms">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">
                    Mostrando {{ students.data.length }} de {{ students.total }} registros
                </span>
                <!-- Simple Pagination buttons could go here -->
            </div>
        </div>

        <!-- Form Modal -->
        <Modal :show="showModal" @close="showModal = false" max-width="2xl">
            <div class="p-8">
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h3 class="text-2xl font-black text-slate-800">
                            {{ editingStudent ? 'Editar' : 'Nuevo' }} <span class="text-primary-500">Estudiante</span>
                        </h3>
                        <p class="text-sm font-medium text-slate-400 mt-1">Completa los datos del expediente académico</p>
                    </div>
                    <button @click="showModal = false" class="w-10 h-10 rounded-full bg-slate-50 text-slate-400 hover:bg-slate-100 transition-all">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Nombres</label>
                            <input v-model="form.first_name" type="text" class="w-full bg-slate-50 border-2 border-slate-50 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all" required>
                            <p v-if="form.errors.first_name" class="text-xs text-red-500 font-bold mt-1 ml-1">{{ form.errors.first_name }}</p>
                        </div>
                        <div class="space-y-2">
                            <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Apellidos</label>
                            <input v-model="form.last_name" type="text" class="w-full bg-slate-50 border-2 border-slate-50 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all" required>
                            <p v-if="form.errors.last_name" class="text-xs text-red-500 font-bold mt-1 ml-1">{{ form.errors.last_name }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Cédula o Identificación</label>
                            <input v-model="form.cedula" type="text" class="w-full bg-slate-50 border-2 border-slate-50 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all">
                            <p v-if="form.errors.cedula" class="text-xs text-red-500 font-bold mt-1 ml-1">{{ form.errors.cedula }}</p>
                        </div>
                        <div class="space-y-2">
                            <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Fecha de Nacimiento</label>
                            <div class="relative">
                                <input v-model="form.birth_date" type="date" class="w-full bg-slate-50 border-2 border-slate-50 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all" required>
                            </div>
                            <p v-if="form.errors.birth_date" class="text-xs text-red-500 font-bold mt-1 ml-1">{{ form.errors.birth_date }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Género</label>
                            <div class="relative">
                                <select v-model="form.gender" class="w-full bg-slate-50 border-2 border-slate-50 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all appearance-none cursor-pointer">
                                    <option value="M">Masculino</option>
                                    <option value="F">Femenino</option>
                                </select>
                                <i class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 pointer-events-none"></i>
                            </div>
                        </div>
                        <div v-if="editingStudent" class="flex items-center h-full pt-6">
                            <label class="relative inline-flex items-center cursor-pointer group">
                                <input v-model="form.is_active" type="checkbox" class="sr-only peer">
                                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                                <span class="ml-3 text-sm font-bold text-slate-500 group-hover:text-slate-700 transition-colors">Estudiante Activo</span>
                            </label>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-4 mt-12">
                        <button type="button" @click="showModal = false" class="px-6 py-3 text-sm font-black uppercase tracking-widest text-slate-400 hover:text-slate-600 transition-all">
                            Cancelar
                        </button>
                        <button 
                            type="submit" 
                            :disabled="form.processing" 
                            class="px-10 py-3.5 bg-primary-600 text-white text-[11px] font-black uppercase tracking-widest rounded-2xl shadow-lg shadow-primary-600/20 hover:bg-primary-500 hover:-translate-y-0.5 transition-all disabled:opacity-50 disabled:translate-y-0"
                        >
                            <i class="fas fa-save mr-2"></i>
                            {{ editingStudent ? 'Actualizar' : 'Registrar' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AppLayout>
</template>
