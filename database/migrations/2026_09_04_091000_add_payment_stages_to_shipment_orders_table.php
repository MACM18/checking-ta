<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipment_orders', function (Blueprint $table) {
            $table->timestamp('payment_submitted_at')->nullable()->after('payment_amount');
            $table->string('payment_submission_ref')->nullable()->after('payment_submitted_at');
            $table->text('payment_submission_notes')->nullable()->after('payment_submission_ref');
            $table->timestamp('payment_confirmed_at')->nullable()->after('payment_submission_notes');
            $table->foreignId('payment_confirmed_by')->nullable()->constrained('users')->nullOnDelete()->after('payment_confirmed_at');
        });
    }

    public function down(): void
    {
        Schema::table('shipment_orders', function (Blueprint $table) {
            $table->dropForeign(['payment_confirmed_by']);
            $table->dropColumn([
                'payment_submitted_at',
                'payment_submission_ref',
                'payment_submission_notes',
                'payment_confirmed_at',
                'payment_confirmed_by',
            ]);
        });
    }
};
