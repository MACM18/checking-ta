<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('item_code', 100)->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('item_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->string('item_code', 100)->index();
            $table->string('price_list', 50)->index();
            $table->string('currency', 10);
            $table->string('price_label', 50)->index();
            $table->decimal('price', 15, 4);
            $table->timestamps();

            $table->unique(['item_code', 'price_list', 'price_label'], 'unique_item_price_tier');
            $table->index(['item_code', 'price_label']);
            $table->index(['price_list', 'price_label']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_prices');
        Schema::dropIfExists('items');
    }
};
