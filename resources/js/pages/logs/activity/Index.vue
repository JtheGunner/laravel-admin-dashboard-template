<script lang="ts" setup>
import ResourceIndex from '@/components/resource/ResourceIndex.vue';
import type { ColumnConfig, Paginator, ResourceRow } from '@/types/resource';

defineProps<{
    items: Paginator<ResourceRow>;
    filters: { search: string };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Activity', href: '/logs/activity' }],
    },
});

const columns: ColumnConfig[] = [
    { key: 'created_at', label: 'When', type: 'datetime', sortable: true },
    { key: 'event', label: 'Event', sortable: true },
    { key: 'user_name', label: 'User' },
    { key: 'description', label: 'Description', width: 'grow' },
];
</script>

<template>
    <ResourceIndex
        :columns="columns"
        :fields="[]"
        :filters="filters"
        :rows="items"
        endpoint="/logs/activity"
        readonly
        singular="Activity"
        title=""
    />
</template>
