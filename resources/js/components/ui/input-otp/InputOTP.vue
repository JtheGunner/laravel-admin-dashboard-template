<script lang="ts" setup>
import type { HTMLAttributes } from 'vue';
import type { OTPInputEmits, OTPInputProps } from 'vue-input-otp';
import { OTPInput } from 'vue-input-otp';
import { reactiveOmit } from '@vueuse/core';
import { useForwardPropsEmits } from 'reka-ui';
import { cn } from '@/lib/utils';

const props = defineProps<OTPInputProps & { class?: HTMLAttributes["class"] }>()

const emits = defineEmits<OTPInputEmits>()

const delegatedProps = reactiveOmit(props, "class", "containerClass")

const forwarded = useForwardPropsEmits(delegatedProps, emits)
</script>

<template>
  <OTPInput
    v-slot="slotProps"
    :container-class="cn('flex items-center gap-2 has-disabled:opacity-50', props.class)"
    class="disabled:cursor-not-allowed"
    data-slot="input-otp"
    v-bind="forwarded"
  >
    <slot v-bind="slotProps" />
  </OTPInput>
</template>
