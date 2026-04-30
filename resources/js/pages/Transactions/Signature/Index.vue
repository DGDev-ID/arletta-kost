<script setup lang="ts">
import Pagination from '@/components/Pagination.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { useToast } from '@/composables/useToast';
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
import { ref, nextTick, computed } from 'vue';
import { Pencil, Plus, Trash2, GripVertical, Save, X } from 'lucide-vue-next';

const { success: toastSuccess, error: toastError } = useToast();

interface BillItem {
    id: number;
    room_number: string;
    tenant_name: string;
    start_date: string | null;
    due_date: string | null;
}

interface SignedBillItem {
    id: number;
    room_number: string;
    tenant_name: string;
    start_date: string | null;
    due_date: string | null;
    signature: string | null;
}

interface TermItem {
    id: number;
    order: number;
    content: string;
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
    signedBills: SignedBillItem[];
    terms: TermItem[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Transactions', href: '/transactions' },
    { title: 'Signature', href: '#' },
];

const formatDate = (d: string | null) => d ?? '-';

// ─── Signature modal ────────────────────────────────────────────────────────
const showModal = ref(false);
const selectedBill = ref<null | BillItem>(null);
const canvasRef = ref<HTMLCanvasElement | null>(null);
let ctx: CanvasRenderingContext2D | null = null;
const drawing = ref(false);
const agreedToTerms = ref(false);
const form = useForm({ signature: '' });

const openModal = async (bill: BillItem) => {
    selectedBill.value = bill;
    agreedToTerms.value = false;
    showModal.value = true;
    await nextTick();
    initCanvas();
};

const initCanvas = () => {
    const canvas = canvasRef.value;
    if (!canvas) return;
    const width = 700;
    const height = 200;
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
    canvas.onpointerup = () => { drawing.value = false; };
    canvas.onpointerleave = () => { drawing.value = false; };
};

const clearCanvas = () => {
    const canvas = canvasRef.value;
    if (!canvas || !ctx) return;
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
            toastSuccess('Tanda tangan berhasil disimpan.');
            location.reload();
        },
        onError: () => {
            toastError('Gagal menyimpan tanda tangan.');
        },
    });
};

// ─── Terms & Conditions edit panel ─────────────────────────────────────────
const showTermsEditor = ref(false);

// Local editable copy of terms
interface EditableTerm {
    id: number | null;  // null = new (not yet saved)
    order: number;
    content: string;
}

const editableTerms = ref<EditableTerm[]>([]);

const openTermsEditor = () => {
    // Deep copy from props
    editableTerms.value = props.terms.map(t => ({ id: t.id, order: t.order, content: t.content }));
    showTermsEditor.value = true;
};

const addNewTerm = () => {
    const maxOrder = editableTerms.value.length > 0
        ? Math.max(...editableTerms.value.map(t => t.order))
        : 0;
    editableTerms.value.push({ id: null, order: maxOrder + 1, content: '' });
};

const removeTerm = (index: number) => {
    editableTerms.value.splice(index, 1);
    // Re-order
    editableTerms.value.forEach((t, i) => { t.order = i + 1; });
};

const savingTerms = ref(false);

const saveTerms = () => {
    // Filter out empty terms
    const validTerms = editableTerms.value.filter(t => t.content.trim() !== '');
    // Re-order after potential removals
    validTerms.forEach((t, i) => { t.order = i + 1; });

    savingTerms.value = true;
    router.put(
        route('transactions.terms-conditions.bulk-update'),
        { terms: validTerms },
        {
            preserveScroll: true,
            onSuccess: () => {
                showTermsEditor.value = false;
                toastSuccess('Syarat & ketentuan berhasil diperbarui.');
                location.reload();
            },
            onError: () => {
                toastError('Gagal menyimpan syarat & ketentuan.');
            },
            onFinish: () => { savingTerms.value = false; },
        }
    );
};

// Terms displayed in signature modal — use props.terms (live data)
const displayTerms = computed(() => props.terms);
</script>

<template>
    <Head title="Signature" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-muted/40 py-10">
            <div class="max-w-7xl mx-auto px-6 space-y-8">

                <!-- Header row -->
                <div class="flex items-start justify-between">
                    <Heading title="Signatures" description="Bills berstatus paid yang belum ditandatangani." />
                    <Button variant="outline" size="sm" @click="openTermsEditor" class="flex items-center gap-1.5 shrink-0">
                        <Pencil class="h-3.5 w-3.5" />
                        Edit Syarat &amp; Ketentuan
                    </Button>
                </div>

                <!-- Table: Pending Signatures -->
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

                <Pagination :meta="bills" />

                <!-- Table: Signed Bills -->
                <div class="mt-8">
                    <Heading title="Daftar Tenant yang Sudah Menandatangani"
                             description="Riwayat bill yang telah berhasil ditandatangani." variant="small" />

                    <div class="overflow-hidden rounded-lg border mt-4">
                        <table class="w-full text-sm">
                            <thead class="border-b bg-muted/50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-medium">#</th>
                                    <th class="px-4 py-3 text-left font-medium">Room</th>
                                    <th class="px-4 py-3 text-left font-medium">Tenant</th>
                                    <th class="px-4 py-3 text-left font-medium">Start</th>
                                    <th class="px-4 py-3 text-left font-medium">Due</th>
                                    <th class="px-4 py-3 text-center font-medium">TTD</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="signedBills.length === 0">
                                    <td colspan="6" class="px-4 py-8 text-center text-muted-foreground">Belum ada tenant yang menandatangani.</td>
                                </tr>
                                <tr v-for="(sb, idx) in signedBills" :key="sb.id" class="border-b last:border-0">
                                    <td class="px-4 py-3 font-medium">{{ idx + 1 }}</td>
                                    <td class="px-4 py-3">{{ sb.room_number }}</td>
                                    <td class="px-4 py-3">{{ sb.tenant_name }}</td>
                                    <td class="px-4 py-3 text-xs">{{ formatDate(sb.start_date) }}</td>
                                    <td class="px-4 py-3 text-xs">{{ formatDate(sb.due_date) }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-flex rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700 dark:bg-green-900/30 dark:text-green-400">
                                            Sudah TTD
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Signature Modal -->
                <Dialog v-model:open="showModal">
                    <DialogContent class="sm:max-w-3xl">
                        <DialogHeader>
                            <DialogTitle>Tanda Tangan</DialogTitle>
                        </DialogHeader>

                        <div class="p-4 space-y-4">
                            <!-- Syarat & Ketentuan (dinamis) -->
                            <div class="rounded-lg border bg-muted/30 p-4 max-h-48 overflow-y-auto">
                                <h4 class="text-sm font-semibold mb-2">Syarat dan Ketentuan</h4>
                                <p v-if="displayTerms.length === 0" class="text-xs text-muted-foreground italic">
                                    Belum ada syarat & ketentuan yang ditambahkan.
                                </p>
                                <ol class="list-decimal list-inside space-y-1.5 text-xs text-muted-foreground leading-relaxed">
                                    <li v-for="term in displayTerms" :key="term.id">{{ term.content }}</li>
                                </ol>
                            </div>

                            <!-- Checkbox Persetujuan -->
                            <label class="flex items-start gap-3 cursor-pointer select-none">
                                <input
                                    type="checkbox"
                                    v-model="agreedToTerms"
                                    class="mt-0.5 h-4 w-4 rounded border-input"
                                />
                                <span class="text-sm">
                                    Saya telah membaca dan <strong>menyetujui Syarat dan Ketentuan</strong> di atas.
                                </span>
                            </label>

                            <!-- Canvas -->
                            <div>
                                <p class="text-sm text-muted-foreground mb-2">Gambar tanda tangan pada area di bawah, lalu klik Save.</p>
                                <div class="w-full overflow-auto">
                                    <canvas ref="canvasRef" class="w-full rounded border bg-white"></canvas>
                                </div>
                            </div>
                        </div>

                        <DialogFooter>
                            <DialogClose as-child>
                                <Button variant="secondary">Batal</Button>
                            </DialogClose>
                            <Button variant="ghost" @click="clearCanvas">Clear</Button>
                            <Button
                                @click="submitSignature"
                                :disabled="form.processing || !agreedToTerms"
                                :title="!agreedToTerms ? 'Anda harus menyetujui Syarat dan Ketentuan terlebih dahulu' : ''"
                            >
                                Save
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>

                <!-- Terms & Conditions Editor Dialog -->
                <Dialog v-model:open="showTermsEditor">
                    <DialogContent class="sm:max-w-2xl max-h-[90vh] flex flex-col">
                        <DialogHeader>
                            <DialogTitle>Edit Syarat &amp; Ketentuan</DialogTitle>
                        </DialogHeader>

                        <div class="flex-1 overflow-y-auto py-2 space-y-3 pr-1">
                            <p class="text-xs text-muted-foreground">
                                Edit, hapus, atau tambah butir syarat &amp; ketentuan. Urutan mengikuti nomor di kiri.
                            </p>

                            <!-- List of editable terms -->
                            <div
                                v-for="(term, index) in editableTerms"
                                :key="index"
                                class="flex items-start gap-2 group"
                            >
                                <!-- Order number -->
                                <span class="flex-shrink-0 flex items-center justify-center w-7 h-8 mt-0.5 rounded bg-muted text-xs font-medium text-muted-foreground">
                                    {{ index + 1 }}
                                </span>

                                <!-- Textarea -->
                                <textarea
                                    v-model="term.content"
                                    rows="2"
                                    placeholder="Tulis syarat & ketentuan..."
                                    class="flex-1 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 resize-none"
                                ></textarea>

                                <!-- Delete button -->
                                <button
                                    type="button"
                                    @click="removeTerm(index)"
                                    class="flex-shrink-0 mt-1.5 p-1.5 rounded text-muted-foreground hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-950/30 transition-colors opacity-0 group-hover:opacity-100"
                                    title="Hapus butir ini"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </button>
                            </div>

                            <!-- Empty state -->
                            <div v-if="editableTerms.length === 0" class="text-center py-6 text-sm text-muted-foreground">
                                Belum ada syarat & ketentuan. Klik tombol di bawah untuk menambahkan.
                            </div>
                        </div>

                        <!-- Add new term button -->
                        <div class="pt-3 border-t">
                            <button
                                type="button"
                                @click="addNewTerm"
                                class="w-full flex items-center justify-center gap-2 rounded-md border border-dashed border-input px-4 py-2.5 text-sm text-muted-foreground hover:border-primary hover:text-primary transition-colors"
                            >
                                <Plus class="h-4 w-4" />
                                Tambah Butir Baru
                            </button>
                        </div>

                        <DialogFooter class="pt-2">
                            <DialogClose as-child>
                                <Button variant="secondary" class="gap-1.5">
                                    <X class="h-3.5 w-3.5" /> Batal
                                </Button>
                            </DialogClose>
                            <Button @click="saveTerms" :disabled="savingTerms" class="gap-1.5">
                                <Save class="h-3.5 w-3.5" />
                                {{ savingTerms ? 'Menyimpan...' : 'Simpan Perubahan' }}
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>

            </div>
        </div>
    </AppLayout>
</template>
