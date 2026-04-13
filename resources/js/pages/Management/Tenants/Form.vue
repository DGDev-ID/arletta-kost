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

interface RoomOption {
    id: number;
    room_number: string;
    kost_name: string;
}

interface TenantData {
    id: number;
    room_id: number;
    email: string;
    name: string;
    nik: string | null;
    ktp_number: string | null;
    birth_place: string | null;
    birth_date: string | null;
    gender: string | null;
    address: string | null;
    phone_number: string;
}

const props = defineProps<{
    rooms: RoomOption[];
    tenant?: TenantData;
}>();

const isEdit = computed(() => !!props.tenant);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Tenants', href: '/management/tenants' },
    { title: isEdit.value ? 'Edit Tenant' : 'Tambah Tenant', href: '#' },
];

const form = useForm({
    room_id: props.tenant?.room_id ?? '',
    email: props.tenant?.email ?? '',
    name: props.tenant?.name ?? '',
    nik: props.tenant?.nik ?? '',
    ktp_number: props.tenant?.ktp_number ?? '',
    birth_place: props.tenant?.birth_place ?? '',
    birth_date: props.tenant?.birth_date ?? '',
    gender: props.tenant?.gender ?? '',
    address: props.tenant?.address ?? '',
    phone_number: props.tenant?.phone_number ?? '',
});

const submit = () => {
    if (isEdit.value && props.tenant) {
        form.put(route('management.tenants.update', props.tenant.id));
    } else {
        form.post(route('management.tenants.store'));
    }
};
</script>

<template>
    <Head :title="isEdit ? 'Edit Tenant' : 'Tambah Tenant'" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-muted/40 py-10">
            <div class="max-w-7xl mx-auto px-6 space-y-8">

            <Heading :title="isEdit ? 'Edit Tenant' : 'Tambah Tenant'" />

            <div class="mx-auto w-full max-w-xl">
                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Room -->
                    <div class="grid gap-2">
                        <Label for="room_id">Room</Label>
                        <select
                            id="room_id"
                            v-model="form.room_id"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                        >
                            <option value="" disabled>Pilih room...</option>
                            <option v-for="room in rooms" :key="room.id" :value="room.id">
                                {{ room.kost_name }} — {{ room.room_number }}
                            </option>
                        </select>
                        <InputError :message="form.errors.room_id" />
                    </div>

                    <!-- Name -->
                    <div class="grid gap-2">
                        <Label for="name">Nama Lengkap</Label>
                        <Input id="name" v-model="form.name" placeholder="Nama lengkap" />
                        <InputError :message="form.errors.name" />
                    </div>

                    <!-- Email -->
                    <div class="grid gap-2">
                        <Label for="email">Email</Label>
                        <Input id="email" v-model="form.email" type="email" placeholder="email@example.com" />
                        <InputError :message="form.errors.email" />
                    </div>

                    <!-- Phone -->
                    <div class="grid gap-2">
                        <Label for="phone_number">No. HP</Label>
                        <Input id="phone_number" v-model="form.phone_number" placeholder="08xxxxxxxxxx" />
                        <InputError :message="form.errors.phone_number" />
                    </div>

                    <!-- Gender -->
                    <div class="grid gap-2">
                        <Label for="gender">Jenis Kelamin</Label>
                        <select
                            id="gender"
                            v-model="form.gender"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                        >
                            <option value="">— Pilih —</option>
                            <option value="male">Laki-laki</option>
                            <option value="female">Perempuan</option>
                        </select>
                        <InputError :message="form.errors.gender" />
                    </div>

                    <!-- NIK -->
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="nik">NIK</Label>
                            <Input id="nik" v-model="form.nik" placeholder="NIK" />
                            <InputError :message="form.errors.nik" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="ktp_number">No. KTP</Label>
                            <Input id="ktp_number" v-model="form.ktp_number" placeholder="No. KTP" />
                            <InputError :message="form.errors.ktp_number" />
                        </div>
                    </div>

                    <!-- Birth -->
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="birth_place">Tempat Lahir</Label>
                            <Input id="birth_place" v-model="form.birth_place" placeholder="Kota" />
                            <InputError :message="form.errors.birth_place" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="birth_date">Tanggal Lahir</Label>
                            <Input id="birth_date" v-model="form.birth_date" type="date" />
                            <InputError :message="form.errors.birth_date" />
                        </div>
                    </div>

                    <!-- Address -->
                    <div class="grid gap-2">
                        <Label for="address">Alamat</Label>
                        <textarea
                            id="address"
                            v-model="form.address"
                            rows="3"
                            placeholder="Alamat lengkap"
                            class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                        ></textarea>
                        <InputError :message="form.errors.address" />
                    </div>

                    <div class="flex gap-3">
                        <Button type="submit" :disabled="form.processing">
                            <LoaderCircle v-if="form.processing" class="mr-1.5 h-4 w-4 animate-spin" />
                            {{ isEdit ? 'Update' : 'Simpan' }}
                        </Button>
                        <Button type="button" variant="secondary" as-child>
                            <a :href="route('management.tenants.index')">Batal</a>
                        </Button>
                    </div>
                </form>
            </div>
            </div>
        </div>
    </AppLayout>
</template>
