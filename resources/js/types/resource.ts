export interface PaginatorLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface Paginator<T> {
    data: T[];
    links: PaginatorLink[];
    from: number | null;
    to: number | null;
    total: number;
}

export interface ColumnConfig {
    /** Key into the row object. */
    key: string;
    label: string;
    /**
     * When set, the cell renders the values of these row keys joined by a single
     * space (empty values skipped), letting several fields share one column
     * (e.g. PLZ + Ort). Sorting still uses `key`.
     */
    keys?: string[];
    /** Optional extra classes for the cell (e.g. text alignment, width). */
    class?: string;
    /** When true, clicking the header sorts the table client-side by this column. */
    sortable?: boolean;
    /**
     * 'icon' renders a Lucide/Simple-Icons preview alongside the stored value.
     * 'html' renders the stored value as rendered HTML (for rich-text fields).
     * 'markdown' renders the stored value as rendered Markdown (headings, lists,
     * bold …). In the compact table a plain-text preview is shown instead.
     * 'datetime' renders the stored value as a localized date/time (e.g. deleted_at).
     * 'date' renders the stored value as a localized date only (no time).
     * 'image' renders the stored URL as a small thumbnail, with a neutral
     * placeholder icon when no value is present.
     * 'image-text' renders a small thumbnail (from `imageKey`) next to the text
     * value stored under `key`, sharing a single column.
     * 'badge' renders the `key` text as a coloured badge, using the colour token
     * from `colorKey` and (optionally) a leading icon from `iconKey`.
     * 'password' hides the stored value behind a "Show password" toggle,
     * revealing it (and offering a copy button in detail context) only on click.
     */
    type?:
        | 'icon'
        | 'html'
        | 'markdown'
        | 'datetime'
        | 'date'
        | 'image'
        | 'image-text'
        | 'badge'
        | 'password';
    /**
     * Suppress the automatic link rendering: a default (untyped) column whose value
     * looks like a URL is normally shown as a clickable link. Set `true` to render
     * it as plain text instead (e.g. an identifier that happens to be URL-shaped).
     */
    noLink?: boolean;
    /**
     * For the 'datetime' and 'date' types: a PHP `date()`-style pattern overriding
     * the default rendering (`j M Y, H:i` for datetime, `j M Y` for date). Tokens:
     * `d`/`j` day, `m`/`n`/`M` month, `Y`/`y` year, `H`/`G` hour, `i` minute, `s`
     * second — any other character is literal (see `formatWithPattern`). Example:
     * `'M j, Y'` renders e.g. `Aug 2, 2026`.
     */
    dateFormat?: string;
    /**
     * For the 'image-text' type: the row key holding the thumbnail URL rendered
     * alongside the `key` text value.
     */
    imageKey?: string;
    /**
     * For the 'image' and 'image-text' types: the Lucide icon name (kebab-case,
     * e.g. 'factory') rendered as the placeholder when no image is present or the
     * image fails to load. Defaults to a neutral image icon.
     */
    fallbackIcon?: string;
    /** For the 'badge' type: the row key holding the colour token. */
    colorKey?: string;
    /** For the 'badge' type: the row key holding the icon reference (optional). */
    iconKey?: string;
    /**
     * A small secondary line rendered in muted text beneath the cell's primary
     * value (in the flat table, the tree and the row detail panel). A fixed free
     * text — e.g. a static hint or unit description. When `subtitleKey` is also
     * set and that row key holds a value, the dynamic value wins and this acts as
     * the fallback. Independent of the column `type`, so any column can carry one.
     */
    subtitle?: string;
    /**
     * Row key whose value renders as the cell subtitle (see `subtitle`) — lets a
     * second DB column ride along under the primary one (e.g. a code under a name).
     * Empty values fall back to the static `subtitle` text, or render nothing.
     */
    subtitleKey?: string;
    /**
     * When set, limits the cell's text to this many characters (HTML stripped),
     * appending an ellipsis when cut. It caps the *text* only, never the cell
     * structure or formatting: every type keeps its rendering — an 'image-text'
     * column keeps its thumbnail/icon, a link stays clickable, a 'badge' keeps its
     * coloured pill, an 'icon' keeps its glyph, a 'datetime'/'date' keeps its
     * localized format and a 'password' keeps its reveal/copy controls — only the
     * label is shortened.
     */
    truncate?: number;
    /**
     * In the expanded row detail panel, long flowing text is truncated to this many
     * characters (default: 200) and an expand/collapse button is shown. Set to
     * `false` to always show the full content without any truncation.
     */
    detailTruncate?: number | false;
    /**
     * Column width strategy. Prefer this over hand-written `w-*` classes in `class`.
     * Every bounded variant truncates overflow with a CSS ellipsis (the full value
     * is shown via an AppTooltip on hover).
     * - 'content' (default): the cell never wraps and shrinks to its content width
     *   (unbounded — a long value can widen the table and push later columns off).
     * - 'grow': the cell absorbs the remaining table width, then truncates.
     * - number: a maximum pixel width — the column stays content-sized but never
     *   exceeds this, truncating longer values. A ceiling, not an exact width, so no
     *   precise value is needed; short values still size to their content.
     * - a '%' string (e.g. '20%'): a fixed share of the table width.
     * - any other CSS length (e.g. '15rem'): an absolute max-width cap, like `number`.
     */
    width?: 'content' | 'grow' | number | string;
    /** Horizontal alignment of the header and cells. Default 'left' (use 'right' for numbers/dates). */
    align?: 'left' | 'center' | 'right';
    /**
     * Unit suffix appended to a non-empty scalar/badge value, separated by a
     * space (e.g. unit '%' renders `92` as `92 %`). Empty values keep the plain
     * placeholder without a unit.
     */
    unit?: string;
    /** Allow the cell to wrap onto multiple lines (overrides the nowrap default). */
    wrap?: boolean;
    /**
     * Responsive auto-hide: only show the column at or above this Tailwind breakpoint
     * (hidden on narrower viewports). Undefined = always visible. The user can still
     * force such a column visible via the column-visibility menu.
     *
     * A hard, author-declared floor that COEXISTS with the automatic width-based
     * auto-hide (`priority`): a column hidden by `showFrom` is not even a fit
     * candidate, so use `showFrom` for coarse semantic priorities and `priority`
     * for the fine-grained squeeze.
     */
    showFrom?: 'sm' | 'md' | 'lg' | 'xl' | '2xl';
    /**
     * Overflow priority for the automatic width-based auto-hide: when the table
     * would be wider than its container, the lowest-`priority` columns are dropped
     * first. Undefined defaults to 0, and ties are broken by declaration order so
     * later-declared columns drop first — meaning earlier (usually more important)
     * columns survive by default with no annotation. Set a higher number to keep a
     * late column longer, or a negative number to drop an early column sooner.
     * Dropped columns stay reachable via the row detail panel and the column menu.
     * `alwaysVisible` columns and columns the user has force-shown are never dropped.
     */
    priority?: number;
    /** Exclude the column from the user-facing visibility menu (always rendered). */
    alwaysVisible?: boolean;
}

export type SortDirection = 'asc' | 'desc';

/** A single selectable value in a faceted filter. */
export interface FacetOption {
    value: string | number;
    label: string;
    /** True when sibling filters leave no matching rows for this option. */
    disabled?: boolean;
}

/**
 * A faceted (multi-select) filter shown above the table. Declared server-side and
 * delivered as a shared page prop; `selected` echoes the values currently in force.
 */
export interface FacetConfig {
    key: string;
    label: string;
    type?: string;
    options?: FacetOption[];
    /** Total number of options before cascading narrows the list. Used to detect active state. */
    optionsTotal: number;
    selected: (string | number)[];
    /** ISO date strings (YYYY-MM-DD) of days that have at least one entry. Only present on date-type filters. */
    availableDates?: string[];
}

export type FieldType =
    | 'text'
    | 'textarea'
    | 'number'
    | 'date'
    | 'datetime'
    | 'select'
    | 'multiselect'
    | 'checkbox'
    | 'richtext'
    | 'htmlOrText'
    | 'repeater'
    | 'range'
    | 'file'
    | 'image'
    | 'icon'
    | 'color'
    | 'password'
    | 'section';

export interface FieldOption {
    value: string | number;
    label: string;
    /** Optional badge-color token (e.g. "emerald") for use in tabs and badges. */
    color?: string | null;
    /** Optional icon name for use in badges. */
    icon?: string | null;
}

export interface FieldConfig {
    name: string;
    label: string;
    type?: FieldType;
    options?: FieldOption[];
    required?: boolean;
    /** When true, the input is rendered read-only (value shown but not editable). */
    disabled?: boolean;
    placeholder?: string;
    /** 1 = half width, 2 = full width (default 1). */
    colSpan?: 1 | 2;
    /** For range fields. */
    min?: number;
    max?: number;
    step?: number;
    /** For file/image fields. */
    accept?: string;
    maxSizeMb?: number;
    /**
     * Vue component rendered as the icon in the section card header.
     * Only meaningful for fields with type === 'section'. Wrap in markRaw() at
     * the call site to prevent Vue from making the component reactive.
     */
    sectionIcon?: object;
    /**
     * For type === 'section': CSS max-height applied to the scrollable body
     * (e.g. '60vh'). When set, overflow-y: auto is also applied.
     */
    maxHeight?: string;
    /**
     * For type === 'section': overrides the global `editable` prop on
     * ResourceDetailPage for this specific section. Set to false for read-only
     * sections like mail history.
     */
    sectionEditable?: boolean;
    /**
     * For select fields: row key whose value is a badge-colour token (e.g.
     * "emerald"). When set and the colour is truthy, the select label is
     * rendered as a coloured badge in view mode instead of plain text.
     */
    colorField?: string;
}

/** A table row is any record that at least carries an id. */
export type ResourceRow = Record<string, unknown> & { id: number };

/**
 * A row expanded into a hierarchy node for tree rendering. Carries the original
 * row fields plus computed metadata: nesting depth, the dotted position label
 * (e.g. "1", "1.2") and its child nodes.
 */
export type TreeNode = ResourceRow & {
    _depth: number;
    _label: string;
    _children: TreeNode[];
};
