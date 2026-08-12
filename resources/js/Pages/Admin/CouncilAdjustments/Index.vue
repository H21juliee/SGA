<script setup>
import { ref, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'

const props = defineProps({
    activeYear: Object,
    sections:   Array,
    subjects:   Array,
    lapses:     Array,
    rows:       Array,
    filters:    Object,
})

const gradeLevelId = ref(props.filters.grade_level_id ? (parseInt(props.filters.grade_level_id) || '') : '')
const sectionId = ref(props.filters.section_id ? (parseInt(props.filters.section_id) || '') : '')
const subjectId = ref(props.filters.subject_id ? (parseInt(props.filters.subject_id) || '') : '')
const lapseId   = ref(props.filters.lapse_id   ? (parseInt(props.filters.lapse_id) || '') : '')

// Si hay una sección pero no hay grade_level_id en la URL, inicializarlo
if (!gradeLevelId.value && sectionId.value) {
    const sec = props.sections.find(s => s.id === sectionId.value)
    if (sec) gradeLevelId.value = sec.grade_level_id
}

const gradeLevels = computed(() => {
    const unique = []
    const ids = new Set()
    props.sections.forEach(s => {
        if (s.grade_level && !ids.has(s.grade_level.id)) {
            unique.push(s.grade_level)
            ids.add(s.grade_level.id)
        }
    })
    return unique.sort((a, b) => a.level - b.level)
})

const filteredSections = computed(() => {
    if (!gradeLevelId.value) return []
    return props.sections.filter(s => s.grade_level_id === gradeLevelId.value)
})

// Ajustes locales (por grade_id)
const adjustments = ref({})
const isLoading = ref(false)

// Inicializar desde los datos del servidor
const initAdjustments = () => {
    adjustments.value = {}
    props.rows.forEach(row => {
        adjustments.value[row.grade_id] = row.council_adjustment
    })
    isLoading.value = false
}
initAdjustments()

const definitiveOf = (row) => {
    const adj = adjustments.value[row.grade_id] ?? 0
    return Math.min(20, Math.max(1, row.score + adj))
}

watch(gradeLevelId, (newId, oldId) => {
    if (oldId && newId !== oldId) {
        sectionId.value = ''
        subjectId.value = ''
    }
    if (newId) {
        router.get('/admin/council-adjustments', { grade_level_id: newId }, { preserveState: true, only: ['subjects'] })
    }
})

watch([sectionId, subjectId, lapseId], ([sec, sub, lap]) => {
    if (sec && sub && lap) {
        isLoading.value = true
        router.get('/admin/council-adjustments', {
            grade_level_id: gradeLevelId.value,
            section_id: sec,
            subject_id: sub,
            lapse_id: lap
        }, { preserveState: true, onSuccess: initAdjustments })
    }
})

function saveAll() {
    const changes = props.rows.map(row => ({
        grade_id:           row.grade_id,
        council_adjustment: adjustments.value[row.grade_id] ?? 0,
    }))

    router.post('/admin/council-adjustments/batch', { changes }, { preserveScroll: true })
}

const hasFilters = computed(() => sectionId.value && subjectId.value && lapseId.value)
</script>

<template>
    <AppLayout title="Ajuste de Consejo">
        <div class="space-y-8 max-w-12xl mx-auto">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row gap-6 items-start sm:items-end justify-between animate-fade-in-up">
                <div>
                    <h1 class="text-3xl font-extrabold text-slate-800">
                        Ajuste de <span class="gradient-text">Consejo</span>
                    </h1>
                    <p class="text-slate-400 font-medium mt-2">Aplicar ajuste del consejo docente a las notas definitivas de un lapso</p>
                </div>
            </div>

            <!-- Filtros -->
            <div class="glass-card rounded-3xl p-6 shadow-xl animate-fade-in-up flex flex-wrap items-end gap-6" style="animation-delay: 50ms">
                <!-- Año -->
                <div class="flex flex-col gap-2 min-w-[160px]">
                    <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Año</label>
                    <div class="relative">
                        <select v-model="gradeLevelId"
                            class="w-full bg-white border-2 border-slate-100 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:ring-0 outline-none transition-all appearance-none shadow-sm">
                            <option value="">Seleccionar año...</option>
                            <option v-for="g in gradeLevels" :key="g.id" :value="g.id">
                                {{ g.name }}
                            </option>
                        </select>
                        <i class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 pointer-events-none"></i>
                    </div>
                </div>

                <!-- Sección -->
                <div class="flex flex-col gap-2 min-w-[160px]">
                    <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Sección</label>
                    <div class="relative">
                        <select v-model="sectionId" :disabled="!gradeLevelId"
                            class="w-full bg-white border-2 border-slate-100 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:ring-0 outline-none transition-all appearance-none shadow-sm disabled:opacity-50">
                            <option value="">Seleccionar sección...</option>
                            <option v-for="s in filteredSections" :key="s.id" :value="s.id">
                                Sección {{ s.name }}
                            </option>
                        </select>
                        <i class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 pointer-events-none"></i>
                    </div>
                </div>

                <!-- Materia -->
                <div class="flex flex-col gap-2 min-w-[200px]" v-if="subjects.length > 0">
                    <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Materia</label>
                    <div class="relative">
                        <select v-model="subjectId"
                            class="w-full bg-white border-2 border-slate-100 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:ring-0 outline-none transition-all appearance-none shadow-sm">
                            <option value="">Seleccionar materia...</option>
                            <option v-for="s in subjects" :key="s.id" :value="s.id">{{ s.name }}</option>
                        </select>
                        <i class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 pointer-events-none"></i>
                    </div>
                </div>

                <!-- Lapso -->
                <div class="flex flex-col gap-2 min-w-[160px]" v-if="lapses.length > 0">
                    <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Lapso</label>
                    <div class="relative">
                        <select v-model="lapseId"
                            class="w-full bg-white border-2 border-slate-100 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:ring-0 outline-none transition-all appearance-none shadow-sm">
                            <option value="">Seleccionar lapso...</option>
                            <option v-for="l in lapses" :key="l.id" :value="l.id">{{ l.name }}</option>
                        </select>
                        <i class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 pointer-events-none"></i>
                    </div>
                </div>
            </div>

            <!-- Grid -->
            <div v-if="isLoading" class="glass-card rounded-3xl p-16 text-center animate-fade-in-up">
                <div class="w-16 h-16 bg-primary-50 rounded-full flex items-center justify-center mx-auto mb-4 text-primary-500 shadow-inner">
                    <i class="fas fa-spinner fa-spin text-2xl"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-700">Cargando información...</h3>
                <p class="text-slate-500 text-sm mt-1">Por favor espera mientras obtenemos los datos.</p>
            </div>

            <div v-else-if="rows.length > 0" class="glass-card rounded-3xl overflow-hidden shadow-2xl animate-fade-in-up" style="animation-delay: 100ms">
                <!-- Toolbar -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                    <div class="text-sm text-slate-500 font-medium flex items-center gap-2">
                        <i class="fas fa-table"></i>
                        {{ rows.length }} estudiante{{ rows.length !== 1 ? 's' : '' }}
                    </div>
                    <button @click="saveAll"
                        class="px-5 py-2 text-xs font-bold uppercase tracking-widest rounded-xl bg-slate-900 text-white shadow-md hover:bg-slate-800 hover:-translate-y-0.5 transition-all flex items-center gap-2">
                        <i class="fas fa-save"></i> Guardar Todo
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b-2 border-slate-100 bg-slate-50/80">
                                <th class="px-4 py-4 text-left text-xs font-black text-slate-400 uppercase tracking-widest w-8">#</th>
                                <th class="px-4 py-4 text-left text-xs font-black text-slate-400 uppercase tracking-widest">Estudiante</th>
                                <th class="px-4 py-4 text-center text-xs font-black text-slate-400 uppercase tracking-widest w-32">Nota Docente</th>
                                <th class="px-4 py-4 text-center text-xs font-black text-slate-400 uppercase tracking-widest w-36">Ajuste Consejo</th>
                                <th class="px-4 py-4 text-center text-xs font-black text-slate-400 uppercase tracking-widest w-32">Definitiva</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <tr v-for="(row, idx) in rows" :key="row.grade_id" class="hover:bg-primary-50/20 transition-colors">
                                <td class="px-4 py-3 text-xs text-slate-400 font-mono">{{ idx + 1 }}</td>
                                <td class="px-4 py-3 font-black text-slate-700">{{ row.student_name }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-slate-100 text-slate-700 font-black text-sm">
                                        {{ row.score }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <input
                                        v-model.number="adjustments[row.grade_id]"
                                        type="number"
                                        min="-5" max="5"
                                        class="w-20 text-center bg-white border-2 border-slate-200 rounded-xl px-2 py-2 text-sm font-black text-slate-700 focus:border-primary-400 focus:ring-0 outline-none transition-all shadow-sm"
                                    >
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl font-black text-sm shadow-sm"
                                        :class="definitiveOf(row) < 10
                                            ? 'bg-red-50 text-red-600 border border-red-100'
                                            : 'bg-emerald-50 text-emerald-700 border border-emerald-100'">
                                        {{ definitiveOf(row) }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-else-if="hasFilters && !isLoading" class="glass-card rounded-3xl p-16 text-center animate-fade-in-up">
                <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 text-slate-200">
                    <i class="fas fa-inbox text-2xl"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-400">Sin notas registradas</h3>
                <p class="text-slate-300 text-sm mt-1">No hay notas cargadas para esta combinación de sección, materia y lapso.</p>
            </div>
        </div>
    </AppLayout>
</template>
