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
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('name', 100);
            $table->string('symbol', 10)->nullable();
            $table->decimal('exchange_rate', 14, 6)->default(1.000000)->comment('Rate relative to 1 USD');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamp('rate_updated_at')->nullable();
            $table->timestamps();

            $table->index('is_active');
            $table->index('code');
        });

        // Seed default foundational currencies: USD, AED, EUR
        $now = now();
        DB::table('currencies')->insert([
            [
                'code' => 'USD',
                'name' => 'US Dollar',
                'symbol' => '$',
                'exchange_rate' => 1.000000,
                'is_active' => true,
                'is_default' => true,
                'rate_updated_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'AED',
                'name' => 'UAE Dirham',
                'symbol' => 'AED',
                'exchange_rate' => 3.672500,
                'is_active' => true,
                'is_default' => false,
                'rate_updated_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'EUR',
                'name' => 'Euro',
                'symbol' => '€',
                'exchange_rate' => 0.869300,
                'is_active' => true,
                'is_default' => false,
                'rate_updated_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};
