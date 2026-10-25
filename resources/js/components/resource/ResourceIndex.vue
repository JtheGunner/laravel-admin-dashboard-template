<script lang="ts" setup>
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import {
    ChevronDown,
    ChevronLeft,
    ChevronRight,
    ChevronsUpDown,
    ChevronUp,
    Eye,
    GripVertical,
    Inbox,
    List,
    Plus,
    RotateCcw,
    Trash2,
} from '@lucide/vue';
import { breakpointsTailwind, useBreakpoints } from '@vueuse/core';
import {
    computed,
    onMounted,
    provide,
    ref,
    useSlots,
    watch,
    watchEffect,
} from 'vue';
import draggable from 'vuedraggable';
import AppTooltip from '@/components/AppTooltip.vue';
import ColumnVisibilityMenu from '@/components/resource/ColumnVisibilityMenu.vue';
import ResourceCard from '@/components/resource/ResourceCard.vue';
import ResourceCell from '@/components/resource/ResourceCell.vue';
import ResourceFacetFilter from '@/components/resource/ResourceFacetFilter.vue';
import ResourceFormFields from '@/components/resource/ResourceFormFields.vue';
import ResourceRowActions from '@/components/resource/ResourceRowActions.vue';
import ResourceRowContextMenu from '@/components/resource/ResourceRowContextMenu.vue';
import ResourceSortControl from '@/components/resource/ResourceSortControl.vue';
import ResourceTreeLevel from '@/components/resource/ResourceTreeLevel.vue';
import SearchInput from '@/components/SearchInput.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetFooter,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { useCopy } from '@/composables/useCopy';
import { useFitColumns } from '@/composables/useFitColumns';
import { useVisibleColumns } from '@/composables/useVisibleColumns';
import {
    copyableText,
    hasCopyableContent,
    isCapWidth,
    isGrowColumn,
    isPercentWidth,
    isTruncating,
    joinKeys,
    treeColumnClass,
    treeColumnStyle,
} from '@/lib/resourceCell';
import type { RowTintFn } from '@/lib/rowTint';
import { rowTintRowClass, selectedRowClass } from '@/lib/rowTint';
import { displayUrl, isUrl } from '@/lib/utils';
import type {
    ColumnConfig,
    FacetConfig,
    FieldConfig,
    Paginator,
    ResourceRow,
    SortDirection,
    TreeNode,
} from '@/types/resource';

const props = withDefaults(
    defineProps<{
        /** Base resource path, e.g. "/categories". */
        endpoint: string;
        /** Singular label used in buttons/dialog, e.g. "Customer". */
        singular: string;
        /** Optional card-header title; defaults to `<singular> list`. */
        title?: string;
        columns: ColumnConfig[];
        /**
         * Enables the per-row expand toggle (a chevron that reveals a detail panel
         * with the record's fields laid out vertically). Default: true. Set to
         * false to remove the expand column entirely for a table.
         */
        expandable?: boolean;
        /**
         * Columns rendered inside the expanded detail panel. Defaults to `columns`
         * (the same set shown in the collapsed row). Provide a different set to show
         * additional/other fields only when a row is expanded.
         */
        expandedColumns?: ColumnConfig[];
        fields: FieldConfig[];
        /** Override fields shown only in the edit form (create still uses `fields`). */
        editFields?: FieldConfig[];
        rows: Paginator<ResourceRow>;
        filters: {
            search: string;
            trashed?: boolean;
            /** Active server-side sort column (real DB column) echoed back. */
            sort?: string | null;
            direction?: SortDirection | null;
            /** Column the list falls back to when no explicit sort is active. */
            defaultSort?: string | null;
        };
        /** Enables drag-and-drop reordering (sections with a `position` column). */
        sortable?: boolean;
        /**
         * Renders rows as an indented hierarchy (rows carrying `parent_id` +
         * `position`). Drag reordering is constrained to siblings of the same parent
         * and the position column shows a dotted label (1, 1.1, …). Disables search
         * and pagination (the full set is loaded server-side).
         */
        tree?: boolean;
        /** Show the edit (pencil) button per row. Default: true. */
        showEdit?: boolean;
        /** Show the delete (trash) button per row. Default: true. */
        showDelete?: boolean;
        /** Show the copy (duplicate) button per row. Default: true. */
        showCopy?: boolean;
        /**
         * Read-only mode for append-only log views: hides the create button, the
         * selection checkboxes (and thus bulk actions), the per-row
         * edit/duplicate/delete actions and double-click-to-edit. The rows can only
         * be searched, sorted, expanded and paginated. Default: false.
         */
        readonly?: boolean;
        /**
         * Force row selection (checkbox column + selection bar) on, overriding the
         * default. Selection is already on for normal tables; set `true` on a
         * `readonly` table to keep the mutating per-row actions hidden while still
         * allowing multiselect for custom bulk actions supplied via the
         * `bulkActions` slot. To turn selection off, use `readonly` — an unset
         * `selectable` cannot force it off (Vue casts an absent boolean to `false`,
         * which the effective flag treats the same as "use the `readonly` default").
         */
        selectable?: boolean;
        /**
         * Hides the search input, disables server-side filtering, and hides the
         * pagination footer. Use when all records are loaded server-side at once
         * (e.g. template sub-resources on the assignments page).
         */
        showSearch?: boolean;
        /**
         * When set, create-form submissions POST to this endpoint instead of
         * `endpoint`. Use together with multiselect fields for bulk-add.
         */
        bulkStoreEndpoint?: string;
        /** Overrides the label of the row-level "add" button (default: `New <singular>`). */
        addLabel?: string;
        /**
         * Optional secondary "create new" flow: when both `createNewFields` and
         * `createNewEndpoint` are set, a separate button opens a create dialog using
         * these fields and POSTs to `createNewEndpoint` (instead of the normal add flow).
         */
        createNewFields?: FieldConfig[];
        /** Endpoint the secondary "create new" dialog POSTs to. */
        createNewEndpoint?: string;
        /** Label for the secondary "create new" button/dialog (default: `Create new`). */
        createNewLabel?: string;
        /**
         * Optional async guard run right before an edit/create is submitted.
         * Receives the current form data and the edited row id (null on create).
         *
         * Return `false` (or `{ proceed: false }`) to abort the submission — e.g.
         * after the user declines a confirmation dialog. Return `true` to proceed,
         * or `{ proceed: true, merge }` to proceed and merge extra page-specific
         * fields into the submitted payload (e.g. a "notify user" flag).
         * Defaults to always proceeding.
         */
        beforeSubmit?: (
            data: FormData,
            editingId: number | null,
        ) =>
            | boolean
            | { proceed: boolean; merge?: Record<string, unknown> }
            | Promise<
                  | boolean
                  | { proceed: boolean; merge?: Record<string, unknown> }
              >;
        /**
         * Optional per-row colour marking. Returns a tint key from the shared
         * palette (see `@/lib/rowTint`) to give a row a subtle coloured surface,
         * or `null`/`undefined` to leave it on the default background. Applies in
         * both the table and the small-screen card layout. Example: mark test
         * records amber via `(row) => (row.is_test ? 'amber' : null)`.
         */
        rowTint?: RowTintFn;
        /**
         * When set, double-clicking a row navigates to this URL instead of
         * opening the edit sheet, and an Eye icon button appears in the row
         * actions column as the first action.
         */
        detailRoute?: (row: ResourceRow) => string;
        /**
         * Facet keys to hide from the filter toolbar. The facets are still active
         * (selections are still sent to the server) — only the UI control is hidden.
         * Useful when a tab already scopes to a specific facet value, making the
         * corresponding filter widget redundant.
         */
        hiddenFacetKeys?: string[];
        /**
         * Additional query parameters a page owns (e.g. an exclusion expression),
         * sent with every search/sort/filter request and re-applied when they change.
         */
        extraQuery?: Record<string, unknown>;
    }>(),
    {
        showEdit: true,
        showDelete: true,
        showCopy: true,
        showSearch: true,
        expandable: true,
        readonly: false,
        extraQuery: () => ({}),
    },
);

const slots = useSlots();

// Effective per-row action flags: `readonly` (append-only logs) forces every
// mutating affordance off, regardless of the individual show* props.
const canEdit = computed(() => props.showEdit && !props.readonly);
const canDelete = computed(() => props.showDelete && !props.readonly);
const canCopy = computed(() => props.showCopy && !props.readonly);

// Whether rows are selectable (drives the checkbox column and, in turn, the bulk
// action bar). Defaults on for normal tables and off in readonly mode (the
// built-in bulk actions mutate); a page can force it on with `:selectable` (e.g.
// a readonly log that supplies its own `bulkActions`).
//
// `||`, not `??`: Vue casts an *absent* Boolean prop to `false` (not `undefined`),
// so `props.selectable ?? !props.readonly` would short-circuit to `false` on every
// page that doesn't pass `selectable` and the checkboxes would never appear. With
// `||`, an unset (false) `selectable` correctly falls back to `!readonly`.
const selectable = computed(() => props.selectable || !props.readonly);

// Tree only: an identical fixed width for the actions column in the header and in
// every row. The header shows the "Actions" label while a row shows the action
// buttons, so their natural widths differ — and in a flex layout (unlike the flat
// `<table>`, which shares a column width across header + body) that difference is
// absorbed by a `grow` column, which then has a different width in the header than
// in the body and shifts every column after it out of alignment. Reserving the
// same width in both keeps `grow` identical, so the trailing columns stay aligned.
const treeActionsWidth = computed<string>(() => {
    const buttons =
        (canCopy.value ? 1 : 0) +
        (canEdit.value ? 1 : 0) +
        (canDelete.value ? 1 : 0);

    // size-8 buttons (2rem) separated by gap-0.5 (0.125rem); the 3.5rem floor keeps
    // the "Actions" label from clipping when a row shows few or no buttons.
    const buttonsRem = buttons > 0 ? buttons * 2 + (buttons - 1) * 0.125 : 0;

    return `${Math.max(buttonsRem, 3.5)}rem`;
});

// Whether the trailing "Actions" column is rendered. Non-readonly tables always
// keep it (unchanged behaviour); a readonly log only keeps it when the page still
// supplies custom row actions via the `rowActions` slot.
const showActions = computed(() =>
    props.readonly ? Boolean(slots.rowActions) : true,
);

// Inertia useForm payload — values are string|number|boolean|array depending on the field type.

type FormData = Record<string, any>;

function blankForm(): FormData {
    const data: FormData = {};
    const seen = new Set<string>();

    for (const field of [
        ...props.fields,
        ...(props.editFields ?? []),
        ...(props.createNewFields ?? []),
    ]) {
        if (seen.has(field.name) || field.type === 'section') {
            continue;
        }

        seen.add(field.name);

        if (field.type === 'checkbox') {
            data[field.name] = false;
        } else if (field.type === 'repeater' || field.type === 'multiselect') {
            data[field.name] = [];
        } else if (field.type === 'range') {
            data[field.name] = field.min ?? 0;
        } else {
            data[field.name] = '';
        }
    }

    return data;
}

// --- Column sorting ---
// Shared page props expose the model's real DB columns. Those are sorted
// server-side so the ordering spans every page. Columns not in this set
// (computed values like relation names or counts) fall back to client-side
// sorting of the currently displayed page.
const pageProps = usePage();

const serverSortKeys = computed<string[]>(
    () => (pageProps.props.sortableColumns as string[] | undefined) ?? [],
);

// Server-side sorting only applies to the paginated table view — not tree mode
// or full-dataset sub-resources, where every row is already loaded.
const paginated = computed(() => props.showSearch && !props.tree);

const sortKey = ref<string | null>(props.filters.sort ?? null);
const sortDir = ref<SortDirection>(props.filters.direction ?? 'asc');

// Whether the given column is ordered by the server (rows arrive pre-sorted).
function isServerSorted(key: string | null): key is string {
    return (
        paginated.value && key !== null && serverSortKeys.value.includes(key)
    );
}

// Column sorting (clickable headers + the standalone sort control) is disabled
// for manually ordered tables — drag-reorder (`sortable`) or the tree hierarchy —
// where sorting by a column would fight the manual order.
const columnSortingEnabled = computed<boolean>(
    () => !props.sortable && !props.tree,
);

function toggleSort(col: ColumnConfig) {
    if (!col.sortable || !columnSortingEnabled.value) {
        return;
    }

    if (sortKey.value === col.key) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortKey.value = col.key;
        sortDir.value = 'asc';
    }

    // Real DB columns round-trip to the server so the sort spans all pages.
    if (isServerSorted(col.key)) {
        applyQuery();
    }
}

// Keep the header indicators in sync when the server echoes the active sort back
// (e.g. after paginating or changing the page size).
watch(
    () => [props.filters.sort, props.filters.direction] as const,
    ([key, dir]) => {
        if (key) {
            sortKey.value = key;
            sortDir.value = dir ?? 'asc';
        }
    },
);

// Local, sortable copy of the rows (in sync with the server data).
const rowsBase = ref<ResourceRow[]>([...props.rows.data]);
watch(
    () => props.rows.data,
    (value) => {
        rowsBase.value = [...value];
    },
);

const rowsLocal = computed<ResourceRow[]>(() => {
    // No sort, or the server already returned the rows in order.
    if (!sortKey.value || isServerSorted(sortKey.value)) {
        return rowsBase.value;
    }

    const key = sortKey.value;
    const dir = sortDir.value === 'asc' ? 1 : -1;

    return [...rowsBase.value].sort((a, b) => {
        const va = a[key] ?? '';
        const vb = b[key] ?? '';

        if (typeof va === 'number' && typeof vb === 'number') {
            return (va - vb) * dir;
        }

        return String(va).localeCompare(String(vb), 'en') * dir;
    });
});

const rowsDisplay = computed<ResourceRow[]>({
    get() {
        return rowsLocal.value;
    },
    set(value) {
        rowsBase.value = value;
    },
});

// Drag & drop works on the base array (not the sorted computed).
function onDragEnd() {
    // rowsBase keeps the order after dragging; persist to server.
    router.post(
        `${props.endpoint}/reorder`,
        {
            ids: rowsBase.value.map((row) => row.id),
            from: props.rows.from ?? 1,
        },
        { preserveScroll: true, preserveState: true },
    );
}

// --- Tree mode: turn the flat row list into a hierarchy ---
const treeNodes = ref<TreeNode[]>([]);

function buildTree(rows: ResourceRow[]): TreeNode[] {
    const ids = new Set(rows.map((row) => row.id));
    const childrenOf = new Map<number | null, ResourceRow[]>();

    for (const row of rows) {
        const rawParent = row.parent_id as number | null | undefined;
        // Orphaned references (parent not in the set) as the top level.
        const key = rawParent != null && ids.has(rawParent) ? rawParent : null;
        const bucket = childrenOf.get(key) ?? [];
        bucket.push(row);
        childrenOf.set(key, bucket);
    }

    const byPosition = (a: ResourceRow, b: ResourceRow) =>
        (Number(a.position) || 0) - (Number(b.position) || 0);

    const build = (
        parentKey: number | null,
        depth: number,
        prefix: string,
    ): TreeNode[] =>
        (childrenOf.get(parentKey) ?? [])
            .slice()
            .sort(byPosition)
            .map((row, index) => {
                const label = prefix
                    ? `${prefix}.${index + 1}`
                    : String(index + 1);

                return {
                    ...row,
                    _depth: depth,
                    _label: label,
                    _children: build(row.id, depth + 1, label),
                } as TreeNode;
            });

    return build(null, 0, '');
}

watch(
    () => props.rows.data,
    (value) => {
        if (props.tree) {
            treeNodes.value = buildTree(value);
        }
    },
    { immediate: true },
);

function persistTreeOrder(parentId: number | null, ids: number[]) {
    router.post(
        `${props.endpoint}/reorder`,
        { parentId, ids },
        { preserveScroll: true, preserveState: true },
    );
}

// --- Page size (from shared page props, no threading through every page) ---
const pageSizes = computed<number[]>(
    () =>
        (pageProps.props.pageSizes as number[] | undefined) ?? [
            10, 15, 25, 50, 100,
        ],
);
const perPageValue = ref(
    String((pageProps.props.perPage as number | undefined) ?? 15),
);

// --- Faceted filters (from shared page props, declared server-side) ---
// The ResourceController delivers each filter's options + the currently active
// selection; the table renders a filter menu and round-trips the selection on
// search/sort/pagination. `facetSelections` mirrors the server-echoed selection.
const facetDefs = computed<FacetConfig[]>(
    () => (pageProps.props.facets as FacetConfig[] | undefined) ?? [],
);

function facetSelectionsFromProps(): Record<string, (string | number)[]> {
    const out: Record<string, (string | number)[]> = {};

    for (const facet of facetDefs.value) {
        const isDate = facet.type === 'date';
        const optionCount = facet.options?.length ?? 0;
        // Normalize a full-set selection back to empty so the UI shows nothing checked,
        // which is the visual counterpart of "no filter active".
        const isFullSet =
            !isDate && optionCount > 0 && facet.selected.length >= optionCount;
        out[facet.key] = isFullSet ? [] : [...facet.selected];
    }

    return out;
}

const facetSelections = ref<Record<string, (string | number)[]>>(
    facetSelectionsFromProps(),
);

// Re-sync when the server echoes an updated selection (after any navigation).
watch(facetDefs, () => {
    facetSelections.value = facetSelectionsFromProps();
});

// Filter query params for the manually built round-trips (search/perPage/sort/
// trashed). Pagination links already carry the filters via withQueryString().
function filterParam(): Record<string, unknown> {
    if (facetDefs.value.length === 0) {
        return {};
    }

    const filter: Record<string, (string | number)[]> = {};

    for (const facet of facetDefs.value) {
        const selected = facetSelections.value[facet.key] ?? [];
        const isDate = facet.type === 'date';

        // An empty selection means "no filter" — send all option values so the backend
        // receives a full set (no constraint applied), bypassing any declared default.
        if (
            !isDate &&
            selected.length === 0 &&
            (facet.options?.length ?? 0) > 0
        ) {
            filter[facet.key] = facet.options!.map((o) => o.value);
        } else {
            filter[facet.key] = selected;
        }
    }

    return { filter };
}

function toggleFacetValue(
    key: string,
    value: string | number,
    checked: boolean,
): void {
    const current = facetSelections.value[key] ?? [];
    const next = checked
        ? [...current.filter((v) => String(v) !== String(value)), value]
        : current.filter((v) => String(v) !== String(value));

    facetSelections.value = { ...facetSelections.value, [key]: next };
    applyQuery();
}

function updateFacetValues(key: string, values: (string | number)[]): void {
    facetSelections.value = { ...facetSelections.value, [key]: values };
    applyQuery();
}

// --- Trash (SoftDeletes) ---
// `softDeletes`/`trashedCount` come as shared page props from the
// ResourceController, so no individual index page needs to be adjusted.
const softDeletes = computed(() =>
    Boolean(pageProps.props.softDeletes as boolean | undefined),
);
const trashedCount = computed(() =>
    Number((pageProps.props.trashedCount as number | undefined) ?? 0),
);
const showTrashed = computed(() => Boolean(props.filters.trashed));

// Drag & drop only makes sense in the active view (not in the trash).
const dragEnabled = computed(
    () => Boolean(props.sortable) && !showTrashed.value,
);

// The full column set: the page columns plus, in the trash, an extra
// "deleted at" column. Fed into the visibility composable below.
const baseColumns = computed<ColumnConfig[]>(() =>
    showTrashed.value
        ? [
              ...props.columns,
              {
                  key: 'deleted_at',
                  label: 'Deleted at',
                  type: 'datetime',
                  align: 'right',
                  alwaysVisible: true,
              },
          ]
        : props.columns,
);

// Single source of truth for which columns are rendered (responsive `showFrom`
// rule + per-user overrides persisted per endpoint). Header and body loops both
// iterate `visibleColumns`.
const {
    visibleColumns,
    entries: columnEntries,
    hasOverrides: columnHasOverrides,
    hasHiddenOverrides: columnHasHiddenOverrides,
    toggle: toggleColumn,
    reset: resetColumns,
    baseEligibleKeys,
    lowestDroppableKey,
    highestHiddenKey,
    setFitHidden,
    resetFit,
} = useVisibleColumns(baseColumns, () => props.endpoint);

// Let ResourceCellValue know whether columns are in redistributed mode (at least
// one column is actively hidden) so it can skip the data-level character cap.
provide('columnHasOverrides', columnHasHiddenOverrides);

// The table's scroll-wrapper (holds the <thead> that the fit measures) and the
// always-present card container (its width is the available space, readable in
// both table and card mode).
const tableWrapperRef = ref<HTMLElement | null>(null);
const tableContainerRef = ref<HTMLElement | null>(null);

// Automatically drop the lowest-priority columns when the table would overflow
// its container (keeping the sticky header intact by never adding a scroll
// container). `mustCard` becomes true only when even the pinned columns + actions
// cannot fit — the table then falls back to the stacked card layout instead of
// clipping. Only runs outside tree mode.
const { mustCard } = useFitColumns(
    tableWrapperRef,
    tableContainerRef,
    baseColumns,
    {
        baseEligibleKeys,
        lowestDroppableKey,
        highestHiddenKey,
        setFitHidden,
        resetFit,
    },
    {
        tableMode: () => !props.tree && !isCardLayout.value,
        active: () => !props.tree,
    },
);

// --- Responsive layout mode ---
// The flat table is replaced by a stacked card list when the viewport is below
// `md` OR the table cannot fit its container even at its minimum width (`mustCard`,
// e.g. a wide actions column on a narrow content area). Cards keep every field
// reachable without horizontal scrolling. Gated behind `mounted` so the server and
// first client render both produce the table — avoiding a full table↔cards
// hydration mismatch — then it switches after mount.
const breakpoints = useBreakpoints(breakpointsTailwind);
const mounted = ref(false);
onMounted(() => {
    mounted.value = true;
});
const isCardLayout = computed<boolean>(
    () =>
        mounted.value &&
        !props.tree &&
        (!breakpoints.greaterOrEqual('md').value || mustCard.value),
);

// The column rendered as each card's title (the pinned column, else the first).
const titleColumn = computed<ColumnConfig | null>(
    () =>
        props.columns.find((col) => col.alwaysVisible) ??
        props.columns[0] ??
        null,
);

// Sortable columns offered in the standalone sort control.
const sortableColumns = computed<ColumnConfig[]>(() =>
    props.columns.filter((col) => col.sortable),
);

// True when at least one column is hidden (auto-fit or breakpoint). Such columns
// have no clickable header, so the sort control below must be offered to reach them.
const hasHiddenColumns = computed<boolean>(
    () => visibleColumns.value.length < baseColumns.value.length,
);

// Show the standalone sort control whenever a header click cannot cover every
// sortable column: always in card mode (no headers), and in table mode as soon as
// any column is hidden.
const showSortControl = computed<boolean>(
    () =>
        columnSortingEnabled.value &&
        rowsLocal.value.length > 0 &&
        sortableColumns.value.length > 0 &&
        (isCardLayout.value || hasHiddenColumns.value),
);

// The server's default order column, but only when it is one of the sortable
// columns the dropdown offers (so a `position`/`id` default just shows no
// selection). Used to pre-fill the control before the user sorts explicitly.
const defaultSortColumn = computed<string | undefined>(() => {
    const key = props.filters.defaultSort ?? null;

    return key && sortableColumns.value.some((col) => col.key === key)
        ? key
        : undefined;
});

// What the control displays: the active sort if the user set one, otherwise the
// default order (ascending). Kept separate from `sortKey` on purpose — leaving
// `sortKey` null while no explicit sort is active keeps drag-and-drop enabled and
// the table header indicators quiet.
const effectiveSortKey = computed<string | undefined>(
    () => sortKey.value ?? defaultSortColumn.value,
);
const effectiveSortDir = computed<SortDirection>(() =>
    sortKey.value ? sortDir.value : 'asc',
);

// Sort control: the Select picks the column, a separate button flips the
// direction. Both commit to `sortKey`/`sortDir` and round-trip to the server for
// real DB columns.
const sortSelectKey = computed<string | undefined>({
    get: () => effectiveSortKey.value,
    set: (key) => {
        const col = key
            ? props.columns.find((c) => c.key === key && c.sortable)
            : undefined;

        if (!col) {
            return;
        }

        sortKey.value = col.key;
        sortDir.value = 'asc';

        if (isServerSorted(col.key)) {
            applyQuery();
        }
    },
});

function toggleSortDir(): void {
    const key = effectiveSortKey.value;

    if (!key) {
        return;
    }

    // Commit the (possibly default) column with the flipped direction.
    sortKey.value = key;
    sortDir.value = effectiveSortDir.value === 'asc' ? 'desc' : 'asc';

    if (isServerSorted(key)) {
        applyQuery();
    }
}

// When no column absorbs the slack itself, a trailing spacer cell keeps the
// content columns from ballooning to fill the `w-full` table.
const hasGrowColumn = computed<boolean>(() =>
    visibleColumns.value.some(isGrowColumn),
);

// --- Row expansion (per-row detail panel) ---
// Columns shown in the expanded detail panel; falls back to the collapsed-row
// columns when the page does not declare a separate set. A page-declared detail
// column without an explicit `type` inherits the rendering type (icon, image,
// badge, html, …) of the same-key table column, so e.g. an icon still renders as
// an icon in the detail panel without repeating the type on every field.
const expandedColumns = computed<ColumnConfig[]>(() => {
    const declared = props.expandedColumns;

    if (!declared) {
        return props.columns;
    }

    const byKey = new Map(props.columns.map((col) => [col.key, col]));

    return declared.map((col) => {
        const base = byKey.get(col.key);

        if (col.type || !base?.type) {
            return col;
        }

        return {
            ...col,
            type: base.type,
            imageKey: col.imageKey ?? base.imageKey,
            fallbackIcon: col.fallbackIcon ?? base.fallbackIcon,
            colorKey: col.colorKey ?? base.colorKey,
            iconKey: col.iconKey ?? base.iconKey,
        };
    });
});

// Copy field values to the clipboard (double-click a detail field / link icon).
const { copyText } = useCopy();

// Copy a detail field and highlight exactly the element that was double-clicked.
function copyField(
    event: MouseEvent,
    row: ResourceRow,
    col: ColumnConfig,
): void {
    copyText(copyableText(row, col), event.currentTarget as HTMLElement | null);
}

// IDs of the currently expanded rows. Reset when the underlying data changes
// (page/search change) — see the shared watch with the multi-select state.
const expandedIds = ref<Set<number>>(new Set());

function isExpanded(id: number): boolean {
    return expandedIds.value.has(id);
}

function toggleExpand(id: number): void {
    const next = new Set(expandedIds.value);

    if (next.has(id)) {
        next.delete(id);
    } else {
        next.add(id);
    }

    expandedIds.value = next;
}

// Number of columns a full-width detail row must span: selection + optional
// expand/drag columns + visible data columns + optional spacer + actions.
const columnSpan = computed<number>(
    () =>
        (selectable.value ? 1 : 0) +
        (props.expandable ? 1 : 0) +
        (dragEnabled.value ? 1 : 0) +
        visibleColumns.value.length +
        (hasGrowColumn.value || columnHasHiddenOverrides.value ? 0 : 1) +
        (showActions.value ? 1 : 0),
);

// Inline max-width for absolute-cap columns. Applied to BOTH the cell and its inner
// content element: on the inner block an absolute max-width actually clips the text
// (regardless of the auto table layout), and the cell/header then caps to it. This
// replaces the old `width` + `max-w-0` combo, where `max-width:0` (from `max-w-0`)
// beat the inline `width` and collapsed the column to nothing.
function capStyle(col: ColumnConfig): Record<string, string> {
    if (typeof col.width === 'number') {
        return { maxWidth: `${col.width}px` };
    }

    return isCapWidth(col) ? { maxWidth: String(col.width) } : {};
}

// Inline width for percentage columns — the proportional target the paired
// `max-w-0` (see widthClass) lets the auto-layout table honour.
function columnStyle(col: ColumnConfig): Record<string, string> {
    if (isPercentWidth(col)) {
        return { width: String(col.width) };
    }

    return capStyle(col);
}

// Width utility classes shared by header and body cells. `grow` absorbs the
// remaining table width; a percentage takes a fixed share. Both add `max-w-0` to
// drop the cell's intrinsic content minimum so the auto-layout table honours the
// target width and the inner `.truncate` clips. Absolute caps need no class — they
// clip via their inline `max-width` (capStyle) and stay content-sized below it.
function widthClass(col: ColumnConfig): string | undefined {
    if (isGrowColumn(col)) {
        return 'w-full max-w-0';
    }

    return isPercentWidth(col) ? 'max-w-0' : undefined;
}

function alignClass(col: ColumnConfig): string {
    if (col.align === 'right') {
        return 'text-right tabular-nums';
    }

    if (col.align === 'center') {
        return 'text-center';
    }

    return '';
}

function nowrapClass(col: ColumnConfig): string {
    return col.wrap ? 'whitespace-normal' : 'whitespace-nowrap';
}

// Measures the sticky bulk-selection bar so the table header can offset exactly
// below it without guessing at line-height + padding arithmetic.
const selectionBarEl = ref<HTMLElement | null>(null);
const selectionBarHeight = ref(0);

watchEffect((onCleanup) => {
    const el = selectionBarEl.value;

    if (!el) {
        selectionBarHeight.value = 0;

        return;
    }

    const ro = new ResizeObserver(() => {
        selectionBarHeight.value = el.offsetHeight;
    });
    ro.observe(el);
    selectionBarHeight.value = el.offsetHeight;
    onCleanup(() => ro.disconnect());
});

// Shared visual tokens for both table headers: the flat `<thead>` and the tree's
// flex header row (the tree body is a recursive flex/draggable layout, not a
// `<table>`, so it cannot reuse the `<thead>` element itself). Consuming one
// computed keeps their height, typography, background and sticky behaviour in
// lock-step so the two headers can never drift apart again. Sticks below the
// 70px app header (`AppSidebarHeader`), and shifts further down when the
// bulk-selection bar is visible (measured live via ResizeObserver).
const tableHeaderTop = computed(() => `${70 + selectionBarHeight.value}px`);
const TABLE_HEADER_CLASS = computed(
    () =>
        `sticky z-10 bg-muted/95 text-left text-xs font-medium text-muted-foreground backdrop-blur`,
);

function headerColumnClass(col: ColumnConfig): (string | undefined)[] {
    return [
        nowrapClass(col),
        alignClass(col),
        widthClass(col),
        col.class,
        col.sortable && columnSortingEnabled.value
            ? 'cursor-pointer select-none hover:text-foreground'
            : undefined,
    ];
}

function cellColumnClass(col: ColumnConfig): (string | undefined)[] {
    return [nowrapClass(col), alignClass(col), widthClass(col), col.class];
}

function cellContentClass(col: ColumnConfig): string {
    if (isTruncating(col)) {
        return 'truncate';
    }

    // When columns are redistributed, char-capped columns also get CSS truncation
    // so the td acts as the clipping boundary instead of the JS character limit.
    if (columnHasHiddenOverrides.value && col.truncate) {
        return 'truncate';
    }

    return '';
}

// When columns redistribute (spacer removed), the inner content div must not
// carry a max-width cap — the td is now the constraint and should expand freely.
function contentCapStyle(col: ColumnConfig): Record<string, string> {
    if (columnHasHiddenOverrides.value) {
        return {};
    }

    return capStyle(col);
}

// A cell's full plain-text value (keys joined, HTML stripped) for tooltips.
function fullCellText(row: ResourceRow, col: ColumnConfig): string | undefined {
    if (col.keys) {
        return joinKeys(row, col.keys);
    }

    const value = row[col.key];

    if (value === null || value === undefined || value === '') {
        return undefined;
    }

    if (col.type === 'html') {
        const el = document.createElement('div');
        el.innerHTML = String(value);

        return (el.textContent ?? '').replace(/\s+/g, ' ').trim() || undefined;
    }

    const str = String(value);

    return isUrl(str) ? displayUrl(str) : str;
}

// Full value shown as an AppTooltip whenever a cell's content is clipped — by a
// bounded width (CSS ellipsis) or by a `truncate` character cap. A pure character
// cap only earns a tooltip when the value was actually cut (longer than the limit).
function cellTitle(row: ResourceRow, col: ColumnConfig): string | undefined {
    const widthClipped = isTruncating(col);

    if (!widthClipped && !col.truncate) {
        return undefined;
    }

    const full = fullCellText(row, col);

    if (full === undefined) {
        return undefined;
    }

    if (!widthClipped && col.truncate && full.length <= col.truncate) {
        return undefined;
    }

    return full;
}

function trashedParam(): Record<string, unknown> {
    return showTrashed.value ? { trashed: 1 } : {};
}

function toggleTrashed() {
    const key = sortKey.value;
    const sortParams = isServerSorted(key)
        ? { sort: key, direction: sortDir.value }
        : {};

    router.get(
        props.endpoint,
        {
            search: search.value,
            perPage: Number(perPageValue.value),
            ...sortParams,
            ...filterParam(),
            ...props.extraQuery,
            // On toggling, set the target state (inverts the current view).
            ...(showTrashed.value ? {} : { trashed: 1 }),
        },
        { preserveState: true, replace: true, preserveScroll: true },
    );
}

// --- Search (debounced) ---
const search = ref(props.filters.search);

function applyQuery() {
    // Tree mode loads the full dataset and filters client-side (see
    // displayTreeNodes), hence no server round-trip.
    if (!props.showSearch || props.tree) {
        return;
    }

    const key = sortKey.value;
    const sortParams = isServerSorted(key)
        ? { sort: key, direction: sortDir.value }
        : {};

    router.get(
        props.endpoint,
        {
            search: search.value,
            perPage: Number(perPageValue.value),
            ...sortParams,
            ...filterParam(),
            ...props.extraQuery,
            ...trashedParam(),
        },
        { preserveState: true, replace: true, preserveScroll: true },
    );
}

// A page-owned parameter changed (e.g. the exclusion expression): reload the list.
watch(() => props.extraQuery, applyQuery, { deep: true });

let searchTimeout: ReturnType<typeof setTimeout> | undefined;
watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyQuery, 300);
});

// --- Tree search (client-side, preserving the ancestors) ---
function nodeMatches(node: TreeNode, query: string): boolean {
    return props.columns.some((col) =>
        String(node[col.key] ?? '')
            .toLowerCase()
            .includes(query),
    );
}

function filterTree(nodes: TreeNode[], query: string): TreeNode[] {
    const result: TreeNode[] = [];

    for (const node of nodes) {
        const children = filterTree(node._children, query);

        // The match itself OR a match among the children → keep the node
        // (so ancestors stay visible as context).
        if (nodeMatches(node, query) || children.length > 0) {
            result.push({ ...node, _children: children });
        }
    }

    return result;
}

const displayTreeNodes = computed<TreeNode[]>(() => {
    const query = search.value.toLowerCase().trim();

    return query ? filterTree(treeNodes.value, query) : treeNodes.value;
});

function flattenNodes(nodes: TreeNode[]): number[] {
    return nodes.flatMap((node) => [node.id, ...flattenNodes(node._children)]);
}

// IDs that are currently visible/selectable (tree: filtered hierarchy).
const visibleIds = computed<number[]>(() =>
    props.tree
        ? flattenNodes(displayTreeNodes.value)
        : rowsLocal.value.map((row) => row.id),
);

// While a tree search is active, disable reordering — otherwise a
// filtered sibling list would persist wrong positions.
const treeSortable = computed(
    () =>
        Boolean(props.sortable) &&
        search.value.trim() === '' &&
        !showTrashed.value,
);

watch(perPageValue, applyQuery);

// --- Panel / Form ---
// 'assign'    = assign existing entries (default "new" button)
// 'createNew' = capture a new record (separate button)
// 'edit'      = edit an existing entry
const panelOpen = ref(false);
const editingId = ref<number | null>(null);
const creatingNew = ref(false);
const form = useForm<FormData>(blankForm());

// Notify the page when the create/edit dialog opens, so page-specific slot
// state (e.g. an extraction result panel) can be reset for the fresh record.
const emit = defineEmits<{ open: [id: number | null] }>();
watch(panelOpen, (open) => {
    if (open) {
        emit('open', editingId.value);
    }
});

const activeFields = computed(() => {
    if (editingId.value !== null && props.editFields) {
        return props.editFields;
    }

    if (creatingNew.value && props.createNewFields) {
        return props.createNewFields;
    }

    return props.fields;
});

function openCreate() {
    editingId.value = null;
    creatingNew.value = false;
    form.defaults(blankForm());
    form.reset();
    form.clearErrors();
    panelOpen.value = true;
}

function openCreateNew() {
    editingId.value = null;
    creatingNew.value = true;
    form.defaults(blankForm());
    form.reset();
    form.clearErrors();
    panelOpen.value = true;
}

function fillFormFromRow(row: ResourceRow) {
    for (const field of activeFields.value) {
        if (field.type === 'section') {
            continue;
        }

        const value = row[field.name];

        if (field.type === 'checkbox') {
            form[field.name] = Boolean(value);
        } else if (field.type === 'select') {
            if (value === null || value === undefined) {
                const hasZeroOption = field.options?.some(
                    (opt: { value: string | number }) =>
                        opt.value === 0 || opt.value === '0',
                );
                form[field.name] = hasZeroOption ? '0' : '';
            } else {
                form[field.name] = String(value);
            }
        } else if (field.type === 'repeater' || field.type === 'multiselect') {
            form[field.name] = Array.isArray(value) ? [...value] : [];
        } else if (field.type === 'range') {
            form[field.name] =
                value === null || value === undefined
                    ? (field.min ?? 0)
                    : Number(value);
        } else {
            form[field.name] = value ?? '';
        }
    }
}

function openEdit(row: ResourceRow) {
    editingId.value = row.id;
    creatingNew.value = false;
    form.clearErrors();
    fillFormFromRow(row);
    panelOpen.value = true;
}

// Appends the current page's query string as `from` onto a detail URL, so a
// Show page that offers its own delete action (e.g. a resource with a dedicated detail page) can
// echo it back on the delete request and redirect to this same filtered
// index view afterwards instead of a bare, unfiltered `back()`.
function withReturnToIndex(url: string): string {
    const query = pageProps.url.split('?')[1];

    if (!query) {
        return url;
    }

    return `${url}${url.includes('?') ? '&' : '?'}from=${encodeURIComponent(query)}`;
}

// A double-click on a row runs exactly one action, chosen by priority:
// detail route > edit sheet > expand toggle. When the table is neither
// navigable, editable, nor expandable the double-click is inert.
function handleRowDblClick(row: ResourceRow): void {
    if (!showTrashed.value && props.detailRoute) {
        router.visit(withReturnToIndex(props.detailRoute(row)));

        return;
    }

    if (!showTrashed.value && canEdit.value) {
        openEdit(row);

        return;
    }

    if (props.expandable) {
        toggleExpand(row.id);
    }
}

// Whether double-clicking a row does anything — drives the cell cursor so a
// table with no interaction (not editable, not expandable, no detail route)
// shows the default cursor instead of a misleading pointer.
const rowIsInteractive = computed(
    () => Boolean(props.detailRoute) || canEdit.value || props.expandable,
);

function openCopy(row: ResourceRow) {
    editingId.value = null;
    creatingNew.value = false;
    form.defaults(blankForm());
    form.reset();
    form.clearErrors();
    fillFormFromRow(row);
    panelOpen.value = true;
}

async function submit() {
    // Extra fields the beforeSubmit guard asked to merge into the payload.
    let extra: Record<string, unknown> = {};

    if (props.beforeSubmit) {
        const decision = await props.beforeSubmit(form.data(), editingId.value);
        const proceed =
            typeof decision === 'boolean' ? decision : decision.proceed;

        if (!proceed) {
            return;
        }

        if (typeof decision !== 'boolean' && decision.merge) {
            extra = decision.merge;
        }
    }

    form.transform((data) => {
        const out: FormData = {};

        for (const [key, value] of Object.entries(data)) {
            if (Array.isArray(value)) {
                // Repeater: ignore empty/whitespace entries, keep the rest.
                out[key] = value.filter(
                    (entry) => typeof entry !== 'string' || entry.trim() !== '',
                );
            } else {
                // Empty strings → null (clean for nullable FKs / numbers).
                out[key] = value === '' ? null : value;
            }
        }

        // Page-specific fields injected by beforeSubmit are merged verbatim.
        return { ...out, ...extra };
    });

    const options = {
        preserveScroll: true,
        onSuccess: () => {
            panelOpen.value = false;
            editingId.value = null;
            creatingNew.value = false;
            form.reset();
        },
    };

    if (editingId.value !== null) {
        form.put(`${props.endpoint}/${editingId.value}`, options);
    } else if (creatingNew.value && props.createNewEndpoint) {
        form.post(props.createNewEndpoint, options);
    } else {
        form.post(props.bulkStoreEndpoint ?? props.endpoint, options);
    }
}

// Submit the form via Ctrl/Cmd+Enter. Capture phase so that the rich-text editor
// (Tiptap) does not intercept the key combination first; double submit is prevented.
function onFormKeydown(event: KeyboardEvent) {
    if ((event.ctrlKey || event.metaKey) && event.key === 'Enter') {
        event.preventDefault();

        if (!form.processing) {
            submit();
        }
    }
}

// --- Delete (with confirmation dialog) ---
const deleteTarget = ref<ResourceRow | null>(null);
const confirmOpen = ref(false);

function destroy(row: ResourceRow) {
    deleteTarget.value = row;
    confirmOpen.value = true;
}

function confirmDelete() {
    const row = deleteTarget.value;

    if (!row) {
        return;
    }

    router.delete(`${props.endpoint}/${row.id}`, {
        preserveScroll: true,
        onFinish: () => {
            confirmOpen.value = false;
            deleteTarget.value = null;
        },
    });
}

// --- Restore / permanent delete (trash) ---
// Restore is harmless → without confirmation. Permanent delete is
// irreversible → with a confirmation dialog.
function restore(row: ResourceRow) {
    router.post(
        `${props.endpoint}/${row.id}/restore`,
        {},
        { preserveScroll: true },
    );
}

const forceTarget = ref<ResourceRow | null>(null);
const confirmForceOpen = ref(false);

function forceDestroy(row: ResourceRow) {
    forceTarget.value = row;
    confirmForceOpen.value = true;
}

function confirmForceDelete() {
    const row = forceTarget.value;

    if (!row) {
        return;
    }

    router.delete(`${props.endpoint}/${row.id}/force`, {
        preserveScroll: true,
        onFinish: () => {
            confirmForceOpen.value = false;
            forceTarget.value = null;
        },
    });
}

// --- Multi-select (table view only) ---
const selectedIds = ref<number[]>([]);

// Reset the selection and any expanded rows as soon as the data set changes
// (e.g. page change) — the row ids no longer refer to the visible records.
watch(
    () => props.rows.data,
    () => {
        selectedIds.value = [];
        expandedIds.value = new Set();
    },
);

function isSelected(id: number): boolean {
    return selectedIds.value.includes(id);
}

function toggleRow(id: number, value: boolean | 'indeterminate') {
    if (value) {
        if (!selectedIds.value.includes(id)) {
            selectedIds.value = [...selectedIds.value, id];
        }
    } else {
        selectedIds.value = selectedIds.value.filter((x) => x !== id);
    }
}

const headerChecked = computed<boolean | 'indeterminate'>(() => {
    if (visibleIds.value.length === 0 || selectedIds.value.length === 0) {
        return false;
    }

    return selectedIds.value.length >= visibleIds.value.length
        ? true
        : 'indeterminate';
});

function toggleAll(value: boolean | 'indeterminate') {
    selectedIds.value = value ? [...visibleIds.value] : [];
}

function clearSelection() {
    selectedIds.value = [];
}

const confirmBulkOpen = ref(false);

function bulkDelete() {
    if (selectedIds.value.length === 0) {
        return;
    }

    router.post(
        `${props.endpoint}/bulk-destroy`,
        { ids: selectedIds.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                selectedIds.value = [];
            },
            onFinish: () => {
                confirmBulkOpen.value = false;
            },
        },
    );
}

const confirmBulkForceOpen = ref(false);

function bulkRestore() {
    if (selectedIds.value.length === 0) {
        return;
    }

    router.post(
        `${props.endpoint}/bulk-restore`,
        { ids: selectedIds.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                selectedIds.value = [];
            },
        },
    );
}

function bulkForceDelete() {
    if (selectedIds.value.length === 0) {
        return;
    }

    router.post(
        `${props.endpoint}/bulk-force-destroy`,
        { ids: selectedIds.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                selectedIds.value = [];
            },
            onFinish: () => {
                confirmBulkForceOpen.value = false;
            },
        },
    );
}
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-4 px-4 pb-4 md:px-6 md:pb-6">
        <div
            ref="tableContainerRef"
            class="overflow-clip rounded-2xl border border-border bg-card text-card-foreground shadow-card"
        >
            <!-- Optional header prefix (e.g. tab navigation injected by the page) -->
            <slot name="headerPrefix" />

            <!-- Card header: title + search + action -->
            <div
                class="flex flex-wrap items-center justify-between gap-3 border-b border-border px-6 py-5"
            >
                <div class="flex flex-wrap items-center gap-2">
                    <h4
                        v-if="title ?? true"
                        class="mr-2 text-lg font-semibold text-foreground"
                    >
                        {{ title ?? `${singular} list` }}
                    </h4>
                    <SearchInput
                        v-if="showSearch"
                        v-model="search"
                        class="w-full sm:w-60"
                    />
                    <template v-if="facetDefs.length > 0">
                        <ResourceFacetFilter
                            v-for="facet in facetDefs.filter(
                                (f) => !hiddenFacetKeys?.includes(f.key),
                            )"
                            :key="facet.key"
                            :facet="facet"
                            :selected="facetSelections[facet.key] ?? []"
                            @toggle="toggleFacetValue"
                            @update:selected="
                                (v) => updateFacetValues(facet.key, v)
                            "
                        />
                    </template>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <!-- Sort control in table mode: appears here in the header bar
                         as soon as columns are hidden (whose header is no longer
                         clickable). In card mode it sits as its own row above the
                         list. -->
                    <ResourceSortControl
                        v-if="showSortControl && !isCardLayout"
                        v-model="sortSelectKey"
                        :active-key="effectiveSortKey ?? null"
                        :columns="sortableColumns"
                        :direction="effectiveSortDir"
                        @toggle-direction="toggleSortDir"
                    />

                    <!-- Page-supplied toolbar actions, rendered alongside the
                         built-in column/trash buttons (e.g. a
                         custom "Sync" button). -->
                    <slot name="toolbarActions" />

                    <ColumnVisibilityMenu
                        v-if="!tree"
                        :entries="columnEntries"
                        :has-overrides="columnHasOverrides"
                        @reset="resetColumns"
                        @toggle="toggleColumn"
                    />

                    <template v-if="!showTrashed && !readonly">
                        <Button class="h-10" @click="openCreate">
                            <Plus class="size-4" />
                            {{ addLabel ?? `New ${singular}` }}
                        </Button>
                        <Button
                            v-if="createNewFields && createNewEndpoint"
                            class="h-10"
                            variant="outline"
                            @click="openCreateNew"
                        >
                            <Plus class="size-4" />
                            {{ createNewLabel ?? 'Create new' }}
                        </Button>
                    </template>

                    <!-- Trash toggle: only for SoftDeletes resources -->
                    <Button
                        v-if="softDeletes"
                        :variant="showTrashed ? 'default' : 'outline'"
                        class="h-10"
                        @click="toggleTrashed"
                    >
                        <List v-if="showTrashed" class="size-4" />
                        <Trash2 v-else class="size-4" />
                        <template v-if="showTrashed">Show active</template>
                        <template v-else>
                            Trash
                            <span
                                v-if="trashedCount > 0"
                                class="ml-1 inline-flex min-w-5 items-center justify-center rounded-full bg-muted px-1.5 text-xs font-medium text-muted-foreground"
                            >
                                {{ trashedCount }}
                            </span>
                        </template>
                    </Button>
                </div>
            </div>

            <!-- Selection bar (multi-select) -->
            <div
                v-if="selectedIds.length > 0"
                ref="selectionBarEl"
                class="sticky top-[70px] z-20 flex flex-wrap items-center justify-between gap-3 border-b border-border bg-accent px-6 py-3"
            >
                <span class="text-sm font-medium text-foreground">
                    {{ selectedIds.length }} selected
                </span>
                <div class="flex items-center gap-2">
                    <Button size="sm" variant="ghost" @click="clearSelection">
                        Clear selection
                    </Button>
                    <!-- Page-supplied bulk actions (e.g. bulk
                         archive) replace the built-in delete/restore buttons. -->
                    <slot
                        v-if="$slots.bulkActions"
                        :clear="clearSelection"
                        :ids="selectedIds"
                        :trashed="showTrashed"
                        name="bulkActions"
                    />
                    <template v-else-if="showTrashed">
                        <Button
                            size="sm"
                            variant="outline"
                            @click="bulkRestore"
                        >
                            <RotateCcw class="size-4" />
                            Restore ({{ selectedIds.length }})
                        </Button>
                        <Button
                            size="sm"
                            variant="destructive"
                            @click="confirmBulkForceOpen = true"
                        >
                            <Trash2 class="size-4" />
                            Delete permanently ({{ selectedIds.length }})
                        </Button>
                    </template>
                    <Button
                        v-else
                        size="sm"
                        variant="destructive"
                        @click="confirmBulkOpen = true"
                    >
                        <Trash2 class="size-4" />
                        Delete ({{ selectedIds.length }})
                    </Button>
                </div>
            </div>

            <!-- Hierarchical view (indented, reordering constrained to each parent group) -->
            <template v-if="tree">
                <div
                    :class="[
                        TABLE_HEADER_CLASS,
                        'flex items-center gap-2 border-y border-border px-5 py-3',
                    ]"
                    :style="{ top: tableHeaderTop }"
                >
                    <span v-if="selectable" class="w-5 shrink-0">
                        <Checkbox
                            :model-value="headerChecked"
                            aria-label="Select all"
                            @update:model-value="toggleAll"
                        />
                    </span>
                    <span v-if="treeSortable" class="w-4" />
                    <span class="w-14 shrink-0">Pos.</span>
                    <span
                        v-for="col in columns"
                        :key="col.key"
                        :class="[col.class, treeColumnClass(col)]"
                        :style="treeColumnStyle(col)"
                        class="truncate"
                    >
                        {{ col.label }}
                    </span>
                    <span
                        :style="{ width: treeActionsWidth }"
                        class="ml-auto shrink-0 text-right"
                        >Actions</span
                    >
                </div>

                <ResourceTreeLevel
                    :actions-width="treeActionsWidth"
                    :columns="columns"
                    :nodes="displayTreeNodes"
                    :parent-id="null"
                    :selectable="selectable"
                    :selected-ids="selectedIds"
                    :show-copy="canCopy"
                    :show-delete="canDelete"
                    :show-edit="canEdit"
                    :sortable="treeSortable"
                    @copy="openCopy"
                    @edit="openEdit"
                    @remove="destroy"
                    @reorder="persistTreeOrder"
                    @toggle="toggleRow"
                />

                <div
                    v-if="displayTreeNodes.length === 0"
                    class="flex flex-col items-center justify-center gap-3 px-4 py-16 text-center"
                >
                    <div
                        class="flex size-12 items-center justify-center rounded-full bg-muted text-muted-foreground"
                    >
                        <Inbox class="size-5" />
                    </div>
                    <p class="text-sm text-muted-foreground">
                        No entries found.
                    </p>
                    <Button size="sm" variant="outline" @click="openCreate">
                        <Plus class="size-4" />
                        New {{ singular }}
                    </Button>
                </div>
            </template>

            <!-- Table view (cards below md) -->
            <template v-else>
                <!-- Sort control in card mode (no clickable table header); in
                     table mode the same control sits at the top of the header
                     bar. -->
                <div
                    v-if="showSortControl && isCardLayout"
                    class="flex items-center gap-2 border-b border-border px-4 py-3"
                >
                    <span class="text-sm text-muted-foreground">Sortieren</span>
                    <ResourceSortControl
                        v-model="sortSelectKey"
                        :active-key="effectiveSortKey ?? null"
                        :columns="sortableColumns"
                        :direction="effectiveSortDir"
                        full-width
                        @toggle-direction="toggleSortDir"
                    />
                </div>

                <!-- On desktop the wrapper stays overflow-visible so the sticky
                     <thead> resolves against the page (SidebarInset) like the app
                     header — never a scroll container. The auto-fit keeps the table
                     within the container; when even the minimum table cannot fit,
                     `mustCard` switches the whole layout to cards below (no
                     horizontal scroll, nothing clipped). Below md it is cards too. -->
                <div
                    v-show="!isCardLayout"
                    ref="tableWrapperRef"
                    class="overflow-x-auto md:overflow-visible"
                >
                    <draggable
                        v-if="!isCardLayout"
                        v-model="rowsDisplay"
                        :disabled="!dragEnabled || sortKey !== null"
                        class="w-full text-sm"
                        handle=".drag-handle"
                        item-key="id"
                        tag="table"
                        @end="onDragEnd"
                    >
                        <template #header>
                            <thead
                                :class="TABLE_HEADER_CLASS"
                                :style="{ top: tableHeaderTop }"
                            >
                                <tr class="border-y border-border">
                                    <th
                                        v-if="selectable"
                                        class="w-10 py-3 pr-2 pl-5"
                                    >
                                        <Checkbox
                                            :model-value="headerChecked"
                                            aria-label="Select all"
                                            @update:model-value="toggleAll"
                                        />
                                    </th>
                                    <th
                                        v-if="expandable"
                                        class="w-8 px-2 py-4"
                                    ></th>
                                    <th
                                        v-if="dragEnabled"
                                        class="w-8 px-2 py-4"
                                    ></th>
                                    <th
                                        v-for="col in visibleColumns"
                                        :key="col.key"
                                        :class="headerColumnClass(col)"
                                        :data-col-key="col.key"
                                        :style="columnStyle(col)"
                                        class="px-4 py-3 text-xs font-medium"
                                        @click="toggleSort(col)"
                                    >
                                        <span
                                            class="inline-flex items-center gap-1.5"
                                        >
                                            {{ col.label }}
                                            <span
                                                v-if="
                                                    col.sortable &&
                                                    columnSortingEnabled
                                                "
                                                class="inline-flex flex-col text-muted-foreground/60"
                                            >
                                                <ChevronUp
                                                    v-if="
                                                        sortKey === col.key &&
                                                        sortDir === 'asc'
                                                    "
                                                    class="size-3.5 text-primary"
                                                />
                                                <ChevronDown
                                                    v-else-if="
                                                        sortKey === col.key &&
                                                        sortDir === 'desc'
                                                    "
                                                    class="size-3.5 text-primary"
                                                />
                                                <ChevronsUpDown
                                                    v-else
                                                    class="size-3.5"
                                                />
                                            </span>
                                        </span>
                                    </th>
                                    <!-- Absorbs remaining width so content columns
                                     stay content-sized when none is `grow`. -->
                                    <th
                                        v-if="
                                            !hasGrowColumn &&
                                            !columnHasHiddenOverrides
                                        "
                                        aria-hidden="true"
                                        class="w-full"
                                        data-spacer
                                    ></th>
                                    <th
                                        v-if="showActions"
                                        class="px-4 py-3 text-right font-medium"
                                    >
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                        </template>

                        <template #item="{ element: row }">
                            <tbody>
                                <ResourceRowContextMenu
                                    :row="row"
                                    :show-copy="canCopy"
                                    :show-delete="canDelete"
                                    :show-edit="canEdit"
                                    :show-trashed="showTrashed"
                                    @copy="openCopy"
                                    @edit="openEdit"
                                    @force="forceDestroy"
                                    @remove="destroy"
                                    @restore="restore"
                                >
                                    <template #actions="{ row: actionRow }">
                                        <slot
                                            :row="actionRow"
                                            name="rowActions"
                                        />
                                    </template>
                                    <template #trigger>
                                        <tr
                                            :class="[
                                                isSelected(row.id)
                                                    ? selectedRowClass(
                                                          row,
                                                          rowTint,
                                                      )
                                                    : rowTintRowClass(
                                                          row,
                                                          rowTint,
                                                      ),
                                                isExpanded(row.id)
                                                    ? ''
                                                    : 'border-b border-border',
                                            ]"
                                            class="border-l-4 transition-colors"
                                            @dblclick="handleRowDblClick(row)"
                                        >
                                            <td
                                                v-if="selectable"
                                                class="w-10 py-2 pr-2 pl-5"
                                                @dblclick.stop
                                            >
                                                <Checkbox
                                                    :aria-label="`Select row ${row.id}`"
                                                    :model-value="
                                                        isSelected(row.id)
                                                    "
                                                    @update:model-value="
                                                        (v) =>
                                                            toggleRow(row.id, v)
                                                    "
                                                />
                                            </td>
                                            <td
                                                v-if="expandable"
                                                class="w-8 px-2 text-center"
                                                @dblclick.stop
                                            >
                                                <button
                                                    :aria-expanded="
                                                        isExpanded(row.id)
                                                    "
                                                    :aria-label="
                                                        isExpanded(row.id)
                                                            ? 'Collapse row'
                                                            : 'Expand row'
                                                    "
                                                    class="inline-flex size-7 items-center justify-center rounded-full text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                                                    type="button"
                                                    @click="
                                                        toggleExpand(row.id)
                                                    "
                                                >
                                                    <ChevronRight
                                                        :class="
                                                            isExpanded(row.id)
                                                                ? 'rotate-90'
                                                                : ''
                                                        "
                                                        class="size-4 transition-transform"
                                                    />
                                                </button>
                                            </td>
                                            <td
                                                v-if="dragEnabled"
                                                class="drag-handle w-8 cursor-move px-2 text-center text-muted-foreground"
                                            >
                                                <GripVertical
                                                    class="inline size-4"
                                                />
                                            </td>
                                            <td
                                                v-for="col in visibleColumns"
                                                :key="col.key"
                                                :class="[
                                                    cellColumnClass(col),
                                                    rowIsInteractive
                                                        ? 'cursor-pointer'
                                                        : 'cursor-default',
                                                ]"
                                                :style="columnStyle(col)"
                                                class="px-4 py-2"
                                            >
                                                <AppTooltip
                                                    :text="
                                                        cellTitle(row, col) ??
                                                        ''
                                                    "
                                                >
                                                    <div
                                                        :class="
                                                            cellContentClass(
                                                                col,
                                                            )
                                                        "
                                                        :style="
                                                            contentCapStyle(col)
                                                        "
                                                    >
                                                        <ResourceCell
                                                            :col="col"
                                                            :row="row"
                                                        />
                                                    </div>
                                                </AppTooltip>
                                            </td>
                                            <td
                                                v-if="
                                                    !hasGrowColumn &&
                                                    !columnHasHiddenOverrides
                                                "
                                                aria-hidden="true"
                                            ></td>
                                            <td
                                                v-if="showActions"
                                                class="px-4 py-2"
                                                @dblclick.stop
                                            >
                                                <ResourceRowActions
                                                    :row="row"
                                                    :show-copy="canCopy"
                                                    :show-delete="canDelete"
                                                    :show-edit="canEdit"
                                                    :show-trashed="showTrashed"
                                                    @copy="openCopy"
                                                    @edit="openEdit"
                                                    @force="forceDestroy"
                                                    @remove="destroy"
                                                    @restore="restore"
                                                >
                                                    <template
                                                        #default="{
                                                            row: actionRow,
                                                        }"
                                                    >
                                                        <AppTooltip
                                                            v-if="detailRoute"
                                                            text="Open details"
                                                        >
                                                            <Link
                                                                :href="
                                                                    withReturnToIndex(
                                                                        detailRoute(
                                                                            actionRow,
                                                                        ),
                                                                    )
                                                                "
                                                                aria-label="Open details"
                                                                class="inline-flex size-7 items-center justify-center rounded-full text-muted-foreground transition-colors hover:bg-primary/10 hover:text-primary"
                                                            >
                                                                <Eye
                                                                    class="size-4"
                                                                />
                                                            </Link>
                                                        </AppTooltip>
                                                        <slot
                                                            :row="actionRow"
                                                            name="rowActions"
                                                        />
                                                    </template>
                                                </ResourceRowActions>
                                            </td>
                                        </tr>
                                    </template>
                                </ResourceRowContextMenu>
                                <!-- Expanded detail view: all configured columns
                                     vertically, full table width. -->
                                <tr
                                    v-if="expandable && isExpanded(row.id)"
                                    class="border-b border-border bg-muted/20"
                                >
                                    <td :colspan="columnSpan" class="px-6 py-5">
                                        <dl
                                            class="grid grid-cols-1 gap-x-8 gap-y-4 sm:grid-cols-2 lg:grid-cols-3"
                                        >
                                            <div
                                                v-for="col in expandedColumns"
                                                :key="col.key"
                                                class="flex flex-col gap-0.5"
                                            >
                                                <dt
                                                    class="text-xs font-medium text-muted-foreground"
                                                >
                                                    {{ col.label }}
                                                </dt>
                                                <dd
                                                    class="text-sm text-foreground"
                                                >
                                                    <!-- Only the text itself is the
                                                         copy target (inline-block),
                                                         not the whole cell. Empty
                                                         fields (shown as "-") are not
                                                         copyable. -->
                                                    <AppTooltip
                                                        :text="
                                                            hasCopyableContent(
                                                                row,
                                                                col,
                                                            )
                                                                ? 'Double-click to copy'
                                                                : ''
                                                        "
                                                    >
                                                        <span
                                                            :class="
                                                                hasCopyableContent(
                                                                    row,
                                                                    col,
                                                                )
                                                                    ? 'cursor-copy'
                                                                    : ''
                                                            "
                                                            class="inline-block max-w-full align-top"
                                                            @dblclick.stop="
                                                                hasCopyableContent(
                                                                    row,
                                                                    col,
                                                                ) &&
                                                                copyField(
                                                                    $event,
                                                                    row,
                                                                    col,
                                                                )
                                                            "
                                                        >
                                                            <ResourceCell
                                                                :col="col"
                                                                :row="row"
                                                                detail
                                                                @copy="copyText"
                                                            />
                                                        </span>
                                                    </AppTooltip>
                                                </dd>
                                            </div>
                                        </dl>
                                    </td>
                                </tr>
                            </tbody>
                        </template>
                    </draggable>
                </div>

                <!-- Card view (below md): each row as a vertical card instead of a
                     horizontally scrolling table row. -->
                <draggable
                    v-if="isCardLayout && rowsLocal.length > 0"
                    v-model="rowsDisplay"
                    :disabled="!dragEnabled || sortKey !== null"
                    class="flex flex-col gap-3 p-4"
                    handle=".drag-handle"
                    item-key="id"
                    tag="ul"
                    @end="onDragEnd"
                >
                    <template #item="{ element: row }">
                        <ResourceCard
                            :columns="expandedColumns"
                            :drag-enabled="dragEnabled"
                            :readonly="readonly"
                            :row="row"
                            :selectable="selectable"
                            :selected="isSelected(row.id)"
                            :show-copy="canCopy"
                            :show-delete="canDelete"
                            :show-edit="canEdit"
                            :show-trashed="showTrashed"
                            :tint="rowTint?.(row) ?? null"
                            :title-column="titleColumn"
                            @copy="openCopy"
                            @edit="openEdit"
                            @force="forceDestroy"
                            @remove="destroy"
                            @restore="restore"
                            @toggle="toggleRow"
                        >
                            <template #rowActions="{ row: actionRow }">
                                <slot :row="actionRow" name="rowActions" />
                            </template>
                        </ResourceCard>
                    </template>
                </draggable>

                <div
                    v-if="rowsLocal.length === 0"
                    class="flex flex-col items-center justify-center gap-3 px-4 py-16 text-center"
                >
                    <div
                        class="flex size-12 items-center justify-center rounded-full bg-muted text-muted-foreground"
                    >
                        <Trash2 v-if="showTrashed" class="size-5" />
                        <Inbox v-else class="size-5" />
                    </div>
                    <p class="text-sm text-muted-foreground">
                        {{
                            showTrashed
                                ? 'The trash is empty.'
                                : 'No entries found.'
                        }}
                    </p>
                    <Button
                        v-if="!showTrashed && !readonly"
                        size="sm"
                        variant="outline"
                        @click="openCreate"
                    >
                        <Plus class="size-4" />
                        New {{ singular }}
                    </Button>
                </div>

                <!-- Pagination -->
                <div
                    v-if="rowsLocal.length > 0 && showSearch"
                    class="flex flex-wrap items-center justify-between gap-3 border-t border-border px-5 py-4"
                >
                    <!-- Left: showing info + per-page selector -->
                    <div
                        class="flex items-center gap-2 text-sm text-muted-foreground"
                    >
                        <span
                            v-if="
                                rows.from != null &&
                                rows.to != null &&
                                rows.total != null
                            "
                        >
                            Showing
                            <span class="font-medium text-foreground">{{
                                rows.from
                            }}</span>
                            –
                            <span class="font-medium text-foreground">{{
                                rows.to
                            }}</span>
                            of
                            <span class="font-medium text-foreground">{{
                                rows.total
                            }}</span>
                            entries
                        </span>
                        <span v-else>Per page</span>

                        <Select v-model="perPageValue">
                            <SelectTrigger class="h-7 w-[4rem] text-xs">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="size in pageSizes"
                                    :key="size"
                                    :value="String(size)"
                                >
                                    {{ size }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Right: page number buttons -->
                    <div
                        v-if="rows.links.length > 3"
                        class="flex items-center gap-1"
                    >
                        <!-- Prev arrow -->
                        <AppTooltip side="top" text="Previous">
                            <button
                                :disabled="!rows.links[0]?.url"
                                class="inline-flex size-8 items-center justify-center rounded-lg border border-border text-muted-foreground transition-colors hover:bg-muted hover:text-foreground disabled:pointer-events-none disabled:opacity-40"
                                type="button"
                                @click="
                                    rows.links[0]?.url &&
                                    router.get(
                                        rows.links[0].url,
                                        {},
                                        {
                                            preserveState: true,
                                            preserveScroll: true,
                                        },
                                    )
                                "
                            >
                                <ChevronLeft class="size-4" />
                            </button>
                        </AppTooltip>

                        <!-- Page numbers (skip first and last link = prev/next) -->
                        <template v-for="(link, i) in rows.links" :key="i">
                            <button
                                v-if="i > 0 && i < rows.links.length - 1"
                                :class="
                                    link.active
                                        ? 'border-primary bg-primary text-primary-foreground'
                                        : 'border-border text-muted-foreground hover:bg-muted hover:text-foreground disabled:pointer-events-none disabled:opacity-40'
                                "
                                :disabled="!link.url"
                                class="inline-flex size-8 items-center justify-center rounded-lg border text-sm transition-colors"
                                type="button"
                                @click="
                                    link.url &&
                                    router.get(
                                        link.url,
                                        {},
                                        {
                                            preserveState: true,
                                            preserveScroll: true,
                                        },
                                    )
                                "
                            >
                                <span v-html="link.label" />
                            </button>
                        </template>

                        <!-- Next arrow -->
                        <AppTooltip side="top" text="Next">
                            <button
                                :disabled="
                                    !rows.links[rows.links.length - 1]?.url
                                "
                                class="inline-flex size-8 items-center justify-center rounded-lg border border-border text-muted-foreground transition-colors hover:bg-muted hover:text-foreground disabled:pointer-events-none disabled:opacity-40"
                                type="button"
                                @click="
                                    rows.links[rows.links.length - 1]?.url &&
                                    router.get(
                                        rows.links[rows.links.length - 1].url!,
                                        {},
                                        {
                                            preserveState: true,
                                            preserveScroll: true,
                                        },
                                    )
                                "
                            >
                                <ChevronRight class="size-4" />
                            </button>
                        </AppTooltip>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <Sheet v-model:open="panelOpen">
        <SheetContent
            class="flex w-full flex-col gap-0 bg-card p-0 sm:max-w-2xl lg:max-w-3xl"
            side="right"
        >
            <SheetHeader class="border-b px-6 py-5">
                <SheetTitle>
                    <template v-if="editingId !== null">
                        Edit {{ singular }}
                    </template>
                    <template v-else-if="creatingNew">
                        {{ createNewLabel ?? 'Create new' }}: {{ singular }}
                    </template>
                    <template v-else> New {{ singular }} </template>
                </SheetTitle>
                <SheetDescription>
                    Fill in the fields and save (Ctrl/Cmd + Enter).
                </SheetDescription>
            </SheetHeader>

            <form
                id="resource-form"
                class="grid flex-1 auto-rows-min grid-cols-1 content-start items-start gap-x-6 gap-y-5 overflow-y-auto p-6 sm:grid-cols-2"
                @submit.prevent="submit"
                @keydown.capture="onFormKeydown"
            >
                <ResourceFormFields :fields="activeFields" :form="form">
                    <!-- Forward page-provided per-field slots (e.g.
                     `after-<field>`) down into the field grid. -->
                    <template
                        v-for="(_, slotName) in $slots"
                        :key="slotName"
                        #[slotName]="slotProps"
                    >
                        <slot :name="slotName" v-bind="slotProps ?? {}" />
                    </template>
                </ResourceFormFields>
                <!-- Page-specific controls inside the form grid (e.g. a
                 custom helper button). -->
                <slot :editing-id="editingId" :form="form" name="formExtra" />
            </form>

            <SheetFooter class="flex-row justify-end gap-3 border-t px-6 py-4">
                <Button
                    type="button"
                    variant="outline"
                    @click="panelOpen = false"
                >
                    Cancel
                </Button>
                <Button
                    :disabled="form.processing"
                    form="resource-form"
                    type="submit"
                >
                    Save
                </Button>
            </SheetFooter>
        </SheetContent>
    </Sheet>

    <Dialog v-model:open="confirmOpen">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Delete {{ singular }}?</DialogTitle>
                <DialogDescription>
                    This entry will be permanently removed. This action cannot
                    be undone.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button variant="outline" @click="confirmOpen = false">
                    Cancel
                </Button>
                <Button variant="destructive" @click="confirmDelete">
                    <Trash2 class="size-4" />
                    Delete
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <Dialog v-model:open="confirmBulkOpen">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>
                    Delete {{ selectedIds.length }} entries?
                </DialogTitle>
                <DialogDescription>
                    The selected entries will be permanently removed. This
                    action cannot be undone.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button variant="outline" @click="confirmBulkOpen = false">
                    Cancel
                </Button>
                <Button variant="destructive" @click="bulkDelete">
                    <Trash2 class="size-4" />
                    Delete {{ selectedIds.length }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <Dialog v-model:open="confirmForceOpen">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Permanently delete {{ singular }}?</DialogTitle>
                <DialogDescription>
                    This entry will be irrevocably removed from the database and
                    cannot be restored.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button variant="outline" @click="confirmForceOpen = false">
                    Cancel
                </Button>
                <Button variant="destructive" @click="confirmForceDelete">
                    <Trash2 class="size-4" />
                    Delete permanently
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <Dialog v-model:open="confirmBulkForceOpen">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>
                    Permanently delete {{ selectedIds.length }} entries?
                </DialogTitle>
                <DialogDescription>
                    The selected entries will be irrevocably removed from the
                    database and cannot be restored.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button variant="outline" @click="confirmBulkForceOpen = false">
                    Cancel
                </Button>
                <Button variant="destructive" @click="bulkForceDelete">
                    <Trash2 class="size-4" />
                    Permanently delete {{ selectedIds.length }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
