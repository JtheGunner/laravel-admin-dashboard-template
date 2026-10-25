<script lang="ts" setup>
import { router } from '@inertiajs/vue3';
import { CornerDownLeft, Search, XIcon } from '@lucide/vue';
import { useEventListener, useMagicKeys, whenever } from '@vueuse/core';
import { computed, nextTick, ref, watch } from 'vue';
import AppIcon from '@/components/AppIcon.vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    flattenNavItems,
    mainNavItems,
    managementNavItems,
} from '@/lib/navigation';

// Flat list of all navigable destinations, derived from the shared navigation
// tree so it never drifts from the sidebar.
const flat = [...mainNavItems, ...flattenNavItems(managementNavItems)];

const open = ref(false);
const query = ref('');
const activeIndex = ref(0);
const inputRef = ref<HTMLInputElement | null>(null);

const results = computed(() => {
    const q = query.value.trim().toLowerCase();

    if (!q) {
        return flat;
    }

    return flat.filter((item) => item.title.toLowerCase().includes(q));
});

watch(results, () => {
    activeIndex.value = 0;
});

watch(open, async (isOpen) => {
    if (isOpen) {
        query.value = '';
        activeIndex.value = 0;
        await nextTick();
        inputRef.value?.focus();
    }
});

// ⌘K / Ctrl+K opens the palette.
const keys = useMagicKeys({
    passive: false,
    onEventFired(e) {
        if (e.key === 'k' && (e.metaKey || e.ctrlKey) && e.type === 'keydown') {
            e.preventDefault();
        }
    },
});

whenever(keys['Meta+k'], () => (open.value = !open.value));
whenever(keys['Ctrl+k'], () => (open.value = !open.value));

// Also openable via the search button in the header (decoupled via a window event).
// Register only client-side — during SSR render (Node) there is no `window`.
if (typeof window !== 'undefined') {
    useEventListener(window, 'open-command-palette', () => (open.value = true));
}

function move(delta: number) {
    const count = results.value.length;

    if (count === 0) {
        return;
    }

    activeIndex.value = (activeIndex.value + delta + count) % count;
}

function select(index = activeIndex.value) {
    const item = results.value[index];

    if (!item) {
        return;
    }

    const href = item.href;

    // Nested groups (only `items`, no `href`) are not jump targets.
    if (!href) {
        return;
    }

    open.value = false;
    // Trigger the navigation only after closing the dialog (avoid a render race
    // between dialog unmount and Inertia visit).
    nextTick(() => router.get(href));
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent
            class="gap-0 overflow-hidden p-0 sm:max-w-xl"
            @keydown.down.prevent="move(1)"
            @keydown.up.prevent="move(-1)"
            @keydown.enter.prevent="select()"
        >
            <DialogTitle class="sr-only">Schnellsuche</DialogTitle>
            <DialogDescription class="sr-only">
                Jump to a section
            </DialogDescription>
            <div class="flex items-center gap-2 border-b px-4">
                <Search class="size-4 shrink-0 text-muted-foreground" />
                <input
                    ref="inputRef"
                    v-model="query"
                    class="h-12 w-full bg-transparent text-sm outline-none placeholder:text-muted-foreground"
                    placeholder="Search sections…"
                />
                <button
                    v-if="query"
                    class="absolute top-1/2 right-2.5 -translate-y-1/2 text-muted-foreground transition-colors hover:text-foreground"
                    type="button"
                    @click="query = ''"
                >
                    <XIcon class="h-4 w-4" />
                </button>
            </div>

            <div class="max-h-80 overflow-y-auto p-2">
                <button
                    v-for="(item, i) in results"
                    :key="item.title"
                    :class="
                        i === activeIndex
                            ? 'bg-accent text-accent-foreground'
                            : 'hover:bg-muted'
                    "
                    class="flex w-full items-center gap-3 rounded-md px-3 py-2 text-left text-sm transition-colors"
                    type="button"
                    @click="select(i)"
                    @mouseenter="activeIndex = i"
                >
                    <AppIcon
                        v-if="item.icon"
                        :icon="item.icon"
                        class="size-4 text-black"
                    />
                    <span class="flex-1">{{ item.title }}</span>
                    <CornerDownLeft
                        v-if="i === activeIndex"
                        class="size-3.5 text-black"
                    />
                </button>

                <p
                    v-if="results.length === 0"
                    class="px-3 py-6 text-center text-sm text-muted-foreground"
                >
                    No section found.
                </p>
            </div>
        </DialogContent>
    </Dialog>
</template>
