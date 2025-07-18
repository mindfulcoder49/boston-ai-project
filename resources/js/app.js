import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy/dist/vue.m';
import { translations } from './translations';
import { createGtag } from 'vue-gtag'; // Import createGtag

const appName = 'BostonScope'

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const gtag = createGtag({ // Initialize gtag
            tagId: import.meta.env.VITE_GA_ID, // Ensure VITE_GA_ID is in your .env
        });
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue, Ziggy)
            .use(gtag) // Use the gtag plugin
            .provide('translations', translations)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
