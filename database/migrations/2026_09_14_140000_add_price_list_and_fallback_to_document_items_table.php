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
            if (! Schema::hasColumn('document_items', 'price_list')) {
                $table->string('price_list', 50)->nullable()->after('unit_price');
            }
            if (! Schema::hasColumn('document_items', 'is_fallback')) {
                $table->boolean('is_fallback')->default(false)->after('price_list');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('document_items', function (Blueprint $table) {
            if (Schema::hasColumn('document_items', 'is_fallback')) {
                $table->dropColumn('is_fallback');
            }
            if (Schema::hasColumn('document_items', 'price_list')) {
                $table->dropColumn('price_list');
            }
        });
    }
};
