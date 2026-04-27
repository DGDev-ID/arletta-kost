<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE bills MODIFY COLUMN status ENUM('paid', 'unpaid', 'cancelled', 'refund_request', 'refund', 'checked_out') DEFAULT 'unpaid'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE bills MODIFY COLUMN status ENUM('paid', 'unpaid', 'cancelled', 'refund_request', 'refund') DEFAULT 'unpaid'");
    }
};
