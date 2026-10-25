<script lang="ts" setup>
/**
 * Mount this once in AppShell (or any root layout) to enable the imperative
 * useConfirm() composable across the whole app.
 */
import { computed } from 'vue';
import AppConfirmDialog from '@/components/AppConfirmDialog.vue';
import { useConfirmState } from '@/composables/useConfirm';

const { state, confirm, cancel } = useConfirmState();

const open = computed(() => state.value !== null);
</script>

<template>
    <AppConfirmDialog
        v-if="state"
        :cancel-label="state.cancelLabel"
        :confirm-label="state.confirmLabel"
        :message="state.message"
        :open="open"
        :title="state.title"
        :variant="state.variant"
        @cancel="cancel"
        @confirm="confirm"
        @update:open="
            (v) => {
                if (!v) cancel();
            }
        "
    />
</template>
