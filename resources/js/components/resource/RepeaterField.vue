<script lang="ts" setup>
import { Plus, X } from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

const props = defineProps<{
    modelValue: string[] | null;
    placeholder?: string;
}>();
const emit = defineEmits<{ 'update:modelValue': [value: string[]] }>();

const items = computed<string[]>(() =>
    Array.isArray(props.modelValue) ? props.modelValue : [],
);

function updateItem(index: number, value: string) {
    const next = [...items.value];
    next[index] = value;
    emit('update:modelValue', next);
}

function addItem() {
    emit('update:modelValue', [...items.value, '']);
}

function removeItem(index: number) {
    const next = [...items.value];
    next.splice(index, 1);
    emit('update:modelValue', next);
}
</script>

<template>
    <div class="space-y-2">
        <div v-for="(item, index) in items" :key="index" class="flex gap-2">
            <Input
                :model-value="item"
                :placeholder="placeholder"
                @update:model-value="(v) => updateItem(index, String(v))"
            />
            <Button
                aria-label="Remove entry"
                size="icon"
                type="button"
                variant="ghost"
                @click="removeItem(index)"
            >
                <X class="size-4" />
            </Button>
        </div>
        <Button size="sm" type="button" variant="outline" @click="addItem">
            <Plus class="size-4" />
            Add entry
        </Button>
    </div>
</template>
