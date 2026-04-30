<script setup lang="ts">
import Pagination from '@/components/Pagination.vue';
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
import Heading from '@/components/Heading.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { useToast } from '@/composables/useToast';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { LogOut, Search } from 'lucide-vue-next';
import { ref, watch } from 'vue';

const { success: toastSuccess, error: toastError } = useToast();

interface OccupancyItem {
    tenant_id: number;
    tenant_name: string;
    room_id: number;
    room_number: string;
    kost_name: string;
    start_date: string | null;
    due_date: string | null;
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
    occupancies: Paginator<OccupancyItem>;
    filters: { search: string };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Check Out', href: '/management/checkouts' },
];

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

const search = ref(props.filters.search ?? '');
const showCheckoutDialog = ref(false);
const occupancyToCheckout = ref<OccupancyItem | null>(null);

const flash = computed(() => {
    const page = router.page as any;
    return page?.props?.flash as { success?: string; error?: string } | undefined;
});

const applyFilter = useDebounceFn(() => {
    router.get(
        route('management.checkouts.index'),
        { search: search.value },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}, 300);

watch(search, applyFilter);

const confirmCheckout = (occupancy: OccupancyItem) => {
    occupancyToCheckout.value = occupancy;
    showCheckoutDialog.value = true;
};

const processCheckout = () => {
    if (!occupancyToCheckout.value) return;
    router.post(route('management.checkouts.process', {
        tenant: occupancyToCheckout.value.tenant_id,
        room: occupancyToCheckout.value.room_id
    }), {}, {
        onSuccess: () => {
            toastSuccess('Check out berhasil diproses.');
        },
        onError: () => {
            toastError('Gagal melakukan check out.');
        },
        onFinish: () => {
            showCheckoutDialog.value = false;
            occupancyToCheckout.value = null;
        },
    });
};
</script>

<template>
    <Head title="Check Out" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-muted/40 py-10">
            <div class="max-w-7xl mx-auto px-6 space-y-8">

                <!-- Header -->
                <div class="flex flex-col justify-between gap-4">
                    <div class="flex flex-row justify-between items-center">
                        <Heading
                            title="Manage Check Outs"
                            :description="`Proses check out tenant dari kamar. Total Penghuni: ${occupancies.total}`"
                        />
                    </div>
                </div>

                <!-- Search -->
                <div class="relative max-w-sm">
                    <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input v-model="search" placeholder="Cari nama tenant atau kamar..." class="pl-9" />
                </div>

                <!-- Table -->
                <div class="overflow-hidden rounded-lg border">
                    <table class="w-full text-sm">
                        <thead class="border-b bg-muted/50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium">Nama Tenant</th>
                                <th class="px-4 py-3 text-left font-medium">Kost</th>
                                <th class="px-4 py-3 text-left font-medium">Kamar</th>
                                <th class="px-4 py-3 text-left font-medium">Periode Sewa (Bill)</th>
                                <th class="px-4 py-3 text-right font-medium">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="occupancies.data.length === 0">
                                <td colspan="5" class="px-4 py-8 text-center text-muted-foreground">Tidak ada tenant yang menempati kamar saat ini.</td>
                            </tr>
                            <tr v-for="occ in occupancies.data" :key="`${occ.tenant_id}-${occ.room_id}`" class="border-b last:border-0">
                                <td class="px-4 py-3 font-medium">{{ occ.tenant_name }}</td>
                                <td class="px-4 py-3">{{ occ.kost_name }}</td>
                                <td class="px-4 py-3 font-semibold">{{ occ.room_number }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div v-if="occ.start_date || occ.due_date" class="text-xs">
                                        <span>{{ formatDate(occ.start_date) }}</span>
                                        <span class="mx-1 text-muted-foreground">→</span>
                                        <span>{{ formatDate(occ.due_date) }}</span>
                                    </div>
                                    <span v-else class="text-xs text-muted-foreground">-</span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <Button variant="outline" size="sm" class="h-8 gap-1.5 text-orange-600 hover:text-orange-700 hover:bg-orange-50 border-orange-200" @click="confirmCheckout(occ)">
                                            <LogOut class="h-4 w-4" />
                                            Check Out
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <Pagination :meta="occupancies" />
            </div>
        </div>

        <!-- Checkout confirmation dialog -->
        <Dialog v-model:open="showCheckoutDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Konfirmasi Check Out</DialogTitle>
                    <DialogDescription>
                        Apakah Anda yakin ingin melakukan check out untuk tenant <strong>{{ occupancyToCheckout?.tenant_name }}</strong> dari kamar <strong>{{ occupancyToCheckout?.room_number }}</strong>? <br><br>
                        Kamar akan kembali menjadi <strong>available</strong> dan status tagihan (bill) akan diubah menjadi <strong>checked out</strong>.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <DialogClose as-child>
                        <Button variant="secondary">Batal</Button>
                    </DialogClose>
                    <Button variant="default" class="bg-orange-600 hover:bg-orange-700" @click="processCheckout">Proses Check Out</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
