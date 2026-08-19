<script setup>
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'

const props = defineProps({
    questions: Array,
    userName: String,
})

const form = useForm({
    password: '',
    password_confirmation: '',
    question_1: '',
    answer_1: '',
    question_2: '',
    answer_2: '',
})

const step = ref(1) // 1 = password, 2 = security questions

function goToStep2() {
    if (!form.password || !form.password_confirmation) return
    if (form.password !== form.password_confirmation) {
        form.setError('password_confirmation', 'Las contraseñas no coinciden.')
        return
    }
    if (form.password.length < 8) {
        form.setError('password', 'La contraseña debe tener al menos 8 caracteres.')
        return
    }
    form.clearErrors()
    step.value = 2
}

function submit() {
    form.post('/password/setup')
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
                    <div class="w-16 h-16 rounded-[2rem] bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center shadow-lg">
                        <i class="fas fa-shield-alt text-3xl text-white"></i>
                    </div>
                </div>
                <h1 class="text-2xl font-black text-slate-800 tracking-tight">
                    Configuración <span class="gradient-text">Inicial</span>
                </h1>
                <p class="text-slate-400 font-bold text-sm mt-2">
                    Bienvenido(a), <span class="text-slate-600">{{ userName }}</span>. Antes de continuar, necesitas configurar tu seguridad.
                </p>
            </div>

            <!-- Progress Steps -->
            <div class="flex items-center justify-center gap-3 mb-8">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-black transition-all"
                         :class="step === 1 ? 'bg-primary-500 text-white shadow-lg shadow-primary-500/30' : 'bg-emerald-500 text-white'">
                        <i v-if="step > 1" class="fas fa-check text-[10px]"></i>
                        <span v-else>1</span>
                    </div>
                    <span class="text-[10px] font-black uppercase tracking-widest" :class="step === 1 ? 'text-primary-600' : 'text-emerald-600'">Contraseña</span>
                </div>
                <div class="w-12 h-[2px] bg-slate-200 rounded-full overflow-hidden">
                    <div class="h-full bg-primary-500 transition-all duration-500" :style="{ width: step >= 2 ? '100%' : '0%' }"></div>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-black transition-all"
                         :class="step === 2 ? 'bg-primary-500 text-white shadow-lg shadow-primary-500/30' : 'bg-slate-100 text-slate-400'">
                        2
                    </div>
                    <span class="text-[10px] font-black uppercase tracking-widest" :class="step === 2 ? 'text-primary-600' : 'text-slate-400'">Seguridad</span>
                </div>
            </div>

            <!-- Form Card -->
            <div class="glass-card rounded-[2.5rem] border-white/60 p-8 sm:p-10 shadow-2xl shadow-primary-900/5 relative overflow-hidden">
                <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-primary-500 via-primary-400 to-accent-400"></div>

                <!-- Step 1: Password -->
                <div v-show="step === 1" class="space-y-6">
                    <div class="text-center mb-2">
                        <h2 class="text-lg font-black text-slate-800">Establece tu contraseña privada</h2>
                        <p class="text-xs text-slate-400 font-medium mt-1">Esta contraseña será solo tuya. Nadie más la conocerá.</p>
                    </div>

                    <div class="space-y-2">
                        <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Nueva Contraseña</label>
                        <div class="relative group">
                            <input v-model="form.password" type="password" required
                                   class="w-full pl-12 pr-4 py-4 bg-slate-50 border-2 border-slate-50 rounded-2xl text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all"
                                   placeholder="Escribe tu nueva contraseña">
                            <i class="fas fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-slate-300 group-focus-within:text-primary-500 transition-colors"></i>
                        </div>
                        <p v-if="form.errors.password" class="text-xs text-red-500 font-bold mt-1 ml-1">{{ form.errors.password }}</p>
                    </div>

                    <div class="space-y-2">
                        <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Confirmar Contraseña</label>
                        <div class="relative group">
                            <input v-model="form.password_confirmation" type="password" required
                                   class="w-full pl-12 pr-4 py-4 bg-slate-50 border-2 border-slate-50 rounded-2xl text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all"
                                   placeholder="Repite tu contraseña">
                            <i class="fas fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-slate-300 group-focus-within:text-primary-500 transition-colors"></i>
                        </div>
                        <p v-if="form.errors.password_confirmation" class="text-xs text-red-500 font-bold mt-1 ml-1">{{ form.errors.password_confirmation }}</p>
                    </div>

                    <div class="bg-slate-50 rounded-2xl p-4 border border-slate-100">
                        <p class="text-[10px] font-bold text-slate-500 leading-relaxed">
                            <i class="fas fa-info-circle text-primary-400 mr-1"></i>
                            La contraseña debe tener mínimo 8 caracteres e incluir: mayúsculas, minúsculas, números y símbolos (ej. @, #, $).
                        </p>
                    </div>

                    <button @click="goToStep2" type="button"
                            class="w-full py-4 px-6 rounded-2xl text-[11px] font-black uppercase tracking-widest text-white bg-primary-600 hover:bg-primary-500 shadow-xl shadow-primary-600/20 hover:-translate-y-0.5 transition-all duration-300 flex items-center justify-center gap-3">
                        <span>Siguiente</span>
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </div>

                <!-- Step 2: Security Questions -->
                <form v-show="step === 2" @submit.prevent="submit" class="space-y-6">
                    <div class="text-center mb-2">
                        <h2 class="text-lg font-black text-slate-800">Preguntas de Seguridad</h2>
                        <p class="text-xs text-slate-400 font-medium mt-1">Estas preguntas te permitirán recuperar tu contraseña si la olvidas.</p>
                    </div>

                    <!-- Question 1 -->
                    <div class="space-y-3 bg-slate-50 rounded-2xl p-5 border border-slate-100">
                        <h3 class="text-[10px] font-black text-primary-500 uppercase tracking-widest">Pregunta 1</h3>
                        <div class="space-y-2">
                            <div class="relative">
                                <select v-model="form.question_1"
                                        class="w-full bg-white border-2 border-slate-200 rounded-xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:ring-0 outline-none transition-all appearance-none cursor-pointer shadow-sm">
                                    <option value="">Selecciona una pregunta...</option>
                                    <option v-for="q in questions" :key="q" :value="q" :disabled="q === form.question_2">{{ q }}</option>
                                </select>
                                <i class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 pointer-events-none"></i>
                            </div>
                            <p v-if="form.errors.question_1" class="text-xs text-red-500 font-bold ml-1">{{ form.errors.question_1 }}</p>
                        </div>
                        <div class="space-y-2">
                            <input v-model="form.answer_1" type="text" placeholder="Tu respuesta..."
                                   class="w-full bg-white border-2 border-slate-200 rounded-xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:ring-0 outline-none transition-all shadow-sm">
                            <p v-if="form.errors.answer_1" class="text-xs text-red-500 font-bold ml-1">{{ form.errors.answer_1 }}</p>
                        </div>
                    </div>

                    <!-- Question 2 -->
                    <div class="space-y-3 bg-slate-50 rounded-2xl p-5 border border-slate-100">
                        <h3 class="text-[10px] font-black text-primary-500 uppercase tracking-widest">Pregunta 2</h3>
                        <div class="space-y-2">
                            <div class="relative">
                                <select v-model="form.question_2"
                                        class="w-full bg-white border-2 border-slate-200 rounded-xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:ring-0 outline-none transition-all appearance-none cursor-pointer shadow-sm">
                                    <option value="">Selecciona una pregunta...</option>
                                    <option v-for="q in questions" :key="q" :value="q" :disabled="q === form.question_1">{{ q }}</option>
                                </select>
                                <i class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 pointer-events-none"></i>
                            </div>
                            <p v-if="form.errors.question_2" class="text-xs text-red-500 font-bold ml-1">{{ form.errors.question_2 }}</p>
                        </div>
                        <div class="space-y-2">
                            <input v-model="form.answer_2" type="text" placeholder="Tu respuesta..."
                                   class="w-full bg-white border-2 border-slate-200 rounded-xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:ring-0 outline-none transition-all shadow-sm">
                            <p v-if="form.errors.answer_2" class="text-xs text-red-500 font-bold ml-1">{{ form.errors.answer_2 }}</p>
                        </div>
                    </div>

                    <div class="bg-amber-50 rounded-2xl p-4 border border-amber-100">
                        <p class="text-[10px] font-bold text-amber-700 leading-relaxed">
                            <i class="fas fa-exclamation-triangle text-amber-500 mr-1"></i>
                            Recuerda bien tus respuestas. Son la única forma de recuperar tu contraseña sin ayuda del administrador.
                        </p>
                    </div>

                    <div class="flex gap-3">
                        <button @click="step = 1" type="button"
                                class="px-6 py-4 rounded-2xl text-[11px] font-black uppercase tracking-widest text-slate-500 bg-slate-100 hover:bg-slate-200 transition-all">
                            <i class="fas fa-arrow-left mr-1"></i> Atrás
                        </button>
                        <button type="submit" :disabled="form.processing"
                                class="flex-1 py-4 px-6 rounded-2xl text-[11px] font-black uppercase tracking-widest text-white bg-primary-600 hover:bg-primary-500 shadow-xl shadow-primary-600/20 hover:-translate-y-0.5 transition-all duration-300 disabled:opacity-50 flex items-center justify-center gap-3">
                            <template v-if="!form.processing">
                                <i class="fas fa-shield-alt"></i>
                                <span>Completar Configuración</span>
                            </template>
                            <template v-else>
                                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                </svg>
                                <span>Guardando...</span>
                            </template>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Footer -->
            <div class="text-center mt-8">
                <p class="text-slate-400 font-bold text-[10px] uppercase tracking-[0.2em]">
                    SGA © 2026 — Configuración de Seguridad
                </p>
            </div>
        </div>
    </div>
</template>