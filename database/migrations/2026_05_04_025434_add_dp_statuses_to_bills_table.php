<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // PostgreSQL enforces enum as a check constraint; we must drop and recreate it
        // to add the new 'down_payment' and 'finished_payment' statuses.
        DB::statement("ALTER TABLE bills DROP CONSTRAINT IF EXISTS bills_status_check");
        DB::statement("ALTER TABLE bills ADD CONSTRAINT bills_status_check CHECK (status IN ('paid', 'unpaid', 'cancelled', 'refund_request', 'refund', 'checked_out', 'down_payment', 'finished_payment'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE bills DROP CONSTRAINT IF EXISTS bills_status_check");
        DB::statement("ALTER TABLE bills ADD CONSTRAINT bills_status_check CHECK (status IN ('paid', 'unpaid', 'cancelled', 'refund_request', 'refund', 'checked_out'))");
    }
};
