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
        $promo = $this->promos()->latest()->first();

        return [
            'original_price' => round($originalPrice, 2),
            'final_price'    => round($originalPrice, 2),
            'applied_promo'  => $promo?->type,
            'bonus_days'     => 0,
            'cashback'       => 0.0,
        ];
    }
}
