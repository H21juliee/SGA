<script setup>
import { ref, computed, watch } from 'vue'
import { router, useForm, usePage, Link } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'
import Modal from '@/Components/UI/Modal.vue'
import Swal from 'sweetalert2'

const props = defineProps({
    users: Object,
    roles: Array,
    filters: Object,
})

const page = usePage()
const isCurrentUserSuperAdmin = computed(() => {
    const authRoles = page.props.auth?.roles ?? []
    return authRoles.includes('SuperAdmin')
})

// El rol SuperAdmin no debe aparecer en los checkboxes ordinarios
const selectableRoles = computed(() => {
    return (props.roles || []).filter(r => r.name !== 'SuperAdmin')
})

const search = ref(props.filters.search || '')
const roleFilter = ref(props.filters.role || '')
const statusFilter = ref(props.filters.status || 'all')
const sortCol = ref(props.filters.sort || 'name')
const sortDir = ref(props.filters.direction || 'asc')

let searchTimeout = null
watch([search, roleFilter, statusFilter], () => {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
        loadData()
    }, 300)
})

function loadData() {
    router.get('/admin/users', {
        search: search.value,
        role: roleFilter.value,
        status: statusFilter.value,
        sort: sortCol.value,
        direction: sortDir.value,
    }, { preserveState: true, replace: true })
}

// Mostrar alerta con contraseña temporal única (Opción A) cuando se crea o resetea un usuario
watch(() => page.props.flash?.temp_credentials, (creds) => {
    if (!creds || !creds.password) return

    const isCreated = creds.action === 'created'
    const credentialText = `Usuario: ${creds.name}\nCorreo: ${creds.email}\nCédula: ${creds.cedula || 'N/A'}\nContraseña temporal: ${creds.password}`

    Swal.fire({
        title: isCreated ? '¡Usuario Creado!' : '¡Contraseña Restablecida!',
        html: `
            <div class="text-left space-y-3 mt-2">
                <p class="text-xs text-slate-500 font-medium leading-relaxed">
                    Se ha generado una <strong>contraseña temporal segura de un solo uso</strong> para <strong>${creds.name}</strong>. Entréguesela al usuario; el sistema le solicitará cambiarla y configurar sus preguntas de seguridad en su primer ingreso.
                </p>
                <div class="bg-slate-50 border-2 border-slate-200 rounded-2xl p-4 space-y-2 text-xs">
                    <div class="flex justify-between items-center">
                        <span class="font-bold text-slate-400 uppercase tracking-wider">Correo:</span>
                        <span class="font-black text-slate-700">${creds.email}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="font-bold text-slate-400 uppercase tracking-wider">Documento:</span>
                        <span class="font-black text-slate-700">${creds.cedula || 'N/A'}</span>
                    </div>
                    <div class="pt-2 border-t border-slate-200 flex justify-between items-center">
                        <span class="font-bold text-primary-600 uppercase tracking-wider">Clave Temporal:</span>
                        <code class="px-3 py-1.5 bg-primary-50 text-primary-700 rounded-xl font-black text-base tracking-widest border border-primary-200 select-all">${creds.password}</code>
                    </div>
                </div>
            </div>
        `,
        icon: 'success',
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-copy mr-1.5"></i> Copiar Credenciales',
        cancelButtonText: 'Cerrar',
        buttonsStyling: false,
        customClass: {
            popup: 'rounded-3xl border-2 border-slate-100 shadow-2xl p-6',
            title: 'text-2xl font-black text-slate-800',
            confirmButton: 'px-6 py-3 bg-primary-600 text-white text-[11px] font-black uppercase tracking-widest rounded-2xl shadow-lg shadow-primary-600/20 hover:bg-primary-500 transition-all mx-2 cursor-pointer',
            cancelButton: 'px-6 py-3 bg-slate-100 text-slate-600 text-[11px] font-black uppercase tracking-widest rounded-2xl hover:bg-slate-200 transition-all mx-2 cursor-pointer',
        },
    }).then((result) => {
        if (result.isConfirmed && navigator.clipboard) {
            navigator.clipboard.writeText(credentialText)
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Credenciales copiadas al portapapeles',
                showConfirmButton: false,
                timer: 2500,
            })
        }
    })
}, { immediate: true })

// --- Validación de Documento / Cédula homologada con Estudiante y Representante ---
const docType = ref('V')
const docNumber = ref('')
const docError = ref('')

const docPlaceholder = computed(() => {
    switch (docType.value) {
        case 'V': return 'Ej: 18456789 (6-9 dígitos)'
        case 'E': return 'Ej: 84550210 (6-9 dígitos)'
        case 'P': return 'Ej: AB1234567 (6-15 caracteres)'
        default: return 'Ej: 18456789'
    }
})

const docMaxLength = computed(() => (docType.value === 'P' ? 15 : 9))

function syncCedulaField() {
    const cleanNum = docNumber.value.trim()
    form.cedula = cleanNum ? `${docType.value}-${cleanNum}` : ''
}

function allowOnlyDocKey(e) {
    if (['Backspace', 'Delete', 'Tab', 'Escape', 'Enter', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Home', 'End'].includes(e.key)) {
        return
    }
    if (e.ctrlKey || e.metaKey) {
        return
    }
    if (docType.value === 'P') {
        if (!/^[a-zA-Z0-9]$/.test(e.key)) e.preventDefault()
    } else {
        if (!/^[0-9]$/.test(e.key)) e.preventDefault()
    }
}

function allowOnlyNameKey(e) {
    if (['Backspace', 'Delete', 'Tab', 'Escape', 'Enter', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Home', 'End', ' '].includes(e.key)) {
        return
    }
    if (e.ctrlKey || e.metaKey) {
        return
    }
    if (!/^[\p{L}\s'-]$/u.test(e.key)) {
        e.preventDefault()
    }
}

function onDocTypeChange() {
    docError.value = ''
    if (docType.value !== 'P') {
        docNumber.value = docNumber.value.replace(/[^0-9]/g, '').slice(0, 9)
    } else {
        docNumber.value = docNumber.value.toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 15)
    }
    syncCedulaField()
}

function onDocNumberInput(e) {
    let val = (e.target.value || '').toUpperCase()
    if (docType.value === 'P') {
        val = val.replace(/[^A-Z0-9]/g, '').slice(0, 15)
    } else {
        val = val.replace(/[^0-9]/g, '').slice(0, 9)
    }
    docNumber.value = val
    e.target.value = val
    docError.value = ''
    if (form.errors.cedula) form.clearErrors('cedula')
    syncCedulaField()
}

function validateDocNumber() {
    const num = docNumber.value.trim()
    if (!num) {
        docError.value = 'El número de documento es obligatorio.'
        return false
    }
    if (docType.value === 'P') {
        if (!/^[A-Z0-9]{6,15}$/.test(num)) {
            docError.value = 'El pasaporte debe tener entre 6 y 15 caracteres alfanuméricos.'
            return false
        }
    } else {
        if (!/^\d{6,9}$/.test(num)) {
            docError.value = 'La cédula debe contener entre 6 y 9 dígitos numéricos.'
            return false
        }
    }
    docError.value = ''
    return true
}

function parseExistingCedula(cedulaStr) {
    if (!cedulaStr) {
        docType.value = 'V'
        docNumber.value = ''
        docError.value = ''
        return
    }
    const clean = String(cedulaStr).trim().toUpperCase()
    const match = clean.match(/^([VEP])-(.+)$/)
    if (match) {
        docType.value = match[1]
        docNumber.value = match[2]
    } else {
        docType.value = 'V'
        docNumber.value = clean.replace(/[^0-9]/g, '').slice(0, 9)
    }
    docError.value = ''
}

function splitFullNameFallback(fullName) {
    const clean = (fullName || '').trim()
    if (!clean) return { first: '', last: '' }
    const parts = clean.split(/\s+/)
    if (parts.length === 1) return { first: parts[0], last: '' }
    if (parts.length === 2) return { first: parts[0], last: parts[1] }
    const half = Math.ceil(parts.length / 2)
    return {
        first: parts.slice(0, half).join(' '),
        last: parts.slice(half).join(' '),
    }
}

function formatPhone(value) {
    if (!value) return ''
    let val = value.replace(/[^0-9]/g, '')
    if (val.length > 0 && val[0] !== '0') {
        val = '0' + val
    }
    if (val.length > 4) {
        val = val.substring(0, 4) + '-' + val.substring(4)
    }
    if (val.length > 12) {
        val = val.substring(0, 12)
    }
    return val
}

const showModal = ref(false)
const isEditing = ref(false)
const targetUser = ref(null)

const isEditingTargetSuperAdmin = computed(() => {
    return isEditing.value && targetUser.value?.roles?.some(r => r.name === 'SuperAdmin')
})

const form = useForm({
    first_name: '',
    last_name: '',
    email: '',
    cedula: '',
    phone: '',
    password: '',
    roles: [],
    is_active: true,
})

function resetUserForm() {
    form.first_name = ''
    form.last_name = ''
    form.email = ''
    form.cedula = ''
    form.phone = ''
    form.password = ''
    form.roles = []
    form.is_active = true
    docType.value = 'V'
    docNumber.value = ''
    docError.value = ''
    form.clearErrors()
}

function openCreateModal() {
    isEditing.value = false
    targetUser.value = null
    resetUserForm()
    showModal.value = true
}

function openEditModal(user) {
    isEditing.value = true
    targetUser.value = user
    resetUserForm()

    const fallback = splitFullNameFallback(user.name)
    form.first_name = user.first_name || fallback.first
    form.last_name = user.last_name || fallback.last
    form.email = user.email
    form.cedula = user.cedula || ''
    parseExistingCedula(user.cedula)
    syncCedulaField()
    form.phone = user.phone || ''
    form.password = ''
    form.roles = user.roles.map(r => r.name).filter(name => name !== 'SuperAdmin')
    form.is_active = user.is_active === 1 || user.is_active === true

    showModal.value = true
}

function validateClientForm() {
    form.clearErrors()
    let valid = true

    if (!form.first_name.trim()) {
        form.setError('first_name', 'Los nombres son obligatorios.')
        valid = false
    }
    if (!form.last_name.trim()) {
        form.setError('last_name', 'Los apellidos son obligatorios.')
        valid = false
    }
    if (!validateDocNumber()) {
        form.setError('cedula', docError.value)
        valid = false
    }
    if (!form.email.trim()) {
        form.setError('email', 'El correo electrónico es obligatorio.')
        valid = false
    } else if (!/^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/.test(form.email.trim())) {
        form.setError('email', 'Ingresa un correo electrónico válido (ej. jperez@colegio.edu.ve).')
        valid = false
    }
    if (form.phone && !/^(0412|0414|0424|0416|0426|02\d{2})-\d{7}$/.test(form.phone)) {
        form.setError('phone', 'El teléfono debe tener un formato válido (ej. 0414-1234567).')
        valid = false
    }
    if (!isEditingTargetSuperAdmin.value && form.roles.length === 0) {
        form.setError('roles', 'Debe seleccionar al menos un rol para el usuario.')
        valid = false
    }

    return valid
}

function submit() {
    syncCedulaField()
    if (!validateClientForm()) return

    if (isEditing.value) {
        form.put(`/admin/users/${targetUser.value.id}`, {
            onSuccess: () => {
                showModal.value = false
                resetUserForm()
            }
        })
    } else {
        form.post('/admin/users', {
            onSuccess: () => {
                showModal.value = false
                resetUserForm()
            }
        })
    }
}

function destroy(user) {
    const isDeactivating = Boolean(user.is_active)
    Swal.fire({
        title: isDeactivating ? '¿Desactivar Usuario?' : '¿Reactivar Usuario?',
        text: `¿Seguro que desea ${isDeactivating ? 'desactivar' : 'reactivar'} el acceso de ${user.name}?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: isDeactivating ? 'Sí, desactivar' : 'Sí, reactivar',
        cancelButtonText: 'Cancelar',
        buttonsStyling: false,
        customClass: {
            popup: 'rounded-3xl border-2 border-slate-100 shadow-2xl p-6',
            title: 'text-2xl font-black text-slate-800',
            htmlContainer: 'text-slate-500 font-medium',
            confirmButton: isDeactivating
                ? 'px-6 py-3 bg-red-500 text-white text-[11px] font-black uppercase tracking-widest rounded-2xl shadow-lg shadow-red-500/20 hover:bg-red-400 transition-all mx-2 cursor-pointer'
                : 'px-6 py-3 bg-emerald-600 text-white text-[11px] font-black uppercase tracking-widest rounded-2xl shadow-lg shadow-emerald-600/20 hover:bg-emerald-500 transition-all mx-2 cursor-pointer',
            cancelButton: 'px-6 py-3 bg-slate-200 text-slate-600 text-[11px] font-black uppercase tracking-widest rounded-2xl hover:bg-slate-300 transition-all mx-2 cursor-pointer'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/admin/users/${user.id}`, {
                preserveScroll: true,
            })
        }
    })
}

function resetPassword(user) {
    Swal.fire({
        title: '¿Restablecer contraseña?',
        html: `<p class="text-sm text-slate-600">Se generará una <strong>nueva contraseña temporal aleatoria</strong> para <strong>${user.name}</strong> y se reiniciarán sus preguntas de seguridad.</p><p class="text-xs text-slate-500 mt-2">El sistema te mostrará la clave temporal en pantalla para que puedas copiarla y entregársela al usuario.</p>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, generar clave temporal',
        cancelButtonText: 'Cancelar',
        buttonsStyling: false,
        customClass: {
            popup: 'rounded-3xl border-2 border-slate-100 shadow-2xl p-6',
            title: 'text-2xl font-black text-slate-800',
            confirmButton: 'px-6 py-3 bg-amber-500 text-white text-[11px] font-black uppercase tracking-widest rounded-2xl shadow-lg shadow-amber-500/20 hover:bg-amber-400 transition-all mx-2 cursor-pointer',
            cancelButton: 'px-6 py-3 bg-slate-200 text-slate-600 text-[11px] font-black uppercase tracking-widest rounded-2xl hover:bg-slate-300 transition-all mx-2 cursor-pointer',
        }
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(`/admin/users/${user.id}/reset-password`, {}, {
                preserveScroll: true,
            })
        }
    })
}

function transferSuperAdmin(user) {
    Swal.fire({
        title: '¿Transferir Rol SuperAdmin?',
        html: `
            <div class="text-left space-y-2 text-sm text-slate-600">
                <p>Estás a punto de transferir la máxima autoridad del sistema (<strong>SuperAdmin</strong>) a <strong>${user.name}</strong>.</p>
                <div class="p-3 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-xs font-semibold">
                    <i class="fas fa-shield-alt mr-1"></i> Por seguridad solo puede existir un único SuperAdmin. Al confirmar, <strong>${user.name}</strong> será el único SuperAdmin y tu cuenta pasará a tener el rol de <strong>Administrador</strong>.
                </div>
            </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, transferir SuperAdmin',
        cancelButtonText: 'Cancelar',
        buttonsStyling: false,
        customClass: {
            popup: 'rounded-3xl border-2 border-slate-100 shadow-2xl p-6',
            title: 'text-2xl font-black text-slate-800',
            confirmButton: 'px-6 py-3 bg-rose-600 text-white text-[11px] font-black uppercase tracking-widest rounded-2xl shadow-lg shadow-rose-600/20 hover:bg-rose-500 transition-all mx-2 cursor-pointer',
            cancelButton: 'px-6 py-3 bg-slate-200 text-slate-600 text-[11px] font-black uppercase tracking-widest rounded-2xl hover:bg-slate-300 transition-all mx-2 cursor-pointer',
        }
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(`/admin/users/${user.id}/transfer-superadmin`, {}, {
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

                <button v-if="$can('users.manage')" @click="openCreateModal" class="flex items-center justify-center gap-2 px-6 py-3.5 bg-primary-600 text-white text-[11px] font-black uppercase tracking-widest rounded-2xl shadow-lg shadow-primary-600/20 hover:bg-primary-500 hover:-translate-y-0.5 transition-all w-full lg:w-auto cursor-pointer">
                    <i class="fas fa-plus"></i>
                    <span>Nuevo Registro</span>
                </button>
            </div>

            <!-- Toolbar (Search, Role Filter & Status Filter) -->
            <div class="glass-card rounded-3xl p-5 sm:p-6 shadow-xl border-2 border-transparent animate-fade-in-up" style="animation-delay: 100ms">
                <div class="flex flex-col lg:flex-row gap-4 justify-between items-stretch lg:items-center">
                    <div class="relative flex-1">
                        <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Buscar por nombres, apellidos, correo o cédula (Ej: V-18456789)..."
                            class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl pl-12 pr-4 py-3.5 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all shadow-sm"
                        >
                    </div>

                    <div class="flex flex-col sm:flex-row gap-3 items-stretch sm:items-center">
                        <!-- Filtro por Estado (Todos / Activos / Inactivos) -->
                        <div class="bg-slate-100 p-1.5 rounded-2xl flex items-center gap-1 border border-slate-200/70">
                            <button
                                type="button"
                                @click="statusFilter = 'all'"
                                class="px-3.5 py-2 rounded-xl text-[11px] font-black uppercase tracking-wider transition-all cursor-pointer"
                                :class="statusFilter === 'all' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                            >
                                Todos
                            </button>
                            <button
                                type="button"
                                @click="statusFilter = 'active'"
                                class="px-3.5 py-2 rounded-xl text-[11px] font-black uppercase tracking-wider transition-all flex items-center gap-1.5 cursor-pointer"
                                :class="statusFilter === 'active' ? 'bg-emerald-500 text-white shadow-sm' : 'text-slate-500 hover:text-emerald-600'"
                            >
                                <span class="w-1.5 h-1.5 rounded-full" :class="statusFilter === 'active' ? 'bg-white' : 'bg-emerald-500'"></span>
                                Activos
                            </button>
                            <button
                                type="button"
                                @click="statusFilter = 'inactive'"
                                class="px-3.5 py-2 rounded-xl text-[11px] font-black uppercase tracking-wider transition-all flex items-center gap-1.5 cursor-pointer"
                                :class="statusFilter === 'inactive' ? 'bg-slate-600 text-white shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                            >
                                <span class="w-1.5 h-1.5 rounded-full" :class="statusFilter === 'inactive' ? 'bg-white' : 'bg-slate-400'"></span>
                                Inactivos
                            </button>
                        </div>

                        <!-- Filtro por Rol -->
                        <div class="relative w-full sm:w-56">
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
            </div>

            <!-- Listado de Usuarios -->
            <div class="space-y-3 animate-fade-in-up" style="animation-delay: 200ms">
                <div
                    v-for="user in users.data"
                    :key="user.id"
                    class="bg-white p-4 sm:p-5 rounded-2xl shadow-sm border-2 transition-all flex flex-col md:flex-row md:items-center justify-between gap-4 group hover:border-slate-300"
                    :class="!user.is_active ? 'opacity-75 bg-slate-50/60 border-slate-200/70' : 'border-slate-100'"
                >
                    <!-- Info Principal -->
                    <div class="flex items-center gap-4 flex-1 min-w-0">
                        <div class="relative shrink-0">
                            <div class="w-12 h-12 rounded-full flex items-center justify-center font-black text-sm shadow-sm border bg-slate-50 text-slate-500 border-slate-200 group-hover:bg-primary-50 group-hover:text-primary-600 transition-colors">
                                {{ user.name.charAt(0) }}
                            </div>
                            <!-- Status Indicator -->
                            <div class="absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 rounded-full border-2 border-white flex items-center justify-center"
                                 :class="user.is_active ? 'bg-emerald-500' : 'bg-slate-400'" :title="user.is_active ? 'Usuario Activo' : 'Usuario Inactivo'">
                                <div v-if="user.is_active" class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></div>
                            </div>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <h3 class="text-base font-black text-slate-800 truncate">{{ user.name }}</h3>
                                <span v-if="!user.is_active" class="px-2 py-0.5 rounded-md text-[9px] font-black uppercase tracking-widest bg-slate-200 text-slate-600">
                                    Inactivo
                                </span>
                            </div>
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
                                      'bg-primary-50 text-primary-600 border-primary-100': r.name === 'Docente' || r.name === 'Administrador',
                                      'bg-emerald-50 text-emerald-600 border-emerald-100': r.name === 'Secretaria',
                                      'bg-rose-50 text-rose-600 border-rose-100': r.name === 'SuperAdmin',
                                      'bg-slate-50 text-slate-500 border-slate-200': !['Docente', 'Administrador', 'Secretaria', 'SuperAdmin'].includes(r.name)
                                  }">
                                <i v-if="r.name === 'SuperAdmin'" class="fas fa-crown mr-1 text-[8px]"></i>
                                {{ r.name }}
                            </span>
                            <span v-if="!user.roles.length" class="text-xs text-slate-300 italic">Sin rol</span>
                        </div>

                        <!-- Botones -->
                        <div class="flex items-center gap-2 bg-slate-50 p-1.5 rounded-xl border border-slate-100 shrink-0">
                            <Link :href="'/admin/users/' + user.id"
                                  class="w-9 h-9 rounded-lg bg-white text-slate-500 hover:text-primary-600 hover:bg-primary-50 hover:border-primary-200 hover:shadow-sm transition-all border border-slate-200 flex items-center justify-center"
                                  title="Ver Detalles">
                                <i class="fas fa-eye text-[11px]"></i>
                            </Link>

                            <!-- Si el usuario es el propio SuperAdmin logueado, puede editar sus datos básicos -->
                            <button
                                v-if="$can('users.manage') && (!user.roles.some(r => r.name === 'SuperAdmin') || isCurrentUserSuperAdmin)"
                                @click="openEditModal(user)"
                                class="w-9 h-9 rounded-lg bg-white text-slate-500 hover:text-primary-600 hover:bg-primary-50 hover:border-primary-200 hover:shadow-sm transition-all border border-slate-200 flex items-center justify-center cursor-pointer"
                                title="Editar"
                            >
                                <i class="fas fa-edit text-[11px]"></i>
                            </button>

                            <template v-if="!user.roles.some(r => r.name === 'SuperAdmin')">
                                <!-- Botón Transferir SuperAdmin (Exclusivo para el SuperAdmin actual sobre usuarios activos) -->
                                <button
                                    v-if="isCurrentUserSuperAdmin && user.is_active"
                                    @click="transferSuperAdmin(user)"
                                    class="w-9 h-9 rounded-lg bg-white text-rose-500 hover:text-rose-600 hover:bg-rose-50 hover:border-rose-200 hover:shadow-sm transition-all border border-slate-200 flex items-center justify-center cursor-pointer"
                                    title="Transferir titularidad de SuperAdmin a este usuario"
                                >
                                    <i class="fas fa-crown text-[11px]"></i>
                                </button>

                                <button
                                    v-if="$can('users.reset_password') && $page.props.security_questions_enabled"
                                    @click="resetPassword(user)"
                                    class="w-9 h-9 rounded-lg bg-white text-slate-500 hover:text-amber-600 hover:bg-amber-50 hover:border-amber-200 hover:shadow-sm transition-all border border-slate-200 flex items-center justify-center cursor-pointer"
                                    title="Generar Nueva Contraseña Temporal"
                                >
                                    <i class="fas fa-key text-[11px]"></i>
                                </button>

                                <button
                                    v-if="$can('users.manage')"
                                    @click="destroy(user)"
                                    class="w-9 h-9 rounded-lg transition-all border flex items-center justify-center cursor-pointer"
                                    :class="user.is_active
                                        ? 'bg-white text-slate-400 border-slate-200 hover:bg-red-50 hover:text-red-500 hover:border-red-200 hover:shadow-sm'
                                        : 'bg-emerald-50 text-emerald-600 border-emerald-200 hover:bg-emerald-500 hover:text-white hover:border-emerald-500 hover:shadow-sm'"
                                    :title="user.is_active ? 'Desactivar Usuario' : 'Reactivar Usuario'"
                                >
                                    <i class="fas text-[11px]" :class="user.is_active ? 'fa-power-off' : 'fa-user-check'"></i>
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
                            {{ isEditing ? 'Editar Usuario' : 'Nuevo Usuario' }}
                        </h3>
                        <p class="text-xs font-medium text-slate-400 mt-1">Completa los datos personales y de acceso del usuario</p>
                    </div>
                    <button @click="showModal = false" class="ml-auto w-10 h-10 rounded-full bg-slate-50 text-slate-400 hover:bg-slate-100 transition-all flex justify-center items-center cursor-pointer">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <form @submit.prevent="submit" novalidate class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Nombres -->
                        <div class="space-y-2">
                            <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">
                                Nombres <span class="text-rose-500">*</span>
                            </label>
                            <input
                                v-model="form.first_name"
                                @keydown="allowOnlyNameKey"
                                @input="form.clearErrors('first_name')"
                                type="text"
                                placeholder="Ej: Juan Carlos"
                                class="w-full bg-slate-50 border-2 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:bg-white focus:ring-0 outline-none transition-all shadow-sm"
                                :class="form.errors.first_name ? 'border-rose-300 focus:border-rose-400' : 'border-slate-200 focus:border-primary-400'"
                            >
                            <p v-if="form.errors.first_name" class="text-xs font-bold text-rose-500 ml-1">{{ form.errors.first_name }}</p>
                        </div>

                        <!-- Apellidos -->
                        <div class="space-y-2">
                            <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">
                                Apellidos <span class="text-rose-500">*</span>
                            </label>
                            <input
                                v-model="form.last_name"
                                @keydown="allowOnlyNameKey"
                                @input="form.clearErrors('last_name')"
                                type="text"
                                placeholder="Ej: Pérez Rodríguez"
                                class="w-full bg-slate-50 border-2 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:bg-white focus:ring-0 outline-none transition-all shadow-sm"
                                :class="form.errors.last_name ? 'border-rose-300 focus:border-rose-400' : 'border-slate-200 focus:border-primary-400'"
                            >
                            <p v-if="form.errors.last_name" class="text-xs font-bold text-rose-500 ml-1">{{ form.errors.last_name }}</p>
                        </div>

                        <!-- Documento de Identidad / Cédula (Homologado con Estudiante y Representante) -->
                        <div class="space-y-2 md:col-span-2">
                            <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">
                                Cédula / Documento de Identidad <span class="text-rose-500">*</span>
                            </label>
                            <div class="flex gap-2">
                                <select
                                    v-model="docType"
                                    @change="onDocTypeChange"
                                    class="w-24 shrink-0 bg-slate-50 border-2 border-slate-200 rounded-2xl px-3 py-3 text-slate-700 text-sm font-black focus:border-primary-400 focus:bg-white outline-none transition-all shadow-sm cursor-pointer"
                                >
                                    <option value="V">V -</option>
                                    <option value="E">E -</option>
                                    <option value="P">P -</option>
                                </select>
                                <input
                                    :value="docNumber"
                                    @keydown="allowOnlyDocKey"
                                    @input="onDocNumberInput"
                                    @blur="validateDocNumber"
                                    type="text"
                                    :maxlength="docMaxLength"
                                    :placeholder="docPlaceholder"
                                    class="flex-1 bg-slate-50 border-2 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:bg-white focus:ring-0 outline-none transition-all shadow-sm"
                                    :class="(docError || form.errors.cedula) ? 'border-rose-300 focus:border-rose-400' : 'border-slate-200 focus:border-primary-400'"
                                >
                            </div>
                            <div class="flex items-center justify-between px-1">
                                <p v-if="docError || form.errors.cedula" class="text-xs font-bold text-rose-500">
                                    {{ docError || form.errors.cedula }}
                                </p>
                                <p v-else class="text-[10px] font-bold text-slate-400">
                                    Formato registrado: <span class="text-slate-600">{{ form.cedula || `${docType}-XXXXXXXX` }}</span>
                                </p>
                            </div>
                        </div>

                        <!-- Correo Electrónico -->
                        <div class="space-y-2">
                            <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">
                                Correo Electrónico <span class="text-rose-500">*</span>
                            </label>
                            <input
                                v-model="form.email"
                                @input="form.clearErrors('email')"
                                type="email"
                                placeholder="Ej: jperez@colegio.edu.ve"
                                class="w-full bg-slate-50 border-2 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:bg-white focus:ring-0 outline-none transition-all shadow-sm"
                                :class="form.errors.email ? 'border-rose-300 focus:border-rose-400' : 'border-slate-200 focus:border-primary-400'"
                            >
                            <p v-if="form.errors.email" class="text-xs font-bold text-rose-500 ml-1">{{ form.errors.email }}</p>
                        </div>

                        <!-- Teléfono -->
                        <div class="space-y-2">
                            <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Teléfono</label>
                            <input
                                v-model="form.phone"
                                @input="form.phone = formatPhone($event.target.value); form.clearErrors('phone')"
                                type="tel"
                                placeholder="Ej: 0414-1234567"
                                maxlength="12"
                                class="w-full bg-slate-50 border-2 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:bg-white focus:ring-0 outline-none transition-all shadow-sm"
                                :class="form.errors.phone ? 'border-rose-300 focus:border-rose-400' : 'border-slate-200 focus:border-primary-400'"
                            >
                            <p v-if="form.errors.phone" class="text-xs font-bold text-rose-500 ml-1">{{ form.errors.phone }}</p>
                        </div>

                        <!-- Roles (Excluyendo SuperAdmin) -->
                        <div class="space-y-2 md:col-span-2">
                            <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">
                                Roles del Usuario <span v-if="!isEditingTargetSuperAdmin" class="text-rose-500">*</span>
                            </label>

                            <div v-if="isEditingTargetSuperAdmin" class="p-4 rounded-2xl bg-rose-50 border border-rose-200 flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-rose-500 text-white flex items-center justify-center shrink-0">
                                    <i class="fas fa-crown text-sm"></i>
                                </div>
                                <div class="text-xs text-rose-800 font-semibold">
                                    Este usuario posee el rol exclusivo de <strong>SuperAdmin</strong>. Para transferir este rol a otro usuario activo, utiliza el botón <i class="fas fa-crown mx-1"></i> en el listado principal.
                                </div>
                            </div>

                            <div class="bg-slate-50 border-2 border-slate-200 rounded-2xl p-4 grid grid-cols-1 sm:grid-cols-2 gap-3 shadow-sm">
                                <label v-for="role in selectableRoles" :key="role.id" class="flex items-center gap-3 cursor-pointer group p-2 rounded-xl hover:bg-slate-100 transition-colors">
                                    <input
                                        type="checkbox"
                                        :value="role.name"
                                        v-model="form.roles"
                                        @change="form.clearErrors('roles')"
                                        class="rounded accent-primary-600 w-4 h-4 cursor-pointer"
                                    >
                                    <span class="text-sm font-bold text-slate-700 group-hover:text-primary-700 transition-colors">{{ role.name }}</span>
                                </label>
                            </div>
                            <p v-if="form.errors.roles" class="text-xs font-bold text-rose-500 ml-1">{{ form.errors.roles }}</p>
                        </div>

                        <!-- Aviso de Contraseña Temporal Automática (Opción A) -->
                        <div v-if="!isEditing && $page.props.security_questions_enabled" class="md:col-span-2 p-4 rounded-2xl bg-primary-50/70 border border-primary-100 flex items-start gap-3">
                            <i class="fas fa-shield-alt text-primary-600 mt-0.5"></i>
                            <p class="text-xs text-slate-600 font-medium leading-relaxed">
                                Al crear el usuario, el sistema generará automáticamente una <strong>contraseña temporal aleatoria segura</strong> y te la mostrará en pantalla para copiarla. El usuario deberá cambiarla en su primer inicio de sesión.
                            </p>
                        </div>

                        <div class="space-y-2 md:col-span-2" v-if="!$page.props.security_questions_enabled">
                            <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Contraseña</label>
                            <input
                                v-model="form.password"
                                type="password"
                                :required="!isEditing"
                                :placeholder="isEditing ? 'Dejar vacío para mantener la contraseña actual' : 'Ej: ClaveSegura#2026'"
                                class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all shadow-sm"
                            >
                            <p class="text-[10px] text-slate-400 mt-1 ml-1 font-medium leading-tight">
                                <i class="fas fa-info-circle mr-1"></i> Mínimo 8 caracteres. Debe incluir mayúsculas, minúsculas, números y símbolos (ej. @, #, $).
                            </p>
                            <p v-if="form.errors.password" class="text-xs font-bold text-rose-500 ml-1">{{ form.errors.password }}</p>
                        </div>
                    </div>

                    <div v-if="isEditing && !isEditingTargetSuperAdmin" class="pt-6 mt-4 border-t-2 border-slate-50 flex items-center justify-between">
                        <div>
                            <h4 class="text-sm font-bold text-slate-800">Estado de la cuenta</h4>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1">Los usuarios inactivos no pueden ingresar al sistema</p>
                        </div>
                        <label class="flex items-center gap-3 cursor-pointer group">
                            <div class="relative">
                                <input type="checkbox" v-model="form.is_active" class="sr-only peer">
                                <div class="w-12 h-7 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-emerald-500"></div>
                            </div>
                            <span class="text-xs font-black uppercase tracking-widest transition-colors" :class="form.is_active ? 'text-emerald-500' : 'text-slate-400'">{{ form.is_active ? 'Activa' : 'Inactiva' }}</span>
                        </label>
                    </div>

                    <div class="flex items-center justify-end gap-4 mt-8 pt-4">
                        <button type="button" @click="showModal = false" class="px-6 py-3.5 text-sm font-black uppercase tracking-widest text-slate-400 hover:text-slate-600 transition-all cursor-pointer">Cancelar</button>
                        <button type="submit" :disabled="form.processing" class="flex items-center gap-2 px-8 py-3.5 bg-primary-600 text-white text-[11px] font-black uppercase tracking-widest rounded-2xl shadow-lg shadow-primary-500/30 hover:bg-primary-700 hover:-translate-y-0.5 transition-all disabled:opacity-50 cursor-pointer">
                            <i class="fas fa-save"></i>
                            {{ isEditing ? 'Guardar Cambios' : 'Crear Usuario' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AppLayout>
</template>
