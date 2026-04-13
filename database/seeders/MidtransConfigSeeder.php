<?php

namespace Database\Seeders;

use App\Models\MidtransConfig;
use App\Models\User;
use Illuminate\Database\Seeder;

class MidtransConfigSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::whereHas('roles', fn ($q) => $q->where('name', 'owner'))->first();

        MidtransConfig::create([
            'owner_id' => $owner->id,
            'server_key' => 'SB-Mid-server-XXXXXXXXXXXXXXXX',
            'client_key' => 'SB-Mid-client-XXXXXXXXXXXXXXXX',
            'is_production' => false,
        ]);
    }
}
