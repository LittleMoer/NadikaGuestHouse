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
        Schema::create('kamar', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_kamar')->unique(); // Nomor kamar
            $table->string('tipe'); // Tipe kamar (single, double, suite, dll)
            $table->integer('kapasitas')->default(1); // Kapasitas tamu
            $table->decimal('harga', 10, 2); // Harga per malam
            $table->string('status')->default('tersedia'); // tersedia, terisi, perawatan
            $table->text('deskripsi')->nullable(); // Deskripsi kamar
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kamar');
    }
};
