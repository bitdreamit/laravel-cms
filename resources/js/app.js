import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

const pages = import.meta.glob('../pages/**/*.vue', { eager: true });

createInertiaApp({
    resolve: (name) => {
        const path = `../pages/${name}.vue`;
        return pages[path] ?? pages[`../pages/admin/${name}.vue`];
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
});
