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
    roleOptions: FieldOption[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Users', href: '/users' }],
    },
});

const columns: ColumnConfig[] = [
    { key: 'name', label: 'Name', sortable: true, width: 'grow' },
    { key: 'email', label: 'Email', sortable: true },
    { key: 'role', label: 'Role', sortable: true },
    { key: 'created_at', label: 'Created', type: 'datetime', sortable: true },
];

const fields = computed<FieldConfig[]>(() => [
    { name: 'name', label: 'Name', required: true, colSpan: 2 },
    { name: 'email', label: 'Email', required: true, colSpan: 2 },
    {
        name: 'role',
        label: 'Role',
        type: 'select',
        required: true,
        options: props.roleOptions,
        colSpan: 2,
    },
    {
        name: 'password',
        label: 'Password (leave empty to keep it unchanged)',
        type: 'password',
        placeholder: '••••••••',
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
        endpoint="/users"
        singular="User"
        title=""
    />
</template>
