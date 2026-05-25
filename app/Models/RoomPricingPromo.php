<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomPricingPromo extends Model
{
    protected $fillable = [
        'room_pricing_id',
        'type',
        'value',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'float',
        ];
    }

    public function roomPricing(): BelongsTo
    {
        return $this->belongsTo(RoomPricing::class, 'room_pricing_id');
    }

    /** Discount amount applied to the given price. Returns 0 for non-monetary types. */
    public function discountAmount(float $price): float
    {
        return match ($this->type) {
            'discount_percent' => round($price * ($this->value / 100), 2),
            'discount_amount'  => round(min($this->value, $price), 2),
            default            => 0.0,
        };
    }

    /** Bonus days for bonus_days type. Returns 0 for other types. */
    public function bonusDays(): int
    {
        return $this->type === 'bonus_days' ? (int) $this->value : 0;
    }
}
