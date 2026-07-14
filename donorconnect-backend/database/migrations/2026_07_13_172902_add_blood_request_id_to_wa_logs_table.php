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
        Schema::table('wa_logs', function (Blueprint $table) {
            $table->foreignId('blood_request_id')->nullable()->after('user_id')
                ->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wa_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('blood_request_id');
        });
    }
};
