<script setup lang="ts">
import { Button } from '@/components/ui/button';
import Heading from '@/components/Heading.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
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
import { ArrowLeft, CheckCircle, FileText, LoaderCircle, XCircle } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface RoomInfo {
    id: number;
    room_number: string;
    kost_name: string;
    category_name: string;
    status: string;
    start_date: string;
    end_date: string;
}

interface TenantData {
    id: number;
    name: string;
    email: string;
    phone_number: string;
    nik: string | null;
    birth_place: string | null;
    birth_date: string | null;
    gender: string | null;
    address: string | null;
    rooms: RoomInfo[];
}

interface TransactionItem {
    id: number;
    order_id: string;
    payment_type: string;
    status: string;
    total_price: number;
}

interface BillItem {
    id: number;
    total_price: number;
    start_date: string;
    due_date: string;
    status: string;
    room_id: number;
    transactions: TransactionItem[];
}

interface PricingItem {
    id: number;
    duration_days: number;
    price: number;
}

interface CategoryItem {
    id: number;
    name: string;
    kost_name: string;
    pricings: PricingItem[];
}

interface OccupiedPeriod {
    start_date: string;
    due_date: string;
}

interface AvailableRoom {
    id: number;
    room_number: string;
    kost_name: string;
    category_id: number;
    category_name: string;
    occupied_periods: OccupiedPeriod[];
}

const props = defineProps<{
    tenant: TenantData;
    bills: BillItem[];
    categories: CategoryItem[];
    availableRooms: AvailableRoom[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Tenants', href: '/management/tenants' },
    { title: props.tenant.name, href: '#' },
];

const flash = computed(() => {
    const page = router.page as any;
    return page?.props?.flash as { success?: string; error?: string } | undefined;
});

// Show page will display all sections as cards (no tabs)

// --- Bill Form ---
const showBillDialog = ref(false);

const billForm = useForm({
    tenant_id: props.tenant.id,
    category_id: '' as number | '',
    room_id: '' as number | '',
    pricing_id: '' as number | '',
    total_price: 0,
    start_date: '',
    due_date: '',
});

// Rooms filtered by selected category and date overlap
const filteredRooms = computed(() => {
    if (!billForm.category_id) return [];
    return props.availableRooms.filter(r => {
        if (r.category_id !== billForm.category_id) return false;

        // Check date overlap if dates are selected
        if (billForm.start_date && billForm.due_date) {
            return !r.occupied_periods.some(p =>
                p.start_date < billForm.due_date && p.due_date > billForm.start_date
            );
        }

        return true;
    });
});

// Pricings filtered by selected category
const filteredPricings = computed(() => {
    if (!billForm.category_id) return [];
    const cat = props.categories.find(c => c.id === billForm.category_id);
    return cat?.pricings ?? [];
});

const selectedPricing = computed(() => {
    if (!billForm.pricing_id) return null;
    return filteredPricings.value.find((p) => p.id === billForm.pricing_id) ?? null;
});

// Auto-calculate total_price and due_date when pricing or start_date change
watch(
    () => [billForm.pricing_id, billForm.start_date],
    () => {
        if (selectedPricing.value && billForm.start_date) {
            billForm.total_price = selectedPricing.value.price;
            const start = new Date(billForm.start_date);
            start.setDate(start.getDate() + selectedPricing.value.duration_days);
            billForm.due_date = start.toISOString().split('T')[0];
        }
    },
);

// Reset room and pricing when category changes
watch(() => billForm.category_id, () => {
    billForm.room_id = '';
    billForm.pricing_id = '';
    billForm.total_price = 0;
    billForm.due_date = '';
});

// Reset pricing when room changes
watch(() => billForm.room_id, () => {
    billForm.pricing_id = '';
    billForm.total_price = 0;
    billForm.due_date = '';
});

const submitBill = () => {
    billForm.post(route('management.bills.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showBillDialog.value = false;
            billForm.reset('category_id', 'room_id', 'pricing_id', 'total_price', 'start_date', 'due_date');
        },
    });
};

// --- Manual payment actions ---
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

// --- Status update ---
const showStatusDialog = ref(false);
const selectedBill = ref<BillItem | null>(null);
const newStatus = ref('');

const openStatusDialog = (bill: BillItem) => {
    selectedBill.value = bill;
    newStatus.value = bill.status;
    showStatusDialog.value = true;
};

const updateStatus = () => {
    if (!selectedBill.value) return;
    router.patch(
        route('management.bills.update-status', selectedBill.value.id),
        { status: newStatus.value },
        {
            preserveScroll: true,
            onFinish: () => {
                showStatusDialog.value = false;
                selectedBill.value = null;
            },
        },
    );
};

const statusBadge = (status: string) => {
    const map: Record<string, string> = {
        unpaid: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
        paid: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
        pending: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
        success: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
        failed: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
        cancelled: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-400',
        refund_request: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
        refund: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
    };
    return map[status] ?? 'bg-gray-100 text-gray-700';
};

const roomStatusBadge = (status: string) => {
    const map: Record<string, string> = {
        available: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
        occupied: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
        maintenance: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
    };
    return map[status] ?? 'bg-gray-100 text-gray-700';
};

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value);
};

const formatDuration = (days: number) => {
    if (days % 30 === 0) return `${days / 30} bulan`;
    return `${days} hari`;
};

// Filtered bills: show unpaid/pending with manual payment_type
const manualBills = computed(() => {
    return props.bills.filter(bill => {
        const isUnpaidOrPending = bill.status === 'unpaid';

        const hasManualTransaction = bill.transactions.some(
            t => t.payment_type === 'manual'
        );

        const hasNoFailedTransaction = !bill.transactions.some(
            t => t.status === 'failed'
        );

        return isUnpaidOrPending && hasManualTransaction && hasNoFailedTransaction;
    });
});

// All bills for the history view
const allBills = computed(() => props.bills);
</script>

<template>
    <Head :title="tenant.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-muted/40 py-10">
            <div class="max-w-7xl mx-auto px-6 space-y-8">

            <!-- Header -->
            <div class="flex flex-col justify-between gap-4">
                <div class="flex flex-row justify-between item-center">
                    <div class="flex items-center gap-3">
                        <Button variant="ghost" size="icon" class="h-8 w-8" as-child>
                            <Link :href="route('management.tenants.index')">
                                <ArrowLeft class="h-4 w-4" />
                            </Link>
                        </Button>
                        <Heading :title="tenant.name"
                                :description="`${tenant.rooms.map(r => `${r.kost_name} — ${r.room_number}`).join(', ')}`" />
                    </div>

                    <div >
                        <button
                            @click="showBillDialog = true"
                            class="cursor-pointer inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-medium text-primary-foreground shadow transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                            <FileText class="h-4 w-4" />
                            Create Bill
                        </button>
                        <!-- <Link :href="route('management.tenants.edit', tenant.id)"
                            class="cursor-pointer inline-flex items-center justify-center gap-2 rounded-xl border border-input bg-background px-4 py-2.5 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                            Edit Tenant
                        </Link> -->
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

            <!-- All sections shown as cards (Info, Rooms, Bills) -->

            <!-- Tenant Info -->
            <div class="rounded-lg border p-4 space-y-4">
                <HeadingSmall title="Info Tenant" description="Data pribadi tenant" />
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <p class="text-xs text-muted-foreground">Email</p>
                        <p class="text-sm font-medium">{{ tenant.email }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">No. HP</p>
                        <p class="text-sm font-medium">{{ tenant.phone_number }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Gender</p>
                        <p class="text-sm font-medium capitalize">{{ tenant.gender ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">NIK</p>
                        <p class="text-sm font-medium">{{ tenant.nik ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Tempat, Tanggal Lahir</p>
                        <p class="text-sm font-medium">{{ tenant.birth_place ?? '-' }}{{ tenant.birth_date ? `, ${tenant.birth_date}` : '' }}</p>
                    </div>
                    <div class="sm:col-span-2 lg:col-span-3">
                        <p class="text-xs text-muted-foreground">Alamat</p>
                        <p class="text-sm font-medium">{{ tenant.address ?? '-' }}</p>
                    </div>
                </div>
            </div>

            <!-- Rooms -->
            <div class="rounded-lg border p-4 space-y-4">
                <HeadingSmall title="Rooms" :description="`${tenant.rooms.length} room(s) ditempati`" />
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div v-for="room in tenant.rooms" :key="room.id" class="rounded-lg border p-4 space-y-2">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-semibold">{{ room.room_number }}</p>
                            <span :class="roomStatusBadge(room.status)" class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium capitalize">
                                {{ room.start_date }} sampai {{ room.end_date }}
                            </span>
                        </div>
                        <p class="text-xs text-muted-foreground">{{ room.kost_name }}</p>
                        <p class="text-xs text-muted-foreground">Kategori: {{ room.category_name }}</p>
                    </div>
                </div>
            </div>

            <!-- Bills -->
            <div class="rounded-lg border p-4 space-y-6">
                <!-- Manual Bills (unpaid/pending) -->
                <div v-if="manualBills.length > 0">
                    <HeadingSmall title="Bills Menunggu Konfirmasi (Manual)" :description="`${manualBills.length} bill(s) perlu tindakan`" />
                    <div class="overflow-hidden rounded-lg border">
                        <table class="w-full text-sm">
                            <thead class="border-b bg-muted/50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-medium">Total</th>
                                    <th class="px-4 py-3 text-left font-medium">Mulai</th>
                                    <th class="px-4 py-3 text-left font-medium">Jatuh Tempo</th>
                                    <th class="px-4 py-3 text-left font-medium">Status</th>
                                    <th class="px-4 py-3 text-left font-medium">Transaction</th>
                                    <th class="px-4 py-3 text-right font-medium">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="bill in manualBills" :key="bill.id" class="border-b last:border-0">
                                    <td class="px-4 py-3">{{ formatCurrency(bill.total_price) }}</td>
                                    <td class="px-4 py-3">{{ bill.start_date }}</td>
                                    <td class="px-4 py-3">{{ bill.due_date }}</td>
                                    <td class="px-4 py-3">
                                        <span :class="statusBadge(bill.status)" class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium capitalize">
                                            {{ bill.status }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div v-for="t in bill.transactions.filter(t => t.payment_type === 'manual')" :key="t.id" class="text-xs">
                                            <span :class="statusBadge(t.status)" class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium capitalize">
                                                {{ t.status }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex items-center justify-end gap-1">
                                            <template v-for="t in bill.transactions.filter(t => t.payment_type === 'manual' && t.status === 'pending')" :key="t.id">
                                                <Button variant="outline" size="sm" class="h-7 text-xs text-green-700" @click="confirmAction(t.id, 'success')">
                                                    <CheckCircle class="mr-1 h-3 w-3" />
                                                    Make Success
                                                </Button>
                                                <Button variant="outline" size="sm" class="h-7 text-xs text-red-700" @click="confirmAction(t.id, 'failed')">
                                                    <XCircle class="mr-1 h-3 w-3" />
                                                    Make Failed
                                                </Button>
                                            </template>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <Separator v-if="manualBills.length > 0" />

                <!-- All Bills History -->
                <div>
                    <HeadingSmall title="Riwayat Tagihan" :description="`${allBills.length} tagihan`" />
                    <div class="overflow-hidden rounded-lg border">
                        <table class="w-full text-sm">
                            <thead class="border-b bg-muted/50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-medium">#</th>
                                    <th class="px-4 py-3 text-left font-medium">Total</th>
                                    <th class="px-4 py-3 text-left font-medium">Mulai Sewa</th>
                                    <th class="px-4 py-3 text-left font-medium">Akhir Sewa</th>
                                    <th class="px-4 py-3 text-left font-medium">Status</th>
                                    <!-- <th class="px-4 py-3 text-right font-medium">Aksi</th> -->
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="allBills.length === 0">
                                    <td colspan="6" class="px-4 py-8 text-center text-muted-foreground">Belum ada bill.</td>
                                </tr>
                                <tr v-for="(bill, idx) in allBills" :key="bill.id" class="border-b last:border-0">
                                    <td class="px-4 py-3 font-medium">{{ idx + 1 }}</td>
                                    <td class="px-4 py-3">{{ formatCurrency(bill.total_price) }}</td>
                                    <td class="px-4 py-3">{{ bill.start_date }}</td>
                                    <td class="px-4 py-3">{{ bill.due_date }}</td>
                                    <td class="px-4 py-3">
                                        <span :class="statusBadge(bill.status)" class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium capitalize">
                                            {{ bill.status }} <span v-if="bill.status === 'unpaid' && bill.transactions.some(t => t.status === 'failed')"> (Failed Transaction)</span>
                                        </span>
                                    </td>
                                    <!-- <td class="px-4 py-3 text-right">
                                        <Button variant="ghost" size="sm" class="h-7 text-xs" @click="openStatusDialog(bill)"> Ubah Status </Button>
                                    </td> -->
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            </div>
        </div>

        <!-- Create Bill Dialog -->
        <Dialog v-model:open="showBillDialog">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Buat Bill Baru</DialogTitle>
                    <DialogDescription> Buat tagihan untuk {{ tenant.name }}. </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitBill" class="space-y-4">
                    <!-- Category selection -->
                    <div class="grid gap-2">
                        <Label for="bill_category_id">Kategori</Label>
                        <select
                            id="bill_category_id"
                            v-model="billForm.category_id"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                        >
                            <option value="" disabled>Pilih kategori...</option>
                            <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                                {{ cat.kost_name }} — {{ cat.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Start Date -->
                    <div class="grid gap-2">
                        <Label for="start_date">Tanggal Mulai</Label>
                        <Input id="start_date" v-model="billForm.start_date" type="date" />
                    </div>

                    <!-- Pricing -->
                    <div class="grid gap-2">
                        <Label for="pricing_id">Durasi / Paket</Label>
                        <select
                            id="pricing_id"
                            v-model="billForm.pricing_id"
                            :disabled="!billForm.category_id || !billForm.start_date"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:opacity-50"
                        >
                            <option value="" disabled>Pilih durasi...</option>
                            <option v-for="p in filteredPricings" :key="p.id" :value="p.id">
                                {{ formatDuration(p.duration_days) }} — {{ formatCurrency(p.price) }}
                            </option>
                        </select>
                    </div>

                    <!-- Room selection (filtered by category and date) -->
                    <div class="grid gap-2">
                        <Label for="bill_room_id">Room</Label>
                        <select
                            id="bill_room_id"
                            v-model="billForm.room_id"
                            :disabled="!billForm.category_id || !billForm.pricing_id || !billForm.start_date"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:opacity-50"
                        >
                            <option value="" disabled>Pilih room...</option>
                            <option v-for="room in filteredRooms" :key="room.id" :value="room.id">
                                {{ room.kost_name }} — {{ room.room_number }}
                            </option>
                        </select>
                    </div>

                    <!-- Due Date (auto) -->
                    <div class="grid gap-2">
                        <Label for="due_date">Jatuh Tempo</Label>
                        <Input id="due_date" v-model="billForm.due_date" type="date" disabled />
                    </div>

                    <!-- Total Price (auto) -->
                    <div class="grid gap-2">
                        <Label for="total_price">Total Harga</Label>
                        <Input id="total_price" :model-value="formatCurrency(billForm.total_price)" disabled />
                    </div>

                    <DialogFooter>
                        <DialogClose as-child>
                            <Button type="button" variant="secondary">Batal</Button>
                        </DialogClose>
                        <Button type="submit" :disabled="billForm.processing || !billForm.room_id || !billForm.pricing_id || !billForm.start_date">
                            <LoaderCircle v-if="billForm.processing" class="mr-1.5 h-4 w-4 animate-spin" />
                            Buat Bill
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Update Status Dialog -->
        <Dialog v-model:open="showStatusDialog">
            <DialogContent class="sm:max-w-sm">
                <DialogHeader>
                    <DialogTitle>Ubah Status Bill</DialogTitle>
                    <DialogDescription v-if="selectedBill">
                        Bill {{ formatCurrency(selectedBill.total_price) }}
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-2">
                    <Label for="new_status">Status</Label>
                    <select
                        id="new_status"
                        v-model="newStatus"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                    >
                        <option value="unpaid">Unpaid</option>
                        <option value="paid">Paid</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>

                <DialogFooter>
                    <DialogClose as-child>
                        <Button variant="secondary">Batal</Button>
                    </DialogClose>
                    <Button @click="updateStatus">Simpan</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

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
