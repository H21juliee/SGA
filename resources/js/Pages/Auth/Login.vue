<script setup>
import { ref, computed } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'

const page = usePage()

const form = useForm({
    email: '',
    password: '',
    remember: false,
})

const showPassword = ref(false)

const clientErrors = ref({
    email: '',
    password: '',
})

const touched = ref({
    email: false,
    password: false,
})

// Regex que exige formato completo: usuario@dominio.extension (evita errores como usuario@gmailcom)
const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/

function validateEmail(showIfEmpty = true) {
    const val = (form.email || '').trim()
    if (!val) {
        clientErrors.value.email = showIfEmpty ? 'Por favor, ingresa tu correo electrónico.' : ''
        return false
    }
    if (!emailRegex.test(val)) {
        clientErrors.value.email = 'Ingresa un correo electrónico válido (ej. usuario@colegio.com).'
        return false
    }
    clientErrors.value.email = ''
    return true
}

function validatePassword(showIfEmpty = true) {
    const val = form.password || ''
    if (!val) {
        clientErrors.value.password = showIfEmpty ? 'Por favor, ingresa tu contraseña.' : ''
        return false
    }
    clientErrors.value.password = ''
    return true
}

function onEmailInput() {
    form.clearErrors('email')
    if (touched.value.email) {
        validateEmail(false)
    } else {
        clientErrors.value.email = ''
    }
}

function onEmailBlur() {
    touched.value.email = true
    validateEmail(true)
}

function onPasswordInput() {
    form.clearErrors('password')
    if (touched.value.password) {
        validatePassword(false)
    } else {
        clientErrors.value.password = ''
    }
}

function onPasswordBlur() {
    touched.value.password = true
    validatePassword(true)
}

// Errores combinados (frontend y backend)
const emailError = computed(() => {
    if (clientErrors.value.email) return clientErrors.value.email
    // Si el backend arrojó un error específico de formato o campo requerido
    if (form.errors.email && form.errors.email !== 'Las credenciales proporcionadas no coinciden con nuestros registros.') {
        return form.errors.email
    }
    return ''
})

const passwordError = computed(() => {
    return clientErrors.value.password || form.errors.password || ''
})

// Error global de autenticación fallida
const authError = computed(() => {
    return form.errors.email === 'Las credenciales proporcionadas no coinciden con nuestros registros.'
        ? form.errors.email
        : ''
})

function submit() {
    touched.value.email = true
    touched.value.password = true
    form.clearErrors()

    const isEmailValid = validateEmail(true)
    const isPasswordValid = validatePassword(true)

    if (!isEmailValid || !isPasswordValid) {
        if (!isEmailValid) {
            document.getElementById('email')?.focus()
        } else if (!isPasswordValid) {
            document.getElementById('password')?.focus()
        }
        return
    }

    form.email = form.email.trim()

    form.post('/login', {
        preserveScroll: true,
        onFinish: () => {
            form.password = ''
            touched.value.password = false
        },
    })
}
</script>

<template>
    <div class="min-h-screen bg-mesh flex items-center justify-center px-4 relative overflow-hidden">
        <!-- Background decorations -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <!-- Pulsing color Orbs -->
            <div class="absolute -top-40 -right-40 w-[500px] h-[500px] bg-primary-200/20 rounded-full blur-[120px] animate-pulse-slow"></div>
            <div class="absolute -bottom-40 -left-40 w-[500px] h-[500px] bg-accent-200/20 rounded-full blur-[120px] animate-pulse-slow" style="animation-delay: 1.5s"></div>
        </div>

        <div class="relative w-full max-w-4xl animate-fade-in-up">
            <!-- Wide Form Card -->
            <div class="glass-card rounded-[2.5rem] border-white/60 shadow-2xl shadow-primary-900/10 relative overflow-hidden flex flex-col md:flex-row">
                <!-- Top accent line -->
                <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-primary-500 via-primary-400 to-accent-400 z-10"></div>

                <!-- Left Side: Branding -->
                <div class="md:w-1/2 p-10 lg:p-14 flex flex-col items-center justify-center bg-gradient-to-br from-primary-50/80 to-white/50 border-r border-slate-100/50 relative overflow-hidden">
                    <!-- School Logo (if uploaded) -->
                    <template v-if="$page.props.school?.logo_path">
                        <div class="relative z-10 animate-fade-in-up text-center">
                            <img :src="'/storage/' + $page.props.school.logo_path" alt="Logo del Plantel" class="w-auto h-48 md:h-64 object-contain drop-shadow-xl rounded-[2rem] mx-auto" />
                            <h1 class="text-2xl font-black text-slate-800 tracking-tight leading-tight text-center mt-6">
                                Sistema de <br/><span class="gradient-text">Gestión Académica</span>
                            </h1>
                        </div>
                    </template>
                    
                    <!-- SGA Official Logo (High Res, Large & Clean) -->
                    <template v-else>
                        <div class="text-center animate-fade-in-up flex flex-col items-center justify-center p-2">
                            <img 
                                src="/pwa-512x512.png" 
                                alt="SGA - Sistema de Gestión Académica" 
                                class="w-56 h-56 sm:w-64 sm:h-64 md:w-72 md:h-72 object-contain drop-shadow-2xl hover:scale-105 transition-transform duration-500" 
                            />
                        </div>
                    </template>
                </div>

                <!-- Right Side: Login Form -->
                <div class="md:w-1/2 p-10 lg:p-14 bg-white/40">
                    <div class="mb-8">
                        <h2 class="text-2xl font-bold text-slate-800">Bienvenido</h2>
                        <p class="text-slate-400 font-bold text-xs mt-2 uppercase tracking-widest">Ingresa tus credenciales</p>
                    </div>

                    <!-- Banner de Alerta de Credenciales Incorrectas -->
                    <div v-if="authError" class="mb-6 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-bold flex items-center gap-3 shadow-xs animate-fade-in-up">
                        <div class="w-8 h-8 rounded-xl bg-rose-500 text-white flex items-center justify-center shrink-0 shadow-sm shadow-rose-500/30">
                            <i class="fas fa-exclamation-triangle text-xs"></i>
                        </div>
                        <span class="leading-tight">{{ authError }}</span>
                    </div>

                    <form novalidate @submit.prevent="submit" class="space-y-6">
                        <!-- Email -->
                        <div class="space-y-2">
                            <label for="email" class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Correo Electrónico</label>
                            <div class="relative group">
                                <input
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    autofocus
                                    autocomplete="email"
                                    @input="onEmailInput"
                                    @blur="onEmailBlur"
                                    class="w-full pl-12 pr-4 py-4 bg-slate-50 border-2 rounded-2xl text-slate-700 text-sm font-bold focus:ring-0 outline-none transition-all"
                                    :class="emailError ? 'border-rose-300 bg-rose-50/20 text-rose-900 focus:border-rose-500 focus:bg-white' : 'border-slate-100 focus:border-primary-400 focus:bg-white'"
                                    placeholder="nombre@colegio.com"
                                />
                                <i
                                    class="fas fa-envelope absolute left-4 top-1/2 -translate-y-1/2 transition-colors"
                                    :class="emailError ? 'text-rose-400' : 'text-slate-300 group-focus-within:text-primary-500'"
                                ></i>
                            </div>
                            <p v-if="emailError" class="mt-1 text-xs font-bold text-rose-500 flex items-center gap-1.5 ml-1 animate-fade-in-up">
                                <i class="fas fa-exclamation-circle text-[11px] shrink-0"></i>
                                <span>{{ emailError }}</span>
                            </p>
                        </div>

                        <!-- Password -->
                        <div class="space-y-2">
                            <label for="password" class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Contraseña</label>
                            <div class="relative group">
                                <input
                                    id="password"
                                    v-model="form.password"
                                    :type="showPassword ? 'text' : 'password'"
                                    autocomplete="current-password"
                                    @input="onPasswordInput"
                                    @blur="onPasswordBlur"
                                    class="w-full pl-12 pr-12 py-4 bg-slate-50 border-2 rounded-2xl text-slate-700 text-sm font-bold focus:ring-0 outline-none transition-all"
                                    :class="passwordError ? 'border-rose-300 bg-rose-50/20 text-rose-900 focus:border-rose-500 focus:bg-white' : 'border-slate-100 focus:border-primary-400 focus:bg-white'"
                                    placeholder="••••••••"
                                />
                                <i
                                    class="fas fa-lock absolute left-4 top-1/2 -translate-y-1/2 transition-colors"
                                    :class="passwordError ? 'text-rose-400' : 'text-slate-300 group-focus-within:text-primary-500'"
                                ></i>
                                <button
                                    type="button"
                                    @click="showPassword = !showPassword"
                                    class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 hover:text-slate-600 focus:text-primary-500 transition-colors p-1"
                                    :title="showPassword ? 'Ocultar contraseña' : 'Ver contraseña'"
                                    tabindex="-1"
                                >
                                    <i class="fas" :class="showPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                                </button>
                            </div>
                            <p v-if="passwordError" class="mt-1 text-xs font-bold text-rose-500 flex items-center gap-1.5 ml-1 animate-fade-in-up">
                                <i class="fas fa-exclamation-circle text-[11px] shrink-0"></i>
                                <span>{{ passwordError }}</span>
                            </p>
                        </div>

                        <!-- Remember & Forgot -->
                        <div class="flex items-center justify-between px-1">
                            <label class="flex items-center gap-3 cursor-pointer group">
                                <div class="relative">
                                    <input
                                        v-model="form.remember"
                                        type="checkbox"
                                        class="peer sr-only"
                                    />
                                    <div class="w-5 h-5 bg-slate-100 border-2 border-slate-100 rounded-lg peer-checked:bg-primary-500 peer-checked:border-primary-500 transition-all"></div>
                                    <i class="fas fa-check absolute inset-0 text-[10px] text-white flex items-center justify-center opacity-0 peer-checked:opacity-100 transition-opacity"></i>
                                </div>
                                <span class="text-xs font-bold text-slate-500 group-hover:text-slate-700 transition-colors">Recordarme</span>
                            </label>
                            <a v-if="$page.props.security_questions_enabled" href="/password/recover" class="text-xs font-black text-primary-500 hover:text-primary-600 transition-colors uppercase tracking-wider">¿Olvidaste tu clave?</a>
                        </div>

                        <!-- Submit -->
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full py-4.5 px-6 rounded-2xl text-[11px] font-black uppercase tracking-widest text-white bg-primary-600 hover:bg-primary-500 shadow-xl shadow-primary-600/20 hover:-translate-y-0.5 transition-all duration-300 disabled:opacity-50 mt-4 flex items-center justify-center gap-3"
                        >
                            <template v-if="!form.processing">
                                <span>Ingresar al Sistema</span>
                                <i class="fas fa-arrow-right"></i>
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

            <!-- Footer -->
            <div class="text-center mt-10">
                <p class="text-slate-400 font-bold text-[10px] uppercase tracking-[0.2em]">
                    SGA © 2026 — Plataforma Educativa de Alto Rendimiento
                </p>
            </div>
        </div>
    </div>
</template>

