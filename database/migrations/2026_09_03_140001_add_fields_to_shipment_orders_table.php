<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipment_orders', function (Blueprint $table) {
            $table->string('document_reference')->nullable()->after('document_id');
            $table->string('proforma_invoice_no')->nullable()->after('document_reference');
            $table->string('shipment_category')->default('Standard')->after('delivery_date');
        });
    }

    public function down(): void
    {
        Schema::table('shipment_orders', function (Blueprint $table) {
            $table->dropColumn(['document_reference', 'proforma_invoice_no', 'shipment_category']);
        });
    }
};
