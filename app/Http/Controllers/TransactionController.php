<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\TransactionRefund;
use Barryvdh\DomPDF\Facade\Pdf;
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
            ->withQueryString()
            ->through(fn (Transaction $trx) => [
                'id' => $trx->id,
                'order_id' => $trx->order_id,
                'tenant_name' => $trx->bill->tenant->name ?? '-',
                'room_number' => $trx->bill->room->room_number ?? '-',
                'payment_type' => $trx->payment_type,
                'midtrans_method' => $trx->midtrans_method,
                'transaction_type' => $trx->transaction_type,
                'total_price' => (float) $trx->total_price,
                'status' => $trx->status,
                'created_at' => $trx->created_at->format('d-m-Y H:i'),
                'checkin_date' => $trx->bill?->start_date?->format('d-m-Y') ?? '-',
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
        $transaction->load(['bill.tenant', 'bill.room.roomCategory.kost', 'details']);

        $data = [
            'id' => $transaction->id,
            'order_id' => $transaction->order_id,
            'payment_type' => $transaction->payment_type,
            'transaction_type' => $transaction->transaction_type,
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
            'id'             => $transaction->bill->id,
            'total_price'    => (float) $transaction->bill->total_price,
            'start_date'     => $transaction->bill->start_date->format('d-m-Y'),
            'due_date'       => $transaction->bill->due_date->format('d-m-Y'),
            'status'         => $transaction->bill->status,
            'payment_scheme' => $transaction->bill->payment_scheme,
            'dp_amount'      => (float) $transaction->bill->dp_amount,
            'room_number'    => $transaction->bill->room->room_number ?? '-',
            'category_name'  => $transaction->bill->room->roomCategory->name ?? '-',
            'kost_name'      => $transaction->bill->room->roomCategory->kost->name ?? 'Arletta Kost',
            'kost_address'   => $transaction->bill->room->roomCategory->kost->address ?? '-',
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

    public function invoice(Transaction $transaction)
    {
        $transaction->load(['bill.tenant', 'bill.room.roomCategory.kost', 'details']);


        $formatRupiah = fn ($v) => 'Rp ' . number_format((float) $v, 0, ',', '.');


        $data = [
            'transaction' => [
                'order_id'         => $transaction->order_id,
                'payment_type'     => $transaction->payment_type,
                'transaction_type' => $transaction->transaction_type,
                'midtrans_method'  => $transaction->midtrans_method,
                'transaction_fee'  => (float) $transaction->transaction_fee,
                'fee_formatted'    => $formatRupiah($transaction->transaction_fee),
                'total_price'      => (float) $transaction->total_price,
                'total_price_formatted' => $formatRupiah($transaction->total_price),
                'status'           => $transaction->status,
                'created_at'       => $transaction->created_at->format('d M Y, H:i'),
            ],
            'tenant' => [
                'name'         => $transaction->bill->tenant->name ?? '-',
                'phone_number' => $transaction->bill->tenant->phone_number ?? '-',
                'email'        => $transaction->bill->tenant->email ?? '',
            ],
            'bill' => [
                'payment_scheme'        => $transaction->bill->payment_scheme,
                'total_price'           => (float) $transaction->bill->total_price,
                'total_price_formatted' => $formatRupiah($transaction->bill->total_price),
                'dp_amount'             => (float) $transaction->bill->dp_amount,
                'dp_amount_formatted'   => $formatRupiah($transaction->bill->dp_amount),
                'start_date'            => $transaction->bill->start_date->format('d M Y'),
                'due_date'              => $transaction->bill->due_date->format('d M Y'),
            ],
            'room' => [
                'room_number'   => $transaction->bill->room->room_number ?? '-',
                'category_name' => $transaction->bill->room->roomCategory->name ?? '-',
            ],
            'kost' => [
                'name'    => $transaction->bill->room->roomCategory->kost->name ?? 'Arletta Kost',
                'address' => $transaction->bill->room->roomCategory->kost->address ?? '-',
            ],
        ];

        $pdf = Pdf::loadView('pdf.invoice', $data)
            ->setPaper('A4', 'portrait')
            ->setOptions([
                'dpi'                     => 96,
                'defaultFont'             => 'DejaVu Sans',
                'isRemoteEnabled'         => false,
                'isHtml5ParserEnabled'    => true,
                'isFontSubsettingEnabled' => true,
                'enable_php'              => false,
            ]);

        return $pdf->download('Invoice-' . $transaction->order_id . '.pdf');
    }

    public function export(Request $request)
    {
        $search      = $request->input('search', '');
        $status      = $request->input('status', '');
        $paymentType = $request->input('payment_type', '');

        $transactions = Transaction::with(['bill.tenant', 'bill.room'])
            ->when($search, fn ($q) => $q->where(function ($q2) use ($search) {
                $q2->where('order_id', 'like', "%{$search}%")
                    ->orWhereHas('bill.tenant', fn ($q3) => $q3->where('name', 'like', "%{$search}%"));
            }))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($paymentType, fn ($q) => $q->where('payment_type', $paymentType))
            ->latest()
            ->get();

        $filename = 'Transaksi-' . now()->format('Ymd-His') . '.xls';

        $headers = [
            'Content-Type'        => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $callback = function () use ($transactions) {
            $html = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
            $html .= '<head><meta charset="utf-8">';
            $html .= '<style>';
            $html .= 'table { border-collapse: collapse; width: 100%; font-family: sans-serif; }';
            $html .= 'th { background-color: #2563eb; color: #ffffff; border: 1px solid #e5e7eb; padding: 10px; text-align: center; font-weight: bold; }';
            $html .= 'td { border: 1px solid #e5e7eb; padding: 8px; vertical-align: top; }';
            $html .= 'tr:nth-child(even) td { background-color: #f8fafc; }';
            $html .= 'h2 { font-family: sans-serif; color: #1e293b; margin-bottom: 20px; }';
            $html .= '</style>';
            $html .= '</head><body>';
            
            $html .= '<h2>Laporan Transaksi Arletta Kost</h2>';
            $html .= '<table>';
            $html .= '<thead><tr>';
            $html .= '<th>No</th>';
            $html .= '<th>Order ID</th>';
            $html .= '<th>Tenant</th>';
            $html .= '<th>Room</th>';
            $html .= '<th>Metode Pembayaran</th>';
            $html .= '<th>Tipe Transaksi</th>';
            $html .= '<th>Total (Rp)</th>';
            $html .= '<th>Status</th>';
            $html .= '<th>Tanggal Check-in</th>';
            $html .= '<th>Tanggal Transaksi</th>';
            $html .= '</tr></thead><tbody>';

            $no = 1;
            foreach ($transactions as $trx) {
                $paymentLabel = match ($trx->payment_type) {
                    'manual'   => 'Manual',
                    'midtrans' => $trx->midtrans_method ? strtoupper($trx->midtrans_method) : 'Midtrans',
                    'debit'    => 'Debit',
                    default    => $trx->payment_type,
                };

                $txType = match ($trx->transaction_type) {
                    'full_payment'     => 'Full Payment',
                    'down_payment'     => 'Down Payment',
                    'finished_payment' => 'Pelunasan',
                    default            => $trx->transaction_type ?? 'Full Payment',
                };

                $total = number_format((int) $trx->total_price, 0, ',', '.');
                $status = ucfirst($trx->status);
                $checkin = $trx->bill?->start_date?->format('d-m-Y') ?? '-';
                $trxDate = $trx->created_at->format('d-m-Y H:i');
                $tenant = $trx->bill?->tenant?->name ?? '-';
                $room = $trx->bill?->room?->room_number ?? '-';

                $html .= '<tr>';
                $html .= "<td style='text-align:center;'>{$no}</td>";
                $html .= "<td>{$trx->order_id}</td>";
                $html .= "<td>{$tenant}</td>";
                $html .= "<td style='text-align:center;'>{$room}</td>";
                $html .= "<td style='text-align:center;'>{$paymentLabel}</td>";
                $html .= "<td style='text-align:center;'>{$txType}</td>";
                $html .= "<td style='text-align:right;'>{$total}</td>";
                $html .= "<td style='text-align:center;'>{$status}</td>";
                $html .= "<td style='text-align:center;'>{$checkin}</td>";
                $html .= "<td style='text-align:center;'>{$trxDate}</td>";
                $html .= '</tr>';
                $no++;
            }

            $html .= '</tbody></table></body></html>';

            echo $html;
        };

        return response()->stream($callback, 200, $headers);
    }

    public function refund(Request $request, Transaction $transaction): RedirectResponse
    {
        $validated = $request->validate([
            'remark' => 'nullable|string|max:1000',
        ]);

        $amount = $transaction->total_price * 0.8;

        // Create a refund request record and mark the bill as 'refund_request'
        // Avoid duplicate requests: update existing request or create new
        $existing = TransactionRefund::where('bill_id', $transaction->bill_id)->first();
        if ($existing) {
            $existing->update([
                'amount' => $amount,
                'remark' => $validated['remark'] ?? null,
            ]);
        } else {
            TransactionRefund::create([
                'bill_id' => $transaction->bill_id,
                'amount' => $amount,
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
