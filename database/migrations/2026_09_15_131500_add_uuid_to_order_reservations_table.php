<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('order_reservations', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->after('id');
        });

        // Generate unique UUIDs for all existing order reservations
        DB::table('order_reservations')->whereNull('uuid')->orderBy('id')->each(function ($res) {
            DB::table('order_reservations')->where('id', $res->id)->update([
                'uuid' => (string) Str::uuid(),
            ]);
        });

        Schema::table('order_reservations', function (Blueprint $table) {
            $table->unique('uuid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_reservations', function (Blueprint $table) {
            $table->dropUnique(['uuid']);
            $table->dropColumn('uuid');
        });
    }
};
