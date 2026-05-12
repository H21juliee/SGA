<script setup>
import { ref } from 'vue'
import { router, usePage } from '@inertiajs/vue3'

const page = usePage()
const form = ref({
    email: '',
    password: '',
    remember: false,
})
const loading = ref(false)

const errors = ref({})

function submit() {
    loading.value = true
    router.post('/login', form.value, {
        onError: (e) => {
            errors.value = e
            loading.value = false
        },
        onSuccess: () => {
            loading.value = false
        },
    })
}
</script>

<template>
    <div class="min-h-screen bg-mesh flex items-center justify-center px-4 relative overflow-hidden">
        <!-- Background decorations -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute -top-40 -right-40 w-[500px] h-[500px] bg-primary-200/20 rounded-full blur-[120px] animate-pulse-slow"></div>
            <div class="absolute -bottom-40 -left-40 w-[500px] h-[500px] bg-accent-200/20 rounded-full blur-[120px] animate-pulse-slow" style="animation-delay: 1.5s"></div>
        </div>

        <div class="relative w-full max-w-md animate-fade-in-up">
            <!-- Logo & Brand -->
            <div class="text-center mb-10">
                <div class="inline-flex items-center justify-center w-20 h-20 rounded-[2.5rem] bg-white shadow-2xl shadow-primary-500/10 mb-6 group hover:-translate-y-1 transition-all duration-500">
                    <div class="w-16 h-16 rounded-[2rem] bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center shadow-lg group-hover:rotate-12 transition-all duration-500">
                        <i class="fas fa-graduation-cap text-3xl text-white"></i>
                    </div>
                </div>
                <h1 class="text-3xl font-black text-slate-800 tracking-tight leading-tight">
                    Sistema de <span class="gradient-text">Gestión Escolar</span>
                </h1>
                <p class="text-slate-400 font-bold text-sm mt-3 uppercase tracking-widest">Panel de Acceso</p>
            </div>

            <!-- Form Card -->
            <div class="glass-card rounded-[2.5rem] border-white/60 p-10 shadow-2xl shadow-primary-900/5 relative overflow-hidden">
                <!-- Top accent line -->
                <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-primary-500 via-primary-400 to-accent-400"></div>

                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Email -->
                    <div class="space-y-2">
                        <label for="email" class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Correo Electrónico</label>
                        <div class="relative group">
                            <input
                                id="email"
                                v-model="form.email"
                                type="email"
                                required
                                autofocus
                                class="w-full pl-12 pr-4 py-4 bg-slate-50 border-2 border-slate-50 rounded-2xl text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all"
                                placeholder="nombre@colegio.com"
                            />
                            <i class="fas fa-envelope absolute left-4 top-1/2 -translate-y-1/2 text-slate-300 group-focus-within:text-primary-500 transition-colors"></i>
                        </div>
                        <p v-if="errors.email" class="mt-1 text-xs font-bold text-red-500 ml-1">{{ errors.email }}</p>
                    </div>

                    <!-- Password -->
                    <div class="space-y-2">
                        <label for="password" class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Contraseña</label>
                        <div class="relative group">
                            <input
                                id="password"
                                v-model="form.password"
                                type="password"
                                required
                                class="w-full pl-12 pr-4 py-4 bg-slate-50 border-2 border-slate-50 rounded-2xl text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all"
                                placeholder="••••••••"
                            />
                            <i class="fas fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-slate-300 group-focus-within:text-primary-500 transition-colors"></i>
                        </div>
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
                        <a href="#" class="text-xs font-black text-primary-500 hover:text-primary-600 transition-colors uppercase tracking-wider">¿Olvidaste tu clave?</a>
                    </div>

                    <!-- Submit -->
                    <button
                        type="submit"
                        :disabled="loading"
                        class="w-full py-4.5 px-6 rounded-2xl text-[11px] font-black uppercase tracking-widest text-white bg-primary-600 hover:bg-primary-500 shadow-xl shadow-primary-600/20 hover:-translate-y-0.5 transition-all duration-300 disabled:opacity-50 mt-4 flex items-center justify-center gap-3"
                    >
                        <template v-if="!loading">
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

            <!-- Footer -->
            <div class="text-center mt-10 space-y-4">
                <p class="text-slate-400 font-bold text-[10px] uppercase tracking-[0.2em]">
                    SGE © 2026 — Plataforma Educativa de Alto Rendimiento
                </p>
                <div class="flex items-center justify-center gap-6">
                    <span class="w-8 h-[1px] bg-slate-200"></span>
                    <div class="flex gap-4">
                        <i class="fab fa-facebook text-slate-300 hover:text-primary-500 cursor-pointer transition-colors"></i>
                        <i class="fab fa-twitter text-slate-300 hover:text-primary-500 cursor-pointer transition-colors"></i>
                        <i class="fab fa-instagram text-slate-300 hover:text-primary-500 cursor-pointer transition-colors"></i>
                    </div>
                    <span class="w-8 h-[1px] bg-slate-200"></span>
                </div>
            </div>
        </div>
    </div>
</template>
