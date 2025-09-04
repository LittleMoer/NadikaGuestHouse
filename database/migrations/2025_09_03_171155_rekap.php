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
        Schema::create('rekap', function (Blueprint $table) {
            $table->id();
            $table->date('periode_awal'); // Tanggal awal periode rekap
            $table->date('periode_akhir'); // Tanggal akhir periode rekap
            $table->integer('jumlah_booking')->default(0); // Jumlah booking
            $table->integer('jumlah_tamu')->default(0); // Jumlah tamu
            $table->decimal('total_pendapatan', 12, 2)->default(0); // Total pendapatan
            $table->string('tipe_rekap')->default('bulanan'); // bulanan, mingguan
            $table->text('catatan')->nullable(); // Catatan tambahan
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rekap');
    }
};
