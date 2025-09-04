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
        Schema::create('pelanggan', function (Blueprint $table) {
            $table->id();
            $table->string('nama'); // Nama pelanggan
            $table->string('email')->unique()->nullable(); // Email pelanggan
            $table->string('telepon')->nullable(); // Nomor telepon
            $table->string('alamat')->nullable(); // Alamat pelanggan
            $table->string('jenis_identitas')->nullable(); // Jenis identitas (KTP, SIM, Paspor, dll)
            $table->string('nomor_identitas')->nullable(); // Nomor identitas
            $table->string('tempat_lahir')->nullable(); // Tempat lahir
            $table->date('tanggal_lahir')->nullable(); // Tanggal lahir
            $table->string('kewarganegaraan')->nullable(); // Kewarganegaraan
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pelanggan');
    }
};
