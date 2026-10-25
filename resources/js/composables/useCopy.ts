import { useClipboard } from '@vueuse/core';
import { toast } from 'vue-sonner';
import { selectElementContents } from '@/lib/utils';

/** Copy text to the clipboard with a toast confirmation (no-op for empty text). */
export function useCopy(): {
    copyText: (text: string, selectEl?: HTMLElement | null) => Promise<void>;
} {
    const { copy } = useClipboard({ legacy: true });

    async function copyText(
        text: string,
        selectEl?: HTMLElement | null,
    ): Promise<void> {
        const value = text.trim();

        if (value === '') {
            return;
        }

        try {
            await copy(value);
            toast.success('Copied');
            // Highlight what was copied so the user sees the exact selection.
            selectElementContents(selectEl ?? null);
        } catch {
            toast.error('Copy failed');
        }
    }

    return { copyText };
}
