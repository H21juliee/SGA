<script setup>
import { computed } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'
import DataGrid from '@/Components/DataGrid/DataGrid.vue'

const props = defineProps({
    section: Object,
    subject: Object,
    enrollments: Array,
    isClosed: Boolean,
})

const rows = computed(() =>
    props.enrollments.map(enrollment => {
        const revGrade = enrollment.revision_grades?.[0]
        return {
            enrollment_id: enrollment.id,
            student_name: `${enrollment.student.last_name}, ${enrollment.student.first_name}`,
            cedula: enrollment.student.cedula ?? '—',
            score: revGrade?.score ?? null,
            status: revGrade?.status ?? 'pending',
        }
    })
)

const columns = computed(() => [
    { key: 'student_name', label: 'Estudiante', editable: false },
    { key: 'cedula', label: 'Cédula', editable: false },
    { key: 'score', label: 'Nota', editable: !props.isClosed, type: 'number', min: 1, max: 20 },
    { 
        key: 'status', 
        label: 'Estado', 
        editable: false,
        format: (val) => {
            if (val === 'approved') return '<span class="text-emerald-500 font-bold">Aprobado</span>'
            if (val === 'failed') return '<span class="text-red-500 font-bold">Reprobado</span>'
            return '<span class="text-slate-400">Pendiente</span>'
        }
    },
])

function onCellUpdate({ rowId, column, value }) {
    if (props.isClosed) return

    router.patch('/revisions', {
        enrollment_id: rowId,
        subject_id: props.subject.id,
        score: value,
    }, { preserveScroll: true, preserveState: true })
}

function onSaveAll() {
    if (props.isClosed) return

    const changes = rows.value.map(row => ({
        enrollment_id: row.enrollment_id,
        subject_id: props.subject.id,
        score: row.score,
    }))

    router.post('/revisions/batch', { changes }, { preserveScroll: true })
}
</script>

<template>
    <AppLayout :title="`Revisiones — ${subject.name}`">
        <div class="space-y-8 max-w-12xl mx-auto">
            <!-- Header & Breadcrumbs -->
            <div class="animate-fade-in-up">
                <div class="flex items-center gap-4 mb-4">
                    <Link href="/revisions" class="px-4 py-2 rounded-xl bg-white border-2 border-slate-100 text-slate-500 font-bold text-xs uppercase tracking-wider hover:bg-slate-50 hover:text-red-500 hover:border-red-200 transition-all shadow-sm flex items-center gap-2 group">
                        <i class="fas fa-arrow-left group-hover:-translate-x-1 transition-transform"></i> Volver
                    </Link>

                    <nav class="flex items-center gap-3 text-[10px] font-black uppercase tracking-widest text-slate-400">
                        <Link href="/revisions" class="hover:text-red-500 transition-colors">Revisiones</Link>
                        <i class="fas fa-chevron-right text-[8px]"></i>
                        <span class="text-slate-600">{{ subject.name }}</span>
                    </nav>
                </div>

                <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div>
                        <h1 class="text-3xl font-extrabold text-slate-800">Evaluación de Revisión</h1>
                        <div class="flex items-center gap-3 mt-2">
                            <span class="px-2.5 py-1 rounded-lg bg-slate-100 text-slate-500 text-[10px] font-bold uppercase tracking-wider">
                                {{ section.grade_level?.name }} — Sección {{ section.name }}
                            </span>
                            <div class="w-1 h-1 rounded-full bg-slate-300"></div>
                            <span class="text-sm font-bold text-red-500">{{ subject.name }}</span>
                        </div>
                    </div>

                    <div
                        class="flex items-center gap-3 px-5 py-3 rounded-2xl shadow-sm border animate-pulse-slow"
                        :class="!isClosed
                            ? 'bg-amber-50 border-amber-100 text-amber-700'
                            : 'bg-slate-50 border-slate-200 text-slate-500'"
                    >
                        <div
                            class="w-3 h-3 rounded-full"
                            :class="!isClosed ? 'bg-amber-500 shadow-lg shadow-amber-500/50' : 'bg-slate-400'"
                        ></div>
                        <span class="text-xs font-black uppercase tracking-widest">
                            {{ !isClosed ? 'Carga Abierta' : 'Año Cerrado (Solo Lectura)' }}
                        </span>
                    </div>
                </div>
            </div>

            <div v-if="rows.length === 0" class="glass-card rounded-3xl p-16 text-center animate-fade-in-up">
                <div class="w-20 h-20 bg-emerald-50 rounded-full flex items-center justify-center mx-auto mb-6 text-emerald-500">
                    <i class="fas fa-check-circle text-4xl"></i>
                </div>
                <h3 class="text-xl font-bold text-slate-600">No hay aplazados</h3>
                <p class="text-slate-400 mt-2 max-w-sm mx-auto">Todos los estudiantes aprobaron esta materia durante los lapsos regulares.</p>
            </div>
            
            <template v-else>
                <!-- DataGrid Container -->
                <div class="glass-card rounded-3xl overflow-hidden shadow-2xl animate-fade-in-up" style="animation-delay: 100ms">
                    <DataGrid
                        :columns="columns"
                        :rows="rows"
                        row-key="enrollment_id"
                        :readonly="isClosed"
                        @cell-update="onCellUpdate"
                        @save-all="onSaveAll"
                    />
                </div>

                <!-- Hint for read-only -->
                <div v-if="isClosed" class="flex items-center gap-3 p-4 bg-slate-100/50 rounded-2xl border-2 border-dashed border-slate-200 text-slate-400 text-sm animate-fade-in-up" style="animation-delay: 200ms">
                    <i class="fas fa-info-circle text-lg"></i>
                    <p class="font-medium">El año escolar está cerrado. Los cambios en las notas de revisión no están permitidos.</p>
                </div>
            </template>
        </div>
    </AppLayout>
</template>
