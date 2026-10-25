import { router } from '@inertiajs/vue3';
import type { ComputedRef, Ref } from 'vue';
import { computed, onMounted, ref } from 'vue';
import { update as updateAppearanceRoute } from '@/routes/appearance';
import type { Appearance, ResolvedAppearance } from '@/types';

export type { Appearance, ResolvedAppearance };

// Module-level so every consumer (the settings tabs, the profile select and the
// header toggle) shares one reactive source of truth for the active appearance.
const appearance = ref<Appearance>('light');

export type UseAppearanceReturn = {
    appearance: Ref<Appearance>;
    resolvedAppearance: ComputedRef<ResolvedAppearance>;
    updateAppearance: (value: Appearance, persist?: boolean) => void;
};

export function updateTheme(value: Appearance): void {
    if (typeof window === 'undefined') {
        return;
    }

    if (value === 'system') {
        const mediaQueryList = window.matchMedia(
            '(prefers-color-scheme: dark)',
        );
        const systemTheme = mediaQueryList.matches ? 'dark' : 'light';

        document.documentElement.classList.toggle(
            'dark',
            systemTheme === 'dark',
        );
    } else {
        document.documentElement.classList.toggle('dark', value === 'dark');
    }
}

const setCookie = (name: string, value: string, days = 365) => {
    if (typeof document === 'undefined') {
        return;
    }

    const maxAge = days * 24 * 60 * 60;

    document.cookie = `${name}=${value};path=/;max-age=${maxAge};SameSite=Lax`;
};

const mediaQuery = () => {
    if (typeof window === 'undefined') {
        return null;
    }

    return window.matchMedia('(prefers-color-scheme: dark)');
};

const getStoredAppearance = () => {
    if (typeof window === 'undefined') {
        return null;
    }

    return localStorage.getItem('appearance') as Appearance | null;
};

const prefersDark = (): boolean => {
    if (typeof window === 'undefined') {
        return false;
    }

    return window.matchMedia('(prefers-color-scheme: dark)').matches;
};

const handleSystemThemeChange = () => {
    const currentAppearance = getStoredAppearance();

    updateTheme(currentAppearance || 'light');
};

/**
 * Persists the given appearance to the authenticated user's DB record. The
 * client works with 'system'; the server maps it to the 'auto' column value.
 * Sent as a background partial visit so toggling the theme never reloads the
 * page or its data.
 */
function persistAppearance(value: Appearance): void {
    router.patch(
        updateAppearanceRoute().url,
        { appearance: value },
        { preserveScroll: true, preserveState: true, only: [] },
    );
}

/**
 * Reads the user's dark_mode value from the DB (via Inertia shared props) and
 * applies it as the active appearance. The DB values are 'light', 'dark', 'auto';
 * 'auto' maps to 'system' for the Appearance type used client-side.
 * Call this whenever the server-side value should take precedence.
 */
export function applyServerAppearance(dbMode: string): void {
    const value: Appearance =
        dbMode === 'auto' ? 'system' : (dbMode as Appearance);

    appearance.value = value;
    localStorage.setItem('appearance', value);
    setCookie('appearance', value);
    updateTheme(value);
}

export function initializeTheme(): void {
    if (typeof window === 'undefined') {
        return;
    }

    // On initial load the Inertia page data is embedded in data-page on #app.
    // Read dark_mode from there so the DB is the source of truth.
    try {
        const el = document.getElementById('app');

        if (el?.dataset.page) {
            const page = JSON.parse(el.dataset.page) as {
                props?: { auth?: { user?: { dark_mode?: string } } };
            };
            const dbMode = page.props?.auth?.user?.dark_mode;

            if (dbMode) {
                applyServerAppearance(dbMode);
                mediaQuery()?.addEventListener(
                    'change',
                    handleSystemThemeChange,
                );

                return;
            }
        }
    } catch {
        // fall through to localStorage fallback
    }

    // Fallback for unauthenticated pages or parse errors. Light is the default
    // when no explicit preference is stored (rather than following the OS).
    const savedAppearance = getStoredAppearance();
    updateTheme(savedAppearance || 'light');
    mediaQuery()?.addEventListener('change', handleSystemThemeChange);
}

export function useAppearance(): UseAppearanceReturn {
    onMounted(() => {
        const savedAppearance = localStorage.getItem(
            'appearance',
        ) as Appearance | null;

        if (savedAppearance) {
            appearance.value = savedAppearance;
        }
    });

    const resolvedAppearance = computed<ResolvedAppearance>(() => {
        if (appearance.value === 'system') {
            return prefersDark() ? 'dark' : 'light';
        }

        return appearance.value;
    });

    function updateAppearance(value: Appearance, persist = true) {
        appearance.value = value;

        // Store in localStorage for client-side persistence...
        localStorage.setItem('appearance', value);

        // Store in cookie for SSR...
        setCookie('appearance', value);

        updateTheme(value);

        // Persist to the DB so the preference survives across devices/sessions.
        // Skipped when a caller already saved the value server-side (e.g. the
        // profile form) to avoid a redundant write.
        if (persist) {
            persistAppearance(value);
        }
    }

    return {
        appearance,
        resolvedAppearance,
        updateAppearance,
    };
}
