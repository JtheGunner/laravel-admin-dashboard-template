<script lang="ts" setup>
import { CheckIcon, XIcon } from '@lucide/vue';
import AppTooltip from '@/components/AppTooltip.vue';
import { BADGE_COLORS } from '@/lib/badgeColors';

/**
 * Colour picker for the curated badge palette. Stores a colour token string
 * (e.g. "emerald") or null. Rendered by ResourceFormFields for `type: 'color'`.
 */
const props = defineProps<{ modelValue: string | null | undefined }>();
const emit = defineEmits<{ 'update:modelValue': [value: string | null] }>();

function select(token: string): void {
    emit('update:modelValue', props.modelValue === token ? null : token);
}

function clear(): void {
    emit('update:modelValue', null);
}
</script>

<template>
    <div class="flex flex-wrap items-center gap-2">
        <AppTooltip
            v-for="color in BADGE_COLORS"
            :key="color.value"
            :text="color.label"
        >
            <button
                :class="[
                    color.swatch,
                    modelValue === color.value
                        ? 'ring-ring'
                        : 'ring-transparent',
                ]"
                class="flex size-7 items-center justify-center rounded-full ring-2 ring-offset-2 ring-offset-background transition-transform hover:scale-110"
                type="button"
                @click="select(color.value)"
            >
                <CheckIcon
                    v-if="modelValue === color.value"
                    class="size-4 text-white drop-shadow"
                />
            </button>
        </AppTooltip>

        <AppTooltip v-if="modelValue" text="Remove colour">
            <button
                class="flex size-7 shrink-0 items-center justify-center rounded-full border border-input text-muted-foreground transition-colors hover:bg-muted/40 hover:text-foreground"
                type="button"
                @click="clear"
            >
                <XIcon class="size-4" />
            </button>
        </AppTooltip>
    </div>
</template>
