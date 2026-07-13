<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('donor_candidates', function (Blueprint $table) {
            $table->string('kode_verifikasi', 6)->nullable()->after('qr_token');
        });
    }

    public function down(): void
    {
        Schema::table('donor_candidates', function (Blueprint $table) {
            $table->dropColumn('kode_verifikasi');
        });
    }
};
