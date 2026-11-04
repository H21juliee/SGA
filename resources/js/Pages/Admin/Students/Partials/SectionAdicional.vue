<script setup>
const props = defineProps({
    form: { type: Object, required: true },
})

const livesWithOptions = ['Madre', 'Padre', 'Otros']
const religionOptions = [
    'Católica',
    'Evangélica',
    'Testigo de Jehová',
    'Adventista',
    'Mormona',
    'Otra religión',
    'Ninguna / No profesa',
]

function toggleLivesWith(opt) {
    if (!Array.isArray(props.form.profile.lives_with)) {
        props.form.profile.lives_with = []
    }
    const idx = props.form.profile.lives_with.indexOf(opt)
    if (idx > -1) {
        props.form.profile.lives_with.splice(idx, 1)
    } else {
        props.form.profile.lives_with.push(opt)
    }
}
</script>

<template>
    <div class="space-y-6">
        <!-- Encabezado de Sección -->
        <div class="border-b border-slate-100 pb-4">
            <h3 class="text-xl font-black text-slate-800 flex items-center gap-2.5">
                <span class="w-8 h-8 rounded-xl bg-primary-100 text-primary-600 flex items-center justify-center text-sm font-black">3</span>
                Información Adicional y Entorno Familiar
            </h3>
            <p class="text-xs text-slate-400 font-bold mt-1 ml-10">Convivencia familiar, religión, recursos y actividades</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Vive Con (Selección múltiple) -->
            <div class="space-y-2 md:col-span-2">
                <label class="text-[11px] font-black text-slate-500 uppercase tracking-wider ml-1">
                    ¿Con quién vive el estudiante? (Seleccione todos los que apliquen)
                </label>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <label
                        v-for="opt in livesWithOptions"
                        :key="opt"
                        class="p-4 rounded-2xl border-2 cursor-pointer flex items-center justify-between transition-all select-none"
                        :class="form.profile.lives_with?.includes(opt) ? 'bg-primary-50/60 border-primary-400 text-primary-900 shadow-sm' : 'bg-slate-50 border-slate-200 text-slate-600 hover:bg-slate-100'"
                    >
                        <div class="flex items-center gap-3">
                            <i :class="opt === 'Madre' ? 'fa-female' : opt === 'Padre' ? 'fa-male' : 'fa-users'" class="fas text-primary-500"></i>
                            <span class="text-sm font-black">{{ opt }}</span>
                        </div>
                        <input
                            type="checkbox"
                            :checked="form.profile.lives_with?.includes(opt)"
                            @change="toggleLivesWith(opt)"
                            class="w-5 h-5 rounded-lg border-2 border-slate-300 text-primary-600 focus:ring-0 cursor-pointer"
                        />
                    </label>
                </div>
            </div>

            <!-- N.º Grupo Familiar -->
            <div class="space-y-1.5">
                <label class="text-[11px] font-black text-slate-500 uppercase tracking-wider ml-1">
                    N.º de Personas del Grupo Familiar
                </label>
                <input
                    v-model.number="form.profile.family_group_count"
                    type="number"
                    min="1"
                    max="30"
                    placeholder="EJ: 4"
                    class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl px-4 py-3 text-slate-800 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all"
                />
            </div>

            <!-- Religión -->
            <div class="space-y-1.5">
                <label class="text-[11px] font-black text-slate-500 uppercase tracking-wider ml-1">
                    Religión o Creencia
                </label>
                <select
                    v-model="form.profile.religion"
                    class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl px-4 py-3 text-slate-800 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all cursor-pointer"
                >
                    <option value="">Seleccione religión o creencia...</option>
                    <option v-for="r in religionOptions" :key="r" :value="r">{{ r }}</option>
                </select>
            </div>
        </div>

        <!-- Toggles de Actividades, Canaima y Comedor -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5 pt-2">
            <!-- Actividades Extraescolares -->
            <div class="bg-white border-2 border-slate-100 rounded-3xl p-5 shadow-sm space-y-4 md:col-span-3">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-black">
                            <i class="fas fa-futbol"></i>
                        </div>
                        <div>
                            <h4 class="text-sm font-black text-slate-800">Actividades Extraescolares</h4>
                            <p class="text-xs text-slate-400 font-bold">¿Practica deportes, música u otra actividad fuera de clases?</p>
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <button
                            type="button"
                            @click="form.profile.extracurricular_activities = true"
                            :class="form.profile.extracurricular_activities ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/20' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                            class="px-5 py-2 rounded-xl text-xs font-black uppercase tracking-wider transition-all"
                        >
                            Sí
                        </button>
                        <button
                            type="button"
                            @click="form.profile.extracurricular_activities = false; form.profile.extracurricular_activities_detail = ''"
                            :class="!form.profile.extracurricular_activities ? 'bg-slate-800 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                            class="px-5 py-2 rounded-xl text-xs font-black uppercase tracking-wider transition-all"
                        >
                            No
                        </button>
                    </div>
                </div>

                <!-- Detalle condicional -->
                <transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0">
                    <div v-if="form.profile.extracurricular_activities" class="pt-3 border-t border-slate-100">
                        <label class="text-[11px] font-black text-indigo-600 uppercase tracking-wider ml-1">
                            ¿Cuál actividad practica? <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="form.profile.extracurricular_activities_detail"
                            @input="form.profile.extracurricular_activities_detail = ($event.target.value || '').toUpperCase()"
                            type="text"
                            placeholder="EJ: BÉISBOL MENOR, DANZAS TRADICIONALES, SISTEMA DE ORQUESTAS..."
                            class="w-full mt-1.5 bg-slate-50 border-2 border-indigo-200 rounded-2xl px-4 py-2.5 text-slate-800 text-sm font-bold focus:border-indigo-400 focus:bg-white outline-none transition-all uppercase"
                        />
                        <p v-if="form.errors['profile.extracurricular_activities_detail']" class="text-xs text-red-500 font-bold ml-1 mt-1">
                            {{ form.errors['profile.extracurricular_activities_detail'] }}
                        </p>
                    </div>
                </transition>
            </div>

            <!-- Posee Canaima -->
            <div class="bg-white border-2 border-slate-100 rounded-3xl p-5 shadow-sm flex items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-sky-50 text-sky-600 flex items-center justify-center font-black">
                        <i class="fas fa-laptop"></i>
                    </div>
                    <div>
                        <h4 class="text-sm font-black text-slate-800">Posee Canaima</h4>
                        <p class="text-[11px] text-slate-400 font-bold">Portátil o tablet educativa</p>
                    </div>
                </div>

                <div class="flex gap-1.5">
                    <button
                        type="button"
                        @click="form.profile.has_canaima = true"
                        :class="form.profile.has_canaima ? 'bg-sky-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600'"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-black uppercase tracking-wider transition-all"
                    >
                        Sí
                    </button>
                    <button
                        type="button"
                        @click="form.profile.has_canaima = false"
                        :class="!form.profile.has_canaima ? 'bg-slate-800 text-white shadow-sm' : 'bg-slate-100 text-slate-600'"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-black uppercase tracking-wider transition-all"
                    >
                        No
                    </button>
                </div>
            </div>

            <!-- Usa Comedor Escolar -->
            <div class="bg-white border-2 border-slate-100 rounded-3xl p-5 shadow-sm flex items-center justify-between gap-3 md:col-span-2">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-black">
                        <i class="fas fa-utensils"></i>
                    </div>
                    <div>
                        <h4 class="text-sm font-black text-slate-800">Usa el Comedor Escolar</h4>
                        <p class="text-[11px] text-slate-400 font-bold">Programa de alimentación escolar (PAE)</p>
                    </div>
                </div>

                <div class="flex gap-1.5">
                    <button
                        type="button"
                        @click="form.profile.uses_canteen = true"
                        :class="form.profile.uses_canteen ? 'bg-emerald-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600'"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-black uppercase tracking-wider transition-all"
                    >
                        Sí
                    </button>
                    <button
                        type="button"
                        @click="form.profile.uses_canteen = false"
                        :class="!form.profile.uses_canteen ? 'bg-slate-800 text-white shadow-sm' : 'bg-slate-100 text-slate-600'"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-black uppercase tracking-wider transition-all"
                    >
                        No
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
