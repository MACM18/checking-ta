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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('document_number', 60)->index();
            $table->string('document_type', 50)->index();
            $table->string('company_name');
            $table->string('country', 100);
            $table->text('address')->nullable();
            $table->text('contact_details')->nullable();
            $table->date('document_date');
            $table->string('currency', 10)->default('USD'); // USD or AED
            $table->decimal('total_net_weight', 12, 3)->nullable();
            $table->decimal('total_gross_weight', 12, 3)->nullable();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('final_total', 15, 2)->default(0);
            $table->unsignedInteger('current_version')->default(1);
            $table->string('status', 30)->default('draft');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
