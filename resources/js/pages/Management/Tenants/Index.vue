<script setup lang="ts">
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
import Heading from '@/components/Heading.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { Eye, FileText, FileTextIcon, Pencil, Plus, Search, Trash2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface TenantItem {
    id: number;
    name: string;
    email: string;
    phone_number: string;
    gender: string | null;
    start_date: string | null;
    end_date: string | null;
    rooms: { room_number: string; kost_name: string }[];
    bills_count: number;
    unpaid_bills: number;
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
    tenants: Paginator<TenantItem>;
    filters: { search: string };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Tenants', href: '/management/tenants' },
];

const search = ref(props.filters.search ?? '');
const showDeleteDialog = ref(false);
const tenantToDelete = ref<TenantItem | null>(null);

const flash = computed(() => {
    const page = router.page as any;
    return page?.props?.flash as { success?: string; error?: string } | undefined;
});

const applyFilter = useDebounceFn(() => {
    router.get(
        route('management.tenants.index'),
        { search: search.value },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}, 300);

watch(search, applyFilter);

const confirmDelete = (tenant: TenantItem) => {
    tenantToDelete.value = tenant;
    showDeleteDialog.value = true;
};

const deleteTenant = () => {
    if (!tenantToDelete.value) return;
    router.delete(route('management.tenants.destroy', tenantToDelete.value.id), {
        onFinish: () => {
            showDeleteDialog.value = false;
            tenantToDelete.value = null;
        },
    });
};
</script>

<template>
    <Head title="Tenants" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-muted/40 py-10">
            <div class="max-w-7xl mx-auto px-6 space-y-8">

            <!-- Header -->
            <div class="flex flex-col justify-between gap-4">

                <div class="flex flex-row justify-between items-center">
                    <Heading
                        title="Manage Tenants"
                        :description="`Kelola data tenant terdaftar. Total Tenants: ${tenants.total}`"
                    />

                    <Button as-child>
                        <Link :href="route('management.tenants.create')">
                            <Plus class="w-4 h-4 mr-1" />
                            Tambah Tenant
                        </Link>
                    </Button>
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
                <Input v-model="search" placeholder="Cari tenant..." class="pl-9" />
            </div>

            <!-- Table -->
            <div class="overflow-hidden rounded-lg border">
                <table class="w-full text-sm">
                    <thead class="border-b bg-muted/50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">Nama</th>
                            <th class="px-4 py-3 text-left font-medium">Email</th>
                            <th class="px-4 py-3 text-left font-medium">No. HP</th>
                            <th class="px-4 py-3 text-left font-medium w-64">Rooms</th>
                            <th class="px-4 py-3 text-left font-medium">Booking</th>
                            <th class="px-4 py-3 text-left font-medium">Bills</th>
                            <th class="px-4 py-3 text-center font-medium">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="tenants.data.length === 0">
                            <td colspan="8" class="px-4 py-8 text-center text-muted-foreground">Tidak ada data tenant.</td>
                        </tr>
                        <tr v-for="tenant in tenants.data" :key="tenant.id" class="border-b last:border-0">
                            <td class="px-4 py-3 font-medium">{{ tenant.name }}</td>
                            <td class="px-4 py-3">{{ tenant.email }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">{{ tenant.phone_number }}</td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-1">
                                    <span v-for="(room, idx) in tenant.rooms" :key="idx" class="inline-flex rounded-full bg-muted px-2 py-0.5 text-xs font-medium">
                                        {{ room.kost_name }} — {{ room.room_number }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div v-if="tenant.start_date || tenant.end_date" class="text-xs">
                                    <span>{{ tenant.start_date ?? '-' }}</span>
                                    <span class="mx-1 text-muted-foreground">→</span>
                                    <span>{{ tenant.end_date ?? '-' }}</span>
                                </div>
                                <span v-else class="text-xs text-muted-foreground">-</span>
                            </td>
                            <td class="px-4 py-3">
                               <div class="flex items-center gap-2 whitespace-nowrap">       
                                   <span class="text-xs">
                                       {{ tenant.bills_count }} total
                                       <span
                                       v-if="tenant.unpaid_bills > 0"
                                       class="ml-1 inline-flex rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700 dark:bg-red-900/30 dark:text-red-400"
                                    >
                                        {{ tenant.unpaid_bills }} unpaid
                                    </span>
                                </span>
                            </div>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <Button variant="outline" size="sm" class="h-7 text-xs" as-child title="Detail">
                                        <Link :href="route('management.tenants.show', tenant.id)">
                                            <FileTextIcon class="h-4 w-4" /> Bill
                                        </Link>
                                    </Button>
                                    <Button variant="ghost" size="icon" class="h-8 w-8" as-child title="Edit">
                                        <Link :href="route('management.tenants.edit', tenant.id)">
                                            <Pencil class="h-4 w-4" />
                                        </Link>
                                    </Button>
                                    <Button variant="ghost" size="icon" class="h-8 w-8 text-destructive" title="Hapus" @click="confirmDelete(tenant)">
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <Pagination :meta="tenants" />
            </div>
        </div>

        <!-- Delete confirmation dialog -->
        <Dialog v-model:open="showDeleteDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Hapus Tenant</DialogTitle>
                    <DialogDescription>
                        Apakah Anda yakin ingin menghapus tenant <strong>{{ tenantToDelete?.name }}</strong
                        >? Room akan kembali tersedia.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <DialogClose as-child>
                        <Button variant="secondary">Batal</Button>
                    </DialogClose>
                    <Button variant="destructive" @click="deleteTenant">Hapus</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
