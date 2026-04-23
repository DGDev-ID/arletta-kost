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

interface TenantData {
    id: number;
    email: string;
    name: string;
    nik: string | null;
    birth_place: string | null;
    birth_date: string | null;
    gender: string | null;
    address: string | null;
    phone_number: string;
}

const props = defineProps<{
    tenant?: TenantData;
}>();

const isEdit = computed(() => !!props.tenant);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Tenants', href: '/management/tenants' },
    { title: isEdit.value ? 'Edit Tenant' : 'Tambah Tenant', href: '#' },
];

const form = useForm({
    email: props.tenant?.email ?? '',
    name: props.tenant?.name ?? '',
    nik: props.tenant?.nik ?? '',
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
        <div class="min-h-screen bg-muted/40">
            <div class="max-w-7xl mx-auto px-6 py-8">
                <div class="rounded-2xl border bg-background shadow-sm p-8 space-y-8">

            <Heading :title="isEdit ? 'Edit Tenant' : 'Tambah Tenant'"
            description="Tambahkan atau edit data tenant sesuai kebutuhan" />

            <form @submit.prevent="submit" class="space-y-6">

                <!-- ACTION BUTTON -->
                <div class="flex justify-end">
                    <Button type="submit" :disabled="form.processing">
                        <LoaderCircle v-if="form.processing" class="mr-1.5 h-4 w-4 animate-spin" />
                        {{ isEdit ? 'Update' : 'Simpan' }}
                    </Button>
                </div>

                <!-- GRID -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                    <!-- LEFT: CREDENTIAL -->
                    <div class="lg:col-span-1">
                        <div class="rounded-2xl border bg-background p-6 shadow-sm space-y-4">
                            
                            <h3 class="text-lg font-semibold">Credential</h3>

                            <!-- NAME -->
                            <div class="grid gap-2">
                                <Label>Nama Lengkap</Label>
                                <Input v-model="form.name" />
                                <InputError :message="form.errors.name" />
                            </div>

                            <!-- EMAIL -->
                            <div class="grid gap-2">
                                <Label>Email</Label>
                                <Input v-model="form.email" type="email" />
                                <InputError :message="form.errors.email" />
                            </div>

                            <!-- PHONE -->
                            <div class="grid gap-2">
                                <Label>No HP</Label>
                                <Input v-model="form.phone_number" />
                                <InputError :message="form.errors.phone_number" />
                            </div>

                        </div>
                    </div>

                    <!-- RIGHT: DETAIL -->
                    <div class="lg:col-span-2">
                        <div class="rounded-2xl border bg-background p-6 shadow-sm space-y-6">

                            <h3 class="text-lg font-semibold">User Detail</h3>

                            <!-- GRID 2 COL -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                                <div>
                                    <Label>NIK</Label>
                                    <Input v-model="form.nik" />
                                </div>

                                <div>
                                    <Label>Tempat Lahir</Label>
                                    <Input v-model="form.birth_place" />
                                </div>

                                <div>
                                    <Label>Tanggal Lahir</Label>
                                    <Input type="date" v-model="form.birth_date" />
                                </div>

                                <div>
                                    <Label>Gender</Label>
                                    <select v-model="form.gender" class="border rounded px-2 py-2 w-full">
                                        <option value="">Pilih</option>
                                        <option value="male">Laki-laki</option>
                                        <option value="female">Perempuan</option>
                                    </select>
                                </div>

                            </div>

                            <!-- ADDRESS -->
                            <div>
                                <Label>Alamat</Label>
                                <textarea v-model="form.address" class="w-full border rounded p-2" />
                            </div>

                        </div>
                    </div>

                </div>

            </form>
            </div>
            </div>
        </div>
    </AppLayout>
</template>
