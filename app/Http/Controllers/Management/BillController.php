<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BillController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tenant_id' => 'required|exists:tenants,id',
            'room_id' => 'required|exists:rooms,id',
            'total_price' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:start_date',
            'payment_scheme' => 'required|in:full_pay,dp',
        ]);

        $validated['status'] = 'unpaid';

        if ($validated['payment_scheme'] === 'dp') {
            $validated['dp_amount'] = $validated['total_price'] * 0.5;
        }

        DB::transaction(function () use ($validated) {
            $bill = Bill::create($validated);

            // Auto-create transaction with manual payment type
            $transaction = $bill->transactions()->create([
                'order_id' => 'BILL-' . $bill->id . '-' . now()->timestamp,
                'payment_type' => 'manual',
                'transaction_type' => $bill->payment_scheme === 'dp' ? 'down_payment' : 'full_payment',
                'total_price' => $bill->payment_scheme === 'dp' ? $bill->dp_amount : $bill->total_price,
                'status' => 'pending',
            ]);

            // Auto-create transaction detail
            $transaction->details()->create([
                'status' => 'pending',
            ]);

            // Attach the room to the tenant (if not already attached)
            $tenant = \App\Models\Tenant::find($validated['tenant_id']);
            if (! $tenant->rooms()->where('rooms.id', $validated['room_id'])->exists()) {
                $tenant->rooms()->attach($validated['room_id']);
            }
        });

        return back()->with('success', 'Bill berhasil dibuat.');
    }

    public function updateStatus(Request $request, Bill $bill): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:paid,unpaid,cancelled',
        ]);

        $bill->update($validated);

        return back()->with('success', 'Status bill berhasil diperbarui.');
    }

    public function makeSuccess(Transaction $transaction): RedirectResponse
    {
        DB::transaction(function () use ($transaction) {
            $transaction->update(['status' => 'success']);

            // Update transaction details
            $transaction->details()->update(['status' => 'success']);

            // Add a success detail record
            $transaction->details()->create(['status' => 'success']);

            // Update bill status based on transaction type
            $billStatus = 'paid';
            if ($transaction->transaction_type === 'down_payment') {
                $billStatus = 'down_payment';

                // Automatically generate the remaining payment transaction
                $remainingAmount = $transaction->bill->total_price - $transaction->bill->dp_amount;
                $newTransaction = $transaction->bill->transactions()->create([
                    'order_id' => 'REMAINING-' . $transaction->bill->id . '-' . now()->timestamp,
                    'payment_type' => 'manual',
                    'transaction_type' => 'finished_payment',
                    'total_price' => $remainingAmount,
                    'status' => 'pending',
                ]);
                
                $newTransaction->details()->create([
                    'status' => 'pending',
                ]);

            } elseif ($transaction->transaction_type === 'finished_payment') {
                $billStatus = 'finished_payment';
            }
            
            $transaction->bill->update(['status' => $billStatus]);
        });

        return back()->with('success', 'Transaksi berhasil diupdate menjadi Success.');
    }

    public function makeFailed(Transaction $transaction): RedirectResponse
    {
        DB::transaction(function () use ($transaction) {
            $transaction->update(['status' => 'failed']);

            // Update transaction details
            $transaction->details()->update(['status' => 'failed']);

            // Add a failed detail record
            $transaction->details()->create(['status' => 'failed']);

            // Update bill status to cancelled
            $transaction->bill->update(['status' => 'cancelled']);
        });

        return back()->with('success', 'Transaksi berhasil diupdate menjadi Failed.');
}
}
