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
        Schema::table('order_reservation_items', function (Blueprint $table) {
            if (! Schema::hasColumn('order_reservation_items', 'supplier_invoice_no')) {
                $table->string('supplier_invoice_no', 100)->nullable()->after('short_qty');
            }
            if (! Schema::hasColumn('order_reservation_items', 'remarks')) {
                $table->text('remarks')->nullable()->after('shortage_reason');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_reservation_items', function (Blueprint $table) {
            if (Schema::hasColumn('order_reservation_items', 'supplier_invoice_no')) {
                $table->dropColumn('supplier_invoice_no');
            }
            if (Schema::hasColumn('order_reservation_items', 'remarks')) {
                $table->dropColumn('remarks');
            }
        });
    }
};
