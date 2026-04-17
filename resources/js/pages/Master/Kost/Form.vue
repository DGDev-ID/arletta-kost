<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle, MapPin, X, Search } from 'lucide-vue-next';
import { computed, onUnmounted, ref, nextTick } from 'vue';
import * as L from 'leaflet';
import 'leaflet/dist/leaflet.css';

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

// Leaflet map integration for selecting coordinates
// Fix default marker icon paths for Vite
L.Icon.Default.mergeOptions({
    iconRetinaUrl: new URL('leaflet/dist/images/marker-icon-2x.png', import.meta.url).href,
    iconUrl: new URL('leaflet/dist/images/marker-icon.png', import.meta.url).href,
    shadowUrl: new URL('leaflet/dist/images/marker-shadow.png', import.meta.url).href,
});

// ── Map State & Functions ──────────────────────────────────────────────────
const showMap = ref(false);
const mapContainer = ref<HTMLElement | null>(null);
const searchQuery = ref('');
const searchLoading = ref(false);
const searchResults = ref<{ display_name: string; lat: string; lon: string }[]>([]);

let map: L.Map | null = null;
let marker: L.Marker | null = null;

const parseCoordinates = (val: string | null | undefined): [number, number] | null => {
    if (!val) return null;
    const parts = String(val).split(',').map((s) => parseFloat(s.trim()));
    if (parts.length === 2 && Number.isFinite(parts[0]) && Number.isFinite(parts[1])) {
        return [parts[0], parts[1]];
    }
    return null;
};

const setFormCoord = (lat: number, lng: number) => {
    form.address_coordinate = `${lat.toFixed(7)}, ${lng.toFixed(7)}`;
};

const initMap = () => {
    if (!mapContainer.value || map) return;

    const initial = parseCoordinates(form.address_coordinate) ?? [-6.2088, 106.8456];
    
    map = L.map(mapContainer.value).setView(initial, 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 19,
    }).addTo(map);

    const coords = parseCoordinates(form.address_coordinate);
    if (coords) {
        marker = L.marker(coords, { draggable: true }).addTo(map);
        marker.on('dragend', () => {
            const p = marker!.getLatLng();
            setFormCoord(p.lat, p.lng);
        });
    }

    map.on('click', (e: L.LeafletMouseEvent) => {
        const latlng = e.latlng;
        if (marker) {
            marker.setLatLng(latlng);
        } else {
            marker = L.marker(latlng, { draggable: true }).addTo(map!);
            marker.on('dragend', () => {
                const p = marker!.getLatLng();
                setFormCoord(p.lat, p.lng);
            });
        }
        setFormCoord(latlng.lat, latlng.lng);
    });
};

const openMap = async () => {
    showMap.value = true;
    await nextTick();
    initMap();
    setTimeout(() => map?.invalidateSize(), 100);
};

const clearCoordinate = () => {
    form.address_coordinate = '';
    if (marker && map) {
        map.removeLayer(marker);
        marker = null;
    }
};

const searchLocation = async () => {
    const q = searchQuery.value.trim();
    if (!q) return;
    searchLoading.value = true;
    searchResults.value = [];
    try {
        const res = await fetch(
            `https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(q)}&format=json&limit=5`,
            { headers: { 'Accept-Language': 'id,en' } },
        );
        searchResults.value = await res.json();
    } finally {
        searchLoading.value = false;
    }
};

const selectResult = (result: { display_name: string; lat: string; lon: string }) => {
    const lat = parseFloat(result.lat);
    const lng = parseFloat(result.lon);
    const latlng: L.LatLngTuple = [lat, lng];
    setFormCoord(lat, lng);
    
    // Opsional: otomatis mengisi field alamat jika alamat masih kosong
    if (!form.address) form.address = result.display_name;
    
    map?.setView(latlng, 16);
    if (marker) {
        marker.setLatLng(latlng);
    } else {
        marker = L.marker(latlng, { draggable: true }).addTo(map!);
        marker.on('dragend', () => {
            const p = marker!.getLatLng();
            setFormCoord(p.lat, p.lng);
        });
    }
    searchResults.value = [];
    searchQuery.value = result.display_name;
};

onUnmounted(() => {
    map?.remove();
    map = null;
    marker = null;
});
</script>

<template>
    <Head :title="isEdit ? 'Edit Kost' : 'Tambah Kost'" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-muted/40 ">
            <div class="max-w-3xl mx-auto px-16 py-8">
            <div class="rounded-2xl border bg-background shadow-sm p-8 space-y-8">


            <Heading :title="isEdit ? 'Edit Kost' : 'Tambah Kost'"
            description="Tambah informasi detail untuk kost ini." />

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
                        <Label for="address_coordinate">
                            Koordinat Lokasi
                            <span class="text-muted-foreground text-xs font-normal ml-1">(opsional)</span>
                        </Label>

                        <div class="flex gap-2">
                            <Input
                                id="address_coordinate"
                                v-model="form.address_coordinate"
                                readonly
                                placeholder="-6.2088000, 106.8456000"
                                class="flex-1 bg-muted/40 cursor-default"
                            />
                            <button
                                type="button"
                                @click="openMap"
                                class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 border border-blue-200 text-sm font-medium transition whitespace-nowrap"
                            >
                                <MapPin :size="16" />
                                Pilih di Maps
                            </button>
                            <button
                                v-if="form.address_coordinate"
                                type="button"
                                @click="clearCoordinate"
                                class="inline-flex items-center px-3 rounded-lg bg-red-50 text-red-500 hover:bg-red-100 border border-red-200 transition"
                                title="Hapus koordinat"
                            >
                                <X :size="16" />
                            </button>
                        </div>
                        <InputError :message="form.errors.address_coordinate" />

                        <div v-if="showMap" class="mt-1 rounded-xl border overflow-hidden shadow-sm">
                            <div class="p-3 bg-background border-b space-y-2">
                                <div class="flex gap-2 items-center">
                                    <MapPin :size="16" class="text-blue-500 shrink-0" />
                                    <Input
                                        v-model="searchQuery"
                                        type="text"
                                        placeholder="Cari nama tempat atau alamat..."
                                        class="flex-1"
                                        @keydown.enter.prevent="searchLocation"
                                    />
                                    <button
                                        type="button"
                                        @click="searchLocation"
                                        :disabled="searchLoading"
                                        class="inline-flex items-center gap-1 px-3 py-2 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 border border-blue-200 text-sm transition disabled:opacity-50"
                                    >
                                        <Search :size="14" />
                                        {{ searchLoading ? '...' : 'Cari' }}
                                    </button>
                                    <button
                                        type="button"
                                        @click="showMap = false"
                                        class="inline-flex items-center gap-1 px-3 py-2 rounded-lg bg-muted hover:bg-muted/80 text-sm transition"
                                    >
                                        <X :size="14" />
                                        Tutup
                                    </button>
                                </div>
                                <ul v-if="searchResults.length" class="rounded-lg border divide-y text-sm max-h-40 overflow-y-auto">
                                    <li
                                        v-for="result in searchResults"
                                        :key="result.lat + result.lon"
                                        @click="selectResult(result)"
                                        class="px-3 py-2 cursor-pointer hover:bg-muted/60 transition truncate"
                                    >
                                        {{ result.display_name }}
                                    </li>
                                </ul>
                            </div>
                            <div ref="mapContainer" class="w-full h-80"></div>
                            <div class="px-4 py-2.5 bg-muted/40 text-xs text-muted-foreground text-center">
                                Cari lokasi, klik pada peta, atau geser marker untuk memilih koordinat. Peta menggunakan OpenStreetMap.
                            </div>
                        </div>

                        <p v-if="form.address_coordinate" class="text-xs text-green-600 flex items-center gap-1">
                            <MapPin :size="12" />
                            Koordinat terpilih: {{ form.address_coordinate }}
                        </p>
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
        </div>
    </AppLayout>
</template>
