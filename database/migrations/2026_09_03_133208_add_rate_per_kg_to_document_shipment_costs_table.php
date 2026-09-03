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
        Schema::table('document_shipment_costs', function (Blueprint $table) {
            $table->decimal('rate_per_kg', 15, 2)->nullable()->after('checked_weight');
            $table->decimal('chargeable_weight', 10, 3)->nullable()->after('rate_per_kg');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('document_shipment_costs', function (Blueprint $table) {
            $table->dropColumn(['rate_per_kg', 'chargeable_weight']);
        });
    }
};
