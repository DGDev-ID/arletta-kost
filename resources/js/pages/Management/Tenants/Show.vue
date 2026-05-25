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
import { useToast } from '@/composables/useToast';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, CheckCircle, FileText, LoaderCircle, XCircle } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const { error: toastError } = useToast();

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
    transaction_type?: string;
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
    room_number?: string;
    kost_name?: string;
    payment_scheme?: string;
    dp_amount?: number;
    transactions: TransactionItem[];
}

interface PricingItem {
    id: number;
    duration_days: number;
    price: number;
    final_price?: number;
    bonus_days?: number;
    cashback?: number;
    applied_promos?: any[];
}

interface CategoryItem {
    id: number;
    name: string;
    kost_name: string;
    daily_price: number;
    pricings: PricingItem[];      // pricings non-harian (duration_days != 1)
    all_pricings: PricingItem[];  // semua pricings termasuk harian
    gender?: string | null;
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
    { title: 'Penyewa', href: '/management/tenants' },
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
    booking_type: 'monthly' as 'monthly' | 'daily',
    total_price: 0,
    payment_scheme: 'full_pay' as 'full_pay' | 'dp',
    start_date: '',
    due_date: '',
    promo_code: '',
});

// dpAmount and displayTotalPrice are declared below (after promoResult)

// --- Promo ---
interface PromoResult {
    promo_id: number;
    name: string;
    code: string;
    type: string;
    value: number;
    discount_amount: number;
    bonus_days: number;
    final_price: number;
}
const promoCodeInput  = ref('');
const promoResult     = ref<PromoResult | null>(null);
const promoError      = ref('');
const promoLoading    = ref(false);
const dpAmount = computed(() => {
    const basePrice = promoResult.value?.final_price ?? billForm.total_price;
    if (billForm.payment_scheme === 'dp') {
        return basePrice * 0.5;
    }
    return null;
});

const displayTotalPrice = computed(() => promoResult.value?.final_price ?? billForm.total_price);

// Auto-uppercase promo code input
watch(promoCodeInput, (val) => {
    const upper = val.toUpperCase().replace(/[^A-Z0-9_-]/g, '');
    if (upper !== val) promoCodeInput.value = upper;
});

const validatePromo = async () => {
    if (!promoCodeInput.value.trim() || billForm.total_price <= 0) return;
    promoLoading.value = true;
    promoError.value   = '';
    promoResult.value  = null;
    try {
        const res = await fetch('/api/promos/validate', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
            body: JSON.stringify({ code: promoCodeInput.value.trim(), total_price: billForm.total_price }),
        });
        const json = await res.json();
        if (json.success) {
            promoResult.value     = json.data;
            billForm.promo_code   = promoCodeInput.value.trim().toUpperCase();
        } else {
            promoError.value = json.message ?? 'Kode promo tidak valid.';
        }
    } catch {
        promoError.value = 'Gagal memvalidasi promo.';
    } finally {
        promoLoading.value = false;
    }
};

const clearPromo = () => {
    promoCodeInput.value  = '';
    promoResult.value     = null;
    promoError.value      = '';
    billForm.promo_code   = '';
};

// Reset promo when total_price changes
watch(() => billForm.total_price, () => {
    if (promoResult.value) clearPromo();
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

// Pricings filtered by selected category (exclude daily pricing duration_days==1)
const filteredPricings = computed(() => {
    if (!billForm.category_id) return [];
    const cat = props.categories.find(c => c.id === billForm.category_id);
    return cat?.pricings ?? []; // already filtered on backend (no duration_days==1)
});

// Harga harian dari kategori yang dipilih
const selectedCategoryDailyPrice = computed(() => {
    if (!billForm.category_id) return 0;
    const cat = props.categories.find(c => c.id === billForm.category_id);
    return cat?.daily_price ?? 0;
});

// Categories filtered by tenant gender (if tenant.gender is set)
const categoriesForTenant = computed(() => {
    const tGender = props.tenant.gender;
    if (!tGender) return props.categories;
    return props.categories.filter((c: any) => {
        const catGender = c.gender ?? 'mixed';
        if (!catGender) return true;
        if (catGender === 'mixed') return true;
        return String(catGender) === String(tGender);
    });
});

// Map gender codes to Indonesian labels for frontend display
const genderLabel = (g: string | null | undefined): string => {
    if (!g) return '-';
    if (g === 'male') return 'Laki-laki';
    if (g === 'female') return 'Perempuan';
    if (g === 'mixed') return 'Campur';
    return String(g);
};

// Apakah kategori yang dipilih mendukung paket harian
const hasDailyPricing = computed(() => selectedCategoryDailyPrice.value > 0);

const selectedPricing = computed(() => {
    if (!billForm.pricing_id) return null;
    return filteredPricings.value.find((p) => p.id === billForm.pricing_id) ?? null;
});

// Hitung jumlah hari antara start_date dan due_date (untuk mode harian)
const dailyDays = computed(() => {
    if (billForm.booking_type !== 'daily' || !billForm.start_date || !billForm.due_date) return 0;
    const start = new Date(billForm.start_date);
    const end = new Date(billForm.due_date);
    const diff = Math.ceil((end.getTime() - start.getTime()) / (1000 * 60 * 60 * 24));
    return diff > 0 ? diff : 0;
});

// Auto-calculate total_price and due_date when pricing or start_date change (monthly)
watch(
    () => [billForm.pricing_id, billForm.start_date],
    () => {
        if (billForm.booking_type === 'monthly' && selectedPricing.value && billForm.start_date) {
            // Use pricing-level final_price if available (after promos)
            const finalPrice = selectedPricing.value.final_price ?? selectedPricing.value.price;
            billForm.total_price = finalPrice;

            const start = new Date(billForm.start_date);
            const baseDays = Number(selectedPricing.value.duration_days ?? 0);
            const bonus = Number(selectedPricing.value.bonus_days ?? 0);
            start.setDate(start.getDate() + baseDays + bonus);
            billForm.due_date = start.toISOString().split('T')[0];
        }
    },
);

// Auto-calculate total_price when daily days or daily_price change
watch(
    () => [dailyDays.value, selectedCategoryDailyPrice.value],
    () => {
        if (billForm.booking_type === 'daily') {
            billForm.total_price = selectedCategoryDailyPrice.value * dailyDays.value;
        }
    },
);

// Reset fields when booking_type changes
watch(() => billForm.booking_type, (newType) => {
    billForm.pricing_id = '';
    billForm.total_price = 0;
    billForm.due_date = '';
    if (newType === 'daily') {
        billForm.pricing_id = '';
    }
});

// Reset room and pricing when category changes
watch(() => billForm.category_id, () => {
    billForm.room_id = '';
    billForm.pricing_id = '';
    billForm.total_price = 0;
    billForm.due_date = '';
    // Reset booking_type ke monthly bila kategori berubah
    if (billForm.booking_type === 'daily' && !hasDailyPricing.value) {
        billForm.booking_type = 'monthly';
    }
});

// Minimum selectable start date (no restriction - allow past dates)
const minStartDate = computed(() => {
    // Return a date far in the past so there's no practical limitation
    return '1900-01-01';
});


const submitBill = () => {
    // No longer prevent past dates - allow flexibility in billing
    billForm.post(route('management.bills.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showBillDialog.value = false;
            clearPromo();
            billForm.reset('category_id', 'room_id', 'pricing_id', 'booking_type', 'total_price', 'payment_scheme', 'start_date', 'due_date', 'promo_code');
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

    const isSuccess = confirmDialog.value.action === 'success';
    const routeName = isSuccess
        ? 'management.bills.make-success'
        : 'management.bills.make-failed';

    router.patch(route(routeName, confirmDialog.value.transactionId), {}, {
        preserveScroll: true,
        onFinish: () => {
            confirmDialog.value.show = false;
        }
    });
};

const formatDate = (dateString: string | null | undefined) => {
    if (!dateString) return '-';
    
    // Memisahkan string "YYYY-MM-DD" menjadi array ["YYYY", "MM", "DD"]
    const parts = dateString.split(' ')[0].split('-'); 
    
    if (parts.length === 3) {
        // Gabungkan kembali dengan format DD-MM-YYYY
        return `${parts[2]}-${parts[1]}-${parts[0]}`;
    }
    
    return dateString;
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

const dpPendingBills = computed(() => {
    return props.bills.filter(bill => {
        return bill.payment_scheme === 'dp' && bill.status === 'down_payment';
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
                            class="cursor-pointer inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-medium text-white shadow transition-colors hover:bg-blue-700 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">                            <FileText class="h-4 w-4" />
                            Create Bill
                        </button>
                        <!-- <Link :href="route('management.tenants.edit', tenant.id)"
                            class="cursor-pointer inline-flex items-center justify-center gap-2 rounded-xl border border-input bg-background px-4 py-2.5 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                            Edit Tenant
                        </Link> -->
                    </div>
                </div>
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
                        <p class="text-sm font-medium">{{ tenant.gender ? genderLabel(tenant.gender) : '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">NIK</p>
                        <p class="text-sm font-medium">{{ tenant.nik ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Tempat, Tanggal Lahir</p>
                        <p class="text-sm font-medium">{{ tenant.birth_place ?? '-' }}{{ tenant.birth_date ? `, ${formatDate(tenant.birth_date)}` : '' }}</p>
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
                                {{ formatDate (room.start_date) }} sampai {{ formatDate(room.end_date) }}
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
                                    <th class="px-4 py-3 text-left font-medium">Ruangan</th>
                                    <th class="px-4 py-3 text-left font-medium">Total</th>
                                    <th class="px-4 py-3 text-left font-medium">Mulai</th>
                                    <th class="px-4 py-3 text-left font-medium">Habis Masa</th>
                                    <th class="px-4 py-3 text-left font-medium">Status</th>
                                    <th class="px-4 py-3 text-left font-medium">Transaction</th>
                                    <th class="px-4 py-3 text-right font-medium">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="bill in manualBills" :key="bill.id" class="border-b last:border-0">
                                    <td class="px-4 py-3">
                                        <p class="font-medium">{{ bill.room_number }}</p>
                                        <p class="text-xs text-muted-foreground">{{ bill.kost_name }}</p>
                                    </td>
                                    <td class="px-4 py-3">{{ formatCurrency(bill.total_price) }}</td>
                                    <td class="px-4 py-3">{{ formatDate(bill.start_date) }}</td>
                                    <td class="px-4 py-3">{{ formatDate(bill.due_date) }}</td>
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

                <!-- DP Waiting for Remaining Payment -->
                <div v-if="dpPendingBills.length > 0">
                    <HeadingSmall title="Menunggu Pelunasan (DP)" :description="`${dpPendingBills.length} tagihan menunggu pelunasan`" />
                    <div class="overflow-hidden rounded-lg border">
                        <table class="w-full text-sm">
                            <thead class="border-b bg-muted/50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-medium">Ruangan</th>
                                    <th class="px-4 py-3 text-left font-medium">Total Bill</th>
                                    <th class="px-4 py-3 text-left font-medium">Sudah Dibayar</th>
                                    <th class="px-4 py-3 text-left font-medium">Sisa Pembayaran</th>
                                    <th class="px-4 py-3 text-left font-medium">Habis Masa</th>
                                    <th class="px-4 py-3 text-left font-medium">Status</th>
                                    <th class="px-4 py-3 text-right font-medium">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="bill in dpPendingBills" :key="bill.id" class="border-b last:border-0">
                                    <td class="px-4 py-3">
                                        <p class="font-medium">{{ bill.room_number }}</p>
                                        <p class="text-xs text-muted-foreground">{{ bill.kost_name }}</p>
                                    </td>
                                    <td class="px-4 py-3">{{ formatCurrency(bill.total_price) }}</td>
                                    <td class="px-4 py-3">{{ formatCurrency(bill.dp_amount || 0) }}</td>
                                    <td class="px-4 py-3 font-semibold text-orange-600 dark:text-orange-400">{{ formatCurrency(bill.total_price - (bill.dp_amount || 0)) }}</td>
                                    <td class="px-4 py-3">{{ formatDate(bill.due_date) }}</td>
                                    <td class="px-4 py-3">
                                        <span :class="statusBadge(bill.status)" class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium capitalize">
                                            {{ bill.status }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex items-center justify-end gap-1">
                                            <template v-for="t in bill.transactions.filter(t => t.transaction_type === 'finished_payment' && t.status === 'pending')" :key="t.id">
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

                <Separator v-if="dpPendingBills.length > 0" />

                <!-- All Bills History -->
                <div>
                    <HeadingSmall title="Riwayat Tagihan" :description="`${allBills.length} tagihan`" />
                    <div class="overflow-hidden rounded-lg border">
                        <table class="w-full text-sm">
                            <thead class="border-b bg-muted/50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-medium">#</th>
                                    <th class="px-4 py-3 text-left font-medium">Ruangan</th>
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
                                    <td class="px-4 py-3">
                                        <p class="font-medium">{{ bill.room_number }}</p>
                                        <p class="text-xs text-muted-foreground">{{ bill.kost_name }}</p>
                                    </td>
                                    <td class="px-4 py-3">{{ formatCurrency(bill.total_price) }}</td>
                                    <td class="px-4 py-3">{{formatDate (bill.start_date) }}</td>
                                    <td class="px-4 py-3">{{ formatDate (bill.due_date) }}</td>
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
            <DialogContent class="max-h-[85vh] overflow-y-auto hide-scrollbar sm:max-w-md">
                <DialogHeader >
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
                            :disabled="categoriesForTenant.length === 0"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                        >
                            <option value="" disabled>Pilih kategori...</option>
                            <option v-for="cat in categoriesForTenant" :key="cat.id" :value="cat.id">
                                {{ cat.kost_name }} — {{ cat.name }}
                            </option>
                        </select>

                        <p v-if="categoriesForTenant.length === 0" class="text-sm text-muted-foreground">
                            Tidak ada kategori ruangan yang tersedia untuk {{ props.tenant.gender ? genderLabel(props.tenant.gender) : 'tenant ini' }}.
                        </p>
                    </div>

                    <!-- Booking Type Toggle -->
                    <div class="grid gap-2" v-if="billForm.category_id">
                        <Label>Tipe Durasi</Label>
                        <div class="flex rounded-md border border-input overflow-hidden">
                            <button
                                type="button"
                                @click="billForm.booking_type = 'monthly'"
                                :class="billForm.booking_type === 'monthly'
                                    ? 'bg-primary text-primary-foreground'
                                    : 'bg-background text-foreground hover:bg-muted'"
                                class="flex-1 px-4 py-2 text-sm font-medium transition-colors"
                            >
                                Bulanan
                            </button>
                            <button
                                type="button"
                                @click="billForm.booking_type = 'daily'"
                                :disabled="!hasDailyPricing"
                                :title="!hasDailyPricing ? 'Kategori ini tidak memiliki harga harian' : ''"
                                :class="billForm.booking_type === 'daily'
                                    ? 'bg-primary text-primary-foreground'
                                    : 'bg-background text-foreground hover:bg-muted disabled:opacity-40 disabled:cursor-not-allowed'"
                                class="flex-1 px-4 py-2 text-sm font-medium transition-colors"
                            >
                                Harian
                            </button>
                        </div>
                        <p v-if="billForm.booking_type === 'daily' && hasDailyPricing" class="text-xs text-muted-foreground">
                            Harga harian: <strong>{{ formatCurrency(selectedCategoryDailyPrice) }}</strong>/hari
                        </p>
                        <p v-if="!hasDailyPricing && billForm.category_id" class="text-xs text-orange-500">
                            Kategori ini belum memiliki pricing harian (duration_days = 1).
                        </p>
                    </div>

                    <!-- Start Date -->
                    <div class="grid gap-2">
                        <Label for="start_date">Tanggal Mulai</Label>
                        <Input id="start_date" v-model="billForm.start_date" type="date" :min="minStartDate" />
                    </div>

                    <!-- [MONTHLY] Pricing dropdown -->
                    <div class="grid gap-2" v-if="billForm.booking_type === 'monthly'">
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

                    <!-- [DAILY] End Date picker -->
                    <div class="grid gap-2" v-if="billForm.booking_type === 'daily'">
                        <Label for="daily_due_date">Tanggal Selesai</Label>
                        <Input
                            id="daily_due_date"
                            v-model="billForm.due_date"
                            type="date"
                            :min="billForm.start_date"
                            :disabled="!billForm.start_date"
                        />
                        <!-- Daily summary -->
                        <div
                            v-if="dailyDays > 0"
                            class="rounded-md bg-blue-50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800 px-3 py-2 text-xs text-blue-700 dark:text-blue-300"
                        >
                            <span class="font-medium">{{ dailyDays }} hari</span>
                            × {{ formatCurrency(selectedCategoryDailyPrice) }} =
                            <span class="font-semibold">{{ formatCurrency(billForm.total_price) }}</span>
                        </div>
                    </div>

                    <!-- Room selection (filtered by category and date) -->
                    <div class="grid gap-2">
                        <Label for="bill_room_id">Room</Label>
                        <select
                            id="bill_room_id"
                            v-model="billForm.room_id"
                            :disabled="!billForm.category_id || !billForm.start_date || (billForm.booking_type === 'monthly' ? !billForm.pricing_id : !billForm.due_date)"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:opacity-50"
                        >
                            <option value="" disabled>Pilih room...</option>
                            <option v-for="room in filteredRooms" :key="room.id" :value="room.id">
                                {{ room.kost_name }} — {{ room.room_number }}
                            </option>
                        </select>
                    </div>

                    <!-- [MONTHLY] Due Date (auto-filled) -->
                    <div class="grid gap-2" v-if="billForm.booking_type === 'monthly'">
                        <Label for="due_date">Habis Masa</Label>
                        <Input id="due_date" v-model="billForm.due_date" type="date" disabled />
                    </div>

                    <!-- Total Price (auto) -->
                    <div class="grid gap-2">
                        <Label for="total_price">Total Harga</Label>
                        <Input id="total_price" :model-value="formatCurrency(displayTotalPrice)" disabled />
                        <p class="text-xs text-muted-foreground mt-1">
                            Catatan: Harga di atas sudah memperhitungkan promo yang berlaku pada kategori kamar (paket bulanan atau harga harian). Untuk promo tambahan, masukkan kode promo di bawah untuk potongan lebih lanjut.
                        </p>
                    </div>

                    <!-- Promo Code -->
                    <div class="grid gap-2">
                        <Label>Kode Promo (Opsional)</Label>
                        <div class="flex gap-2">
                            <Input
                                v-model="promoCodeInput"
                                placeholder="Masukkan kode promo..."
                                class="font-mono uppercase tracking-widest"
                                :disabled="!!promoResult || billForm.total_price <= 0"
                            />
                            <Button
                                v-if="!promoResult"
                                type="button"
                                variant="outline"
                                size="sm"
                                class="shrink-0"
                                :disabled="!promoCodeInput.trim() || billForm.total_price <= 0 || promoLoading"
                                @click="validatePromo"
                            >
                                <LoaderCircle v-if="promoLoading" class="h-4 w-4 animate-spin" />
                                <span v-else>Pakai</span>
                            </Button>
                            <Button
                                v-else
                                type="button"
                                variant="ghost"
                                size="sm"
                                class="shrink-0 text-destructive"
                                @click="clearPromo"
                            >
                                Hapus
                            </Button>
                        </div>
                        <p v-if="promoError" class="text-xs text-destructive">{{ promoError }}</p>
                        <div
                            v-if="promoResult"
                            class="rounded-md bg-green-50 dark:bg-green-950/30 border border-green-200 dark:border-green-800 px-3 py-2 text-xs text-green-700 dark:text-green-300 space-y-0.5"
                        >
                            <p class="font-semibold">{{ promoResult.name }}</p>
                            <p v-if="promoResult.discount_amount > 0">
                                Diskon: <strong>{{ formatCurrency(promoResult.discount_amount) }}</strong>
                                → Harga akhir: <strong>{{ formatCurrency(promoResult.final_price) }}</strong>
                            </p>
                            <p v-if="promoResult.bonus_days > 0">
                                Bonus <strong>{{ promoResult.bonus_days }} hari</strong> menginap tambahan
                            </p>
                        </div>
                        <p v-if="billForm.total_price <= 0 && !promoResult" class="text-xs text-muted-foreground">
                            Pilih paket terlebih dahulu sebelum menggunakan promo.
                        </p>
                    </div>

                    <!-- Payment Scheme -->
                    <div class="grid gap-2">
                        <Label for="payment_scheme">Skema Pembayaran</Label>
                        <select
                            id="payment_scheme"
                            v-model="billForm.payment_scheme"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                        >
                            <option value="full_pay">Full Pay</option>
                            <option value="dp">Down Payment (DP 50%)</option>
                        </select>
                    </div>

                    <!-- DP Amount (If DP selected) -->
                    <div class="grid gap-2" v-if="billForm.payment_scheme === 'dp'">
                        <Label>Nominal DP yang akan ditagihkan (50%)</Label>
                        <Input :model-value="formatCurrency(dpAmount || 0)" disabled class="bg-blue-50/50" />
                    </div>

                    <DialogFooter>
                        <DialogClose as-child>
                            <Button type="button" variant="secondary">Batal</Button>
                        </DialogClose>
                        <Button
                            type="submit"
                            :disabled="billForm.processing
                                || !billForm.room_id
                                || !billForm.start_date
                                || !billForm.due_date
                                || (billForm.booking_type === 'monthly' && !billForm.pricing_id)
                                || (billForm.booking_type === 'daily' && dailyDays <= 0)"
                        >
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

<style>
/* Hide native scrollbars but keep scrolling behavior */
.hide-scrollbar {
    -ms-overflow-style: none; /* IE and Edge */
    scrollbar-width: none; /* Firefox */
}
.hide-scrollbar::-webkit-scrollbar {
    display: none; /* Chrome, Safari, Opera */
}
</style>
