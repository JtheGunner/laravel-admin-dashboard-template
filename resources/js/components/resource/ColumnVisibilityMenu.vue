<script lang="ts" setup>
import { Columns3, RotateCcw } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import type { ColumnVisibilityEntry } from '@/composables/useVisibleColumns';

defineProps<{
    entries: ColumnVisibilityEntry[];
    hasOverrides: boolean;
}>();

const emit = defineEmits<{ toggle: [key: string]; reset: [] }>();
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger :as-child="true">
            <Button aria-label="Show columns" class="h-10" variant="outline">
                <Columns3 class="size-4" />
                Columns
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-60">
            <DropdownMenuLabel>Visible columns</DropdownMenuLabel>
            <DropdownMenuSeparator />
            <template v-for="entry in entries" :key="entry.column.key">
                <DropdownMenuCheckboxItem
                    v-if="entry.toggleable"
                    :model-value="entry.visible"
                    @select="(e: Event) => e.preventDefault()"
                    @update:model-value="emit('toggle', entry.column.key)"
                >
                    <span class="flex-1">{{ entry.column.label }}</span>
                    <span
                        v-if="
                            (entry.autoHidden || entry.fitHidden) &&
                            entry.visible
                        "
                        class="ml-2 text-xs text-muted-foreground"
                    >
                        erzwungen
                    </span>
                    <span
                        v-else-if="entry.fitHidden"
                        class="ml-2 text-xs text-muted-foreground"
                    >
                        Platz
                    </span>
                    <span
                        v-else-if="entry.autoHidden"
                        class="ml-2 text-xs text-muted-foreground"
                    >
                        auto
                    </span>
                </DropdownMenuCheckboxItem>
            </template>
            <template v-if="hasOverrides">
                <DropdownMenuSeparator />
                <DropdownMenuItem @select="emit('reset')">
                    <RotateCcw class="size-4" />
                    Reset
                </DropdownMenuItem>
            </template>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
