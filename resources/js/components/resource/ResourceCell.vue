<script lang="ts" setup>
import { computed } from 'vue';
import ResourceCellValue from '@/components/resource/ResourceCellValue.vue';
import { isTruncating, subtitleText } from '@/lib/resourceCell';
import type { ColumnConfig, ResourceRow } from '@/types/resource';

/**
 * Table cell: renders the column's primary value via {@link ResourceCellValue} and,
 * when the column declares one, a muted second line beneath it (the `subtitle` free
 * text or the value of `subtitleKey`). Both the flat table, the tree and the row
 * detail panel render through here, so a subtitle shows consistently everywhere.
 */
const props = defineProps<{
    row: ResourceRow;
    col: ColumnConfig;
    /** Detail context — forwarded verbatim to {@link ResourceCellValue}. */
    detail?: boolean;
}>();

// Forwarded from the value renderer (per-link / password copy affordances in the
// detail panel); the parent writes the value to the clipboard.
const emit = defineEmits<{ copy: [text: string, el?: HTMLElement | null] }>();

// image-text columns render the subtitle inline (logo left, text+subtitle stacked
// right) inside ResourceCellValue itself, so we must not emit a duplicate here.
const subtitle = computed<string>(() =>
    props.col.type === 'image-text' ? '' : subtitleText(props.row, props.col),
);

// A bounded-width column (grow / percentage / absolute cap) clips both the primary
// value and the subtitle to a single truncated line. An unbounded `content` column
// stays intrinsically sized: the subtitle must NOT truncate so it can widen the
// column just like a long primary value does (`min-w-0` would collapse that width).
const bounded = computed<boolean>(() => isTruncating(props.col));
</script>

<template>
    <span
        v-if="subtitle"
        :class="bounded ? 'min-w-0' : ''"
        class="flex flex-col leading-tight"
    >
        <span :class="bounded ? 'truncate' : ''">
            <ResourceCellValue
                :col="col"
                :detail="detail"
                :row="row"
                @copy="(text, el) => emit('copy', text, el)"
            />
        </span>
        <span
            :class="bounded ? 'truncate' : ''"
            class="pt-0.5 text-xs font-normal text-muted-foreground"
            >{{ subtitle }}</span
        >
    </span>
    <ResourceCellValue
        v-else
        :col="col"
        :detail="detail"
        :row="row"
        @copy="(text, el) => emit('copy', text, el)"
    />
</template>
