<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomPricingPromo extends Model
{
    protected $fillable = [
        'room_pricing_id',
        'type',
    ];

    protected function casts(): array
    {
        return [];
    }

    public function roomPricing(): BelongsTo
    {
        return $this->belongsTo(RoomPricing::class, 'room_pricing_id');
    }
}
