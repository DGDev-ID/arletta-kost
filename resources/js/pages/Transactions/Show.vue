<script setup lang="ts">
import HeadingSmall from '@/components/HeadingSmall.vue';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, LoaderCircle, RotateCcw } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface TransactionData {
    id: number;
    order_id: string;
    payment_type: string;
    midtrans_method: string | null;
    transaction_fee: number;
    total_price: number;
    status: string;
    snap_token: string | null;
    created_at: string;
}

interface TenantData {
    name: string;
    phone_number: string;
}

interface BillData {
    id: number;
    total_price: number;
    start_date: string;
    due_date: string;
    status: string;
}

interface DetailItem {
    id: number;
    status: string;
    created_at: string;
}

interface RefundItem {
    id: number;
    amount: number;
    remark: string | null;
    created_at: string;
}

const props = defineProps<{
    transaction: TransactionData;
    tenant: TenantData;
    bill: BillData;
    details: DetailItem[];
    refunds: RefundItem[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Transactions', href: '/transactions' },
    { title: props.transaction.order_id, href: '#' },
];

const flash = computed(() => {
    const page = router.page as any;
    return page?.props?.flash as { success?: string; error?: string } | undefined;
});

// Refund dialog
const showRefundDialog = ref(false);
const refundForm = useForm({
    amount: props.transaction.total_price * 0.8,
    remark: '',
});

const submitRefund = () => {
    refundForm.post(route('transactions.refund', props.transaction.id), {
        preserveScroll: true,
        onSuccess: () => {
            showRefundDialog.value = false;
            refundForm.reset();
        },
    });
};

const statusBadge = (s: string) => {
    const map: Record<string, string> = {
        pending: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
        success: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
        failed: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
        paid: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
        unpaid: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
        cancelled: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-400',
    };
    return map[s] ?? 'bg-gray-100 text-gray-700';
};

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value);
};

const timelineIcon = (s: string) => {
    const map: Record<string, string> = {
        pending: 'bg-yellow-500',
        success: 'bg-green-500',
        failed: 'bg-red-500',
    };
    return map[s] ?? 'bg-gray-400';
};
</script>

<template>
    <Head :title="`Transaction ${transaction.order_id}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-muted/40 py-10">
            <div class="max-w-7xl mx-auto px-6 space-y-8">

            <!-- Header -->
            <div class="flex flex-col justify-between gap-4">
                <div class="flex flex-row justify-between item-center">

                    <div class="flex items-center gap-3">
                        <Button variant="ghost" size="icon" class="h-8 w-8" as-child>
                            <Link :href="route('transactions.index')">
                                <ArrowLeft class="h-4 w-4" />
                            </Link>
                        </Button>
                        <Heading :title="transaction.order_id"
                                :description="transaction.created_at" />
                    </div>

                    <div>
                        <button
                            @click="showRefundDialog = true"
                            class="cursor-pointer inline-flex items-center justify-center gap-2 rounded-xl border border-input bg-background px-4 py-2.5 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                            <RotateCcw class="h-4 w-4" />
                            Request Refund
                        </button>
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

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <!-- Transaction Info -->
                <div class="rounded-lg border p-4">
                    <HeadingSmall title="Info Transaksi" />
                    <div class="mt-4 grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs text-muted-foreground">Order ID</p>
                            <p class="font-mono text-sm font-medium">{{ transaction.order_id }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground">Tipe Pembayaran</p>
                            <p class="text-sm font-medium capitalize">{{ transaction.payment_type }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground">Metode Midtrans</p>
                            <p class="text-sm font-medium uppercase">{{ transaction.midtrans_method ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground">Status</p>
                            <span :class="statusBadge(transaction.status)" class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium capitalize">
                                {{ transaction.status }}
                            </span>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground">Biaya Transaksi</p>
                            <p class="text-sm font-medium">{{ formatCurrency(transaction.transaction_fee) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground">Total</p>
                            <p class="text-sm font-semibold">{{ formatCurrency(transaction.total_price) }}</p>
                        </div>
                    </div>
                </div>

                <!-- Tenant + Bill Info -->
                <div class="space-y-6">
                    <div class="rounded-lg border p-4">
                        <HeadingSmall title="Info Tenant" />
                        <div class="mt-4 grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-xs text-muted-foreground">Nama</p>
                                <p class="text-sm font-medium">{{ tenant.name }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-muted-foreground">No. HP</p>
                                <p class="text-sm font-medium">{{ tenant.phone_number }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-lg border p-4">
                        <HeadingSmall title="Info Bill" />
                        <div class="mt-4 grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-xs text-muted-foreground">Periode</p>
                                <p class="text-sm font-medium">{{ bill.start_date }} — {{ bill.due_date }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-muted-foreground">Total Bill</p>
                                <p class="text-sm font-medium">{{ formatCurrency(bill.total_price) }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-muted-foreground">Status Bill</p>
                                <span :class="statusBadge(bill.status)" class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium capitalize">
                                    {{ bill.status }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Transaction Details (Timeline) -->
            <div class="rounded-lg border p-4">
                <HeadingSmall title="Transaction Log" description="Riwayat perubahan status transaksi" />
                <div class="mt-4">
                    <div v-if="details.length === 0" class="py-6 text-center text-sm text-muted-foreground">Belum ada log transaksi.</div>
                    <div v-else class="relative ml-3 border-l border-border pl-6">
                        <div v-for="detail in details" :key="detail.id" class="relative mb-6 last:mb-0">
                            <span
                                :class="timelineIcon(detail.status)"
                                class="absolute -left-[calc(1.5rem+0.3125rem)] top-1 h-2.5 w-2.5 rounded-full ring-2 ring-background"
                            ></span>
                            <div class="flex items-center gap-3">
                                <span
                                    :class="statusBadge(detail.status)"
                                    class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium capitalize"
                                >
                                    {{ detail.status }}
                                </span>
                                <span class="text-xs text-muted-foreground">{{ detail.created_at }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Refund History -->
            <div class="rounded-lg border p-4">
                <HeadingSmall title="Riwayat Refund" :description="`${refunds.length} refund`" />
                <div class="mt-4 overflow-hidden rounded-lg border">
                    <table class="w-full text-sm">
                        <thead class="border-b bg-muted/50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium">#</th>
                                <th class="px-4 py-3 text-left font-medium">Jumlah</th>
                                <th class="px-4 py-3 text-left font-medium">Keterangan</th>
                                <th class="px-4 py-3 text-left font-medium">Tanggal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="refunds.length === 0">
                                <td colspan="4" class="px-4 py-8 text-center text-muted-foreground">Belum ada refund.</td>
                            </tr>
                            <tr v-for="(refund, idx) in refunds" :key="refund.id" class="border-b last:border-0">
                                <td class="px-4 py-3 font-medium">{{ idx + 1 }}</td>
                                <td class="px-4 py-3">{{ formatCurrency(refund.amount) }}</td>
                                <td class="px-4 py-3">{{ refund.remark ?? '-' }}</td>
                                <td class="px-4 py-3 text-xs">{{ refund.created_at }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            </div>
        </div>

        <!-- Refund Dialog -->
        <Dialog v-model:open="showRefundDialog">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Request Refund</DialogTitle>
                    <DialogDescription> Ajukan refund untuk transaksi {{ transaction.order_id }} </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitRefund" class="grid gap-4">
                    <div class="grid gap-2">
                        <Label for="refund_amount">Jumlah Refund (80%)</Label>
                        <Input id="refund_amount" :model-value="formatCurrency(refundForm.amount)" disabled />
                    </div>
                    <div class="grid gap-2">
                        <Label for="refund_remark">Keterangan (opsional)</Label>
                        <Input id="refund_remark" v-model="refundForm.remark" placeholder="Alasan refund..." />
                        <p v-if="refundForm.errors.remark" class="text-xs text-destructive">{{ refundForm.errors.remark }}</p>
                    </div>

                    <DialogFooter>
                        <DialogClose as-child>
                            <Button variant="secondary" type="button">Batal</Button>
                        </DialogClose>
                        <Button type="submit" :disabled="refundForm.processing">
                            <LoaderCircle v-if="refundForm.processing" class="mr-1.5 h-4 w-4 animate-spin" />
                            Submit Refund
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
