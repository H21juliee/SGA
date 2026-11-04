<script setup>
import { ref, reactive, computed } from 'vue'
import axios from 'axios'
import Modal from '@/Components/UI/Modal.vue'

defineProps({ show: Boolean })
const emit = defineEmits(['close', 'created'])

const emptyStudent = () => ({
    first_name: '', last_name: '', cedula: '', birth_date: '', gender: 'M', address: '',
})

const student = reactive(emptyStudent())
const errors = ref({})
const processing = ref(false)

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

function onDocTypeChange() {
    delete errors.value.cedula
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
        delete errors.value.cedula
        return true
    }
    const num = docNumber.value.trim()
    if (docType.value === 'V' || docType.value === 'E') {
        if (!/^\d{6,9}$/.test(num)) {
            errors.value.cedula = ['La cédula debe contener entre 6 y 9 dígitos numéricos.']
            return false
        }
    } else if (docType.value === 'CE') {
        if (!/^[VE][1-9]\d{10}$/.test(num)) {
            errors.value.cedula = ['La cédula escolar debe tener 12 caracteres oficiales (ej: V11214200350).']
            return false
        }
    } else if (docType.value === 'P') {
        if (!/^[A-Z0-9]{6,15}$/.test(num)) {
            errors.value.cedula = ['El pasaporte debe tener entre 6 y 15 caracteres alfanuméricos.']
            return false
        }
    }
    delete errors.value.cedula
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

function reset() {
    Object.assign(student, emptyStudent())
    errors.value = {}
    docType.value = 'V'
    docNumber.value = ''
}

function close() {
    reset()
    emit('close')
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
    if (!student.birth_date) {
        delete errors.value.birth_date
        return true
    }
    if (student.birth_date > maxBirthDate.value) {
        errors.value.birth_date = ['El estudiante debe tener al menos 10 años de edad.']
        return false
    }
    if (student.birth_date < minBirthDate.value) {
        errors.value.birth_date = ['El estudiante no puede tener más de 20 años de edad.']
        return false
    }
    delete errors.value.birth_date
    return true
}

function submit() {
    errors.value = {}

    if (!validateBirthDate() || !validateDocNumber()) {
        return
    }
    processing.value = true
    axios.post('/admin/enrollments/quick-student', {
        ...student,
        cedula: buildCedulaValue(),
    })
        .then(({ data }) => {
            emit('created', { ...data.student })
            reset()
        })
        .catch((e) => {
            if (e.response?.status === 422) errors.value = e.response.data.errors
        })
        .finally(() => { processing.value = false })
}

const inputClass = 'w-full bg-slate-50 border-2 border-slate-200 rounded-2xl px-4 py-2.5 text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all'
const labelClass = 'text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1'
</script>

<template>
    <Modal :show="show" @close="close" maxWidth="2xl">
        <div class="p-8 max-h-[85vh] overflow-y-auto">
            <div class="flex items-center gap-4 mb-6">
                <div class="w-12 h-12 rounded-2xl bg-primary-50 text-primary-600 flex items-center justify-center text-xl border border-primary-100">
                    <i class="fas fa-user-plus"></i>
                </div>
                <div>
                    <h3 class="text-xl font-black text-slate-800 leading-tight">Registrar alumno nuevo</h3>
                    <p class="text-xs font-medium text-slate-400 mt-1">Al guardar, quedará seleccionado para inscribirlo.</p>
                </div>
            </div>

            <form novalidate @submit.prevent="submit" class="space-y-5">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label :class="labelClass">Nombres *</label>
                        <input 
                            v-model="student.first_name" 
                            @input="student.first_name = ($event.target.value || '').toUpperCase()"
                            type="text" 
                            placeholder="EJ: CARLOS JOSÉ"
                            required 
                            :class="[inputClass, 'uppercase']"
                        >
                        <p v-if="errors.first_name" class="text-xs text-red-500 font-bold ml-1">{{ errors.first_name[0] }}</p>
                    </div>
                    <div class="space-y-1.5">
                        <label :class="labelClass">Apellidos *</label>
                        <input 
                            v-model="student.last_name" 
                            @input="student.last_name = ($event.target.value || '').toUpperCase()"
                            type="text" 
                            placeholder="EJ: PÉREZ GÓMEZ"
                            required 
                            :class="[inputClass, 'uppercase']"
                        >
                        <p v-if="errors.last_name" class="text-xs text-red-500 font-bold ml-1">{{ errors.last_name[0] }}</p>
                    </div>
                    <div class="space-y-1.5 sm:col-span-2">
                        <div class="flex items-center justify-between">
                            <label :class="labelClass">Documento de Identidad / Cédula</label>
                            <span class="text-[10px] font-bold text-slate-400">Opcional</span>
                        </div>
                        <div class="flex flex-col sm:flex-row gap-2">
                            <div class="sm:w-44 shrink-0">
                                <select 
                                    v-model="docType" 
                                    @change="onDocTypeChange" 
                                    class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl px-3 py-2.5 text-slate-700 text-xs font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all shadow-sm cursor-pointer"
                                >
                                    <option value="V">V- (Venezolano)</option>
                                    <option value="E">E- (Extranjero)</option>
                                    <option value="CE">Cédula Escolar</option>
                                    <option value="P">P- (Pasaporte)</option>
                                    <option value="NONE">Sin documento</option>
                                </select>
                            </div>
                            <div class="flex-1">
                                <input 
                                    v-model="docNumber" 
                                    @input="onDocNumberInput"
                                    type="text" 
                                    :disabled="docType === 'NONE'"
                                    :placeholder="docPlaceholder"
                                    :maxlength="docMaxLength"
                                    :class="inputClass"
                                >
                            </div>
                        </div>
                        <div v-if="docType === 'NONE'" class="mt-2 p-3 bg-amber-50 border border-amber-200 rounded-xl text-amber-800 text-xs flex items-start gap-2">
                            <i class="fas fa-exclamation-triangle mt-0.5 text-amber-500 shrink-0"></i>
                            <span>El alumno quedará registrado <strong>sin documento</strong>. Asegúrese de que sus nombres, apellidos y fecha de nacimiento sean exactos para evitar registros duplicados.</span>
                        </div>
                        <p v-if="errors.cedula" class="text-xs text-red-500 font-bold ml-1">{{ errors.cedula[0] }}</p>
                    </div>
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <label :class="labelClass">Fecha de nacimiento *</label>
                            <span class="text-[10px] font-bold text-slate-400">10 a 20 años</span>
                        </div>
                        <input 
                            v-model="student.birth_date" 
                            @input="validateBirthDate"
                            @change="validateBirthDate"
                            type="date" 
                            :min="minBirthDate" 
                            :max="maxBirthDate" 
                            required 
                            :class="inputClass"
                        >
                        <p v-if="errors.birth_date" class="text-xs text-red-500 font-bold ml-1">{{ errors.birth_date[0] }}</p>
                    </div>
                    <div class="space-y-1.5">
                        <label :class="labelClass">Género *</label>
                        <select v-model="student.gender" :class="inputClass">
                            <option value="M">Masculino</option>
                            <option value="F">Femenino</option>
                        </select>
                    </div>
                    <div class="space-y-1.5">
                        <label :class="labelClass">Dirección</label>
                        <input v-model="student.address" type="text" placeholder="Opcional" :class="inputClass">
                    </div>
                </div>

                <div class="flex items-center justify-end gap-4 pt-2">
                    <button type="button" @click="close" class="px-6 py-3 text-sm font-black uppercase tracking-widest text-slate-400 hover:text-slate-600 transition-all">Cancelar</button>
                    <button type="submit" :disabled="processing" class="px-8 py-3 bg-primary-600 text-white text-[11px] font-black uppercase tracking-widest rounded-2xl shadow-lg hover:bg-primary-700 hover:-translate-y-0.5 transition-all disabled:opacity-50">Guardar y continuar</button>
                </div>
            </form>
        </div>
    </Modal>
</template>
