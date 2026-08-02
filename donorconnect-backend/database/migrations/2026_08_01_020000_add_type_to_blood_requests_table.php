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
        Schema::table('blood_requests', function (Blueprint $table) {
            $table->enum('type', ['emergency', 'event'])->default('emergency')->after('admin_id');
        });

        // 'event' requests don't target a specific blood type/rhesus and don't
        // enforce a hard quota, so these columns must accept null for them.
        if (DB::getDriverName() === 'sqlite') {
            // SQLite tidak punya ALTER TABLE MODIFY COLUMN; Laravel merekonstruksi
            // tabel di belakang layar untuk mengubah constraint milik enum()/nullable()
            // (pola sama seperti add_screening_passed_to_donor_candidates_status.php).
            Schema::table('blood_requests', function (Blueprint $table) {
                $table->enum('blood_type', ['A', 'B', 'AB', 'O'])->nullable()->change();
                $table->enum('rhesus', ['+', '-'])->nullable()->change();
                $table->integer('required_bags')->nullable()->change();
            });

            return;
        }

        // MySQL tidak mendukung ALTER TABLE CHANGE ENUM secara langsung dengan Blueprint.
        DB::statement("ALTER TABLE blood_requests MODIFY blood_type ENUM('A', 'B', 'AB', 'O') NULL");
        DB::statement("ALTER TABLE blood_requests MODIFY rhesus ENUM('+', '-') NULL");
        DB::statement("ALTER TABLE blood_requests MODIFY required_bags INT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('blood_requests', function (Blueprint $table) {
                $table->enum('blood_type', ['A', 'B', 'AB', 'O'])->nullable(false)->change();
                $table->enum('rhesus', ['+', '-'])->nullable(false)->change();
                $table->integer('required_bags')->nullable(false)->change();
            });
        } else {
            DB::statement("ALTER TABLE blood_requests MODIFY blood_type ENUM('A', 'B', 'AB', 'O') NOT NULL");
            DB::statement("ALTER TABLE blood_requests MODIFY rhesus ENUM('+', '-') NOT NULL");
            DB::statement("ALTER TABLE blood_requests MODIFY required_bags INT NOT NULL");
        }

        Schema::table('blood_requests', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
