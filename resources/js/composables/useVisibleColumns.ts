import { breakpointsTailwind, useBreakpoints } from '@vueuse/core';
import type { ComputedRef, MaybeRefOrGetter } from 'vue';
import { computed, onMounted, ref, toValue, watch } from 'vue';
import type { ColumnConfig } from '@/types/resource';

/** An entry describing a column plus its current visibility state (for the menu). */
export interface ColumnVisibilityEntry {
    column: ColumnConfig;
    /** Whether the column is currently rendered. */
    visible: boolean;
    /** Whether it is hidden only because of its responsive `showFrom` breakpoint. */
    autoHidden: boolean;
    /**
     * Whether it is hidden only because the table would overflow its container
     * (the automatic width-based auto-fit dropped it). Distinct from `autoHidden`.
     */
    fitHidden: boolean;
    /** Whether the user may toggle it (false for `alwaysVisible` columns). */
    toggleable: boolean;
}

export interface UseVisibleColumnsReturn {
    /** The columns that should actually be rendered, in declaration order. */
    visibleColumns: ComputedRef<ColumnConfig[]>;
    /** All columns with their current visibility state — drives the column menu. */
    entries: ComputedRef<ColumnVisibilityEntry[]>;
    /** True when the user has stored any manual override for this table. */
    hasOverrides: ComputedRef<boolean>;
    /**
     * True when at least one column is explicitly hidden by a user override
     * (value `=== false` in the overrides map). Distinct from `hasOverrides`,
     * which is also true when columns are merely forced-visible. Only this
     * narrower state justifies removing the trailing spacer and expanding text.
     */
    hasHiddenOverrides: ComputedRef<boolean>;
    /** Flip a single column's visibility and persist the choice. */
    toggle: (key: string) => void;
    /** Clear all user overrides (back to the responsive defaults). */
    reset: () => void;
    /**
     * Keys of columns that would render ignoring the width-based auto-fit, in
     * declaration order. Changes only when the eligible set changes (override,
     * breakpoint, or column-list change) — not when auto-fit drops a column — so
     * consumers can watch it to reset the fit from a clean slate.
     */
    baseEligibleKeys: ComputedRef<string[]>;
    /** Key of the lowest-priority currently-rendered column the fit may drop. */
    lowestDroppableKey: () => string | null;
    /** Key of the highest-priority fit-hidden column eligible to be re-shown. */
    highestHiddenKey: () => string | null;
    /** Mark a column hidden/shown by the width-based auto-fit. */
    setFitHidden: (key: string, hidden: boolean) => void;
    /** Clear all auto-fit drops (every eligible column becomes visible again). */
    resetFit: () => void;
}

/** A column reduced to the fields the overflow ranking compares. */
interface RankedColumn {
    key: string;
    index: number;
    priority: number;
}

const STORAGE_PREFIX = 'table-columns:';

function storageKeyFor(table: string): string {
    return `${STORAGE_PREFIX}${table}`;
}

/**
 * Central source of truth for which table columns are rendered. Combines the
 * responsive `showFrom` breakpoint rule with per-user overrides persisted in
 * localStorage (keyed by the table's endpoint), so the header and body loops
 * iterate a single filtered list instead of duplicating `hidden md:` classes.
 *
 * @param columns  The full column set (already including any dynamic columns
 *                 such as the trash `deleted_at` column).
 * @param table    A stable per-table key (the resource endpoint) for storage.
 */
export function useVisibleColumns(
    columns: MaybeRefOrGetter<ColumnConfig[]>,
    table: MaybeRefOrGetter<string>,
): UseVisibleColumnsReturn {
    const breakpoints = useBreakpoints(breakpointsTailwind);

    // Explicit per-column overrides: key → forced visibility (true = show,
    // false = hide). Absent keys fall back to the responsive default.
    const overrides = ref<Record<string, boolean>>({});

    function load(): void {
        if (typeof window === 'undefined') {
            return;
        }

        try {
            const raw = localStorage.getItem(storageKeyFor(toValue(table)));
            overrides.value = raw
                ? (JSON.parse(raw) as Record<string, boolean>)
                : {};
        } catch {
            overrides.value = {};
        }
    }

    onMounted(load);
    // Reload when navigating between tables that reuse the same component.
    watch(() => toValue(table), load);

    watch(
        overrides,
        (value) => {
            if (typeof window === 'undefined') {
                return;
            }

            const key = storageKeyFor(toValue(table));

            if (Object.keys(value).length === 0) {
                localStorage.removeItem(key);
            } else {
                localStorage.setItem(key, JSON.stringify(value));
            }
        },
        { deep: true },
    );

    // Keys the width-based auto-fit has dropped because the table would overflow.
    // Not persisted — it is recomputed from live measurements after every layout
    // change (see useFitColumns) and starts empty so the SSR/first render is stable.
    const fitHiddenKeys = ref<Set<string>>(new Set());

    // A column is auto-hidden when its breakpoint requirement is not met.
    function autoHiddenColumn(col: ColumnConfig): boolean {
        return (
            col.showFrom != null &&
            !breakpoints.greaterOrEqual(col.showFrom).value
        );
    }

    // Whether the user has explicitly forced this column visible via the menu.
    function forcedOn(col: ColumnConfig): boolean {
        return overrides.value[col.key] === true;
    }

    // Would the column render ignoring the width-based auto-fit? (alwaysVisible,
    // user override, else the responsive `showFrom` gate.)
    function baseEligible(col: ColumnConfig): boolean {
        if (col.alwaysVisible) {
            return true;
        }

        const override = overrides.value[col.key];

        if (override !== undefined) {
            return override;
        }

        return !autoHiddenColumn(col);
    }

    // Hidden by auto-fit only — never applies to alwaysVisible or user-forced
    // columns, which the fit must not touch.
    function fitHidden(col: ColumnConfig): boolean {
        return (
            fitHiddenKeys.value.has(col.key) &&
            !col.alwaysVisible &&
            !forcedOn(col)
        );
    }

    function effectiveVisible(col: ColumnConfig): boolean {
        return baseEligible(col) && !fitHidden(col);
    }

    // Columns the auto-fit is allowed to drop: eligible, not pinned, not forced.
    function droppable(col: ColumnConfig): boolean {
        return baseEligible(col) && !col.alwaysVisible && !forcedOn(col);
    }

    // Default importance is neutral (0); ties are broken by declaration order in
    // the ranking (later-declared columns drop first), so earlier — usually more
    // important — columns survive without any per-page `priority` annotation.
    function priorityOf(col: ColumnConfig): number {
        return col.priority ?? 0;
    }

    const visibleColumns = computed<ColumnConfig[]>(() =>
        toValue(columns).filter(effectiveVisible),
    );

    const baseEligibleKeys = computed<string[]>(() =>
        toValue(columns)
            .filter(baseEligible)
            .map((col) => col.key),
    );

    const entries = computed<ColumnVisibilityEntry[]>(() =>
        toValue(columns).map((column) => ({
            column,
            visible: effectiveVisible(column),
            autoHidden: autoHiddenColumn(column),
            fitHidden: fitHidden(column),
            toggleable: !column.alwaysVisible,
        })),
    );

    const hasOverrides = computed<boolean>(
        () => Object.keys(overrides.value).length > 0,
    );

    const hasHiddenOverrides = computed<boolean>(() =>
        Object.values(overrides.value).some((v) => !v),
    );

    function toggle(key: string): void {
        const col = toValue(columns).find((c) => c.key === key);

        if (!col || col.alwaysVisible) {
            return;
        }

        overrides.value = {
            ...overrides.value,
            [key]: !effectiveVisible(col),
        };
    }

    function reset(): void {
        overrides.value = {};
    }

    // Picks the winning column key among those passing `eligible`, using `better`
    // to compare two candidates. Kept as a reduce so the accumulator type stays
    // `Ranked | null` (a plain closure-assigned `best` narrows to `never`).
    function pickColumn(
        eligible: (col: ColumnConfig) => boolean,
        better: (a: RankedColumn, b: RankedColumn) => boolean,
    ): string | null {
        const ranked: RankedColumn[] = [];

        toValue(columns).forEach((col, index) => {
            if (eligible(col)) {
                ranked.push({
                    key: col.key,
                    index,
                    priority: priorityOf(col),
                });
            }
        });

        return (
            ranked.reduce<RankedColumn | null>(
                (best, cur) =>
                    best === null || better(cur, best) ? cur : best,
                null,
            )?.key ?? null
        );
    }

    // Lowest-priority currently-rendered droppable column (ties broken by later
    // declaration order) — the next victim when the table overflows.
    function lowestDroppableKey(): string | null {
        return pickColumn(
            (col) => effectiveVisible(col) && droppable(col),
            (a, b) =>
                a.priority < b.priority ||
                (a.priority === b.priority && a.index > b.index),
        );
    }

    // Highest-priority fit-hidden but still-eligible column (ties broken by
    // earlier declaration order) — the next to bring back when space frees up.
    function highestHiddenKey(): string | null {
        return pickColumn(
            (col) => fitHidden(col) && baseEligible(col),
            (a, b) =>
                a.priority > b.priority ||
                (a.priority === b.priority && a.index < b.index),
        );
    }

    function setFitHidden(key: string, hidden: boolean): void {
        const has = fitHiddenKeys.value.has(key);

        if (hidden === has) {
            return;
        }

        const next = new Set(fitHiddenKeys.value);

        if (hidden) {
            next.add(key);
        } else {
            next.delete(key);
        }

        fitHiddenKeys.value = next;
    }

    function resetFit(): void {
        if (fitHiddenKeys.value.size > 0) {
            fitHiddenKeys.value = new Set();
        }
    }

    return {
        visibleColumns,
        entries,
        hasOverrides,
        hasHiddenOverrides,
        toggle,
        reset,
        baseEligibleKeys,
        lowestDroppableKey,
        highestHiddenKey,
        setFitHidden,
        resetFit,
    };
}
