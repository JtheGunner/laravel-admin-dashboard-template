<script lang="ts" setup>
import { router } from '@inertiajs/vue3';
import {
    CheckCircle2,
    Clock,
    Play,
    RefreshCw,
    Shield,
    Timer,
    XCircle,
} from '@lucide/vue';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import ScheduleController from '@/actions/App/Http/Controllers/Debug/ScheduleController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { Spinner } from '@/components/ui/spinner';

interface ScheduledTask {
    expression: string;
    command: string;
    description: string | null;
    next_due_date: string;
    next_due_date_human: string;
    timezone: string;
    has_mutex: boolean;
    repeat_seconds: number | null;
    environments: string[];
}

defineProps<{
    tasks: ScheduledTask[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Debug', href: '/debug/schedule' },
            { title: 'Cron jobs', href: '/debug/schedule' },
        ],
    },
});

const refreshing = ref(false);

function refresh() {
    refreshing.value = true;
    router.reload({ onFinish: () => (refreshing.value = false) });
}

function commandLabel(command: string): string {
    return command.replace(/^php artisan /, '');
}

// --- Manual run ---
const runOpen = ref(false);
const runTask = ref<ScheduledTask | null>(null);
const running = ref(false);
const runOutput = ref('');
const runExitCode = ref<number | null>(null);

function openRun(task: ScheduledTask) {
    runTask.value = task;
    runOutput.value = '';
    runExitCode.value = null;
    runOpen.value = true;
}

async function executeRun() {
    const task = runTask.value;

    if (!task) {
        return;
    }

    running.value = true;
    runOutput.value = '';
    runExitCode.value = null;

    try {
        const response = await fetch(ScheduleController.run().url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN':
                    (
                        document.querySelector(
                            'meta[name="csrf-token"]',
                        ) as HTMLMetaElement | null
                    )?.content ?? '',
                Accept: 'application/json',
            },
            body: JSON.stringify({ command: task.command }),
        });

        const data = (await response.json()) as {
            output?: string;
            exit_code?: number;
            message?: string;
        };

        if (!response.ok) {
            toast.error(data.message ?? 'The command could not be run.');

            return;
        }

        runOutput.value = data.output ?? '';
        runExitCode.value = data.exit_code ?? 0;

        if (data.exit_code === 0) {
            toast.success(`"${commandLabel(task.command)}" ran successfully.`);
        } else {
            toast.error(
                `"${commandLabel(task.command)}" exited with code ${data.exit_code}.`,
            );
        }
    } catch {
        toast.error('Network error while running the command.');
    } finally {
        running.value = false;
    }
}
</script>

<template>
    <div class="flex flex-col gap-6 px-4 pb-6 md:px-6">
        <p class="text-sm text-muted-foreground">
            All registered Laravel scheduled tasks with their schedule and next
            due time.
        </p>

        <Card>
            <CardHeader
                class="flex flex-row items-start justify-between gap-2 space-y-0"
            >
                <div>
                    <CardTitle class="flex items-center gap-2">
                        <Clock class="size-5 text-foreground" />
                        Scheduled tasks
                        <Badge class="ml-1" variant="secondary">
                            {{ tasks.length }}
                        </Badge>
                    </CardTitle>
                    <CardDescription>
                        Source: <code>routes/console.php</code> via
                        <code>schedule:list</code>
                    </CardDescription>
                </div>
                <Button
                    :disabled="refreshing"
                    size="sm"
                    variant="outline"
                    @click="refresh"
                >
                    <RefreshCw
                        :class="['size-4', refreshing && 'animate-spin']"
                    />
                    Refresh
                </Button>
            </CardHeader>
            <CardContent>
                <p
                    v-if="tasks.length === 0"
                    class="py-8 text-center text-sm text-muted-foreground"
                >
                    No scheduled tasks found.
                </p>
                <div v-else class="grid gap-3">
                    <div
                        v-for="task in tasks"
                        :key="task.command"
                        class="flex flex-col gap-3 rounded-lg border p-4 sm:flex-row sm:items-start sm:justify-between"
                    >
                        <div class="min-w-0 flex-1">
                            <p class="font-mono text-sm font-semibold">
                                {{ commandLabel(task.command) }}
                            </p>
                            <p
                                v-if="task.description"
                                class="mt-0.5 text-sm text-muted-foreground"
                            >
                                {{ task.description }}
                            </p>
                            <div
                                class="mt-2 flex flex-wrap items-center gap-2 text-xs text-muted-foreground"
                            >
                                <span class="flex items-center gap-1 font-mono">
                                    <Timer class="size-3.5" />
                                    {{ task.expression }}
                                </span>
                                <span>·</span>
                                <span>{{ task.timezone }}</span>
                                <template v-if="task.has_mutex">
                                    <span>·</span>
                                    <span
                                        class="flex items-center gap-1 text-amber-600 dark:text-amber-400"
                                    >
                                        <Shield class="size-3.5" />
                                        Mutex active
                                    </span>
                                </template>
                                <template v-if="task.environments.length > 0">
                                    <span>·</span>
                                    <span>
                                        Environments:
                                        {{ task.environments.join(', ') }}
                                    </span>
                                </template>
                            </div>
                        </div>
                        <div class="flex shrink-0 flex-col items-end gap-2">
                            <div class="text-right">
                                <p class="text-sm font-medium">
                                    {{ task.next_due_date_human }}
                                </p>
                                <p
                                    class="mt-0.5 font-mono text-xs text-muted-foreground"
                                >
                                    {{ task.next_due_date }}
                                </p>
                            </div>
                            <Button
                                size="sm"
                                variant="outline"
                                @click="openRun(task)"
                            >
                                <Play class="size-3.5" />
                                Run
                            </Button>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Run sheet -->
        <Sheet v-model:open="runOpen">
            <SheetContent
                class="flex w-full flex-col gap-0 p-0 sm:max-w-xl"
                side="right"
            >
                <SheetHeader class="border-b px-6 py-5">
                    <SheetTitle class="font-mono">
                        {{ runTask ? commandLabel(runTask.command) : '' }}
                    </SheetTitle>
                    <SheetDescription>
                        Run the command manually and show its output.
                    </SheetDescription>
                </SheetHeader>

                <div class="flex flex-1 flex-col gap-4 overflow-y-auto p-6">
                    <Button
                        :disabled="running"
                        class="self-start"
                        @click="executeRun"
                    >
                        <Spinner v-if="running" class="size-4" />
                        <Play v-else class="size-4" />
                        {{ running ? 'Running…' : 'Run now' }}
                    </Button>

                    <template v-if="runExitCode !== null || runOutput">
                        <div class="flex items-center gap-2">
                            <CheckCircle2
                                v-if="runExitCode === 0"
                                class="size-4 text-emerald-600 dark:text-emerald-400"
                            />
                            <XCircle v-else class="size-4 text-destructive" />
                            <span class="text-sm font-medium">
                                Exit code {{ runExitCode }}
                            </span>
                        </div>

                        <div>
                            <p class="mb-2 text-sm font-medium">Output</p>
                            <pre
                                class="max-h-[60vh] overflow-auto rounded-lg bg-muted p-4 font-mono text-xs leading-relaxed whitespace-pre-wrap"
                            ><code>{{ runOutput || '(no output)' }}</code></pre>
                        </div>
                    </template>

                    <p
                        v-else-if="!running"
                        class="text-sm text-muted-foreground"
                    >
                        Click "Run now" to start the task immediately. The
                        output appears here.
                    </p>
                </div>
            </SheetContent>
        </Sheet>
    </div>
</template>
