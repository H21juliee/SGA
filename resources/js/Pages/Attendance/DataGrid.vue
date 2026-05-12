<script setup>
import { ref, computed } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'
import DataGrid from '@/Components/DataGrid/DataGrid.vue'

const props = defineProps({
    section: Object,
    subject: Object,
    date: String,
    lapse: Object,
    enrollments: Array,
    isLocked: Boolean,
})

const currentDate = ref(props.date)

const statusOptions = ['present', 'absent', 'late', 'excused']

const rows = computed(() =>
    props.enrollments.map(enrollment => {
        const att = enrollment.attendances?.[0]
        return {
            enrollment_id: enrollment.id,
            student_name: `${enrollment.student.last_name}, ${enrollment.student.first_name}`,
            status: att?.status ?? 'present',
            notes: att?.notes ?? '',
        }
    })
)

const columns = computed(() => [
    { key: 'student_name', label: 'Estudiante', editable: false },
    { key: 'status', label: 'Asistencia', editable: !props.isLocked, type: 'select', options: statusOptions },
    { key: 'notes', label: 'Observaciones', editable: !props.isLocked, type: 'text' },
])

function updateDate() {
    router.get(`/attendance/${props.section.id}`, { 
        date: currentDate.value,
        subject_id: props.subject.id 
    }, { preserveState: true })
}

function onCellUpdate({ rowId, column, value }) {
    if (props.isLocked) return

    // Buscar la fila para enviar el estado actual de los otros campos
    const row = rows.value.find(r => r.enrollment_id === rowId)
    
    router.patch('/attendance', {
        enrollment_id: rowId,
        subject_id: props.subject.id,
        date: props.date,
        status: column === 'status' ? value : row.status,
        notes: column === 'notes' ? value : row.notes,
        lapse_id: props.lapse?.id,
    }, { preserveScroll: true, preserveState: true })
}

function onSaveAll() {
    if (props.isLocked) return

    const changes = rows.value.map(row => ({
        enrollment_id: row.enrollment_id,
        subject_id: props.subject.id,
        date: props.date,
        status: row.status,
        notes: row.notes,
        lapse_id: props.lapse?.id,
    }))

    router.post('/attendance/batch', { changes }, { preserveScroll: true })
}

function finalizeAttendance() {
    if (confirm('¿Estás seguro de finalizar la asistencia? Una vez finalizada, no se podrán realizar más cambios.')) {
        router.post('/attendance/lock', {
            subject_id: props.subject.id,
            section_id: props.section.id,
            date: props.date,
        })
    }
}
</script>

<template>
    <AppLayout :title="`Asistencia — ${subject.name} (${section.name})`">
        <div class="space-y-8 max-w-6xl mx-auto">
            <!-- Header & Breadcrumbs -->
            <div class="animate-fade-in-up">
                <nav class="flex items-center gap-3 text-[10px] font-black uppercase tracking-widest text-slate-400 mb-4">
                    <Link href="/attendance" class="hover:text-emerald-600 transition-colors">Asistencia</Link>
                    <i class="fas fa-chevron-right text-[8px]"></i>
                    <span class="text-slate-600">{{ section.grade_level?.name }} — {{ section.name }}</span>
                </nav>

                <div class="glass-card rounded-3xl p-6 lg:p-8 shadow-xl relative overflow-hidden">
                    <div class="absolute top-0 right-0 p-8 opacity-5 pointer-events-none">
                        <i class="fas fa-calendar-check text-8xl text-emerald-600"></i>
                    </div>

                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-8 relative z-10">
                        <div>
                            <div class="flex flex-wrap items-center gap-3 mb-2">
                                <h1 class="text-3xl font-extrabold text-slate-800">{{ subject.name }}</h1>
                                <span v-if="lapse" class="px-2.5 py-1 bg-primary-50 text-primary-600 text-[10px] font-black rounded-lg uppercase tracking-wider border border-primary-100">
                                    {{ lapse.name }}
                                </span>
                                <span v-else class="px-2.5 py-1 bg-red-50 text-red-600 text-[10px] font-black rounded-lg uppercase tracking-wider border border-red-100">
                                    Sin Lapso Abierto
                                </span>
                            </div>
                            <p class="text-slate-400 font-bold uppercase tracking-widest text-xs">Sección {{ section.name }} · {{ section.grade_level?.name }}</p>
                        </div>
                        
                        <div class="flex flex-col sm:flex-row items-start sm:items-end gap-4">
                            <div class="flex flex-col gap-2 min-w-[200px]">
                                <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Fecha de Asistencia</label>
                                <div class="relative">
                                    <input 
                                        type="date" 
                                        v-model="currentDate" 
                                        @change="updateDate"
                                        class="w-full bg-slate-50 border-2 border-slate-100 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-emerald-400 focus:ring-0 outline-none transition-all cursor-pointer shadow-sm"
                                    >
                                    <i class="fas fa-calendar-day absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 pointer-events-none"></i>
                                </div>
                            </div>

                            <button
                                v-if="!isLocked"
                                @click="finalizeAttendance"
                                class="flex items-center justify-center gap-3 px-6 py-3.5 bg-emerald-600 text-white text-[11px] font-black uppercase tracking-widest rounded-2xl shadow-lg shadow-emerald-600/20 hover:bg-emerald-500 hover:-translate-y-0.5 transition-all group"
                            >
                                <i class="fas fa-lock-open group-hover:hidden"></i>
                                <i class="fas fa-lock hidden group-hover:inline"></i>
                                Finalizar Pase
                            </button>
                            <div v-else class="flex items-center gap-3 px-6 py-3.5 bg-slate-100 text-slate-400 text-[11px] font-black uppercase tracking-widest rounded-2xl border border-slate-200 cursor-not-allowed">
                                <i class="fas fa-lock"></i>
                                {{ !lapse ? 'Bloqueado (Sin Lapso)' : 'Sesión Finalizada' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- DataGrid Container -->
            <div class="relative animate-fade-in-up" style="animation-delay: 100ms">
                <div v-if="isLocked" class="absolute -top-4 right-8 z-20 flex items-center gap-2 px-4 py-1.5 bg-amber-500 text-white text-[10px] font-black uppercase tracking-widest rounded-full shadow-lg shadow-amber-500/30">
                    <i class="fas fa-eye"></i>
                    Modo Solo Lectura
                </div>
                <div class="glass-card rounded-3xl overflow-hidden shadow-2xl">
                    <DataGrid 
                        :columns="columns" 
                        :rows="rows" 
                        row-key="enrollment_id" 
                        :loading="false"
                        :readonly="isLocked"
                        @cell-update="onCellUpdate" 
                        @save-all="onSaveAll"
                    />
                </div>
            </div>

            <!-- Lock Info -->
            <div v-if="isLocked" class="flex items-center gap-4 p-5 bg-slate-50 border border-slate-100 rounded-2xl animate-fade-in-up" style="animation-delay: 200ms">
                <div class="w-10 h-10 bg-amber-100 text-amber-600 rounded-xl flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-shield-halved"></i>
                </div>
                <p class="text-sm font-bold text-slate-500 leading-relaxed">
                    Esta sesión de asistencia ha sido finalizada y protegida contra cambios accidentales. 
                    Si necesitas realizar ajustes, contacta con el administrador.
                </p>
            </div>
        </div>
    </AppLayout>
</template>
