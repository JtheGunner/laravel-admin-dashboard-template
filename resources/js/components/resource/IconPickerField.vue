<script lang="ts" setup>
import * as LucideIcons from '@lucide/vue';
import { XIcon } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import AppTooltip from '@/components/AppTooltip.vue';
import SearchInput from '@/components/SearchInput.vue';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import type { SiIcon } from '@/composables/useIconLibrary';
import {
    ALL_LUCIDE,
    ALL_SI,
    siIconBySlug,
    siSvg,
} from '@/composables/useIconLibrary';

const props = defineProps<{ modelValue: string | null | undefined }>();
const emit = defineEmits<{ 'update:modelValue': [value: string | null] }>();

// --- State ---
const MAX_VISIBLE = 240;
const open = ref(false);
const search = ref('');
const activeTab = ref<'lucide' | 'si'>('lucide');

// --- Parse stored value (e.g. "lucide:activity" or "si:github") ---
const parsedValue = computed(() => {
    const v = props.modelValue;

    if (!v) {
        return null;
    }

    const colon = v.indexOf(':');

    if (colon === -1) {
        return { prefix: 'lucide', name: v };
    }

    return { prefix: v.slice(0, colon), name: v.slice(colon + 1) };
});

const currentLucideComponent = computed(() => {
    const p = parsedValue.value;

    if (!p || p.prefix !== 'lucide') {
        return null;
    }

    const pascal = p.name.replace(/(^|-)([a-z])/g, (_, __, c: string) =>
        c.toUpperCase(),
    );

    return (LucideIcons as Record<string, unknown>)[pascal] ?? null;
});

const currentSiIcon = computed((): SiIcon | null => {
    const p = parsedValue.value;

    if (!p || p.prefix !== 'si') {
        return null;
    }

    return siIconBySlug(p.name) ?? null;
});

// --- Filtered lists (always search both tabs) ---
const filteredLucide = computed(() => {
    const q = search.value.toLowerCase().trim();

    return q ? ALL_LUCIDE.filter((i) => i.name.includes(q)) : ALL_LUCIDE;
});

const filteredSi = computed(() => {
    const q = search.value.toLowerCase().trim();

    return q
        ? ALL_SI.filter(
              (i) => i.slug.includes(q) || i.title.toLowerCase().includes(q),
          )
        : ALL_SI;
});

// Move an already set icon (if no search term is active) to the start of the
// list so that it is immediately visible and marked when opening — otherwise
// with many icons it ends up outside the first MAX_VISIBLE entries.
const visibleLucide = computed(() => {
    const list = filteredLucide.value;
    const p = parsedValue.value;

    if (!search.value && p?.prefix === 'lucide') {
        const idx = list.findIndex((i) => i.name === p.name);

        if (idx > 0) {
            const reordered = list.slice();
            reordered.unshift(reordered.splice(idx, 1)[0]);

            return reordered.slice(0, MAX_VISIBLE);
        }
    }

    return list.slice(0, MAX_VISIBLE);
});

const visibleSi = computed(() => {
    const list = filteredSi.value;
    const p = parsedValue.value;

    if (!search.value && p?.prefix === 'si') {
        const idx = list.findIndex((i) => i.slug === p.name);

        if (idx > 0) {
            const reordered = list.slice();
            reordered.unshift(reordered.splice(idx, 1)[0]);

            return reordered.slice(0, MAX_VISIBLE);
        }
    }

    return list.slice(0, MAX_VISIBLE);
});

const activeCount = computed(() =>
    activeTab.value === 'lucide'
        ? filteredLucide.value.length
        : filteredSi.value.length,
);
const otherCount = computed(() =>
    activeTab.value === 'lucide'
        ? filteredSi.value.length
        : filteredLucide.value.length,
);
const otherTabLabel = computed(() =>
    activeTab.value === 'lucide' ? 'Simple Icons' : 'Lucide',
);

// --- Actions ---
function selectLucide(name: string): void {
    emit('update:modelValue', `lucide:${name}`);
    open.value = false;
    search.value = '';
}

function selectSi(slug: string): void {
    emit('update:modelValue', `si:${slug}`);
    open.value = false;
    search.value = '';
}

function clear(): void {
    emit('update:modelValue', null);
}

function switchTab(tab: 'lucide' | 'si'): void {
    activeTab.value = tab;
}

// When opening, preselect the tab matching the already set value and
// reset the search so that the current icon is directly visible.
watch(open, (isOpen) => {
    if (!isOpen) {
        return;
    }

    search.value = '';
    activeTab.value = parsedValue.value?.prefix === 'si' ? 'si' : 'lucide';
});
</script>

<template>
    <div class="flex items-center gap-2">
        <Dialog v-model:open="open">
            <DialogTrigger as-child>
                <button
                    class="flex h-9 w-full items-center gap-2 rounded-lg border border-input bg-white px-3 text-sm shadow-xs transition-[color,box-shadow] outline-none hover:bg-muted/40 focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                    type="button"
                >
                    <component
                        :is="currentLucideComponent"
                        v-if="currentLucideComponent"
                        class="h-4 w-4 shrink-0 text-foreground"
                    />
                    <span
                        v-else-if="currentSiIcon"
                        class="inline-flex h-4 w-4 shrink-0 items-center justify-center text-foreground [&>svg]:h-4 [&>svg]:w-4"
                        v-html="siSvg(currentSiIcon.path)"
                    />
                    <span
                        v-if="modelValue"
                        class="flex-1 text-left font-mono text-xs"
                        >{{ modelValue }}</span
                    >
                    <span v-else class="flex-1 text-left text-muted-foreground"
                        >Select icon…</span
                    >
                </button>
            </DialogTrigger>

            <DialogContent
                class="flex max-h-[80vh] w-full max-w-2xl flex-col gap-0 p-0"
            >
                <DialogHeader class="border-b border-border px-5 py-4">
                    <DialogTitle>Select icon</DialogTitle>
                </DialogHeader>

                <!-- Tab bar -->
                <div class="flex border-b border-border">
                    <button
                        :class="
                            activeTab === 'lucide'
                                ? 'border-b-2 border-primary text-primary'
                                : 'text-muted-foreground hover:text-foreground'
                        "
                        class="px-5 py-2.5 text-sm font-medium transition-colors"
                        type="button"
                        @click="switchTab('lucide')"
                    >
                        Lucide
                    </button>
                    <button
                        :class="
                            activeTab === 'si'
                                ? 'border-b-2 border-primary text-primary'
                                : 'text-muted-foreground hover:text-foreground'
                        "
                        class="px-5 py-2.5 text-sm font-medium transition-colors"
                        type="button"
                        @click="switchTab('si')"
                    >
                        Simple Icons
                    </button>
                </div>

                <!-- Search -->
                <div class="border-b border-border px-4 py-3">
                    <SearchInput v-model="search" :autofocus="true" />
                    <div class="mt-1.5 flex items-center gap-2">
                        <p class="text-xs text-muted-foreground">
                            {{
                                activeCount > MAX_VISIBLE
                                    ? `Showing ${MAX_VISIBLE} of ${activeCount} icons — refine your search`
                                    : `${activeCount} Icons`
                            }}
                        </p>
                        <!-- Cross-tab hint when searching -->
                        <button
                            v-if="search && otherCount > 0"
                            class="text-xs text-primary underline-offset-2 hover:underline"
                            type="button"
                            @click="
                                switchTab(
                                    activeTab === 'lucide' ? 'si' : 'lucide',
                                )
                            "
                        >
                            Auch {{ otherCount }} in {{ otherTabLabel }}
                        </button>
                    </div>
                </div>

                <!-- Lucide grid -->
                <div
                    v-if="activeTab === 'lucide'"
                    class="flex-1 overflow-y-auto p-4"
                >
                    <div
                        class="grid grid-cols-[repeat(auto-fill,minmax(80px,1fr))] gap-2"
                    >
                        <AppTooltip
                            v-for="icon in visibleLucide"
                            :key="icon.name"
                            :text="icon.name"
                        >
                            <button
                                :class="{
                                    'border-primary bg-primary/5 text-primary':
                                        modelValue === `lucide:${icon.name}`,
                                }"
                                class="flex flex-col items-center gap-1.5 rounded-lg border border-transparent p-2 text-center transition-colors hover:border-border hover:bg-muted/50"
                                type="button"
                                @click="selectLucide(icon.name)"
                            >
                                <component
                                    :is="
                                        (
                                            LucideIcons as Record<
                                                string,
                                                unknown
                                            >
                                        )[icon.componentName]
                                    "
                                    class="h-5 w-5 shrink-0"
                                />
                                <span
                                    class="w-full truncate text-[10px] leading-tight"
                                    >{{ icon.name }}</span
                                >
                            </button>
                        </AppTooltip>
                    </div>
                </div>

                <!-- Simple Icons grid -->
                <div v-else class="flex-1 overflow-y-auto p-4">
                    <div
                        class="grid grid-cols-[repeat(auto-fill,minmax(80px,1fr))] gap-2"
                    >
                        <AppTooltip
                            v-for="icon in visibleSi"
                            :key="icon.slug"
                            :text="`${icon.title} (${icon.slug})`"
                        >
                            <button
                                :class="{
                                    'border-primary bg-primary/5 text-primary':
                                        modelValue === `si:${icon.slug}`,
                                }"
                                class="flex flex-col items-center gap-1.5 rounded-lg border border-transparent p-2 text-center transition-colors hover:border-border hover:bg-muted/50"
                                type="button"
                                @click="selectSi(icon.slug)"
                            >
                                <span
                                    class="inline-flex h-5 w-5 shrink-0 items-center justify-center [&>svg]:h-5 [&>svg]:w-5"
                                    v-html="siSvg(icon.path)"
                                />
                                <span
                                    class="w-full truncate text-[10px] leading-tight"
                                    >{{ icon.slug }}</span
                                >
                            </button>
                        </AppTooltip>
                    </div>
                </div>
            </DialogContent>
        </Dialog>

        <AppTooltip v-if="modelValue" text="Remove icon">
            <button
                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-input text-muted-foreground transition-colors hover:bg-muted/40 hover:text-foreground"
                type="button"
                @click="clear"
            >
                <XIcon class="h-4 w-4" />
            </button>
        </AppTooltip>
    </div>
</template>
