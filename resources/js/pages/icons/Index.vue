<script lang="ts" setup>
import * as LucideIcons from '@lucide/vue';
import { CheckIcon } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import AppTooltip from '@/components/AppTooltip.vue';
import SearchInput from '@/components/SearchInput.vue';
import type { UnderlineTab } from '@/components/UnderlineTabs.vue';
import UnderlineTabs from '@/components/UnderlineTabs.vue';
import { ALL_LUCIDE, ALL_SI, siSvg } from '@/composables/useIconLibrary';
import lucideTags from '@/data/lucide-tags.json';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Icons', href: '/icons' }],
    },
});

const LUCIDE_TAGS = lucideTags as Record<string, string[]>;

// --- State ---
const MAX_VISIBLE = 10000;
const search = ref('');

// Remember the active icon tab in the session so a reload does not jump back.
const TAB_STORAGE_KEY = 'icons-active-tab';
const storedTab = sessionStorage.getItem(TAB_STORAGE_KEY);
const activeTab = ref<'lucide' | 'si'>(
    storedTab === 'si' || storedTab === 'lucide' ? storedTab : 'lucide',
);
watch(activeTab, (tab) => {
    sessionStorage.setItem(TAB_STORAGE_KEY, tab);
});

const iconTabs = computed<UnderlineTab[]>(() => [
    { id: 'lucide', label: 'Lucide', count: ALL_LUCIDE.length },
    { id: 'si', label: 'Simple Icons', count: ALL_SI.length },
]);

const copied = ref<string | null>(null);

// --- Filtered lists ---
const filteredLucide = computed(() => {
    const q = search.value.toLowerCase().trim();

    if (!q) {
        return ALL_LUCIDE;
    }

    return ALL_LUCIDE.filter(
        (i) =>
            i.name.includes(q) ||
            (LUCIDE_TAGS[i.name] ?? []).some((tag) => tag.includes(q)),
    );
});

const filteredSi = computed(() => {
    const q = search.value.toLowerCase().trim();

    if (!q) {
        return ALL_SI;
    }

    return ALL_SI.filter(
        (i) => i.slug.includes(q) || i.title.toLowerCase().includes(q),
    );
});

const visibleLucide = computed(() =>
    filteredLucide.value.slice(0, MAX_VISIBLE),
);
const visibleSi = computed(() => filteredSi.value.slice(0, MAX_VISIBLE));

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

// --- Copy to clipboard ---
async function copyValue(value: string): Promise<void> {
    await navigator.clipboard.writeText(value);
    copied.value = value;
    setTimeout(() => {
        copied.value = null;
    }, 1500);
}
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-4 px-4 pb-4 md:px-6 md:pb-6">
        <div
            class="overflow-hidden rounded-2xl border border-border bg-card text-card-foreground shadow-card"
        >
            <div class="p-6">
                <!-- Tab Navigation (Underline style) -->
                <UnderlineTabs v-model="activeTab" :tabs="iconTabs" show-count>
                    <template #trailing>
                        <SearchInput
                            v-model="search"
                            class="ml-auto w-full sm:w-60"
                            placeholder="Search by name or topic…"
                        />
                    </template>
                </UnderlineTabs>

                <!-- Tab Content -->
                <div class="pt-4">
                    <!-- Info row -->
                    <div
                        class="mb-4 flex flex-wrap items-center justify-between gap-2"
                    >
                        <p class="text-xs text-muted-foreground">
                            <template v-if="activeTab === 'lucide'">
                                Click an icon to copy
                                <code>lucide:name</code> to the clipboard.
                                Search also understands topics (e.g. "finances",
                                "medical", "chart").
                            </template>
                            <template v-else>
                                Click an icon to copy
                                <code>si:slug</code> to the clipboard. Search by
                                name or brand (e.g. "GitHub", "grafana").
                            </template>
                        </p>
                        <span class="text-sm text-muted-foreground">
                            {{ activeCount.toLocaleString('en') }} icons
                            <template v-if="search && otherCount > 0">
                                ·
                                <button
                                    class="text-primary underline-offset-2 hover:underline"
                                    type="button"
                                    @click="
                                        activeTab =
                                            activeTab === 'lucide'
                                                ? 'si'
                                                : 'lucide'
                                    "
                                >
                                    Also
                                    {{ otherCount.toLocaleString('en') }} in
                                    {{
                                        activeTab === 'lucide'
                                            ? 'Simple Icons'
                                            : 'Lucide'
                                    }}
                                </button>
                            </template>
                        </span>
                    </div>

                    <!-- Lucide grid -->
                    <div v-if="activeTab === 'lucide'">
                        <div
                            class="grid grid-cols-[repeat(auto-fill,minmax(180px,1fr))] gap-2"
                        >
                            <AppTooltip
                                v-for="icon in visibleLucide"
                                :key="icon.name"
                                :text="`lucide:${icon.name}${(LUCIDE_TAGS[icon.name] ?? []).length ? ' · Tags: ' + LUCIDE_TAGS[icon.name].join(', ') : ''}`"
                            >
                                <button
                                    class="group relative flex flex-col items-center gap-1.5 rounded-lg border border-transparent p-3 text-center transition-colors hover:border-border hover:bg-muted/50"
                                    type="button"
                                    @click="copyValue(`lucide:${icon.name}`)"
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
                                        class="h-11 w-11 shrink-0 transition-colors group-hover:text-primary"
                                    />
                                    <span
                                        class="w-full truncate text-[11px] leading-tight text-muted-foreground"
                                    >
                                        {{ icon.name }}
                                    </span>
                                    <span
                                        v-if="copied === `lucide:${icon.name}`"
                                        class="absolute inset-0 flex items-center justify-center rounded-lg bg-primary/10 text-primary"
                                    >
                                        <CheckIcon class="h-5 w-5" />
                                    </span>
                                </button>
                            </AppTooltip>
                        </div>
                        <p
                            v-if="filteredLucide.length > MAX_VISIBLE"
                            class="mt-3 text-center text-xs text-muted-foreground"
                        >
                            Showing {{ MAX_VISIBLE }} of
                            {{ filteredLucide.length }} — refine your search
                        </p>
                    </div>

                    <!-- Simple Icons grid -->
                    <div v-else>
                        <div
                            class="grid grid-cols-[repeat(auto-fill,minmax(180px,1fr))] gap-2"
                        >
                            <AppTooltip
                                v-for="icon in visibleSi"
                                :key="icon.slug"
                                :text="`si:${icon.slug} · ${icon.title}`"
                            >
                                <button
                                    class="group relative flex flex-col items-center gap-1.5 rounded-lg border border-transparent p-3 text-center transition-colors hover:border-border hover:bg-muted/50"
                                    type="button"
                                    @click="copyValue(`si:${icon.slug}`)"
                                >
                                    <span
                                        class="inline-flex h-11 w-11 shrink-0 items-center justify-center transition-colors group-hover:text-primary [&>svg]:h-11 [&>svg]:w-11"
                                        v-html="siSvg(icon.path, icon.hex)"
                                    />
                                    <span
                                        class="w-full truncate text-[11px] leading-tight text-muted-foreground"
                                    >
                                        {{ icon.slug }}
                                    </span>
                                    <span
                                        v-if="copied === `si:${icon.slug}`"
                                        class="absolute inset-0 flex items-center justify-center rounded-lg bg-primary/10 text-primary"
                                    >
                                        <CheckIcon class="h-5 w-5" />
                                    </span>
                                </button>
                            </AppTooltip>
                        </div>
                        <p
                            v-if="filteredSi.length > MAX_VISIBLE"
                            class="mt-3 text-center text-xs text-muted-foreground"
                        >
                            Showing {{ MAX_VISIBLE }} of
                            {{ filteredSi.length }} — refine your search
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
