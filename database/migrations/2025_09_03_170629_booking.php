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
        Schema::create('booking', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pelanggan_id')->constrained('pelanggan')->onDelete('cascade'); // ID pelanggan
            $table->foreignId('kamar_id')->constrained('kamar')->onDelete('cascade'); // ID kamar
            $table->dateTime('tanggal_checkin'); // Tanggal check-in
            $table->dateTime('tanggal_checkout'); // Tanggal check-out
            $table->integer('jumlah_tamu')->default(1); // Jumlah tamu
            $table->string('status')->default('dipesan'); // dipesan, checkin, checkout, dibatalkan
            $table->text('catatan')->nullable(); // Catatan tambahan
            $table->decimal('total_harga', 12, 2)->nullable(); // Total harga
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking');
    }
};
