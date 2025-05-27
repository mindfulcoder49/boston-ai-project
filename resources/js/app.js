import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy/dist/vue.m';
import { translations } from './translations';
import * as VueGtagModule from 'vue-gtag'; // Changed from default import to namespace import

const appName = 'BostonScope';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const vueApp = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue, Ziggy)
            .provide('translations', translations);

        if (import.meta.env.VITE_GA_ID) {
            // Use the .default property from the namespace import
            const VueGtagPlugin = VueGtagModule.default || VueGtagModule; 
            if (VueGtagPlugin) {
                vueApp.use(VueGtagPlugin, {
                    config: { id: import.meta.env.VITE_GA_ID }
                });
            } else {
                console.error('Failed to load VueGtag plugin. Default export might be missing or module structure is unexpected.');
            }
        }

        vueApp.mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
