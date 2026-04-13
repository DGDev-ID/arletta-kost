<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
import { computed } from 'vue';

interface OwnerItem {
    id: number;
    name: string;
}

interface KostData {
    id: number;
    owner_id: number;
    name: string;
    address: string;
    address_coordinate: string | null;
    description: string | null;
}

const props = defineProps<{
    owners: OwnerItem[];
    kost?: KostData;
}>();

const isEdit = computed(() => !!props.kost);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Kost', href: '/master/kosts' },
    { title: isEdit.value ? 'Edit Kost' : 'Tambah Kost', href: '#' },
];

const form = useForm({
    owner_id: props.kost?.owner_id ?? '',
    name: props.kost?.name ?? '',
    address: props.kost?.address ?? '',
    address_coordinate: props.kost?.address_coordinate ?? '',
    description: props.kost?.description ?? '',
});

const submit = () => {
    if (isEdit.value && props.kost) {
        form.put(route('master.kosts.update', props.kost.id));
    } else {
        form.post(route('master.kosts.store'));
    }
};
</script>

<template>
    <Head :title="isEdit ? 'Edit Kost' : 'Tambah Kost'" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-muted/40 py-10">
            <div class="max-w-7xl mx-auto px-6 space-y-8">

            <Heading :title="isEdit ? 'Edit Kost' : 'Tambah Kost'" />

            <div class="mx-auto w-full max-w-xl">
                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Owner -->
                    <div class="grid gap-2">
                        <Label for="owner_id">Owner</Label>
                        <select
                            id="owner_id"
                            v-model="form.owner_id"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                        >
                            <option value="" disabled>Pilih owner...</option>
                            <option v-for="owner in owners" :key="owner.id" :value="owner.id">{{ owner.name }}</option>
                        </select>
                        <InputError :message="form.errors.owner_id" />
                    </div>

                    <!-- Nama Kost -->
                    <div class="grid gap-2">
                        <Label for="name">Nama Kost</Label>
                        <Input id="name" v-model="form.name" placeholder="Contoh: Kost Arletta Residence" />
                        <InputError :message="form.errors.name" />
                    </div>

                    <!-- Alamat -->
                    <div class="grid gap-2">
                        <Label for="address">Alamat</Label>
                        <textarea
                            id="address"
                            v-model="form.address"
                            placeholder="Alamat lengkap kost"
                            rows="3"
                            class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                        />
                        <InputError :message="form.errors.address" />
                    </div>

                    <!-- Koordinat -->
                    <div class="grid gap-2">
                        <Label for="address_coordinate">Koordinat (Opsional)</Label>
                        <Input id="address_coordinate" v-model="form.address_coordinate" placeholder="Contoh: -6.2088,106.8456" />
                        <InputError :message="form.errors.address_coordinate" />
                    </div>

                    <!-- Deskripsi -->
                    <div class="grid gap-2">
                        <Label for="description">Deskripsi (Opsional)</Label>
                        <textarea
                            id="description"
                            v-model="form.description"
                            placeholder="Deskripsi kost"
                            rows="3"
                            class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                        />
                        <InputError :message="form.errors.description" />
                    </div>

                    <div class="flex gap-3">
                        <Button type="submit" :disabled="form.processing">
                            <LoaderCircle v-if="form.processing" class="mr-1.5 h-4 w-4 animate-spin" />
                            {{ isEdit ? 'Update' : 'Simpan' }}
                        </Button>
                        <Button type="button" variant="secondary" as-child>
                            <a :href="route('master.kosts.index')">Batal</a>
                        </Button>
                    </div>
                </form>
            </div>
            </div>
        </div>
    </AppLayout>
</template>
