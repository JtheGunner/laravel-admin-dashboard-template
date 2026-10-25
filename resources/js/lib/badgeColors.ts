/**
 * Curated badge colour palette. A record stores one of these
 * tokens (e.g. "emerald"); the token drives both the swatch in the colour picker
 * and the coloured badge shown across the app.
 *
 * The class strings are written out in full so Tailwind's scanner keeps them —
 * do not build them dynamically from the token.
 */
export interface BadgeColor {
    /** Stored token, e.g. "emerald". */
    value: string;
    /** English label shown as the swatch tooltip. */
    label: string;
    /** Solid swatch class for the picker. */
    swatch: string;
    /** Badge classes (tinted background + text + ring) for display. */
    badge: string;
}

export const BADGE_COLORS: BadgeColor[] = [
    {
        value: 'slate',
        label: 'Gray',
        swatch: 'bg-slate-500',
        badge: 'bg-slate-500/10 text-slate-700 ring-slate-500/20 dark:text-slate-300',
    },
    {
        value: 'red',
        label: 'Red',
        swatch: 'bg-red-500',
        badge: 'bg-red-500/10 text-red-700 ring-red-500/20 dark:text-red-400',
    },
    {
        value: 'orange',
        label: 'Orange',
        swatch: 'bg-orange-500',
        badge: 'bg-orange-500/10 text-orange-700 ring-orange-500/20 dark:text-orange-400',
    },
    {
        value: 'amber',
        label: 'Amber',
        swatch: 'bg-amber-500',
        badge: 'bg-amber-500/10 text-amber-700 ring-amber-500/20 dark:text-amber-400',
    },
    {
        value: 'yellow',
        label: 'Yellow',
        swatch: 'bg-yellow-500',
        badge: 'bg-yellow-500/10 text-yellow-700 ring-yellow-500/20 dark:text-yellow-400',
    },
    {
        value: 'lime',
        label: 'Lime',
        swatch: 'bg-lime-500',
        badge: 'bg-lime-500/10 text-lime-700 ring-lime-500/20 dark:text-lime-400',
    },
    {
        value: 'green',
        label: 'Green',
        swatch: 'bg-green-500',
        badge: 'bg-green-500/10 text-green-700 ring-green-500/20 dark:text-green-400',
    },
    {
        value: 'emerald',
        label: 'Emerald',
        swatch: 'bg-emerald-500',
        badge: 'bg-emerald-500/10 text-emerald-700 ring-emerald-500/20 dark:text-emerald-400',
    },
    {
        value: 'teal',
        label: 'Teal',
        swatch: 'bg-teal-500',
        badge: 'bg-teal-500/10 text-teal-700 ring-teal-500/20 dark:text-teal-400',
    },
    {
        value: 'cyan',
        label: 'Cyan',
        swatch: 'bg-cyan-500',
        badge: 'bg-cyan-500/10 text-cyan-700 ring-cyan-500/20 dark:text-cyan-400',
    },
    {
        value: 'sky',
        label: 'Sky',
        swatch: 'bg-sky-500',
        badge: 'bg-sky-500/10 text-sky-700 ring-sky-500/20 dark:text-sky-400',
    },
    {
        value: 'blue',
        label: 'Blue',
        swatch: 'bg-blue-500',
        badge: 'bg-blue-500/10 text-blue-700 ring-blue-500/20 dark:text-blue-400',
    },
    {
        value: 'indigo',
        label: 'Indigo',
        swatch: 'bg-indigo-500',
        badge: 'bg-indigo-500/10 text-indigo-700 ring-indigo-500/20 dark:text-indigo-400',
    },
    {
        value: 'violet',
        label: 'Violet',
        swatch: 'bg-violet-500',
        badge: 'bg-violet-500/10 text-violet-700 ring-violet-500/20 dark:text-violet-400',
    },
    {
        value: 'purple',
        label: 'Purple',
        swatch: 'bg-purple-500',
        badge: 'bg-purple-500/10 text-purple-700 ring-purple-500/20 dark:text-purple-400',
    },
    {
        value: 'fuchsia',
        label: 'Fuchsia',
        swatch: 'bg-fuchsia-500',
        badge: 'bg-fuchsia-500/10 text-fuchsia-700 ring-fuchsia-500/20 dark:text-fuchsia-400',
    },
    {
        value: 'pink',
        label: 'Pink',
        swatch: 'bg-pink-500',
        badge: 'bg-pink-500/10 text-pink-700 ring-pink-500/20 dark:text-pink-400',
    },
    {
        value: 'rose',
        label: 'Rose',
        swatch: 'bg-rose-500',
        badge: 'bg-rose-500/10 text-rose-700 ring-rose-500/20 dark:text-rose-400',
    },
];

/** Neutral fallback badge classes when no (or an unknown) colour is stored. */
export const DEFAULT_BADGE_CLASSES =
    'bg-muted text-muted-foreground ring-border';

const BADGE_COLOR_MAP: Record<string, BadgeColor> = Object.fromEntries(
    BADGE_COLORS.map((color) => [color.value, color]),
);

/** Badge classes for a stored colour token (falls back to a neutral style). */
export function badgeClasses(token: string | null | undefined): string {
    if (token && BADGE_COLOR_MAP[token]) {
        return BADGE_COLOR_MAP[token].badge;
    }

    return DEFAULT_BADGE_CLASSES;
}

/** Solid swatch background class for a token (for bars/dots); neutral fallback. */
export function swatchClasses(token: string | null | undefined): string {
    if (token && BADGE_COLOR_MAP[token]) {
        return BADGE_COLOR_MAP[token].swatch;
    }

    return 'bg-muted-foreground/40';
}
