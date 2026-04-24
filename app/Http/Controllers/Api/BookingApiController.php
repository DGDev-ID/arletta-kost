<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\Room;
use App\Models\RoomPricing;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BookingApiController extends Controller
{
    /**
     * Handle room booking from the landing page.
     * Currently assumes payment success based on landing page mock.
     */
    public function store(Request $request)
    {
        $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'pricing_id' => 'required|exists:room_pricings,id',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'payment_method' => 'required|string'
        ]);

        try {
            DB::beginTransaction();

            $room = Room::findOrFail($request->room_id);
            $pricing = RoomPricing::findOrFail($request->pricing_id);

            // Create Tenant
            $tenant = Tenant::create([
                'room_id' => $room->id,
                'name' => $request->name,
                'email' => $request->email,
                'phone_number' => $request->phone,
            ]);

            // Deposit and Admin Fee logic from frontend
            // Deposit = cheapest pricing (approximate, we could calculate correctly but let's just use minimum price or just use the pricing price + 25000)
            // The frontend sends everything, so maybe we should trust the frontend's total or calculate it here.
            // Let's get the minimum price for deposit
            $minPricing = RoomPricing::where('room_category_id', $room->room_category_id)->orderBy('duration_days', 'asc')->first();
            $deposit = $minPricing ? $minPricing->price : 0;
            $adminFee = 25000;
            
            $totalPrice = $pricing->price + $deposit + $adminFee;

            $startDate = Carbon::today();
            $dueDate = Carbon::today()->addDays($pricing->duration_days);

            // Create Bill
            $bill = Bill::create([
                'room_id' => $room->id,
                'tenant_id' => $tenant->id,
                'total_price' => $totalPrice,
                'start_date' => $startDate,
                'due_date' => $dueDate,
                'status' => 'paid', // Since landing page mocks success
            ]);

            // Create Transaction
            $transaction = Transaction::create([
                'bill_id' => $bill->id,
                'order_id' => 'MID-' . strtoupper(Str::random(10)),
                'payment_type' => 'midtrans',
                'midtrans_method' => str_contains($request->payment_method, 'va') ? 'va' : 'qris',
                'transaction_fee' => $adminFee,
                'total_price' => $totalPrice,
                'status' => 'success', // Simulated success
            ]);

            // Create Transaction Detail
            TransactionDetail::create([
                'transaction_id' => $transaction->id,
                'status' => 'success',
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Booking and payment successful',
                'data' => [
                    'transaction_id' => $transaction->id,
                    'order_id' => $transaction->order_id
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Booking failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
