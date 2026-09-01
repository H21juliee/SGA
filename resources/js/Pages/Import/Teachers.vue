<template>
    <AppLayout title="Importar Docentes">
        <div class="max-w-4xl mx-auto space-y-8">

            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-3 mb-1">
                        <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center shadow-lg shadow-amber-500/30">
                            <i class="fas fa-chalkboard-teacher text-white text-sm"></i>
                        </div>
                        <h1 class="text-2xl font-extrabold text-slate-800">Importar Docentes</h1>
                    </div>
                    <p class="text-sm text-slate-500 ml-13">Carga masiva desde archivo Excel (.xlsx) o CSV</p>
                </div>
                <a
                    href="/templates/teachers_import_template.xlsx"
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
                entity-label="docente(s)"
            />

            <!-- Instrucciones -->
            <div class="glass-card rounded-2xl p-6 border border-amber-100/50">
                <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wider mb-4 flex items-center gap-2">
                    <i class="fas fa-info-circle text-amber-400"></i>
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
                                    <code class="bg-amber-50 text-amber-700 text-xs font-mono px-2 py-0.5 rounded-lg">{{ col.name }}</code>
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
                <div class="mt-3 p-3 rounded-xl bg-amber-50 border border-amber-100 space-y-1">
                    <p class="text-xs text-amber-700 flex items-center gap-1.5 font-semibold">
                        <i class="fas fa-key text-amber-500"></i>
                        Contraseña inicial
                    </p>
                    <p class="text-xs text-amber-600">
                        La contraseña inicial de cada docente será su cédula (ej: <code class="bg-amber-100 px-1 rounded">V-12345678</code>). Deberán cambiarla al primer inicio de sesión si el sistema de preguntas de seguridad está activo.
                    </p>
                </div>
            </div>

            <!-- Upload Form -->
            <div class="glass-card rounded-2xl p-6 border border-amber-100/50">
                <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wider mb-5 flex items-center gap-2">
                    <i class="fas fa-upload text-amber-400"></i>
                    Subir archivo
                </h2>

                <form @submit.prevent="submit" enctype="multipart/form-data">
                    <!-- Drop Zone -->
                    <div
                        class="relative rounded-2xl border-2 border-dashed transition-all duration-300 p-10 text-center cursor-pointer"
                        :class="[
                            isDragging
                                ? 'border-amber-400 bg-amber-50 scale-[1.01]'
                                : selectedFile
                                    ? 'border-emerald-400 bg-emerald-50'
                                    : 'border-slate-200 bg-slate-50 hover:border-amber-300 hover:bg-amber-50/40'
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
                        <template v-if="!selectedFile">
                            <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-amber-100 to-orange-100 flex items-center justify-center mx-auto mb-4 transition-transform duration-300" :class="isDragging ? 'scale-110' : ''">
                                <i class="fas fa-cloud-upload-alt text-amber-500 text-2xl"></i>
                            </div>
                            <p class="text-slate-700 font-semibold mb-1">Arrastra tu archivo aquí</p>
                            <p class="text-slate-400 text-sm">o <span class="text-amber-600 font-semibold underline underline-offset-2">haz clic para seleccionar</span></p>
                            <p class="text-slate-300 text-xs mt-3">Formatos aceptados: .xlsx · .xls · .csv — Máx. 10 MB</p>
                        </template>
                        <template v-else>
                            <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-emerald-100 to-teal-100 flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-file-excel text-emerald-600 text-2xl"></i>
                            </div>
                            <p class="text-emerald-700 font-bold mb-1">{{ selectedFile.name }}</p>
                            <p class="text-slate-400 text-xs">{{ formatSize(selectedFile.size) }} · Haz clic para cambiar el archivo</p>
                        </template>
                    </div>

                    <p v-if="form.errors.file" class="mt-3 text-sm text-red-500 flex items-center gap-1.5">
                        <i class="fas fa-exclamation-circle"></i>
                        {{ form.errors.file }}
                    </p>

                    <div class="mt-5 p-4 rounded-xl bg-blue-50 border border-blue-200 flex items-start gap-3">
                        <i class="fas fa-info-circle text-blue-500 mt-0.5 flex-shrink-0"></i>
                        <div class="text-xs text-blue-700">
                            <span class="font-bold">Procesamiento en tiempo real:</span>
                            Al importar, el sistema creará los usuarios con rol <strong>Docente</strong> y les asignará su cédula como contraseña temporal.
                        </div>
                    </div>

                    <div class="mt-6 flex items-center justify-between gap-4">
                        <Link
                            href="/admin/users"
                            class="text-sm text-slate-400 hover:text-slate-600 font-medium transition-colors flex items-center gap-1.5"
                        >
                            <i class="fas fa-arrow-left text-xs"></i>
                            Volver a usuarios
                        </Link>
                        <button
                            type="submit"
                            :disabled="!selectedFile || form.processing"
                            class="inline-flex items-center gap-2 px-6 py-3 rounded-xl font-bold text-sm text-white bg-gradient-to-r from-amber-600 to-orange-600 shadow-lg shadow-amber-500/30 hover:shadow-xl hover:shadow-amber-500/40 hover:-translate-y-0.5 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:translate-y-0 disabled:hover:shadow-lg"
                        >
                            <i v-if="form.processing" class="fas fa-spinner fa-spin"></i>
                            <i v-else class="fas fa-file-import"></i>
                            {{ form.processing ? 'Enviando...' : 'Importar Docentes' }}
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

const isDragging   = ref(false)
const selectedFile = ref(null)
const fileInput    = ref(null)
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
    form.post('/import/teachers', {
        forceFormData: true,
        onSuccess: () => {
            selectedFile.value = null
            form.reset()
            if (fileInput.value) fileInput.value.value = ''
        },
    })
}

const columns = [
    { name: 'nombre',   required: true,  note: 'Nombre completo del docente. Ej: Carlos Ramírez' },
    { name: 'email',    required: true,  note: 'Correo único. Ej: cramírez@escuela.edu.ve' },
    { name: 'cedula',   required: true,  note: 'Formato: V-12345678 o E-12345678. Se usará como contraseña inicial.' },
    { name: 'telefono', required: false, note: 'Opcional. Ej: 0414-1234567' },
]
</script>
