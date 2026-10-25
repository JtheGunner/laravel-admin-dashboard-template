<script lang="ts" setup>
import { computed } from 'vue';
import ResourceIndex from '@/components/resource/ResourceIndex.vue';
import type {
    ColumnConfig,
    FieldConfig,
    FieldOption,
    Paginator,
    ResourceRow,
} from '@/types/resource';

const props = defineProps<{
    items: Paginator<ResourceRow>;
    filters: { search: string };
    parentOptions: FieldOption[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Categories', href: '/categories' }],
    },
});

const columns: ColumnConfig[] = [
    { key: 'name', label: 'Name', width: 'grow' },
    { key: 'icon', label: 'Icon', type: 'icon' },
    { key: 'color', label: 'Color' },
];

const fields = computed<FieldConfig[]>(() => [
    { name: 'name', label: 'Name', required: true, colSpan: 2 },
    {
        name: 'parent_id',
        label: 'Parent category',
        type: 'select',
        options: props.parentOptions,
    },
    { name: 'icon', label: 'Icon', type: 'icon' },
    { name: 'color', label: 'Color', type: 'color' },
]);
</script>

<template>
    <ResourceIndex
        :columns="columns"
        :fields="fields"
        :filters="filters"
        :rows="items"
        endpoint="/categories"
        singular="Category"
        sortable
        title=""
        tree
    />
</template>
