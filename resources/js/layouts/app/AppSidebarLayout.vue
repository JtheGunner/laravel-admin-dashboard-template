<script lang="ts" setup>
import { usePage } from '@inertiajs/vue3';
import { watch } from 'vue';
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import CommandPalette from '@/components/CommandPalette.vue';
import PageTitle from '@/components/PageTitle.vue';
import { Toaster } from '@/components/ui/sonner';
import { applyServerAppearance } from '@/composables/useAppearance';
import type { BreadcrumbItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
    pageTitle?: string;
};

const props = withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

// Keep the theme in sync with auth.user.dark_mode after Inertia navigations
// or after the user saves their profile (no hard refresh needed).
const page = usePage();
watch(
    () => page.props.auth?.user?.dark_mode,
    (dbMode) => {
        if (dbMode) {
            applyServerAppearance(dbMode);
        }
    },
    // immediate: apply on mount too. After login the layout mounts via an
    // Inertia visit (no full reload), so app.ts's initializeTheme() does not
    // re-run — without this the stored dark_mode would never be applied.
    { immediate: true },
);
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar />
        <!-- h-svh + overflow-y-auto makes the main area the scroll container
             (instead of the window), so sticky headers inside it — the app
             header and the resource-table <thead> — actually stick. -->
        <AppContent
            class="h-svh overflow-x-hidden overflow-y-auto"
            variant="sidebar"
        >
            <AppSidebarHeader />
            <PageTitle
                :breadcrumbs="props.breadcrumbs"
                :page-title="props.pageTitle"
            />
            <slot />
        </AppContent>
        <CommandPalette />
        <Toaster />
    </AppShell>
</template>
