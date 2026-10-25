import { router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import type { FlashToast } from '@/types/ui';

export function initializeFlashToast(): void {
    router.on('flash', (event) => {
        const flash = (event as CustomEvent).detail?.flash;
        const data = flash?.toast as FlashToast | undefined;

        if (!data) {
            return;
        }

        toast[data.type](
            data.message,
            data.action
                ? {
                      action: {
                          label: data.action.label,
                          onClick: () => router.visit(data.action?.href ?? '/'),
                      },
                  }
                : undefined,
        );
    });
}
