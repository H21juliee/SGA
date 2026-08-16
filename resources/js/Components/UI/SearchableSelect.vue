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
    
    // If the search query exactly matches the selected option's label, show all options
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
    // Optionally clear search when opening to see all options, but keep the current if we want
    if (selectedOption.value) {
        searchQuery.value = ''
    }
}

const handleInput = () => {
    isOpen.value = true
    // If user starts typing, we should probably clear the actual modelValue until they select a valid one
    // But it's better to just emit null if it doesn't match
    if (selectedOption.value && searchQuery.value !== selectedOption.value.label) {
        emit('update:modelValue', '')
    }
}

const handleClickOutside = (event) => {
    if (containerRef.value && !containerRef.value.contains(event.target)) {
        isOpen.value = false
        // Revert search query to selected option if no new option was selected
        if (selectedOption.value) {
            searchQuery.value = selectedOption.value.label
        } else {
            searchQuery.value = ''
        }
    }
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
            @focus="openDropdown"
            @input="handleInput"
            :placeholder="placeholder"
            :required="required && !modelValue"
            class="w-full bg-slate-50 border-2 border-slate-400 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:bg-white focus:ring-0 outline-none transition-all cursor-text"
        >
        <!-- Icon -->
        <i v-if="icon" :class="icon" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 pointer-events-none"></i>
        <i v-else class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 pointer-events-none"></i>

        <!-- Hidden actual input for form submission if needed, though Inertia uses modelValue -->
        
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
