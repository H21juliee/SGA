<script setup>
import { ref } from 'vue'
import { router, useForm, Link } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'
import Modal from '@/Components/UI/Modal.vue'

const props = defineProps({
    subjects: Object,
    levels: Array,
    filters: Object,
})

const showModal = ref(false)
const editingSubject = ref(null)
const searchQuery = ref(props.filters.search || '')
const sortCol = ref(props.filters.sort || 'grade_level_id')
const sortDir = ref(props.filters.direction || 'asc')

const form = useForm({
    grade_level_id: '',
    name: '',
    code: '',
    weight: 10,
    grading_type: 'numeric',
})

function doSearch() {
    router.get('/admin/subjects', { search: searchQuery.value, sort: sortCol.value, direction: sortDir.value }, { preserveState: true, replace: true })
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
    editingSubject.value = null
    form.reset()
    form.weight = 10
    form.grading_type = 'numeric'
    form.clearErrors()
    showModal.value = true
}

function openEditModal(subject) {
    editingSubject.value = subject
    form.grade_level_id = subject.grade_level_id
    form.name = subject.name
    form.code = subject.code
    form.weight = subject.weight || 10
    form.grading_type = subject.grading_type || 'numeric'
    form.clearErrors()
    showModal.value = true
}

function submit() {
    if (editingSubject.value) {
        form.put(`/admin/subjects/${editingSubject.value.id}`, { onSuccess: () => showModal.value = false })
    } else {
        form.post('/admin/subjects', { onSuccess: () => showModal.value = false })
    }
}
</script>

<template>
    <AppLayout title="Pensum de Materias">
        <div class="space-y-8 max-w-12xl mx-auto">
            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row gap-6 items-center justify-between animate-fade-in-up">
                <div>
                    <h2 class="text-3xl font-extrabold text-slate-800">
                        Materias y <span class="gradient-text">Pensum</span>
                    </h2>
                    <p class="text-slate-400 font-medium mt-2">Gestiona las asignaturas impartidas en cada nivel académico</p>
                </div>
                <div class="flex flex-col sm:flex-row items-center gap-4 w-full sm:w-auto">
                    <!-- Buscador -->
                    <div class="relative w-full sm:w-64">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                        <input 
                            v-model="searchQuery" 
                            @keyup.enter="doSearch"
                            type="text" 
                            placeholder="Buscar por materia o código..." 
                            class="w-full bg-white border-2 border-slate-100 rounded-xl pl-9 pr-4 py-3 text-sm text-slate-700 placeholder:text-slate-400 focus:border-primary-400 focus:ring-0 outline-none transition-all shadow-sm"
                        >
                    </div>
                    
                    <button
                        @click="openCreateModal"
                        class="flex items-center justify-center gap-2 px-6 py-3.5 bg-primary-600 text-white text-[11px] font-black uppercase tracking-widest rounded-2xl shadow-lg shadow-primary-600/20 hover:bg-primary-500 hover:-translate-y-0.5 transition-all w-full sm:w-auto"
                    >
                        <i class="fas fa-plus"></i>
                        Nueva Materia
                    </button>
                </div>
            </div>

            <!-- Table Container -->
            <div class="glass-card rounded-3xl overflow-hidden shadow-2xl animate-fade-in-up" style="animation-delay: 100ms">
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-50 text-slate-400 text-[10px] uppercase font-black tracking-[0.2em]">
                        <tr>
                            <th @click="toggleSort('grade_level_id')" class="px-8 py-5 cursor-pointer hover:bg-slate-100 hover:text-slate-600 transition-colors select-none group">
                                Nivel / Año
                                <span v-if="sortCol === 'grade_level_id'" class="ml-1 text-primary-500">
                                    <i class="fas" :class="sortDir === 'asc' ? 'fa-sort-up' : 'fa-sort-down'"></i>
                                </span>
                                <span v-else class="ml-1 opacity-0 group-hover:opacity-100 transition-opacity"><i class="fas fa-sort"></i></span>
                            </th>
                            <th @click="toggleSort('code')" class="px-8 py-5 cursor-pointer hover:bg-slate-100 hover:text-slate-600 transition-colors select-none group">
                                Código
                                <span v-if="sortCol === 'code'" class="ml-1 text-primary-500">
                                    <i class="fas" :class="sortDir === 'asc' ? 'fa-sort-up' : 'fa-sort-down'"></i>
                                </span>
                                <span v-else class="ml-1 opacity-0 group-hover:opacity-100 transition-opacity"><i class="fas fa-sort"></i></span>
                            </th>
                            <th @click="toggleSort('name')" class="px-8 py-5 cursor-pointer hover:bg-slate-100 hover:text-slate-600 transition-colors select-none group">
                                Materia
                                <span v-if="sortCol === 'name'" class="ml-1 text-primary-500">
                                    <i class="fas" :class="sortDir === 'asc' ? 'fa-sort-up' : 'fa-sort-down'"></i>
                                </span>
                                <span v-else class="ml-1 opacity-0 group-hover:opacity-100 transition-opacity"><i class="fas fa-sort"></i></span>
                            </th>
                            <th class="px-8 py-5 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="subject in subjects.data" :key="subject.id" class="group hover:bg-slate-50 transition-colors">
                            <td class="px-8 py-4">
                                <span class="px-2.5 py-1 rounded-lg bg-slate-100 text-slate-400 text-[10px] font-black uppercase tracking-wider border border-slate-200">
                                    {{ subject.grade_level?.name }}
                                </span>
                            </td>
                            <td class="px-8 py-4">
                                <span class="px-3 py-1.5 rounded-xl bg-primary-50 text-primary-600 text-[11px] font-black border border-primary-100 shadow-sm group-hover:bg-primary-500 group-hover:text-white transition-all">
                                    {{ subject.code }}
                                </span>
                            </td>
                            <td class="px-8 py-4">
                                <div class="font-black text-slate-700 text-base group-hover:text-primary-700 transition-colors">
                                    {{ subject.name }}
                                </div>
                                <span v-if="subject.grading_type === 'qualitative'"
                                    class="text-[9px] font-black uppercase tracking-widest px-2 py-0.5 rounded-full bg-amber-100 text-amber-600 border border-amber-200">
                                    Cualitativa
                                </span>
                            </td>
                            <td class="px-8 py-4 text-right">
                                <button 
                                    @click="openEditModal(subject)" 
                                    class="w-10 h-10 rounded-xl bg-slate-50 text-slate-400 hover:text-primary-600 hover:bg-primary-50 transition-all border border-transparent hover:border-primary-100"
                                    title="Editar Materia"
                                >
                                    <i class="fas fa-edit"></i>
                                </button>
                            </td>
                        </tr>
                        <tr v-if="subjects.data.length === 0">
                            <td colspan="4" class="px-8 py-20 text-center">
                                <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 text-slate-200">
                                    <i class="fas fa-book-open text-2xl"></i>
                                </div>
                                <h3 class="text-lg font-bold text-slate-400">No hay materias para mostrar</h3>
                                <p class="text-slate-300 text-sm mt-1">Ajusta tu búsqueda o agrega una nueva materia.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Info -->
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4 px-4 animate-fade-in-up" style="animation-delay: 200ms">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">
                    Mostrando {{ subjects.data.length }} de {{ subjects.total }} registros
                </span>
                
                <div class="flex flex-wrap justify-center items-center gap-1.5 w-full sm:w-auto" v-if="subjects.links && subjects.links.length > 3">
                    <Link
                        v-for="(link, i) in subjects.links"
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
        <Modal :show="showModal" @close="showModal = false" max-width="md">
            <div class="p-8">
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h3 class="text-2xl font-black text-slate-800">
                            {{ editingSubject ? 'Editar' : 'Nueva' }} <span class="text-primary-500">Materia</span>
                        </h3>
                        <p class="text-sm font-medium text-slate-400 mt-1">Configura los detalles de la asignatura</p>
                    </div>
                    <button @click="showModal = false" class="w-10 h-10 rounded-full bg-slate-50 text-slate-400 hover:bg-slate-100 transition-all">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="space-y-2">
                        <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Nivel / Año Académico</label>
                        <div class="relative">
                            <select v-model="form.grade_level_id" class="w-full bg-slate-50 border-2 border-slate-400 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all appearance-none cursor-pointer" required>
                                <option v-for="lvl in levels" :key="lvl.id" :value="lvl.id">{{ lvl.name }}</option>
                            </select>
                            <i class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 pointer-events-none"></i>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Nombre de la Materia</label>
                        <input v-model="form.name" type="text" class="w-full bg-slate-50 border-2 border-slate-400 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all" required>
                    </div>

                    <div class="space-y-2">
                        <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Código Único</label>
                        <input v-model="form.code" type="text" class="w-full bg-slate-50 border-2 border-slate-400 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all" required placeholder="ej. MAT-101">
                    </div>

                    <div class="space-y-2">
                        <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Tipo de Calificación</label>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="flex items-center gap-3 p-3 rounded-2xl border-2 cursor-pointer transition-all"
                                :class="form.grading_type === 'numeric' ? 'border-primary-400 bg-primary-50' : 'border-slate-200 bg-slate-50 hover:border-slate-300'">
                                <input type="radio" v-model="form.grading_type" value="numeric" class="hidden">
                                <div class="w-8 h-8 rounded-xl flex items-center justify-center text-sm font-black"
                                    :class="form.grading_type === 'numeric' ? 'bg-primary-500 text-white' : 'bg-slate-200 text-slate-500'">
                                    20
                                </div>
                                <div>
                                    <div class="font-black text-slate-700 text-sm">Numérica</div>
                                    <div class="text-[10px] text-slate-400">Del 1 al 20</div>
                                </div>
                            </label>
                            <label class="flex items-center gap-3 p-3 rounded-2xl border-2 cursor-pointer transition-all"
                                :class="form.grading_type === 'qualitative' ? 'border-amber-400 bg-amber-50' : 'border-slate-200 bg-slate-50 hover:border-slate-300'">
                                <input type="radio" v-model="form.grading_type" value="qualitative" class="hidden">
                                <div class="w-8 h-8 rounded-xl flex items-center justify-center text-sm font-black"
                                    :class="form.grading_type === 'qualitative' ? 'bg-amber-500 text-white' : 'bg-slate-200 text-slate-500'">
                                    A
                                </div>
                                <div>
                                    <div class="font-black text-slate-700 text-sm">Cualitativa</div>
                                    <div class="text-[10px] text-slate-400">A, B, C, D</div>
                                </div>
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
                            class="px-10 py-3.5 bg-primary-600 text-white text-[11px] font-black uppercase tracking-widest rounded-2xl shadow-lg shadow-primary-600/20 hover:bg-primary-500 hover:-translate-y-0.5 transition-all"
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
