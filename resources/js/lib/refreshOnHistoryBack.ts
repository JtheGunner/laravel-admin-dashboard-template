import { router } from '@inertiajs/vue3';

/**
 * True when the document itself was opened through the browser's back/forward
 * buttons (e.g. returning from an external site) — Inertia then also boots from
 * its stored history state instead of asking the server.
 */
function openedViaBackForward(): boolean {
    const entry = window.performance?.getEntriesByType('navigation')[0] as
        PerformanceNavigationTiming | undefined;

    return entry?.type === 'back_forward';
}

/**
 * Inertia restores a page from the browser's history state when the user
 * navigates back/forward, so the props (table rows, detail record, …) are the
 * ones captured when they left — stale as soon as anything changed in between.
 *
 * Stale-while-revalidate: the restored page still renders instantly, then the
 * current props are fetched in the background (state, scroll and dialogs are
 * preserved). Restoring from history is recognisable by the `navigate` event
 * carrying no `visitId`; regular visits and reloads always carry one.
 */
export function initializeRefreshOnHistoryBack(): void {
    let firstNavigation = true;

    router.on('navigate', (event) => {
        const restoredFromHistory =
            event.detail.visitId === undefined ||
            (firstNavigation && openedViaBackForward());

        firstNavigation = false;

        if (!restoredFromHistory) {
            return;
        }

        router.reload();
    });
}
