<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('name', 100);
            $table->string('code', 50)->unique();
            $table->string('logo_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->uuid('payment_method_id')->nullable()->after('order_id');
            $table->foreign('payment_method_id')->references('id')->on('payment_methods')->onDelete('set null');
        });

        // Seed default payment methods
        DB::table('payment_methods')->insert([
            [
                'id' => DB::raw('gen_random_uuid()'),
                'name' => 'Wave',
                'code' => 'wave',
                'logo_url' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => DB::raw('gen_random_uuid()'),
                'name' => 'Orange Money',
                'code' => 'orange_money',
                'logo_url' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => DB::raw('gen_random_uuid()'),
                'name' => 'MTN MoMo',
                'code' => 'mtn_momo',
                'logo_url' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => DB::raw('gen_random_uuid()'),
                'name' => 'Moov Money',
                'code' => 'moov_money',
                'logo_url' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => DB::raw('gen_random_uuid()'),
                'name' => 'Carte Bancaire',
                'code' => 'card',
                'logo_url' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['payment_method_id']);
            $table->dropColumn('payment_method_id');
        });

        Schema::dropIfExists('payment_methods');
    }
};
