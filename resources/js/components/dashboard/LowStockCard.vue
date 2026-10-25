<script lang="ts" setup>
import { PackageX } from '@lucide/vue';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import type { LowStockItem } from '@/types/dashboard';

defineProps<{ items: LowStockItem[] }>();
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle class="flex items-center gap-2">
                <PackageX class="size-4.5 text-amber-500" />
                Low stock
            </CardTitle>
            <CardDescription class="mt-1">
                Products that are about to run out.
            </CardDescription>
        </CardHeader>
        <CardContent>
            <p
                v-if="items.length === 0"
                class="py-6 text-center text-sm text-muted-foreground"
            >
                All products are well stocked.
            </p>
            <ul v-else class="divide-y divide-border">
                <li
                    v-for="item in items"
                    :key="item.id"
                    class="flex items-center justify-between gap-3 py-2.5"
                >
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium text-foreground">
                            {{ item.name }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            {{ item.sku }}
                        </p>
                    </div>
                    <span
                        :class="
                            item.stock === 0
                                ? 'bg-red-500/10 text-red-600 dark:text-red-400'
                                : 'bg-amber-500/10 text-amber-600 dark:text-amber-400'
                        "
                        class="rounded-full px-2.5 py-0.5 text-xs font-medium"
                    >
                        {{ item.stock }} left
                    </span>
                </li>
            </ul>
        </CardContent>
    </Card>
</template>
