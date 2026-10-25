<script lang="ts" setup>
import { Activity, Pencil, Plus, Trash2 } from '@lucide/vue';
import type { Component } from 'vue';
import { computed } from 'vue';
import AppTooltip from '@/components/AppTooltip.vue';
import { badgeClasses } from '@/lib/badgeColors';
import type { ActivityItem } from '@/types/dashboard';

/**
 * The bare activity-log list (`<ol>`) used by the dashboard's TimelineCard.
 * `showSubject` appends the entry's subject after the label.
 *
 * `maxRows` turns the list into a scroll area that is exactly that many rows tall
 * (the rest scrolls). To make the height deterministic, rows are given a uniform
 * height in that mode and their text is clamped to one line each. Without
 * `maxRows` the rows keep their natural height.
 */
const props = withDefaults(
    defineProps<{
        activity: ActivityItem[];
        showSubject?: boolean;
        maxRows?: number;
    }>(),
    { showSubject: false, maxRows: 0 },
);

// One entry is a label line (text-sm, 20px) plus a note line and a time line
// (text-xs, 16px each) = 52px; the list uses gap-4 (16px) between rows.
const ROW_HEIGHT = 52;
const ROW_GAP = 16;

const scrolls = computed(() => props.maxRows > 0);
const scrollStyle = computed(() =>
    scrolls.value
        ? {
              maxHeight: `${props.maxRows * ROW_HEIGHT + (props.maxRows - 1) * ROW_GAP}px`,
          }
        : undefined,
);

// The timeline icons come from a fixed set of icon names sent by the backend
// (see DashboardMetrics::recentActivity), so they are mapped statically here —
// no full-icon-set import on the landing page.
const ICON_MAP: Record<string, Component> = {
    Plus,
    Pencil,
    Trash2,
    Activity,
};
</script>

<template>
    <div
        :class="scrolls ? 'scrollbar-stable overflow-y-auto pr-1' : ''"
        :style="scrollStyle"
    >
        <ol class="grid gap-4">
            <li
                v-for="(entry, index) in activity"
                :key="index"
                :class="scrolls ? 'overflow-hidden' : ''"
                :style="scrolls ? { height: `${ROW_HEIGHT}px` } : undefined"
                class="flex items-start gap-3"
            >
                <span
                    :class="badgeClasses(entry.color)"
                    class="flex size-8 shrink-0 items-center justify-center rounded-full ring-1 ring-inset [&>svg]:size-4"
                >
                    <component
                        :is="ICON_MAP[entry.icon]"
                        v-if="ICON_MAP[entry.icon]"
                    />
                </span>
                <div class="min-w-0 flex-1">
                    <p :class="scrolls ? 'truncate' : ''" class="text-sm">
                        <span class="font-medium">{{ entry.label }}</span>
                        <span v-if="showSubject" class="text-muted-foreground">
                            · {{ entry.subject }}</span
                        >
                    </p>
                    <p
                        v-if="entry.note"
                        class="truncate text-xs text-muted-foreground"
                    >
                        {{ entry.note }}
                    </p>
                    <AppTooltip :text="entry.occurredAt">
                        <p class="w-fit text-xs text-muted-foreground/80">
                            {{ entry.occurredAtHuman }}
                        </p>
                    </AppTooltip>
                </div>
            </li>
        </ol>
    </div>
</template>
