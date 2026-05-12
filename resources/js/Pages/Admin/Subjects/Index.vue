<script setup>
import { ref } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'
import Modal from '@/Components/UI/Modal.vue'

const props = defineProps({
    subjects: Array,
    levels: Array,
})

const showModal = ref(false)
const editingSubject = ref(null)

const form = useForm({
    grade_level_id: '',
    name: '',
    code: '',
    weight: 1,
})

function openCreateModal() {
    editingSubject.value = null
    form.reset()
    form.clearErrors()
    showModal.value = true
}

function openEditModal(subject) {
    editingSubject.value = subject
    form.grade_level_id = subject.grade_level_id
    form.name = subject.name
    form.code = subject.code
    form.weight = subject.weight
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
        <div class="space-y-8 max-w-7xl mx-auto">
            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row gap-6 items-center justify-between animate-fade-in-up">
                <div>
                    <h2 class="text-3xl font-extrabold text-slate-800">
                        Materias y <span class="gradient-text">Pensum</span>
                    </h2>
                    <p class="text-slate-400 font-medium mt-2">Gestiona las asignaturas impartidas en cada nivel académico</p>
                </div>
                <button
                    @click="openCreateModal"
                    class="flex items-center justify-center gap-2 px-6 py-3.5 bg-primary-600 text-white text-[11px] font-black uppercase tracking-widest rounded-2xl shadow-lg shadow-primary-600/20 hover:bg-primary-500 hover:-translate-y-0.5 transition-all w-full sm:w-auto"
                >
                    <i class="fas fa-plus"></i>
                    Nueva Materia
                </button>
            </div>

            <!-- Table Container -->
            <div class="glass-card rounded-3xl overflow-hidden shadow-2xl animate-fade-in-up" style="animation-delay: 100ms">
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-50 text-slate-400 text-[10px] uppercase font-black tracking-[0.2em]">
                        <tr>
                            <th class="px-8 py-5">Nivel / Año</th>
                            <th class="px-8 py-5">Código</th>
                            <th class="px-8 py-5">Materia</th>
                            <th class="px-8 py-5 text-center">Peso (UC)</th>
                            <th class="px-8 py-5 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="subject in subjects" :key="subject.id" class="group hover:bg-slate-50 transition-colors">
                            <td class="px-8 py-5">
                                <span class="px-2.5 py-1 rounded-lg bg-slate-100 text-slate-400 text-[10px] font-black uppercase tracking-wider border border-slate-200">
                                    {{ subject.grade_level?.name }}
                                </span>
                            </td>
                            <td class="px-8 py-5">
                                <span class="px-3 py-1.5 rounded-xl bg-primary-50 text-primary-600 text-[11px] font-black border border-primary-100 shadow-sm group-hover:bg-primary-500 group-hover:text-white transition-all">
                                    {{ subject.code }}
                                </span>
                            </td>
                            <td class="px-8 py-5">
                                <div class="font-black text-slate-700 text-base group-hover:text-primary-700 transition-colors">
                                    {{ subject.name }}
                                </div>
                            </td>
                            <td class="px-8 py-5 text-center">
                                <div class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-slate-50 text-slate-500 font-black text-xs border border-slate-100">
                                    {{ subject.weight }}
                                </div>
                            </td>
                            <td class="px-8 py-5 text-right">
                                <button 
                                    @click="openEditModal(subject)" 
                                    class="w-10 h-10 rounded-xl bg-slate-50 text-slate-400 hover:text-primary-600 hover:bg-primary-50 transition-all border border-transparent hover:border-primary-100"
                                    title="Editar Materia"
                                >
                                    <i class="fas fa-edit"></i>
                                </button>
                            </td>
                        </tr>
                        <tr v-if="subjects.length === 0">
                            <td colspan="5" class="px-8 py-20 text-center">
                                <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 text-slate-200">
                                    <i class="fas fa-book-open text-2xl"></i>
                                </div>
                                <h3 class="text-lg font-bold text-slate-400">No hay materias registradas</h3>
                                <p class="text-slate-300 text-sm mt-1">Comienza añadiendo materias al pensum escolar.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
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
                            <select v-model="form.grade_level_id" class="w-full bg-slate-50 border-2 border-slate-50 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all appearance-none cursor-pointer" required>
                                <option v-for="lvl in levels" :key="lvl.id" :value="lvl.id">{{ lvl.name }}</option>
                            </select>
                            <i class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 pointer-events-none"></i>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Nombre de la Materia</label>
                        <input v-model="form.name" type="text" class="w-full bg-slate-50 border-2 border-slate-50 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all" required>
                    </div>

                    <div class="grid grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Código Único</label>
                            <input v-model="form.code" type="text" class="w-full bg-slate-50 border-2 border-slate-50 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all" required placeholder="ej. MAT-101">
                        </div>
                        <div class="space-y-2">
                            <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Peso (UC)</label>
                            <input v-model="form.weight" type="number" min="1" max="10" class="w-full bg-slate-50 border-2 border-slate-50 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all" required>
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
