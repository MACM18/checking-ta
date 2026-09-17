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
        Schema::table('document_items', function (Blueprint $table) {
            if (! Schema::hasColumn('document_items', 'order_sheet_reference')) {
                $table->string('order_sheet_reference', 100)->nullable()->after('item_code')->index();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('document_items', function (Blueprint $table) {
            if (Schema::hasColumn('document_items', 'order_sheet_reference')) {
                $table->dropIndex(['order_sheet_reference']);
                $table->dropColumn('order_sheet_reference');
            }
        });
    }
};
