import { toast } from 'vue-sonner';

export function xsrfToken(): string {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);

    return match ? decodeURIComponent(match[1]) : '';
}

export class ApiError extends Error {
    constructor(
        message: string,
        public status: number,
        public errors: Record<string, string[]> = {},
    ) {
        super(message);
        this.name = 'ApiError';
    }
}

/**
 * A lightweight wrapper around fetch() that automatically includes CSRF tokens,
 * accepts/sends JSON, and parses standard Laravel validation responses.
 */
export async function fetchApi<T = unknown>(
    url: string,
    options: RequestInit = {},
    showErrorToast = true,
): Promise<T> {
    const headers = new Headers(options.headers || {});

    if (!headers.has('Accept')) {
        headers.set('Accept', 'application/json');
    }

    if (!headers.has('X-Requested-With')) {
        headers.set('X-Requested-With', 'XMLHttpRequest');
    }

    if (
        options.method &&
        options.method.toUpperCase() !== 'GET' &&
        !headers.has('X-XSRF-TOKEN')
    ) {
        headers.set('X-XSRF-TOKEN', xsrfToken());
    }

    if (
        options.body &&
        typeof options.body === 'string' &&
        !headers.has('Content-Type')
    ) {
        headers.set('Content-Type', 'application/json');
    }

    if (options.credentials === undefined) {
        options.credentials = 'same-origin';
    }

    const response = await fetch(url, {
        ...options,
        headers,
    });

    const data = (await response.json().catch(() => ({}))) as Record<
        string,
        unknown
    >;

    if (!response.ok) {
        let firstValidationError = null;
        const errors = (data.errors as Record<string, string[]>) || {};

        if (response.status === 422 && Object.keys(errors).length > 0) {
            const firstKey = Object.keys(errors)[0];

            if (firstKey && errors[firstKey]?.[0]) {
                firstValidationError = errors[firstKey][0];
            }
        }

        const message =
            (data.message as string | undefined) ??
            firstValidationError ??
            `Error ${response.status}`;

        if (showErrorToast) {
            toast.error(message);
        }

        throw new ApiError(message, response.status, errors);
    }

    return data as T;
}
