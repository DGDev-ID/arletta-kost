<script setup lang="ts">
import Pagination from '@/components/Pagination.vue';
import RoomCategoryModal from '@/components/RoomCategoryModal.vue';
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
import Heading from '@/components/Heading.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { useToast } from '@/composables/useToast';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { Pencil, Plus, Search, Settings, Trash2, List } from 'lucide-vue-next';
import { ref, watch } from 'vue';

const { success: toastSuccess, error: toastError } = useToast();

interface RoomItem {
    id: number;
    room_number: string;
    status: string;
    category_name: string;
    kost_name: string;
    kost_id: number;
    room_category_id: number;
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

interface CategoryItem {
    id: number;
    name: string;
    kost_name: string;
    kost_id: number;
}

interface KostItem {
    id: number;
    name: string;
}

const props = defineProps<{
    rooms: Paginator<RoomItem>;
    categories: CategoryItem[];
    kosts: KostItem[];
    filters: { search: string; kost_id: string | number };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Room', href: '/master/rooms' },
];

const search = ref(props.filters.search ?? '');
const selectedKostId = ref<number | ''>(props.filters.kost_id ? Number(props.filters.kost_id) : '');
const showCategoryModal = ref(false);
const showDeleteDialog = ref(false);
const roomToDelete = ref<RoomItem | null>(null);


const applyFilter = useDebounceFn(() => {
    router.get(
        route('master.rooms.index'),
        { search: search.value, kost_id: selectedKostId.value },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}, 300);

watch(search, applyFilter);
watch(selectedKostId, applyFilter);

const statusBadge = (status: string) => {
    const map: Record<string, string> = {
        available: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
        occupied: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
        maintenance: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
    };
    return map[status] ?? 'bg-gray-100 text-gray-700';
};

const confirmDelete = (room: RoomItem) => {
    roomToDelete.value = room;
    showDeleteDialog.value = true;
};

const deleteRoom = () => {
    if (!roomToDelete.value) return;
    router.delete(route('master.rooms.destroy', roomToDelete.value.id), {
        onSuccess: () => {
            toastSuccess('Room berhasil dihapus.');
        },
        onError: () => {
            toastError('Gagal menghapus room.');
        },
        onFinish: () => {
            showDeleteDialog.value = false;
            roomToDelete.value = null;
        },
    });
};

// Log now opens a dedicated page; no modal/fetch here.
</script>

<template>
    <Head title="Room" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-muted/40 py-10">
            <div class="max-w-7xl mx-auto px-6 space-y-4">

            <!-- Header -->
            <div class="flex flex-col justify-between gap-4">
                <div class="flex flex-row justify-between item-center">

                    <Heading title="Manage Rooms"
                            :description="`Kelola data room terdaftar. Total Rooms: ${rooms.total}`" />

                    <div>
                        <!-- <button
                            @click="showCategoryModal = true"
                            class="cursor-pointer inline-flex items-center justify-center gap-2 rounded-xl border border-input bg-background px-4 py-2.5 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                            <Settings class="h-4 w-4" />
                            Manage Categories
                        </button> -->
                        <Link :href="route('master.rooms.create')"
                            class="cursor-pointer inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-medium text-primary-foreground shadow transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50">
                            Add Room
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <div class="relative max-w-sm flex-1">
                    <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input v-model="search" placeholder="Cari room..." class="pl-9" />
                </div>
                <select
                    v-model="selectedKostId"
                    class="h-9 rounded-md border border-input bg-background px-3 text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
                >
                    <option value="">Semua Kost</option>
                    <option v-for="kost in props.kosts" :key="kost.id" :value="kost.id">{{ kost.name }}</option>
                </select>
            </div>

            <!-- Table -->
            <div class="overflow-hidden rounded-lg border">
                <table class="w-full text-sm">
                    <thead class="border-b bg-muted/50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">No. Kamar</th>
                            <th class="px-4 py-3 text-left font-medium">Kategori</th>
                            <th class="px-4 py-3 text-left font-medium">Kost</th>
                            <th class="px-4 py-3 text-left font-medium">Status</th>
                            <th class="px-4 py-3 text-right font-medium">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="rooms.data.length === 0">
                            <td colspan="5" class="px-4 py-8 text-center text-muted-foreground">Tidak ada data room.</td>
                        </tr>
                        <tr v-for="room in rooms.data" :key="room.id" class="border-b last:border-0">
                            <td class="px-4 py-3 font-medium">{{ room.room_number }}</td>
                            <td class="px-4 py-3">{{ room.category_name }}</td>
                            <td class="px-4 py-3">{{ room.kost_name }}</td>
                            <td class="px-4 py-3">
                                <span :class="statusBadge(room.status)" class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium capitalize">
                                    {{ room.status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <Button variant="ghost" size="icon" class="h-8 w-8" as-child>
                                        <Link :href="route('master.rooms.bills.log', room.id)">
                                            <List class="h-4 w-4" />
                                        </Link>
                                    </Button>
                                    <Button variant="ghost" size="icon" class="h-8 w-8" as-child>
                                        <Link :href="route('master.rooms.edit', room.id)">
                                            <Pencil class="h-4 w-4" />
                                        </Link>
                                    </Button>
                                    <Button variant="ghost" size="icon" class="h-8 w-8 text-destructive" @click="confirmDelete(room)">
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <Pagination :meta="rooms" />
            </div>
        </div>

        <!-- Delete confirmation dialog -->
        <Dialog v-model:open="showDeleteDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Hapus Room</DialogTitle>
                    <DialogDescription>
                        Apakah Anda yakin ingin menghapus room <strong>{{ roomToDelete?.room_number }}</strong>? Tindakan ini tidak dapat dibatalkan.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <DialogClose as-child>
                        <Button variant="secondary">Batal</Button>
                    </DialogClose>
                    <Button variant="destructive" @click="deleteRoom">Hapus</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Paid bills now shown in dedicated page -->

        <!-- Room Category Modal -->
        <RoomCategoryModal v-model:open="showCategoryModal" />
    </AppLayout>
</template>
