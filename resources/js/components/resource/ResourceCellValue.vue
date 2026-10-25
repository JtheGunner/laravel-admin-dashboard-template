<script lang="ts" setup>
import * as LucideIcons from '@lucide/vue';
import { Copy, Eye, EyeOff, Image as ImageIcon } from '@lucide/vue';
import { marked } from 'marked';
import type { FunctionalComponent, Ref } from 'vue';
import { computed, inject, ref, watch } from 'vue';
import IconPreview from '@/components/resource/IconPreview.vue';
import { badgeClasses } from '@/lib/badgeColors';
import {
    display,
    displayWithUnit,
    EMPTY_PLACEHOLDER,
    formatDate,
    formatDateTime,
    htmlHasContent,
    htmlToText,
    isTruncating,
    joinKeys,
    looksLikeHtml,
    subtitleText,
    truncateText,
} from '@/lib/resourceCell';
import { displayUrl, externalHref, isUrl } from '@/lib/utils';
import type { ColumnConfig, ResourceRow } from '@/types/resource';

const props = defineProps<{
    row: ResourceRow;
    col: ColumnConfig;
    /**
     * Detail context (row detail panel / card body): untyped values are rendered
     * richer than in the compact table — arrays as a bullet list and HTML strings
     * as rendered HTML, instead of joined/escaped text.
     */
    detail?: boolean;
}>();

// Emitted when the per-link copy button is pressed (detail context); the parent
// writes the value to the clipboard and highlights the passed link element.
const emit = defineEmits<{ copy: [text: string, el?: HTMLElement | null] }>();

// The raw cell value, narrowed for the detail-only array/HTML branches.
const value = computed<unknown>(() => props.row[props.col.key]);

// When ResourceIndex redistributes column widths (spacer removed), the td is the
// clipping boundary and CSS truncation handles display — skip the JS character cap.
const columnHasOverrides = inject<Ref<boolean>>(
    'columnHasOverrides',
    ref(false),
);

// Applies the column's `truncate` character cap to an already-formatted display
// string, leaving the cell's structure intact — the badge ring, icon glyph, link,
// date format, etc. all survive and only the text is shortened. Without `truncate`
// the string is returned unchanged. Skipped when columns are redistributed so the
// wider td can reveal more text via CSS truncation.
function cap(text: string): string {
    if (columnHasOverrides.value) {
        return text;
    }

    return props.col.truncate ? truncateText(text, props.col.truncate) : text;
}
const asArray = computed<unknown[]>(() =>
    Array.isArray(value.value) ? value.value : [],
);

// The rendered link, so the copy button can request it be highlighted.
const linkRef = ref<HTMLElement | null>(null);

function onCopyLink(): void {
    emit('copy', String(props.row[props.col.key]), linkRef.value);
}

// Subtitle for the image-text branch: rendered inline (logo left, text+subtitle
// stacked right). ResourceCell skips its own subtitle for this type to avoid duplication.
const subtitle = computed<string>(() => subtitleText(props.row, props.col));
const bounded = computed<boolean>(() => isTruncating(props.col));

// 'password' columns start masked; the toggle flips this per cell. Reka/Vue
// recycles cell components across rows, so reset to masked whenever the value
// changes to avoid leaking a revealed password into the next record.
const revealed = ref(false);
watch(
    () => props.row[props.col.key],
    () => {
        revealed.value = false;
    },
);

// Detail context: copy the revealed password value to the clipboard.
function onCopyPassword(): void {
    emit('copy', String(props.row[props.col.key]));
}

// 'markdown' columns: render the raw Markdown string to HTML for the detail panel,
// or strip it to plain text for the compact table preview.
const markdownHtml = computed<string>(() => {
    if (props.col.type !== 'markdown') {
        return '';
    }

    const raw = props.row[props.col.key];

    if (typeof raw !== 'string' || !raw.trim()) {
        return '';
    }

    return marked(raw) as string;
});

// Tracks whether the thumbnail failed to load, so image columns can fall back to
// the placeholder icon just as they do when no image URL is present at all.
const imageFailed = ref(false);

// The image source may change when the underlying row is reused for another
// record (Vue recycles cell components); reset the error flag so the new image
// gets a fresh chance to load.
watch(
    () => [props.row[props.col.key], props.row[props.col.imageKey ?? '']],
    () => {
        imageFailed.value = false;
    },
);

// Placeholder icon shown when an image is missing or fails to load. Resolves the
// column's `fallbackIcon` (a kebab-case Lucide name) to its component, falling
// back to the neutral image icon.
const fallbackIcon = computed<FunctionalComponent>(() => {
    const name = props.col.fallbackIcon;

    if (!name) {
        return ImageIcon;
    }

    const pascal = name.replace(/(^|-)([a-z])/g, (_, __, c: string) =>
        c.toUpperCase(),
    );

    return (
        ((LucideIcons as Record<string, unknown>)[
            pascal
        ] as FunctionalComponent) ?? ImageIcon
    );
});

// Detail-panel expand/collapse for long flowing text.
// Resets to collapsed whenever the cell value changes (row swapped out or edited).
const detailExpanded = ref(false);
watch(
    () => props.row[props.col.key],
    () => {
        detailExpanded.value = false;
    },
);

// Effective character limit for the detail panel: 200 by default, a custom number
// when the column declares `detailTruncate`, or false to always show everything.
const detailLimit = computed<number | false>(() => {
    const cfg = props.col.detailTruncate;

    return cfg === false ? false : typeof cfg === 'number' ? cfg : 200;
});

// Returns true when the given plain text exceeds the detail limit and truncation
// should be applied (only in detail context).
function exceedsDetailLimit(text: string): boolean {
    const limit = detailLimit.value;

    return props.detail === true && limit !== false && text.length > limit;
}

// Returns the text truncated to the detail limit with an ellipsis appended.
function truncateForDetail(text: string): string {
    const limit = detailLimit.value;

    return limit !== false && text.length > limit
        ? `${text.slice(0, limit)}…`
        : text;
}
</script>

<template>
    <span
        v-if="typeof row[col.key] === 'boolean'"
        :class="
            row[col.key]
                ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400'
                : 'bg-muted text-muted-foreground'
        "
        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
    >
        {{ row[col.key] ? 'Yes' : 'No' }}
    </span>
    <span v-else-if="col.keys">{{ joinKeys(row, col.keys) }}</span>
    <!-- Password: masked behind a reveal toggle; empty values show the placeholder. -->
    <span v-else-if="col.type === 'password' && !row[col.key]">{{
        EMPTY_PLACEHOLDER
    }}</span>
    <button
        v-else-if="col.type === 'password' && !revealed"
        class="inline-flex items-center gap-1.5 rounded text-sm text-muted-foreground transition-colors hover:text-foreground"
        type="button"
        @click.stop="revealed = true"
        @dblclick.stop
    >
        <Eye class="size-3.5 shrink-0" />
        Show password
    </button>
    <span
        v-else-if="col.type === 'password'"
        class="inline-flex items-center gap-1.5"
    >
        <span class="break-all">{{ cap(display(row[col.key])) }}</span>
        <button
            aria-label="Hide password"
            class="inline-flex size-6 shrink-0 items-center justify-center rounded text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
            type="button"
            @click.stop="revealed = false"
            @dblclick.stop
        >
            <EyeOff class="size-3.5" />
        </button>
        <!-- Detail context: an explicit copy affordance for the revealed value. -->
        <button
            v-if="detail"
            aria-label="Copy password"
            class="inline-flex size-6 shrink-0 items-center justify-center rounded text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
            type="button"
            @click.stop="onCopyPassword"
            @dblclick.stop
        >
            <Copy class="size-3.5" />
        </button>
    </span>
    <IconPreview
        v-else-if="col.type === 'icon' && row[col.key]"
        :label="cap(display(row[col.key]))"
        :value="row[col.key] as string"
    />
    <span v-else-if="col.type === 'icon'">{{ EMPTY_PLACEHOLDER }}</span>
    <span
        v-else-if="col.type === 'badge' && row[col.key]"
        :class="
            badgeClasses(col.colorKey ? (row[col.colorKey] as string) : null)
        "
        class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset"
    >
        <IconPreview
            v-if="col.iconKey && row[col.iconKey]"
            :value="row[col.iconKey] as string"
        />
        {{ cap(displayWithUnit(row[col.key], col.unit)) }}
    </span>
    <span v-else-if="col.type === 'badge'">{{ EMPTY_PLACEHOLDER }}</span>
    <div v-else-if="col.type === 'image-text'" class="flex items-center gap-2">
        <img
            v-if="col.imageKey && row[col.imageKey] && !imageFailed"
            :alt="col.label"
            :src="encodeURI(row[col.imageKey] as string)"
            class="size-7 shrink-0 self-center rounded bg-muted object-contain p-0.5"
            @error="imageFailed = true"
        />
        <span
            v-else
            class="flex size-7 shrink-0 items-center justify-center self-center rounded bg-muted text-muted-foreground"
        >
            <component :is="fallbackIcon" class="size-4" />
        </span>
        <span
            v-if="subtitle"
            :class="bounded ? 'min-w-0' : ''"
            class="flex flex-col leading-tight"
        >
            <span :class="bounded ? 'truncate' : ''">{{
                cap(display(row[col.key]))
            }}</span>
            <span
                :class="bounded ? 'truncate' : ''"
                class="pt-0.5 text-xs font-normal text-muted-foreground"
                >{{ cap(subtitle) }}</span
            >
        </span>
        <span v-else>{{ cap(display(row[col.key])) }}</span>
    </div>
    <img
        v-else-if="col.type === 'image' && row[col.key] && !imageFailed"
        :alt="col.label"
        :src="encodeURI(row[col.key] as string)"
        class="size-7 rounded bg-muted object-contain p-0.5"
        @error="imageFailed = true"
    />
    <span
        v-else-if="col.type === 'image'"
        class="flex size-7 items-center justify-center rounded bg-muted text-muted-foreground"
    >
        <component :is="fallbackIcon" class="size-4" />
    </span>
    <!-- Detail context: rendered HTML block, with expand/collapse for long content. -->
    <template
        v-else-if="
            col.type === 'html' && detail && htmlHasContent(row[col.key])
        "
    >
        <span
            v-if="
                exceedsDetailLimit(htmlToText(row[col.key])) && !detailExpanded
            "
            class="text-sm"
        >
            {{ truncateForDetail(htmlToText(row[col.key])) }}
            <button
                class="ml-1 cursor-pointer text-xs text-muted-foreground transition-colors hover:text-foreground"
                type="button"
                @click.stop="detailExpanded = true"
            >
                Show more
            </button>
        </span>
        <div v-else>
            <div
                class="max-w-prose text-sm [&_*]:my-0 [&_p]:leading-snug"
                v-html="(row[col.key] as string) ?? ''"
            />
            <button
                v-if="exceedsDetailLimit(htmlToText(row[col.key]))"
                class="mt-1 cursor-pointer text-xs text-muted-foreground transition-colors hover:text-foreground"
                type="button"
                @click.stop="detailExpanded = false"
            >
                Show less
            </button>
        </div>
    </template>
    <!-- Compact table: single-line plain-text preview; the cell wrapper truncates
         it to the column so rich text never overflows into other columns. -->
    <span v-else-if="col.type === 'html' && htmlHasContent(row[col.key])">{{
        cap(htmlToText(row[col.key]))
    }}</span>
    <span v-else-if="col.type === 'html'">{{ EMPTY_PLACEHOLDER }}</span>
    <!-- Detail context: rendered Markdown block, with expand/collapse for long content. -->
    <template v-else-if="col.type === 'markdown' && detail && markdownHtml">
        <span
            v-if="
                exceedsDetailLimit(htmlToText(markdownHtml)) && !detailExpanded
            "
            class="text-sm"
        >
            {{ truncateForDetail(htmlToText(markdownHtml)) }}
            <button
                class="ml-1 cursor-pointer text-xs text-muted-foreground transition-colors hover:text-foreground"
                type="button"
                @click.stop="detailExpanded = true"
            >
                Show more
            </button>
        </span>
        <div v-else>
            <div
                class="text-sm [&_blockquote]:border-l-2 [&_blockquote]:border-border [&_blockquote]:pl-3 [&_blockquote]:text-muted-foreground [&_h1]:mt-4 [&_h1]:mb-2 [&_h1]:text-xl [&_h1]:font-bold [&_h2]:mt-4 [&_h2]:mb-2 [&_h2]:text-base [&_h2]:font-semibold [&_h3]:mt-3 [&_h3]:mb-1 [&_h3]:text-sm [&_h3]:font-semibold [&_li]:my-0.5 [&_ol]:my-2 [&_ol]:list-decimal [&_ol]:pl-5 [&_p]:my-2 [&_strong]:font-semibold [&_ul]:my-2 [&_ul]:list-disc [&_ul]:pl-5"
                v-html="markdownHtml"
            />
            <button
                v-if="exceedsDetailLimit(htmlToText(markdownHtml))"
                class="mt-1 cursor-pointer text-xs text-muted-foreground transition-colors hover:text-foreground"
                type="button"
                @click.stop="detailExpanded = false"
            >
                Show less
            </button>
        </div>
    </template>
    <!-- Compact table: plain-text preview; the cell wrapper truncates to the column width. -->
    <span v-else-if="col.type === 'markdown' && markdownHtml">{{
        cap(htmlToText(markdownHtml))
    }}</span>
    <span v-else-if="col.type === 'markdown'">{{ EMPTY_PLACEHOLDER }}</span>
    <span v-else-if="col.type === 'datetime'" class="text-muted-foreground">{{
        cap(formatDateTime(row[col.key], col.dateFormat))
    }}</span>
    <span v-else-if="col.type === 'date'" class="text-muted-foreground">{{
        cap(formatDate(row[col.key], col.dateFormat))
    }}</span>
    <!-- Detail context: an array value becomes a bullet list. -->
    <ul
        v-else-if="detail && asArray.length > 0"
        class="list-disc space-y-0.5 pl-4 text-sm"
    >
        <li v-for="(item, index) in asArray" :key="index">
            {{ display(item) }}
        </li>
    </ul>
    <span v-else-if="detail && Array.isArray(value)">{{
        EMPTY_PLACEHOLDER
    }}</span>
    <!-- Detail context: an untyped HTML string rendered as HTML, with expand/collapse. -->
    <template
        v-else-if="detail && looksLikeHtml(value) && htmlHasContent(value)"
    >
        <span
            v-if="exceedsDetailLimit(htmlToText(value)) && !detailExpanded"
            class="text-sm"
        >
            {{ truncateForDetail(htmlToText(value)) }}
            <button
                class="ml-1 cursor-pointer text-xs text-muted-foreground transition-colors hover:text-foreground"
                type="button"
                @click.stop="detailExpanded = true"
            >
                Show more
            </button>
        </span>
        <div v-else>
            <div
                class="max-w-prose text-sm [&_*]:my-0 [&_p]:leading-snug"
                v-html="value as string"
            />
            <button
                v-if="exceedsDetailLimit(htmlToText(value))"
                class="mt-1 cursor-pointer text-xs text-muted-foreground transition-colors hover:text-foreground"
                type="button"
                @click.stop="detailExpanded = false"
            >
                Show less
            </button>
        </div>
    </template>
    <span
        v-else-if="!col.noLink && isUrl(row[col.key])"
        class="inline-flex items-center gap-1.5"
    >
        <a
            ref="linkRef"
            :class="detail ? 'break-all' : 'min-w-0 truncate'"
            :href="externalHref(row[col.key] as string)"
            class="text-primary underline underline-offset-2 hover:text-primary/80"
            rel="noopener noreferrer"
            target="_blank"
            @click.stop
            >{{ cap(displayUrl(display(row[col.key]))) }}</a
        >
        <!-- Detail context: an explicit copy affordance next to the link. -->
        <button
            v-if="detail"
            aria-label="Copy link"
            class="inline-flex size-6 shrink-0 items-center justify-center rounded text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
            type="button"
            @click.stop="onCopyLink"
            @dblclick.stop
        >
            <Copy class="size-3.5" />
        </button>
    </span>
    <!-- Catch-all: plain text. In detail context apply detailTruncate instead of
         the compact-table cap(), so long values get the expand/collapse toggle. -->
    <template v-else-if="detail">
        <span
            v-if="
                exceedsDetailLimit(displayWithUnit(row[col.key], col.unit)) &&
                !detailExpanded
            "
        >
            {{ truncateForDetail(displayWithUnit(row[col.key], col.unit)) }}
            <button
                class="ml-1 cursor-pointer text-xs text-muted-foreground transition-colors hover:text-foreground"
                type="button"
                @click.stop="detailExpanded = true"
            >
                Show more
            </button>
        </span>
        <span v-else>
            {{ displayWithUnit(row[col.key], col.unit) }}
            <button
                v-if="
                    exceedsDetailLimit(displayWithUnit(row[col.key], col.unit))
                "
                class="ml-1 cursor-pointer text-xs text-muted-foreground transition-colors hover:text-foreground"
                type="button"
                @click.stop="detailExpanded = false"
            >
                Show less
            </button>
        </span>
    </template>
    <span v-else>{{ cap(displayWithUnit(row[col.key], col.unit)) }}</span>
</template>
