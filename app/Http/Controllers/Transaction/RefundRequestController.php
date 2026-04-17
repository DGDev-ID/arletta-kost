<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\TransactionRefund;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class RefundRequestController extends Controller
{
    public function index(Request $request): Response
    {
        // Bills already refunded
        $refunds = Bill::with(['tenant', 'room.roomCategory.kost'])
            ->where('status', 'refund')
            ->latest()
            ->paginate(15, ['*'], 'refunds_page')
            ->through(fn (Bill $bill) => [
                'id' => $bill->id,
                'tenant_name' => $bill->tenant->name ?? '-',
                'tenant_phone' => $bill->tenant->phone_number ?? '-',
                'room_number' => $bill->room->room_number ?? '-',
                'total_price' => (float) $bill->total_price,
                'start_date' => $bill->start_date?->format('d-m-Y'),
                'due_date' => $bill->due_date?->format('d-m-Y'),
                'status' => $bill->status,
            ]);

        // Bills with refund requests awaiting approval
        $requests = Bill::with(['tenant', 'room.roomCategory.kost'])
            ->where('status', 'refund_request')
            ->latest()
            ->paginate(15, ['*'], 'requests_page')
            ->through(fn (Bill $bill) => [
                'id' => $bill->id,
                'tenant_name' => $bill->tenant->name ?? '-',
                'tenant_phone' => $bill->tenant->phone_number ?? '-',
                'room_number' => $bill->room->room_number ?? '-',
                'total_price' => (float) $bill->total_price,
                'start_date' => $bill->start_date?->format('d-m-Y'),
                'due_date' => $bill->due_date?->format('d-m-Y'),
                'status' => $bill->status,
            ]);

        return Inertia::render('Transactions/RefundRequest/Index', [
            'refunds' => $refunds,
            'requests' => $requests,
        ]);
    }

    public function approve(Bill $bill): RedirectResponse
    {
        if ($bill->status !== 'refund_request') {
            return back()->with('error', 'Bill tidak dalam status refund_request.');
        }

        DB::transaction(function () use ($bill) {
            // If a TransactionRefund already exists (created at request time), do not duplicate
            $existing = TransactionRefund::where('bill_id', $bill->id)->first();
            if (! $existing) {
                TransactionRefund::create([
                    'bill_id' => $bill->id,
                    'amount' => $bill->total_price,
                    'remark' => 'Approved by admin',
                ]);
            }

            $bill->update(['status' => 'refund']);
        });

        return back()->with('success', 'Refund request disetujui.');
    }

    public function reject(Bill $bill): RedirectResponse
    {
        if ($bill->status !== 'refund_request') {
            return back()->with('error', 'Bill tidak dalam status refund_request.');
        }

        // Remove any refund request record and revert bill to paid
        DB::transaction(function () use ($bill) {
            TransactionRefund::where('bill_id', $bill->id)->delete();
            $bill->update(['status' => 'paid']);
        });

        return back()->with('success', 'Refund request ditolak dan status diubah menjadi paid.');
    }
}
