<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RoomPricing extends Model
{
    protected $fillable = [
        'room_category_id',
        'duration_days',
        'price',
    ];

    public function roomCategory(): BelongsTo
    {
        return $this->belongsTo(RoomCategory::class, 'room_category_id');
    }

    public function promos(): HasMany
    {
        return $this->hasMany(RoomPricingPromo::class, 'room_pricing_id');
    }

    public function getFinalPrice(): array
    {
        $originalPrice = (float) $this->price;
        $finalPrice = $originalPrice;
        $appliedPromo = null;
        $bonusDays = 0;
        $cashback = 0.0;

        $promo = $this->promos()->latest()->first();

        if ($promo) {
            $appliedPromo = $promo->type;

            match ($promo->type) {
                'discount_percent' => $finalPrice = $originalPrice * (1 - $promo->discount_amount / 100),
                'discount_amount'  => $finalPrice = max(0, $originalPrice - (float) $promo->discount_amount),
                'bonus_days'       => $bonusDays = (int) $promo->bonus_days,
                'cashback'         => $cashback = (float) $promo->discount_amount,
            };
        }

        return [
            'original_price' => round($originalPrice, 2),
            'final_price'    => round($finalPrice, 2),
            'applied_promo'  => $appliedPromo,
            'bonus_days'     => $bonusDays,
            'cashback'       => $cashback,
        ];
    }
}
