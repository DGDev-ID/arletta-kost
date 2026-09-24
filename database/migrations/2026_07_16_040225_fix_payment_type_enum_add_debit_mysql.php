<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Fix: extend payment_type to include 'debit'.
     * PostgreSQL uses CHECK constraints for ENUM-like behaviour.
     * This migration is a no-op on PostgreSQL because
     * 2026_07_15_194912 already handles it via CHECK constraint.
     */
    public function up(): void
    {
        // Already handled by 2026_07_15_194912 for PostgreSQL.
        // No additional action needed.
    }

    public function down(): void
    {
        // Already handled by 2026_07_15_194912 for PostgreSQL.
    }
};
