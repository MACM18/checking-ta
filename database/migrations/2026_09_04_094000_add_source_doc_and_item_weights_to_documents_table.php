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
            $table->foreignId('source_document_id')->nullable()->after('status')->constrained('documents')->nullOnDelete();
            $table->string('source_document_number', 60)->nullable()->after('source_document_id')->index();
        });

        Schema::table('document_items', function (Blueprint $table) {
            $table->decimal('unit_weight', 12, 3)->default(0)->after('unit_price');
            $table->decimal('total_weight', 12, 3)->default(0)->after('unit_weight');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('document_items', function (Blueprint $table) {
            $table->dropColumn(['unit_weight', 'total_weight']);
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->dropForeign(['source_document_id']);
            $table->dropColumn(['source_document_id', 'source_document_number']);
        });
    }
};
