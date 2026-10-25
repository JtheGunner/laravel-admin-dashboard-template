<script lang="ts" setup>
import { Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, ListOrdered, ReceiptText } from '@lucide/vue';
import { computed, markRaw, ref } from 'vue';
import OrderController from '@/actions/App/Http/Controllers/OrderController';
import ResourceDetailPage from '@/components/resource/ResourceDetailPage.vue';
import { Button } from '@/components/ui/button';
import type { FieldConfig, FieldOption, ResourceRow } from '@/types/resource';

interface OrderLine {
    id: number;
    product_name: string | null;
    quantity: number;
    unit_price: string;
    line_total: string;
}

const props = defineProps<{
    item: ResourceRow & { items: OrderLine[] };
    customerOptions: FieldOption[];
    statusOptions: FieldOption[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Orders', href: '/orders' },
            { title: 'Order details' },
        ],
    },
});

const detail = ref<InstanceType<typeof ResourceDetailPage> | null>(null);

const form = useForm({
    customer_id: props.item.customer_id as number,
    number: props.item.number as string,
    status: props.item.status as string,
    total: props.item.total as string,
    notes: (props.item.notes as string | null) ?? '',
});

const fields = computed<FieldConfig[]>(() => [
    {
        name: 'section_order',
        label: 'Order',
        type: 'section',
        sectionIcon: markRaw(ReceiptText),
    },
    { name: 'number', label: 'Order number', disabled: true },
    {
        name: 'customer_id',
        label: 'Customer',
        type: 'select',
        options: props.customerOptions,
        disabled: true,
    },
    {
        name: 'status',
        label: 'Status',
        type: 'select',
        options: props.statusOptions,
        colorField: 'status_color',
    },
    { name: 'total', label: 'Total (CHF)', disabled: true },
    { name: 'placed_at', label: 'Placed at', type: 'datetime', disabled: true },
    { name: 'notes', label: 'Notes', type: 'textarea', colSpan: 2 },
    {
        name: 'section_items',
        label: 'Items',
        type: 'section',
        colSpan: 2,
        sectionIcon: markRaw(ListOrdered),
        sectionEditable: false,
    },
]);

function save(): void {
    form.put(OrderController.update(props.item.id).url, {
        preserveScroll: true,
        onSuccess: () => detail.value?.clearEditing(),
    });
}
</script>

<template>
    <div class="flex flex-col gap-4">
        <div class="px-4 pt-1 md:px-6">
            <Button as-child size="sm" variant="ghost">
                <Link href="/orders"
                    ><ArrowLeft class="size-4" /> Back to orders</Link
                >
            </Button>
        </div>

        <ResourceDetailPage
            ref="detail"
            :fields="fields"
            :form="form"
            :row="item"
            :saving="form.processing"
            @cancel="form.reset()"
            @save="save"
        >
            <template #section_items-customBody>
                <table class="w-full text-sm">
                    <thead class="text-left text-xs text-muted-foreground">
                        <tr>
                            <th class="py-2 font-medium">Product</th>
                            <th class="py-2 text-right font-medium">Qty</th>
                            <th class="py-2 text-right font-medium">
                                Unit price
                            </th>
                            <th class="py-2 text-right font-medium">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr v-for="line in item.items" :key="line.id">
                            <td class="py-2">{{ line.product_name ?? '—' }}</td>
                            <td class="py-2 text-right">{{ line.quantity }}</td>
                            <td class="py-2 text-right">
                                {{ line.unit_price }}
                            </td>
                            <td class="py-2 text-right font-medium">
                                {{ line.line_total }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </template>
        </ResourceDetailPage>
    </div>
</template>
