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
        Schema::create('document_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->string('package_type', 50)->default('Carton');
            $table->string('dimension_type', 20)->default('standard'); // 'standard' (L x W x H) or 'diameter' (Dia x H)
            $table->decimal('length_cm', 10, 2)->nullable();
            $table->decimal('width_cm', 10, 2)->nullable();
            $table->decimal('height_cm', 10, 2)->nullable();
            $table->decimal('diameter_cm', 10, 2)->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('gross_weight_per_pkg_kg', 10, 3)->nullable();
            $table->decimal('total_gross_weight_kg', 10, 3)->nullable();
            $table->decimal('volumetric_weight_kg', 10, 3)->nullable();
            $table->decimal('cbm', 10, 4)->nullable();
            $table->unsignedInteger('sort_order')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_packages');
    }
};
