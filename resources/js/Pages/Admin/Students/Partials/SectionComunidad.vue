<script setup>
const props = defineProps({
    form: { type: Object, required: true },
    hideHeader: { type: Boolean, default: false },
})

const servicesList = [
    'Electricidad',
    'Aguas blancas',
    'Cloacas',
    'Aseo urbano',
    'Telefonía',
    'Transporte público',
    'Gas comunal',
]

const serviceQualityList = ['Excelente', 'Bueno', 'Regular', 'Deficiente']

const institutionsList = [
    'Parques',
    'Canchas deportivas',
    'Plazas',
    'Biblioteca',
    'Organizaciones religiosas',
    'Centros / Módulos de salud',
]

function toggleService(item) {
    if (!Array.isArray(props.form.profile.functioning_services)) {
        props.form.profile.functioning_services = []
    }
    const idx = props.form.profile.functioning_services.indexOf(item)
    if (idx > -1) {
        props.form.profile.functioning_services.splice(idx, 1)
    } else {
        props.form.profile.functioning_services.push(item)
    }
}

function toggleInstitution(item) {
    if (!Array.isArray(props.form.profile.community_institutions)) {
        props.form.profile.community_institutions = []
    }
    const idx = props.form.profile.community_institutions.indexOf(item)
    if (idx > -1) {
        props.form.profile.community_institutions.splice(idx, 1)
    } else {
        props.form.profile.community_institutions.push(item)
    }
}
</script>

<template>
    <div class="space-y-6">
        <!-- Encabezado de Sección (Opcional si se usa dentro de un Acordeón) -->
        <div v-if="!hideHeader" class="border-b border-slate-100 pb-4">
            <h3 class="text-xl font-black text-slate-800 flex items-center gap-2.5">
                <span class="px-2.5 py-1 rounded-xl bg-primary-100 text-primary-600 flex items-center justify-center text-xs font-black">5.2</span>
                Información de la Comunidad y Entorno Social
            </h3>
            <p class="text-xs text-slate-400 font-bold mt-1 ml-10">Organización comunal, servicios comunitarios e instituciones del entorno</p>
        </div>

        <!-- Nombre de Comunidad y Consejo Comunal -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div class="space-y-1.5">
                <label class="text-[11px] font-black text-slate-500 uppercase tracking-wider ml-1">
                    Sector / Nombre Específico de la Comunidad
                </label>
                <input
                    v-model="form.profile.community_name"
                    @input="form.profile.community_name = ($event.target.value || '').toUpperCase()"
                    type="text"
                    placeholder="EJ: SECTOR 3, CALLE LOS MANGOS"
                    class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl px-4 py-3 text-slate-800 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all uppercase"
                />
            </div>

            <!-- Pertenece a Consejo Comunal -->
            <div class="bg-white border-2 border-slate-100 rounded-2xl p-4 shadow-sm flex items-center justify-between gap-3">
                <div>
                    <h4 class="text-xs font-black text-slate-800 uppercase tracking-wider">¿Pertenece al Consejo Comunal?</h4>
                    <p class="text-[11px] text-slate-400 font-bold">Participación del grupo familiar</p>
                </div>

                <div class="flex gap-1.5 shrink-0">
                    <button
                        type="button"
                        @click="form.profile.belongs_to_communal_council = true"
                        :class="form.profile.belongs_to_communal_council ? 'bg-primary-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600'"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-black uppercase tracking-wider transition-all"
                    >
                        Sí
                    </button>
                    <button
                        type="button"
                        @click="form.profile.belongs_to_communal_council = false"
                        :class="!form.profile.belongs_to_communal_council ? 'bg-slate-800 text-white shadow-sm' : 'bg-slate-100 text-slate-600'"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-black uppercase tracking-wider transition-all"
                    >
                        No
                    </button>
                </div>
            </div>
        </div>

        <!-- Servicios que funcionan en la comunidad -->
        <div class="bg-white border-2 border-slate-100 rounded-3xl p-4 sm:p-6 shadow-sm space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-3">
                <h4 class="text-xs font-black text-slate-700 uppercase tracking-wider">
                    <i class="fas fa-tools mr-1.5 text-primary-500"></i> Servicios que Funcionan en la Comunidad
                </h4>
                <span class="text-[11px] text-slate-400 font-bold">Marque los que prestan servicio de forma regular</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                <label
                    v-for="svc in servicesList"
                    :key="svc"
                    class="p-3.5 rounded-2xl border-2 cursor-pointer flex items-center justify-between gap-3 transition-all select-none"
                    :class="form.profile.functioning_services?.includes(svc) ? 'bg-primary-50/70 border-primary-400 text-primary-900 shadow-sm' : 'bg-slate-50 border-slate-200 text-slate-600 hover:bg-slate-100'"
                >
                    <span class="text-xs font-black">{{ svc }}</span>
                    <input
                        type="checkbox"
                        :checked="form.profile.functioning_services?.includes(svc)"
                        @change="toggleService(svc)"
                        class="w-4 h-4 rounded-md border-2 border-slate-300 text-primary-600 focus:ring-0 cursor-pointer shrink-0"
                    />
                </label>
            </div>

            <!-- Calidad de los servicios -->
            <div class="pt-3 border-t border-slate-100 grid grid-cols-1 sm:grid-cols-2 gap-4 items-center">
                <label class="text-[11px] font-black text-slate-500 uppercase tracking-wider ml-1">
                    Calidad General de los Servicios Comunitarios
                </label>
                <select
                    v-model="form.profile.services_quality"
                    class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl px-4 py-2.5 text-slate-800 text-xs font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all cursor-pointer"
                >
                    <option value="">Seleccione calidad percibida...</option>
                    <option v-for="q in serviceQualityList" :key="q" :value="q">{{ q }}</option>
                </select>
            </div>
        </div>

        <!-- Instituciones en la comunidad -->
        <div class="bg-white border-2 border-slate-100 rounded-3xl p-4 sm:p-6 shadow-sm space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-3">
                <h4 class="text-xs font-black text-slate-700 uppercase tracking-wider">
                    <i class="fas fa-building mr-1.5 text-primary-500"></i> Instituciones y Espacios en la Comunidad
                </h4>
                <span class="text-[11px] text-slate-400 font-bold">Espacios públicos y comunitarios cercanos</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                <label
                    v-for="inst in institutionsList"
                    :key="inst"
                    class="p-3.5 rounded-2xl border-2 cursor-pointer flex items-center justify-between transition-all select-none"
                    :class="form.profile.community_institutions?.includes(inst) ? 'bg-indigo-50/70 border-indigo-400 text-indigo-900 shadow-sm' : 'bg-slate-50 border-slate-200 text-slate-600 hover:bg-slate-100'"
                >
                    <span class="text-xs font-black">{{ inst }}</span>
                    <input
                        type="checkbox"
                        :checked="form.profile.community_institutions?.includes(inst)"
                        @change="toggleInstitution(inst)"
                        class="w-4 h-4 rounded-md border-2 border-slate-300 text-indigo-600 focus:ring-0 cursor-pointer"
                    />
                </label>
            </div>
        </div>
    </div>
</template>
