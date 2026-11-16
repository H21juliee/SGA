<script setup>
import { ref, computed, watch } from 'vue'
import axios from 'axios'

const props = defineProps({
    form: { type: Object, required: true },
    communities: { type: Array, default: () => [] },
    gradeLevels: { type: Array, default: () => [] },
    guardian: { type: Object, default: null },
    studentId: { type: [Number, String, null], default: null },
    hasEnrollments: { type: Boolean, default: false },
})

const localCommunities = ref([...props.communities])
watch(() => props.communities, (list) => {
    const existingIds = new Set(list.map(c => c.id))
    const extras = localCommunities.value.filter(c => !existingIds.has(c.id))
    localCommunities.value = [...list, ...extras].sort((a, b) => a.name.localeCompare(b.name, 'es'))
}, { deep: true })

const showQuickCommunity = ref(false)
const quickCommunityName = ref('')
const quickCommunityError = ref('')
const quickCommunityNotice = ref('')
const savingQuickCommunity = ref(false)

const normalizeText = (text) => String(text || '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .trim()
    .toLowerCase()

const similarCommunity = computed(() => {
    const q = normalizeText(quickCommunityName.value)
    if (q.length < 3) return null
    return localCommunities.value.find(c => normalizeText(c.name) === q) || null
})

const createQuickCommunity = async () => {
    const trimmed = quickCommunityName.value.trim()
    if (!trimmed) {
        quickCommunityError.value = 'Escriba el nombre de la comunidad o sector.'
        return
    }
    savingQuickCommunity.value = true
    quickCommunityError.value = ''
    quickCommunityNotice.value = ''

    try {
        const { data } = await axios.post('/admin/communities/quick', { name: trimmed })
        const community = data.community
        if (community && !localCommunities.value.some(c => c.id === community.id)) {
            localCommunities.value = [...localCommunities.value, community]
                .sort((a, b) => a.name.localeCompare(b.name, 'es'))
        }
        if (community) {
            props.form.community_id = community.id
        }
        quickCommunityName.value = ''
        showQuickCommunity.value = false
        quickCommunityNotice.value = data.message || 'Comunidad seleccionada.'
        setTimeout(() => { quickCommunityNotice.value = '' }, 4000)
    } catch (error) {
        quickCommunityError.value =
            error.response?.data?.errors?.name?.[0] ||
            error.response?.data?.message ||
            'No se pudo registrar la comunidad.'
    } finally {
        savingQuickCommunity.value = false
    }
}

const firstGradeLevel = computed(() => {
    if (!props.gradeLevels || props.gradeLevels.length === 0) return null
    return [...props.gradeLevels].sort((a, b) => (a.order_num || 0) - (b.order_num || 0))[0]
})

function setEntryType(type) {
    props.form.entry_type = type
    if (type === 'new') {
        props.form.target_grade_level_id = firstGradeLevel.value ? firstGradeLevel.value.id : ''
        props.form.origin_school = ''
        if (props.form.clearErrors) {
            props.form.clearErrors('target_grade_level_id')
            props.form.clearErrors('origin_school')
        }
    }
}

watch(() => props.gradeLevels, () => {
    if ((!props.form.entry_type || props.form.entry_type === 'new') && !props.form.target_grade_level_id && firstGradeLevel.value) {
        props.form.target_grade_level_id = firstGradeLevel.value.id
    }
}, { immediate: true })

// Rango de edad permitido: 10 a 20 años
const maxBirthDate = computed(() => {
    const d = new Date()
    d.setFullYear(d.getFullYear() - 10)
    return d.toISOString().split('T')[0]
})

const minBirthDate = computed(() => {
    const d = new Date()
    d.setFullYear(d.getFullYear() - 20)
    return d.toISOString().split('T')[0]
})

const calculatedAge = computed(() => {
    if (!props.form.birth_date) return null
    const birth = new Date(props.form.birth_date)
    const today = new Date()
    let age = today.getFullYear() - birth.getFullYear()
    const m = today.getMonth() - birth.getMonth()
    if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) {
        age--
    }
    return age
})

// --- Documento de Identidad / Cédula Escolar del Estudiante ---
// Tipos: 'V' (Venezolano SAIME), 'E' (Extranjero SAIME), 'CE' (Cédula Escolar MPPE), 'P' (Pasaporte), 'NONE' (Sin documento / En trámite)
const docType = ref('V')
const docNumber = ref('')
const docError = ref('')

// Representante legal (proveniente de la selección en el Paso 1)
const currentGuardian = computed(() => props.guardian)
const ceBirthOrder = ref('1') // 1: Único/1er morocho, 2: 2do morocho, 3: 3er trillizo, etc.
const ceSuccessMsg = ref('')

const isCheckingCedula = ref(false)
const cedulaDuplicate = ref(null)
let checkCedulaTimeout = null

async function checkCedulaUniqueness(force = false) {
    if (checkCedulaTimeout) clearTimeout(checkCedulaTimeout)

    if (docType.value === 'NONE' || !docNumber.value.trim() || !validateDocNumber()) {
        cedulaDuplicate.value = null
        return
    }

    const currentDoc = props.form.cedula
    if (!currentDoc) {
        cedulaDuplicate.value = null
        return
    }

    const execute = async () => {
        isCheckingCedula.value = true
        try {
            const { data } = await axios.get('/admin/students/check-cedula', {
                params: {
                    cedula: currentDoc,
                    student_id: props.studentId || null,
                }
            })
            if (data.exists) {
                cedulaDuplicate.value = data.student
                docError.value = data.message
                if (props.form.setError) {
                    props.form.setError('cedula', data.message)
                }
            } else {
                cedulaDuplicate.value = null
                if (docError.value && docError.value.includes('ya se encuentra registrada')) {
                    docError.value = ''
                }
                if (props.form.clearErrors) {
                    props.form.clearErrors('cedula')
                }
            }
        } catch (e) {
            console.error('Error al verificar cédula', e)
        } finally {
            isCheckingCedula.value = false
        }
    }

    if (force) {
        await execute()
    } else {
        checkCedulaTimeout = setTimeout(execute, 400)
    }
}

const docPlaceholder = computed(() => {
    switch (docType.value) {
        case 'V': return 'Ej: 30123456 (6-9 dígitos numéricos)'
        case 'E': return 'Ej: 84550210 (6-9 dígitos numéricos)'
        case 'CE': return 'Ej: V11214200350 (12 carac. oficiales MPPE)'
        case 'P': return 'Ej: ABC123456 (6-15 carac.)'
        default: return 'Sin documento registrado (en trámite)'
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
    const c = String(cedula).trim().toUpperCase()
    if (c.startsWith('V-')) return { type: 'V', number: c.replace('V-', '').replace(/[^0-9]/g, '').slice(0, 9) }
    if (c.startsWith('E-')) return { type: 'E', number: c.replace('E-', '').replace(/[^0-9]/g, '').slice(0, 9) }
    if (c.startsWith('P-')) return { type: 'P', number: c.replace('P-', '').replace(/[^A-Z0-9]/g, '').slice(0, 15) }
    if (c.startsWith('CE-')) return { type: 'CE', number: c.replace('CE-', '').slice(0, 12) }
    if (/^[VE][1-9]\d{10}$/.test(c)) return { type: 'CE', number: c }
    if (/^\d{6,9}$/.test(c)) return { type: 'V', number: c }
    return { type: 'V', number: c.replace(/[^0-9]/g, '').slice(0, 9) }
}

function buildCedulaValue() {
    if (docType.value === 'NONE' || !docNumber.value.trim()) {
        return ''
    }
    const clean = docNumber.value.trim().toUpperCase()
    if (docType.value === 'V') return `V-${clean}`
    if (docType.value === 'E') return `E-${clean}`
    if (docType.value === 'P') return `P-${clean}`
    if (docType.value === 'CE') return clean
    return clean
}

function updateCedulaForm() {
    props.form.cedula = buildCedulaValue()
}

// Inicializar desde form.cedula si ya viene cargada
watch(() => props.form.cedula, (val) => {
    const currentBuilt = buildCedulaValue()
    if (val !== currentBuilt) {
        const parsed = parseCedulaToDoc(val)
        docType.value = parsed.type
        docNumber.value = parsed.number
    }
}, { immediate: true })

function onDocTypeChange() {
    docError.value = ''
    ceSuccessMsg.value = ''
    cedulaDuplicate.value = null
    if (props.form.clearErrors) {
        props.form.clearErrors('cedula')
    } else if (props.form.errors) {
        delete props.form.errors.cedula
    }

    if (docType.value === 'NONE') {
        docNumber.value = ''
    } else if (docType.value === 'V' || docType.value === 'E') {
        docNumber.value = docNumber.value.replace(/[^0-9]/g, '').slice(0, 9)
    } else if (docType.value === 'CE') {
        docNumber.value = docNumber.value.toUpperCase().replace(/[^VE0-9]/g, '').slice(0, 12)
    } else if (docType.value === 'P') {
        docNumber.value = docNumber.value.toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 15)
    }
    updateCedulaForm()
    validateDocNumber()
}

function allowOnlyDocKey(e, type) {
    if (['Backspace', 'Delete', 'Tab', 'Escape', 'Enter', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Home', 'End'].includes(e.key)) {
        return
    }
    if (e.ctrlKey || e.metaKey) {
        return
    }
    if (type === 'V' || type === 'E') {
        if (!/^[0-9]$/.test(e.key)) {
            e.preventDefault()
        }
    } else if (type === 'CE') {
        // Permitir V o E si está en la primera posición, o dígitos del 0 al 9
        const isFirstChar = !docNumber.value || docNumber.value.length === 0
        if (isFirstChar) {
            if (!/^[VEve0-9]$/.test(e.key)) {
                e.preventDefault()
            }
        } else {
            if (!/^[0-9]$/.test(e.key)) {
                e.preventDefault()
            }
        }
    } else if (type === 'P') {
        if (!/^[a-zA-Z0-9]$/.test(e.key)) {
            e.preventDefault()
        }
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
    e.target.value = val
    ceSuccessMsg.value = ''
    cedulaDuplicate.value = null
    updateCedulaForm()
    if (validateDocNumber()) {
        checkCedulaUniqueness(false)
    }
}

function validateDocNumber() {
    docError.value = ''
    if (docType.value === 'NONE' || !docNumber.value.trim()) {
        if (props.form.clearErrors) props.form.clearErrors('cedula')
        return true
    }
    const num = docNumber.value.trim()
    if (docType.value === 'V' || docType.value === 'E') {
        if (!/^\d{6,9}$/.test(num)) {
            docError.value = 'La cédula debe contener entre 6 y 9 dígitos numéricos.'
            return false
        }
    } else if (docType.value === 'CE') {
        const ce = num.toUpperCase()
        if (ce.length !== 12) {
            docError.value = `La cédula escolar debe tener exactamente 12 caracteres (tiene ${ce.length}). Ej: V11214200350.`
            return false
        }
        if (!/^[VE]/.test(ce)) {
            docError.value = 'El primer carácter de la cédula escolar debe ser V (Venezolano) o E (Extranjero).'
            return false
        }
        if (!/^[VE][1-9]/.test(ce)) {
            docError.value = 'El segundo carácter indica el orden de parto del alumno y debe ser un dígito del 1 al 9 (ej: 1 para hijo único o primer morocho, 2 para segundo morocho).'
            return false
        }
        if (!/^[VE][1-9]\d{2}/.test(ce)) {
            docError.value = 'Los caracteres 3 y 4 deben ser los 2 últimos dígitos del año de nacimiento (ej: 12 para 2012).'
            return false
        }
        if (!/^[VE][1-9]\d{10}$/.test(ce)) {
            docError.value = 'Los últimos 8 caracteres deben ser numéricos correspondientes a la cédula del representante (con ceros a la izquierda).'
            return false
        }
    } else if (docType.value === 'P') {
        if (!/^[A-Z0-9]{6,15}$/.test(num)) {
            docError.value = 'El pasaporte debe tener entre 6 y 15 caracteres alfanuméricos.'
            return false
        }
    }
    if (props.form.clearErrors) props.form.clearErrors('cedula')
    return true
}

// --- Generador Automático de Cédula Escolar MPPE ---
// Estructura oficial: [V/E] + [Orden Parto 1-9] + [AA Año Nacimiento] + [Cédula Representante 8 dígitos]
const canGenerateCe = computed(() => {
    return Boolean(props.form.birth_date && currentGuardian.value?.cedula)
})

const computedSuggestedCe = computed(() => {
    if (!props.form.birth_date || !currentGuardian.value?.cedula) return ''
    const prefix = (props.form.nationality === 'E') ? 'E' : 'V'
    const order = ceBirthOrder.value || '1'
    const yearDigits = props.form.birth_date.split('-')[0].slice(-2)
    const rawDigits = currentGuardian.value.cedula.replace(/[^0-9]/g, '')
    const padded = rawDigits.padStart(8, '0').slice(-8)
    return `${prefix}${order}${yearDigits}${padded}`
})

function generateCedulaEscolar() {
    docError.value = ''
    ceSuccessMsg.value = ''

    if (!props.form.birth_date) {
        docError.value = 'Indique primero la fecha de nacimiento del estudiante para extraer los 2 dígitos del año.'
        return false
    }

    if (!currentGuardian.value?.cedula) {
        docError.value = 'Seleccione o registre primero al representante legal en la sección inferior para extraer los 8 dígitos de su cédula.'
        return false
    }

    const generated = computedSuggestedCe.value
    docType.value = 'CE'
    docNumber.value = generated
    ceSuccessMsg.value = `Cédula Escolar generada exitosamente según normativa oficial MPPE (${generated})`
    updateCedulaForm()
    validateDocNumber()
    return true
}

// --- Teléfonos y Validación Venezuela ---
// Formato estricto: 0412, 0414, 0424, 0416, 0426 o 02XX seguido de guion y 7 dígitos numéricos
const venezuelanPhoneRegex = /^(0412|0414|0424|0416|0426|02\d{2})-\d{7}$/
const phoneMobileError = ref('')
const phoneHomeError = ref('')

function allowOnlyNumbersKey(e) {
    // Permitir teclas especiales y de navegación
    if (['Backspace', 'Delete', 'Tab', 'Escape', 'Enter', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Home', 'End'].includes(e.key)) {
        return
    }
    // Permitir atajos Ctrl o Cmd (Mac)
    if (e.ctrlKey || e.metaKey) {
        return
    }
    // Si no es un dígito del 0 al 9, bloquear inmediatamente la tecla
    if (!/^[0-9]$/.test(e.key)) {
        e.preventDefault()
    }
}

function formatPhone(value) {
    if (!value) return ''
    let val = value.replace(/[^0-9]/g, '')
    if (val.length > 0 && val[0] !== '0') {
        val = '0' + val
    }
    if (val.length > 4) {
        val = val.substring(0, 4) + '-' + val.substring(4)
    }
    if (val.length > 12) {
        val = val.substring(0, 12)
    }
    return val
}

function onPhoneMobileInput(e) {
    const formatted = formatPhone(e.target.value)
    props.form.phone_mobile = formatted
    e.target.value = formatted // Asegura actualización instantánea en el DOM
    if (props.form.phone_mobile.length === 12 || phoneMobileError.value) {
        validatePhoneMobile()
    }
}

function validatePhoneMobile() {
    phoneMobileError.value = ''
    if (!props.form.phone_mobile || !props.form.phone_mobile.trim()) {
        return true
    }
    const clean = props.form.phone_mobile.trim()
    if (!venezuelanPhoneRegex.test(clean)) {
        phoneMobileError.value = 'El teléfono móvil debe tener un formato válido en Venezuela (0412, 0414, 0424, 0416, 0426) seguido de 7 dígitos. Ej: 0414-1234567.'
        return false
    }
    return true
}

function onPhoneHomeInput(e) {
    const formatted = formatPhone(e.target.value)
    props.form.phone_home = formatted
    e.target.value = formatted // Asegura actualización instantánea en el DOM
    if (props.form.phone_home.length === 12 || phoneHomeError.value) {
        validatePhoneHome()
    }
}

function validatePhoneHome() {
    phoneHomeError.value = ''
    if (!props.form.phone_home || !props.form.phone_home.trim()) {
        return true
    }
    const clean = props.form.phone_home.trim()
    if (!venezuelanPhoneRegex.test(clean)) {
        phoneHomeError.value = 'El teléfono fijo debe tener un código de área válido (ej: 0212, 0251) seguido de 7 dígitos. Ej: 0212-1234567.'
        return false
    }
    return true
}
</script>

<template>
    <div class="space-y-6">
        <!-- Encabezado de Sección -->
        <div class="border-b border-slate-100 pb-4">
            <h3 class="text-xl font-black text-slate-800 flex items-center gap-2.5">
                <span class="w-8 h-8 rounded-xl bg-primary-100 text-primary-600 flex items-center justify-center text-sm font-black">2</span>
                Información Principal del Estudiante
            </h3>
            <p class="text-xs text-slate-400 font-bold mt-1 ml-10">Datos de filiación, condición de ingreso, documento de identidad y contacto</p>
        </div>

        <!-- Condición de Ingreso y Año a Cursar (Elegibilidad de Matriculación) -->
        <div class="p-4 sm:p-5 rounded-3xl bg-slate-50/90 border-2 border-slate-200/80 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div>
                    <h4 class="text-xs font-black uppercase tracking-wider text-slate-700 flex items-center gap-2">
                        <i class="fas fa-graduation-cap text-primary-600"></i>
                        Condición de Ingreso y Año a Cursar <span class="text-red-500">*</span>
                    </h4>
                    <p class="text-[11px] text-slate-400 font-semibold mt-0.5">
                        Define en qué Año estará habilitado el estudiante al momento de matricularlo en una sección.
                    </p>
                </div>
                <span v-if="hasEnrollments" class="px-3 py-1 rounded-xl bg-amber-50 border border-amber-200 text-amber-700 text-[10px] font-black uppercase tracking-wider">
                    <i class="fas fa-lock mr-1"></i> Nivel controlado por historial académico
                </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <button
                    type="button"
                    :disabled="hasEnrollments"
                    @click="setEntryType('new')"
                    class="p-3.5 rounded-2xl border-2 text-left transition-all flex items-start gap-3 cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed"
                    :class="(!form.entry_type || form.entry_type === 'new')
                        ? 'bg-white border-primary-500 shadow-md shadow-primary-500/10'
                        : 'bg-white/60 border-slate-200 hover:border-slate-300'"
                >
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 mt-0.5"
                         :class="(!form.entry_type || form.entry_type === 'new') ? 'bg-primary-600 text-white' : 'bg-slate-100 text-slate-400'">
                        <i class="fas fa-user-plus text-xs"></i>
                    </div>
                    <div>
                        <p class="text-xs font-black text-slate-800 uppercase tracking-wider">Nuevo Ingreso Regular (1er Año)</p>
                        <p class="text-[11px] font-semibold text-slate-500 mt-0.5">
                            El estudiante inicia en la institución y solo podrá matricularse en <strong>{{ firstGradeLevel?.name || '1er Año' }}</strong>.
                        </p>
                    </div>
                </button>

                <button
                    type="button"
                    :disabled="hasEnrollments"
                    @click="setEntryType('transfer')"
                    class="p-3.5 rounded-2xl border-2 text-left transition-all flex items-start gap-3 cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed"
                    :class="form.entry_type === 'transfer'
                        ? 'bg-white border-primary-500 shadow-md shadow-primary-500/10'
                        : 'bg-white/60 border-slate-200 hover:border-slate-300'"
                >
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 mt-0.5"
                         :class="form.entry_type === 'transfer' ? 'bg-primary-600 text-white' : 'bg-slate-100 text-slate-400'">
                        <i class="fas fa-exchange-alt text-xs"></i>
                    </div>
                    <div>
                        <p class="text-xs font-black text-slate-800 uppercase tracking-wider">Procedente de Otra Institución (Traslado)</p>
                        <p class="text-[11px] font-semibold text-slate-500 mt-0.5">
                            Permite seleccionar el Año específico (1ro a 5to Año) que le corresponde cursar por equivalencia/certificación.
                        </p>
                    </div>
                </button>
            </div>

            <!-- Campos adicionales cuando proviene de otra institución -->
            <div v-if="form.entry_type === 'transfer'" class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2 border-t border-slate-200/80 animate-fade-in">
                <div class="space-y-1.5">
                    <label class="text-[11px] font-black text-slate-500 uppercase tracking-wider ml-1">
                        Año a Cursar en el Plantel <span class="text-red-500">*</span>
                    </label>
                    <select
                        v-model="form.target_grade_level_id"
                        :disabled="hasEnrollments"
                        @change="form.clearErrors && form.clearErrors('target_grade_level_id')"
                        class="w-full bg-white border-2 border-slate-200 rounded-2xl px-4 py-3 text-slate-800 text-sm font-bold focus:border-primary-400 outline-none transition-all cursor-pointer disabled:bg-slate-100"
                    >
                        <option value="" disabled>Seleccione el Año a cursar...</option>
                        <option v-for="level in gradeLevels" :key="level.id" :value="level.id">
                            {{ level.name }}
                        </option>
                    </select>
                    <p v-if="form.errors.target_grade_level_id" class="text-xs text-red-500 font-bold ml-1">{{ form.errors.target_grade_level_id }}</p>
                </div>

                <div class="space-y-1.5">
                    <label class="text-[11px] font-black text-slate-500 uppercase tracking-wider ml-1">
                        Plantel / Institución de Procedencia <span class="text-red-500">*</span>
                    </label>
                    <input
                        v-model="form.origin_school"
                        @input="form.origin_school = ($event.target.value || '').toUpperCase(); form.clearErrors && form.clearErrors('origin_school')"
                        type="text"
                        placeholder="EJ: U.E.N. SIMÓN BOLÍVAR"
                        class="w-full bg-white border-2 border-slate-200 rounded-2xl px-4 py-3 text-slate-800 text-sm font-bold focus:border-primary-400 outline-none transition-all uppercase"
                    />
                    <p v-if="form.errors.origin_school" class="text-xs text-red-500 font-bold ml-1">{{ form.errors.origin_school }}</p>
                </div>
            </div>
        </div>

        <!-- Campos de Identificación -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            <!-- Nombres -->
            <div class="space-y-1.5">
                <label class="text-[11px] font-black text-slate-500 uppercase tracking-wider ml-1">
                    Nombres <span class="text-red-500">*</span>
                </label>
                <input
                    v-model="form.first_name"
                    @input="form.first_name = ($event.target.value || '').toUpperCase()"
                    type="text"
                    placeholder="EJ: CARLOS JOSÉ"
                    class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl px-4 py-3 text-slate-800 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all uppercase"
                    required
                />
                <p v-if="form.errors.first_name" class="text-xs text-red-500 font-bold ml-1">{{ form.errors.first_name }}</p>
            </div>

            <!-- Apellidos -->
            <div class="space-y-1.5">
                <label class="text-[11px] font-black text-slate-500 uppercase tracking-wider ml-1">
                    Apellidos <span class="text-red-500">*</span>
                </label>
                <input
                    v-model="form.last_name"
                    @input="form.last_name = ($event.target.value || '').toUpperCase()"
                    type="text"
                    placeholder="EJ: PÉREZ RAMÍREZ"
                    class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl px-4 py-3 text-slate-800 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all uppercase"
                    required
                />
                <p v-if="form.errors.last_name" class="text-xs text-red-500 font-bold ml-1">{{ form.errors.last_name }}</p>
            </div>

            <!-- Nacionalidad -->
            <div class="space-y-1.5">
                <label class="text-[11px] font-black text-slate-500 uppercase tracking-wider ml-1">
                    Nacionalidad <span class="text-red-500">*</span>
                </label>
                <select
                    v-model="form.nationality"
                    class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl px-4 py-3 text-slate-800 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all cursor-pointer"
                    required
                >
                    <option value="V">Venezolana (V)</option>
                    <option value="E">Extranjera (E)</option>
                </select>
                <p v-if="form.errors.nationality" class="text-xs text-red-500 font-bold ml-1">{{ form.errors.nationality }}</p>
            </div>

            <!-- Cédula / Documento de Identidad -->
            <div class="space-y-1.5 md:col-span-2 lg:col-span-1">
                <label class="text-[11px] font-black text-slate-500 uppercase tracking-wider ml-1 flex items-center justify-between">
                    <span>Documento / Cédula</span>
                    <span class="text-[10px] text-slate-400 font-bold">Opcional si está en trámite</span>
                </label>
                <div class="flex bg-slate-50 border-2 rounded-2xl p-1 transition-all"
                     :class="(docError || form.errors.cedula || cedulaDuplicate) ? 'border-red-300 bg-red-50/20' : 'border-slate-200 focus-within:border-primary-400 focus-within:bg-white'">
                    <select
                        v-model="docType"
                        @change="onDocTypeChange"
                        class="bg-transparent font-black text-slate-700 text-xs px-3 py-2.5 border-r border-slate-200 focus:outline-none cursor-pointer"
                    >
                        <option value="V">V- (Cédula SAIME)</option>
                        <option value="E">E- (Cédula Ext. SAIME)</option>
                        <option value="CE">C.E. (Cédula Escolar MPPE)</option>
                        <option value="P">P- (Pasaporte)</option>
                        <option value="NONE">S/D (Sin documento / En trámite)</option>
                    </select>
                    <input
                        :value="docNumber"
                        @input="onDocNumberInput"
                        @blur="checkCedulaUniqueness(true)"
                        @keydown="(e) => allowOnlyDocKey(e, docType)"
                        type="text"
                        :disabled="docType === 'NONE'"
                        :placeholder="docPlaceholder"
                        :maxlength="docMaxLength"
                        class="w-full bg-transparent border-0 px-3 py-2 text-sm font-bold text-slate-800 placeholder:text-slate-300 focus:ring-0 outline-none uppercase disabled:text-slate-400 disabled:cursor-not-allowed"
                    />
                    <div v-if="isCheckingCedula" class="flex items-center pr-3 text-slate-400">
                        <i class="fas fa-circle-notch fa-spin text-xs"></i>
                    </div>
                </div>

                <!-- Alerta destacada cuando la cédula ya existe en la base de datos -->
                <div v-if="cedulaDuplicate" class="p-3 bg-red-50 border-2 border-red-200 rounded-2xl flex items-start gap-2.5 text-xs text-red-700 animate-fade-in mt-1.5 shadow-xs">
                    <i class="fas fa-exclamation-triangle text-red-500 text-sm mt-0.5 shrink-0"></i>
                    <div>
                        <p class="font-black text-red-800">Cédula ya registrada en el sistema</p>
                        <p class="text-[11px] text-red-700 mt-0.5 font-bold">
                            Este documento ya pertenece a: <strong class="underline">{{ cedulaDuplicate.full_name }}</strong> (Cédula: {{ cedulaDuplicate.cedula }}).
                        </p>
                    </div>
                </div>

                <p v-else-if="docError" class="text-xs text-red-500 font-bold ml-1">{{ docError }}</p>
                <p v-else-if="form.errors.cedula" class="text-xs text-red-500 font-bold ml-1">{{ form.errors.cedula }}</p>
                <p v-if="ceSuccessMsg" class="text-xs text-emerald-600 font-bold ml-1 flex items-center gap-1">
                    <i class="fas fa-check-circle"></i> {{ ceSuccessMsg }}
                </p>

                <!-- Asistente de Cédula Escolar según Normativa MPPE (Desactivado temporalmente para consulta)
                <div v-if="docType === 'CE'" class="p-3.5 bg-blue-50/70 border border-blue-200 rounded-2xl space-y-2 mt-2">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-black uppercase tracking-wider text-blue-800 flex items-center gap-1.5">
                            <i class="fas fa-id-card text-blue-600"></i> Normativa Cédula Escolar (12 carac.)
                        </span>
                        <span class="text-[10px] text-blue-600 font-bold">Manual MPPE</span>
                    </div>
                    <p class="text-[11px] text-blue-700 leading-relaxed font-medium">
                        Estructura: <strong class="font-black text-blue-900">[V/E]</strong> + <strong class="font-black text-blue-900">[Orden Parto 1-9]</strong> + <strong class="font-black text-blue-900">[Año Nac. AA]</strong> + <strong class="font-black text-blue-900">[8 dígitos Cédula Representante]</strong>
                    </p>

                    <div class="pt-2 border-t border-blue-200/80 flex flex-wrap items-center gap-2">
                        <div class="flex items-center gap-1.5 bg-white border border-blue-200 rounded-xl px-2.5 py-1">
                            <label class="text-[10px] font-black uppercase text-slate-500">Orden / Parto:</label>
                            <select
                                v-model="ceBirthOrder"
                                class="bg-transparent text-xs font-black text-blue-800 focus:outline-none cursor-pointer"
                            >
                                <option value="1">1 (Hijo Único / 1er Morocho)</option>
                                <option value="2">2 (2do Morocho)</option>
                                <option value="3">3 (3er Trillizo)</option>
                                <option value="4">4 (4to Cuatrillizo)</option>
                                <option value="5">5</option>
                            </select>
                        </div>

                        <button
                            type="button"
                            @click="generateCedulaEscolar"
                            class="px-3 py-1.5 bg-blue-600 hover:bg-blue-500 text-white rounded-xl text-xs font-black uppercase tracking-wider shadow-sm transition-all flex items-center gap-1.5"
                        >
                            <i class="fas fa-magic"></i> Autogenerar
                        </button>

                        <span v-if="computedSuggestedCe && docNumber !== computedSuggestedCe" class="text-[11px] text-blue-600 font-bold">
                            Sugerida: <code class="bg-blue-100 text-blue-900 px-1 py-0.5 rounded font-black">{{ computedSuggestedCe }}</code>
                        </span>
                    </div>
                </div>
                -->
            </div>

            <!-- Fecha de Nacimiento -->
            <div class="space-y-1.5">
                <label class="text-[11px] font-black text-slate-500 uppercase tracking-wider ml-1 flex items-center justify-between">
                    <span>Fecha de Nacimiento <span class="text-red-500">*</span></span>
                    <span v-if="calculatedAge !== null" class="text-xs font-black text-primary-600 bg-primary-50 px-2 py-0.5 rounded-lg">
                        {{ calculatedAge }} años
                    </span>
                </label>
                <input
                    v-model="form.birth_date"
                    :min="minBirthDate"
                    :max="maxBirthDate"
                    type="date"
                    class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl px-4 py-3 text-slate-800 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all cursor-pointer"
                    required
                />
                <p v-if="form.errors.birth_date" class="text-xs text-red-500 font-bold ml-1">{{ form.errors.birth_date }}</p>
            </div>

            <!-- Sexo -->
            <div class="space-y-1.5">
                <label class="text-[11px] font-black text-slate-500 uppercase tracking-wider ml-1">
                    Sexo <span class="text-red-500">*</span>
                </label>
                <div class="grid grid-cols-2 gap-3">
                    <button
                        type="button"
                        @click="form.gender = 'M'"
                        :class="form.gender === 'M' ? 'bg-blue-500 text-white border-blue-500 shadow-md shadow-blue-500/20' : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100'"
                        class="py-3 px-4 rounded-2xl border-2 text-xs font-black uppercase tracking-wider flex items-center justify-center gap-2 transition-all"
                    >
                        <i class="fas fa-mars"></i> Masculino
                    </button>
                    <button
                        type="button"
                        @click="form.gender = 'F'"
                        :class="form.gender === 'F' ? 'bg-pink-500 text-white border-pink-500 shadow-md shadow-pink-500/20' : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100'"
                        class="py-3 px-4 rounded-2xl border-2 text-xs font-black uppercase tracking-wider flex items-center justify-center gap-2 transition-all"
                    >
                        <i class="fas fa-venus"></i> Femenino
                    </button>
                </div>
                <p v-if="form.errors.gender" class="text-xs text-red-500 font-bold ml-1">{{ form.errors.gender }}</p>
            </div>

            <!-- Lugar de Nacimiento -->
            <div class="space-y-1.5 md:col-span-2">
                <label class="text-[11px] font-black text-slate-500 uppercase tracking-wider ml-1">
                    Lugar de Nacimiento <span class="text-red-500">*</span>
                </label>
                <input
                    v-model="form.birth_place"
                    @input="form.birth_place = ($event.target.value || '').toUpperCase()"
                    type="text"
                    placeholder="EJ: LAGUNILLAS, ESTADO MÉRIDA"
                    class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl px-4 py-3 text-slate-800 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all uppercase"
                    required
                />
                <p v-if="form.errors.birth_place" class="text-xs text-red-500 font-bold ml-1">{{ form.errors.birth_place }}</p>
            </div>

            <!-- Comunidad -->
            <div class="space-y-1.5">
                <label class="text-[11px] font-black text-slate-500 uppercase tracking-wider ml-1">
                    Comunidad / Sector <span class="text-red-500">*</span>
                </label>
                <div class="flex items-center gap-2">
                    <select
                        v-model="form.community_id"
                        class="flex-1 bg-slate-50 border-2 border-slate-200 rounded-2xl px-4 py-3 text-slate-800 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all cursor-pointer"
                        required
                    >
                        <option value="" disabled>Seleccione comunidad...</option>
                        <option v-for="c in localCommunities" :key="c.id" :value="c.id">
                            {{ c.name }}
                        </option>
                    </select>
                    <button
                        v-if="$can('students.create') || $can('students.edit') || $can('settings.manage')"
                        type="button"
                        @click="showQuickCommunity = !showQuickCommunity; quickCommunityError = ''"
                        :title="showQuickCommunity ? 'Cerrar' : 'Agregar nueva comunidad o sector'"
                        class="shrink-0 w-11 h-11 rounded-2xl border-2 border-primary-200 bg-primary-50 text-primary-700 hover:bg-primary-600 hover:text-white hover:border-primary-600 transition-all flex items-center justify-center font-black"
                    >
                        <i class="fas" :class="showQuickCommunity ? 'fa-times' : 'fa-plus'"></i>
                    </button>
                </div>

                <!-- Alta rápida inline -->
                <div v-if="showQuickCommunity" class="mt-2 p-3 bg-primary-50/70 border border-primary-200 rounded-2xl space-y-2">
                    <p class="text-[10px] font-black text-primary-700 uppercase tracking-wider">
                        Registrar nueva comunidad / sector (Lagunillas - Mérida)
                    </p>
                    <div class="flex items-center gap-2">
                        <input
                            v-model="quickCommunityName"
                            @keydown.enter.prevent="createQuickCommunity"
                            type="text"
                            maxlength="120"
                            placeholder="Ej: Sector El Molino / La Vega"
                            class="flex-1 bg-white border-2 border-primary-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 focus:border-primary-500 focus:ring-0 outline-none"
                        />
                        <button
                            type="button"
                            @click="createQuickCommunity"
                            :disabled="savingQuickCommunity || !quickCommunityName.trim()"
                            class="px-4 py-2 bg-primary-600 text-white rounded-xl text-[11px] font-black uppercase tracking-wider hover:bg-primary-700 disabled:opacity-50 transition-all flex items-center gap-1.5"
                        >
                            <i class="fas" :class="savingQuickCommunity ? 'fa-spinner fa-spin' : 'fa-check'"></i>
                            Guardar
                        </button>
                    </div>
                    <p v-if="similarCommunity" class="text-[11px] font-bold text-amber-700">
                        <i class="fas fa-info-circle mr-1"></i>
                        Ya existe «{{ similarCommunity.name }}». Al guardar se seleccionará automáticamente.
                    </p>
                    <p v-if="quickCommunityError" class="text-[11px] font-bold text-red-600">
                        {{ quickCommunityError }}
                    </p>
                </div>

                <p v-if="quickCommunityNotice" class="text-xs text-emerald-600 font-bold ml-1">
                    <i class="fas fa-check-circle mr-1"></i>{{ quickCommunityNotice }}
                </p>
                <p v-if="form.errors.community_id" class="text-xs text-red-500 font-bold ml-1">{{ form.errors.community_id }}</p>
            </div>
        </div>

        <!-- Dirección de Habitación -->
        <div class="space-y-1.5">
            <label class="text-[11px] font-black text-slate-500 uppercase tracking-wider ml-1">
                Dirección Exacta de Habitación <span class="text-red-500">*</span>
            </label>
            <textarea
                v-model="form.address"
                @input="form.address = ($event.target.value || '').toUpperCase()"
                rows="2"
                placeholder="EJ: AV. PRINCIPAL, SECTOR PUEBLO VIEJO, CASA N° 12"
                class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl px-4 py-3 text-slate-800 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all resize-none uppercase"
                required
            ></textarea>
            <p v-if="form.errors.address" class="text-xs text-red-500 font-bold ml-1">{{ form.errors.address }}</p>
        </div>

        <!-- Teléfonos y Correo -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <div class="space-y-1.5">
                <label class="text-[11px] font-black text-slate-500 uppercase tracking-wider ml-1 flex items-center justify-between">
                    <span>Teléfono Móvil</span>
                    <!-- <span class="text-[10px] text-slate-400 font-bold">Solo números</span> -->
                </label>
                <input
                    :value="form.phone_mobile"
                    @input="onPhoneMobileInput"
                    @keydown="allowOnlyNumbersKey"
                    type="tel"
                    inputmode="numeric"
                    maxlength="12"
                    placeholder="Ej: 0412-1234567"
                    class="w-full bg-slate-50 border-2 rounded-2xl px-4 py-3 text-slate-800 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all"
                    :class="(phoneMobileError || form.errors.phone_mobile) ? 'border-red-300 bg-red-50/20' : 'border-slate-200'"
                />
                <p v-if="phoneMobileError" class="text-xs text-red-500 font-bold ml-1">{{ phoneMobileError }}</p>
                <p v-else-if="form.errors.phone_mobile" class="text-xs text-red-500 font-bold ml-1">{{ form.errors.phone_mobile }}</p>
            </div>

            <div class="space-y-1.5">
                <label class="text-[11px] font-black text-slate-500 uppercase tracking-wider ml-1 flex items-center justify-between">
                    <span>Teléfono Fijo / Habitación</span>
                    <!-- <span class="text-[10px] text-slate-400 font-bold">Solo números</span> -->
                </label>
                <input
                    :value="form.phone_home"
                    @input="onPhoneHomeInput"
                    @keydown="allowOnlyNumbersKey"
                    type="tel"
                    inputmode="numeric"
                    maxlength="12"
                    placeholder="Ej: 0274-1234567"
                    class="w-full bg-slate-50 border-2 rounded-2xl px-4 py-3 text-slate-800 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all"
                    :class="(phoneHomeError || form.errors.phone_home) ? 'border-red-300 bg-red-50/20' : 'border-slate-200'"
                />
                <p v-if="phoneHomeError" class="text-xs text-red-500 font-bold ml-1">{{ phoneHomeError }}</p>
                <p v-else-if="form.errors.phone_home" class="text-xs text-red-500 font-bold ml-1">{{ form.errors.phone_home }}</p>
            </div>

            <div class="space-y-1.5">
                <label class="text-[11px] font-black text-slate-500 uppercase tracking-wider ml-1">Correo Electrónico</label>
                <input
                    v-model="form.email"
                    @input="form.email = ($event.target.value || '').toLowerCase()"
                    type="email"
                    placeholder="Ej: estudiante@ejemplo.com"
                    class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl px-4 py-3 text-slate-800 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all lowercase"
                />
                <p v-if="form.errors.email" class="text-xs text-red-500 font-bold ml-1">{{ form.errors.email }}</p>
            </div>
        </div>

    </div>
</template>
