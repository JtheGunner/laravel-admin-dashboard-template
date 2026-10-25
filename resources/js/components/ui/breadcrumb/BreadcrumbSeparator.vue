<script lang="ts" setup>
import type { HTMLAttributes } from 'vue';
import { ChevronRight } from '@lucide/vue';
import { cn } from '@/lib/utils';

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()
</script>

<template>
  <li
    :class="cn('[&>svg]:size-3.5', props.class)"
    aria-hidden="true"
    data-slot="breadcrumb-separator"
    role="presentation"
  >
    <slot>
      <ChevronRight />
    </slot>
  </li>
</template>
