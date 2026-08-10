import './bootstrap';
import '../css/app.css';
import 'vue-sonner/style.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import autoGlobalInject from './Plugins/autoGlobalInject';
import { ensureTranslations } from './Translations/translationStore';
import translationManifest from 'virtual:translation-manifest';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';
const pages = import.meta.glob('./Pages/**/*.vue');

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: async (name) => {
        const component = await resolvePageComponent(`./Pages/${name}.vue`, pages);
        const fallbackNamespace = name.split('/').pop();

        await ensureTranslations(translationManifest[name] ?? [fallbackNamespace]).catch(() => {});

        return component;
    },
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(autoGlobalInject)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
