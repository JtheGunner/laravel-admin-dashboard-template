<script lang="ts" setup>
import { Link } from '@inertiajs/vue3';
import { ChevronRight } from '@lucide/vue';
import { ref } from 'vue';
import AppIcon from '@/components/AppIcon.vue';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import {
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import type { NavTreeItem } from '@/types';

const props = withDefaults(
    defineProps<{
        item: NavTreeItem;
        // 0 = top level (SidebarMenuButton), >0 = nested (SidebarMenuSubButton).
        level?: number;
    }>(),
    { level: 0 },
);

const { isCurrentUrl } = useCurrentUrl();

const hasChildren = (item: NavTreeItem): boolean =>
    Array.isArray(item.items) && item.items.length > 0;

const isActiveLeaf = (item: NavTreeItem): boolean =>
    item.href ? isCurrentUrl(item.href) : false;

const containsCurrentUrl = (item: NavTreeItem): boolean =>
    isActiveLeaf(item) || (item.items ?? []).some(containsCurrentUrl);

// Initial state: if the group contains the active route, it is always expanded.
// Otherwise the group's optional `defaultOpen` flag decides; without a value
// top-level groups are open and nested ones closed.
const open = ref(
    containsCurrentUrl(props.item) ||
        (props.item.defaultOpen ?? props.level === 0),
);
</script>

<template>
    <!-- Leaf: direct link -->
    <template v-if="!hasChildren(item)">
        <SidebarMenuSubItem v-if="level > 0">
            <SidebarMenuSubButton :is-active="isActiveLeaf(item)" as-child>
                <a
                    v-if="item.href && item.external"
                    :href="String(item.href)"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    <AppIcon v-if="item.icon" :icon="item.icon" />
                    <span>{{ item.title }}</span>
                </a>
                <Link v-else-if="item.href" :href="item.href">
                    <AppIcon v-if="item.icon" :icon="item.icon" />
                    <span>{{ item.title }}</span>
                    <span
                        v-if="item.count !== undefined"
                        class="ml-auto shrink-0 rounded bg-primary/10 px-1.5 py-px text-[10px] font-medium text-primary tabular-nums group-data-[collapsible=icon]:hidden"
                        >{{ item.count }}</span
                    >
                </Link>
            </SidebarMenuSubButton>
        </SidebarMenuSubItem>
        <SidebarMenuItem v-else>
            <SidebarMenuButton
                :is-active="isActiveLeaf(item)"
                :tooltip="item.title"
                as-child
            >
                <a
                    v-if="item.href && item.external"
                    :href="String(item.href)"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    <AppIcon v-if="item.icon" :icon="item.icon" />
                    <span>{{ item.title }}</span>
                </a>
                <Link v-else-if="item.href" :href="item.href">
                    <AppIcon v-if="item.icon" :icon="item.icon" />
                    <span>{{ item.title }}</span>
                    <span
                        v-if="item.count !== undefined"
                        class="ml-auto shrink-0 rounded bg-muted px-1.5 py-px text-[10px] font-medium text-muted-foreground tabular-nums group-data-[collapsible=icon]:hidden"
                        >{{ item.count }}</span
                    >
                </Link>
            </SidebarMenuButton>
        </SidebarMenuItem>
    </template>

    <!-- Branch: collapsible group -->
    <Collapsible v-else v-model:open="open" as-child class="group/collapsible">
        <SidebarMenuItem v-if="level === 0">
            <CollapsibleTrigger as-child>
                <SidebarMenuButton
                    :is-active="containsCurrentUrl(item)"
                    :tooltip="item.title"
                    class="font-bold"
                >
                    <AppIcon v-if="item.icon" :icon="item.icon" />
                    <span>{{ item.title }}</span>
                    <ChevronRight
                        :class="{ 'rotate-90': open }"
                        class="ml-auto transition-transform duration-200"
                    />
                </SidebarMenuButton>
            </CollapsibleTrigger>
            <CollapsibleContent>
                <SidebarMenuSub>
                    <NavCollapsible
                        v-for="child in item.items"
                        :key="child.title"
                        :item="child"
                        :level="level + 1"
                    />
                </SidebarMenuSub>
            </CollapsibleContent>
        </SidebarMenuItem>

        <SidebarMenuSubItem v-else>
            <CollapsibleTrigger as-child>
                <SidebarMenuSubButton as="button">
                    <AppIcon v-if="item.icon" :icon="item.icon" />
                    <span>{{ item.title }}</span>
                    <ChevronRight
                        :class="{ 'rotate-90': open }"
                        class="ml-auto transition-transform duration-200"
                    />
                </SidebarMenuSubButton>
            </CollapsibleTrigger>
            <CollapsibleContent>
                <SidebarMenuSub>
                    <NavCollapsible
                        v-for="child in item.items"
                        :key="child.title"
                        :item="child"
                        :level="level + 1"
                    />
                </SidebarMenuSub>
            </CollapsibleContent>
        </SidebarMenuSubItem>
    </Collapsible>
</template>
