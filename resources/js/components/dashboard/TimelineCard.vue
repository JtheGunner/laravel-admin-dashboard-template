<script lang="ts" setup>
import type { Component } from 'vue';
import ActivityTimelineList from '@/components/dashboard/ActivityTimelineList.vue';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import type { ActivityItem } from '@/types/dashboard';

/**
 * A dashboard card that renders an activity timeline as a scrollable list. The
 * list is exactly `visibleRows` entries tall and scrolls for the rest.
 */
withDefaults(
    defineProps<{
        title: string;
        description: string;
        icon: Component;
        activity: ActivityItem[];
        emptyText?: string;
        /** How many entries are visible before the list scrolls. */
        visibleRows?: number;
    }>(),
    { emptyText: 'No entries yet.', visibleRows: 7 },
);
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle class="flex items-center gap-2">
                <component :is="icon" class="size-4.5 text-muted-foreground" />
                {{ title }}
            </CardTitle>
            <CardDescription>{{ description }}</CardDescription>
        </CardHeader>
        <CardContent>
            <p
                v-if="activity.length === 0"
                class="py-8 text-center text-sm text-muted-foreground"
            >
                {{ emptyText }}
            </p>
            <ActivityTimelineList
                v-else
                :activity="activity"
                :max-rows="visibleRows"
                show-subject
            />
        </CardContent>
    </Card>
</template>
