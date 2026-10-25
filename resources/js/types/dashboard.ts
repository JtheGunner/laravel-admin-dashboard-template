/**
 * Shape of the dashboard payload provided by App\Services\Dashboard\DashboardMetrics.
 * Kept in one place so the page and its widgets stay in sync with the backend.
 */

export interface DashboardKpis {
    customers: number;
    products: number;
    orders: number;
    revenue: string;
}

export interface MonthDatum {
    label: string;
    value: number;
}

export interface StatusDatum {
    title: string;
    color: string;
    count: number;
}

export interface LowStockItem {
    id: number;
    name: string;
    sku: string;
    stock: number;
}

export interface ActivityItem {
    label: string;
    icon: string;
    color: string;
    subject: string;
    note: string | null;
    occurredAt: string;
    occurredAtHuman: string;
}
