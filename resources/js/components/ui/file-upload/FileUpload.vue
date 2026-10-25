<script lang="ts" setup>
import { computed, onUnmounted, ref, watch } from 'vue';
import { Eye, FileIcon, UploadCloud, X } from '@lucide/vue';
import AppTooltip from '@/components/AppTooltip.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';

const props = defineProps<{
    modelValue: string | File | null;
    accept?: string;
    label?: string;
    maxSizeMb?: number;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: File | null): void;
    (e: 'error', message: string): void;
}>();

const fileInputRef = ref<HTMLInputElement | null>(null);
const isDragActive = ref(false);
const objectUrl = ref<string | null>(null);

const currentPreview = computed(() => {
    if (props.modelValue instanceof File) {
        if (objectUrl.value) {
            URL.revokeObjectURL(objectUrl.value);
        }
        // eslint-disable-next-line vue/no-side-effects-in-computed-properties
        objectUrl.value = URL.createObjectURL(props.modelValue);
        return objectUrl.value;
    }
    return props.modelValue || null; // string url or null
});

onUnmounted(() => {
    if (objectUrl.value) {
        URL.revokeObjectURL(objectUrl.value);
    }
});

const hasImageError = ref(false);

watch(currentPreview, () => {
    hasImageError.value = false;
});

function handleImageError() {
    hasImageError.value = true;
}

const isImage = computed(() => {
    if (props.modelValue instanceof File) {
        return props.modelValue.type.startsWith('image/');
    }
    if (typeof props.modelValue === 'string') {
        return props.modelValue.match(/\.(jpeg|jpg|gif|png|webp|svg)/i) !== null;
    }
    return false;
});

const isPdf = computed(() => {
    if (props.modelValue instanceof File) {
        return props.modelValue.type === 'application/pdf';
    }
    if (typeof props.modelValue === 'string') {
        return /\.pdf(\?|#|$)/i.test(props.modelValue);
    }
    return false;
});

// A preview is possible for images and PDFs (embedded in the dialog).
const canPreview = computed(
    () => !!currentPreview.value && (isImage.value || isPdf.value),
);
const previewOpen = ref(false);

// Encode URLs for <img>/<iframe>/<a> (stored files keep their
// real name and may contain spaces/special characters). Blob URLs stay
// unchanged.
const previewSrc = computed(() =>
    currentPreview.value ? encodeURI(currentPreview.value) : null,
);

const isFileInstance = computed(() => props.modelValue instanceof File);

const fileName = computed(() => {
    if (props.modelValue instanceof File) {
        return props.modelValue.name;
    }
    if (typeof props.modelValue === 'string') {
        return props.modelValue.substring(props.modelValue.lastIndexOf('/') + 1);
    }
    return '';
});

const fileSize = computed(() => {
    if (props.modelValue instanceof File) {
        const bytes = props.modelValue.size;
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    return '';
});

function handleFileChange(event: Event) {
    const files = (event.target as HTMLInputElement).files;
    if (files && files.length > 0) {
        validateAndEmit(files[0]);
    }
}

function handleDrop(event: DragEvent) {
    isDragActive.value = false;
    const files = event.dataTransfer?.files;
    if (files && files.length > 0) {
        validateAndEmit(files[0]);
    }
}

function validateAndEmit(file: File) {
    if (props.accept) {
        const acceptTypes = props.accept.split(',').map((t) => t.trim());
        const fileType = file.type;
        const name = file.name;
        const matches = acceptTypes.some((type) => {
            if (type.endsWith('/*')) {
                const prefix = type.replace('/*', '');
                return fileType.startsWith(prefix);
            }
            if (type.startsWith('.')) {
                return name.endsWith(type);
            }
            return fileType === type;
        });
        if (!matches) {
            emit('error', 'Invalid file type.');
            return;
        }
    }

    if (props.maxSizeMb) {
        const maxBytes = props.maxSizeMb * 1024 * 1024;
        if (file.size > maxBytes) {
            emit('error', `File must not be larger than ${props.maxSizeMb} MB.`);
            return;
        }
    }

    emit('update:modelValue', file);
}

function triggerBrowse() {
    fileInputRef.value?.click();
}

function clearFile() {
    if (fileInputRef.value) {
        fileInputRef.value.value = '';
    }
    emit('update:modelValue', null);
}
</script>

<template>
    <div
        :class="[
            isDragActive
                ? 'border-primary bg-primary/5 ring-2 ring-primary/20'
                : 'border-border bg-card hover:border-muted-foreground/30',
        ]"
        class="relative flex min-h-[180px] w-full cursor-pointer flex-col justify-center rounded-2xl border border-dashed p-4 transition-all duration-200"
        @click="triggerBrowse"
        @dragover.prevent="isDragActive = true"
        @dragleave.prevent="isDragActive = false"
        @drop.prevent="handleDrop"
    >
        <input
            ref="fileInputRef"
            :accept="accept"
            class="hidden"
            type="file"
            @change="handleFileChange"
        />

        <div
            v-if="currentPreview"
            class="flex flex-col items-center gap-4 sm:flex-row sm:flex-wrap sm:items-center"
        >
            <!-- Image Preview -->
            <AppTooltip
                v-if="isImage && !hasImageError"
                :text="canPreview ? 'Preview' : ''"
            >
                <button
                    :class="[
                        'relative size-20 shrink-0 overflow-hidden rounded-xl border border-border bg-muted shadow-xs',
                        canPreview ? 'cursor-zoom-in' : '',
                    ]"
                    type="button"
                    @click.stop="canPreview && (previewOpen = true)"
                >
                    <img
                        :src="previewSrc ?? undefined"
                        alt="Preview"
                        class="h-full w-full object-cover"
                        @error="handleImageError"
                    />
                </button>
            </AppTooltip>
            <!-- File Icon (non-image or broken image); PDFs open the preview -->
            <AppTooltip v-else :text="canPreview ? 'Preview' : ''">
                <button
                    :class="[
                        'flex size-20 shrink-0 items-center justify-center rounded-xl border border-border bg-muted text-muted-foreground',
                        canPreview ? 'cursor-zoom-in hover:text-foreground' : '',
                    ]"
                    type="button"
                    @click.stop="canPreview && (previewOpen = true)"
                >
                    <FileIcon class="size-10" />
                </button>
            </AppTooltip>

            <div class="min-w-0 flex-1 basis-40 text-center sm:text-left">
                <AppTooltip :text="fileName">
                    <p class="truncate text-sm font-medium text-foreground">
                        {{ fileName || (isFileInstance ? 'Selected file' : 'Current file') }}
                    </p>
                </AppTooltip>
                <p v-if="fileSize" class="mt-0.5 text-xs text-muted-foreground">
                    {{ fileSize }}
                </p>
            </div>

            <div class="flex shrink-0 gap-2">
                <Button
                    v-if="canPreview"
                    size="sm"
                    type="button"
                    variant="outline"
                    @click.stop="previewOpen = true"
                >
                    <Eye class="size-4" />
                    Preview
                </Button>
                <Button size="sm" type="button" variant="outline">
                    Change
                </Button>
                <Button
                    class="size-8 rounded-full text-red-500 hover:bg-red-500/10 hover:text-red-600"
                    size="icon"
                    type="button"
                    variant="ghost"
                    @click.stop="clearFile"
                >
                    <X class="size-4" />
                </Button>
            </div>
        </div>

        <div
            v-else
            class="flex flex-col items-center justify-center py-6 text-center"
        >
            <div
                class="mb-3 flex size-12 items-center justify-center rounded-full bg-muted text-muted-foreground"
            >
                <UploadCloud class="size-6" />
            </div>
            <p class="text-sm font-medium text-foreground">
                Drag files here or
                <span class="font-semibold text-primary hover:underline"
                    >browse</span
                >
            </p>
            <p class="mt-1 text-xs text-muted-foreground">
                {{ label || 'Images or documents up to 2 MB' }}
            </p>
        </div>

        <!-- Preview dialog (image or embedded PDF); its content is teleported to
         <body>, so it does not clash with the click dropzone. -->
        <Dialog v-model:open="previewOpen">
            <DialogContent class="max-w-5xl" @click.stop>
                <DialogHeader>
                    <DialogTitle class="truncate pr-6">
                        {{ fileName || 'Preview' }}
                    </DialogTitle>
                </DialogHeader>
                <img
                    v-if="isImage && !hasImageError"
                    :alt="fileName"
                    :src="previewSrc ?? undefined"
                    class="max-h-[80vh] w-full rounded-lg object-contain"
                />
                <iframe
                    v-else-if="isPdf"
                    :src="previewSrc ?? undefined"
                    :title="fileName"
                    class="h-[80vh] w-full rounded-lg border border-border"
                />
            </DialogContent>
        </Dialog>
    </div>
</template>
