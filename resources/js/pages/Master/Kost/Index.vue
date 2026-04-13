<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import Pagination from '@/components/Pagination.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { MapPin, Pencil, Plus, Search, Trash2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface KostItem {
    id: number;
    name: string;
    address: string;
    address_coordinate: string | null;
    description: string | null;
    owner_name: string;
    owner_id: number;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface Paginator<T> {
    data: T[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    links: PaginationLink[];
}

const props = defineProps<{
    kosts: Paginator<KostItem>;
    filters: { search: string };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Kost', href: '/master/kosts' },
];

const search = ref(props.filters.search ?? '');
const showDeleteDialog = ref(false);
const kostToDelete = ref<KostItem | null>(null);

const flash = computed(() => {
    const page = router.page as any;
    return page?.props?.flash as { success?: string; error?: string } | undefined;
});

const applyFilter = useDebounceFn(() => {
    router.get(
        route('master.kosts.index'),
        { search: search.value },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}, 300);

watch(search, applyFilter);

const confirmDelete = (kost: KostItem) => {
    kostToDelete.value = kost;
    showDeleteDialog.value = true;
};

const deleteKost = () => {
    if (!kostToDelete.value) return;
    router.delete(route('master.kosts.destroy', kostToDelete.value.id), {
        onFinish: () => {
            showDeleteDialog.value = false;
            kostToDelete.value = null;
        },
    });
};
</script>

<template>
    <Head title="Kost" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-muted/40 py-10">
            <div class="max-w-7xl mx-auto px-6 space-y-8">
            
            <!-- Header -->
            <div class="flex flex-col justify-between gap-4">
                <div class="flex flex-row justify-between item-center">

                    <Heading title="Manage Kosts"
                            :description="`Kelola data kost terdaftar. Total Kosts: ${kosts.total}`" />

                    <div >
                            <Link :href="route('master.kosts.create')"
                                class="cursor-pointer inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-medium text-primary-foreground shadow transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50">
                                Add Kost
                        </Link>
                </div>
            </div>
            </div>

            <!-- Flash message -->
            <div
                v-if="flash?.success"
                class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 dark:border-green-800 dark:bg-green-900/20 dark:text-green-400"
            >
                {{ flash.success }}
            </div>

            <!-- Search -->
            <div class="relative max-w-sm">
                <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                <Input v-model="search" placeholder="Cari kost..." class="pl-9" />
            </div>

            <!-- Table -->
            <div class="overflow-hidden rounded-lg border">
                <table class="w-full text-sm">
                    <thead class="border-b bg-muted/50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">Nama</th>
                            <th class="px-4 py-3 text-left font-medium">Alamat</th>
                            <th class="px-4 py-3 text-left font-medium">Owner</th>
                            <th class="px-4 py-3 text-left font-medium">Koordinat</th>
                            <th class="px-4 py-3 text-right font-medium">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="kosts.data.length === 0">
                            <td colspan="5" class="px-4 py-8 text-center text-muted-foreground">Tidak ada data kost.</td>
                        </tr>
                        <tr v-for="kost in kosts.data" :key="kost.id" class="border-b last:border-0">
                            <td class="px-4 py-3 font-medium">{{ kost.name }}</td>
                            <td class="max-w-xs truncate px-4 py-3">{{ kost.address }}</td>
                            <td class="px-4 py-3">{{ kost.owner_name }}</td>
                            <td class="px-4 py-3">
                                <span v-if="kost.address_coordinate" class="inline-flex items-center gap-1 text-xs text-muted-foreground">
                                    <MapPin class="h-3 w-3" />
                                    {{ kost.address_coordinate }}
                                </span>
                                <span v-else class="text-xs text-muted-foreground">-</span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <Button variant="ghost" size="icon" class="h-8 w-8" as-child>
                                        <Link :href="route('master.kosts.edit', kost.id)">
                                            <Pencil class="h-4 w-4" />
                                        </Link>
                                    </Button>
                                    <Button variant="ghost" size="icon" class="h-8 w-8 text-destructive" @click="confirmDelete(kost)">
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            </div>

            <!-- Pagination -->
            <Pagination :meta="kosts" />
        </div>

        <!-- Delete confirmation dialog -->
        <Dialog v-model:open="showDeleteDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Hapus Kost</DialogTitle>
                    <DialogDescription>
                        Apakah Anda yakin ingin menghapus kost <strong>{{ kostToDelete?.name }}</strong>? Semua data terkait (kategori, room, dll) akan ikut terhapus.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <DialogClose as-child>
                        <Button variant="secondary">Batal</Button>
                    </DialogClose>
                    <Button variant="destructive" @click="deleteKost">Hapus</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
