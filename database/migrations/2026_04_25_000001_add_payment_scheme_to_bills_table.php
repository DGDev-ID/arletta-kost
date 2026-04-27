<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bills', function (Blueprint $table) {
            $table->string('payment_scheme')->default('full_pay')->after('total_price');
            $table->decimal('dp_amount', 15, 2)->nullable()->after('payment_scheme');
        });

        // Modify ENUM column for status
        // DB::statement("ALTER TABLE bills MODIFY COLUMN status ENUM('paid', 'unpaid', 'cancelled', 'refund_request', 'refund', 'checked_out', 'down_payment', 'finished_payment') DEFAULT 'unpaid'");
        // Using string to avoid ENUM issues if we add more in the future or Doctrine DBAL issues.
        // Wait, standard laravel way to update ENUM might require Doctrine\DBAL. 
        // Let's use DB::statement for mysql
        DB::statement("ALTER TABLE bills MODIFY COLUMN status ENUM('paid', 'unpaid', 'cancelled', 'refund_request', 'refund', 'checked_out', 'down_payment', 'finished_payment') DEFAULT 'unpaid'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bills', function (Blueprint $table) {
            $table->dropColumn(['payment_scheme', 'dp_amount']);
        });

        DB::statement("ALTER TABLE bills MODIFY COLUMN status ENUM('paid', 'unpaid', 'cancelled', 'refund_request', 'refund', 'checked_out') DEFAULT 'unpaid'");
    }
};
