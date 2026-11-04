<script setup>
import { ref, reactive, computed } from 'vue'
import axios from 'axios'

const props = defineProps({
    student: Object,
    suggested: { type: Object, default: null }, // representante de la inscripción anterior (hermanos)
})
const emit = defineEmits(['done', 'back'])

// Estados: 'confirm' (ya tiene representante) | 'search' | 'create'
const mode = ref(props.student?.guardian ? 'confirm' : 'search')
const docType = ref('V')
const docNumber = ref('')
const searching = ref(false)
const notFound = ref(false)
const saving = ref(false)
const searchError = ref('')
const errors = ref({})
const form = reactive({ cedula: '', first_name: '', last_name: '', phone: '', email: '' })

const maxLen = computed(() => (docType.value === 'P' ? 15 : 9))

const docPlaceholder = computed(() => {
    switch (docType.value) {
        case 'V': return 'Ej: 12345678 (6-9 dígitos)'
        case 'E': return 'Ej: 84550210 (6-9 dígitos)'
        case 'P': return 'Ej: ABC123456 (6-15 caracteres)'
        default: return 'Número de documento'
    }
})

const inputClass = 'w-full bg-slate-50 border-2 border-slate-200 rounded-2xl px-4 py-2.5 text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all'
const labelClass = 'text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1'

function onDocInput(e) {
    let v = (e.target.value || '').toUpperCase()
    v = docType.value === 'P' ? v.replace(/[^A-Z0-9]/g, '') : v.replace(/[^0-9]/g, '')
    docNumber.value = v.slice(0, maxLen.value)
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

const venezuelanPhoneRegex = /^(0412|0414|0424|0416|0426|02\d{2})-\d{7}$/

function onPhoneInput(e) {
    form.phone = formatPhone(e.target.value)
    if (form.phone.length === 12 || errors.value.phone) {
        validatePhone()
    }
}

function validatePhone() {
    if (!form.phone || !form.phone.trim()) {
        delete errors.value.phone
        return true
    }
    const clean = form.phone.trim()
    if (!venezuelanPhoneRegex.test(clean)) {
        errors.value.phone = ['El número de teléfono debe tener un código válido en Venezuela (0412, 0414, 0424, 0416, 0426 o 02XX) seguido de 7 dígitos. Ej: 0414-1234567.']
        return false
    }
    delete errors.value.phone
    return true
}

function validateEmail() {
    if (!form.email || !form.email.trim()) {
        delete errors.value.email
        return true
    }
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    if (!emailRegex.test(form.email.trim())) {
        errors.value.email = ['El correo electrónico debe tener un formato válido (ej: nombre@correo.com).']
        return false
    }
    delete errors.value.email
    return true
}

// Asigna el representante al alumno (sirve para alumnos nuevos y existentes) y avanza.
function assign(guardian) {
    saving.value = true
    axios.patch(`/admin/enrollments/students/${props.student.id}/guardian`, { guardian_id: guardian.id })
        .then(({ data }) => emit('done', { student: data.student, guardian: data.student.guardian ?? guardian }))
        .catch((e) => {
            searchError.value = e.response?.data?.message || 'No se pudo asignar el representante.'
        })
        .finally(() => { saving.value = false })
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
    axios.get('/admin/guardians/search', { params: { cedula: builtCedula.value } })
        .then(({ data }) => {
            if (data.guardian) {
                assign(data.guardian)
            } else {
                notFound.value = true
                form.cedula = builtCedula.value
            }
        })
        .catch(() => { searchError.value = 'No se pudo realizar la búsqueda. Intenta de nuevo.' })
        .finally(() => { searching.value = false })
}

function startCreate() {
    mode.value = 'create'
    errors.value = {}
    form.cedula = builtCedula.value
    form.first_name = ''
    form.last_name = ''
    form.phone = ''
    form.email = ''
}

function cancelCreate() {
    mode.value = 'search'
    notFound.value = false
    errors.value = {}
}

function createAndAssign() {
    errors.value = {}
    let isValid = true

    if (!form.first_name || !form.first_name.trim()) {
        errors.value.first_name = ['El nombre del representante es obligatorio.']
        isValid = false
    }

    if (!form.last_name || !form.last_name.trim()) {
        errors.value.last_name = ['El apellido del representante es obligatorio.']
        isValid = false
    }

    if (!validatePhone()) isValid = false
    if (!validateEmail()) isValid = false

    if (!isValid) return

    const firstClean = form.first_name.trim().toUpperCase()
    const lastClean = form.last_name.trim().toUpperCase()
    const fullName = `${firstClean} ${lastClean}`

    saving.value = true
    axios.post('/admin/guardians', {
        cedula: form.cedula,
        first_name: firstClean,
        last_name: lastClean,
        name: fullName,
        phone: form.phone ? form.phone.trim() : null,
        email: form.email ? form.email.trim() : null,
    })
        .then(({ data }) => {
            saving.value = false
            assign(data.guardian)
        })
        .catch((e) => {
            saving.value = false
            if (e.response?.status === 422) {
                errors.value = e.response.data.errors
            }
        })
}

function change() {
    mode.value = 'search'
    docNumber.value = ''
    notFound.value = false
    searchError.value = ''
}
</script>

<template>
    <div class="glass-card rounded-3xl p-6 sm:p-8 shadow-xl space-y-6 animate-fade-in-up">
        <!-- Encabezado de alumno seleccionado -->
        <div class="flex items-center justify-between gap-3 bg-primary-50/60 border border-primary-100 rounded-2xl p-4">
            <div class="min-w-0">
                <p class="text-[10px] font-black uppercase tracking-widest text-primary-400">Alumno seleccionado</p>
                <p class="text-sm font-black text-slate-800 truncate">{{ student.last_name }}, {{ student.first_name }}</p>
            </div>
            <button type="button" @click="emit('back')" class="text-xs font-black text-primary-600 hover:underline shrink-0">Cambiar</button>
        </div>

        <div>
            <h3 class="text-lg font-black text-slate-800">¿Quién es su representante?</h3>
            <p class="text-xs font-medium text-slate-400">Busca por cédula; si no existe podrás registrarlo aquí mismo.</p>
        </div>

        <!-- MODO 1: Ya tiene representante asignado -->
        <div v-if="mode === 'confirm'" class="space-y-4">
            <div class="flex items-center gap-3 bg-white p-4 rounded-2xl border-2 border-emerald-100 shadow-sm">
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg shrink-0">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-black text-slate-800 truncate">{{ student.guardian.name }}</p>
                    <p class="text-[11px] font-bold text-slate-400">
                        <i class="fas fa-id-card opacity-60 mr-1"></i>{{ student.guardian.cedula }}
                        <span v-if="student.guardian.phone" class="ml-2"><i class="fas fa-phone opacity-60 mr-1"></i>{{ student.guardian.phone }}</span>
                    </p>
                </div>
                <button type="button" @click="change" class="text-xs font-black text-primary-600 hover:underline shrink-0">Cambiar</button>
            </div>
            <div class="flex justify-end">
                <button type="button" @click="emit('done', { student, guardian: student.guardian })"
                    class="px-8 py-3 bg-primary-600 text-white text-[11px] font-black uppercase tracking-widest rounded-2xl shadow-lg shadow-primary-600/20 hover:bg-primary-700 hover:-translate-y-0.5 transition-all">
                    Continuar <i class="fas fa-arrow-right ml-1.5"></i>
                </button>
            </div>
        </div>

        <!-- MODO 2: Buscar / Crear representante -->
        <div v-else class="space-y-4">
            <!-- Sugerencia para hermanos (reutilizar representante anterior) -->
            <button v-if="suggested && mode !== 'create'" type="button" @click="assign(suggested)" :disabled="saving"
                class="w-full text-left flex items-center gap-3 p-4 bg-emerald-50 border-2 border-emerald-200 rounded-2xl hover:border-emerald-400 hover:shadow-md transition-all disabled:opacity-50 group">
                <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center text-base shrink-0 group-hover:scale-105 transition-transform">
                    <i class="fas fa-people-roof"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-[10px] font-black uppercase tracking-widest text-emerald-600">Mismo representante de la inscripción anterior</p>
                    <p class="text-sm font-black text-slate-700 truncate">{{ suggested.name }} · {{ suggested.cedula }}</p>
                </div>
                <span class="px-4 py-2 bg-emerald-600 text-white text-[11px] font-black uppercase tracking-widest rounded-xl shadow-sm group-hover:bg-emerald-700 transition-colors">
                    Usar
                </span>
            </button>

            <!-- Buscador por Cédula / Documento -->
            <div class="flex flex-col sm:flex-row gap-2">
                <div class="sm:w-44 shrink-0">
                    <select v-model="docType" @change="onDocTypeChange"
                        class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl px-3 py-2.5 text-slate-700 text-xs font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all cursor-pointer shadow-sm">
                        <option value="V">V- (Venezolano)</option>
                        <option value="E">E- (Extranjero)</option>
                        <option value="P">P- (Pasaporte)</option>
                    </select>
                </div>
                <div class="flex-1">
                    <input :value="docNumber" @input="onDocInput" @keydown.enter.prevent="search" type="text" :maxlength="maxLen"
                        :placeholder="docPlaceholder" :class="inputClass">
                </div>
                <button type="button" @click="search" :disabled="searching || saving || !docNumber"
                    class="px-7 py-2.5 bg-slate-800 text-white text-xs font-black uppercase tracking-widest rounded-2xl hover:bg-slate-700 transition-all disabled:opacity-50 shadow-sm shrink-0">
                    <i v-if="searching" class="fas fa-spinner fa-spin mr-1"></i>
                    <i v-else class="fas fa-search mr-1.5"></i>
                    <span>Buscar</span>
                </button>
            </div>
            <p v-if="searchError" class="text-xs text-red-500 font-bold ml-1">{{ searchError }}</p>

            <!-- Banner cuando no se encuentra: Botón estilizado para registrar -->
            <div v-if="notFound && mode !== 'create'" 
                class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-sm text-amber-800 bg-amber-50 border-2 border-amber-200/80 rounded-2xl p-4 shadow-sm animate-fade-in-up">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center shrink-0">
                        <i class="fas fa-user-slash text-xs"></i>
                    </div>
                    <span class="font-bold text-xs sm:text-sm">No existe un representante registrado con la cédula <strong>{{ builtCedula }}</strong>.</span>
                </div>
                <button type="button" @click="startCreate" 
                    class="px-5 py-2.5 bg-amber-600 hover:bg-amber-700 text-white text-[11px] font-black uppercase tracking-widest rounded-xl shadow-md shadow-amber-600/20 hover:-translate-y-0.5 transition-all shrink-0 flex items-center justify-center gap-1.5">
                    <i class="fas fa-user-plus text-xs"></i>
                    <span>Registrar representante</span>
                </button>
            </div>

            <!-- Formulario de creación con transición animada suave -->
            <Transition
                enter-active-class="transition-all duration-300 ease-out"
                enter-from-class="opacity-0 -translate-y-3 scale-[0.98]"
                enter-to-class="opacity-100 translate-y-0 scale-100"
                leave-active-class="transition-all duration-200 ease-in"
                leave-from-class="opacity-100 translate-y-0 scale-100"
                leave-to-class="opacity-0 -translate-y-2 scale-[0.98]"
            >
                <div v-if="mode === 'create'" class="grid grid-cols-1 sm:grid-cols-2 gap-4 bg-white p-6 rounded-3xl border-2 border-slate-100 shadow-md">
                    <div class="sm:col-span-2 flex items-center gap-2 pb-2 border-b border-slate-100">
                        <div class="w-7 h-7 rounded-lg bg-primary-50 text-primary-600 flex items-center justify-center text-xs">
                            <i class="fas fa-id-badge"></i>
                        </div>
                        <h4 class="text-xs font-black text-slate-700 uppercase tracking-wider">Datos del nuevo representante</h4>
                    </div>

                    <!-- Cédula -->
                    <div class="space-y-1.5 sm:col-span-2">
                        <label :class="labelClass">Cédula / Documento *</label>
                        <input v-model="form.cedula" type="text" readonly :class="[inputClass, 'opacity-70 bg-slate-100 cursor-not-allowed font-mono']">
                        <p v-if="errors.cedula" class="text-xs text-red-500 font-bold ml-1">{{ errors.cedula[0] }}</p>
                    </div>

                    <!-- Nombres -->
                    <div class="space-y-1.5">
                        <label :class="labelClass">Nombres *</label>
                        <input 
                            v-model="form.first_name" 
                            @input="form.first_name = ($event.target.value || '').toUpperCase()"
                            type="text" 
                            placeholder="EJ: MARÍA ELENA" 
                            :class="[inputClass, 'uppercase']"
                        >
                        <p v-if="errors.first_name" class="text-xs text-red-500 font-bold ml-1">{{ errors.first_name[0] }}</p>
                    </div>

                    <!-- Apellidos -->
                    <div class="space-y-1.5">
                        <label :class="labelClass">Apellidos *</label>
                        <input 
                            v-model="form.last_name" 
                            @input="form.last_name = ($event.target.value || '').toUpperCase()"
                            type="text" 
                            placeholder="EJ: PÉREZ GÓMEZ" 
                            :class="[inputClass, 'uppercase']"
                        >
                        <p v-if="errors.last_name" class="text-xs text-red-500 font-bold ml-1">{{ errors.last_name[0] }}</p>
                    </div>

                    <!-- Teléfono con formato Venezuela -->
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <label :class="labelClass">Teléfono</label>
                            <span class="text-[10px] font-bold text-slate-400">0412, 0414, 0424, 0416, 02XX</span>
                        </div>
                        <input 
                            v-model="form.phone" 
                            @input="onPhoneInput" 
                            @blur="validatePhone"
                            type="tel" 
                            placeholder="Ej: 0414-1234567" 
                            maxlength="12"
                            :class="inputClass"
                        >
                        <p v-if="errors.phone" class="text-xs text-red-500 font-bold ml-1">{{ errors.phone[0] }}</p>
                    </div>

                    <!-- Correo -->
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <label :class="labelClass">Correo electrónico</label>
                            <span class="text-[10px] font-bold text-slate-400">Opcional</span>
                        </div>
                        <input 
                            v-model="form.email" 
                            @input="validateEmail"
                            type="email" 
                            placeholder="ejemplo@correo.com" 
                            :class="inputClass"
                        >
                        <p v-if="errors.email" class="text-xs text-red-500 font-bold ml-1">{{ errors.email[0] }}</p>
                    </div>

                    <!-- Botones de Acción -->
                    <div class="sm:col-span-2 flex items-center justify-end gap-3 pt-2">
                        <button type="button" @click="cancelCreate" 
                            class="px-5 py-2.5 text-xs font-black uppercase tracking-widest text-slate-400 hover:text-slate-600 transition-colors">
                            Cancelar
                        </button>
                        <button type="button" @click="createAndAssign" :disabled="saving"
                            class="px-6 py-2.5 rounded-xl bg-emerald-500 text-white text-xs font-black uppercase tracking-widest hover:bg-emerald-600 hover:-translate-y-0.5 shadow-md shadow-emerald-500/20 transition-all disabled:opacity-50 flex items-center gap-1.5">
                            <i v-if="saving" class="fas fa-spinner fa-spin"></i>
                            <i v-else class="fas fa-save"></i>
                            <span>Guardar y continuar</span>
                        </button>
                    </div>
                </div>
            </Transition>

            <!-- Advertencia al omitir representante -->
            <div class="p-3.5 bg-amber-50 border border-amber-200/80 rounded-2xl text-amber-800 text-xs flex items-start gap-2.5 shadow-sm">
                <i class="fas fa-exclamation-triangle mt-0.5 text-amber-500 shrink-0"></i>
                <span>Omitir el representante deja al alumno sin contacto asociado. Podrás asignarlo después desde la ficha del estudiante si es necesario.</span>
            </div>
        </div>

        <!-- Navegación inferior -->
        <div class="flex items-center justify-between gap-4 pt-2">
            <button type="button" @click="emit('back')" 
                class="px-6 py-3 text-sm font-black uppercase tracking-widest text-slate-400 hover:text-slate-600 transition-all">
                <i class="fas fa-arrow-left mr-1.5"></i> Atrás
            </button>
            <button v-if="mode !== 'confirm'" type="button" @click="emit('done', { student, guardian: null })"
                class="px-6 py-3 bg-white border-2 border-slate-100 text-slate-500 text-[11px] font-black uppercase tracking-widest rounded-2xl hover:border-slate-300 hover:bg-slate-50 transition-all shadow-sm">
                Omitir por ahora
            </button>
        </div>
    </div>
</template>

