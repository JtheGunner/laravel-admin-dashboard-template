<script lang="ts" setup>
import { Link } from '@inertiajs/vue3';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { edit as editAppearance } from '@/routes/appearance';
import { edit as editProfile } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';
import type { NavItem } from '@/types';

const tabNavItems: NavItem[] = [
    {
        title: 'Account',
        href: editProfile(),
    },
    {
        title: 'Appearance',
        href: editAppearance(),
    },
    {
        title: 'Security',
        href: editSecurity(),
    },
];

const { isCurrentOrParentUrl } = useCurrentUrl();
</script>

<template>
    <div class="px-4 pb-4 md:px-6 md:pb-6">
        <div
            class="overflow-hidden rounded-2xl border border-border bg-card text-card-foreground shadow-card"
        >
            <div class="p-6">
                <!-- Tab Navigation (Underline style) -->
                <div class="border-b border-border">
                    <nav
                        aria-label="Settings"
                        class="-mb-px flex items-center space-x-2 overflow-x-auto p-1 [&::-webkit-scrollbar]:h-1.5 [&::-webkit-scrollbar-thumb]:rounded-full [&::-webkit-scrollbar-thumb]:bg-border"
                    >
                        <Link
                            v-for="item in tabNavItems"
                            :key="item.title"
                            :class="
                                isCurrentOrParentUrl(item.href)
                                    ? 'border-primary text-primary'
                                    : 'border-transparent text-muted-foreground hover:text-foreground'
                            "
                            :href="item.href"
                            class="inline-flex items-center gap-2 border-b-2 px-2.5 py-2 pb-2 text-sm font-medium whitespace-nowrap transition-colors duration-200 ease-in-out"
                        >
                            {{ item.title }}
                        </Link>
                    </nav>
                </div>

                <!-- Tab Content -->
                <div class="space-y-10 pt-6">
                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>
