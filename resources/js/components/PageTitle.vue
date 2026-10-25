<script lang="ts" setup>
import { Link } from '@inertiajs/vue3';
import {
    ChevronRight,
    FileText,
    FolderKanban,
    LayoutGrid,
    LayoutList,
    Link2,
    LockKeyhole,
    Puzzle,
    Settings2,
    Star,
} from '@lucide/vue';
import type { Component } from 'vue';
import { computed } from 'vue';
import AppIcon from '@/components/AppIcon.vue';
import { iconForHref } from '@/lib/navigation';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

// Icons for breadcrumbs that are NOT sidebar menu destinations (settings pages
// and sub-page crumbs). Menu-bound views inherit their icon from the sidebar
// via `iconForHref`, so they must not be duplicated here.
const ICON_MAP: Record<string, Component> = {
    'file-text': FileText,
    'folder-kanban': FolderKanban,
    'layout-grid': LayoutGrid,
    'layout-list': LayoutList,
    'link-2': Link2,
    'lock-keyhole': LockKeyhole,
    puzzle: Puzzle,
    'settings-2': Settings2,
    star: Star,
};

const props = withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
        pageTitle?: string;
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const title = computed(
    () =>
        props.pageTitle ??
        props.breadcrumbs[props.breadcrumbs.length - 1]?.title ??
        'Welcome',
);

const icon = computed(() => {
    const last = props.breadcrumbs[props.breadcrumbs.length - 1];

    if (!last) {
        return null;
    }

    // An explicit `icon` on the breadcrumb overrides the menu default.
    if (last.icon) {
        return ICON_MAP[last.icon] ?? null;
    }

    // Otherwise inherit the sidebar menu icon for this destination, so a view
    // with a menu entry does not have to re-declare its icon.
    return iconForHref(last.href) ?? null;
});
</script>

<template>
    <div
        class="flex flex-col justify-center gap-2 px-4 pt-4 pb-5 sm:flex-row sm:items-center sm:justify-between md:px-6 md:pt-6"
    >
        <div class="page-title flex items-center gap-2">
            <AppIcon
                v-if="icon"
                :icon="icon"
                class="ml-1 h-7 w-7 text-foreground"
            />
            <h4 class="text-xl font-semibold text-foreground md:text-2xl">
                {{ title }}
            </h4>
        </div>

        <nav aria-label="Breadcrumb">
            <ol class="flex items-center gap-1.5 text-sm text-muted-foreground">
                <li>
                    <Link
                        :href="dashboard()"
                        class="transition-colors hover:text-primary"
                    >
                        Admin Dashboard
                    </Link>
                </li>
                <li
                    v-for="(crumb, i) in breadcrumbs"
                    :key="i"
                    class="flex items-center gap-1.5"
                >
                    <ChevronRight
                        aria-hidden="true"
                        class="size-3.5 text-muted-foreground/50"
                    />
                    <Link
                        v-if="crumb.href && i < breadcrumbs.length - 1"
                        :href="crumb.href"
                        class="transition-colors hover:text-primary"
                    >
                        {{ crumb.title }}
                    </Link>
                    <span v-else class="text-foreground">{{
                        crumb.title
                    }}</span>
                </li>
            </ol>
        </nav>
    </div>
</template>
