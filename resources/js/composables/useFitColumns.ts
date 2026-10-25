import { useResizeObserver } from '@vueuse/core';
import type { ComputedRef, MaybeRefOrGetter, Ref } from 'vue';
import {
    computed,
    nextTick,
    onBeforeUnmount,
    onMounted,
    readonly,
    ref,
    toValue,
    watch,
} from 'vue';
import { isGrowColumn } from '@/lib/resourceCell';
import type { ColumnConfig } from '@/types/resource';

/** The slice of `useVisibleColumns` the fit loop drives. */
export interface FitColumnsApi {
    /** Recomputes only when the eligible-ignoring-fit set changes. */
    baseEligibleKeys: ComputedRef<string[]>;
    lowestDroppableKey: () => string | null;
    highestHiddenKey: () => string | null;
    setFitHidden: (key: string, hidden: boolean) => void;
    resetFit: () => void;
}

export interface FitColumnsOptions {
    /** True while the flat table is the rendered layout (runs the column fit). */
    tableMode: MaybeRefOrGetter<boolean>;
    /** True while the mechanism applies at all (false in tree mode). */
    active: MaybeRefOrGetter<boolean>;
}

export interface UseFitColumnsReturn {
    /**
     * True when the container is narrower than the smallest possible table (its
     * pinned columns + structural cells + actions). The consumer must then switch
     * to the stacked card layout — horizontal scrolling is not used, so the sticky
     * header is never broken and no part of a row is ever clipped-and-unreachable.
     */
    mustCard: Readonly<Ref<boolean>>;
}

// A column must overflow by more than this before it is dropped, and a hidden
// column may only return when at least RESHOW_MARGIN px of slack remains beyond
// its own width. The asymmetric thresholds form a deadband that prevents the
// show → overflow → hide → fit → show oscillation.
const OVERFLOW_EPS = 1;
const RESHOW_MARGIN = 48;
// `grow` columns shrink and truncate, so they barely cost width; treat them as a
// small constant instead of their inflated rendered width.
const GROW_MIN = 64;
// Safety cap on the per-convergence drop/show chain (columns count is far below).
const MAX_STEPS = 40;
// Hysteresis for the table↔card switch: enter cards as soon as the table cannot
// fit, but only return to the table once this much extra room exists, so a width
// hovering at the boundary does not flip-flop.
const CARD_HYSTERESIS = 24;

/**
 * Keeps a resource table within its container by (1) progressively dropping the
 * lowest-priority columns until it fits — bringing them back with hysteresis when
 * space frees up — and (2) signalling `mustCard` when even the pinned columns plus
 * actions cannot fit, so the consumer falls back to a stacked card layout instead
 * of clipping or horizontally scrolling. The available width is read from an
 * always-present container element (not the table wrapper, which is hidden in card
 * mode) so the switch back to the table works without a header to measure. Reads
 * are batched in a rAF against the header row only (N cells, not N×rows).
 *
 * @param wrapperRef    Table wrapper holding the `<thead>` (present in table mode).
 * @param containerRef  Always-present element whose width is the available space.
 * @param columns       The full column set (for `grow`/`alwaysVisible` classification).
 * @param api           Ranking + fit-set mutators from `useVisibleColumns`.
 * @param options       `tableMode` gates the column fit; `active` gates everything.
 */
export function useFitColumns(
    wrapperRef: Ref<HTMLElement | null>,
    containerRef: Ref<HTMLElement | null>,
    columns: MaybeRefOrGetter<ColumnConfig[]>,
    api: FitColumnsApi,
    options: FitColumnsOptions,
): UseFitColumnsReturn {
    // Natural (content) width per column key, refreshed while the column is
    // visible so re-show decisions work even while the column is dropped.
    const naturalWidths = new Map<string, number>();

    // Width of the smallest possible table (structural cells + pinned columns +
    // actions), measured in table mode and cached so the card→table switch-back
    // decision still works when no header is rendered. See mustCard.
    const minTableWidth = ref(0);

    // See UseFitColumnsReturn.mustCard.
    const mustCard = ref(false);

    const growKeys = computed<Set<string>>(
        () =>
            new Set(
                toValue(columns)
                    .filter(isGrowColumn)
                    .map((col) => col.key),
            ),
    );

    const alwaysVisibleKeys = computed<Set<string>>(
        () =>
            new Set(
                toValue(columns)
                    .filter((col) => col.alwaysVisible)
                    .map((col) => col.key),
            ),
    );

    /** Available width from the always-present container (0 when not laid out). */
    function availableWidth(): number {
        return containerRef.value?.clientWidth ?? 0;
    }

    /**
     * Reads the header row: the total rendered width of the visible columns
     * (`needed`), the width the table cannot shrink below (`minWidth`), and
     * the sum of stored natural content widths (`naturalSum`).
     *
     * Natural widths are only updated when the table is overflowing (`needed >
     * available + OVERFLOW_EPS`), because in that state rendered width equals
     * content width. When fitting, rendered widths are inflated by proportional
     * distribution — updating them then would break re-show decisions.
     *
     * Returns null when no header is rendered.
     */
    function measureHeader(available: number): {
        needed: number;
        minWidth: number;
        naturalSum: number;
        fixedWidth: number;
    } | null {
        const thead = wrapperRef.value?.querySelector('thead');

        if (!thead) {
            return null;
        }

        // First pass: collect rendered widths and build `needed`.
        interface Cell {
            key: string;
            renderedWidth: number;
            alwaysVisible: boolean;
        }
        const cells: Cell[] = [];
        let needed = 0;
        let minWidth = 0;
        // Width of structural cells only (checkbox, expand, drag, actions) —
        // excludes data columns. Used by decide() alongside naturalSum so the
        // re-show check correctly accounts for the full table width.
        let fixedWidth = 0;

        thead.querySelectorAll<HTMLElement>('tr > th').forEach((th) => {
            const key = th.dataset.colKey;

            if (key !== undefined) {
                const renderedWidth = growKeys.value.has(key)
                    ? GROW_MIN
                    : th.getBoundingClientRect().width;
                cells.push({
                    key,
                    renderedWidth,
                    alwaysVisible: alwaysVisibleKeys.value.has(key),
                });
                needed += renderedWidth;

                return;
            }

            // Structural cells (checkbox, expand, drag, actions) count toward
            // both needed and minWidth. The trailing spacer is excluded via
            // data-spacer and costs nothing.
            if (th.dataset.spacer === undefined) {
                const width = th.getBoundingClientRect().width;
                needed += width;
                minWidth += width;
                fixedWidth += width;
            }
        });

        // Second pass: update naturalWidths only when overflowing (rendered ≈
        // content). When fitting, retain the values calibrated during overflow.
        const overflowing = needed > available + OVERFLOW_EPS;

        for (const { key, renderedWidth, alwaysVisible } of cells) {
            if (overflowing || !naturalWidths.has(key)) {
                naturalWidths.set(key, renderedWidth);
            }

            const natural = naturalWidths.get(key) ?? renderedWidth;

            if (alwaysVisible) {
                minWidth += natural;
            }
        }

        // naturalSum: sum of stored content widths for visible columns. Used
        // by decide() to compute actual slack for re-show decisions.
        let naturalSum = 0;

        for (const { key, renderedWidth } of cells) {
            naturalSum += naturalWidths.get(key) ?? renderedWidth;
        }

        return { needed, minWidth, naturalSum, fixedWidth };
    }

    /** One drop/show decision. Returns true when it mutated the fit set. */
    function decide(
        needed: number,
        naturalSum: number,
        fixedWidth: number,
        available: number,
    ): boolean {
        if (needed > available + OVERFLOW_EPS) {
            const victim = api.lowestDroppableKey();

            if (victim) {
                api.setFitHidden(victim, true);

                return true;
            }

            // Nothing left to drop — the caller falls back to cards via mustCard.
            return false;
        }

        const candidate = api.highestHiddenKey();

        if (candidate) {
            const cost = naturalWidths.get(candidate);

            if (
                cost !== undefined &&
                naturalSum + fixedWidth + cost + RESHOW_MARGIN <= available
            ) {
                api.setFitHidden(candidate, false);

                return true;
            }
        }

        return false;
    }

    /** Updates mustCard from the container width and the cached minimum width. */
    function recomputeMustCard(available: number): void {
        if (minTableWidth.value <= 0 || available <= 0) {
            return;
        }

        if (mustCard.value) {
            if (available >= minTableWidth.value + CARD_HYSTERESIS) {
                mustCard.value = false;
            }
        } else if (available < minTableWidth.value) {
            mustCard.value = true;
        }
    }

    let rafId: number | null = null;

    function tick(remaining: number): void {
        rafId = null;

        if (!toValue(options.active)) {
            mustCard.value = false;

            return;
        }

        const available = availableWidth();

        if (available <= 0) {
            return;
        }

        if (toValue(options.tableMode)) {
            const header = measureHeader(available);

            if (header) {
                minTableWidth.value = header.minWidth;

                const changed = decide(
                    header.needed,
                    header.naturalSum,
                    header.fixedWidth,
                    available,
                );

                // A change re-renders the table; wait for the DOM, then measure
                // again so the drop/show chain converges.
                if (changed && remaining > 0) {
                    nextTick(() => {
                        rafId = requestAnimationFrame(() =>
                            tick(remaining - 1),
                        );
                    });

                    return;
                }
            }
        }

        recomputeMustCard(available);
    }

    function schedule(): void {
        if (rafId !== null || typeof window === 'undefined') {
            return;
        }

        rafId = requestAnimationFrame(() => tick(MAX_STEPS));
    }

    // Container resized → re-evaluate fit and the card threshold. Observing the
    // always-present container (not the table wrapper) keeps this firing in card
    // mode so the switch back to the table happens when the width grows.
    useResizeObserver(containerRef, schedule);

    // Eligible set changed (breakpoint, user override, or column list) → start
    // over from a clean slate so a newly forced/eligible column is measured.
    watch(api.baseEligibleKeys, () => {
        api.resetFit();
        nextTick(schedule);
    });

    // When the layout flips (card ↔ table), re-measure so the newly shown table
    // refines its fit. Watching the internal ref avoids touching the consumer's
    // `isCardLayout`, which is declared after this call (temporal dead zone).
    watch(mustCard, () => nextTick(schedule));

    onMounted(() => nextTick(schedule));

    onBeforeUnmount(() => {
        if (rafId !== null) {
            cancelAnimationFrame(rafId);
            rafId = null;
        }
    });

    return { mustCard: readonly(mustCard) };
}
