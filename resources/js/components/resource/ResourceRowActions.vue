<script lang="ts" setup>
import { Copy, MoreHorizontal, Pencil, RotateCcw, Trash2 } from '@lucide/vue';
import type { Component, CSSProperties, FunctionalComponent, VNode } from 'vue';
import { computed, h, ref, useSlots } from 'vue';
import AppTooltip from '@/components/AppTooltip.vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import type { MenuEntry, MenuSlotEntry } from '@/lib/row-actions';
import { entryFromNode, isSeparator } from '@/lib/row-actions';
import { flatten } from '@/lib/vnode';
import type { ResourceRow } from '@/types/resource';

/**
 * Per-row action buttons (edit / duplicate / delete, or restore / force-delete in
 * the trash). Shared by the table row cell and the card layout so the button markup
 * lives in one place. Page-specific actions are prepended via the default slot
 * (forwarded from `ResourceIndex`'s `rowActions` slot).
 *
 * Overflow behaviour: at most `MAX_VISIBLE` buttons are shown inline. When there are
 * more, the last inline slot becomes a "More actions" menu holding the rest.
 *
 * Hover behaviour: hovering the actions area reveals all overflow buttons in a Teleport-
 * based overlay that extends left over adjacent columns. The overlay is positioned via
 * getBoundingClientRect() so it is immune to `overflow:clip` ancestors and avoids any
 * conflicts with nested reka-ui primitives (PopoverRoot/DropdownMenu).
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

/** Maximum number of buttons rendered inline (the overflow trigger counts as one). */
const MAX_VISIBLE = 4;

const slots = useSlots();

/** Renders a plain array of VNodes as a fragment. */
const Render: FunctionalComponent<{ nodes: VNode[] }> = (renderProps) =>
    renderProps.nodes;

/** A separator marker — excluded from the inline button row and the overflow count. */
function isSeparatorNode(node: VNode): boolean {
    return isSeparator(node);
}

interface BuiltinAction {
    key: string;
    label: string;
    icon: Component;
    buttonClass: string;
    iconClass: string;
    event: 'edit' | 'copy' | 'remove' | 'restore' | 'force';
}

const builtinActions = computed<BuiltinAction[]>(() => {
    if (props.showTrashed) {
        return [
            {
                key: 'restore',
                label: 'Restore',
                icon: RotateCcw,
                buttonClass:
                    'bg-emerald-500/10 text-emerald-600 hover:bg-emerald-500/20 dark:bg-emerald-500/20 dark:text-emerald-400',
                iconClass: 'size-4',
                event: 'restore',
            },
            {
                key: 'force',
                label: 'Delete permanently',
                icon: Trash2,
                buttonClass:
                    'bg-red-500/10 text-red-600 hover:bg-red-500/20 dark:bg-red-500/20 dark:text-red-400',
                iconClass: 'size-4 text-red-600',
                event: 'force',
            },
        ];
    }

    const actions: BuiltinAction[] = [];

    if (props.showEdit) {
        actions.push({
            key: 'edit',
            label: 'Edit',
            icon: Pencil,
            buttonClass:
                'bg-sky-500/10 text-sky-600 hover:bg-sky-500/20 dark:bg-sky-500/20 dark:text-sky-400',
            iconClass: 'size-4',
            event: 'edit',
        });
    }

    if (props.showCopy) {
        actions.push({
            key: 'copy',
            label: 'Duplicate',
            icon: Copy,
            buttonClass:
                'bg-indigo-500/10 text-indigo-600 hover:bg-indigo-500/20 dark:bg-indigo-500/20 dark:text-indigo-400',
            iconClass: 'size-4',
            event: 'copy',
        });
    }

    if (props.showDelete) {
        actions.push({
            key: 'delete',
            label: 'Delete',
            icon: Trash2,
            buttonClass:
                'bg-red-500/10 text-red-600 hover:bg-red-500/20 dark:bg-red-500/20 dark:text-red-400',
            iconClass: 'size-4 text-red-600',
            event: 'remove',
        });
    }

    return actions;
});

/** Emits the given built-in action event with the current row. */
function dispatch(event: BuiltinAction['event']): void {
    (emit as (e: BuiltinAction['event'], row: ResourceRow) => void)(
        event,
        props.row,
    );
}

/** Builds the VNode for a built-in action, matching the slotted buttons' styling. */
function builtinVNode(action: BuiltinAction): VNode {
    return h(
        AppTooltip,
        { key: action.key, text: action.label },
        {
            default: () =>
                h(
                    'button',
                    {
                        type: 'button',
                        'aria-label': action.label,
                        class: [
                            'inline-flex size-8 items-center justify-center rounded-full transition-colors',
                            action.buttonClass,
                        ],
                        onClick: () => dispatch(action.event),
                    },
                    [h(action.icon, { class: action.iconClass })],
                ),
        },
    );
}

const slotNodes = computed(() =>
    flatten(slots.default?.({ row: props.row }) ?? []),
);
const buttonNodes = computed<VNode[]>(() => [
    ...slotNodes.value.filter((node) => !isSeparatorNode(node)),
    ...builtinActions.value.map(builtinVNode),
]);

const hasOverflow = computed(() => buttonNodes.value.length > MAX_VISIBLE);
/** When overflowing, keep the last inline slot for the "More actions" trigger. */
const inlineButtons = computed(() =>
    hasOverflow.value
        ? buttonNodes.value.slice(0, MAX_VISIBLE - 1)
        : buttonNodes.value,
);
const overflowButtons = computed(() =>
    hasOverflow.value ? buttonNodes.value.slice(MAX_VISIBLE - 1) : [],
);

/**
 * All button nodes parsed into structured menu entries, used by the "More actions"
 * dropdown to render icon + label items instead of bare icon buttons.
 */
const overflowEntries = computed<MenuEntry[]>(() =>
    overflowButtons.value
        .map((node, index) => entryFromNode(node, index))
        .filter(
            (entry): entry is MenuEntry =>
                entry !== null &&
                entry.kind !== 'separator' &&
                entry.label !== '',
        ),
);

/**
 * Inline buttons parsed into entries — used in the hover overlay so inline buttons
 * can be re-rendered there (and hidden in the anchor) to achieve a seamless
 * "appended" appearance: [inline1…inlineN][overflow1…][…]
 */
const inlineEntries = computed<MenuEntry[]>(() =>
    inlineButtons.value
        .map((node, index) => entryFromNode(node, index))
        .filter(
            (entry): entry is MenuEntry =>
                entry !== null &&
                entry.kind !== 'separator' &&
                entry.label !== '',
        ),
);

/** Combined set shown in the hover overlay: inline buttons first, then overflow. */
const allExpandedEntries = computed(() => [
    ...inlineEntries.value,
    ...overflowEntries.value,
]);

/**
 * Slot overflow entries (page-specific, come first) vs. builtin overflow entries.
 * Used to place a separator between the two groups in the dropdown.
 * Separators are excluded from the count — they are not real buttons.
 */
const slotButtonCount = computed(
    () => slotNodes.value.filter((n) => !isSeparatorNode(n)).length,
);

/**
 * All slot entries shown in the dropdown (inline + overflow), including separators
 * so they are reflected at their correct positions between grouped actions.
 */
const allDropdownSlotEntries = computed<MenuSlotEntry[]>(() =>
    slotNodes.value
        .map((node, i) => entryFromNode(node, i))
        .filter(
            (e): e is MenuSlotEntry =>
                e !== null && (e.kind === 'separator' || e.label !== ''),
        ),
);

/** All builtin entries shown in the dropdown (inline + overflow). */
const allDropdownBuiltinEntries = computed<MenuEntry[]>(() =>
    buttonNodes.value
        .slice(slotButtonCount.value)
        .map((node, i) => entryFromNode(node, slotButtonCount.value + i))
        .filter(
            (e): e is MenuEntry =>
                e !== null && e.kind !== 'separator' && e.label !== '',
        ),
);

const showDropdownSeparator = computed(
    () =>
        allDropdownSlotEntries.value.some((e) => e.kind !== 'separator') &&
        allDropdownBuiltinEntries.value.length > 0,
);

// ── Hover expansion ──────────────────────────────────────────────────────────

const isExpanded = ref(false);
const anchorRef = ref<HTMLElement | null>(null);
const moreButtonRef = ref<HTMLElement | null>(null);
const overlayStyle = ref<CSSProperties>({});
let closeTimer: ReturnType<typeof setTimeout> | null = null;

function computeOverlayStyle(): void {
    if (!anchorRef.value || !moreButtonRef.value) {
        return;
    }

    // Position the overlay so overflow buttons appear immediately to the left of
    // the "..." trigger, inserted between the inline buttons and the trigger.
    const row = anchorRef.value.getBoundingClientRect();
    const moreBtn = moreButtonRef.value.getBoundingClientRect();

    overlayStyle.value = {
        position: 'fixed',
        top: `${row.top}px`,
        height: `${row.height}px`,
        right: `${window.innerWidth - moreBtn.left + 2}px`,
        zIndex: 200,
    };
}

function startExpand(): void {
    if (closeTimer !== null) {
        clearTimeout(closeTimer);
        closeTimer = null;
    }

    computeOverlayStyle();
    isExpanded.value = true;
}

function scheduleCollapse(): void {
    closeTimer = setTimeout(() => {
        isExpanded.value = false;
        closeTimer = null;
    }, 120);
}

function cancelCollapse(): void {
    if (closeTimer !== null) {
        clearTimeout(closeTimer);
        closeTimer = null;
    }
}

function collapse(): void {
    if (closeTimer !== null) {
        clearTimeout(closeTimer);
        closeTimer = null;
    }

    isExpanded.value = false;
}
</script>

<template>
    <div
        ref="anchorRef"
        class="flex items-center justify-end gap-0.5"
        @mouseenter="cancelCollapse"
        @mouseleave="scheduleCollapse"
    >
        <!--
          visibility:hidden + pointer-events-none preserve layout space (so "..." stays
          fixed) while hiding the inline buttons when the overlay is active.
          The overlay renders re-constructions of them in their place.
        -->
        <span
            :class="
                hasOverflow && isExpanded ? 'pointer-events-none invisible' : ''
            "
            class="flex items-center gap-0.5"
        >
            <Render :nodes="inlineButtons" />
        </span>
        <!--
          No AppTooltip wrapper on this trigger. Tooltip and DropdownMenu each
          wrap their trigger in a Popper anchor with their own pointer/click
          handlers; combining them on this trigger (nested `as-child`, or even
          via a wrapper) reliably swallowed the menu's click in reka-ui. The
          button is a standard "more actions" affordance and keeps an
          `aria-label`, so it stays a plain DropdownMenuTrigger.
        -->
        <!-- ref on the plain span, not inside as-child, to ensure reliable capture across all rows. -->
        <span
            v-if="hasOverflow"
            ref="moreButtonRef"
            class="inline-flex"
            @mouseenter="startExpand"
        >
            <DropdownMenu>
                <DropdownMenuTrigger :as-child="true">
                    <button
                        aria-label="More actions"
                        class="inline-flex size-8 items-center justify-center rounded-full bg-gray-500/10 text-gray-600 transition-colors hover:bg-gray-500/20 dark:bg-white/5 dark:text-gray-300 dark:hover:bg-white/10"
                        type="button"
                    >
                        <MoreHorizontal class="size-4" />
                    </button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" class="min-w-40">
                    <template
                        v-for="entry in allDropdownSlotEntries"
                        :key="entry.key"
                    >
                        <DropdownMenuSeparator
                            v-if="entry.kind === 'separator'"
                        />
                        <DropdownMenuItem
                            v-else
                            :disabled="(entry as MenuEntry).disabled"
                            @select="(entry as MenuEntry).run()"
                        >
                            <component
                                :is="(entry as MenuEntry).icon"
                                v-if="(entry as MenuEntry).icon"
                                :class="[
                                    'size-4',
                                    (entry as MenuEntry).iconClass,
                                ]"
                            />
                            {{ (entry as MenuEntry).label }}
                        </DropdownMenuItem>
                    </template>

                    <DropdownMenuSeparator v-if="showDropdownSeparator" />

                    <DropdownMenuItem
                        v-for="entry in allDropdownBuiltinEntries"
                        :key="entry.key"
                        :disabled="entry.disabled"
                        @select="entry.run()"
                    >
                        <component
                            :is="entry.icon"
                            v-if="entry.icon"
                            :class="['size-4', entry.iconClass]"
                        />
                        {{ entry.label }}
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </span>
    </div>

    <!--
      Hover expansion overlay — rendered at body level to escape overflow:clip ancestors
      and avoid conflicts with nested reka-ui primitives.

      IMPORTANT: we render fresh buttons from `overflowEntries` (structured data) rather
      than reusing the raw `overflowButtons` VNodes. Reusing the same VNode instances in a
      Teleport while they are also part of the component's render tree causes Vue to call
      parentNode() on stale .el references during patching, throwing a TypeError and
      preventing the DropdownMenu from opening.
    -->
    <Teleport v-if="hasOverflow" to="body">
        <Transition
            enter-active-class="transition-[opacity,transform] duration-500 ease-out"
            enter-from-class="opacity-0 translate-x-10"
            enter-to-class="opacity-100 translate-x-0"
            leave-active-class="transition-[opacity,transform] duration-200 ease-in"
            leave-from-class="opacity-100 translate-x-0"
            leave-to-class="opacity-0 translate-x-10"
        >
            <!--
              pointer-events-none on the outer div so the anchor underneath stays
              "hot" — the overlay is visually on top but transparent to the mouse.
              The inner span re-enables pointer events for the actual buttons and owns
              the collapse handlers, preventing anchor mouseleave/mouseenter flicker.
            -->
            <div
                v-if="isExpanded"
                :style="overlayStyle"
                class="pointer-events-none bg-background pr-0.5"
            >
                <span
                    class="pointer-events-auto flex items-center gap-0.5"
                    @mouseenter="cancelCollapse"
                    @mouseleave="scheduleCollapse"
                >
                    <AppTooltip
                        v-for="entry in allExpandedEntries"
                        :key="entry.key"
                        :text="entry.label"
                    >
                        <button
                            :aria-label="entry.label"
                            :class="
                                entry.buttonClass ||
                                'inline-flex size-8 items-center justify-center rounded-full bg-gray-500/10 text-gray-600 transition-colors hover:bg-gray-500/20 dark:bg-white/5 dark:text-gray-300 dark:hover:bg-white/10'
                            "
                            :disabled="entry.disabled"
                            type="button"
                            @click="
                                entry.run();
                                collapse();
                            "
                        >
                            <component
                                :is="entry.icon"
                                v-if="entry.icon"
                                :class="['size-4', entry.iconClass]"
                            />
                        </button>
                    </AppTooltip>
                </span>
            </div>
        </Transition>
    </Teleport>
</template>
