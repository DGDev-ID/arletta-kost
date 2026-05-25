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
        $promos = $this->promos;

        if ($promos->isEmpty()) {
            return [
                'original_price' => round($originalPrice, 2),
                'final_price'    => round($originalPrice, 2),
                'applied_promos' => [],
                'bonus_days'     => 0,
                'cashback'       => 0.0,
            ];
        }

        $finalPrice = $originalPrice;
        $bonusDays  = 0;
        $cashback   = 0.0;
        $appliedPromos = [];

        foreach ($promos as $promo) {
            switch ($promo->type) {
                case 'discount_percent':
                case 'discount_amount':
                    $discount    = $promo->discountAmount($originalPrice);
                    $finalPrice  = max(0, $finalPrice - $discount);
                    break;
                case 'bonus_days':
                    $bonusDays  += $promo->bonusDays();
                    break;
                case 'cashback':
                    $cashback   += round(min((float) $promo->value, $originalPrice), 2);
                    break;
            }
            $appliedPromos[] = [
                'id'    => $promo->id,
                'type'  => $promo->type,
                'value' => $promo->value,
            ];
        }

        return [
            'original_price' => round($originalPrice, 2),
            'final_price'    => round($finalPrice, 2),
            'applied_promos' => $appliedPromos,
            'bonus_days'     => $bonusDays,
            'cashback'       => round($cashback, 2),
        ];
    }
}
