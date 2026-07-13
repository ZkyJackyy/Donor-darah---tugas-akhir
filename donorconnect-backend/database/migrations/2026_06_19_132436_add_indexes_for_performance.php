<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Indexes for users table
        Schema::table('users', function (Blueprint $table) {
            $table->index('blood_type');
            $table->index('rhesus');
            $table->index('is_available');
            $table->index(['latitude', 'longitude']);
            $table->index('last_donor_date');
        });

        // Indexes for blood_requests table
        Schema::table('blood_requests', function (Blueprint $table) {
            $table->index('status');
            $table->index('urgency_level');
            $table->index('blood_type');
            $table->index('rhesus');
            $table->index('deadline');
        });

        // Indexes for donor_candidates table
        Schema::table('donor_candidates', function (Blueprint $table) {
            $table->index('status');
            $table->index('user_id');
            $table->index('blood_request_id');
            $table->index(['blood_request_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['blood_type']);
            $table->dropIndex(['rhesus']);
            $table->dropIndex(['is_available']);
            $table->dropIndex(['latitude', 'longitude']);
            $table->dropIndex(['last_donor_date']);
        });

        Schema::table('blood_requests', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['urgency_level']);
            $table->dropIndex(['blood_type']);
            $table->dropIndex(['rhesus']);
            $table->dropIndex(['deadline']);
        });

        Schema::table('donor_candidates', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['user_id']);
            $table->dropIndex(['blood_request_id']);
            $table->dropIndex(['blood_request_id', 'status']);
        });
    }
};
