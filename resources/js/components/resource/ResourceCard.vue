<script lang="ts" setup>
import { GripVertical } from '@lucide/vue';
import { computed } from 'vue';
import AppTooltip from '@/components/AppTooltip.vue';
import ResourceCell from '@/components/resource/ResourceCell.vue';
import ResourceRowActions from '@/components/resource/ResourceRowActions.vue';
import ResourceRowContextMenu from '@/components/resource/ResourceRowContextMenu.vue';
import { Checkbox } from '@/components/ui/checkbox';
import { useCopy } from '@/composables/useCopy';
import { copyableText, hasCopyableContent } from '@/lib/resourceCell';
import type { RowTint } from '@/lib/rowTint';
import { ROW_TINT_CARD_CLASSES } from '@/lib/rowTint';
import type { ColumnConfig, ResourceRow } from '@/types/resource';

/**
 * A single record rendered as a vertical card for the small-screen layout: a
 * header with selection, drag handle, the title field and the action buttons,
 * then a label/value list of the remaining columns (mirrors the table's expanded
 * detail panel). Replaces horizontal scrolling below the `md` breakpoint.
 */
const props = defineProps<{
    row: ResourceRow;
    /** Columns shown as label/value pairs (the title column is omitted). */
    columns: ColumnConfig[];
    /** The column rendered as the card title (usually the first/pinned one). */
    titleColumn: ColumnConfig | null;
    selected: boolean;
    /** Optional row-tint key from the shared palette (see `@/lib/rowTint`). */
    tint?: RowTint | null;
    dragEnabled: boolean;
    showTrashed: boolean;
    showEdit: boolean;
    showDelete: boolean;
    showCopy: boolean;
    /** Whether the selection checkbox is shown (disabled for readonly logs). */
    selectable?: boolean;
    /** Read-only mode: hide selection and disable double-click-to-edit. */
    readonly?: boolean;
}>();

const emit = defineEmits<{
    toggle: [id: number, value: boolean | 'indeterminate'];
    edit: [row: ResourceRow];
    copy: [row: ResourceRow];
    remove: [row: ResourceRow];
    restore: [row: ResourceRow];
    force: [row: ResourceRow];
}>();

// The card surface: a tinted background + coloured left edge when a row-tint is
// set (replacing the default `bg-card`), otherwise the plain card background.
const surfaceClass = computed<string>(() =>
    props.tint ? ROW_TINT_CARD_CLASSES[props.tint] : 'bg-card',
);

// The title column is shown in the header, so drop it from the body list.
const bodyColumns = computed<ColumnConfig[]>(() =>
    props.titleColumn
        ? props.columns.filter((col) => col.key !== props.titleColumn?.key)
        : props.columns,
);

// Copy field values to the clipboard (double-click a field / the link copy icon).
const { copyText } = useCopy();

// Copy a field and highlight exactly the element that was double-clicked.
function copyField(event: MouseEvent, col: ColumnConfig): void {
    copyText(
        copyableText(props.row, col),
        event.currentTarget as HTMLElement | null,
    );
}
</script>

<template>
    <ResourceRowContextMenu
        :row="row"
        :show-copy="showCopy"
        :show-delete="showDelete"
        :show-edit="showEdit"
        :show-trashed="showTrashed"
        @copy="emit('copy', row)"
        @edit="emit('edit', row)"
        @force="emit('force', row)"
        @remove="emit('remove', row)"
        @restore="emit('restore', row)"
    >
        <template #actions="{ row: actionRow }">
            <slot :row="actionRow" name="rowActions" />
        </template>
        <template #trigger>
            <li
                :class="[
                    surfaceClass,
                    selected ? 'ring-2 ring-primary/40' : '',
                ]"
                class="rounded-xl border border-border"
                @dblclick="!showTrashed && !readonly && emit('edit', row)"
            >
                <div
                    class="flex items-center gap-2 border-b border-border px-4 py-3"
                >
                    <span
                        v-if="dragEnabled"
                        aria-label="Verschieben"
                        class="drag-handle mt-0.5 cursor-move text-muted-foreground"
                    >
                        <GripVertical class="size-4" />
                    </span>
                    <Checkbox
                        v-if="selectable !== false"
                        :aria-label="`Select row ${row.id}`"
                        :model-value="selected"
                        class="mt-0.5"
                        @update:model-value="(v) => emit('toggle', row.id, v)"
                        @dblclick.stop
                    />
                    <div
                        class="min-w-0 flex-1 text-sm font-medium text-foreground"
                    >
                        <ResourceCell
                            v-if="titleColumn"
                            :col="titleColumn"
                            :row="row"
                        />
                    </div>
                    <ResourceRowActions
                        :row="row"
                        :show-copy="showCopy"
                        :show-delete="showDelete"
                        :show-edit="showEdit"
                        :show-trashed="showTrashed"
                        @copy="emit('copy', row)"
                        @edit="emit('edit', row)"
                        @force="emit('force', row)"
                        @remove="emit('remove', row)"
                        @restore="emit('restore', row)"
                        @dblclick.stop
                    >
                        <template #default="{ row: actionRow }">
                            <slot :row="actionRow" name="rowActions" />
                        </template>
                    </ResourceRowActions>
                </div>

                <dl
                    class="grid grid-cols-1 gap-x-6 gap-y-3 px-4 py-3 sm:grid-cols-2"
                >
                    <div
                        v-for="col in bodyColumns"
                        :key="col.key"
                        class="flex flex-col gap-0.5"
                    >
                        <dt class="text-xs font-medium text-muted-foreground">
                            {{ col.label }}
                        </dt>
                        <dd class="min-w-0 text-sm text-foreground">
                            <!-- Only the text itself is the copy target, not the whole cell.
                         Empty fields (shown as "-") are not copyable. -->
                            <AppTooltip
                                :text="
                                    hasCopyableContent(row, col)
                                        ? 'Double-click to copy'
                                        : ''
                                "
                            >
                                <span
                                    :class="
                                        hasCopyableContent(row, col)
                                            ? 'cursor-copy'
                                            : ''
                                    "
                                    class="inline-block max-w-full align-top"
                                    @dblclick.stop="
                                        hasCopyableContent(row, col) &&
                                        copyField($event, col)
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
            </li>
        </template>
    </ResourceRowContextMenu>
</template>
