import type { InertiaLinkProps } from '@inertiajs/vue3';
import type { ClassValue } from 'clsx';
import { clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function toUrl(href: NonNullable<InertiaLinkProps['href']>) {
    return typeof href === 'string' ? href : href?.url;
}

/**
 * Common top-level domains used to decide whether a bare (scheme-less) string
 * like "example.ch" is a linkable domain. Kept deliberately small so that
 * dotted non-URLs — product names such as "Vue.js"/"Node.js", file names like
 * "logo.png", version numbers "1.2" — are not mistaken for links.
 */
const LINKABLE_TLDS = new Set<string>([
    'com',
    'net',
    'org',
    'io',
    'dev',
    'app',
    'ai',
    'co',
    'info',
    'biz',
    'me',
    'xyz',
    'tech',
    'cloud',
    'online',
    'site',
    'shop',
    'digital',
    'gmbh',
    'eu',
    'ch',
    'de',
    'at',
    'li',
    'fr',
    'it',
    'uk',
    'us',
    'nl',
    'es',
    'pl',
    'se',
    'no',
    'dk',
    'fi',
    'be',
    'ca',
]);

/**
 * Matches an optional scheme, one or more domain labels (ASCII or Unicode IDN),
 * a TLD, and an optional path. The /u flag enables \p{L}/\p{N} Unicode categories.
 */
const URL_PATTERN =
    /^(https?:\/\/)?((?:[\p{L}\p{N}](?:[\p{L}\p{N}-]*[\p{L}\p{N}])?\.)+([a-z]{2,}))(?:[/?#]\S*)?$/iu;

/**
 * Returns true when the value looks like a linkable URL or domain — used to
 * auto-render table cell values as clickable links. Strings carrying an
 * explicit http(s) scheme or a "www." prefix always count; bare domains only
 * count when their TLD is in {@link LINKABLE_TLDS}, to avoid false positives on
 * dotted text (e.g. "Vue.js", "logo.png").
 */
export function isUrl(value: unknown): value is string {
    if (typeof value !== 'string') {
        return false;
    }

    const trimmed = value.trim();
    const match = URL_PATTERN.exec(trimmed);

    if (!match) {
        return false;
    }

    // Explicit scheme or "www." prefix → trust it regardless of the TLD.
    if (match[1] || /^www\./i.test(trimmed)) {
        return true;
    }

    return LINKABLE_TLDS.has(match[3].toLowerCase());
}

/**
 * Normalizes a linkable value into an absolute href, prepending "https://"
 * when the value carries no scheme (e.g. "example.ch" → "https://firma.ch").
 */
export function externalHref(value: string): string {
    const trimmed = value.trim();

    return /^https?:\/\//i.test(trimmed) ? trimmed : `https://${trimmed}`;
}

// ── Punycode / IDNA display decoder ─────────────────────────────────────────
// RFC 3492 §6.2 — decodes a single Punycode-encoded ACE label ("xn--…") back
// to its Unicode form for human-readable display. The href always keeps the
// original (ASCII-compatible) form so browsers resolve it correctly.

function punycodeAdapt(
    delta: number,
    numPoints: number,
    firstTime: boolean,
): number {
    let d = firstTime ? Math.floor(delta / 700) : delta >> 1;
    d += Math.floor(d / numPoints);
    let k = 0;

    while (d > (35 * 26) >> 1) {
        d = Math.floor(d / 35);
        k += 36;
    }

    return k + Math.floor((36 * d) / (d + 38));
}

function decodePunycodeLabel(label: string): string {
    if (!label.toLowerCase().startsWith('xn--')) {
        return label;
    }

    const input = label.slice(4);
    const output: number[] = [];
    const delim = input.lastIndexOf('-');

    if (delim > 0) {
        for (let j = 0; j < delim; j++) {
            output.push(input.charCodeAt(j));
        }
    }

    let n = 128;
    let i = 0;
    let bias = 72;
    let inp = delim > 0 ? delim + 1 : 0;

    while (inp < input.length) {
        const oldi = i;
        let w = 1;

        for (let k = 36; ; k += 36) {
            if (inp >= input.length) {
                return label;
            }

            const c = input.charCodeAt(inp++);
            const digit =
                c >= 48 && c <= 57
                    ? c - 22
                    : c >= 65 && c <= 90
                      ? c - 65
                      : c >= 97 && c <= 122
                        ? c - 97
                        : 36;

            if (digit >= 36) {
                return label;
            }

            i += digit * w;
            const t = k <= bias ? 1 : k >= bias + 26 ? 26 : k - bias;

            if (digit < t) {
                break;
            }

            w *= 36 - t;
        }

        const len = output.length + 1;
        bias = punycodeAdapt(i - oldi, len, oldi === 0);
        n += Math.floor(i / len);
        i = i % len;
        output.splice(i, 0, n);
        i++;
    }

    try {
        return String.fromCodePoint(...output);
    } catch {
        return label;
    }
}

/**
 * Returns a human-readable representation of a URL for display purposes.
 * Punycode-encoded hostname labels (xn--…) are decoded back to their Unicode
 * form (e.g. "xn--bcher-kva.ch" → "bücher.ch"). The href of any link should
 * still reference the original ASCII-compatible value.
 */
export function displayUrl(url: string): string {
    if (!url.includes('xn--')) {
        return url;
    }

    try {
        const withScheme = /^https?:\/\//i.test(url) ? url : `https://${url}`;
        const parsed = new URL(withScheme);
        const decodedHost = parsed.hostname
            .split('.')
            .map(decodePunycodeLabel)
            .join('.');

        if (decodedHost === parsed.hostname) {
            return url;
        }

        return url.replace(parsed.hostname, decodedHost);
    } catch {
        return url;
    }
}

/** Visually select (highlight) an element's text contents, e.g. after copying it. */
export function selectElementContents(el: HTMLElement | null): void {
    if (!el || typeof window === 'undefined') {
        return;
    }

    const selection = window.getSelection();

    if (!selection) {
        return;
    }

    const range = document.createRange();
    range.selectNodeContents(el);
    selection.removeAllRanges();
    selection.addRange(range);
}
