<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('booking', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pelanggan_id')->nullable()->index();
            $table->dateTime('tanggal_checkin');
            $table->dateTime('tanggal_checkout');
            $table->unsignedTinyInteger('status')->default(1); // 1=dipesan,2=checkin,3=checkout,4=cancel
            $table->string('payment_status', 20)->default('dp'); // dp, lunas, dp_cancel
            $table->string('payment_method', 20)->nullable(); // cash, transfer, qris, card
            $table->unsignedTinyInteger('pemesanan')->default(0); // 0=walkin,1=traveloka,2=agent1,3=agent2
            $table->text('catatan')->nullable();
            $table->unsignedBigInteger('total_harga')->default(0);
            $table->unsignedInteger('jumlah_tamu_total')->default(1);
            $table->unsignedBigInteger('total_cafe')->nullable();
            // legacy support
            $table->unsignedTinyInteger('dp_percentage')->nullable();
            // new fields
            $table->unsignedBigInteger('dp_amount')->nullable();
            $table->boolean('discount_review')->default(false);
            $table->boolean('discount_follow')->default(false);
            $table->enum('extra_time', ['none','half','sixth'])->default('none');
            $table->boolean('per_head_mode')->default(false);
            $table->unsignedBigInteger('diskon')->nullable();
            // biaya tambahan lainnya di luar kamar dan cafe
            $table->unsignedBigInteger('biaya_tambahan')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('booking');
    }
};

