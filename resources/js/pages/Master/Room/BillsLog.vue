<script setup lang="ts">
import Pagination from '@/components/Pagination.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Input } from '@/components/ui/input';
import { useDebounceFn } from '@vueuse/core';
import { Search } from 'lucide-vue-next';
import { ref, watch } from 'vue';

interface BillItem {
    id: number;
    tenant_name?: string;
    tenant_phone?: string;
    start_date?: string;
    due_date?: string;
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
    room: { id: number; room_number: string; category_name?: string; kost_name?: string };
    bills: Paginator<BillItem>;
    filters: { search?: string };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Room', href: '/master/rooms' },
    { title: 'Room Log', href: `/master/rooms/${props.room.id}/bills/log` },
];

const search = ref(props.filters?.search ?? '');

const applyFilter = useDebounceFn(() => {
    router.get(
        route('master.rooms.bills.log', props.room.id),
        { search: search.value || undefined },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}, 300);

watch(search, applyFilter);

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value);
};
</script>

<template>
    <Head title="Room Log" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-muted/40 py-10">
            <div class="max-w-7xl mx-auto px-6 space-y-8">

                <div class="flex flex-col justify-between gap-4">
                    <div class="flex flex-row justify-between item-center">
                        <Heading
                            title="Room Log"
                            :description="`Log penggunaan room: ${props.room.room_number} (kemarin, hari ini, masa depan)`"
                        />

                        <div>
                            <Link :href="route('master.rooms.index')" class="cursor-pointer inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-medium text-primary-foreground shadow transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50">
                                Kembali
                            </Link>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <div class="relative max-w-sm flex-1">
                        <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input v-model="search" placeholder="Cari tenant..." class="pl-9" />
                    </div>
                </div>

                <div class="overflow-hidden rounded-lg border">
                    <table class="w-full text-sm">
                        <thead class="border-b bg-muted/50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium">No</th>
                                <th class="px-4 py-3 text-left font-medium">Tenant Name</th>
                                <th class="px-4 py-3 text-left font-medium">Tenant Phone</th>
                                <th class="px-4 py-3 text-left font-medium">Start Date</th>
                                <th class="px-4 py-3 text-left font-medium">Due Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="bills.data.length === 0">
                                <td colspan="5" class="px-4 py-8 text-center text-muted-foreground">Tidak ada tagihan berstatus paid untuk room ini.</td>
                            </tr>
                            <tr v-for="(bill, index) in bills.data" :key="bill.id" class="border-b last:border-0">
                                <td class="px-4 py-3">{{ (bills.from ?? 0) + index }}</td>
                                <td class="px-4 py-3">{{ bill.tenant_name ?? '-' }}</td>
                                <td class="px-4 py-3">{{ bill.tenant_phone ?? '-' }}</td>
                                <td class="px-4 py-3">{{ bill.start_date }}</td>
                                <td class="px-4 py-3">{{ bill.due_date }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <Pagination :meta="bills" />
            </div>
        </div>
    </AppLayout>
</template>
