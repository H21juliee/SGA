<script setup>
import { ref, onMounted } from 'vue'

const deferredPrompt = ref(null)
const showBanner = ref(false)
const isInstalled = ref(false)

onMounted(() => {
    // Check if already in standalone mode (already installed & running as PWA)
    if (window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true) {
        isInstalled.value = true
        return
    }

    // Check if user dismissed recently (wait 7 days before asking again)
    const dismissedAt = localStorage.getItem('sga_pwa_dismissed_at')
    if (dismissedAt) {
        const daysSinceDismiss = (Date.now() - parseInt(dismissedAt, 10)) / (1000 * 60 * 60 * 24)
        if (daysSinceDismiss < 7) {
            return
        }
    }

    // Capture install prompt event
    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault()
        deferredPrompt.value = e
        showBanner.value = true
    })

    window.addEventListener('appinstalled', () => {
        showBanner.value = false
        deferredPrompt.value = null
        isInstalled.value = true
    })
})

async function installPwa() {
    if (!deferredPrompt.value) return

    deferredPrompt.value.prompt()
    const { outcome } = await deferredPrompt.value.userChoice
    if (outcome === 'accepted') {
        showBanner.value = false
    }
    deferredPrompt.value = null
}

function dismissBanner() {
    showBanner.value = false
    localStorage.setItem('sga_pwa_dismissed_at', Date.now().toString())
}
</script>

<template>
    <transition
        enter-active-class="transform transition ease-out duration-300"
        enter-from-class="translate-y-8 opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition ease-in duration-200"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 translate-y-8"
    >
        <div
            v-if="showBanner"
            class="fixed bottom-6 right-6 z-50 max-w-sm w-[calc(100%-3rem)] bg-white/95 backdrop-blur-md rounded-2xl shadow-2xl border border-primary-100 p-4 transition-all duration-300 hover:shadow-primary-900/20"
            style="box-shadow: 0 15px 35px -5px rgba(38, 70, 92, 0.2)"
        >
            <div class="flex items-start gap-3.5">
                <img
                    src="/pwa-192x192.png"
                    alt="SGA"
                    class="w-12 h-12 rounded-xl object-cover shadow-md flex-shrink-0 border border-slate-100"
                />
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between">
                        <h4 class="text-sm font-bold text-slate-800 leading-tight">Instalar App SGA</h4>
                        <button
                            @click="dismissBanner"
                            class="text-slate-400 hover:text-slate-600 transition-colors p-1"
                            title="Cerrar"
                        >
                            <i class="fas fa-times text-xs"></i>
                        </button>
                    </div>
                    <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                        Accede de forma rápida y directa desde tu pantalla de inicio.
                    </p>
                    <div class="mt-3 flex items-center gap-2">
                        <button
                            @click="installPwa"
                            class="px-3.5 py-1.5 rounded-lg text-xs font-bold text-white bg-primary-600 hover:bg-primary-700 active:scale-95 transition-all shadow-sm flex items-center gap-1.5"
                        >
                            <i class="fas fa-download text-[10px]"></i>
                            Instalar
                        </button>
                        <button
                            @click="dismissBanner"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium text-slate-500 hover:text-slate-700 transition-colors"
                        >
                            Ahora no
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </transition>
</template>
