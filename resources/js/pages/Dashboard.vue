<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import {
    Building,
    FolderTree,
    BedDouble,
    DoorOpen,
    DoorClosed,
    Wrench,
    TrendingUp,
    Users,
    Shield,
    FileWarning,
    FileCheck,
    FileSignature,
    HandCoins,
    Wallet,
    Banknote,
    Clock,
} from 'lucide-vue-next';

interface Stats {
    total_kosts: number;
    total_room_categories: number;
    total_rooms: number;
    available_rooms: number;
    occupied_rooms: number;
    maintenance_rooms: number;
    occupancy_rate: number;
    total_tenants: number;
    total_users: number;
    unpaid_bills: number;
    paid_bills: number;
    pending_signatures: number;
    refund_requests: number;
    total_revenue: number;
    pending_revenue: number;
    total_transactions: number;
}

interface RecentBill {
    id: number;
    tenant_name: string;
    room_number: string;
    kost_name: string;
    total_price: number;
    status: string;
    due_date: string | null;
    created_at: string | null;
}

const props = defineProps<{
    stats: Stats;
    recentBills: RecentBill[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
];

const formatCurrency = (val: number) =>
    new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val);

const statusBadge = (status: string) => {
    const map: Record<string, string> = {
        paid: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
        unpaid: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
        cancelled: 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
        refund_request: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
        refund: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
    };
    return map[status] ?? 'bg-gray-100 text-gray-700';
};
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
            <!-- Section: Master Data -->
            <div>
                <h3 class="mb-3 text-xs font-semibold uppercase tracking-wider text-muted-foreground">Master Data</h3>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <!-- Total Kost -->
                    <div class="group relative overflow-hidden rounded-xl border bg-background p-5 shadow-sm transition-shadow hover:shadow-md">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Total Kost</p>
                                <p class="mt-1 text-3xl font-bold tracking-tight">{{ stats.total_kosts }}</p>
                            </div>
                            <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                                <Building class="h-5 w-5" />
                            </div>
                        </div>
                    </div>

                    <!-- Total Categories -->
                    <div class="group relative overflow-hidden rounded-xl border bg-background p-5 shadow-sm transition-shadow hover:shadow-md">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Kategori Kamar</p>
                                <p class="mt-1 text-3xl font-bold tracking-tight">{{ stats.total_room_categories }}</p>
                            </div>
                            <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400">
                                <FolderTree class="h-5 w-5" />
                            </div>
                        </div>
                    </div>

                    <!-- Total Rooms -->
                    <div class="group relative overflow-hidden rounded-xl border bg-background p-5 shadow-sm transition-shadow hover:shadow-md">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Total Kamar</p>
                                <p class="mt-1 text-3xl font-bold tracking-tight">{{ stats.total_rooms }}</p>
                            </div>
                            <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-indigo-100 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400">
                                <BedDouble class="h-5 w-5" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section: Room Status -->
            <div>
                <h3 class="mb-3 text-xs font-semibold uppercase tracking-wider text-muted-foreground">Status Kamar</h3>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-xl border bg-background p-5 shadow-sm transition-shadow hover:shadow-md">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Available</p>
                                <p class="mt-1 text-3xl font-bold tracking-tight text-green-600 dark:text-green-400">{{ stats.available_rooms }}</p>
                            </div>
                            <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400">
                                <DoorOpen class="h-5 w-5" />
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl border bg-background p-5 shadow-sm transition-shadow hover:shadow-md">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Occupied</p>
                                <p class="mt-1 text-3xl font-bold tracking-tight text-red-600 dark:text-red-400">{{ stats.occupied_rooms }}</p>
                            </div>
                            <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400">
                                <DoorClosed class="h-5 w-5" />
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl border bg-background p-5 shadow-sm transition-shadow hover:shadow-md">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Maintenance</p>
                                <p class="mt-1 text-3xl font-bold tracking-tight text-yellow-600 dark:text-yellow-400">{{ stats.maintenance_rooms }}</p>
                            </div>
                            <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-yellow-100 text-yellow-600 dark:bg-yellow-900/30 dark:text-yellow-400">
                                <Wrench class="h-5 w-5" />
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl border bg-background p-5 shadow-sm transition-shadow hover:shadow-md">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Occupancy Rate</p>
                                <p class="mt-1 text-3xl font-bold tracking-tight">{{ stats.occupancy_rate }}%</p>
                            </div>
                            <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-cyan-100 text-cyan-600 dark:bg-cyan-900/30 dark:text-cyan-400">
                                <TrendingUp class="h-5 w-5" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section: Management -->
            <div>
                <h3 class="mb-3 text-xs font-semibold uppercase tracking-wider text-muted-foreground">Management</h3>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-xl border bg-background p-5 shadow-sm transition-shadow hover:shadow-md">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Tenant Aktif</p>
                                <p class="mt-1 text-3xl font-bold tracking-tight">{{ stats.total_tenants }}</p>
                            </div>
                            <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400">
                                <Users class="h-5 w-5" />
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl border bg-background p-5 shadow-sm transition-shadow hover:shadow-md">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Total Users</p>
                                <p class="mt-1 text-3xl font-bold tracking-tight">{{ stats.total_users }}</p>
                            </div>
                            <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-slate-100 text-slate-600 dark:bg-slate-900/30 dark:text-slate-400">
                                <Shield class="h-5 w-5" />
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl border bg-background p-5 shadow-sm transition-shadow hover:shadow-md">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Pending TTD</p>
                                <p class="mt-1 text-3xl font-bold tracking-tight text-amber-600 dark:text-amber-400">{{ stats.pending_signatures }}</p>
                            </div>
                            <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400">
                                <FileSignature class="h-5 w-5" />
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl border bg-background p-5 shadow-sm transition-shadow hover:shadow-md">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Refund Request</p>
                                <p class="mt-1 text-3xl font-bold tracking-tight text-orange-600 dark:text-orange-400">{{ stats.refund_requests }}</p>
                            </div>
                            <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-orange-100 text-orange-600 dark:bg-orange-900/30 dark:text-orange-400">
                                <HandCoins class="h-5 w-5" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section: Revenue & Transactions -->
            <div>
                <h3 class="mb-3 text-xs font-semibold uppercase tracking-wider text-muted-foreground">Revenue & Transaksi</h3>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-xl border bg-background p-5 shadow-sm transition-shadow hover:shadow-md">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Total Revenue</p>
                                <p class="mt-1 text-2xl font-bold tracking-tight text-green-600 dark:text-green-400">{{ formatCurrency(stats.total_revenue) }}</p>
                            </div>
                            <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400">
                                <Banknote class="h-5 w-5" />
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl border bg-background p-5 shadow-sm transition-shadow hover:shadow-md">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Pending Revenue</p>
                                <p class="mt-1 text-2xl font-bold tracking-tight text-amber-600 dark:text-amber-400">{{ formatCurrency(stats.pending_revenue) }}</p>
                            </div>
                            <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400">
                                <Clock class="h-5 w-5" />
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl border bg-background p-5 shadow-sm transition-shadow hover:shadow-md">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Bills Paid</p>
                                <p class="mt-1 text-3xl font-bold tracking-tight">{{ stats.paid_bills }}</p>
                                <p class="mt-0.5 text-xs text-muted-foreground">{{ stats.unpaid_bills }} unpaid</p>
                            </div>
                            <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-teal-100 text-teal-600 dark:bg-teal-900/30 dark:text-teal-400">
                                <FileCheck class="h-5 w-5" />
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl border bg-background p-5 shadow-sm transition-shadow hover:shadow-md">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Total Transaksi</p>
                                <p class="mt-1 text-3xl font-bold tracking-tight">{{ stats.total_transactions }}</p>
                            </div>
                            <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-violet-100 text-violet-600 dark:bg-violet-900/30 dark:text-violet-400">
                                <Wallet class="h-5 w-5" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section: Recent Bills -->
            <div>
                <h3 class="mb-3 text-xs font-semibold uppercase tracking-wider text-muted-foreground">Tagihan Terbaru</h3>
                <div class="overflow-hidden rounded-xl border bg-background shadow-sm">
                    <table class="w-full text-sm">
                        <thead class="border-b bg-muted/50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium">Tenant</th>
                                <th class="px-4 py-3 text-left font-medium">Room</th>
                                <th class="px-4 py-3 text-left font-medium">Kost</th>
                                <th class="px-4 py-3 text-right font-medium">Harga</th>
                                <th class="px-4 py-3 text-left font-medium">Status</th>
                                <th class="px-4 py-3 text-left font-medium">Tanggal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="recentBills.length === 0">
                                <td colspan="6" class="px-4 py-8 text-center text-muted-foreground">Belum ada data tagihan.</td>
                            </tr>
                            <tr v-for="bill in recentBills" :key="bill.id" class="border-b last:border-0 transition-colors hover:bg-muted/30">
                                <td class="px-4 py-3 font-medium">{{ bill.tenant_name }}</td>
                                <td class="px-4 py-3">{{ bill.room_number }}</td>
                                <td class="px-4 py-3 text-muted-foreground">{{ bill.kost_name }}</td>
                                <td class="px-4 py-3 text-right font-medium tabular-nums">{{ formatCurrency(bill.total_price) }}</td>
                                <td class="px-4 py-3">
                                    <span :class="statusBadge(bill.status)" class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium capitalize">
                                        {{ bill.status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-xs text-muted-foreground">{{ bill.created_at ?? '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
