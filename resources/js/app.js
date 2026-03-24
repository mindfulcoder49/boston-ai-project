import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy/dist/vue.m';
import { translations } from './translations';
import { createGtag } from 'vue-gtag'; // Import createGtag
import { isAnalyticsEnabledForCurrentRoute } from './Utils/analytics';

const appName = 'PublicDataWatch';

createInertiaApp({
    title: (title) => {
        if (!title) {
            return appName;
        }

        return title.includes(appName) ? title : `${title} | ${appName}`;
    },
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue, Ziggy)
            .provide('translations', translations)
        ;

        if (import.meta.env.VITE_GA_ID && isAnalyticsEnabledForCurrentRoute()) {
            const gtag = createGtag({
                tagId: import.meta.env.VITE_GA_ID,
            });
            app.use(gtag);
        }

        return app.mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
