<script setup lang="ts">
import Pagination from '@/components/Pagination.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';

interface BillItem {
    id: number;
    tenant_name: string;
    tenant_phone: string;
    room_number: string;
    total_price: number;
    start_date: string | null;
    due_date: string | null;
    status: string;
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
    refunds: Paginator<BillItem>;
    requests: Paginator<BillItem>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Transactions', href: '/transactions' },
    { title: 'Refund Request', href: '#' },
];

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value);
};

const approve = (id: number) => {
    if (!confirm('Setujui refund ini?')) return;
    router.patch(route('transactions.refund-requests.approve', id), {}, { preserveScroll: true });
};

const reject = (id: number) => {
    if (!confirm('Tolak refund ini? Status akan diubah menjadi paid.')) return;
    router.patch(route('transactions.refund-requests.reject', id), {}, { preserveScroll: true });
};
</script>

<template>
    <Head title="Refund Request" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-muted/40 py-10">
            <div class="max-w-7xl mx-auto px-6 space-y-8">

                <Heading title="Refunds" description="Daftar bill yang sudah berstatus refund." />

                <div class="overflow-hidden rounded-lg border">
                    <table class="w-full text-sm">
                        <thead class="border-b bg-muted/50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium">#</th>
                                <th class="px-4 py-3 text-left font-medium">Tenant</th>
                                <th class="px-4 py-3 text-left font-medium">Phone</th>
                                <th class="px-4 py-3 text-left font-medium">Room</th>
                                <th class="px-4 py-3 text-left font-medium">Total</th>
                                <th class="px-4 py-3 text-left font-medium">Start</th>
                                <th class="px-4 py-3 text-left font-medium">Due</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="refunds.data.length === 0">
                                <td colspan="7" class="px-4 py-8 text-center text-muted-foreground">Tidak ada data refund.</td>
                            </tr>
                            <tr v-for="(b, idx) in refunds.data" :key="b.id" class="border-b last:border-0">
                                <td class="px-4 py-3 font-medium">{{ (refunds.from ?? 0) + idx }}</td>
                                <td class="px-4 py-3">{{ b.tenant_name }}</td>
                                <td class="px-4 py-3">{{ b.tenant_phone }}</td>
                                <td class="px-4 py-3">{{ b.room_number }}</td>
                                <td class="px-4 py-3">{{ formatCurrency(b.total_price) }}</td>
                                <td class="px-4 py-3 text-xs">{{ b.start_date ?? '-' }}</td>
                                <td class="px-4 py-3 text-xs">{{ b.due_date ?? '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <Pagination :meta="refunds" />

                <Heading title="Refund Requests" description="Permintaan refund yang menunggu keputusan." />

                <div class="overflow-hidden rounded-lg border">
                    <table class="w-full text-sm">
                        <thead class="border-b bg-muted/50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium">#</th>
                                <th class="px-4 py-3 text-left font-medium">Tenant</th>
                                <th class="px-4 py-3 text-left font-medium">Phone</th>
                                <th class="px-4 py-3 text-left font-medium">Room</th>
                                <th class="px-4 py-3 text-left font-medium">Total</th>
                                <th class="px-4 py-3 text-left font-medium">Start</th>
                                <th class="px-4 py-3 text-left font-medium">Due</th>
                                <th class="px-4 py-3 text-right font-medium">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="requests.data.length === 0">
                                <td colspan="8" class="px-4 py-8 text-center text-muted-foreground">Tidak ada request refund.</td>
                            </tr>
                            <tr v-for="(b, idx) in requests.data" :key="b.id" class="border-b last:border-0">
                                <td class="px-4 py-3 font-medium">{{ (requests.from ?? 0) + idx }}</td>
                                <td class="px-4 py-3">{{ b.tenant_name }}</td>
                                <td class="px-4 py-3">{{ b.tenant_phone }}</td>
                                <td class="px-4 py-3">{{ b.room_number }}</td>
                                <td class="px-4 py-3">{{ formatCurrency(b.total_price) }}</td>
                                <td class="px-4 py-3 text-xs">{{ b.start_date ?? '-' }}</td>
                                <td class="px-4 py-3 text-xs">{{ b.due_date ?? '-' }}</td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex justify-end gap-2">
                                        <Button size="sm" variant="ghost" @click="reject(b.id)">Not Approve</Button>
                                        <Button size="sm" @click="approve(b.id)">Approve</Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <Pagination :meta="requests" />

            </div>
        </div>
    </AppLayout>
</template>
