import { router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import type { FlashToast } from '@/types/ui';

export function initializeFlashToast(): void {
    router.on('navigate', (event) => {
        const data = (event.detail.page.props as Record<string, unknown>)?.flash as
            | { toast?: FlashToast }
            | undefined;

        if (!data?.toast) {
return;
}

        toast[data.toast.type](data.toast.message);
    });
}
