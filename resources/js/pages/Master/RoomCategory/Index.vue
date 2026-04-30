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
import { useToast } from '@/composables/useToast';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { Image, List, Pencil, Search, Trash2 } from 'lucide-vue-next';
import { ref, watch } from 'vue';

const { success: toastSuccess, error: toastError } = useToast();

interface CategoryItem {
    id: number;
    name: string;
    description: string | null;
    kost_id: number;
    kost_name: string;
    images_count: number;
    details_count: number;
    rooms_count: number;
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
    categories: Paginator<CategoryItem>;
    filters: { search: string };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Room Category', href: '/master/room-categories' },
];

const search = ref(props.filters.search ?? '');
const showDeleteDialog = ref(false);
const categoryToDelete = ref<CategoryItem | null>(null);


const applyFilter = useDebounceFn(() => {
    router.get(
        route('master.room-categories.index'),
        { search: search.value },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}, 300);

watch(search, applyFilter);

const confirmDelete = (cat: CategoryItem) => {
    categoryToDelete.value = cat;
    showDeleteDialog.value = true;
};

const deleteCategory = () => {
    if (!categoryToDelete.value) return;
    router.delete(route('master.room-categories.destroy', categoryToDelete.value.id), {
        onSuccess: () => {
            toastSuccess('Kategori berhasil dihapus.');
        },
        onError: () => {
            toastError('Gagal menghapus kategori.');
        },
        onFinish: () => {
            showDeleteDialog.value = false;
            categoryToDelete.value = null;
        },
    });
};
</script>

<template>
    <Head title="Room Category" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-muted/40 py-10">
            <div class="max-w-7xl mx-auto px-6 space-y-8">

            <!-- Header -->
            <div class="flex flex-col justify-between gap-4">
                <div class="flex flex-row justify-between item-center">
                    <Heading title="Manage Room Categories"
                            :description="`Kelola kategori kamar. Total: ${categories.total}`" />
                    <div>
                        <Link :href="route('master.room-categories.create')"
                            class="cursor-pointer inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-medium text-primary-foreground shadow transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50">
                            Add Category
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Search -->
            <div class="relative max-w-sm">
                <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                <Input v-model="search" placeholder="Cari kategori..." class="pl-9" />
            </div>

            <!-- Table -->
            <div class="overflow-hidden rounded-lg border">
                <table class="w-full text-sm">
                    <thead class="border-b bg-muted/50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">Nama</th>
                            <th class="px-4 py-3 text-left font-medium">Kost</th>
                            <th class="px-4 py-3 text-left font-medium">Deskripsi</th>
                            <th class="px-4 py-3 text-center font-medium">Images</th>
                            <th class="px-4 py-3 text-center font-medium">Details</th>
                            <th class="px-4 py-3 text-center font-medium">Rooms</th>
                            <th class="px-4 py-3 text-right font-medium">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="categories.data.length === 0">
                            <td colspan="7" class="px-4 py-8 text-center text-muted-foreground">Tidak ada data kategori.</td>
                        </tr>
                        <tr v-for="cat in categories.data" :key="cat.id" class="border-b last:border-0">
                            <td class="px-4 py-3 font-medium">{{ cat.name }}</td>
                            <td class="px-4 py-3">{{ cat.kost_name }}</td>
                            <td class="max-w-xs truncate px-4 py-3 text-muted-foreground">{{ cat.description ?? '-' }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center gap-1 text-xs text-muted-foreground">
                                    <Image class="h-3 w-3" />
                                    {{ cat.images_count }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center gap-1 text-xs text-muted-foreground">
                                    <List class="h-3 w-3" />
                                    {{ cat.details_count }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="text-xs text-muted-foreground">{{ cat.rooms_count }}</span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <Button variant="ghost" size="icon" class="h-8 w-8" as-child>
                                        <Link :href="route('master.room-categories.edit', cat.id)">
                                            <Pencil class="h-4 w-4" />
                                        </Link>
                                    </Button>
                                    <Button variant="ghost" size="icon" class="h-8 w-8 text-destructive" @click="confirmDelete(cat)">
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <Pagination :meta="categories" />
            </div>
        </div>

        <!-- Delete confirmation dialog -->
        <Dialog v-model:open="showDeleteDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Hapus Kategori</DialogTitle>
                    <DialogDescription>
                        Apakah Anda yakin ingin menghapus kategori <strong>{{ categoryToDelete?.name }}</strong>? Semua data terkait (images, details, rooms) akan ikut terhapus.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <DialogClose as-child>
                        <Button variant="secondary">Batal</Button>
                    </DialogClose>
                    <Button variant="destructive" @click="deleteCategory">Hapus</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
