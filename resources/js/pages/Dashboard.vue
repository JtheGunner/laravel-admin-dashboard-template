<script lang="ts" setup>
import {
    CalendarRange,
    DollarSign,
    History,
    Package,
    PieChart,
    ShoppingCart,
    UsersRound,
} from '@lucide/vue';
import { computed } from 'vue';
import DashboardSection from '@/components/dashboard/DashboardSection.vue';
import LowStockCard from '@/components/dashboard/LowStockCard.vue';
import StatCard from '@/components/dashboard/StatCard.vue';
import StatChart from '@/components/dashboard/StatChart.vue';
import TimelineCard from '@/components/dashboard/TimelineCard.vue';
import { dashboard } from '@/routes';
import type {
    ActivityItem,
    DashboardKpis,
    LowStockItem,
    MonthDatum,
    StatusDatum,
} from '@/types/dashboard';

const props = defineProps<{
    kpis: DashboardKpis;
    ordersByMonth: MonthDatum[];
    ordersByStatus: StatusDatum[];
    lowStock: LowStockItem[];
    activity: ActivityItem[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
    },
});

const kpiCards = computed(() => [
    {
        label: 'Customers',
        value: props.kpis.customers,
        icon: UsersRound,
        href: '/customers',
        tint: 'bg-primary/10 text-primary',
    },
    {
        label: 'Products',
        value: props.kpis.products,
        icon: Package,
        href: '/products',
        tint: 'bg-sky-500/10 text-sky-600 dark:text-sky-400',
    },
    {
        label: 'Orders',
        value: props.kpis.orders,
        icon: ShoppingCart,
        href: '/orders',
        tint: 'bg-amber-500/10 text-amber-600 dark:text-amber-400',
    },
    {
        label: 'Revenue',
        value: props.kpis.revenue,
        icon: DollarSign,
        tint: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400',
    },
]);

const monthSeries = computed(() => [
    { name: 'Orders', data: props.ordersByMonth.map((m) => m.value) },
]);
const monthCategories = computed(() => props.ordersByMonth.map((m) => m.label));
const statusSeries = computed(() => props.ordersByStatus.map((s) => s.count));
const statusLabels = computed(() => props.ordersByStatus.map((s) => s.title));
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-8 px-4 pb-4 md:px-6 md:pb-6">
        <DashboardSection :icon="ShoppingCart" title="Overview">
            <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard
                    v-for="card in kpiCards"
                    :key="card.label"
                    :href="card.href"
                    :icon="card.icon"
                    :label="card.label"
                    :tint="card.tint"
                    :value="card.value"
                />
            </div>

            <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                <StatChart
                    :categories="monthCategories"
                    :icon="CalendarRange"
                    :series="monthSeries"
                    title="Orders per month"
                    type="bar"
                />
                <StatChart
                    :icon="PieChart"
                    :labels="statusLabels"
                    :series="statusSeries"
                    title="Orders by status"
                    type="donut"
                />
                <LowStockCard :items="lowStock" />
                <TimelineCard
                    :activity="activity"
                    :icon="History"
                    description="The latest actions across the application."
                    empty-text="No activity yet."
                    title="Recent activity"
                />
            </div>
        </DashboardSection>
    </div>
</template>
