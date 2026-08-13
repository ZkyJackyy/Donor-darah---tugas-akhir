<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Pengajuan keluarga yang disetujui admin butuh jeda sebelum masuk
     * 'open' — admin masih perlu memutuskan lanjut siarkan WA atau tandai
     * terpenuhi dari stok PMI. Tanpa status ini, approve() langsung ke
     * 'open' membuat request "aktif dicari" walau admin belum memilih aksi
     * apa pun.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('blood_requests', function (Blueprint $table) {
                $table->enum('status', ['pending_review', 'approved', 'open', 'fulfilled', 'cancelled', 'rejected'])
                    ->default('open')
                    ->change();
            });

            return;
        }

        DB::statement("ALTER TABLE blood_requests MODIFY COLUMN status ENUM('pending_review', 'approved', 'open', 'fulfilled', 'cancelled', 'rejected') NOT NULL DEFAULT 'open'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('blood_requests', function (Blueprint $table) {
                $table->enum('status', ['pending_review', 'open', 'fulfilled', 'cancelled', 'rejected'])
                    ->default('open')
                    ->change();
            });

            return;
        }

        DB::statement("ALTER TABLE blood_requests MODIFY COLUMN status ENUM('pending_review', 'open', 'fulfilled', 'cancelled', 'rejected') NOT NULL DEFAULT 'open'");
    }
};
