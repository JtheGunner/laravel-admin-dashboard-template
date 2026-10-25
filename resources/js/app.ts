import { createInertiaApp } from '@inertiajs/vue3';
import { createApp, h } from 'vue';
import { initializeTheme } from '@/composables/useAppearance';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { initializeFlashToast } from '@/lib/flashToast';
import { initializeInertiaErrorToasts } from '@/lib/inertiaErrors';
import { initializeRefreshOnHistoryBack } from '@/lib/refreshOnHistoryBack';
import { setDisplayTimeZone } from '@/lib/resourceCell';

createInertiaApp({
    title: (title, page) => {
        const appName = (page.props.name as string) || 'Laravel';

        return title ? `${title} - ${appName}` : appName;
    },
    layout: (name) => {
        switch (true) {
            case name === 'Welcome':
                return null;
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            default:
                return AppLayout;
        }
    },
    setup({ el, App, props, plugin }) {
        setDisplayTimeZone(
            props.initialPage.props.displayTimezone as string | undefined,
        );

        const app = createApp({ render: () => h(App, props) });
        app.use(plugin);

        // el can theoretically be null in the type signature; guard before mounting.
        if (el) {
            app.mount(el);
        }
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();

// This will listen for flash toast data from the server...
initializeFlashToast();

// This replaces Inertia's default full-screen error modal with a styled toast...
initializeInertiaErrorToasts();

// This refreshes the props of a page restored via the browser's back/forward buttons...
initializeRefreshOnHistoryBack();
