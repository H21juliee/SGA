<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'

const props = defineProps({
    user: Object,
    academicLoads: Array,
})

const role = computed(() => props.user.roles[0]?.name || 'Sin Rol')
const isDocente = computed(() => role.value === 'Docente')

// Agrupar carga académica por año escolar
const groupedLoads = computed(() => {
    if (!props.academicLoads) return []
    const groups = {}
    props.academicLoads.forEach(load => {
        const yearName = load.school_year.name
        if (!groups[yearName]) {
            groups[yearName] = []
        }
        groups[yearName].push(load)
    })
    return Object.keys(groups).map(year => ({
        year,
        loads: groups[year]
    }))
})

</script>

<template>
    <AppLayout :title="`Perfil de ${user.name}`">
        <div class="space-y-8 max-w-7xl mx-auto">
            
            <!-- Header Section -->
            <div class="flex items-center justify-between animate-fade-in-up">
                <div class="flex items-center gap-4">
                    <Link href="/admin/users" class="w-10 h-10 rounded-xl bg-white text-slate-400 hover:text-primary-600 shadow-sm flex items-center justify-center transition-all hover:-translate-x-1">
                        <i class="fas fa-arrow-left"></i>
                    </Link>
                    <div>
                        <h2 class="text-3xl font-extrabold text-slate-800">
                            Perfil de <span class="gradient-text">Usuario</span>
                        </h2>
                    </div>
                </div>
            </div>

            <!-- Profile Card -->
            <div class="glass-card rounded-3xl p-8 shadow-2xl relative overflow-hidden animate-fade-in-up" style="animation-delay: 100ms">
                <div class="absolute top-0 left-0 w-full h-32 bg-gradient-to-r from-primary-500 to-indigo-500 opacity-10"></div>
                
                <div class="relative z-10 flex flex-col md:flex-row gap-8 items-start md:items-center">
                    <!-- Avatar -->
                    <div class="w-32 h-32 rounded-3xl bg-gradient-to-br from-primary-500 to-indigo-600 text-white flex items-center justify-center text-5xl font-black shadow-xl shadow-primary-500/30 shrink-0">
                        {{ user.name.charAt(0) }}
                    </div>
                    
                    <!-- Info -->
                    <div class="flex-1 space-y-4">
                        <div>
                            <div class="flex flex-wrap items-center gap-3 mb-2">
                                <h3 class="text-3xl font-black text-slate-800">{{ user.name }}</h3>
                                <span class="px-3 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest border shadow-sm"
                                      :class="{
                                          'bg-primary-50 text-primary-600 border-primary-100': role === 'Docente',
                                          'bg-indigo-50 text-indigo-600 border-indigo-100': role === 'Administrador',
                                          'bg-emerald-50 text-emerald-600 border-emerald-100': role === 'Secretaria',
                                          'bg-rose-50 text-rose-600 border-rose-100': role === 'SuperAdmin',
                                          'bg-slate-50 text-slate-500 border-slate-200': role === 'Sin Rol'
                                      }">
                                    {{ role }}
                                </span>
                                <span v-if="user.is_active" class="px-3 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest bg-emerald-50 text-emerald-600 border border-emerald-100 shadow-sm flex items-center gap-2">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                    Activo
                                </span>
                                <span v-else class="px-3 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest bg-slate-50 text-slate-500 border border-slate-200 shadow-sm">
                                    Inactivo
                                </span>
                            </div>
                            <p class="text-slate-500 font-medium">Registrado el {{ new Date(user.created_at).toLocaleDateString() }}</p>
                        </div>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 pt-4 border-t border-slate-100">
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Cédula</p>
                                <p class="font-bold text-slate-700">{{ user.cedula || 'No especificada' }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Correo Electrónico</p>
                                <p class="font-bold text-slate-700">{{ user.email }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Teléfono</p>
                                <p class="font-bold text-slate-700">{{ user.phone || 'No especificado' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Academic Load Section (Only for Teachers) -->
            <div v-if="isDocente" class="space-y-6 animate-fade-in-up" style="animation-delay: 200ms">
                <div class="flex items-center gap-3 px-2">
                    <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-500 flex items-center justify-center text-lg">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <h3 class="text-xl font-black text-slate-800">Carga Académica</h3>
                </div>

                <div v-if="groupedLoads.length === 0" class="glass-card rounded-3xl p-12 text-center shadow-lg">
                    <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 text-slate-300">
                        <i class="fas fa-folder-open text-3xl"></i>
                    </div>
                    <h4 class="text-lg font-bold text-slate-500">Sin materias asignadas</h4>
                    <p class="text-sm text-slate-400 mt-1">Este docente aún no tiene carga académica en el sistema.</p>
                </div>

                <div v-else class="grid grid-cols-1 gap-6">
                    <div v-for="group in groupedLoads" :key="group.year" class="glass-card rounded-3xl overflow-hidden shadow-xl border border-white/40">
                        <div class="bg-slate-50/80 px-6 py-4 border-b border-slate-100/50">
                            <h4 class="font-black text-slate-700 tracking-wide">
                                Año Escolar <span class="text-primary-600">{{ group.year }}</span>
                            </h4>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <div v-for="load in group.loads" :key="load.id" class="p-4 rounded-2xl bg-white border border-slate-100 shadow-sm hover:shadow-md transition-shadow group">
                                    <div class="flex items-start justify-between mb-3">
                                        <div class="w-10 h-10 rounded-xl bg-primary-50 text-primary-500 flex items-center justify-center group-hover:bg-primary-500 group-hover:text-white transition-colors">
                                            <i class="fas fa-book"></i>
                                        </div>
                                        <span class="px-2 py-1 rounded bg-slate-50 text-[10px] font-black text-slate-500 tracking-wider">
                                            SECCIÓN {{ load.section.name }}
                                        </span>
                                    </div>
                                    <h5 class="font-bold text-slate-800 line-clamp-1" :title="load.subject.name">
                                        {{ load.subject.name }}
                                    </h5>
                                    <p class="text-xs font-bold text-slate-400 mt-1">
                                        {{ load.section.grade_level.name }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
