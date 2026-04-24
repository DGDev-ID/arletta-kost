<script setup lang="ts">
import Pagination from '@/components/Pagination.vue';
import Heading from '@/components/Heading.vue';
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
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { CheckCircle, XCircle } from 'lucide-vue-next';
import { ref } from 'vue';

interface BillItem {
    id: number;
    tenant_name: string;
    tenant_phone: string;
    room_number: string;
    total_price: number;
    refund_amount: number;
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

const confirmDialog = ref({
    show: false,
    billId: null as number | null,
    action: '' as 'approve' | 'reject' | '',
});

const confirmAction = (billId: number, action: 'approve' | 'reject') => {
    confirmDialog.value = {
        show: true,
        billId,
        action,
    };
};

const proceedAction = () => {
    if (!confirmDialog.value.billId) return;

    const routeName = confirmDialog.value.action === 'approve' 
        ? 'transactions.refund-requests.approve' 
        : 'transactions.refund-requests.reject';
    
    router.patch(route(routeName, confirmDialog.value.billId), {}, {
        preserveScroll: true,
        onFinish: () => {
            confirmDialog.value.show = false;
        }
    });
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
                                <th class="px-4 py-3 text-left font-medium">Start</th>
                                <th class="px-4 py-3 text-left font-medium">Due</th>
                                <th class="px-4 py-3 text-left font-medium">Total</th>
                                <th class="px-4 py-3 text-left font-medium text-orange-600 dark:text-orange-400">Refund Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="refunds.data.length === 0">
                                <td colspan="8" class="px-4 py-8 text-center text-muted-foreground">Tidak ada data refund.</td>
                            </tr>
                            <tr v-for="(b, idx) in refunds.data" :key="b.id" class="border-b last:border-0">
                                <td class="px-4 py-3 font-medium">{{ (refunds.from ?? 0) + idx }}</td>
                                <td class="px-4 py-3">{{ b.tenant_name }}</td>
                                <td class="px-4 py-3">{{ b.tenant_phone }}</td>
                                <td class="px-4 py-3">{{ b.room_number }}</td>
                                <td class="px-4 py-3 text-xs">{{ b.start_date ?? '-' }}</td>
                                <td class="px-4 py-3 text-xs">{{ b.due_date ?? '-' }}</td>
                                <td class="px-4 py-3">{{ formatCurrency(b.total_price) }}</td>
                                <td class="px-4 py-3 font-medium text-orange-600 dark:text-orange-400">{{ formatCurrency(b.refund_amount) }}</td>
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
                                <th class="px-4 py-3 text-left font-medium">Start</th>
                                <th class="px-4 py-3 text-left font-medium">Due</th>
                                <th class="px-4 py-3 text-left font-medium">Total</th>
                                <th class="px-4 py-3 text-left font-medium text-orange-600 dark:text-orange-400">Refund Amount</th>
                                <th class="px-4 py-3 text-right font-medium">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="requests.data.length === 0">
                                <td colspan="9" class="px-4 py-8 text-center text-muted-foreground">Tidak ada request refund.</td>
                            </tr>
                            <tr v-for="(b, idx) in requests.data" :key="b.id" class="border-b last:border-0">
                                <td class="px-4 py-3 font-medium">{{ (requests.from ?? 0) + idx }}</td>
                                <td class="px-4 py-3">{{ b.tenant_name }}</td>
                                <td class="px-4 py-3">{{ b.tenant_phone }}</td>
                                <td class="px-4 py-3">{{ b.room_number }}</td>
                                <td class="px-4 py-3 text-xs">{{ b.start_date ?? '-' }}</td>
                                <td class="px-4 py-3 text-xs">{{ b.due_date ?? '-' }}</td>
                                <td class="px-4 py-3">{{ formatCurrency(b.total_price) }}</td>
                                <td class="px-4 py-3 font-medium text-orange-600 dark:text-orange-400">{{ formatCurrency(b.refund_amount) }}</td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex justify-end gap-1">
                                        <Button variant="outline" size="sm" class="h-7 text-xs text-green-700" @click="confirmAction(b.id, 'approve')">
                                            <CheckCircle class="mr-1 h-3 w-3" />
                                            Make Success
                                        </Button>
                                        <Button variant="outline" size="sm" class="h-7 text-xs text-red-700" @click="confirmAction(b.id, 'reject')">
                                            <XCircle class="mr-1 h-3 w-3" />
                                            Make Failed
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <Pagination :meta="requests" />

            </div>
        </div>

        <!-- Confirm Action Dialog -->
        <Dialog v-model:open="confirmDialog.show">
            <DialogContent class="sm:max-w-sm">
                <DialogHeader>
                    <DialogTitle>Konfirmasi Aksi</DialogTitle>
                    <DialogDescription>
                        Apakah Anda yakin ingin <strong :class="confirmDialog.action === 'approve' ? 'text-green-600' : 'text-red-600'">{{ confirmDialog.action === 'approve' ? 'menyetujui' : 'menolak' }}</strong> permintaan refund ini?
                    </DialogDescription>
                </DialogHeader>

                <DialogFooter class="mt-4">
                    <DialogClose as-child>
                        <Button variant="secondary">Batal</Button>
                    </DialogClose>
                    <Button :variant="confirmDialog.action === 'approve' ? 'default' : 'destructive'" @click="proceedAction">
                        Ya, Yakin
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
