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
        ]);

        $validated['status'] = 'unpaid';

        DB::transaction(function () use ($validated) {
            $bill = Bill::create($validated);

            // Auto-create transaction with manual payment type
            $transaction = $bill->transactions()->create([
                'order_id' => 'BILL-' . $bill->id . '-' . now()->timestamp,
                'payment_type' => 'manual',
                'total_price' => $bill->total_price,
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

            // Update bill status to paid
            $transaction->bill->update(['status' => 'paid']);
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
