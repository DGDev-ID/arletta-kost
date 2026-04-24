<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';

interface Props {
    class?: string;
}

defineProps<Props>();
</script>

<template>
    <div class="flex aspect-square size-8 items-center justify-center rounded-md text-sidebar-primary-foreground">
        <img 
            src="/images/arletta-kost-icon.png" 
            alt="Arletta Kost Logo"
            class="size-5 object-contain"
        />
    </div>
    <div class="ml-1 grid flex-1 text-left text-sm">
        <span class="mb-0.5 truncate font-semibold leading-none">Arletta Kost</span>
    </div>
</template>
