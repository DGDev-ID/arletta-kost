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
        DB::statement("
        DO $$
        BEGIN
            IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'bill_status') THEN
                CREATE TYPE bill_status AS ENUM (
                    'paid',
                    'unpaid',
                    'cancelled',
                    'refund_request',
                    'refund',
                    'checked_out'
                );
            END IF;
        END$$;
    ");

        // ✅ DROP DEFAULT DULU (INI YANG KURANG)
        DB::statement("
        ALTER TABLE bills 
        ALTER COLUMN status DROP DEFAULT;
    ");

        // ✅ Baru ubah tipe
        DB::statement("
        ALTER TABLE bills 
        ALTER COLUMN status TYPE bill_status 
        USING status::text::bill_status;
    ");

        // ✅ Set default lagi
        DB::statement("
        ALTER TABLE bills 
        ALTER COLUMN status SET DEFAULT 'unpaid';
    ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE bills ALTER COLUMN status DROP DEFAULT;");

        DB::statement("
        CREATE TYPE bill_status_old AS ENUM (
            'paid',
            'unpaid',
            'cancelled',
            'refund_request',
            'refund'
        );
    ");

        DB::statement("
        ALTER TABLE bills 
        ALTER COLUMN status TYPE bill_status_old 
        USING status::text::bill_status_old;
    ");

        DB::statement("DROP TYPE bill_status;");
        DB::statement("ALTER TYPE bill_status_old RENAME TO bill_status;");

        DB::statement("
        ALTER TABLE bills 
        ALTER COLUMN status SET DEFAULT 'unpaid';
    ");
    }
};
