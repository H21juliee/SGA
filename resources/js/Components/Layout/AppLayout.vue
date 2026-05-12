<template>
    <div class="min-h-screen bg-mesh font-sans antialiased text-slate-800">
        <!-- Sidebar Overlay (Mobile) -->
        <div 
            v-if="isSidebarOpen" 
            @click="isSidebarOpen = false"
            class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-40 lg:hidden transition-opacity duration-300"
        ></div>

        <!-- Sidebar -->
        <aside 
            class="glass-sidebar fixed top-0 left-0 bottom-0 w-72 h-full z-50 transition-transform duration-300 transform shadow-2xl border-r border-white/20 flex flex-col"
            :class="isSidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
        >
            <div class="flex flex-col h-full p-6">
                <!-- Logo -->
                <div class="flex items-center gap-4 mb-10 px-2">
                    <div class="w-12 h-12 bg-gradient-to-br from-primary-500 to-primary-700 rounded-2xl flex items-center justify-center text-white text-2xl shadow-lg shadow-primary-500/30">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-black gradient-text tracking-tight leading-none">SGE</h1>
                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-1">Gestión Escolar</p>
                    </div>
                </div>

                <!-- Nav -->
                <nav class="flex-1 space-y-1.5 overflow-y-auto custom-scrollbar pr-2">
                    <template v-for="item in navigation" :key="item.name">
                        <!-- Standard Link -->
                        <div v-if="!item.children">
                            <Link
                                :href="item.href"
                                @click="isSidebarOpen = false"
                                class="flex items-center gap-3 px-5 py-3 rounded-xl font-medium transition-all duration-300"
                                :class="$page.url.startsWith(item.href)
                                    ? 'nav-link-active'
                                    : 'text-slate-500 hover:bg-primary-50 hover:text-primary-600 hover:translate-x-1'"
                            >
                                <i :class="getIcon(item.icon)" class="w-6 text-center"></i>
                                <span>{{ item.name }}</span>
                            </Link>
                        </div>

                        <!-- Dropdown -->
                        <div v-else class="space-y-1">
                            <button
                                @click="toggleMenu(item.name)"
                                class="w-full flex items-center justify-between gap-3 px-5 py-3 rounded-xl text-slate-500 font-medium hover:bg-primary-50 hover:text-primary-600 transition-all duration-300"
                            >
                                <div class="flex items-center gap-3">
                                    <i :class="getIcon(item.icon)" class="w-6 text-center"></i>
                                    <span>{{ item.name }}</span>
                                </div>
                                <i class="fas fa-chevron-down text-xs transition-transform duration-300" :class="openMenu === name ? 'rotate-180' : ''"></i>
                            </button>
                            
                            <div v-show="openMenu === item.name" class="ml-9 space-y-1 border-l-2 border-primary-100 pl-4 py-1 animate-slide-in">
                                <Link
                                    v-for="child in item.children"
                                    :key="child.name"
                                    :href="child.href"
                                    @click="isSidebarOpen = false"
                                    class="block px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200"
                                    :class="$page.url.startsWith(child.href)
                                        ? 'text-primary-600 bg-primary-50 font-bold'
                                        : 'text-slate-400 hover:text-slate-600 hover:bg-slate-50'"
                                >
                                    {{ child.name }}
                                </Link>
                            </div>
                        </div>
                    </template>
                </nav>

                <!-- User Profile -->
                <div class="mt-auto border-t border-slate-100 pt-6">
                    <div class="bg-gradient-to-br from-primary-50/50 to-purple-50/50 border border-primary-100 rounded-2xl p-4 flex items-center gap-3 shadow-sm hover:shadow-md transition-all duration-300 group">
                        <div class="w-11 h-11 bg-gradient-to-br from-primary-500 to-accent-500 rounded-full flex items-center justify-center text-white font-bold text-lg shadow-md group-hover:scale-105 transition-transform">
                            {{ user?.name?.charAt(0) ?? '?' }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <h4 class="text-sm font-bold text-slate-800 truncate">{{ user?.name }}</h4>
                            <p class="text-[10px] text-slate-400 font-semibold uppercase tracking-wider">{{ roles[0] ?? '' }}</p>
                        </div>
                        <button @click="logout" class="p-2 text-slate-300 hover:text-red-500 transition-colors" title="Cerrar sesión">
                            <i class="fas fa-sign-out-alt"></i>
                        </button>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex flex-col min-h-screen lg:ml-72 transition-all duration-300">
            <!-- Header -->
            <header class="sticky top-4 z-40 mx-4 lg:mx-8 mt-4 glass-card rounded-2xl p-4 lg:p-6 shadow-xl animate-fade-in-up">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <button @click="isSidebarOpen = true" class="lg:hidden w-10 h-10 rounded-xl bg-primary-50 text-primary-600 flex items-center justify-center hover:bg-primary-100 transition-colors">
                            <i class="fas fa-bars"></i>
                        </button>
                        <div>
                            <h2 class="text-lg lg:text-xl font-extrabold text-slate-800 leading-none">{{ title }}</h2>
                            <p class="text-[11px] text-slate-400 font-medium mt-1 hidden sm:block">Sistema de Gestión Escolar</p>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-3">
                        <button class="w-10 h-10 rounded-xl bg-white text-slate-400 flex items-center justify-center shadow-sm border border-slate-100 hover:border-primary-300 hover:text-primary-500 transition-all">
                            <i class="fas fa-bell"></i>
                        </button>
                        <div class="h-8 w-[1px] bg-slate-200 mx-1 hidden sm:block"></div>
                        <div class="hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-xl bg-slate-50 border border-slate-100">
                            <div class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></div>
                            <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Sistema Online</span>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Content Area -->
            <main class="flex-1 p-4 lg:p-8">
                <!-- Flash Messages -->
                <div v-if="flash.success" class="mb-6 animate-fade-in-up">
                    <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-100 text-emerald-700 text-sm flex items-center gap-3 shadow-sm">
                        <div class="w-8 h-8 rounded-lg bg-emerald-500 text-white flex items-center justify-center shadow-md shadow-emerald-500/20">
                            <i class="fas fa-check text-xs"></i>
                        </div>
                        <span class="font-medium">{{ flash.success }}</span>
                    </div>
                </div>
                
                <div v-if="flash.error" class="mb-6 animate-fade-in-up">
                    <div class="p-4 rounded-2xl bg-red-50 border border-red-100 text-red-700 text-sm flex items-center gap-3 shadow-sm">
                        <div class="w-8 h-8 rounded-lg bg-red-500 text-white flex items-center justify-center shadow-md shadow-red-500/20">
                            <i class="fas fa-exclamation-triangle text-xs"></i>
                        </div>
                        <span class="font-medium">{{ flash.error }}</span>
                    </div>
                </div>

                <!-- Page Content -->
                <div class="animate-fade-in-up-delay-1">
                    <slot />
                </div>
            </main>
        </div>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { usePage, router, Link } from '@inertiajs/vue3'

const page = usePage()
const user = computed(() => page.props.auth?.user)
const roles = computed(() => page.props.auth?.roles ?? [])

const props = defineProps({
    title: { type: String, default: '' },
})

const navigation = computed(() => {
    return [
        { name: 'Dashboard', href: '/dashboard', icon: 'dashboard', show: true },
        { name: 'Notas', href: '/grades', icon: 'grades', show: can('grades.view') },
        { name: 'Asistencia', href: '/attendance', icon: 'attendance', show: can('attendance.view') },
        { name: 'Reportes', href: '/reports', icon: 'reports', show: can('reports.generate') },
        {
            name: 'Administración',
            icon: 'admin',
            show: roles.value.some(r => ['SuperAdmin', 'Administrador', 'Secretaria'].includes(r)),
            children: [
                { name: 'Estudiantes', href: '/admin/students', show: can('students.view') },
                { name: 'Años Escolares', href: '/admin/school-years', show: can('school_years.view') },
                { name: 'Secciones', href: '/admin/sections', show: can('sections.manage') },
                { name: 'Materias', href: '/admin/subjects', show: can('subjects.manage') },
                { name: 'Carga Académica', href: '/admin/academic-loads', show: can('academic_load.view') },
                { name: 'Inscripciones', href: '/admin/enrollments', show: can('academic_load.view') },
            ]
        }
    ].filter(i => i.show)
})

function can(permission) {
    if (roles.value.includes('SuperAdmin')) return true
    const perms = page.props.auth?.permissions ?? []
    return perms.includes(permission)
}

function getIcon(name) {
    const icons = {
        'dashboard': 'fas fa-th-large',
        'grades': 'fas fa-book',
        'attendance': 'fas fa-check-circle',
        'reports': 'fas fa-chart-bar',
        'admin': 'fas fa-cog',
        'students': 'fas fa-users'
    }
    return icons[name] || 'fas fa-circle'
}

function logout() {
    router.post('/logout')
}

const flash = computed(() => page.props.flash ?? {})
const isSidebarOpen = ref(false)
const openMenu = ref(null)

function toggleSidebar() {
    isSidebarOpen.value = !isSidebarOpen.value
}

function toggleMenu(name) {
    openMenu.value = openMenu.value === name ? null : name
}
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(99, 102, 241, 0.1);
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(99, 102, 241, 0.2);
}
</style>
