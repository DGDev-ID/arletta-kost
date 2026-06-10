<?php

namespace App\Console\Commands;

use App\Models\Bill;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AutoCheckoutExpiredBills extends Command
{
    protected $signature   = 'bills:auto-checkout';
    protected $description = 'Auto checkout bills yang sudah melewati due_date dan set room menjadi available';

    public function handle(): int
    {
        // Cari bill yang statusnya aktif tapi due_date sudah lewat
        $activeStatuses = ['paid', 'unpaid', 'down_payment', 'finished_payment'];

        $expiredBills = Bill::with(['room', 'tenant'])
            ->whereIn('status', $activeStatuses)
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', now()->toDateString())
            ->get();

        if ($expiredBills->isEmpty()) {
            $this->info('Tidak ada bill yang expired.');
            return self::SUCCESS;
        }

        $count = 0;

        foreach ($expiredBills as $bill) {
            DB::transaction(function () use ($bill, &$count) {
                // Update bill status ke checked_out
                $bill->update(['status' => 'checked_out']);

                // Set room kembali ke available
                if ($bill->room) {
                    $bill->room->update(['status' => 'available']);
                }

                // Detach room dari tenant jika masih terhubung
                if ($bill->tenant && $bill->room) {
                    $bill->tenant->rooms()->detach($bill->room->id);
                }

                $count++;

                $this->line(sprintf(
                    '  [OK] Bill #%d (Tenant: %s, Room: %s, Due: %s) -> checked_out',
                    $bill->id,
                    $bill->tenant?->name ?? '-',
                    $bill->room?->room_number ?? '-',
                    $bill->due_date?->toDateString(),
                ));
            });
        }

        $this->info("Auto-checkout selesai. Total: {$count} bill diproses.");

        return self::SUCCESS;
    }
}
