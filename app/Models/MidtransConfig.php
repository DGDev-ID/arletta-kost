<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MidtransConfig extends Model
{
    protected $fillable = [
        'owner_id',
        'server_key',
        'client_key',
        'is_production',
    ];

    protected $hidden = [
        'server_key',
        'client_key',
    ];

    protected function casts(): array
    {
        return [
            'is_production' => 'boolean',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}
