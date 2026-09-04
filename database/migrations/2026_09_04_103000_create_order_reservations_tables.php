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
        Schema::create('order_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->nullable()->constrained('documents')->nullOnDelete();
            $table->string('reservation_number', 60)->index();
            $table->string('reserve_document_number', 60)->index();
            $table->string('company_name')->nullable();
            $table->string('country', 100)->nullable();
            $table->date('reservation_date')->nullable();
            $table->string('status', 30)->default('pending_check'); // pending_check, all_available, has_shortage, fulfilled, cancelled
            $table->decimal('total_requested_qty', 12, 3)->default(0);
            $table->decimal('total_available_qty', 12, 3)->default(0);
            $table->decimal('total_short_qty', 12, 3)->default(0);
            $table->unsignedInteger('total_items_count')->default(0);
            $table->unsignedInteger('short_items_count')->default(0);
            $table->string('warehouse_location', 100)->nullable();
            $table->timestamp('warehouse_confirmed_at')->nullable();
            $table->foreignId('warehouse_confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('warehouse_notes')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_legacy_record')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('order_reservation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_reservation_id')->constrained('order_reservations')->cascadeOnDelete();
            $table->foreignId('document_item_id')->nullable()->constrained('document_items')->nullOnDelete();
            $table->string('item_code', 100)->index();
            $table->text('description')->nullable();
            $table->decimal('requested_qty', 12, 3)->default(1);
            $table->decimal('available_qty', 12, 3)->default(0);
            $table->decimal('short_qty', 12, 3)->default(0);
            $table->string('bin_location', 100)->nullable();
            $table->string('status', 30)->default('pending'); // pending, available, short, missing
            $table->text('shortage_reason')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_reservation_items');
        Schema::dropIfExists('order_reservations');
    }
};
