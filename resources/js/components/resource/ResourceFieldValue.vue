<script lang="ts" setup>
import { Copy, Eye, EyeOff, Image as ImageIcon } from '@lucide/vue';
import { marked } from 'marked';
import { computed, ref, watch } from 'vue';
import IconPreview from '@/components/resource/IconPreview.vue';
import { useCopy } from '@/composables/useCopy';
import { badgeClasses } from '@/lib/badgeColors';
import {
    display,
    EMPTY_PLACEHOLDER,
    formatDate,
    formatDateTime,
    htmlHasContent,
    looksLikeHtml,
} from '@/lib/resourceCell';
import { displayUrl, externalHref, isUrl } from '@/lib/utils';
import type { FieldConfig, FieldOption, ResourceRow } from '@/types/resource';

const props = defineProps<{
    field: FieldConfig;
    row: ResourceRow;
}>();

const value = computed<unknown>(() => props.row[props.field.name]);

// password: masked behind a reveal toggle; reset when the value changes.
const revealed = ref(false);
watch(value, () => {
    revealed.value = false;
});

const { copyText } = useCopy();

// select: resolve the stored value to its display label via field.options.
const selectLabel = computed<string>(() => {
    if (props.field.type !== 'select' || !props.field.options) {
        return EMPTY_PLACEHOLDER;
    }

    const v = value.value;

    if (v === null || v === undefined || v === '' || v === 0 || v === '0') {
        return EMPTY_PLACEHOLDER;
    }

    const match = props.field.options.find(
        (o: FieldOption) => String(o.value) === String(v),
    );

    return match?.label ?? String(v);
});

// multiselect: resolve each stored value to its label.
const multiselectLabels = computed<string[]>(() => {
    if (props.field.type !== 'multiselect' || !props.field.options) {
        return [];
    }

    const v = value.value;
    const ids: unknown[] = Array.isArray(v) ? v : [];

    return ids.map((id) => {
        const match = props.field.options?.find(
            (o: FieldOption) => String(o.value) === String(id),
        );

        return match?.label ?? String(id);
    });
});

// richtext / htmlOrText: rendered HTML content.
const htmlContent = computed<string>(() => {
    const t = props.field.type;

    if (t !== 'richtext' && t !== 'htmlOrText') {
        return '';
    }

    const v = value.value;

    return typeof v === 'string' && htmlHasContent(v) ? v : '';
});

// Textarea fields may contain Markdown.
const markdownHtml = computed<string>(() => {
    const v = value.value;

    if (typeof v !== 'string' || !v.trim()) {
        return '';
    }

    // Only auto-render Markdown for textarea fields whose value looks like Markdown
    // (starts with a heading or bullet) but NOT like HTML (already handled above).
    if (
        props.field.type === 'textarea' &&
        !looksLikeHtml(v) &&
        /^#{1,6} /.test(v)
    ) {
        return marked(v) as string;
    }

    return '';
});

// image: track load failure so we can fall back to the placeholder.
const imageFailed = ref(false);

watch(
    () => value.value,
    () => {
        imageFailed.value = false;
    },
);

// colorField: badge classes for select fields that carry a badge-colour token.
const selectBadgeClasses = computed<string | null>(() => {
    if (props.field.type !== 'select' || !props.field.colorField) {
        return null;
    }

    const color = props.row[props.field.colorField];

    if (!color || selectLabel.value === EMPTY_PLACEHOLDER) {
        return null;
    }

    return badgeClasses(typeof color === 'string' ? color : null);
});

// repeater: the field stores a JSON array (or already an array) of strings.
const repeaterItems = computed<string[]>(() => {
    const v = value.value;

    if (Array.isArray(v)) {
        return (v as unknown[]).map(String).filter(Boolean);
    }

    if (typeof v === 'string') {
        try {
            const parsed: unknown = JSON.parse(v);

            if (Array.isArray(parsed)) {
                return (parsed as unknown[]).map(String).filter(Boolean);
            }
        } catch {
            // not JSON — render as a single item
            return v.trim() ? [v] : [];
        }
    }

    return [];
});
</script>

<template>
    <!-- section fields are rendered as section headers by the parent, not here -->
    <template v-if="field.type === 'section'" />

    <!-- password: masked behind a reveal toggle with a copy button when revealed -->
    <span
        v-else-if="field.type === 'password' && !value"
        class="text-muted-foreground"
        >{{ EMPTY_PLACEHOLDER }}</span
    >
    <button
        v-else-if="field.type === 'password' && !revealed"
        class="inline-flex items-center gap-1.5 rounded text-sm text-muted-foreground transition-colors hover:text-foreground"
        type="button"
        @click.stop="revealed = true"
    >
        <Eye class="size-3.5 shrink-0" />
        Show password
    </button>
    <span
        v-else-if="field.type === 'password'"
        class="inline-flex items-center gap-1.5"
    >
        <span class="break-all">{{ display(value) }}</span>
        <button
            aria-label="Hide password"
            class="inline-flex size-6 shrink-0 items-center justify-center rounded text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
            type="button"
            @click.stop="revealed = false"
        >
            <EyeOff class="size-3.5" />
        </button>
        <button
            aria-label="Copy password"
            class="inline-flex size-6 shrink-0 items-center justify-center rounded text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
            type="button"
            @click.stop="copyText(String(value))"
        >
            <Copy class="size-3.5" />
        </button>
    </span>

    <!-- checkbox: Yes / No badge -->
    <span
        v-else-if="field.type === 'checkbox'"
        :class="
            value
                ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400'
                : 'bg-muted text-muted-foreground'
        "
        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
    >
        {{ value ? 'Yes' : 'No' }}
    </span>

    <!-- select: display as coloured badge when colorField is set, else plain text -->
    <span
        v-else-if="field.type === 'select' && selectBadgeClasses"
        :class="selectBadgeClasses"
        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset"
        >{{ selectLabel }}</span
    >
    <span v-else-if="field.type === 'select'">{{ selectLabel }}</span>

    <!-- multiselect: comma-separated labels or bullet list -->
    <ul
        v-else-if="field.type === 'multiselect' && multiselectLabels.length > 0"
        class="list-disc space-y-0.5 pl-4 text-sm"
    >
        <li v-for="label in multiselectLabels" :key="label">{{ label }}</li>
    </ul>
    <span v-else-if="field.type === 'multiselect'">{{
        EMPTY_PLACEHOLDER
    }}</span>

    <!-- richtext / htmlOrText: rendered HTML -->
    <div
        v-else-if="
            (field.type === 'richtext' || field.type === 'htmlOrText') &&
            htmlContent
        "
        class="text-sm **:my-0 [&_p]:leading-snug"
        v-html="htmlContent"
    />
    <span
        v-else-if="field.type === 'richtext' || field.type === 'htmlOrText'"
        >{{ EMPTY_PLACEHOLDER }}</span
    >

    <!-- textarea: preserve whitespace; auto-render Markdown headings -->
    <div
        v-else-if="field.type === 'textarea' && markdownHtml"
        class="text-sm [&_blockquote]:border-l-2 [&_blockquote]:border-border [&_blockquote]:pl-3 [&_blockquote]:text-muted-foreground [&_h1]:mt-4 [&_h1]:mb-2 [&_h1]:text-xl [&_h1]:font-bold [&_h2]:mt-4 [&_h2]:mb-2 [&_h2]:text-base [&_h2]:font-semibold [&_h3]:mt-3 [&_h3]:mb-1 [&_h3]:text-sm [&_h3]:font-semibold [&_li]:my-0.5 [&_ol]:my-2 [&_ol]:list-decimal [&_ol]:pl-5 [&_p]:my-2 [&_strong]:font-semibold [&_ul]:my-2 [&_ul]:list-disc [&_ul]:pl-5"
        v-html="markdownHtml"
    />
    <span
        v-else-if="field.type === 'textarea' && value"
        class="text-sm whitespace-pre-wrap"
        >{{ value }}</span
    >
    <span v-else-if="field.type === 'textarea'">{{ EMPTY_PLACEHOLDER }}</span>

    <!-- date -->
    <span v-else-if="field.type === 'date'" class="text-muted-foreground">{{
        formatDate(value)
    }}</span>

    <!-- datetime -->
    <span v-else-if="field.type === 'datetime'" class="text-muted-foreground">{{
        formatDateTime(value)
    }}</span>

    <!-- image: thumbnail or placeholder icon -->
    <img
        v-else-if="field.type === 'image' && value && !imageFailed"
        :alt="field.label"
        :src="encodeURI(String(value))"
        class="size-10 rounded bg-muted object-contain p-0.5"
        @error="imageFailed = true"
    />
    <span
        v-else-if="field.type === 'image'"
        class="flex size-10 items-center justify-center rounded bg-muted text-muted-foreground"
    >
        <ImageIcon class="size-5" />
    </span>

    <!-- file: download link -->
    <a
        v-else-if="field.type === 'file' && value"
        :href="String(value)"
        class="text-sm text-primary underline-offset-2 hover:underline"
        rel="noopener"
        target="_blank"
        >{{ String(value).split('/').pop() }}</a
    >
    <span v-else-if="field.type === 'file'">{{ EMPTY_PLACEHOLDER }}</span>

    <!-- color: swatch + hex value -->
    <span
        v-else-if="field.type === 'color' && value"
        class="inline-flex items-center gap-2 text-sm"
    >
        <span
            :style="{ backgroundColor: String(value) }"
            class="size-4 rounded-sm border border-border"
        />
        {{ value }}
    </span>
    <span v-else-if="field.type === 'color'">{{ EMPTY_PLACEHOLDER }}</span>

    <!-- icon: Lucide icon preview -->
    <IconPreview
        v-else-if="field.type === 'icon' && value"
        :label="display(value)"
        :value="String(value)"
    />
    <span v-else-if="field.type === 'icon'">{{ EMPTY_PLACEHOLDER }}</span>

    <!-- repeater: bullet list -->
    <ul
        v-else-if="field.type === 'repeater' && repeaterItems.length > 0"
        class="list-disc space-y-0.5 pl-4 text-sm"
    >
        <li v-for="item in repeaterItems" :key="item">{{ item }}</li>
    </ul>
    <span v-else-if="field.type === 'repeater'">{{ EMPTY_PLACEHOLDER }}</span>

    <!-- range / number -->
    <span v-else-if="field.type === 'range' || field.type === 'number'">
        {{
            value !== null && value !== undefined && value !== ''
                ? value
                : EMPTY_PLACEHOLDER
        }}
    </span>

    <!-- default (text and untyped): auto-link URLs with a copy button, else plain text -->
    <span v-else-if="isUrl(value)" class="inline-flex items-center gap-1.5">
        <a
            :href="externalHref(String(value))"
            class="text-sm break-all text-primary underline-offset-2 hover:underline"
            rel="noopener"
            target="_blank"
            >{{ displayUrl(String(value)) }}</a
        >
        <button
            aria-label="Copy link"
            class="inline-flex size-6 shrink-0 items-center justify-center rounded text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
            type="button"
            @click.stop="copyText(String(value))"
        >
            <Copy class="size-3.5" />
        </button>
    </span>
    <span v-else>{{ display(value) }}</span>
</template>
