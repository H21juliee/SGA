<template>
    <AppLayout title="Importar Estudiantes">
        <div class="max-w-4xl mx-auto space-y-8">

            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-3 mb-1">
                        <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-violet-500 to-primary-600 flex items-center justify-center shadow-lg shadow-violet-500/30">
                            <i class="fas fa-user-graduate text-white text-sm"></i>
                        </div>
                        <h1 class="text-2xl font-extrabold text-slate-800">Importar Estudiantes</h1>
                    </div>
                    <p class="text-sm text-slate-500 ml-13">Carga masiva desde archivo Excel (.xlsx) o CSV</p>
                </div>
                <a
                    href="/templates/students_import_template.xlsx"
                    download
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-50 text-emerald-700 font-semibold text-sm border border-emerald-200 hover:bg-emerald-100 transition-all duration-200 hover:shadow-md hover:shadow-emerald-100"
                >
                    <i class="fas fa-download text-xs"></i>
                    Descargar Plantilla
                </a>
            </div>

            <!-- Resultado de importación -->
            <ImportResultSummary
                v-if="result"
                :result="result"
                entity-label="estudiante(s)"
            />

            <!-- Instrucciones -->
            <div class="glass-card rounded-2xl p-6 border border-primary-100/50">
                <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wider mb-4 flex items-center gap-2">
                    <i class="fas fa-info-circle text-primary-400"></i>
                    Formato del archivo
                </h2>
                <div class="overflow-x-auto rounded-xl border border-slate-100">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-100">
                                <th class="text-left px-4 py-3 font-bold text-slate-600">Columna</th>
                                <th class="text-left px-4 py-3 font-bold text-slate-600">Requerido</th>
                                <th class="text-left px-4 py-3 font-bold text-slate-600">Ejemplo / Notas</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <tr v-for="col in columns" :key="col.name" class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-4 py-3">
                                    <code class="bg-primary-50 text-primary-700 text-xs font-mono px-2 py-0.5 rounded-lg">{{ col.name }}</code>
                                </td>
                                <td class="px-4 py-3">
                                    <span v-if="col.required" class="inline-flex items-center gap-1 text-emerald-600 font-semibold text-xs">
                                        <i class="fas fa-check-circle"></i> Sí
                                    </span>
                                    <span v-else class="text-slate-400 text-xs">Opcional</span>
                                </td>
                                <td class="px-4 py-3 text-slate-500 text-xs">{{ col.note }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p class="mt-3 text-xs text-slate-400 flex items-center gap-1.5">
                    <i class="fas fa-lightbulb text-amber-400"></i>
                    Si una cédula ya existe en el sistema, esa fila será registrada como error en el resumen.
                    El campo <code class="bg-slate-100 px-1 rounded text-slate-600">status</code> se asigna como <em>Regular</em> en todos los casos.
                </p>
            </div>

            <!-- Upload Form -->
            <div class="glass-card rounded-2xl p-6 border border-violet-100/50">
                <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wider mb-5 flex items-center gap-2">
                    <i class="fas fa-upload text-violet-400"></i>
                    Subir archivo
                </h2>

                <form @submit.prevent="submit" enctype="multipart/form-data">
                    <!-- Drop Zone -->
                    <div
                        class="relative rounded-2xl border-2 border-dashed transition-all duration-300 p-10 text-center cursor-pointer"
                        :class="[
                            isDragging
                                ? 'border-violet-400 bg-violet-50 scale-[1.01]'
                                : selectedFile
                                    ? 'border-emerald-400 bg-emerald-50'
                                    : 'border-slate-200 bg-slate-50 hover:border-violet-300 hover:bg-violet-50/40'
                        ]"
                        @dragover.prevent="isDragging = true"
                        @dragleave.prevent="isDragging = false"
                        @drop.prevent="onDrop"
                        @click="$refs.fileInput.click()"
                    >
                        <input
                            ref="fileInput"
                            type="file"
                            accept=".xlsx,.xls,.csv"
                            class="hidden"
                            @change="onFileChange"
                        />

                        <!-- No file selected -->
                        <template v-if="!selectedFile">
                            <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-violet-100 to-primary-100 flex items-center justify-center mx-auto mb-4 transition-transform duration-300" :class="isDragging ? 'scale-110' : ''">
                                <i class="fas fa-cloud-upload-alt text-violet-500 text-2xl"></i>
                            </div>
                            <p class="text-slate-700 font-semibold mb-1">Arrastra tu archivo aquí</p>
                            <p class="text-slate-400 text-sm">o <span class="text-violet-600 font-semibold underline underline-offset-2">haz clic para seleccionar</span></p>
                            <p class="text-slate-300 text-xs mt-3">Formatos aceptados: .xlsx · .xls · .csv — Máx. 10 MB</p>
                        </template>

                        <!-- File selected -->
                        <template v-else>
                            <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-emerald-100 to-teal-100 flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-file-excel text-emerald-600 text-2xl"></i>
                            </div>
                            <p class="text-emerald-700 font-bold mb-1">{{ selectedFile.name }}</p>
                            <p class="text-slate-400 text-xs">{{ formatSize(selectedFile.size) }} · Haz clic para cambiar el archivo</p>
                        </template>
                    </div>

                    <!-- Error -->
                    <p v-if="form.errors.file" class="mt-3 text-sm text-red-500 flex items-center gap-1.5">
                        <i class="fas fa-exclamation-circle"></i>
                        {{ form.errors.file }}
                    </p>

                    <!-- Processing notice -->
                    <div class="mt-5 p-4 rounded-xl bg-blue-50 border border-blue-200 flex items-start gap-3">
                        <i class="fas fa-info-circle text-blue-500 mt-0.5 flex-shrink-0"></i>
                        <div class="text-xs text-blue-700">
                            <span class="font-bold">Procesamiento en tiempo real:</span>
                            Al hacer clic en importar, el sistema procesará el archivo y te mostrará
                            un resumen con la cantidad de estudiantes registrados y las filas con errores.
                        </div>
                    </div>

                    <!-- Submit -->
                    <div class="mt-6 flex items-center justify-between gap-4">
                        <Link
                            href="/admin/students"
                            class="text-sm text-slate-400 hover:text-slate-600 font-medium transition-colors flex items-center gap-1.5"
                        >
                            <i class="fas fa-arrow-left text-xs"></i>
                            Volver al directorio
                        </Link>
                        <button
                            type="submit"
                            :disabled="!selectedFile || form.processing"
                            class="inline-flex items-center gap-2 px-6 py-3 rounded-xl font-bold text-sm text-white bg-gradient-to-r from-violet-600 to-primary-600 shadow-lg shadow-violet-500/30 hover:shadow-xl hover:shadow-violet-500/40 hover:-translate-y-0.5 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:translate-y-0 disabled:hover:shadow-lg"
                        >
                            <i v-if="form.processing" class="fas fa-spinner fa-spin"></i>
                            <i v-else class="fas fa-file-import"></i>
                            {{ form.processing ? 'Enviando...' : 'Importar Estudiantes' }}
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Components/Layout/AppLayout.vue'
import ImportResultSummary from '@/Components/Import/ImportResultSummary.vue'
import { Link, useForm, usePage } from '@inertiajs/vue3'
import { ref, computed } from 'vue'

const page = usePage()
const result = computed(() => page.props.flash?.import_result ?? null)

const isDragging  = ref(false)
const selectedFile = ref(null)
const fileInput   = ref(null)

const form = useForm({ file: null })

function onFileChange(e) {
    const file = e.target.files[0]
    if (file) setFile(file)
}

function onDrop(e) {
    isDragging.value = false
    const file = e.dataTransfer.files[0]
    if (file) setFile(file)
}

function setFile(file) {
    selectedFile.value = file
    form.file = file
}

function formatSize(bytes) {
    if (bytes < 1024) return bytes + ' B'
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB'
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB'
}

function submit() {
    form.post('/import/students', {
        forceFormData: true,
        onSuccess: () => {
            selectedFile.value = null
            form.reset()
            if (fileInput.value) fileInput.value.value = ''
        },
    })
}

const columns = [
    { name: 'nombres',          required: true,  note: 'Nombres completos del estudiante. Ej: María Alejandra' },
    { name: 'apellidos',        required: true,  note: 'Apellidos completos. Ej: González Pérez' },
    { name: 'cedula_escolar',   required: false, note: 'Formato: V-12345678 o E-12345678. Si está vacía se omite.' },
    { name: 'fecha_nacimiento', required: true,  note: 'Formatos aceptados: DD/MM/YYYY o YYYY-MM-DD' },
    { name: 'genero',           required: true,  note: 'M para Masculino, F para Femenino' },
]
</script>
