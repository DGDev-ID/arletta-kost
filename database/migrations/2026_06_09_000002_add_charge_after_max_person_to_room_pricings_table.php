<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('room_pricings', function (Blueprint $table) {
            $table->decimal('charge_after_max_person', 15, 2)->default(100000)->after('price');
        });
    }

    public function down(): void
    {
        Schema::table('room_pricings', function (Blueprint $table) {
            $table->dropColumn('charge_after_max_person');
        });
    }
};
