<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use App\Services\XenditService;
use Illuminate\Support\Facades\Log;

class TransactionService
{
    public static function makeTransaction(
        Bill $bill,
        string $paymentType
    ): Transaction {
        $transaction = $bill->transactions()->create([
            'order_id'         => 'KOST-' . $bill->id . '-' . now()->timestamp,
            'payment_type'     => $paymentType,
            'transaction_type' => $bill->payment_scheme === 'dp'
                ? 'down_payment'
                : 'full_payment',
            'total_price'      => $bill->payment_scheme === 'dp'
                ? $bill->dp_amount
                : $bill->total_price,
            'status'           => 'pending'
        ]);

        $transaction->details()->create([
            'status' => $transaction->status,
        ]);

        if ($transaction->payment_type === 'qris') {
            $transaction->update([
                'midtrans_method' => 'qris',
            ]);

            $qr = XenditService::createQr($transaction);
            Log::info('QR Code created for transaction', [
                'transaction_id' => $transaction->id,
                'qr_data' => $qr
            ]);
        }

        return $transaction;
    }

    public static function updateStatus(Bill $bill, string $status)
    {
        $bill->update(['status' => $status]);
    }

    public static function makeSuccess(Transaction $transaction)
    {
        DB::transaction(function () use ($transaction) {
            $transaction->update(['status' => 'success']);
            $transaction->details()->update(['status' => 'success']);
            $transaction->details()->create(['status' => 'success']);

            $billStatus = 'paid';
            if ($transaction->transaction_type === 'down_payment') {
                $billStatus = 'down_payment';

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
    }

    public static function makeFailed(Transaction $transaction)
    {
        DB::transaction(function () use ($transaction) {
            $transaction->update(['status' => 'failed']);
            $transaction->details()->update(['status' => 'failed']);
            $transaction->details()->create(['status' => 'failed']);
            $transaction->bill->update(['status' => 'cancelled']);
        });
    }
}
