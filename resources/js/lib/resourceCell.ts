import type { ColumnConfig, FieldConfig, ResourceRow } from '@/types/resource';

/** Placeholder shown in table cells whenever a value is empty. */
export const EMPTY_PLACEHOLDER = '-';

/**
 * Whether a column absorbs the table's remaining width and truncates its content
 * to a single line. True for explicit `width: 'grow'` and for rich-text (`html`)
 * columns without an explicit width — so their content is clipped to the column
 * instead of overflowing into neighbouring columns.
 */
export function isGrowColumn(col: ColumnConfig): boolean {
    if (col.width === 'grow') {
        return true;
    }

    // Untyped-width html columns fill the remaining width and clip — unless a
    // character limit (`truncate`) is set, in which case the value is already
    // bounded to a short plain-text preview and the column can size to it.
    return (
        col.type === 'html' &&
        !col.truncate &&
        (col.width === undefined || col.width === 'content')
    );
}

/**
 * A percentage width (e.g. '20%') — a proportion of the table, not an absolute
 * cap. Sized like `grow` (the `max-w-0` trick) but to a fixed share.
 */
export function isPercentWidth(col: ColumnConfig): boolean {
    return typeof col.width === 'string' && col.width.trim().endsWith('%');
}

/**
 * An absolute max-width cap: a px number or a literal absolute CSS length
 * (rem/em/ch/vw…). The column stays content-sized but never exceeds this, clipping
 * longer values — so an exact width is unnecessary, the value is just a ceiling.
 * Percentages are excluded (they resolve against the auto-sized cell, so an inner
 * max-width can't bound them — they take the proportional `max-w-0` path instead).
 */
export function isCapWidth(col: ColumnConfig): boolean {
    const width = col.width;

    if (typeof width === 'number') {
        return true;
    }

    return (
        typeof width === 'string' &&
        width !== 'content' &&
        width !== 'grow' &&
        !width.trim().endsWith('%')
    );
}

/**
 * True when a column has a bounded width and its content should be clipped with a
 * CSS ellipsis (grow, a percentage share, or an absolute cap — but not when
 * wrapping). A `content`/undefined-width column is unbounded: it sizes to its
 * content (including the subtitle line) and must not truncate.
 */
export function isTruncating(col: ColumnConfig): boolean {
    if (col.wrap) {
        return false;
    }

    return isGrowColumn(col) || isPercentWidth(col) || isCapWidth(col);
}

/** Strip HTML to a single line of plain text (tags removed, whitespace collapsed). */
export function htmlToText(value: unknown): string {
    if (value === null || value === undefined || value === '') {
        return '';
    }

    const el = document.createElement('div');
    el.innerHTML = String(value);

    return (el.textContent ?? '').replace(/\s+/g, ' ').trim();
}

/** Human-readable rendering of a scalar cell value (booleans → Yes/No, empty → placeholder). */
export function display(value: unknown): string {
    if (value === true) {
        return 'Yes';
    }

    if (value === false) {
        return 'No';
    }

    if (value === null || value === undefined || value === '') {
        return EMPTY_PLACEHOLDER;
    }

    return String(value);
}

/**
 * Human-readable scalar rendering with an optional unit suffix appended when a
 * value is present (e.g. `92` + `%` → `92 %`). Empty values render the plain
 * placeholder without a unit.
 */
export function displayWithUnit(value: unknown, unit?: string): string {
    const text = display(value);

    if (!unit || text === EMPTY_PLACEHOLDER) {
        return text;
    }

    return `${text} ${unit}`;
}

/** Plain-text value of a cell suitable for copying to the clipboard ('' when empty). */
export function copyableText(row: ResourceRow, col: ColumnConfig): string {
    if (col.keys) {
        const joined = joinKeys(row, col.keys);

        return joined === EMPTY_PLACEHOLDER ? '' : joined;
    }

    const value = row[col.key];

    if (value === null || value === undefined || value === '') {
        return '';
    }

    if (Array.isArray(value)) {
        return value.map((item) => String(item)).join('\n');
    }

    if (col.type === 'html' || looksLikeHtml(value)) {
        return htmlToText(value);
    }

    if (typeof value === 'boolean') {
        return value ? 'Yes' : 'No';
    }

    return String(value);
}

/** Whether a cell holds copyable content — false for empty fields shown as the placeholder. */
export function hasCopyableContent(
    row: ResourceRow,
    col: ColumnConfig,
): boolean {
    return copyableText(row, col).trim() !== '';
}

/**
 * Plain-text value of a detail-page field suitable for copying to the clipboard.
 * Returns '' when empty, not applicable (section), or self-managed (password).
 */
export function fieldCopyableText(
    row: ResourceRow,
    field: FieldConfig,
): string {
    // Password fields manage their own copy button; sections are not values.
    if (field.type === 'password' || field.type === 'section') {
        return '';
    }

    const value = row[field.name];

    if (field.type === 'checkbox') {
        return value ? 'Yes' : 'No';
    }

    if (value === null || value === undefined || value === '') {
        return '';
    }

    if (field.type === 'richtext' || field.type === 'htmlOrText') {
        return typeof value === 'string' ? htmlToText(value) : '';
    }

    if (field.type === 'repeater') {
        const items: unknown[] = Array.isArray(value)
            ? value
            : (() => {
                  try {
                      const parsed: unknown = JSON.parse(String(value));

                      return Array.isArray(parsed) ? parsed : [];
                  } catch {
                      return typeof value === 'string' && value.trim()
                          ? [value]
                          : [];
                  }
              })();

        return items.map(String).filter(Boolean).join('\n');
    }

    if (field.type === 'multiselect') {
        const ids: unknown[] = Array.isArray(value) ? value : [];

        return ids
            .map((id) => {
                const match = field.options?.find(
                    (o) => String(o.value) === String(id),
                );

                return match?.label ?? String(id);
            })
            .join(', ');
    }

    if (field.type === 'select') {
        if (value === 0 || value === '0') {
            return '';
        }

        const match = field.options?.find(
            (o) => String(o.value) === String(value),
        );

        return match?.label ?? String(value);
    }

    return String(value);
}

/** Whether a detail field holds copyable content (false for empty, section, and password). */
export function fieldHasCopyableContent(
    row: ResourceRow,
    field: FieldConfig,
): boolean {
    return fieldCopyableText(row, field).trim() !== '';
}

/**
 * Resolve a column's subtitle — the muted second line shown beneath the primary
 * cell value. A dynamic `subtitleKey` (another row column) takes precedence when it
 * holds a non-empty value; otherwise the static `subtitle` free text is used. Returns
 * '' when neither yields content, so callers can skip rendering an empty line.
 */
export function subtitleText(row: ResourceRow, col: ColumnConfig): string {
    if (col.subtitleKey) {
        const value = row[col.subtitleKey];

        if (
            value !== null &&
            value !== undefined &&
            String(value).trim() !== ''
        ) {
            return String(value);
        }
    }

    return col.subtitle ?? '';
}

/** Join several row values into one cell (empty values skipped, e.g. PLZ + Ort). */
export function joinKeys(row: ResourceRow, keys: string[]): string {
    const parts = keys
        .map((key) => row[key])
        .filter(
            (value) =>
                value !== null &&
                value !== undefined &&
                String(value).trim() !== '',
        )
        .map((value) => String(value));

    return parts.length > 0 ? parts.join(' ') : EMPTY_PLACEHOLDER;
}

/**
 * Heuristic: does the string look like HTML markup (a real `<tag>` with a closing
 * bracket)? Used to auto-render untyped detail fields that hold rich text. Kept
 * strict — a stray `<` or `a < b` does not match, only `<p>…`, `</li>`, `<br/>`.
 */
export function looksLikeHtml(value: unknown): boolean {
    return typeof value === 'string' && /<\/?[a-z][^>]*>/i.test(value);
}

/** True when a rich-text/HTML value has any visible text content. */
export function htmlHasContent(value: unknown): boolean {
    if (value === null || value === undefined || value === '') {
        return false;
    }

    const el = document.createElement('div');
    el.innerHTML = String(value);

    return (el.textContent ?? '').trim() !== '';
}

/** Reduce rich text/HTML to plain text and truncate to `max` characters (with an ellipsis). */
export function truncateText(value: unknown, max: number): string {
    if (value === null || value === undefined || value === '') {
        return EMPTY_PLACEHOLDER;
    }

    const el = document.createElement('div');
    el.innerHTML = String(value);
    const text = (el.textContent ?? '').replace(/\s+/g, ' ').trim();

    if (text === '') {
        return EMPTY_PLACEHOLDER;
    }

    return text.length > max ? `${text.slice(0, max)}…` : text;
}

/** Default fixed width of a tree column without an explicit `width` (former `w-32`). */
const TREE_DEFAULT_COLUMN_WIDTH = '8rem';

/**
 * Fixed inline width for a column in the tree layout, resolved from `col.width`:
 * - number            → that many pixels
 * - '%' / CSS length  → used verbatim
 * - 'content'/undef   → the default width ({@link TREE_DEFAULT_COLUMN_WIDTH})
 * `grow` columns flex instead (no fixed width — see {@link treeColumnStyle}).
 */
function treeColumnFixedWidth(col: ColumnConfig): string {
    if (typeof col.width === 'number') {
        return `${col.width}px`;
    }

    if (typeof col.width === 'string' && col.width !== 'content') {
        return col.width;
    }

    return TREE_DEFAULT_COLUMN_WIDTH;
}

/**
 * Flex sizing class for a tree column. The tree is a fixed-column flex layout —
 * each row is an independent flex row (unlike the flat table's shared auto-layout
 * `<table>`), so columns only align vertically when every row gives a column the
 * same width. A `grow` column absorbs the remaining row width; every other column
 * holds the fixed width from {@link treeColumnStyle}. `min-w-0` lets the paired
 * inner `.truncate` clip overflow instead of forcing the row wider.
 */
export function treeColumnClass(col: ColumnConfig): string {
    return col.width === 'grow' ? 'min-w-0 flex-1' : 'min-w-0 shrink-0';
}

/**
 * Fixed inline width (`width` + `maxWidth`) for a tree column. `grow` columns flex
 * and get no fixed width. Both properties are set so a `box-border` cell keeps its
 * column width even with the first column's depth padding, keeping the columns
 * vertically aligned across rows.
 */
export function treeColumnStyle(col: ColumnConfig): Record<string, string> {
    if (col.width === 'grow') {
        return {};
    }

    const width = treeColumnFixedWidth(col);

    return { width, maxWidth: width };
}

/** Default pattern for `date` columns: e.g. `2 Aug 2026`. */
export const DEFAULT_DATE_FORMAT = 'j M Y';

/**
 * Default pattern for every date with a time: e.g. `2 Aug 2026, 14:30`. The
 * backend counterpart is `App\Support\DateTimeFormat::DISPLAY`.
 */
export const DEFAULT_DATETIME_FORMAT = 'j M Y, H:i';

let displayTimeZone: string | undefined;

/**
 * Sets the timezone every date is rendered in (`APP_DISPLAY_TIMEZONE`, shared by the
 * server as the `displayTimezone` Inertia prop). Without it the browser's timezone is
 * used. The server stores instants in UTC and formats its own labels in this same
 * timezone, so both sides always show the same time.
 */
export function setDisplayTimeZone(timeZone: string | undefined): void {
    displayTimeZone = timeZone;
}

interface DateParts {
    year: number;
    month: number;
    day: number;
    hour: number;
    minute: number;
    second: number;
}

const formatterCache = new Map<string, Intl.DateTimeFormat>();

/** The wall-clock parts of an instant in the display timezone (browser's if unset). */
function partsFromDate(date: Date): DateParts {
    if (!displayTimeZone) {
        return {
            year: date.getFullYear(),
            month: date.getMonth() + 1,
            day: date.getDate(),
            hour: date.getHours(),
            minute: date.getMinutes(),
            second: date.getSeconds(),
        };
    }

    let formatter = formatterCache.get(displayTimeZone);

    if (!formatter) {
        formatter = new Intl.DateTimeFormat('en-GB', {
            timeZone: displayTimeZone,
            year: 'numeric',
            month: 'numeric',
            day: 'numeric',
            hour: 'numeric',
            minute: 'numeric',
            second: 'numeric',
            hourCycle: 'h23',
        });
        formatterCache.set(displayTimeZone, formatter);
    }

    const parts = formatter.formatToParts(date);
    const read = (type: Intl.DateTimeFormatPartTypes): number =>
        Number(parts.find((part) => part.type === type)?.value);

    return {
        year: read('year'),
        month: read('month'),
        day: read('day'),
        hour: read('hour'),
        minute: read('minute'),
        second: read('second'),
    };
}

const WALL_CLOCK_VALUE =
    /^(\d{4})-(\d{2})-(\d{2})(?:[T ](\d{2}):(\d{2})(?::(\d{2}))?)?$/;

/**
 * Values without an offset (`2026-08-21`, `2026-08-21T14:00`) already are wall-clock
 * time in the display timezone — they are shown as they are, never shifted.
 */
function partsFromWallClock(value: string): DateParts | null {
    const match = WALL_CLOCK_VALUE.exec(value);

    if (!match) {
        return null;
    }

    return {
        year: Number(match[1]),
        month: Number(match[2]),
        day: Number(match[3]),
        hour: Number(match[4] ?? 0),
        minute: Number(match[5] ?? 0),
        second: Number(match[6] ?? 0),
    };
}

const MONTH_ABBREVIATIONS: readonly string[] = [
    'Jan',
    'Feb',
    'Mar',
    'Apr',
    'May',
    'Jun',
    'Jul',
    'Aug',
    'Sep',
    'Oct',
    'Nov',
    'Dec',
];

function formatParts(parts: DateParts, pattern: string): string {
    const pad = (value: number): string => String(value).padStart(2, '0');

    const tokens: Record<string, () => string> = {
        d: () => pad(parts.day),
        j: () => String(parts.day),
        m: () => pad(parts.month),
        n: () => String(parts.month),
        M: () => MONTH_ABBREVIATIONS[parts.month - 1] ?? '',
        Y: () => String(parts.year),
        y: () => pad(parts.year % 100),
        H: () => pad(parts.hour),
        G: () => String(parts.hour),
        i: () => pad(parts.minute),
        s: () => pad(parts.second),
    };

    let out = '';

    for (let index = 0; index < pattern.length; index++) {
        const char = pattern[index];

        if (char === '\\' && index + 1 < pattern.length) {
            out += pattern[++index];
            continue;
        }

        out += tokens[char]?.() ?? char;
    }

    return out;
}

/**
 * Render a `Date` using PHP `date()`-style tokens — the same notation used on the
 * backend, so a column's `dateFormat` reads the same in the Vue config as in a
 * Laravel accessor. The instant is shown in the display timezone. Supported tokens:
 *
 * - `d` day of month, 2 digits (01–31)      `j` day, no leading zero (1–31)
 * - `m` month, 2 digits (01–12)             `n` month, no leading zero (1–12)
 * - `M` month, short English name (Jan–Dec)
 * - `Y` year, 4 digits                      `y` year, 2 digits
 * - `H` hour 24h, 2 digits (00–23)          `G` hour 24h, no leading zero (0–23)
 * - `i` minutes, 2 digits (00–59)           `s` seconds, 2 digits (00–59)
 *
 * Any other character is emitted verbatim (so `.`, `:`, spaces act as separators).
 * A backslash escapes the next character, letting a literal token letter through.
 */
export function formatWithPattern(date: Date, pattern: string): string {
    return formatParts(partsFromDate(date), pattern);
}

/** The current time as the value of an `<input type="datetime-local">`. */
export function nowForDatetimeInput(): string {
    return formatWithPattern(new Date(), 'Y-m-d\\TH:i');
}

function parseAndFormat(value: unknown, pattern: string): string {
    if (value === null || value === undefined || value === '') {
        return EMPTY_PLACEHOLDER;
    }

    const wallClock =
        typeof value === 'string' ? partsFromWallClock(value) : null;

    if (wallClock) {
        return formatParts(wallClock, pattern);
    }

    const date = new Date(String(value));

    return Number.isNaN(date.getTime())
        ? String(value)
        : formatWithPattern(date, pattern);
}

/**
 * Date/time rendering, defaulting to `DEFAULT_DATETIME_FORMAT`; a column can pass a
 * custom PHP-style pattern (see {@link formatWithPattern}). Falls back to the raw
 * value when unparseable.
 */
export function formatDateTime(
    value: unknown,
    pattern: string = DEFAULT_DATETIME_FORMAT,
): string {
    return parseAndFormat(value, pattern);
}

/**
 * Date-only rendering, defaulting to `DEFAULT_DATE_FORMAT`; a column can pass a
 * custom PHP-style pattern (see {@link formatWithPattern}). Falls back to the raw
 * value when unparseable.
 */
export function formatDate(
    value: unknown,
    pattern: string = DEFAULT_DATE_FORMAT,
): string {
    return parseAndFormat(value, pattern);
}
