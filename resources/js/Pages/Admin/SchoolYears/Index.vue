<script setup>
import { ref } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'
import Modal from '@/Components/UI/Modal.vue'

const props = defineProps({
    years: Array,
})

const showModal = ref(false)
const showPromoteModal = ref(false)
const editingYear = ref(null)
const promoteYear = ref(null)

const form = useForm({
    name: '',
    start_date: '',
    end_date: '',
})

const promoteForm = useForm({
    next_school_year_id: '',
})

function openCreateModal() {
    editingYear.value = null
    form.reset()
    form.clearErrors()
    showModal.value = true
}

function openPromoteModal(year) {
    promoteYear.value = year
    promoteForm.reset()
    promoteForm.clearErrors()
    showPromoteModal.value = true
}

function openEditModal(year) {
    editingYear.value = year
    form.name = year.name
    form.start_date = year.start_date ? year.start_date.split('T')[0] : ''
    form.end_date = year.end_date ? year.end_date.split('T')[0] : ''
    form.clearErrors()
    showModal.value = true
}

function submit() {
    if (editingYear.value) {
        form.put(`/admin/school-years/${editingYear.value.id}`, {
            onSuccess: () => showModal.value = false,
        })
    } else {
        form.post('/admin/school-years', {
            onSuccess: () => showModal.value = false,
        })
    }
}

function submitPromote() {
    if(confirm('¿Está seguro de que desea cerrar este año escolar y realizar la promoción masiva? ESTA ACCIÓN NO SE PUEDE DESHACER.')) {
        promoteForm.post(`/admin/school-years/${promoteYear.value.id}/promote`, {
            onSuccess: () => {
                showPromoteModal.value = false
            }
        })
    }
}

function toggleActive(year) {
    if (confirm(`¿Estás seguro de establecer "${year.name}" como el Año Escolar Activo?`)) {
        router.post(`/admin/school-years/${year.id}/toggle`)
    }
}

function toggleLapse(lapse) {
    router.post(`/admin/lapses/${lapse.id}/toggle`, {}, { preserveScroll: true })
}
</script>

<template>
    <AppLayout title="Años Escolares">
        <div class="space-y-8 max-w-7xl mx-auto">
            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row justify-between items-center animate-fade-in-up">
                <div>
                    <h2 class="text-3xl font-extrabold text-slate-800">
                        Gestión de <span class="gradient-text">Años Escolares</span>
                    </h2>
                    <p class="text-slate-400 font-medium mt-2">Administra los periodos académicos y lapsos escolares</p>
                </div>
                <button
                    @click="openCreateModal"
                    class="flex items-center justify-center gap-2 px-6 py-3.5 bg-primary-600 text-white text-[11px] font-black uppercase tracking-widest rounded-2xl shadow-lg shadow-primary-600/20 hover:bg-primary-500 hover:-translate-y-0.5 transition-all w-full sm:w-auto"
                >
                    <i class="fas fa-plus"></i>
                    Nuevo Año Escolar
                </button>
            </div>

            <!-- Years Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <div
                    v-for="(year, index) in years"
                    :key="year.id"
                    class="glass-card rounded-3xl p-8 relative overflow-hidden transition-all duration-300 group hover:-translate-y-2 animate-fade-in-up shadow-xl"
                    :class="year.is_active ? 'ring-4 ring-primary-100' : ''"
                    :style="{ animationDelay: `${(index + 1) * 50}ms` }"
                >
                    <!-- Background Decoration -->
                    <div v-if="year.is_active" class="absolute -top-12 -right-12 w-32 h-32 bg-primary-500/5 blur-3xl rounded-full"></div>
                    
                    <div class="flex justify-between items-start mb-6">
                        <div>
                            <h3 class="text-2xl font-black text-slate-800">{{ year.name }}</h3>
                            <div class="flex items-center gap-2 mt-2">
                                <i class="far fa-calendar-alt text-slate-300 text-xs"></i>
                                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                                    {{ new Date(year.start_date).toLocaleDateString() }} — {{ new Date(year.end_date).toLocaleDateString() }}
                                </span>
                            </div>
                        </div>
                        <span
                            v-if="year.is_active"
                            class="px-3 py-1 text-[10px] font-black rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-100 uppercase tracking-widest shadow-sm"
                        >
                            ACTIVO
                        </span>
                        <span
                            v-else-if="year.is_closed"
                            class="px-3 py-1 text-[10px] font-black rounded-lg bg-slate-100 text-slate-400 border border-slate-200 uppercase tracking-widest shadow-sm"
                        >
                            CERRADO
                        </span>
                    </div>

                    <!-- Lapses List -->
                    <div v-if="year.lapses?.length > 0" class="mb-8 space-y-3">
                        <div v-for="lapse in year.lapses" :key="lapse.id" class="flex justify-between items-center p-3.5 rounded-2xl bg-slate-50 border border-slate-100 group-hover:bg-white transition-colors">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-xl bg-white flex items-center justify-center text-primary-500 shadow-sm">
                                    <i class="fas fa-bookmark text-xs"></i>
                                </div>
                                <span class="text-xs font-black text-slate-600 uppercase tracking-wider">{{ lapse.name }}</span>
                            </div>
                            <button
                                @click="toggleLapse(lapse)"
                                class="px-4 py-2 text-[9px] font-black uppercase tracking-widest rounded-xl transition-all border shadow-sm"
                                :class="lapse.is_open 
                                    ? 'bg-emerald-500 text-white border-emerald-400 hover:bg-emerald-600' 
                                    : 'bg-white text-slate-400 border-slate-200 hover:bg-slate-50'"
                            >
                                {{ lapse.is_open ? 'Abierto' : 'Cerrado' }}
                            </button>
                        </div>
                    </div>

                    <!-- Actions Footer -->
                    <div class="flex items-center gap-3 pt-6 border-t-2 border-slate-50">
                        <button
                            v-if="year.is_active"
                            @click="openPromoteModal(year)"
                            class="flex-1 flex items-center justify-center gap-2 py-3 text-[10px] font-black uppercase tracking-widest bg-red-50 text-red-600 hover:bg-red-600 hover:text-white rounded-2xl transition-all border-2 border-red-100 hover:border-red-600 shadow-sm"
                        >
                            <i class="fas fa-lock"></i>
                            Cerrar y Promover
                        </button>
                        <button
                            v-if="!year.is_active && !year.is_closed"
                            @click="toggleActive(year)"
                            class="flex-1 flex items-center justify-center gap-2 py-3 text-[10px] font-black uppercase tracking-widest bg-primary-50 text-primary-600 hover:bg-primary-600 hover:text-white rounded-2xl transition-all border-2 border-primary-100 hover:border-primary-600 shadow-sm"
                        >
                            <i class="fas fa-check-circle"></i>
                            Hacer Activo
                        </button>
                        <button
                            v-if="!year.is_closed"
                            @click="openEditModal(year)"
                            class="flex-1 flex items-center justify-center gap-2 py-3 text-[10px] font-black uppercase tracking-widest bg-slate-50 text-slate-500 hover:bg-slate-800 hover:text-white rounded-2xl transition-all border-2 border-slate-100 hover:border-slate-800 shadow-sm"
                        >
                            <i class="fas fa-edit"></i>
                            Editar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <Modal :show="showModal" @close="showModal = false" max-width="md">
            <div class="p-8">
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h3 class="text-2xl font-black text-slate-800">
                            {{ editingYear ? 'Editar' : 'Nuevo' }} <span class="text-primary-500">Año Escolar</span>
                        </h3>
                        <p class="text-sm font-medium text-slate-400 mt-1">Configura el nuevo periodo académico</p>
                    </div>
                    <button @click="showModal = false" class="w-10 h-10 rounded-full bg-slate-50 text-slate-400 hover:bg-slate-100 transition-all">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="space-y-2">
                        <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Nombre (ej. 2026-2027)</label>
                        <input v-model="form.name" type="text" class="w-full bg-slate-50 border-2 border-slate-50 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all" required>
                        <p v-if="form.errors.name" class="text-xs text-red-500 font-bold mt-1 ml-1">{{ form.errors.name }}</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Fecha de Inicio</label>
                            <input v-model="form.start_date" type="date" class="w-full bg-slate-50 border-2 border-slate-50 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all" required>
                            <p v-if="form.errors.start_date" class="text-xs text-red-500 font-bold mt-1 ml-1">{{ form.errors.start_date }}</p>
                        </div>
                        <div class="space-y-2">
                            <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Fecha de Fin</label>
                            <input v-model="form.end_date" type="date" class="w-full bg-slate-50 border-2 border-slate-50 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all" required>
                            <p v-if="form.errors.end_date" class="text-xs text-red-500 font-bold mt-1 ml-1">{{ form.errors.end_date }}</p>
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

        <!-- Promote Modal -->
        <Modal :show="showPromoteModal" @close="showPromoteModal = false" max-width="lg">
            <div class="p-8">
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h3 class="text-2xl font-black text-slate-800">Cerrar y <span class="text-red-500">Promover</span></h3>
                        <p class="text-sm font-medium text-slate-400 mt-1">
                            Cierre definitivo del año <span class="text-slate-700 font-black">{{ promoteYear?.name }}</span>
                        </p>
                    </div>
                    <button @click="showPromoteModal = false" class="w-10 h-10 rounded-full bg-slate-50 text-slate-400 hover:bg-slate-100 transition-all">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <form @submit.prevent="submitPromote" class="space-y-8">
                    <div class="space-y-3">
                        <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Año Escolar de Destino</label>
                        <div class="relative">
                            <select 
                                v-model="promoteForm.next_school_year_id" 
                                class="w-full bg-slate-50 border-2 border-slate-50 rounded-2xl px-4 py-4 text-slate-700 text-sm font-bold focus:border-red-400 focus:bg-white focus:ring-0 outline-none transition-all appearance-none cursor-pointer shadow-sm"
                                required
                            >
                                <option value="">Seleccione el año siguiente...</option>
                                <option 
                                    v-for="year in years.filter(y => !y.is_closed && y.id !== promoteYear?.id)" 
                                    :key="year.id" 
                                    :value="year.id"
                                >
                                    {{ year.name }}
                                </option>
                            </select>
                            <i class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 pointer-events-none"></i>
                        </div>
                        <p v-if="promoteForm.errors.next_school_year_id" class="text-xs text-red-500 font-bold mt-1 ml-1">{{ promoteForm.errors.next_school_year_id }}</p>
                    </div>

                    <div class="bg-red-50 border-2 border-red-100 p-6 rounded-3xl relative overflow-hidden">
                        <div class="absolute -top-4 -right-4 text-red-100 opacity-50">
                            <i class="fas fa-shield-alt text-6xl rotate-12"></i>
                        </div>
                        <div class="flex gap-4 relative z-10">
                            <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-red-500 shadow-sm flex-shrink-0">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <div class="space-y-1">
                                <h4 class="text-xs font-black text-red-700 uppercase tracking-widest">Acción Crítica</h4>
                                <p class="text-xs text-red-600 font-bold leading-relaxed">
                                    Este proceso cerrará definitivamente el año actual. Los estudiantes serán promovidos según su rendimiento. 
                                    <span class="block mt-1 underline">Esta acción no se puede deshacer.</span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-4">
                        <button type="button" @click="showPromoteModal = false" class="px-6 py-3 text-sm font-black uppercase tracking-widest text-slate-400 hover:text-slate-600 transition-all">
                            Cancelar
                        </button>
                        <button 
                            type="submit" 
                            :disabled="promoteForm.processing || !promoteForm.next_school_year_id" 
                            class="px-10 py-3.5 bg-red-600 text-white text-[11px] font-black uppercase tracking-widest rounded-2xl shadow-lg shadow-red-600/20 hover:bg-red-500 hover:-translate-y-0.5 transition-all disabled:opacity-50"
                        >
                            {{ promoteForm.processing ? 'Procesando...' : 'Confirmar Cierre y Promoción' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AppLayout>
</template>
