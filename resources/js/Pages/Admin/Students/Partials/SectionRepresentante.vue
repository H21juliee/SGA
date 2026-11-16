<script setup>
import { ref, reactive, computed, watch } from 'vue'
import axios from 'axios'

const props = defineProps({
    modelValue: [Number, String, null],
    initialGuardian: { type: Object, default: null },
    error: String,
})

const emit = defineEmits(['update:modelValue', 'update:guardian'])

// Estados principales
const selectedGuardian = ref(props.initialGuardian)
const searchDocType = ref('V')
const searchDocNumber = ref('')
const searching = ref(false)
const notFound = ref(false)
const searchError = ref('')
const showCreateForm = ref(false)
const saving = ref(false)
const createErrors = ref({})

// Formulario de nuevo representante (Valores iniciales)
const form = reactive({
    doc_type: 'V',
    doc_number: '',
    first_name: '',
    last_name: '',
    nationality: 'V',
    birth_place: '',
    birth_date: '',
    education_level: '',
    address: '',
    is_working: true,
    work_sector: 'PUBLICO',
    work_type: 'EMPLEADO FIJO',
    occupation: '',
    email: '',
    phone_home: '',
    phone: '', // teléfono móvil
    relationship: 'MADRE',
})

watch(() => props.initialGuardian, (val) => {
    selectedGuardian.value = val || null
}, { immediate: true })

watch(() => props.modelValue, (val) => {
    if (!val) {
        selectedGuardian.value = null
    }
})

// Límites de edad (18 a 90 años) idéntico a la lógica del estudiante
const minBirthDate = computed(() => {
    const d = new Date()
    d.setFullYear(d.getFullYear() - 90)
    return d.toISOString().split('T')[0]
})

const maxBirthDate = computed(() => {
    const d = new Date()
    d.setFullYear(d.getFullYear() - 18)
    return d.toISOString().split('T')[0]
})

const calculatedAge = computed(() => {
    if (!form.birth_date) return null
    const birth = new Date(form.birth_date)
    const today = new Date()
    let age = today.getFullYear() - birth.getFullYear()
    const m = today.getMonth() - birth.getMonth()
    if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) {
        age--
    }
    return age
})

// Control de teclado exclusivo numérico
function allowOnlyNumbersKey(e) {
    if (['Backspace', 'Delete', 'Tab', 'Escape', 'Enter', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Home', 'End'].includes(e.key)) {
        return
    }
    if (e.ctrlKey || e.metaKey) {
        return
    }
    if (!/^[0-9]$/.test(e.key)) {
        e.preventDefault()
    }
}

function allowOnlyDocKey(e, type) {
    if (['Backspace', 'Delete', 'Tab', 'Escape', 'Enter', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Home', 'End'].includes(e.key)) {
        return
    }
    if (e.ctrlKey || e.metaKey) {
        return
    }
    if (type === 'P') {
        if (!/^[a-zA-Z0-9]$/.test(e.key)) e.preventDefault()
    } else {
        if (!/^[0-9]$/.test(e.key)) e.preventDefault()
    }
}

// Máscara venezolana de teléfonos
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

const venezuelanPhoneRegex = /^(0412|0414|0424|0416|0426|02\d{2})-\d{7}$/

function onPhoneMobileInput(e) {
    const formatted = formatPhone(e.target.value)
    form.phone = formatted
    e.target.value = formatted
    if (createErrors.value.phone && venezuelanPhoneRegex.test(form.phone)) {
        delete createErrors.value.phone
    }
}

function onPhoneHomeInput(e) {
    const formatted = formatPhone(e.target.value)
    form.phone_home = formatted
    e.target.value = formatted
    if (createErrors.value.phone_home && venezuelanPhoneRegex.test(form.phone_home)) {
        delete createErrors.value.phone_home
    }
}

// Búsqueda
const builtSearchCedula = computed(() => `${searchDocType.value}-${searchDocNumber.value.trim()}`)

function onSearchDocInput(e) {
    let v = (e.target.value || '').toUpperCase()
    v = searchDocType.value === 'P' ? v.replace(/[^A-Z0-9]/g, '') : v.replace(/[^0-9]/g, '')
    searchDocNumber.value = v.slice(0, searchDocType.value === 'P' ? 15 : 9)
    e.target.value = searchDocNumber.value
    searchError.value = ''
    notFound.value = false
}

function validSearchDoc() {
    const n = searchDocNumber.value.trim()
    if (searchDocType.value === 'P') return /^[A-Z0-9]{6,15}$/.test(n)
    return /^\d{6,9}$/.test(n)
}

function search() {
    searchError.value = ''
    if (!validSearchDoc()) {
        searchError.value = searchDocType.value === 'P'
            ? 'El pasaporte debe contener entre 6 y 15 caracteres alfanuméricos.'
            : 'La cédula debe contener entre 6 y 9 dígitos numéricos.'
        return
    }

    searching.value = true
    notFound.value = false
    showCreateForm.value = false

    axios.get('/admin/guardians/search', { params: { cedula: builtSearchCedula.value } })
        .then(({ data }) => {
            if (data.guardian) {
                selectGuardian(data.guardian)
            } else {
                notFound.value = true
                openCreateFormWithDoc(searchDocType.value, searchDocNumber.value)
            }
        })
        .catch(() => {
            searchError.value = 'No se pudo realizar la búsqueda. Verifique la conexión.'
        })
        .finally(() => {
            searching.value = false
        })
}

function selectGuardian(guardian) {
    selectedGuardian.value = guardian
    notFound.value = false
    showCreateForm.value = false
    emit('update:modelValue', guardian.id)
    emit('update:guardian', guardian)
}

function removeGuardian() {
    selectedGuardian.value = null
    emit('update:modelValue', null)
    emit('update:guardian', null)
}

function openCreateFormWithDoc(type = 'V', num = '') {
    showCreateForm.value = true
    form.doc_type = type
    form.doc_number = num
    form.first_name = ''
    form.last_name = ''
    form.nationality = type === 'E' ? 'E' : 'V'
    form.birth_place = ''
    form.birth_date = ''
    form.education_level = 'BACHILLER'
    form.address = ''
    form.is_working = true
    form.work_sector = 'PUBLICO'
    form.work_type = 'EMPLEADO FIJO'
    form.occupation = ''
    form.email = ''
    form.phone_home = ''
    form.phone = ''
    form.relationship = 'MADRE'
    createErrors.value = {}
}

function validateBirthDate() {
    if (!form.birth_date) {
        createErrors.value.birth_date = ['La fecha de nacimiento del representante es obligatoria.']
        return false
    }
    if (form.birth_date > maxBirthDate.value) {
        createErrors.value.birth_date = ['El representante debe tener al menos 18 años de edad.']
        return false
    }
    if (form.birth_date < minBirthDate.value) {
        createErrors.value.birth_date = ['El representante no puede tener más de 90 años de edad.']
        return false
    }
    delete createErrors.value.birth_date
    return true
}

function submitCreate() {
    createErrors.value = {}
    let isValid = true

    const docNum = (form.doc_number || '').trim()
    if (!docNum) {
        createErrors.value.cedula = ['La cédula del representante es obligatoria.']
        isValid = false
    } else if (form.doc_type === 'P') {
        if (!/^[A-Z0-9]{6,15}$/.test(docNum)) {
            createErrors.value.cedula = ['El pasaporte debe contener entre 6 y 15 caracteres alfanuméricos.']
            isValid = false
        }
    } else {
        if (!/^\d{6,9}$/.test(docNum)) {
            createErrors.value.cedula = ['La cédula debe contener entre 6 y 9 dígitos numéricos.']
            isValid = false
        }
    }

    if (!form.first_name?.trim()) {
        createErrors.value.first_name = ['Los nombres del representante son obligatorios.']
        isValid = false
    }
    if (!form.last_name?.trim()) {
        createErrors.value.last_name = ['Los apellidos del representante son obligatorios.']
        isValid = false
    }
    if (!form.birth_place?.trim()) {
        createErrors.value.birth_place = ['El lugar de nacimiento es obligatorio.']
        isValid = false
    }
    if (!validateBirthDate()) {
        isValid = false
    }
    if (!form.education_level) {
        createErrors.value.education_level = ['Seleccione el nivel de instrucción.']
        isValid = false
    }
    if (!form.address?.trim()) {
        createErrors.value.address = ['La dirección de habitación es obligatoria.']
        isValid = false
    }
    if (!form.relationship) {
        createErrors.value.relationship = ['Seleccione el parentesco con el estudiante.']
        isValid = false
    }

    if (form.is_working) {
        if (!form.work_sector) {
            createErrors.value.work_sector = ['Indique si trabaja para el sector público o privado.']
            isValid = false
        }
        if (!form.work_type) {
            createErrors.value.work_type = ['Indique el tipo de trabajo.']
            isValid = false
        }
    }

    if (form.phone && form.phone.trim()) {
        if (!venezuelanPhoneRegex.test(form.phone.trim())) {
            createErrors.value.phone = ['Formato inválido (ej: 0414-1234567).']
            isValid = false
        }
    }

    if (form.phone_home && form.phone_home.trim()) {
        if (!venezuelanPhoneRegex.test(form.phone_home.trim())) {
            createErrors.value.phone_home = ['Formato inválido (ej: 0212-1234567).']
            isValid = false
        }
    }

    if (form.email && form.email.trim()) {
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email.trim())) {
            createErrors.value.email = ['El formato del correo electrónico no es válido.']
            isValid = false
        }
    }

    if (!isValid) return

    saving.value = true

    const payload = {
        cedula: `${form.doc_type}-${docNum}`,
        first_name: form.first_name.trim().toUpperCase(),
        last_name: form.last_name.trim().toUpperCase(),
        nationality: form.nationality,
        birth_place: form.birth_place.trim().toUpperCase(),
        birth_date: form.birth_date,
        education_level: form.education_level.trim().toUpperCase(),
        address: form.address.trim().toUpperCase(),
        is_working: Boolean(form.is_working),
        work_sector: form.is_working ? form.work_sector : null,
        work_type: form.is_working ? form.work_type : null,
        occupation: form.occupation?.trim() ? form.occupation.trim().toUpperCase() : null,
        phone: form.phone ? form.phone.trim() : null,
        phone_home: form.phone_home ? form.phone_home.trim() : null,
        email: form.email ? form.email.trim().toLowerCase() : null,
        relationship: form.relationship.trim().toUpperCase(),
    }

    axios.post('/admin/guardians', payload)
        .then(({ data }) => {
            saving.value = false
            selectGuardian(data.guardian)
        })
        .catch((e) => {
            saving.value = false
            if (e.response?.status === 422) {
                createErrors.value = e.response.data.errors || {}
            } else {
                searchError.value = 'Error al registrar el representante. Verifique los datos ingresados.'
            }
        })
}
</script>

<template>
    <div class="space-y-6 animate-fade-in">
        <!-- Encabezado de Sección -->
        <div class="border-b border-slate-100 pb-4">
            <h3 class="text-xl font-black text-slate-800 flex items-center gap-2.5">
                <span class="w-8 h-8 rounded-xl bg-primary-100 text-primary-600 flex items-center justify-center text-sm font-black">1</span>
                Datos del Representante Legal
            </h3>
            <p class="text-xs text-slate-400 font-bold mt-1 ml-10">
                Identificación, información formativa, laboral y de contacto del adulto responsable
            </p>
        </div>

        <!-- MODO A: Representante ya seleccionado -->
        <div v-if="selectedGuardian" class="bg-white border-2 border-emerald-200 rounded-3xl p-5 sm:p-6 shadow-sm space-y-4">
            <!-- 1. Cabecera con Avatar, Cédula, Nombre y Contacto -->
            <div class="flex items-start justify-between gap-4">
                <div class="flex items-start sm:items-center gap-3.5 sm:gap-4 flex-1 min-w-0">
                    <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-2xl bg-emerald-600 text-white flex items-center justify-center font-black text-lg sm:text-xl shadow-md shadow-emerald-600/20 shrink-0">
                        {{ selectedGuardian.last_name?.charAt(0) || 'R' }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="px-2.5 py-0.5 bg-emerald-100 text-emerald-800 rounded-lg text-xs font-black uppercase">
                                {{ selectedGuardian.cedula }}
                            </span>
                            <span class="text-sm font-black text-slate-800 uppercase truncate">
                                {{ selectedGuardian.last_name }}, {{ selectedGuardian.first_name }}
                            </span>
                        </div>
                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs font-bold text-slate-500 mt-1">
                            <span v-if="selectedGuardian.relationship" class="text-primary-600 font-black uppercase">
                                <i class="fas fa-users mr-1"></i> Parentesco: {{ selectedGuardian.relationship }}
                            </span>
                            <span v-if="selectedGuardian.phone">
                                <i class="fas fa-mobile-alt mr-1 text-slate-400"></i> Móvil: {{ selectedGuardian.phone }}
                            </span>
                            <span v-if="selectedGuardian.phone_home">
                                <i class="fas fa-phone mr-1 text-slate-400"></i> Fijo: {{ selectedGuardian.phone_home }}
                            </span>
                        </div>
                    </div>
                </div>

                <span class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200/80 rounded-xl text-[11px] font-black uppercase tracking-wider shrink-0">
                    <i class="fas fa-check-circle text-emerald-500"></i> Asignado
                </span>
            </div>

            <!-- 2. Información Adicional: Instrucción, Ocupación y Dirección Completa -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-3 border-t border-slate-100 text-xs font-bold text-slate-600">
                <div v-if="selectedGuardian.education_level">
                    <span class="text-slate-400 font-medium">Instrucción:</span> {{ selectedGuardian.education_level }}
                </div>
                <div v-if="selectedGuardian.occupation">
                    <span class="text-slate-400 font-medium">Ocupación:</span> {{ selectedGuardian.occupation }}
                </div>
                <div v-if="selectedGuardian.address" class="sm:col-span-3">
                    <span class="text-slate-400 font-medium">Dirección:</span> {{ selectedGuardian.address }}
                </div>
            </div>

            <!-- 3. Pie de Tarjeta: Acción Secundaria para Cambiar Representante -->
            <div class="pt-3 border-t border-slate-100 flex items-center justify-end">
                <button
                    type="button"
                    @click="removeGuardian"
                    class="w-full sm:w-auto px-4 py-2.5 bg-slate-100 hover:bg-rose-50 text-slate-600 hover:text-rose-600 border border-slate-200 hover:border-rose-200 rounded-xl text-xs font-black uppercase tracking-wider transition-all flex items-center justify-center gap-2"
                >
                    <i class="fas fa-sync-alt"></i> Cambiar Representante
                </button>
            </div>
        </div>

        <!-- MODO B: Búsqueda o Registro -->
        <div v-else class="space-y-6">
            <!-- Bloque de Búsqueda por Cédula -->
            <div class="bg-white border-2 border-slate-100 rounded-3xl p-6 shadow-sm space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="text-sm font-black text-slate-800 uppercase tracking-wider">
                            <i class="fas fa-search text-primary-500 mr-1.5"></i> Buscar Representante Registrado
                        </h4>
                        <p class="text-xs text-slate-400 font-bold mt-0.5">
                            Si ya tiene hijos inscritos o está registrado en el colegio, ingrese su documento
                        </p>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row gap-3">
                    <div class="flex bg-slate-50 border-2 rounded-2xl p-1 flex-1 transition-all"
                         :class="searchError ? 'border-red-300 bg-red-50/20' : 'border-slate-200 focus-within:border-primary-400 focus-within:bg-white'">
                        <select
                            v-model="searchDocType"
                            class="bg-transparent font-black text-slate-700 text-xs px-3 py-2.5 border-r border-slate-200 focus:outline-none cursor-pointer"
                        >
                            <option value="V">V-</option>
                            <option value="E">E-</option>
                            <option value="P">P-</option>
                        </select>
                        <input
                            :value="searchDocNumber"
                            @input="onSearchDocInput"
                            @keydown="(e) => allowOnlyDocKey(e, searchDocType)"
                            @keydown.enter.prevent="search"
                            type="text"
                            :placeholder="searchDocType === 'P' ? 'Ej: ABC123456 (6-15 caracteres)' : 'Ej: 12345678 (6-9 dígitos numéricos)'"
                            class="w-full bg-transparent border-0 px-3 py-2 text-sm font-bold text-slate-800 placeholder:text-slate-300 focus:ring-0 outline-none uppercase"
                        />
                    </div>

                    <button
                        type="button"
                        @click="search"
                        :disabled="searching || !searchDocNumber.trim()"
                        class="px-6 py-3 bg-slate-800 hover:bg-slate-700 text-white rounded-2xl text-xs font-black uppercase tracking-wider transition-all disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center gap-2 shadow-sm shrink-0"
                    >
                        <i v-if="searching" class="fas fa-circle-notch fa-spin"></i>
                        <i v-else class="fas fa-search"></i>
                        Buscar
                    </button>
                </div>

                <p v-if="searchError" class="text-xs text-red-500 font-bold ml-1">{{ searchError }}</p>

                <!-- Aviso si no existe y botón para abrir formulario -->
                <div v-if="notFound && !showCreateForm" class="p-4 bg-amber-50/80 border-2 border-amber-200 rounded-2xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 animate-fade-in">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-info-circle text-amber-500 text-lg shrink-0"></i>
                        <div>
                            <p class="text-xs font-black text-amber-900">
                                No se encontró representante con documento {{ builtSearchCedula }}
                            </p>
                            <p class="text-[11px] text-amber-700 font-bold">
                                Ingrese los datos a continuación para registrar la ficha completa del familiar.
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="openCreateFormWithDoc(searchDocType, searchDocNumber)"
                        class="px-4 py-2 bg-amber-600 hover:bg-amber-500 text-white rounded-xl text-xs font-black uppercase tracking-wider shadow-sm transition-all shrink-0"
                    >
                        <i class="fas fa-user-plus mr-1"></i> Completar Ficha
                    </button>
                </div>
            </div>

            <!-- Formulario Completo de Alta Rápida Organizado Lógicamente -->
            <div v-if="showCreateForm" class="bg-white border-2 border-slate-100 rounded-3xl p-6 sm:p-8 shadow-sm space-y-8 animate-fade-in-up">
                <!-- Encabezado del Formulario -->
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-4">
                    <div class="flex-1 min-w-0 pr-1">
                        <h4 class="text-sm font-black text-slate-800 uppercase tracking-wider flex items-center gap-2">
                            <span class="w-7 h-7 rounded-lg bg-primary-100 text-primary-600 flex items-center justify-center text-xs font-black shrink-0">
                                <i class="fas fa-user-plus"></i>
                            </span>
                            <span class="truncate">Representante Legal</span>
                        </h4>
                        <p class="text-xs text-slate-400 font-bold mt-1">
                            Complete los datos requeridos (*) para registrar y asignar al representante legal
                        </p>
                    </div>

                    <button
                        type="button"
                        @click="showCreateForm = false"
                        class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-rose-50 text-slate-400 hover:text-rose-600 border border-slate-200/80 hover:border-rose-200 flex items-center justify-center shrink-0 transition-all shadow-xs"
                        title="Cerrar formulario"
                        aria-label="Cerrar formulario"
                    >
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </div>

                <!-- 1. IDENTIFICACIÓN Y PARENTESCO -->
                <div class="space-y-4">
                    <div class="flex items-center gap-2 text-xs font-black text-slate-700 uppercase tracking-wider border-b border-slate-100 pb-2">
                        <i class="fas fa-id-card text-primary-500"></i>
                        <span>1. Datos de Identificación y Filiación</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                        <!-- Cédula / Documento (Criterio de búsqueda bloqueado no editable) -->
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-black text-slate-500 uppercase tracking-wider ml-1 flex items-center justify-between">
                                <span>Cédula de Identidad <span class="text-red-500">*</span></span>
                                <!-- <span class="text-[10px] text-amber-600 font-black flex items-center gap-1">
                                    <i class="fas fa-lock text-[9px]"></i> Criterio de búsqueda
                                </span> -->
                            </label>
                            <div class="flex items-center bg-slate-100/90 border-2 border-slate-200 rounded-2xl px-4 py-2.5 text-slate-700 select-none shadow-inner cursor-not-allowed">
                                <span class="px-2.5 py-0.5 rounded-lg bg-slate-200 text-slate-800 text-xs font-black mr-2.5">
                                    {{ form.doc_type }}-
                                </span>
                                <span class="text-sm font-black tracking-wider text-slate-800 flex-1">
                                    {{ form.doc_number }}
                                </span>
                                <i class="fas fa-lock text-slate-400 text-xs" title="No modificable porque es el criterio de búsqueda"></i>
                            </div>
                            <p v-if="createErrors.cedula" class="text-xs text-red-500 font-bold ml-1">{{ createErrors.cedula[0] }}</p>
                        </div>

                        <!-- Parentesco con el Estudiante -->
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-black text-slate-500 uppercase tracking-wider ml-1">
                                Parentesco con el Alumno <span class="text-red-500">*</span>
                            </label>
                            <select
                                v-model="form.relationship"
                                class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl px-4 py-2.5 text-xs font-bold text-slate-800 focus:border-primary-400 focus:bg-white outline-none cursor-pointer uppercase transition-all"
                                :class="createErrors.relationship ? 'border-red-300 bg-red-50/20' : ''"
                            >
                                <option value="" disabled>Seleccione parentesco...</option>
                                <option value="MADRE">MADRE</option>
                                <option value="PADRE">PADRE</option>
                                <option value="ABUELO">ABUELO</option>
                                <option value="ABUELA">ABUELA</option>
                                <option value="TIO">TÍO</option>
                                <option value="TIA">TÍA</option>
                                <option value="HERMANO">HERMANO</option>
                                <option value="HERMANA">HERMANA</option>
                                <option value="TUTOR LEGAL">TUTOR LEGAL</option>
                                <option value="OTRO">OTRO</option>
                            </select>
                            <p v-if="createErrors.relationship" class="text-xs text-red-500 font-bold ml-1">{{ createErrors.relationship[0] }}</p>
                        </div>

                        <!-- Nacionalidad (Botones tipo píldora coherentes con la UI) -->
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-black text-slate-500 uppercase tracking-wider ml-1">
                                Nacionalidad <span class="text-red-500">*</span>
                            </label>
                            <div class="grid grid-cols-2 gap-2">
                                <button
                                    type="button"
                                    @click="form.nationality = 'V'"
                                    :class="form.nationality === 'V' ? 'bg-primary-600 text-white border-primary-600 shadow-md shadow-primary-600/20' : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100'"
                                    class="py-2.5 px-3 rounded-2xl border-2 text-xs font-black uppercase tracking-wider flex items-center justify-center gap-1.5 transition-all"
                                >
                                    Venezolana (V)
                                </button>
                                <button
                                    type="button"
                                    @click="form.nationality = 'E'"
                                    :class="form.nationality === 'E' ? 'bg-primary-600 text-white border-primary-600 shadow-md shadow-primary-600/20' : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100'"
                                    class="py-2.5 px-3 rounded-2xl border-2 text-xs font-black uppercase tracking-wider flex items-center justify-center gap-1.5 transition-all"
                                >
                                    Extranjera (E)
                                </button>
                            </div>
                        </div>

                        <!-- Nombres -->
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-black text-slate-500 uppercase tracking-wider ml-1">
                                Nombres <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.first_name"
                                @input="form.first_name = ($event.target.value || '').toUpperCase(); delete createErrors.first_name"
                                type="text"
                                placeholder="EJ: MARÍA TERESA"
                                class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl px-4 py-2.5 text-xs font-bold text-slate-800 uppercase focus:border-primary-400 focus:bg-white outline-none transition-all"
                                :class="createErrors.first_name ? 'border-red-300 bg-red-50/20' : ''"
                            />
                            <p v-if="createErrors.first_name" class="text-xs text-red-500 font-bold ml-1">{{ createErrors.first_name[0] }}</p>
                        </div>

                        <!-- Apellidos -->
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-black text-slate-500 uppercase tracking-wider ml-1">
                                Apellidos <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.last_name"
                                @input="form.last_name = ($event.target.value || '').toUpperCase(); delete createErrors.last_name"
                                type="text"
                                placeholder="EJ: RODRÍGUEZ PÉREZ"
                                class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl px-4 py-2.5 text-xs font-bold text-slate-800 uppercase focus:border-primary-400 focus:bg-white outline-none transition-all"
                                :class="createErrors.last_name ? 'border-red-300 bg-red-50/20' : ''"
                            />
                            <p v-if="createErrors.last_name" class="text-xs text-red-500 font-bold ml-1">{{ createErrors.last_name[0] }}</p>
                        </div>

                        <!-- Lugar de Nacimiento -->
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-black text-slate-500 uppercase tracking-wider ml-1">
                                Lugar de Nacimiento <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.birth_place"
                                @input="form.birth_place = ($event.target.value || '').toUpperCase(); delete createErrors.birth_place"
                                type="text"
                                placeholder="EJ: BARQUISIMETO, EDO. LARA"
                                class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl px-4 py-2.5 text-xs font-bold text-slate-800 uppercase focus:border-primary-400 focus:bg-white outline-none transition-all"
                                :class="createErrors.birth_place ? 'border-red-300 bg-red-50/20' : ''"
                            />
                            <p v-if="createErrors.birth_place" class="text-xs text-red-500 font-bold ml-1">{{ createErrors.birth_place[0] }}</p>
                        </div>

                        <!-- Fecha de Nacimiento (con indicador de edad) -->
                        <div class="space-y-1.5 md:col-span-2 lg:col-span-1">
                            <label class="text-[11px] font-black text-slate-500 uppercase tracking-wider ml-1 flex items-center justify-between">
                                <span>Fecha de Nacimiento <span class="text-red-500">*</span></span>
                                <span v-if="calculatedAge !== null" class="text-xs font-black text-primary-600 bg-primary-50 px-2 py-0.5 rounded-lg">
                                    {{ calculatedAge }} años
                                </span>
                            </label>
                            <input
                                v-model="form.birth_date"
                                @change="validateBirthDate"
                                :min="minBirthDate"
                                :max="maxBirthDate"
                                type="date"
                                class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl px-4 py-2.5 text-xs font-bold text-slate-800 focus:border-primary-400 focus:bg-white outline-none transition-all cursor-pointer"
                                :class="createErrors.birth_date ? 'border-red-300 bg-red-50/20' : ''"
                            />
                            <p v-if="createErrors.birth_date" class="text-xs text-red-500 font-bold ml-1">{{ createErrors.birth_date[0] }}</p>
                        </div>
                    </div>
                </div>

                <!-- 2. FORMACIÓN Y SITUACIÓN LABORAL -->
                <div class="space-y-4">
                    <div class="flex items-center gap-2 text-xs font-black text-slate-700 uppercase tracking-wider border-b border-slate-100 pb-2">
                        <i class="fas fa-briefcase text-primary-500"></i>
                        <span>2. Formación Académica y Situación Laboral</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                        <!-- Nivel de Instrucción -->
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-black text-slate-500 uppercase tracking-wider ml-1">
                                Nivel de Instrucción <span class="text-red-500">*</span>
                            </label>
                            <select
                                v-model="form.education_level"
                                class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl px-4 py-2.5 text-xs font-bold text-slate-800 focus:border-primary-400 focus:bg-white outline-none cursor-pointer uppercase transition-all"
                                :class="createErrors.education_level ? 'border-red-300 bg-red-50/20' : ''"
                            >
                                <option value="" disabled>Seleccione nivel...</option>
                                <option value="SIN INSTRUCCION">SIN INSTRUCCIÓN</option>
                                <option value="PRIMARIA">PRIMARIA</option>
                                <option value="SECUNDARIA">SECUNDARIA</option>
                                <option value="BACHILLER">BACHILLER</option>
                                <option value="TECNICO MEDIO">TÉCNICO MEDIO</option>
                                <option value="T.S.U.">T.S.U.</option>
                                <option value="UNIVERSITARIO">UNIVERSITARIO</option>
                                <option value="POSTGRADO">POSTGRADO</option>
                            </select>
                            <p v-if="createErrors.education_level" class="text-xs text-red-500 font-bold ml-1">{{ createErrors.education_level[0] }}</p>
                        </div>

                        <!-- ¿Trabaja Actualmente? (Botones tipo píldora) -->
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-black text-slate-500 uppercase tracking-wider ml-1">
                                ¿Trabaja Actualmente? <span class="text-red-500">*</span>
                            </label>
                            <div class="grid grid-cols-2 gap-2">
                                <button
                                    type="button"
                                    @click="form.is_working = true"
                                    :class="form.is_working ? 'bg-emerald-600 text-white border-emerald-600 shadow-md shadow-emerald-600/20' : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100'"
                                    class="py-2.5 px-3 rounded-2xl border-2 text-xs font-black uppercase tracking-wider flex items-center justify-center gap-1.5 transition-all"
                                >
                                    <i class="fas fa-check-circle"></i> Sí Trabaja
                                </button>
                                <button
                                    type="button"
                                    @click="form.is_working = false; form.work_sector = null; form.work_type = null; delete createErrors.work_sector; delete createErrors.work_type"
                                    :class="!form.is_working ? 'bg-slate-800 text-white border-slate-800 shadow-md shadow-slate-800/20' : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100'"
                                    class="py-2.5 px-3 rounded-2xl border-2 text-xs font-black uppercase tracking-wider flex items-center justify-center gap-1.5 transition-all"
                                >
                                    <i class="fas fa-times-circle"></i> No Trabaja
                                </button>
                            </div>
                        </div>

                        <!-- Profesión u Ocupación -->
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-black text-slate-500 uppercase tracking-wider ml-1">
                                Profesión u Ocupación
                            </label>
                            <input
                                v-model="form.occupation"
                                @input="form.occupation = ($event.target.value || '').toUpperCase()"
                                type="text"
                                placeholder="EJ: DOCENTE, COMERCIANTE, MECÁNICO"
                                class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl px-4 py-2.5 text-xs font-bold text-slate-800 uppercase focus:border-primary-400 focus:bg-white outline-none transition-all"
                            />
                        </div>

                        <!-- Sector Laboral (Condicional) -->
                        <div v-if="form.is_working" class="space-y-1.5 animate-fade-in">
                            <label class="text-[11px] font-black text-slate-500 uppercase tracking-wider ml-1">
                                Sector Laboral <span class="text-red-500">*</span>
                            </label>
                            <div class="grid grid-cols-2 gap-2">
                                <button
                                    type="button"
                                    @click="form.work_sector = 'PUBLICO'; delete createErrors.work_sector"
                                    :class="form.work_sector === 'PUBLICO' ? 'bg-primary-600 text-white border-primary-600 shadow-md shadow-primary-600/20' : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100'"
                                    class="py-2.5 px-3 rounded-2xl border-2 text-xs font-black uppercase tracking-wider flex items-center justify-center gap-1.5 transition-all"
                                >
                                    <i class="fas fa-landmark"></i> Público
                                </button>
                                <button
                                    type="button"
                                    @click="form.work_sector = 'PRIVADO'; delete createErrors.work_sector"
                                    :class="form.work_sector === 'PRIVADO' ? 'bg-primary-600 text-white border-primary-600 shadow-md shadow-primary-600/20' : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100'"
                                    class="py-2.5 px-3 rounded-2xl border-2 text-xs font-black uppercase tracking-wider flex items-center justify-center gap-1.5 transition-all"
                                >
                                    <i class="fas fa-building"></i> Privado
                                </button>
                            </div>
                            <p v-if="createErrors.work_sector" class="text-xs text-red-500 font-bold ml-1">{{ createErrors.work_sector[0] }}</p>
                        </div>

                        <!-- Tipo de Trabajo (Condicional) -->
                        <div v-if="form.is_working" class="space-y-1.5 md:col-span-2 lg:col-span-2 animate-fade-in">
                            <label class="text-[11px] font-black text-slate-500 uppercase tracking-wider ml-1">
                                Tipo de Trabajo <span class="text-red-500">*</span>
                            </label>
                            <select
                                v-model="form.work_type"
                                @change="delete createErrors.work_type"
                                class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl px-4 py-2.5 text-xs font-bold text-slate-800 focus:border-primary-400 focus:bg-white outline-none cursor-pointer uppercase transition-all"
                                :class="createErrors.work_type ? 'border-red-300 bg-red-50/20' : ''"
                            >
                                <option value="" disabled>Seleccione tipo de trabajo...</option>
                                <option value="EMPLEADO FIJO">EMPLEADO FIJO</option>
                                <option value="CONTRATADO">CONTRATADO</option>
                                <option value="INDEPENDIENTE / CUENTA PROPIA">INDEPENDIENTE / CUENTA PROPIA</option>
                                <option value="EVENTUAL / INFORMAL">EVENTUAL / INFORMAL</option>
                            </select>
                            <p v-if="createErrors.work_type" class="text-xs text-red-500 font-bold ml-1">{{ createErrors.work_type[0] }}</p>
                        </div>
                    </div>
                </div>

                <!-- 3. CONTACTO Y HABITACIÓN -->
                <div class="space-y-4">
                    <div class="flex items-center gap-2 text-xs font-black text-slate-700 uppercase tracking-wider border-b border-slate-100 pb-2">
                        <i class="fas fa-address-book text-primary-500"></i>
                        <span>3. Datos de Contacto y Ubicación</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                        <!-- Teléfono Móvil (Principal) -->
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-black text-slate-500 uppercase tracking-wider ml-1 flex items-center justify-between">
                                <span>Teléfono Móvil</span>
                                <!-- <span class="text-[10px] text-slate-400 font-bold">Solo números</span> -->
                            </label>
                            <input
                                :value="form.phone"
                                @input="onPhoneMobileInput"
                                @keydown="allowOnlyNumbersKey"
                                type="tel"
                                inputmode="numeric"
                                maxlength="12"
                                placeholder="Ej: 0414-1234567"
                                class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl px-4 py-2.5 text-xs font-bold text-slate-800 focus:border-primary-400 focus:bg-white outline-none transition-all"
                                :class="createErrors.phone ? 'border-red-300 bg-red-50/20' : ''"
                            />
                            <p v-if="createErrors.phone" class="text-xs text-red-500 font-bold ml-1">{{ createErrors.phone[0] }}</p>
                        </div>

                        <!-- Teléfono Fijo -->
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
                                class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl px-4 py-2.5 text-xs font-bold text-slate-800 focus:border-primary-400 focus:bg-white outline-none transition-all"
                                :class="createErrors.phone_home ? 'border-red-300 bg-red-50/20' : ''"
                            />
                            <p v-if="createErrors.phone_home" class="text-xs text-red-500 font-bold ml-1">{{ createErrors.phone_home[0] }}</p>
                        </div>

                        <!-- Correo Electrónico -->
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-black text-slate-500 uppercase tracking-wider ml-1">
                                Correo Electrónico
                            </label>
                            <input
                                v-model="form.email"
                                @input="form.email = ($event.target.value || '').toLowerCase()"
                                type="email"
                                placeholder="Ej: representante@ejemplo.com"
                                class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl px-4 py-2.5 text-xs font-bold text-slate-800 focus:border-primary-400 focus:bg-white outline-none transition-all lowercase"
                                :class="createErrors.email ? 'border-red-300 bg-red-50/20' : ''"
                            />
                            <p v-if="createErrors.email" class="text-xs text-red-500 font-bold ml-1">{{ createErrors.email[0] }}</p>
                        </div>

                        <!-- Dirección de Habitación del Representante (Ocupa las 3 columnas) -->
                        <div class="space-y-1.5 md:col-span-3">
                            <label class="text-[11px] font-black text-slate-500 uppercase tracking-wider ml-1">
                                Dirección Exacta de Habitación <span class="text-red-500">*</span>
                            </label>
                            <textarea
                                v-model="form.address"
                                @input="form.address = ($event.target.value || '').toUpperCase(); delete createErrors.address"
                                rows="2"
                                placeholder="EJ: CALLE 4, SECTOR EL MOLINO, CASA N° 12"
                                class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl px-4 py-2.5 text-xs font-bold text-slate-800 uppercase focus:border-primary-400 focus:bg-white outline-none transition-all resize-none"
                                :class="createErrors.address ? 'border-red-300 bg-red-50/20' : ''"
                            ></textarea>
                            <p v-if="createErrors.address" class="text-xs text-red-500 font-bold ml-1">{{ createErrors.address[0] }}</p>
                        </div>
                    </div>
                </div>

                <!-- Botonera de Guardar / Cancelar -->
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                    <button
                        type="button"
                        @click="showCreateForm = false"
                        class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-black uppercase tracking-wider transition-all"
                    >
                        Cancelar
                    </button>
                    <button
                        type="button"
                        @click="submitCreate"
                        :disabled="saving"
                        class="px-6 py-2.5 bg-primary-600 hover:bg-primary-500 text-white rounded-xl text-xs font-black uppercase tracking-wider transition-all shadow-md shadow-primary-600/20 disabled:opacity-50 flex items-center gap-2"
                    >
                        <i v-if="saving" class="fas fa-circle-notch fa-spin"></i>
                        <i v-else class="fas fa-save"></i>
                        Guardar
                    </button>
                </div>
            </div>
        </div>

        <p v-if="error" class="text-xs text-red-500 font-bold ml-1">{{ error }}</p>
    </div>
</template>
