<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add 'debit' to payment_type options.
     * PostgreSQL uses CHECK constraints for ENUM-like behaviour.
     */
    public function up(): void
    {
        // Drop existing constraint (name may vary; handle both conventions)
        DB::statement("ALTER TABLE transactions DROP CONSTRAINT IF EXISTS transactions_payment_type_check");

        // Recreate with debit included
        DB::statement("ALTER TABLE transactions ADD CONSTRAINT transactions_payment_type_check
            CHECK (payment_type IN ('manual', 'midtrans', 'debit'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE transactions DROP CONSTRAINT IF EXISTS transactions_payment_type_check");

        DB::statement("ALTER TABLE transactions ADD CONSTRAINT transactions_payment_type_check
            CHECK (payment_type IN ('manual', 'midtrans'))");
    }
};
