<?php

namespace Database\Seeders;

use App\Models\Kost;
use App\Models\User;
use Illuminate\Database\Seeder;

class KostSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::whereHas('roles', fn ($q) => $q->where('name', 'owner'))->first();
        $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'admin'))->first();

        $kost = Kost::create([
            'owner_id' => $owner->id,
            'name' => 'Kost Arletta Residence',
            'address' => 'Jl. Merdeka No. 10, Jakarta Selatan',
            'address_coordinate' => '-6.2088,106.8456',
            'description' => 'Kost nyaman dan strategis di pusat kota Jakarta dengan fasilitas lengkap.',
        ]);

        $kost->admins()->attach($admin->id);
    }
}
