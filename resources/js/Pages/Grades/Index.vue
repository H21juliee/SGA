<script setup>
import { ref } from 'vue'
import AppLayout from '@/Components/Layout/AppLayout.vue'
import { Link, router } from '@inertiajs/vue3'

const props = defineProps({
    loads: { type: Array, default: () => [] },
    activeYear: { type: Object, default: null },
    lapses: { type: Array, default: () => [] },
    schoolYears: { type: Array, default: () => [] },
})

const selectedYearId = ref(props.activeYear?.id)

function changeYear() {
    router.get('/grades', { school_year_id: selectedYearId.value })
}
</script>

<template>
    <AppLayout title="Gestión de Notas">
        <div class="space-y-8">
            <!-- Header Section -->
            <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-6 animate-fade-in-up">
                <div>
                    <h1 class="text-3xl font-extrabold text-slate-800">
                        Gestión de <span class="gradient-text">Notas</span>
                    </h1>
                    <p class="text-slate-400 font-medium mt-2">Seleccione una materia y sección para registrar calificaciones</p>
                </div>

                <div class="flex flex-col gap-2 min-w-[240px]">
                    <label class="text-[11px] font-bold text-slate-400 uppercase tracking-widest ml-1">Año Escolar</label>
                    <div class="relative">
                        <select 
                            v-model="selectedYearId" 
                            @change="changeYear"
                            class="w-full bg-white border-2 border-slate-100 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-primary-400 focus:ring-0 outline-none transition-all appearance-none cursor-pointer shadow-sm"
                        >
                            <option v-for="year in schoolYears" :key="year.id" :value="year.id">
                                {{ year.name }} {{ year.is_active ? '— (Año Actual)' : '' }}
                            </option>
                        </select>
                        <i class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                    </div>
                </div>
            </div>

            <!-- Status Warnings -->
            <div v-if="!activeYear" class="glass-card rounded-2xl bg-amber-500/5 border border-amber-500/20 p-6 animate-fade-in-up">
                <div class="flex items-center gap-4 text-amber-600">
                    <i class="fas fa-exclamation-circle text-xl"></i>
                    <p class="font-bold">No hay un año escolar activo configurado para este periodo.</p>
                </div>
            </div>

            <div v-else-if="loads.length === 0" class="glass-card rounded-3xl p-16 text-center animate-fade-in-up">
                <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-6 text-slate-200">
                    <i class="fas fa-book-open text-4xl"></i>
                </div>
                <h3 class="text-xl font-bold text-slate-600">Sin carga académica</h3>
                <p class="text-slate-400 mt-2 max-w-sm mx-auto">No tienes materias asignadas para gestionar notas en el año escolar seleccionado.</p>
            </div>

            <!-- Loads Grid -->
            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div
                    v-for="(load, index) in loads"
                    :key="load.id"
                    class="glass-card rounded-3xl p-6 shadow-xl relative overflow-hidden group hover:-translate-y-1 transition-all duration-300 animate-fade-in-up"
                    :style="{ animationDelay: `${(index + 1) * 50}ms` }"
                >
                    <!-- Gradient accent line -->
                    <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-primary-400 to-accent-400 opacity-20 group-hover:opacity-100 transition-opacity"></div>

                    <div class="flex items-start justify-between mb-4">
                        <div class="flex-1">
                            <h3 class="text-lg font-black text-slate-800 group-hover:text-primary-600 transition-colors leading-tight">
                                {{ load.subject?.name }}
                            </h3>
                            <p class="text-xs font-bold text-slate-400 mt-1 uppercase tracking-wider">
                                {{ load.section?.grade_level?.name }} · Sección {{ load.section?.name }}
                            </p>
                        </div>
                        <div class="w-10 h-10 bg-primary-50 text-primary-600 rounded-xl flex items-center justify-center text-xs font-bold shadow-sm">
                            {{ load.subject?.code }}
                        </div>
                    </div>

                    <div class="h-[1px] bg-slate-100 w-full my-4"></div>

                    <!-- Lapses Selection -->
                    <div class="space-y-3">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Seleccionar Lapso</p>
                        <div class="flex gap-2">
                            <Link
                                v-for="lapse in lapses"
                                :key="lapse.id"
                                :href="`/grades/${load.section_id}/${load.subject_id}/${lapse.id}`"
                                class="flex-1 flex flex-col items-center py-3 rounded-2xl border-2 transition-all duration-300 group/btn relative overflow-hidden"
                                :class="lapse.is_open
                                    ? 'border-emerald-100 bg-emerald-50/30 text-emerald-700 hover:border-emerald-300 hover:bg-emerald-50 shadow-sm'
                                    : 'border-slate-50 bg-slate-50/50 text-slate-400 hover:border-slate-200'"
                            >
                                <span class="text-xs font-black uppercase tracking-widest">{{ lapse.name }}</span>
                                <div v-if="lapse.is_open" class="mt-1 flex items-center gap-1">
                                    <div class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></div>
                                    <span class="text-[8px] font-bold uppercase tracking-tighter">Abierto</span>
                                </div>
                                <span v-else class="text-[8px] font-bold uppercase tracking-tighter mt-1">Cerrado</span>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
