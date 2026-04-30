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
import { Pencil, Search, Trash2 } from 'lucide-vue-next';
import { ref, watch } from 'vue';

const { success: toastSuccess, error: toastError } = useToast();

interface UserItem {
    id: number;
    name: string;
    email: string;
    roles: string[];
    kosts: string[];
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
    users: Paginator<UserItem>;
    filters: { search: string };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Users', href: '/management/users' },
];

const search = ref(props.filters.search ?? '');
const showDeleteDialog = ref(false);
const userToDelete = ref<UserItem | null>(null);


const applyFilter = useDebounceFn(() => {
    router.get(
        route('management.users.index'),
        { search: search.value },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}, 300);

watch(search, applyFilter);

const confirmDelete = (user: UserItem) => {
    userToDelete.value = user;
    showDeleteDialog.value = true;
};

const deleteUser = () => {
    if (!userToDelete.value) return;
    router.delete(route('management.users.destroy', userToDelete.value.id), {
        onSuccess: () => {
            toastSuccess('User berhasil dihapus.');
        },
        onError: () => {
            toastError('Gagal menghapus user.');
        },
        onFinish: () => {
            showDeleteDialog.value = false;
            userToDelete.value = null;
        },
    });
};

const roleBadge = (role: string) => {
    const map: Record<string, string> = {
        superadmin: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
        admin: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        owner: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    };
    return map[role] ?? 'bg-gray-100 text-gray-700';
};
</script>

<template>
    <Head title="Users" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-muted/40 py-10">
            <div class="max-w-7xl mx-auto px-6 space-y-8">

            <!-- Header -->
            <div class="flex flex-col justify-between gap-4">
                <div class="flex flex-row justify-between item-center">
                    <Heading title="Manage Users"
                            :description="`Kelola data user terdaftar. Total: ${users.total}`" />
                    <div>
                        <Link :href="route('management.users.create')"
                            class="cursor-pointer inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-medium text-primary-foreground shadow transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50">
                            Add User
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Search -->
            <div class="relative max-w-sm">
                <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                <Input v-model="search" placeholder="Cari user..." class="pl-9" />
            </div>

            <!-- Table -->
            <div class="overflow-hidden rounded-lg border">
                <table class="w-full text-sm">
                    <thead class="border-b bg-muted/50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">Nama</th>
                            <th class="px-4 py-3 text-left font-medium">Email</th>
                            <th class="px-4 py-3 text-left font-medium">Role</th>
                            <th class="px-4 py-3 text-left font-medium">Kost</th>
                            <th class="px-4 py-3 text-right font-medium">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="users.data.length === 0">
                            <td colspan="5" class="px-4 py-8 text-center text-muted-foreground">Tidak ada data user.</td>
                        </tr>
                        <tr v-for="user in users.data" :key="user.id" class="border-b last:border-0">
                            <td class="px-4 py-3 font-medium">{{ user.name }}</td>
                            <td class="px-4 py-3">{{ user.email }}</td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-1">
                                    <span
                                        v-for="role in user.roles"
                                        :key="role"
                                        :class="roleBadge(role)"
                                        class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium capitalize"
                                    >
                                        {{ role }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span v-if="user.kosts.length > 0" class="text-xs">{{ user.kosts.join(', ') }}</span>
                                <span v-else class="text-xs text-muted-foreground">-</span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <Button variant="ghost" size="icon" class="h-8 w-8" as-child>
                                        <Link :href="route('management.users.edit', user.id)">
                                            <Pencil class="h-4 w-4" />
                                        </Link>
                                    </Button>
                                    <Button variant="ghost" size="icon" class="h-8 w-8 text-destructive" @click="confirmDelete(user)">
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <Pagination :meta="users" />
            </div>
        </div>

        <!-- Delete confirmation dialog -->
        <Dialog v-model:open="showDeleteDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Hapus User</DialogTitle>
                    <DialogDescription>
                        Apakah Anda yakin ingin menghapus user <strong>{{ userToDelete?.name }}</strong>?
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <DialogClose as-child>
                        <Button variant="secondary">Batal</Button>
                    </DialogClose>
                    <Button variant="destructive" @click="deleteUser">Hapus</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
