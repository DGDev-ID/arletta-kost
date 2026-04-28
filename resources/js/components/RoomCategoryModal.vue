<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { LoaderCircle, Pencil, Plus, Search, Trash2, X } from 'lucide-vue-next';
import { onMounted, ref, watch } from 'vue';

interface CategoryItem {
    id: number;
    name: string;
    description: string | null;
    kost_id: number;
    kost_name: string;
    gender?: string | null;
}

interface KostItem {
    id: number;
    name: string;
}

const open = defineModel<boolean>('open', { default: false });

const categories = ref<CategoryItem[]>([]);
const kosts = ref<KostItem[]>([]);
const search = ref('');
const loading = ref(false);
const saving = ref(false);
const errors = ref<Record<string, string>>({});

const showForm = ref(false);
const editingCategory = ref<CategoryItem | null>(null);
const formData = ref({ kost_id: '', name: '', description: '', gender: 'mixed' });

const toast = ref<{ message: string; type: 'success' | 'error' } | null>(null);

const showToast = (message: string, type: 'success' | 'error') => {
    toast.value = { message, type };
    setTimeout(() => (toast.value = null), 3000);
};

const fetchCategories = async () => {
    loading.value = true;
    try {
        const params = search.value ? `?search=${encodeURIComponent(search.value)}` : '';
        const res = await fetch(`/master/room-categories${params}`, {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        categories.value = await res.json();
    } finally {
        loading.value = false;
    }
};

const fetchKosts = async () => {
    const res = await fetch('/master/room-categories/kosts', {
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    });
    kosts.value = await res.json();
};

const openCreateForm = () => {
    editingCategory.value = null;
    formData.value = { kost_id: '', name: '', description: '', gender: 'mixed' };
    errors.value = {};
    showForm.value = true;
};

const openEditForm = (cat: CategoryItem) => {
    editingCategory.value = cat;
    formData.value = {
        kost_id: String(cat.kost_id),
        name: cat.name,
        description: cat.description ?? '',
        gender: cat.gender ?? 'mixed',
    };
    errors.value = {};
    showForm.value = true;
};

const cancelForm = () => {
    showForm.value = false;
    editingCategory.value = null;
    errors.value = {};
};

const getCsrfToken = (): string => {
    const meta = document.querySelector('meta[name="csrf-token"]');
    if (meta) return meta.getAttribute('content') ?? '';
    const cookies = document.cookie.split(';');
    for (const cookie of cookies) {
        const [name, value] = cookie.trim().split('=');
        if (name === 'XSRF-TOKEN') return decodeURIComponent(value);
    }
    return '';
};

const saveCategory = async () => {
    saving.value = true;
    errors.value = {};

    try {
        const isEdit = !!editingCategory.value;
        const url = isEdit
            ? `/master/room-categories/${editingCategory.value!.id}`
            : '/master/room-categories';

        const res = await fetch(url, {
            method: isEdit ? 'PUT' : 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': getCsrfToken(),
            },
            body: JSON.stringify({
                kost_id: Number(formData.value.kost_id),
                name: formData.value.name,
                description: formData.value.description || null,
                gender: formData.value.gender || null,
            }),
        });

        if (res.status === 422) {
            const data = await res.json();
            errors.value = data.errors ?? {};
            return;
        }

        if (!res.ok) throw new Error('Server error');

        showToast(isEdit ? 'Kategori berhasil diperbarui.' : 'Kategori berhasil ditambahkan.', 'success');
        cancelForm();
        await fetchCategories();
    } catch {
        showToast('Terjadi kesalahan.', 'error');
    } finally {
        saving.value = false;
    }
};

const deleteCategory = async (cat: CategoryItem) => {
    if (!confirm(`Hapus kategori "${cat.name}"?`)) return;

    try {
        const res = await fetch(`/master/room-categories/${cat.id}`, {
            method: 'DELETE',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': getCsrfToken(),
            },
        });

        if (!res.ok) throw new Error('Server error');

        showToast('Kategori berhasil dihapus.', 'success');
        await fetchCategories();
    } catch {
        showToast('Gagal menghapus kategori.', 'error');
    }
};

// Group by kost
const groupedCategories = () => {
    const groups: Record<string, CategoryItem[]> = {};
    categories.value.forEach((cat) => {
        if (!groups[cat.kost_name]) groups[cat.kost_name] = [];
        groups[cat.kost_name].push(cat);
    });
    return groups;
};

watch(open, (val) => {
    if (val) {
        fetchCategories();
        fetchKosts();
        showForm.value = false;
    }
});

watch(search, () => fetchCategories());
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="max-h-[85vh] overflow-y-auto sm:max-w-2xl">
            <DialogHeader>
                <DialogTitle>Manage Room Categories</DialogTitle>
                <DialogDescription>Kelola kategori kamar untuk setiap kost.</DialogDescription>
            </DialogHeader>

            <!-- Toast -->
            <div
                v-if="toast"
                :class="[
                    'rounded-lg border px-4 py-3 text-sm',
                    toast.type === 'success'
                        ? 'border-green-200 bg-green-50 text-green-700 dark:border-green-800 dark:bg-green-900/20 dark:text-green-400'
                        : 'border-red-200 bg-red-50 text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400',
                ]"
            >
                {{ toast.message }}
            </div>

            <!-- Create / Edit form -->
            <div v-if="showForm" class="space-y-4 rounded-lg border bg-muted/30 p-4">
                <div class="flex items-center justify-between">
                    <h4 class="text-sm font-medium">{{ editingCategory ? 'Edit Kategori' : 'Tambah Kategori' }}</h4>
                    <Button variant="ghost" size="icon" class="h-7 w-7" @click="cancelForm">
                        <X class="h-4 w-4" />
                    </Button>
                </div>

                <div class="grid gap-3">
                    <div class="grid gap-1.5">
                        <Label>Kost</Label>
                        <select
                            v-model="formData.kost_id"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                        >
                            <option value="" disabled>Pilih kost...</option>
                            <option v-for="kost in kosts" :key="kost.id" :value="kost.id">{{ kost.name }}</option>
                        </select>
                        <InputError :message="errors.kost_id?.[0]" />
                    </div>

                    <div class="grid gap-1.5">
                        <Label>Nama Kategori</Label>
                        <Input v-model="formData.name" placeholder="Contoh: Standard, Deluxe" />
                        <InputError :message="errors.name?.[0]" />
                    </div>

                    <div class="grid gap-1.5">
                        <Label>Deskripsi</Label>
                        <Input v-model="formData.description" placeholder="Deskripsi kategori (opsional)" />
                        <InputError :message="errors.description?.[0]" />
                    </div>
                </div>

                <div class="flex gap-2">
                    <Button size="sm" :disabled="saving" @click="saveCategory">
                        <LoaderCircle v-if="saving" class="mr-1 h-4 w-4 animate-spin" />
                        {{ editingCategory ? 'Update' : 'Simpan' }}
                    </Button>
                    <Button variant="secondary" size="sm" @click="cancelForm">Batal</Button>
                </div>
            </div>

            <!-- Actions bar -->
            <div class="flex items-center gap-2">
                <div class="relative flex-1">
                    <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input v-model="search" placeholder="Cari kategori..." class="pl-9" />
                </div>
                <Button v-if="!showForm" size="sm" @click="openCreateForm">
                    <Plus class="mr-1 h-4 w-4" />
                    Tambah
                </Button>
            </div>

            <!-- Category list -->
            <div v-if="loading" class="flex items-center justify-center py-8">
                <LoaderCircle class="h-6 w-6 animate-spin text-muted-foreground" />
            </div>

            <div v-else-if="categories.length === 0" class="py-8 text-center text-sm text-muted-foreground">
                Tidak ada kategori.
            </div>

            <div v-else class="space-y-4">
                <div v-for="(cats, kostName) in groupedCategories()" :key="kostName">
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-muted-foreground">{{ kostName }}</p>
                    <div class="space-y-1">
                        <div
                            v-for="cat in cats"
                            :key="cat.id"
                            class="flex items-center justify-between rounded-lg border px-3 py-2.5 transition-colors hover:bg-muted/50"
                        >
                            <div>
                                <p class="text-sm font-medium">{{ cat.name }}</p>
                                <p v-if="cat.description" class="text-xs text-muted-foreground">{{ cat.description }}</p>
                            </div>
                            <div class="flex gap-1">
                                <Button variant="ghost" size="icon" class="h-7 w-7" @click="openEditForm(cat)">
                                    <Pencil class="h-3.5 w-3.5" />
                                </Button>
                                <Button variant="ghost" size="icon" class="h-7 w-7 text-destructive" @click="deleteCategory(cat)">
                                    <Trash2 class="h-3.5 w-3.5" />
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
