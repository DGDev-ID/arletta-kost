<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    protected $fillable = [
        'room_id',
        'email',
        'name',
        'nik',
        'birth_place',
        'birth_date',
        'gender',
        'address',
        'phone_number',
        'start_date',
        'end_date',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    public function rooms(): BelongsToMany
    {
        return $this->belongsToMany(Room::class, 'tenant_rooms')->withTimestamps();
    }

    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class, 'tenant_id');
    }
}
