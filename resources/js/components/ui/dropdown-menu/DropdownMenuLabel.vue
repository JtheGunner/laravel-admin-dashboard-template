<script lang="ts" setup>
import type { DropdownMenuLabelProps } from 'reka-ui';
import { DropdownMenuLabel, useForwardProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { reactiveOmit } from '@vueuse/core';
import { cn } from '@/lib/utils';

const props = defineProps<DropdownMenuLabelProps & { class?: HTMLAttributes["class"], inset?: boolean }>()

const delegatedProps = reactiveOmit(props, "class", "inset")
const forwardedProps = useForwardProps(delegatedProps)
</script>

<template>
  <DropdownMenuLabel
    :class="cn('px-2 py-1.5 text-sm font-medium data-[inset]:pl-8', props.class)"
    :data-inset="inset ? '' : undefined"
    data-slot="dropdown-menu-label"
    v-bind="forwardedProps"
  >
    <slot />
  </DropdownMenuLabel>
</template>
