<script setup>
import { ref, computed, nextTick } from 'vue'
import { useForm } from '@inertiajs/vue3'
import NewStudentModal from './NewStudentModal.vue'
import GuardianStep from './GuardianStep.vue'

const props = defineProps({
    activeYear: Object,
    gradeLevels: Array,
    wizardSections: Array,
    availableStudents: Array,
})
const emit = defineEmits(['view-section'])

const step = ref(1) // 1 Alumno, 2 Representante, 3 Sección, 4 Confirmar, 5 Éxito
const search = ref('')
const searchInput = ref(null)
const student = ref(null)
const guardian = ref(null) // representante asignado en esta inscripción
const lastGuardian = ref(null) // se conserva al inscribir hermanos
const gradeId = ref(null)
const section = ref(null)
const showNewStudent = ref(false)
const createdStudents = ref([]) // alumnos creados en esta sesión (aún no vienen del servidor)
const enrolledIds = ref(new Set()) // alumnos inscritos en esta sesión para que no vuelvan a aparecer

const form = useForm({ section_id: '', student_id: '' })

const steps = [
    { n: 1, label: 'Alumno' },
    { n: 2, label: 'Representante' },
    { n: 3, label: 'Sección' },
    { n: 4, label: 'Confirmar' },
]

const allStudents = computed(() => {
    const availableIds = new Set(props.availableStudents.map(s => s.id))
    const list = [
        ...createdStudents.value.filter(s => !availableIds.has(s.id)),
        ...props.availableStudents,
    ]
    return list.filter(s => !enrolledIds.value.has(s.id))
})

const results = computed(() => {
    const q = search.value.trim().toLowerCase()
    const list = q
        ? allStudents.value.filter(s =>
            `${s.first_name} ${s.last_name}`.toLowerCase().includes(q) ||
            (s.cedula && s.cedula.toLowerCase().includes(q)))
        : allStudents.value
    return list.slice(0, 30)
})

const sectionsOfGrade = computed(() =>
    props.wizardSections.filter(s => s.grade_level_id === gradeId.value))

const selectedGrade = computed(() => props.gradeLevels.find(g => g.id === gradeId.value))

function selectStudent(s) {
    student.value = s
    guardian.value = s.guardian ?? null
    step.value = 2
}

// El paso Representante devuelve el alumno actualizado (con su representante) o null si se omite
function onGuardianDone({ student: s, guardian: g }) {
    student.value = s
    guardian.value = g
    if (g) lastGuardian.value = g
    step.value = 3
}

function onStudentCreated(s) {
    createdStudents.value.unshift(s)
    showNewStudent.value = false
    selectStudent(s)
}

function selectGrade(id) {
    gradeId.value = id
    section.value = null
}

function occupancy(sec) {
    return Math.min(100, Math.round((sec.enrolled_count / sec.capacity) * 100))
}

function isFull(sec) {
    return sec.enrolled_count >= sec.capacity
}

function barColor(sec) {
    const p = occupancy(sec)
    if (p >= 100) return 'bg-red-500'
    if (p >= 80) return 'bg-amber-400'
    return 'bg-emerald-500'
}

function chooseSection(sec) {
    if (isFull(sec)) return
    section.value = sec
    step.value = 4
}

function confirm() {
    form.section_id = section.value.id
    form.student_id = student.value.id
    const enrolledStudentId = student.value.id
    form.post('/admin/enrollments', {
        preserveScroll: true,
        onSuccess: () => {
            enrolledIds.value.add(enrolledStudentId)
            createdStudents.value = createdStudents.value.filter(s => s.id !== enrolledStudentId)
            step.value = 5
        },
    })
}

// keepGuardian = true: flujo de hermanos, se sugiere el mismo representante en el siguiente alumno
function restart(keepGuardian = false) {
    student.value = null
    guardian.value = null
    if (!keepGuardian) lastGuardian.value = null
    section.value = null
    gradeId.value = null
    search.value = ''
    step.value = 1
    nextTick(() => searchInput.value?.focus())
}

function pickFirst() {
    if (results.value.length) selectStudent(results.value[0])
}
</script>

<template>
    <div class="max-w-4xl mx-auto space-y-6">
        <!-- Indicador de pasos -->
        <div v-if="step < 5" class="flex items-center justify-center gap-2 sm:gap-4">
            <template v-for="(s, i) in steps" :key="s.n">
                <div class="flex items-center gap-2">
                    <div class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-black border-2 transition-all"
                        :class="step > s.n ? 'bg-emerald-500 border-emerald-500 text-white'
                            : step === s.n ? 'bg-primary-600 border-primary-600 text-white shadow-lg shadow-primary-600/30'
                            : 'bg-white border-slate-200 text-slate-300'">
                        <i v-if="step > s.n" class="fas fa-check text-xs"></i>
                        <span v-else>{{ s.n }}</span>
                    </div>
                    <span class="text-xs font-black uppercase tracking-widest hidden sm:block"
                        :class="step === s.n ? 'text-slate-800' : 'text-slate-300'">{{ s.label }}</span>
                </div>
                <div v-if="i < steps.length - 1" class="w-8 sm:w-16 h-0.5 rounded-full" :class="step > s.n ? 'bg-emerald-400' : 'bg-slate-200'"></div>
            </template>
        </div>

        <!-- PASO 1: Alumno -->
        <div v-if="step === 1" class="glass-card rounded-3xl p-6 sm:p-8 shadow-xl space-y-5 animate-fade-in-up">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h3 class="text-lg font-black text-slate-800">¿Quién es el alumno?</h3>
                    <p class="text-xs font-medium text-slate-400">Busca por nombre o cédula. Solo aparecen alumnos sin inscripción en el año activo.</p>
                </div>
                <button v-if="$can('students.create')" @click="showNewStudent = true"
                    class="px-5 py-2.5 bg-primary-600 text-white text-[11px] font-black uppercase tracking-widest rounded-2xl shadow-lg hover:bg-primary-700 hover:-translate-y-0.5 transition-all shrink-0">
                    <i class="fas fa-plus mr-1.5"></i> Registrar alumno nuevo
                </button>
            </div>

            <div class="relative">
                <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                <input ref="searchInput" v-model="search" @keydown.enter.prevent="pickFirst" type="text" autofocus
                    placeholder="Buscar por nombre o cédula..."
                    class="w-full pl-11 pr-4 py-3.5 bg-white border-2 border-slate-100 rounded-2xl text-slate-700 text-sm font-bold focus:border-primary-400 focus:ring-0 outline-none transition-all shadow-sm">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-[420px] overflow-y-auto custom-scrollbar pr-1">
                <button v-for="s in results" :key="s.id" @click="selectStudent(s)"
                    class="text-left p-4 bg-white border-2 border-slate-100 rounded-2xl hover:border-primary-300 hover:shadow-md transition-all flex items-center gap-3 group">
                    <div class="w-10 h-10 rounded-full bg-slate-50 text-slate-400 flex items-center justify-center text-xs font-black shrink-0 border border-slate-200 group-hover:bg-primary-50 group-hover:text-primary-600 transition-colors">
                        {{ s.last_name?.charAt(0) }}
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-black text-slate-700 truncate">{{ s.last_name }}, {{ s.first_name }}</p>
                        <p class="text-[11px] font-bold text-slate-400"><i class="fas fa-id-card opacity-50 mr-1"></i>{{ s.cedula || 'Sin cédula' }}</p>
                    </div>
                    <i class="fas fa-chevron-right ml-auto text-slate-200 group-hover:text-primary-400 transition-colors"></i>
                </button>
                <div v-if="results.length === 0" class="sm:col-span-2 text-center py-12">
                    <div class="w-12 h-12 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-3 text-slate-200"><i class="fas fa-user-slash text-xl"></i></div>
                    <p class="text-sm font-bold text-slate-400">No se encontró ningún alumno disponible.</p>
                    <p v-if="$can('students.create')" class="text-xs text-slate-300 mt-1">Usa "Registrar alumno nuevo" si aún no existe.</p>
                </div>
            </div>
            <p v-if="allStudents.length > 30 && !search" class="text-[11px] text-slate-400 font-bold text-center">Mostrando 30 de {{ allStudents.length }}. Escribe para filtrar.</p>
        </div>

        <!-- PASO 2: Representante -->
        <GuardianStep v-if="step === 2" :student="student" :suggested="lastGuardian"
            @done="onGuardianDone" @back="step = 1" />

        <!-- PASO 3: Nivel y sección -->
        <div v-if="step === 3" class="glass-card rounded-3xl p-6 sm:p-8 shadow-xl space-y-6 animate-fade-in-up">
            <div class="flex items-center justify-between gap-3 bg-primary-50/60 border border-primary-100 rounded-2xl p-4">
                <div class="min-w-0">
                    <p class="text-[10px] font-black uppercase tracking-widest text-primary-400">Alumno seleccionado</p>
                    <p class="text-sm font-black text-slate-800 truncate">{{ student.last_name }}, {{ student.first_name }}</p>
                </div>
                <button @click="step = 1" class="text-xs font-black text-primary-600 hover:underline shrink-0">Cambiar</button>
            </div>

            <div>
                <h3 class="text-lg font-black text-slate-800 mb-3">¿En qué nivel?</h3>
                <div class="flex flex-wrap gap-2">
                    <button v-for="g in gradeLevels" :key="g.id" @click="selectGrade(g.id)"
                        class="px-4 py-2.5 rounded-xl text-sm font-black border-2 transition-all"
                        :class="gradeId === g.id ? 'bg-primary-600 border-primary-600 text-white shadow-lg shadow-primary-600/30' : 'bg-white border-slate-100 text-slate-600 hover:border-primary-300'">
                        {{ g.name }}
                    </button>
                </div>
            </div>

            <div v-if="gradeId">
                <h3 class="text-lg font-black text-slate-800 mb-3">¿En qué sección?</h3>
                <div v-if="sectionsOfGrade.length === 0" class="p-6 text-center text-sm font-bold text-slate-400 bg-slate-50 rounded-2xl">
                    Este nivel no tiene secciones en el año activo.
                </div>
                <div v-else class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <button v-for="sec in sectionsOfGrade" :key="sec.id" @click="chooseSection(sec)" :disabled="isFull(sec)"
                        class="text-left p-4 bg-white border-2 rounded-2xl transition-all"
                        :class="isFull(sec) ? 'border-slate-100 opacity-60 cursor-not-allowed' : 'border-slate-100 hover:border-primary-300 hover:shadow-md'">
                        <div class="flex items-center justify-between">
                            <p class="text-base font-black text-slate-800">Sección {{ sec.name }}</p>
                            <i v-if="isFull(sec)" class="fas fa-lock text-red-400 text-xs"></i>
                        </div>
                        <p class="text-xs font-bold text-slate-400 mt-1">{{ sec.enrolled_count }} / {{ sec.capacity }} cupos</p>
                        <div class="h-2 bg-slate-100 rounded-full mt-3 overflow-hidden">
                            <div class="h-full rounded-full transition-all" :class="barColor(sec)" :style="{ width: occupancy(sec) + '%' }"></div>
                        </div>
                        <p v-if="isFull(sec)" class="text-[10px] font-black uppercase tracking-widest text-red-500 mt-2">Llena</p>
                    </button>
                </div>
            </div>

            <div class="flex justify-start">
                <button @click="step = 2" class="px-6 py-3 text-sm font-black uppercase tracking-widest text-slate-400 hover:text-slate-600 transition-all">Atrás</button>
            </div>
        </div>

        <!-- PASO 4: Confirmar -->
        <div v-if="step === 4" class="glass-card rounded-3xl p-6 sm:p-8 shadow-xl space-y-6 animate-fade-in-up">
            <h3 class="text-lg font-black text-slate-800">Confirma la inscripción</h3>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="p-4 bg-white border-2 border-slate-100 rounded-2xl">
                    <dt class="text-[10px] font-black uppercase tracking-widest text-slate-400">Alumno</dt>
                    <dd class="text-sm font-black text-slate-800 mt-1">{{ student.last_name }}, {{ student.first_name }}</dd>
                    <dd class="text-xs font-bold text-slate-400">{{ student.cedula || 'Sin cédula' }}</dd>
                </div>
                <div class="p-4 bg-white border-2 border-slate-100 rounded-2xl">
                    <dt class="text-[10px] font-black uppercase tracking-widest text-slate-400">Representante</dt>
                    <dd v-if="guardian" class="text-sm font-black text-slate-800 mt-1">{{ guardian.name }}</dd>
                    <dd v-if="guardian" class="text-xs font-bold text-slate-400">{{ guardian.cedula }}</dd>
                    <dd v-else class="text-sm font-black text-amber-600 mt-1"><i class="fas fa-exclamation-triangle mr-1"></i>Sin representante</dd>
                </div>
                <div class="p-4 bg-white border-2 border-slate-100 rounded-2xl">
                    <dt class="text-[10px] font-black uppercase tracking-widest text-slate-400">Destino</dt>
                    <dd class="text-sm font-black text-slate-800 mt-1">{{ selectedGrade?.name }} — Sección {{ section.name }}</dd>
                    <dd class="text-xs font-bold text-slate-400">{{ section.enrolled_count }} / {{ section.capacity }} cupos ocupados</dd>
                </div>
                <div class="p-4 bg-white border-2 border-slate-100 rounded-2xl sm:col-span-2">
                    <dt class="text-[10px] font-black uppercase tracking-widest text-slate-400">Año escolar</dt>
                    <dd class="text-sm font-black text-slate-800 mt-1">{{ activeYear?.name }}</dd>
                </div>
            </dl>
            <div class="flex items-center justify-between gap-4">
                <button @click="step = 3" class="px-6 py-3 text-sm font-black uppercase tracking-widest text-slate-400 hover:text-slate-600 transition-all">Atrás</button>
                <button @click="confirm" :disabled="form.processing"
                    v-if="$can('enrollments.manage') || $can('enrollments.create')"
                    class="px-8 py-3 bg-emerald-500 text-white text-[11px] font-black uppercase tracking-widest rounded-2xl shadow-lg shadow-emerald-500/30 hover:bg-emerald-600 hover:-translate-y-0.5 transition-all disabled:opacity-50">
                    <i class="fas fa-check mr-1.5"></i> Inscribir
                </button>
            </div>
        </div>

        <!-- ÉXITO -->
        <div v-if="step === 5" class="glass-card rounded-3xl p-10 shadow-xl text-center space-y-5 animate-fade-in-up">
            <div class="w-16 h-16 bg-emerald-50 rounded-full flex items-center justify-center mx-auto text-emerald-500"><i class="fas fa-check text-2xl"></i></div>
            <div>
                <h3 class="text-xl font-black text-slate-800">¡Inscripción registrada!</h3>
                <p class="text-sm text-slate-400 mt-1">{{ student.last_name }}, {{ student.first_name }} → {{ selectedGrade?.name }} · Sección {{ section.name }}</p>
            </div>
            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <!-- <button v-if="guardian" @click="restart(true)" class="px-6 py-3 bg-emerald-500 text-white text-[11px] font-black uppercase tracking-widest rounded-2xl shadow-lg hover:bg-emerald-600 transition-all">Inscribir otro hijo de {{ guardian.name }}</button> -->
                <button @click="restart()" class="px-6 py-3 bg-primary-600 text-white text-[11px] font-black uppercase tracking-widest rounded-2xl shadow-lg hover:bg-primary-700 transition-all">Inscribir otro alumno</button>
                <button @click="emit('view-section', { grade_level_id: gradeId, section_id: section.id })" class="px-6 py-3 bg-white border-2 border-slate-100 text-slate-600 text-[11px] font-black uppercase tracking-widest rounded-2xl hover:border-slate-300 transition-all">Ver sección</button>
            </div>
        </div>

        <NewStudentModal :show="showNewStudent" @close="showNewStudent = false" @created="onStudentCreated" />
    </div>
</template>
