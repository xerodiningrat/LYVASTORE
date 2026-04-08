import '../css/app.css';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import GlobalPageLoader from './components/GlobalPageLoader.vue';
import { initializeTheme } from './composables/useAppearance';

const brandName = 'LYVA INDONESIA';
const configuredAppName = `${import.meta.env.VITE_APP_NAME || ''}`.trim();

const resolveDocumentTitle = (title: string) => {
    const pageTitle = title.trim();

    if (!pageTitle) {
        return brandName;
    }

    const normalizedPageTitle = pageTitle.toLowerCase();
    const normalizedConfiguredAppName = configuredAppName.toLowerCase();
    const normalizedBrandName = brandName.toLowerCase();

    if (normalizedPageTitle === normalizedConfiguredAppName || normalizedPageTitle === normalizedBrandName) {
        return brandName;
    }

    return `${brandName} | ${pageTitle}`;
};

createInertiaApp({
    title: resolveDocumentTitle,
    resolve: (name) => resolvePageComponent(`./pages/${name}.vue`, import.meta.glob<DefineComponent>('./pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        createApp({
            render: () =>
                h(GlobalPageLoader, null, {
                    default: () => h(App, props),
                }),
        })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: false,
});

// This will set light / dark mode on page load...
initializeTheme();

if (typeof window !== 'undefined' && 'serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        let reloadedForNewServiceWorker = false;

        navigator.serviceWorker
            .register('/sw.js')
            .then((registration) => {
                registration.update().catch(() => {
                    // Abaikan kalau browser belum bisa cek update saat ini.
                });

                navigator.serviceWorker.addEventListener('controllerchange', () => {
                    if (reloadedForNewServiceWorker) {
                        return;
                    }

                    reloadedForNewServiceWorker = true;
                    window.location.reload();
                });
            })
            .catch(() => {
                // Tetap aman kalau service worker gagal diregistrasi.
            });
    });
}
