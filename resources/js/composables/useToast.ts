import { ref } from 'vue';

export type ToastType = 'success' | 'error' | 'warning' | 'info';

export interface Toast {
    id: number;
    type: ToastType;
    message: string;
    duration?: number;
}

const toasts = ref<Toast[]>([]);
let counter = 0;

export function useToast() {
    const add = (message: string, type: ToastType = 'info', duration = 3500) => {
        const id = ++counter;
        toasts.value.push({ id, type, message, duration });
        setTimeout(() => remove(id), duration);
    };

    const remove = (id: number) => {
        const idx = toasts.value.findIndex((t) => t.id === id);
        if (idx !== -1) toasts.value.splice(idx, 1);
    };

    const success = (message: string, duration?: number) => add(message, 'success', duration);
    const error   = (message: string, duration?: number) => add(message, 'error', duration ?? 5000);
    const warning = (message: string, duration?: number) => add(message, 'warning', duration);
    const info    = (message: string, duration?: number) => add(message, 'info', duration);

    return { toasts, add, remove, success, error, warning, info };
}
