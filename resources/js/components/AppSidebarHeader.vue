<script lang="ts" setup>
import { usePage } from '@inertiajs/vue3';
import { Bell, BookOpen, ChevronDown, Search } from '@lucide/vue';
import { computed } from 'vue';
import AppearanceToggle from '@/components/AppearanceToggle.vue';
import AppTooltip from '@/components/AppTooltip.vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { SidebarTrigger } from '@/components/ui/sidebar';
import UserInfo from '@/components/UserInfo.vue';
import UserMenuContent from '@/components/UserMenuContent.vue';

const page = usePage();
const user = computed(() => page.props.auth.user);

// Platform-specific shortcut label (⌘ on macOS, otherwise Ctrl).
const shortcut = computed(() => {
    if (typeof navigator === 'undefined') {
        return '⌘K';
    }

    return /Mac|iPhone|iPad/.test(navigator.platform) ? '⌘K' : 'Ctrl K';
});

function openPalette() {
    window.dispatchEvent(new CustomEvent('open-command-palette'));
}
</script>

<template>
    <header
        class="sticky top-0 z-20 flex h-[70px] shrink-0 items-center gap-3 border-b border-border bg-card px-4 transition-[width,height] ease-linear md:px-6"
    >
        <SidebarTrigger
            class="size-10 rounded-lg border border-border text-muted-foreground hover:bg-muted"
        />

        <button
            class="hidden h-11 w-full max-w-sm items-center gap-2.5 rounded-lg border border-border bg-muted/40 px-4 text-sm text-muted-foreground transition-colors hover:bg-muted sm:flex"
            type="button"
            @click="openPalette"
        >
            <Search class="size-4 shrink-0" />
            <span
                class="w-px flex-1 overflow-hidden text-left text-ellipsis whitespace-nowrap"
                >Search or type a command…</span
            >
            <kbd
                class="rounded border border-border bg-background px-1.5 py-0.5 font-sans text-xs"
            >
                {{ shortcut }}
            </kbd>
        </button>

        <div class="ml-auto flex items-center gap-2">
            <AppearanceToggle />

            <AppTooltip side="bottom" text="Notifications">
                <button
                    aria-label="Notifications"
                    class="relative inline-flex size-10 items-center justify-center rounded-full text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                    type="button"
                >
                    <Bell class="size-5" />
                    <span
                        class="absolute top-2 right-2.5 size-2 rounded-full bg-orange-500 ring-2 ring-card"
                    />
                </button>
            </AppTooltip>

            <AppTooltip side="bottom" text="API documentation">
                <a
                    aria-label="API documentation"
                    class="inline-flex size-10 items-center justify-center rounded-full text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                    href="/docs/api"
                    rel="noopener noreferrer"
                    target="_blank"
                >
                    <BookOpen class="size-5" />
                </a>
            </AppTooltip>

            <div class="mx-1 hidden h-6 w-px bg-border sm:block" />

            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <button
                        class="flex items-center gap-2 rounded-full py-1 pr-2 pl-1 transition-colors hover:bg-muted"
                        type="button"
                    >
                        <UserInfo :user="user" />
                        <ChevronDown
                            class="hidden size-4 text-muted-foreground sm:block"
                        />
                    </button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" class="min-w-56 rounded-lg">
                    <UserMenuContent :user="user" />
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
    </header>
</template>
