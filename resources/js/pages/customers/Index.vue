<script lang="ts" setup>
import ResourceIndex from '@/components/resource/ResourceIndex.vue';
import type {
    ColumnConfig,
    FieldConfig,
    Paginator,
    ResourceRow,
} from '@/types/resource';

defineProps<{
    items: Paginator<ResourceRow>;
    filters: { search: string };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Customers', href: '/customers' }],
    },
});

const columns: ColumnConfig[] = [
    { key: 'name', label: 'Name', sortable: true, width: 'grow' },
    { key: 'email', label: 'Email', sortable: true },
    { key: 'company', label: 'Company', sortable: true },
    { key: 'city', label: 'City', sortable: true, showFrom: 'lg' },
    { key: 'country', label: 'Country', sortable: true, showFrom: 'xl' },
    { key: 'phone', label: 'Phone', showFrom: 'xl' },
    { key: 'created_at', label: 'Created', type: 'datetime', sortable: true },
];

const fields: FieldConfig[] = [
    { name: 'name', label: 'Name', required: true, colSpan: 2 },
    { name: 'email', label: 'Email', required: true },
    { name: 'phone', label: 'Phone' },
    { name: 'company', label: 'Company', colSpan: 2 },
    { name: 'city', label: 'City' },
    { name: 'country', label: 'Country (ISO code)', placeholder: 'CH' },
    { name: 'notes', label: 'Notes', type: 'textarea', colSpan: 2 },
];
</script>

<template>
    <ResourceIndex
        :columns="columns"
        :fields="fields"
        :filters="filters"
        :rows="items"
        endpoint="/customers"
        singular="Customer"
        title=""
    />
</template>
