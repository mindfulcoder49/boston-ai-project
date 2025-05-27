import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp, router } from '@inertiajs/vue3'; // Import router
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy/dist/vue.m';
import { translations } from './translations';
import * as VueGtagModule from 'vue-gtag'; // Changed import style

const appName = 'BostonScope'; // Or use: import.meta.env.VITE_APP_NAME || 'BostonScope';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const vueApp = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue, Ziggy)
            .provide('translations', translations);

        if (import.meta.env.VITE_GA_ID) {
            // Access the plugin via .default and event via .event from the namespace
            const VueGtag = VueGtagModule.default;
            const eventFunction = VueGtagModule.event;

            vueApp.use(VueGtag, {
                config: { id: import.meta.env.VITE_GA_ID }
            });

            // Track SPA navigations
            router.on('finish', () => {
                if (props.initialPage && props.initialPage.url) {
                    eventFunction('page_view', { // Use the extracted event function
                        page_path: new URL(props.initialPage.url, window.location.origin).pathname,
                        page_location: props.initialPage.url,
                        page_title: document.title,
                    });
                }
            });
        }

        vueApp.mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
