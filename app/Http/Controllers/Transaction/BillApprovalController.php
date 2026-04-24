<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BillApprovalController extends Controller
{
    public function index(Request $request): Response
    {
        // Table 1: Pending Manual Bills
        // Bills with status unpaid, and has a transaction with payment_type = manual and status = pending
        $pendingBills = Bill::with(['tenant', 'room.roomCategory.kost', 'transactions' => function ($q) {
            $q->where('payment_type', 'manual')->where('status', 'pending');
        }])
            ->where('status', 'unpaid')
            ->whereHas('transactions', function ($q) {
                $q->where('payment_type', 'manual')->where('status', 'pending');
            })
            ->latest()
            ->paginate(15, ['*'], 'pending_page')
            ->through(fn (Bill $bill) => [
                'id' => $bill->id,
                'tenant_id' => $bill->tenant_id,
                'tenant_name' => $bill->tenant->name ?? '-',
                'tenant_phone' => $bill->tenant->phone_number ?? '-',
                'room_number' => $bill->room->room_number ?? '-',
                'total_price' => (float) $bill->total_price,
                'start_date' => $bill->start_date?->format('d-m-Y'),
                'due_date' => $bill->due_date?->format('d-m-Y'),
                'status' => $bill->status,
                // Pass the pending transaction ID so the frontend can call makeSuccess/makeFailed on it
                'pending_transaction_id' => $bill->transactions->first()?->id,
            ]);

        // Table 2: History Bills (Paid or Cancelled)
        $historyBills = Bill::with(['tenant', 'room.roomCategory.kost'])
            ->whereIn('status', ['paid', 'cancelled'])
            ->latest()
            ->paginate(15, ['*'], 'history_page')
            ->through(fn (Bill $bill) => [
                'id' => $bill->id,
                'tenant_id' => $bill->tenant_id,
                'tenant_name' => $bill->tenant->name ?? '-',
                'tenant_phone' => $bill->tenant->phone_number ?? '-',
                'room_number' => $bill->room->room_number ?? '-',
                'total_price' => (float) $bill->total_price,
                'start_date' => $bill->start_date?->format('d-m-Y'),
                'due_date' => $bill->due_date?->format('d-m-Y'),
                'status' => $bill->status,
            ]);

        return Inertia::render('Transactions/BillApproval/Index', [
            'pendingBills' => $pendingBills,
            'historyBills' => $historyBills,
        ]);
    }
}
