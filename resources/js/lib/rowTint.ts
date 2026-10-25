import type { ResourceRow } from '@/types/resource';

/**
 * A semantic row-tint key. Pages pick from this fixed palette (instead of passing
 * raw Tailwind classes) so every tinted table across the app shares one look. A
 * page hands <ResourceIndex> a `(row) => RowTint | null` function; rows returning
 * `null`/`undefined` keep the default surface.
 */
export type RowTint =
    'amber' | 'rose' | 'red' | 'emerald' | 'green' | 'sky' | 'violet' | 'slate';

/** Maps a row to a tint, or to null/undefined to leave the row untinted. */
export type RowTintFn = (row: ResourceRow) => RowTint | null | undefined;

/**
 * Background tint for a full table row (`<tr>`). Kept subtle so the row text stays
 * legible. This sets `background-color`, so a generic grey hover such as
 * `hover:bg-muted/40` would replace it outright rather than layer on top — tinted
 * rows therefore pair this with {@link ROW_TINT_HOVER_CLASSES} instead (see
 * {@link rowTintRowClass}). The class strings are written out in full so
 * Tailwind's content scanner keeps every tint in the build.
 */
export const ROW_TINT_CLASSES: Record<RowTint, string> = {
    amber: 'bg-amber-100/60 dark:bg-amber-400/10',
    rose: 'bg-rose-100/60 dark:bg-rose-400/10',
    red: 'bg-red-100/60 dark:bg-red-400/10',
    emerald: 'bg-emerald-100/60 dark:bg-emerald-400/10',
    green: 'bg-green-100/60 dark:bg-green-400/10',
    sky: 'bg-sky-100/60 dark:bg-sky-400/10',
    violet: 'bg-violet-100/60 dark:bg-violet-400/10',
    slate: 'bg-slate-200/60 dark:bg-slate-400/10',
};

/**
 * Tint for the small-screen card layout: a soft background plus a coloured left
 * edge, so the same semantic marking reads on cards as on table rows. Includes the
 * background, so the card must drop its default `bg-card` when a tint is applied.
 */
export const ROW_TINT_CARD_CLASSES: Record<RowTint, string> = {
    amber: 'border-l-4 border-l-amber-400 bg-amber-50/70 dark:border-l-amber-400/60 dark:bg-amber-400/10',
    rose: 'border-l-4 border-l-rose-400 bg-rose-50/70 dark:border-l-rose-400/60 dark:bg-rose-400/10',
    red: 'border-l-4 border-l-red-400 bg-red-50/70 dark:border-l-red-400/60 dark:bg-red-400/10',
    emerald:
        'border-l-4 border-l-emerald-400 bg-emerald-50/70 dark:border-l-emerald-400/60 dark:bg-emerald-400/10',
    green: 'border-l-4 border-l-green-400 bg-green-50/70 dark:border-l-green-400/60 dark:bg-green-400/10',
    sky: 'border-l-4 border-l-sky-400 bg-sky-50/70 dark:border-l-sky-400/60 dark:bg-sky-400/10',
    violet: 'border-l-4 border-l-violet-400 bg-violet-50/70 dark:border-l-violet-400/60 dark:bg-violet-400/10',
    slate: 'border-l-4 border-l-slate-400 bg-slate-100/70 dark:border-l-slate-400/60 dark:bg-slate-400/10',
};

/**
 * Hover-state background for a tinted table row: a slightly deeper wash of the
 * *same* hue. Hovering a marked row then still reads as that colour (issue
 * ADMIN-6) instead of the generic grey `hover:bg-muted/40` painting the tint out,
 * while the deeper shade still gives a clear hover cue. Written out in full for
 * the Tailwind content scanner, same as {@link ROW_TINT_CLASSES}.
 */
export const ROW_TINT_HOVER_CLASSES: Record<RowTint, string> = {
    amber: 'hover:bg-amber-200/70 dark:hover:bg-amber-400/20',
    rose: 'hover:bg-rose-200/70 dark:hover:bg-rose-400/20',
    red: 'hover:bg-red-200/70 dark:hover:bg-red-400/20',
    emerald: 'hover:bg-emerald-200/70 dark:hover:bg-emerald-400/20',
    green: 'hover:bg-green-200/70 dark:hover:bg-green-400/20',
    sky: 'hover:bg-sky-200/70 dark:hover:bg-sky-400/20',
    violet: 'hover:bg-violet-200/70 dark:hover:bg-violet-400/20',
    slate: 'hover:bg-slate-300/70 dark:hover:bg-slate-400/20',
};

/**
 * Background + same-hue hover wash for a tinted `<tr>`. Shared by the selected
 * and unselected row-class builders so the tint surface is defined once.
 */
function tintSurfaceClass(tint: RowTint): string {
    return `${ROW_TINT_CLASSES[tint]} ${ROW_TINT_HOVER_CLASSES[tint]}`;
}

/**
 * Full set of `<tr>` background classes for an unselected row: the tint plus its
 * same-hue hover wash when the row is tinted, otherwise the generic grey hover.
 * Keeps the tint visible on hover instead of letting `hover:bg-muted/40` replace
 * it (issue ADMIN-6). The transparent left border matches the 4px bar the row
 * reserves so a selected row (see {@link selectedRowClass}) never shifts.
 */
export function rowTintRowClass(row: ResourceRow, fn?: RowTintFn): string {
    const tint = fn?.(row);
    const surface = tint ? tintSurfaceClass(tint) : 'hover:bg-muted/40';

    return `border-l-transparent ${surface}`;
}

/**
 * `<tr>` classes for a *selected* row. Keeps the row-tint (and its same-hue
 * hover) so the status colour stays readable, and adds an opaque left bar in the
 * brand primary as the unambiguous "selected" cue — the earlier half-transparent
 * `bg-accent/30` was swallowed by any tint behind it (issue ADMIN-10). Untinted
 * rows get a bluer `bg-primary/10` wash so selection also reads with no tint.
 * The 4px bar width is reserved by the row's static `border-l-4`; both this and
 * {@link rowTintRowClass} set only the bar's colour, so selecting shifts nothing.
 */
export function selectedRowClass(row: ResourceRow, fn?: RowTintFn): string {
    const tint = fn?.(row);
    const surface = tint
        ? tintSurfaceClass(tint)
        : 'bg-primary/10 hover:bg-primary/15';

    return `border-l-primary ${surface}`;
}
