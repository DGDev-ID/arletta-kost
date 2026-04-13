<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaction extends Model
{
    protected $fillable = [
        'bill_id',
        'order_id',
        'payment_type',
        'midtrans_method',
        'transaction_fee',
        'total_price',
        'status',
        'snap_token',
    ];

    protected function casts(): array
    {
        return [
            'transaction_fee' => 'decimal:2',
            'total_price' => 'decimal:2',
        ];
    }

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class, 'bill_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(TransactionDetail::class, 'transaction_id');
    }
}
