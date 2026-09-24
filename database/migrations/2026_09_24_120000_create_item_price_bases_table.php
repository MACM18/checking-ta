<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_price_bases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->string('price_list', 50);
            $table->decimal('base_price_usd', 15, 4);
            $table->decimal('usd_to_aed_multiplier', 12, 6);
            $table->timestamps();

            $table->unique(['item_id', 'price_list']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_price_bases');
    }
};
