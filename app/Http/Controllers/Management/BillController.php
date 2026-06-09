<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\Promo;
use App\Models\RoomPricing;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\TransactionService;

class BillController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tenant_id'       => 'required|exists:tenants,id',
            'room_id'         => 'required|exists:rooms,id',
            'room_pricing_id' => 'nullable|exists:room_pricings,id',
            'booking_type'    => 'required|in:monthly,daily',
            'total_price'     => 'required|numeric|min:0',
            'start_date'      => 'required|date',
            'due_date'        => 'required|date|after_or_equal:start_date',
            'payment_scheme'  => 'required|in:full_pay,dp',
            'promo_code'      => 'nullable|string|max:50',
            'person'          => 'nullable|integer|min:1',
        ]);

        // Resolve person charge if pricing is given
        $chargePerson = 0;
        $personCount  = $validated['person'] ?? null;
        if ($personCount && !empty($validated['room_pricing_id'])) {
            $pricing      = RoomPricing::with('roomCategory')->find($validated['room_pricing_id']);
            $maxPerson    = $pricing?->roomCategory?->max_person ?? 2;
            $extraPersons = max(0, (int) $personCount - $maxPerson);
            $chargePerson = $extraPersons * (float) ($pricing?->charge_after_max_person ?? 0);
            $validated['total_price'] = (float) $validated['total_price'] + $chargePerson;
        }

        $validated['status'] = 'unpaid';

        // Apply promo if provided
        $promo = null;
        if (! empty($validated['promo_code'])) {
            $promo = Promo::where('code', strtoupper($validated['promo_code']))->first();

            if (! $promo || ! $promo->isValid((float) $validated['total_price'])) {
                return back()->withErrors(['promo_code' => 'Kode promo tidak valid atau tidak dapat digunakan.'])->withInput();
            }

            // Apply discount to total_price
            $discount = $promo->discountAmount((float) $validated['total_price']);
            $validated['total_price'] = max(0, (float) $validated['total_price'] - $discount);

            // Extend due_date for bonus_days promo
            if ($promo->type === 'bonus_days' && $promo->bonusDays() > 0) {
                $due = new \DateTime($validated['due_date']);
                $due->modify('+' . $promo->bonusDays() . ' days');
                $validated['due_date'] = $due->format('Y-m-d');
            }
        }

        if ($validated['payment_scheme'] === 'dp') {
            $validated['dp_amount'] = $validated['total_price'] * 0.5;
        }

        // Remove fields not in bills table
        unset($validated['promo_code']);
        unset($validated['room_pricing_id']);
        unset($validated['person']);


        DB::transaction(function () use ($validated, $promo, $chargePerson, $personCount) {
            $bill = Bill::create($validated);

            $extra = [];
            if ($personCount !== null) {
                $extra['person']            = (int) $personCount;
                $extra['charge_person_fee'] = $chargePerson;
            }

            $transaction = TransactionService::makeTransaction($bill, 'manual', $extra);

            // Attach the room to the tenant (if not already attached)
            $tenant = \App\Models\Tenant::find($validated['tenant_id']);
            if (! $tenant->rooms()->where('rooms.id', $validated['room_id'])->exists()) {
                $tenant->rooms()->attach($validated['room_id']);
            }

            // Increment promo usage counter
            if ($promo) {
                $promo->increment('usage_count');
            }
        });
        DB::commit();

        return back()->with('success', 'Bill berhasil dibuat.');
    }

    public function updateStatus(Request $request, Bill $bill): RedirectResponse
    {
        TransactionService::updateStatus($bill, $request->input('status'));
        return back()->with('success', 'Status bill berhasil diperbarui.');
    }

    public function makeSuccess(Transaction $transaction): RedirectResponse
    {
        TransactionService::makeSuccess($transaction);
        return back()->with('success', 'Transaksi berhasil diupdate menjadi Success.');
    }

    public function makeFailed(Transaction $transaction): RedirectResponse
    {
        TransactionService::makeFailed($transaction);
        return back()->with('success', 'Transaksi berhasil diupdate menjadi Failed.');
}
}
