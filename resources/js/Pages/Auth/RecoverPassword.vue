<script setup>
import { ref, computed } from 'vue'
import { router, usePage, useForm } from '@inertiajs/vue3'

const page = usePage()

const props = defineProps({
    step: { type: Number, default: 1 },
    userId: Number,
    userName: String,
    questions: Array,
})

const currentStep = ref(props.step || 1)

// Step 1: Find user by cedula (Homologado con Usuarios / Estudiantes / Representantes)
const docType = ref('V')
const docNumber = ref('')
const docError = ref('')

const docPlaceholder = computed(() => {
    switch (docType.value) {
        case 'V': return 'Ej: 18456789 (6-9 dígitos)'
        case 'E': return 'Ej: 84550210 (6-9 dígitos)'
        case 'P': return 'Ej: AB1234567 (6-15 caracteres)'
        default: return 'Ej: 18456789'
    }
})

const docMaxLength = computed(() => (docType.value === 'P' ? 15 : 9))

const cedulaForm = useForm({ cedula: '' })

function syncCedulaField() {
    const cleanNum = docNumber.value.trim()
    cedulaForm.cedula = cleanNum ? `${docType.value}-${cleanNum}` : ''
}

function allowOnlyDocKey(e) {
    if (['Backspace', 'Delete', 'Tab', 'Escape', 'Enter', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Home', 'End'].includes(e.key)) {
        return
    }
    if (e.ctrlKey || e.metaKey) {
        return
    }
    if (docType.value === 'P') {
        if (!/^[a-zA-Z0-9]$/.test(e.key)) e.preventDefault()
    } else {
        if (!/^[0-9]$/.test(e.key)) e.preventDefault()
    }
}

function onDocTypeChange() {
    docError.value = ''
    if (docType.value !== 'P') {
        docNumber.value = docNumber.value.replace(/[^0-9]/g, '').slice(0, 9)
    } else {
        docNumber.value = docNumber.value.toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 15)
    }
    syncCedulaField()
}

function onDocNumberInput(e) {
    let val = (e.target.value || '').toUpperCase()
    if (docType.value === 'P') {
        val = val.replace(/[^A-Z0-9]/g, '').slice(0, 15)
    } else {
        val = val.replace(/[^0-9]/g, '').slice(0, 9)
    }
    docNumber.value = val
    e.target.value = val
    docError.value = ''
    if (cedulaForm.errors.cedula) cedulaForm.clearErrors('cedula')
    syncCedulaField()
}

function validateDocNumber() {
    const num = docNumber.value.trim()
    if (!num) {
        docError.value = 'El número de documento es obligatorio.'
        return false
    }
    if (docType.value === 'P') {
        if (!/^[A-Z0-9]{6,15}$/.test(num)) {
            docError.value = 'El pasaporte debe tener entre 6 y 15 caracteres alfanuméricos.'
            return false
        }
    } else {
        if (!/^\d{6,9}$/.test(num)) {
            docError.value = 'La cédula debe contener entre 6 y 9 dígitos numéricos.'
            return false
        }
    }
    docError.value = ''
    return true
}

function findUser() {
    syncCedulaField()
    if (!validateDocNumber()) {
        cedulaForm.setError('cedula', docError.value)
        return
    }

    cedulaForm.post('/password/recover/find', {
        preserveState: true,
    })
}

// Step 2+3: Answer questions and set new password
const resetForm = useForm({
    user_id: props.userId || '',
    answer_1: '',
    answer_2: '',
    password: '',
    password_confirmation: '',
})

const showPasswordFields = ref(false)

function submitReset() {
    resetForm.user_id = props.userId
    resetForm.post('/password/recover/reset')
}
</script>

<template>
    <div class="min-h-screen bg-mesh flex items-center justify-center px-4 relative overflow-hidden">
        <!-- Background decorations -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute -top-40 -right-40 w-[500px] h-[500px] bg-primary-200/20 rounded-full blur-[120px] animate-pulse-slow"></div>
            <div class="absolute -bottom-40 -left-40 w-[500px] h-[500px] bg-accent-200/20 rounded-full blur-[120px] animate-pulse-slow" style="animation-delay: 1.5s"></div>
        </div>

        <div class="relative w-full max-w-lg animate-fade-in-up">
            <!-- Logo & Brand -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-20 h-20 rounded-[2.5rem] bg-white shadow-2xl shadow-primary-500/10 mb-6">
                    <div class="w-16 h-16 rounded-[2rem] bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center shadow-lg">
                        <i class="fas fa-key text-3xl text-white"></i>
                    </div>
                </div>
                <h1 class="text-2xl font-black text-slate-800 tracking-tight">
                    Recuperar <span class="gradient-text">Contraseña</span>
                </h1>
                <p class="text-slate-400 font-bold text-sm mt-2">Responde tus preguntas de seguridad para restablecer tu clave.</p>
            </div>

            <!-- Flash success from redirect -->
            <div v-if="page.props.flash?.success" class="mb-6 bg-emerald-50 border-2 border-emerald-200 rounded-2xl p-4 text-center animate-fade-in-up">
                <p class="text-sm font-bold text-emerald-700"><i class="fas fa-check-circle mr-2"></i>{{ page.props.flash.success }}</p>
            </div>

            <!-- Form Card -->
            <div class="glass-card rounded-[2.5rem] border-white/60 p-8 sm:p-10 shadow-2xl shadow-primary-900/5 relative overflow-hidden">
                <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-amber-500 via-orange-400 to-primary-400"></div>

                <!-- Step 1: Enter Cedula -->
                <div v-if="!props.questions" class="space-y-6">
                    <div class="text-center mb-2">
                        <h2 class="text-lg font-black text-slate-800">Identifícate</h2>
                        <p class="text-xs text-slate-400 font-medium mt-1">Ingresa tu cédula para buscar tu cuenta.</p>
                    </div>

                    <form @submit.prevent="findUser" novalidate class="space-y-5">
                        <div class="space-y-2">
                            <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">
                                Cédula de Identidad <span class="text-rose-500">*</span>
                            </label>
                            <div class="flex gap-2">
                                <select
                                    v-model="docType"
                                    @change="onDocTypeChange"
                                    class="w-24 shrink-0 bg-slate-50 border-2 border-slate-200 rounded-2xl px-3 py-3.5 text-slate-700 text-sm font-black focus:border-primary-400 focus:bg-white outline-none transition-all shadow-sm cursor-pointer"
                                >
                                    <option value="V">V -</option>
                                    <option value="E">E -</option>
                                    <option value="P">P -</option>
                                </select>
                                <div class="relative flex-1">
                                    <input
                                        :value="docNumber"
                                        @keydown="allowOnlyDocKey"
                                        @input="onDocNumberInput"
                                        @blur="validateDocNumber"
                                        type="text"
                                        :maxlength="docMaxLength"
                                        :placeholder="docPlaceholder"
                                        class="w-full pl-11 pr-4 py-3.5 bg-slate-50 border-2 rounded-2xl text-slate-700 text-sm font-bold focus:bg-white focus:ring-0 outline-none transition-all shadow-sm"
                                        :class="(docError || cedulaForm.errors.cedula) ? 'border-rose-300 focus:border-rose-400' : 'border-slate-200 focus:border-primary-400'"
                                    >
                                    <i class="fas fa-id-card absolute left-4 top-1/2 -translate-y-1/2 text-slate-300 pointer-events-none"></i>
                                </div>
                            </div>
                            <div class="flex items-center justify-between px-1">
                                <p v-if="docError || cedulaForm.errors.cedula" class="text-xs font-bold text-rose-500">
                                    {{ docError || cedulaForm.errors.cedula }}
                                </p>
                                <p v-else class="text-[10px] font-bold text-slate-400">
                                    Formato: <span class="text-slate-600">{{ cedulaForm.cedula || `${docType}-XXXXXXXX` }}</span>
                                </p>
                            </div>
                        </div>

                        <button type="submit" :disabled="cedulaForm.processing"
                                class="w-full py-4 px-6 rounded-2xl text-[11px] font-black uppercase tracking-widest text-white bg-primary-600 hover:bg-primary-500 shadow-xl shadow-primary-600/20 hover:-translate-y-0.5 transition-all duration-300 disabled:opacity-50 flex items-center justify-center gap-3 cursor-pointer">
                            <template v-if="!cedulaForm.processing">
                                <i class="fas fa-search"></i>
                                <span>Buscar mi cuenta</span>
                            </template>
                            <template v-else>
                                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                </svg>
                                <span>Buscando...</span>
                            </template>
                        </button>
                    </form>
                </div>

                <!-- Step 2+3: Answer Questions + New Password -->
                <div v-else class="space-y-6">
                    <div class="text-center mb-2">
                        <div class="inline-flex items-center gap-2 bg-emerald-50 text-emerald-700 px-4 py-2 rounded-full text-xs font-bold mb-3 border border-emerald-100">
                            <i class="fas fa-user-check"></i>
                            <span>{{ userName }}</span>
                        </div>
                        <h2 class="text-lg font-black text-slate-800">Responde tus preguntas</h2>
                        <p class="text-xs text-slate-400 font-medium mt-1">Verifica tu identidad respondiendo las preguntas que configuraste.</p>
                    </div>

                    <form @submit.prevent="submitReset" class="space-y-5">
                        <!-- Question 1 -->
                        <div class="space-y-2 bg-slate-50 rounded-2xl p-5 border border-slate-100">
                            <label class="text-[10px] font-black text-primary-500 uppercase tracking-widest">{{ questions[0]?.question }}</label>
                            <input v-model="resetForm.answer_1" type="text" required placeholder="Tu respuesta..."
                                   class="w-full bg-white border-2 border-slate-200 rounded-xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:ring-0 outline-none transition-all shadow-sm">
                        </div>

                        <!-- Question 2 -->
                        <div class="space-y-2 bg-slate-50 rounded-2xl p-5 border border-slate-100">
                            <label class="text-[10px] font-black text-primary-500 uppercase tracking-widest">{{ questions[1]?.question }}</label>
                            <input v-model="resetForm.answer_2" type="text" required placeholder="Tu respuesta..."
                                   class="w-full bg-white border-2 border-slate-200 rounded-xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:ring-0 outline-none transition-all shadow-sm">
                        </div>

                        <!-- Error for incorrect answers -->
                        <p v-if="resetForm.errors.answers" class="bg-red-50 border border-red-200 rounded-xl p-4 text-xs text-red-600 font-bold">
                            <i class="fas fa-exclamation-circle mr-1"></i> {{ resetForm.errors.answers }}
                        </p>

                        <!-- New Password -->
                        <div class="space-y-4 pt-2 border-t border-slate-100">
                            <h3 class="text-sm font-black text-slate-800 pt-2">Nueva Contraseña</h3>

                            <div class="space-y-2">
                                <input v-model="resetForm.password" type="password" required placeholder="Nueva contraseña"
                                       class="w-full bg-slate-50 border-2 border-slate-200 rounded-xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all shadow-sm">
                                <p v-if="resetForm.errors.password" class="text-xs text-red-500 font-bold ml-1">{{ resetForm.errors.password }}</p>
                            </div>
                            <div class="space-y-2">
                                <input v-model="resetForm.password_confirmation" type="password" required placeholder="Confirmar contraseña"
                                       class="w-full bg-slate-50 border-2 border-slate-200 rounded-xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all shadow-sm">
                            </div>

                            <div class="bg-slate-50 rounded-xl p-3 border border-slate-100">
                                <p class="text-[10px] font-bold text-slate-500 leading-relaxed">
                                    <i class="fas fa-info-circle text-primary-400 mr-1"></i>
                                    Mínimo 8 caracteres, con mayúsculas, minúsculas, números y símbolos.
                                </p>
                            </div>
                        </div>

                        <button type="submit" :disabled="resetForm.processing"
                                class="w-full py-4 px-6 rounded-2xl text-[11px] font-black uppercase tracking-widest text-white bg-primary-600 hover:bg-primary-500 shadow-xl shadow-primary-600/20 hover:-translate-y-0.5 transition-all duration-300 disabled:opacity-50 flex items-center justify-center gap-3">
                            <template v-if="!resetForm.processing">
                                <i class="fas fa-save"></i>
                                <span>Guardar Nueva Contraseña</span>
                            </template>
                            <template v-else>
                                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                </svg>
                                <span>Verificando...</span>
                            </template>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Back to Login -->
            <div class="text-center mt-6">
                <a href="/login" class="text-xs font-black text-primary-500 hover:text-primary-600 transition-colors uppercase tracking-wider">
                    <i class="fas fa-arrow-left mr-1"></i> Volver al inicio de sesión
                </a>
            </div>

            <!-- Footer -->
            <div class="text-center mt-6">
                <p class="text-slate-400 font-bold text-[10px] uppercase tracking-[0.2em]">
                    SGA © 2026 — Recuperación de Acceso
                </p>
            </div>
        </div>
    </div>
</template>