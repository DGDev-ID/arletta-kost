<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { LoaderCircle, Plus, Trash2, Upload, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { formatRupiah } from '@/lib/currency';

interface KostItem {
    id: number;
    name: string;
}

interface ImageItem {
    id: number;
    img_url: string;
    full_url: string;
}

interface DetailItem {
    id?: number;
    detail: string;
    icon: string;
}

interface PromoItem {
    id?: number;
    type: string;
}

interface PricingItem {
    id?: number;
    duration_days: number | null;
    price: number | null;
    promos: PromoItem[];
}

interface CategoryData {
    id: number;
    kost_id: number;
    name: string;
    description: string | null;
    images: ImageItem[];
    details: DetailItem[];
}

const props = defineProps<{
    kosts: KostItem[];
    category?: CategoryData;
}>();

const isEdit = computed(() => !!props.category);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Room Category', href: '/master/room-categories' },
    { title: isEdit.value ? 'Edit Category' : 'Tambah Category', href: '#' },
];

// Form state
const kost_id = ref(props.category?.kost_id ?? '');
const name = ref(props.category?.name ?? '');
const description = ref(props.category?.description ?? '');
const details = ref<DetailItem[]>(props.category?.details?.map(d => ({ ...d })) ?? []);
const pricings = ref<PricingItem[]>(props.category?.pricings?.map((p: any) => ({
    id: p.id,
    duration_days: p.duration_days,
    price: p.price,
    promos: (p.promos ?? []).map((r: any) => ({ id: r.id, type: r.type })),
})) ?? []);
const existingImages = ref<ImageItem[]>(props.category?.images?.map(i => ({ ...i })) ?? []);
const newImages = ref<File[]>([]);
const newImagePreviews = ref<string[]>([]);
const removedImageIds = ref<number[]>([]);
const processing = ref(false);
const errors = ref<Record<string, string>>({});

// Add detail row
const addDetail = () => {
    details.value.push({ detail: '', icon: '' });
};

// Pricing handlers
const addPricing = () => {
    pricings.value.push({ duration_days: 30, price: 0, promos: [] });
};

const removePricing = (index: number) => {
    pricings.value.splice(index, 1);
};

const addPromo = (pricingIndex: number) => {
    pricings.value[pricingIndex].promos.push({ type: '' });
};

const removePromo = (pricingIndex: number, promoIndex: number) => {
    pricings.value[pricingIndex].promos.splice(promoIndex, 1);
};

// Helper memformat angka dengan titik
const formatInputRupiah = (value: number | null) => {
    if (!value) return '';
    return value.toLocaleString('id-ID'); 
};

// Handler yang baru (menerima string/angka, bukan Event DOM)
const handlePriceInput = (val: string | number | undefined, pidx: number) => {
    // Jika input kosong (user menghapus semua angka)
    if (!val) {
        pricings.value[pidx].price = null;
        return;
    }
    
    // Pastikan val menjadi string, lalu hapus semua karakter selain angka
    const rawValue = String(val).replace(/\D/g, '');
    
    // Simpan nilai murni ke state
    pricings.value[pidx].price = rawValue ? parseInt(rawValue, 10) : null;
};

// Remove detail row
const removeDetail = (index: number) => {
    details.value.splice(index, 1);
};

// Handle image file selection
const onImageSelect = (e: Event) => {
    const input = e.target as HTMLInputElement;
    if (!input.files) return;
    for (const file of Array.from(input.files)) {
        newImages.value.push(file);
        const reader = new FileReader();
        reader.onload = (ev) => {
            newImagePreviews.value.push(ev.target?.result as string);
        };
        reader.readAsDataURL(file);
    }
    input.value = '';
};

// Remove new image preview
const removeNewImage = (index: number) => {
    newImages.value.splice(index, 1);
    newImagePreviews.value.splice(index, 1);
};

// Mark existing image for removal
const removeExistingImage = (img: ImageItem) => {
    removedImageIds.value.push(img.id);
    existingImages.value = existingImages.value.filter(i => i.id !== img.id);
};

// Submit form
const submit = () => {
    processing.value = true;
    errors.value = {};

    const formData = new FormData();
    formData.append('kost_id', String(kost_id.value));
    formData.append('name', name.value);
    formData.append('description', description.value);

    // Images
    newImages.value.forEach((file) => {
        formData.append('images[]', file);
    });

    // Removed images (edit mode)
    removedImageIds.value.forEach((id) => {
        formData.append('removed_images[]', String(id));
    });

    // Details
    details.value.forEach((d, i) => {
        if (d.id) formData.append(`details[${i}][id]`, String(d.id));
        formData.append(`details[${i}][detail]`, d.detail);
        formData.append(`details[${i}][icon]`, d.icon || '');
    });

    // Pricings and promos
    pricings.value.forEach((p, i) => {
        if (p.id) formData.append(`pricings[${i}][id]`, String(p.id));
        formData.append(`pricings[${i}][duration_days]`, String(p.duration_days ?? ''));
        formData.append(`pricings[${i}][price]`, String(p.price ?? 0));
        (p.promos || []).forEach((pr, j) => {
            if (pr.id) formData.append(`pricings[${i}][promos][${j}][id]`, String(pr.id));
            formData.append(`pricings[${i}][promos][${j}][type]`, pr.type || '');
        });
    });

    const url = isEdit.value
        ? route('master.room-categories.update', props.category!.id)
        : route('master.room-categories.store');

    router.post(url, formData, {
        forceFormData: true,
        onSuccess: () => {
            processing.value = false;
        },
        onError: (e) => {
            errors.value = e as Record<string, string>;
            processing.value = false;
        },
    });
};
</script>

<template>
    <Head :title="isEdit ? 'Edit Category' : 'Tambah Category'" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-muted/40">
            <div class="max-w-3xl mx-auto px-16 py-8">
                <div class="rounded-2xl border bg-background shadow-sm p-8 space-y-8">

            <Heading :title="isEdit ? 'Edit Room Category' : 'Tambah Room Category'"
            description="Tambah Informasi Kategori Kamar" />

            <div class="mx-auto w-full max-w-2xl">
                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Kost -->
                    <div class="grid gap-2">
                        <Label for="kost_id">Kost</Label>
                        <select
                            id="kost_id"
                            v-model="kost_id"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                        >
                            <option value="" disabled>Pilih kost...</option>
                            <option v-for="kost in kosts" :key="kost.id" :value="kost.id">{{ kost.name }}</option>
                        </select>
                        <InputError :message="errors.kost_id" />
                    </div>

                    <!-- Name -->
                    <div class="grid gap-2">
                        <Label for="name">Nama Kategori</Label>
                        <Input id="name" v-model="name" placeholder="Contoh: Standard, Deluxe" />
                        <InputError :message="errors.name" />
                    </div>

                    <!-- Description -->
                    <div class="grid gap-2">
                        <Label for="description">Deskripsi (Opsional)</Label>
                        <textarea
                            id="description"
                            v-model="description"
                            placeholder="Deskripsi kategori"
                            rows="3"
                            class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                        />
                        <InputError :message="errors.description" />
                    </div>

                    <!-- Images -->
                    <div class="grid gap-3">
                        <Label>Images</Label>

                        <!-- Existing images -->
                        <div v-if="existingImages.length > 0" class="flex flex-wrap gap-3">
                            <div v-for="img in existingImages" :key="img.id" class="relative group">
                                <img :src="img.full_url" alt="Category image" class="h-24 w-24 rounded-lg object-cover border" />
                                <button
                                    type="button"
                                    @click="removeExistingImage(img)"
                                    class="absolute -top-2 -right-2 rounded-full bg-destructive p-1 text-white opacity-0 group-hover:opacity-100 transition-opacity"
                                >
                                    <X class="h-3 w-3" />
                                </button>
                            </div>
                        </div>

                        <!-- New image previews -->
                        <div v-if="newImagePreviews.length > 0" class="flex flex-wrap gap-3">
                            <div v-for="(preview, idx) in newImagePreviews" :key="idx" class="relative group">
                                <img :src="preview" alt="New image preview" class="h-24 w-24 rounded-lg object-cover border border-dashed border-primary" />
                                <button
                                    type="button"
                                    @click="removeNewImage(idx)"
                                    class="absolute -top-2 -right-2 rounded-full bg-destructive p-1 text-white opacity-0 group-hover:opacity-100 transition-opacity"
                                >
                                    <X class="h-3 w-3" />
                                </button>
                            </div>
                        </div>

                        <!-- Upload button -->
                        <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-dashed border-input px-4 py-3 text-sm text-muted-foreground transition-colors hover:bg-muted/50">
                            <Upload class="h-4 w-4" />
                            Upload Images
                            <input type="file" accept="image/*" multiple class="hidden" @change="onImageSelect" />
                        </label>
                        <InputError :message="errors['images']" />
                    </div>

                    <!-- Details (Dynamic Form) -->
                    <div class="grid gap-3">
                        <div class="flex items-center justify-between">
                            <Label>Details / Fasilitas</Label>
                            <Button type="button" variant="outline" size="sm" @click="addDetail">
                                <Plus class="mr-1 h-3 w-3" />
                                Tambah Detail
                            </Button>
                        </div>

                        <div v-if="details.length === 0" class="rounded-lg border border-dashed px-4 py-6 text-center text-sm text-muted-foreground">
                            Belum ada detail. Klik "Tambah Detail" untuk menambahkan.
                        </div>

                        <div v-for="(detail, idx) in details" :key="idx" class="flex items-start gap-3 rounded-lg border bg-muted/30 p-3">
                            <div class="flex-1 grid gap-2">
                                <Input v-model="detail.detail" :placeholder="`Detail ${idx + 1}, misal: AC, WiFi`" />
                                <InputError :message="errors[`details.${idx}.detail`]" />
                            </div>
                            <!-- <div class="w-32">
                                <Input v-model="detail.icon" placeholder="Icon (opsional)" />
                            </div> -->
                            <Button type="button" variant="ghost" size="icon" class="h-9 w-9 mt-0.5 text-destructive" @click="removeDetail(idx)">
                                <Trash2 class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>

                    <!-- Pricings -->
                    <div class="grid gap-3">
                        <div class="flex items-center justify-between">
                            <Label>Pricings</Label>
                            <Button type="button" variant="outline" size="sm" @click="addPricing">
                                <Plus class="mr-1 h-3 w-3" />
                                Tambah Pricing
                            </Button>
                        </div>

                        <div v-if="pricings.length === 0" class="rounded-lg border border-dashed px-4 py-6 text-center text-sm text-muted-foreground">
                            Belum ada pricing. Klik "Tambah Pricing" untuk menambahkan.
                        </div>

                        <div v-for="(pricing, pidx) in pricings" :key="pidx" class="rounded-lg border bg-muted/30 p-3 space-y-3">
                            <div class="flex gap-3">
                                <div class="flex-1 grid gap-2">
                                    <Label>Durasi (hari)</Label>
                                    <Input type="number" v-model.number="pricing.duration_days" />
                                </div>
                                <div class="w-48 grid gap-2">
                                    <Label>Harga</Label>
                                    <Input 
        type="text" 
        :model-value="pricing.price ? formatRupiah(pricing.price) : ''"
        @update:model-value="(val) => handlePriceInput(val, pidx)"
        placeholder="Rp 0"
    />
                                </div>
                                <div class="flex items-start">
                                    <Button type="button" variant="ghost" size="icon" class="h-9 w-9 mt-6 text-destructive" @click="removePricing(pidx)">
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>

                            <!-- Promos for this pricing -->
                            <div class="grid gap-2">
                                <div class="flex items-center justify-between">
                                    <Label>Promos</Label>
                                    <Button type="button" variant="outline" size="sm" @click="addPromo(pidx)">
                                        <Plus class="mr-1 h-3 w-3" />
                                        Tambah Promo
                                    </Button>
                                </div>

                                <div v-if="pricing.promos.length === 0" class="rounded-lg border border-dashed px-4 py-4 text-center text-sm text-muted-foreground">
                                    Belum ada promo untuk pricing ini.
                                </div>

                                <div v-for="(promo, pridx) in pricing.promos" :key="pridx" class="flex items-center gap-3">
                                    <div class="flex-1">
                                        <Input v-model="promo.type" placeholder="Type promo, misal: cashback, bonus_days" />
                                    </div>
                                    <Button type="button" variant="ghost" size="icon" class="h-9 w-9 text-destructive" @click="removePromo(pidx, pridx)">
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-3">
                        <Button type="submit" :disabled="processing">
                            <LoaderCircle v-if="processing" class="mr-1.5 h-4 w-4 animate-spin" />
                            {{ isEdit ? 'Update' : 'Simpan' }}
                        </Button>
                        <Button type="button" variant="secondary" as-child>
                            <a :href="route('master.room-categories.index')">Batal</a>
                        </Button>
                    </div>
                </form>
            </div>
            </div>
            </div>
        </div>
    </AppLayout>
</template>
