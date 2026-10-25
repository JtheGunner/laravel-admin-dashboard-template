import type { Component, VNode } from 'vue';
import AppTooltip from '@/components/AppTooltip.vue';
import { ContextMenuSeparator } from '@/components/ui/context-menu';
import { flatten } from '@/lib/vnode';

export interface ActionEntry {
    kind: 'action';
    key: string;
    label: string;
    icon: Component | null;
    iconClass: string;
    /** Full class string from the original button, used to style hover-overlay clones. */
    buttonClass: string;
    disabled: boolean;
    run: () => void;
}

export interface SeparatorEntry {
    kind: 'separator';
    key: string;
}

/** Interactive button entries that can be rendered as clickable items. */
export type MenuEntry = ActionEntry;

/** All possible slot entry types, including visual separators. */
export type MenuSlotEntry = MenuEntry | SeparatorEntry;

/** Returns true when the VNode is a `ContextMenuSeparator` separator marker. */
export function isSeparator(node: VNode): boolean {
    return node.type === ContextMenuSeparator;
}

export function invokeHandler(handler: unknown, ...args: unknown[]): void {
    if (Array.isArray(handler)) {
        for (const fn of handler) {
            if (typeof fn === 'function') {
                fn(...args);
            }
        }
    } else if (typeof handler === 'function') {
        handler(...args);
    }
}

function pickColorClasses(className: unknown): string {
    if (typeof className !== 'string') {
        return '';
    }

    return className
        .split(/\s+/)
        .filter((token) => /^(dark:)?text-/.test(token))
        .join(' ');
}

function iconColor(button: VNode, iconNode: VNode | undefined): string {
    return (
        pickColorClasses(iconNode?.props?.class) ||
        pickColorClasses((button.props ?? {}).class)
    );
}

function normalizeClass(cls: unknown): string {
    if (typeof cls === 'string') {
        return cls;
    }

    if (Array.isArray(cls)) {
        return (cls as unknown[])
            .filter((c) => typeof c === 'string')
            .join(' ');
    }

    return '';
}

/** The `<button>` or `<a>` VNode rendered inside an `AppTooltip`'s default slot, if any. */
function tooltipInteractive(node: VNode): VNode | null {
    const children = node.children;

    if (
        children &&
        typeof children === 'object' &&
        'default' in children &&
        typeof children.default === 'function'
    ) {
        // Template-compiled slots return VNode[], but h()-created slots may return
        // a single VNode. Normalise to an array before flattening.
        const raw = children.default() as VNode | VNode[];
        const nodes = Array.isArray(raw) ? raw : [raw];
        const rendered = flatten(nodes);

        return (
            rendered.find(
                (child) => child.type === 'button' || child.type === 'a',
            ) ?? null
        );
    }

    return null;
}

/** Builds a menu entry from a single slotted action VNode, or null if unsupported. */
export function entryFromNode(
    node: VNode,
    index: number,
): MenuSlotEntry | null {
    const nodeProps = (node.props ?? {}) as Record<string, unknown>;

    if (isSeparator(node)) {
        return { kind: 'separator', key: `slot-sep-${index}` };
    }

    if (node.type === AppTooltip) {
        const interactive = tooltipInteractive(node);

        if (!interactive) {
            return null;
        }

        const interactiveProps = (interactive.props ?? {}) as Record<
            string,
            unknown
        >;
        const label =
            (nodeProps.text as string | undefined) ||
            (interactiveProps['aria-label'] as string | undefined) ||
            '';
        const iconNode = flatten(
            Array.isArray(interactive.children)
                ? (interactive.children as VNode[])
                : [],
        )[0];

        let run: () => void;

        if (interactive.type === 'a') {
            const href = String(interactiveProps.href ?? '');
            const target = String(interactiveProps.target ?? '_self');
            run = () => window.open(href, target, 'noopener,noreferrer');
        } else {
            run = () => invokeHandler(interactiveProps.onClick);
        }

        return {
            kind: 'action',
            key: `slot-${index}`,
            label,
            icon: (iconNode?.type as Component | undefined) ?? null,
            iconClass: iconColor(interactive, iconNode),
            buttonClass: normalizeClass(interactiveProps.class),
            disabled: Boolean(interactiveProps.disabled),
            run,
        };
    }

    if (node.type === 'button') {
        const label = (nodeProps['aria-label'] as string | undefined) ?? '';
        const iconNode = flatten(
            Array.isArray(node.children) ? (node.children as VNode[]) : [],
        )[0];

        return {
            kind: 'action',
            key: `slot-${index}`,
            label,
            icon: (iconNode?.type as Component | undefined) ?? null,
            iconClass: iconColor(node, iconNode),
            buttonClass: normalizeClass(nodeProps.class),
            disabled: Boolean(nodeProps.disabled),
            run: () => invokeHandler(nodeProps.onClick),
        };
    }

    return null;
}
