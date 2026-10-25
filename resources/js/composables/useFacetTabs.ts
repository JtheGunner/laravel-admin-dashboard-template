import { usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import type { UnderlineTab } from '@/components/UnderlineTabs.vue';

/**
 * Encapsulates the boilerplate of syncing a facet filter with UnderlineTabs.
 *
 * @param facetKey The URL parameter key (e.g. 'status')
 * @param options A getter returning the array of options (with value, label, and optional color)
 * @param counts A getter returning the counts map (with value mapping to count, plus 'total')
 */
export function useFacetTabs(
    facetKey: string,
    options: () => {
        value: string | number;
        label: string;
        color?: string | null;
    }[],
    counts: () => Record<string | number, number>,
) {
    const page = usePage();

    /** Derive the active tab from the current facet selection. */
    const activeTab = computed<string>(() => {
        const facets = (page.props.facets ?? []) as Array<{
            key: string;
            selected: (string | number)[];
            options: Array<{ value: string | number }>;
        }>;
        const facet = facets.find((f) => f.key === facetKey);

        if (!facet) {
            return 'all';
        }

        if (
            facet.selected.length === 0 ||
            facet.selected.length === facet.options.length
        ) {
            return 'all';
        }

        if (facet.selected.length === 1) {
            return String(facet.selected[0]);
        }

        return 'all';
    });

    const tabModel = ref(activeTab.value);

    watch(activeTab, (v) => {
        tabModel.value = v;
    });

    const tabs = computed<UnderlineTab[]>(() => {
        const allTab: UnderlineTab = {
            id: 'all',
            label: 'All',
            count: counts().total ?? 0,
        };

        const statusTabs: UnderlineTab[] = options().map((opt) => ({
            id: String(opt.value),
            label: opt.label,
            count: counts()[opt.value] ?? 0,
            color: opt.color,
        }));

        return [allTab, ...statusTabs];
    });

    return {
        activeTab,
        tabModel,
        tabs,
    };
}
