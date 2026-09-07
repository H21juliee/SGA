import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { registerSW } from 'virtual:pwa-register';
import '../css/app.css';

// Register Service Worker for PWA with automatic updates
registerSW({
    immediate: true,
    onNeedRefresh() {
        console.log('[SGA PWA] Nueva versión disponible.');
    },
    onOfflineReady() {
        console.log('[SGA PWA] Aplicación lista para operar sin conexión.');
    },
});

createInertiaApp({
    title: (title) => title ? `${title} — SGA` : 'SGA - Sistema de Gestión Académica',
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) })
            .use(plugin);

        app.mixin({
            methods: {
                $can(permission) {
                    const auth = this.$page.props.auth;
                    if (auth?.roles?.includes('SuperAdmin')) return true;
                    return auth?.permissions?.includes(permission) ?? false;
                }
            }
        });

        app.mount(el);
    },
    progress: {
        color: '#336b87',
        showSpinner: true,
    },
});
