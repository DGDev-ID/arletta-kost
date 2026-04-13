<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bill extends Model
{
    protected $fillable = [
        'room_id',
        'tenant_id',
        'total_price',
        'start_date',
        'due_date',
        'signature',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'due_date' => 'date',
            'total_price' => 'decimal:2',
        ];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'bill_id');
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(TransactionRefund::class, 'bill_id');
    }
}
