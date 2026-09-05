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
        Schema::table('documents', function (Blueprint $table) {
            $table->index(['document_type', 'created_at']);
            $table->index('created_at');
            $table->index('status');
            $table->index('company_name');
        });

        Schema::table('shipment_orders', function (Blueprint $table) {
            $table->index(['status', 'created_at']);
            $table->index('created_at');
            $table->index('company_name');
            $table->index('customer_po_number');
            $table->index('tracking_awb_no');
        });

        Schema::table('order_reservations', function (Blueprint $table) {
            $table->index(['status', 'created_at']);
            $table->index('created_at');
            $table->index('company_name');
        });

        Schema::table('order_reservation_items', function (Blueprint $table) {
            $table->index('status');
            $table->index('short_qty');
        });

        Schema::table('document_items', function (Blueprint $table) {
            $table->index('item_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex(['document_type', 'created_at']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['status']);
            $table->dropIndex(['company_name']);
        });

        Schema::table('shipment_orders', function (Blueprint $table) {
            $table->dropIndex(['status', 'created_at']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['company_name']);
            $table->dropIndex(['customer_po_number']);
            $table->dropIndex(['tracking_awb_no']);
        });

        Schema::table('order_reservations', function (Blueprint $table) {
            $table->dropIndex(['status', 'created_at']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['company_name']);
        });

        Schema::table('order_reservation_items', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['short_qty']);
        });

        Schema::table('document_items', function (Blueprint $table) {
            $table->dropIndex(['item_code']);
        });
    }
};
