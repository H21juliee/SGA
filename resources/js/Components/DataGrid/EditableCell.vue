<script setup>
import { ref, computed, nextTick } from 'vue'

const props = defineProps({
    value: [String, Number],
    type: { type: String, default: 'number' },
    min: { type: Number, default: undefined },
    max: { type: Number, default: undefined },
    options: { type: Array, default: () => [] },
    isActive: { type: Boolean, default: false },
})

const emit = defineEmits(['update', 'navigate', 'activate'])

const editing = ref(false)
const localValue = ref(props.value)
const inputRef = ref(null)
const saveStatus = ref('idle')

const isValid = computed(() => {
    if (props.type === 'number' && props.min != null && props.max != null) {
        const num = Number(localValue.value)
        return !isNaN(num) && num >= props.min && num <= props.max
    }
    return true
})

function startEdit() {
    editing.value = true
    localValue.value = props.value
    nextTick(() => {
        if (inputRef.value) {
            inputRef.value.focus()
            if (props.type === 'number') inputRef.value.select()
        }
    })
}

function commitEdit() {
    editing.value = false
    if (localValue.value !== props.value && isValid.value) {
        saveStatus.value = 'saving'
        emit('update', props.type === 'number' ? Number(localValue.value) : localValue.value)
        setTimeout(() => { saveStatus.value = 'saved' }, 500)
        setTimeout(() => { saveStatus.value = 'idle' }, 2500)
    }
}

function onKeydown(e) {
    switch (e.key) {
        case 'Tab':
            e.preventDefault()
            commitEdit()
            emit('navigate', e.shiftKey ? 'left' : 'right')
            break
        case 'Enter':
            e.preventDefault()
            commitEdit()
            emit('navigate', 'down')
            break
        case 'Escape':
            localValue.value = props.value
            editing.value = false
            break
    }
}
</script>

<template>
    <div
        class="editable-cell relative group cursor-pointer min-h-[40px] flex items-center justify-center transition-all"
        @click="emit('activate'); startEdit()"
    >
        <!-- Edit Mode: Number -->
        <input
            v-if="editing && type === 'number'"
            ref="inputRef"
            v-model="localValue"
            type="number"
            :min="min"
            :max="max"
            step="0.1"
            class="w-full h-9 px-2 bg-white border-2 rounded-lg text-center text-sm font-bold text-slate-800 focus:outline-none transition-all shadow-sm"
            :class="{
                'border-primary-400 focus:ring-4 focus:ring-primary-400/10': isValid,
                'border-red-400 focus:ring-4 focus:ring-red-400/10': !isValid,
            }"
            @blur="commitEdit"
            @keydown="onKeydown"
        />

        <!-- Edit Mode: Select -->
        <select
            v-else-if="editing && type === 'select'"
            ref="inputRef"
            v-model="localValue"
            class="w-full h-9 px-2 bg-white border-2 border-primary-400 rounded-lg text-sm font-bold text-slate-800 focus:outline-none focus:ring-4 focus:ring-primary-400/10 transition-all shadow-sm"
            @blur="commitEdit"
            @change="commitEdit"
            @keydown="onKeydown"
        >
            <option v-for="opt in options" :key="opt" :value="opt">
                {{ opt.charAt(0).toUpperCase() + opt.slice(1) }}
            </option>
        </select>

        <!-- Edit Mode: Text -->
        <input
            v-else-if="editing && type === 'text'"
            ref="inputRef"
            v-model="localValue"
            type="text"
            class="w-full h-9 px-2 bg-white border-2 border-primary-400 rounded-lg text-sm font-bold text-slate-800 focus:outline-none focus:ring-4 focus:ring-primary-400/10 transition-all shadow-sm"
            @blur="commitEdit"
            @keydown="onKeydown"
        />

        <!-- Display Mode -->
        <span
            v-else
            class="w-full block px-2 py-2 text-center text-sm font-bold rounded-xl transition-all"
            :class="[
                value != null ? 'text-slate-700' : 'text-slate-300',
                isActive ? 'bg-primary-50 text-primary-700 ring-2 ring-primary-100' : 'group-hover:bg-slate-50'
            ]"
        >
            {{ value ?? '—' }}
        </span>

        <!-- Save Status Indicators -->
        <div v-if="saveStatus !== 'idle'" class="absolute -top-1 -right-1 z-10">
            <div
                class="w-3 h-3 rounded-full border-2 border-white shadow-sm"
                :class="saveStatus === 'saving' ? 'bg-amber-400 animate-pulse' : 'bg-emerald-500'"
            ></div>
        </div>
    </div>
</template>
