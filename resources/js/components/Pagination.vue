<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';
import { computed } from 'vue';

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginationMeta {
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    links: PaginationLink[];
}

const props = defineProps<{
    meta: PaginationMeta;
}>();

// Strip HTML entities from Laravel pagination labels (e.g. &laquo;)
const cleanLabel = (label: string) => {
    const el = document.createElement('span');
    el.innerHTML = label;
    return el.textContent ?? label;
};

// Only show numbered page links (exclude prev/next arrows from links array)
const pageLinks = computed(() => props.meta.links.slice(1, -1));
</script>

<template>
    <div v-if="meta.last_page > 1" class="flex items-center justify-between gap-4">
        <!-- Info -->
        <p class="text-sm text-muted-foreground">
            Menampilkan <span class="font-medium">{{ meta.from ?? 0 }}</span>–<span class="font-medium">{{ meta.to ?? 0 }}</span>
            dari <span class="font-medium">{{ meta.total }}</span> data
        </p>

        <!-- Controls -->
        <div class="flex items-center gap-1">
            <!-- Prev -->
            <Link
                v-if="meta.links[0].url"
                :href="meta.links[0].url"
                preserve-scroll
                preserve-state
                class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-input bg-background text-sm hover:bg-accent hover:text-accent-foreground disabled:pointer-events-none disabled:opacity-50"
            >
                <ChevronLeft class="h-4 w-4" />
            </Link>
            <span
                v-else
                class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-input bg-background text-sm opacity-50"
            >
                <ChevronLeft class="h-4 w-4" />
            </span>

            <!-- Page numbers -->
            <template v-for="link in pageLinks" :key="link.label">
                <span
                    v-if="link.label === '...'"
                    class="inline-flex h-8 w-8 items-center justify-center text-sm text-muted-foreground"
                >
                    …
                </span>
                <Link
                    v-else-if="link.url && !link.active"
                    :href="link.url"
                    preserve-scroll
                    preserve-state
                    class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-input bg-background text-sm hover:bg-accent hover:text-accent-foreground"
                >
                    {{ cleanLabel(link.label) }}
                </Link>
                <span
                    v-else
                    class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-primary text-sm font-medium text-primary-foreground"
                >
                    {{ cleanLabel(link.label) }}
                </span>
            </template>

            <!-- Next -->
            <Link
                v-if="meta.links[meta.links.length - 1].url"
                :href="meta.links[meta.links.length - 1].url"
                preserve-scroll
                preserve-state
                class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-input bg-background text-sm hover:bg-accent hover:text-accent-foreground"
            >
                <ChevronRight class="h-4 w-4" />
            </Link>
            <span
                v-else
                class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-input bg-background text-sm opacity-50"
            >
                <ChevronRight class="h-4 w-4" />
            </span>
        </div>
    </div>
</template>
