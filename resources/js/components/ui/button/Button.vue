<script lang="ts" setup>
import type { PrimitiveProps } from 'reka-ui';
import { Primitive } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { LoaderCircle } from '@lucide/vue';
import type { ButtonVariants } from '.';
import { buttonVariants } from '.';
import { cn } from '@/lib/utils';

interface Props extends PrimitiveProps {
  variant?: ButtonVariants["variant"]
  size?: ButtonVariants["size"]
  class?: HTMLAttributes["class"]
  loading?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  as: "button",
})
</script>

<template>
  <Primitive
    :as="as"
    :as-child="asChild"
    :class="cn(buttonVariants({ variant, size }), props.class)"
    :disabled="props.loading || undefined"
    data-slot="button"
  >
    <LoaderCircle v-if="props.loading" class="size-4 shrink-0 animate-spin" />
    <slot />
  </Primitive>
</template>
