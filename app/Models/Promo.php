<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Promo extends Model
{
    protected $fillable = [
        'name',
        'code',
        'description',
        'type',
        'value',
        'min_purchase',
        'max_usage',
        'usage_count',
        'is_active',
        'valid_from',
        'valid_until',
    ];

    protected function casts(): array
    {
        return [
            'value'        => 'decimal:2',
            'min_purchase' => 'decimal:2',
            'max_usage'    => 'integer',
            'usage_count'  => 'integer',
            'is_active'    => 'boolean',
            'valid_from'   => 'date',
            'valid_until'  => 'date',
        ];
    }

    /**
     * Check whether this promo is currently usable.
     */
    public function isValid(float $totalPrice = 0): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->max_usage !== null && $this->usage_count >= $this->max_usage) {
            return false;
        }

        if ($this->valid_from && now()->startOfDay()->lt($this->valid_from)) {
            return false;
        }

        if ($this->valid_until && now()->endOfDay()->gt($this->valid_until->endOfDay())) {
            return false;
        }

        if ($this->min_purchase !== null && $totalPrice < (float) $this->min_purchase) {
            return false;
        }

        return true;
    }

    /**
     * Calculate the discount amount (in IDR) for a given total price.
     * Returns 0 for bonus_days promos (those only extend due_date).
     */
    public function discountAmount(float $totalPrice): float
    {
        return match ($this->type) {
            'discount_percent' => round($totalPrice * ((float) $this->value / 100), 2),
            'discount_amount'  => min((float) $this->value, $totalPrice),
            default            => 0.0,
        };
    }

    /**
     * Returns the bonus days if type is bonus_days, else 0.
     */
    public function bonusDays(): int
    {
        return $this->type === 'bonus_days' ? (int) $this->value : 0;
    }
}
