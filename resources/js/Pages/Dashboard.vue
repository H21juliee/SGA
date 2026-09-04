<template>
    <AppLayout title="Dashboard">
        <div class="space-y-8 max-w-7xl mx-auto pb-10">
            <!-- Welcome Header -->
            <div class="animate-fade-in-up flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-3xl lg:text-4xl font-extrabold text-slate-800 tracking-tight">
                        Bienvenido, <span class="gradient-text">{{ user?.name }}</span>
                    </h1>
                    <p class="text-slate-400 font-medium mt-1">
                        Panel de control — {{ primaryRoleName }}
                    </p>
                </div>
                
                <div v-if="activeYear" class="flex items-center gap-2 self-start md:self-auto px-4 py-2 rounded-2xl bg-white/70 shadow-sm border border-slate-100">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span class="text-xs font-bold text-slate-600">Año Activo: {{ activeYear.name }}</span>
                </div>
            </div>

            <!-- ============================================================= -->
            <!-- 1. BLOQUE SUPERADMIN / ADMINISTRADOR                          -->
            <!-- ============================================================= -->
            <template v-if="isAdminOrSuperAdmin">
                <!-- Admin Stats Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div
                        v-for="(card, index) in adminCards"
                        :key="card.title"
                        class="glass-card rounded-3xl p-6 shadow-xl relative overflow-hidden group hover:-translate-y-1 transition-all duration-300"
                        :class="`animate-fade-in-up`"
                        :style="{ animationDelay: `${(index + 1) * 80}ms` }"
                    >
                        <div
                            class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r scale-x-0 origin-left transition-transform duration-500 group-hover:scale-x-100"
                            :class="card.barGradient"
                        ></div>

                        <div class="relative flex flex-col h-full">
                            <div
                                class="w-14 h-14 rounded-2xl flex items-center justify-center text-2xl mb-5 shadow-lg group-hover:scale-110 transition-transform duration-300"
                                :class="card.iconClass"
                            >
                                <i :class="card.faIcon"></i>
                            </div>

                            <p class="text-slate-400 font-medium text-sm mb-1">{{ card.title }}</p>
                            <h3 class="text-3xl font-extrabold text-slate-800">{{ card.value }}</h3>
                            
                            <div class="mt-4 flex items-center gap-1.5 text-xs font-bold" :class="card.badgeColor">
                                <i class="fas fa-chart-line"></i>
                                <span>{{ card.subtitle }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Middle Row: Grade Progress & Attendance -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Progreso de Carga de Calificaciones por Lapso (2 Cols) -->
                    <div class="lg:col-span-2 glass-card rounded-3xl p-6 sm:p-8 shadow-xl animate-fade-in-up" style="animation-delay: 350ms">
                        <div class="flex items-center justify-between mb-6">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-primary-50 text-primary-600 flex items-center justify-center text-lg shadow-sm">
                                    <i class="fas fa-tasks"></i>
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-slate-800">Progreso Global de Calificaciones</h3>
                                    <p class="text-xs text-slate-400 font-medium">Avance de carga de notas en el año escolar</p>
                                </div>
                            </div>
                            <Link href="/grades" class="text-xs font-bold text-primary-600 hover:text-primary-700 flex items-center gap-1">
                                Ver Calificaciones <i class="fas fa-arrow-right text-[10px]"></i>
                            </Link>
                        </div>

                        <div v-if="gradeProgress && gradeProgress.length > 0" class="space-y-5">
                            <div
                                v-for="lapse in gradeProgress"
                                :key="lapse.id"
                                class="p-4 rounded-2xl bg-white/60 border border-slate-100 hover:border-slate-200 transition-all shadow-sm"
                            >
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center gap-2.5">
                                        <span class="font-extrabold text-sm text-slate-800">{{ lapse.name }}</span>
                                        <span
                                            class="text-[10px] font-black uppercase px-2 py-0.5 rounded-md tracking-wider border"
                                            :class="lapse.is_open 
                                                ? 'bg-emerald-50 text-emerald-600 border-emerald-200' 
                                                : 'bg-slate-100 text-slate-400 border-slate-200'"
                                        >
                                            {{ lapse.is_open ? 'Abierto' : 'Cerrado' }}
                                        </span>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-sm font-black text-slate-700">{{ lapse.percentage }}%</span>
                                        <span class="text-[11px] text-slate-400 ml-1.5 font-medium">({{ lapse.loaded }} / {{ lapse.expected }} notas)</span>
                                    </div>
                                </div>

                                <!-- Progress bar -->
                                <div class="w-full bg-slate-100 rounded-full h-3 overflow-hidden p-0.5 shadow-inner">
                                    <div
                                        class="h-full rounded-full transition-all duration-700 shadow-sm"
                                        :class="lapse.percentage >= 100 
                                            ? 'bg-gradient-to-r from-emerald-500 to-teal-400' 
                                            : (lapse.percentage >= 50 ? 'bg-gradient-to-r from-primary-500 to-primary-400' : 'bg-gradient-to-r from-amber-400 to-orange-400')"
                                        :style="{ width: `${Math.min(lapse.percentage, 100)}%` }"
                                    ></div>
                                </div>
                            </div>
                        </div>

                        <div v-else class="text-center py-8 text-slate-400 text-sm font-medium">
                            <i class="fas fa-info-circle text-2xl mb-2 text-slate-300"></i>
                            <p>No hay lapsos registrados en el año escolar activo.</p>
                        </div>
                    </div>

                    <!-- Asistencia de Hoy (1 Col) -->
                    <div class="glass-card rounded-3xl p-6 sm:p-8 shadow-xl animate-fade-in-up flex flex-col justify-between" style="animation-delay: 450ms">
                        <div>
                            <div class="flex items-center justify-between mb-6">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center text-lg shadow-sm">
                                        <i class="fas fa-user-check"></i>
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-bold text-slate-800">Asistencia de Hoy</h3>
                                        <p class="text-xs text-slate-400 font-medium">Resumen del día</p>
                                    </div>
                                </div>
                                <Link href="/attendance" class="text-xs font-bold text-teal-600 hover:text-teal-700">
                                    <i class="fas fa-external-link-alt"></i>
                                </Link>
                            </div>

                            <div v-if="todayAttendance.total > 0" class="space-y-3">
                                <div class="flex items-center justify-between p-3 rounded-xl bg-emerald-50/70 border border-emerald-100">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                                        <span class="text-xs font-bold text-emerald-800">Presentes</span>
                                    </div>
                                    <span class="text-sm font-black text-emerald-700">{{ todayAttendance.present }}</span>
                                </div>

                                <div class="flex items-center justify-between p-3 rounded-xl bg-red-50/70 border border-red-100">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2.5 h-2.5 rounded-full bg-red-500"></span>
                                        <span class="text-xs font-bold text-red-800">Ausentes</span>
                                    </div>
                                    <span class="text-sm font-black text-red-700">{{ todayAttendance.absent }}</span>
                                </div>

                                <div class="flex items-center justify-between p-3 rounded-xl bg-amber-50/70 border border-amber-100">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                                        <span class="text-xs font-bold text-amber-800">Retardos</span>
                                    </div>
                                    <span class="text-sm font-black text-amber-700">{{ todayAttendance.late }}</span>
                                </div>

                                <div class="flex items-center justify-between p-3 rounded-xl bg-sky-50/70 border border-sky-100">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2.5 h-2.5 rounded-full bg-sky-500"></span>
                                        <span class="text-xs font-bold text-sky-800">Justificados</span>
                                    </div>
                                    <span class="text-sm font-black text-sky-700">{{ todayAttendance.excused }}</span>
                                </div>
                            </div>

                            <div v-else class="text-center py-10 text-slate-400 font-medium text-xs">
                                <i class="fas fa-calendar-day text-3xl mb-2 text-slate-300"></i>
                                <p>No se ha registrado asistencia en el sistema el día de hoy.</p>
                            </div>
                        </div>

                        <div v-if="todayAttendance.total > 0" class="mt-4 pt-4 border-t border-slate-100 flex items-center justify-between text-xs font-bold text-slate-500">
                            <span>Total Evaluados:</span>
                            <span class="text-slate-800 font-black text-sm">{{ todayAttendance.total }}</span>
                        </div>
                    </div>
                </div>

                <!-- Bottom Row: Recent Activity Log Feed -->
                <div class="glass-card rounded-3xl p-6 sm:p-8 shadow-xl animate-fade-in-up" style="animation-delay: 550ms">
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-lg shadow-sm">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-slate-800">Actividad Reciente del Sistema</h3>
                                <p class="text-xs text-slate-400 font-medium">Registro de auditoría y operaciones clave</p>
                            </div>
                        </div>
                        <Link v-if="can('audit.view')" href="/admin/audit" class="text-xs font-bold text-indigo-600 hover:text-indigo-700 flex items-center gap-1">
                            Ver Auditoría Completa <i class="fas fa-arrow-right text-[10px]"></i>
                        </Link>
                    </div>

                    <div v-if="recentActivity && recentActivity.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div
                            v-for="log in recentActivity"
                            :key="log.id"
                            class="p-4 rounded-2xl bg-white/60 border border-slate-100 hover:shadow-md transition-all flex flex-col justify-between"
                        >
                            <div>
                                <div class="flex items-center justify-between gap-2 mb-2">
                                    <span class="text-[10px] font-black uppercase tracking-wider px-2 py-0.5 rounded-md bg-slate-100 text-slate-600">
                                        {{ log.module }}
                                    </span>
                                    <span class="text-[10px] text-slate-400 font-medium">{{ log.time_ago }}</span>
                                </div>
                                <p class="text-xs font-semibold text-slate-700 line-clamp-2">{{ log.description }}</p>
                            </div>

                            <div class="mt-3 pt-3 border-t border-slate-100/60 flex items-center gap-2">
                                <div class="w-5 h-5 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-[9px] font-bold">
                                    {{ log.user_name?.charAt(0) }}
                                </div>
                                <span class="text-[11px] font-bold text-slate-500 truncate">{{ log.user_name }}</span>
                            </div>
                        </div>
                    </div>

                    <div v-else class="text-center py-6 text-slate-400 text-xs font-medium">
                        <p>No hay registros recientes de actividad.</p>
                    </div>
                </div>
            </template>

            <!-- ============================================================= -->
            <!-- 2. BLOQUE DOCENTE                                             -->
            <!-- ============================================================= -->
            <template v-else-if="isDocenteOnly">
                <!-- Teacher KPI Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div
                        v-for="(card, index) in teacherCards"
                        :key="card.title"
                        class="glass-card rounded-3xl p-6 shadow-xl relative overflow-hidden group hover:-translate-y-1 transition-all duration-300 animate-fade-in-up"
                        :style="{ animationDelay: `${(index + 1) * 80}ms` }"
                    >
                        <div class="relative flex flex-col h-full">
                            <div
                                class="w-14 h-14 rounded-2xl flex items-center justify-center text-2xl mb-5 shadow-lg group-hover:scale-110 transition-transform duration-300"
                                :class="card.iconClass"
                            >
                                <i :class="card.faIcon"></i>
                            </div>

                            <p class="text-slate-400 font-medium text-sm mb-1">{{ card.title }}</p>
                            <h3 class="text-3xl font-extrabold text-slate-800">{{ card.value }}</h3>
                            
                            <div class="mt-4 flex items-center gap-1.5 text-xs font-bold text-slate-500">
                                <span>{{ card.subtitle }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Teacher Academic Load Cards -->
                <div class="glass-card rounded-3xl p-6 sm:p-8 shadow-xl animate-fade-in-up" style="animation-delay: 350ms">
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center text-lg shadow-sm">
                                <i class="fas fa-chalkboard-teacher"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-slate-800">Mis Secciones y Asignaturas</h3>
                                <p class="text-xs text-slate-400 font-medium">Estado de carga de notas en el lapso actual</p>
                            </div>
                        </div>
                    </div>

                    <div v-if="teacherLoads && teacherLoads.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                        <div
                            v-for="load in teacherLoads"
                            :key="load.id"
                            class="p-5 rounded-2xl bg-white border border-slate-100 shadow-sm hover:shadow-md transition-all flex flex-col justify-between"
                        >
                            <div>
                                <div class="flex items-center justify-between gap-2 mb-2">
                                    <span class="text-xs font-black px-2.5 py-1 rounded-lg bg-teal-50 text-teal-700 border border-teal-100">
                                        {{ load.grade_level }} — Sec. {{ load.section_name }}
                                    </span>
                                    <span class="text-[11px] font-bold text-slate-400">
                                        {{ load.students_count }} Alumnos
                                    </span>
                                </div>

                                <h4 class="text-base font-extrabold text-slate-800 mt-2">{{ load.subject_name }}</h4>

                                <!-- Progress -->
                                <div class="mt-4">
                                    <div class="flex items-center justify-between text-xs font-bold mb-1.5">
                                        <span class="text-slate-400">Notas Lapso Activo:</span>
                                        <span :class="load.percentage >= 100 ? 'text-emerald-600 font-black' : 'text-slate-700 font-black'">
                                            {{ load.grades_loaded }} / {{ load.students_count }} ({{ load.percentage }}%)
                                        </span>
                                    </div>
                                    <div class="w-full bg-slate-100 rounded-full h-2.5 overflow-hidden">
                                        <div
                                            class="h-full rounded-full transition-all duration-500"
                                            :class="load.percentage >= 100 ? 'bg-emerald-500' : (load.percentage > 0 ? 'bg-teal-500' : 'bg-slate-300')"
                                            :style="{ width: `${Math.min(load.percentage, 100)}%` }"
                                        ></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="mt-5 pt-4 border-t border-slate-100 flex items-center gap-2">
                                <Link
                                    v-if="load.open_lapse_id"
                                    :href="`/grades/${load.section_id}/${load.subject_id}/${load.open_lapse_id}`"
                                    class="flex-1 py-2.5 px-3 rounded-xl bg-teal-600 hover:bg-teal-500 text-white font-bold text-xs text-center transition-colors flex items-center justify-center gap-1.5 shadow-sm shadow-teal-600/20"
                                >
                                    <i class="fas fa-file-signature"></i>
                                    <span>Calificar</span>
                                </Link>
                                <Link
                                    :href="`/attendance?section_id=${load.section_id}&subject_id=${load.subject_id}`"
                                    class="py-2.5 px-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold text-xs transition-colors flex items-center justify-center gap-1.5"
                                    title="Pasar Asistencia"
                                >
                                    <i class="fas fa-clipboard-check"></i>
                                </Link>
                            </div>
                        </div>
                    </div>

                    <div v-else class="text-center py-10 text-slate-400 font-medium text-xs">
                        <i class="fas fa-book-open text-3xl mb-2 text-slate-300"></i>
                        <p>No tienes asignaturas asignadas en el año escolar activo.</p>
                    </div>
                </div>
            </template>

            <!-- ============================================================= -->
            <!-- 3. BLOQUE SECRETARÍA / OTROS                                  -->
            <!-- ============================================================= -->
            <template v-else>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div
                        v-for="(card, index) in secretaryCards"
                        :key="card.title"
                        class="glass-card rounded-3xl p-6 shadow-xl relative overflow-hidden group hover:-translate-y-1 transition-all duration-300 animate-fade-in-up"
                        :style="{ animationDelay: `${(index + 1) * 80}ms` }"
                    >
                        <div class="relative flex flex-col h-full">
                            <div
                                class="w-14 h-14 rounded-2xl flex items-center justify-center text-2xl mb-5 shadow-lg group-hover:scale-110 transition-transform duration-300"
                                :class="card.iconClass"
                            >
                                <i :class="card.faIcon"></i>
                            </div>

                            <p class="text-slate-400 font-medium text-sm mb-1">{{ card.title }}</p>
                            <h3 class="text-3xl font-extrabold text-slate-800">{{ card.value }}</h3>
                            
                            <div class="mt-4 flex items-center gap-1.5 text-xs font-bold text-slate-500">
                                <span>{{ card.subtitle }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <!-- ============================================================= -->
            <!-- ACCIONES RÁPIDAS (ADAPTADAS A PERMISOS)                        -->
            <!-- ============================================================= -->
            <section class="glass-card rounded-3xl p-8 shadow-xl animate-fade-in-up" style="animation-delay: 600ms">
                <h3 class="text-xl font-bold text-slate-800 mb-6 flex items-center gap-3">
                    <div class="w-10 h-10 bg-amber-500 rounded-xl flex items-center justify-center text-white shadow-lg shadow-amber-500/20">
                        <i class="fas fa-bolt"></i>
                    </div>
                    Acciones Rápidas
                </h3>
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <Link v-if="can('students.create') || can('students.view')" href="/admin/students" class="action-btn-hover p-6 border-2 border-dashed border-slate-100 rounded-2xl bg-white/50 flex flex-col items-center gap-4 text-center group transition-all">
                        <div class="w-16 h-16 rounded-2xl flex items-center justify-center text-3xl group-hover:scale-110 transition-transform" style="background: rgba(51, 107, 135, 0.15); color: #336b87">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <span class="font-bold text-slate-700 text-sm">Directorio Estudiantes</span>
                    </Link>

                    <Link v-if="can('enrollments.view') || can('enrollments.create')" href="/admin/enrollments" class="action-btn-hover p-6 border-2 border-dashed border-slate-100 rounded-2xl bg-white/50 flex flex-col items-center gap-4 text-center group transition-all">
                        <div class="w-16 h-16 rounded-2xl flex items-center justify-center text-3xl group-hover:scale-110 transition-transform" style="background: rgba(16, 185, 129, 0.15); color: #10b981">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <span class="font-bold text-slate-700 text-sm">Inscripciones</span>
                    </Link>

                    <Link v-if="can('grades.view') || can('grades.edit')" href="/grades" class="action-btn-hover p-6 border-2 border-dashed border-slate-100 rounded-2xl bg-white/50 flex flex-col items-center gap-4 text-center group transition-all">
                        <div class="w-16 h-16 rounded-2xl flex items-center justify-center text-3xl group-hover:scale-110 transition-transform" style="background: rgba(15, 118, 110, 0.15); color: #0f766e">
                            <i class="fas fa-file-signature"></i>
                        </div>
                        <span class="font-bold text-slate-700 text-sm">Registrar Notas</span>
                    </Link>

                    <Link v-if="can('attendance.view') || can('attendance.manage')" href="/attendance" class="action-btn-hover p-6 border-2 border-dashed border-slate-100 rounded-2xl bg-white/50 flex flex-col items-center gap-4 text-center group transition-all">
                        <div class="w-16 h-16 rounded-2xl flex items-center justify-center text-3xl group-hover:scale-110 transition-transform" style="background: rgba(217, 119, 6, 0.15); color: #d97706">
                            <i class="fas fa-clipboard-check"></i>
                        </div>
                        <span class="font-bold text-slate-700 text-sm">Tomar Asistencia</span>
                    </Link>

                    <Link v-if="can('reports.generate')" href="/reports" class="action-btn-hover p-6 border-2 border-dashed border-slate-100 rounded-2xl bg-white/50 flex flex-col items-center gap-4 text-center group transition-all">
                        <div class="w-16 h-16 rounded-2xl flex items-center justify-center text-3xl group-hover:scale-110 transition-transform" style="background: rgba(2, 132, 199, 0.15); color: #0284c7">
                            <i class="fas fa-file-pdf"></i>
                        </div>
                        <span class="font-bold text-slate-700 text-sm">Boletines y Reportes</span>
                    </Link>
                </div>
            </section>

            <!-- Warning if no active year -->
            <div v-if="!activeYear" class="animate-fade-in-up">
                <div class="glass-card rounded-2xl bg-amber-500/5 border border-amber-500/20 p-6 flex items-start gap-4">
                    <div class="w-12 h-12 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center text-xl flex-shrink-0">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div>
                        <h3 class="text-amber-700 font-bold">Atención: No hay un año escolar activo</h3>
                        <p class="text-slate-500 text-sm mt-1">Configure un año escolar activo desde el módulo de planificación para comenzar la gestión académica.</p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Components/Layout/AppLayout.vue'
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'

const page = usePage()
const user = computed(() => page.props.auth?.user)
const userRoles = computed(() => page.props.auth?.roles ?? [])

function can(permission) {
    if (userRoles.value.includes('SuperAdmin')) return true
    const perms = page.props.auth?.permissions ?? []
    return perms.includes(permission)
}

const props = defineProps({
    stats: { type: Object, default: () => ({}) },
    activeYear: { type: Object, default: null },
    gradeProgress: { type: Array, default: () => [] },
    todayAttendance: { type: Object, default: () => ({ present: 0, absent: 0, late: 0, excused: 0, total: 0 }) },
    recentActivity: { type: Array, default: () => [] },
    teacherLoads: { type: Array, default: () => [] },
    teacherStats: { type: Object, default: () => ({}) },
    secretaryStats: { type: Object, default: () => ({}) },
})

const isAdminOrSuperAdmin = computed(() => {
    return userRoles.value.includes('SuperAdmin') || userRoles.value.includes('Administrador')
})

const isDocenteOnly = computed(() => {
    return userRoles.value.includes('Docente') && !isAdminOrSuperAdmin.value
})

const primaryRoleName = computed(() => {
    if (userRoles.value.includes('SuperAdmin')) return 'Super Administrador'
    if (userRoles.value.includes('Administrador')) return 'Administrador'
    if (userRoles.value.includes('Docente')) return 'Portal Docente'
    if (userRoles.value.includes('Secretaria')) return 'Control de Estudios'
    return 'Usuario'
})

// Cards for Admin
const adminCards = computed(() => [
    {
        title: 'Estudiantes Activos',
        value: props.stats.total_students ?? 0,
        faIcon: 'fas fa-user-graduate',
        iconClass: 'stat-icon-primary',
        barGradient: 'from-primary-500 to-primary-500',
        badgeColor: 'text-primary-500',
        subtitle: 'Matrícula general'
    },
    {
        title: 'Inscritos en Año',
        value: props.stats.total_enrollments ?? 0,
        faIcon: 'fas fa-user-check',
        iconClass: 'stat-icon-success',
        barGradient: 'from-emerald-400 to-teal-500',
        badgeColor: 'text-emerald-500',
        subtitle: 'Año activo'
    },
    {
        title: 'Año Escolar',
        value: props.stats.school_year ?? '—',
        faIcon: 'fas fa-calendar-alt',
        iconClass: 'stat-icon-warning',
        barGradient: 'from-amber-400 to-orange-500',
        badgeColor: 'text-amber-500',
        subtitle: 'Período en curso'
    },
    {
        title: 'Lapsos Abiertos',
        value: props.stats.open_lapses ?? 0,
        faIcon: 'fas fa-door-open',
        iconClass: 'stat-icon-info',
        barGradient: 'from-cyan-400 to-sky-500',
        badgeColor: 'text-cyan-500',
        subtitle: 'Disponibles para carga'
    },
])

// Cards for Teacher
const teacherCards = computed(() => [
    {
        title: 'Mis Secciones',
        value: props.teacherStats.total_sections ?? 0,
        faIcon: 'fas fa-users-class',
        iconClass: 'stat-icon-primary',
        subtitle: 'Secciones asignadas'
    },
    {
        title: 'Mis Asignaturas',
        value: props.teacherStats.total_subjects ?? 0,
        faIcon: 'fas fa-book',
        iconClass: 'stat-icon-success',
        subtitle: 'Carga académica'
    },
    {
        title: 'Alumnos a Cargo',
        value: props.teacherStats.total_students_reach ?? 0,
        faIcon: 'fas fa-user-graduate',
        iconClass: 'stat-icon-warning',
        subtitle: 'Total estudiantes'
    },
    {
        title: 'Lapso Activo',
        value: props.teacherStats.open_lapse_name ?? '—',
        faIcon: 'fas fa-clock',
        iconClass: 'stat-icon-info',
        subtitle: 'Período de evaluación'
    },
])

// Cards for Secretary
const secretaryCards = computed(() => [
    {
        title: 'Estudiantes Activos',
        value: props.stats.total_students ?? 0,
        faIcon: 'fas fa-user-graduate',
        iconClass: 'stat-icon-primary',
        subtitle: 'Matrícula general'
    },
    {
        title: 'Inscripciones Activas',
        value: props.stats.total_enrollments ?? 0,
        faIcon: 'fas fa-id-card',
        iconClass: 'stat-icon-success',
        subtitle: 'Período en curso'
    },
    {
        title: 'Materias Pendientes',
        value: props.secretaryStats.pending_debts ?? 0,
        faIcon: 'fas fa-exclamation-circle',
        iconClass: 'stat-icon-warning',
        subtitle: 'Estudiantes en revisión'
    },
    {
        title: 'Año Escolar',
        value: props.stats.school_year ?? '—',
        faIcon: 'fas fa-calendar-alt',
        iconClass: 'stat-icon-info',
        subtitle: 'Período activo'
    },
])
</script>
