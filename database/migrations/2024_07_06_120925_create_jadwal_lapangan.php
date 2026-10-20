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
        Schema::create('jadwal_lapangan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lapangan_id')->constrained('lapangan')->onDelete('cascade');
            $table->foreignId('court_id')->constrained('courts')->onDelete('cascade');
            $table->time('jam_awal');
            $table->time('jam_akhir');
            $table->date('tanggal');
            $table->enum('status', ['ready', 'booking', 'closed'])->default('ready');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jadwal_lapangan');
    }
};
