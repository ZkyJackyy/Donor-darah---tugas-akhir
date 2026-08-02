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
        Schema::table('donor_candidates', function (Blueprint $table) {
            $table->dropColumn('qr_token');
        });

        // 'code' (kode_verifikasi path) was already being written by
        // AdminBloodRequestController::verifyByCode() but was never a valid
        // enum value here, which would fail on MySQL — widen it now that
        // 'qr' is no longer produced.
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE donor_candidates MODIFY verification_method ENUM('manual', 'code') NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE donor_candidates MODIFY verification_method ENUM('qr', 'manual') NULL");
        }

        Schema::table('donor_candidates', function (Blueprint $table) {
            $table->text('qr_token')->nullable()->after('verification_method');
        });
    }
};
