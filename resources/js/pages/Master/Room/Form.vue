<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import Heading from '@/components/Heading.vue';
import RoomCategoryModal from '@/components/RoomCategoryModal.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { LoaderCircle, Settings } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface CategoryItem {
    id: number;
    name: string;
    kost_name: string;
    kost_id: number;
}

interface KostItem {
    id: number;
    name: string;
}

interface RoomData {
    id: number;
    room_category_id: number;
    room_number: string;
    status: string;
}

const props = defineProps<{
    categories: CategoryItem[];
    kosts: KostItem[];
    room?: RoomData;
}>();

const isEdit = computed(() => !!props.room);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Room', href: '/master/rooms' },
    { title: isEdit.value ? 'Edit Room' : 'Tambah Room', href: '#' },
];

const form = useForm({
    room_category_id: props.room?.room_category_id ?? '',
    room_number: props.room?.room_number ?? '',
    status: props.room?.status ?? 'available',
});

const showCategoryModal = ref(false);
const categorySearch = ref('');

// Init selectedKostId from the room's current category (edit mode)
const initKostId = props.room
    ? (props.categories.find((c) => c.id === props.room!.room_category_id)?.kost_id ?? '')
    : '';
const selectedKostId = ref<number | ''>(initKostId);

// Filtered categories by selected kost + search
const filteredCategories = computed(() => {
    return props.categories.filter((c) => {
        const matchKost = selectedKostId.value ? c.kost_id === selectedKostId.value : true;
        const matchSearch = categorySearch.value ? c.name.toLowerCase().includes(categorySearch.value.toLowerCase()) : true;
        return matchKost && matchSearch;
    });
});

// Group filtered categories by kost
const groupedCategories = computed(() => {
    const groups: Record<string, CategoryItem[]> = {};
    filteredCategories.value.forEach((cat) => {
        if (!groups[cat.kost_name]) groups[cat.kost_name] = [];
        groups[cat.kost_name].push(cat);
    });
    return groups;
});

// Reset category when kost changes
watch(selectedKostId, () => {
    form.room_category_id = '';
});

const submit = () => {
    if (isEdit.value && props.room) {
        form.put(route('master.rooms.update', props.room.id));
    } else {
        form.post(route('master.rooms.store'));
    }
};

// Refresh categories after modal closes
watch(showCategoryModal, (open) => {
    if (!open) {
        router.reload({ only: ['categories'] });
    }
});
</script>

<template>
    <Head :title="isEdit ? 'Edit Room' : 'Tambah Room'" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-muted/40">
            <div class="max-w-3xl mx-auto px-16 py-8">
                <div class="rounded-2xl border bg-background shadow-sm p-8 space-y-8">

            <Heading :title="isEdit ? 'Edit Room' : 'Tambah Room'"
            description="tambahkan atau edit data room sesuai kebutuhan" />

            <div class="mx-auto w-full max-w-xl">
                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Kost -->
                    <div class="grid gap-2">
                        <Label for="kost_id">Kost</Label>
                        <select
                            id="kost_id"
                            v-model="selectedKostId"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                        >
                            <option value="">Semua Kost</option>
                            <option v-for="kost in props.kosts" :key="kost.id" :value="kost.id">{{ kost.name }}</option>
                        </select>
                    </div>

                    <!-- Category -->
                    <div class="grid gap-2">
                        <div class="flex items-center justify-between">
                            <Label for="room_category_id">Kategori Kamar</Label>
                            <Button type="button" variant="link" size="sm" class="h-auto p-0 text-xs" @click="showCategoryModal = true">
                                <Settings class="mr-1 h-3 w-3" />
                                Manage Category
                            </Button>
                        </div>
                        <Input v-model="categorySearch" placeholder="Cari kategori..." class="mb-1" />
                        <select
                            id="room_category_id"
                            v-model="form.room_category_id"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                        >
                            <option value="" disabled>Pilih kategori...</option>
                            <optgroup v-for="(cats, kostName) in groupedCategories" :key="kostName" :label="kostName">
                                <option v-for="cat in cats" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                            </optgroup>
                        </select>
                        <InputError :message="form.errors.room_category_id" />
                    </div>

                    <!-- Room Number -->
                    <div class="grid gap-2">
                        <Label for="room_number">Nomor Kamar</Label>
                        <Input id="room_number" v-model="form.room_number" placeholder="Contoh: S001" />
                        <InputError :message="form.errors.room_number" />
                    </div>

                    <!-- Status (hidden when editing) -->
                    <div v-if="!isEdit" class="grid gap-2">
                        <Label for="status">Status</Label>
                        <select
                            id="status"
                            v-model="form.status"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                        >
                            <option value="available">Available</option>
                            <option value="occupied">Occupied</option>
                            <option value="maintenance">Maintenance</option>
                        </select>
                        <InputError :message="form.errors.status" />
                    </div>

                    <div class="flex gap-3">
                        <Button type="submit" :disabled="form.processing">
                            <LoaderCircle v-if="form.processing" class="mr-1.5 h-4 w-4 animate-spin" />
                            {{ isEdit ? 'Update' : 'Simpan' }}
                        </Button>
                        <Button type="button" variant="secondary" as-child>
                            <a :href="route('master.rooms.index')">Batal</a>
                        </Button>
                    </div>
                </form>
            </div>
            </div>
            </div>
        </div>

        <RoomCategoryModal v-model:open="showCategoryModal" />
    </AppLayout>
</template>
