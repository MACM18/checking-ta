<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            DB::statement('SET SESSION lock_wait_timeout = 5');
        } catch (Throwable $e) {
            // Non-MySQL or restricted privilege, proceed
        }

        $indexes = [
            'documents' => [
                ['document_type', 'created_at'],
                'created_at',
                'status',
                'company_name',
            ],
            'shipment_orders' => [
                ['status', 'created_at'],
                'created_at',
                'company_name',
                'customer_po_number',
                'tracking_awb_no',
            ],
            'order_reservations' => [
                ['status', 'created_at'],
                'created_at',
                'company_name',
            ],
            'order_reservation_items' => [
                'status',
                'short_qty',
            ],
            'document_items' => [
                'item_code',
            ],
        ];

        foreach ($indexes as $tableName => $tableIndexes) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            foreach ($tableIndexes as $columns) {
                try {
                    if (! Schema::hasIndex($tableName, $columns)) {
                        Schema::table($tableName, function (Blueprint $table) use ($columns) {
                            $table->index($columns);
                        });
                    }
                } catch (Throwable $e) {
                    // Index already exists, lock timeout, or duplicate key — safe to continue
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $indexes = [
            'documents' => [
                ['document_type', 'created_at'],
                'created_at',
                'status',
                'company_name',
            ],
            'shipment_orders' => [
                ['status', 'created_at'],
                'created_at',
                'company_name',
                'customer_po_number',
                'tracking_awb_no',
            ],
            'order_reservations' => [
                ['status', 'created_at'],
                'created_at',
                'company_name',
            ],
            'order_reservation_items' => [
                'status',
                'short_qty',
            ],
            'document_items' => [
                'item_code',
            ],
        ];

        foreach ($indexes as $tableName => $tableIndexes) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            foreach ($tableIndexes as $columns) {
                try {
                    if (Schema::hasIndex($tableName, $columns)) {
                        Schema::table($tableName, function (Blueprint $table) use ($columns) {
                            $table->dropIndex(is_array($columns) ? $columns : [$columns]);
                        });
                    }
                } catch (Throwable $e) {
                    // Safe to continue
                }
            }
        }
    }
};
