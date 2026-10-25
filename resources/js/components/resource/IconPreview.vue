<script lang="ts" setup>
import * as LucideIcons from '@lucide/vue';
import * as SimpleIcons from 'simple-icons';
import { computed } from 'vue';

interface SiIcon {
    slug: string;
    title: string;
    path: string;
}

const props = defineProps<{
    value: string | null | undefined;
    /** Overrides the text shown next to the glyph (e.g. a truncated `value`). */
    label?: string;
}>();

const parsed = computed(() => {
    const v = props.value;

    if (!v) {
        return null;
    }

    const colon = v.indexOf(':');

    if (colon === -1) {
        return { prefix: 'lucide', name: v };
    }

    return { prefix: v.slice(0, colon), name: v.slice(colon + 1) };
});

const lucideComponent = computed(() => {
    const p = parsed.value;

    if (!p || p.prefix !== 'lucide') {
        return null;
    }

    const pascal = p.name.replace(/(^|-)([a-z])/g, (_, __, c: string) =>
        c.toUpperCase(),
    );

    return (LucideIcons as Record<string, unknown>)[pascal] ?? null;
});

const siIcon = computed((): SiIcon | null => {
    const p = parsed.value;

    if (!p || p.prefix !== 'si') {
        return null;
    }

    const key = 'si' + p.name.charAt(0).toUpperCase() + p.name.slice(1);

    return ((SimpleIcons as Record<string, unknown>)[key] as SiIcon) ?? null;
});

function siSvg(path: string): string {
    return `<svg viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="${path}"/></svg>`;
}
</script>

<template>
    <span v-if="value" class="inline-flex items-center gap-1.5">
        <component
            :is="lucideComponent"
            v-if="lucideComponent"
            class="h-4 w-4 shrink-0"
        />
        <span
            v-else-if="siIcon"
            class="inline-flex h-4 w-4 shrink-0 items-center justify-center [&>svg]:h-4 [&>svg]:w-4"
            v-html="siSvg(siIcon.path)"
        />
        <code class="text-xs text-muted-foreground">{{ label ?? value }}</code>
    </span>
</template>
