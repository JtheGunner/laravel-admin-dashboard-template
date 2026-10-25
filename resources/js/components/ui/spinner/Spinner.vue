<script lang="ts" setup>
import type { HTMLAttributes } from 'vue';
import { Loader2Icon } from '@lucide/vue';
import { cn } from '@/lib/utils';

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()
</script>

<template>
  <Loader2Icon
    :class="cn('size-4 animate-spin', props.class)"
    aria-label="Loading"
    role="status"
  />
</template>
