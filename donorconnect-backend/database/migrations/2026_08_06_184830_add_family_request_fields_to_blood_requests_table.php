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
     * Menambahkan dukungan untuk user mengajukan permintaan donor pengganti
     * (untuk keluarga) yang divalidasi admin sebelum masuk alur pencarian
     * pendonor yang sudah ada. admin_id jadi nullable karena pengajuan
     * 'pending_review' belum dimiliki admin manapun sampai disetujui.
     */
    public function up(): void
    {
        Schema::table('blood_requests', function (Blueprint $table) {
            $table->foreignId('requested_by_user_id')->nullable()->after('admin_id')
                ->constrained('users')->nullOnDelete();
            $table->string('patient_name')->nullable()->after('notes');
            $table->string('patient_relationship')->nullable()->after('patient_name');
            $table->text('rejection_reason')->nullable()->after('patient_relationship');
        });

        if (DB::getDriverName() === 'sqlite') {
            Schema::table('blood_requests', function (Blueprint $table) {
                $table->foreignId('admin_id')->nullable()->change();
                $table->enum('status', ['pending_review', 'open', 'fulfilled', 'cancelled', 'rejected'])
                    ->default('open')
                    ->change();
            });

            return;
        }

        DB::statement("ALTER TABLE blood_requests MODIFY admin_id BIGINT UNSIGNED NULL");
        DB::statement("ALTER TABLE blood_requests MODIFY COLUMN status ENUM('pending_review', 'open', 'fulfilled', 'cancelled', 'rejected') NOT NULL DEFAULT 'open'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('blood_requests', function (Blueprint $table) {
            $table->dropForeign(['requested_by_user_id']);
            $table->dropColumn(['requested_by_user_id', 'patient_name', 'patient_relationship', 'rejection_reason']);
        });

        if (DB::getDriverName() === 'sqlite') {
            Schema::table('blood_requests', function (Blueprint $table) {
                $table->enum('status', ['open', 'fulfilled', 'cancelled'])->default('open')->change();
                $table->foreignId('admin_id')->nullable(false)->change();
            });

            return;
        }

        DB::statement("ALTER TABLE blood_requests MODIFY COLUMN status ENUM('open', 'fulfilled', 'cancelled') NOT NULL DEFAULT 'open'");
        DB::statement("ALTER TABLE blood_requests MODIFY admin_id BIGINT UNSIGNED NOT NULL");
    }
};
