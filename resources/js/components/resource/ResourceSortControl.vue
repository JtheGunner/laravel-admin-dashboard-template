<script lang="ts" setup>
import { ChevronDown, ChevronUp } from '@lucide/vue';
import AppTooltip from '@/components/AppTooltip.vue';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { ColumnConfig, SortDirection } from '@/types/resource';

/**
 * Standalone sort control (column picker + direction toggle) used where clicking
 * a table header is not possible: the card layout, and the table header bar once
 * columns have been auto-hidden. Compact — the trigger sizes to its content with a
 * max width rather than stretching.
 */
defineProps<{
    /** Sortable columns to choose from. */
    columns: ColumnConfig[];
    /** Currently selected sort column key (undefined = none). */
    modelValue: string | undefined;
    /** Active sort direction (drives the toggle icon). */
    direction: SortDirection;
    /** The applied sort key; the direction toggle is disabled when null. */
    activeKey: string | null;
    /** Stretch the column picker to fill the row (card layout); compact otherwise. */
    fullWidth?: boolean;
}>();

const emit = defineEmits<{
    'update:modelValue': [key: string];
    toggleDirection: [];
}>();
</script>

<template>
    <div :class="fullWidth ? 'flex-1' : ''" class="flex items-center gap-1.5">
        <Select
            :model-value="modelValue"
            @update:model-value="(v) => emit('update:modelValue', String(v))"
        >
            <SelectTrigger
                :class="
                    fullWidth ? 'flex-1' : 'w-auto max-w-[13rem] min-w-[8rem]'
                "
                class="h-10 text-sm"
            >
                <SelectValue placeholder="Sortieren …" />
            </SelectTrigger>
            <SelectContent>
                <SelectItem
                    v-for="col in columns"
                    :key="col.key"
                    :value="col.key"
                >
                    {{ col.label }}
                </SelectItem>
            </SelectContent>
        </Select>
        <AppTooltip :text="direction === 'asc' ? 'Aufsteigend' : 'Absteigend'">
            <button
                :disabled="!activeKey"
                aria-label="Sortierrichtung umkehren"
                class="inline-flex size-10 shrink-0 items-center justify-center rounded-lg border border-border text-muted-foreground transition-colors hover:bg-muted hover:text-foreground disabled:pointer-events-none disabled:opacity-40"
                type="button"
                @click="emit('toggleDirection')"
            >
                <ChevronUp v-if="direction === 'asc'" class="size-4" />
                <ChevronDown v-else class="size-4" />
            </button>
        </AppTooltip>
    </div>
</template>
