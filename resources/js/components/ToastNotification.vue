<script setup lang="ts">
import { useToast } from '@/composables/useToast';
import { CheckCircle, Info, TriangleAlert, XCircle, X } from 'lucide-vue-next';
import { TransitionGroup } from 'vue';

const { toasts, remove } = useToast();

const icons = {
    success: CheckCircle,
    error: XCircle,
    warning: TriangleAlert,
    info: Info,
};

const styles = {
    success: {
        container: 'border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-950/80',
        icon: 'text-green-600 dark:text-green-400',
        text: 'text-green-800 dark:text-green-200',
        close: 'text-green-500 hover:text-green-700 dark:text-green-400 dark:hover:text-green-200',
    },
    error: {
        container: 'border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-950/80',
        icon: 'text-red-600 dark:text-red-400',
        text: 'text-red-800 dark:text-red-200',
        close: 'text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-200',
    },
    warning: {
        container: 'border-yellow-200 bg-yellow-50 dark:border-yellow-800 dark:bg-yellow-950/80',
        icon: 'text-yellow-600 dark:text-yellow-400',
        text: 'text-yellow-800 dark:text-yellow-200',
        close: 'text-yellow-500 hover:text-yellow-700 dark:text-yellow-400 dark:hover:text-yellow-200',
    },
    info: {
        container: 'border-blue-200 bg-blue-50 dark:border-blue-800 dark:bg-blue-950/80',
        icon: 'text-blue-600 dark:text-blue-400',
        text: 'text-blue-800 dark:text-blue-200',
        close: 'text-blue-500 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-200',
    },
};
</script>

<template>
    <Teleport to="body">
        <div class="fixed bottom-5 right-5 z-[9999] flex flex-col gap-2 pointer-events-none" aria-live="polite">
            <TransitionGroup
                enter-active-class="transition-all duration-300 ease-out"
                enter-from-class="opacity-0 translate-y-4 scale-95"
                enter-to-class="opacity-100 translate-y-0 scale-100"
                leave-active-class="transition-all duration-200 ease-in"
                leave-from-class="opacity-100 translate-y-0 scale-100"
                leave-to-class="opacity-0 translate-y-2 scale-95"
            >
                <div
                    v-for="toast in toasts"
                    :key="toast.id"
                    :class="[
                        'pointer-events-auto flex items-start gap-3 rounded-xl border px-4 py-3 shadow-lg backdrop-blur-sm min-w-[280px] max-w-sm',
                        styles[toast.type].container,
                    ]"
                >
                    <component
                        :is="icons[toast.type]"
                        :class="['mt-0.5 h-4 w-4 flex-shrink-0', styles[toast.type].icon]"
                    />
                    <p :class="['flex-1 text-sm font-medium leading-snug', styles[toast.type].text]">
                        {{ toast.message }}
                    </p>
                    <button
                        @click="remove(toast.id)"
                        :class="['flex-shrink-0 transition-colors', styles[toast.type].close]"
                        aria-label="Tutup notifikasi"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>
            </TransitionGroup>
        </div>
    </Teleport>
</template>
