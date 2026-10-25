<script lang="ts" setup>
import { Bold, Italic, List, ListOrdered } from '@lucide/vue';
import StarterKit from '@tiptap/starter-kit';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import { watch } from 'vue';

const props = defineProps<{ modelValue: string | null; resizable?: boolean }>();
const emit = defineEmits<{ 'update:modelValue': [value: string] }>();

// When `resizable` is set, the editable area itself gets a drag handle
// (`resize-y`) plus its own scroll, so the user can size the field freely.
const editorClass = [
    'min-h-48 max-w-none bg-transparent px-3 py-2 text-sm focus:outline-none',
    props.resizable ? 'resize-y overflow-auto' : '',
]
    .filter(Boolean)
    .join(' ');

const editor = useEditor({
    content: props.modelValue || '',
    extensions: [StarterKit],
    editorProps: {
        attributes: {
            class: editorClass,
        },
    },
    onUpdate: ({ editor }) => emit('update:modelValue', editor.getHTML()),
});

// Mirror external changes (e.g. prefill while editing) into the editor.
watch(
    () => props.modelValue,
    (value) => {
        const next = value || '';

        if (editor.value && next !== editor.value.getHTML()) {
            editor.value.commands.setContent(next, { emitUpdate: false });
        }
    },
);

function btnClass(active: boolean): string {
    return [
        'rounded p-1.5 hover:bg-muted',
        active ? 'bg-muted text-foreground' : 'text-muted-foreground',
    ].join(' ');
}
</script>

<template>
    <div
        class="overflow-hidden rounded-md border border-input bg-white focus-within:ring-1 focus-within:ring-ring dark:bg-input/30"
    >
        <div
            v-if="editor"
            class="flex gap-1 border-b border-input bg-muted/40 p-1.5"
        >
            <button
                :class="btnClass(editor.isActive('bold'))"
                type="button"
                @click="editor.chain().focus().toggleBold().run()"
            >
                <Bold class="size-4" />
            </button>
            <button
                :class="btnClass(editor.isActive('italic'))"
                type="button"
                @click="editor.chain().focus().toggleItalic().run()"
            >
                <Italic class="size-4" />
            </button>
            <button
                :class="btnClass(editor.isActive('bulletList'))"
                type="button"
                @click="editor.chain().focus().toggleBulletList().run()"
            >
                <List class="size-4" />
            </button>
            <button
                :class="btnClass(editor.isActive('orderedList'))"
                type="button"
                @click="editor.chain().focus().toggleOrderedList().run()"
            >
                <ListOrdered class="size-4" />
            </button>
        </div>
        <EditorContent :editor="editor" />
    </div>
</template>

<style scoped>
:deep(.ProseMirror) {
    min-height: 8rem;
}
:deep(.ProseMirror:focus) {
    outline: none;
}
:deep(.ProseMirror ul) {
    list-style: disc;
    padding-left: 1.25rem;
}
:deep(.ProseMirror ol) {
    list-style: decimal;
    padding-left: 1.25rem;
}
:deep(.ProseMirror p) {
    margin: 0.25rem 0;
}
</style>
