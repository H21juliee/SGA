<script setup>
import { computed, ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'

const props = defineProps({
    section: Object,
    subject: Object,
    lapse: Object,
    allLapses: Array,
    dates: Array,
    enrollments: Array,
})

const selectedLapseId = ref(props.lapse.id)

function changeLapse() {
    router.get(`/attendance/history/${props.section.id}/${props.subject.id}/${selectedLapseId.value}`)
}

const getStatusColor = (status) => {
    switch (status) {
        case 'present': return 'bg-emerald-50 text-emerald-600 border border-emerald-100'
        case 'absent': return 'bg-red-50 text-red-600 border border-red-100'
        case 'late': return 'bg-amber-50 text-amber-600 border border-amber-100'
        case 'excused': return 'bg-blue-50 text-blue-600 border border-blue-100'
        default: return 'bg-slate-50 text-slate-300'
    }
}

const getStatusInitial = (status) => {
    switch (status) {
        case 'present': return 'P'
        case 'absent': return 'A'
        case 'late': return 'T'
        case 'excused': return 'J'
        default: return '-'
    }
}

const matrix = computed(() => {
    return props.enrollments.map(en => {
        const studentData = {}
        en.attendances.forEach(att => {
            // Asegurar que tomamos solo la parte de la fecha YYYY-MM-DD
            const dateStr = att.date.substring(0, 10)
            studentData[dateStr] = {
                status: att.status,
                notes: att.notes
            }
        })
        
        const totalAbsences = en.attendances.filter(a => a.status === 'absent').length
        const totalClasses = props.dates.length
        const attendanceRate = totalClasses > 0 ? Math.round(((totalClasses - totalAbsences) / totalClasses) * 100) : 100

        return {
            id: en.id,
            name: `${en.student.last_name}, ${en.student.first_name}`,
            attendance: studentData,
            totalAbsences,
            attendanceRate
        }
    })
})

const formatDate = (dateStr) => {
    // Tomar solo los primeros 10 caracteres (YYYY-MM-DD)
    const pureDate = dateStr.substring(0, 10)
    const [year, month, day] = pureDate.split('-')
    return `${day}/${month}/${year}`
}
</script>

<template>
    <AppLayout title="Historial de Asistencia">
        <div class="space-y-8 max-w-7xl mx-auto">
            <!-- Header & Breadcrumbs -->
            <div class="animate-fade-in-up">
                <nav class="flex items-center gap-3 text-[10px] font-black uppercase tracking-widest text-slate-400 mb-4">
                    <Link href="/attendance" class="hover:text-emerald-600 transition-colors">Asistencia</Link>
                    <i class="fas fa-chevron-right text-[8px]"></i>
                    <span class="text-slate-600">Historial de Matriz</span>
                </nav>

                <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-6">
                    <div>
                        <h1 class="text-3xl font-extrabold text-slate-800">
                            Matriz de <span class="gradient-text">Asistencia</span>
                        </h1>
                        <p class="text-slate-400 font-bold uppercase tracking-widest text-xs mt-2">
                            {{ subject.name }} · {{ section.grade_level?.name }} · Sección {{ section.name }}
                        </p>
                    </div>

                    <div class="flex flex-col gap-2 min-w-[200px]">
                        <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Filtrar por Lapso</label>
                        <div class="relative">
                            <select 
                                v-model="selectedLapseId" 
                                @change="changeLapse"
                                class="w-full bg-white border-2 border-slate-100 rounded-2xl px-4 py-3 text-slate-700 text-sm font-bold focus:border-emerald-400 focus:ring-0 outline-none transition-all appearance-none cursor-pointer shadow-sm"
                            >
                                <option v-for="l in allLapses" :key="l.id" :value="l.id">{{ l.name }}</option>
                            </select>
                            <i class="fas fa-filter absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 pointer-events-none"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Matrix Table -->
            <div class="glass-card rounded-3xl shadow-2xl overflow-hidden animate-fade-in-up" style="animation-delay: 100ms">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/80 border-b border-slate-100">
                                <th class="px-6 py-4 text-xs font-black text-slate-400 uppercase tracking-widest sticky left-0 bg-slate-50 z-20 min-w-[240px]">Estudiante</th>
                                <th v-for="date in dates" :key="date" class="px-2 py-4 text-center text-[10px] font-black text-slate-500 uppercase min-w-[70px] leading-tight border-l border-slate-100/50">
                                    {{ formatDate(date).split('/').slice(0,2).join('/') }}<br/>
                                    <span class="text-[8px] text-slate-300 font-bold">{{ formatDate(date).split('/')[2] }}</span>
                                </th>
                                <th class="px-6 py-4 text-center text-xs font-black text-slate-400 uppercase tracking-widest bg-slate-50/80 border-l border-slate-100">Faltas</th>
                                <th class="px-6 py-4 text-center text-xs font-black text-slate-400 uppercase tracking-widest bg-slate-50/80 border-l border-slate-100">% Asist.</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="row in matrix" :key="row.id" class="group hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4 font-bold text-slate-700 sticky left-0 bg-white group-hover:bg-slate-50 z-10 border-r border-slate-100 transition-colors">
                                    {{ row.name }}
                                </td>
                                <td v-for="date in dates" :key="date" class="px-1 py-3 text-center border-l border-slate-50">
                                    <div 
                                        v-if="row.attendance[date.substring(0, 10)]"
                                        class="inline-flex items-center justify-center w-9 h-9 rounded-xl text-[11px] font-black relative group/cell transition-all shadow-sm"
                                        :class="getStatusColor(row.attendance[date.substring(0, 10)].status)"
                                        :title="row.attendance[date.substring(0, 10)].notes || ''"
                                    >
                                        {{ getStatusInitial(row.attendance[date.substring(0, 10)].status) }}
                                        
                                        <!-- Note Indicator -->
                                        <div 
                                            v-if="row.attendance[date.substring(0, 10)].notes" 
                                            class="absolute -top-1 -right-1 w-2.5 h-2.5 bg-primary-500 rounded-full border-2 border-white shadow-sm"
                                        ></div>
                                    </div>
                                    <div v-else class="text-slate-200 font-black">-</div>
                                </td>
                                <td class="px-6 py-4 text-center font-black border-l border-slate-100" :class="row.totalAbsences > 3 ? 'text-red-500' : 'text-slate-400'">
                                    {{ row.totalAbsences }}
                                </td>
                                <td class="px-6 py-4 text-center border-l border-slate-100">
                                    <div class="flex flex-col items-center gap-1">
                                        <span 
                                            class="px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider shadow-sm"
                                            :class="row.attendanceRate < 75 ? 'bg-red-50 text-red-600' : 'bg-emerald-50 text-emerald-600'"
                                        >
                                            {{ row.attendanceRate }}%
                                        </span>
                                        <div class="w-12 h-1 bg-slate-100 rounded-full overflow-hidden">
                                            <div 
                                                class="h-full transition-all duration-500" 
                                                :class="row.attendanceRate < 75 ? 'bg-red-400' : 'bg-emerald-400'"
                                                :style="{ width: `${row.attendanceRate}%` }"
                                            ></div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Empty State -->
                <div v-if="dates.length === 0" class="p-20 text-center animate-fade-in">
                    <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 text-slate-200">
                        <i class="fas fa-folder-open text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-400">Sin registros de asistencia</h3>
                    <p class="text-slate-300 text-sm mt-1">No hay datos para mostrar en este lapso.</p>
                </div>
            </div>

            <!-- Legend Section -->
            <div class="flex flex-wrap items-center justify-center gap-8 p-6 bg-white rounded-3xl border border-slate-100 shadow-sm animate-fade-in-up" style="animation-delay: 200ms">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-[10px] font-black shadow-sm">P</div>
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Presente</span>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-xl bg-red-50 text-red-600 flex items-center justify-center text-[10px] font-black shadow-sm">A</div>
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Ausente</span>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-[10px] font-black shadow-sm">T</div>
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Tarde</span>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-[10px] font-black shadow-sm">J</div>
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Justificado</span>
                </div>
            </div>
        </div>
    </AppLayout>
</template>


<style scoped>
.sticky {
    position: sticky;
    left: 0;
}
</style>
