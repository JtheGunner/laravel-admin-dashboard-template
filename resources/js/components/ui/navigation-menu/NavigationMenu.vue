<script lang="ts" setup>
import type { NavigationMenuRootEmits, NavigationMenuRootProps } from 'reka-ui';
import { NavigationMenuRoot, useForwardPropsEmits } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { reactiveOmit } from '@vueuse/core';
import { cn } from '@/lib/utils';
import NavigationMenuViewport from './NavigationMenuViewport.vue';

const props = withDefaults(defineProps<NavigationMenuRootProps & {
  class?: HTMLAttributes["class"]
  viewport?: boolean
}>(), {
  viewport: true,
})
const emits = defineEmits<NavigationMenuRootEmits>()

const delegatedProps = reactiveOmit(props, "class", "viewport")
const forwarded = useForwardPropsEmits(delegatedProps, emits)
</script>

<template>
  <NavigationMenuRoot
    v-slot="slotProps"
    :class="cn('group/navigation-menu relative flex max-w-max flex-1 items-center justify-center', props.class)"
    :data-viewport="viewport"
    data-slot="navigation-menu"
    v-bind="forwarded"
  >
    <slot v-bind="slotProps" />
    <NavigationMenuViewport v-if="viewport" />
  </NavigationMenuRoot>
</template>
