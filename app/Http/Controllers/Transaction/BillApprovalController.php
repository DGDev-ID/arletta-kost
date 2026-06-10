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
                'pending_transaction_id' => $bill->transactions->first()?->id,
                'payment_type'   => $bill->transactions->first()?->payment_type,
                'midtrans_method'=> $bill->transactions->first()?->midtrans_method,
            ]);

        $historyBills = Bill::with(['tenant', 'room.roomCategory.kost', 'transactions' => function ($q) {
            $q->latest()->limit(1);
        }])
            ->whereIn('status', ['paid', 'cancelled', 'checked_out'])
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
                'payment_type'   => $bill->transactions->first()?->payment_type,
                'midtrans_method'=> $bill->transactions->first()?->midtrans_method,
            ]);

        return Inertia::render('Transactions/BillApproval/Index', [
            'pendingBills' => $pendingBills,
            'historyBills' => $historyBills,
        ]);
    }

    public function show(Bill $bill): Response
    {
        $bill->load(['tenant', 'room.roomCategory.kost', 'transactions.details']);

        $data = [
            'id' => $bill->id,
            'total_price' => (float) $bill->total_price,
            'start_date' => $bill->start_date?->format('d-m-Y'),
            'due_date' => $bill->due_date?->format('d-m-Y'),
            'status' => $bill->status,
        ];

        $tenant = null;
        if ($bill->tenant) {
            $tenant = [
                'id' => $bill->tenant->id,
                'name' => $bill->tenant->name,
                'email' => $bill->tenant->email,
                'phone_number' => $bill->tenant->phone_number,
                'nik' => $bill->tenant->nik ?? '-',
                'address' => $bill->tenant->address ?? '-',
            ];
        }

        $room = null;
        if ($bill->room) {
            $room = [
                'id' => $bill->room->id,
                'room_number' => $bill->room->room_number,
                'kost_name' => $bill->room->roomCategory->kost->name ?? '-',
                'category_name' => $bill->room->roomCategory->name ?? '-',
            ];
        }

        $transactions = $bill->transactions->map(function ($t) {
            return [
                'id' => $t->id,
                'order_id' => $t->order_id,
                'payment_type' => $t->payment_type,
                'midtrans_method'=> $t->midtrans_method,
                'total_price' => (float) $t->total_price,
                'status' => $t->status,
                'created_at' => $t->created_at->format('d-m-Y H:i'),
                'details' => $t->details->map(fn($d) => [
                    'id' => $d->id,
                    'status' => $d->status,
                    'created_at' => $d->created_at->format('d-m-Y H:i'),
                ])
            ];
        });

        return Inertia::render('Transactions/BillApproval/Show', [
            'bill' => $data,
            'tenant' => $tenant,
            'room' => $room,
            'transactions' => $transactions,
        ]);
    }
}
