<script lang="ts" setup>
/**
 * AppConfirmDialog – a reusable confirmation dialog with TailAdmin-styled alert box.
 *
 * Usage (programmatic, via useConfirm composable):
 *   const confirm = useConfirm()
 *   const ok = await confirm({
 *     title: 'Are you sure?',
 *     message: 'This action cannot be undone.',
 *     variant: 'warning',
 *     confirmLabel: 'Yes, delete',
 *   })
 *   if (ok) { ... }
 *
 * Or declaratively with v-model:
 *   <AppConfirmDialog v-model:open="showDialog" title="..." @confirm="doIt" />
 */
import { AlertCircle, AlertTriangle, CheckCircle, Info, X } from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

type Variant = 'info' | 'warning' | 'error' | 'success';

const props = withDefaults(
    defineProps<{
        open: boolean;
        title: string;
        message?: string;
        variant?: Variant;
        confirmLabel?: string;
        cancelLabel?: string;
    }>(),
    {
        variant: 'warning',
        confirmLabel: 'Confirm',
        cancelLabel: 'Cancel',
    },
);

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'confirm'): void;
    (e: 'cancel'): void;
}>();

// TailAdmin colour scheme ported from alert.blade.php
const variantConfig = computed(() => {
    const map: Record<
        Variant,
        {
            container: string;
            icon: string;
            confirmVariant: 'default' | 'destructive';
        }
    > = {
        info: {
            container:
                'border-blue-500 bg-blue-50 dark:border-blue-500/30 dark:bg-blue-500/15',
            icon: 'text-blue-500',
            confirmVariant: 'default',
        },
        success: {
            container:
                'border-green-500 bg-green-50 dark:border-green-500/30 dark:bg-green-500/15',
            icon: 'text-green-500',
            confirmVariant: 'default',
        },
        warning: {
            container:
                'border-yellow-500 bg-yellow-50 dark:border-yellow-500/30 dark:bg-yellow-500/15',
            icon: 'text-yellow-500',
            confirmVariant: 'default',
        },
        error: {
            container:
                'border-red-500 bg-red-50 dark:border-red-500/30 dark:bg-red-500/15',
            icon: 'text-red-500',
            confirmVariant: 'destructive',
        },
    };

    return map[props.variant];
});

const IconComponent = computed(() => {
    const map: Record<Variant, typeof AlertCircle> = {
        info: Info,
        success: CheckCircle,
        warning: AlertTriangle,
        error: AlertCircle,
    };

    return map[props.variant];
});

function close() {
    emit('update:open', false);
}

function handleCancel() {
    emit('cancel');
    close();
}

function handleConfirm() {
    emit('confirm');
    close();
}
</script>

<template>
    <Dialog :open="open" @update:open="(v) => emit('update:open', v)">
        <DialogContent :show-close-button="false" class="max-w-md">
            <DialogHeader>
                <!-- TailAdmin-style alert box -->
                <div
                    :class="variantConfig.container"
                    class="mb-2 rounded-xl border p-4"
                >
                    <div class="flex items-start gap-3">
                        <div :class="variantConfig.icon" class="-mt-0.5">
                            <component :is="IconComponent" class="size-6" />
                        </div>
                        <div class="flex-1">
                            <DialogTitle
                                class="text-sm font-semibold text-gray-800 dark:text-white/90"
                            >
                                {{ title }}
                            </DialogTitle>
                            <DialogDescription
                                v-if="message"
                                class="mt-1 text-sm text-gray-500 dark:text-gray-400"
                            >
                                {{ message }}
                            </DialogDescription>
                        </div>
                        <!-- Close icon -->
                        <button
                            :aria-label="cancelLabel"
                            :class="variantConfig.icon"
                            class="rounded p-0.5 opacity-60 transition-opacity hover:opacity-100 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                            type="button"
                            @click="handleCancel"
                        >
                            <X class="size-4" />
                        </button>
                    </div>
                </div>
            </DialogHeader>

            <DialogFooter class="gap-2">
                <Button variant="outline" @click="handleCancel">
                    {{ cancelLabel }}
                </Button>
                <Button
                    :variant="variantConfig.confirmVariant"
                    @click="handleConfirm"
                >
                    {{ confirmLabel }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
