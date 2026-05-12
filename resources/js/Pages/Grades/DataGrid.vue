<script setup>
import { computed } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'
import DataGrid from '@/Components/DataGrid/DataGrid.vue'

const props = defineProps({
    section: Object,
    subject: Object,
    lapse: Object,
    enrollments: Array,
})

const rows = computed(() =>
    props.enrollments.map(enrollment => {
        const grade = enrollment.grades?.[0]
        return {
            enrollment_id: enrollment.id,
            student_name: `${enrollment.student.last_name}, ${enrollment.student.first_name}`,
            cedula: enrollment.student.cedula ?? '—',
            score: grade?.score ?? null,
        }
    })
)

const columns = computed(() => [
    { key: 'student_name', label: 'Estudiante', editable: false },
    { key: 'cedula', label: 'Cédula', editable: false },
    { key: 'score', label: 'Nota', editable: props.lapse.is_open, type: 'number', min: 1, max: 20 },
])

function onCellUpdate({ rowId, column, value }) {
    if (!props.lapse.is_open) return

    router.patch('/grades', {
        enrollment_id: rowId,
        subject_id: props.subject.id,
        lapse_id: props.lapse.id,
        score: value,
    }, { preserveScroll: true, preserveState: true })
}

function onSaveAll() {
    if (!props.lapse.is_open) return

    const changes = rows.value.map(row => ({
        enrollment_id: row.enrollment_id,
        subject_id: props.subject.id,
        lapse_id: props.lapse.id,
        score: row.score,
    }))

    router.post('/grades/batch', { changes }, { preserveScroll: true })
}
</script>

<template>
    <AppLayout :title="`Notas — ${subject.name}`">
        <div class="space-y-8 max-w-6xl mx-auto">
            <!-- Header & Breadcrumbs -->
            <div class="animate-fade-in-up">
                <nav class="flex items-center gap-3 text-[10px] font-black uppercase tracking-widest text-slate-400 mb-4">
                    <Link href="/grades" class="hover:text-primary-600 transition-colors">Notas</Link>
                    <i class="fas fa-chevron-right text-[8px]"></i>
                    <span class="text-slate-600">{{ subject.name }}</span>
                </nav>

                <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div>
                        <h1 class="text-3xl font-extrabold text-slate-800">{{ subject.name }}</h1>
                        <div class="flex items-center gap-3 mt-2">
                            <span class="px-2.5 py-1 rounded-lg bg-slate-100 text-slate-500 text-[10px] font-bold uppercase tracking-wider">
                                {{ section.grade_level?.name }} — Sección {{ section.name }}
                            </span>
                            <div class="w-1 h-1 rounded-full bg-slate-300"></div>
                            <span class="text-sm font-bold text-primary-500">{{ lapse.name }}</span>
                        </div>
                    </div>

                    <div
                        class="flex items-center gap-3 px-5 py-3 rounded-2xl shadow-sm border animate-pulse-slow"
                        :class="lapse.is_open
                            ? 'bg-emerald-50 border-emerald-100 text-emerald-700'
                            : 'bg-red-50 border-red-100 text-red-700'"
                    >
                        <div
                            class="w-3 h-3 rounded-full"
                            :class="lapse.is_open ? 'bg-emerald-500 shadow-lg shadow-emerald-500/50' : 'bg-red-500 shadow-lg shadow-red-500/50'"
                        ></div>
                        <span class="text-xs font-black uppercase tracking-widest">
                            {{ lapse.is_open ? 'Periodo Abierto' : 'Periodo de solo lectura' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- DataGrid Container -->
            <div class="glass-card rounded-3xl overflow-hidden shadow-2xl animate-fade-in-up" style="animation-delay: 100ms">
                <DataGrid
                    :columns="columns"
                    :rows="rows"
                    row-key="enrollment_id"
                    :readonly="!lapse.is_open"
                    @cell-update="onCellUpdate"
                    @save-all="onSaveAll"
                />
            </div>

            <!-- Hint for read-only -->
            <div v-if="!lapse.is_open" class="flex items-center gap-3 p-4 bg-slate-100/50 rounded-2xl border-2 border-dashed border-slate-200 text-slate-400 text-sm animate-fade-in-up" style="animation-delay: 200ms">
                <i class="fas fa-info-circle text-lg"></i>
                <p class="font-medium">Este lapso ha sido cerrado. Los cambios no están permitidos y la información se presenta en modo histórico.</p>
            </div>
        </div>
    </AppLayout>
</template>
