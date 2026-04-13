<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('room_pricing_promos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_pricing_id')->constrained('room_pricings')->cascadeOnDelete();
            $table->enum('type', ['discount_percent', 'discount_amount', 'bonus_days', 'cashback']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_pricing_promos');
    }
};
