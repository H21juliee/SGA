<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'

const props = defineProps({
    modelValue: {
        type: [String, Number],
        default: ''
    },
    options: {
        type: Array,
        required: true
    },
    placeholder: {
        type: String,
        default: 'Seleccionar...'
    },
    icon: {
        type: String,
        default: ''
    },
    required: {
        type: Boolean,
        default: false
    },
    disabled: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['update:modelValue'])

const isOpen = ref(false)
const searchQuery = ref('')
const containerRef = ref(null)

const selectedOption = computed(() => {
    return props.options.find(opt => opt.value === props.modelValue)
})

watch(() => props.modelValue, (newVal) => {
    if (!newVal) {
        searchQuery.value = ''
    } else {
        const option = props.options.find(opt => opt.value === newVal)
        if (option) {
            searchQuery.value = option.label
        }
    }
}, { immediate: true })

const filteredOptions = computed(() => {
    if (!searchQuery.value) return props.options
    
    if (selectedOption.value && searchQuery.value === selectedOption.value.label) {
        return props.options
    }

    const query = searchQuery.value.toLowerCase()
    return props.options.filter(opt => opt.label.toLowerCase().includes(query))
})

const selectOption = (option) => {
    searchQuery.value = option.label
    emit('update:modelValue', option.value)
    isOpen.value = false
}

const openDropdown = () => {
    isOpen.value = true
    if (selectedOption.value) {
        searchQuery.value = ''
    }
}

const handleInput = () => {
    isOpen.value = true
    // REMOVED: Do not emit empty just because user is typing
}

const handleClickOutside = (event) => {
    if (containerRef.value && !containerRef.value.contains(event.target)) {
        isOpen.value = false
        // Revert to selected option if clicking outside
        if (selectedOption.value) {
            searchQuery.value = selectedOption.value.label
        } else {
            searchQuery.value = ''
        }
    }
}

const clearSelection = () => {
    searchQuery.value = ''
    emit('update:modelValue', '')
    isOpen.value = false
}

onMounted(() => {
    document.addEventListener('mousedown', handleClickOutside)
})

onBeforeUnmount(() => {
    document.removeEventListener('mousedown', handleClickOutside)
})
</script>

<template>
    <div class="relative" ref="containerRef">
        <!-- Input Layer -->
        <input 
            type="text" 
            v-model="searchQuery"
            @focus="!disabled && openDropdown()"
            @input="!disabled && handleInput()"
            :placeholder="placeholder"
            :required="required && !modelValue"
            :disabled="disabled"
            class="w-full bg-slate-50 border-2 border-slate-200 rounded-xl px-4 py-2 text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all pr-10 disabled:opacity-50 disabled:cursor-not-allowed"
            :class="[disabled ? 'cursor-not-allowed' : 'cursor-text']"
        >
        
        <!-- Clear button / Icon -->
        <button 
            v-if="modelValue && !required && !disabled"
            @click.stop="clearSelection"
            class="absolute right-3 top-1/2 -translate-y-1/2 w-6 h-6 flex items-center justify-center text-slate-400 hover:text-rose-500 hover:bg-rose-50 rounded-full transition-colors z-10"
            title="Quitar asignación"
        >
            <i class="fas fa-times text-xs"></i>
        </button>
        <i v-else-if="icon" :class="icon" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 pointer-events-none"></i>
        <i v-else class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 pointer-events-none"></i>

        <!-- Dropdown Layer -->
        <transition 
            enter-active-class="transition duration-100 ease-out"
            enter-from-class="transform scale-95 opacity-0"
            enter-to-class="transform scale-100 opacity-100"
            leave-active-class="transition duration-75 ease-in"
            leave-from-class="transform scale-100 opacity-100"
            leave-to-class="transform scale-95 opacity-0"
        >
            <div v-if="isOpen" class="absolute z-50 w-full mt-2 bg-white rounded-2xl shadow-xl border border-slate-100 overflow-hidden max-h-60 overflow-y-auto">
                <ul v-if="filteredOptions.length > 0" class="py-2">
                    <li 
                        v-for="opt in filteredOptions" 
                        :key="opt.value"
                        @click="selectOption(opt)"
                        class="px-4 py-2 text-sm font-bold text-slate-600 hover:bg-primary-50 hover:text-primary-600 cursor-pointer transition-colors"
                        :class="{'bg-primary-50 text-primary-600': modelValue === opt.value}"
                    >
                        {{ opt.label }}
                    </li>
                </ul>
                <div v-else class="px-4 py-3 text-sm text-slate-400 font-medium text-center">
                    No se encontraron resultados
                </div>
            </div>
        </transition>
    </div>
</template>