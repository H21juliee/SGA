import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import '../css/app.css';

createInertiaApp({
    title: (title) => title ? `${title} — SGA` : 'SGA - Sistema de Gestión Académica',
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.vue', { eager: true });
        return pages[`./Pages/${name}.vue`];
    },
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
        color: '#6366f1',
        showSpinner: true,
    },
});
