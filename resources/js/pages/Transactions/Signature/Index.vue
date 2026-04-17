<script setup lang="ts">
import Pagination from '@/components/Pagination.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { ref, nextTick, onMounted } from 'vue';

interface BillItem {
    id: number;
    room_number: string;
    tenant_name: string;
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
    bills: Paginator<BillItem>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Transactions', href: '/transactions' },
    { title: 'Signature', href: '#' },
];

const formatDate = (d: string | null) => d ?? '-';

const showModal = ref(false);
const selectedBill = ref<null | BillItem>(null);
const canvasRef = ref<HTMLCanvasElement | null>(null);
let ctx: CanvasRenderingContext2D | null = null;
const drawing = ref(false);
const form = useForm({ signature: '' });

const openModal = async (bill: BillItem) => {
    selectedBill.value = bill;
    showModal.value = true;
    await nextTick();
    initCanvas();
};

const initCanvas = () => {
    const canvas = canvasRef.value;
    if (!canvas) return;
    const width = 700; // px
    const height = 200; // px
    const ratio = window.devicePixelRatio || 1;
    canvas.width = width * ratio;
    canvas.height = height * ratio;
    canvas.style.width = width + 'px';
    canvas.style.height = height + 'px';
    ctx = canvas.getContext('2d');
    if (!ctx) return;
    ctx.scale(ratio, ratio);
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';
    ctx.strokeStyle = '#000000';
    ctx.lineWidth = 2;

    // attach pointer events
    canvas.onpointerdown = (e: PointerEvent) => {
        drawing.value = true;
        const rect = canvas.getBoundingClientRect();
        ctx!.beginPath();
        ctx!.moveTo(e.clientX - rect.left, e.clientY - rect.top);
    };
    canvas.onpointermove = (e: PointerEvent) => {
        if (!drawing.value) return;
        const rect = canvas.getBoundingClientRect();
        ctx!.lineTo(e.clientX - rect.left, e.clientY - rect.top);
        ctx!.stroke();
    };
    canvas.onpointerup = () => {
        drawing.value = false;
    };
    canvas.onpointerleave = () => {
        drawing.value = false;
    };
};

const clearCanvas = () => {
    const canvas = canvasRef.value;
    if (!canvas || !ctx) return;
    // reset transform then clear
    ctx.setTransform(1, 0, 0, 1, 0, 0);
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    initCanvas();
};

const submitSignature = () => {
    const canvas = canvasRef.value;
    if (!canvas || !selectedBill.value) return;
    const dataUrl = canvas.toDataURL('image/png');
    form.signature = dataUrl;
    form.patch(route('transactions.signatures.sign', selectedBill.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showModal.value = false;
            // reload the page to refresh list
            location.reload();
        },
    });
};
</script>

<template>
    <Head title="Signature" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-muted/40 py-10">
            <div class="max-w-7xl mx-auto px-6 space-y-8">

                <Heading title="Signatures" description="Bills berstatus paid yang belum ditandatangani." />

                <div class="overflow-hidden rounded-lg border">
                    <table class="w-full text-sm">
                        <thead class="border-b bg-muted/50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium">#</th>
                                <th class="px-4 py-3 text-left font-medium">Room</th>
                                <th class="px-4 py-3 text-left font-medium">Tenant</th>
                                <th class="px-4 py-3 text-left font-medium">Start</th>
                                <th class="px-4 py-3 text-left font-medium">Due</th>
                                <th class="px-4 py-3 text-right font-medium">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="bills.data.length === 0">
                                <td colspan="6" class="px-4 py-8 text-center text-muted-foreground">Tidak ada bill untuk ditandatangani.</td>
                            </tr>
                            <tr v-for="(b, idx) in bills.data" :key="b.id" class="border-b last:border-0">
                                <td class="px-4 py-3 font-medium">{{ (bills.from ?? 0) + idx }}</td>
                                <td class="px-4 py-3">{{ b.room_number }}</td>
                                <td class="px-4 py-3">{{ b.tenant_name }}</td>
                                <td class="px-4 py-3 text-xs">{{ formatDate(b.start_date) }}</td>
                                <td class="px-4 py-3 text-xs">{{ formatDate(b.due_date) }}</td>
                                <td class="px-4 py-3 text-right">
                                    <Button size="sm" @click="openModal(b)">Signature</Button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Signature Modal -->
                <Dialog v-model:open="showModal">
                    <DialogContent class="sm:max-w-3xl">
                        <DialogHeader>
                            <DialogTitle>Tanda Tangan</DialogTitle>
                        </DialogHeader>

                        <div class="p-4">
                            <p class="text-sm text-muted-foreground mb-2">Gambar tanda tangan pada area di bawah, lalu klik Save.</p>
                            <div class="w-full overflow-auto">
                                <canvas ref="canvasRef" class="w-full rounded border bg-white"></canvas>
                            </div>
                        </div>

                        <DialogFooter>
                            <DialogClose as-child>
                                <Button variant="secondary">Batal</Button>
                            </DialogClose>
                            <Button variant="ghost" @click="clearCanvas">Clear</Button>
                            <Button @click="submitSignature" :disabled="form.processing">Save</Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>

                <Pagination :meta="bills" />

            </div>
        </div>
    </AppLayout>
</template>
