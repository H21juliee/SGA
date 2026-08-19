<script setup>
import { ref, computed, watch } from 'vue'
import { router, useForm, Link } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'
import Modal from '@/Components/UI/Modal.vue'
import Swal from 'sweetalert2'

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
    // Limitar a máximo 10 dígitos numéricos (Total 11 caracteres sin guion)
    if (val.length > 9) {
        val = val.substring(0, 9);
    }
    if (val.length > 1) {
        val = val[0] + '-' + val.substring(1);
    }
    return val;
}


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
            onSuccess: () => { showModal.value = false }
        })
    } else {
        form.post('/admin/users', {
            onSuccess: () => { showModal.value = false }
        })
    }
}

function destroy(user) {
    Swal.fire({
        title: '¿Confirmar Acción?',
        text: `¿Seguro que desea ${user.is_active ? 'desactivar' : 'activar'} a ${user.name}?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: user.is_active ? 'Sí, desactivar' : 'Sí, activar',
        cancelButtonText: 'Cancelar',
        buttonsStyling: false,
        customClass: {
            popup: 'rounded-3xl border-2 border-slate-100 shadow-2xl',
            title: 'text-2xl font-black text-slate-800',
            htmlContainer: 'text-slate-500 font-medium',
            confirmButton: 'px-6 py-3 bg-red-500 text-white text-[11px] font-black uppercase tracking-widest rounded-2xl shadow-lg shadow-red-500/20 hover:bg-red-400 transition-all mx-2',
            cancelButton: 'px-6 py-3 bg-slate-500 text-white text-[11px] font-black uppercase tracking-widest rounded-2xl shadow-lg shadow-slate-500/20 hover:bg-slate-400 transition-all mx-2'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/admin/users/${user.id}`)
        }
    })
}

function resetPassword(user) {
    Swal.fire({
        title: '¿Resetear contraseña?',
        html: `<p class="text-sm text-slate-600">Se establecerá la <strong>cédula</strong> del usuario (<strong>${user.cedula || 'N/A'}</strong>) como contraseña temporal.</p><p class="text-sm text-slate-600 mt-2">El usuario deberá cambiarla y configurar sus preguntas de seguridad en su próximo inicio de sesión.</p>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#f59e0b',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Sí, resetear',
        cancelButtonText: 'Cancelar',
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(`/admin/users/${user.id}/reset-password`, {}, {
                preserveScroll: true,
            })
        }
    })
}
</script>

<template>
    <AppLayout title="Usuarios y Docentes">
        <div class="space-y-8 max-w-7xl mx-auto pb-10">
            <!-- Header Section -->
            <div class="flex flex-col lg:flex-row gap-6 items-center justify-between animate-fade-in-up">
                <div>
                    <h2 class="text-3xl font-extrabold text-slate-800">
                        Registro de <span class="gradient-text">Personal</span>
                    </h2>
                    <p class="text-slate-400 font-medium mt-1">Administra accesos y roles del sistema (Docentes, Administrativos)</p>
                </div>
                
                <button @click="openCreateModal" class="flex items-center justify-center gap-2 px-6 py-3.5 bg-primary-600 text-white text-[11px] font-black uppercase tracking-widest rounded-2xl shadow-lg shadow-primary-600/20 hover:bg-primary-500 hover:-translate-y-0.5 transition-all w-full lg:w-auto">
                    <i class="fas fa-plus"></i>
                    <span>Nuevo Registro</span>
                </button>
            </div>

            <!-- Toolbar (Search & Filter) -->
            <div class="glass-card rounded-3xl p-5 sm:p-6 shadow-xl border-2 border-transparent animate-fade-in-up" style="animation-delay: 100ms">
                <div class="flex flex-col sm:flex-row gap-4 justify-between items-center">
                    <div class="relative w-full sm:w-[400px]">
                        <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input 
                            v-model="search"
                            type="text" 
                            placeholder="Buscar por nombre, cédula o correo..." 
                            class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl pl-12 pr-4 py-3.5 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all shadow-sm"
                        >
                    </div>

                    <div class="relative w-full sm:w-64">
                        <select 
                            v-model="roleFilter"
                            class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl pl-4 pr-10 py-3.5 text-sm font-bold text-slate-700 focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all appearance-none cursor-pointer shadow-sm"
                        >
                            <option value="">Todos los Roles</option>
                            <option v-for="role in roles" :key="role.id" :value="role.name">{{ role.name }}</option>
                        </select>
                        <i class="fas fa-filter absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 pointer-events-none text-xs"></i>
                    </div>
                </div>
            </div>

            <!-- Listado de Usuarios -->
            <div class="space-y-3 animate-fade-in-up" style="animation-delay: 200ms">
                <div 
                    v-for="user in users.data" 
                    :key="user.id"
                    class="bg-white p-4 sm:p-5 rounded-2xl shadow-sm border-2 transition-all flex flex-col md:flex-row md:items-center justify-between gap-4 group hover:border-slate-300"
                    :class="!user.is_active ? 'opacity-70 hover:opacity-100 grayscale hover:grayscale-0 border-slate-100' : 'border-slate-100'"
                >
                    <!-- Info Principal -->
                    <div class="flex items-center gap-4 flex-1 min-w-0">
                        <div class="relative shrink-0">
                            <div class="w-12 h-12 rounded-full flex items-center justify-center font-black text-sm shadow-sm border bg-slate-50 text-slate-500 border-slate-200 group-hover:bg-primary-50 group-hover:text-primary-600 transition-colors">
                                {{ user.name.charAt(0) }}
                            </div>
                            <!-- Status Indicator -->
                            <div class="absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 rounded-full border-2 border-white flex items-center justify-center"
                                 :class="user.is_active ? 'bg-emerald-500' : 'bg-slate-400'" title="Estado">
                                <div v-if="user.is_active" class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></div>
                            </div>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h3 class="text-base font-black text-slate-800 truncate">{{ user.name }}</h3>
                            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 mt-1 text-xs font-bold text-slate-500">
                                <span class="flex items-center gap-1.5 shrink-0"><i class="fas fa-id-card opacity-50"></i> {{ user.cedula || 'Sin Cédula' }}</span>
                                <span class="flex items-center gap-1.5 shrink-0"><i class="fas fa-envelope opacity-50"></i> {{ user.email }}</span>
                                <span class="hidden md:flex items-center gap-1.5 shrink-0" v-if="user.phone"><i class="fas fa-phone-alt opacity-50"></i> {{ user.phone }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Estatus y Acciones -->
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between md:justify-end gap-4 w-full md:w-auto shrink-0">
                        
                        <!-- Roles -->
                        <div class="flex flex-wrap gap-1.5">
                            <span v-for="r in user.roles" :key="r.id"
                                  class="px-2.5 py-1 rounded-lg text-[9px] font-black uppercase tracking-widest border shadow-sm whitespace-nowrap"
                                  :class="{
                                      'bg-primary-50 text-primary-600 border-primary-100': r.name === 'Docente',
                                      'bg-indigo-50 text-indigo-600 border-indigo-100': r.name === 'Administrador',
                                      'bg-emerald-50 text-emerald-600 border-emerald-100': r.name === 'Secretaria',
                                      'bg-rose-50 text-rose-600 border-rose-100': r.name === 'SuperAdmin',
                                      'bg-slate-50 text-slate-500 border-slate-200': !['Docente', 'Administrador', 'Secretaria', 'SuperAdmin'].includes(r.name)
                                  }">
                                {{ r.name }}
                            </span>
                            <span v-if="!user.roles.length" class="text-xs text-slate-300 italic">Sin rol</span>
                        </div>

                        <!-- Botones -->
                        <div class="flex items-center gap-2 bg-slate-50 p-1.5 rounded-xl border border-slate-100 shrink-0">
                            <Link :href="'/admin/users/' + user.id" 
                                  class="w-9 h-9 rounded-lg bg-white text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 hover:border-indigo-200 hover:shadow-sm transition-all border border-slate-200 flex items-center justify-center" 
                                  title="Ver Detalles">
                                <i class="fas fa-eye text-[11px]"></i>
                            </Link>
                            <template v-if="!user.roles.some(r => r.name === 'SuperAdmin')">
                                <button @click="openEditModal(user)" 
                                        class="w-9 h-9 rounded-lg bg-white text-slate-500 hover:text-primary-600 hover:bg-primary-50 hover:border-primary-200 hover:shadow-sm transition-all border border-slate-200 flex items-center justify-center" 
                                        title="Editar">
                                    <i class="fas fa-edit text-[11px]"></i>
                                </button>
                                <button @click="resetPassword(user)"
                                        class="w-9 h-9 rounded-lg bg-white text-slate-500 hover:text-amber-600 hover:bg-amber-50 hover:border-amber-200 hover:shadow-sm transition-all border border-slate-200 flex items-center justify-center"
                                        title="Resetear Contraseña"
                                        v-if="user.cedula && $page.props.security_questions_enabled">
                                    <i class="fas fa-key text-[11px]"></i>
                                </button>
                                <button @click="destroy(user)" 
                                        class="w-9 h-9 rounded-lg transition-all border flex items-center justify-center" 
                                        :class="user.is_active ? 'bg-white text-slate-400 border-slate-200 hover:bg-red-50 hover:text-red-500 hover:border-red-200 hover:shadow-sm' : 'bg-red-50 text-red-500 border-red-200 hover:bg-emerald-50 hover:text-emerald-600 hover:border-emerald-200 hover:shadow-sm'"
                                        :title="user.is_active ? 'Desactivar' : 'Reactivar'">
                                    <i class="fas text-[11px]" :class="user.is_active ? 'fa-power-off' : 'fa-undo'"></i>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="users.data.length === 0" class="col-span-full glass-card rounded-3xl p-20 text-center">
                    <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-6 text-slate-300">
                        <i class="fas fa-users-slash text-3xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-500">No hay registros</h3>
                    <p class="text-slate-400 text-sm mt-2">Prueba cambiando los filtros de búsqueda o registra un nuevo usuario.</p>
                </div>
            </div>
            
            <!-- Pagination Info -->
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4 px-4 mt-6 animate-fade-in-up" style="animation-delay: 300ms">
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

        <!-- Create/Edit Modal -->
        <Modal :show="showModal" @close="showModal = false" maxWidth="lg">
            <div class="p-8">
                <div class="flex items-center gap-4 mb-8">
                    <div class="w-12 h-12 rounded-2xl bg-primary-50 text-primary-500 flex items-center justify-center text-xl shadow-sm">
                        <i class="fas" :class="isEditing ? 'fa-user-edit' : 'fa-user-plus'"></i>
                    </div>
                    <div>
                        <h3 class="text-2xl font-black text-slate-800 leading-tight">
                            {{ isEditing ? 'Editar Registro' : 'Nuevo Registro' }}
                        </h3>
                        <p class="text-xs font-medium text-slate-400 mt-1">Completa los datos de acceso del sistema</p>
                    </div>
                    <button @click="showModal = false" class="ml-auto w-10 h-10 rounded-full bg-slate-50 text-slate-400 hover:bg-slate-100 transition-all flex justify-center items-center">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Nombre Completo</label>
                            <input v-model="form.name" type="text" required class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all shadow-sm">
                            <p v-if="form.errors.name" class="text-xs font-bold text-rose-500 ml-1">{{ form.errors.name }}</p>
                        </div>
                        
                        <div class="space-y-2">
                            <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Cédula</label>
                            <input v-model="form.cedula" @input="form.cedula = formatCedula($event.target.value)" type="text" class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all shadow-sm" maxlength="12">
                            <p v-if="form.errors.cedula" class="text-xs font-bold text-rose-500 ml-1">{{ form.errors.cedula }}</p>
                        </div>

                        <div class="space-y-2">
                            <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Correo Electrónico</label>
                            <input v-model="form.email" type="email" required class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all shadow-sm">
                            <p v-if="form.errors.email" class="text-xs font-bold text-rose-500 ml-1">{{ form.errors.email }}</p>
                        </div>

                        <div class="space-y-2">
                            <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Teléfono</label>
                            <input v-model="form.phone" @input="form.phone = formatPhone($event.target.value)" type="tel" placeholder="0414-1234567" maxlength="12" class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all shadow-sm">
                            <p v-if="form.errors.phone" class="text-xs font-bold text-rose-500 ml-1">{{ form.errors.phone }}</p>
                        </div>

                        <div class="space-y-2 md:col-span-2">
                            <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Roles (puede seleccionar varios)</label>
                            <div class="bg-slate-50 border-2 border-slate-200 rounded-2xl p-4 grid grid-cols-2 gap-3 shadow-sm">
                                <label v-for="role in roles" :key="role.id" class="flex items-center gap-3 cursor-pointer group p-2 rounded-xl hover:bg-slate-100 transition-colors">
                                    <input
                                        type="checkbox"
                                        :value="role.name"
                                        v-model="form.roles"
                                        class="rounded accent-primary-600 w-4 h-4 cursor-pointer"
                                    >
                                    <span class="text-sm font-bold text-slate-700 group-hover:text-primary-700 transition-colors">{{ role.name }}</span>
                                </label>
                            </div>
                            <p v-if="form.errors.roles" class="text-xs font-bold text-rose-500 ml-1">{{ form.errors.roles }}</p>
                        </div>

                        <div class="space-y-2 md:col-span-2" v-if="!$page.props.security_questions_enabled">
                            <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Contraseña</label>
                            <input v-model="form.password" type="password" :required="!isEditing" :placeholder="isEditing ? 'Dejar vacío para mantener actual' : 'Escribe una contraseña segura'" class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all shadow-sm">
                            <p class="text-[10px] text-slate-400 mt-1 ml-1 font-medium leading-tight">
                                <i class="fas fa-info-circle mr-1"></i> Mínimo 8 caracteres. Debe incluir mayúsculas, minúsculas, números y símbolos (ej. @, #, $).
                            </p>
                            <p v-if="form.errors.password" class="text-xs font-bold text-rose-500 ml-1">{{ form.errors.password }}</p>
                        </div>
                    </div>

                    <div v-if="isEditing" class="pt-6 mt-4 border-t-2 border-slate-50 flex items-center justify-between">
                        <div>
                            <h4 class="text-sm font-bold text-slate-800">Estado de la cuenta</h4>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1">Los inactivos no pueden ingresar</p>
                        </div>
                        <label class="flex items-center gap-3 cursor-pointer group">
                            <div class="relative">
                                <input type="checkbox" v-model="form.is_active" class="sr-only peer">
                                <div class="w-12 h-7 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-emerald-500"></div>
                            </div>
                            <span class="text-xs font-black uppercase tracking-widest transition-colors" :class="form.is_active ? 'text-emerald-500' : 'text-slate-400'">{{ form.is_active ? 'Activa' : 'Suspendida' }}</span>
                        </label>
                    </div>

                    <div class="flex items-center justify-end gap-4 mt-8 pt-4">
                        <button type="button" @click="showModal = false" class="px-6 py-3.5 text-sm font-black uppercase tracking-widest text-slate-400 hover:text-slate-600 transition-all">Cancelar</button>
                        <button type="submit" :disabled="form.processing" class="flex items-center gap-2 px-8 py-3.5 bg-primary-600 text-white text-[11px] font-black uppercase tracking-widest rounded-2xl shadow-lg shadow-primary-500/30 hover:bg-primary-700 hover:-translate-y-0.5 transition-all disabled:opacity-50">
                            <i class="fas fa-save"></i>
                            {{ isEditing ? 'Guardar Cambios' : 'Crear Usuario' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AppLayout>
</template>