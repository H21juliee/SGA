<script setup>
import { ref } from 'vue'

const props = defineProps({
    form: { type: Object, required: true },
})

const bloodTypes = ['O+', 'O-', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'Desconocido']
const visitFrequencies = [
    'Mensual',
    'Trimestral',
    'Semestral',
    'Anual',
    'Solo cuando está enfermo',
]

const heightError = ref('')
const weightError = ref('')

function allowDecimalKey(e, maxDecimals = 2) {
    if (['Backspace', 'Delete', 'Tab', 'Escape', 'Enter', 'ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(e.key)) {
        return
    }
    if (e.ctrlKey || e.metaKey) return

    const val = e.target.value || ''
    const hasSeparator = val.includes('.') || val.includes(',')

    if (e.key === '.' || e.key === ',') {
        if (hasSeparator) {
            e.preventDefault()
        }
        return
    }

    if (/^[0-9]$/.test(e.key)) {
        const sepIndex = val.indexOf('.') !== -1 ? val.indexOf('.') : val.indexOf(',')
        if (sepIndex !== -1) {
            const start = e.target.selectionStart ?? val.length
            const end = e.target.selectionEnd ?? val.length
            // Si el cursor está en la parte decimal y ya tiene el máximo de dígitos permitidos
            if (start > sepIndex && (end - start) === 0) {
                const decPart = val.slice(sepIndex + 1)
                if (decPart.length >= maxDecimals) {
                    e.preventDefault()
                    return
                }
            }
        }
        return
    }

    e.preventDefault()
}

function onHeightInput(e) {
    let raw = e.target.value || ''
    raw = raw.replace(/[^0-9.,]/g, '')

    // Limitar parte decimal a un máximo de 2 dígitos
    const sep = raw.includes(',') ? ',' : (raw.includes('.') ? '.' : null)
    let cleaned = raw
    if (sep) {
        const parts = raw.split(sep)
        const intPart = parts[0]
        const decPart = parts.slice(1).join('').replace(/[.,]/g, '').slice(0, 2)
        cleaned = intPart + sep + decPart
    }

    props.form.profile.height = cleaned
    e.target.value = cleaned
    validateHeight(cleaned)
}

function validateHeight(val) {
    heightError.value = ''
    if (props.form.clearErrors) props.form.clearErrors('profile.height')

    if (!val || !val.toString().trim()) return true

    const str = val.toString().trim().replace(',', '.')
    if (str.includes('.')) {
        const dec = str.split('.')[1]
        if (dec && dec.length > 2) {
            heightError.value = 'La estatura solo permite hasta 2 decimales (ej: 165.50 cm).'
            return false
        }
    }

    const num = parseFloat(str)
    if (isNaN(num)) {
        heightError.value = 'Ingrese una estatura válida en centímetros.'
        return false
    }
    if (num > 0 && num <= 2.5) {
        heightError.value = `Indique en centímetros: ej. ${Math.round(num * 100)} cm (entre 90 y 230 cm).`
        return false
    }
    if (num < 90 || num > 230) {
        heightError.value = 'La estatura debe estar entre 90 y 230 cm.'
        return false
    }
    return true
}

function onWeightInput(e) {
    let raw = e.target.value || ''
    raw = raw.replace(/[^0-9.,]/g, '')

    // Limitar parte decimal a un máximo de 2 dígitos
    const sep = raw.includes(',') ? ',' : (raw.includes('.') ? '.' : null)
    let cleaned = raw
    if (sep) {
        const parts = raw.split(sep)
        const intPart = parts[0]
        const decPart = parts.slice(1).join('').replace(/[.,]/g, '').slice(0, 2)
        cleaned = intPart + sep + decPart
    }

    props.form.profile.weight = cleaned
    e.target.value = cleaned
    validateWeight(cleaned)
}

function validateWeight(val) {
    weightError.value = ''
    if (props.form.clearErrors) props.form.clearErrors('profile.weight')

    if (!val || !val.toString().trim()) return true

    const str = val.toString().trim().replace(',', '.')
    if (str.includes('.')) {
        const dec = str.split('.')[1]
        if (dec && dec.length > 2) {
            weightError.value = 'El peso solo permite hasta 2 decimales (ej: 63.75 kg).'
            return false
        }
    }

    const num = parseFloat(str)
    if (isNaN(num)) {
        weightError.value = 'Ingrese un peso válido en kilogramos.'
        return false
    }
    if (num < 20 || num > 200) {
        weightError.value = 'El peso corporal debe estar entre 20 y 200 kg.'
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
                <span class="w-8 h-8 rounded-xl bg-primary-100 text-primary-600 flex items-center justify-center text-sm font-black">4</span>
                Información Médica y de Salud
            </h3>
            <p class="text-xs text-slate-400 font-bold mt-1 ml-10">Condiciones de salud, medidas antropométricas y control médico</p>
        </div>

        <!-- 1. Enfermedad con tratamiento -->
        <div class="bg-white border-2 border-slate-100 rounded-3xl p-5 shadow-sm space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center font-black">
                        <i class="fas fa-heartbeat"></i>
                    </div>
                    <div>
                        <h4 class="text-sm font-black text-slate-800">¿Padece alguna enfermedad con tratamiento o control médico?</h4>
                        <p class="text-xs text-slate-400 font-bold">Asma, diabetes, epilepsia, cardiopatías u otros</p>
                    </div>
                </div>

                <div class="flex gap-2">
                    <button
                        type="button"
                        @click="form.profile.has_medical_condition = true"
                        :class="form.profile.has_medical_condition ? 'bg-rose-600 text-white shadow-md shadow-rose-600/20' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                        class="px-5 py-2 rounded-xl text-xs font-black uppercase tracking-wider transition-all"
                    >
                        Sí
                    </button>
                    <button
                        type="button"
                        @click="form.profile.has_medical_condition = false; form.profile.medical_condition_detail = ''"
                        :class="!form.profile.has_medical_condition ? 'bg-slate-800 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                        class="px-5 py-2 rounded-xl text-xs font-black uppercase tracking-wider transition-all"
                    >
                        No
                    </button>
                </div>
            </div>

            <transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0">
                <div v-if="form.profile.has_medical_condition" class="pt-3 border-t border-slate-100">
                    <label class="text-[11px] font-black text-rose-600 uppercase tracking-wider ml-1">
                        Indique la enfermedad y tratamiento <span class="text-red-500">*</span>
                    </label>
                    <input
                        v-model="form.profile.medical_condition_detail"
                        @input="form.profile.medical_condition_detail = ($event.target.value || '').toUpperCase()"
                        type="text"
                        placeholder="EJ: ASMA BRONQUIAL (SALBUTAMOL EN CRISIS)"
                        class="w-full mt-1.5 bg-slate-50 border-2 border-rose-200 rounded-2xl px-4 py-2.5 text-slate-800 text-sm font-bold focus:border-rose-400 focus:bg-white outline-none transition-all uppercase"
                    />
                    <p v-if="form.errors['profile.medical_condition_detail']" class="text-xs text-red-500 font-bold ml-1 mt-1">
                        {{ form.errors['profile.medical_condition_detail'] }}
                    </p>
                </div>
            </transition>
        </div>

        <!-- 2. Alergias -->
        <div class="bg-white border-2 border-slate-100 rounded-3xl p-5 shadow-sm space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center font-black">
                        <i class="fas fa-allergies"></i>
                    </div>
                    <div>
                        <h4 class="text-sm font-black text-slate-800">¿Presenta algún tipo de alergia?</h4>
                        <p class="text-xs text-slate-400 font-bold">Medicamentos, alimentos, picaduras, polvo u otros agentes</p>
                    </div>
                </div>

                <div class="flex gap-2">
                    <button
                        type="button"
                        @click="form.profile.has_allergies = true"
                        :class="form.profile.has_allergies ? 'bg-amber-600 text-white shadow-md shadow-amber-600/20' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                        class="px-5 py-2 rounded-xl text-xs font-black uppercase tracking-wider transition-all"
                    >
                        Sí
                    </button>
                    <button
                        type="button"
                        @click="form.profile.has_allergies = false; form.profile.allergies_detail = ''"
                        :class="!form.profile.has_allergies ? 'bg-slate-800 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                        class="px-5 py-2 rounded-xl text-xs font-black uppercase tracking-wider transition-all"
                    >
                        No
                    </button>
                </div>
            </div>

            <transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0">
                <div v-if="form.profile.has_allergies" class="pt-3 border-t border-slate-100">
                    <label class="text-[11px] font-black text-amber-700 uppercase tracking-wider ml-1">
                        ¿A qué es alérgico? <span class="text-red-500">*</span>
                    </label>
                    <input
                        v-model="form.profile.allergies_detail"
                        @input="form.profile.allergies_detail = ($event.target.value || '').toUpperCase()"
                        type="text"
                        placeholder="EJ: ALERGIA A LA PENICILINA, MARISCOS, PICADURA DE ABEJA..."
                        class="w-full mt-1.5 bg-slate-50 border-2 border-amber-200 rounded-2xl px-4 py-2.5 text-slate-800 text-sm font-bold focus:border-amber-400 focus:bg-white outline-none transition-all uppercase"
                    />
                    <p v-if="form.errors['profile.allergies_detail']" class="text-xs text-red-500 font-bold ml-1 mt-1">
                        {{ form.errors['profile.allergies_detail'] }}
                    </p>
                </div>
            </transition>
        </div>

        <!-- 3. Toggles de Medicamento Educación Física y Constancia Médica -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="bg-white border-2 border-slate-100 rounded-3xl p-5 shadow-sm flex items-center justify-between gap-3">
                <div>
                    <h4 class="text-xs font-black text-slate-800 uppercase tracking-wider">Medicamento para Educación Física</h4>
                    <p class="text-[11px] text-slate-400 font-bold">¿Requiere inhalador o medicación durante actividad física?</p>
                </div>

                <div class="flex gap-1.5 shrink-0">
                    <button
                        type="button"
                        @click="form.profile.requires_pe_medication = true"
                        :class="form.profile.requires_pe_medication ? 'bg-primary-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600'"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-black uppercase tracking-wider transition-all"
                    >
                        Sí
                    </button>
                    <button
                        type="button"
                        @click="form.profile.requires_pe_medication = false"
                        :class="!form.profile.requires_pe_medication ? 'bg-slate-800 text-white shadow-sm' : 'bg-slate-100 text-slate-600'"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-black uppercase tracking-wider transition-all"
                    >
                        No
                    </button>
                </div>
            </div>

            <div class="bg-white border-2 border-slate-100 rounded-3xl p-5 shadow-sm flex items-center justify-between gap-3">
                <div>
                    <h4 class="text-xs font-black text-slate-800 uppercase tracking-wider">Constancia Médica Vigente</h4>
                    <p class="text-[11px] text-slate-400 font-bold">¿Consigna informe médico o certificado de salud escolar?</p>
                </div>

                <div class="flex gap-1.5 shrink-0">
                    <button
                        type="button"
                        @click="form.profile.has_medical_certificate = true"
                        :class="form.profile.has_medical_certificate ? 'bg-primary-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600'"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-black uppercase tracking-wider transition-all"
                    >
                        Sí
                    </button>
                    <button
                        type="button"
                        @click="form.profile.has_medical_certificate = false"
                        :class="!form.profile.has_medical_certificate ? 'bg-slate-800 text-white shadow-sm' : 'bg-slate-100 text-slate-600'"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-black uppercase tracking-wider transition-all"
                    >
                        No
                    </button>
                </div>
            </div>
        </div>

        <!-- 4. Antropometría y Frecuencia médica -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
            <!-- Grupo Sanguíneo -->
            <div class="space-y-1.5">
                <label class="text-[11px] font-black text-slate-500 uppercase tracking-wider ml-1">Grupo Sanguíneo</label>
                <select
                    v-model="form.profile.blood_type"
                    class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl px-4 py-3 text-slate-800 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all cursor-pointer"
                >
                    <option value="">Seleccione...</option>
                    <option v-for="bt in bloodTypes" :key="bt" :value="bt">{{ bt }}</option>
                </select>
            </div>

            <!-- Talla (Estatura en cm) -->
            <div class="space-y-1.5">
                <label class="text-[11px] font-black text-slate-500 uppercase tracking-wider ml-1 flex items-center justify-between">
                    <span>Talla (Estatura)</span>
                    <span class="text-[10px] text-slate-400 font-bold">90 - 230 cm (máx. 2 dec.)</span>
                </label>
                <div class="flex items-center bg-slate-50 border-2 rounded-2xl px-4 py-2.5 transition-all focus-within:border-primary-400 focus-within:bg-white"
                     :class="(heightError || form.errors['profile.height']) ? 'border-red-300 bg-red-50/20' : 'border-slate-200'">
                    <input
                        :value="form.profile.height"
                        @input="onHeightInput"
                        @blur="validateHeight(form.profile.height)"
                        @keydown="allowDecimalKey($event, 2)"
                        type="text"
                        inputmode="decimal"
                        placeholder="Ej: 165"
                        class="w-full bg-transparent border-0 p-0 text-slate-800 text-sm font-bold focus:ring-0 outline-none"
                    />
                    <span class="text-xs font-black text-slate-400 uppercase select-none ml-2 shrink-0">cm</span>
                </div>
                <p v-if="heightError" class="text-xs text-red-500 font-bold ml-1">{{ heightError }}</p>
                <p v-else-if="form.errors['profile.height']" class="text-xs text-red-500 font-bold ml-1">{{ form.errors['profile.height'] }}</p>
            </div>

            <!-- Peso Corporal (en kg) -->
            <div class="space-y-1.5">
                <label class="text-[11px] font-black text-slate-500 uppercase tracking-wider ml-1 flex items-center justify-between">
                    <span>Peso Corporal</span>
                    <span class="text-[10px] text-slate-400 font-bold">20 - 200 kg (máx. 2 dec.)</span>
                </label>
                <div class="flex items-center bg-slate-50 border-2 rounded-2xl px-4 py-2.5 transition-all focus-within:border-primary-400 focus-within:bg-white"
                     :class="(weightError || form.errors['profile.weight']) ? 'border-red-300 bg-red-50/20' : 'border-slate-200'">
                    <input
                        :value="form.profile.weight"
                        @input="onWeightInput"
                        @blur="validateWeight(form.profile.weight)"
                        @keydown="allowDecimalKey($event, 2)"
                        type="text"
                        inputmode="decimal"
                        placeholder="Ej: 54.50"
                        class="w-full bg-transparent border-0 p-0 text-slate-800 text-sm font-bold focus:ring-0 outline-none"
                    />
                    <span class="text-xs font-black text-slate-400 uppercase select-none ml-2 shrink-0">kg</span>
                </div>
                <p v-if="weightError" class="text-xs text-red-500 font-bold ml-1">{{ weightError }}</p>
                <p v-else-if="form.errors['profile.weight']" class="text-xs text-red-500 font-bold ml-1">{{ form.errors['profile.weight'] }}</p>
            </div>

            <!-- Regularidad Consulta -->
            <div class="space-y-1.5">
                <label class="text-[11px] font-black text-slate-500 uppercase tracking-wider ml-1">Visita al Médico</label>
                <select
                    v-model="form.profile.doctor_visit_frequency"
                    class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl px-4 py-3 text-slate-800 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all cursor-pointer"
                >
                    <option value="">Seleccione regularidad...</option>
                    <option v-for="f in visitFrequencies" :key="f" :value="f">{{ f }}</option>
                </select>
            </div>
        </div>
    </div>
</template>
