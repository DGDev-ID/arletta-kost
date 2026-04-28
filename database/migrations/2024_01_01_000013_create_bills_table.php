
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained('rooms')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->decimal('total_price', 15, 2);
            $table->string('payment_scheme')->default('full_pay');
            $table->decimal('dp_amount', 15, 2)->nullable();
            $table->date('start_date');
            $table->date('due_date');
            $table->string('signature')->nullable();
            $table->enum('status', ['paid', 'unpaid', 'cancelled', 'refund_request', 'refund', 'checked_out'])->default('unpaid');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bills');
    }
};
