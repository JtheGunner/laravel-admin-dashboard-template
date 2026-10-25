<script lang="ts" setup>
import { Info, TriangleAlert } from '@lucide/vue';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import AppTooltip from '@/components/AppTooltip.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { Spinner } from '@/components/ui/spinner';
import UnderlineTabs from '@/components/UnderlineTabs.vue';
import { useConfirm } from '@/composables/useConfirm';
import { BADGE_COLORS } from '@/lib/badgeColors';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Components', href: '/examples/components' }],
    },
});

const confirm = useConfirm();
const activeTab = ref('overview');
const tabs = [
    { id: 'overview', label: 'Overview' },
    { id: 'activity', label: 'Activity', count: 4 },
    { id: 'settings', label: 'Settings' },
];

async function askForConfirmation(): Promise<void> {
    const confirmed = await confirm({
        title: 'Delete this item?',
        message: 'This action cannot be undone.',
        variant: 'warning',
        confirmLabel: 'Yes, delete',
    });

    if (confirmed) {
        toast.success('Item deleted.');
    }
}
</script>

<template>
    <div class="grid gap-6 px-4 pb-4 md:grid-cols-2 md:px-6 md:pb-6">
        <Card>
            <CardHeader>
                <CardTitle>Buttons</CardTitle>
                <CardDescription>Variants and sizes.</CardDescription>
            </CardHeader>
            <CardContent class="flex flex-wrap items-center gap-3">
                <Button>Default</Button>
                <Button variant="secondary">Secondary</Button>
                <Button variant="outline">Outline</Button>
                <Button variant="ghost">Ghost</Button>
                <Button variant="link">Link</Button>
                <Button variant="destructive">Destructive</Button>
                <Button size="sm">Small</Button>
                <Button size="lg">Large</Button>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>Badges</CardTitle>
                <CardDescription>
                    Component variants and the colour palette used by tables.
                </CardDescription>
            </CardHeader>
            <CardContent class="flex flex-wrap items-center gap-2">
                <Badge>Default</Badge>
                <Badge variant="secondary">Secondary</Badge>
                <Badge variant="outline">Outline</Badge>
                <Badge variant="destructive">Destructive</Badge>
                <span
                    v-for="color in BADGE_COLORS"
                    :key="color.value"
                    :class="color.badge"
                    class="rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset"
                >
                    {{ color.label }}
                </span>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>Alerts</CardTitle>
            </CardHeader>
            <CardContent class="grid gap-3">
                <Alert>
                    <Info />
                    <AlertTitle>Heads up</AlertTitle>
                    <AlertDescription>
                        This is an informational alert.
                    </AlertDescription>
                </Alert>
                <Alert variant="destructive">
                    <TriangleAlert />
                    <AlertTitle>Something went wrong</AlertTitle>
                    <AlertDescription>
                        This is a destructive alert.
                    </AlertDescription>
                </Alert>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>Feedback</CardTitle>
                <CardDescription>
                    Toasts, confirmation dialog, tooltip and loading states.
                </CardDescription>
            </CardHeader>
            <CardContent class="flex flex-wrap items-center gap-3">
                <Button variant="outline" @click="toast.success('Saved.')">
                    Success toast
                </Button>
                <Button variant="outline" @click="toast.error('Failed.')">
                    Error toast
                </Button>
                <Button variant="outline" @click="askForConfirmation">
                    Confirm dialog
                </Button>
                <AppTooltip text="I am a tooltip">
                    <Button variant="outline">Hover me</Button>
                </AppTooltip>
                <Spinner />
                <Skeleton class="h-6 w-24" />
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>Tabs</CardTitle>
            </CardHeader>
            <CardContent>
                <UnderlineTabs v-model="activeTab" :tabs="tabs" show-count />
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>Avatars</CardTitle>
            </CardHeader>
            <CardContent class="flex items-center gap-3">
                <Avatar><AvatarFallback>AB</AvatarFallback></Avatar>
                <Avatar><AvatarFallback>CD</AvatarFallback></Avatar>
                <Avatar><AvatarFallback>EF</AvatarFallback></Avatar>
            </CardContent>
        </Card>
    </div>
</template>
