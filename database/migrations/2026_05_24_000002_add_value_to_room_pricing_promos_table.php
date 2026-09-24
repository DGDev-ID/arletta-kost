<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('room_pricing_promos', function (Blueprint $table) {
            $table->decimal('value', 15, 2)->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('room_pricing_promos', function (Blueprint $table) {
            $table->dropColumn('value');
        });
    }
};
