<script setup lang="ts">
import Pagination from '@/components/Pagination.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { Eye, Search } from 'lucide-vue-next';
import { ref, watch } from 'vue';

interface TransactionItem {
    id: number;
    order_id: string;
    tenant_name: string;
    room_number: string;
    payment_type: string;
    transaction_type: string;
    total_price: number;
    status: string;
    created_at: string;
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
    transactions: Paginator<TransactionItem>;
    filters: { search: string; status: string; payment_type: string };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Transactions', href: '/transactions' },
];

const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? '');
const paymentType = ref(props.filters.payment_type ?? '');

const applyFilter = useDebounceFn(() => {
    router.get(
        route('transactions.index'),
        {
            search: search.value || undefined,
            status: status.value || undefined,
            payment_type: paymentType.value || undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}, 300);

watch([search, status, paymentType], applyFilter);

const statusBadge = (s: string) => {
    const map: Record<string, string> = {
        pending: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
        success: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
        failed: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    };
    return map[s] ?? 'bg-gray-100 text-gray-700';
};

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value);
};
</script>

<template>
    <Head title="Transactions" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-muted/40 py-10">
            <div class="max-w-7xl mx-auto px-6 space-y-8">

            <!-- Header -->
            <div class="flex flex-col justify-between gap-4">
                <div class="flex flex-row justify-between item-center">

                    <Heading title="Transactions"
                            :description="`Riwayat transaksi pembayaran. Total: ${transactions.total}`" />

                </div>
            </div>

            <!-- Filters -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <div class="relative max-w-sm flex-1">
                    <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input v-model="search" placeholder="Cari order ID atau tenant..." class="pl-9" />
                </div>
                <select
                    v-model="status"
                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 sm:w-40"
                >
                    <option value="">Semua Status</option>
                    <option value="pending">Pending</option>
                    <option value="success">Success</option>
                    <option value="failed">Failed</option>
                </select>
                <select
                    v-model="paymentType"
                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 sm:w-40"
                >
                    <option value="">Semua Tipe</option>
                    <option value="manual">Manual</option>
                    <option value="midtrans">Midtrans</option>
                </select>
            </div>

            <!-- Table -->
            <div class="overflow-hidden rounded-lg border">
                <table class="w-full text-sm">
                    <thead class="border-b bg-muted/50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">Order ID</th>
                            <th class="px-4 py-3 text-left font-medium">Tenant</th>
                            <th class="px-4 py-3 text-left font-medium">Room</th>
                            <th class="px-4 py-3 text-left font-medium">Metode</th>
                            <th class="px-4 py-3 text-left font-medium">Tipe</th>
                            <th class="px-4 py-3 text-left font-medium">Total</th>
                            <th class="px-4 py-3 text-left font-medium">Status</th>
                            <th class="px-4 py-3 text-left font-medium">Tanggal</th>
                            <th class="px-4 py-3 text-right font-medium">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="transactions.data.length === 0">
                            <td colspan="8" class="px-4 py-8 text-center text-muted-foreground">Tidak ada data transaksi.</td>
                        </tr>
                        <tr v-for="trx in transactions.data" :key="trx.id" class="border-b last:border-0">
                            <td class="px-4 py-3 font-mono text-xs font-medium">{{ trx.order_id }}</td>
                            <td class="px-4 py-3">{{ trx.tenant_name }}</td>
                            <td class="px-4 py-3">{{ trx.room_number }}</td>
                            <td class="px-4 py-3 capitalize">{{ trx.payment_type }}</td>
                            <td class="px-4 py-3 capitalize">{{ trx.transaction_type ? trx.transaction_type.replace('_', ' ') : 'Full Payment' }}</td>
                            <td class="px-4 py-3">{{ formatCurrency(trx.total_price) }}</td>
                            <td class="px-4 py-3">
                                <span :class="statusBadge(trx.status)" class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium capitalize">
                                    {{ trx.status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs">{{ trx.created_at }}</td>
                            <td class="px-4 py-3 text-right">
                                <Button variant="ghost" size="icon" class="h-8 w-8" as-child title="Detail">
                                    <Link :href="route('transactions.show', trx.id)">
                                        <Eye class="h-4 w-4" />
                                    </Link>
                                </Button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <Pagination :meta="transactions" />
            </div>
        </div>
    </AppLayout>
</template>
