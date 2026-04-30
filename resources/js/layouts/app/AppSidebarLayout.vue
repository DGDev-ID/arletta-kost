<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import ToastNotification from '@/components/ToastNotification.vue';
import { useToast } from '@/composables/useToast';
import type { BreadcrumbItemType } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { watch } from 'vue';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const { success, error } = useToast();
const page = usePage();

// Auto-show toast whenever Inertia flash props change (after any server action)
watch(
    () => (page.props as any).flash,
    (flash: { success?: string; error?: string } | undefined) => {
        if (flash?.success) success(flash.success);
        if (flash?.error)   error(flash.error);
    },
    { deep: true },
);
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar />
        <AppContent variant="sidebar">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <slot />
        </AppContent>
    </AppShell>
    <ToastNotification />
</template>

