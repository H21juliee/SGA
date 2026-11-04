<script setup>
const props = defineProps({
    form: { type: Object, required: true },
    hideHeader: { type: Boolean, default: false },
})

const housingTypes = ['Casa-quinta', 'Casa', 'Rancho', 'Apartamento', 'Habitación']
const housingTenures = ['Propia', 'Propia pagándola', 'Alquilada', 'Prestada / Cedida']
const housingConditions = ['Excelente', 'Buena', 'Regular', 'Mala']
</script>

<template>
    <div class="space-y-6">
        <!-- Encabezado de Sección (Opcional si se usa dentro de un Acordeón) -->
        <div v-if="!hideHeader" class="border-b border-slate-100 pb-4">
            <h3 class="text-xl font-black text-slate-800 flex items-center gap-2.5">
                <span class="px-2.5 py-1 rounded-xl bg-primary-100 text-primary-600 flex items-center justify-center text-xs font-black">5.1</span>
                Información de Habitación y Vivienda
            </h3>
            <p class="text-xs text-slate-400 font-bold mt-1 ml-10">Características del inmueble donde reside el estudiante</p>
        </div>

        <!-- Tipo, Tenencia y Estado -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <div class="space-y-1.5">
                <label class="text-[11px] font-black text-slate-500 uppercase tracking-wider ml-1">Tipo de Vivienda</label>
                <select
                    v-model="form.profile.housing_type"
                    class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl px-4 py-3 text-slate-800 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all cursor-pointer"
                >
                    <option value="">Seleccione tipo...</option>
                    <option v-for="t in housingTypes" :key="t" :value="t">{{ t }}</option>
                </select>
            </div>

            <div class="space-y-1.5">
                <label class="text-[11px] font-black text-slate-500 uppercase tracking-wider ml-1">Tenencia de la Vivienda</label>
                <select
                    v-model="form.profile.housing_tenure"
                    class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl px-4 py-3 text-slate-800 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all cursor-pointer"
                >
                    <option value="">Seleccione tenencia...</option>
                    <option v-for="t in housingTenures" :key="t" :value="t">{{ t }}</option>
                </select>
            </div>

            <div class="space-y-1.5">
                <label class="text-[11px] font-black text-slate-500 uppercase tracking-wider ml-1">Estado de la Vivienda</label>
                <select
                    v-model="form.profile.housing_condition"
                    class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl px-4 py-3 text-slate-800 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all cursor-pointer"
                >
                    <option value="">Seleccione estado...</option>
                    <option v-for="c in housingConditions" :key="c" :value="c">{{ c }}</option>
                </select>
            </div>
        </div>

        <!-- Servicios Básicos en la Vivienda -->
        <div class="bg-white border-2 border-slate-100 rounded-3xl p-4 sm:p-6 shadow-sm space-y-4">
            <h4 class="text-xs font-black text-slate-700 uppercase tracking-wider">
                <i class="fas fa-faucet mr-1.5 text-primary-500"></i> Servicios Básicos en la Vivienda
            </h4>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Agua Potable -->
                <div class="p-4 sm:p-5 rounded-2xl border-2 flex flex-col justify-between gap-3.5 transition-all"
                    :class="form.profile.has_water ? 'bg-cyan-50/70 border-cyan-300' : 'bg-slate-50 border-slate-200'"
                >
                    <!-- Texto Arriba -->
                    <div>
                        <p class="text-xs font-black text-slate-800">Agua Potable</p>
                        <p class="text-[10px] text-slate-400 font-bold">Por tubería</p>
                    </div>

                    <!-- Icono y Selección Abajo -->
                    <div class="flex items-center justify-between gap-2 pt-2 border-t border-slate-200/50">
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center font-black shrink-0"
                            :class="form.profile.has_water ? 'bg-cyan-500 text-white shadow-sm' : 'bg-slate-200 text-slate-400'"
                        >
                            <i class="fas fa-tint"></i>
                        </div>
                        <div class="flex gap-1.5 shrink-0">
                            <button
                                type="button"
                                @click="form.profile.has_water = true"
                                :class="form.profile.has_water ? 'bg-cyan-600 text-white shadow-sm shadow-cyan-600/20' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                                class="px-3.5 py-1.5 rounded-xl text-xs font-black uppercase tracking-wider transition-all"
                            >
                                Sí
                            </button>
                            <button
                                type="button"
                                @click="form.profile.has_water = false"
                                :class="!form.profile.has_water ? 'bg-slate-800 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                                class="px-3.5 py-1.5 rounded-xl text-xs font-black uppercase tracking-wider transition-all"
                            >
                                No
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Luz Eléctrica -->
                <div class="p-4 sm:p-5 rounded-2xl border-2 flex flex-col justify-between gap-3.5 transition-all"
                    :class="form.profile.has_electricity ? 'bg-amber-50/70 border-amber-300' : 'bg-slate-50 border-slate-200'"
                >
                    <!-- Texto Arriba -->
                    <div>
                        <p class="text-xs font-black text-slate-800">Luz Eléctrica</p>
                        <p class="text-[10px] text-slate-400 font-bold">Servicio activo</p>
                    </div>

                    <!-- Icono y Selección Abajo -->
                    <div class="flex items-center justify-between gap-2 pt-2 border-t border-slate-200/50">
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center font-black shrink-0"
                            :class="form.profile.has_electricity ? 'bg-amber-500 text-white shadow-sm' : 'bg-slate-200 text-slate-400'"
                        >
                            <i class="fas fa-bolt"></i>
                        </div>
                        <div class="flex gap-1.5 shrink-0">
                            <button
                                type="button"
                                @click="form.profile.has_electricity = true"
                                :class="form.profile.has_electricity ? 'bg-amber-600 text-white shadow-sm shadow-amber-600/20' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                                class="px-3.5 py-1.5 rounded-xl text-xs font-black uppercase tracking-wider transition-all"
                            >
                                Sí
                            </button>
                            <button
                                type="button"
                                @click="form.profile.has_electricity = false"
                                :class="!form.profile.has_electricity ? 'bg-slate-800 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                                class="px-3.5 py-1.5 rounded-xl text-xs font-black uppercase tracking-wider transition-all"
                            >
                                No
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Internet -->
                <div class="p-4 sm:p-5 rounded-2xl border-2 flex flex-col justify-between gap-3.5 transition-all"
                    :class="form.profile.has_internet ? 'bg-indigo-50/70 border-indigo-300' : 'bg-slate-50 border-slate-200'"
                >
                    <!-- Texto Arriba -->
                    <div>
                        <p class="text-xs font-black text-slate-800">Internet</p>
                        <p class="text-[10px] text-slate-400 font-bold">En el hogar</p>
                    </div>

                    <!-- Icono y Selección Abajo -->
                    <div class="flex items-center justify-between gap-2 pt-2 border-t border-slate-200/50">
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center font-black shrink-0"
                            :class="form.profile.has_internet ? 'bg-indigo-500 text-white shadow-sm' : 'bg-slate-200 text-slate-400'"
                        >
                            <i class="fas fa-wifi"></i>
                        </div>
                        <div class="flex gap-1.5 shrink-0">
                            <button
                                type="button"
                                @click="form.profile.has_internet = true"
                                :class="form.profile.has_internet ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/20' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                                class="px-3.5 py-1.5 rounded-xl text-xs font-black uppercase tracking-wider transition-all"
                            >
                                Sí
                            </button>
                            <button
                                type="button"
                                @click="form.profile.has_internet = false"
                                :class="!form.profile.has_internet ? 'bg-slate-800 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                                class="px-3.5 py-1.5 rounded-xl text-xs font-black uppercase tracking-wider transition-all"
                            >
                                No
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Servicios Adicionales -->
        <div class="space-y-1.5">
            <label class="text-[11px] font-black text-slate-500 uppercase tracking-wider ml-1">
                Servicios Adicionales en la Vivienda
            </label>
            <input
                v-model="form.profile.additional_services"
                @input="form.profile.additional_services = ($event.target.value || '').toUpperCase()"
                type="text"
                placeholder="EJ: TV POR CABLE, LÍNEA FIJA CANTV, POZO DE AGUA..."
                class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl px-4 py-3 text-slate-800 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all uppercase"
            />
        </div>
    </div>
</template>
