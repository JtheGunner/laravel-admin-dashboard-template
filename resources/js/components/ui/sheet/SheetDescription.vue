<script lang="ts" setup>
import type { DialogDescriptionProps } from 'reka-ui';
import { DialogDescription } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { reactiveOmit } from '@vueuse/core';
import { cn } from '@/lib/utils';

const props = defineProps<DialogDescriptionProps & { class?: HTMLAttributes["class"] }>()

const delegatedProps = reactiveOmit(props, "class")
</script>

<template>
  <DialogDescription
    :class="cn('text-muted-foreground text-sm', props.class)"
    data-slot="sheet-description"
    v-bind="delegatedProps"
  >
    <slot />
  </DialogDescription>
</template>
