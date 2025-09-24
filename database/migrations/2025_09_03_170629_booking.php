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
            $table->foreignId('pelanggan_id')->constrained('pelanggan')->onDelete('cascade');
            $table->dateTime('tanggal_checkin');
            $table->dateTime('tanggal_checkout');
            $table->integer('jumlah_tamu_total')->default(1); // total tamu (jika melibatkan banyak kamar)
            $table->integer('status')->default(1); // 1 dipesan,2 checkin,3 checkout,4 dibatalkan
            $table->integer('pemesanan')->default(0); // 0 walk-in,1 online
            $table->text('catatan')->nullable();
            $table->decimal('total_harga',12,2)->default(0);
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
