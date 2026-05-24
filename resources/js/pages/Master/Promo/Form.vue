<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { ArrowLeft, LoaderCircle, RefreshCw } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface PromoData {
    id: number;
    name: string;
    code: string;
    description: string | null;
    type: string;
    value: number;
    min_purchase: number | null;
    max_usage: number | null;
    is_active: boolean;
    valid_from: string | null;
    valid_until: string | null;
}

const props = defineProps<{
    promo?: PromoData;
}>();

const isEdit = computed(() => !!props.promo);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Promo', href: '/master/promos' },
    { title: isEdit.value ? 'Edit Promo' : 'Tambah Promo', href: '#' },
];

// Form state
const name        = ref(props.promo?.name ?? '');
const code        = ref(props.promo?.code ?? '');
const description = ref(props.promo?.description ?? '');
const type        = ref(props.promo?.type ?? 'discount_percent');
const value       = ref<number | string>(props.promo?.value ?? '');
const min_purchase = ref<number | string>(props.promo?.min_purchase ?? '');
const max_usage   = ref<number | string>(props.promo?.max_usage ?? '');
const is_active   = ref(props.promo?.is_active ?? true);
const valid_from  = ref(props.promo?.valid_from ?? '');
const valid_until = ref(props.promo?.valid_until ?? '');

const processing = ref(false);
const errors     = ref<Record<string, string>>({});

// Auto-uppercase code
const handleCodeInput = (e: Event) => {
    const input = e.target as HTMLInputElement;
    code.value = input.value.toUpperCase().replace(/[^A-Z0-9_-]/g, '');
};

// Random code generator
const generateCode = () => {
    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    let result = '';
    for (let i = 0; i < 8; i++) {
        result += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    code.value = result;
};

const valueLabel = computed(() => {
    if (type.value === 'discount_percent') return 'Nilai Diskon (%)';
    if (type.value === 'discount_amount')  return 'Nilai Diskon (Rp)';
    return 'Jumlah Hari Bonus';
});

const valuePlaceholder = computed(() => {
    if (type.value === 'discount_percent') return 'Contoh: 10 (artinya 10%)';
    if (type.value === 'discount_amount')  return 'Contoh: 50000';
    return 'Contoh: 7 (artinya 7 hari)';
});

const submit = () => {
    processing.value = true;
    errors.value = {};

    const payload: Record<string, unknown> = {
        name:        name.value,
        code:        code.value,
        description: description.value || null,
        type:        type.value,
        value:       value.value,
        min_purchase: min_purchase.value !== '' ? min_purchase.value : null,
        max_usage:   max_usage.value !== '' ? max_usage.value : null,
        is_active:   is_active.value,
        valid_from:  valid_from.value || null,
        valid_until: valid_until.value || null,
    };

    const routeName = isEdit.value
        ? route('master.promos.update', props.promo!.id)
        : route('master.promos.store');

    const method = isEdit.value ? 'put' : 'post';

    router[method](routeName, payload, {
        onError: (errs) => { errors.value = errs; },
        onFinish: () => { processing.value = false; },
    });
};
</script>

<template>
    <Head :title="isEdit ? 'Edit Promo' : 'Tambah Promo'" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-muted/40 py-10">
            <div class="max-w-7xl mx-auto px-6 space-y-8">

                <!-- Header -->
                <div class="flex items-center gap-3">
                    <Button variant="ghost" size="icon" class="h-8 w-8" as-child>
                        <a :href="route('master.promos.index')">
                            <ArrowLeft class="h-4 w-4" />
                        </a>
                    </Button>
                    <Heading
                        :title="isEdit ? 'Edit Promo' : 'Tambah Promo'"
                        description="Atur kode promo umum yang bisa digunakan oleh penyewa." />
                </div>

                <!-- Form Card -->
                <div class="rounded-lg border bg-card p-6 space-y-6">
                    <form @submit.prevent="submit" class="space-y-5">

                        <!-- Name -->
                        <div class="grid gap-2">
                            <Label for="name">Nama Promo <span class="text-destructive">*</span></Label>
                            <Input id="name" v-model="name" placeholder="Contoh: Promo 10 Pembeli Pertama" />
                            <InputError :message="errors.name" />
                        </div>

                        <!-- Code -->
                        <div class="grid gap-2">
                            <Label for="code">Kode Promo <span class="text-destructive">*</span></Label>
                            <div class="flex gap-2">
                                <Input
                                    id="code"
                                    :value="code"
                                    @input="handleCodeInput"
                                    placeholder="Contoh: PROMO10"
                                    class="font-mono tracking-widest uppercase"
                                    maxlength="50"
                                />
                                <Button type="button" variant="outline" size="icon" @click="generateCode"
                                        title="Generate kode acak">
                                    <RefreshCw class="h-4 w-4" />
                                </Button>
                            </div>
                            <p class="text-xs text-muted-foreground">
                                Hanya huruf kapital, angka, tanda hubung (-) dan garis bawah (_).
                            </p>
                            <InputError :message="errors.code" />
                        </div>

                        <!-- Description -->
                        <div class="grid gap-2">
                            <Label for="description">Deskripsi</Label>
                            <textarea
                                id="description"
                                v-model="description"
                                rows="3"
                                placeholder="Deskripsi singkat promo (opsional)"
                                class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                            ></textarea>
                            <InputError :message="errors.description" />
                        </div>

                        <!-- Type -->
                        <div class="grid gap-2">
                            <Label for="type">Tipe Promo <span class="text-destructive">*</span></Label>
                            <select
                                id="type"
                                v-model="type"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                            >
                                <option value="discount_percent">Diskon Persen (%)</option>
                                <option value="discount_amount">Diskon Nominal (Rp)</option>
                                <option value="bonus_days">Bonus Hari Menginap</option>
                            </select>
                            <InputError :message="errors.type" />
                        </div>

                        <!-- Value -->
                        <div class="grid gap-2">
                            <Label for="value">{{ valueLabel }} <span class="text-destructive">*</span></Label>
                            <Input
                                id="value"
                                v-model="value"
                                type="number"
                                min="0"
                                :max="type === 'discount_percent' ? 100 : undefined"
                                step="any"
                                :placeholder="valuePlaceholder"
                            />
                            <InputError :message="errors.value" />
                        </div>

                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <!-- Min Purchase -->
                            <div class="grid gap-2">
                                <Label for="min_purchase">Minimal Pembelian (Rp)</Label>
                                <Input
                                    id="min_purchase"
                                    v-model="min_purchase"
                                    type="number"
                                    min="0"
                                    placeholder="Kosongkan jika tidak ada"
                                />
                                <InputError :message="errors.min_purchase" />
                            </div>

                            <!-- Max Usage -->
                            <div class="grid gap-2">
                                <Label for="max_usage">Maksimum Penggunaan</Label>
                                <Input
                                    id="max_usage"
                                    v-model="max_usage"
                                    type="number"
                                    min="1"
                                    placeholder="Kosongkan = tidak terbatas"
                                />
                                <p class="text-xs text-muted-foreground">
                                    Contoh: 10 artinya hanya 10 pembeli pertama.
                                </p>
                                <InputError :message="errors.max_usage" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <!-- Valid From -->
                            <div class="grid gap-2">
                                <Label for="valid_from">Berlaku Mulai</Label>
                                <Input id="valid_from" v-model="valid_from" type="date" />
                                <InputError :message="errors.valid_from" />
                            </div>

                            <!-- Valid Until -->
                            <div class="grid gap-2">
                                <Label for="valid_until">Berlaku Sampai</Label>
                                <Input id="valid_until" v-model="valid_until" type="date"
                                       :min="valid_from || undefined" />
                                <InputError :message="errors.valid_until" />
                            </div>
                        </div>

                        <!-- Is Active toggle -->
                        <div class="flex items-center gap-3">
                            <button
                                type="button"
                                @click="is_active = !is_active"
                                :class="is_active
                                    ? 'bg-primary'
                                    : 'bg-input'"
                                class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2"
                            >
                                <span
                                    :class="is_active ? 'translate-x-5' : 'translate-x-0'"
                                    class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                ></span>
                            </button>
                            <Label class="cursor-pointer" @click="is_active = !is_active">
                                Promo {{ is_active ? 'Aktif' : 'Nonaktif' }}
                            </Label>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-end gap-3 pt-2">
                            <Button variant="outline" type="button" as-child>
                                <a :href="route('master.promos.index')">Batal</a>
                            </Button>
                            <Button type="submit" :disabled="processing">
                                <LoaderCircle v-if="processing" class="mr-2 h-4 w-4 animate-spin" />
                                {{ isEdit ? 'Simpan Perubahan' : 'Tambah Promo' }}
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
