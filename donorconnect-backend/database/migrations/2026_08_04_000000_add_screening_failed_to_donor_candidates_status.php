<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Menambahkan status 'screening_failed' ke ENUM donor_candidates.status
     * (candidate tidak memenuhi syarat skrining mandiri, tapi tetap tercatat).
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('donor_candidates', function (Blueprint $table) {
                $table->enum('status', ['pending', 'notified', 'screening_passed', 'screening_failed', 'confirmed', 'declined', 'verified', 'no_response'])
                    ->default('pending')
                    ->change();
            });

            return;
        }

        DB::statement("ALTER TABLE donor_candidates MODIFY COLUMN status ENUM('pending', 'notified', 'screening_passed', 'screening_failed', 'confirmed', 'declined', 'verified', 'no_response') NOT NULL DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('donor_candidates', function (Blueprint $table) {
                $table->enum('status', ['pending', 'notified', 'screening_passed', 'confirmed', 'declined', 'verified', 'no_response'])
                    ->default('pending')
                    ->change();
            });

            return;
        }

        DB::statement("ALTER TABLE donor_candidates MODIFY COLUMN status ENUM('pending', 'notified', 'screening_passed', 'confirmed', 'declined', 'verified', 'no_response') NOT NULL DEFAULT 'pending'");
    }
};
