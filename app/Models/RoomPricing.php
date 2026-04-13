<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
}
