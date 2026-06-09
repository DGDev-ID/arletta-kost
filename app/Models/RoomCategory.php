<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RoomCategory extends Model
{
    protected $fillable = [
        'kost_id',
        'name',
        'description',
        'gender',
        'max_person',
    ];

    public function kost(): BelongsTo
    {
        return $this->belongsTo(Kost::class, 'kost_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(RoomCategoryImage::class, 'room_category_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(RoomCategoryDetail::class, 'room_category_id');
    }

    public function pricings(): HasMany
    {
        return $this->hasMany(RoomPricing::class, 'room_category_id');
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class, 'room_category_id');
    }
}
