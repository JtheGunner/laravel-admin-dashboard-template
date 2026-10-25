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
    categoryOptions: FieldOption[];
    statusOptions: FieldOption[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Products', href: '/products' }],
    },
});

const columns: ColumnConfig[] = [
    {
        key: 'name',
        label: 'Product',
        type: 'image-text',
        imageKey: 'image_url',
        fallbackIcon: 'package',
        subtitleKey: 'sku',
        sortable: true,
        width: 'grow',
    },
    { key: 'category_name', label: 'Category', sortable: true },
    {
        key: 'price',
        label: 'Price',
        sortable: true,
        align: 'right',
        unit: 'CHF',
    },
    { key: 'stock', label: 'Stock', sortable: true, align: 'right' },
    {
        key: 'status_label',
        label: 'Status',
        type: 'badge',
        colorKey: 'status_color',
        sortable: true,
    },
];

const fields = computed<FieldConfig[]>(() => [
    { name: 'name', label: 'Name', required: true, colSpan: 2 },
    { name: 'sku', label: 'SKU', required: true },
    {
        name: 'category_id',
        label: 'Category',
        type: 'select',
        options: props.categoryOptions,
    },
    { name: 'price', label: 'Price (CHF)', type: 'number', required: true },
    { name: 'stock', label: 'Stock', type: 'number', required: true },
    {
        name: 'status',
        label: 'Status',
        type: 'select',
        required: true,
        options: props.statusOptions,
    },
    {
        name: 'rating',
        label: 'Rating',
        type: 'range',
        min: 0,
        max: 5,
        step: 1,
    },
    { name: 'is_featured', label: 'Featured product', type: 'checkbox' },
    { name: 'image_path', label: 'Image', type: 'image', colSpan: 2 },
    {
        name: 'description',
        label: 'Description',
        type: 'richtext',
        colSpan: 2,
    },
]);
</script>

<template>
    <ResourceIndex
        :columns="columns"
        :fields="fields"
        :filters="filters"
        :rows="items"
        endpoint="/products"
        singular="Product"
        title=""
    />
</template>
