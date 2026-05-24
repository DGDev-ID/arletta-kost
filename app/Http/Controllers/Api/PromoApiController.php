<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiBaseController;
use App\Models\Promo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PromoApiController extends ApiBaseController
{
    /**
     * POST /api/promos/validate
     * Validate a promo code and return discount info.
     */
    public function validate(Request $request): JsonResponse
    {
        $request->validate([
            'code'        => 'required|string',
            'total_price' => 'required|numeric|min:0',
        ]);

        $promo = Promo::where('code', strtoupper($request->input('code')))->first();

        if (! $promo) {
            return $this->clientError('Kode promo tidak ditemukan.', null, 422);
        }

        $totalPrice = (float) $request->input('total_price');

        if (! $promo->isValid($totalPrice)) {
            $reason = $this->invalidReason($promo, $totalPrice);
            return $this->clientError($reason, null, 422);
        }

        $discountAmount = $promo->discountAmount($totalPrice);
        $bonusDays      = $promo->bonusDays();
        $finalPrice     = max(0, $totalPrice - $discountAmount);

        return $this->success([
            'promo_id'        => $promo->id,
            'name'            => $promo->name,
            'code'            => $promo->code,
            'type'            => $promo->type,
            'value'           => (float) $promo->value,
            'discount_amount' => $discountAmount,
            'bonus_days'      => $bonusDays,
            'final_price'     => $finalPrice,
        ], 'Promo valid.');
    }

    // ─── Private helpers ──────────────────────────────────────────────

    private function invalidReason(Promo $promo, float $totalPrice): string
    {
        if (! $promo->is_active) {
            return 'Promo ini sudah tidak aktif.';
        }

        if ($promo->max_usage !== null && $promo->usage_count >= $promo->max_usage) {
            return 'Kuota promo sudah habis.';
        }

        if ($promo->valid_from && now()->startOfDay()->lt($promo->valid_from)) {
            return 'Promo belum berlaku.';
        }

        if ($promo->valid_until && now()->endOfDay()->gt($promo->valid_until->endOfDay())) {
            return 'Promo sudah kadaluarsa.';
        }

        if ($promo->min_purchase !== null && $totalPrice < (float) $promo->min_purchase) {
            $formatted = number_format((float) $promo->min_purchase, 0, ',', '.');
            return "Minimum pembelian Rp {$formatted} untuk menggunakan promo ini.";
        }

        return 'Promo tidak dapat digunakan.';
    }
}
