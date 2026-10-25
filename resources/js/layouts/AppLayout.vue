<script lang="ts" setup>
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItem } from '@/types';

const { breadcrumbs = [], pageTitle } = defineProps<{
    breadcrumbs?: BreadcrumbItem[];
    pageTitle?: string;
}>();
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs" :page-title="pageTitle">
        <slot />
    </AppLayout>
</template>
