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
        Schema::create('lapangan', function (Blueprint $table) {
            $table->id();
            $table->string('nama_lapangan', 155);
            $table->text('alamat');
            $table->text('deskripsi')->nullable();
            $table->string('image')->nullable();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Adding user_id with foreign key constraint
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lapangan');
    }
};
