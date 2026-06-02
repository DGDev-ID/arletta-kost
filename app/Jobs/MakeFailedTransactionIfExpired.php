<?php

namespace App\Jobs;

use App\Models\Transaction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class MakeFailedTransactionIfExpired implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    protected $transactionId;

    public function __construct($transactionId)
    {
        $this->transactionId = $transactionId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Checking if transaction {$this->transactionId} has expired...");
        
        $transaction = Transaction::find($this->transactionId);
        if (!$transaction) return;
        if ($transaction->status !== 'pending') return;

        Log::info('Transaction is still pending, marking as failed', [
            'transaction_id' => $this->transactionId,
        ]);
        $transaction->update([
            'status' => 'failed',
        ]);
    }
}
