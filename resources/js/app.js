import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { i18nVue } from 'laravel-vue-i18n';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import PrimeVue from 'primevue/config';
import ConfirmationService from 'primevue/confirmationservice';
import ToastService from 'primevue/toastservice';
import Aura from '@primeuix/themes/aura';
import 'primeicons/primeicons.css';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

/**
 * A frontend hibalog payloadba kerülő metaadatot biztonságosan stringgé alakítja.
 */
const stringifyMetadata = (value) => {
    try {
        return typeof value === 'object' ? JSON.stringify(value) : String(value);
    } catch {
        return 'Unserializable metadata';
    }
};

/**
 * A globális frontend hibakezelők által összegyűjtött hibát továbbítja a backend felé.
 */
const sendFrontendError = async (payload) => {
    try {
        await window.axios.post(route('frontend-errors.store'), payload);
    } catch {
        // Ha maga a logolási kérés bukik el, nem indítunk újabb kliensoldali hibalogot.
    }
};

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });

        app.config.errorHandler = (error, instance, info) => {
            void sendFrontendError({
                type: 'vue-error',
                message: error?.message ?? 'Vue error',
                component: instance?.$options?.name ?? null,
                stack: error?.stack ?? null,
                metadata: {
                    info,
                },
                url: window.location.href,
            });
        };

        window.addEventListener('error', (event) => {
            void sendFrontendError({
                type: 'window-error',
                message: event.message,
                stack: event.error?.stack ?? null,
                metadata: {
                    filename: event.filename,
                    lineno: event.lineno,
                    colno: event.colno,
                },
                url: window.location.href,
            });
        });

        window.addEventListener('unhandledrejection', (event) => {
            const reason = event.reason;

            void sendFrontendError({
                type: 'unhandled-rejection',
                message: reason?.message ?? String(reason),
                stack: reason?.stack ?? null,
                metadata: {
                    reason: stringifyMetadata(reason),
                },
                url: window.location.href,
            });
        });

        return app
            .use(plugin)
            .use(i18nVue, {
                resolve: async (lang) => {
                    const langs = import.meta.glob('../../lang/*.json');
                    const language = langs[`../../lang/${lang}.json`];

                    if (language) {
                        return await language();
                    }

                    return await langs['../../lang/en.json']();
                },
            })
            .use(PrimeVue, {
                theme: {
                    preset: Aura,
                },
            })
            .use(ConfirmationService)
            .use(ToastService)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
