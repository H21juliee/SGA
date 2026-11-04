<script setup>
import { computed } from 'vue'

const props = defineProps({
    show: { type: Boolean, default: false },
    form: { type: Object, required: true },
    selectedGuardian: { type: Object, default: null },
    communities: { type: Array, default: () => [] },
    submitting: { type: Boolean, default: false },
})

const emit = defineEmits(['close', 'confirm', 'goToStep'])

const communityName = computed(() => {
    if (!props.form.community_id) return 'No seleccionada'
    const c = props.communities.find(item => String(item.id) === String(props.form.community_id))
    return c ? c.name : 'No seleccionada'
})

const studentAge = computed(() => {
    if (!props.form.birth_date) return null
    const birth = new Date(props.form.birth_date)
    const today = new Date()
    let age = today.getFullYear() - birth.getFullYear()
    const m = today.getMonth() - birth.getMonth()
    if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) {
        age--
    }
    return age
})

const docsConsigned = computed(() => {
    return props.form.profile?.documents_consignment || []
})

const deliveredDocsCount = computed(() => {
    return docsConsigned.value.filter(d => d.delivered).length
})

const pendingMandatoryCount = computed(() => {
    return docsConsigned.value.filter(d => d.is_mandatory && !d.delivered).length
})

function handleEditStep(stepNumber) {
    emit('goToStep', stepNumber)
    emit('close')
}
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" @click="emit('close')"></div>

        <div class="min-h-screen px-4 text-center flex items-center justify-center py-6 sm:py-12">
            <div
                class="relative bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:max-w-4xl w-full border border-slate-100 flex flex-col max-h-[90vh]"
                @click.stop
            >
                <!-- Cabecera del Modal -->
                <div class="bg-gradient-to-r from-slate-900 via-primary-950 to-slate-900 px-6 py-5 text-white flex items-center justify-between shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-white/10 flex items-center justify-center text-primary-400 text-lg">
                            <i class="fas fa-clipboard-check"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-black tracking-tight">Revisión de Ficha de Inscripción</h3>
                            <p class="text-xs text-slate-400 font-medium mt-0.5">Verifica los datos suministrados antes del registro definitivo</p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="emit('close')"
                        class="w-9 h-9 rounded-xl bg-white/10 hover:bg-white/20 text-slate-300 hover:text-white flex items-center justify-center transition-colors"
                    >
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <!-- Cuerpo Scrollable -->
                <div class="p-4 sm:p-6 space-y-5 overflow-y-auto flex-1 bg-slate-50/50">
                    <!-- Estatus de Recaudos Banner -->
                    <div
                        class="p-4 rounded-2xl border-2 flex items-center justify-between gap-3"
                        :class="pendingMandatoryCount === 0 ? 'bg-emerald-50 border-emerald-200 text-emerald-900' : 'bg-amber-50 border-amber-200 text-amber-900'"
                    >
                        <div class="flex items-center gap-3">
                            <i :class="pendingMandatoryCount === 0 ? 'fas fa-check-circle text-emerald-500 text-xl' : 'fas fa-exclamation-triangle text-amber-500 text-xl'"></i>
                            <div>
                                <h4 class="text-xs font-black uppercase tracking-wider">
                                    {{ pendingMandatoryCount === 0 ? 'Expediente Completo' : `Expediente con ${pendingMandatoryCount} Recaudo(s) Pendiente(s)` }}
                                </h4>
                                <p class="text-[11px] font-semibold opacity-90 mt-0.5">
                                    {{ deliveredDocsCount }} de {{ docsConsigned.length }} documentos entregados.
                                </p>
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="handleEditStep(6)"
                            class="px-3 py-1.5 rounded-xl text-xs font-bold bg-white shadow-xs border border-slate-200 hover:bg-slate-50 transition-all shrink-0"
                        >
                            Ver recaudos
                        </button>
                    </div>

                    <!-- 1. Representante Legal -->
                    <div class="bg-white border-2 border-slate-200/80 rounded-2xl p-4 sm:p-5 shadow-xs space-y-3">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                            <div class="flex items-center gap-2">
                                <span class="w-6 h-6 rounded-lg bg-primary-100 text-primary-600 flex items-center justify-center text-xs font-black">1</span>
                                <h4 class="text-xs font-black uppercase tracking-wider text-slate-800">Representante Legal</h4>
                            </div>
                            <button
                                type="button"
                                @click="handleEditStep(1)"
                                class="text-[11px] font-bold text-primary-600 hover:text-primary-800 flex items-center gap-1"
                            >
                                <i class="fas fa-edit text-[10px]"></i> Modificar
                            </button>
                        </div>
                        <div v-if="selectedGuardian" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3 text-xs">
                            <div>
                                <span class="text-slate-400 font-bold block text-[10px] uppercase">Nombre y Apellido</span>
                                <span class="font-extrabold text-slate-800">{{ selectedGuardian.first_name }} {{ selectedGuardian.last_name }}</span>
                            </div>
                            <div>
                                <span class="text-slate-400 font-bold block text-[10px] uppercase">Cédula / Documento</span>
                                <span class="font-bold text-slate-700">{{ selectedGuardian.doc_type || 'V' }}-{{ selectedGuardian.doc_number || selectedGuardian.cedula }}</span>
                            </div>
                            <div>
                                <span class="text-slate-400 font-bold block text-[10px] uppercase">Parentesco</span>
                                <span class="font-bold text-slate-700">{{ selectedGuardian.relationship || 'REPRESENTANTE' }}</span>
                            </div>
                            <div>
                                <span class="text-slate-400 font-bold block text-[10px] uppercase">Teléfono Móvil</span>
                                <span class="font-bold text-slate-700">{{ selectedGuardian.phone || 'No registrado' }}</span>
                            </div>
                            <div>
                                <span class="text-slate-400 font-bold block text-[10px] uppercase">Correo Electrónico</span>
                                <span class="font-bold text-slate-700">{{ selectedGuardian.email || 'No registrado' }}</span>
                            </div>
                            <div>
                                <span class="text-slate-400 font-bold block text-[10px] uppercase">Ocupación / Trabajo</span>
                                <span class="font-bold text-slate-700">{{ selectedGuardian.occupation || (selectedGuardian.is_working ? 'TRABAJA' : 'NO TRABAJA') }}</span>
                            </div>
                        </div>
                        <div v-else class="text-xs text-rose-600 font-bold">
                            No se ha seleccionado representante legal.
                        </div>
                    </div>

                    <!-- 2. Información del Estudiante -->
                    <div class="bg-white border-2 border-slate-200/80 rounded-2xl p-4 sm:p-5 shadow-xs space-y-3">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                            <div class="flex items-center gap-2">
                                <span class="w-6 h-6 rounded-lg bg-primary-100 text-primary-600 flex items-center justify-center text-xs font-black">2</span>
                                <h4 class="text-xs font-black uppercase tracking-wider text-slate-800">Datos del Estudiante</h4>
                            </div>
                            <button
                                type="button"
                                @click="handleEditStep(2)"
                                class="text-[11px] font-bold text-primary-600 hover:text-primary-800 flex items-center gap-1"
                            >
                                <i class="fas fa-edit text-[10px]"></i> Modificar
                            </button>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3 text-xs">
                            <div>
                                <span class="text-slate-400 font-bold block text-[10px] uppercase">Nombre Completo</span>
                                <span class="font-extrabold text-slate-800">{{ form.first_name }} {{ form.last_name }}</span>
                            </div>
                            <div>
                                <span class="text-slate-400 font-bold block text-[10px] uppercase">Documento / Cédula</span>
                                <span class="font-bold text-slate-700">{{ form.cedula || 'En trámite / Sin cédula' }}</span>
                            </div>
                            <div>
                                <span class="text-slate-400 font-bold block text-[10px] uppercase">Sexo y Nacionalidad</span>
                                <span class="font-bold text-slate-700">{{ form.gender === 'M' ? 'MASCULINO' : 'FEMENINO' }} ({{ form.nationality }})</span>
                            </div>
                            <div>
                                <span class="text-slate-400 font-bold block text-[10px] uppercase">Fecha Nacimiento / Edad</span>
                                <span class="font-bold text-slate-700">{{ form.birth_date }} <span v-if="studentAge">({{ studentAge }} años)</span></span>
                            </div>
                            <div>
                                <span class="text-slate-400 font-bold block text-[10px] uppercase">Lugar de Nacimiento</span>
                                <span class="font-bold text-slate-700">{{ form.birth_place || 'No indicado' }}</span>
                            </div>
                            <div>
                                <span class="text-slate-400 font-bold block text-[10px] uppercase">Comunidad Asignada</span>
                                <span class="font-bold text-slate-700">{{ communityName }}</span>
                            </div>
                            <div class="sm:col-span-2 md:col-span-3">
                                <span class="text-slate-400 font-bold block text-[10px] uppercase">Dirección de Habitación</span>
                                <span class="font-medium text-slate-700">{{ form.address || 'No indicada' }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- 3. Ficha Familiar y Salud (2 columnas en desktop) -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Ficha Familiar -->
                        <div class="bg-white border-2 border-slate-200/80 rounded-2xl p-4 sm:p-5 shadow-xs space-y-3">
                            <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                                <div class="flex items-center gap-2">
                                    <span class="w-6 h-6 rounded-lg bg-primary-100 text-primary-600 flex items-center justify-center text-xs font-black">3</span>
                                    <h4 class="text-xs font-black uppercase tracking-wider text-slate-800">Entorno Familiar</h4>
                                </div>
                                <button
                                    type="button"
                                    @click="handleEditStep(3)"
                                    class="text-[11px] font-bold text-primary-600 hover:text-primary-800 flex items-center gap-1"
                                >
                                    <i class="fas fa-edit text-[10px]"></i> Modificar
                                </button>
                            </div>
                            <div class="space-y-2 text-xs">
                                <div>
                                    <span class="text-slate-400 font-bold block text-[10px] uppercase">Vive con:</span>
                                    <div class="flex flex-wrap gap-1 mt-1">
                                        <span
                                            v-for="lw in form.profile?.lives_with || []"
                                            :key="lw"
                                            class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 text-[10px] font-bold"
                                        >
                                            {{ lw }}
                                        </span>
                                        <span v-if="!form.profile?.lives_with?.length" class="text-slate-400 italic">No especificado</span>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-2 pt-1">
                                    <div>
                                        <span class="text-slate-400 font-bold block text-[10px] uppercase">Canaima:</span>
                                        <span class="font-bold text-slate-700">{{ form.profile?.has_canaima ? 'SÍ TIENE' : 'NO TIENE' }}</span>
                                    </div>
                                    <div>
                                        <span class="text-slate-400 font-bold block text-[10px] uppercase">Comedor Escolar:</span>
                                        <span class="font-bold text-slate-700">{{ form.profile?.uses_canteen ? 'SÍ USA' : 'NO USA' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Salud y Médica -->
                        <div class="bg-white border-2 border-slate-200/80 rounded-2xl p-4 sm:p-5 shadow-xs space-y-3">
                            <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                                <div class="flex items-center gap-2">
                                    <span class="w-6 h-6 rounded-lg bg-primary-100 text-primary-600 flex items-center justify-center text-xs font-black">4</span>
                                    <h4 class="text-xs font-black uppercase tracking-wider text-slate-800">Ficha Médica</h4>
                                </div>
                                <button
                                    type="button"
                                    @click="handleEditStep(4)"
                                    class="text-[11px] font-bold text-primary-600 hover:text-primary-800 flex items-center gap-1"
                                >
                                    <i class="fas fa-edit text-[10px]"></i> Modificar
                                </button>
                            </div>
                            <div class="grid grid-cols-3 gap-2 text-xs">
                                <div>
                                    <span class="text-slate-400 font-bold block text-[10px] uppercase">Grupo Sanguíneo</span>
                                    <span class="font-bold text-slate-700">{{ form.profile?.blood_type || 'No indicado' }}</span>
                                </div>
                                <div>
                                    <span class="text-slate-400 font-bold block text-[10px] uppercase">Estatura</span>
                                    <span class="font-bold text-slate-700">{{ form.profile?.height ? `${form.profile.height} cm` : 'N/A' }}</span>
                                </div>
                                <div>
                                    <span class="text-slate-400 font-bold block text-[10px] uppercase">Peso</span>
                                    <span class="font-bold text-slate-700">{{ form.profile?.weight ? `${form.profile.weight} kg` : 'N/A' }}</span>
                                </div>
                            </div>
                            <div class="text-xs pt-1 space-y-1">
                                <div v-if="form.profile?.has_medical_condition">
                                    <span class="text-rose-600 font-bold">Condición Médica:</span>
                                    <span class="text-slate-700 font-medium ml-1">{{ form.profile?.medical_condition_detail }}</span>
                                </div>
                                <div v-if="form.profile?.has_allergies">
                                    <span class="text-amber-600 font-bold">Alergias:</span>
                                    <span class="text-slate-700 font-medium ml-1">{{ form.profile?.allergies_detail }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 4. Resumen de Documentos Consignados -->
                    <div class="bg-white border-2 border-slate-200/80 rounded-2xl p-4 sm:p-5 shadow-xs space-y-3">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                            <div class="flex items-center gap-2">
                                <span class="w-6 h-6 rounded-lg bg-primary-100 text-primary-600 flex items-center justify-center text-xs font-black">6</span>
                                <h4 class="text-xs font-black uppercase tracking-wider text-slate-800">Recaudos y Consignación</h4>
                            </div>
                            <button
                                type="button"
                                @click="handleEditStep(6)"
                                class="text-[11px] font-bold text-primary-600 hover:text-primary-800 flex items-center gap-1"
                            >
                                <i class="fas fa-edit text-[10px]"></i> Modificar
                            </button>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                            <div
                                v-for="doc in docsConsigned"
                                :key="doc.code"
                                class="flex items-center justify-between p-2 rounded-xl border"
                                :class="doc.delivered ? 'bg-emerald-50/40 border-emerald-200' : 'bg-slate-50 border-slate-200'"
                            >
                                <div class="flex items-center gap-2 min-w-0">
                                    <i :class="doc.delivered ? 'fas fa-check-circle text-emerald-600' : 'far fa-circle text-slate-400'"></i>
                                    <span class="font-bold text-slate-700 truncate text-[11px]">{{ doc.name }}</span>
                                </div>
                                <span
                                    class="px-2 py-0.5 rounded text-[9px] font-black uppercase shrink-0"
                                    :class="doc.delivered ? 'bg-emerald-100 text-emerald-800' : (doc.is_mandatory ? 'bg-rose-100 text-rose-800' : 'bg-slate-200 text-slate-600')"
                                >
                                    {{ doc.delivered ? 'Consignado' : (doc.is_mandatory ? 'Pendiente' : 'Opcional') }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer del Modal -->
                <div class="bg-slate-100 px-6 py-4 flex flex-col sm:flex-row items-center justify-between gap-3 shrink-0 border-t border-slate-200">
                    <button
                        type="button"
                        @click="emit('close')"
                        class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 font-bold text-xs transition-colors"
                    >
                        Volver a revisar
                    </button>

                    <button
                        type="button"
                        @click="emit('confirm')"
                        :disabled="submitting"
                        class="w-full sm:w-auto px-6 py-2.5 rounded-xl bg-primary-600 hover:bg-primary-700 active:scale-95 text-white font-extrabold text-xs shadow-md shadow-primary-600/30 transition-all flex items-center justify-center gap-2 disabled:opacity-50"
                    >
                        <i v-if="submitting" class="fas fa-spinner fa-spin"></i>
                        <i v-else class="fas fa-check"></i>
                        <span>Confirmar y Registrar Estudiante</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
