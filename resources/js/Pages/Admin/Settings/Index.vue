<script setup>
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'
import { ref } from 'vue'

const props = defineProps({
    settings: { type: Object, default: () => ({}) },
})

const form = useForm({
    school_name:         props.settings.school_name ?? '',
    school_code:         props.settings.school_code ?? '',
    municipality:        props.settings.municipality ?? '',
    state:               props.settings.state ?? '',
    director_name:       props.settings.director_name ?? '',
    control_study_name:  props.settings.control_study_name ?? '',
})

const logoForm = useForm({ logo: null })
const logoPreview = ref(props.settings.logo_path ? `/storage/${props.settings.logo_path}` : null)

function submit() {
    form.put('/admin/settings')
}

function onLogoChange(e) {
    const file = e.target.files[0]
    if (!file) return
    logoPreview.value = URL.createObjectURL(file)
    logoForm.logo = file
}

function uploadLogo() {
    logoForm.post('/admin/settings/logo', {
        forceFormData: true,
        onSuccess: () => {},
    })
}
</script>

<template>
    <AppLayout title="Configuración Institucional">
        <div class="space-y-8 max-w-4xl mx-auto">
            <!-- Header -->
            <div class="animate-fade-in-up">
                <h1 class="text-3xl font-extrabold text-slate-800">
                    Configuración <span class="gradient-text">Institucional</span>
                </h1>
                <p class="text-slate-400 font-medium mt-2">Datos que aparecerán en el encabezado del boletín y documentos PDF</p>
            </div>

            <!-- Logo -->
            <div class="glass-card rounded-3xl p-8 shadow-xl animate-fade-in-up" style="animation-delay: 50ms">
                <h2 class="text-lg font-black text-slate-700 mb-6 flex items-center gap-3">
                    <i class="fas fa-image text-primary-500"></i> Logo del Plantel
                </h2>
                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-8">
                    <!-- Preview -->
                    <div class="w-28 h-28 rounded-2xl border-2 border-dashed border-slate-200 bg-slate-50 flex items-center justify-center overflow-hidden flex-shrink-0">
                        <img v-if="logoPreview" :src="logoPreview" class="w-full h-full object-contain p-2" alt="Logo">
                        <i v-else class="fas fa-image text-4xl text-slate-200"></i>
                    </div>
                    <div class="flex-1 space-y-4">
                        <p class="text-sm font-medium text-slate-500">
                            Formatos aceptados: PNG, JPG, SVG. Máximo 2 MB.<br>
                            Se recomienda una imagen cuadrada de al menos 200×200px.
                        </p>
                        <div class="flex flex-col sm:flex-row gap-3">
                            <label class="cursor-pointer">
                                <input type="file" accept=".png,.jpg,.jpeg,.svg" class="hidden" @change="onLogoChange">
                                <span class="px-5 py-2.5 rounded-xl bg-slate-100 text-slate-600 font-bold text-sm hover:bg-slate-200 transition-all flex items-center gap-2">
                                    <i class="fas fa-folder-open"></i> Seleccionar archivo
                                </span>
                            </label>
                            <button
                                v-if="logoForm.logo"
                                @click="uploadLogo"
                                :disabled="logoForm.processing"
                                class="px-5 py-2.5 rounded-xl bg-primary-600 text-white font-bold text-sm hover:bg-primary-500 hover:-translate-y-0.5 transition-all flex items-center gap-2 shadow-md shadow-primary-600/20"
                            >
                                <i class="fas fa-upload"></i> Subir Logo
                            </button>
                        </div>
                        <p v-if="logoForm.errors.logo" class="text-red-500 text-xs font-bold">{{ logoForm.errors.logo }}</p>
                    </div>
                </div>
            </div>

            <!-- Settings Form -->
            <div class="glass-card rounded-3xl p-8 shadow-xl animate-fade-in-up" style="animation-delay: 100ms">
                <h2 class="text-lg font-black text-slate-700 mb-6 flex items-center gap-3">
                    <i class="fas fa-school text-primary-500"></i> Datos del Plantel
                </h2>
                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2 space-y-2">
                            <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest">Nombre del Plantel</label>
                            <input v-model="form.school_name" type="text" placeholder="Ej. Liceo Luis Enrique Márquez Barillas"
                                class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all">
                        </div>

                        <div class="space-y-2">
                            <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest">Código del Plantel</label>
                            <input v-model="form.school_code" type="text" placeholder="Ej. S0622D1420"
                                class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all">
                        </div>

                        <div class="space-y-2">
                            <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest">Municipio</label>
                            <input v-model="form.municipality" type="text" placeholder="Ej. Lagunillas"
                                class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all">
                        </div>

                        <div class="space-y-2">
                            <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest">Estado</label>
                            <input v-model="form.state" type="text" placeholder="Ej. Mérida"
                                class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all">
                        </div>

                        <div class="space-y-2">
                            <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest">Nombre del Director(a)</label>
                            <input v-model="form.director_name" type="text" placeholder="Ej. Prof. Wuilliam Rojo"
                                class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all">
                        </div>

                        <div class="space-y-2">
                            <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest">Control de Estudio</label>
                            <input v-model="form.control_study_name" type="text" placeholder="Ej. Prof. Maiddy Davila"
                                class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all">
                        </div>
                    </div>

                    <div class="flex justify-end pt-4 border-t border-slate-100">
                        <button v-if="$can('settings.manage')" type="submit"
                            :disabled="form.processing"
                            class="px-10 py-3.5 bg-primary-600 text-white text-[11px] font-black uppercase tracking-widest rounded-2xl shadow-lg shadow-primary-600/20 hover:bg-primary-500 hover:-translate-y-0.5 transition-all flex items-center gap-2"
                        >
                            <i class="fas fa-save"></i> Guardar Configuración
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
