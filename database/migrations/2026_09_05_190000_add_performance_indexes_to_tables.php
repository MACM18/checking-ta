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
            if (! Schema::hasIndex('documents', ['document_type', 'created_at'])) {
                $table->index(['document_type', 'created_at']);
            }
            if (! Schema::hasIndex('documents', ['created_at'])) {
                $table->index('created_at');
            }
            if (! Schema::hasIndex('documents', ['status'])) {
                $table->index('status');
            }
            if (! Schema::hasIndex('documents', ['company_name'])) {
                $table->index('company_name');
            }
        });

        Schema::table('shipment_orders', function (Blueprint $table) {
            if (! Schema::hasIndex('shipment_orders', ['status', 'created_at'])) {
                $table->index(['status', 'created_at']);
            }
            if (! Schema::hasIndex('shipment_orders', ['created_at'])) {
                $table->index('created_at');
            }
            if (! Schema::hasIndex('shipment_orders', ['company_name'])) {
                $table->index('company_name');
            }
            if (! Schema::hasIndex('shipment_orders', ['customer_po_number'])) {
                $table->index('customer_po_number');
            }
            if (! Schema::hasIndex('shipment_orders', ['tracking_awb_no'])) {
                $table->index('tracking_awb_no');
            }
        });

        Schema::table('order_reservations', function (Blueprint $table) {
            if (! Schema::hasIndex('order_reservations', ['status', 'created_at'])) {
                $table->index(['status', 'created_at']);
            }
            if (! Schema::hasIndex('order_reservations', ['created_at'])) {
                $table->index('created_at');
            }
            if (! Schema::hasIndex('order_reservations', ['company_name'])) {
                $table->index('company_name');
            }
        });

        Schema::table('order_reservation_items', function (Blueprint $table) {
            if (! Schema::hasIndex('order_reservation_items', ['status'])) {
                $table->index('status');
            }
            if (! Schema::hasIndex('order_reservation_items', ['short_qty'])) {
                $table->index('short_qty');
            }
        });

        Schema::table('document_items', function (Blueprint $table) {
            if (! Schema::hasIndex('document_items', ['item_code'])) {
                $table->index('item_code');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            if (Schema::hasIndex('documents', ['document_type', 'created_at'])) {
                $table->dropIndex(['document_type', 'created_at']);
            }
            if (Schema::hasIndex('documents', ['created_at'])) {
                $table->dropIndex(['created_at']);
            }
            if (Schema::hasIndex('documents', ['status'])) {
                $table->dropIndex(['status']);
            }
            if (Schema::hasIndex('documents', ['company_name'])) {
                $table->dropIndex(['company_name']);
            }
        });

        Schema::table('shipment_orders', function (Blueprint $table) {
            if (Schema::hasIndex('shipment_orders', ['status', 'created_at'])) {
                $table->dropIndex(['status', 'created_at']);
            }
            if (Schema::hasIndex('shipment_orders', ['created_at'])) {
                $table->dropIndex(['created_at']);
            }
            if (Schema::hasIndex('shipment_orders', ['company_name'])) {
                $table->dropIndex(['company_name']);
            }
            if (Schema::hasIndex('shipment_orders', ['customer_po_number'])) {
                $table->dropIndex(['customer_po_number']);
            }
            if (Schema::hasIndex('shipment_orders', ['tracking_awb_no'])) {
                $table->dropIndex(['tracking_awb_no']);
            }
        });

        Schema::table('order_reservations', function (Blueprint $table) {
            if (Schema::hasIndex('order_reservations', ['status', 'created_at'])) {
                $table->dropIndex(['status', 'created_at']);
            }
            if (Schema::hasIndex('order_reservations', ['created_at'])) {
                $table->dropIndex(['created_at']);
            }
            if (Schema::hasIndex('order_reservations', ['company_name'])) {
                $table->dropIndex(['company_name']);
            }
        });

        Schema::table('order_reservation_items', function (Blueprint $table) {
            if (Schema::hasIndex('order_reservation_items', ['status'])) {
                $table->dropIndex(['status']);
            }
            if (Schema::hasIndex('order_reservation_items', ['short_qty'])) {
                $table->dropIndex(['short_qty']);
            }
        });

        Schema::table('document_items', function (Blueprint $table) {
            if (Schema::hasIndex('document_items', ['item_code'])) {
                $table->dropIndex(['item_code']);
            }
        });
    }
};
