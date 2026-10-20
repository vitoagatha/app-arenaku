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
        Schema::create('konfirmasi_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jadwal_lapangan_id')->constrained('jadwal_lapangan')->onDelete('cascade');
            $table->string('nama');
            $table->string('no_whatsapp');
            $table->integer('durasi')->nullable();
            $table->enum('status', ['pending', 'confirmed', 'canceled'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('konfirmasi_bookings');
    }
};
