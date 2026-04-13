<?php

namespace Database\Seeders;

use App\Models\RoomPricing;
use App\Models\RoomPricingPromo;
use Illuminate\Database\Seeder;

class RoomPricingPromoSeeder extends Seeder
{
    public function run(): void
    {
        $pricings = RoomPricing::all();

        $promoTypes = ['discount_percent', 'discount_amount', 'bonus_days', 'cashback'];

        foreach ($pricings as $index => $pricing) {
            RoomPricingPromo::create([
                'room_pricing_id' => $pricing->id,
                'type' => $promoTypes[$index % count($promoTypes)],
            ]);
        }
    }
}
