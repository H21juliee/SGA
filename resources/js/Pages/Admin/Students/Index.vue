<script setup>
import { ref } from 'vue'
import { router, useForm, Link } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'
import Modal from '@/Components/UI/Modal.vue'

const props = defineProps({
    students: Object,
    filters: Object,
})

const search = ref(props.filters.search || '')
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
})

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
    router.get('/admin/students', { search: search.value, sort: sortCol.value, direction: sortDir.value }, { preserveState: true, replace: true })
}

function toggleSort(col) {
    if (sortCol.value === col) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
    } else {
        sortCol.value = col
        sortDir.value = 'asc'
    }
    doSearch()
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
    form.status = student.status
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
        <div class="space-y-8 max-w-12xl mx-auto">
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
                <div class="overflow-x-auto w-full">
                    <table class="w-full text-sm text-left min-w-[800px]">
                    <thead class="bg-slate-50 text-slate-400 text-[10px] uppercase font-black tracking-[0.2em]">
                        <tr>
                            <th @click="toggleSort('last_name')" class="px-8 py-5 cursor-pointer hover:bg-slate-100 hover:text-slate-600 transition-colors select-none group">
                                Apellidos y Nombres
                                <span v-if="sortCol === 'last_name'" class="ml-1 text-primary-500">
                                    <i class="fas" :class="sortDir === 'asc' ? 'fa-sort-up' : 'fa-sort-down'"></i>
                                </span>
                                <span v-else class="ml-1 opacity-0 group-hover:opacity-100 transition-opacity"><i class="fas fa-sort"></i></span>
                            </th>
                            <th @click="toggleSort('cedula')" class="px-8 py-5 cursor-pointer hover:bg-slate-100 hover:text-slate-600 transition-colors select-none group">
                                Cédula
                                <span v-if="sortCol === 'cedula'" class="ml-1 text-primary-500">
                                    <i class="fas" :class="sortDir === 'asc' ? 'fa-sort-up' : 'fa-sort-down'"></i>
                                </span>
                                <span v-else class="ml-1 opacity-0 group-hover:opacity-100 transition-opacity"><i class="fas fa-sort"></i></span>
                            </th>
                            <th @click="toggleSort('birth_date')" class="px-8 py-5 cursor-pointer hover:bg-slate-100 hover:text-slate-600 transition-colors select-none group">
                                Fecha Nac.
                                <span v-if="sortCol === 'birth_date'" class="ml-1 text-primary-500">
                                    <i class="fas" :class="sortDir === 'asc' ? 'fa-sort-up' : 'fa-sort-down'"></i>
                                </span>
                                <span v-else class="ml-1 opacity-0 group-hover:opacity-100 transition-opacity"><i class="fas fa-sort"></i></span>
                            </th>
                            <th @click="toggleSort('status')" class="px-8 py-5 cursor-pointer hover:bg-slate-100 hover:text-slate-600 transition-colors select-none group">
                                Estado
                                <span v-if="sortCol === 'status'" class="ml-1 text-primary-500">
                                    <i class="fas" :class="sortDir === 'asc' ? 'fa-sort-up' : 'fa-sort-down'"></i>
                                </span>
                                <span v-else class="ml-1 opacity-0 group-hover:opacity-100 transition-opacity"><i class="fas fa-sort"></i></span>
                            </th>
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
                                {{ formatDate(student.birth_date) }}
                            </td>
                            <td class="px-8 py-5">
                                <span v-if="student.status === 'regular'" class="px-2.5 py-1 text-[10px] font-black uppercase tracking-wider rounded-lg border shadow-sm bg-emerald-50 text-emerald-600 border-emerald-100">
                                    Regular
                                </span>
                                <span v-else-if="student.status === 'graduated'" class="px-2.5 py-1 text-[10px] font-black uppercase tracking-wider rounded-lg border shadow-sm bg-sky-50 text-sky-600 border-sky-100">
                                    Graduado
                                </span>
                                <span v-else-if="student.status === 'withdrawn'" class="px-2.5 py-1 text-[10px] font-black uppercase tracking-wider rounded-lg border shadow-sm bg-gray-50 text-gray-600 border-gray-200">
                                    Retirado
                                </span>
                                <span v-else-if="student.status === 'suspended'" class="px-2.5 py-1 text-[10px] font-black uppercase tracking-wider rounded-lg border shadow-sm bg-red-50 text-red-600 border-red-100">
                                    Suspendido
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
            </div>

            <!-- Pagination Info -->
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4 px-4 animate-fade-in-up" style="animation-delay: 200ms">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">
                    Mostrando {{ students.data.length }} de {{ students.total }} registros
                </span>
                
                <div class="flex flex-wrap justify-center items-center gap-1.5 w-full sm:w-auto" v-if="students.links && students.links.length > 3">
                    <Link
                        v-for="(link, i) in students.links"
                        :key="i"
                        :href="link.url || '#'"
                        class="px-3 sm:px-4 py-2 sm:py-2.5 rounded-xl text-[10px] sm:text-xs font-black uppercase tracking-widest transition-all shadow-sm"
                        :class="[
                            link.active ? 'bg-primary-500 text-white ring-2 ring-primary-500/20' : 'bg-white text-slate-500 hover:bg-slate-50 border border-slate-200/60',
                            !link.url ? 'opacity-40 cursor-not-allowed border-transparent shadow-none' : 'cursor-pointer hover:-translate-y-0.5'
                        ]"
                        v-html="link.label.replace(/pagination\.previous|previous/i, '&laquo;').replace(/pagination\.next|next/i, '&raquo;')"
                    ></Link>
                </div>
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
                            <input v-model="form.first_name" type="text" class="w-full bg-slate-50 border-2 border-slate-400 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all" required>
                            <p v-if="form.errors.first_name" class="text-xs text-red-500 font-bold mt-1 ml-1">{{ form.errors.first_name }}</p>
                        </div>
                        <div class="space-y-2">
                            <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Apellidos</label>
                            <input v-model="form.last_name" type="text" class="w-full bg-slate-50 border-2 border-slate-400 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all" required>
                            <p v-if="form.errors.last_name" class="text-xs text-red-500 font-bold mt-1 ml-1">{{ form.errors.last_name }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Cédula o Identificación</label>
                            <input v-model="form.cedula" type="text" class="w-full bg-slate-50 border-2 border-slate-400 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all">
                            <p v-if="form.errors.cedula" class="text-xs text-red-500 font-bold mt-1 ml-1">{{ form.errors.cedula }}</p>
                        </div>
                        <div class="space-y-2">
                            <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Fecha de Nacimiento</label>
                            <div class="relative">
                                <input v-model="form.birth_date" type="date" class="w-full bg-slate-50 border-2 border-slate-400 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all" required>
                            </div>
                            <p v-if="form.errors.birth_date" class="text-xs text-red-500 font-bold mt-1 ml-1">{{ form.errors.birth_date }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Género</label>
                            <div class="relative">
                                <select v-model="form.gender" class="w-full bg-slate-50 border-2 border-slate-400 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all appearance-none cursor-pointer">
                                    <option value="M">Masculino</option>
                                    <option value="F">Femenino</option>
                                </select>
                                <i class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 pointer-events-none"></i>
                            </div>
                        </div>
                        <div v-if="editingStudent" class="space-y-2">
                            <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Estatus del Estudiante</label>
                            <div class="relative">
                                <select v-model="form.status" class="w-full bg-slate-50 border-2 border-slate-400 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all appearance-none cursor-pointer">
                                    <option value="regular">Regular</option>
                                    <option value="graduated">Graduado</option>
                                    <option value="withdrawn">Retirado</option>
                                    <option value="suspended">Suspendido</option>
                                </select>
                                <i class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 pointer-events-none"></i>
                            </div>
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
