<script lang="ts" setup>
import { SearchIcon, XIcon } from '@lucide/vue';
import { Input } from '@/components/ui/input';

withDefaults(
    defineProps<{
        placeholder?: string;
        autofocus?: boolean;
    }>(),
    {
        placeholder: 'Search…',
        autofocus: false,
    },
);

const model = defineModel<string>({ default: '' });
</script>

<template>
    <div class="relative">
        <SearchIcon
            class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
        />
        <Input
            v-model="model"
            :autofocus="autofocus"
            :class="model ? 'pr-8' : ''"
            :placeholder="placeholder"
            class="pl-9"
        />
        <button
            v-if="model"
            class="absolute top-1/2 right-2.5 -translate-y-1/2 text-muted-foreground transition-colors hover:text-foreground"
            type="button"
            @click="model = ''"
        >
            <XIcon class="size-4" />
        </button>
    </div>
</template>
