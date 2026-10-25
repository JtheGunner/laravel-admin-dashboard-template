<script lang="ts" setup>
import type { Component } from 'vue';
import { badgeClasses } from '@/lib/badgeColors';

export interface UnderlineTab {
    /** Unique tab identifier used for v-model matching. */
    id: string;
    /** Visible label text. */
    label: string;
    /** Optional Lucide icon component rendered before the label. */
    icon?: Component;
    /** Optional row count displayed as a badge. */
    count?: number;
    /** Optional badge-color token (e.g. "emerald") for the count badge. Defaults to primary. */
    color?: string | null;
}

const props = withDefaults(
    defineProps<{
        tabs: UnderlineTab[];
        /** Whether to show count badges on tabs that have a `count` value. */
        showCount?: boolean;
    }>(),
    { showCount: false },
);

const modelValue = defineModel<string>({ required: true });

function activate(id: string): void {
    modelValue.value = id;
}
</script>

<template>
    <div class="border-b border-border">
        <nav
            class="-mb-px flex items-center space-x-2 overflow-x-auto px-6 pt-4 [&::-webkit-scrollbar]:h-1.5 [&::-webkit-scrollbar-thumb]:rounded-full [&::-webkit-scrollbar-thumb]:bg-border"
        >
            <div class="flex items-center">
                <button
                    v-for="tab in props.tabs"
                    :key="tab.id"
                    :class="
                        modelValue === tab.id
                            ? 'border-primary text-primary'
                            : 'border-transparent text-muted-foreground hover:text-foreground'
                    "
                    class="inline-flex items-center gap-2 border-b-2 px-2.5 py-2 pb-3 text-sm font-medium whitespace-nowrap transition-colors duration-200 ease-in-out"
                    type="button"
                    @click="activate(tab.id)"
                >
                    <component :is="tab.icon" v-if="tab.icon" class="size-4" />
                    {{ tab.label }}
                    <span
                        v-if="showCount && tab.count != null"
                        :class="
                            tab.color
                                ? badgeClasses(tab.color)
                                : 'bg-primary/10 text-primary'
                        "
                        class="inline-flex items-center justify-center rounded-full px-2 py-0.5 text-xs font-medium"
                    >
                        {{ tab.count.toLocaleString('en') }}
                    </span>
                </button>
            </div>

            <!-- Right-aligned slot for extra controls (e.g. search input) -->
            <slot name="trailing" />
        </nav>
    </div>
</template>
