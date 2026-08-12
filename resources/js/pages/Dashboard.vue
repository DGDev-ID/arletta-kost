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
    chartData: { day: string; value: number; raw_value: number; label: string }[];
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

// Kalkulasi untuk Radial Chart (Circle)
const circleRadius = 36;
const circleCircumference = 2 * Math.PI * circleRadius;
const occupancyStrokeDashoffset = circleCircumference - (props.stats.occupancy_rate / 100) * circleCircumference;
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6 bg-slate-50/50 dark:bg-background">
            
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-xl border bg-background p-5 shadow-sm">
                    <div class="flex items-center justify-between pb-2">
                        <p class="text-sm font-medium text-muted-foreground">Total Revenue</p>
                        <div class="flex h-8 w-8 items-center justify-center rounded-md bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400">
                            <Banknote class="h-4 w-4" />
                        </div>
                    </div>
                    <div>
                        <p class="text-2xl font-bold tracking-tight">{{ formatCurrency(stats.total_revenue) }}</p>
                    </div>
                </div>

                <div class="rounded-xl border bg-background p-5 shadow-sm">
                    <div class="flex items-center justify-between pb-2">
                        <p class="text-sm font-medium text-muted-foreground">Total Transaksi</p>
                        <div class="flex h-8 w-8 items-center justify-center rounded-md bg-violet-100 text-violet-600 dark:bg-violet-900/30 dark:text-violet-400">
                            <Wallet class="h-4 w-4" />
                        </div>
                    </div>
                    <div>
                        <p class="text-2xl font-bold tracking-tight">{{ stats.total_transactions }}</p>
                        <p class="text-xs text-muted-foreground mt-1">{{ stats.paid_bills }} lunas, {{ stats.unpaid_bills }} belum</p>
                    </div>
                </div>

                <div class="rounded-xl border bg-background p-5 shadow-sm">
                    <div class="flex items-center justify-between pb-2">
                        <p class="text-sm font-medium text-muted-foreground">Kamar Tersedia</p>
                        <div class="flex h-8 w-8 items-center justify-center rounded-md bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                            <DoorOpen class="h-4 w-4" />
                        </div>
                    </div>
                    <div>
                        <p class="text-2xl font-bold tracking-tight">{{ stats.available_rooms }}</p>
                        <p class="text-xs text-muted-foreground mt-1">Dari total {{ stats.total_rooms }} kamar</p>
                    </div>
                </div>

                <div class="rounded-xl border bg-background p-5 shadow-sm">
                    <div class="flex items-center justify-between pb-2">
                        <p class="text-sm font-medium text-muted-foreground">Pending TTD / Refund</p>
                        <div class="flex h-8 w-8 items-center justify-center rounded-md bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400">
                            <FileWarning class="h-4 w-4" />
                        </div>
                    </div>
                    <div>
                        <p class="text-2xl font-bold tracking-tight text-amber-600">{{ stats.pending_signatures + stats.refund_requests }}</p>
                        <p class="text-xs text-muted-foreground mt-1">Perlu tindakan segera</p>
                    </div>
                </div>
            </div>

            <div class="grid gap-4 lg:grid-cols-3">
                
                <div class="lg:col-span-2 rounded-xl border bg-background p-5 shadow-sm flex flex-col">
                    <h3 class="font-semibold text-sm mb-4">Revenue 7 Hari Terakhir</h3>
                    <!-- Chart area: 160px tinggi, flex-end agar bar tumbuh dari bawah -->
                    <div class="relative flex items-end justify-between gap-1 border-b border-border" style="height: 160px;">
                        <div v-for="(item, index) in chartData" :key="index" class="relative group flex flex-col items-center flex-1">
                            <!-- Tooltip muncul saat hover -->
                            <div class="absolute -top-9 left-1/2 -translate-x-1/2 bg-zinc-900 text-white text-xs py-1 px-2 rounded-md opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap z-20 pointer-events-none shadow-lg">
                                {{ item.label }}
                            </div>
                            <!-- Bar: tinggi dalam pixel (160px = 100%) -->
                            <div
                                class="w-full max-w-[36px] rounded-t-md transition-all duration-500 ease-out group-hover:opacity-80"
                                :class="item.raw_value > 0 ? 'bg-slate-800 dark:bg-slate-200' : 'bg-slate-100 dark:bg-slate-800'"
                                :style="{ height: `${Math.max(item.value, 2) * 1.6}px` }"
                            ></div>
                        </div>
                    </div>
                    <!-- Label tanggal di bawah -->
                    <div class="flex justify-between gap-1 mt-2 px-0">
                        <div v-for="(item, index) in chartData" :key="'label-' + index" class="flex-1 flex items-center justify-center">
                            <span class="text-[10px] text-muted-foreground whitespace-nowrap">{{ item.day }}</span>
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border bg-background p-5 shadow-sm flex flex-col items-center justify-center">
                    <h3 class="font-semibold text-sm self-start w-full mb-4">Occupancy Kamar</h3>
                    <div class="relative flex items-center justify-center mt-4">
                        <svg class="w-32 h-32 transform -rotate-90">
                            <circle cx="64" cy="64" :r="circleRadius" class="stroke-slate-100 dark:stroke-slate-800" stroke-width="12" fill="transparent" />
                            <circle 
                                cx="64" cy="64" :r="circleRadius" 
                                class="stroke-slate-800 dark:stroke-slate-200 transition-all duration-1000 ease-out" 
                                stroke-width="12" fill="transparent" 
                                stroke-linecap="round"
                                :stroke-dasharray="circleCircumference"
                                :stroke-dashoffset="occupancyStrokeDashoffset"
                            />
                        </svg>
                        <div class="absolute flex flex-col items-center justify-center">
                            <span class="text-3xl font-bold">{{ stats.occupancy_rate }}%</span>
                        </div>
                    </div>
                    <p class="text-xs text-muted-foreground mt-6 text-center">
                        {{ stats.occupied_rooms }} dari {{ stats.total_rooms }} kamar sedang digunakan
                    </p>
                </div>
            </div>

            <div>
                <h3 class="mb-3 text-xs font-semibold uppercase tracking-wider text-muted-foreground">Detail Data</h3>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="flex items-center p-4 border rounded-xl bg-background shadow-sm">
                        <div class="p-3 mr-4 bg-blue-50 text-blue-600 rounded-lg dark:bg-blue-900/30 dark:text-blue-400"><Building class="w-5 h-5"/></div>
                        <div>
                            <p class="text-xs text-muted-foreground">Total Kost</p>
                            <p class="text-lg font-semibold">{{ stats.total_kosts }} <span class="text-xs font-normal text-muted-foreground">({{ stats.total_room_categories }} Tipe)</span></p>
                        </div>
                    </div>
                    <div class="flex items-center p-4 border rounded-xl bg-background shadow-sm">
                        <div class="p-3 mr-4 bg-yellow-50 text-yellow-600 rounded-lg dark:bg-yellow-900/30 dark:text-yellow-400"><Wrench class="w-5 h-5"/></div>
                        <div>
                            <p class="text-xs text-muted-foreground">Kamar Maintenance</p>
                            <p class="text-lg font-semibold">{{ stats.maintenance_rooms }}</p>
                        </div>
                    </div>
                    <div class="flex items-center p-4 border rounded-xl bg-background shadow-sm">
                        <div class="p-3 mr-4 bg-emerald-50 text-emerald-600 rounded-lg dark:bg-emerald-900/30 dark:text-emerald-400"><Users class="w-5 h-5"/></div>
                        <div>
                            <p class="text-xs text-muted-foreground">Tenant Aktif</p>
                            <p class="text-lg font-semibold">{{ stats.total_tenants }}</p>
                        </div>
                    </div>
                    <div class="flex items-center p-4 border rounded-xl bg-background shadow-sm">
                        <div class="p-3 mr-4 bg-orange-50 text-orange-600 rounded-lg dark:bg-orange-900/30 dark:text-orange-400"><Clock class="w-5 h-5"/></div>
                        <div>
                            <p class="text-xs text-muted-foreground">Pending Revenue</p>
                            <p class="text-lg font-semibold">{{ formatCurrency(stats.pending_revenue) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <h3 class="mb-3 text-xs font-semibold uppercase tracking-wider text-muted-foreground">Transaksi Terbaru</h3>
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
                                <td colspan="6" class="px-4 py-8 text-center text-muted-foreground">Belum ada data transaksi.</td>
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