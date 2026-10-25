<script lang="ts" setup>
import type { SimpleIcon } from 'simple-icons';
import type { Component } from 'vue';
import { computed } from 'vue';

/**
 * Renders a navigation icon — either a Lucide component or a
 * simple-icons data object ({ path, hex, ... }, which is NOT a Vue component).
 * Simple icons are drawn with fill="currentColor" so that they — like the
 * Lucide icons — inherit the text color (inactive = black, active = primary color)
 * instead of their fixed brand colour.
 */
const props = defineProps<{
    icon: Component | SimpleIcon;
}>();

const simpleIcon = computed<SimpleIcon | null>(() => {
    const icon = props.icon as Partial<SimpleIcon>;

    return typeof icon === 'object' &&
        icon !== null &&
        typeof icon.path === 'string'
        ? (icon as SimpleIcon)
        : null;
});
</script>

<template>
    <svg
        v-if="simpleIcon"
        aria-hidden="true"
        fill="currentColor"
        viewBox="0 0 24 24"
        xmlns="http://www.w3.org/2000/svg"
    >
        <title v-if="simpleIcon.title">{{ simpleIcon.title }}</title>
        <path :d="simpleIcon.path" />
    </svg>
    <component :is="icon" v-else />
</template>
