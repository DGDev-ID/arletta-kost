<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiBaseController;
use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\Promo;
use App\Models\Room;
use App\Models\RoomPricing;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Services\TransactionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BookingApiController extends ApiBaseController
{
    /**
     * Handle room booking from the landing page.
     * Currently assumes payment success based on landing page mock.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'cust_name'      => 'required|string|max:255',
            'cust_email'     => 'required|email|max:255',
            'cust_phone'     => 'required|string|max:20',

            'room_id'        => 'required|exists:rooms,id',
            'room_pricing_id'     => 'required|exists:room_pricings,id',

            'start_date'     => 'required|date',
            'payment_scheme' => 'required|in:full_pay,dp',

            'payment_method' => 'required|string',
            'promo_code'     => 'nullable|string|max:50',
        ]);

        try {
            $room = Room::findOrFail($request->room_id);
            $pricing = RoomPricing::findOrFail($request->room_pricing_id);

            $startDate = Carbon::parse($request->start_date);
            $dueDate = (clone $startDate)->addDays($pricing->duration_days);

            if (! $room->isAvailableForDates($startDate->toDateString(), $dueDate->toDateString())) {
                return $this->clientError('Kamar sudah dipesan atau sedang digunakan pada tanggal yang dipilih. Silakan pilih tanggal atau kamar lain.');
            }

            $transaction = DB::transaction(function () use ($request, $room, $pricing, $startDate, $dueDate) {
                $tenant = Tenant::create([
                    'name' => $request->cust_name,
                    'email' => $request->cust_email,
                    'phone_number' => $request->cust_phone,
                ]);

                $totalPrice = $request->payment_scheme === 'dp' ? $pricing->price * 0.5 : $pricing->price;

                $promo = null;
                if (! empty($request->promo_code)) {
                    $promo = Promo::where('code', strtoupper($request->promo_code))->first();

                    if (! $promo || ! $promo->isValid((float) $totalPrice)) {
                        throw new \Exception('Kode promo tidak valid atau tidak dapat digunakan.');
                    }

                    $discount = $promo->discountAmount((float) $totalPrice);
                    $totalPrice = max(0, (float) $totalPrice - $discount);

                    if ($promo->type === 'bonus_days' && $promo->bonusDays() > 0) {
                        $dueDate->addDays($promo->bonusDays());
                    }
                }

                $bill = Bill::create([
                    'tenant_id'      => $tenant->id,
                    'room_id'        => $room->id,
                    'total_price'    => $request->payment_scheme === 'dp' ? 0 : $totalPrice,
                    'dp_amount'      => $request->payment_scheme === 'dp' ? $totalPrice : 0,
                    'start_date'     => $startDate,
                    'due_date'       => $dueDate,
                    'payment_scheme' => $request->payment_scheme,
                    'status'         => 'unpaid',
                ]);

                $transaction = TransactionService::makeTransaction($bill, 'midtrans');

                if (! $tenant->rooms()->where('rooms.id', $room->id)->exists()) {
                    $tenant->rooms()->attach($room->id);
                }

                if ($promo) {
                    $promo->increment('usage_count');
                }

                return $transaction;
            });

            return $this->success($transaction, 'Booking successful');
        } catch (\Exception $e) {
            return $this->serverError($e);
        }
    }

    public function checkStatus(Transaction $transaction)
    {
        return $this->success($transaction->status, 'Transaction status retrieved successfully');
    }
}
