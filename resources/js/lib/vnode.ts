import type { VNode } from 'vue';
import { Comment, Fragment, Text } from 'vue';

/** Flattens fragments and drops comment / whitespace-only text nodes. */
export function flatten(nodes: VNode[]): VNode[] {
    const out: VNode[] = [];

    for (const node of nodes) {
        if (node.type === Fragment && Array.isArray(node.children)) {
            out.push(...flatten(node.children as VNode[]));
        } else if (node.type === Comment) {
            continue;
        } else if (
            node.type === Text &&
            typeof node.children === 'string' &&
            node.children.trim() === ''
        ) {
            continue;
        } else {
            out.push(node);
        }
    }

    return out;
}
