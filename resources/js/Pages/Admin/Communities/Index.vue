<script setup>
import { ref, watch, computed } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'
import Modal from '@/Components/UI/Modal.vue'

const props = defineProps({
    communities: { type: Object, required: true },
    filters: { type: Object, default: () => ({ search: '', status: 'all', sort: 'name', direction: 'asc' }) },
    stats: { type: Object, default: () => ({ total: 0, active: 0, inactive: 0 }) },
})

const search = ref(props.filters.search || '')
const statusFilter = ref(props.filters.status || 'all')
const sortCol = ref(props.filters.sort || 'name')
const sortDir = ref(props.filters.direction || 'asc')
const showFilters = ref(false)

const activeFiltersCount = computed(() => {
    let count = 0
    if (sortCol.value !== 'name' || sortDir.value !== 'asc') count++
    return count
})

const doSearch = () => {
    router.get('/admin/communities', {
        search: search.value?.trim() || undefined,
        status: statusFilter.value !== 'all' ? statusFilter.value : undefined,
        sort: sortCol.value !== 'name' ? sortCol.value : undefined,
        direction: sortDir.value !== 'asc' ? sortDir.value : undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    })
}

const setStatusFilter = (nextStatus) => {
    statusFilter.value = nextStatus
    doSearch()
}

const toggleSortDirection = () => {
    sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
    doSearch()
}

const resetFilters = () => {
    search.value = ''
    statusFilter.value = 'all'
    sortCol.value = 'name'
    sortDir.value = 'asc'
    doSearch()
}

let searchTimeout = null
watch(search, () => {
    if (searchTimeout) clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
        doSearch()
    }, 400)
})

watch(sortCol, () => {
    doSearch()
})

// Modal Crear / Editar
const showModal = ref(false)
const editingCommunity = ref(null)
const form = useForm({ name: '' })

const openCreateModal = () => {
    editingCommunity.value = null
    form.reset()
    form.clearErrors()
    showModal.value = true
}

const openEditModal = (community) => {
    editingCommunity.value = community
    form.name = community.name
    form.clearErrors()
    showModal.value = true
}

const closeModal = () => {
    showModal.value = false
    editingCommunity.value = null
    form.reset()
    form.clearErrors()
}

const submit = () => {
    if (editingCommunity.value) {
        form.put(`/admin/communities/${editingCommunity.value.id}`, {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        })
    } else {
        form.post('/admin/communities', {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        })
    }
}

const toggleCommunity = (community) => {
    router.patch(`/admin/communities/${community.id}/toggle`, {}, { preserveScroll: true })
}
</script>

<template>
    <AppLayout title="Comunidades y Sectores">
        <div class="space-y-6 max-w-7xl mx-auto pb-10">
            <!-- Header Section (estilo Directorio de Estudiantes) -->
            <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-6 animate-fade-in-up">
                <div>
                    <h1 class="text-3xl font-extrabold text-slate-800">
                        Comunidades y <span class="gradient-text">Sectores</span>
                    </h1>
                    <p class="text-slate-400 font-bold mt-2 text-sm">
                        Administra el catálogo de sectores disponibles en el registro de estudiantes (Lagunillas - Mérida)
                    </p>
                </div>

                <div class="flex flex-col sm:flex-row gap-4 w-full lg:w-auto">
                    <button
                        v-if="$can('settings.manage')"
                        type="button"
                        @click="openCreateModal"
                        class="flex items-center justify-center gap-2 px-6 py-3.5 bg-primary-600 text-white text-[11px] font-black uppercase tracking-widest rounded-2xl shadow-lg shadow-primary-600/20 hover:bg-primary-500 hover:-translate-y-0.5 transition-all cursor-pointer"
                    >
                        <i class="fas fa-plus"></i>
                        Nueva Comunidad
                    </button>
                </div>
            </div>

            <!-- Toolbar Mobile-First (Buscador, Filtros rápidos y Ordenamiento) -->
            <div class="glass-card rounded-3xl p-4 sm:p-5 shadow-xl animate-fade-in-up flex flex-col gap-4" style="animation-delay: 50ms">
                <!-- Fila 1: Buscador y Botón de Filtros / Orden -->
                <div class="flex items-center gap-2.5 sm:gap-3 w-full">
                    <div class="relative flex-1">
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Buscar comunidad o sector..."
                            class="w-full pl-10 pr-9 sm:pl-11 sm:pr-10 py-3 bg-white border-2 border-slate-100 rounded-2xl text-slate-700 text-sm font-bold focus:border-primary-400 focus:ring-0 outline-none transition-all shadow-sm"
                        >
                        <i class="fas fa-search absolute left-3.5 sm:left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <button
                            v-if="search"
                            type="button"
                            @click="search = ''; doSearch()"
                            class="absolute right-3 top-1/2 -translate-y-1/2 w-6 h-6 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-400 hover:text-slate-600 flex items-center justify-center text-xs transition-colors"
                            title="Limpiar búsqueda"
                        >
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <button
                        type="button"
                        @click="showFilters = !showFilters"
                        :class="showFilters || activeFiltersCount > 0
                            ? 'bg-primary-50 text-primary-600 border-primary-200 shadow-sm ring-2 ring-primary-100'
                            : 'bg-white text-slate-600 border-slate-100 hover:bg-slate-50'"
                        class="px-3.5 sm:px-4 py-3 rounded-2xl border-2 font-bold text-xs flex items-center gap-2 transition-all shrink-0 cursor-pointer shadow-sm active:scale-95"
                    >
                        <i class="fas fa-sliders-h text-sm"></i>
                        <span class="hidden sm:inline">Organizar</span>
                        <span
                            v-if="activeFiltersCount > 0"
                            class="w-5 h-5 rounded-full bg-primary-600 text-white text-[10px] font-black flex items-center justify-center ml-0.5"
                        >
                            {{ activeFiltersCount }}
                        </span>
                        <i class="fas fa-chevron-down text-[10px] transition-transform duration-200" :class="showFilters ? 'rotate-180' : ''"></i>
                    </button>
                </div>

                <!-- Fila 2: Selector Rápido de Estado -->
                <div class="flex items-center gap-2 overflow-x-auto custom-scrollbar pb-1 -mx-1 px-1 touch-pan-x">
                    <button
                        type="button"
                        @click="setStatusFilter('all')"
                        :class="statusFilter === 'all'
                            ? 'bg-primary-600 text-white shadow-md shadow-primary-600/25'
                            : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                        class="px-3.5 py-2 rounded-xl text-xs font-black uppercase tracking-wider whitespace-nowrap transition-all shrink-0 active:scale-95"
                    >
                        Todas ({{ stats.total }})
                    </button>
                    <button
                        type="button"
                        @click="setStatusFilter('active')"
                        :class="statusFilter === 'active'
                            ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/25'
                            : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                        class="px-3.5 py-2 rounded-xl text-xs font-black uppercase tracking-wider whitespace-nowrap transition-all shrink-0 active:scale-95"
                    >
                        Activas ({{ stats.active }})
                    </button>
                    <button
                        type="button"
                        @click="setStatusFilter('inactive')"
                        :class="statusFilter === 'inactive'
                            ? 'bg-amber-500 text-white shadow-md shadow-amber-500/25'
                            : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                        class="px-3.5 py-2 rounded-xl text-xs font-black uppercase tracking-wider whitespace-nowrap transition-all shrink-0 active:scale-95"
                    >
                        Inactivas ({{ stats.inactive }})
                    </button>
                </div>

                <!-- Panel Desplegable de Organización y Ordenamiento -->
                <div
                    v-show="showFilters"
                    class="p-4 bg-slate-50/90 rounded-2xl border border-slate-200/80 animate-fade-in-up"
                >
                    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-3 sm:gap-4">
                        <!-- Criterio de Ordenamiento -->
                        <div class="flex-1 max-w-md">
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">
                                Organizar por
                            </label>
                            <div class="flex items-center gap-2">
                                <select
                                    v-model="sortCol"
                                    class="flex-1 bg-white border-2 border-slate-200/80 rounded-xl px-3 py-2 text-xs font-bold text-slate-700 focus:border-primary-400 outline-none shadow-sm cursor-pointer"
                                >
                                    <option value="name">Nombre (Alfabético)</option>
                                    <option value="students_count">Cantidad de Estudiantes</option>
                                    <option value="is_active">Estado (Activa / Inactiva)</option>
                                    <option value="created_at">Fecha de Registro</option>
                                </select>
                                <button
                                    type="button"
                                    @click="toggleSortDirection"
                                    class="px-3 py-2 bg-white border-2 border-slate-200/80 rounded-xl text-slate-600 hover:text-primary-600 hover:border-primary-300 text-xs font-bold transition-all shadow-sm active:scale-95"
                                    :title="sortDir === 'asc' ? 'Orden Ascendente' : 'Orden Descendente'"
                                >
                                    <i :class="sortDir === 'asc' ? 'fas fa-sort-amount-down-alt' : 'fas fa-sort-amount-up-alt'"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Botón Limpiar -->
                        <button
                            v-if="activeFiltersCount > 0 || statusFilter !== 'all' || search"
                            type="button"
                            @click="resetFilters"
                            class="w-full sm:w-auto px-4 py-2 bg-slate-200/80 hover:bg-slate-300 text-slate-600 text-xs font-black uppercase tracking-wider rounded-xl transition-all flex items-center justify-center gap-1.5 active:scale-95"
                        >
                            <i class="fas fa-undo-alt text-[10px]"></i>
                            Restablecer
                        </button>
                    </div>
                </div>

                <!-- Fila Inferior: Conteo -->
                <div class="flex items-center justify-between text-[11px] font-black text-slate-400 uppercase tracking-widest pt-2 border-t border-slate-100">
                    <span>Mostrando {{ communities.data.length }} de {{ communities.total }} comunidades</span>
                    <span class="text-primary-600 font-extrabold hidden sm:flex items-center gap-1.5">
                        <i class="fas fa-map-marker-alt"></i> Lagunillas - Mérida
                    </span>
                </div>
            </div>

            <!-- Listado de Tarjetas -->
            <div class="space-y-3 animate-fade-in-up" style="animation-delay: 100ms">
                <div v-if="communities.data.length === 0" class="glass-card rounded-3xl p-16 text-center">
                    <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 text-slate-200">
                        <i class="fas fa-map-marker-alt text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-400">No se encontraron comunidades</h3>
                    <p class="text-slate-300 text-sm mt-1">Intenta con otro término de búsqueda o filtro.</p>
                </div>

                <div
                    v-for="c in communities.data"
                    :key="c.id"
                    class="bg-white p-4 sm:p-5 rounded-2xl shadow-sm border-2 border-slate-100 transition-all flex flex-col md:flex-row md:items-center justify-between gap-4 group hover:border-slate-300"
                    :class="!c.is_active ? 'opacity-85' : ''"
                >
                    <!-- Info Principal -->
                    <div class="flex items-center gap-4 flex-1 min-w-0">
                        <div
                            class="w-12 h-12 rounded-full flex items-center justify-center font-black text-sm shrink-0 shadow-sm border"
                            :class="c.is_active
                                ? 'bg-primary-50 text-primary-600 border-primary-100'
                                : 'bg-slate-100 text-slate-400 border-slate-200'"
                        >
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div class="min-w-0">
                            <h3
                                class="text-base font-black truncate"
                                :class="c.is_active ? 'text-slate-800' : 'text-slate-400 line-through'"
                            >
                                {{ c.name }}
                            </h3>
                            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 mt-1 text-xs font-bold text-slate-500">
                                <span class="flex items-center gap-1.5">
                                    <i class="fas fa-user-graduate opacity-50"></i>
                                    {{ c.students_count ?? 0 }} {{ (c.students_count ?? 0) === 1 ? 'estudiante' : 'estudiantes' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Estatus y Acciones -->
                    <div class="flex items-center justify-between md:justify-end gap-3 w-full md:w-auto">
                        <div class="shrink-0 flex justify-center">
                            <span
                                v-if="c.is_active"
                                class="px-2.5 py-1 text-[10px] font-black uppercase tracking-widest rounded-lg border shadow-sm bg-emerald-50 text-emerald-600 border-emerald-100"
                            >
                                Activa
                            </span>
                            <span
                                v-else
                                class="px-2.5 py-1 text-[10px] font-black uppercase tracking-widest rounded-lg border shadow-sm bg-amber-50 text-amber-700 border-amber-100"
                            >
                                Inactiva
                            </span>
                        </div>

                        <div v-if="$can('settings.manage')" class="flex items-center gap-2 bg-slate-50 p-1.5 rounded-xl border border-slate-100">
                            <button
                                type="button"
                                @click="openEditModal(c)"
                                class="w-9 h-9 rounded-lg bg-white text-slate-500 hover:text-primary-600 hover:bg-primary-50 hover:border-primary-200 hover:shadow-sm transition-all border border-slate-200 flex items-center justify-center"
                                title="Editar nombre"
                            >
                                <i class="fas fa-pen text-[11px]"></i>
                            </button>
                            <button
                                type="button"
                                @click="toggleCommunity(c)"
                                class="w-9 h-9 rounded-lg bg-white transition-all border border-slate-200 flex items-center justify-center hover:shadow-sm"
                                :class="c.is_active
                                    ? 'text-amber-600 hover:bg-amber-50 hover:border-amber-200'
                                    : 'text-emerald-600 hover:bg-emerald-50 hover:border-emerald-200'"
                                :title="c.is_active ? 'Desactivar comunidad' : 'Activar comunidad'"
                            >
                                <i class="fas text-[11px]" :class="c.is_active ? 'fa-ban' : 'fa-check'"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Paginación -->
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4 px-4 mt-6 animate-fade-in-up" style="animation-delay: 150ms">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">
                    Mostrando {{ communities.data.length }} de {{ communities.total }} registros
                </span>

                <div class="flex flex-wrap justify-center items-center gap-1.5 w-full sm:w-auto" v-if="communities.links && communities.links.length > 3">
                    <Link
                        v-for="(link, i) in communities.links"
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

        <!-- Modal Crear / Editar Comunidad -->
        <Modal :show="showModal" @close="closeModal" maxWidth="md">
            <div class="p-6 sm:p-8 space-y-6">
                <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                    <div>
                        <h3 class="text-lg font-black text-slate-800">
                            {{ editingCommunity ? 'Editar Comunidad / Sector' : 'Nueva Comunidad / Sector' }}
                        </h3>
                        <p class="text-xs text-slate-400 font-bold mt-0.5">
                            Lagunillas - Municipio Sucre, Mérida
                        </p>
                    </div>
                    <button
                        type="button"
                        @click="closeModal"
                        class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 hover:bg-slate-200 flex items-center justify-center transition-colors"
                    >
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </div>

                <form @submit.prevent="submit" class="space-y-5">
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-black text-slate-500 uppercase tracking-wider ml-1">
                            Nombre de la Comunidad o Sector <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="form.name"
                            type="text"
                            maxlength="120"
                            placeholder="Ej: Sector El Molino / La Vega"
                            class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl px-4 py-3 text-sm font-bold text-slate-800 focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all"
                            required
                            autofocus
                        />
                        <p v-if="form.errors.name" class="text-xs text-red-500 font-bold ml-1">
                            {{ form.errors.name }}
                        </p>
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <button
                            type="button"
                            @click="closeModal"
                            class="px-5 py-2.5 rounded-xl bg-slate-100 text-slate-600 text-xs font-black uppercase tracking-wider hover:bg-slate-200 transition-colors"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing || !form.name.trim()"
                            class="px-6 py-2.5 rounded-xl bg-primary-600 text-white text-xs font-black uppercase tracking-wider hover:bg-primary-500 disabled:opacity-50 shadow-lg shadow-primary-600/20 transition-all flex items-center gap-2"
                        >
                            <i class="fas" :class="form.processing ? 'fa-spinner fa-spin' : 'fa-save'"></i>
                            {{ editingCommunity ? 'Guardar Cambios' : 'Registrar' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AppLayout>
</template>
