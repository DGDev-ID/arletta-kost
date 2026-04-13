<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kost extends Model
{
    protected $table = 'm_kosts';

    protected $fillable = [
        'owner_id',
        'name',
        'address',
        'address_coordinate',
        'description',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function admins(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'kost_admins', 'kost_id', 'admin_id');
    }

    public function roomCategories(): HasMany
    {
        return $this->hasMany(RoomCategory::class, 'kost_id');
    }
}
