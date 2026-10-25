import { router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';

/**
 * A friendly message for a failed request, chosen by HTTP status. Anything
 * without a specific case falls back to a generic "something went wrong".
 */
function messageForStatus(status: number | undefined): string {
    switch (status) {
        case 419:
            return 'Your session has expired. Please reload the page and try again.';
        case 401:
        case 403:
            return 'You do not have permission to do that.';
        case 404:
            return 'Not found – the entry no longer exists.';
        case 429:
            return 'Too many requests. Please wait a moment and try again.';
        default:
            return 'Something went wrong. Please try again.';
    }
}

/**
 * Replace Inertia's default full-screen error modal (which renders the raw
 * server error page — Ignition in dev, a bare 500 page in prod) with a styled
 * toast. Covers two cases:
 *
 * - `httpException`: the server returned a non-2xx, non-Inertia response (a 500
 *   HTML page, a 419 session-expired page, …). We swallow the default modal
 *   (preventDefault) and toast instead.
 * - `networkError`: the request never completed (offline, request aborted, …).
 *
 * Validation errors (422) are regular Inertia responses handled by the forms and
 * never reach here, so this only surfaces genuine failures.
 */
export function initializeInertiaErrorToasts(): void {
    router.on('httpException', (event) => {
        event.preventDefault();
        toast.error(messageForStatus(event.detail.response.status));
    });

    router.on('networkError', (event) => {
        event.preventDefault();
        toast.error('Network error – the request could not be completed.');
    });
}
