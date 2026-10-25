<script lang="ts" setup>
import { Moon, Sun } from '@lucide/vue';
import { computed } from 'vue';
import AppTooltip from '@/components/AppTooltip.vue';
import { useAppearance } from '@/composables/useAppearance';

const { resolvedAppearance, updateAppearance } = useAppearance();

const isDark = computed(() => resolvedAppearance.value === 'dark');

function toggle() {
    updateAppearance(isDark.value ? 'light' : 'dark');
}
</script>

<template>
    <AppTooltip :text="isDark ? 'Light mode' : 'Dark mode'" side="bottom">
        <button
            :aria-label="
                isDark ? 'Switch to light mode' : 'Switch to dark mode'
            "
            class="inline-flex size-10 items-center justify-center rounded-full text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
            type="button"
            @click="toggle"
        >
            <Sun v-if="isDark" class="size-5" />
            <Moon v-else class="size-5" />
        </button>
    </AppTooltip>
</template>
