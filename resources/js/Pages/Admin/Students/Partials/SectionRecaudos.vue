<script setup>
import { computed, onMounted, watch } from 'vue'

const props = defineProps({
    form: { type: Object, required: true },
})

const DEFAULT_DOCUMENTS = [
    // Estudiante
    {
        code: 'birth_certificate',
        name: 'Partida de Nacimiento',
        description: 'Copia legible y/o vista del documento original',
        target: 'ESTUDIANTE',
        is_mandatory: true,
        icon: 'fa-certificate',
    },
    {
        code: 'student_id_card',
        name: 'Cédula de Identidad del Alumno',
        description: 'Copia legible (si ya posee cédula SAIME emitida)',
        target: 'ESTUDIANTE',
        is_mandatory: false,
        icon: 'fa-id-card',
    },
    {
        code: 'student_photos',
        name: 'Fotos Tipo Carnet del Alumno',
        description: 'Cuatro (4) fotografías recientes en fondo blanco',
        target: 'ESTUDIANTE',
        is_mandatory: true,
        icon: 'fa-camera',
    },
    {
        code: 'academic_certificate',
        name: 'Boleta de Promoción / Notas Certificadas',
        description: 'Certificación de calificaciones del año o grado anterior',
        target: 'ESTUDIANTE',
        is_mandatory: true,
        icon: 'fa-graduation-cap',
    },
    {
        code: 'conduct_withdrawal_letter',
        name: 'Constancia de Retiro y Buena Conducta',
        description: 'Emitida por el plantel de procedencia (si aplica)',
        target: 'ESTUDIANTE',
        is_mandatory: false,
        icon: 'fa-file-signature',
    },
    {
        code: 'medical_health_certificate',
        name: 'Certificado de Salud / Tarjeta de Vacunas',
        description: 'Constancia médica integral y control de inmunizaciones',
        target: 'ESTUDIANTE',
        is_mandatory: true,
        icon: 'fa-heartbeat',
    },
    // Representante
    {
        code: 'guardian_id_card',
        name: 'Copia Cédula del Representante',
        description: 'Fotocopia ampliada, nítida y vigente del responsable',
        target: 'REPRESENTANTE',
        is_mandatory: true,
        icon: 'fa-address-card',
    },
    {
        code: 'guardian_photos',
        name: 'Fotos Tipo Carnet del Representante',
        description: 'Dos (2) fotografías recientes del adulto responsable',
        target: 'REPRESENTANTE',
        is_mandatory: true,
        icon: 'fa-user-circle',
    },
    {
        code: 'residence_proof',
        name: 'Constancia de Residencia / Recibo de Servicio',
        description: 'Emitida por Consejo Comunal, CNE o recibo público',
        target: 'REPRESENTANTE',
        is_mandatory: true,
        icon: 'fa-map-marked-alt',
    },
    {
        code: 'employment_proof',
        name: 'Constancia de Trabajo o Ingresos',
        description: 'Constancia laboral o certificación de ingresos firmada',
        target: 'REPRESENTANTE',
        is_mandatory: false,
        icon: 'fa-briefcase',
    },
    {
        code: 'legal_guardianship_document',
        name: 'Documento de Tutela / Custodia Legal',
        description: 'Poder notariado o acta CPNNA si no es padre/madre',
        target: 'REPRESENTANTE',
        is_mandatory: false,
        icon: 'fa-gavel',
    },
]

function ensureDocuments() {
    if (!props.form.profile) {
        props.form.profile = {}
    }
    if (!Array.isArray(props.form.profile.documents_consignment)) {
        props.form.profile.documents_consignment = []
    }

    const today = new Date().toISOString().split('T')[0]
    const currentList = [...props.form.profile.documents_consignment]
    let changed = false

    DEFAULT_DOCUMENTS.forEach(defDoc => {
        const found = currentList.find(d => d.code === defDoc.code)
        if (!found) {
            currentList.push({
                code: defDoc.code,
                name: defDoc.name,
                description: defDoc.description,
                target: defDoc.target,
                is_mandatory: defDoc.is_mandatory,
                icon: defDoc.icon,
                delivered: false,
                delivered_at: today,
                notes: '',
            })
            changed = true
        }
    })

    if (changed || props.form.profile.documents_consignment.length !== currentList.length) {
        props.form.profile.documents_consignment = currentList
    }
    return currentList
}

watch(
    () => props.form.profile?.documents_consignment,
    (val) => {
        if (!Array.isArray(val) || val.length === 0) {
            ensureDocuments()
        }
    },
    { immediate: true, deep: true }
)

onMounted(() => {
    ensureDocuments()
})

const documentsList = computed(() => {
    const list = props.form.profile?.documents_consignment
    if (!Array.isArray(list) || list.length === 0) {
        return ensureDocuments()
    }
    return list
})

const studentDocs = computed(() => {
    return documentsList.value.filter(d => d.target === 'ESTUDIANTE')
})

const guardianDocs = computed(() => {
    return documentsList.value.filter(d => d.target === 'REPRESENTANTE')
})

const totalDocsCount = computed(() => documentsList.value.length)
const deliveredCount = computed(() => documentsList.value.filter(d => d.delivered).length)
const pendingMandatoryCount = computed(() => documentsList.value.filter(d => d.is_mandatory && !d.delivered).length)

function setDocumentDelivered(doc, value) {
    doc.delivered = value
    if (value && !doc.delivered_at) {
        doc.delivered_at = new Date().toISOString().split('T')[0]
    }
}
</script>

<template>
    <div class="space-y-6 animate-fade-in">
        <!-- Encabezado de Sección -->
        <div class="border-b border-slate-100 pb-4">
            <h3 class="text-xl font-black text-slate-800 flex items-center gap-2.5">
                <span class="w-8 h-8 rounded-xl bg-primary-100 text-primary-600 flex items-center justify-center text-sm font-black">6</span>
                Consignación de Recaudos y Documentos
            </h3>
            <p class="text-xs text-slate-400 font-bold mt-1 ml-10">
                Registro y control de documentos entregados para el expediente físico del estudiante
            </p>
        </div>

        <!-- Barra de Progreso y Estatus de Expediente -->
        <div class="bg-gradient-to-r from-slate-50 to-primary-50/30 border-2 border-slate-200/80 rounded-3xl p-4 sm:p-6 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <div
                    class="w-12 h-12 rounded-2xl flex items-center justify-center text-xl shrink-0 shadow-sm"
                    :class="pendingMandatoryCount === 0 ? 'bg-emerald-500 text-white shadow-emerald-500/20' : 'bg-amber-500 text-white shadow-amber-500/20'"
                >
                    <i :class="pendingMandatoryCount === 0 ? 'fas fa-clipboard-check' : 'fas fa-folder-open'"></i>
                </div>
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-xs font-black uppercase tracking-wider text-slate-800">Estado del Expediente</span>
                        <span
                            class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider"
                            :class="pendingMandatoryCount === 0 ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : 'bg-amber-100 text-amber-800 border border-amber-300'"
                        >
                            {{ pendingMandatoryCount === 0 ? 'Expediente Completo' : `Pendientes Obligatorios (${pendingMandatoryCount})` }}
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 font-semibold mt-0.5">
                        {{ deliveredCount }} de {{ totalDocsCount }} documentos consignados en total
                    </p>
                </div>
            </div>

            <!-- Mini barra visual -->
            <div class="w-full sm:w-48 bg-slate-200 rounded-full h-2.5 overflow-hidden">
                <div
                    class="h-full transition-all duration-500 rounded-full"
                    :class="pendingMandatoryCount === 0 ? 'bg-emerald-500' : 'bg-primary-600'"
                    :style="{ width: `${totalDocsCount ? Math.round((deliveredCount / totalDocsCount) * 100) : 0}%` }"
                ></div>
            </div>
        </div>

        <!-- Bloque 1: Recaudos del Estudiante -->
        <div class="space-y-4">
            <div class="flex items-center gap-2">
                <i class="fas fa-user-graduate text-primary-600 text-sm"></i>
                <h4 class="text-xs font-black text-slate-700 uppercase tracking-widest">Recaudos del Estudiante</h4>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div
                    v-for="doc in studentDocs"
                    :key="doc.code"
                    class="border-2 rounded-2xl p-4 sm:p-5 transition-all bg-white"
                    :class="doc.delivered ? 'border-emerald-200 bg-emerald-50/20 shadow-xs' : 'border-slate-200 hover:border-slate-300'"
                >
                    <!-- Cabecera del Documento -->
                    <div class="flex items-start justify-between gap-3 mb-2.5">
                        <div class="flex items-start gap-2.5 min-w-0">
                            <div
                                class="w-8 h-8 rounded-xl flex items-center justify-center text-xs shrink-0 mt-0.5"
                                :class="doc.delivered ? 'bg-emerald-500 text-white' : 'bg-slate-100 text-slate-500'"
                            >
                                <i :class="['fas', doc.icon || 'fa-file']"></i>
                            </div>
                            <div class="min-w-0">
                                <h5 class="text-xs font-black text-slate-800 leading-snug">{{ doc.name }}</h5>
                                <p class="text-[11px] text-slate-400 font-semibold line-clamp-1 mt-0.5">{{ doc.description }}</p>
                            </div>
                        </div>

                        <span
                            class="px-2 py-0.5 rounded-md text-[9px] font-black uppercase tracking-wider shrink-0"
                            :class="doc.is_mandatory ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-slate-100 text-slate-600'"
                        >
                            {{ doc.is_mandatory ? 'Requerido' : 'Opcional' }}
                        </span>
                    </div>

                    <!-- Fila de Acciones SÍ / NO -->
                    <div class="flex items-center justify-between pt-3 border-t border-slate-100 gap-2">
                        <span class="text-[11px] font-bold text-slate-500">¿Consignado?</span>
                        <div class="flex items-center gap-1.5 p-1 bg-slate-100 rounded-xl">
                            <button
                                type="button"
                                @click="setDocumentDelivered(doc, true)"
                                class="px-3.5 py-1 rounded-lg text-xs font-black transition-all"
                                :class="doc.delivered ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-500 hover:text-slate-800'"
                            >
                                SÍ
                            </button>
                            <button
                                type="button"
                                @click="setDocumentDelivered(doc, false)"
                                class="px-3.5 py-1 rounded-lg text-xs font-black transition-all"
                                :class="!doc.delivered ? 'bg-slate-700 text-white shadow-xs' : 'text-slate-500 hover:text-slate-800'"
                            >
                                NO
                            </button>
                        </div>
                    </div>

                    <!-- Campos condicionales al marcar SÍ -->
                    <div v-if="doc.delivered" class="mt-3 pt-3 border-t border-emerald-100 space-y-2.5 animate-fade-in">
                        <div class="flex flex-col sm:flex-row gap-2">
                            <div class="w-full sm:w-1/3">
                                <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider mb-1">Fecha entrega</label>
                                <input
                                    type="date"
                                    v-model="doc.delivered_at"
                                    class="w-full text-xs font-bold rounded-xl border-slate-200 focus:border-primary-500 focus:ring-primary-500 bg-white py-1.5 px-2.5"
                                />
                            </div>
                            <div class="w-full sm:w-2/3">
                                <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider mb-1">Observaciones (opcional)</label>
                                <input
                                    type="text"
                                    v-model="doc.notes"
                                    placeholder="Ej: Original cotejado, copia legible"
                                    class="w-full text-xs font-medium rounded-xl border-slate-200 focus:border-primary-500 focus:ring-primary-500 bg-white py-1.5 px-2.5"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bloque 2: Recaudos del Representante -->
        <div class="space-y-4 pt-4 border-t border-slate-100">
            <div class="flex items-center gap-2">
                <i class="fas fa-user-tie text-primary-600 text-sm"></i>
                <h4 class="text-xs font-black text-slate-700 uppercase tracking-widest">Recaudos del Representante Legal</h4>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div
                    v-for="doc in guardianDocs"
                    :key="doc.code"
                    class="border-2 rounded-2xl p-4 sm:p-5 transition-all bg-white"
                    :class="doc.delivered ? 'border-emerald-200 bg-emerald-50/20 shadow-xs' : 'border-slate-200 hover:border-slate-300'"
                >
                    <!-- Cabecera del Documento -->
                    <div class="flex items-start justify-between gap-3 mb-2.5">
                        <div class="flex items-start gap-2.5 min-w-0">
                            <div
                                class="w-8 h-8 rounded-xl flex items-center justify-center text-xs shrink-0 mt-0.5"
                                :class="doc.delivered ? 'bg-emerald-500 text-white' : 'bg-slate-100 text-slate-500'"
                            >
                                <i :class="['fas', doc.icon || 'fa-file']"></i>
                            </div>
                            <div class="min-w-0">
                                <h5 class="text-xs font-black text-slate-800 leading-snug">{{ doc.name }}</h5>
                                <p class="text-[11px] text-slate-400 font-semibold line-clamp-1 mt-0.5">{{ doc.description }}</p>
                            </div>
                        </div>

                        <span
                            class="px-2 py-0.5 rounded-md text-[9px] font-black uppercase tracking-wider shrink-0"
                            :class="doc.is_mandatory ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-slate-100 text-slate-600'"
                        >
                            {{ doc.is_mandatory ? 'Requerido' : 'Opcional' }}
                        </span>
                    </div>

                    <!-- Fila de Acciones SÍ / NO -->
                    <div class="flex items-center justify-between pt-3 border-t border-slate-100 gap-2">
                        <span class="text-[11px] font-bold text-slate-500">¿Consignado?</span>
                        <div class="flex items-center gap-1.5 p-1 bg-slate-100 rounded-xl">
                            <button
                                type="button"
                                @click="setDocumentDelivered(doc, true)"
                                class="px-3.5 py-1 rounded-lg text-xs font-black transition-all"
                                :class="doc.delivered ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-500 hover:text-slate-800'"
                            >
                                SÍ
                            </button>
                            <button
                                type="button"
                                @click="setDocumentDelivered(doc, false)"
                                class="px-3.5 py-1 rounded-lg text-xs font-black transition-all"
                                :class="!doc.delivered ? 'bg-slate-700 text-white shadow-xs' : 'text-slate-500 hover:text-slate-800'"
                            >
                                NO
                            </button>
                        </div>
                    </div>

                    <!-- Campos condicionales al marcar SÍ -->
                    <div v-if="doc.delivered" class="mt-3 pt-3 border-t border-emerald-100 space-y-2.5 animate-fade-in">
                        <div class="flex flex-col sm:flex-row gap-2">
                            <div class="w-full sm:w-1/3">
                                <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider mb-1">Fecha entrega</label>
                                <input
                                    type="date"
                                    v-model="doc.delivered_at"
                                    class="w-full text-xs font-bold rounded-xl border-slate-200 focus:border-primary-500 focus:ring-primary-500 bg-white py-1.5 px-2.5"
                                />
                            </div>
                            <div class="w-full sm:w-2/3">
                                <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider mb-1">Observaciones (opcional)</label>
                                <input
                                    type="text"
                                    v-model="doc.notes"
                                    placeholder="Ej: Pendiente recibo actualizado"
                                    class="w-full text-xs font-medium rounded-xl border-slate-200 focus:border-primary-500 focus:ring-primary-500 bg-white py-1.5 px-2.5"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
