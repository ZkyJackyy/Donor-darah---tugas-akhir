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
     * Kandidat dari event donor terbuka tidak melalui perhitungan jarak
     * (tidak ada wave), jadi distance_km harus boleh null untuk mereka.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('donor_candidates', function (Blueprint $table) {
                $table->decimal('distance_km', 8, 2)->nullable()->change();
            });

            return;
        }

        DB::statement('ALTER TABLE donor_candidates MODIFY distance_km DECIMAL(8, 2) NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('donor_candidates', function (Blueprint $table) {
                $table->decimal('distance_km', 8, 2)->nullable(false)->change();
            });

            return;
        }

        DB::statement('ALTER TABLE donor_candidates MODIFY distance_km DECIMAL(8, 2) NOT NULL');
    }
};
