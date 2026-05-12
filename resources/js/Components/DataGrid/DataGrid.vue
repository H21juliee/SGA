<script setup>
import { ref } from 'vue'
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

function onCellUpdate(rowIndex, colKey, value) {
    emit('cell-update', {
        rowId: props.rows[rowIndex][props.rowKey],
        column: colKey,
        value,
        rowIndex,
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

    rowIdx = Math.max(0, Math.min(rowIdx, props.rows.length - 1))
    colIdx = Math.max(0, Math.min(colIdx, colKeys.length - 1))
    activeCell.value = { row: rowIdx, col: colKeys[colIdx] }
}
</script>

<template>
    <div class="datagrid-wrapper">
        <!-- Toolbar -->
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2 text-sm text-slate-500">
                <i class="fas fa-table"></i>
                <span>{{ rows.length }} registro{{ rows.length !== 1 ? 's' : '' }}</span>
            </div>
            <div class="flex items-center gap-2">
                <span v-if="loading" class="text-xs text-amber-500 flex items-center gap-1">
                    <i class="fas fa-spinner fa-spin"></i>
                    Guardando...
                </span>
                <button
                    v-if="!readonly"
                    @click="emit('save-all')"
                    class="px-4 py-2 text-xs font-semibold uppercase tracking-wide rounded-md bg-slate-900 text-white shadow-sm hover:bg-slate-800 transition-all flex items-center gap-2"
                >
                    <i class="fas fa-save"></i> Guardar Todo
                </button>
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50">
                        <th class="px-4 py-3 text-left text-xs font-bold text-slate-400 uppercase w-8">
                            #
                        </th>
                        <th
                            v-for="col in columns"
                            :key="col.key"
                            class="px-4 py-3 text-left text-xs font-bold text-slate-400 uppercase"
                        >
                            {{ col.label }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr
                        v-for="(row, rowIdx) in rows"
                        :key="row[rowKey]"
                        class="hover:bg-slate-50 transition-colors"
                    >
                        <td class="px-4 py-2 text-xs text-slate-400 font-mono">
                            {{ rowIdx + 1 }}
                        </td>
                        <td
                            v-for="col in columns"
                            :key="col.key"
                            class="px-2 py-1"
                        >
                            <EditableCell
                                v-if="col.editable"
                                :value="row[col.key]"
                                :type="col.type || 'number'"
                                :min="col.min"
                                :max="col.max"
                                :options="col.options || []"
                                :is-active="activeCell.row === rowIdx && activeCell.col === col.key"
                                @update="(val) => onCellUpdate(rowIdx, col.key, val)"
                                @navigate="(dir) => navigateCell(dir, rowIdx, col.key)"
                                @activate="activeCell = { row: rowIdx, col: col.key }"
                            />
                            <span v-else class="block px-2 py-1 text-slate-700">
                                {{ row[col.key] }}
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Footer hint -->
        <p class="mt-3 text-xs text-slate-400 text-center">
            <kbd class="px-1.5 py-0.5 rounded border border-slate-200 bg-slate-50 text-slate-500 font-mono">Tab</kbd> siguiente ·
            <kbd class="px-1.5 py-0.5 rounded border border-slate-200 bg-slate-50 text-slate-500 font-mono">Enter</kbd> abajo ·
            <kbd class="px-1.5 py-0.5 rounded border border-slate-200 bg-slate-50 text-slate-500 font-mono">Esc</kbd> cancelar
        </p>
    </div>
</template>
