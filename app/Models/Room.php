<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Room extends Model
{
    protected $fillable = [
        'room_category_id',
        'room_number',
        'status',
    ];

    public function roomCategory(): BelongsTo
    {
        return $this->belongsTo(RoomCategory::class, 'room_category_id');
    }

    public function tenant(): HasOne
    {
        return $this->hasOne(Tenant::class, 'room_id');
    }

    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class, 'room_id');
    }
}
