<script setup>
import { ref, computed } from 'vue'
import EditableCell from './EditableCell.vue'

const props = defineProps({
    columns: { type: Array, required: true },
    rows: { type: Array, required: true },
    rowKey: { type: String, required: true },
    loading: { type: Boolean, default: false },
    readonly: { type: Boolean, default: false },
})

const emit = defineEmits(['cell-update', 'save-all'])

const activeCell = ref({ row: null, col: null })

// Búsqueda y Ordenamiento
const searchQuery = ref('')
const sortCol = ref(null)
const sortDir = ref('asc')

const processedRows = computed(() => {
    let result = [...props.rows]

    // Filtrar por búsqueda
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase()
        result = result.filter(row => {
            return Object.values(row).some(val => 
                String(val).toLowerCase().includes(query)
            )
        })
    }

    // Ordenar
    if (sortCol.value) {
        result.sort((a, b) => {
            let valA = a[sortCol.value]
            let valB = b[sortCol.value]

            if (typeof valA === 'string') valA = valA.toLowerCase()
            if (typeof valB === 'string') valB = valB.toLowerCase()

            if (valA < valB) return sortDir.value === 'asc' ? -1 : 1
            if (valA > valB) return sortDir.value === 'asc' ? 1 : -1
            return 0
        })
    }

    return result
})

function toggleSort(colKey) {
    if (sortCol.value === colKey) {
        if (sortDir.value === 'asc') {
            sortDir.value = 'desc'
        } else {
            sortCol.value = null
            sortDir.value = 'asc'
        }
    } else {
        sortCol.value = colKey
        sortDir.value = 'asc'
    }
}

function onCellUpdate(rowId, colKey, value) {
    emit('cell-update', {
        rowId: rowId,
        column: colKey,
        value,
    })
}

function navigateCell(direction, currentRow, currentCol) {
    const colKeys = props.columns.filter(c => c.editable).map(c => c.key)
    let rowIdx = currentRow
    let colIdx = colKeys.indexOf(currentCol)

    switch (direction) {
        case 'right':
        case 'tab':
            colIdx++
            if (colIdx >= colKeys.length) { colIdx = 0; rowIdx++ }
            break
        case 'left':
            colIdx--
            if (colIdx < 0) { colIdx = colKeys.length - 1; rowIdx-- }
            break
        case 'down': rowIdx++; break
        case 'up': rowIdx--; break
    }

    rowIdx = Math.max(0, Math.min(rowIdx, processedRows.value.length - 1))
    colIdx = Math.max(0, Math.min(colIdx, colKeys.length - 1))
    activeCell.value = { row: rowIdx, col: colKeys[colIdx] }
}
</script>

<template>
    <div class="datagrid-wrapper">
        <!-- Toolbar -->
        <div class="flex flex-col md:flex-row items-center justify-between gap-4 mb-4 mt-4 mx-2">
            <div class="flex items-center gap-4 w-full md:w-auto">
                <div class="flex items-center gap-2 text-sm text-slate-500 font-medium whitespace-nowrap">
                    <i class="fas fa-table"></i>
                    <span>{{ processedRows.length }} registro{{ processedRows.length !== 1 ? 's' : '' }}</span>
                </div>
                
                <!-- Buscador -->
                <div class="relative w-full md:w-64">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                    <input 
                        v-model="searchQuery" 
                        type="text" 
                        placeholder="Buscar por cédula o nombre..." 
                        class="w-full bg-slate-50 border-2 border-slate-100 rounded-xl pl-9 pr-4 py-2 text-sm text-slate-700 placeholder:text-slate-400 focus:border-primary-400 focus:ring-0 outline-none transition-all shadow-sm"
                    >
                </div>
            </div>
            
            <div class="flex items-center gap-3 self-end md:self-auto">
                <span v-if="loading" class="text-xs font-bold text-amber-500 flex items-center gap-2">
                    <i class="fas fa-spinner fa-spin"></i>
                    Guardando...
                </span>
                <button
                    v-if="!readonly"
                    @click="emit('save-all')"
                    class="px-5 py-2 text-xs font-bold uppercase tracking-widest rounded-xl bg-slate-900 text-white shadow-md hover:bg-slate-800 hover:-translate-y-0.5 active:translate-y-0 transition-all flex items-center gap-2"
                >
                    <i class="fas fa-save"></i> Guardar Todo
                </button>
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto rounded-2xl border-2 border-slate-100 bg-white shadow-sm">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b-2 border-slate-100 bg-slate-50/80">
                        <th class="px-4 py-4 text-left text-xs font-black text-slate-400 uppercase tracking-widest w-8">
                            #
                        </th>
                        <th
                            v-for="col in columns"
                            :key="col.key"
                            class="px-4 py-4 text-left text-xs font-black text-slate-400 uppercase tracking-widest cursor-pointer hover:bg-slate-100/50 hover:text-slate-600 transition-colors select-none group"
                            @click="toggleSort(col.key)"
                        >
                            <div class="flex items-center gap-2">
                                {{ col.label }}
                                <div class="flex flex-col text-[8px] opacity-30 group-hover:opacity-100 transition-opacity"
                                     :class="{ 'opacity-100 text-primary-500': sortCol === col.key }">
                                    <i class="fas fa-chevron-up leading-[0.5]" :class="{ 'text-slate-300': sortCol === col.key && sortDir === 'desc' }"></i>
                                    <i class="fas fa-chevron-down leading-[0.5]" :class="{ 'text-slate-300': sortCol === col.key && sortDir === 'asc' }"></i>
                                </div>
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <tr
                        v-for="(row, rowIdx) in processedRows"
                        :key="row[rowKey]"
                        class="hover:bg-primary-50/30 transition-colors group"
                    >
                        <td class="px-4 py-3 text-xs text-slate-400 font-mono font-medium">
                            {{ rowIdx + 1 }}
                        </td>
                        <td
                            v-for="col in columns"
                            :key="col.key"
                            class="px-2 py-1.5"
                        >
                            <EditableCell
                                v-if="col.editable"
                                :value="row[col.key]"
                                :type="col.type || 'number'"
                                :min="col.min"
                                :max="col.max"
                                :options="col.options || []"
                                :is-active="activeCell.row === rowIdx && activeCell.col === col.key"
                                @update="(val) => onCellUpdate(row[rowKey], col.key, val)"
                                @navigate="(dir) => navigateCell(dir, rowIdx, col.key)"
                                @activate="activeCell = { row: rowIdx, col: col.key }"
                            />
                            <span v-else-if="col.format" class="block px-2 py-1.5" v-html="col.format(row[col.key], row)"></span>
                            <span v-else class="block px-2 py-1.5 text-slate-700 font-medium group-hover:text-slate-900 transition-colors">
                                {{ row[col.key] }}
                            </span>
                        </td>
                    </tr>
                    
                    <tr v-if="processedRows.length === 0">
                        <td :colspan="columns.length + 1" class="px-4 py-8 text-center text-slate-400 font-medium">
                            No se encontraron resultados para la búsqueda.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Footer hint -->
        <div class="mt-4 flex items-center justify-center gap-4 text-xs font-medium text-slate-400">
            <span class="flex items-center gap-1.5"><kbd class="px-2 py-1 rounded bg-slate-50 border-b-2 border-slate-200 text-slate-500 font-mono text-[10px] uppercase tracking-wider shadow-sm">Tab</kbd> Siguiente</span>
            <span class="flex items-center gap-1.5"><kbd class="px-2 py-1 rounded bg-slate-50 border-b-2 border-slate-200 text-slate-500 font-mono text-[10px] uppercase tracking-wider shadow-sm">Enter</kbd> Abajo</span>
            <span class="flex items-center gap-1.5"><kbd class="px-2 py-1 rounded bg-slate-50 border-b-2 border-slate-200 text-slate-500 font-mono text-[10px] uppercase tracking-wider shadow-sm">Esc</kbd> Cancelar</span>
        </div>
    </div>
</template>
