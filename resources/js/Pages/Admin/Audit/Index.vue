<template>
  <AppLayout title="Auditoría y Trazabilidad">
    <div class="space-y-6 max-w-12xl mx-auto pb-10">
      <!-- Header Section -->
      <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-6 animate-fade-in-up">
        <div>
          <h1 class="text-3xl font-extrabold text-slate-800">
            <i class="fas fa-search text-primary-500 mr-2"></i>
            Auditoría y <span class="gradient-text">Trazabilidad</span>
          </h1>
          <p class="text-slate-400 font-bold mt-2 text-sm">Registro completo de acciones críticas y cambios en el sistema</p>
        </div>
      </div>

      <!-- Toolbar (Filters and Tabs) -->
      <div class="glass-card rounded-3xl p-5 shadow-xl animate-fade-in-up flex flex-col gap-5" style="animation-delay: 50ms">
        
        <div class="flex flex-col xl:flex-row gap-4 justify-between w-full">
          <!-- Tabs -->
          <div class="flex flex-wrap gap-2 w-full xl:w-auto overflow-x-auto custom-scrollbar pb-2 xl:pb-0 shrink-0">
            <button 
              @click="setTab('log')" 
              :class="activeTab === 'log' ? 'bg-primary-600 text-white shadow-md shadow-primary-500/20' : 'bg-slate-100 text-slate-500 hover:bg-slate-200'" 
              class="px-5 py-3 rounded-2xl text-xs font-black uppercase tracking-widest transition-all whitespace-nowrap flex items-center gap-2"
            >
              <i class="fas fa-list"></i>
              Log de Acciones
            </button>
            <button 
              @click="setTab('changes')" 
              :class="activeTab === 'changes' ? 'bg-primary-600 text-white shadow-md shadow-primary-500/20' : 'bg-slate-100 text-slate-500 hover:bg-slate-200'" 
              class="px-5 py-3 rounded-2xl text-xs font-black uppercase tracking-widest transition-all whitespace-nowrap flex items-center gap-2"
            >
              <i class="fas fa-history"></i>
              Historial de Cambios
            </button>
          </div>

          <!-- Filtros -->
          <div class="flex flex-wrap lg:flex-nowrap gap-3 items-center w-full justify-end">
            <div class="relative w-full lg:w-48 shrink-0">
              <input
                v-model="form.search"
                type="text"
                placeholder="Buscar descripción..."
                class="w-full pl-10 pr-4 py-2.5 bg-white border-2 border-slate-100 rounded-2xl text-slate-700 text-sm font-bold focus:border-primary-400 focus:ring-0 outline-none transition-all shadow-sm"
                @keyup.enter="applyFilters"
              >
              <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
            </div>
            
            <div class="w-full lg:w-40 shrink-0">
              <select v-model="form.module" @change="applyFilters" class="w-full py-2.5 px-4 bg-white border-2 border-slate-100 rounded-2xl text-slate-700 text-sm font-bold focus:border-primary-400 focus:ring-0 outline-none transition-all shadow-sm">
                <option value="">Todos los módulos</option>
                <option v-for="m in modules" :key="m.value" :value="m.value">{{ m.label }}</option>
              </select>
            </div>

            <div class="w-full lg:w-40 shrink-0">
              <select v-model="form.action" @change="applyFilters" class="w-full py-2.5 px-4 bg-white border-2 border-slate-100 rounded-2xl text-slate-700 text-sm font-bold focus:border-primary-400 focus:ring-0 outline-none transition-all shadow-sm">
                <option value="">Todas las acciones</option>
                <option v-for="a in actions" :key="a.value" :value="a.value">{{ a.label }}</option>
              </select>
            </div>

            <div class="flex gap-2 w-full lg:w-auto">
              <input
                v-model="form.from"
                type="date"
                class="w-full lg:w-36 py-2.5 px-3 bg-white border-2 border-slate-100 rounded-2xl text-slate-700 text-sm font-bold focus:border-primary-400 focus:ring-0 outline-none transition-all shadow-sm"
                @change="applyFilters"
                title="Desde"
              >
              <input
                v-model="form.to"
                type="date"
                class="w-full lg:w-36 py-2.5 px-3 bg-white border-2 border-slate-100 rounded-2xl text-slate-700 text-sm font-bold focus:border-primary-400 focus:ring-0 outline-none transition-all shadow-sm"
                @change="applyFilters"
                title="Hasta"
              >
            </div>
            
            <button @click="clearFilters" class="px-4 py-2.5 rounded-2xl bg-slate-100 text-slate-500 hover:bg-slate-200 text-xs font-black uppercase tracking-widest transition-all whitespace-nowrap shrink-0" title="Limpiar Filtros">
              <i class="fas fa-times"></i>
            </button>
          </div>
        </div>

        <hr class="border-slate-100">

        <!-- Conteo -->
        <div class="flex justify-between items-center px-2">
            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">
                {{ logs.total }} <span class="font-medium lowercase">{{ activeTab === 'changes' ? 'cambios registrados' : 'acciones registradas' }}</span>
            </p>
        </div>
      </div>

      <!-- Table Section -->
      <div class="glass-card rounded-3xl shadow-xl overflow-hidden animate-fade-in-up" style="animation-delay: 100ms">
        <div class="overflow-x-auto custom-scrollbar">
          
          <div v-if="logs.data.length === 0" class="flex flex-col items-center justify-center py-16 px-4">
              <div class="w-24 h-24 mb-6 bg-slate-50 rounded-full flex items-center justify-center">
                  <i class="fas fa-inbox text-4xl text-slate-300"></i>
              </div>
              <h3 class="text-lg font-bold text-slate-700 mb-1">No hay registros</h3>
              <p class="text-slate-400 text-sm font-medium text-center max-w-sm">No se encontraron acciones o cambios que coincidan con los filtros aplicados.</p>
          </div>

          <table v-else class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-slate-50 border-b-2 border-slate-100">
                <th class="px-5 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest whitespace-nowrap">Fecha / Hora</th>
                <th class="px-5 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest whitespace-nowrap">Usuario</th>
                <th class="px-5 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest whitespace-nowrap">Módulo</th>
                <th v-if="activeTab === 'log'" class="px-5 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest whitespace-nowrap">Acción</th>
                <th class="px-5 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Descripción</th>
                <th v-if="activeTab === 'log'" class="px-5 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest whitespace-nowrap">IP</th>
                <th v-if="activeTab === 'changes'" class="px-5 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest whitespace-nowrap text-center">Detalles</th>
              </tr>
            </thead>
            <tbody>
              <template v-for="entry in logs.data" :key="entry.id">
                <tr 
                  class="border-b border-slate-100 hover:bg-slate-50/50 transition-colors"
                  :class="{ 'bg-primary-50/30': expandedId === entry.id, 'cursor-pointer': activeTab === 'changes' }"
                  @click="activeTab === 'changes' ? toggleExpand(entry.id) : null"
                >
                  <td class="px-5 py-4 whitespace-nowrap">
                    <span class="block text-sm font-bold text-slate-700">{{ formatDate(entry.created_at) }}</span>
                    <span class="block text-[11px] font-bold text-slate-400">{{ formatTime(entry.created_at) }}</span>
                  </td>
                  <td class="px-5 py-4 whitespace-nowrap">
                    <div class="flex items-center gap-3">
                      <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-primary-100 to-primary-200 flex items-center justify-center text-primary-700 font-bold text-xs">
                          {{ entry.user?.name?.charAt(0) ?? 'S' }}
                      </div>
                      <span class="text-sm font-bold text-slate-700">{{ entry.user?.name ?? 'Sistema' }}</span>
                    </div>
                  </td>
                  <td class="px-5 py-4 whitespace-nowrap">
                    <span class="px-3 py-1 bg-slate-100 text-slate-600 rounded-lg text-[10px] font-bold uppercase tracking-wider">{{ moduleLabel(entry.module) }}</span>
                  </td>
                  <td v-if="activeTab === 'log'" class="px-5 py-4 whitespace-nowrap">
                    <span class="px-3 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider" :class="actionClass(entry.action)">
                      {{ actionLabel(entry.action) }}
                    </span>
                  </td>
                  <td class="px-5 py-4">
                    <p class="text-sm font-medium text-slate-600 max-w-md">{{ entry.description }}</p>
                  </td>
                  <td v-if="activeTab === 'log'" class="px-5 py-4 whitespace-nowrap">
                    <span class="text-xs font-mono font-medium text-slate-400">{{ entry.ip_address ?? '—' }}</span>
                  </td>
                  <td v-if="activeTab === 'changes'" class="px-5 py-4 whitespace-nowrap text-center">
                    <button class="w-8 h-8 rounded-lg bg-slate-100 text-slate-400 hover:bg-primary-100 hover:text-primary-600 transition-colors">
                      <i class="fas transition-transform duration-200" :class="expandedId === entry.id ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                    </button>
                  </td>
                </tr>
                
                <!-- Expanded Properties Row (only for changes tab) -->
                <tr v-if="activeTab === 'changes' && expandedId === entry.id" class="bg-slate-50 border-b border-slate-100">
                  <td colspan="5" class="px-8 py-6">
                    <div class="bg-white border border-slate-100 rounded-2xl p-5 shadow-sm">
                      <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-4">Detalle de Cambios</h4>
                      <div class="grid gap-3">
                        <div v-for="(oldVal, field) in (entry.properties?.old ?? {})" :key="field" class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4 p-3 rounded-xl bg-slate-50 border border-slate-100">
                          <div class="sm:w-1/4">
                            <span class="text-xs font-bold text-slate-600 font-mono">{{ field }}</span>
                          </div>
                          <div class="flex-1 flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4">
                            <div class="flex-1 px-4 py-2 rounded-lg bg-red-50 text-red-700 text-sm font-medium border border-red-100 break-all">
                              {{ oldVal ?? '—' }}
                            </div>
                            <div class="text-slate-300 hidden sm:block">
                              <i class="fas fa-arrow-right"></i>
                            </div>
                            <div class="text-slate-300 sm:hidden flex justify-center">
                              <i class="fas fa-arrow-down"></i>
                            </div>
                            <div class="flex-1 px-4 py-2 rounded-lg bg-emerald-50 text-emerald-700 text-sm font-medium border border-emerald-100 break-all">
                              {{ entry.properties?.new?.[field] ?? '—' }}
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="logs.last_page > 1" class="border-t border-slate-100 p-5 bg-slate-50/50 flex justify-center">
          <div class="flex items-center gap-2">
            <button
              v-for="page in visiblePages"
              :key="page"
              @click="goToPage(page)"
              :disabled="page === '...'"
              class="w-10 h-10 rounded-xl font-bold text-sm flex items-center justify-center transition-all"
              :class="[
                page === logs.current_page 
                  ? 'bg-primary-600 text-white shadow-md shadow-primary-600/20' 
                  : page === '...'
                    ? 'text-slate-400 cursor-default'
                    : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-100'
              ]"
            >
              {{ page }}
            </button>
          </div>
        </div>
      </div>

    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'

const props = defineProps({
  logs:    Object,
  modules: Array,
  actions: Array,
  filters: Object,
})

const activeTab   = ref(props.filters?.tab ?? 'log')
const expandedId  = ref(null)

const form = ref({
  search: props.filters?.search ?? '',
  module: props.filters?.module ?? '',
  action: props.filters?.action ?? '',
  from:   props.filters?.from   ?? '',
  to:     props.filters?.to     ?? '',
})

// ── Navigation ────────────────────────────────────────────────────────────────

function applyFilters() {
  router.get('/admin/audit', { ...form.value, tab: activeTab.value }, { preserveState: true })
}

function clearFilters() {
  form.value = { search: '', module: '', action: '', from: '', to: '' }
  applyFilters()
}

function setTab(tab) {
  activeTab.value = tab
  router.get('/admin/audit', { tab, ...form.value }, { preserveState: true })
}

function goToPage(page) {
  if (page === '...') return
  router.get('/admin/audit', { ...form.value, tab: activeTab.value, page }, { preserveState: true })
}

// ── Helpers ───────────────────────────────────────────────────────────────────

function toggleExpand(id) {
  expandedId.value = expandedId.value === id ? null : id
}

function formatDate(dt) {
  if (!dt) return '—'
  return new Date(dt).toLocaleDateString('es-VE', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

function formatTime(dt) {
  if (!dt) return ''
  return new Date(dt).toLocaleTimeString('es-VE', { hour: '2-digit', minute: '2-digit' })
}

function moduleLabel(mod) {
  const map = {
    estudiantes: 'Estudiantes', inscripciones: 'Inscripciones', usuarios: 'Usuarios',
    roles: 'Roles', años_escolares: 'Años Escs.', secciones: 'Secciones',
    materias: 'Materias', carga_academica: 'Carga Acad.', consejo: 'Consejo',
    revisiones: 'Revisiones', configuracion: 'Config.', importacion: 'Importación',
  }
  return map[mod] ?? mod
}

function actionLabel(action) {
  const map = {
    created: 'Creado', updated: 'Editado', deleted: 'Eliminado',
    imported: 'Importado', promoted: 'Promoción',
    council_updated: 'Aj. Consejo', revision_updated: 'Revisión',
  }
  return map[action] ?? action
}

function actionClass(action) {
  const map = {
    created: 'bg-emerald-100 text-emerald-700', 
    updated: 'bg-blue-100 text-blue-700', 
    deleted: 'bg-red-100 text-red-700',
    imported: 'bg-primary-100 text-primary-700', 
    promoted: 'bg-orange-100 text-orange-700',
    council_updated: 'bg-amber-100 text-amber-700', 
    revision_updated: 'bg-teal-100 text-teal-700',
  }
  return map[action] ?? 'bg-slate-100 text-slate-700'
}

const visiblePages = computed(() => {
  const total   = props.logs.last_page
  const current = props.logs.current_page
  if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1)
  const pages = []
  if (current > 3) pages.push(1, '...')
  for (let i = Math.max(1, current - 2); i <= Math.min(total, current + 2); i++) pages.push(i)
  if (current < total - 2) pages.push('...', total)
  return pages
})
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    height: 6px;
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(99, 102, 241, 0.2);
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(99, 102, 241, 0.4);
}
</style>
