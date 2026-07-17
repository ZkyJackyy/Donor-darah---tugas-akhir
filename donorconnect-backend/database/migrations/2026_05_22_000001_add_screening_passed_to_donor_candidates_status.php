<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Menambahkan status 'screening_passed' ke ENUM donor_candidates.status
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            // SQLite tidak punya ALTER TABLE MODIFY COLUMN; Laravel merekonstruksi
            // tabel di belakang layar untuk mengubah constraint CHECK milik enum().
            Schema::table('donor_candidates', function (Blueprint $table) {
                $table->enum('status', ['pending', 'notified', 'screening_passed', 'confirmed', 'declined', 'verified', 'no_response'])
                    ->default('pending')
                    ->change();
            });

            return;
        }

        // MySQL tidak mendukung ALTER TABLE CHANGE ENUM secara langsung dengan Blueprint.
        // Gunakan raw SQL statement.
        DB::statement("ALTER TABLE donor_candidates MODIFY COLUMN status ENUM('pending', 'notified', 'screening_passed', 'confirmed', 'declined', 'verified', 'no_response') NOT NULL DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('donor_candidates', function (Blueprint $table) {
                $table->enum('status', ['pending', 'notified', 'confirmed', 'declined', 'verified', 'no_response'])
                    ->default('pending')
                    ->change();
            });

            return;
        }

        DB::statement("ALTER TABLE donor_candidates MODIFY COLUMN status ENUM('pending', 'notified', 'confirmed', 'declined', 'verified', 'no_response') NOT NULL DEFAULT 'pending'");
    }
};
