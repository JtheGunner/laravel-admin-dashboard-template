<script lang="ts" setup>
import { Link } from '@inertiajs/vue3';
import { TrendingDown, TrendingUp } from '@lucide/vue';
import type { Component } from 'vue';

defineProps<{
    label: string;
    value: number | string;
    icon: Component;
    href?: string;
    /** Tailwind utility classes for the icon badge tint, e.g. "bg-primary/10 text-primary". */
    tint?: string;
    /**
     * Optional trend indicator shown below the main value.
     * value: percentage change (positive or negative number)
     * direction: explicit override; when omitted, derived from value sign.
     */
    trend?: {
        value: number;
        direction?: 'up' | 'down';
    };
}>();
</script>

<template>
    <component
        :is="href ? Link : 'div'"
        :class="href ? 'hover:shadow-card-lg' : ''"
        :href="href"
        class="flex flex-col gap-4 rounded-2xl border border-border bg-card p-6 shadow-card transition-shadow"
    >
        <div class="flex items-center justify-between">
            <div
                :class="tint ?? 'bg-primary/10 text-primary'"
                class="flex size-11 items-center justify-center rounded-xl"
            >
                <component :is="icon" class="size-5.5" />
            </div>

            <!-- Trend-Indikator (optional) -->
            <template v-if="trend !== undefined">
                <span
                    v-if="
                        (trend.direction ??
                            (trend.value >= 0 ? 'up' : 'down')) === 'up'
                    "
                    class="inline-flex items-center gap-1 rounded-full bg-emerald-500/10 px-2.5 py-0.5 text-xs font-medium text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-400"
                >
                    <TrendingUp class="size-3.5" />
                    +{{ Math.abs(trend.value) }}%
                </span>
                <span
                    v-else
                    class="inline-flex items-center gap-1 rounded-full bg-red-500/10 px-2.5 py-0.5 text-xs font-medium text-red-500 dark:bg-red-500/20 dark:text-red-400"
                >
                    <TrendingDown class="size-3.5" />
                    -{{ Math.abs(trend.value) }}%
                </span>
            </template>
        </div>

        <div class="min-w-0">
            <p
                class="truncate text-xs font-semibold tracking-wider text-muted-foreground/80 uppercase"
            >
                {{ label }}
            </p>
            <p class="mt-2 text-3xl leading-none font-bold tabular-nums">
                {{ value }}
            </p>
        </div>
    </component>
</template>
