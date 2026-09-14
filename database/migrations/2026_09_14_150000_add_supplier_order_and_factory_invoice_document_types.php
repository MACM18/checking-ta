<?php

use App\Models\DocumentType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('document_types')) {
            DocumentType::firstOrCreate(
                ['code' => 'supplier_order'],
                [
                    'name' => 'Supplier Order (B)',
                    'prefix' => 'B',
                    'suffix' => null,
                    'description' => 'Supplier purchase order / order sheet with item numbers and quantities',
                    'badge_color' => 'purple',
                    'is_active' => true,
                    'is_system' => true,
                    'sort_order' => 9,
                ]
            );

            DocumentType::firstOrCreate(
                ['code' => 'factory_invoice'],
                [
                    'name' => 'Factory Invoice',
                    'prefix' => 'F,INV-F,FAC',
                    'suffix' => null,
                    'description' => 'Factory shipment invoice / receipt with item numbers and quantities received',
                    'badge_color' => 'teal',
                    'is_active' => true,
                    'is_system' => true,
                    'sort_order' => 10,
                ]
            );

            Cache::forget('active_document_types_map');
            Cache::forget('active_custom_document_types');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('document_types')) {
            DocumentType::whereIn('code', ['supplier_order', 'factory_invoice'])->delete();
            Cache::forget('active_document_types_map');
            Cache::forget('active_custom_document_types');
        }
    }
};
