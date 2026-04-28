<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bill_id')->constrained('bills')->cascadeOnDelete();
            $table->string('order_id')->unique();
            $table->enum('transaction_type', ['full_payment', 'down_payment', 'finished_payment'])->default('full_payment');
            $table->enum('payment_type', ['manual', 'midtrans']);
            $table->enum('midtrans_method', ['va', 'qris'])->nullable();
            $table->decimal('transaction_fee', 15, 2)->default(0);
            $table->decimal('total_price', 15, 2);
            $table->enum('status', ['pending', 'success', 'failed'])->default('pending');
            $table->string('snap_token')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
