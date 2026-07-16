<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Fix: extend payment_type ENUM to include 'debit' using MySQL syntax.
     * The previous migration used PostgreSQL CHECK constraint syntax
     * which had no effect on MySQL.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE transactions MODIFY COLUMN payment_type ENUM('manual', 'midtrans', 'debit') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE transactions MODIFY COLUMN payment_type ENUM('manual', 'midtrans') NOT NULL");
    }
};
