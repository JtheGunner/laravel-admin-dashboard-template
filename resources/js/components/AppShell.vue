<script lang="ts" setup>
import { usePage } from '@inertiajs/vue3';
import AppConfirmDialogHost from '@/components/AppConfirmDialogHost.vue';
import { SidebarProvider } from '@/components/ui/sidebar';
import type { AppVariant } from '@/types';

type Props = {
    variant?: AppVariant;
};

withDefaults(defineProps<Props>(), {
    variant: 'sidebar',
});

const isOpen = usePage().props.sidebarOpen;
</script>

<template>
    <div v-if="variant === 'header'" class="flex min-h-screen w-full flex-col">
        <slot />
        <AppConfirmDialogHost />
    </div>
    <SidebarProvider v-else :default-open="isOpen">
        <slot />
        <AppConfirmDialogHost />
    </SidebarProvider>
</template>
