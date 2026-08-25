<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::unprepared("ALTER TYPE payment_provider ADD VALUE IF NOT EXISTS 'cinetpay';");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Postgres does not support removing a value from an ENUM type.
    }
};
