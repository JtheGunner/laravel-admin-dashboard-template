<script lang="ts" setup>
import type { RangeCalendarHeaderProps } from 'reka-ui';
import { RangeCalendarHeader, useForwardProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { reactiveOmit } from '@vueuse/core';
import { cn } from '@/lib/utils';

const props = defineProps<RangeCalendarHeaderProps & { class?: HTMLAttributes["class"] }>()

const delegatedProps = reactiveOmit(props, "class")

const forwardedProps = useForwardProps(delegatedProps)
</script>

<template>
  <RangeCalendarHeader
    :class="cn('flex justify-center pt-1 relative items-center w-full', props.class)"
    data-slot="range-calendar-header"
    v-bind="forwardedProps"
  >
    <slot />
  </RangeCalendarHeader>
</template>
