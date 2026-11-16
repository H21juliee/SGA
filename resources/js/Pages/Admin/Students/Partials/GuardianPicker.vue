<script setup>
import { ref, watch, computed } from 'vue'
import axios from 'axios'

const props = defineProps({
    modelValue: [Number, String, null],
    initialGuardian: { type: Object, default: null },
    error: String,
})

const emit = defineEmits(['update:modelValue', 'update:guardian'])

const selectedGuardian = ref(props.initialGuardian)
const docType = ref('V')
const docNumber = ref('')
const searching = ref(false)
const notFound = ref(false)
const searchError = ref('')
const showCreateForm = ref(false)
const saving = ref(false)
const createErrors = ref({})

// Documento del formulario de nuevo representante
const createDocType = ref('V')
const createDocNumber = ref('')
const createForm = ref({
    first_name: '',
    last_name: '',
    phone: '',
    email: '',
})

watch(() => props.initialGuardian, (val) => {
    if (val) {
        selectedGuardian.value = val
    }
}, { immediate: true })

watch(() => props.modelValue, (val) => {
    if (!val) {
        selectedGuardian.value = null
    }
})

const maxLen = computed(() => (docType.value === 'P' ? 15 : 9))

const docPlaceholder = computed(() => {
    switch (docType.value) {
        case 'V': return 'Ej: 12345678 (6-9 dígitos)'
        case 'E': return 'Ej: 84550210 (6-9 dígitos)'
        case 'P': return 'Ej: ABC123456 (6-15 caracteres)'
        default: return 'Número de documento'
    }
})

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
        if (!/^[a-zA-Z0-9]$/.test(e.key)) {
            e.preventDefault()
        }
    } else {
        if (!/^[0-9]$/.test(e.key)) {
            e.preventDefault()
        }
    }
}

function onDocInput(e) {
    let v = (e.target.value || '').toUpperCase()
    v = docType.value === 'P' ? v.replace(/[^A-Z0-9]/g, '') : v.replace(/[^0-9]/g, '')
    docNumber.value = v.slice(0, maxLen.value)
    e.target.value = docNumber.value
    searchError.value = ''
    notFound.value = false
}

function onDocTypeChange() {
    docNumber.value = ''
    searchError.value = ''
    notFound.value = false
}

const builtCedula = computed(() => `${docType.value}-${docNumber.value.trim()}`)

function validDoc() {
    const n = docNumber.value.trim()
    if (docType.value === 'P') return /^[A-Z0-9]{6,15}$/.test(n)
    return /^\d{6,9}$/.test(n)
}

const venezuelanPhoneRegex = /^(0412|0414|0424|0416|0426|02\d{2})-\d{7}$/

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

function onPhoneInput(e) {
    const formatted = formatPhone(e.target.value)
    createForm.value.phone = formatted
    e.target.value = formatted
    if (createErrors.value.phone && venezuelanPhoneRegex.test(createForm.value.phone)) {
        delete createErrors.value.phone
    }
}

function onCreateDocInput(e) {
    let v = (e.target.value || '').toUpperCase()
    v = createDocType.value === 'P' ? v.replace(/[^A-Z0-9]/g, '') : v.replace(/[^0-9]/g, '')
    createDocNumber.value = v.slice(0, createDocType.value === 'P' ? 15 : 9)
    e.target.value = createDocNumber.value
    if (createErrors.value.cedula) {
        delete createErrors.value.cedula
    }
}

function search() {
    searchError.value = ''
    if (!validDoc()) {
        searchError.value = docType.value === 'P'
            ? 'El pasaporte debe contener entre 6 y 15 caracteres alfanuméricos.'
            : 'La cédula debe contener entre 6 y 9 dígitos numéricos.'
        return
    }

    searching.value = true
    notFound.value = false
    showCreateForm.value = false

    axios.get('/admin/guardians/search', { params: { cedula: builtCedula.value } })
        .then(({ data }) => {
            if (data.guardian) {
                select(data.guardian)
            } else {
                notFound.value = true
                createDocType.value = docType.value
                createDocNumber.value = docNumber.value
            }
        })
        .catch(() => {
            searchError.value = 'No se pudo realizar la búsqueda. Verifique la conexión.'
        })
        .finally(() => {
            searching.value = false
        })
}

function select(guardian) {
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

function openCreate() {
    showCreateForm.value = true
    createDocType.value = docType.value
    createDocNumber.value = docNumber.value
    createForm.value.first_name = ''
    createForm.value.last_name = ''
    createForm.value.phone = ''
    createForm.value.email = ''
    createErrors.value = {}
}

function submitCreate() {
    createErrors.value = {}
    let isValid = true

    const docNum = (createDocNumber.value || '').trim()
    if (!docNum) {
        createErrors.value.cedula = ['La cédula o documento del representante es obligatorio.']
        isValid = false
    } else if (createDocType.value === 'P') {
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

    if (!createForm.value.first_name || !createForm.value.first_name.trim()) {
        createErrors.value.first_name = ['El nombre del representante es obligatorio.']
        isValid = false
    }

    if (!createForm.value.last_name || !createForm.value.last_name.trim()) {
        createErrors.value.last_name = ['El apellido del representante es obligatorio.']
        isValid = false
    }

    if (createForm.value.phone && createForm.value.phone.trim()) {
        if (!venezuelanPhoneRegex.test(createForm.value.phone.trim())) {
            createErrors.value.phone = ['El número de teléfono debe tener un código válido en Venezuela (0412, 0414, 0424, 0416, 0426 o 02XX) seguido de 7 dígitos. Ej: 0414-1234567.']
            isValid = false
        }
    }

    if (createForm.value.email && createForm.value.email.trim()) {
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(createForm.value.email.trim())) {
            createErrors.value.email = ['El correo electrónico debe tener un formato válido (ej: nombre@correo.com).']
            isValid = false
        }
    }

    if (!isValid) return

    saving.value = true

    const finalCedula = `${createDocType.value}-${docNum}`
    const payload = {
        cedula: finalCedula,
        first_name: createForm.value.first_name.trim().toUpperCase(),
        last_name: createForm.value.last_name.trim().toUpperCase(),
        phone: createForm.value.phone ? createForm.value.phone.trim() : null,
        email: createForm.value.email ? createForm.value.email.trim() : null,
    }

    axios.post('/admin/guardians', payload)
        .then(({ data }) => {
            saving.value = false
            select(data.guardian)
        })
        .catch((e) => {
            saving.value = false
            if (e.response?.status === 422) {
                createErrors.value = e.response.data.errors || {}
            } else {
                searchError.value = 'Error al registrar el representante. Intente nuevamente.'
            }
        })
}
</script>

<template>
    <div class="bg-white border-2 border-slate-100 rounded-3xl p-6 shadow-sm space-y-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-primary-50 text-primary-600 flex items-center justify-center font-black">
                    <i class="fas fa-user-shield text-base"></i>
                </div>
                <div>
                    <h4 class="text-sm font-black text-slate-800 uppercase tracking-wider">Representante Legal</h4>
                    <p class="text-xs text-slate-400 font-bold">Buscar por cédula o registrar si es nuevo</p>
                </div>
            </div>

            <span v-if="selectedGuardian" class="px-3 py-1 bg-emerald-50 text-emerald-600 border border-emerald-200 rounded-xl text-[10px] font-black uppercase tracking-widest">
                Asignado
            </span>
            <span v-else class="px-3 py-1 bg-slate-100 text-slate-500 rounded-xl text-[10px] font-black uppercase tracking-widest">
                Opcional
            </span>
        </div>

        <!-- Si ya está seleccionado -->
        <div v-if="selectedGuardian" class="p-4 bg-emerald-50/60 border-2 border-emerald-200 rounded-2xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 transition-all">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-black text-lg shadow-sm">
                    {{ selectedGuardian.last_name?.charAt(0) || 'R' }}
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-black text-emerald-800 uppercase">{{ selectedGuardian.cedula }}</span>
                        <span class="text-xs text-emerald-600 font-bold">•</span>
                        <span class="text-sm font-black text-slate-800">{{ selectedGuardian.last_name }}, {{ selectedGuardian.first_name }}</span>
                    </div>
                    <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500 font-bold mt-1">
                        <span v-if="selectedGuardian.phone"><i class="fas fa-phone mr-1 text-slate-400"></i>{{ selectedGuardian.phone }}</span>
                        <span v-if="selectedGuardian.email"><i class="fas fa-envelope mr-1 text-slate-400"></i>{{ selectedGuardian.email }}</span>
                    </div>
                </div>
            </div>

            <button
                type="button"
                @click="removeGuardian"
                class="px-4 py-2 bg-white text-slate-600 hover:text-red-600 hover:bg-red-50 border border-slate-200 hover:border-red-200 rounded-xl text-xs font-black uppercase tracking-wider transition-all shrink-0"
            >
                <i class="fas fa-times mr-1.5"></i> Cambiar
            </button>
        </div>

        <!-- Si no está seleccionado: Buscador -->
        <div v-else class="space-y-4">
            <div class="flex flex-col sm:flex-row gap-2">
                <div class="flex bg-slate-50 border-2 border-slate-200 rounded-2xl p-1 flex-1 focus-within:border-primary-400 transition-all">
                    <select
                        v-model="docType"
                        @change="onDocTypeChange"
                        class="bg-transparent font-black text-slate-700 text-xs px-3 py-2 border-r border-slate-200 focus:outline-none cursor-pointer"
                    >
                        <option value="V">V-</option>
                        <option value="E">E-</option>
                        <option value="P">P-</option>
                    </select>
                    <input
                        :value="docNumber"
                        @input="onDocInput"
                        @keydown="(e) => allowOnlyDocKey(e, docType)"
                        @keydown.enter.prevent="search"
                        type="text"
                        :placeholder="docPlaceholder"
                        class="w-full bg-transparent border-0 px-3 py-2 text-sm font-bold text-slate-800 placeholder:text-slate-300 focus:ring-0 outline-none uppercase"
                    />
                </div>

                <button
                    type="button"
                    @click="search"
                    :disabled="searching || !docNumber.trim()"
                    class="px-5 py-3 bg-slate-800 hover:bg-slate-700 text-white rounded-2xl text-xs font-black uppercase tracking-wider transition-all disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center gap-2 shrink-0 shadow-sm"
                >
                    <i v-if="searching" class="fas fa-circle-notch fa-spin"></i>
                    <i v-else class="fas fa-search"></i>
                    Buscar
                </button>
            </div>

            <p v-if="searchError" class="text-xs text-red-500 font-bold ml-1">{{ searchError }}</p>

            <!-- No encontrado: invitación a registrar -->
            <transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0 translate-y-1" enter-to-class="opacity-100 translate-y-0">
                <div v-if="notFound && !showCreateForm" class="p-4 bg-amber-50/70 border-2 border-amber-200 rounded-2xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-info-circle text-amber-500 text-lg"></i>
                        <div>
                            <p class="text-xs font-black text-amber-900">No se encontró representante con documento {{ builtCedula }}</p>
                            <p class="text-[11px] text-amber-700 font-bold">Puedes registrarlo en este momento con datos básicos.</p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="openCreate"
                        class="px-4 py-2 bg-amber-600 hover:bg-amber-500 text-white rounded-xl text-xs font-black uppercase tracking-wider shadow-sm transition-all shrink-0"
                    >
                        <i class="fas fa-user-plus mr-1.5"></i> Registrar Representante
                    </button>
                </div>
            </transition>

            <!-- Formulario de alta rápida -->
            <transition enter-active-class="transition duration-300 ease-out" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0">
                <div v-if="showCreateForm" class="p-5 bg-slate-50/80 border-2 border-slate-200 rounded-2xl space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                        <h5 class="text-xs font-black text-slate-700 uppercase tracking-wider">
                            <i class="fas fa-user-plus mr-1 text-primary-500"></i> Nuevo Representante
                        </h5>
                        <button
                            type="button"
                            @click="showCreateForm = false"
                            class="text-xs text-slate-400 hover:text-slate-600 font-black uppercase"
                        >
                            Cancelar
                        </button>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Documento / Cédula *</label>
                            <div class="flex bg-white border-2 rounded-xl p-0.5 transition-all mt-0.5"
                                 :class="createErrors.cedula ? 'border-red-300 bg-red-50/20' : 'border-slate-200 focus-within:border-primary-400'">
                                <select
                                    v-model="createDocType"
                                    class="bg-transparent font-black text-slate-700 text-xs px-2.5 py-2 border-r border-slate-200 focus:outline-none cursor-pointer"
                                >
                                    <option value="V">V-</option>
                                    <option value="E">E-</option>
                                    <option value="P">P-</option>
                                </select>
                                <input
                                    :value="createDocNumber"
                                    @input="onCreateDocInput"
                                    @keydown="(e) => allowOnlyDocKey(e, createDocType)"
                                    type="text"
                                    :placeholder="createDocType === 'P' ? 'Ej: ABC123456' : 'Ej: 12345678'"
                                    :maxlength="createDocType === 'P' ? 15 : 9"
                                    class="w-full bg-transparent border-0 px-3 py-1.5 text-xs font-bold text-slate-800 placeholder:text-slate-300 focus:ring-0 outline-none uppercase"
                                />
                            </div>
                            <p v-if="createErrors.cedula" class="text-[11px] text-red-500 font-bold mt-1 ml-1">{{ createErrors.cedula[0] }}</p>
                        </div>

                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1 flex items-center justify-between">
                                <span>Teléfono</span>
                                <!-- <span class="text-[9px] text-slate-400 font-bold">Solo números</span> -->
                            </label>
                            <input
                                :value="createForm.phone"
                                @input="onPhoneInput"
                                @keydown="allowOnlyNumbersKey"
                                type="tel"
                                inputmode="numeric"
                                maxlength="12"
                                placeholder="Ej: 0414-1234567"
                                class="w-full bg-white border-2 rounded-xl px-3 py-2 text-xs font-bold text-slate-700 focus:border-primary-400 outline-none mt-0.5"
                                :class="createErrors.phone ? 'border-red-300 bg-red-50/20' : 'border-slate-200'"
                            />
                            <p v-if="createErrors.phone" class="text-[11px] text-red-500 font-bold mt-1 ml-1">{{ createErrors.phone[0] }}</p>
                        </div>

                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Nombres *</label>
                            <input
                                v-model="createForm.first_name"
                                @input="createForm.first_name = ($event.target.value || '').toUpperCase(); if (createErrors.first_name) delete createErrors.first_name"
                                type="text"
                                placeholder="EJ: MARÍA TERESA"
                                class="w-full bg-white border-2 rounded-xl px-3 py-2 text-xs font-bold text-slate-700 focus:border-primary-400 outline-none uppercase mt-0.5"
                                :class="createErrors.first_name ? 'border-red-300 bg-red-50/20' : 'border-slate-200'"
                            />
                            <p v-if="createErrors.first_name" class="text-[11px] text-red-500 font-bold mt-1 ml-1">{{ createErrors.first_name[0] }}</p>
                        </div>

                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Apellidos *</label>
                            <input
                                v-model="createForm.last_name"
                                @input="createForm.last_name = ($event.target.value || '').toUpperCase(); if (createErrors.last_name) delete createErrors.last_name"
                                type="text"
                                placeholder="EJ: PÉREZ GÓMEZ"
                                class="w-full bg-white border-2 rounded-xl px-3 py-2 text-xs font-bold text-slate-700 focus:border-primary-400 outline-none uppercase mt-0.5"
                                :class="createErrors.last_name ? 'border-red-300 bg-red-50/20' : 'border-slate-200'"
                            />
                            <p v-if="createErrors.last_name" class="text-[11px] text-red-500 font-bold mt-1 ml-1">{{ createErrors.last_name[0] }}</p>
                        </div>

                        <div class="sm:col-span-2">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Correo Electrónico (Opcional)</label>
                            <input
                                v-model="createForm.email"
                                type="email"
                                placeholder="Ej: correo@ejemplo.com"
                                class="w-full bg-white border-2 border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-700 focus:border-primary-400 outline-none"
                            />
                            <p v-if="createErrors.email" class="text-[11px] text-red-500 font-bold mt-1 ml-1">{{ createErrors.email[0] }}</p>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button
                            type="button"
                            @click="showCreateForm = false"
                            class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-xl text-xs font-black uppercase tracking-wider transition-all"
                        >
                            Cancelar
                        </button>
                        <button
                            type="button"
                            @click="submitCreate"
                            :disabled="saving"
                            class="px-5 py-2 bg-primary-600 hover:bg-primary-500 text-white rounded-xl text-xs font-black uppercase tracking-wider transition-all shadow-md shadow-primary-600/20 disabled:opacity-50 flex items-center gap-2"
                        >
                            <i v-if="saving" class="fas fa-circle-notch fa-spin"></i>
                            <i v-else class="fas fa-save"></i>
                            Guardar y Asignar
                        </button>
                    </div>
                </div>
            </transition>
        </div>
    </div>
</template>
