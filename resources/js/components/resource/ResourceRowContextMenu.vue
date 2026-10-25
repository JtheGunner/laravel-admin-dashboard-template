<script lang="ts" setup>
import { Copy, Pencil, RotateCcw, Trash2 } from '@lucide/vue';
import { computed, useSlots } from 'vue';
import {
    ContextMenu,
    ContextMenuContent,
    ContextMenuItem,
    ContextMenuSeparator,
    ContextMenuTrigger,
} from '@/components/ui/context-menu';
import type { ActionEntry, MenuEntry, MenuSlotEntry } from '@/lib/row-actions';
import { entryFromNode } from '@/lib/row-actions';
import { flatten } from '@/lib/vnode';
import type { ResourceRow } from '@/types/resource';

/**
 * Wraps a table/tree row (passed via the `trigger` slot) in a right-click context
 * menu that mirrors the per-row action buttons and switches.
 *
 * The built-in actions (edit / duplicate / delete, or restore / force-delete in the
 * trash) are known structurally. Page-specific actions are forwarded verbatim through
 * the `actions` slot — the same VNodes the row renders inline via `ResourceRowActions`
 * — and introspected here into menu entries: an `AppTooltip`-wrapped `<button>` becomes
 * a normal item (its tooltip text is the label, its icon and click handler are reused).
 *
 * When no action applies at all (e.g. a read-only log with no custom actions) the
 * trigger is rendered bare, without attaching a menu.
 */
const props = defineProps<{
    row: ResourceRow;
    showTrashed: boolean;
    showEdit: boolean;
    showDelete: boolean;
    showCopy: boolean;
}>();

const emit = defineEmits<{
    edit: [row: ResourceRow];
    copy: [row: ResourceRow];
    remove: [row: ResourceRow];
    restore: [row: ResourceRow];
    force: [row: ResourceRow];
}>();

const slots = useSlots();

/** Page-specific entries derived from the forwarded `actions` slot (includes separators). */
const slotEntries = computed<MenuSlotEntry[]>(() => {
    const nodes = flatten(slots.actions?.({ row: props.row }) ?? []);

    return nodes
        .map((node, index) => entryFromNode(node, index))
        .filter(
            (entry): entry is MenuSlotEntry =>
                entry !== null &&
                (entry.kind === 'separator' || entry.label !== ''),
        );
});

/**
 * Built-in edit / duplicate / delete (or restore / force-delete) entries. The
 * `iconClass` colours mirror the matching `ResourceRowActions` buttons exactly.
 */
const builtinEntries = computed<ActionEntry[]>(() => {
    if (props.showTrashed) {
        return [
            {
                kind: 'action',
                key: 'restore',
                label: 'Restore',
                icon: RotateCcw,
                iconClass: 'text-emerald-600 dark:text-emerald-400',
                buttonClass: '',
                disabled: false,
                run: () => emit('restore', props.row),
            },
            {
                kind: 'action',
                key: 'force',
                label: 'Delete permanently',
                icon: Trash2,
                iconClass: 'text-red-600',
                buttonClass: '',
                disabled: false,
                run: () => emit('force', props.row),
            },
        ];
    }

    const entries: ActionEntry[] = [];

    if (props.showEdit) {
        entries.push({
            kind: 'action',
            key: 'edit',
            label: 'Edit',
            icon: Pencil,
            iconClass: 'text-sky-600 dark:text-sky-400',
            buttonClass: '',
            disabled: false,
            run: () => emit('edit', props.row),
        });
    }

    if (props.showCopy) {
        entries.push({
            kind: 'action',
            key: 'copy',
            label: 'Duplicate',
            icon: Copy,
            iconClass: 'text-indigo-600 dark:text-indigo-400',
            buttonClass: '',
            disabled: false,
            run: () => emit('copy', props.row),
        });
    }

    if (props.showDelete) {
        entries.push({
            kind: 'action',
            key: 'delete',
            label: 'Delete',
            icon: Trash2,
            iconClass: 'text-red-600',
            buttonClass: '',
            disabled: false,
            run: () => emit('remove', props.row),
        });
    }

    return entries;
});

const hasActionSlotEntries = computed(() =>
    slotEntries.value.some((e) => e.kind !== 'separator'),
);

const hasMenu = computed(
    () => hasActionSlotEntries.value || builtinEntries.value.length > 0,
);

// A divider between the page-specific block and the built-in block, but only when
// both are present (separators-only slot does not count as a real block).
const showSeparator = computed(
    () => hasActionSlotEntries.value && builtinEntries.value.length > 0,
);
</script>

<template>
    <ContextMenu v-if="hasMenu">
        <ContextMenuTrigger :as-child="true">
            <slot name="trigger" />
        </ContextMenuTrigger>
        <ContextMenuContent>
            <template v-for="entry in slotEntries" :key="entry.key">
                <ContextMenuSeparator v-if="entry.kind === 'separator'" />
                <ContextMenuItem
                    v-else
                    :disabled="(entry as MenuEntry).disabled"
                    @select="(entry as MenuEntry).run()"
                >
                    <component
                        :is="(entry as MenuEntry).icon"
                        v-if="(entry as MenuEntry).icon"
                        :class="['size-4', (entry as MenuEntry).iconClass]"
                    />
                    {{ (entry as MenuEntry).label }}
                </ContextMenuItem>
            </template>

            <ContextMenuSeparator v-if="showSeparator" />

            <ContextMenuItem
                v-for="entry in builtinEntries"
                :key="entry.key"
                @select="entry.run()"
            >
                <component
                    :is="entry.icon"
                    v-if="entry.icon"
                    :class="['size-4', entry.iconClass]"
                />
                {{ entry.label }}
            </ContextMenuItem>
        </ContextMenuContent>
    </ContextMenu>
    <slot v-else name="trigger" />
</template>
