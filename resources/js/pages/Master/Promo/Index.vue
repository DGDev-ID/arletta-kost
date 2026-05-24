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
import { Pencil, Search, Trash2, Tag } from 'lucide-vue-next';
import { ref, watch } from 'vue';

const { success: toastSuccess, error: toastError } = useToast();

interface PromoItem {
    id: number;
    name: string;
    code: string;
    description: string | null;
    type: 'discount_percent' | 'discount_amount' | 'bonus_days';
    value: number;
    min_purchase: number | null;
    max_usage: number | null;
    usage_count: number;
    is_active: boolean;
    valid_from: string | null;
    valid_until: string | null;
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
    promos: Paginator<PromoItem>;
    filters: { search: string };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Promo', href: '/master/promos' },
];

const search = ref(props.filters.search ?? '');
const showDeleteDialog = ref(false);
const promoToDelete = ref<PromoItem | null>(null);

const applyFilter = useDebounceFn(() => {
    router.get(
        route('master.promos.index'),
        { search: search.value },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}, 300);

watch(search, applyFilter);

const confirmDelete = (promo: PromoItem) => {
    promoToDelete.value = promo;
    showDeleteDialog.value = true;
};

const deletePromo = () => {
    if (!promoToDelete.value) return;
    router.delete(route('master.promos.destroy', promoToDelete.value.id), {
        onSuccess: () => toastSuccess('Promo berhasil dihapus.'),
        onError: () => toastError('Gagal menghapus promo.'),
        onFinish: () => {
            showDeleteDialog.value = false;
            promoToDelete.value = null;
        },
    });
};

const typeLabel = (type: string) => {
    const map: Record<string, string> = {
        discount_percent: 'Diskon %',
        discount_amount:  'Diskon Rp',
        bonus_days:       'Bonus Hari',
    };
    return map[type] ?? type;
};

const typeBadge = (type: string) => {
    const map: Record<string, string> = {
        discount_percent: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        discount_amount:  'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
        bonus_days:       'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
    };
    return map[type] ?? 'bg-gray-100 text-gray-700';
};

const valueDisplay = (promo: PromoItem) => {
    if (promo.type === 'discount_percent') return `${promo.value}%`;
    if (promo.type === 'discount_amount')
        return `Rp ${new Intl.NumberFormat('id-ID').format(promo.value)}`;
    return `${promo.value} hari`;
};

const formatDate = (d: string | null) => {
    if (!d) return '-';
    const parts = d.split('-');
    if (parts.length === 3) return `${parts[2]}/${parts[1]}/${parts[0]}`;
    return d;
};

const formatCurrency = (v: number | null) =>
    v !== null ? `Rp ${new Intl.NumberFormat('id-ID').format(v)}` : '-';
</script>

<template>
    <Head title="Promo" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-muted/40 py-10">
            <div class="max-w-7xl mx-auto px-6 space-y-8">

                <!-- Header -->
                <div class="flex flex-col justify-between gap-4">
                    <div class="flex flex-row justify-between items-center">
                        <Heading title="Manage Promo"
                                 :description="`Kelola kode promo umum. Total: ${promos.total}`" />
                        <Link :href="route('master.promos.create')"
                              class="cursor-pointer inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-medium text-primary-foreground shadow transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50">
                            <Tag class="h-4 w-4" />
                            Tambah Promo
                        </Link>
                    </div>
                </div>

                <!-- Search -->
                <div class="relative max-w-sm">
                    <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input v-model="search" placeholder="Cari nama / kode promo..." class="pl-9" />
                </div>

                <!-- Table -->
                <div class="overflow-hidden rounded-lg border">
                    <table class="w-full text-sm">
                        <thead class="border-b bg-muted/50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium">Nama</th>
                                <th class="px-4 py-3 text-left font-medium">Kode</th>
                                <th class="px-4 py-3 text-left font-medium">Tipe</th>
                                <th class="px-4 py-3 text-left font-medium">Nilai</th>
                                <th class="px-4 py-3 text-left font-medium">Min. Pembelian</th>
                                <th class="px-4 py-3 text-center font-medium">Kuota</th>
                                <th class="px-4 py-3 text-center font-medium">Berlaku</th>
                                <th class="px-4 py-3 text-center font-medium">Status</th>
                                <th class="px-4 py-3 text-right font-medium">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="promos.data.length === 0">
                                <td colspan="9" class="px-4 py-8 text-center text-muted-foreground">
                                    Tidak ada data promo.
                                </td>
                            </tr>
                            <tr v-for="promo in promos.data" :key="promo.id" class="border-b last:border-0">
                                <td class="px-4 py-3 font-medium">
                                    {{ promo.name }}
                                    <p v-if="promo.description" class="text-xs text-muted-foreground truncate max-w-[160px]">
                                        {{ promo.description }}
                                    </p>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="font-mono text-xs font-semibold tracking-widest bg-muted px-2 py-0.5 rounded">
                                        {{ promo.code }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span :class="typeBadge(promo.type)"
                                          class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium">
                                        {{ typeLabel(promo.type) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 font-medium">{{ valueDisplay(promo) }}</td>
                                <td class="px-4 py-3 text-muted-foreground text-xs">{{ formatCurrency(promo.min_purchase) }}</td>
                                <td class="px-4 py-3 text-center text-xs text-muted-foreground">
                                    <span v-if="promo.max_usage">
                                        {{ promo.usage_count }} / {{ promo.max_usage }}
                                    </span>
                                    <span v-else>{{ promo.usage_count }} / ∞</span>
                                </td>
                                <td class="px-4 py-3 text-center text-xs text-muted-foreground">
                                    {{ formatDate(promo.valid_from) }} –<br />{{ formatDate(promo.valid_until) }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span :class="promo.is_active
                                        ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                                        : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'"
                                          class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium">
                                        {{ promo.is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <Button variant="ghost" size="icon" class="h-8 w-8" as-child>
                                            <Link :href="route('master.promos.edit', promo.id)">
                                                <Pencil class="h-4 w-4" />
                                            </Link>
                                        </Button>
                                        <Button variant="ghost" size="icon" class="h-8 w-8 text-destructive"
                                                @click="confirmDelete(promo)">
                                            <Trash2 class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <Pagination :meta="promos" />
            </div>
        </div>

        <!-- Delete confirmation dialog -->
        <Dialog v-model:open="showDeleteDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Hapus Promo</DialogTitle>
                    <DialogDescription>
                        Apakah Anda yakin ingin menghapus promo
                        <strong>{{ promoToDelete?.name }}</strong>
                        (kode: <strong>{{ promoToDelete?.code }}</strong>)?
                        Tindakan ini tidak dapat dibatalkan.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <DialogClose as-child>
                        <Button variant="secondary">Batal</Button>
                    </DialogClose>
                    <Button variant="destructive" @click="deletePromo">Hapus</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
