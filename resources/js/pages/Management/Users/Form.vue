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
import { computed, watch } from 'vue';

interface RoleItem {
    id: number;
    name: string;
}

interface KostItem {
    id: number;
    name: string;
}

interface UserData {
    id: number;
    name: string;
    email: string;
    role_id: number | null;
    kost_ids: number[];
}

const props = defineProps<{
    roles: RoleItem[];
    kosts: KostItem[];
    user?: UserData;
}>();

const isEdit = computed(() => !!props.user);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Users', href: '/management/users' },
    { title: isEdit.value ? 'Edit User' : 'Tambah User', href: '#' },
];

const form = useForm({
    name: props.user?.name ?? '',
    email: props.user?.email ?? '',
    password: '',
    password_confirmation: '',
    role_id: props.user?.role_id ?? '',
    kost_ids: props.user?.kost_ids ?? [] as number[],
});

const selectedRole = computed(() => {
    if (!form.role_id) return null;
    return props.roles.find(r => r.id === Number(form.role_id));
});

const isAdmin = computed(() => selectedRole.value?.name === 'admin');

// Clear kost_ids if role is not admin
watch(() => form.role_id, () => {
    if (!isAdmin.value) {
        form.kost_ids = [];
    }
});

const toggleKost = (kostId: number) => {
    const idx = form.kost_ids.indexOf(kostId);
    if (idx === -1) {
        form.kost_ids.push(kostId);
    } else {
        form.kost_ids.splice(idx, 1);
    }
};

const submit = () => {
    if (isEdit.value && props.user) {
        form.put(route('management.users.update', props.user.id));
    } else {
        form.post(route('management.users.store'));
    }
};
</script>

<template>
    <Head :title="isEdit ? 'Edit User' : 'Tambah User'" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-muted/40 py-10">
            <div class="max-w-7xl mx-auto px-6 space-y-8">

            <Heading :title="isEdit ? 'Edit User' : 'Tambah User'" />

            <div class="mx-auto w-full max-w-xl">
                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Name -->
                    <div class="grid gap-2">
                        <Label for="name">Nama</Label>
                        <Input id="name" v-model="form.name" placeholder="Nama lengkap" />
                        <InputError :message="form.errors.name" />
                    </div>

                    <!-- Email -->
                    <div class="grid gap-2">
                        <Label for="email">Email</Label>
                        <Input id="email" v-model="form.email" type="email" placeholder="email@example.com" />
                        <InputError :message="form.errors.email" />
                    </div>

                    <!-- Password -->
                    <div class="grid gap-2">
                        <Label for="password">Password {{ isEdit ? '(kosongkan jika tidak diubah)' : '' }}</Label>
                        <Input id="password" v-model="form.password" type="password" placeholder="••••••••" />
                        <InputError :message="form.errors.password" />
                    </div>

                    <!-- Password Confirmation -->
                    <div class="grid gap-2">
                        <Label for="password_confirmation">Konfirmasi Password</Label>
                        <Input id="password_confirmation" v-model="form.password_confirmation" type="password" placeholder="••••••••" />
                    </div>

                    <!-- Role -->
                    <div class="grid gap-2">
                        <Label for="role_id">Role</Label>
                        <select
                            id="role_id"
                            v-model="form.role_id"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                        >
                            <option value="" disabled>Pilih role...</option>
                            <option v-for="role in roles" :key="role.id" :value="role.id">{{ role.name }}</option>
                        </select>
                        <InputError :message="form.errors.role_id" />
                    </div>

                    <!-- Kost Assignment (only for admin) -->
                    <div v-if="isAdmin" class="grid gap-2">
                        <Label>Assign Kost</Label>
                        <p class="text-xs text-muted-foreground">Pilih kost yang dikelola admin ini.</p>
                        <div class="space-y-2 rounded-lg border p-3">
                            <div v-if="kosts.length === 0" class="text-sm text-muted-foreground">Tidak ada kost tersedia.</div>
                            <label
                                v-for="kost in kosts"
                                :key="kost.id"
                                class="flex items-center gap-3 rounded-md px-2 py-1.5 transition-colors hover:bg-muted/50 cursor-pointer"
                            >
                                <input
                                    type="checkbox"
                                    :checked="form.kost_ids.includes(kost.id)"
                                    @change="toggleKost(kost.id)"
                                    class="h-4 w-4 rounded border-input"
                                />
                                <span class="text-sm">{{ kost.name }}</span>
                            </label>
                        </div>
                        <InputError :message="form.errors.kost_ids" />
                    </div>

                    <div class="flex gap-3">
                        <Button type="submit" :disabled="form.processing">
                            <LoaderCircle v-if="form.processing" class="mr-1.5 h-4 w-4 animate-spin" />
                            {{ isEdit ? 'Update' : 'Simpan' }}
                        </Button>
                        <Button type="button" variant="secondary" as-child>
                            <a :href="route('management.users.index')">Batal</a>
                        </Button>
                    </div>
                </form>
            </div>
            </div>
        </div>
    </AppLayout>
</template>
