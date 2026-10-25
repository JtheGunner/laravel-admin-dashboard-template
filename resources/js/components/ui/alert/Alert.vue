<script lang="ts" setup>
import type { HTMLAttributes } from 'vue';
import type { AlertVariants } from '.';
import { alertVariants } from '.';
import { cn } from '@/lib/utils';

const props = defineProps<{
  class?: HTMLAttributes["class"]
  variant?: AlertVariants["variant"]
}>()
</script>

<template>
  <div
    :class="cn(alertVariants({ variant }), props.class)"
    data-slot="alert"
    role="alert"
  >
    <slot />
  </div>
</template>
