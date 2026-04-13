<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('room_pricings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_category_id')->constrained('room_categories')->cascadeOnDelete();
            $table->integer('duration_days');
            $table->decimal('price', 15, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_pricings');
    }
};
