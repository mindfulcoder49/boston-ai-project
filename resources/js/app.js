import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp, router } from '@inertiajs/vue3'; // Import router
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy/dist/vue.m';
import { translations } from './translations';
import * as VueGtagModule from 'vue-gtag'; // Keep using namespace import for stability

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
            const VueGtag = VueGtagModule.default; // Access default export for the plugin
            const gtagEvent = VueGtagModule.event; // Access named export 'event'

            if (VueGtag && typeof gtagEvent === 'function') {
                vueApp.use(VueGtag, {
                    config: { id: import.meta.env.VITE_GA_ID }
                });

                // Track SPA navigations
                router.on('finish', () => {
                    // Ensure props.initialPage.url is the new URL after navigation
                    const currentPath = window.location.pathname;
                    const currentUrl = window.location.href;
                    const currentTitle = document.title;

                    gtagEvent('page_view', {
                        page_path: currentPath,
                        page_location: currentUrl,
                        page_title: currentTitle,
                    });
                });
            } else {
                console.error('Failed to initialize VueGtag or access event function.');
            }
        }

        vueApp.mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
