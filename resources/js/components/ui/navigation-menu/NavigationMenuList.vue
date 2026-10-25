<script lang="ts" setup>
import type { NavigationMenuListProps } from 'reka-ui';
import { NavigationMenuList, useForwardProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { reactiveOmit } from '@vueuse/core';
import { cn } from '@/lib/utils';

const props = defineProps<NavigationMenuListProps & { class?: HTMLAttributes["class"] }>()

const delegatedProps = reactiveOmit(props, "class")

const forwardedProps = useForwardProps(delegatedProps)
</script>

<template>
  <NavigationMenuList
    :class="
      cn(
        'group flex flex-1 list-none items-center justify-center gap-1',
        props.class,
      )
    "
    data-slot="navigation-menu-list"
    v-bind="forwardedProps"
  >
    <slot />
  </NavigationMenuList>
</template>
