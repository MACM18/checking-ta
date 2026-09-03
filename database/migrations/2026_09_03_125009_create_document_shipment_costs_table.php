<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('document_shipment_costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->string('method', 50); // dhl, air_freight, sea_freight
            $table->decimal('checked_weight', 10, 3)->nullable();
            $table->decimal('system_amount', 15, 2)->nullable();
            $table->decimal('added_amount', 15, 2)->nullable();
            $table->decimal('given_amount', 15, 2)->nullable();
            $table->timestamps();

            $table->unique(['document_id', 'method']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_shipment_costs');
    }
};
