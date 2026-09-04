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
        Schema::table('shipment_orders', function (Blueprint $table) {
            $table->foreignId('invoice_document_id')->nullable()->after('document_reference')->constrained('documents')->nullOnDelete();
            $table->foreignId('packing_list_document_id')->nullable()->after('invoice_document_id')->constrained('documents')->nullOnDelete();
            $table->index('linked_invoice_no');
            $table->index('linked_packing_list_no');
            $table->index('proforma_invoice_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipment_orders', function (Blueprint $table) {
            $table->dropForeign(['invoice_document_id']);
            $table->dropForeign(['packing_list_document_id']);
            $table->dropIndex(['linked_invoice_no']);
            $table->dropIndex(['linked_packing_list_no']);
            $table->dropIndex(['proforma_invoice_no']);
            $table->dropColumn(['invoice_document_id', 'packing_list_document_id']);
        });
    }
};
