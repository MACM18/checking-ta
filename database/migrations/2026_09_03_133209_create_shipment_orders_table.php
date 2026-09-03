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
        Schema::create('shipment_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 50)->unique();
            $table->foreignId('document_id')->nullable()->constrained('documents')->nullOnDelete(); // Source PI
            $table->string('company_name');
            $table->string('country', 100);
            $table->string('customer_po_number', 100)->nullable();
            $table->date('customer_po_date')->nullable();
            $table->text('customer_po_notes')->nullable();
            $table->string('payment_status', 30)->default('pending'); // pending, advance_received, fully_paid
            $table->string('payment_reference', 100)->nullable();
            $table->decimal('payment_amount', 15, 2)->nullable();
            $table->string('currency', 10)->default('USD');
            $table->string('linked_invoice_no', 60)->nullable();
            $table->string('linked_packing_list_no', 60)->nullable();
            $table->boolean('draft_documents_sent')->default(false);
            $table->text('draft_documents_notes')->nullable();
            $table->string('carrier_method', 50)->nullable();
            $table->string('tracking_awb_no', 100)->nullable();
            $table->date('dispatch_date')->nullable();
            $table->date('delivery_date')->nullable();
            $table->unsignedInteger('current_stage')->default(1);
            $table->string('status', 30)->default('active'); // active, completed, cancelled
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipment_orders');
    }
};
