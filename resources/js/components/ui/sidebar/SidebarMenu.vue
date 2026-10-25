<script lang="ts" setup>
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()
</script>

<template>
  <ul
    :class="cn('flex w-full min-w-0 flex-col gap-1 group-data-[collapsible=icon]:justify-center group-data-[collapsible=icon]:items-center', props.class)"
    data-sidebar="menu"
    data-slot="sidebar-menu"
  >
    <slot />
  </ul>
</template>
