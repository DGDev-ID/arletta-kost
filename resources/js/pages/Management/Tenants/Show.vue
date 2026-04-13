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
import { ArrowLeft, FileText, LoaderCircle } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface TenantData {
    id: number;
    name: string;
    email: string;
    phone_number: string;
    nik: string | null;
    ktp_number: string | null;
    birth_place: string | null;
    birth_date: string | null;
    gender: string | null;
    address: string | null;
    room_id: number;
    room_number: string;
    kost_name: string;
    category_name: string;
}

interface BillItem {
    id: number;
    total_price: number;
    start_date: string;
    due_date: string;
    status: string;
}

interface PricingItem {
    id: number;
    duration_days: number;
    price: number;
}

const props = defineProps<{
    tenant: TenantData;
    bills: BillItem[];
    pricings: PricingItem[];
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

// --- Bill Form ---
const showBillDialog = ref(false);

const billForm = useForm({
    tenant_id: props.tenant.id,
    room_id: props.tenant.room_id,
    pricing_id: '' as number | '',
    total_price: 0,
    start_date: '',
    due_date: '',
});

const selectedPricing = computed(() => {
    if (!billForm.pricing_id) return null;
    return props.pricings.find((p) => p.id === billForm.pricing_id) ?? null;
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

const submitBill = () => {
    billForm.post(route('management.bills.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showBillDialog.value = false;
            billForm.reset('pricing_id', 'total_price', 'start_date', 'due_date');
        },
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
        cancelled: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-400',
        refund_request: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
        refund: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
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
                                :description="`${tenant.kost_name} — Kamar ${tenant.room_number}`" />
                    </div>

                    <div class="flex gap-2">
                        <button
                            @click="showBillDialog = true"
                            class="cursor-pointer inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-medium text-primary-foreground shadow transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                            <FileText class="h-4 w-4" />
                            Create Bill
                        </button>
                        <Link :href="route('management.tenants.edit', tenant.id)"
                            class="cursor-pointer inline-flex items-center justify-center gap-2 rounded-xl border border-input bg-background px-4 py-2.5 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                            Edit Tenant
                        </Link>
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

            <!-- Tenant Info -->
            <div class="rounded-lg border p-4">
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
                        <p class="text-xs text-muted-foreground">No. KTP</p>
                        <p class="text-sm font-medium">{{ tenant.ktp_number ?? '-' }}</p>
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

                <Separator class="my-4" />

                <HeadingSmall title="Info Room" />
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div>
                        <p class="text-xs text-muted-foreground">Kost</p>
                        <p class="text-sm font-medium">{{ tenant.kost_name }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Kategori</p>
                        <p class="text-sm font-medium">{{ tenant.category_name }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">No. Kamar</p>
                        <p class="text-sm font-medium">{{ tenant.room_number }}</p>
                    </div>
                </div>
            </div>

            <!-- Bills Table -->
            <div>
                <HeadingSmall title="Riwayat Tagihan" :description="`${bills.length} tagihan`" />
                <div class="overflow-hidden rounded-lg border">
                    <table class="w-full text-sm">
                        <thead class="border-b bg-muted/50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium">#</th>
                                <th class="px-4 py-3 text-left font-medium">Total</th>
                                <th class="px-4 py-3 text-left font-medium">Mulai</th>
                                <th class="px-4 py-3 text-left font-medium">Jatuh Tempo</th>
                                <th class="px-4 py-3 text-left font-medium">Status</th>
                                <th class="px-4 py-3 text-right font-medium">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="bills.length === 0">
                                <td colspan="6" class="px-4 py-8 text-center text-muted-foreground">Belum ada bill.</td>
                            </tr>
                            <tr v-for="(bill, idx) in bills" :key="bill.id" class="border-b last:border-0">
                                <td class="px-4 py-3 font-medium">{{ idx + 1 }}</td>
                                <td class="px-4 py-3">{{ formatCurrency(bill.total_price) }}</td>
                                <td class="px-4 py-3">{{ bill.start_date }}</td>
                                <td class="px-4 py-3">{{ bill.due_date }}</td>
                                <td class="px-4 py-3">
                                    <span :class="statusBadge(bill.status)" class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium capitalize">
                                        {{ bill.status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <Button variant="ghost" size="sm" class="h-7 text-xs" @click="openStatusDialog(bill)"> Ubah Status </Button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            </div>
        </div>

        <!-- Create Bill Dialog -->
        <Dialog v-model:open="showBillDialog">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Buat Bill Baru</DialogTitle>
                    <DialogDescription> Buat tagihan untuk {{ tenant.name }} di kamar {{ tenant.room_number }}. </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitBill" class="space-y-4">
                    <!-- Pricing -->
                    <div class="grid gap-2">
                        <Label for="pricing_id">Durasi / Paket</Label>
                        <select
                            id="pricing_id"
                            v-model="billForm.pricing_id"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                        >
                            <option value="" disabled>Pilih durasi...</option>
                            <option v-for="p in pricings" :key="p.id" :value="p.id">
                                {{ formatDuration(p.duration_days) }} — {{ formatCurrency(p.price) }}
                            </option>
                        </select>
                    </div>

                    <!-- Start Date -->
                    <div class="grid gap-2">
                        <Label for="start_date">Tanggal Mulai</Label>
                        <Input id="start_date" v-model="billForm.start_date" type="date" />
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
                        <Button type="submit" :disabled="billForm.processing || !billForm.pricing_id || !billForm.start_date">
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
    </AppLayout>
</template>
