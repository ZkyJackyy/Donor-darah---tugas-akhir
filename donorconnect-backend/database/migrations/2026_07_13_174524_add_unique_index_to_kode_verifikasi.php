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
        Schema::table('donor_candidates', function (Blueprint $table) {
            // MySQL/MariaDB allow multiple NULLs through a unique index, so
            // candidates that never confirmed (kode_verifikasi still null)
            // are unaffected — this only guarantees uniqueness once a code
            // is actually generated.
            $table->unique('kode_verifikasi');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('donor_candidates', function (Blueprint $table) {
            $table->dropUnique(['kode_verifikasi']);
        });
    }
};
