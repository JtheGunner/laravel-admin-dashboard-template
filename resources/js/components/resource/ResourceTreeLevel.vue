<script lang="ts" setup>
import { Copy, GripVertical, Pencil, Trash2 } from '@lucide/vue';
import draggable from 'vuedraggable';
import AppTooltip from '@/components/AppTooltip.vue';
import ResourceCell from '@/components/resource/ResourceCell.vue';
import ResourceRowContextMenu from '@/components/resource/ResourceRowContextMenu.vue';
import { Checkbox } from '@/components/ui/checkbox';
import {
    copyableText,
    treeColumnClass,
    treeColumnStyle,
} from '@/lib/resourceCell';
import type { ColumnConfig, ResourceRow, TreeNode } from '@/types/resource';

/**
 * Renders a sibling level of a resource tree and calls itself recursively for its
 * children. Drag & drop is limited to the respective level via a `group` name
 * that is unique per parent ID — so entries can only be reordered within the
 * same parent group.
 */
const props = defineProps<{
    nodes: TreeNode[];
    columns: ColumnConfig[];
    /** Parent ID of this level (null = top level). */
    parentId: number | null;
    sortable?: boolean;
    /**
     * Whether the selection checkbox column is shown. Must mirror the tree
     * header's `selectable` (in ResourceIndex) — otherwise the body renders a
     * checkbox column the header lacks and every cell shifts out of alignment.
     */
    selectable?: boolean;
    /**
     * Fixed width (CSS length) reserved for the actions column, mirroring the tree
     * header's `treeActionsWidth`. Keeps the header and rows reserving the same
     * space so a `grow` column stays identical and the trailing columns align.
     */
    actionsWidth?: string;
    showEdit?: boolean;
    showDelete?: boolean;
    showCopy?: boolean;
    /** IDs of the currently selected rows (multi-select). */
    selectedIds?: number[];
}>();

const emit = defineEmits<{
    edit: [row: ResourceRow];
    remove: [row: ResourceRow];
    copy: [row: ResourceRow];
    reorder: [parentId: number | null, ids: number[]];
    toggle: [id: number, value: boolean | 'indeterminate'];
}>();

function onEnd() {
    emit(
        'reorder',
        props.parentId,
        props.nodes.map((node) => node.id),
    );
}

// Fixed column width shared with the tree header (ResourceIndex), plus the depth
// indentation on the first column. `box-border` keeps the column width constant
// despite the padding, so columns stay vertically aligned across rows.
function cellStyle(
    col: ColumnConfig,
    index: number,
    node: TreeNode,
): Record<string, string> {
    const style = treeColumnStyle(col);

    if (index === 0) {
        return { ...style, paddingLeft: `${node._depth * 1.25}rem` };
    }

    return style;
}

// Full plain-text value shown as a hover tooltip when a cell is clipped (matches
// the flat table). Booleans render as compact pills and get no tooltip.
function cellTooltip(node: TreeNode, col: ColumnConfig): string {
    if (typeof node[col.key] === 'boolean') {
        return '';
    }

    return copyableText(node, col);
}
</script>

<template>
    <draggable
        :disabled="!sortable"
        :group="`tree-${parentId ?? 'root'}`"
        :list="nodes"
        handle=".drag-handle"
        item-key="id"
        tag="div"
        @end="onEnd"
    >
        <template #item="{ element }">
            <div class="border-b border-border last:border-0">
                <ResourceRowContextMenu
                    :row="element"
                    :show-copy="showCopy !== false"
                    :show-delete="showDelete !== false"
                    :show-edit="showEdit !== false"
                    :show-trashed="false"
                    @copy="emit('copy', $event)"
                    @edit="emit('edit', $event)"
                    @remove="emit('remove', $event)"
                >
                    <template #trigger>
                        <div
                            :class="element._depth === 0 ? 'font-medium' : ''"
                            class="flex items-center gap-2 px-5 py-3.5 text-sm transition-colors hover:bg-muted/40"
                            @dblclick="emit('edit', element)"
                        >
                            <span
                                v-if="selectable"
                                class="w-5 shrink-0"
                                @dblclick.stop
                            >
                                <Checkbox
                                    :aria-label="`Select row ${element.id}`"
                                    :model-value="
                                        (selectedIds ?? []).includes(element.id)
                                    "
                                    @update:model-value="
                                        (v) => emit('toggle', element.id, v)
                                    "
                                />
                            </span>
                            <span
                                v-if="sortable"
                                class="drag-handle cursor-move text-muted-foreground"
                            >
                                <GripVertical class="size-4" />
                            </span>
                            <span
                                class="w-14 shrink-0 text-xs text-muted-foreground tabular-nums"
                            >
                                {{ element._label }}
                            </span>
                            <span
                                v-for="(col, i) in columns"
                                :key="col.key"
                                :class="[
                                    col.class,
                                    'text-muted-foreground',
                                    treeColumnClass(col),
                                ]"
                                :style="cellStyle(col, i, element)"
                            >
                                <AppTooltip :text="cellTooltip(element, col)">
                                    <span class="block truncate">
                                        <ResourceCell
                                            :col="col"
                                            :row="element"
                                        />
                                    </span>
                                </AppTooltip>
                            </span>
                            <span
                                :style="{ width: actionsWidth }"
                                class="ml-auto flex shrink-0 items-center justify-end gap-0.5"
                                @dblclick.stop
                            >
                                <AppTooltip
                                    v-if="showCopy !== false"
                                    text="Duplizieren"
                                >
                                    <button
                                        aria-label="Duplizieren"
                                        class="inline-flex size-8 items-center justify-center rounded-full bg-indigo-500/10 text-indigo-600 hover:bg-indigo-500/20 dark:bg-indigo-500/20 dark:text-indigo-400"
                                        type="button"
                                        @click="emit('copy', element)"
                                    >
                                        <Copy class="size-4" />
                                    </button>
                                </AppTooltip>
                                <AppTooltip
                                    v-if="showEdit !== false"
                                    text="Edit"
                                >
                                    <button
                                        aria-label="Edit"
                                        class="inline-flex size-8 items-center justify-center rounded-full bg-sky-500/10 text-sky-600 hover:bg-sky-500/20 dark:bg-sky-500/20 dark:text-sky-400"
                                        type="button"
                                        @click="emit('edit', element)"
                                    >
                                        <Pencil class="size-4 text-primary" />
                                    </button>
                                </AppTooltip>
                                <AppTooltip
                                    v-if="showDelete !== false"
                                    text="Delete"
                                >
                                    <button
                                        aria-label="Delete"
                                        class="inline-flex size-8 items-center justify-center rounded-full bg-red-500/10 text-red-600 transition-colors hover:bg-red-500/20 dark:bg-red-500/20 dark:text-red-400"
                                        type="button"
                                        @click="emit('remove', element)"
                                    >
                                        <Trash2 class="size-4 text-red-600" />
                                    </button>
                                </AppTooltip>
                            </span>
                        </div>
                    </template>
                </ResourceRowContextMenu>

                <ResourceTreeLevel
                    v-if="element._children.length"
                    :actions-width="actionsWidth"
                    :columns="columns"
                    :nodes="element._children"
                    :parent-id="element.id"
                    :selectable="selectable"
                    :selected-ids="selectedIds"
                    :show-copy="showCopy"
                    :show-delete="showDelete"
                    :show-edit="showEdit"
                    :sortable="sortable"
                    @copy="emit('copy', $event)"
                    @edit="emit('edit', $event)"
                    @remove="emit('remove', $event)"
                    @reorder="(p, ids) => emit('reorder', p, ids)"
                    @toggle="(id, v) => emit('toggle', id, v)"
                />
            </div>
        </template>
    </draggable>
</template>
