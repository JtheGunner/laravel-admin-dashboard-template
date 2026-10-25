<script lang="ts" setup>
import type { ContextMenuLabelProps } from 'reka-ui';
import { ContextMenuLabel, useForwardProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { reactiveOmit } from '@vueuse/core';
import { cn } from '@/lib/utils';

const props = defineProps<
    ContextMenuLabelProps & { class?: HTMLAttributes['class']; inset?: boolean }
>();

const delegatedProps = reactiveOmit(props, 'class', 'inset');
const forwardedProps = useForwardProps(delegatedProps);
</script>

<template>
    <ContextMenuLabel
        :class="
            cn(
                'text-muted-foreground px-2 py-1.5 text-xs font-medium data-[inset]:pl-8',
                props.class,
            )
        "
        :data-inset="inset ? '' : undefined"
        data-slot="context-menu-label"
        v-bind="forwardedProps"
    >
        <slot />
    </ContextMenuLabel>
</template>
