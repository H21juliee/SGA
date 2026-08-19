<script setup>
import { ref, computed, watch } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'
import Swal from 'sweetalert2'

const props = defineProps({
    section: Object,
    subject: Object,
    date: String,
    lapse: Object,
    enrollments: Array,
    isLocked: Boolean,
})

const currentDate = ref(props.date)
const searchQuery = ref('')
const sortCol = ref('student_name')
const activeNoteId = ref(null)
const today = new Date().toISOString().split('T')[0]

// Initialize local state for immediate visual feedback
const localRows = ref(
    props.enrollments.map(enrollment => {
        const att = enrollment.attendances?.[0]
        return {
            enrollment_id: enrollment.id,
            student_name: `${enrollment.student.last_name}, ${enrollment.student.first_name}`,
            cedula: enrollment.student.cedula,
            status: att?.status ?? 'present',
            notes: att?.notes ?? '',
            saving: false
        }
    })
)

watch(() => props.enrollments, (newVal) => {
    // Only update if not currently typing a note or if lengths differ
    localRows.value = newVal.map(enrollment => {
        const att = enrollment.attendances?.[0]
        const existing = localRows.value.find(r => r.enrollment_id === enrollment.id)
        return {
            enrollment_id: enrollment.id,
            student_name: `${enrollment.student.last_name}, ${enrollment.student.first_name}`,
            cedula: enrollment.student.cedula,
            status: att?.status ?? 'present',
            notes: existing?.notes !== undefined && existing.notes !== (att?.notes ?? '') && activeNoteId.value === enrollment.id 
                    ? existing.notes 
                    : (att?.notes ?? ''),
            saving: false
        }
    })
}, { deep: true })

const filteredRows = computed(() => {
    let result = [...localRows.value]
    
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase()
        result = result.filter(row => {
            return row.student_name.toLowerCase().includes(query) || 
                   (row.cedula && row.cedula.toLowerCase().includes(query))
        })
    }
    
    if (sortCol.value === 'cedula') {
        result.sort((a, b) => (a.cedula || '').localeCompare(b.cedula || ''))
    } else {
        result.sort((a, b) => a.student_name.localeCompare(b.student_name))
    }
    
    return result
})

const stats = computed(() => {
    return {
        present: localRows.value.filter(r => r.status === 'present').length,
        absent: localRows.value.filter(r => r.status === 'absent').length,
        late: localRows.value.filter(r => r.status === 'late').length,
        excused: localRows.value.filter(r => r.status === 'excused').length,
        total: localRows.value.length
    }
})

function updateDate() {
    router.get(`/attendance/${props.section.id}`, { 
        date: currentDate.value,
        subject_id: props.subject.id 
    }, { preserveState: true })
}

function setStatus(row, newStatus) {
    if (props.isLocked || row.status === newStatus) return
    row.status = newStatus
    saveRow(row)
}

function updateNotes(row) {
    if (props.isLocked) return
    saveRow(row)
}

function saveNote(row) {
    if (props.isLocked) return
    saveRow(row)
    activeNoteId.value = null
}

function saveRow(row) {
    row.saving = true
    router.patch('/attendance', {
        enrollment_id: row.enrollment_id,
        subject_id: props.subject.id,
        date: props.date,
        status: row.status,
        notes: row.notes,
        lapse_id: props.lapse?.id,
    }, { 
        preserveScroll: true, 
        preserveState: true,
        onFinish: () => { row.saving = false }
    })
}

function markAllPresent() {
    if (props.isLocked) return
    
    localRows.value.forEach(row => {
        if (row.status !== 'present') {
            row.status = 'present'
        }
    })

    const changes = localRows.value.map(row => ({
        enrollment_id: row.enrollment_id,
        subject_id: props.subject.id,
        date: props.date,
        status: row.status,
        notes: row.notes,
        lapse_id: props.lapse?.id,
    }))

    router.post('/attendance/batch', { changes }, { preserveScroll: true, preserveState: true })
}

function finalizeAttendance() {
    Swal.fire({
        title: '¿Finalizar Asistencia?',
        text: 'Una vez finalizada, no se podrán realizar más cambios para este día.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, finalizar',
        cancelButtonText: 'Cancelar',
        buttonsStyling: false,
        customClass: {
            popup: 'rounded-3xl border-2 border-slate-100 shadow-2xl',
            title: 'text-2xl font-black text-slate-800',
            htmlContainer: 'text-slate-500 font-medium',
            confirmButton: 'px-6 py-3 bg-emerald-600 text-white text-[11px] font-black uppercase tracking-widest rounded-2xl shadow-lg shadow-emerald-600/20 hover:bg-emerald-500 transition-all mx-2',
            cancelButton: 'px-6 py-3 bg-slate-500 text-white text-[11px] font-black uppercase tracking-widest rounded-2xl shadow-lg shadow-slate-500/20 hover:bg-slate-400 transition-all mx-2'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            router.post('/attendance/lock', {
                subject_id: props.subject.id,
                section_id: props.section.id,
                date: props.date,
            })
        }
    })
}

function toggleNote(rowId) {
    if (activeNoteId.value === rowId) {
        activeNoteId.value = null
    } else {
        activeNoteId.value = rowId
    }
}
</script>

<template>
    <AppLayout :title="`Asistencia — ${subject.name} (${section.name})`">
        <div class="space-y-6 max-w-7xl mx-auto pb-10">
            <!-- Header & Breadcrumbs -->
            <div class="animate-fade-in-up">
                <div class="flex items-center gap-4 mb-4">
                    <Link href="/attendance" class="px-4 py-2 rounded-xl bg-white border-2 border-slate-100 text-slate-500 font-bold text-xs uppercase tracking-wider hover:bg-slate-50 hover:text-emerald-600 hover:border-emerald-200 transition-all shadow-sm flex items-center gap-2 group">
                        <i class="fas fa-arrow-left group-hover:-translate-x-1 transition-transform"></i> Volver
                    </Link>

                    <nav class="flex items-center gap-3 text-[10px] font-black uppercase tracking-widest text-slate-400 hidden sm:flex">
                        <Link href="/attendance" class="hover:text-emerald-600 transition-colors">Asistencia</Link>
                        <i class="fas fa-chevron-right text-[8px]"></i>
                        <span class="text-slate-600">{{ section.grade_level?.name }} — {{ section.name }}</span>
                    </nav>
                </div>

                <div class="glass-card rounded-3xl p-5 sm:p-8 shadow-xl relative overflow-hidden">
                    <div class="absolute top-0 right-0 p-8 opacity-5 pointer-events-none hidden sm:block">
                        <i class="fas fa-calendar-check text-8xl text-emerald-600"></i>
                    </div>

                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 relative z-10">
                        <div>
                            <div class="flex flex-wrap items-center gap-3 mb-2">
                                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-800">{{ subject.name }}</h1>
                                <span v-if="lapse" class="px-2.5 py-1 bg-primary-50 text-primary-600 text-[10px] font-black rounded-lg uppercase tracking-wider border border-primary-100">
                                    {{ lapse.name }}
                                </span>
                                <span v-else class="px-2.5 py-1 bg-red-50 text-red-600 text-[10px] font-black rounded-lg uppercase tracking-wider border border-red-100">
                                    Sin Lapso Abierto
                                </span>
                            </div>
                            <p class="text-slate-500 font-bold uppercase tracking-widest text-xs">
                                {{ section.grade_level?.name }} — Sección {{ section.name }}
                            </p>
                        </div>
                        
                        <div class="flex flex-col sm:flex-row items-start sm:items-end gap-4 w-full lg:w-auto">
                            <div class="flex flex-col gap-2 w-full sm:w-[200px]">
                                <label class="text-[10px] sm:text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Fecha de Asistencia</label>
                                <div class="relative">
                                    <input 
                                        type="date" 
                                        v-model="currentDate" 
                                        :max="today"
                                        @change="updateDate"
                                        class="w-full bg-slate-50 border-2 border-slate-100 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-emerald-400 focus:ring-0 outline-none transition-all cursor-pointer shadow-sm"
                                    >
                                    <i class="fas fa-calendar-day absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 pointer-events-none"></i>
                                </div>
                            </div>

                            <button
                                v-if="!isLocked"
                                @click="finalizeAttendance"
                                class="w-full sm:w-auto flex items-center justify-center gap-3 px-6 py-3.5 bg-emerald-600 text-white text-[11px] font-black uppercase tracking-widest rounded-2xl shadow-lg shadow-emerald-600/20 hover:bg-emerald-500 hover:-translate-y-0.5 transition-all group"
                            >
                                <i class="fas fa-lock-open group-hover:hidden"></i>
                                <i class="fas fa-lock hidden group-hover:inline"></i>
                                Finalizar Pase
                            </button>
                            <div v-else class="w-full sm:w-auto flex items-center justify-center gap-3 px-6 py-3.5 bg-slate-100 text-slate-400 text-[11px] font-black uppercase tracking-widest rounded-2xl border border-slate-200 cursor-not-allowed">
                                <i class="fas fa-lock text-amber-500"></i>
                                {{ !lapse ? 'Bloqueado (Sin Lapso)' : 'Sesión Finalizada' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats & Tools -->
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4 animate-fade-in-up" style="animation-delay: 100ms">
                <!-- Estadísticas -->
                <div class="flex flex-wrap items-center gap-2 sm:gap-4 w-full sm:w-auto bg-white p-2 px-4 rounded-2xl border-2 border-slate-100 shadow-sm">
                    <div class="text-[10px] font-black uppercase tracking-widest text-slate-400 pr-2 border-r-2 border-slate-100">
                        Resumen
                    </div>
                    <div class="flex items-center gap-1.5 text-xs font-bold text-emerald-600">
                        <div class="w-2 h-2 rounded-full bg-emerald-500"></div> {{ stats.present }}
                    </div>
                    <div class="flex items-center gap-1.5 text-xs font-bold text-red-500">
                        <div class="w-2 h-2 rounded-full bg-red-500"></div> {{ stats.absent }}
                    </div>
                    <div class="flex items-center gap-1.5 text-xs font-bold text-amber-500">
                        <div class="w-2 h-2 rounded-full bg-amber-500"></div> {{ stats.late }}
                    </div>
                    <div class="flex items-center gap-1.5 text-xs font-bold text-sky-500">
                        <div class="w-2 h-2 rounded-full bg-sky-500"></div> {{ stats.excused }}
                    </div>
                </div>

                <!-- Buscador, Filtro y Acciones -->
                <div class="flex flex-col md:flex-row items-center gap-3 w-full md:w-auto">
                    <div class="flex items-center gap-2 w-full md:w-auto">
                        <div class="relative flex-1 md:w-56">
                            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                            <input 
                                v-model="searchQuery" 
                                type="text" 
                                placeholder="Buscar por nombre o cédula..." 
                                class="w-full bg-white border-2 border-slate-100 rounded-xl pl-9 pr-4 py-2 text-sm text-slate-700 placeholder:text-slate-400 focus:border-emerald-400 focus:ring-0 outline-none transition-all shadow-sm"
                            >
                        </div>
                        
                        <div class="relative w-36 shrink-0">
                            <select v-model="sortCol" class="w-full bg-white border-2 border-slate-100 rounded-xl px-3 py-2 text-slate-700 text-xs font-bold focus:border-emerald-400 focus:ring-0 outline-none transition-all appearance-none shadow-sm cursor-pointer">
                                <option value="student_name">Por Apellido</option>
                                <option value="cedula">Por Cédula</option>
                            </select>
                            <i class="fas fa-sort absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
                        </div>
                    </div>
                    
                    <button
                        v-if="!isLocked"
                        @click="markAllPresent"
                        class="shrink-0 w-full md:w-auto md:px-4 py-2 h-10 flex items-center justify-center gap-2 bg-slate-900 text-white rounded-xl shadow-md hover:bg-slate-800 transition-all text-xs font-bold uppercase tracking-widest"
                        title="Marcar todos como Presentes"
                    >
                        <i class="fas fa-check-double"></i>
                        <span class="inline">Todos Presentes</span>
                    </button>
                </div>
            </div>

            <!-- Lock Warning -->
            <div v-if="isLocked" class="flex items-center gap-4 p-4 bg-amber-50 border-2 border-amber-100 rounded-2xl animate-fade-in-up" style="animation-delay: 150ms">
                <div class="w-8 h-8 bg-amber-100 text-amber-600 rounded-xl flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-shield-halved"></i>
                </div>
                <p class="text-xs font-bold text-amber-800 leading-relaxed">
                    Modo Lectura. Esta sesión ya fue finalizada y está protegida.
                </p>
            </div>

            <!-- Attendance List -->
            <div class="bg-white rounded-3xl shadow-xl border-2 border-slate-100 overflow-hidden animate-fade-in-up" style="animation-delay: 200ms">
                <div v-if="filteredRows.length === 0" class="p-10 text-center text-slate-400 font-bold">
                    <i class="fas fa-search text-3xl mb-3 opacity-20"></i>
                    <p>No se encontraron estudiantes.</p>
                </div>

                <div class="divide-y-2 divide-slate-50">
                    <div 
                        v-for="(row, idx) in filteredRows" 
                        :key="row.enrollment_id"
                        class="p-4 sm:p-5 hover:bg-slate-50/50 transition-colors flex flex-col sm:flex-row sm:items-center justify-between gap-4"
                        :class="{'opacity-75 grayscale': isLocked}"
                        :style="activeNoteId === row.enrollment_id ? 'position: relative; z-index: 50;' : 'position: relative; z-index: 0;'"
                    >
                        <!-- Info Estudiante -->
                        <div class="flex items-center gap-4">
                            <div class="w-8 h-8 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center font-black text-[10px]">
                                {{ idx + 1 }}
                            </div>
                            <div>
                                <h3 class="text-sm font-black text-slate-800">{{ row.student_name }}</h3>
                                <p class="text-xs font-bold text-slate-400 mt-0.5"><i class="fas fa-id-card opacity-50 mr-1"></i> {{ row.cedula || 'Sin Cédula' }}</p>
                            </div>
                        </div>

                        <!-- Botones de Asistencia -->
                        <div class="flex flex-wrap items-center gap-2 self-start sm:self-auto ml-12 sm:ml-0">
                            <!-- Presente -->
                            <button 
                                @click="setStatus(row, 'present')"
                                :disabled="isLocked"
                                class="w-10 h-10 sm:w-12 sm:h-12 rounded-2xl flex items-center justify-center text-sm font-black transition-all transform active:scale-95 disabled:active:scale-100 disabled:cursor-not-allowed"
                                :class="row.status === 'present' ? 'bg-emerald-500 text-white shadow-lg shadow-emerald-500/30 scale-110 z-10' : 'bg-slate-100 text-slate-400 hover:bg-slate-200'"
                                title="Presente"
                            >
                                P
                            </button>
                            <!-- Ausente -->
                            <button 
                                @click="setStatus(row, 'absent')"
                                :disabled="isLocked"
                                class="w-10 h-10 sm:w-12 sm:h-12 rounded-2xl flex items-center justify-center text-sm font-black transition-all transform active:scale-95 disabled:active:scale-100 disabled:cursor-not-allowed"
                                :class="row.status === 'absent' ? 'bg-red-500 text-white shadow-lg shadow-red-500/30 scale-110 z-10' : 'bg-slate-100 text-slate-400 hover:bg-slate-200'"
                                title="Ausente"
                            >
                                A
                            </button>
                            <!-- Tardanza -->
                            <button 
                                @click="setStatus(row, 'late')"
                                :disabled="isLocked"
                                class="w-10 h-10 sm:w-12 sm:h-12 rounded-2xl flex items-center justify-center text-sm font-black transition-all transform active:scale-95 disabled:active:scale-100 disabled:cursor-not-allowed"
                                :class="row.status === 'late' ? 'bg-amber-500 text-white shadow-lg shadow-amber-500/30 scale-110 z-10' : 'bg-slate-100 text-slate-400 hover:bg-slate-200'"
                                title="Tardanza"
                            >
                                T
                            </button>
                            <!-- Justificado -->
                            <button 
                                @click="setStatus(row, 'excused')"
                                :disabled="isLocked"
                                class="w-10 h-10 sm:w-12 sm:h-12 rounded-2xl flex items-center justify-center text-sm font-black transition-all transform active:scale-95 disabled:active:scale-100 disabled:cursor-not-allowed"
                                :class="row.status === 'excused' ? 'bg-sky-500 text-white shadow-lg shadow-sky-500/30 scale-110 z-10' : 'bg-slate-100 text-slate-400 hover:bg-slate-200'"
                                title="Justificado"
                            >
                                J
                            </button>

                            <div class="w-px h-8 bg-slate-200 mx-1"></div>

                            <!-- Botón Observaciones -->
                            <div class="relative">
                                <button 
                                    @click="toggleNote(row.enrollment_id)"
                                    class="w-10 h-10 sm:w-12 sm:h-12 rounded-2xl flex items-center justify-center text-sm transition-all"
                                    :class="row.notes ? 'bg-indigo-100 text-indigo-600 font-black' : 'bg-transparent text-slate-300 hover:bg-slate-100 hover:text-slate-500'"
                                    title="Observaciones"
                                >
                                    <i class="fas fa-comment-dots" :class="{'animate-pulse': row.saving}"></i>
                                </button>
                                
                                <!-- Caja de Texto Flotante para Notas -->
                                <div 
                                    v-if="activeNoteId === row.enrollment_id"
                                    class="absolute right-0 top-full mt-2 w-64 bg-white border-2 border-slate-100 p-3 rounded-2xl shadow-xl z-50"
                                >
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-[10px] font-black uppercase text-slate-400 tracking-widest">Observación</span>
                                        <button @click="activeNoteId = null" class="text-slate-300 hover:text-slate-500"><i class="fas fa-times text-xs"></i></button>
                                    </div>
                                    <textarea 
                                        v-model="row.notes"
                                        :disabled="isLocked"
                                        class="w-full bg-slate-50 border-2 border-slate-100 rounded-xl px-3 py-2 text-xs text-slate-700 focus:border-indigo-400 focus:ring-0 outline-none resize-none disabled:bg-slate-100 disabled:text-slate-400"
                                        rows="2"
                                        placeholder="Escribe el motivo..."
                                    ></textarea>
                                    <div class="mt-2 flex justify-end">
                                        <button 
                                            v-if="!isLocked"
                                            @click="saveNote(row)" 
                                            class="px-4 py-1.5 bg-indigo-600 text-white text-[10px] font-black uppercase tracking-widest rounded-lg hover:bg-indigo-500 transition-all shadow-sm"
                                        >
                                            Guardar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>