<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'

const props = defineProps({
    student: Object,
    enrollments: Array,
})

// Calcular la nota definitiva de una materia (promedio de los 3 lapsos)
const calculateFinalGrade = (grades) => {
    if (!grades || grades.length === 0) return null;
    
    let sum = 0;
    let count = 0;
    
    // Asumimos que los lapsos son 1, 2 y 3.
    const lapses = [1, 2, 3];
    
    lapses.forEach(lap => {
        const grade = grades.find(g => g.lapse.number === lap);
        if (grade && grade.score !== null) {
            sum += parseFloat(grade.score);
            count++;
        }
    });
    
    if (count === 0) return null;
    return (sum / count).toFixed(2);
}

// Obtener nota por lapso
const getLapseGrade = (grades, lapseNumber) => {
    if (!grades) return '-';
    const grade = grades.find(g => g.lapse.number === lapseNumber);
    return grade && grade.score !== null ? grade.score : '-';
}

// Formatear fecha
const formatDate = (dateString) => {
    if (!dateString) return 'No especificada';
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(dateString).toLocaleDateString('es-ES', options);
}

</script>

<template>
    <AppLayout :title="`Expediente de ${student.first_name}`">
        <div class="space-y-8 max-w-7xl mx-auto">
            
            <!-- Header Section -->
            <div class="flex items-center justify-between animate-fade-in-up">
                <div class="flex items-center gap-4">
                    <Link href="/admin/students" class="w-10 h-10 rounded-xl bg-white text-slate-400 hover:text-primary-600 shadow-sm flex items-center justify-center transition-all hover:-translate-x-1">
                        <i class="fas fa-arrow-left"></i>
                    </Link>
                    <div>
                        <h2 class="text-3xl font-extrabold text-slate-800">
                            Expediente <span class="gradient-text">Académico</span>
                        </h2>
                    </div>
                </div>
            </div>

            <!-- Profile Card -->
            <div class="glass-card rounded-3xl p-8 shadow-2xl relative overflow-hidden animate-fade-in-up" style="animation-delay: 100ms">
                <div class="absolute top-0 left-0 w-full h-32 bg-gradient-to-r from-primary-500 to-indigo-500 opacity-10"></div>
                
                <div class="relative z-10 flex flex-col md:flex-row gap-8 items-start md:items-center">
                    <!-- Avatar -->
                    <div class="w-32 h-32 rounded-3xl bg-gradient-to-br from-primary-500 to-indigo-600 text-white flex items-center justify-center text-5xl font-black shadow-xl shadow-primary-500/30 shrink-0 overflow-hidden">
                        <img v-if="student.photo_url" :src="student.photo_url" alt="Foto del estudiante" class="w-full h-full object-cover">
                        <span v-else>{{ student.first_name.charAt(0) }}{{ student.last_name.charAt(0) }}</span>
                    </div>
                    
                    <!-- Info -->
                    <div class="flex-1 space-y-4">
                        <div>
                            <div class="flex flex-wrap items-center gap-3 mb-2">
                                <h3 class="text-3xl font-black text-slate-800">{{ student.last_name }}, {{ student.first_name }}</h3>
                                
                                <span v-if="student.status === 'regular'" class="px-3 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest bg-emerald-50 text-emerald-600 border border-emerald-100 shadow-sm flex items-center gap-2">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                    Regular
                                </span>
                                <span v-else-if="student.status === 'withdrawn'" class="px-3 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest bg-slate-50 text-slate-600 border border-slate-200 shadow-sm">
                                    Retirado
                                </span>
                                <span v-else-if="student.status === 'suspended'" class="px-3 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest bg-rose-50 text-rose-600 border border-rose-200 shadow-sm">
                                    Suspendido
                                </span>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 pt-4 border-t border-slate-100">
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Cédula</p>
                                <p class="font-bold text-slate-700">{{ student.cedula || 'No especificada' }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Fecha Nac.</p>
                                <p class="font-bold text-slate-700">{{ formatDate(student.birth_date) }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Representante</p>
                                <p class="font-bold text-slate-700">{{ student.guardian ? student.guardian.name : '' || 'No especificado' }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Tel. Repr.</p>
                                <p class="font-bold text-slate-700">{{ student.guardian ? student.guardian.phone : '' || 'No especificado' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Academic History Section -->
            <div class="space-y-6 animate-fade-in-up" style="animation-delay: 200ms">
                <div class="flex items-center gap-3 px-2">
                    <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-500 flex items-center justify-center text-lg">
                        <i class="fas fa-history"></i>
                    </div>
                    <h3 class="text-xl font-black text-slate-800">Historial de Inscripciones y Notas</h3>
                </div>

                <div v-if="enrollments.length === 0" class="glass-card rounded-3xl p-12 text-center shadow-lg">
                    <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 text-slate-300">
                        <i class="fas fa-folder-open text-3xl"></i>
                    </div>
                    <h4 class="text-lg font-bold text-slate-500">Sin historial académico</h4>
                    <p class="text-sm text-slate-400 mt-1">Este estudiante no tiene inscripciones registradas en el sistema.</p>
                </div>

                <div v-else class="grid grid-cols-1 gap-8">
                    <div v-for="enrollment in enrollments" :key="enrollment.id" class="glass-card rounded-3xl overflow-hidden shadow-xl border border-white/40">
                        
                        <!-- Header del Año Escolar -->
                        <div class="bg-slate-50/80 px-6 py-5 border-b border-slate-100/50 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div>
                                <h4 class="font-black text-slate-700 tracking-wide text-lg">
                                    Año Escolar <span class="text-primary-600">{{ enrollment.school_year.name }}</span>
                                </h4>
                                <p class="text-xs font-bold text-slate-500 mt-1 uppercase tracking-widest">
                                    {{ enrollment.section.grade_level.name }} - SECCIÓN {{ enrollment.section.name }}
                                </p>
                            </div>
                            
                            <div class="flex items-center gap-3">
                                <span v-if="enrollment.status === 'active'" class="px-3 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest bg-emerald-50 text-emerald-600 border border-emerald-100 shadow-sm flex items-center gap-2">
                                    Activa
                                </span>
                                <span v-else-if="enrollment.status === 'withdrawn'" class="px-3 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest bg-slate-50 text-slate-600 border border-slate-200 shadow-sm">
                                    Retirada
                                </span>
                                <span v-else-if="enrollment.status === 'completed'" class="px-3 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest bg-indigo-50 text-indigo-600 border border-indigo-200 shadow-sm">
                                    Completada
                                </span>
                            </div>
                        </div>

                        <!-- Tabla de Calificaciones -->
                        <div class="overflow-x-auto w-full">
                            <table class="w-full text-sm text-left">
                                <thead class="bg-slate-50/50 text-slate-400 text-[10px] uppercase font-black tracking-[0.2em]">
                                    <tr>
                                        <th class="px-6 py-4">Materia</th>
                                        <th class="px-4 py-4 text-center w-24">Lapso 1</th>
                                        <th class="px-4 py-4 text-center w-24">Lapso 2</th>
                                        <th class="px-4 py-4 text-center w-24">Lapso 3</th>
                                        <th class="px-6 py-4 text-center w-32">Definitiva</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-50">
                                    <template v-if="enrollment.grades && enrollment.grades.length > 0">
                                        <tr v-for="subjectGroup in Object.values(
                                            enrollment.grades.reduce((acc, grade) => {
                                                if (!acc[grade.subject_id]) acc[grade.subject_id] = { subject: grade.subject, grades: [] };
                                                acc[grade.subject_id].grades.push(grade);
                                                return acc;
                                            }, {})
                                        )" :key="subjectGroup.subject.id" class="hover:bg-slate-50/50 transition-colors">
                                            <td class="px-6 py-4 font-bold text-slate-700">
                                                {{ subjectGroup.subject.name }}
                                            </td>
                                            <td class="px-4 py-4 text-center font-medium text-slate-600">
                                                {{ getLapseGrade(subjectGroup.grades, 1) }}
                                            </td>
                                            <td class="px-4 py-4 text-center font-medium text-slate-600">
                                                {{ getLapseGrade(subjectGroup.grades, 2) }}
                                            </td>
                                            <td class="px-4 py-4 text-center font-medium text-slate-600">
                                                {{ getLapseGrade(subjectGroup.grades, 3) }}
                                            </td>
                                            <td class="px-6 py-4 text-center">
                                                <span class="inline-flex items-center justify-center px-3 py-1 rounded-lg text-xs font-black shadow-sm"
                                                    :class="{
                                                        'bg-emerald-50 text-emerald-600 border border-emerald-100': calculateFinalGrade(subjectGroup.grades) >= 10,
                                                        'bg-rose-50 text-rose-600 border border-rose-100': calculateFinalGrade(subjectGroup.grades) < 10 && calculateFinalGrade(subjectGroup.grades) !== null,
                                                        'bg-slate-50 text-slate-400 border border-slate-100': calculateFinalGrade(subjectGroup.grades) === null
                                                    }">
                                                    {{ calculateFinalGrade(subjectGroup.grades) || '-' }}
                                                </span>
                                            </td>
                                        </tr>
                                    </template>
                                    <tr v-else>
                                        <td colspan="5" class="px-6 py-8 text-center text-slate-400 text-sm">
                                            No hay calificaciones registradas para este año escolar.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>


