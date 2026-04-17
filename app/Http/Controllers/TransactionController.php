<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\TransactionRefund;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->input('search', '');
        $status = $request->input('status', '');
        $paymentType = $request->input('payment_type', '');

        $transactions = Transaction::with(['bill.tenant', 'bill.room'])
            ->when($search, fn ($q) => $q->where(function ($q2) use ($search) {
                $q2->where('order_id', 'like', "%{$search}%")
                    ->orWhereHas('bill.tenant', fn ($q3) => $q3->where('name', 'like', "%{$search}%"));
            }))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($paymentType, fn ($q) => $q->where('payment_type', $paymentType))
            ->latest()
            ->paginate(15)
            ->through(fn (Transaction $trx) => [
                'id' => $trx->id,
                'order_id' => $trx->order_id,
                'tenant_name' => $trx->bill->tenant->name ?? '-',
                'room_number' => $trx->bill->room->room_number ?? '-',
                'payment_type' => $trx->payment_type,
                'total_price' => (float) $trx->total_price,
                'status' => $trx->status,
                'created_at' => $trx->created_at->format('d-m-Y H:i'),
            ]);

        return Inertia::render('Transactions/Index', [
            'transactions' => $transactions,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'payment_type' => $paymentType,
            ],
        ]);
    }

    public function show(Transaction $transaction): Response
    {
        $transaction->load(['bill.tenant', 'bill.room', 'details']);

        $data = [
            'id' => $transaction->id,
            'order_id' => $transaction->order_id,
            'payment_type' => $transaction->payment_type,
            'midtrans_method' => $transaction->midtrans_method,
            'transaction_fee' => (float) $transaction->transaction_fee,
            'total_price' => (float) $transaction->total_price,
            'status' => $transaction->status,
            'snap_token' => $transaction->snap_token,
            'created_at' => $transaction->created_at->format('d-m-Y H:i'),
        ];

        $tenant = [
            'name' => $transaction->bill->tenant->name ?? '-',
            'phone_number' => $transaction->bill->tenant->phone_number ?? '-',
        ];

        $bill = [
            'id' => $transaction->bill->id,
            'total_price' => (float) $transaction->bill->total_price,
            'start_date' => $transaction->bill->start_date->format('d-m-Y'),
            'due_date' => $transaction->bill->due_date->format('d-m-Y'),
            'status' => $transaction->bill->status,
        ];

        $details = $transaction->details()
            ->latest()
            ->get()
            ->map(fn ($d) => [
                'id' => $d->id,
                'status' => $d->status,
                'created_at' => $d->created_at->format('d-m-Y H:i'),
            ]);

        $refunds = TransactionRefund::where('bill_id', $transaction->bill_id)
            ->latest()
            ->get()
            ->map(fn ($r) => [
                'id' => $r->id,
                'amount' => (float) $r->amount,
                'remark' => $r->remark,
                'created_at' => $r->created_at->format('d-m-Y H:i'),
            ]);

        return Inertia::render('Transactions/Show', [
            'transaction' => $data,
            'tenant' => $tenant,
            'bill' => $bill,
            'details' => $details,
            'refunds' => $refunds,
        ]);
    }

    public function refund(Request $request, Transaction $transaction): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'remark' => 'nullable|string|max:1000',
        ]);

        // Create a refund request record and mark the bill as 'refund_request'
        // Avoid duplicate requests: update existing request or create new
        $existing = TransactionRefund::where('bill_id', $transaction->bill_id)->first();
        if ($existing) {
            $existing->update([
                'amount' => $validated['amount'],
                'remark' => $validated['remark'] ?? null,
            ]);
        } else {
            TransactionRefund::create([
                'bill_id' => $transaction->bill_id,
                'amount' => $validated['amount'],
                'remark' => $validated['remark'] ?? null,
            ]);
        }

        // Mark bill as refund_request so admin can review in Refund Request menu
        if ($transaction->bill) {
            $transaction->bill->update(['status' => 'refund_request']);
        }

        return back()->with('success', 'Refund request berhasil diajukan dan menunggu persetujuan.');
    }
}
