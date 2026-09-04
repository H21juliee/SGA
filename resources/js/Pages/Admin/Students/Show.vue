<script setup>
import { ref, computed } from 'vue'
import { Link, useForm, router } from '@inertiajs/vue3'
import axios from 'axios'
import Swal from 'sweetalert2'
import AppLayout from '@/Components/Layout/AppLayout.vue'
import Modal from '@/Components/UI/Modal.vue'

const props = defineProps({
    student: Object,
    enrollments: Array,
    subjectDebts: { type: Array, default: () => [] },
    allSubjects: { type: Array, default: () => [] },
    schoolYears: { type: Array, default: () => [] },
})

// Collapsible School Years
const expandedYears = ref({})
if (props.enrollments && props.enrollments.length > 0) {
    const active = props.enrollments.find(e => e.school_year?.is_active) || props.enrollments[0]
    expandedYears.value[active.id] = true
}

function toggleYear(id) {
    expandedYears.value[id] = !expandedYears.value[id]
}

// Student Edit Modal
const showEditStudentModal = ref(false)
const studentForm = useForm({
    first_name: '',
    last_name: '',
    cedula: '',
    birth_date: '',
    gender: 'M',
    status: 'regular',
    guardian_id: '',
})

const guardianSearchCedula = ref('')
const searchingGuardian = ref(false)
const guardianNotFound = ref(false)
const selectedGuardian = ref(props.student.guardian || null)

const showGuardianModal = ref(false)
const guardianFormErrors = ref({})
const guardianForm = useForm({
    cedula: '',
    name: '',
    phone: '',
    email: '',
})

function formatPhone(value) {
    if (!value) return '';
    let val = value.replace(/[^0-9]/g, '');
    if (val.length > 0 && val[0] !== '0') {
        val = '0' + val;
    }
    if (val.length > 4) {
        val = val.substring(0, 4) + '-' + val.substring(4);
    }
    if (val.length > 12) {
        val = val.substring(0, 12);
    }
    return val;
}

function formatCedula(value) {
    if (!value) return '';
    let val = value.toUpperCase().replace(/[^VEP0-9]/g, '');
    if (val.length > 0 && !['V', 'E', 'P'].includes(val[0])) {
        if (/[0-9]/.test(val[0])) {
            val = 'V' + val;
        } else {
            val = val.substring(1);
        }
    }
    if (val.length > 9) {
        val = val.substring(0, 9);
    }
    if (val.length > 1) {
        val = val[0] + '-' + val.substring(1);
    }
    return val;
}

function openEditStudentModal() {
    studentForm.clearErrors()
    studentForm.first_name = props.student.first_name || ''
    studentForm.last_name = props.student.last_name || ''
    studentForm.cedula = props.student.cedula || ''
    studentForm.birth_date = props.student.birth_date ? props.student.birth_date.split('T')[0] : ''
    studentForm.gender = props.student.gender || 'M'
    studentForm.status = props.student.status || 'regular'
    studentForm.guardian_id = props.student.guardian_id || ''
    selectedGuardian.value = props.student.guardian || null
    guardianSearchCedula.value = props.student.guardian?.cedula || ''
    guardianNotFound.value = false
    showEditStudentModal.value = true
}

function searchGuardian() {
    if (!guardianSearchCedula.value) return
    searchingGuardian.value = true
    guardianNotFound.value = false
    selectedGuardian.value = null
    studentForm.guardian_id = ''

    axios.get('/admin/guardians/search', { params: { cedula: guardianSearchCedula.value } })
        .then(response => {
            if (response.data.guardian) {
                selectedGuardian.value = response.data.guardian
                studentForm.guardian_id = response.data.guardian.id
            } else {
                guardianNotFound.value = true
            }
        })
        .finally(() => {
            searchingGuardian.value = false
        })
}

function openCreateGuardian() {
    guardianForm.cedula = guardianSearchCedula.value
    guardianForm.name = ''
    guardianForm.phone = ''
    guardianForm.email = ''
    guardianFormErrors.value = {}
    showGuardianModal.value = true
}

function submitGuardian() {
    axios.post('/admin/guardians', guardianForm.data())
        .then(response => {
            showGuardianModal.value = false
            selectedGuardian.value = response.data.guardian
            studentForm.guardian_id = response.data.guardian.id
            guardianSearchCedula.value = response.data.guardian.cedula
            guardianNotFound.value = false
        })
        .catch(error => {
            if (error.response && error.response.status === 422) {
                guardianFormErrors.value = error.response.data.errors
            }
        })
}

function submitStudentEdit() {
    studentForm.put(`/admin/students/${props.student.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            showEditStudentModal.value = false
        }
    })
}

// Subject Debts State
const isDebtsOpen = ref(false)
const isDebtModalOpen = ref(false)
const editingDebtId = ref(null)

const debtForm = useForm({
    subject_id: '',
    origin_school_year_id: '',
    status: 'pending',
    score: '',
    moment: '',
    acta_number: '',
    notes: '',
})

const groupedSubjects = computed(() => {
    const groups = {}
    props.allSubjects.forEach(sub => {
        const gradeName = sub.grade_level?.name || 'Otras Materias'
        if (!groups[gradeName]) groups[gradeName] = []
        groups[gradeName].push(sub)
    })
    return groups
})

function resetDebtForm() {
    debtForm.subject_id = ''
    debtForm.origin_school_year_id = ''
    debtForm.status = 'pending'
    debtForm.score = ''
    debtForm.moment = ''
    debtForm.acta_number = ''
    debtForm.notes = ''
    debtForm.clearErrors()
}

function openDebtModal(debt = null) {
    if (debt) {
        editingDebtId.value = debt.id
        resetDebtForm()
        debtForm.subject_id = debt.subject_id
        debtForm.origin_school_year_id = debt.origin_school_year_id || ''
        debtForm.status = debt.status || 'pending'
        debtForm.score = debt.score !== null && debt.score !== undefined ? debt.score : ''
        debtForm.moment = debt.moment || ''
        debtForm.acta_number = debt.acta_number || ''
        debtForm.notes = debt.notes || ''
    } else {
        editingDebtId.value = null
        resetDebtForm()
    }
    isDebtModalOpen.value = true
}

function onScoreInput() {
    if (debtForm.score && Number(debtForm.score) >= 10) {
        debtForm.status = 'resolved'
    }
}

function submitDebt() {
    if (editingDebtId.value) {
        debtForm.patch(`/admin/subject-debts/${editingDebtId.value}`, {
            preserveScroll: true,
            onSuccess: () => {
                isDebtModalOpen.value = false
                resetDebtForm()
                editingDebtId.value = null
            }
        })
    } else {
        debtForm.post(`/admin/students/${props.student.id}/subject-debts`, {
            preserveScroll: true,
            onSuccess: () => {
                isDebtModalOpen.value = false
                resetDebtForm()
                editingDebtId.value = null
            }
        })
    }
}

function toggleDebtStatus(debt) {
    const newStatus = debt.status === 'pending' ? 'resolved' : 'pending'
    router.patch(`/admin/subject-debts/${debt.id}`, {
        status: newStatus,
    }, {
        preserveScroll: true,
    })
}

function deleteDebt(debt) {
    Swal.fire({
        title: '¿Confirmar Eliminación?',
        text: `¿Estás seguro de eliminar el registro de materia pendiente '${debt.subject?.name}'?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        buttonsStyling: false,
        customClass: {
            popup: 'rounded-3xl border-2 border-slate-100 shadow-2xl',
            title: 'text-2xl font-black text-slate-800',
            htmlContainer: 'text-slate-500 font-medium',
            confirmButton: 'px-6 py-3 bg-red-500 text-white text-[11px] font-black uppercase tracking-widest rounded-2xl shadow-lg shadow-red-500/20 hover:bg-red-400 transition-all mx-2',
            cancelButton: 'px-6 py-3 bg-slate-500 text-white text-[11px] font-black uppercase tracking-widest rounded-2xl shadow-lg shadow-slate-500/20 hover:bg-slate-400 transition-all mx-2'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/admin/subject-debts/${debt.id}`, {
                preserveScroll: true,
            })
        }
    })
}

const openCards = ref({})
function toggleCard(key) {
    openCards.value[key] = !openCards.value[key]
}

const formatDate = (dateString) => {
    if (!dateString) return 'No especificada'
    const dateOnly = dateString.split('T')[0]
    const parts = dateOnly.split('-')
    if (parts.length === 3) {
        const year = parseInt(parts[0], 10)
        const month = parseInt(parts[1], 10) - 1
        const day = parseInt(parts[2], 10)
        const date = new Date(year, month, day)
        return date.toLocaleDateString('es-ES', { year: 'numeric', month: 'long', day: 'numeric' })
    }
    return dateString
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
    completed: { label: 'Completada', class: 'bg-primary-50 text-primary-600 border-primary-200' },
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
                <div class="absolute top-0 left-0 w-full h-32 bg-gradient-to-r from-primary-500 to-primary-500 opacity-10 pointer-events-none"></div>
                <div class="relative z-10 flex flex-col sm:flex-row gap-6 items-start sm:items-center">
                    <div class="w-24 h-24 sm:w-28 sm:h-28 rounded-2xl bg-gradient-to-br from-primary-500 to-primary-600 text-white flex items-center justify-center text-4xl font-black shadow-xl shadow-primary-500/30 shrink-0 overflow-hidden">
                        <img v-if="student.photo_url" :src="student.photo_url" alt="Foto" class="w-full h-full object-cover">
                        <span v-else>{{ student.first_name.charAt(0) }}{{ student.last_name.charAt(0) }}</span>
                    </div>
                    <div class="flex-1 min-w-0 space-y-3">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div class="flex flex-wrap items-center gap-3">
                                <h3 class="text-2xl font-black text-slate-800">{{ student.last_name }}, {{ student.first_name }}</h3>
                                <span v-if="studentStatusMap[student.status]"
                                      class="px-3 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest border shadow-sm flex items-center gap-1.5"
                                      :class="studentStatusMap[student.status].class">
                                    <span v-if="studentStatusMap[student.status].dot" class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                    {{ studentStatusMap[student.status].label }}
                                </span>
                            </div>

                            <button @click="openEditStudentModal"
                                    class="px-3.5 py-2 rounded-xl bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 font-bold text-xs shadow-sm flex items-center gap-2 transition-all hover:border-primary-300 hover:text-primary-600 hover:shadow shrink-0">
                                <i class="fas fa-user-edit text-primary-500"></i>
                                <span>Editar Estudiante</span>
                            </button>
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
            <!-- Profile Card End -->
            </div>

            <!-- Materias Pendientes / Arrastre Académico (Accordion) -->
            <div class="glass-card rounded-3xl overflow-hidden shadow-xl border border-white/40 animate-fade-in-up" style="animation-delay: 150ms">
                <!-- Header (Clickable Accordion) -->
                <div @click="isDebtsOpen = !isDebtsOpen"
                     class="bg-slate-50/90 hover:bg-slate-100/80 px-5 sm:px-6 py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 cursor-pointer transition-all select-none"
                     :class="{ 'border-b border-slate-100/60': isDebtsOpen }">
                    <div class="flex items-center gap-3.5">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center font-bold text-sm shadow-sm shrink-0 transition-colors"
                             :class="isDebtsOpen ? 'bg-amber-500 text-white shadow-amber-500/30' : 'bg-amber-50 text-amber-600'">
                            <i class="fas fa-book-reader text-sm"></i>
                        </div>
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="text-base sm:text-lg font-black text-slate-800">Materias Pendientes / Arrastre</h3>
                                <span v-if="subjectDebts.filter(d => d.status === 'pending').length > 0"
                                      class="px-2.5 py-0.5 rounded-full text-xs font-black bg-amber-100 text-amber-800 border border-amber-200">
                                    {{ subjectDebts.filter(d => d.status === 'pending').length }} pendiente(s)
                                </span>
                                <span v-else class="px-2.5 py-0.5 rounded-full text-xs font-black bg-emerald-100 text-emerald-800 border border-emerald-200">
                                    Solvente
                                </span>
                            </div>
                            <p class="text-xs text-slate-400 font-medium">Asignaturas de arrastre adeudadas de años escolares anteriores</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2.5 self-start sm:self-auto">
                        <button @click.stop="openDebtModal()"
                                class="px-3.5 py-2 rounded-xl bg-primary-600 hover:bg-primary-700 text-white font-bold text-xs shadow-md shadow-primary-500/20 flex items-center gap-2 transition-all hover:scale-105 shrink-0">
                            <i class="fas fa-plus"></i>
                            <span>Asignar</span>
                        </button>

                        <div class="w-8 h-8 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-slate-400 shadow-sm transition-transform duration-200"
                             :class="{ 'rotate-180': isDebtsOpen }">
                            <i class="fas fa-chevron-down text-xs"></i>
                        </div>
                    </div>
                </div>

                <!-- Collapsible Content -->
                <transition
                    enter-active-class="transition-all duration-300 ease-out overflow-hidden"
                    enter-from-class="max-h-0 opacity-0"
                    enter-to-class="max-h-[2000px] opacity-100"
                    leave-active-class="transition-all duration-200 ease-in overflow-hidden"
                    leave-from-class="max-h-[2000px] opacity-100"
                    leave-to-class="max-h-0 opacity-0"
                >
                    <div v-show="isDebtsOpen" class="p-6 sm:p-8 space-y-6">
                        <!-- Empty State -->
                        <div v-if="!subjectDebts || subjectDebts.length === 0" class="py-6 text-center text-slate-400">
                            <div class="w-12 h-12 rounded-full bg-slate-50 text-slate-300 flex items-center justify-center mx-auto mb-2">
                                <i class="fas fa-check-circle text-xl"></i>
                            </div>
                            <p class="text-sm font-bold text-slate-600">Sin deudas académicas registradas</p>
                            <p class="text-xs text-slate-400 mt-0.5">El estudiante se encuentra solvente sin materias pendientes de arrastre.</p>
                        </div>

                        <!-- Debts List -->
                        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div v-for="debt in subjectDebts" :key="debt.id"
                                 class="p-4 rounded-2xl border transition-all hover:shadow-md flex flex-col justify-between"
                                 :class="debt.status === 'resolved' ? 'bg-emerald-50/40 border-emerald-200/70' : 'bg-amber-50/40 border-amber-200/70'">
                                <div class="space-y-2">
                                    <div class="flex items-start justify-between gap-2">
                                        <div>
                                            <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">
                                                {{ debt.subject?.grade_level?.name || 'Materia' }}
                                            </span>
                                            <h4 class="font-black text-slate-800 text-base leading-tight">
                                                {{ debt.subject?.name || 'Materia no encontrada' }}
                                            </h4>
                                        </div>
                                        <span class="px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider border shadow-sm shrink-0"
                                              :class="debt.status === 'resolved' ? 'bg-emerald-100 text-emerald-800 border-emerald-200' : 'bg-amber-100 text-amber-800 border-amber-200'">
                                            <i :class="debt.status === 'resolved' ? 'fas fa-check mr-1' : 'fas fa-clock mr-1'"></i>
                                            {{ debt.status === 'resolved' ? 'Solvente' : 'Pendiente' }}
                                        </span>
                                    </div>

                                    <div class="text-xs text-slate-500 space-y-1 pt-1">
                                        <div v-if="debt.score !== null && debt.score !== undefined" class="flex items-center gap-1.5 font-bold text-xs">
                                            <span class="text-slate-400">Calificación:</span>
                                            <span class="px-2 py-0.5 rounded-lg text-xs font-black shadow-sm"
                                                  :class="debt.score >= 10 ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : 'bg-rose-100 text-rose-800 border border-rose-200'">
                                                {{ Number(debt.score).toFixed(0) }} pts
                                            </span>
                                        </div>
                                        <p v-if="debt.moment">
                                            <span class="font-bold text-slate-600">Momento:</span> {{ debt.moment }}
                                        </p>
                                        <p v-if="debt.acta_number">
                                            <span class="font-bold text-slate-600">Acta Nº:</span> {{ debt.acta_number }}
                                        </p>
                                        <p v-if="debt.origin_school_year">
                                            <span class="font-bold text-slate-600">Origen:</span> Año {{ debt.origin_school_year.name }}
                                        </p>
                                        <p v-if="debt.resolved_at">
                                            <span class="font-bold text-slate-600">Solventada:</span> {{ formatDate(debt.resolved_at) }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-center justify-end gap-1.5 pt-3 mt-3 border-t"
                                     :class="debt.status === 'resolved' ? 'border-emerald-200/50' : 'border-amber-200/50'">
                                    <button @click="openDebtModal(debt)"
                                            class="px-2.5 py-1 rounded-lg text-xs font-bold bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 flex items-center gap-1 transition-colors shadow-sm"
                                            title="Asentar nota o editar datos">
                                        <i class="fas fa-edit text-slate-500"></i>
                                        <span>{{ debt.score ? 'Editar Nota' : 'Asentar Nota' }}</span>
                                    </button>

                                    <button @click="toggleDebtStatus(debt)"
                                            class="px-2.5 py-1 rounded-lg text-xs font-bold transition-colors flex items-center gap-1"
                                            :class="debt.status === 'pending' ? 'bg-emerald-600 hover:bg-emerald-700 text-white' : 'bg-slate-200 hover:bg-slate-300 text-slate-700'"
                                            :title="debt.status === 'pending' ? 'Marcar como Aprobada / Solvente' : 'Marcar como Pendiente'">
                                        <i :class="debt.status === 'pending' ? 'fas fa-check' : 'fas fa-undo'"></i>
                                        <span>{{ debt.status === 'pending' ? 'Aprobar' : 'Reabrir' }}</span>
                                    </button>

                                    <button @click="deleteDebt(debt)"
                                            class="w-7 h-7 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-500 flex items-center justify-center transition-colors"
                                            title="Eliminar materia pendiente">
                                        <i class="fas fa-trash-alt text-xs"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </transition>
            </div>

            <!-- Academic History -->
            <div class="space-y-6 animate-fade-in-up" style="animation-delay: 200ms">
                <div class="flex items-center gap-3 px-1">
                    <div class="w-10 h-10 rounded-xl bg-primary-50 text-primary-500 flex items-center justify-center">
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

                <div v-else class="space-y-4 sm:space-y-6">
                    <div v-for="enrollment in enrollments" :key="enrollment.id"
                         class="glass-card rounded-3xl overflow-hidden shadow-xl border border-white/40 transition-all">

                        <!-- School Year Header (Clickable Accordion) -->
                        <div @click="toggleYear(enrollment.id)"
                             class="bg-slate-50/90 hover:bg-slate-100/80 px-5 sm:px-6 py-4 border-b border-slate-100/50 flex flex-col sm:flex-row sm:items-center justify-between gap-3 cursor-pointer transition-all select-none">
                            <div class="flex items-center gap-3.5">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center font-bold text-sm shadow-sm shrink-0 transition-colors"
                                     :class="expandedYears[enrollment.id] ? 'bg-primary-500 text-white shadow-primary-500/30' : 'bg-slate-200 text-slate-600'">
                                    <i class="fas fa-graduation-cap text-sm"></i>
                                </div>
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h4 class="font-black text-slate-800 text-base sm:text-lg">
                                            Año Escolar <span class="text-primary-600">{{ enrollment.school_year.name }}</span>
                                        </h4>
                                        <span v-if="enrollment.school_year.is_active"
                                              class="px-2 py-0.5 rounded-md text-[9px] font-black uppercase tracking-widest bg-emerald-100 text-emerald-800 border border-emerald-200">
                                            Activo
                                        </span>
                                    </div>
                                    <p class="text-xs font-bold text-slate-500 mt-0.5 uppercase tracking-widest">
                                        {{ enrollment.section.grade_level.name }} – Sección {{ enrollment.section.name }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-3 self-start sm:self-auto">
                                <span v-if="enrollmentStatusMap[enrollment.status]"
                                      class="px-3 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest border shadow-sm"
                                      :class="enrollmentStatusMap[enrollment.status].class">
                                    {{ enrollmentStatusMap[enrollment.status].label }}
                                </span>

                                <div class="w-8 h-8 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-slate-400 shadow-sm transition-transform duration-200"
                                     :class="{ 'rotate-180': expandedYears[enrollment.id] }">
                                    <i class="fas fa-chevron-down text-xs"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Accordion Body -->
                        <transition
                            enter-active-class="transition-all duration-300 ease-out overflow-hidden"
                            enter-from-class="max-h-0 opacity-0"
                            enter-to-class="max-h-[5000px] opacity-100"
                            leave-active-class="transition-all duration-200 ease-in overflow-hidden"
                            leave-from-class="max-h-[5000px] opacity-100"
                            leave-to-class="max-h-0 opacity-0"
                        >
                            <div v-show="expandedYears[enrollment.id]">
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
                        </transition>
                    </div>
                </div>
            </div>

        <!-- Modal Asignar Materia Pendiente -->
        <div v-if="isDebtModalOpen"
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm animate-fade-in">
            <div class="bg-white rounded-3xl p-6 sm:p-8 max-w-lg w-full shadow-2xl space-y-6 animate-scale-up border border-slate-100"
                 @click.stop>
                <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center font-bold">
                            <i :class="editingDebtId ? 'fas fa-edit' : 'fas fa-plus'"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-black text-slate-800">
                                {{ editingDebtId ? 'Editar / Asentar Nota de Materia Pendiente' : 'Asignar Materia Pendiente' }}
                            </h3>
                            <p class="text-xs text-slate-400 font-medium">Registro académico y evaluación de materia de arrastre</p>
                        </div>
                    </div>
                    <button @click="isDebtModalOpen = false" class="text-slate-400 hover:text-slate-600 p-2">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <form @submit.prevent="submitDebt" class="space-y-4">
                    <div>
                        <label class="block text-xs font-black uppercase tracking-wider text-slate-600 mb-1.5">
                            Materia que Adeuda <span class="text-rose-500">*</span>
                        </label>
                        <select v-model="debtForm.subject_id"
                                required
                                :disabled="!!editingDebtId"
                                class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm font-medium text-slate-800 focus:border-primary-500 focus:ring-primary-500 bg-white disabled:bg-slate-100 disabled:text-slate-500">
                            <option value="" disabled>Seleccione una materia...</option>
                            <optgroup v-for="(subjects, gradeName) in groupedSubjects" :key="gradeName" :label="gradeName">
                                <option v-for="sub in subjects" :key="sub.id" :value="sub.id">
                                    {{ sub.name }} ({{ sub.code || gradeName }})
                                </option>
                            </optgroup>
                        </select>
                        <p v-if="debtForm.errors.subject_id" class="text-xs text-rose-500 font-bold mt-1">
                            {{ debtForm.errors.subject_id }}
                        </p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-black uppercase tracking-wider text-slate-600 mb-1.5">
                                Año Escolar de Origen <span class="text-slate-400 font-normal normal-case">(Opcional)</span>
                            </label>
                            <select v-model="debtForm.origin_school_year_id"
                                    class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm font-medium text-slate-800 focus:border-primary-500 focus:ring-primary-500 bg-white">
                                <option value="">No especificado / Año anterior</option>
                                <option v-for="year in schoolYears" :key="year.id" :value="year.id">
                                    {{ year.name }} {{ year.is_active ? '(Activo)' : '' }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-black uppercase tracking-wider text-slate-600 mb-1.5">
                                Estado Actual <span class="text-rose-500">*</span>
                            </label>
                            <select v-model="debtForm.status"
                                    class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm font-bold focus:border-primary-500 focus:ring-primary-500 bg-white"
                                    :class="debtForm.status === 'resolved' ? 'text-emerald-700 bg-emerald-50/30' : 'text-amber-700 bg-amber-50/30'">
                                <option value="pending">⏳ Pendiente de Aprobación</option>
                                <option value="resolved">✓ Solvente / Aprobada</option>
                            </select>
                        </div>
                    </div>

                    <!-- Datos de Evaluación (Calificación, Momento, Acta) -->
                    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100 space-y-4">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-clipboard-check text-primary-500 text-sm"></i>
                            <h4 class="text-xs font-black uppercase tracking-wider text-slate-700">Evaluación de Materia Pendiente</h4>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-black uppercase tracking-wider text-slate-600 mb-1.5">
                                    Calificación Obtenida (01 a 20 pts)
                                </label>
                                <input type="number"
                                       v-model="debtForm.score"
                                       min="1"
                                       max="20"
                                       step="1"
                                       placeholder="Ej. 12"
                                       @input="onScoreInput"
                                       class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm font-black text-slate-800 focus:border-primary-500 focus:ring-primary-500 bg-white">
                                <p class="text-[10px] text-slate-400 mt-1">Nota mínima aprobatoria: 10 puntos</p>
                            </div>

                            <div>
                                <label class="block text-xs font-black uppercase tracking-wider text-slate-600 mb-1.5">
                                    Momento de Presentación
                                </label>
                                <select v-model="debtForm.moment"
                                        class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm font-medium text-slate-800 focus:border-primary-500 focus:ring-primary-500 bg-white">
                                    <option value="">Seleccione momento...</option>
                                    <option value="1er Momento (Octubre)">1er Momento (Octubre)</option>
                                    <option value="2do Momento (Diciembre)">2do Momento (Diciembre)</option>
                                    <option value="3er Momento (Febrero)">3er Momento (Febrero)</option>
                                    <option value="4to Momento (Mayo/Junio)">4to Momento (Mayo/Junio)</option>
                                    <option value="Prueba Extraordinaria">Prueba Extraordinaria</option>
                                    <option value="Aprobada en Año Anterior">Aprobada en Año Anterior</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-black uppercase tracking-wider text-slate-600 mb-1.5">
                                    Nº de Acta / Folio <span class="text-slate-400 font-normal normal-case">(Opcional)</span>
                                </label>
                                <input type="text"
                                       v-model="debtForm.acta_number"
                                       placeholder="Ej. ACTA-05-2025"
                                       class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm font-medium text-slate-800 focus:border-primary-500 focus:ring-primary-500 bg-white">
                            </div>

                            <div>
                                <label class="block text-xs font-black uppercase tracking-wider text-slate-600 mb-1.5">
                                    Observaciones <span class="text-slate-400 font-normal normal-case">(Opcional)</span>
                                </label>
                                <input type="text"
                                       v-model="debtForm.notes"
                                       placeholder="Ej. Presentó en horario especial"
                                       class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm font-medium text-slate-800 focus:border-primary-500 focus:ring-primary-500 bg-white">
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                        <button type="button" @click="isDebtModalOpen = false"
                                class="px-4 py-2 rounded-xl text-slate-500 hover:bg-slate-100 font-bold text-xs transition-colors">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="debtForm.processing"
                                class="px-5 py-2.5 rounded-xl bg-primary-600 hover:bg-primary-700 text-white font-bold text-xs shadow-md shadow-primary-500/20 transition-all disabled:opacity-50 flex items-center gap-2">
                            <i v-if="debtForm.processing" class="fas fa-spinner fa-spin"></i>
                            <span>{{ editingDebtId ? 'Actualizar Calificación' : 'Guardar Materia Pendiente' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Editar Estudiante -->
        <Modal :show="showEditStudentModal" @close="showEditStudentModal = false" max-width="2xl">
            <div class="p-8 max-h-[90vh] overflow-y-auto custom-scrollbar">
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h3 class="text-2xl font-black text-slate-800">
                            Editar <span class="text-primary-500">Estudiante</span>
                        </h3>
                        <p class="text-sm font-medium text-slate-400 mt-1">Completa los datos del expediente académico</p>
                    </div>
                    <button @click="showEditStudentModal = false" class="w-10 h-10 rounded-full bg-slate-50 text-slate-400 hover:bg-slate-100 transition-all shadow-sm">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <form @submit.prevent="submitStudentEdit" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Nombres</label>
                            <input v-model="studentForm.first_name" type="text" class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all shadow-sm" required>
                            <p v-if="studentForm.errors.first_name" class="text-xs text-red-500 font-bold mt-1 ml-1">{{ studentForm.errors.first_name }}</p>
                        </div>
                        <div class="space-y-2">
                            <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Apellidos</label>
                            <input v-model="studentForm.last_name" type="text" class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all shadow-sm" required>
                            <p v-if="studentForm.errors.last_name" class="text-xs text-red-500 font-bold mt-1 ml-1">{{ studentForm.errors.last_name }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Cédula Escolar</label>
                            <input v-model="studentForm.cedula" @input="studentForm.cedula = formatCedula($event.target.value)" type="text" class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all shadow-sm" maxlength="12">
                            <p v-if="studentForm.errors.cedula" class="text-xs text-red-500 font-bold mt-1 ml-1">{{ studentForm.errors.cedula }}</p>
                        </div>
                        <div class="space-y-2">
                            <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Fecha de Nacimiento</label>
                            <div class="relative">
                                <input v-model="studentForm.birth_date" type="date" class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all shadow-sm" required>
                            </div>
                            <p v-if="studentForm.errors.birth_date" class="text-xs text-red-500 font-bold mt-1 ml-1">{{ studentForm.errors.birth_date }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Género</label>
                            <div class="relative">
                                <select v-model="studentForm.gender" class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all appearance-none cursor-pointer shadow-sm">
                                    <option value="M">Masculino</option>
                                    <option value="F">Femenino</option>
                                </select>
                                <i class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 pointer-events-none"></i>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Estatus</label>
                            <div class="relative">
                                <select v-model="studentForm.status" class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all appearance-none cursor-pointer shadow-sm">
                                    <option value="regular">Regular</option>
                                    <option value="withdrawn">Retirado</option>
                                    <option value="suspended">Suspendido</option>
                                </select>
                                <i class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 pointer-events-none"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Guardian Section -->
                    <div class="pt-6 border-t border-slate-100">
                        <h4 class="text-sm font-black text-slate-800 uppercase tracking-widest mb-4">Representante</h4>
                        
                        <div class="bg-slate-50 border border-slate-200 rounded-2xl p-6 relative shadow-sm">
                            <div v-if="selectedGuardian" class="flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 bg-primary-50 text-primary-500 rounded-full flex items-center justify-center shadow-sm">
                                        <i class="fas fa-user-tie"></i>
                                    </div>
                                    <div>
                                        <p class="font-bold text-slate-800">{{ selectedGuardian.name }}</p>
                                        <p class="text-xs text-slate-500 font-medium">C.I: {{ selectedGuardian.cedula || 'N/A' }} | Tel: {{ selectedGuardian.phone || 'N/A' }}</p>
                                    </div>
                                </div>
                                <button type="button" @click="selectedGuardian = null; studentForm.guardian_id = ''; guardianSearchCedula = '';" class="text-[10px] font-black text-red-500 hover:text-white uppercase tracking-widest bg-red-50 hover:bg-red-500 px-3 py-2 rounded-lg border border-red-100 transition-colors shadow-sm">
                                    Cambiar
                                </button>
                            </div>
                            
                            <div v-else class="space-y-4">
                                <div>
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1 mb-2 block">Buscar por Cédula</label>
                                    <div class="flex gap-3">
                                        <div class="relative flex-1">
                                            <input v-model="guardianSearchCedula" @input="guardianSearchCedula = formatCedula($event.target.value)" @keyup.enter="searchGuardian" type="text" placeholder="Ej: V-12345678" class="w-full bg-white border-2 border-slate-200 rounded-xl px-4 py-2.5 text-slate-700 text-sm font-bold focus:border-primary-400 focus:ring-0 outline-none transition-all shadow-sm" maxlength="12">
                                        </div>
                                        <button type="button" @click="searchGuardian" :disabled="searchingGuardian || !guardianSearchCedula" class="px-5 py-2.5 bg-primary-600 text-white text-xs font-black uppercase tracking-widest rounded-xl shadow-md shadow-primary-600/20 hover:bg-primary-500 hover:-translate-y-0.5 transition-all disabled:opacity-50 disabled:translate-y-0">
                                            <i v-if="searchingGuardian" class="fas fa-spinner fa-spin"></i>
                                            <i v-else class="fas fa-search"></i>
                                        </button>
                                    </div>
                                    <p v-if="studentForm.errors.guardian_id" class="text-xs text-red-500 font-bold mt-1 ml-1">{{ studentForm.errors.guardian_id }}</p>
                                </div>
                                
                                <div v-if="guardianNotFound" class="bg-amber-50 border border-amber-200 rounded-xl p-4 flex items-center justify-between animate-fade-in-up shadow-sm">
                                    <p class="text-[11px] font-bold text-amber-700">No se encontró representante con esta cédula.</p>
                                    <button type="button" @click="openCreateGuardian" class="px-4 py-2 bg-amber-600 text-white text-[10px] font-black uppercase tracking-widest rounded-lg shadow-sm hover:bg-amber-500 transition-all">
                                        Registrar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-4 mt-8 pt-4">
                        <button type="button" @click="showEditStudentModal = false" class="px-6 py-3 text-sm font-black uppercase tracking-widest text-slate-400 hover:text-slate-600 transition-all">
                            Cancelar
                        </button>
                        <button 
                            type="submit" 
                            :disabled="studentForm.processing" 
                            class="px-10 py-3.5 bg-primary-600 text-white text-[11px] font-black uppercase tracking-widest rounded-2xl shadow-lg shadow-primary-600/20 hover:bg-primary-500 hover:-translate-y-0.5 transition-all disabled:opacity-50 disabled:translate-y-0"
                        >
                            <i class="fas fa-save mr-2"></i>
                            Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Guardian Create Modal -->
        <Modal :show="showGuardianModal" @close="showGuardianModal = false" max-width="md">
            <div class="p-8">
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h3 class="text-xl font-black text-slate-800">
                            Nuevo <span class="text-primary-500">Representante</span>
                        </h3>
                        <p class="text-xs font-medium text-slate-400 mt-1">Ingresa los datos para registrar un nuevo apoderado.</p>
                    </div>
                    <button @click="showGuardianModal = false" class="w-8 h-8 rounded-full bg-slate-50 text-slate-400 hover:bg-slate-100 transition-all shadow-sm">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div class="space-y-4">
                    <div class="space-y-2">
                        <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Cédula</label>
                        <input v-model="guardianForm.cedula" @input="guardianForm.cedula = formatCedula($event.target.value)" type="text" class="w-full bg-slate-50 border-2 border-slate-200 rounded-xl px-4 py-2.5 text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all shadow-sm" maxlength="12">
                        <p v-if="guardianFormErrors && guardianFormErrors.cedula" class="text-xs text-red-500 font-bold mt-1 ml-1">{{ guardianFormErrors.cedula[0] }}</p>
                    </div>
                    <div class="space-y-2">
                        <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Nombre Completo</label>
                        <input v-model="guardianForm.name" type="text" class="w-full bg-slate-50 border-2 border-slate-200 rounded-xl px-4 py-2.5 text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all shadow-sm">
                        <p v-if="guardianFormErrors && guardianFormErrors.name" class="text-xs text-red-500 font-bold mt-1 ml-1">{{ guardianFormErrors.name[0] }}</p>
                    </div>
                    <div class="space-y-2">
                        <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Teléfono</label>
                        <input v-model="guardianForm.phone" @input="guardianForm.phone = formatPhone($event.target.value)" type="tel" placeholder="0414-1234567" maxlength="12" class="w-full bg-slate-50 border-2 border-slate-200 rounded-xl px-4 py-2.5 text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all shadow-sm">
                        <p v-if="guardianFormErrors && guardianFormErrors.phone" class="text-xs text-red-500 font-bold mt-1 ml-1">{{ guardianFormErrors.phone[0] }}</p>
                    </div>
                    <div class="space-y-2">
                        <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Correo Electrónico</label>
                        <input v-model="guardianForm.email" type="email" class="w-full bg-slate-50 border-2 border-slate-200 rounded-xl px-4 py-2.5 text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all shadow-sm">
                        <p v-if="guardianFormErrors && guardianFormErrors.email" class="text-xs text-red-500 font-bold mt-1 ml-1">{{ guardianFormErrors.email[0] }}</p>
                    </div>
                    
                    <div class="flex justify-end pt-6">
                        <button type="button" @click="submitGuardian" class="px-6 py-3 bg-primary-600 text-white text-[11px] font-black uppercase tracking-widest rounded-xl shadow-lg shadow-primary-600/20 hover:bg-primary-500 hover:-translate-y-0.5 transition-all">
                            Guardar y Seleccionar
                        </button>
                    </div>
                </div>
            </div>
        </Modal>

        </div>
    </AppLayout>
</template>