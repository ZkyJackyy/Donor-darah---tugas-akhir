<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Menambahkan status 'screening_passed' ke ENUM donor_candidates.status
     */
    public function up(): void
    {
        // MySQL tidak mendukung ALTER TABLE CHANGE ENUM secara langsung dengan Blueprint.
        // Gunakan raw SQL statement.
        DB::statement("ALTER TABLE donor_candidates MODIFY COLUMN status ENUM('pending', 'notified', 'screening_passed', 'confirmed', 'declined', 'verified', 'no_response') NOT NULL DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE donor_candidates MODIFY COLUMN status ENUM('pending', 'notified', 'confirmed', 'declined', 'verified', 'no_response') NOT NULL DEFAULT 'pending'");
    }
};
