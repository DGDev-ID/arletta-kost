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
import { CheckCircle, XCircle, Eye } from 'lucide-vue-next';
import { ref } from 'vue';

interface BillItem {
    id: number;
    tenant_id: number;
    tenant_name: string;
    tenant_phone: string;
    room_number: string;
    total_price: number;
    start_date: string | null;
    due_date: string | null;
    status: string;
    pending_transaction_id?: number;
    payment_type?: string | null;
    midtrans_method?: string | null;
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
    pendingBills: Paginator<BillItem>;
    historyBills: Paginator<BillItem>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Transactions', href: '/transactions' },
    { title: 'Bill Approval', href: '#' },
];

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value);
};

const confirmDialog = ref({
    show: false,
    transactionId: null as number | null,
    action: '' as 'success' | 'failed' | '',
});

const confirmAction = (transactionId: number, action: 'success' | 'failed') => {
    confirmDialog.value = {
        show: true,
        transactionId,
        action,
    };
};

const proceedAction = () => {
    if (!confirmDialog.value.transactionId) return;

    const routeName = confirmDialog.value.action === 'success' 
        ? 'management.bills.make-success' 
        : 'management.bills.make-failed';
    
    router.patch(route(routeName, confirmDialog.value.transactionId), {}, {
        preserveScroll: true,
        onFinish: () => {
            confirmDialog.value.show = false;
        }
    });
};

const showBill = (billId: number) => {
    router.visit(route('transactions.bill-approval.show', billId));
};
</script>

<template>
    <Head title="Bill Approval" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-muted/40 py-10">
            <div class="max-w-7xl mx-auto px-6 space-y-8">

                <Heading title="Pending Manual Bills" description="Daftar tagihan manual yang menunggu konfirmasi pembayaran." />

                <div class="overflow-hidden rounded-lg border">
                    <table class="w-full text-sm">
                        <thead class="border-b bg-muted/50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium">#</th>
                                <th class="px-4 py-3 text-left font-medium">Tenant</th>
                                <th class="px-4 py-3 text-left font-medium">Phone</th>
                                <th class="px-4 py-3 text-left font-medium">Room</th>
                                <th class="px-4 py-3 text-left font-medium">Metode</th>
                                <th class="px-4 py-3 text-left font-medium">Start</th>
                                <th class="px-4 py-3 text-left font-medium">Due</th>
                                <th class="px-4 py-3 text-left font-medium">Total</th>
                                <th class="px-4 py-3 text-center font-medium">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="pendingBills.data.length === 0">
                                <td colspan="9" class="px-4 py-8 text-center text-muted-foreground">Tidak ada pending bill.</td>
                            </tr>
                            <tr v-for="(b, idx) in pendingBills.data" :key="b.id" class="border-b last:border-0">
                                <td class="px-4 py-3 font-medium">{{ (pendingBills.from ?? 0) + idx }}</td>
                                <td class="px-4 py-3">{{ b.tenant_name }}</td>
                                <td class="px-4 py-3">{{ b.tenant_phone }}</td>
                                <td class="px-4 py-3">{{ b.room_number }}</td>
                                <td class="px-4 py-3">
                                    <template v-if="b.payment_type === 'manual' || !b.payment_type">
                                        <span class="inline-flex items-center rounded-full bg-blue-100 dark:bg-blue-900/30 px-2.5 py-0.5 text-xs font-medium text-blue-700 dark:text-blue-400">Manual</span>
                                    </template>
                                    <template v-else>
                                        <span class="inline-flex items-center rounded-full bg-purple-100 dark:bg-purple-900/30 px-2.5 py-0.5 text-xs font-medium text-purple-700 dark:text-purple-400 capitalize">
                                            {{ b.midtrans_method ?? b.payment_type }}
                                        </span>
                                    </template>
                                </td>
                                <td class="px-4 py-3 text-xs">{{ b.start_date ?? '-' }}</td>
                                <td class="px-4 py-3 text-xs">{{ b.due_date ?? '-' }}</td>
                                <td class="px-4 py-3">{{ formatCurrency(b.total_price) }}</td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex justify-end gap-1">
                                        <Button variant="outline" size="sm" class="h-7 text-xs" @click="showBill(b.id)">
                                            <Eye class="mr-1 h-3 w-3" />
                                            Show
                                        </Button>
                                        <Button v-if="b.pending_transaction_id" variant="outline" size="sm" class="h-7 text-xs text-green-700" @click="confirmAction(b.pending_transaction_id, 'success')">
                                            <CheckCircle class="mr-1 h-3 w-3" />
                                            Make Success
                                        </Button>
                                        <Button v-if="b.pending_transaction_id" variant="outline" size="sm" class="h-7 text-xs text-red-700" @click="confirmAction(b.pending_transaction_id, 'failed')">
                                            <XCircle class="mr-1 h-3 w-3" />
                                            Make Failed
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <Pagination :meta="pendingBills" />

                <Heading title="History Bills" description="Riwayat tagihan yang sudah sukses dibayar atau dibatalkan." />

                <div class="overflow-hidden rounded-lg border">
                    <table class="w-full text-sm">
                        <thead class="border-b bg-muted/50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium">#</th>
                                <th class="px-4 py-3 text-left font-medium">Tenant</th>
                                <th class="px-4 py-3 text-left font-medium">Phone</th>
                                <th class="px-4 py-3 text-left font-medium">Room</th>
                                <th class="px-4 py-3 text-left font-medium">Metode</th>
                                <th class="px-4 py-3 text-left font-medium">Start</th>
                                <th class="px-4 py-3 text-left font-medium">Due</th>
                                <th class="px-4 py-3 text-left font-medium">Total</th>
                                <th class="px-4 py-3 text-left font-medium">Status</th>
                                <th class="px-4 py-3 text-center font-medium">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="historyBills.data.length === 0">
                                <td colspan="10" class="px-4 py-8 text-center text-muted-foreground">Tidak ada history bill.</td>
                            </tr>
                            <tr v-for="(b, idx) in historyBills.data" :key="b.id" class="border-b last:border-0">
                                <td class="px-4 py-3 font-medium">{{ (historyBills.from ?? 0) + idx }}</td>
                                <td class="px-4 py-3">{{ b.tenant_name }}</td>
                                <td class="px-4 py-3">{{ b.tenant_phone }}</td>
                                <td class="px-4 py-3">{{ b.room_number }}</td>
                                <td class="px-4 py-3">
                                    <template v-if="b.payment_type === 'manual' || !b.payment_type">
                                        <span class="inline-flex items-center rounded-full bg-blue-100 dark:bg-blue-900/30 px-2.5 py-0.5 text-xs font-medium text-blue-700 dark:text-blue-400">Manual</span>
                                    </template>
                                    <template v-else>
                                        <span class="inline-flex items-center rounded-full bg-purple-100 dark:bg-purple-900/30 px-2.5 py-0.5 text-xs font-medium text-purple-700 dark:text-purple-400 capitalize">
                                            {{ b.midtrans_method ?? b.payment_type }}
                                        </span>
                                    </template>
                                </td>
                                <td class="px-4 py-3 text-xs">{{ b.start_date ?? '-' }}</td>
                                <td class="px-4 py-3 text-xs">{{ b.due_date ?? '-' }}</td>
                                <td class="px-4 py-3">{{ formatCurrency(b.total_price) }}</td>
                                <td class="px-4 py-3">
                                    <span
                                        class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium capitalize"
                                        :class="{
                                            'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400': b.status === 'paid',
                                            'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400': b.status === 'cancelled',
                                            'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300': b.status === 'checked_out',
                                        }"
                                    >
                                        {{ b.status === 'checked_out' ? 'Checked Out' : b.status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex justify-end gap-1">
                                        <Button variant="outline" size="sm" class="h-7 text-xs" @click="showBill(b.id)">
                                            <Eye class="mr-1 h-3 w-3" />
                                            Show
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <Pagination :meta="historyBills" />

            </div>
        </div>

        <!-- Confirm Action Dialog -->
        <Dialog v-model:open="confirmDialog.show">
            <DialogContent class="sm:max-w-sm">
                <DialogHeader>
                    <DialogTitle>Konfirmasi Aksi</DialogTitle>
                    <DialogDescription>
                        Apakah Anda yakin ingin <strong :class="confirmDialog.action === 'success' ? 'text-green-600' : 'text-red-600'">{{ confirmDialog.action === 'success' ? 'menyetujui' : 'menolak' }}</strong> pembayaran ini?
                    </DialogDescription>
                </DialogHeader>

                <DialogFooter class="mt-4">
                    <DialogClose as-child>
                        <Button variant="secondary">Batal</Button>
                    </DialogClose>
                    <Button :variant="confirmDialog.action === 'success' ? 'default' : 'destructive'" @click="proceedAction">
                        Ya, Yakin
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
