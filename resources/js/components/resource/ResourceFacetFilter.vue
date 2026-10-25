<script lang="ts" setup>
import { parseDate } from '@internationalized/date';
import { Check, ChevronDown, X } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { RangeCalendar } from '@/components/ui/range-calendar';
import type { FacetConfig, FacetOption } from '@/types/resource';

const props = defineProps<{
    facet: FacetConfig;
    /** Currently selected values for this facet. */
    selected: (string | number)[];
}>();

const emit = defineEmits<{
    toggle: [key: string, value: string | number, checked: boolean];
    'update:selected': [values: (string | number)[]];
}>();

function isChecked(value: string | number): boolean {
    return props.selected.some((v) => String(v) === String(value));
}

// Whether this facet currently narrows the list (a non-empty proper subset of
// its options selected — a full or empty selection imposes no constraint).
// Uses optionsTotal (full options count before cascading) so that selecting all
// visible options still registers as active when sibling filters hide some options.
const isActive = computed<boolean>(() => {
    if (props.facet.type === 'date') {
        return props.selected.length > 0;
    }

    return (
        props.selected.length > 0 &&
        props.selected.length < props.facet.optionsTotal
    );
});

// Enabled options first (preserving server order), disabled options at the end.
const sortedOptions = computed<FacetOption[]>(() => {
    const opts = props.facet.options ?? [];

    return [
        ...opts.filter((o) => !o.disabled),
        ...opts.filter((o) => o.disabled),
    ];
});

const isPopoverOpen = ref(false);
const localDateRange = ref<any>(undefined);

watch(isPopoverOpen, (open) => {
    if (open) {
        if (!props.selected || props.selected.length === 0) {
            localDateRange.value = undefined;
        } else if (props.selected.length === 1) {
            const d = parseDate(String(props.selected[0]));
            localDateRange.value = { start: d, end: undefined };
        } else {
            localDateRange.value = {
                start: parseDate(String(props.selected[0])),
                end: parseDate(String(props.selected[1])),
            };
        }
    } else {
        const val = localDateRange.value;

        if (!val || !val.start) {
            emit('update:selected', []);
        } else if (!val.end || val.start.compare(val.end as any) === 0) {
            emit('update:selected', [val.start.toString()]);
        } else {
            emit('update:selected', [val.start.toString(), val.end.toString()]);
        }
    }
});

// A short summary text for the trigger button when active.
// E.g. "Direction: 2 selected" or "Direction: Incoming"
const activeLabel = computed<string | null>(() => {
    if (!isActive.value || props.selected.length === 0) {
        return null;
    }

    if (props.facet.type === 'date') {
        const formatStr = (d: string) => {
            const date = new Date(d);

            return new Intl.DateTimeFormat('en-GB', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
            }).format(date);
        };

        if (props.selected.length === 1) {
            return formatStr(String(props.selected[0]));
        } else if (props.selected.length === 2) {
            return `${formatStr(String(props.selected[0]))} - ${formatStr(String(props.selected[1]))}`;
        }

        return null;
    }

    if (props.selected.length === 1 && props.facet.options) {
        const opt = props.facet.options.find(
            (o) => String(o.value) === String(props.selected[0]),
        );

        return opt ? opt.label : '1 selected';
    }

    return `${props.selected.length} selected`;
});
</script>

<template>
    <Popover v-if="facet.type === 'date'" v-model:open="isPopoverOpen">
        <PopoverTrigger :as-child="true">
            <Button
                :class="{ 'border-primary/50 text-foreground': isActive }"
                aria-label="Filter"
                class="h-11 rounded-lg border-input bg-white px-4 font-normal whitespace-nowrap text-muted-foreground shadow-xs dark:bg-input/30"
                variant="outline"
            >
                {{ facet.label }}
                <span
                    v-if="activeLabel"
                    class="ml-1 font-medium text-foreground"
                >
                    : {{ activeLabel }}
                </span>
                <div
                    v-if="isActive"
                    class="ml-2 flex cursor-pointer items-center justify-center rounded-sm p-0.5 text-muted-foreground transition-colors hover:bg-destructive/10 hover:text-destructive"
                    @pointerdown.stop
                    @click.stop.prevent="emit('update:selected', [])"
                >
                    <X class="size-3.5" />
                </div>
                <ChevronDown v-else class="ml-2 size-4 opacity-50" />
            </Button>
        </PopoverTrigger>
        <PopoverContent align="start" class="w-auto p-0">
            <RangeCalendar
                v-model="localDateRange"
                :available-dates="facet.availableDates"
                initial-focus
            />
        </PopoverContent>
    </Popover>
    <DropdownMenu v-else>
        <DropdownMenuTrigger :as-child="true">
            <Button
                :class="{ 'border-primary/50 text-foreground': isActive }"
                aria-label="Filter"
                class="h-11 rounded-lg border-input bg-white px-4 font-normal whitespace-nowrap text-muted-foreground shadow-xs dark:bg-input/30"
                variant="outline"
            >
                {{ facet.label }}
                <span
                    v-if="activeLabel"
                    class="ml-1 font-medium text-foreground"
                >
                    : {{ activeLabel }}
                </span>
                <div
                    v-if="isActive"
                    class="ml-2 flex cursor-pointer items-center justify-center rounded-sm p-0.5 text-muted-foreground transition-colors hover:bg-destructive/10 hover:text-destructive"
                    @pointerdown.stop
                    @click.stop.prevent="emit('update:selected', [])"
                >
                    <X class="size-3.5" />
                </div>
                <ChevronDown v-else class="ml-2 size-4 opacity-50" />
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent
            align="start"
            class="max-h-80 w-56 overflow-y-auto"
        >
            <DropdownMenuItem
                v-for="opt in sortedOptions"
                :key="String(opt.value)"
                :disabled="opt.disabled"
                class="gap-2"
                @select="(e: Event) => e.preventDefault()"
                @click="
                    emit('toggle', facet.key, opt.value, !isChecked(opt.value))
                "
            >
                <div
                    class="grid size-4 shrink-0 place-content-center rounded-[4px] border shadow-xs"
                    :class="
                        isChecked(opt.value)
                            ? 'border-primary bg-primary'
                            : 'border-input'
                    "
                >
                    <Check
                        v-if="isChecked(opt.value)"
                        class="size-3.5 text-primary-foreground"
                    />
                </div>
                {{ opt.label }}
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
