<?php

namespace Database\Seeders;

use App\Models\Bill;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TransactionSeeder extends Seeder
{
    public function run(): void
    {
        $paidBills = Bill::where('status', 'paid')->get();

        foreach ($paidBills as $bill) {
            $transaction = Transaction::create([
                'bill_id' => $bill->id,
                'order_id' => 'ORD-' . strtoupper(Str::random(10)),
                'payment_type' => 'midtrans',
                'midtrans_method' => 'va',
                'transaction_fee' => 2500,
                'total_price' => $bill->total_price,
                'status' => 'success',
                'snap_token' => Str::random(36),
            ]);

            TransactionDetail::create([
                'transaction_id' => $transaction->id,
                'status' => 'pending',
            ]);

            TransactionDetail::create([
                'transaction_id' => $transaction->id,
                'status' => 'success',
            ]);
        }

        $unpaidBills = Bill::where('status', 'unpaid')->get();

        foreach ($unpaidBills as $bill) {
            $transaction = Transaction::create([
                'bill_id' => $bill->id,
                'order_id' => 'ORD-' . strtoupper(Str::random(10)),
                'payment_type' => 'manual',
                'midtrans_method' => null,
                'transaction_fee' => 0,
                'total_price' => $bill->total_price,
                'status' => 'pending',
                'snap_token' => null,
            ]);

            TransactionDetail::create([
                'transaction_id' => $transaction->id,
                'status' => 'pending',
            ]);
        }
    }
}
