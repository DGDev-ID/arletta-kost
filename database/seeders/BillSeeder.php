<?php

namespace Database\Seeders;

use App\Models\Bill;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BillSeeder extends Seeder
{
    public function run(): void
    {
        $tenants = Tenant::with('room.roomCategory.pricings')->get();

        foreach ($tenants as $tenant) {
            $pricing = $tenant->room->roomCategory->pricings->first();
            $startDate = now()->subDays(15);

            Bill::create([
                'room_id' => $tenant->room_id,
                'tenant_id' => $tenant->id,
                'total_price' => $pricing?->price ?? 1500000,
                'start_date' => $startDate,
                'due_date' => $startDate->copy()->addDays($pricing?->duration_days ?? 30),
                'signature' => Str::random(64),
                'status' => 'unpaid',
            ]);

            Bill::create([
                'room_id' => $tenant->room_id,
                'tenant_id' => $tenant->id,
                'total_price' => $pricing?->price ?? 1500000,
                'start_date' => $startDate->copy()->subDays(30),
                'due_date' => $startDate->copy()->subDays(30)->addDays($pricing?->duration_days ?? 30),
                'signature' => Str::random(64),
                'status' => 'paid',
            ]);
        }
    }
}
