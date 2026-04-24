<script setup lang="ts">
import HeadingSmall from '@/components/HeadingSmall.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';

interface BillData {
    id: number;
    total_price: number;
    start_date: string | null;
    due_date: string | null;
    status: string;
}

interface TenantData {
    id: number;
    name: string;
    email: string;
    phone_number: string;
    nik: string;
    address: string;
}

interface RoomData {
    id: number;
    room_number: string;
    kost_name: string;
    category_name: string;
}

interface DetailItem {
    id: number;
    status: string;
    created_at: string;
}

interface TransactionData {
    id: number;
    order_id: string;
    payment_type: string;
    total_price: number;
    status: string;
    created_at: string;
    details: DetailItem[];
}

const props = defineProps<{
    bill: BillData;
    tenant: TenantData | null;
    room: RoomData | null;
    transactions: TransactionData[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Bill Approval', href: '/transactions/bill-approval' },
    { title: `Detail Bill #${props.bill.id}`, href: '#' },
];

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value);
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
</script>

<template>
    <Head :title="`Detail Bill #${bill.id}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-muted/40 py-10">
            <div class="max-w-7xl mx-auto px-6 space-y-8">

                <!-- Header -->
                <div class="flex flex-col justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <Button variant="ghost" size="icon" class="h-8 w-8" as-child>
                            <Link :href="route('transactions.bill-approval.index')">
                                <ArrowLeft class="h-4 w-4" />
                            </Link>
                        </Button>
                        <Heading :title="`Detail Bill #${bill.id}`" />
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Bill Info -->
                    <div class="rounded-lg border bg-card p-6 space-y-4">
                        <HeadingSmall title="Informasi Tagihan" />
                        <div class="space-y-3">
                            <div class="flex justify-between border-b pb-2">
                                <span class="text-sm text-muted-foreground">Status</span>
                                <span :class="statusBadge(bill.status)" class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium capitalize">
                                    {{ bill.status }}
                                </span>
                            </div>
                            <div class="flex justify-between border-b pb-2">
                                <span class="text-sm text-muted-foreground">Total Tagihan</span>
                                <span class="text-sm font-medium">{{ formatCurrency(bill.total_price) }}</span>
                            </div>
                            <div class="flex justify-between border-b pb-2">
                                <span class="text-sm text-muted-foreground">Periode Mulai</span>
                                <span class="text-sm font-medium">{{ bill.start_date ?? '-' }}</span>
                            </div>
                            <div class="flex justify-between pb-2">
                                <span class="text-sm text-muted-foreground">Jatuh Tempo</span>
                                <span class="text-sm font-medium">{{ bill.due_date ?? '-' }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Room Info -->
                    <div class="rounded-lg border bg-card p-6 space-y-4">
                        <HeadingSmall title="Informasi Kamar" />
                        <div v-if="room" class="space-y-3">
                            <div class="flex justify-between border-b pb-2">
                                <span class="text-sm text-muted-foreground">Nomor Kamar</span>
                                <span class="text-sm font-medium">{{ room.room_number }}</span>
                            </div>
                            <div class="flex justify-between border-b pb-2">
                                <span class="text-sm text-muted-foreground">Kategori</span>
                                <span class="text-sm font-medium">{{ room.category_name }}</span>
                            </div>
                            <div class="flex justify-between pb-2">
                                <span class="text-sm text-muted-foreground">Kost</span>
                                <span class="text-sm font-medium">{{ room.kost_name }}</span>
                            </div>
                        </div>
                        <div v-else class="text-sm text-muted-foreground">
                            Data kamar tidak ditemukan.
                        </div>
                    </div>

                    <!-- Tenant Info -->
                    <div class="rounded-lg border bg-card p-6 space-y-4 md:col-span-2">
                        <HeadingSmall title="Informasi Penyewa" />
                        <div v-if="tenant" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <p class="text-xs text-muted-foreground">Nama</p>
                                <p class="text-sm font-medium">{{ tenant.name }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-muted-foreground">Email</p>
                                <p class="text-sm font-medium">{{ tenant.email }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-muted-foreground">No. Handphone</p>
                                <p class="text-sm font-medium">{{ tenant.phone_number }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-muted-foreground">NIK</p>
                                <p class="text-sm font-medium">{{ tenant.nik }}</p>
                            </div>
                            <div class="sm:col-span-2">
                                <p class="text-xs text-muted-foreground">Alamat</p>
                                <p class="text-sm font-medium">{{ tenant.address }}</p>
                            </div>
                        </div>
                        <div v-else class="text-sm text-muted-foreground">
                            Data penyewa tidak ditemukan.
                        </div>
                    </div>

                    <!-- Transactions History -->
                    <div class="rounded-lg border bg-card p-6 space-y-4 md:col-span-2">
                        <HeadingSmall title="Riwayat Transaksi" description="Daftar transaksi yang terkait dengan tagihan ini." />
                        
                        <div v-if="transactions.length > 0" class="space-y-6">
                            <div v-for="(t, i) in transactions" :key="t.id" class="rounded-md border p-4">
                                <div class="flex flex-col sm:flex-row justify-between sm:items-center border-b pb-3 mb-3 gap-2">
                                    <div>
                                        <p class="font-medium text-sm">{{ t.order_id }}</p>
                                        <p class="text-xs text-muted-foreground">{{ t.created_at }}</p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex rounded-full bg-blue-100 text-blue-700 px-2 py-0.5 text-xs font-medium uppercase">
                                            {{ t.payment_type }}
                                        </span>
                                        <span :class="statusBadge(t.status)" class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium capitalize">
                                            {{ t.status }}
                                        </span>
                                    </div>
                                </div>
                                <div class="flex justify-between items-center mb-4">
                                    <span class="text-sm font-medium">Nominal:</span>
                                    <span class="text-sm font-semibold">{{ formatCurrency(t.total_price) }}</span>
                                </div>
                                
                                <!-- Timeline details -->
                                <div>
                                    <p class="text-xs font-medium text-muted-foreground mb-2 uppercase tracking-wider">Log Status</p>
                                    <ul class="relative border-l border-muted-foreground/30 ml-2 space-y-3">
                                        <li v-for="(d, dIdx) in t.details" :key="d.id" class="ml-4">
                                            <div class="absolute w-2 h-2 bg-primary rounded-full -left-1 mt-1.5 border border-background"></div>
                                            <div class="flex justify-between items-start">
                                                <p class="text-sm font-medium capitalize">{{ d.status }}</p>
                                                <p class="text-xs text-muted-foreground">{{ d.created_at }}</p>
                                            </div>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-sm text-center py-4 text-muted-foreground">
                            Belum ada riwayat transaksi.
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </AppLayout>
</template>
