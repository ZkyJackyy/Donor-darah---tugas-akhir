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
        Schema::create('donor_screenings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('donor_candidate_id')->constrained('donor_candidates')->cascadeOnDelete();
            $table->boolean('health_status')->default(false);
            $table->boolean('min_weight')->default(false);
            $table->boolean('no_medicine')->default(false);
            $table->boolean('not_pregnant')->default(false);
            $table->timestamp('screened_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('donor_screenings');
    }
};
