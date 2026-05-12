<template>
    <AppLayout title="Dashboard">
        <div class="space-y-8">
            <!-- Welcome -->
            <div class="animate-fade-in-up">
                <h1 class="text-3xl lg:text-4xl font-extrabold text-slate-800">
                    Bienvenido al <span class="gradient-text">SGE</span>
                </h1>
                <p class="text-slate-400 font-medium mt-2">Panel de control del Sistema de Gestión Escolar</p>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <div
                    v-for="(card, index) in cards"
                    :key="card.title"
                    class="glass-card rounded-3xl p-6 shadow-xl cursor-pointer relative overflow-hidden group hover:-translate-y-1 transition-all duration-300"
                    :class="`animate-fade-in-up`"
                    :style="{ animationDelay: `${(index + 1) * 100}ms` }"
                >
                    <!-- Top accent bar -->
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
                            <span>Actualizado</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <section class="glass-card rounded-3xl p-8 shadow-xl animate-fade-in-up" style="animation-delay: 500ms">
                <h3 class="text-xl font-bold text-slate-800 mb-6 flex items-center gap-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-amber-400 to-orange-500 rounded-xl flex items-center justify-center text-white shadow-lg shadow-amber-500/20">
                        <i class="fas fa-bolt"></i>
                    </div>
                    Acciones Rápidas
                </h3>
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <Link href="/admin/students" class="action-btn-hover p-6 border-2 border-dashed border-slate-100 rounded-2xl bg-white/50 flex flex-col items-center gap-4 text-center group transition-all">
                        <div class="w-16 h-16 bg-gradient-to-br from-primary-50 to-indigo-100 rounded-2xl flex items-center justify-center text-primary-600 text-3xl group-hover:scale-110 transition-transform">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <span class="font-bold text-slate-700 text-sm">Nuevo Estudiante</span>
                    </Link>

                    <Link href="/grades" class="action-btn-hover p-6 border-2 border-dashed border-slate-100 rounded-2xl bg-white/50 flex flex-col items-center gap-4 text-center group transition-all">
                        <div class="w-16 h-16 bg-gradient-to-br from-emerald-50 to-teal-100 rounded-2xl flex items-center justify-center text-emerald-600 text-3xl group-hover:scale-110 transition-transform">
                            <i class="fas fa-file-signature"></i>
                        </div>
                        <span class="font-bold text-slate-700 text-sm">Registrar Notas</span>
                    </Link>

                    <Link href="/attendance" class="action-btn-hover p-6 border-2 border-dashed border-slate-100 rounded-2xl bg-white/50 flex flex-col items-center gap-4 text-center group transition-all">
                        <div class="w-16 h-16 bg-gradient-to-br from-amber-50 to-orange-100 rounded-2xl flex items-center justify-center text-amber-600 text-3xl group-hover:scale-110 transition-transform">
                            <i class="fas fa-clipboard-check"></i>
                        </div>
                        <span class="font-bold text-slate-700 text-sm">Tomar Asistencia</span>
                    </Link>

                    <Link href="/reports" class="action-btn-hover p-6 border-2 border-dashed border-slate-100 rounded-2xl bg-white/50 flex flex-col items-center gap-4 text-center group transition-all">
                        <div class="w-16 h-16 bg-gradient-to-br from-pink-50 to-rose-100 rounded-2xl flex items-center justify-center text-pink-600 text-3xl group-hover:scale-110 transition-transform">
                            <i class="fas fa-file-pdf"></i>
                        </div>
                        <span class="font-bold text-slate-700 text-sm">Generar Reporte</span>
                    </Link>
                </div>
            </section>

            <!-- Active Year Warning -->
            <div v-if="!activeYear" class="animate-fade-in-up" style="animation-delay: 600ms">
                <div class="glass-card rounded-2xl bg-amber-500/5 border border-amber-500/20 p-6 flex items-start gap-4">
                    <div class="w-12 h-12 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center text-xl flex-shrink-0">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div>
                        <h3 class="text-amber-700 font-bold">Atención: No hay un año escolar activo</h3>
                        <p class="text-slate-500 text-sm mt-1">Configure un año escolar activo desde el módulo de administración para comenzar la gestión académica.</p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Components/Layout/AppLayout.vue'
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
    stats: { type: Object, default: () => ({}) },
    activeYear: { type: Object, default: null },
})

const cards = computed(() => [
    {
        title: 'Estudiantes Activos',
        value: props.stats.total_students ?? 0,
        faIcon: 'fas fa-user-graduate',
        iconClass: 'stat-icon-primary',
        barGradient: 'from-primary-500 to-indigo-500',
        badgeColor: 'text-primary-500'
    },
    {
        title: 'Inscritos',
        value: props.stats.total_enrollments ?? 0,
        faIcon: 'fas fa-user-check',
        iconClass: 'stat-icon-success',
        barGradient: 'from-emerald-400 to-teal-500',
        badgeColor: 'text-emerald-500'
    },
    {
        title: 'Año Escolar',
        value: props.stats.school_year ?? '—',
        faIcon: 'fas fa-calendar-alt',
        iconClass: 'stat-icon-warning',
        barGradient: 'from-amber-400 to-orange-500',
        badgeColor: 'text-amber-500'
    },
    {
        title: 'Lapsos Abiertos',
        value: props.stats.open_lapses ?? 0,
        faIcon: 'fas fa-door-open',
        iconClass: 'stat-icon-info',
        barGradient: 'from-cyan-400 to-sky-500',
        badgeColor: 'text-cyan-500'
    },
])
</script>

