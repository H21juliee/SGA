<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import axios from 'axios'
import Swal from 'sweetalert2'
import AppLayout from '@/Components/Layout/AppLayout.vue'
import SectionRepresentante from './Partials/SectionRepresentante.vue'
import SectionPrincipal from './Partials/SectionPrincipal.vue'
import SectionAdicional from './Partials/SectionAdicional.vue'
import SectionMedica from './Partials/SectionMedica.vue'
import SectionHabitacion from './Partials/SectionHabitacion.vue'
import SectionComunidad from './Partials/SectionComunidad.vue'
import SectionRecaudos from './Partials/SectionRecaudos.vue'
import ConfirmationModal from './Partials/ConfirmationModal.vue'

const props = defineProps({
    communities: { type: Array, default: () => [] },
})

const currentStep = ref(1)
const selectedGuardian = ref(null)
const isHousingOpen = ref(true)
const isCommunityOpen = ref(true)
const showConfirmationModal = ref(false)

const steps = [
    { number: 1, title: 'Representante', subtitle: 'Ficha y Contacto', icon: 'fa-user-tie' },
    { number: 2, title: 'Estudiante', subtitle: 'Identidad y Contacto', icon: 'fa-id-card' },
    { number: 3, title: 'Familiar', subtitle: 'Entorno y Recursos', icon: 'fa-users' },
    { number: 4, title: 'Salud', subtitle: 'Médica y Medidas', icon: 'fa-heartbeat' },
    { number: 5, title: 'Vivienda y Comunidad', subtitle: 'Servicios y Entorno', icon: 'fa-home' },
    { number: 6, title: 'Recaudos', subtitle: 'Consignación y Control', icon: 'fa-folder-open' },
]

const form = useForm({
    // Principal
    first_name: '',
    last_name: '',
    nationality: 'V',
    cedula: '',
    birth_date: '',
    birth_place: '',
    gender: 'M',
    address: '',
    community_id: '',
    email: '',
    phone_home: '',
    phone_mobile: '',
    guardian_id: null,

    // Perfil completo
    profile: {
        // Adicional
        lives_with: [],
        family_group_count: null,
        religion: '',
        extracurricular_activities: false,
        extracurricular_activities_detail: '',
        has_canaima: false,
        uses_canteen: false,

        // Médica
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

        // Habitación
        housing_type: '',
        housing_tenure: '',
        housing_condition: '',
        has_water: false,
        has_electricity: false,
        has_internet: false,
        additional_services: '',

        // Comunidad
        community_name: '',
        belongs_to_communal_council: false,
        functioning_services: [],
        services_quality: '',
        community_institutions: [],

        // Recaudos y Consignación
        documents_consignment: [],
    },
})

// Validación antes de avanzar de paso
const stepErrors = ref({})

function validateCurrentStep() {
    stepErrors.value = {}
    let valid = true

    // Paso 1: Representante Legal
    if (currentStep.value === 1) {
        if (!form.guardian_id) {
            form.setError('guardian_id', 'Debe buscar y seleccionar un representante existente o registrar uno nuevo para continuar.')
            valid = false
        }
    }

    // Paso 2: Información del Estudiante
    if (currentStep.value === 2) {
        if (!form.first_name?.trim()) {
            form.setError('first_name', 'El nombre del estudiante es obligatorio.')
            valid = false
        }
        if (!form.last_name?.trim()) {
            form.setError('last_name', 'El apellido del estudiante es obligatorio.')
            valid = false
        }
        if (!form.nationality) {
            form.setError('nationality', 'La nacionalidad es obligatoria.')
            valid = false
        }
        if (!form.birth_date) {
            form.setError('birth_date', 'La fecha de nacimiento es obligatoria.')
            valid = false
        }
        if (!form.birth_place?.trim()) {
            form.setError('birth_place', 'El lugar de nacimiento es obligatorio.')
            valid = false
        }
        if (!form.address?.trim()) {
            form.setError('address', 'La dirección del estudiante es obligatoria.')
            valid = false
        }
        if (!form.community_id) {
            form.setError('community_id', 'Debe seleccionar una comunidad para el estudiante.')
            valid = false
        }

        const venezuelanPhoneRegex = /^(0412|0414|0424|0416|0426|02\d{2})-\d{7}$/

        // Cédula / Documento (opcional, pero si se escribe debe ser válido)
        if (form.cedula && form.cedula.trim()) {
            const c = form.cedula.trim().toUpperCase()
            const isVOrE = /^[VE]-\d{6,9}$/.test(c)
            const isCE = /^[VE][1-9]\d{10}$/.test(c) || /^CE-\d{10,15}$/.test(c)
            const isP = /^P-[A-Z0-9]{6,15}$/.test(c)
            if (!isVOrE && !isCE && !isP) {
                if (/^[VE]/.test(c) && !c.includes('-')) {
                    form.setError('cedula', 'La Cédula Escolar debe tener 12 caracteres oficiales MPPE (ej: V11214200350: letra + orden parto + año + cédula representante).')
                } else {
                    form.setError('cedula', 'El documento no tiene un formato válido (Cédula V-/E- de 6 a 9 dígitos, Cédula Escolar oficial o Pasaporte).')
                }
                valid = false
            }
        }

        // Teléfono móvil del estudiante (opcional, pero si se escribe debe tener formato venezolano)
        if (form.phone_mobile && form.phone_mobile.trim()) {
            if (!venezuelanPhoneRegex.test(form.phone_mobile.trim())) {
                form.setError('phone_mobile', 'El teléfono móvil debe tener un formato válido en Venezuela (ej: 0412-1234567, 0414-1234567).')
                valid = false
            }
        }

        // Teléfono fijo del estudiante (opcional, pero si se escribe debe tener formato venezolano)
        if (form.phone_home && form.phone_home.trim()) {
            if (!venezuelanPhoneRegex.test(form.phone_home.trim())) {
                form.setError('phone_home', 'El teléfono fijo debe tener un código de área válido (ej: 0212-1234567).')
                valid = false
            }
        }

        // Correo electrónico
        if (form.email && form.email.trim()) {
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email.trim())) {
                form.setError('email', 'El correo electrónico no tiene un formato válido.')
                valid = false
            }
        }
    }

    // Paso 3: Ficha Familiar
    if (currentStep.value === 3) {
        if (form.profile.extracurricular_activities && !form.profile.extracurricular_activities_detail?.trim()) {
            form.setError('profile.extracurricular_activities_detail', 'Indique la actividad que practica.')
            valid = false
        }
    }

    // Paso 4: Salud y Médica
    if (currentStep.value === 4) {
        if (form.profile.has_medical_condition && !form.profile.medical_condition_detail?.trim()) {
            form.setError('profile.medical_condition_detail', 'Indique la enfermedad o tratamiento.')
            valid = false
        }
        if (form.profile.has_allergies && !form.profile.allergies_detail?.trim()) {
            form.setError('profile.allergies_detail', 'Indique a qué es alérgico.')
            valid = false
        }

        // Talla (estatura en cm: 90 - 230 cm, máximo 2 decimales)
        if (form.profile.height !== '' && form.profile.height !== null && form.profile.height !== undefined) {
            const rawH = String(form.profile.height).trim().replace(',', '.')
            if (rawH.includes('.')) {
                const dec = rawH.split('.')[1]
                if (dec && dec.length > 2) {
                    form.setError('profile.height', 'La estatura solo permite hasta 2 cifras decimales (ej: 165.50 cm).')
                    valid = false
                }
            }
            const h = parseFloat(rawH)
            if (isNaN(h)) {
                form.setError('profile.height', 'La estatura debe ser un valor numérico.')
                valid = false
            } else if (h > 0 && h <= 2.5) {
                form.setError('profile.height', 'Indique la estatura en centímetros (ej: 165 cm).')
                valid = false
            } else if (h < 90 || h > 230) {
                form.setError('profile.height', 'La estatura debe estar entre 90 y 230 cm.')
                valid = false
            }
        }

        // Peso corporal (en kg: 20 - 200 kg, máximo 2 decimales)
        if (form.profile.weight !== '' && form.profile.weight !== null && form.profile.weight !== undefined) {
            const rawW = String(form.profile.weight).trim().replace(',', '.')
            if (rawW.includes('.')) {
                const dec = rawW.split('.')[1]
                if (dec && dec.length > 2) {
                    form.setError('profile.weight', 'El peso solo permite hasta 2 cifras decimales (ej: 63.75 kg).')
                    valid = false
                }
            }
            const w = parseFloat(rawW)
            if (isNaN(w)) {
                form.setError('profile.weight', 'El peso debe ser un valor numérico.')
                valid = false
            } else if (w < 20 || w > 200) {
                form.setError('profile.weight', 'El peso corporal debe estar entre 20 y 200 kg.')
                valid = false
            }
        }
    }

    return valid
}

const checkingStep2 = ref(false)

async function verifyStep2Uniqueness() {
    if (!form.cedula || !form.cedula.trim()) {
        return true
    }
    checkingStep2.value = true
    try {
        const { data } = await axios.get('/admin/students/check-cedula', {
            params: { cedula: form.cedula.trim() }
        })
        if (data.exists) {
            form.setError('cedula', data.message)
            Swal.fire({
                icon: 'error',
                title: 'Cédula Ya Registrada',
                html: `<p class="text-sm text-slate-600">${data.message}</p><p class="text-xs text-slate-400 mt-2 font-bold">No puede registrar a dos estudiantes con el mismo documento de identidad.</p>`,
                confirmButtonColor: '#2563eb',
                confirmButtonText: 'Entendido'
            })
            return false
        }
        return true
    } catch (e) {
        console.error('Error al verificar cédula en el servidor', e)
        return true
    } finally {
        checkingStep2.value = false
    }
}

async function nextStep() {
    if (!validateCurrentStep()) {
        return
    }

    if (currentStep.value === 2) {
        const isUnique = await verifyStep2Uniqueness()
        if (!isUnique) {
            return
        }
    }

    if (currentStep.value < 6) {
        currentStep.value++
        window.scrollTo({ top: 0, behavior: 'smooth' })
    }
}

function prevStep() {
    if (currentStep.value > 1) {
        currentStep.value--
        window.scrollTo({ top: 0, behavior: 'smooth' })
    }
}

async function goToStep(num) {
    if (num < currentStep.value) {
        currentStep.value = num
        window.scrollTo({ top: 0, behavior: 'smooth' })
    } else if (num > currentStep.value) {
        if (!validateCurrentStep()) return
        if (currentStep.value === 2) {
            const isUnique = await verifyStep2Uniqueness()
            if (!isUnique) return
        }
        currentStep.value = num
        window.scrollTo({ top: 0, behavior: 'smooth' })
    }
}

// --- Persistencia y Auto-guardado de Borrador (localStorage) ---
const DRAFT_KEY = 'sge_student_create_draft_v1'
const pendingDraft = ref(null)
const showDraftBanner = ref(false)
const lastSavedTime = ref(null)
let autoSaveTimer = null

const hasAnyData = computed(() => {
    return Boolean(
        form.guardian_id ||
        selectedGuardian.value ||
        form.first_name?.trim() ||
        form.last_name?.trim() ||
        form.cedula?.trim() ||
        form.birth_date ||
        form.birth_place?.trim() ||
        form.address?.trim() ||
        form.phone_mobile?.trim() ||
        form.email?.trim() ||
        form.profile?.extracurricular_activities ||
        form.profile?.has_medical_condition ||
        form.profile?.housing_type ||
        form.profile?.community_name
    )
})

const draftSummary = computed(() => {
    if (!pendingDraft.value) return ''
    const d = pendingDraft.value
    const parts = []
    if (d.selectedGuardian) {
        parts.push(`Representante: ${d.selectedGuardian.first_name || ''} ${d.selectedGuardian.last_name || ''}`.trim())
    }
    const studentName = `${d.formData?.first_name || ''} ${d.formData?.last_name || ''}`.trim()
    if (studentName) {
        parts.push(`Estudiante: ${studentName}`)
    }
    return parts.join(' | ') || 'Ficha en proceso'
})

const draftTimeFormatted = computed(() => {
    if (!pendingDraft.value?.savedAt) return ''
    try {
        const date = new Date(pendingDraft.value.savedAt)
        return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) + ' (' + date.toLocaleDateString() + ')'
    } catch {
        return ''
    }
})

function saveDraft() {
    if (!hasAnyData.value) return
    try {
        const draft = {
            savedAt: new Date().toISOString(),
            currentStep: currentStep.value,
            selectedGuardian: selectedGuardian.value,
            formData: {
                first_name: form.first_name,
                last_name: form.last_name,
                nationality: form.nationality,
                cedula: form.cedula,
                birth_date: form.birth_date,
                birth_place: form.birth_place,
                gender: form.gender,
                address: form.address,
                community_id: form.community_id,
                email: form.email,
                phone_home: form.phone_home,
                phone_mobile: form.phone_mobile,
                guardian_id: form.guardian_id,
                profile: JSON.parse(JSON.stringify(form.profile || {})),
            }
        }
        localStorage.setItem(DRAFT_KEY, JSON.stringify(draft))
        const now = new Date()
        lastSavedTime.value = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
    } catch (e) {
        console.warn('No se pudo guardar el borrador en localStorage', e)
    }
}

function clearDraft() {
    try {
        localStorage.removeItem(DRAFT_KEY)
    } catch (e) {}
    pendingDraft.value = null
    showDraftBanner.value = false
}

function restoreDraft() {
    if (!pendingDraft.value) return
    const d = pendingDraft.value

    if (d.formData) {
        Object.keys(d.formData).forEach((key) => {
            if (key === 'profile' && d.formData.profile) {
                Object.assign(form.profile, d.formData.profile)
            } else if (key in form) {
                form[key] = d.formData[key]
            }
        })
    }

    if (d.selectedGuardian) {
        selectedGuardian.value = d.selectedGuardian
        form.guardian_id = d.selectedGuardian.id || form.guardian_id
    }

    if (d.currentStep && d.currentStep >= 1 && d.currentStep <= 6) {
        currentStep.value = d.currentStep
    }

    showDraftBanner.value = false
    const now = new Date()
    lastSavedTime.value = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })

    Swal.fire({
        icon: 'success',
        title: 'Borrador Restaurado',
        text: 'Se han recuperado todos los datos guardados de tu sesión previa.',
        timer: 2500,
        showConfirmButton: false,
        toast: true,
        position: 'top-end',
    })
}

function discardDraft() {
    clearDraft()
    Swal.fire({
        icon: 'info',
        title: 'Borrador descartado',
        text: 'El formulario continuará en blanco.',
        timer: 2000,
        showConfirmButton: false,
        toast: true,
        position: 'top-end',
    })
}

function confirmDiscardAll() {
    Swal.fire({
        title: '¿Reiniciar formulario?',
        text: 'Se borrarán los datos ingresados y el borrador guardado en este navegador.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Sí, reiniciar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            clearDraft()
            form.reset()
            selectedGuardian.value = null
            currentStep.value = 1
            lastSavedTime.value = null
            Swal.fire({
                icon: 'success',
                title: 'Formulario en blanco',
                text: 'Puedes comenzar un nuevo registro.',
                timer: 1500,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            })
        }
    })
}

onMounted(() => {
    try {
        const raw = localStorage.getItem(DRAFT_KEY)
        if (raw) {
            const parsed = JSON.parse(raw)
            if (parsed && (parsed.selectedGuardian || parsed.formData?.first_name || parsed.formData?.guardian_id || parsed.formData?.last_name || parsed.formData?.cedula)) {
                pendingDraft.value = parsed
                showDraftBanner.value = true
            }
        }
    } catch (e) {
        console.warn('Error leyendo borrador', e)
    }
})

onBeforeUnmount(() => {
    if (autoSaveTimer) clearTimeout(autoSaveTimer)
})

watch(
    [
        () => form.first_name,
        () => form.last_name,
        () => form.nationality,
        () => form.cedula,
        () => form.birth_date,
        () => form.birth_place,
        () => form.gender,
        () => form.address,
        () => form.community_id,
        () => form.email,
        () => form.phone_home,
        () => form.phone_mobile,
        () => form.guardian_id,
        () => form.profile,
        selectedGuardian,
        currentStep
    ],
    () => {
        if (showDraftBanner.value) return

        if (autoSaveTimer) clearTimeout(autoSaveTimer)
        autoSaveTimer = setTimeout(() => {
            saveDraft()
        }, 600)
    },
    { deep: true }
)

function handleFormSubmit() {
    if (!validateCurrentStep()) {
        return
    }
    showConfirmationModal.value = true
}

function confirmAndSubmit() {
    showConfirmationModal.value = false
    submit()
}

function submit() {
    if (!validateCurrentStep()) {
        return
    }
    form.post('/admin/students', {
        onSuccess: () => {
            clearDraft()
        },
        onError: (errs) => {
            // Si hay errores en campos de pasos anteriores, saltar al paso correspondiente
            const keys = Object.keys(errs)
            if (keys.some(k => k === 'guardian_id')) {
                currentStep.value = 1
            } else if (keys.some(k => ['first_name', 'last_name', 'cedula', 'birth_date', 'birth_place', 'address', 'community_id', 'nationality', 'phone_mobile', 'phone_home', 'email'].includes(k))) {
                currentStep.value = 2
            } else if (keys.some(k => k.startsWith('profile.extracurricular') || k.startsWith('profile.lives_with') || k.startsWith('profile.family_group_count'))) {
                currentStep.value = 3
            } else if (keys.some(k => k.startsWith('profile.medical') || k.startsWith('profile.allergies') || k.startsWith('profile.blood_type') || k.startsWith('profile.height') || k.startsWith('profile.weight'))) {
                currentStep.value = 4
            } else if (keys.some(k => k.startsWith('profile.housing') || k.startsWith('profile.community') || k.startsWith('profile.has_'))) {
                currentStep.value = 5
            } else if (keys.some(k => k.startsWith('profile.documents_consignment'))) {
                currentStep.value = 6
            }
        },
    })
}
</script>

<template>
    <AppLayout title="Ficha de Registro de Estudiante">
        <div class="max-w-12xl mx-auto space-y-6 pb-12">
            <!-- Barra Superior y Breadcrumb -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 animate-fade-in-up">
                <div>
                    <div class="flex items-center gap-2 text-xs font-black text-slate-400 uppercase tracking-widest mb-1.5">
                        <Link href="/admin/students" class="hover:text-primary-600 transition-colors">Directorio</Link>
                        <span>/</span>
                        <span class="text-primary-600">Nuevo Estudiante</span>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <h1 class="text-3xl font-extrabold text-slate-800">
                            Registro Integral de <span class="gradient-text">Estudiante</span>
                        </h1>
                        <span v-if="lastSavedTime" class="inline-flex items-center gap-1.5 px-3 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-full text-[11px] font-bold shadow-xs">
                            <i class="fas fa-check-circle text-emerald-500"></i>
                            Guardado auto {{ lastSavedTime }}
                        </span>
                    </div>
                    <p class="text-slate-400 text-xs font-bold mt-1">Ficha socioeducativa, médica y comunitaria</p>
                </div>

                <div class="flex items-center gap-2 self-start sm:self-auto flex-wrap">
                    <button
                        v-if="hasAnyData"
                        type="button"
                        @click="confirmDiscardAll"
                        class="px-4 py-2.5 rounded-2xl bg-white border-2 border-rose-200 text-rose-600 hover:text-rose-700 hover:bg-rose-50 text-xs font-black uppercase tracking-wider transition-all shadow-sm flex items-center gap-2"
                        title="Borrar todos los datos y reiniciar el formulario"
                    >
                        <i class="fas fa-trash-alt text-xs"></i> Reiniciar
                    </button>
                    <Link
                        href="/admin/students"
                        class="px-5 py-2.5 rounded-2xl bg-white border-2 border-slate-200 text-slate-600 hover:text-slate-800 hover:bg-slate-50 text-xs font-black uppercase tracking-wider transition-all shadow-sm flex items-center gap-2"
                    >
                        <i class="fas fa-arrow-left"></i> Volver al Directorio
                    </Link>
                </div>
            </div>

            <!-- Banner de Recuperación de Borrador -->
            <transition enter-active-class="transition duration-300 ease-out" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0">
                <div v-if="showDraftBanner" class="bg-gradient-to-r from-amber-50 to-orange-50 border-2 border-amber-300/80 rounded-3xl p-4 sm:p-5 shadow-md flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-start sm:items-center gap-3.5">
                        <div class="w-11 h-11 rounded-2xl bg-amber-500 text-white flex items-center justify-center shrink-0 shadow-sm shadow-amber-500/30">
                            <i class="fas fa-history text-lg"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-xs font-black uppercase tracking-wider text-amber-900">Borrador pendiente detectado</span>
                                <span class="px-2 py-0.5 rounded-full bg-amber-200 text-amber-900 text-[10px] font-black">Paso {{ pendingDraft?.currentStep || 1 }}</span>
                            </div>
                            <p class="text-xs text-amber-800 font-semibold mt-0.5">
                                Guardado el <span class="font-bold">{{ draftTimeFormatted }}</span>. {{ draftSummary }}
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2.5 self-end sm:self-auto shrink-0">
                        <button
                            type="button"
                            @click="restoreDraft"
                            class="px-4 py-2 rounded-xl bg-amber-600 hover:bg-amber-700 active:scale-95 text-white font-black text-xs shadow-md shadow-amber-600/30 transition-all flex items-center gap-2"
                        >
                            <i class="fas fa-arrow-rotate-left"></i> Restaurar
                        </button>
                        <button
                            type="button"
                            @click="discardDraft"
                            class="px-3.5 py-2 rounded-xl bg-white hover:bg-slate-100 text-slate-600 border border-slate-300 font-bold text-xs transition-all"
                        >
                            Descartar
                        </button>
                    </div>
                </div>
            </transition>

            <!-- Wizard Stepper Indicator -->
            <div class="glass-card rounded-3xl p-5 shadow-lg border border-slate-100">
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                    <button
                        v-for="s in steps"
                        :key="s.number"
                        type="button"
                        @click="goToStep(s.number)"
                        class="p-3 rounded-2xl flex items-center gap-3 transition-all text-left group"
                        :class="[
                            currentStep === s.number
                                ? 'bg-primary-600 text-white shadow-md shadow-primary-600/30'
                                : currentStep > s.number
                                    ? 'bg-emerald-50 text-emerald-800 border border-emerald-200 hover:bg-emerald-100/70'
                                    : 'bg-slate-50 text-slate-400 hover:bg-slate-100 border border-slate-200/60'
                        ]"
                    >
                        <div
                            class="w-10 h-10 rounded-xl flex items-center justify-center font-black text-sm shrink-0 transition-transform group-hover:scale-105"
                            :class="[
                                currentStep === s.number
                                    ? 'bg-white/20 text-white'
                                    : currentStep > s.number
                                        ? 'bg-emerald-500 text-white'
                                        : 'bg-slate-200 text-slate-500'
                            ]"
                        >
                            <i v-if="currentStep > s.number" class="fas fa-check text-xs"></i>
                            <i v-else :class="['fas', s.icon]"></i>
                        </div>
                        <div class="min-w-0">
                            <span class="block text-[10px] font-black uppercase tracking-widest opacity-80">Paso {{ s.number }}</span>
                            <span class="block text-xs font-black truncate">{{ s.title }}</span>
                        </div>
                    </button>
                </div>
            </div>

            <!-- Form Container Card -->
            <div class="bg-white rounded-3xl p-4 sm:p-10 shadow-xl border-2 border-slate-100">
                <form novalidate @submit.prevent="handleFormSubmit" class="space-y-8">
                    <!-- Paso 1: Representante Legal -->
                    <transition enter-active-class="transition duration-300 ease-out" enter-from-class="opacity-0 translate-x-4" enter-to-class="opacity-100 translate-x-0">
                        <SectionRepresentante
                            v-show="currentStep === 1"
                            v-model="form.guardian_id"
                            :initial-guardian="selectedGuardian"
                            :error="form.errors.guardian_id"
                            @update:guardian="selectedGuardian = $event"
                        />
                    </transition>

                    <!-- Paso 2: Información del Estudiante -->
                    <transition enter-active-class="transition duration-300 ease-out" enter-from-class="opacity-0 translate-x-4" enter-to-class="opacity-100 translate-x-0">
                        <SectionPrincipal
                            v-show="currentStep === 2"
                            :form="form"
                            :communities="communities"
                            :guardian="selectedGuardian"
                        />
                    </transition>

                    <!-- Paso 3: Ficha Familiar -->
                    <transition enter-active-class="transition duration-300 ease-out" enter-from-class="opacity-0 translate-x-4" enter-to-class="opacity-100 translate-x-0">
                        <SectionAdicional
                            v-show="currentStep === 3"
                            :form="form"
                        />
                    </transition>

                    <!-- Paso 4: Información Médica -->
                    <transition enter-active-class="transition duration-300 ease-out" enter-from-class="opacity-0 translate-x-4" enter-to-class="opacity-100 translate-x-0">
                        <SectionMedica
                            v-show="currentStep === 4"
                            :form="form"
                        />
                    </transition>

                    <!-- Paso 5: Habitación y Comunidad (Acordeón) -->
                    <transition enter-active-class="transition duration-300 ease-out" enter-from-class="opacity-0 translate-x-4" enter-to-class="opacity-100 translate-x-0">
                        <div v-show="currentStep === 5" class="space-y-6">
                            <!-- Encabezado del Paso 5 -->
                            <div class="border-b border-slate-100 pb-4">
                                <h3 class="text-xl font-black text-slate-800 flex items-center gap-2.5">
                                    <span class="w-8 h-8 rounded-xl bg-primary-100 text-primary-600 flex items-center justify-center text-sm font-black">5</span>
                                    Vivienda y Entorno Comunitario
                                </h3>
                                <p class="text-xs text-slate-400 font-bold mt-1 ml-10">
                                    Condiciones habitacionales, servicios básicos y organización social (despliegue en acordeón)
                                </p>
                            </div>

                            <!-- Acordeón 1: Habitación y Servicios Básicos -->
                            <div class="border-2 border-slate-200/80 rounded-3xl overflow-hidden bg-white shadow-sm transition-all">
                                <button
                                    type="button"
                                    @click="isHousingOpen = !isHousingOpen"
                                    class="w-full text-left bg-slate-50/80 hover:bg-slate-100/80 px-4 sm:px-6 py-3.5 sm:py-4 flex items-center justify-between gap-3 transition-colors select-none"
                                    :class="{ 'border-b-2 border-slate-100': isHousingOpen }"
                                >
                                    <div class="flex items-center gap-3.5 min-w-0">
                                        <div
                                            class="hidden sm:flex w-10 h-10 rounded-2xl items-center justify-center font-black text-sm shadow-sm shrink-0 transition-colors"
                                            :class="isHousingOpen ? 'bg-primary-600 text-white shadow-primary-500/20' : 'bg-slate-200 text-slate-500'"
                                        >
                                            <i class="fas fa-home"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <h4 class="text-sm sm:text-base font-black text-slate-800">5.1 Habitación y Servicios Básicos</h4>
                                                <span
                                                    class="text-[10px] font-black uppercase tracking-wider px-2 py-0.5 rounded-lg shrink-0"
                                                    :class="isHousingOpen ? 'bg-primary-50 text-primary-600' : 'bg-slate-200 text-slate-600'"
                                                >
                                                    {{ isHousingOpen ? 'Abierto' : 'Plegado' }}
                                                </span>
                                            </div>
                                            <p class="text-xs text-slate-400 font-bold mt-0.5">Tipo de inmueble, tenencia, condición física y agua/luz/internet</p>
                                        </div>
                                    </div>

                                    <div
                                        class="w-8 h-8 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-slate-400 shadow-sm transition-transform duration-200 shrink-0"
                                        :class="{ 'rotate-180': isHousingOpen }"
                                    >
                                        <i class="fas fa-chevron-down text-xs"></i>
                                    </div>
                                </button>

                                <div v-show="isHousingOpen" class="p-3.5 sm:p-7">
                                    <SectionHabitacion :form="form" :hide-header="true" />
                                </div>
                            </div>

                            <!-- Acordeón 2: Comunidad y Entorno Social -->
                            <div class="border-2 border-slate-200/80 rounded-3xl overflow-hidden bg-white shadow-sm transition-all">
                                <button
                                    type="button"
                                    @click="isCommunityOpen = !isCommunityOpen"
                                    class="w-full text-left bg-slate-50/80 hover:bg-slate-100/80 px-4 sm:px-6 py-3.5 sm:py-4 flex items-center justify-between gap-3 transition-colors select-none"
                                    :class="{ 'border-b-2 border-slate-100': isCommunityOpen }"
                                >
                                    <div class="flex items-center gap-3.5 min-w-0">
                                        <div
                                            class="hidden sm:flex w-10 h-10 rounded-2xl items-center justify-center font-black text-sm shadow-sm shrink-0 transition-colors"
                                            :class="isCommunityOpen ? 'bg-primary-600 text-white shadow-primary-500/20' : 'bg-slate-200 text-slate-500'"
                                        >
                                            <i class="fas fa-city"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <h4 class="text-sm sm:text-base font-black text-slate-800">5.2 Comunidad y Entorno Social</h4>
                                                <span
                                                    class="text-[10px] font-black uppercase tracking-wider px-2 py-0.5 rounded-lg shrink-0"
                                                    :class="isCommunityOpen ? 'bg-primary-50 text-primary-600' : 'bg-slate-200 text-slate-600'"
                                                >
                                                    {{ isCommunityOpen ? 'Abierto' : 'Plegado' }}
                                                </span>
                                            </div>
                                            <p class="text-xs text-slate-400 font-bold mt-0.5">Consejo comunal, servicios comunitarios e instituciones del entorno</p>
                                        </div>
                                    </div>

                                    <div
                                        class="w-8 h-8 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-slate-400 shadow-sm transition-transform duration-200 shrink-0"
                                        :class="{ 'rotate-180': isCommunityOpen }"
                                    >
                                        <i class="fas fa-chevron-down text-xs"></i>
                                    </div>
                                </button>

                                <div v-show="isCommunityOpen" class="p-3.5 sm:p-7">
                                    <SectionComunidad :form="form" :hide-header="true" />
                                </div>
                            </div>
                        </div>
                    </transition>

                    <!-- Paso 6: Recaudos y Consignación -->
                    <transition enter-active-class="transition duration-300 ease-out" enter-from-class="opacity-0 translate-x-4" enter-to-class="opacity-100 translate-x-0">
                        <SectionRecaudos
                            v-show="currentStep === 6"
                            :form="form"
                        />
                    </transition>

                    <!-- Acciones Inferiores (Atrás / Siguiente / Revisar y Guardar) -->
                    <div class="flex flex-col-reverse sm:flex-row items-center justify-between gap-4 pt-6 border-t-2 border-slate-100">
                        <button
                            v-if="currentStep > 1"
                            type="button"
                            @click="prevStep"
                            class="w-full sm:w-auto px-6 py-3 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-black text-xs uppercase tracking-wider transition-all flex items-center justify-center gap-2"
                        >
                            <i class="fas fa-arrow-left"></i> Anterior
                        </button>
                        <div v-else class="hidden sm:block"></div>

                        <div class="flex items-center gap-3 w-full sm:w-auto">
                            <button
                                v-if="currentStep < 6"
                                type="button"
                                @click="nextStep"
                                :disabled="checkingStep2"
                                class="w-full sm:w-auto px-8 py-3.5 rounded-2xl bg-primary-600 hover:bg-primary-500 text-white font-black text-xs uppercase tracking-widest transition-all shadow-lg shadow-primary-600/30 flex items-center justify-center gap-2 disabled:opacity-60"
                            >
                                <i v-if="checkingStep2" class="fas fa-circle-notch fa-spin"></i>
                                <span>Siguiente</span>
                                <i v-if="!checkingStep2" class="fas fa-arrow-right"></i>
                            </button>

                            <button
                                v-else
                                type="button"
                                @click="handleFormSubmit"
                                :disabled="form.processing"
                                class="w-full sm:w-auto px-10 py-3.5 rounded-2xl bg-emerald-600 hover:bg-emerald-500 active:scale-95 text-white font-black text-xs uppercase tracking-widest transition-all shadow-lg shadow-emerald-600/30 flex items-center justify-center gap-2 disabled:opacity-50"
                            >
                                <i v-if="form.processing" class="fas fa-circle-notch fa-spin"></i>
                                <i v-else class="fas fa-clipboard-check text-base"></i>
                                Revisar y Guardar Estudiante
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal de Revisión y Confirmación Final -->
        <ConfirmationModal
            :show="showConfirmationModal"
            :form="form"
            :selected-guardian="selectedGuardian"
            :communities="communities"
            :submitting="form.processing"
            @close="showConfirmationModal = false"
            @confirm="confirmAndSubmit"
            @go-to-step="goToStep"
        />
    </AppLayout>
</template>
