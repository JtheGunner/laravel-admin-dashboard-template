<script lang="ts" setup>
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';

/**
 * The single sanctioned tooltip in the app — the styled hover tooltip used by
 * the table row actions (Edit / Delete / Duplicate). Wrap the target
 * element in `<AppTooltip text="…">…</AppTooltip>`.
 *
 * Do NOT introduce competing tooltip mechanisms: no native `title=` attributes
 * for hover hints (they render an unstyled browser tooltip) and no ad-hoc
 * `Tooltip` / `TooltipContent` primitives. Always route through this component
 * so every tooltip shares one look. (`title` on `<iframe>` for accessibility is
 * fine — that's a frame label, not a tooltip.)
 */

withDefaults(
    defineProps<{
        /** The tooltip text to display. Pass an empty string or set disabled to hide. */
        text: string;
        /** Which side the tooltip pops up on. */
        side?: 'top' | 'right' | 'bottom' | 'left';
        /** Delay in ms before the tooltip appears. */
        delay?: number;
        /** When true, the tooltip is not rendered at all. */
        disabled?: boolean;
        /** Alignment along the side axis. */
        align?: 'start' | 'center' | 'end';
    }>(),
    {
        side: 'top',
        delay: 300,
        disabled: false,
        align: 'center',
    },
);
</script>

<template>
    <!-- When disabled or no text, just render the slot directly without overhead. -->
    <slot v-if="disabled || !text" />

    <TooltipProvider v-else :delay-duration="delay">
        <Tooltip>
            <TooltipTrigger as-child>
                <slot />
            </TooltipTrigger>
            <TooltipContent
                :align="align"
                :side="side"
                class="max-w-xs break-words"
            >
                <p>{{ text }}</p>
            </TooltipContent>
        </Tooltip>
    </TooltipProvider>
</template>
