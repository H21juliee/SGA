<template>
    <div class="space-y-4 animate-fade-in-up">
        <!-- Banner principal -->
        <div
            class="p-5 rounded-2xl flex items-start gap-4 shadow-sm"
            :class="result.skipped > 0
                ? 'bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-200'
                : 'bg-gradient-to-r from-emerald-50 to-teal-50 border border-emerald-200'"
        >
            <div
                class="w-10 h-10 rounded-xl text-white flex items-center justify-center flex-shrink-0 shadow-md"
                :class="result.skipped > 0 ? 'bg-amber-500 shadow-amber-500/30' : 'bg-emerald-500 shadow-emerald-500/30'"
            >
                <i :class="result.skipped > 0 ? 'fas fa-exclamation-triangle' : 'fas fa-check'"></i>
            </div>
            <div class="flex-1 min-w-0">
                <p
                    class="font-bold text-sm"
                    :class="result.skipped > 0 ? 'text-amber-800' : 'text-emerald-800'"
                >
                    Importación completada
                </p>
                <div class="flex flex-wrap gap-4 mt-2">
                    <span class="inline-flex items-center gap-1.5 text-sm font-semibold text-emerald-700">
                        <i class="fas fa-check-circle text-emerald-500"></i>
                        {{ result.created }} {{ entityLabel }} registrado(s)
                    </span>
                    <span v-if="result.skipped > 0" class="inline-flex items-center gap-1.5 text-sm font-semibold text-amber-700">
                        <i class="fas fa-exclamation-circle text-amber-500"></i>
                        {{ result.skipped }} fila(s) con error
                    </span>
                </div>
            </div>
        </div>

        <!-- Tabla de errores -->
        <div v-if="result.skippedRows && result.skippedRows.length > 0" class="glass-card rounded-2xl border border-red-100/60 overflow-hidden">
            <div class="px-5 py-4 border-b border-red-100/60 flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg bg-red-100 flex items-center justify-center">
                    <i class="fas fa-times-circle text-red-500 text-xs"></i>
                </div>
                <h3 class="text-sm font-bold text-slate-700">Filas con errores / omitidas</h3>
                <span class="ml-auto text-xs text-slate-400 bg-slate-100 px-2 py-0.5 rounded-full font-medium">
                    {{ result.skippedRows.length }} registro(s)
                </span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="bg-red-50/50 border-b border-red-100/60">
                            <th class="text-left px-4 py-3 font-bold text-slate-600 w-16">Fila</th>
                            <th class="text-left px-4 py-3 font-bold text-slate-600">Valor</th>
                            <th class="text-left px-4 py-3 font-bold text-slate-600">Motivo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-red-50">
                        <tr v-for="(row, i) in result.skippedRows" :key="i" class="hover:bg-red-50/30 transition-colors">
                            <td class="px-4 py-2.5">
                                <span class="inline-flex items-center justify-center w-6 h-6 rounded-md bg-red-100 text-red-600 font-bold">
                                    {{ row.fila }}
                                </span>
                            </td>
                            <td class="px-4 py-2.5 font-medium text-slate-700 max-w-xs truncate">{{ row.valor || '—' }}</td>
                            <td class="px-4 py-2.5 text-red-600">{{ row.motivo }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    result: {
        type: Object,
        required: true,
    },
    entityLabel: {
        type: String,
        default: 'registro(s)',
    },
})
</script>
