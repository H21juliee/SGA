<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'
import Modal from '@/Components/UI/Modal.vue'
import axios from 'axios'

const props = defineProps({
    roles: Array,
    groupedPermissions: Object,
    permissionLabels: Object,
})

// ── Modal state ──────────────────────────────────────
const showModal = ref(false)
const isEditing = ref(false)
const editingRole = ref(null)
const processing = ref(false)
const errors = ref({})

const form = ref({
    name: '',
    permissions: [],
})

function openCreateModal() {
    isEditing.value = false
    editingRole.value = null
    form.value = { name: '', permissions: [] }
    errors.value = {}
    showModal.value = true
}

async function openEditModal(role) {
    isEditing.value = true
    editingRole.value = role
    errors.value = {}
    form.value = { name: role.name, permissions: [] }

    // Load full permissions list via AJAX
    const res = await axios.get(`/admin/roles/${role.id}`)
    form.value.permissions = res.data.role.permissions ?? []
    showModal.value = true
}

function submit() {
    processing.value = true
    errors.value = {}

    const url = isEditing.value
        ? `/admin/roles/${editingRole.value.id}`
        : '/admin/roles'
    const method = isEditing.value ? 'put' : 'post'

    router[method](url, form.value, {
        onSuccess: () => { showModal.value = false },
        onError: (e) => { errors.value = e },
        onFinish: () => { processing.value = false },
    })
}

function deleteRole(role) {
    if (role.is_system) return
    if (!confirm(`¿Eliminar el rol "${role.name}"? Esta acción no puede deshacerse.`)) return
    router.delete(`/admin/roles/${role.id}`)
}

// ── Permission toggles ───────────────────────────────
function togglePermission(perm) {
    const idx = form.value.permissions.indexOf(perm)
    if (idx === -1) {
        form.value.permissions.push(perm)
    } else {
        form.value.permissions.splice(idx, 1)
    }
}

function toggleGroup(groupPerms) {
    const allOn = groupPerms.every(p => form.value.permissions.includes(p))
    if (allOn) {
        form.value.permissions = form.value.permissions.filter(p => !groupPerms.includes(p))
    } else {
        groupPerms.forEach(p => {
            if (!form.value.permissions.includes(p)) form.value.permissions.push(p)
        })
    }
}

function groupIsAll(groupPerms) {
    return groupPerms.every(p => form.value.permissions.includes(p))
}
function groupIsSome(groupPerms) {
    return groupPerms.some(p => form.value.permissions.includes(p)) && !groupIsAll(groupPerms)
}

const selectAll = computed({
    get: () => {
        const allPerms = Object.values(props.groupedPermissions).flat()
        return allPerms.every(p => form.value.permissions.includes(p))
    },
    set: (val) => {
        const allPerms = Object.values(props.groupedPermissions).flat()
        form.value.permissions = val ? [...allPerms] : []
    }
})

// ── Role color helper ────────────────────────────────
const roleColors = {
    'SuperAdmin':    'bg-rose-50 text-rose-600 border-rose-100',
    'Administrador': 'bg-primary-50 text-primary-600 border-primary-100',
    'Docente':       'bg-primary-50 text-primary-600 border-primary-100',
    'Secretaria':    'bg-emerald-50 text-emerald-600 border-emerald-100',
}
function roleColor(name) {
    return roleColors[name] || 'bg-slate-50 text-slate-600 border-slate-100'
}
</script>

<template>
    <AppLayout title="Roles y Permisos">
        <div class="space-y-8 max-w-12xl mx-auto">
            <!-- Header -->
            <div class="flex flex-col lg:flex-row gap-6 items-center justify-between animate-fade-in-up">
                <div>
                    <h2 class="text-3xl font-extrabold text-slate-800">
                        Roles y <span class="gradient-text">Permisos</span>
                    </h2>
                    <p class="text-slate-400 font-medium mt-1">Crea roles personalizados y asigna los permisos que necesitan</p>
                </div>
                <button v-if="$can('roles.manage')" @click="openCreateModal" class="flex items-center gap-2 px-6 py-3.5 bg-primary-600 text-white text-[11px] font-black uppercase tracking-widest rounded-2xl shadow-lg shadow-primary-600/20 hover:bg-primary-500 hover:-translate-y-0.5 transition-all">
                    <i class="fas fa-plus"></i>
                    Nuevo Rol
                </button>
            </div>

            <!-- Roles Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5 animate-fade-in-up" style="animation-delay:100ms">
                <div
                    v-for="role in roles" :key="role.id"
                    class="glass-card rounded-2xl p-6 flex flex-col gap-4 hover:shadow-xl transition-all duration-300"
                >
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center text-lg font-black border shadow-sm"
                                 :class="roleColor(role.name)">
                                <i class="fas" :class="role.is_system ? 'fa-shield-halved' : 'fa-user-tag'"></i>
                            </div>
                            <div>
                                <h3 class="font-extrabold text-slate-800 text-base leading-tight">{{ role.name }}</h3>
                                <span v-if="role.is_system" class="text-[9px] font-black uppercase tracking-widest text-rose-500">
                                    Sistema — Protegido
                                </span>
                            </div>
                        </div>
                        <!-- Actions -->
                        <div class="flex gap-1.5" v-if="!role.is_system">
                            <button v-if="$can('roles.manage')" @click="openEditModal(role)" class="w-9 h-9 rounded-xl bg-slate-50 text-slate-400 hover:bg-primary-500 hover:text-white transition-all shadow-sm flex items-center justify-center text-sm" title="Editar Rol">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button v-if="$can('roles.manage')" @click="deleteRole(role)" class="w-9 h-9 rounded-xl bg-slate-50 text-slate-400 hover:bg-rose-500 hover:text-white transition-all shadow-sm flex items-center justify-center text-sm" title="Eliminar">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Stats -->
                    <div class="flex gap-3 pt-2 border-t border-slate-100">
                        <div class="flex-1 text-center">
                            <p class="text-xl font-black text-slate-800">{{ role.permissions_count }}</p>
                            <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Permisos</p>
                        </div>
                        <div class="w-px bg-slate-100"></div>
                        <div class="flex-1 text-center">
                            <p class="text-xl font-black text-slate-800">{{ role.users_count }}</p>
                            <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Usuarios</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create / Edit Modal -->
        <Modal :show="showModal" @close="showModal = false" max-width="2xl">
            <div class="p-8">
                <!-- Header -->
                <div class="flex items-center gap-4 mb-8">
                    <div class="w-12 h-12 rounded-2xl bg-primary-50 text-primary-500 flex items-center justify-center text-xl shadow-sm">
                        <i class="fas" :class="isEditing ? 'fa-user-shield' : 'fa-plus-circle'"></i>
                    </div>
                    <div>
                        <h3 class="text-2xl font-black text-slate-800">{{ isEditing ? 'Editar Rol' : 'Nuevo Rol' }}</h3>
                        <p class="text-xs text-slate-400 font-medium mt-1">Selecciona los permisos que tendrá este rol</p>
                    </div>
                    <button @click="showModal = false" class="ml-auto w-9 h-9 rounded-full bg-slate-50 text-slate-400 hover:bg-slate-100 transition-all flex items-center justify-center">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Name -->
                    <div class="space-y-2">
                        <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Nombre del Rol</label>
                        <input
                            v-model="form.name"
                            type="text"
                            required
                            :disabled="isEditing && editingRole?.is_system"
                            placeholder="Ej: Coordinador Académico"
                            class="w-full bg-slate-50 border-2 border-slate-100 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all disabled:opacity-60"
                        >
                        <p v-if="errors.name" class="text-xs font-bold text-rose-500 ml-1">{{ errors.name }}</p>
                    </div>

                    <!-- Permissions -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between mb-1">
                            <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest">Permisos</label>
                            <label class="flex items-center gap-2 cursor-pointer text-xs font-bold text-slate-500 hover:text-primary-600 transition-colors">
                                <input type="checkbox" v-model="selectAll" class="rounded accent-primary-600">
                                Seleccionar todo
                            </label>
                        </div>
                        
                        <div class="max-h-[350px] overflow-y-auto pr-2 space-y-3 custom-scrollbar">
                            <div v-for="(perms, group) in groupedPermissions" :key="group"
                                 class="bg-slate-50 border border-slate-200 rounded-2xl overflow-hidden">
                                <!-- Group header -->
                                <button type="button"
                                    @click="toggleGroup(perms)"
                                    class="w-full flex items-center gap-3 px-4 py-3 text-left hover:bg-slate-100 transition-colors">
                                    <div class="w-5 h-5 border-2 rounded flex items-center justify-center transition-colors flex-shrink-0"
                                         :class="groupIsAll(perms) ? 'bg-primary-500 border-primary-500' : (groupIsSome(perms) ? 'bg-primary-200 border-primary-300' : 'border-slate-300 bg-white')">
                                        <i v-if="groupIsAll(perms)" class="fas fa-check text-[9px] text-white"></i>
                                        <i v-else-if="groupIsSome(perms)" class="fas fa-minus text-[9px] text-primary-600"></i>
                                    </div>
                                    <span class="text-xs font-black text-slate-700 uppercase tracking-widest">{{ group }}</span>
                                    <span class="ml-auto text-[10px] font-bold text-slate-400">{{ perms.filter(p => form.permissions.includes(p)).length }}/{{ perms.length }}</span>
                                </button>
                                <!-- Permissions list -->
                                <div class="px-4 pb-3 grid grid-cols-1 sm:grid-cols-2 gap-1.5 border-t border-slate-200 pt-3">
                                    <label
                                        v-for="perm in perms" :key="perm"
                                        class="flex items-center gap-2.5 cursor-pointer group"
                                    >
                                        <input
                                            type="checkbox"
                                            :value="perm"
                                            :checked="form.permissions.includes(perm)"
                                            @change="togglePermission(perm)"
                                            class="rounded accent-primary-600 w-4 h-4"
                                        >
                                        <span class="text-xs font-semibold text-slate-500 group-hover:text-slate-700 transition-colors">{{ permissionLabels?.[perm] || perm }}</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <p v-if="errors.permissions" class="text-xs font-bold text-rose-500 ml-1">{{ errors.permissions }}</p>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-between pt-4 border-t border-slate-100">
                        <span class="text-xs font-bold text-slate-400">
                            <i class="fas fa-key mr-1"></i>
                            {{ form.permissions.length }} permisos seleccionados
                        </span>
                        <div class="flex gap-3">
                            <button type="button" @click="showModal = false" class="px-6 py-3 text-sm font-black uppercase tracking-widest text-slate-400 hover:text-slate-600 transition-all">
                                Cancelar
                            </button>
                            <button type="submit" :disabled="processing" class="px-8 py-3 bg-primary-600 text-white text-[11px] font-black uppercase tracking-widest rounded-2xl shadow-lg shadow-primary-500/30 hover:bg-primary-700 hover:-translate-y-0.5 transition-all disabled:opacity-50">
                                <i class="fas fa-save mr-1.5"></i>
                                {{ isEditing ? 'Guardar Cambios' : 'Crear Rol' }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </Modal>
    </AppLayout>
</template>