<script setup>
import { ref, computed } from 'vue'
import { Link, useForm, router } from '@inertiajs/vue3'
import axios from 'axios'
import Swal from 'sweetalert2'
import AppLayout from '@/Components/Layout/AppLayout.vue'
import Modal from '@/Components/UI/Modal.vue'
import SectionRepresentante from './Partials/SectionRepresentante.vue'
import SectionPrincipal from './Partials/SectionPrincipal.vue'
import SectionAdicional from './Partials/SectionAdicional.vue'
import SectionMedica from './Partials/SectionMedica.vue'
import SectionHabitacion from './Partials/SectionHabitacion.vue'
import SectionComunidad from './Partials/SectionComunidad.vue'
import SectionRecaudos from './Partials/SectionRecaudos.vue'

const props = defineProps({
    student: Object,
    communities: { type: Array, default: () => [] },
    gradeLevels: { type: Array, default: () => [] },
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
const editTab = ref(1)
const isProfileOpen = ref(false)
const isEditHousingOpen = ref(true)
const isEditCommunityOpen = ref(true)

const studentForm = useForm({
    first_name: '',
    last_name: '',
    nationality: 'V',
    cedula: '',
    birth_date: '',
    birth_place: '',
    gender: 'M',
    status: 'regular',
    address: '',
    community_id: '',
    email: '',
    phone_home: '',
    phone_mobile: '',
    guardian_id: null,
    entry_type: 'new',
    target_grade_level_id: '',
    origin_school: '',
    profile: {
        lives_with: [],
        family_group_count: null,
        religion: '',
        extracurricular_activities: false,
        extracurricular_activities_detail: '',
        has_canaima: false,
        uses_canteen: false,
        has_medical_condition: false,
        medical_condition_detail: '',
        has_allergies: false,
        allergies_detail: '',
        requires_pe_medication: false,
        has_medical_certificate: false,
        blood_type: '',
        height: '',
        weight: '',
        doctor_visit_frequency: '',
        housing_type: '',
        housing_tenure: '',
        housing_condition: '',
        has_water: false,
        has_electricity: false,
        has_internet: false,
        additional_services: '',
        community_name: '',
        belongs_to_communal_council: false,
        functioning_services: [],
        services_quality: '',
        community_institutions: [],
        documents_consignment: [],
    },
})

const guardianSearchCedula = ref('')
const searchingGuardian = ref(false)
const guardianNotFound = ref(false)
const selectedGuardian = ref(props.student.guardian || null)

const showGuardianModal = ref(false)
const guardianFormErrors = ref({})
const guardianForm = useForm({
    cedula: '',
    first_name: '',
    last_name: '',
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

// Documento de identidad / Cédula Escolar
const docType = ref('V')
const docNumber = ref('')

const docPlaceholder = computed(() => {
    switch (docType.value) {
        case 'V': return 'Ej: 30123456 (6-9 dígitos)'
        case 'E': return 'Ej: 84550210 (6-9 dígitos)'
        case 'CE': return 'Ej: V11214200350 (12 carac.)'
        case 'P': return 'Ej: ABC123456'
        default: return 'Sin documento registrado'
    }
})

const docMaxLength = computed(() => {
    switch (docType.value) {
        case 'V':
        case 'E': return 9
        case 'CE': return 12
        case 'P': return 15
        default: return 0
    }
})

function parseCedulaToDoc(cedula) {
    if (!cedula) return { type: 'NONE', number: '' }
    if (cedula.startsWith('V-')) return { type: 'V', number: cedula.replace('V-', '') }
    if (cedula.startsWith('E-')) return { type: 'E', number: cedula.replace('E-', '') }
    if (cedula.startsWith('P-')) return { type: 'P', number: cedula.replace('P-', '') }
    if (/^[VE][1-9]\d{10}$/i.test(cedula)) return { type: 'CE', number: cedula.toUpperCase() }
    if (cedula.startsWith('CE-')) return { type: 'CE', number: cedula.replace('CE-', '').toUpperCase() }
    return { type: 'V', number: cedula }
}

function onDocTypeChange() {
    studentForm.clearErrors('cedula')
    if (docType.value === 'NONE') {
        docNumber.value = ''
    } else if (docType.value === 'V' || docType.value === 'E') {
        docNumber.value = docNumber.value.replace(/[^0-9]/g, '').slice(0, 9)
    } else if (docType.value === 'CE') {
        docNumber.value = docNumber.value.toUpperCase().replace(/[^VE0-9]/g, '').slice(0, 12)
    }
}

function onDocNumberInput(e) {
    let val = (e.target.value || '').toUpperCase()
    if (docType.value === 'V' || docType.value === 'E') {
        val = val.replace(/[^0-9]/g, '').slice(0, 9)
    } else if (docType.value === 'CE') {
        val = val.replace(/[^VE0-9]/g, '').slice(0, 12)
    } else if (docType.value === 'P') {
        val = val.replace(/[^A-Z0-9]/g, '').slice(0, 15)
    }
    docNumber.value = val
    validateDocNumber()
}

function validateDocNumber() {
    if (docType.value === 'NONE' || !docNumber.value.trim()) {
        studentForm.clearErrors('cedula')
        return true
    }
    const num = docNumber.value.trim()
    if (docType.value === 'V' || docType.value === 'E') {
        if (!/^\d{6,9}$/.test(num)) {
            studentForm.setError('cedula', 'La cédula debe contener entre 6 y 9 dígitos numéricos.')
            return false
        }
    } else if (docType.value === 'CE') {
        if (!/^[VE][1-9]\d{10}$/.test(num)) {
            studentForm.setError('cedula', 'La cédula escolar debe tener 12 caracteres oficiales (ej: V11214200350).')
            return false
        }
    } else if (docType.value === 'P') {
        if (!/^[A-Z0-9]{6,15}$/.test(num)) {
            studentForm.setError('cedula', 'El pasaporte debe tener entre 6 y 15 caracteres alfanuméricos.')
            return false
        }
    }
    studentForm.clearErrors('cedula')
    return true
}

function buildCedulaValue() {
    if (docType.value === 'NONE' || !docNumber.value.trim()) {
        return null
    }
    const clean = docNumber.value.trim().toUpperCase()
    if (docType.value === 'V') return `V-${clean}`
    if (docType.value === 'E') return `E-${clean}`
    if (docType.value === 'P') return `P-${clean}`
    if (docType.value === 'CE') return clean
    return clean
}

// Rango de edad permitido: 10 a 20 años
const minBirthDate = computed(() => {
    const d = new Date()
    d.setFullYear(d.getFullYear() - 20)
    return d.toISOString().split('T')[0]
})

const maxBirthDate = computed(() => {
    const d = new Date()
    d.setFullYear(d.getFullYear() - 10)
    return d.toISOString().split('T')[0]
})

function validateBirthDate() {
    if (!studentForm.birth_date) {
        studentForm.clearErrors('birth_date')
        return true
    }
    if (studentForm.birth_date > maxBirthDate.value) {
        studentForm.setError('birth_date', 'El estudiante debe tener al menos 10 años de edad.')
        return false
    }
    if (studentForm.birth_date < minBirthDate.value) {
        studentForm.setError('birth_date', 'El estudiante no puede tener más de 20 años de edad.')
        return false
    }
    studentForm.clearErrors('birth_date')
    return true
}

function openEditStudentModal() {
    studentForm.clearErrors()
    editTab.value = 1
    studentForm.first_name = props.student.first_name || ''
    studentForm.last_name = props.student.last_name || ''
    studentForm.nationality = props.student.nationality || 'V'
    studentForm.cedula = props.student.cedula || ''
    studentForm.birth_date = props.student.birth_date ? props.student.birth_date.split('T')[0] : ''
    studentForm.birth_place = props.student.birth_place || ''
    studentForm.gender = props.student.gender || 'M'
    studentForm.status = props.student.status || 'regular'
    studentForm.address = props.student.address || ''
    studentForm.community_id = props.student.community_id || ''
    studentForm.email = props.student.email || ''
    studentForm.phone_home = props.student.phone_home || ''
    studentForm.phone_mobile = props.student.phone_mobile || ''
    studentForm.guardian_id = props.student.guardian_id || null
    studentForm.entry_type = props.student.entry_type || 'new'
    studentForm.target_grade_level_id = props.student.target_grade_level_id || props.gradeLevels?.[0]?.id || ''
    studentForm.origin_school = props.student.origin_school || ''

    const p = props.student.profile || {}
    studentForm.profile = {
        lives_with: p.lives_with ? [...p.lives_with] : [],
        family_group_count: p.family_group_count ?? null,
        religion: p.religion || '',
        extracurricular_activities: Boolean(p.extracurricular_activities),
        extracurricular_activities_detail: p.extracurricular_activities_detail || '',
        has_canaima: Boolean(p.has_canaima),
        uses_canteen: Boolean(p.uses_canteen),
        has_medical_condition: Boolean(p.has_medical_condition),
        medical_condition_detail: p.medical_condition_detail || '',
        has_allergies: Boolean(p.has_allergies),
        allergies_detail: p.allergies_detail || '',
        requires_pe_medication: Boolean(p.requires_pe_medication),
        has_medical_certificate: Boolean(p.has_medical_certificate),
        blood_type: p.blood_type || '',
        height: p.height || '',
        weight: p.weight || '',
        doctor_visit_frequency: p.doctor_visit_frequency || '',
        housing_type: p.housing_type || '',
        housing_tenure: p.housing_tenure || '',
        housing_condition: p.housing_condition || '',
        has_water: Boolean(p.has_water),
        has_electricity: Boolean(p.has_electricity),
        has_internet: Boolean(p.has_internet),
        additional_services: p.additional_services || '',
        community_name: p.community_name || '',
        belongs_to_communal_council: Boolean(p.belongs_to_communal_council),
        functioning_services: p.functioning_services ? [...p.functioning_services] : [],
        services_quality: p.services_quality || '',
        community_institutions: p.community_institutions ? [...p.community_institutions] : [],
        documents_consignment: p.documents_consignment ? JSON.parse(JSON.stringify(p.documents_consignment)) : [],
    }

    showEditStudentModal.value = true
}

function submitStudentEdit() {
    studentForm.clearErrors()
    const venezuelanPhoneRegex = /^(0412|0414|0424|0416|0426|02\d{2})-\d{7}$/
    let valid = true

    if (!validateBirthDate()) valid = false

    if (studentForm.phone_mobile && studentForm.phone_mobile.trim()) {
        if (!venezuelanPhoneRegex.test(studentForm.phone_mobile.trim())) {
            studentForm.setError('phone_mobile', 'El teléfono móvil debe tener un formato válido en Venezuela (ej: 0412-1234567, 0414-1234567).')
            valid = false
        }
    }

    if (studentForm.phone_home && studentForm.phone_home.trim()) {
        if (!venezuelanPhoneRegex.test(studentForm.phone_home.trim())) {
            studentForm.setError('phone_home', 'El teléfono fijo debe tener un código de área válido (ej: 0212-1234567).')
            valid = false
        }
    }

    // Talla (estatura en cm, máx. 2 decimales)
    if (studentForm.profile?.height !== '' && studentForm.profile?.height !== null && studentForm.profile?.height !== undefined) {
        const rawH = String(studentForm.profile.height).trim().replace(',', '.')
        if (rawH.includes('.')) {
            const dec = rawH.split('.')[1]
            if (dec && dec.length > 2) {
                studentForm.setError('profile.height', 'La estatura solo permite hasta 2 cifras decimales (ej: 165.50 cm).')
                editTab.value = 4
                valid = false
            }
        }
        const h = parseFloat(rawH)
        if (isNaN(h)) {
            studentForm.setError('profile.height', 'La estatura debe ser un valor numérico.')
            editTab.value = 4
            valid = false
        } else if (h > 0 && h <= 2.5) {
            studentForm.setError('profile.height', 'Indique la estatura en centímetros (ej: 165 cm).')
            editTab.value = 4
            valid = false
        } else if (h < 90 || h > 230) {
            studentForm.setError('profile.height', 'La estatura debe estar entre 90 y 230 cm.')
            editTab.value = 4
            valid = false
        }
    }

    // Peso corporal (en kg, máx. 2 decimales)
    if (studentForm.profile?.weight !== '' && studentForm.profile?.weight !== null && studentForm.profile?.weight !== undefined) {
        const rawW = String(studentForm.profile.weight).trim().replace(',', '.')
        if (rawW.includes('.')) {
            const dec = rawW.split('.')[1]
            if (dec && dec.length > 2) {
                studentForm.setError('profile.weight', 'El peso solo permite hasta 2 cifras decimales (ej: 63.75 kg).')
                editTab.value = 4
                valid = false
            }
        }
        const w = parseFloat(rawW)
        if (isNaN(w)) {
            studentForm.setError('profile.weight', 'El peso debe ser un valor numérico.')
            editTab.value = 4
            valid = false
        } else if (w < 20 || w > 200) {
            studentForm.setError('profile.weight', 'El peso corporal debe estar entre 20 y 200 kg.')
            editTab.value = 4
            valid = false
        }
    }

    if (!valid) return

    studentForm.put(`/admin/students/${props.student.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            showEditStudentModal.value = false
        },
        onError: (errs) => {
            const keys = Object.keys(errs)
            if (keys.some(k => k === 'guardian_id')) {
                editTab.value = 1
            } else if (keys.some(k => ['first_name', 'last_name', 'cedula', 'birth_date', 'birth_place', 'address', 'community_id', 'nationality', 'phone_mobile', 'phone_home', 'email'].includes(k))) {
                editTab.value = 2
            } else if (keys.some(k => k.startsWith('profile.extracurricular') || k.startsWith('profile.lives_with') || k.startsWith('profile.family_group_count'))) {
                editTab.value = 3
            } else if (keys.some(k => k.startsWith('profile.medical') || k.startsWith('profile.allergies') || k.startsWith('profile.blood_type') || k.startsWith('profile.height') || k.startsWith('profile.weight'))) {
                editTab.value = 4
            } else if (keys.some(k => k.startsWith('profile.housing') || k.startsWith('profile.community') || k.startsWith('profile.has_'))) {
                editTab.value = 5
            }
        },
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

            <!-- Ficha Socioeducativa y Médica del Alumno (Accordion) -->
            <div class="glass-card rounded-3xl overflow-hidden shadow-xl border border-white/40 animate-fade-in-up" style="animation-delay: 120ms">
                <div @click="isProfileOpen = !isProfileOpen"
                     class="bg-slate-50/90 hover:bg-slate-100/80 px-5 sm:px-6 py-4 flex items-center justify-between gap-3 cursor-pointer transition-all select-none"
                     :class="{ 'border-b border-slate-100/60': isProfileOpen }">
                    <div class="flex items-center gap-3.5">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center font-bold text-sm shadow-sm shrink-0 transition-colors"
                             :class="isProfileOpen ? 'bg-primary-600 text-white shadow-primary-500/30' : 'bg-primary-50 text-primary-600'">
                            <i class="fas fa-file-medical-alt text-sm"></i>
                        </div>
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="text-base sm:text-lg font-black text-slate-800">Ficha Socioeducativa, Médica y Comunitaria</h3>
                                <span v-if="student.profile" class="px-2.5 py-0.5 rounded-full text-xs font-black bg-emerald-100 text-emerald-800 border border-emerald-200">
                                    Completa
                                </span>
                                <span v-else class="px-2.5 py-0.5 rounded-full text-xs font-black bg-slate-100 text-slate-500 border border-slate-200">
                                    Básica
                                </span>
                            </div>
                            <p class="text-xs text-slate-400 font-medium">Datos de convivencia, salud, vivienda y entorno social</p>
                        </div>
                    </div>

                    <div class="w-8 h-8 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-slate-400 shadow-sm transition-transform duration-200"
                         :class="{ 'rotate-180': isProfileOpen }">
                        <i class="fas fa-chevron-down text-xs"></i>
                    </div>
                </div>

                <!-- Contenido de la Ficha -->
                <div v-show="isProfileOpen" class="p-6 sm:p-8 space-y-6 bg-white/60">
                    <!-- Grid de 4 Bloques -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <!-- 1. Identidad y Contacto -->
                        <div class="bg-slate-50/70 border border-slate-200/80 rounded-2xl p-4 space-y-2.5">
                            <h4 class="text-xs font-black text-slate-700 uppercase tracking-wider flex items-center gap-2">
                                <i class="fas fa-id-card text-primary-500"></i> Filiación y Contacto
                            </h4>
                            <div class="space-y-1.5 text-xs">
                                <div><span class="text-slate-400 font-bold">Nacionalidad:</span> <span class="font-bold text-slate-700">{{ student.nationality === 'E' ? 'Extranjera (E)' : 'Venezolana (V)' }}</span></div>
                                <div><span class="text-slate-400 font-bold">Lugar Nac.:</span> <span class="font-bold text-slate-700">{{ student.birth_place || 'No especificado' }}</span></div>
                                <div><span class="text-slate-400 font-bold">Comunidad:</span> <span class="font-bold text-slate-700">{{ student.community?.name || 'No asignada' }}</span></div>
                                <div><span class="text-slate-400 font-bold">Dirección:</span> <span class="font-bold text-slate-700">{{ student.address || 'No registrada' }}</span></div>
                                <div><span class="text-slate-400 font-bold">Móvil:</span> <span class="font-bold text-slate-700">{{ student.phone_mobile || '—' }}</span></div>
                                <div><span class="text-slate-400 font-bold">Fijo:</span> <span class="font-bold text-slate-700">{{ student.phone_home || '—' }}</span></div>
                                <div><span class="text-slate-400 font-bold">Correo:</span> <span class="font-bold text-slate-700">{{ student.email || '—' }}</span></div>
                            </div>
                        </div>

                        <!-- 2. Entorno Familiar -->
                        <div class="bg-slate-50/70 border border-slate-200/80 rounded-2xl p-4 space-y-2.5">
                            <h4 class="text-xs font-black text-slate-700 uppercase tracking-wider flex items-center gap-2">
                                <i class="fas fa-users text-primary-500"></i> Familiar y Social
                            </h4>
                            <div class="space-y-1.5 text-xs">
                                <div><span class="text-slate-400 font-bold">Vive con:</span> <span class="font-bold text-slate-700">{{ student.profile?.lives_with?.length ? student.profile.lives_with.join(', ') : 'No especificado' }}</span></div>
                                <div><span class="text-slate-400 font-bold">Grupo familiar:</span> <span class="font-bold text-slate-700">{{ student.profile?.family_group_count ? student.profile.family_group_count + ' personas' : '—' }}</span></div>
                                <div><span class="text-slate-400 font-bold">Religión:</span> <span class="font-bold text-slate-700">{{ student.profile?.religion || '—' }}</span></div>
                                <div><span class="text-slate-400 font-bold">Extraescolar:</span> <span class="font-bold text-slate-700">{{ student.profile?.extracurricular_activities ? student.profile.extracurricular_activities_detail || 'Sí' : 'No' }}</span></div>
                                <div><span class="text-slate-400 font-bold">Canaima:</span> <span class="font-bold" :class="student.profile?.has_canaima ? 'text-emerald-600' : 'text-slate-500'">{{ student.profile?.has_canaima ? 'Sí posee' : 'No' }}</span></div>
                                <div><span class="text-slate-400 font-bold">Comedor:</span> <span class="font-bold" :class="student.profile?.uses_canteen ? 'text-emerald-600' : 'text-slate-500'">{{ student.profile?.uses_canteen ? 'Sí usa' : 'No' }}</span></div>
                            </div>
                        </div>

                        <!-- 3. Salud y Médica -->
                        <div class="bg-slate-50/70 border border-slate-200/80 rounded-2xl p-4 space-y-2.5">
                            <h4 class="text-xs font-black text-slate-700 uppercase tracking-wider flex items-center gap-2">
                                <i class="fas fa-heartbeat text-rose-500"></i> Salud y Médica
                            </h4>
                            <div class="space-y-1.5 text-xs">
                                <div><span class="text-slate-400 font-bold">Tratamiento:</span> <span class="font-bold" :class="student.profile?.has_medical_condition ? 'text-rose-600' : 'text-slate-700'">{{ student.profile?.has_medical_condition ? student.profile.medical_condition_detail || 'Sí' : 'Ninguno' }}</span></div>
                                <div><span class="text-slate-400 font-bold">Alergias:</span> <span class="font-bold" :class="student.profile?.has_allergies ? 'text-amber-600' : 'text-slate-700'">{{ student.profile?.has_allergies ? student.profile.allergies_detail || 'Sí' : 'No' }}</span></div>
                                <div><span class="text-slate-400 font-bold">Grupo Sang.:</span> <span class="font-bold text-slate-700">{{ student.profile?.blood_type || '—' }}</span></div>
                                <div><span class="text-slate-400 font-bold">Talla / Peso:</span> <span class="font-bold text-slate-700">{{ student.profile?.height ? `${student.profile.height} cm` : '—' }} / {{ student.profile?.weight ? `${student.profile.weight} kg` : '—' }}</span></div>
                                <div><span class="text-slate-400 font-bold">Medic. Ed. Física:</span> <span class="font-bold">{{ student.profile?.requires_pe_medication ? 'Sí requiere' : 'No' }}</span></div>
                                <div><span class="text-slate-400 font-bold">Constancia Médica:</span> <span class="font-bold">{{ student.profile?.has_medical_certificate ? 'Consignada' : 'No' }}</span></div>
                            </div>
                        </div>

                        <!-- 4. Vivienda y Comunidad -->
                        <div class="bg-slate-50/70 border border-slate-200/80 rounded-2xl p-4 space-y-2.5">
                            <h4 class="text-xs font-black text-slate-700 uppercase tracking-wider flex items-center gap-2">
                                <i class="fas fa-home text-primary-500"></i> Vivienda y Comunidad
                            </h4>
                            <div class="space-y-1.5 text-xs">
                                <div><span class="text-slate-400 font-bold">Vivienda:</span> <span class="font-bold text-slate-700">{{ student.profile?.housing_type || '—' }} ({{ student.profile?.housing_tenure || '—' }})</span></div>
                                <div><span class="text-slate-400 font-bold">Estado:</span> <span class="font-bold text-slate-700">{{ student.profile?.housing_condition || '—' }}</span></div>
                                <div>
                                    <span class="text-slate-400 font-bold">Servicios:</span>
                                    <div class="flex flex-wrap gap-1 mt-1">
                                        <span v-if="student.profile?.has_water" class="px-2 py-0.5 bg-cyan-100 text-cyan-800 rounded text-[10px] font-bold">Agua</span>
                                        <span v-if="student.profile?.has_electricity" class="px-2 py-0.5 bg-amber-100 text-amber-800 rounded text-[10px] font-bold">Luz</span>
                                        <span v-if="student.profile?.has_internet" class="px-2 py-0.5 bg-indigo-100 text-indigo-800 rounded text-[10px] font-bold">Internet</span>
                                    </div>
                                </div>
                                <div class="pt-1"><span class="text-slate-400 font-bold">Consejo Comunal:</span> <span class="font-bold">{{ student.profile?.belongs_to_communal_council ? 'Sí' : 'No' }}</span></div>
                                <div><span class="text-slate-400 font-bold">Instituciones:</span> <span class="font-bold text-slate-700">{{ student.profile?.community_institutions?.length ? student.profile.community_institutions.join(', ') : '—' }}</span></div>
                            </div>
                        </div>
                    </div>

                    <!-- Ficha Ampliada del Representante Legal -->
                    <div v-if="student.guardian" class="bg-slate-50/70 border border-slate-200/80 rounded-2xl p-5 space-y-3">
                        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200/60 pb-3">
                            <h4 class="text-xs font-black text-slate-800 uppercase tracking-wider flex items-center gap-2">
                                <i class="fas fa-user-tie text-primary-500"></i> Ficha del Representante Legal
                            </h4>
                            <span v-if="student.guardian.relationship" class="px-2.5 py-1 rounded-lg text-[10px] font-black bg-primary-50 text-primary-700 border border-primary-100 uppercase">
                                Parentesco: {{ student.guardian.relationship }}
                            </span>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-xs">
                            <div>
                                <span class="text-slate-400 font-bold block text-[10px] uppercase">Nombres y Apellidos</span>
                                <span class="font-black text-slate-800 text-sm">{{ student.guardian.name || (student.guardian.first_name + ' ' + student.guardian.last_name) }}</span>
                            </div>
                            <div>
                                <span class="text-slate-400 font-bold block text-[10px] uppercase">Cédula de Identidad</span>
                                <span class="font-black text-slate-800 text-sm">{{ student.guardian.cedula }}</span>
                            </div>
                            <div>
                                <span class="text-slate-400 font-bold block text-[10px] uppercase">Teléfono Móvil</span>
                                <span class="font-bold text-slate-700">{{ student.guardian.phone || '—' }}</span>
                            </div>
                            <div>
                                <span class="text-slate-400 font-bold block text-[10px] uppercase">Teléfono Fijo</span>
                                <span class="font-bold text-slate-700">{{ student.guardian.phone_home || '—' }}</span>
                            </div>
                            <div>
                                <span class="text-slate-400 font-bold block text-[10px] uppercase">Nivel de Instrucción</span>
                                <span class="font-bold text-slate-700">{{ student.guardian.education_level || '—' }}</span>
                            </div>
                            <div>
                                <span class="text-slate-400 font-bold block text-[10px] uppercase">Profesión u Ocupación</span>
                                <span class="font-bold text-slate-700">{{ student.guardian.occupation || '—' }}</span>
                            </div>
                            <div>
                                <span class="text-slate-400 font-bold block text-[10px] uppercase">Situación Laboral</span>
                                <span class="font-bold text-slate-700">{{ student.guardian.is_working ? `Trabaja (${student.guardian.work_sector || 'N/E'} - ${student.guardian.work_type || 'N/E'})` : 'No trabaja' }}</span>
                            </div>
                            <div>
                                <span class="text-slate-400 font-bold block text-[10px] uppercase">Dirección de Habitación</span>
                                <span class="font-bold text-slate-700">{{ student.guardian.address || '—' }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Expediente de Recaudos y Consignación -->
                    <div class="bg-slate-50/70 border border-slate-200/80 rounded-2xl p-5 space-y-3">
                        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200/60 pb-3">
                            <h4 class="text-xs font-black text-slate-800 uppercase tracking-wider flex items-center gap-2">
                                <i class="fas fa-folder-open text-primary-500"></i> Expediente de Recaudos y Consignación
                            </h4>
                            <span
                                v-if="student.profile?.documents_consignment?.length"
                                class="px-2.5 py-1 rounded-lg text-[10px] font-black uppercase"
                                :class="student.profile.documents_consignment.filter(d => d.is_mandatory && !d.delivered).length === 0 ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : 'bg-amber-100 text-amber-800 border border-amber-200'"
                            >
                                {{ student.profile.documents_consignment.filter(d => d.is_mandatory && !d.delivered).length === 0 ? 'Expediente Completo' : 'Recaudos Pendientes' }}
                            </span>
                        </div>
                        <div v-if="student.profile?.documents_consignment?.length" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3 text-xs">
                            <div
                                v-for="doc in student.profile.documents_consignment"
                                :key="doc.code"
                                class="p-3 rounded-xl border bg-white flex flex-col justify-between gap-2 shadow-2xs"
                                :class="doc.delivered ? 'border-emerald-200' : 'border-slate-200'"
                            >
                                <div class="flex items-start justify-between gap-2">
                                    <span class="font-bold text-slate-800 text-[11px]">{{ doc.name }}</span>
                                    <span
                                        class="px-2 py-0.5 rounded text-[9px] font-black uppercase shrink-0"
                                        :class="doc.delivered ? 'bg-emerald-100 text-emerald-800' : (doc.is_mandatory ? 'bg-rose-100 text-rose-800' : 'bg-slate-100 text-slate-600')"
                                    >
                                        {{ doc.delivered ? 'Consignado' : (doc.is_mandatory ? 'Debe' : 'Opcional') }}
                                    </span>
                                </div>
                                <div v-if="doc.delivered" class="text-[10px] text-slate-500">
                                    <span>Entregado: {{ doc.delivered_at }}</span>
                                    <span v-if="doc.notes" class="block italic text-slate-400 truncate">{{ doc.notes }}</span>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-xs text-slate-400 italic">
                            No se registraron recaudos consignados para este estudiante.
                        </div>
                    </div>
                </div>
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

        <!-- Modal Editar Estudiante (5 Secciones Completas) -->
        <Modal :show="showEditStudentModal" @close="showEditStudentModal = false" max-width="4xl">
            <div class="p-6 sm:p-8 max-h-[90vh] overflow-y-auto custom-scrollbar">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-2xl font-black text-slate-800">
                            Editar Ficha de <span class="gradient-text">{{ student.first_name }} {{ student.last_name }}</span>
                        </h3>
                        <p class="text-xs font-bold text-slate-400 mt-1">Expediente personal, familiar, médico, habitacional y comunitario</p>
                    </div>
                    <button @click="showEditStudentModal = false" class="w-10 h-10 rounded-full bg-slate-50 text-slate-400 hover:bg-slate-100 transition-all shadow-sm">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <!-- Tabs de navegación entre las 5 secciones -->
                <div class="flex flex-wrap gap-2 border-b border-slate-100 pb-4 mb-6">
                    <button
                        type="button"
                        @click="editTab = 1"
                        :class="editTab === 1 ? 'bg-primary-600 text-white shadow-md shadow-primary-600/20' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                        class="px-4 py-2 rounded-xl text-xs font-black uppercase tracking-wider transition-all"
                    >
                        1. Representante
                    </button>
                    <button
                        type="button"
                        @click="editTab = 2"
                        :class="editTab === 2 ? 'bg-primary-600 text-white shadow-md shadow-primary-600/20' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                        class="px-4 py-2 rounded-xl text-xs font-black uppercase tracking-wider transition-all"
                    >
                        2. Estudiante
                    </button>
                    <button
                        type="button"
                        @click="editTab = 3"
                        :class="editTab === 3 ? 'bg-primary-600 text-white shadow-md shadow-primary-600/20' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                        class="px-4 py-2 rounded-xl text-xs font-black uppercase tracking-wider transition-all"
                    >
                        3. Familiar
                    </button>
                    <button
                        type="button"
                        @click="editTab = 4"
                        :class="editTab === 4 ? 'bg-primary-600 text-white shadow-md shadow-primary-600/20' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                        class="px-4 py-2 rounded-xl text-xs font-black uppercase tracking-wider transition-all"
                    >
                        4. Salud
                    </button>
                    <button
                        type="button"
                        @click="editTab = 5"
                        :class="editTab === 5 ? 'bg-primary-600 text-white shadow-md shadow-primary-600/20' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                        class="px-4 py-2 rounded-xl text-xs font-black uppercase tracking-wider transition-all"
                    >
                        5. Vivienda y Comunidad
                    </button>
                    <button
                        type="button"
                        @click="editTab = 6"
                        :class="editTab === 6 ? 'bg-primary-600 text-white shadow-md shadow-primary-600/20' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                        class="px-4 py-2 rounded-xl text-xs font-black uppercase tracking-wider transition-all"
                    >
                        6. Recaudos
                    </button>
                </div>

                <form novalidate @submit.prevent="submitStudentEdit" class="space-y-6">
                    <!-- Tab 1: Representante Legal -->
                    <div v-show="editTab === 1">
                        <SectionRepresentante
                            v-model="studentForm.guardian_id"
                            :initial-guardian="student.guardian"
                            :error="studentForm.errors.guardian_id"
                            @update:guardian="selectedGuardian = $event"
                        />
                    </div>

                    <!-- Tab 2: Información del Estudiante -->
                    <div v-show="editTab === 2" class="space-y-5">
                        <div class="space-y-1.5 p-4 bg-slate-50 border-2 border-slate-200/80 rounded-2xl">
                            <label class="text-[11px] font-black text-slate-500 uppercase tracking-wider ml-1">Estatus del Alumno</label>
                            <select
                                v-model="studentForm.status"
                                class="w-full bg-white border-2 border-slate-200 rounded-xl px-4 py-2.5 text-slate-800 text-xs font-bold focus:border-primary-400 outline-none"
                            >
                                <option value="regular">Regular</option>
                                <option value="withdrawn">Retirado</option>
                                <option value="suspended">Suspendido</option>
                                <option value="graduated">Graduado</option>
                            </select>
                        </div>
                        <SectionPrincipal
                            :form="studentForm"
                            :communities="communities"
                            :grade-levels="gradeLevels"
                            :guardian="selectedGuardian || student.guardian"
                            :student-id="student.id"
                            :has-enrollments="Boolean(enrollments && enrollments.length > 0)"
                        />
                    </div>

                    <!-- Tab 3: Familiar -->
                    <div v-show="editTab === 3">
                        <SectionAdicional :form="studentForm" />
                    </div>

                    <!-- Tab 4: Salud -->
                    <div v-show="editTab === 4">
                        <SectionMedica :form="studentForm" />
                    </div>

                    <!-- Tab 5: Vivienda y Comunidad (Acordeón) -->
                    <div v-show="editTab === 5" class="space-y-6">
                        <!-- Acordeón 1: Habitación y Vivienda -->
                        <div class="border-2 border-slate-200/80 rounded-3xl overflow-hidden bg-white shadow-sm transition-all">
                            <button
                                type="button"
                                @click="isEditHousingOpen = !isEditHousingOpen"
                                class="w-full text-left bg-slate-50/80 hover:bg-slate-100/80 px-4 sm:px-6 py-3.5 sm:py-4 flex items-center justify-between gap-3 transition-colors select-none"
                                :class="{ 'border-b-2 border-slate-100': isEditHousingOpen }"
                            >
                                <div class="flex items-center gap-3.5 min-w-0">
                                    <div
                                        class="hidden sm:flex w-10 h-10 rounded-2xl items-center justify-center font-black text-sm shadow-sm shrink-0 transition-colors"
                                        :class="isEditHousingOpen ? 'bg-primary-600 text-white shadow-primary-500/20' : 'bg-slate-200 text-slate-500'"
                                    >
                                        <i class="fas fa-home"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h4 class="text-sm sm:text-base font-black text-slate-800">5.1 Habitación y Servicios Básicos</h4>
                                            <span
                                                class="text-[10px] font-black uppercase tracking-wider px-2 py-0.5 rounded-lg shrink-0"
                                                :class="isEditHousingOpen ? 'bg-primary-50 text-primary-600' : 'bg-slate-200 text-slate-600'"
                                            >
                                                {{ isEditHousingOpen ? 'Abierto' : 'Plegado' }}
                                            </span>
                                        </div>
                                        <p class="text-xs text-slate-400 font-bold mt-0.5">Tipo de inmueble, tenencia, condición física y agua/luz/internet</p>
                                    </div>
                                </div>

                                <div
                                    class="w-8 h-8 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-slate-400 shadow-sm transition-transform duration-200 shrink-0"
                                    :class="{ 'rotate-180': isEditHousingOpen }"
                                >
                                    <i class="fas fa-chevron-down text-xs"></i>
                                </div>
                            </button>

                            <div v-show="isEditHousingOpen" class="p-3.5 sm:p-7">
                                <SectionHabitacion :form="studentForm" :hide-header="true" />
                            </div>
                        </div>

                        <!-- Acordeón 2: Comunidad y Entorno Social -->
                        <div class="border-2 border-slate-200/80 rounded-3xl overflow-hidden bg-white shadow-sm transition-all">
                            <button
                                type="button"
                                @click="isEditCommunityOpen = !isEditCommunityOpen"
                                class="w-full text-left bg-slate-50/80 hover:bg-slate-100/80 px-4 sm:px-6 py-3.5 sm:py-4 flex items-center justify-between gap-3 transition-colors select-none"
                                :class="{ 'border-b-2 border-slate-100': isEditCommunityOpen }"
                            >
                                <div class="flex items-center gap-3.5 min-w-0">
                                    <div
                                        class="hidden sm:flex w-10 h-10 rounded-2xl items-center justify-center font-black text-sm shadow-sm shrink-0 transition-colors"
                                        :class="isEditCommunityOpen ? 'bg-primary-600 text-white shadow-primary-500/20' : 'bg-slate-200 text-slate-500'"
                                    >
                                        <i class="fas fa-city"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h4 class="text-sm sm:text-base font-black text-slate-800">5.2 Comunidad y Entorno Social</h4>
                                            <span
                                                class="text-[10px] font-black uppercase tracking-wider px-2 py-0.5 rounded-lg shrink-0"
                                                :class="isEditCommunityOpen ? 'bg-primary-50 text-primary-600' : 'bg-slate-200 text-slate-600'"
                                            >
                                                {{ isEditCommunityOpen ? 'Abierto' : 'Plegado' }}
                                            </span>
                                        </div>
                                        <p class="text-xs text-slate-400 font-bold mt-0.5">Consejo comunal, servicios comunitarios e instituciones del entorno</p>
                                    </div>
                                </div>

                                <div
                                    class="w-8 h-8 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-slate-400 shadow-sm transition-transform duration-200 shrink-0"
                                    :class="{ 'rotate-180': isEditCommunityOpen }"
                                >
                                    <i class="fas fa-chevron-down text-xs"></i>
                                </div>
                            </button>

                            <div v-show="isEditCommunityOpen" class="p-3.5 sm:p-7">
                                <SectionComunidad :form="studentForm" :hide-header="true" />
                            </div>
                        </div>
                    </div>

                    <!-- Tab 6: Recaudos y Consignación -->
                    <div v-show="editTab === 6">
                        <SectionRecaudos :form="studentForm" />
                    </div>

                    <div class="flex items-center justify-between gap-4 pt-6 border-t border-slate-100">
                        <button
                            type="button"
                            @click="showEditStudentModal = false"
                            class="px-6 py-3 text-xs font-black uppercase tracking-widest text-slate-400 hover:text-slate-600 transition-all"
                        >
                            Cancelar
                        </button>

                        <button
                            type="submit"
                            :disabled="studentForm.processing"
                            class="px-8 py-3 bg-primary-600 text-white text-xs font-black uppercase tracking-widest rounded-2xl shadow-lg shadow-primary-600/20 hover:bg-primary-500 transition-all disabled:opacity-50 flex items-center gap-2"
                        >
                            <i v-if="studentForm.processing" class="fas fa-circle-notch fa-spin"></i>
                            <i v-else class="fas fa-save"></i>
                            Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        </div>
    </AppLayout>
</template>