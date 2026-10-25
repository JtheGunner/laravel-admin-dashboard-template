import { ref } from 'vue';

type Variant = 'info' | 'warning' | 'error' | 'success';

export interface ConfirmOptions {
    title: string;
    message?: string;
    variant?: Variant;
    confirmLabel?: string;
    cancelLabel?: string;
}

interface ConfirmState extends ConfirmOptions {
    resolve: (value: boolean) => void;
}

// Singleton state – shared across the whole app via AppShell
const state = ref<ConfirmState | null>(null);

/**
 * Composable to show a confirmation dialog imperatively.
 *
 * @example
 * const confirm = useConfirm()
 * const ok = await confirm({ title: 'Are you sure?', variant: 'warning' })
 * if (ok) { ... }
 *
 * Requires <AppConfirmDialogHost /> to be mounted once (e.g. in AppShell).
 */
export function useConfirm() {
    return function confirm(options: ConfirmOptions): Promise<boolean> {
        return new Promise((resolve) => {
            state.value = { ...options, resolve };
        });
    };
}

/**
 * Internal hook used by AppConfirmDialogHost to access and drive the shared state.
 */
export function useConfirmState() {
    return {
        state,
        confirm() {
            state.value?.resolve(true);
            state.value = null;
        },
        cancel() {
            state.value?.resolve(false);
            state.value = null;
        },
    };
}
