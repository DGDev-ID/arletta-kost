<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Head, useForm } from '@inertiajs/vue3';
import { 
    LoaderCircle, 
    Mail, 
    Lock, 
    Check, 
    Home 
} from 'lucide-vue-next';

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Masuk ke Dashboard" />

    <div class="flex min-h-screen w-full bg-white text-slate-900">
        <div class="hidden lg:flex w-1/2 flex-col justify-between bg-[#131b2b] p-12 text-white">
            <div class="flex items-center">
                <img src="/images/logo-arletta.png" alt="Arletta Cozy Logo" class="h-12 w-auto">
            </div>

            <div class="max-w-lg">
                <h1 class="mb-4 text-4xl font-bold">Kelola Kost Anda</h1>
                <p class="mb-8 text-slate-400">
                    Platform manajemen kost terpadu untuk mengelola penghuni, ketersediaan kamar, tagihan bulanan, dan fasilitas.
                </p>

                <div class="mb-8 flex gap-8 border-b border-slate-700 pb-8">
                    <div>
                        <div class="text-3xl font-bold">124</div>
                        <div class="text-sm text-slate-400">Penghuni Aktif</div>
                    </div>
                    <div>
                        <div class="text-3xl font-bold">45</div>
                        <div class="text-sm text-slate-400">Kamar Tersedia</div>
                    </div>
                    <div>
                        <div class="text-3xl font-bold">24/7</div>
                        <div class="text-sm text-slate-400">Keamanan</div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 text-sm text-slate-300">
                    <div class="flex items-center gap-2"><Check class="h-4 w-4 text-white" /> Manajemen Penghuni</div>
                    <div class="flex items-center gap-2"><Check class="h-4 w-4 text-white" /> Tagihan Otomatis</div>
                    <div class="flex items-center gap-2"><Check class="h-4 w-4 text-white" /> Manajemen Kamar</div>
                    <div class="flex items-center gap-2"><Check class="h-4 w-4 text-white" /> Pembayaran Online</div>
                    <div class="flex items-center gap-2"><Check class="h-4 w-4 text-white" /> Laporan Keuangan</div>
                    <div class="flex items-center gap-2"><Check class="h-4 w-4 text-white" /> Pencatatan Komplain</div>
                </div>
            </div>

            <div class="text-sm text-slate-500">
                &copy; {{ new Date().getFullYear() }} Arletta Kost. All rights reserved.
            </div>
        </div>

        <div class="flex w-full items-center justify-center p-8 lg:w-1/2">
            <div class="w-full max-w-sm">
                <div class="mb-8">
                    <h2 class="text-2xl font-bold text-slate-900">Masuk ke Dashboard</h2>
                    <p class="text-sm text-slate-500">Silakan masuk dengan akun Anda</p>
                </div>

                <div v-if="status" class="mb-4 text-sm font-medium text-green-600">
                    {{ status }}
                </div>

                <form @submit.prevent="submit" class="flex flex-col gap-6">
                    <div class="grid gap-6">
                        <div class="grid gap-2">
                            <Label for="email">Email</Label>
                            <div class="relative">
                                <Mail class="absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
                                <Input
                                    id="email"
                                    type="email"
                                    required
                                    autofocus
                                    tabindex="1"
                                    autocomplete="email"
                                    v-model="form.email"
                                    placeholder="nama@email.com"
                                    class="pl-10 bg-slate-50/50"
                                />
                            </div>
                            <InputError :message="form.errors.email" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="password">Password</Label>
                            <div class="relative">
                                <Lock class="absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
                                <Input
                                    id="password"
                                    type="password"
                                    required
                                    tabindex="2"
                                    autocomplete="current-password"
                                    v-model="form.password"
                                    placeholder="Masukkan password"
                                    class="pl-10 bg-slate-50/50"
                                />
                            </div>
                            <InputError :message="form.errors.password" />
                        </div>

                        <!-- <div class="flex items-center justify-between" tabindex="3">
                            <Label for="remember" class="flex items-center space-x-3 cursor-pointer">
                                <Checkbox id="remember" v-model:checked="form.remember" tabindex="4" />
                                <span class="font-normal text-slate-600">Ingat saya</span>
                            </Label>
                            
                            <TextLink v-if="canResetPassword" :href="route('password.request')" class="text-sm font-medium text-slate-900" tabindex="5"> 
                                Lupa password? 
                            </TextLink>
                        </div> -->

                        <Button type="submit" class="mt-2 w-full bg-slate-900 text-white hover:bg-slate-800" tabindex="4" :disabled="form.processing">
                            <LoaderCircle v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                            Masuk
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>