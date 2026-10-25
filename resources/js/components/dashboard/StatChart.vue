<script lang="ts" setup>
import type { ApexOptions } from 'apexcharts';
import type { Component } from 'vue';
import { computed } from 'vue';
import VueApexCharts from 'vue3-apexcharts';
import { useAppearance } from '@/composables/useAppearance';

const props = defineProps<{
    title: string;
    type: 'donut' | 'bar';
    /** Donut: number[]; Bar: [{ name, data: number[] }]. */
    series: ApexOptions['series'];
    /** Donut slice labels. */
    labels?: string[];
    /** Bar x-axis categories. */
    categories?: string[];
    height?: number;
    /** Optional icon shown before the title. */
    icon?: Component;
}>();

// Palette aligned with the --chart-* CSS tokens (indigo, teal, amber, purple, pink).
const palette = ['#6172F3', '#13B5A6', '#F5A524', '#A855F7', '#EC4899'];

const { resolvedAppearance } = useAppearance();
const mode = computed(() => resolvedAppearance.value);

const hasData = computed(() => {
    const s = props.series;

    if (Array.isArray(s) && s.length === 0) {
        return false;
    }

    if (props.type === 'donut') {
        return (s as number[]).some((v) => Number(v) > 0);
    }

    return true;
});

const options = computed<ApexOptions>(() => {
    const gridColor = mode.value === 'dark' ? '#2A3142' : '#EAECF2';

    const base: ApexOptions = {
        chart: {
            fontFamily: 'inherit',
            toolbar: { show: false },
            background: 'transparent',
        },
        colors: palette,
        theme: { mode: mode.value },
        tooltip: { theme: mode.value },
        dataLabels: { enabled: false },
        legend: { position: 'bottom', fontFamily: 'inherit' },
    };

    if (props.type === 'donut') {
        return {
            ...base,
            chart: { ...base.chart, type: 'donut' },
            labels: props.labels ?? [],
            stroke: { width: 0 },
            plotOptions: { pie: { donut: { size: '68%' } } },
        };
    }

    return {
        ...base,
        chart: { ...base.chart, type: 'bar' },
        colors: [palette[0]],
        legend: { show: false },
        plotOptions: {
            bar: { borderRadius: 6, columnWidth: '45%', distributed: false },
        },
        xaxis: { categories: props.categories ?? [] },
        grid: { borderColor: gridColor, strokeDashArray: 4 },
    };
});
</script>

<template>
    <div class="rounded-2xl border border-border bg-card p-6 shadow-card">
        <h3
            class="mb-5 flex items-center gap-2 text-base font-semibold text-foreground"
        >
            <component :is="icon" v-if="icon" class="size-4.5 text-sky-500" />
            {{ title }}
        </h3>

        <VueApexCharts
            v-if="hasData"
            :height="height ?? 300"
            :options="options"
            :series="series"
            :type="type"
        />
        <div
            v-else
            class="flex h-[300px] items-center justify-center text-sm text-muted-foreground"
        >
            No data yet.
        </div>
    </div>
</template>
