<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('room_categories', function (Blueprint $table) {
            // Gender indicates intended occupant: male, female, or mixed
            $table->enum('gender', ['male', 'female', 'mixed'])->nullable()->default('mixed')->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('room_categories', function (Blueprint $table) {
            $table->dropColumn('gender');
        });
    }
};
