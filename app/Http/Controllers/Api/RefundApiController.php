<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\TransactionRefund;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RefundApiController extends Controller
{
    /**
     * Cek validitas transaksi sebelum refund diajukan.
     */
    public function checkTransaction(Request $request)
    {
        $request->validate([
            'transaction_order_id' => 'required|string',
            'nik' => 'required|string',
        ]);

        // Cari transaksi berdasarkan order_id, sekalian load relasi bill dan tenant
        $transaction = Transaction::with(['bill.tenant', 'bill.room.roomCategory.kost'])->where('order_id', $request->transaction_order_id)->first();

        // 1. Jika transaksi tidak ditemukan
        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaksi tidak ditemukan.',
            ], 404);
        }

        // 2. Cocokkan NIK dari request dengan NIK di data tenant
        $tenantNik = $transaction->bill?->tenant?->nik;

        if (!$tenantNik || $tenantNik !== $request->nik) {
            return response()->json([
                'success' => false,
                'message' => 'NIK tidak cocok dengan data penyewa transaksi ini.',
            ], 403);
        }

        // Cek status bill, jika sudah refund_request atau refund, mungkin tidak valid lagi
        if (in_array($transaction->bill?->status, ['refund_request', 'refund'])) {
            return response()->json([
                'success' => false,
                'message' => 'Transaksi ini sudah diajukan refund sebelumnya.',
            ], 400);
        }

        // 3. Jika cocok, return success dengan sedikit data transaksi
        return response()->json([
            'success' => true,
            'message' => 'Transaksi valid untuk di-refund.',
            'data' => [
                'order_id' => $transaction->order_id,
                'total_price' => $transaction->total_price,
                'tenant_name' => $transaction->bill->tenant->name,
                'room_number' => $transaction->bill->room->room_number ?? '-',
                'kost_name' => $transaction->bill->room->roomCategory->kost->name ?? '-',
                'bill_status' => $transaction->bill->status,
                'refund_amount_estimation' => $transaction->total_price * 0.8,
            ]
        ], 200);
    }

    /**
     * Mengeksekusi pengajuan refund.
     */
    public function submitRefund(Request $request)
    {
        $request->validate([
            'transaction_order_id' => 'required|string',
            'nik' => 'required|string',
        ]);

        // Cari transaksi berdasarkan order_id
        $transaction = Transaction::with(['bill.tenant'])->where('order_id', $request->transaction_order_id)->first();

        // 1. Jika transaksi tidak ditemukan
        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaksi tidak ditemukan.',
            ], 404);
        }

        // 2. Cocokkan NIK
        $tenantNik = $transaction->bill?->tenant?->nik;

        if (!$tenantNik || $tenantNik !== $request->nik) {
            return response()->json([
                'success' => false,
                'message' => 'NIK tidak cocok dengan data penyewa transaksi ini.',
            ], 403);
        }

        $bill = $transaction->bill;

        if (!$bill) {
            return response()->json([
                'success' => false,
                'message' => 'Data tagihan (bill) tidak ditemukan untuk transaksi ini.',
            ], 404);
        }

        if (in_array($bill->status, ['refund_request', 'refund'])) {
            return response()->json([
                'success' => false,
                'message' => 'Transaksi ini sudah diajukan refund sebelumnya.',
            ], 400);
        }

        // 3. Update status bill dan buat data refund request
        DB::transaction(function () use ($transaction, $bill) {
            // Update status bill menjadi refund_request
            $bill->update(['status' => 'refund_request']);

            // Simpan nominal refund (80% dari total_price) ke tabel transaction_refunds
            $amount = $transaction->total_price * 0.8;

            $existing = TransactionRefund::where('bill_id', $bill->id)->first();
            if ($existing) {
                $existing->update([
                    'amount' => $amount,
                    'remark' => 'Requested by tenant via landing page',
                ]);
            } else {
                TransactionRefund::create([
                    'bill_id' => $bill->id,
                    'amount' => $amount,
                    'remark' => 'Requested by tenant via landing page',
                ]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan refund berhasil diproses.',
        ], 200);
    }
}
