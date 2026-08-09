<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Peringatan sistem untuk admin (mis. semua kandidat donor menolak)
     * ditampilkan di halaman web ini, bukan lewat WA — nomor admin tidak
     * selalu terisi, dan ini bukan pesan yang perlu langsung dilihat di HP.
     */
    public function up(): void
    {
        Schema::create('admin_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->text('message');
            $table->foreignId('blood_request_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            // Two donors declining a request near-simultaneously can both pass
            // the "already alerted?" existence check before either insert
            // commits — this constraint makes the second insert fail cleanly
            // instead of producing a duplicate alert.
            $table->unique(['blood_request_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_alerts');
    }
};
