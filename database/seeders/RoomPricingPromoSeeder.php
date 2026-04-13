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

        $promoTemplates = [
            [
                'type' => 'discount_percent',
                'discount_amount' => 10,
                'bonus_days' => null,
            ],
            [
                'type' => 'discount_amount',
                'discount_amount' => 50000,
                'bonus_days' => null,
            ],
            [
                'type' => 'bonus_days',
                'discount_amount' => null,
                'bonus_days' => 3,
            ],
            [
                'type' => 'cashback',
                'discount_amount' => 25000,
                'bonus_days' => null,
            ],
        ];

        foreach ($pricings as $index => $pricing) {
            $template = $promoTemplates[$index % count($promoTemplates)];

            RoomPricingPromo::create([
                'room_pricing_id' => $pricing->id,
                'type' => $template['type'],
                'discount_amount' => $template['discount_amount'],
                'bonus_days' => $template['bonus_days'],
            ]);
        }
    }
}
