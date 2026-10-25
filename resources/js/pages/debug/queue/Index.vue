<script lang="ts" setup>
import { router } from '@inertiajs/vue3';
import { AlertTriangle, CheckCircle, Layers, RefreshCw } from '@lucide/vue';
import { ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { formatDateTime } from '@/lib/resourceCell';

interface PendingJob {
    id: number;
    queue: string;
    display_name: string;
    attempts: number;
    reserved_at: number | null;
    available_at: number;
    created_at: number;
}

interface FailedJob {
    id: number;
    uuid: string;
    queue: string;
    display_name: string;
    exception_short: string;
    failed_at: string;
}

defineProps<{
    pendingJobs: PendingJob[];
    failedJobs: FailedJob[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Debug', href: '/debug/queue' },
            { title: 'Queue', href: '/debug/queue' },
        ],
    },
});

const refreshing = ref(false);

function refresh() {
    refreshing.value = true;
    router.reload({ onFinish: () => (refreshing.value = false) });
}

function formatTimestamp(ts: number): string {
    return formatDateTime(new Date(ts * 1000).toISOString());
}

function classBasename(name: string): string {
    return name.split('\\').at(-1) ?? name;
}
</script>

<template>
    <div class="flex flex-col gap-6 px-4 pb-6 md:px-6">
        <p class="text-sm text-muted-foreground">
            Pending and failed jobs in the Laravel queue (driver:
            <code>database</code>).
        </p>

        <!-- Stats -->
        <div class="grid gap-4 sm:grid-cols-2">
            <Card>
                <CardContent class="flex items-center gap-4 pt-6">
                    <div
                        class="flex size-10 shrink-0 items-center justify-center rounded-full bg-primary/10"
                    >
                        <Layers class="size-5 text-primary" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold">
                            {{ pendingJobs.length }}
                        </p>
                        <p class="text-sm text-muted-foreground">Pending</p>
                    </div>
                </CardContent>
            </Card>
            <Card>
                <CardContent class="flex items-center gap-4 pt-6">
                    <div
                        :class="[
                            'flex size-10 shrink-0 items-center justify-center rounded-full',
                            failedJobs.length > 0
                                ? 'bg-destructive/10'
                                : 'bg-emerald-500/10',
                        ]"
                    >
                        <AlertTriangle
                            v-if="failedJobs.length > 0"
                            class="size-5 text-destructive"
                        />
                        <CheckCircle
                            v-else
                            class="size-5 text-emerald-600 dark:text-emerald-400"
                        />
                    </div>
                    <div>
                        <p class="text-2xl font-bold">
                            {{ failedJobs.length }}
                        </p>
                        <p class="text-sm text-muted-foreground">Failed</p>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Refresh button -->
        <div class="flex justify-end">
            <Button
                :disabled="refreshing"
                size="sm"
                variant="outline"
                @click="refresh"
            >
                <RefreshCw :class="['size-4', refreshing && 'animate-spin']" />
                Refresh
            </Button>
        </div>

        <!-- Pending jobs -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <Layers class="size-5 text-foreground" />
                    Pending jobs
                    <Badge class="ml-1" variant="secondary">
                        {{ pendingJobs.length }}
                    </Badge>
                </CardTitle>
                <CardDescription>
                    Jobs in the <code>jobs</code> table that have not been
                    processed yet.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <p
                    v-if="pendingJobs.length === 0"
                    class="py-6 text-center text-sm text-muted-foreground"
                >
                    No pending jobs.
                </p>
                <div v-else class="grid gap-2">
                    <div
                        v-for="job in pendingJobs"
                        :key="job.id"
                        class="flex items-start justify-between gap-3 rounded-lg border p-3"
                    >
                        <div class="min-w-0">
                            <p class="truncate font-mono text-sm font-semibold">
                                {{ classBasename(job.display_name) }}
                            </p>
                            <p
                                class="mt-0.5 truncate font-mono text-xs text-muted-foreground"
                            >
                                {{ job.display_name }}
                            </p>
                            <p class="mt-1 text-xs text-muted-foreground">
                                Queue: {{ job.queue }} · Attempts:
                                {{ job.attempts }} · Created:
                                {{ formatTimestamp(job.created_at) }}
                            </p>
                        </div>
                        <Badge
                            :variant="job.reserved_at ? 'secondary' : 'outline'"
                            class="shrink-0"
                        >
                            {{ job.reserved_at ? 'Reserved' : 'Waiting' }}
                        </Badge>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Failed jobs -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <AlertTriangle class="size-5 text-foreground" />
                    Failed jobs
                    <Badge
                        :variant="
                            failedJobs.length > 0 ? 'destructive' : 'secondary'
                        "
                        class="ml-1"
                    >
                        {{ failedJobs.length }}
                    </Badge>
                </CardTitle>
                <CardDescription>
                    Jobs from the <code>failed_jobs</code> table (latest 100 at
                    most).
                </CardDescription>
            </CardHeader>
            <CardContent>
                <p
                    v-if="failedJobs.length === 0"
                    class="py-6 text-center text-sm text-muted-foreground"
                >
                    No failed jobs.
                </p>
                <div v-else class="grid gap-2">
                    <div
                        v-for="job in failedJobs"
                        :key="job.id"
                        class="rounded-lg border border-destructive/20 bg-destructive/5 p-3"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <p class="truncate font-mono text-sm font-semibold">
                                {{ classBasename(job.display_name) }}
                            </p>
                            <Badge variant="outline" class="shrink-0 text-xs">
                                {{ job.queue }}
                            </Badge>
                        </div>
                        <p class="mt-1 font-mono text-xs text-muted-foreground">
                            {{ job.display_name }}
                        </p>
                        <p
                            class="mt-2 rounded bg-destructive/10 px-2 py-1 font-mono text-xs text-destructive"
                        >
                            {{ job.exception_short }}
                        </p>
                        <p class="mt-1.5 text-xs text-muted-foreground">
                            Failed: {{ job.failed_at }} · UUID:
                            {{ job.uuid }}
                        </p>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
