<script lang="ts" setup>
import { computed } from 'vue';
import OrderController from '@/actions/App/Http/Controllers/OrderController';
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
    customerOptions: FieldOption[];
    statusOptions: FieldOption[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Orders', href: '/orders' }],
    },
});

const columns: ColumnConfig[] = [
    { key: 'number', label: 'Order', sortable: true },
    { key: 'customer_name', label: 'Customer', sortable: true, width: 'grow' },
    { key: 'placed_at', label: 'Placed', type: 'datetime', sortable: true },
    {
        key: 'total',
        label: 'Total',
        sortable: true,
        align: 'right',
        unit: 'CHF',
    },
    {
        key: 'status_label',
        label: 'Status',
        type: 'badge',
        colorKey: 'status_color',
        sortable: true,
    },
];

const expandedColumns: ColumnConfig[] = [
    ...columns,
    { key: 'notes', label: 'Notes', wrap: true },
];

const fields = computed<FieldConfig[]>(() => [
    { name: 'number', label: 'Order number', required: true },
    {
        name: 'customer_id',
        label: 'Customer',
        type: 'select',
        required: true,
        options: props.customerOptions,
    },
    {
        name: 'status',
        label: 'Status',
        type: 'select',
        required: true,
        options: props.statusOptions,
    },
    { name: 'total', label: 'Total (CHF)', type: 'number', required: true },
    { name: 'placed_at', label: 'Placed at', type: 'datetime', colSpan: 2 },
    { name: 'notes', label: 'Notes', type: 'textarea', colSpan: 2 },
]);
</script>

<template>
    <ResourceIndex
        :columns="columns"
        :detail-route="(row) => OrderController.show(row.id).url"
        :expanded-columns="expandedColumns"
        :fields="fields"
        :filters="filters"
        :rows="items"
        endpoint="/orders"
        singular="Order"
        title=""
    />
</template>
