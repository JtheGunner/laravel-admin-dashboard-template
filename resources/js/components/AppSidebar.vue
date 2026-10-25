<script lang="ts" setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavCollapsible from '@/components/NavCollapsible.vue';
import NavMain from '@/components/NavMain.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarGroup,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import {
    enrichWithCounts,
    mainNavItems,
    managementNavItems,
} from '@/lib/navigation';
import { dashboard } from '@/routes';

const navCounts = computed(() => usePage().props.navCounts ?? {});
const enrichedNavItems = computed(() =>
    enrichWithCounts(managementNavItems, navCounts.value),
);
</script>

<template>
    <Sidebar collapsible="icon" variant="sidebar">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton as-child size="lg">
                        <Link
                            :href="dashboard()"
                            class="group-data-[collapsible=icon]:w-full!"
                        >
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>
        <SidebarContent>
            <NavMain :items="mainNavItems" />

            <div class="mx-5 border-t border-border/60" />

            <SidebarGroup class="px-4 py-1.5">
                <SidebarMenu class="gap-3.5">
                    <NavCollapsible
                        v-for="item in enrichedNavItems"
                        :key="item.title"
                        :item="item"
                    />
                </SidebarMenu>
            </SidebarGroup>
        </SidebarContent>
    </Sidebar>
    <slot />
</template>
