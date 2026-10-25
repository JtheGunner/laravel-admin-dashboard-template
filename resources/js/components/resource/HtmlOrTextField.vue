<script lang="ts" setup>
import { Code, Type } from '@lucide/vue';
import { ref } from 'vue';
import RichTextField from '@/components/resource/RichTextField.vue';

/**
 * Field that captures the same string either via a WYSIWYG editor (Tiptap, stores
 * HTML) or as a raw textarea (plain text or hand-typed HTML).
 * The stored value is in both cases simply the string — so exactly what was
 * entered in the respective mode is stored.
 */
const props = defineProps<{ modelValue: string | null }>();
const emit = defineEmits<{ 'update:modelValue': [value: string] }>();

// Guess the initial mode from the existing value: if it contains HTML tags, we start
// in rich-text mode, otherwise in the plain-text textarea.
const looksLikeHtml = /<[a-z][\s\S]*>/i.test(props.modelValue ?? '');
const mode = ref<'rich' | 'plain'>(looksLikeHtml ? 'rich' : 'plain');

function update(value: string): void {
    emit('update:modelValue', value);
}

function tabClass(active: boolean): string {
    return [
        'inline-flex items-center gap-1.5 rounded-md px-2.5 py-1 text-xs font-medium transition-colors',
        active
            ? 'bg-primary/10 text-primary'
            : 'text-muted-foreground hover:bg-muted',
    ].join(' ');
}
</script>

<template>
    <div class="grid gap-2">
        <div class="flex gap-1">
            <button
                :class="tabClass(mode === 'rich')"
                type="button"
                @click="mode = 'rich'"
            >
                <Type class="size-3.5" /> Rich-Text
            </button>
            <button
                :class="tabClass(mode === 'plain')"
                type="button"
                @click="mode = 'plain'"
            >
                <Code class="size-3.5" /> Plain-Text
            </button>
        </div>

        <RichTextField
            v-if="mode === 'rich'"
            :model-value="modelValue"
            @update:model-value="update"
        />
        <textarea
            v-else
            :value="modelValue ?? ''"
            class="flex min-h-24 w-full rounded-lg border border-input bg-white px-4 py-2.5 font-mono text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
            rows="4"
            @input="update(($event.target as HTMLTextAreaElement).value)"
        />
    </div>
</template>
