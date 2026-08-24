<script setup>
import { ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'

const props = defineProps({
    student: Object,
    enrollments: Array,
})

const openCards = ref({})
function toggleCard(key) {
    openCards.value[key] = !openCards.value[key]
}

const formatDate = (dateString) => {
    if (!dateString) return 'No especificada'
    const options = { year: 'numeric', month: 'long', day: 'numeric' }
    return new Date(dateString).toLocaleDateString('es-ES', options)
}

const getLapseScore = (grades, lapseNumber) => {
    if (!grades) return null
    const grade = grades.find(g => g.lapse?.number === lapseNumber)
    return (grade && grade.score !== null) ? parseFloat(grade.score) : null
}

const getCouncilAdj = (grades, lapseNumber) => {
    if (!grades) return 0
    const grade = grades.find(g => g.lapse?.number === lapseNumber)
    return (grade && grade.council_adjustment) ? parseInt(grade.council_adjustment) : 0
}

const getLapseDefinitive = (grades, lapseNumber) => {
    const score = getLapseScore(grades, lapseNumber)
    if (score === null) return null
    const adj = getCouncilAdj(grades, lapseNumber)
    return Math.max(1, Math.min(20, score + adj))
}

const fmtScore = (val) => {
    if (val === null || val === undefined) return '-'
    return parseFloat(val).toFixed(2)
}

const calcBaseAverage = (grades) => {
    if (!grades || grades.length === 0) return null
    let sum = 0, count = 0
    ;[1, 2, 3].forEach(lap => {
        const def = getLapseDefinitive(grades, lap)
        if (def !== null) { sum += def; count++ }
    })
    return count === 0 ? null : (sum / count)
}

const getRevision = (enrollment, subjectId) => {
    if (!enrollment.revision_grades) return null
    return enrollment.revision_grades.find(r => r.subject_id === subjectId) || null
}

const calcFinalGrade = (grades, enrollment, subjectId) => {
    const base = calcBaseAverage(grades)
    const revision = getRevision(enrollment, subjectId)
    if (revision && revision.score !== null) {
        return parseFloat(revision.score)
    }
    return base
}

const countAbsences = (enrollment, subjectId) => {
    if (!enrollment.attendances) return 0
    return enrollment.attendances.filter(
        a => a.subject_id === subjectId && a.status === 'absent'
    ).length
}

const groupBySubject = (enrollment) => {
    if (!enrollment.grades || enrollment.grades.length === 0) return []
    const acc = {}
    enrollment.grades.forEach(grade => {
        if (!grade.subject) return
        if (!acc[grade.subject_id]) {
            acc[grade.subject_id] = { subject: grade.subject, grades: [] }
        }
        acc[grade.subject_id].grades.push(grade)
    })
    return Object.values(acc)
}

const gradeColorClass = (score) => {
    if (score === null || score === undefined) return 'bg-slate-50 text-slate-400 border-slate-100'
    if (score >= 10) return 'bg-emerald-50 text-emerald-700 border-emerald-100'
    return 'bg-rose-50 text-rose-600 border-rose-100'
}

const enrollmentStatusMap = {
    active: { label: 'Activa', class: 'bg-emerald-50 text-emerald-600 border-emerald-100' },
    withdrawn: { label: 'Retirada', class: 'bg-slate-50 text-slate-600 border-slate-200' },
    completed: { label: 'Completada', class: 'bg-indigo-50 text-indigo-600 border-indigo-200' },
}

const studentStatusMap = {
    regular: { label: 'Regular', class: 'bg-emerald-50 text-emerald-600 border-emerald-100', dot: true },
    withdrawn: { label: 'Retirado', class: 'bg-slate-50 text-slate-600 border-slate-200', dot: false },
    suspended: { label: 'Suspendido', class: 'bg-rose-50 text-rose-600 border-rose-200', dot: false },
}
</script>

<template>
    <AppLayout :title="`Expediente de ${student.first_name}`">
        <div class="space-y-8 max-w-7xl mx-auto pb-12">

            <!-- Header -->
            <div class="flex items-center gap-4 animate-fade-in-up">
                <Link href="/admin/students"
                      class="w-10 h-10 rounded-xl bg-white text-slate-400 hover:text-primary-600 shadow-sm flex items-center justify-center transition-all hover:-translate-x-1 shrink-0 border border-slate-100">
                    <i class="fas fa-arrow-left"></i>
                </Link>
                <div>
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-800">
                        Expediente <span class="gradient-text">Académico</span>
                    </h2>
                    <p class="text-slate-400 text-sm font-medium mt-0.5">Historial de inscripciones, calificaciones y asistencias</p>
                </div>
            </div>

            <!-- Profile Card -->
            <div class="glass-card rounded-3xl p-6 sm:p-8 shadow-2xl relative overflow-hidden animate-fade-in-up" style="animation-delay: 100ms">
                <div class="absolute top-0 left-0 w-full h-32 bg-gradient-to-r from-primary-500 to-indigo-500 opacity-10 pointer-events-none"></div>
                <div class="relative z-10 flex flex-col sm:flex-row gap-6 items-start sm:items-center">
                    <div class="w-24 h-24 sm:w-28 sm:h-28 rounded-2xl bg-gradient-to-br from-primary-500 to-indigo-600 text-white flex items-center justify-center text-4xl font-black shadow-xl shadow-primary-500/30 shrink-0 overflow-hidden">
                        <img v-if="student.photo_url" :src="student.photo_url" alt="Foto" class="w-full h-full object-cover">
                        <span v-else>{{ student.first_name.charAt(0) }}{{ student.last_name.charAt(0) }}</span>
                    </div>
                    <div class="flex-1 min-w-0 space-y-3">
                        <div class="flex flex-wrap items-center gap-3">
                            <h3 class="text-2xl font-black text-slate-800">{{ student.last_name }}, {{ student.first_name }}</h3>
                            <span v-if="studentStatusMap[student.status]"
                                  class="px-3 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest border shadow-sm flex items-center gap-1.5"
                                  :class="studentStatusMap[student.status].class">
                                <span v-if="studentStatusMap[student.status].dot" class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                {{ studentStatusMap[student.status].label }}
                            </span>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 pt-3 border-t border-slate-100">
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Cédula</p>
                                <p class="font-bold text-slate-700 text-sm">{{ student.cedula || 'No especificada' }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Fecha Nac.</p>
                                <p class="font-bold text-slate-700 text-sm">{{ formatDate(student.birth_date) }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Representante</p>
                                <p class="font-bold text-slate-700 text-sm">{{ student.guardian?.name || 'No especificado' }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Tel. Repr.</p>
                                <p class="font-bold text-slate-700 text-sm">{{ student.guardian?.phone || 'No especificado' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Academic History -->
            <div class="space-y-6 animate-fade-in-up" style="animation-delay: 200ms">
                <div class="flex items-center gap-3 px-1">
                    <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-500 flex items-center justify-center">
                        <i class="fas fa-history"></i>
                    </div>
                    <h3 class="text-xl font-black text-slate-800">Historial de Inscripciones y Notas</h3>
                </div>

                <div v-if="enrollments.length === 0" class="glass-card rounded-3xl p-16 text-center shadow-lg">
                    <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 text-slate-300">
                        <i class="fas fa-folder-open text-3xl"></i>
                    </div>
                    <h4 class="text-lg font-bold text-slate-500">Sin historial académico</h4>
                    <p class="text-sm text-slate-400 mt-1">Este estudiante no tiene inscripciones registradas.</p>
                </div>

                <div v-else class="space-y-8">
                    <div v-for="enrollment in enrollments" :key="enrollment.id"
                         class="glass-card rounded-3xl overflow-hidden shadow-xl border border-white/40">

                        <!-- School Year Header -->
                        <div class="bg-slate-50/80 px-5 sm:px-6 py-5 border-b border-slate-100/50 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div>
                                <h4 class="font-black text-slate-700 text-lg">
                                    Año Escolar <span class="text-primary-600">{{ enrollment.school_year.name }}</span>
                                </h4>
                                <p class="text-xs font-bold text-slate-500 mt-0.5 uppercase tracking-widest">
                                    {{ enrollment.section.grade_level.name }} – Sección {{ enrollment.section.name }}
                                </p>
                            </div>
                            <span v-if="enrollmentStatusMap[enrollment.status]"
                                  class="px-3 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest border shadow-sm self-start sm:self-auto"
                                  :class="enrollmentStatusMap[enrollment.status].class">
                                {{ enrollmentStatusMap[enrollment.status].label }}
                            </span>
                        </div>

                        <div v-if="!groupBySubject(enrollment).length" class="px-6 py-10 text-center text-slate-400 text-sm">
                            No hay calificaciones registradas para este año escolar.
                        </div>

                        <template v-else>
                            <!-- DESKTOP TABLE (md+) -->
                            <div class="hidden md:block overflow-x-auto w-full">
                                <table class="w-full text-sm text-left">
                                    <thead class="bg-slate-50/50 text-[10px] uppercase font-black tracking-[0.15em] text-slate-400">
                                        <tr>
                                            <th class="px-6 py-4">Materia</th>
                                            <th class="px-3 py-4 text-center">Lapso 1</th>
                                            <th class="px-3 py-4 text-center">Lapso 2</th>
                                            <th class="px-3 py-4 text-center">Lapso 3</th>
                                            <th class="px-3 py-4 text-center">Faltas</th>
                                            <th class="px-3 py-4 text-center">Def. Base</th>
                                            <th class="px-3 py-4 text-center">Revisión</th>
                                            <th class="px-5 py-4 text-center">Definitiva</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-50">
                                        <tr v-for="sg in groupBySubject(enrollment)"
                                            :key="sg.subject.id"
                                            class="hover:bg-slate-50/40 transition-colors">
                                            <td class="px-6 py-3.5 font-bold text-slate-700 whitespace-nowrap">{{ sg.subject.name }}</td>
                                            <td class="px-3 py-3.5 text-center">
                                                <span class="font-medium text-slate-600 block">{{ fmtScore(getLapseDefinitive(sg.grades, 1)) }}</span>
                                                <span v-if="getCouncilAdj(sg.grades, 1) !== 0"
                                                      class="inline-flex items-center gap-0.5 text-[9px] font-black text-amber-500"
                                                      :title="`Base: ${fmtScore(getLapseScore(sg.grades, 1))}`">
                                                    ★ {{ getCouncilAdj(sg.grades, 1) > 0 ? '+' : '' }}{{ getCouncilAdj(sg.grades, 1) }} cjo.
                                                </span>
                                            </td>
                                            <td class="px-3 py-3.5 text-center">
                                                <span class="font-medium text-slate-600 block">{{ fmtScore(getLapseDefinitive(sg.grades, 2)) }}</span>
                                                <span v-if="getCouncilAdj(sg.grades, 2) !== 0"
                                                      class="inline-flex items-center gap-0.5 text-[9px] font-black text-amber-500"
                                                      :title="`Base: ${fmtScore(getLapseScore(sg.grades, 2))}`">
                                                    ★ {{ getCouncilAdj(sg.grades, 2) > 0 ? '+' : '' }}{{ getCouncilAdj(sg.grades, 2) }} cjo.
                                                </span>
                                            </td>
                                            <td class="px-3 py-3.5 text-center">
                                                <span class="font-medium text-slate-600 block">{{ fmtScore(getLapseDefinitive(sg.grades, 3)) }}</span>
                                                <span v-if="getCouncilAdj(sg.grades, 3) !== 0"
                                                      class="inline-flex items-center gap-0.5 text-[9px] font-black text-amber-500"
                                                      :title="`Base: ${fmtScore(getLapseScore(sg.grades, 3))}`">
                                                    ★ {{ getCouncilAdj(sg.grades, 3) > 0 ? '+' : '' }}{{ getCouncilAdj(sg.grades, 3) }} cjo.
                                                </span>
                                            </td>
                                            <td class="px-3 py-3.5 text-center">
                                                <span class="font-bold text-sm" :class="countAbsences(enrollment, sg.subject.id) > 0 ? 'text-rose-500' : 'text-slate-300'">
                                                    <i v-if="countAbsences(enrollment, sg.subject.id) > 0" class="fas fa-exclamation-triangle text-[10px] mr-0.5"></i>
                                                    {{ countAbsences(enrollment, sg.subject.id) || '–' }}
                                                </span>
                                            </td>
                                            <td class="px-3 py-3.5 text-center font-medium text-slate-600">{{ fmtScore(calcBaseAverage(sg.grades)) }}</td>
                                            <td class="px-3 py-3.5 text-center">
                                                <span v-if="getRevision(enrollment, sg.subject.id)"
                                                      class="inline-flex items-center justify-center px-2 py-0.5 rounded-md text-xs font-black border shadow-sm"
                                                      :class="gradeColorClass(parseFloat(getRevision(enrollment, sg.subject.id).score))">
                                                    {{ fmtScore(getRevision(enrollment, sg.subject.id).score) }}
                                                </span>
                                                <span v-else class="text-slate-300 text-xs">—</span>
                                            </td>
                                            <td class="px-5 py-3.5 text-center">
                                                <span class="inline-flex items-center justify-center px-3 py-1 rounded-lg text-xs font-black border shadow-sm"
                                                      :class="gradeColorClass(calcFinalGrade(sg.grades, enrollment, sg.subject.id))">
                                                    {{ fmtScore(calcFinalGrade(sg.grades, enrollment, sg.subject.id)) }}
                                                </span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- MOBILE ACCORDION (< md) -->
                            <div class="md:hidden divide-y divide-slate-100">
                                <div v-for="sg in groupBySubject(enrollment)" :key="sg.subject.id">
                                    <!-- Card header -->
                                    <button type="button"
                                            class="w-full text-left px-4 py-4 flex items-center justify-between gap-3 transition-colors active:bg-slate-50"
                                            @click="toggleCard(`${enrollment.id}-${sg.subject.id}`)">
                                        <div class="flex-1 min-w-0">
                                            <p class="font-bold text-slate-700 text-sm truncate">{{ sg.subject.name }}</p>
                                            <div class="flex items-center gap-3 mt-1">
                                                <span class="text-[11px] text-slate-400 font-medium">Prom.: {{ fmtScore(calcBaseAverage(sg.grades)) }}</span>
                                                <span v-if="countAbsences(enrollment, sg.subject.id) > 0"
                                                      class="text-[11px] font-bold text-rose-400 flex items-center gap-1">
                                                    <i class="fas fa-exclamation-triangle text-[9px]"></i>
                                                    {{ countAbsences(enrollment, sg.subject.id) }} faltas
                                                </span>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2 shrink-0">
                                            <span class="inline-flex items-center justify-center px-2.5 py-1 rounded-lg text-xs font-black border shadow-sm"
                                                  :class="gradeColorClass(calcFinalGrade(sg.grades, enrollment, sg.subject.id))">
                                                {{ fmtScore(calcFinalGrade(sg.grades, enrollment, sg.subject.id)) }}
                                            </span>
                                            <i class="fas text-slate-300 text-xs transition-transform"
                                               :class="openCards[`${enrollment.id}-${sg.subject.id}`] ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                                        </div>
                                    </button>

                                    <!-- Card expanded body -->
                                    <div v-if="openCards[`${enrollment.id}-${sg.subject.id}`]"
                                         class="bg-slate-50/60 px-4 pb-4 space-y-3">

                                        <!-- Lapses -->
                                        <div class="bg-white rounded-2xl p-4 border border-slate-100">
                                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-3">Notas por Lapso</p>
                                            <div class="grid grid-cols-3 gap-2">
                                                <div v-for="lap in [1,2,3]" :key="lap" class="text-center">
                                                    <p class="text-[10px] font-black text-slate-400 mb-1.5">Lapso {{ lap }}</p>
                                                    <p class="font-black text-slate-700">{{ fmtScore(getLapseDefinitive(sg.grades, lap)) }}</p>
                                                    <p v-if="getLapseScore(sg.grades, lap) !== null && getCouncilAdj(sg.grades, lap) !== 0"
                                                       class="text-[9px] text-slate-400 mt-0.5">Base: {{ fmtScore(getLapseScore(sg.grades, lap)) }}</p>
                                                    <span v-if="getCouncilAdj(sg.grades, lap) !== 0"
                                                          class="inline-block mt-0.5 px-1.5 py-0.5 rounded text-[8px] font-black bg-amber-50 text-amber-600 border border-amber-100">
                                                        {{ getCouncilAdj(sg.grades, lap) > 0 ? '+' : '' }}{{ getCouncilAdj(sg.grades, lap) }} Consejo
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Attendance -->
                                        <div class="bg-white rounded-2xl p-4 border border-slate-100">
                                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-3">Inasistencias</p>
                                            <div class="grid grid-cols-3 gap-2 mb-3">
                                                <div v-for="lap in [1,2,3]" :key="lap" class="text-center">
                                                    <p class="text-[10px] font-black text-slate-400 mb-1.5">Lapso {{ lap }}</p>
                                                    <p class="font-bold"
                                                       :class="enrollment.attendances?.filter(a => a.subject_id === sg.subject.id && a.lapse_id === sg.grades.find(g => g.lapse?.number === lap)?.lapse_id && a.status === 'absent').length > 0 ? 'text-rose-500' : 'text-slate-300'">
                                                        {{ enrollment.attendances?.filter(a => a.subject_id === sg.subject.id && a.lapse_id === sg.grades.find(g => g.lapse?.number === lap)?.lapse_id && a.status === 'absent').length || '–' }}
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="pt-3 border-t border-slate-100 flex justify-between items-center">
                                                <span class="text-xs text-slate-500 font-medium">Total inasistencias:</span>
                                                <span class="text-sm font-black" :class="countAbsences(enrollment, sg.subject.id) > 0 ? 'text-rose-500' : 'text-slate-400'">
                                                    {{ countAbsences(enrollment, sg.subject.id) }}
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Revision -->
                                        <div class="bg-white rounded-2xl p-4 border border-slate-100">
                                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-3">Evaluación Extraordinaria</p>
                                            <div class="flex items-center justify-between">
                                                <span class="text-sm text-slate-500 font-medium">Nota de Revisión</span>
                                                <span v-if="getRevision(enrollment, sg.subject.id)"
                                                      class="px-2.5 py-1 rounded-lg text-xs font-black border shadow-sm"
                                                      :class="gradeColorClass(parseFloat(getRevision(enrollment, sg.subject.id).score))">
                                                    {{ fmtScore(getRevision(enrollment, sg.subject.id).score) }}
                                                </span>
                                                <span v-else class="text-slate-300 text-sm font-bold">N/A</span>
                                            </div>
                                        </div>

                                        <!-- Final grade summary -->
                                        <div class="rounded-2xl p-4 flex items-center justify-between"
                                             :class="calcFinalGrade(sg.grades, enrollment, sg.subject.id) >= 10 ? 'bg-emerald-50 border border-emerald-100' : 'bg-rose-50 border border-rose-100'">
                                            <span class="font-black uppercase tracking-wide text-sm"
                                                  :class="calcFinalGrade(sg.grades, enrollment, sg.subject.id) >= 10 ? 'text-emerald-700' : 'text-rose-700'">
                                                Nota Definitiva Final
                                            </span>
                                            <div class="flex items-center gap-2">
                                                <span class="text-2xl font-black"
                                                      :class="calcFinalGrade(sg.grades, enrollment, sg.subject.id) >= 10 ? 'text-emerald-600' : 'text-rose-600'">
                                                    {{ fmtScore(calcFinalGrade(sg.grades, enrollment, sg.subject.id)) }}
                                                </span>
                                                <span class="text-xs font-black uppercase"
                                                      :class="calcFinalGrade(sg.grades, enrollment, sg.subject.id) >= 10 ? 'text-emerald-500' : 'text-rose-500'">
                                                    {{ calcFinalGrade(sg.grades, enrollment, sg.subject.id) >= 10 ? '✓ Aprobado' : '✗ Reprobado' }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>