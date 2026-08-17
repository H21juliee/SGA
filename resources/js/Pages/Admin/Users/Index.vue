<script setup>
import { ref, computed, watch } from 'vue'
import { router, useForm, Link } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'
import Modal from '@/Components/UI/Modal.vue'

const props = defineProps({
    users: Object,
    roles: Array,
    filters: Object,
})

const search = ref(props.filters.search || '')
const roleFilter = ref(props.filters.role || '')
const sortCol = ref(props.filters.sort || 'name')
const sortDir = ref(props.filters.direction || 'asc')

let searchTimeout = null
watch([search, roleFilter], () => {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
        loadData()
    }, 300)
})

function loadData() {
    router.get('/admin/users', { 
        search: search.value,
        role: roleFilter.value,
        sort: sortCol.value,
        direction: sortDir.value 
    }, { preserveState: true, replace: true })
}

function toggleSort(col) {
    if (sortCol.value === col) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
    } else {
        sortCol.value = col
        sortDir.value = 'asc'
    }
    loadData()
}

const showModal = ref(false)
const isEditing = ref(false)
const targetUser = ref(null)

const form = useForm({
    name: '',
    email: '',
    cedula: '',
    phone: '',
    password: '',
    roles: [],
    is_active: true,
})

function openCreateModal() {
    isEditing.value = false
    targetUser.value = null
    form.reset()
    form.clearErrors()
    showModal.value = true
}

function openEditModal(user) {
    isEditing.value = true
    targetUser.value = user
    form.reset()
    form.clearErrors()
    
    form.name = user.name
    form.email = user.email
    form.cedula = user.cedula || ''
    form.phone = user.phone || ''
    form.password = ''
    form.roles = user.roles.map(r => r.name)
    form.is_active = user.is_active === 1 || user.is_active === true
    
    showModal.value = true
}

function submit() {
    if (isEditing.value) {
        form.put(`/admin/users/${targetUser.value.id}`, {
            onSuccess: () => showModal.value = false
        })
    } else {
        form.post('/admin/users', {
            onSuccess: () => showModal.value = false
        })
    }
}

function destroy(id) {
    if(confirm('¿Seguro que desea desactivar este usuario?')) {
        router.delete(`/admin/users/${id}`)
    }
}
</script>

<template>
    <AppLayout title="Usuarios">
        <div class="space-y-8 max-w-12xl mx-auto">
            <!-- Header Section -->
            <div class="flex flex-col lg:flex-row gap-6 items-center justify-between animate-fade-in-up">
                <div>
                    <h2 class="text-3xl font-extrabold text-slate-800">
                        Gestión de <span class="gradient-text">Usuarios</span>
                    </h2>
                    <p class="text-slate-400 font-medium mt-1">Administra accesos y roles del sistema</p>
                </div>
                
                <button @click="openCreateModal" class="flex items-center justify-center gap-2 px-6 py-3.5 bg-primary-600 text-white text-[11px] font-black uppercase tracking-widest rounded-2xl shadow-lg shadow-primary-600/20 hover:bg-primary-500 hover:-translate-y-0.5 transition-all">
                    <i class="fas fa-plus"></i>
                    <span>Nuevo Usuario</span>
                </button>
            </div>

            <!-- Content Area -->
            <div class="glass-card rounded-3xl overflow-hidden shadow-2xl animate-fade-in-up" style="animation-delay: 100ms">
                <!-- Data Controls -->
                <div class="p-6 border-b border-slate-50 flex flex-col sm:flex-row gap-4 justify-between items-center bg-slate-50/50">
                    <div class="relative w-full sm:w-96">
                        <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input 
                            v-model="search"
                            type="text" 
                            placeholder="Buscar por nombre, cédula o correo..." 
                            class="w-full bg-white border-2 border-slate-100 rounded-2xl pl-12 pr-4 py-3 text-sm font-bold focus:border-primary-400 focus:ring-0 outline-none transition-all shadow-sm"
                        >
                    </div>

                    <div class="relative w-full sm:w-64">
                        <select 
                            v-model="roleFilter"
                            class="w-full bg-white border-2 border-slate-100 rounded-2xl pl-4 pr-10 py-3 text-sm font-bold text-slate-700 focus:border-primary-400 focus:ring-0 outline-none transition-all appearance-none cursor-pointer shadow-sm"
                        >
                            <option value="">Todos los Roles</option>
                            <option v-for="role in roles" :key="role.id" :value="role.name">{{ role.name }}</option>
                        </select>
                        <i class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                    </div>
                </div>
                
                <!-- Table -->
                <div class="overflow-x-auto custom-scrollbar">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-slate-50 text-slate-400 text-[10px] uppercase font-black tracking-[0.2em]">
                            <tr>
                                <th @click="toggleSort('name')" class="px-8 py-5 cursor-pointer hover:bg-slate-100 hover:text-slate-600 transition-colors select-none group">
                                    Nombre
                                    <span v-if="sortCol === 'name'" class="ml-1 text-primary-500">
                                        <i class="fas" :class="sortDir === 'asc' ? 'fa-sort-up' : 'fa-sort-down'"></i>
                                    </span>
                                </th>
                                <th @click="toggleSort('cedula')" class="px-8 py-5 cursor-pointer hover:bg-slate-100 hover:text-slate-600 transition-colors select-none group">
                                    Cédula
                                </th>
                                <th @click="toggleSort('email')" class="px-8 py-5 cursor-pointer hover:bg-slate-100 hover:text-slate-600 transition-colors select-none group">
                                    Contacto
                                </th>
                                <th class="px-8 py-5">
                                    Rol
                                </th>
                                <th @click="toggleSort('is_active')" class="px-8 py-5 cursor-pointer hover:bg-slate-100 hover:text-slate-600 transition-colors select-none group">
                                    Estado
                                </th>
                                <th class="px-8 py-5 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <tr v-for="user in users.data" :key="user.id" class="group hover:bg-slate-50 transition-colors">
                                <td class="px-8 py-4">
                                    <div class="flex items-center gap-4">
                                        <div class="w-10 h-10 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center font-black group-hover:bg-primary-50 group-hover:text-primary-600 transition-all shadow-sm">
                                            {{ user.name.charAt(0) }}
                                        </div>
                                        <span class="font-black text-slate-700 group-hover:text-primary-700 transition-colors text-base">{{ user.name }}</span>
                                    </div>
                                </td>
                                <td class="px-8 py-4 font-bold text-slate-500 tracking-widest text-xs">{{ user.cedula || 'N/A' }}</td>
                                <td class="px-8 py-4">
                                    <p class="font-bold text-slate-600">{{ user.email }}</p>
                                    <p class="text-[10px] font-bold text-slate-400 mt-0.5">{{ user.phone || 'Sin teléfono' }}</p>
                                </td>
                                <td class="px-8 py-4">
                                    <div class="flex flex-wrap gap-1.5">
                                        <span v-for="r in user.roles" :key="r.id"
                                              class="px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest border shadow-sm"
                                              :class="{
                                                  'bg-primary-50 text-primary-600 border-primary-100': r.name === 'Docente',
                                                  'bg-indigo-50 text-indigo-600 border-indigo-100': r.name === 'Administrador',
                                                  'bg-emerald-50 text-emerald-600 border-emerald-100': r.name === 'Secretaria',
                                                  'bg-rose-50 text-rose-600 border-rose-100': r.name === 'SuperAdmin',
                                                  'bg-slate-50 text-slate-500 border-slate-200': true
                                              }">
                                            {{ r.name }}
                                        </span>
                                        <span v-if="!user.roles.length" class="text-xs text-slate-300 italic">Sin rol</span>
                                    </div>
                                </td>
                                <td class="px-8 py-4">
                                    <span v-if="user.is_active" class="flex items-center gap-2 text-emerald-500 text-xs font-black">
                                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                        Activo
                                    </span>
                                    <span v-else class="flex items-center gap-2 text-slate-400 text-xs font-black">
                                        <span class="w-2 h-2 rounded-full bg-slate-300"></span>
                                        Inactivo
                                    </span>
                                </td>
                                <td class="px-8 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <Link :href="'/admin/users/' + user.id" class="w-10 h-10 rounded-xl bg-slate-50 text-slate-400 hover:bg-indigo-500 hover:text-white transition-all shadow-sm flex items-center justify-center" title="Ver Detalles">
                                            <i class="fas fa-id-card"></i>
                                        </Link>
                                        <button @click="openEditModal(user)" class="w-10 h-10 rounded-xl bg-slate-50 text-slate-400 hover:bg-primary-500 hover:text-white transition-all shadow-sm flex items-center justify-center" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button @click="destroy(user.id)" class="w-10 h-10 rounded-xl bg-slate-50 text-slate-400 hover:bg-rose-500 hover:text-white transition-all shadow-sm flex items-center justify-center" title="Desactivar">
                                            <i class="fas fa-power-off"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="users.data.length === 0">
                                <td colspan="6" class="px-8 py-24 text-center">
                                    <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-6 text-slate-300">
                                        <i class="fas fa-search text-3xl"></i>
                                    </div>
                                    <h3 class="text-xl font-bold text-slate-500">No hay usuarios</h3>
                                    <p class="text-slate-400 text-sm mt-2">Prueba cambiando los filtros de búsqueda</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination Info -->
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4 p-6 border-t border-slate-50 bg-slate-50/30">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">
                        Mostrando {{ users.data.length }} de {{ users.total }} registros
                    </span>
                    
                    <div class="flex flex-wrap justify-center items-center gap-1.5 w-full sm:w-auto" v-if="users.links && users.links.length > 3">
                        <Link
                            v-for="(link, i) in users.links"
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
        </div>

        <!-- Create/Edit Modal -->
        <Modal :show="showModal" @close="showModal = false" maxWidth="lg">
            <div class="p-8">
                <div class="flex items-center gap-4 mb-8">
                    <div class="w-12 h-12 rounded-2xl bg-primary-50 text-primary-500 flex items-center justify-center text-xl shadow-sm">
                        <i class="fas" :class="isEditing ? 'fa-user-edit' : 'fa-user-plus'"></i>
                    </div>
                    <div>
                        <h3 class="text-2xl font-black text-slate-800 leading-tight">
                            {{ isEditing ? 'Editar Usuario' : 'Nuevo Usuario' }}
                        </h3>
                        <p class="text-xs font-medium text-slate-400 mt-1">Completa los datos de acceso</p>
                    </div>
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Nombre Completo</label>
                            <input v-model="form.name" type="text" required class="w-full bg-slate-50 border-2 border-slate-100 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all">
                            <p v-if="form.errors.name" class="text-xs font-bold text-rose-500 ml-1">{{ form.errors.name }}</p>
                        </div>
                        
                        <div class="space-y-2">
                            <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Cédula</label>
                            <input v-model="form.cedula" type="text" class="w-full bg-slate-50 border-2 border-slate-100 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all">
                            <p v-if="form.errors.cedula" class="text-xs font-bold text-rose-500 ml-1">{{ form.errors.cedula }}</p>
                        </div>

                        <div class="space-y-2">
                            <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Correo Electrónico</label>
                            <input v-model="form.email" type="email" required class="w-full bg-slate-50 border-2 border-slate-100 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all">
                            <p v-if="form.errors.email" class="text-xs font-bold text-rose-500 ml-1">{{ form.errors.email }}</p>
                        </div>

                        <div class="space-y-2">
                            <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Teléfono</label>
                            <input v-model="form.phone" type="text" class="w-full bg-slate-50 border-2 border-slate-100 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all">
                            <p v-if="form.errors.phone" class="text-xs font-bold text-rose-500 ml-1">{{ form.errors.phone }}</p>
                        </div>

                        <div class="space-y-2 md:col-span-2">
                            <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Roles (puede seleccionar varios)</label>
                            <div class="bg-slate-50 border-2 border-slate-100 rounded-2xl p-4 grid grid-cols-2 gap-2">
                                <label v-for="role in roles" :key="role.id" class="flex items-center gap-2.5 cursor-pointer group">
                                    <input
                                        type="checkbox"
                                        :value="role.name"
                                        v-model="form.roles"
                                        class="rounded accent-primary-600 w-4 h-4"
                                    >
                                    <span class="text-sm font-bold text-slate-600 group-hover:text-slate-800 transition-colors">{{ role.name }}</span>
                                </label>
                            </div>
                            <p v-if="form.errors.roles" class="text-xs font-bold text-rose-500 ml-1">{{ form.errors.roles }}</p>
                        </div>

                        <div class="space-y-2">
                            <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Contraseña</label>
                            <input v-model="form.password" type="password" :required="!isEditing" :placeholder="isEditing ? 'Dejar vacío para no cambiar' : ''" class="w-full bg-slate-50 border-2 border-slate-100 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all">
                            <p v-if="form.errors.password" class="text-xs font-bold text-rose-500 ml-1">{{ form.errors.password }}</p>
                        </div>
                    </div>

                    <div v-if="isEditing" class="pt-4 border-t border-slate-100">
                        <label class="flex items-center gap-3 cursor-pointer group">
                            <div class="relative">
                                <input type="checkbox" v-model="form.is_active" class="sr-only peer">
                                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                            </div>
                            <span class="text-sm font-bold text-slate-700 group-hover:text-emerald-600 transition-colors">Usuario Activo</span>
                        </label>
                    </div>

                    <div class="flex items-center justify-end gap-4 mt-8">
                        <button type="button" @click="showModal = false" class="px-6 py-3 text-sm font-black uppercase tracking-widest text-slate-400 hover:text-slate-600 transition-all">Cancelar</button>
                        <button type="submit" :disabled="form.processing" class="px-8 py-3 bg-primary-600 text-white text-[11px] font-black uppercase tracking-widest rounded-2xl shadow-lg shadow-primary-500/30 hover:bg-primary-700 hover:-translate-y-0.5 transition-all disabled:opacity-50">
                            {{ isEditing ? 'Guardar Cambios' : 'Crear Usuario' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AppLayout>
</template>






